<?php

namespace App\Models;

use App\Core\Database;
use App\Core\MatchRequests;
use App\Core\VenueSchedule;
use DateTimeImmutable;
use PDO;

/**
 * Pedidos de horário (D64), tabela match_requests (patch 009). As regras ficam em
 * App\Core\MatchRequests; aqui ficam o banco e o fluxo: pedir, aprovar (vira partida), recusar,
 * cancelar e expirar, cada um avisando o outro lado (notifications).
 *
 * Expirar não depende de agendador (a HostGator compartilhada não garante cron): os pedidos
 * pendentes que já passaram do início expiram na próxima leitura (expireDue).
 */
class MatchRequest
{
    /** Pedido com a quadra, o dono e quem pediu (para conferir quem pode ver e montar os avisos). */
    private const SELECT = "SELECT r.*, v.name AS venue_name, v.user_id AS venue_owner_id, v.status AS venue_status,
                                   v.deleted_at AS venue_deleted_at, u.name AS user_name, u.status AS user_status,
                                   u.whatsapp AS user_whatsapp, s.name AS sport_name,
                                   (SELECT file_path FROM venues_images vi WHERE vi.venue_id = v.id ORDER BY vi.position ASC, vi.id ASC LIMIT 1) AS venue_cover
                              FROM match_requests r
                              JOIN venues v ON v.id = r.venue_id
                              JOIN users u ON u.id = r.user_id
                              LEFT JOIN sports s ON s.id = r.sport_id";

