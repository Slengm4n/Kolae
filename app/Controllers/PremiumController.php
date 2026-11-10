<?php

namespace App\Controllers;

use App\Core\AuthHelper;
use App\Core\PremiumPlan;
use App\Core\ViewHelper;
use App\Models\User;

/**
 * Kolae Premium do dono (D61): Configurações > Kolae Premium (ver e pedir) e, no painel do
 * admin, ativar com validade ou voltar para o Grátis.
 */
class PremiumController
{
    /** GET /dashboard/perfil/plano */
    public function show()
    {
        AuthHelper::check();
        $user = User::findById($_SESSION['user_id']);
        ViewHelper::render('users/premium', [
            'user' => $user,
            'planState' => PremiumPlan::state($user),
            'venueCount' => count(\App\Models\Venue::findByUserId((int)$user['id']) ?: []),
        ]);
    }

    /** POST /dashboard/perfil/plano/pedir */
    public function request()
    {
        AuthHelper::check();
        PremiumPlan::request(User::findById($_SESSION['user_id']));
        header('Location: ' . BASE_URL . '/dashboard/perfil/plano?status=plan_requested');
        exit;
    }

    /** POST /admin/premium/{id}/ativar */
    public function activate($id)
    {
        AuthHelper::checkAdmin();
        $user = $this->findUser($id);
        [$ok, $until] = PremiumPlan::parseUntil($_POST['plan_until'] ?? null, !empty($_POST['no_limit']));
        if (!$ok) {
            $this->back('error=plan_invalid_date');
        }
        PremiumPlan::activate($user, $until);
        $this->back('status=plan_activated');
    }

    /** POST /admin/premium/{id}/desativar */
    public function deactivate($id)
    {
        AuthHelper::checkAdmin();
        PremiumPlan::deactivate($this->findUser($id));
        $this->back('status=plan_deactivated');
    }

    private function findUser($id): array
    {
        $user = ctype_digit((string)$id) ? User::findById((int)$id) : false;
        if (!$user) {
            $this->back('error=generic');
        }
        return $user;
    }

    private function back(string $query): void
    {
        header('Location: ' . BASE_URL . '/admin/premium?' . $query);
        exit;
    }
}
