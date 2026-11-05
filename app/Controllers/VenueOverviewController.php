<?php

namespace App\Controllers;

use App\Core\VenueAccess;
use App\Core\VenueSchedule;
use App\Core\VenueSocials;
use App\Core\ViewHelper;
use App\Models\Game;
use App\Models\User;
use App\Models\VenueHour;
use App\Models\VenueImage;
use App\Models\VenueSport;

/**
 * Aba Visão geral da quadra (D47): a porta de entrada da quadra no painel. Mostra a quadra
 * como o atleta vê no app (galeria, preço, esportes, comodidades, descrição, horário),
 * o dia de hoje e o que falta para a quadra ficar completa. Vale para o dono e o admin.
 */
class VenueOverviewController
{
    /**
     * GET {painel}/quadras/{id}/visao-geral
     */
    public function show($id)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        $venueId = (int)$venue['id'];
        $now = VenueSchedule::now();

        $hours = VenueHour::findByVenueId($venueId);
        $images = VenueImage::findByVenueId($venueId);
        $sports = VenueSport::findByVenueIds([$venueId])[$venueId] ?? [];
        $owner = User::findById((int)$venue['user_id']);
        $today = Game::findDetailedBetween($venueId, $now->format('Y-m-d') . ' 00:00:00', $now->modify('+1 day')->format('Y-m-d') . ' 00:00:00');

        ViewHelper::render('venues/overview', [
            'venue' => $venue,
            'activeTab' => 'overview',
            'routePrefix' => VenueAccess::routePrefix(),
            'backUrl' => VenueAccess::panelUrl(),
            'status' => VenueAccess::currentStatus($venue, $hours),
            'images' => $images,
            'sports' => $sports,
            'summary' => VenueHoursController::weekSummary($hours),
            'todayHours' => $hours[(int)$now->format('w')] ?? null,
            'todayMatches' => $today,
            'now' => $now,
            'ownerWhatsapp' => $owner['whatsapp'] ?? null,
            'checklist' => self::checklist($venue, $images, $hours, $sports, $owner['whatsapp'] ?? null),
        ]);
    }

    /**
     * O que deixa a quadra completa no app. Cada item: [chave, feito?, aba que resolve, opcional?].
     * Item opcional aparece na lista, mas não conta no "x de y" nem impede a quadra de ficar completa.
     */
    private static function checklist(array $venue, array $images, array $hours, array $sports, ?string $whatsapp): array
    {
        return [
            ['photos', count($images) >= 3, 'fotos', false],
            ['hours', !empty($hours), 'horario', false],
            ['sports', !empty($sports), 'editar', false],
            ['description', trim((string)$venue['description']) !== '', 'editar', false],
            ['price', $venue['average_price_per_hour'] !== null, 'editar', false],
            ['whatsapp', !empty($whatsapp), 'editar', false],
            ['socials', VenueSocials::links($venue) !== [], 'redes', true],
        ];
    }
}
