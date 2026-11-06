<?php

namespace App\Controllers;

use App\Core\AuthHelper;
use App\Core\VenueAccess;
use App\Core\VenuePlan;
use App\Core\ViewHelper;
use App\Models\VenueHour;

/**
 * Aba Premium da quadra (D61): o dono compara Grátis e Premium e pede a ativação; o admin
 * ativa (com validade) ou desativa na mesma página, pela rota do admin.
 */
class VenuePlanController
{
    /** GET {painel}/quadras/{id}/plano */
    public function show($id)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        ViewHelper::render('venues/plan', [
            'venue' => $venue,
            'activeTab' => 'premium',
            'routePrefix' => VenueAccess::routePrefix(),
            'backUrl' => VenueAccess::panelUrl(),
            'status' => VenueAccess::currentStatus($venue, VenueHour::findByVenueId((int)$venue['id'])),
            'planState' => VenuePlan::state($venue),
            'defaultUntil' => VenuePlan::defaultUntil(),
            'isAdmin' => AuthHelper::isAdmin(),
        ]);
    }

    /** POST {painel}/quadras/{id}/plano/pedir: o dono pede o Premium */
    public function request($id)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        VenuePlan::request($venue);
        $this->back($venue, 'status=plan_requested');
    }

    /** POST /admin/quadras/{id}/plano/ativar */
    public function activate($id)
    {
        AuthHelper::checkAdmin();
        $venue = VenueAccess::findManageableOrRedirect($id);
        [$ok, $until] = VenuePlan::parseUntil($_POST['plan_until'] ?? null, !empty($_POST['no_limit']));
        if (!$ok) {
            $this->back($venue, 'error=plan_invalid_date');
        }
        VenuePlan::activate($venue, $until);
        $this->back($venue, 'status=plan_activated');
    }

    /** POST /admin/quadras/{id}/plano/desativar */
    public function deactivate($id)
    {
        AuthHelper::checkAdmin();
        $venue = VenueAccess::findManageableOrRedirect($id);
        VenuePlan::deactivate($venue);
        $this->back($venue, 'status=plan_deactivated');
    }

    private function back(array $venue, string $query): void
    {
        header('Location: ' . BASE_URL . VenueAccess::routePrefix() . '/quadras/' . (int)$venue['id'] . '/plano?' . $query);
        exit;
    }
}
