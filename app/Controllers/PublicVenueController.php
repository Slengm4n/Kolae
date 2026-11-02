<?php

namespace App\Controllers;

use App\Core\ApiFormat;
use App\Core\VenueSchedule;
use App\Core\ViewHelper;
use App\Models\Game;
use App\Models\Venue;
use App\Models\VenueHour;
use App\Models\VenueImage;
use App\Models\VenueSport;

/**
 * Página pública da quadra (D49): kolae.online/quadra/{id}, aberta a qualquer pessoa.
 * Mostra o mesmo que o app (galeria, informações, status ao vivo, horários ocupados de hoje)
 * e o botão "Falar com o dono". Nunca mostra para quem foi alugado cada horário.
 * Quadra desativada ou excluída dá 404, igual à API (D34).
 */
class PublicVenueController
{
    /**
     * GET /quadra/{id}
     */
    public function show($id)
    {
        $row = ctype_digit((string)$id) ? Venue::findForApi((int)$id) : false;
        if (!$row) {
            http_response_code(404);
            ViewHelper::render('errors/404', ['message' => 'Quadra não encontrada.']);
            return;
        }

        $venueId = (int)$row['id'];
        $now = VenueSchedule::now();
        $hours = VenueHour::findByVenueId($venueId);
        $matches = Game::findScheduledOn([$venueId], $now->format('Y-m-d'))[$venueId] ?? [];
        $images = VenueImage::findByVenueId($venueId);

        // Horários ocupados de hoje que ainda não acabaram (sem nomes)
        $busy = array_values(array_filter($matches, fn($m) => strtotime($m['end_time']) > $now->getTimestamp()));

        ViewHelper::render('venues/public', [
            'venue' => $row,
            'images' => $images,
            'sports' => VenueSport::findByVenueIds([$venueId])[$venueId] ?? [],
            'summary' => VenueHoursController::weekSummary($hours),
            'status' => VenueSchedule::status($row['status'], $hours, $matches, $now),
            'todayHours' => $hours[(int)$now->format('w')] ?? null,
            'busy' => $busy,
            'now' => $now,
            'ownerAvatarUrl' => ApiFormat::uploadUrl('avatars/' . $row['user_id'], $row['owner_avatar_path']),
            'coverUrl' => $images ? BASE_URL . '/uploads/venues/' . $venueId . '/' . rawurlencode(basename($images[0]['file_path'])) : null,
        ]);
    }
}
