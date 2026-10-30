<?php

namespace App\Core;

use App\Models\Game;
use App\Models\Venue;
use App\Models\VenueHour;

/**
 * Class VenueAccess
 * Partes comuns das páginas de gerenciar uma quadra (abas Dados, Horário e Agenda, D35):
 * quem pode mexer, de qual painel a pessoa veio e o status que aparece no topo.
 */
class VenueAccess
{
    /**
     * Prefixo das rotas conforme o painel atual: admin em /admin/... continua no admin;
     * o resto usa /dashboard. Decide pela rota, não pelo Referer.
     */
    public static function routePrefix(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        return AuthHelper::isAdmin() && str_contains($path, '/admin/') ? '/admin' : '/dashboard';
    }

    /**
     * Página de volta do painel atual.
     */
    public static function panelUrl(): string
    {
        return self::routePrefix() === '/admin' ? '/admin/quadras' : '/dashboard';
    }

    /**
     * Quadra que a pessoa logada pode gerenciar (dona ou admin). Se não puder, volta ao painel.
     * @return array linha de Venue::findById
     */
    public static function findManageableOrRedirect($id): array
    {
        AuthHelper::check();
        $venue = ctype_digit((string)$id) ? Venue::findById((int)$id) : false;
        if (!$venue || ((int)$venue['user_id'] !== (int)$_SESSION['user_id'] && !AuthHelper::isAdmin())) {
            header('Location: ' . BASE_URL . self::panelUrl() . '?error=not_found');
            exit;
        }
        return $venue;
    }

    /**
     * Complemento do selo: "até 19:00", "abre 08:00" ou "abre Seg 08:00" ('' sem previsão).
     * @param array $status resultado de currentStatus / VenueSchedule::status
     * @param array $lang   textos do idioma atual
     */
    public static function statusUntilText(array $status, array $lang): string
    {
        $until = $status['status_until'] ?? null;
        if (!$until) {
            return '';
        }
        $time = $until->format('H:i');
        $sameDay = $until->format('Y-m-d') === VenueSchedule::now()->format('Y-m-d');
        $when = $sameDay ? $time : ($lang['weekday_short_' . $until->format('w')] . ' ' . $time);
        return ($status['status'] === 'closed' ? $lang['venue_status_opens'] : $lang['venue_status_until']) . ' ' . $when;
    }

    /**
     * Status da quadra agora, pela mesma regra da API (D03, D40).
     * @return array{status: string, status_until: ?\DateTimeImmutable}
     */
    public static function currentStatus(array $venue, ?array $hours = null): array
    {
        $id = (int)$venue['id'];
        $now = VenueSchedule::now();
        $hours ??= VenueHour::findByVenueId($id);
        $matches = Game::findScheduledOn([$id], $now->format('Y-m-d'))[$id] ?? [];
        return VenueSchedule::status($venue['status'], $hours, $matches, $now);
    }
}
