<?php

namespace App\Controllers;

use App\Core\AuthHelper;
use App\Core\VenueAds;
use App\Models\Notification;
use App\Models\Venue;
use App\Models\VenueAd;

/**
 * Remoção de anúncio pelo admin (D63, parte E), na página Anúncios do admin (/admin/anuncios, D67).
 * O anúncio sai do ar na hora, o dono vê o motivo na aba Anúncio e recebe uma notificação
 * (ad_removed), que aparece no sino quando ele existir (D64).
 */
class AdminAdController
{
    /** POST /admin/anuncios/{id}/remover */
    public function remove($id)
    {
        AuthHelper::checkAdmin();
        $ad = ctype_digit((string)$id) ? VenueAd::findById((int)$id) : null;
        if (!$ad) {
            $this->back('error=ad_failed');
        }
        [$reason, $error] = VenueAds::cleanReason($_POST['reason'] ?? null);
        if ($error !== null) {
            $this->back('error=ad_reason_' . $error, (int)$ad['id']);
        }
        if (!VenueAd::applyAction((int)$ad['id'], (int)$ad['venue_id'], 'remove', $reason)) {
            $this->back('error=ad_failed'); // já removido, ou mudou no meio
        }

        // Avisa o dono; se a notificação falhar, a remoção continua valendo (o motivo está no anúncio)
        try {
            $venue = Venue::findById((int)$ad['venue_id']);
            if ($venue) {
                Notification::create((int)$venue['user_id'], 'ad_removed', [
                    'venue_id' => (int)$venue['id'],
                    'venue_name' => $venue['name'],
                    'ad_id' => (int)$ad['id'],
                    'ad_title' => $ad['title'],
                    'reason' => $reason,
                ]);
            }
        } catch (\Throwable $e) {
            error_log('Anúncios: não criou a notificação da remoção: ' . $e->getMessage());
        }
        $this->back('status=ad_removed');
    }

    /** Volta para a página Anúncios; com erro no motivo, abre de novo o formulário daquele anúncio. */
    private function back(string $query, ?int $adId = null): void
    {
        header('Location: ' . BASE_URL . '/admin/anuncios?' . $query . ($adId ? '&remover=' . $adId : '') . '#anuncios');
        exit;
    }
}
