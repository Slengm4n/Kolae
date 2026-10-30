<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Class VenueSport
 * Esportes que cada quadra atende (tabela venue_sports, patch 004, D29).
 */
class VenueSport
{
    /**
     * Esportes ativos de várias quadras de uma vez, em ordem alfabética.
     * @param int[] $venueIds
     * @return array [venue_id => [['id' => 1, 'name' => 'Futebol', 'icon_path' => 'fa-futbol'], ...]]
     */
    public static function findByVenueIds(array $venueIds): array
    {
        if (!$venueIds) {
            return [];
        }

        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($venueIds), '?'));
        $stmt = $pdo->prepare("SELECT vs.venue_id, s.id, s.name, s.icon_path
                               FROM venue_sports vs
                               JOIN sports s ON s.id = vs.sport_id
                               WHERE vs.venue_id IN ($placeholders) AND s.status = 'active'
                               ORDER BY s.name");
        $stmt->execute(array_map('intval', $venueIds));

        $sports = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $sports[(int)$row['venue_id']][] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'icon_path' => $row['icon_path']
            ];
        }
        return $sports;
    }

    /**
     * Troca os esportes de uma quadra. Ignora ids que não são de esportes ativos.
     * @param int[] $sportIds
     */
    public static function replaceForVenue(int $venueId, array $sportIds): bool
    {
        $activeIds = array_map(fn($s) => (int)$s['id'], Sport::getAll());
        $sportIds = array_values(array_intersect(array_unique(array_map('intval', $sportIds)), $activeIds));

        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM venue_sports WHERE venue_id = ?")->execute([$venueId]);
            $stmt = $pdo->prepare("INSERT INTO venue_sports (venue_id, sport_id) VALUES (?, ?)");
            foreach ($sportIds as $sportId) {
                $stmt->execute([$venueId, $sportId]);
            }
            $pdo->commit();
            return true;
        } catch (\PDOException $e) {
            $pdo->rollBack();
            error_log('Erro ao salvar os esportes da quadra ' . $venueId . ': ' . $e->getMessage());
            return false;
        }
    }
}
