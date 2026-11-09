<?php

namespace App\Models;

use App\Core\ChatRules;
use App\Core\Database;
use PDO;

/**
 * Mensagens do chat (D66), tabela messages (patch 010). O texto já chega limpo e validado
 * (ChatRules::cleanBody). Mensagens de conta inativa não aparecem.
 */
class Message
{
    /**
     * Grava a mensagem, atualiza a conversa (última mensagem) e marca como lida para quem enviou.
     * @return array a mensagem criada (id, conversation_id, sender_id, body, created_at)
     */
    public static function create(array $conversation, int $senderId, string $side, string $body): array
    {
        $pdo = Database::getConnection();
        $column = $side === Conversation::SIDE_OWNER ? 'owner_last_read_id' : 'user_last_read_id';
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)')
                ->execute([(int)$conversation['id'], $senderId, $body]);
            $id = (int)$pdo->lastInsertId();
            $pdo->prepare("UPDATE conversations SET last_message_at = NOW(), $column = GREATEST(IFNULL($column, 0), ?) WHERE id = ?")
                ->execute([$id, (int)$conversation['id']]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return self::findById($id);
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT id, conversation_id, sender_id, body, created_at FROM messages WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Mensagens de uma conversa, sempre em ordem crescente de id.
     * after: só as mais novas (polling); before: as mais antigas (rolar para cima);
     * nenhum: as $perPage mais recentes.
     */
    public static function list(int $conversationId, ?int $after, ?int $before, int $perPage): array
    {
        $perPage = max(1, min(ChatRules::PER_PAGE_MAX, $perPage));
        $sql = "SELECT m.id, m.conversation_id, m.sender_id, m.body, m.created_at
                  FROM messages m
                  JOIN users s ON s.id = m.sender_id AND s.status = 'active'
                 WHERE m.conversation_id = ?";
        $params = [$conversationId];
        if ($after !== null) {
            $stmt = Database::getConnection()->prepare($sql . " AND m.id > ? ORDER BY m.id ASC LIMIT $perPage");
            $stmt->execute([$conversationId, $after]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($before !== null) {
            $sql .= ' AND m.id < ?';
            $params[] = $before;
        }
        $stmt = Database::getConnection()->prepare($sql . " ORDER BY m.id DESC LIMIT $perPage");
        $stmt->execute($params);
        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Quantas mensagens a pessoa enviou na janela do limite (em todas as conversas) e quando foi
     * a mais antiga delas, para o 429 e o Retry-After.
     * @return array{count: int, oldest: ?int} oldest em timestamp
     */
    public static function recentBy(int $senderId, int $windowSeconds = ChatRules::RATE_WINDOW): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT COUNT(*) AS n, UNIX_TIMESTAMP(MIN(created_at)) AS oldest
               FROM messages WHERE sender_id = ? AND created_at > NOW() - INTERVAL ? SECOND'
        );
        $stmt->execute([$senderId, $windowSeconds]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return ['count' => (int)($row['n'] ?? 0), 'oldest' => isset($row['oldest']) ? (int)$row['oldest'] : null];
    }

    /** O limite de envio (20 por minuto) foi atingido? Devolve os segundos de espera, ou null. */
    public static function waitSeconds(int $senderId): ?int
    {
        $recent = self::recentBy($senderId);
        if ($recent['count'] < ChatRules::RATE_LIMIT || $recent['oldest'] === null) {
            return null;
        }
        $now = (int)Database::getConnection()->query('SELECT UNIX_TIMESTAMP(NOW())')->fetchColumn();
        return ChatRules::retryAfter($recent['oldest'], $now);
    }
}
