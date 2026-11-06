<?php

namespace App\Controllers;

use App\Core\VenueAccess;
use App\Core\PremiumPlan;
use App\Core\VenueStats;
use App\Core\ViewHelper;
use App\Models\VenueHour;

/**
 * Aba Estatísticas da quadra (D62), no estilo YouTube Analytics. Grátis vê os cartões dos
 * últimos 7 dias; Premium escolhe 7, 28 ou 90 dias, com gráfico, origens e mapa de calor.
 */
class VenueStatsController
{
    /** GET {painel}/quadras/{id}/estatisticas?periodo=7|28|90 */
    public function show($id)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        $premium = PremiumPlan::forVenue($venue); // o Premium é do dono (D61)
        $days = VenueStats::period($_GET['periodo'] ?? null, $premium);

        ViewHelper::render('venues/stats', [
            'venue' => $venue,
            'activeTab' => 'stats',
            'routePrefix' => VenueAccess::routePrefix(),
            'backUrl' => VenueAccess::panelUrl(),
            'status' => VenueAccess::currentStatus($venue, VenueHour::findByVenueId((int)$venue['id'])),
            'premium' => $premium,
            'days' => $days,
            'stats' => VenueStats::summary((int)$venue['id'], $days),
        ]);
    }
}
