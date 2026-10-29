<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Class VenueHour
 * Horário de funcionamento das quadras (tabela venue_hours, patch 004).
 * Um intervalo por dia da semana (D21); dia sem linha = fechada.
 */
class VenueHour
{
    /**
     * Horários de várias quadras de uma vez.
     * @param int[] $venueIds
     * @return array [venue_id => [weekday => ['opens_at' => 'HH:MM:SS', 'closes_at' => 'HH:MM:SS']]]
     */
    public static function findByVenueIds(array $venueIds): array
    {
        if (!$venueIds) {
            return [];
        }

        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($venueIds), '?'));
        $stmt = $pdo->prepare("SELECT venue_id, weekday, opens_at, closes_at
                               FROM venue_hours
                               WHERE venue_id IN ($placeholders)
                               ORDER BY venue_id, weekday");
        $stmt->execute(array_map('intval', $venueIds));

        $hours = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $hours[(int)$row['venue_id']][(int)$row['weekday']] = [
                'opens_at' => $row['opens_at'],
                'closes_at' => $row['closes_at']
            ];
        }
        return $hours;
    }
}
