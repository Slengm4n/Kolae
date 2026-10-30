<?php

namespace App\Controllers;

use App\Core\VenueAccess;
use App\Core\VenueSchedule;
use App\Core\ViewHelper;
use App\Models\Game;
use App\Models\Sport;
use App\Models\VenueHour;
use App\Models\VenueSport;
use DateTimeImmutable;

/**
 * Aba Agenda da quadra (D35, D37): um dia por vez, linha do tempo, partidas do dia,
 * agendar e cancelar. Só partida avulsa (D39). Vale para o dono e para o admin.
 */
class VenueAgendaController
{
    /** Tamanho máximo de "para quem" (matches.renter_name). */
    private const RENTER_MAX = 100;

    /**
     * GET {painel}/quadras/{id}/agenda?data=YYYY-MM-DD
     */
    public function show($id)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        $day = self::parseDay($_GET['data'] ?? null) ?? VenueSchedule::now()->setTime(0, 0);
        $this->render($venue, $day, [], null);
    }

    /**
     * POST {painel}/quadras/{id}/agenda
     * Body: data (YYYY-MM-DD), start_time e end_time (HH:MM), sport_id e renter_name (opcionais).
     */
    public function schedule($id)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        $venueId = (int)$venue['id'];
        $day = self::parseDay($_POST['data'] ?? null) ?? VenueSchedule::now()->setTime(0, 0);
        $date = $day->format('Y-m-d');

        $old = [
            'start_time' => substr(trim((string)($_POST['start_time'] ?? '')), 0, 5),
            'end_time' => substr(trim((string)($_POST['end_time'] ?? '')), 0, 5),
            'sport_id' => ctype_digit((string)($_POST['sport_id'] ?? '')) ? (int)$_POST['sport_id'] : null,
            'renter_name' => mb_substr(trim((string)($_POST['renter_name'] ?? '')), 0, self::RENTER_MAX),
        ];

        $start = VenueSchedule::at($date, $old['start_time']);
        $end = VenueSchedule::at($date, $old['end_time']);
        if ($start === null || $end === null) {
            $error = 'invalid_time';
        } else {
            $hours = VenueHour::findByVenueId($venueId);
            $sameDay = Game::findScheduledOn([$venueId], $date)[$venueId] ?? [];
            $error = VenueSchedule::validateMatch($hours, $sameDay, $start, $end, VenueSchedule::now());
        }

        // Esporte opcional (D25): só aceita um esporte ativo
        if ($old['sport_id'] !== null && !self::isActiveSport($old['sport_id'])) {
            $old['sport_id'] = null;
        }

        if ($error !== null) {
            http_response_code(422);
            $this->render($venue, $day, $old, $error);
            return;
        }

        $created = Game::create([
            'venue_id' => $venueId,
            'creator_user_id' => (int)$_SESSION['user_id'],
            'start_time' => $start->format('Y-m-d H:i:s'),
            'end_time' => $end->format('Y-m-d H:i:s'),
            'sport_id' => $old['sport_id'],
            'renter_name' => $old['renter_name'],
        ]);
        $this->redirect($venueId, $date, $created ? 'created' : 'error');
    }

    /**
     * POST {painel}/quadras/{id}/agenda/cancelar/{matchId}
     * Body: data (para voltar ao mesmo dia).
     */
    public function cancel($id, $matchId)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        $day = self::parseDay($_POST['data'] ?? null) ?? VenueSchedule::now()->setTime(0, 0);
        // Game::cancel só cancela se a partida for desta quadra
        $ok = ctype_digit((string)$matchId) && Game::cancel((int)$matchId, (int)$venue['id']);
        $this->redirect((int)$venue['id'], $day->format('Y-m-d'), $ok ? 'cancelled' : 'cancel_error');
    }

    private function render(array $venue, DateTimeImmutable $day, array $old, ?string $error): void
    {
        $venueId = (int)$venue['id'];
        $now = VenueSchedule::now();
        $date = $day->format('Y-m-d');
        $hours = VenueHour::findByVenueId($venueId);

        $matches = Game::findDetailedBetween($venueId, "$date 00:00:00", $day->modify('+1 day')->format('Y-m-d') . ' 00:00:00');
        // A linha do tempo do dono mostra o horário mesmo com a quadra em manutenção
        $timeline = VenueSchedule::daySlots('available', $hours, $matches, $day);

        $sports = VenueSport::findByVenueIds([$venueId])[$venueId] ?? [];
        if (!$sports) {
            $sports = array_map(fn($s) => ['id' => (int)$s['id'], 'name' => $s['name']], Sport::getAll());
        }

        ViewHelper::render('venues/agenda', [
            'venue' => $venue,
            'activeTab' => 'agenda',
            'routePrefix' => VenueAccess::routePrefix(),
            'backUrl' => VenueAccess::panelUrl(),
            'status' => VenueAccess::currentStatus($venue, $hours),
            'day' => $day,
            'now' => $now,
            'isToday' => $date === $now->format('Y-m-d'),
            'isPast' => $date < $now->format('Y-m-d'),
            'hasHours' => !empty($hours),
            'timeline' => $timeline,
            'matches' => $matches,
            'sports' => $sports,
            'old' => $old + self::defaultTimes($timeline, $day, $now),
            'error' => $error,
        ]);
    }

    /**
     * Sugestão de início e fim para o formulário: a próxima meia hora (se for hoje) ou a
     * abertura, com 1 hora de duração, sem passar do fechamento.
     */
    private static function defaultTimes(array $timeline, DateTimeImmutable $day, DateTimeImmutable $now): array
    {
        if (!$timeline['opens_at']) {
            return ['start_time' => '', 'end_time' => ''];
        }
        $opens = VenueSchedule::at($day->format('Y-m-d'), $timeline['opens_at']);
        $closes = VenueSchedule::at($day->format('Y-m-d'), $timeline['closes_at']);
        $start = $opens;
        if ($now > $opens) {
            $minutes = (int)$now->format('i');
            $start = $now->setTime((int)$now->format('H'), $minutes < 30 ? 30 : 0)->modify($minutes < 30 ? '+0 hour' : '+1 hour');
        }
        if ($start >= $closes) {
            return ['start_time' => '', 'end_time' => ''];
        }
        $end = min($start->modify('+1 hour'), $closes);
        return ['start_time' => $start->format('H:i'), 'end_time' => $end->format('H:i')];
    }

    private static function isActiveSport(int $sportId): bool
    {
        foreach (Sport::getAll() as $s) {
            if ((int)$s['id'] === $sportId) {
                return true;
            }
        }
        return false;
    }

    /**
     * 'YYYY-MM-DD' → meia-noite desse dia em Brasília; null se inválido.
     */
    private static function parseDay($value): ?DateTimeImmutable
    {
        $value = is_string($value) ? trim($value) : '';
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            return null;
        }
        return new DateTimeImmutable($value . ' 00:00:00', VenueSchedule::timezone());
    }

    private function redirect(int $venueId, string $date, string $status): void
    {
        header('Location: ' . BASE_URL . VenueAccess::routePrefix() . "/quadras/$venueId/agenda?data=$date&status=$status");
        exit;
    }
}
