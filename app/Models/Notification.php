<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Avisos do painel e do app (D64). O texto não fica no banco: `data` guarda ids e textos curtos
 * (JSON) e a mensagem é montada no idioma de quem lê (App\Core\NotificationText).
 */
class Notification
{
    public static function create(int $userId, string $type, array $data = []): bool
    {
        $stmt = Database::getConnection()->prepare(
            "INSERT INTO notifications (user_id, type, data) VALUES (?, ?, ?)"
        );
        return $stmt->execute([$userId, $type, json_encode($data, JSON_UNESCAPED_UNICODE)]);
    }

    /**
     * Notificações da pessoa, as mais recentes primeiro, com `data` já decodificado.
     * @return array{items: array, total: int}
     */
    public static function forUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ?');
        $stmt->execute([$userId]);
        $total = (int)$stmt->fetchColumn();
        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("SELECT id, user_id, type, data, read_at, created_at FROM notifications
                                WHERE user_id = ? ORDER BY id DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute([$userId]);
        $items = array_map(function ($n) {
            $n['data'] = json_decode((string)$n['data'], true) ?: [];
            return $n;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
        return ['items' => $items, 'total' => $total];
    }

    /** Quantas a pessoa ainda não leu (número do sino). */
    public static function unreadCount(int $userId): int
    {
        $stmt = Database::getConnection()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL');
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Marca como lidas: as de $ids (só as da própria pessoa) ou todas ($ids = null).
     * @return int quantas mudaram
     */
    public static function markRead(int $userId, ?array $ids = null): int
    {
        $pdo = Database::getConnection();
        if ($ids === null) {
            $stmt = $pdo->prepare('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL');
            $stmt->execute([$userId]);
            return $stmt->rowCount();
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
        if (!$ids) {
            return 0;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL AND id IN ($in)");
        $stmt->execute(array_merge([$userId], $ids));
        return $stmt->rowCount();
    }
}
