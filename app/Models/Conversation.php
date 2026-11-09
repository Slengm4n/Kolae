<?php

namespace App\Models;

use App\Core\ChatRules;
use App\Core\Database;
use PDO;

/**
 * Conversas do chat (D66), tabela conversations (patch 010). Uma por pessoa e quadra; o
 * responsável é o dono da quadra (venues.user_id). "Lida" pela última mensagem lida de cada lado.
 *
 * Conta excluída no Kolae só fica inativa (User::delete); por isso as consultas escondem as
 * conversas e as mensagens de quem não está ativo (proposta-chat.md, "Privacidade").
 */
class Conversation
{
    public const SIDE_USER = 'user';
    public const SIDE_OWNER = 'owner';

    /** Conversa com a quadra e quem pergunta, para conferir quem pode ver. */
    private const SELECT = "SELECT c.*, v.name AS venue_name, v.user_id AS venue_owner_id, v.status AS venue_status,
                                   v.deleted_at AS venue_deleted_at, u.status AS user_status
                              FROM conversations c
                              JOIN venues v ON v.id = c.venue_id
                              JOIN users u ON u.id = c.user_id";

    public static function findById(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare(self::SELECT . ' WHERE c.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Abre a conversa da pessoa com a quadra, ou devolve a que já existe.
     * @return array{0: array, 1: bool} [conversa, true se acabou de ser criada]
     */
    public static function findOrCreate(int $venueId, int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT IGNORE INTO conversations (venue_id, user_id) VALUES (?, ?)');
        $stmt->execute([$venueId, $userId]);
        $created = $stmt->rowCount() === 1;
        $stmt = $pdo->prepare(self::SELECT . ' WHERE c.venue_id = ? AND c.user_id = ?');
        $stmt->execute([$venueId, $userId]);
        return [$stmt->fetch(PDO::FETCH_ASSOC), $created];
    }

    /**
     * De que lado da conversa a pessoa está: 'user' (quem pergunta), 'owner' (dono da quadra)
     * ou null (não faz parte: a API responde 404). O dono não vê conversa de conta inativa.
     */
    public static function side(array $conversation, int $userId): ?string
    {
        if ((int)$conversation['user_id'] === $userId) {
            return self::SIDE_USER;
        }
        if ((int)$conversation['venue_owner_id'] === $userId && ($conversation['user_status'] ?? '') === 'active') {
            return self::SIDE_OWNER;
        }
        return null;
    }

    /** A quadra ainda aceita mensagem? Desativada (D34) ou excluída: só leitura. */
    public static function venueAvailable(array $conversation): bool
    {
        return ($conversation['venue_status'] ?? '') !== 'unavailable' && empty($conversation['venue_deleted_at']);
    }

    /**
     * Conversas de quem pergunta (GET /conversations), a mais recente primeiro, sem as vazias.
     * Cada uma com a quadra, a capa, a última mensagem visível e as não lidas.
     */
    public static function forUser(int $userId): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT c.*, v.name AS venue_name,
                    (SELECT file_path FROM venues_images vi WHERE vi.venue_id = v.id ORDER BY vi.position ASC, vi.id ASC LIMIT 1) AS venue_cover
               FROM conversations c
               JOIN venues v ON v.id = c.venue_id
              WHERE c.user_id = ? AND c.last_message_at IS NOT NULL
              ORDER BY c.last_message_at DESC, c.id DESC"
        );
        $stmt->execute([$userId]);
        return self::withLastAndUnread($stmt->fetchAll(PDO::FETCH_ASSOC), $userId, self::SIDE_USER);
    }

    /**
     * Conversas das quadras do dono (painel), a mais recente primeiro, sem as vazias e sem as
     * de conta inativa. $venueId filtra uma quadra. Com o nome e a foto de quem pergunta (nunca
     * o e-mail).
     */
    public static function forOwner(int $ownerId, ?int $venueId = null): array
    {
        $sql = "SELECT c.*, v.name AS venue_name, v.status AS venue_status, v.deleted_at AS venue_deleted_at,
                       u.name AS user_name, u.avatar_path AS user_avatar_path
                  FROM conversations c
                  JOIN venues v ON v.id = c.venue_id
                  JOIN users u ON u.id = c.user_id
                 WHERE v.user_id = ? AND v.deleted_at IS NULL AND u.status = 'active' AND c.last_message_at IS NOT NULL";
        $params = [$ownerId];
        if ($venueId !== null) {
            $sql .= ' AND c.venue_id = ?';
            $params[] = $venueId;
        }
        $stmt = Database::getConnection()->prepare($sql . ' ORDER BY c.last_message_at DESC, c.id DESC');
        $stmt->execute($params);
        return self::withLastAndUnread($stmt->fetchAll(PDO::FETCH_ASSOC), $ownerId, self::SIDE_OWNER);
    }