    public static function findById(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare(self::SELECT . ' WHERE r.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Pendentes da pessoa (em todas as quadras) e nesta quadra, para o limite. */
    public static function pendingCounts(int $userId, int $venueId): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT COUNT(*) AS total, SUM(venue_id = ?) AS here FROM match_requests WHERE user_id = ? AND status = 'pending'"
        );
        $stmt->execute([$venueId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return ['user' => (int)($row['total'] ?? 0), 'venue' => (int)($row['here'] ?? 0)];
    }

    /**
     * Grava o pedido (os valores já validados por MatchRequests::validate) e avisa o dono.
     * @return array o pedido criado (formato de findById)
     */
    public static function create(array $venue, array $user, array $values): array
    {
        $pdo = Database::getConnection();
        $pdo->prepare('INSERT INTO match_requests (venue_id, user_id, sport_id, start_time, end_time, message) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([(int)$venue['id'], (int)$user['id'], $values['sport_id'], $values['start_time'], $values['end_time'], $values['message']]);
        $request = self::findById((int)$pdo->lastInsertId());
        self::notify((int)$venue['user_id'], 'request_new', $request);
        return $request;
    }

    /**
     * Pedidos de quem pediu (GET /me/requests), os mais recentes primeiro.
     * @return array{items: array, total: int}
     */
    public static function forUser(int $userId, ?string $status, int $page, int $perPage): array
    {
        self::expireDue();
        $where = 'r.user_id = ?';
        $params = [$userId];
        if ($status !== null) {
            $where .= ' AND r.status = ?';
            $params[] = $status;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM match_requests r WHERE $where");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare(self::SELECT . " WHERE $where ORDER BY r.created_at DESC, r.id DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
        return ['items' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total];
    }

    /**
     * Pedidos das quadras do dono (painel): os pendentes, do horário mais próximo para o mais
     * distante, e o histórico dos últimos 30 dias. Conta inativa fica de fora.
     * @return array{pending: array, history: array}
     */
    public static function forOwner(int $ownerId, ?int $venueId = null): array
    {
        self::expireDue();
        $where = "v.user_id = ? AND v.deleted_at IS NULL AND u.status = 'active'";
        $params = [$ownerId];
        if ($venueId !== null) {
            $where .= ' AND r.venue_id = ?';
            $params[] = $venueId;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(self::SELECT . " WHERE $where AND r.status = 'pending' ORDER BY r.start_time ASC, r.id ASC");
        $stmt->execute($params);
        $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare(self::SELECT . " WHERE $where AND r.status <> 'pending'
                                AND IFNULL(r.responded_at, r.created_at) > NOW() - INTERVAL 30 DAY
                              ORDER BY IFNULL(r.responded_at, r.created_at) DESC, r.id DESC LIMIT 50");
        $stmt->execute($params);
        return ['pending' => $pending, 'history' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /** Pedidos pendentes das quadras do dono (número na aba Solicitações). */
    public static function pendingForOwner(int $ownerId): int
    {
        self::expireDue();
        $stmt = Database::getConnection()->prepare(
            "SELECT COUNT(*) FROM match_requests r JOIN venues v ON v.id = r.venue_id JOIN users u ON u.id = r.user_id
              WHERE v.user_id = ? AND v.deleted_at IS NULL AND u.status = 'active' AND r.status = 'pending'"
        );
        $stmt->execute([$ownerId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Aprova: confere o horário de novo (funcionamento e conflito), cria a partida com o primeiro
     * nome de quem pediu e recusa sozinho os outros pendentes da quadra no mesmo horário.
     * @return array{ok: bool, error: ?string, match_id: ?int, declined: int[]}
     *   error: 'not_pending' | 'closed_day' | 'outside_hours' | 'overlap' | 'in_past' | 'failed'
     */
    public static function approve(array $request, int $ownerId, ?DateTimeImmutable $now = null): array
    {
        $now = $now ?? VenueSchedule::now();
        $result = ['ok' => false, 'error' => null, 'match_id' => null, 'declined' => []];
        if (($request['status'] ?? '') !== 'pending' || MatchRequests::isDue($request, $now)) {
            return ['error' => 'not_pending'] + $result;
        }
        $venueId = (int)$request['venue_id'];
        $start = new DateTimeImmutable($request['start_time'], VenueSchedule::timezone());
        $end = new DateTimeImmutable($request['end_time'], VenueSchedule::timezone());
        $hours = VenueHour::findByVenueId($venueId);
        $sameDay = Game::findScheduledOn([$venueId], $start->format('Y-m-d'))[$venueId] ?? [];
        $check = VenueSchedule::validateMatch($hours, $sameDay, $start, $end, $now);
        if ($check !== null) {
            return ['error' => $check] + $result;
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            // Só um aprova: a condição de status no UPDATE segura dois cliques ao mesmo tempo
            $stmt = $pdo->prepare("UPDATE match_requests SET status = 'approved', responded_at = NOW() WHERE id = ? AND status = 'pending'");
            $stmt->execute([(int)$request['id']]);
            if ($stmt->rowCount() !== 1) {
                $pdo->rollBack();
                return ['error' => 'not_pending'] + $result;
            }
            $matchId = Game::create([
                'venue_id' => $venueId,
                'creator_user_id' => $ownerId,
                'start_time' => $request['start_time'],
                'end_time' => $request['end_time'],
                'sport_id' => $request['sport_id'],
                'renter_name' => MatchRequests::firstName($request['user_name'] ?? ''),
            ]);
            if (!$matchId) {
                $pdo->rollBack();
                return ['error' => 'failed'] + $result;
            }
            $pdo->prepare('UPDATE match_requests SET match_id = ? WHERE id = ?')->execute([$matchId, (int)$request['id']]);

            // Os outros pendentes da quadra que batem com este horário são recusados
            $stmt = $pdo->prepare(self::SELECT . " WHERE r.venue_id = ? AND r.status = 'pending' AND r.id <> ?
                                     AND r.start_time < ? AND r.end_time > ?");
            $stmt->execute([$venueId, (int)$request['id'], $request['end_time'], $request['start_time']]);
            $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($conflicts as $other) {
                $done = $pdo->prepare("UPDATE match_requests SET status = 'declined', responded_at = NOW(), response_note = NULL WHERE id = ? AND status = 'pending'");
                $done->execute([(int)$other['id']]);
                if ($done->rowCount() === 1) {
                    $result['declined'][] = (int)$other['id'];
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('Pedidos: não aprovou o pedido ' . (int)$request['id'] . ': ' . $e->getMessage());
            return ['error' => 'failed'] + $result;
        }

        self::notify((int)$request['user_id'], 'request_approved', self::findById((int)$request['id']));
        foreach ($conflicts as $other) {
            if (in_array((int)$other['id'], $result['declined'], true)) {
                self::notify((int)$other['user_id'], 'request_declined', $other);
            }
        }
        return ['ok' => true, 'match_id' => (int)$matchId] + $result;
    }

    /** Recusa, com motivo opcional (já limpo por MatchRequests::cleanNote). */
    public static function decline(array $request, ?string $note): bool
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE match_requests SET status = 'declined', response_note = ?, responded_at = NOW() WHERE id = ? AND status = 'pending'"
        );
        $stmt->execute([$note, (int)$request['id']]);
        if ($stmt->rowCount() !== 1) {
            return false;
        }
        self::notify((int)$request['user_id'], 'request_declined', ['response_note' => $note] + $request);
        return true;
    }

    /**
     * O jogador cancela (MatchRequests::canCancel já conferido). Aprovado: a partida sai da agenda.
     * O dono é avisado nos dois casos.
     */
    public static function cancel(array $request, ?DateTimeImmutable $now = null): bool
    {
        $now = $now ?? VenueSchedule::now();
        if (!MatchRequests::canCancel($request, $now)) {
            return false;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE match_requests SET status = 'cancelled', responded_at = IFNULL(responded_at, NOW()) WHERE id = ? AND status = ?");
        $stmt->execute([(int)$request['id'], $request['status']]);
        if ($stmt->rowCount() !== 1) {
            return false;
        }
        if ($request['status'] === 'approved' && !empty($request['match_id'])) {
            Game::cancel((int)$request['match_id'], (int)$request['venue_id']);
        }
        self::notify((int)$request['venue_owner_id'], 'request_cancelled', $request);
        return true;
    }

    /**
     * Expira os pendentes que chegaram ao início do horário sem resposta e avisa quem pediu.
     * @return int quantos expiraram
     */
    public static function expireDue(?DateTimeImmutable $now = null): int
    {
        $now = $now ?? VenueSchedule::now();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(self::SELECT . " WHERE r.status = 'pending' AND r.start_time <= ?");
        $stmt->execute([$now->format('Y-m-d H:i:s')]);
        $count = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $request) {
            $done = $pdo->prepare("UPDATE match_requests SET status = 'expired', responded_at = NOW() WHERE id = ? AND status = 'pending'");
            $done->execute([(int)$request['id']]);
            if ($done->rowCount() === 1) {
                $count++;
                self::notify((int)$request['user_id'], 'request_expired', $request);
            }
        }
        return $count;
    }

    // --- Ajudantes ---

    /** Cria o aviso com o que o texto precisa (NotificationText). Falhar não desfaz o pedido. */
    private static function notify(int $userId, string $type, ?array $request): void
    {
        if (!$request) {
            return;
        }
        try {
            Notification::create($userId, $type, [
                'request_id' => (int)$request['id'],
                'venue_id' => (int)$request['venue_id'],
                'venue_name' => $request['venue_name'] ?? '',
                'user_name' => MatchRequests::firstName($request['user_name'] ?? ''),
                'start_time' => $request['start_time'],
                'end_time' => $request['end_time'],
                'response_note' => $request['response_note'] ?? null,
            ]);
        } catch (\Throwable $e) {
            error_log('Pedidos: não criou a notificação ' . $type . ': ' . $e->getMessage());
        }
    }
}
