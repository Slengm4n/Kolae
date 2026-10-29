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

    /**
     * Horário de uma quadra.
     * @return array [weekday => ['opens_at' => 'HH:MM:SS', 'closes_at' => 'HH:MM:SS']]
     */
    public static function findByVenueId(int $venueId): array
    {
        return self::findByVenueIds([$venueId])[$venueId] ?? [];
    }

    /**
     * Troca o horário da semana inteira de uma quadra (o que não vier fica fechado).
     * @param array $hours já validado por VenueSchedule::validateHours
     */
    public static function replaceForVenue(int $venueId, array $hours): bool
    {
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("DELETE FROM venue_hours WHERE venue_id = ?");
            $stmt->execute([$venueId]);

            $stmt = $pdo->prepare("INSERT INTO venue_hours (venue_id, weekday, opens_at, closes_at)
                                   VALUES (?, ?, ?, ?)");
            foreach ($hours as $weekday => $day) {
                $stmt->execute([$venueId, (int)$weekday, $day['opens_at'], $day['closes_at']]);
            }

            $pdo->commit();
            return true;
        } catch (\PDOException $e) {
            $pdo->rollBack();
            error_log('Erro ao salvar o horário da quadra ' . $venueId . ': ' . $e->getMessage());
            return false;
        }
    }
}