    /** Total de mensagens não lidas do dono em todas as quadras (número no menu "Mensagens"). */
    public static function unreadForOwner(int $ownerId): int
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT COUNT(*)
               FROM messages m
               JOIN conversations c ON c.id = m.conversation_id
               JOIN venues v ON v.id = c.venue_id
               JOIN users u ON u.id = c.user_id
               JOIN users s ON s.id = m.sender_id
              WHERE v.user_id = ? AND v.deleted_at IS NULL AND u.status = 'active' AND s.status = 'active'
                AND m.sender_id <> ? AND m.id > IFNULL(c.owner_last_read_id, 0)"
        );
        $stmt->execute([$ownerId, $ownerId]);
        return (int)$stmt->fetchColumn();
    }

    /** Não lidas de um lado numa conversa: as do outro lado acima da última lida. */
    public static function unreadCount(array $conversation, int $userId, string $side): int
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT COUNT(*) FROM messages m JOIN users s ON s.id = m.sender_id AND s.status = 'active'
              WHERE m.conversation_id = ? AND m.sender_id <> ? AND m.id > ?"
        );
        $stmt->execute([(int)$conversation['id'], $userId, (int)($conversation[$side . '_last_read_id'] ?? 0)]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Marca como lidas até $messageId (só avança; maior que a última vale como a última).
     * @return ?int a nova "última lida" daquele lado
     */
    public static function markRead(array $conversation, string $side, int $messageId): ?int
    {
        $column = $side === self::SIDE_OWNER ? 'owner_last_read_id' : 'user_last_read_id';
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT MAX(id) FROM messages WHERE conversation_id = ?');
        $stmt->execute([(int)$conversation['id']]);
        $last = $stmt->fetchColumn();
        $current = $conversation[$column] !== null ? (int)$conversation[$column] : null;
        $next = ChatRules::nextReadPointer($current, $messageId, $last !== null ? (int)$last : null);
        if ($next !== null && $next !== $current) {
            // GREATEST: duas abas lendo ao mesmo tempo nunca fazem o ponteiro voltar
            $pdo->prepare("UPDATE conversations SET $column = GREATEST(IFNULL($column, 0), ?) WHERE id = ?")
                ->execute([$next, (int)$conversation['id']]);
        }
        return $next;
    }

    /** Bloquear (a pessoa lê, mas não envia) ou desbloquear. Só o dono, no painel. */
    public static function setBlocked(int $id, bool $blocked): bool
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE conversations SET blocked_at = ' . ($blocked ? 'IFNULL(blocked_at, NOW())' : 'NULL') . ' WHERE id = ?'
        );
        return $stmt->execute([$id]);
    }

    // --- Ajudantes ---

    /** Junta a última mensagem visível e as não lidas de cada conversa (uma consulta de cada). */
    private static function withLastAndUnread(array $rows, int $userId, string $side): array
    {
        if (!$rows) {
            return [];
        }
        $ids = array_map(fn($r) => (int)$r['id'], $rows);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare(
            "SELECT m.id, m.conversation_id, m.sender_id, m.body, m.created_at
               FROM messages m
               JOIN users s ON s.id = m.sender_id AND s.status = 'active'
              WHERE m.id IN (SELECT MAX(m2.id) FROM messages m2 JOIN users s2 ON s2.id = m2.sender_id AND s2.status = 'active'
                              WHERE m2.conversation_id IN ($in) GROUP BY m2.conversation_id)"
        );
        $stmt->execute($ids);
        $last = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $last[(int)$m['conversation_id']] = $m;
        }

        $readColumn = $side === self::SIDE_OWNER ? 'owner_last_read_id' : 'user_last_read_id';
        $stmt = $pdo->prepare(
            "SELECT m.conversation_id, COUNT(*) AS n
               FROM messages m
               JOIN conversations c ON c.id = m.conversation_id
               JOIN users s ON s.id = m.sender_id AND s.status = 'active'
              WHERE m.conversation_id IN ($in) AND m.sender_id <> ? AND m.id > IFNULL(c.$readColumn, 0)
              GROUP BY m.conversation_id"
        );
        $stmt->execute(array_merge($ids, [$userId]));
        $unread = array_map('intval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));

        $out = [];
        foreach ($rows as $r) {
            $id = (int)$r['id'];
            if (!isset($last[$id])) {
                continue; // só mensagens de conta inativa: fica como vazia
            }
            $r['last_message'] = $last[$id];
            $r['unread_count'] = $unread[$id] ?? 0;
            $out[] = $r;
        }
        return $out;
    }
}
