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
