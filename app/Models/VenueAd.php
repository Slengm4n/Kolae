<?php

namespace App\Models;

use App\Core\ApiFormat;
use App\Core\Database;
use App\Core\PremiumPlan;
use App\Core\VenueAds;
use DateTimeImmutable;
use PDO;

/**
 * Anúncios das quadras (D63), tabela venue_ads (patch 009). As regras ficam em App\Core\VenueAds.
 */
class VenueAd
{
    /** Colunas lidas em todas as consultas. */
    private const COLUMNS = 'a.id, a.venue_id, a.title, a.body, a.image_path, a.starts_on, a.ends_on, a.status, a.removed_reason, a.created_at, a.updated_at';

    /**
     * Cria um anúncio ativo. $values vem de VenueAds::validate.
     * @return int|false id do anúncio
     */
    public static function create(int $venueId, array $values)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO venue_ads (venue_id, title, body, starts_on, ends_on, status) VALUES (?, ?, ?, ?, ?, 'active')"
        );
        if (!$stmt->execute([$venueId, $values['title'], $values['body'], $values['starts_on'], $values['ends_on']])) {
            return false;
        }
        return (int)$pdo->lastInsertId();
    }

    /** Edita título, texto e período de um anúncio da quadra. */
    public static function update(int $id, int $venueId, array $values): bool
    {
        $ad = self::findById($id);
        if (!$ad || (int)$ad['venue_id'] !== $venueId) {
            return false;
        }
        $stmt = Database::getConnection()->prepare(
            "UPDATE venue_ads SET title = ?, body = ?, starts_on = ?, ends_on = ? WHERE id = ? AND venue_id = ?"
        );
        return $stmt->execute([$values['title'], $values['body'], $values['starts_on'], $values['ends_on'], $id, $venueId]);
    }

    /** Pasta das fotos de um anúncio (uploads/ads/{id}/; o .htaccess de uploads/ vale aqui também). */
    public static function uploadDir(int $id): string
    {
        return BASE_PATH . '/uploads/ads/' . $id . '/';
    }

    /**
     * Foto que o anúncio mostra: a dele ou, sem foto, a capa da quadra ($coverFile, nome do arquivo
     * em uploads/venues/{venue_id}/). null se nenhuma das duas existir no servidor.
     */
    public static function imageUrl(array $ad, ?string $coverFile): ?string
    {
        return ApiFormat::uploadUrl('ads/' . (int)$ad['id'], $ad['image_path'] ?? null)
            ?? ApiFormat::uploadUrl('venues/' . (int)$ad['venue_id'], $coverFile);
    }

    /** Troca a foto do anúncio (nome do arquivo em uploads/ads/{id}/, ou null para usar a capa). */
    public static function setImage(int $id, ?string $fileName): bool
    {
        $stmt = Database::getConnection()->prepare("UPDATE venue_ads SET image_path = ? WHERE id = ?");
        return $stmt->execute([$fileName, $id]);
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT ' . self::COLUMNS . ' FROM venue_ads a WHERE a.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** O anúncio em aberto da quadra (ativo ou pausado, ainda no período), ou null. */
    public static function findOpenForVenue(int $venueId, DateTimeImmutable $today): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT ' . self::COLUMNS . " FROM venue_ads a
              WHERE a.venue_id = ? AND a.status IN ('active', 'paused') AND a.ends_on >= ?
              ORDER BY a.id DESC LIMIT 1"
        );
        $stmt->execute([$venueId, $today->format('Y-m-d')]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Anúncios da quadra, do mais novo para o mais antigo (histórico na tela do dono). */
    public static function findByVenue(int $venueId, int $limit = 10): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT ' . self::COLUMNS . ' FROM venue_ads a WHERE a.venue_id = ? ORDER BY a.id DESC LIMIT ' . max(1, $limit)
        );
        $stmt->execute([$venueId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Pausar, retomar ou encerrar (dono) e remover (admin). Só muda se a ação valer para o status
     * atual (VenueAds::nextStatus); a condição no UPDATE evita trocar um status que mudou no meio.
     */
    public static function applyAction(int $id, int $venueId, string $action, ?string $reason = null): bool
    {
        $ad = self::findById($id);
        if (!$ad || (int)$ad['venue_id'] !== $venueId) {
            return false;
        }
        $next = VenueAds::nextStatus($ad['status'], $action);
        if ($next === null) {
            return false;
        }
        $stmt = Database::getConnection()->prepare(
            'UPDATE venue_ads SET status = ?, removed_reason = ? WHERE id = ? AND status = ?'
        );
        $stmt->execute([$next, $action === 'remove' ? $reason : $ad['removed_reason'], $id, $ad['status']]);
        return $stmt->rowCount() === 1;
    }

    /**
     * Anúncios que aparecem hoje, um por quadra: [venue_id => anúncio]. Sem $venueIds, todos
     * (home, /quadras e GET /ads). A regra final (Premium do dono, período, quadra ativa) é a
     * do VenueAds::isVisible; o SQL só corta o que com certeza não aparece.
     */
    public static function visibleByVenue(DateTimeImmutable $today, ?array $venueIds = null): array
    {
        if ($venueIds !== null) {
            $venueIds = array_values(array_unique(array_map('intval', $venueIds)));
            if (!$venueIds) {
                return [];
            }
        }
        $day = $today->format('Y-m-d');
        $sql = 'SELECT ' . self::COLUMNS . ", v.status AS venue_status, v.deleted_at AS venue_deleted_at,
                       u.plan AS owner_plan, u.plan_until AS owner_plan_until
                  FROM venue_ads a
                  JOIN venues v ON v.id = a.venue_id
                  JOIN users u ON u.id = v.user_id
                 WHERE a.status = 'active' AND a.starts_on <= ? AND a.ends_on >= ?
                   AND v.status <> 'unavailable' AND v.deleted_at IS NULL";
        $params = [$day, $day];
        if ($venueIds !== null) {
            $sql .= ' AND a.venue_id IN (' . implode(',', array_fill(0, count($venueIds), '?')) . ')';
            $params = array_merge($params, $venueIds);
        }
        $stmt = Database::getConnection()->prepare($sql . ' ORDER BY a.id DESC');
        $stmt->execute($params);

        $byVenue = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $venueId = (int)$row['venue_id'];
            $premium = PremiumPlan::isPremium(['plan' => $row['owner_plan'], 'plan_until' => $row['owner_plan_until']], $today);
            $venue = ['status' => $row['venue_status'], 'deleted_at' => $row['venue_deleted_at']];
            if (isset($byVenue[$venueId]) || !VenueAds::isVisible($row, $premium, $venue, $today)) {
                continue; // um por quadra: o mais novo
            }
            unset($row['venue_status'], $row['venue_deleted_at'], $row['owner_plan'], $row['owner_plan_until']);
            $byVenue[$venueId] = $row;
        }
        return $byVenue;
    }

    /**
     * Vezes que o anúncio apareceu e cliques (D62), contados nos eventos da quadra desde que ele
     * foi criado até o fim do período. Só um anúncio por vez, então não se misturam.
     * @return array{views: int, clicks: int}
     */
    public static function counts(array $ad): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT SUM(type = 'ad_view') AS views, SUM(type = 'ad_click') AS clicks
               FROM venue_events
              WHERE venue_id = ? AND type IN ('ad_view', 'ad_click')
                AND created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)"
        );
        $stmt->execute([(int)$ad['venue_id'], $ad['created_at'], $ad['ends_on']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return ['views' => (int)($row['views'] ?? 0), 'clicks' => (int)($row['clicks'] ?? 0)];
    }

    /**
     * Anúncios em aberto (ativos ou pausados, ainda no período), para o admin revisar e remover
     * (parte E). Os mais novos primeiro, com a quadra e o dono.
     */
    public static function openForAdmin(DateTimeImmutable $today): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT ' . self::COLUMNS . ", v.name AS venue_name, v.status AS venue_status, v.deleted_at AS venue_deleted_at,
                    u.id AS owner_id, u.name AS owner_name, u.email AS owner_email, u.plan AS owner_plan, u.plan_until AS owner_plan_until
               FROM venue_ads a
               JOIN venues v ON v.id = a.venue_id
               JOIN users u ON u.id = v.user_id
              WHERE a.status IN ('active', 'paused') AND a.ends_on >= ? AND v.deleted_at IS NULL
              ORDER BY a.created_at DESC, a.id DESC"
        );
        $stmt->execute([$today->format('Y-m-d')]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * O que o card "Patrocinado" precisa de cada quadra: dono (para não contar a visita dele),
     * nome, bairro, cidade e capa (foto do anúncio quando ele não tem uma).
     * @return array<int, array> venue_id => [id, user_id, name, neighborhood, city, cover]
     */
    public static function venuesForCards(array $venueIds): array
    {
        $venueIds = array_values(array_unique(array_map('intval', $venueIds)));
        if (!$venueIds) {
            return [];
        }
        $stmt = Database::getConnection()->prepare(
            'SELECT v.id, v.user_id, v.name, n.name AS neighborhood, c.name AS city,
                    (SELECT file_path FROM venues_images vi WHERE vi.venue_id = v.id ORDER BY vi.position ASC, vi.id ASC LIMIT 1) AS cover
               FROM venues v
               LEFT JOIN addresses ad ON v.address_id = ad.id
               LEFT JOIN streets s ON ad.street_id = s.id
               LEFT JOIN neighborhoods n ON s.neighborhood_id = n.id
               LEFT JOIN cities c ON n.city_id = c.id
              WHERE v.id IN (' . implode(',', array_fill(0, count($venueIds), '?')) . ')'
        );
        $stmt->execute($venueIds);
        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[(int)$row['id']] = $row;
        }
        return $rows;
    }
}
