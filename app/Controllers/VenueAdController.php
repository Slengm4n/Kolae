<?php

namespace App\Controllers;

use App\Core\ImageHelper;
use App\Core\PremiumPlan;
use App\Core\VenueAccess;
use App\Core\VenueAds;
use App\Core\VenueSchedule;
use App\Core\ViewHelper;
use App\Models\VenueAd;
use App\Models\VenueHour;
use App\Models\VenueImage;

/**
 * Aba Anúncio da quadra (D63): o dono Premium cria um anúncio por vez, com prévia ao vivo,
 * e pausa, retoma, edita e encerra. Dono Grátis vê o convite para o Premium. Vale para o dono
 * e o admin (mesmas rotas em /dashboard e /admin). Regras em App\Core\VenueAds.
 */
class VenueAdController
{
    /** Ações dos botões: rota => ação do VenueAds. */
    private const ACTIONS = ['pausar' => 'pause', 'retomar' => 'resume', 'encerrar' => 'end'];

    /** GET {painel}/quadras/{id}/anuncio (?editar=1 abre o formulário do anúncio em aberto) */
    public function show($id)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        $today = VenueSchedule::now();
        $open = VenueAd::findOpenForVenue((int)$venue['id'], $today);
        $this->render($venue, [
            'editing' => $open !== null && isset($_GET['editar']),
            'input' => $open ?? [],
            'errors' => [],
        ]);
    }

    /**
     * POST {painel}/quadras/{id}/anuncio
     * Cria o anúncio ou, se já houver um em aberto, edita esse. Body: title, body, starts_on,
     * ends_on, image (arquivo, opcional), remove_image (volta para a capa).
     */
    public function save($id)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        $venueId = (int)$venue['id'];
        if (!PremiumPlan::forVenue($venue)) {
            $this->redirect($venue, ['error' => 'ad_premium']);
        }
        $today = VenueSchedule::now();
        $open = VenueAd::findOpenForVenue($venueId, $today);

        $check = VenueAds::validate($_POST, $today, $open);
        $image = $this->uploadedImage();
        if ($image === false) {
            $check['errors']['image'] = 'invalid';
        }
        if ($check['errors']) {
            // Mostra o formulário de novo com o que a pessoa digitou (nada é gravado)
            http_response_code(422);
            $this->render($venue, [
                'editing' => $open !== null,
                'input' => array_intersect_key($_POST, array_flip(['title', 'body', 'starts_on', 'ends_on'])) + ($open ?? []),
                'errors' => $check['errors'],
            ]);
            return;
        }

        if ($open) {
            $adId = (int)$open['id'];
            $ok = VenueAd::update($adId, $venueId, $check['values']);
            $status = 'ad_updated';
        } else {
            $adId = VenueAd::create($venueId, $check['values']);
            $ok = $adId !== false;
            $status = 'ad_created';
        }
        if (!$ok) {
            $this->redirect($venue, ['error' => 'ad_failed']);
        }

        $current = $open['image_path'] ?? null;
        if ($image !== null) {
            $saved = $this->storeImage((int)$adId, $image);
            if ($saved !== null) {
                $this->deleteImage((int)$adId, $current);
                VenueAd::setImage((int)$adId, $saved);
            }
        } elseif (!empty($_POST['remove_image']) && $current) {
            $this->deleteImage((int)$adId, $current);
            VenueAd::setImage((int)$adId, null);
        }
        $this->redirect($venue, ['status' => $status]);
    }

    /** POST {painel}/quadras/{id}/anuncio/{adId}/pausar | retomar | encerrar */
    public function action($id, $adId, $action)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        $do = self::ACTIONS[$action] ?? null;
        if ($do === null || !ctype_digit((string)$adId)) {
            $this->redirect($venue, ['error' => 'ad_failed']);
        }
        // Retomar volta a mostrar o anúncio: só com o Premium valendo. Pausar e encerrar, sempre.
        if ($do === 'resume' && !PremiumPlan::forVenue($venue)) {
            $this->redirect($venue, ['error' => 'ad_premium']);
        }
        $ok = VenueAd::applyAction((int)$adId, (int)$venue['id'], $do);
        $this->redirect($venue, $ok ? ['status' => 'ad_' . $do] : ['error' => 'ad_failed']);
    }

    // --- Ajudantes ---

    private function render(array $venue, array $form): void
    {
        $venueId = (int)$venue['id'];
        $today = VenueSchedule::now();
        $premium = PremiumPlan::forVenue($venue);
        $open = VenueAd::findOpenForVenue($venueId, $today);
        $images = VenueImage::findByVenueId($venueId);
        $cover = $images[0]['file_path'] ?? null;
        $history = array_values(array_filter(VenueAd::findByVenue($venueId, 6), fn($ad) => !$open || (int)$ad['id'] !== (int)$open['id']));

        ViewHelper::render('venues/ad', [
            'venue' => $venue,
            'activeTab' => 'ad',
            'routePrefix' => VenueAccess::routePrefix(),
            'backUrl' => VenueAccess::panelUrl(),
            'status' => VenueAccess::currentStatus($venue, VenueHour::findByVenueId($venueId)),
            'premium' => $premium,
            'today' => $today->format('Y-m-d'),
            'ad' => $open,
            'adState' => $open ? VenueAds::ownerState($open, $premium, $today) : null,
            'adImage' => $open ? VenueAd::imageUrl($open, $cover) : null,
            'adCounts' => $open ? VenueAd::counts($open) : null,
            'coverUrl' => \App\Core\ApiFormat::uploadUrl('venues/' . $venueId, $cover),
            'lastRemoved' => !$open && $history && $history[0]['status'] === 'removed' ? $history[0] : null,
            'history' => $history,
            'editing' => $form['editing'],
            'input' => $form['input'],
            'errors' => $form['errors'],
        ]);
    }

    /**
     * Foto enviada no formulário: null (nenhuma), false (inválida) ou [tmp, extensão].
     * Mesma validação das fotos da quadra: pelo conteúdo real, até 10 MB.
     */
    private function uploadedImage()
    {
        $file = $_FILES['image'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $extension = $file['error'] === UPLOAD_ERR_OK ? ImageHelper::validateUpload($file['tmp_name'], $file['size']) : false;
        return $extension === false ? false : [$file['tmp_name'], $extension];
    }

    /** Grava a foto em uploads/ads/{id}/ como JPEG otimizado (D48); sem GD, guarda a original. */
    private function storeImage(int $adId, array $image): ?string
    {
        [$tmp, $extension] = $image;
        $dir = VenueAd::uploadDir($adId);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return null;
        }
        $base = uniqid('ad_', true);
        if (ImageHelper::savePhotoAsJpeg($tmp, $dir . $base . '.jpg')) {
            return $base . '.jpg';
        }
        return move_uploaded_file($tmp, $dir . $base . '.' . $extension) ? $base . '.' . $extension : null;
    }

    private function deleteImage(int $adId, ?string $fileName): void
    {
        if ($fileName && basename($fileName) === $fileName && is_file(VenueAd::uploadDir($adId) . $fileName)) {
            @unlink(VenueAd::uploadDir($adId) . $fileName);
        }
    }

    private function redirect(array $venue, array $query): void
    {
        $url = BASE_URL . VenueAccess::routePrefix() . '/quadras/' . (int)$venue['id'] . '/anuncio';
        header('Location: ' . $url . ($query ? '?' . http_build_query($query) : ''));
        exit;
    }
}
