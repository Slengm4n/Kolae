<?php

namespace App\Controllers;

use App\Core\VenueAccess;
use App\Core\VenueSchedule;
use App\Core\ViewHelper;
use App\Models\Game;
use App\Models\VenueHour;

/**
 * Aba Horário da quadra (D35): os 7 dias da semana, cada um aberto (abre/fecha) ou fechado.
 * Um intervalo por dia (D21), sem passar da meia-noite (D22). Vale para o dono e para o admin.
 */
class VenueHoursController
{
    /** Ordem de exibição: segunda a domingo (weekday do PHP: 0 = domingo). */
    public const DISPLAY_ORDER = [1, 2, 3, 4, 5, 6, 0];

    /**
     * GET {painel}/quadras/{id}/horario
     */
    public function show($id)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        $hours = VenueHour::findByVenueId((int)$venue['id']);
        $this->render($venue, $hours, self::formFromHours($hours), []);
    }

    /**
     * POST {painel}/quadras/{id}/horario
     * Body: hours[weekday][open|opens_at|closes_at]. Dia sem "open" = fechado.
     */
    public function save($id)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        $input = is_array($_POST['hours'] ?? null) ? $_POST['hours'] : [];
        $result = VenueSchedule::validateHours($input);

        if ($result['errors']) {
            http_response_code(422);
            $this->render($venue, VenueHour::findByVenueId((int)$venue['id']), self::formFromInput($input), $result['errors']);
            return;
        }

        $url = BASE_URL . VenueAccess::routePrefix() . '/quadras/' . (int)$venue['id'] . '/horario';
        if (!VenueHour::replaceForVenue((int)$venue['id'], $result['hours'])) {
            header('Location: ' . $url . '?status=error');
            exit;
        }
        header('Location: ' . $url . '?status=saved');
        exit;
    }

    private function render(array $venue, array $savedHours, array $form, array $errors): void
    {
        $venueId = (int)$venue['id'];
        $now = VenueSchedule::now();

        // Partidas futuras que não cabem no horário salvo: o site só avisa (D27)
        $future = Game::findDetailedBetween($venueId, $now->format('Y-m-d H:i:s'));
        $outside = $savedHours ? VenueSchedule::matchesOutsideHours($savedHours, $future) : [];

        ViewHelper::render('venues/hours', [
            'venue' => $venue,
            'activeTab' => 'hours',
            'routePrefix' => VenueAccess::routePrefix(),
            'backUrl' => VenueAccess::panelUrl(),
            'status' => VenueAccess::currentStatus($venue, $savedHours),
            'hasHours' => !empty($savedHours),
            'form' => $form,
            'errors' => $errors,
            'summary' => self::weekSummary($savedHours),
            'outsideMatches' => $outside,
        ]);
    }

    /**
     * Valores do formulário a partir do horário salvo. Dia fechado já vem com 08:00–22:00,
     * para o dono só ligar o interruptor.
     * @return array [weekday => ['open' => bool, 'opens_at' => 'HH:MM', 'closes_at' => 'HH:MM']]
     */
    private static function formFromHours(array $hours): array
    {
        $form = [];
        foreach (self::DISPLAY_ORDER as $w) {
            $form[$w] = isset($hours[$w])
                ? ['open' => true, 'opens_at' => substr($hours[$w]['opens_at'], 0, 5), 'closes_at' => substr($hours[$w]['closes_at'], 0, 5)]
                : ['open' => false, 'opens_at' => '08:00', 'closes_at' => '22:00'];
        }
        return $form;
    }

    /**
     * Valores do formulário a partir do que foi enviado (para mostrar de novo com os erros).
     */
    private static function formFromInput(array $input): array
    {
        $form = [];
        foreach (self::DISPLAY_ORDER as $w) {
            $day = is_array($input[$w] ?? null) ? $input[$w] : [];
            $form[$w] = [
                'open' => !empty($day['open']),
                'opens_at' => substr((string)($day['opens_at'] ?? ''), 0, 5),
                'closes_at' => substr((string)($day['closes_at'] ?? ''), 0, 5),
            ];
        }
        return $form;
    }

    /**
     * Resumo "Como aparece no app": dias seguidos com o mesmo horário viram um grupo.
     * Ex: Seg a Sex 08:00–22:00 · Sáb 09:00–18:00 · Dom fechado.
     * @return array lista de ['days' => int[], 'opens_at' => ?'HH:MM', 'closes_at' => ?'HH:MM']
     */
    private static function weekSummary(array $hours): array
    {
        $groups = [];
        foreach (self::DISPLAY_ORDER as $w) {
            $opens = isset($hours[$w]) ? substr($hours[$w]['opens_at'], 0, 5) : null;
            $closes = isset($hours[$w]) ? substr($hours[$w]['closes_at'], 0, 5) : null;
            $last = count($groups) - 1;
            if ($last >= 0 && $groups[$last]['opens_at'] === $opens && $groups[$last]['closes_at'] === $closes) {
                $groups[$last]['days'][] = $w;
            } else {
                $groups[] = ['days' => [$w], 'opens_at' => $opens, 'closes_at' => $closes];
            }
        }
        return $groups;
    }
}
