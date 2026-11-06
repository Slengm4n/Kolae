<?php

namespace App\Controllers;

use App\Core\ApiFormat;
use App\Core\VenueSchedule;
use App\Core\VenueStats;
use App\Core\ViewHelper;
use App\Models\Game;
use App\Models\Sport;
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
    /** Preços máximos oferecidos no filtro, em reais. */
    public const PRICE_OPTIONS = [50, 100, 150, 200];

    /**
     * GET /quadras
     * Lista pública com mapa e filtros (D51). Usa a mesma lista da API (VenueApiController::listVenues).
     * Filtros na URL: q, sport_id, agora=1 (disponível agora), coberta=1, iluminacao=1,
     * preco=<reais>, lat e lng (ordena por distância). Valor inválido é ignorado.
     */
    public function index()
    {
        $get = fn($key) => isset($_GET[$key]) && is_scalar($_GET[$key]) ? trim((string)$_GET[$key]) : '';
        $filters = [];
        $form = ['q' => '', 'sport_id' => 0, 'agora' => false, 'coberta' => false, 'iluminacao' => false, 'preco' => 0, 'perto' => false];

        if (($q = mb_substr($get('q'), 0, 100)) !== '') {
            $filters['q'] = $form['q'] = $q;
        }
        if (ctype_digit($get('sport_id')) && (int)$get('sport_id') > 0) {
            $filters['sport_id'] = $form['sport_id'] = (int)$get('sport_id');
        }
        if ($get('agora') === '1') {
            $filters['status'] = 'available';
            $form['agora'] = true;
        }
        if ($get('coberta') === '1') {
            $filters['is_covered'] = $form['coberta'] = true;
        }
        if ($get('iluminacao') === '1') {
            $filters['has_lighting'] = $form['iluminacao'] = true;
        }
        if (in_array((int)$get('preco'), self::PRICE_OPTIONS, true)) {
            $form['preco'] = (int)$get('preco');
            $filters['max_price_cents'] = $form['preco'] * 100;
        }
        $lat = filter_var($get('lat'), FILTER_VALIDATE_FLOAT);
        $lng = filter_var($get('lng'), FILTER_VALIDATE_FLOAT);
        if ($lat !== false && $lng !== false && abs($lat) <= 90 && abs($lng) <= 180) {
            $filters += ['lat' => $lat, 'lng' => $lng, 'sort' => 'distance'];
            $form['perto'] = true;
        }

        ViewHelper::render('venues/list', [
            'venues' => VenueApiController::listVenues($filters),
            'sports' => Sport::getAll(),
            'form' => $form,
            'coords' => $form['perto'] ? ['lat' => $lat, 'lng' => $lng] : null,
        ]);
    }

    /**
     * GET /quadra/{id}
     */
    public function show($id)
    {
        $row = ctype_digit((string)$id) ? Venue::findForApi((int)$id) : false;
        if (!$row) {
            http_response_code(404);
            ViewHelper::render('errors/404', ['messageKey' => 'error_venue_not_found']);
            return;
        }

        $venueId = (int)$row['id'];
        // Visualização para as estatísticas do dono (D62); quem chega pelo QR Code vem com ?ref=qr
        VenueStats::record($row, 'view', ($_GET['ref'] ?? '') === 'qr' ? 'qr' : 'site',
            isset($_SESSION['user_id']) ? ['id' => $_SESSION['user_id'], 'role' => $_SESSION['user_role'] ?? ''] : null);
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
