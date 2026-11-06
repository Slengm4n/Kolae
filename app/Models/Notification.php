<?php

namespace App\Models;

use App\Core\Database;

/**
 * Avisos do painel e do app (D64). O texto não fica no banco: `data` guarda ids e textos curtos
 * (JSON) e a mensagem é montada no idioma de quem lê.
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
}
