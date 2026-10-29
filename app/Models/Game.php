<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Class Game
 * Partidas (tabela matches), agendadas pelo dono da quadra no site (D02).
 * Gravado só scheduled | cancelled; "em andamento" e "terminada" saem do horário.
 */
class Game
{
    /**
     * Partidas agendadas (não canceladas) de várias quadras que tocam um dia.
     * @param int[]  $venueIds
     * @param string $date 'Y-m-d' (horário de Brasília)
     * @return array [venue_id => [['start_time' => ..., 'end_time' => ...], ...]]
     */
    public static function findScheduledOn(array $venueIds, string $date): array
    {
        if (!$venueIds) {
            return [];
        }

        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($venueIds), '?'));
        $stmt = $pdo->prepare("SELECT venue_id, start_time, end_time
                               FROM matches
                               WHERE venue_id IN ($placeholders)
                                 AND status = 'scheduled'
                                 AND deleted_at IS NULL
                                 AND start_time < ? + INTERVAL 1 DAY
                                 AND end_time > ?
                               ORDER BY start_time");
        $stmt->execute([...array_map('intval', $venueIds), $date, $date]);

        $matches = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $matches[(int)$row['venue_id']][] = [
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time']
            ];
        }
        return $matches;
    }

    /**
     * Partidas agendadas de uma quadra com os detalhes que só o site mostra
     * (para quem foi alugado, esporte), que terminam depois de $from e começam antes de $to.
     * @param string      $from 'Y-m-d H:i:s'
     * @param string|null $to   'Y-m-d H:i:s'; null = sem limite (todas as futuras)
     */
    public static function findDetailedBetween(int $venueId, string $from, ?string $to = null): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT m.id, m.start_time, m.end_time, m.renter_name, m.sport_id, s.name AS sport_name
                FROM matches m
                LEFT JOIN sports s ON s.id = m.sport_id
                WHERE m.venue_id = ?
                  AND m.status = 'scheduled'
                  AND m.deleted_at IS NULL
                  AND m.end_time > ?";
        $params = [$venueId, $from];
        if ($to !== null) {
            $sql .= " AND m.start_time < ?";
            $params[] = $to;
        }
        $stmt = $pdo->prepare($sql . " ORDER BY m.start_time");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Agenda uma partida. Validar antes com VenueSchedule::validateMatch.
     * @param array $data venue_id, creator_user_id, start_time, end_time ('Y-m-d H:i:s'),
     *                    sport_id (opcional, D25), renter_name (opcional)
     * @return int|false id da partida criada
     */
    public static function create(array $data)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO matches (venue_id, sport_id, creator_user_id, renter_name, status, start_time, end_time)
                               VALUES (?, ?, ?, ?, 'scheduled', ?, ?)");
        $renter = trim((string)($data['renter_name'] ?? ''));
        $ok = $stmt->execute([
            (int)$data['venue_id'],
            !empty($data['sport_id']) ? (int)$data['sport_id'] : null,
            (int)$data['creator_user_id'],
            $renter !== '' ? mb_substr($renter, 0, 100) : null,
            $data['start_time'],
            $data['end_time']
        ]);
        return $ok ? (int)$pdo->lastInsertId() : false;
    }

    /**
     * Cancela uma partida agendada, só se ela for da quadra informada.
     * @return bool true se alguma partida foi cancelada
     */
    public static function cancel(int $id, int $venueId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE matches SET status = 'cancelled'
                               WHERE id = ? AND venue_id = ? AND status = 'scheduled' AND deleted_at IS NULL");
        $stmt->execute([$id, $venueId]);
        return $stmt->rowCount() > 0;
    }

    public static function findById(int $id)
    {
        $pdo = Database::getConnection();
        $query = "SELECT * FROM matches WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        return $match;
    }
}
