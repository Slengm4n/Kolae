<?php

namespace App\Controllers;

use App\Core\ImageHelper;
use App\Core\VenueAccess;
use App\Core\ViewHelper;
use App\Models\VenueImage;

/**
 * Aba Fotos da quadra (D46): galeria em ordem, a primeira é a capa. O dono (ou o admin)
 * envia mais fotos, escolhe a capa, muda a ordem e apaga. Até VenueImage::MAX_PER_VENUE.
 */
class VenuePhotosController
{
    /**
     * GET {painel}/quadras/{id}/fotos
     */
    public function show($id)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        $venueId = (int)$venue['id'];

        ViewHelper::render('venues/photos', [
            'venue' => $venue,
            'activeTab' => 'photos',
            'routePrefix' => VenueAccess::routePrefix(),
            'backUrl' => VenueAccess::panelUrl(),
            'status' => VenueAccess::currentStatus($venue),
            'images' => VenueImage::findByVenueId($venueId),
            'maxImages' => VenueImage::MAX_PER_VENUE,
        ]);
    }

    /**
     * POST {painel}/quadras/{id}/fotos
     * Body: images[] (arquivos).
     */
    public function upload($id)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        $result = self::storeUploads((int)$venue['id']);

        if ($result['saved'] === 0 && $result['rejected'] === 0 && $result['over_limit'] === 0) {
            $this->redirect($venue, ['status' => 'none']);
        }
        $this->redirect($venue, ['status' => 'uploaded'] + array_filter($result));
    }

    /**
     * POST {painel}/quadras/{id}/fotos/{imageId}/capa
     */
    public function cover($id, $imageId)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        $ok = ctype_digit((string)$imageId) && VenueImage::makeCover((int)$venue['id'], (int)$imageId);
        $this->redirect($venue, ['status' => $ok ? 'cover' : 'error']);
    }

    /**
     * POST {painel}/quadras/{id}/fotos/{imageId}/mover
     * Body: direction = left | right.
     */
    public function move($id, $imageId)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        $step = ($_POST['direction'] ?? '') === 'left' ? -1 : 1;
        $ok = ctype_digit((string)$imageId) && VenueImage::move((int)$venue['id'], (int)$imageId, $step);
        // Volta para a mesma foto, sem pular para o topo da página
        $this->redirect($venue, $ok ? [] : ['status' => 'error'], $ok ? 'foto-' . (int)$imageId : '');
    }

    /**
     * POST {painel}/quadras/{id}/fotos/{imageId}/excluir
     */
    public function remove($id, $imageId)
    {
        $venue = VenueAccess::findManageableOrRedirect($id);
        $venueId = (int)$venue['id'];
        $image = ctype_digit((string)$imageId) ? VenueImage::findById((int)$imageId) : null;

        // Só apaga foto desta quadra
        if (!$image || (int)$image['venue_id'] !== $venueId) {
            $this->redirect($venue, ['status' => 'error']);
        }
        self::deleteFile($venueId, $image['file_path']);
        VenueImage::delete((int)$image['id']);
        $this->redirect($venue, ['status' => 'deleted']);
    }

    /**
     * Salva as fotos enviadas em $_FILES['images'] no fim da galeria, respeitando o limite.
     * Usado também pelo cadastro da quadra.
     * @return array ['saved' => n, 'rejected' => n (tipo ou tamanho inválido), 'over_limit' => n]
     */
    public static function storeUploads(int $venueId): array
    {
        $result = ['saved' => 0, 'rejected' => 0, 'over_limit' => 0];
        $files = $_FILES['images'] ?? null;
        if (!$files || !is_array($files['name'])) {
            return $result;
        }

        $uploadDir = BASE_PATH . '/uploads/venues/' . $venueId . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $room = VenueImage::MAX_PER_VENUE - count(VenueImage::findByVenueId($venueId));
        foreach (array_keys($files['name']) as $i) {
            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($room <= 0) {
                $result['over_limit']++;
                continue;
            }
            // Valida pelo conteúdo real e gera a extensão no servidor (ignora o nome original)
            $extension = $files['error'][$i] === UPLOAD_ERR_OK
                ? ImageHelper::validateUpload($files['tmp_name'][$i], $files['size'][$i])
                : false;
            if ($extension === false) {
                $result['rejected']++;
                continue;
            }

            // Otimiza para o app (D48); se não der, guarda a original para o upload não falhar
            $baseName = uniqid('venue_', true);
            if (ImageHelper::savePhotoAsJpeg($files['tmp_name'][$i], $uploadDir . $baseName . '.jpg')) {
                $fileName = $baseName . '.jpg';
                $stored = true;
            } else {
                $fileName = $baseName . '.' . $extension;
                $stored = move_uploaded_file($files['tmp_name'][$i], $uploadDir . $fileName);
            }
            if ($stored && VenueImage::create($venueId, $fileName)) {
                ImageHelper::makeThumb($uploadDir . $fileName);
                $result['saved']++;
                $room--;
            } else {
                $result['rejected']++;
            }
        }
        return $result;
    }

    /**
     * Otimiza as fotos que já estavam no servidor antes da D48 (botão no painel do admin).
     * Pula as que já são JPEG pequenos; pode rodar mais de uma vez. Troca o arquivo e o
     * file_path só depois que o JPEG novo foi gravado. Também cria a miniatura (D57) das
     * fotos que ainda não têm.
     * @return array ['optimized' => n, 'skipped' => n, 'failed' => n, 'saved_bytes' => n, 'thumbs' => n]
     */
    public static function optimizeExisting(): array
    {
        @set_time_limit(300);
        $report = ['optimized' => 0, 'skipped' => 0, 'failed' => 0, 'saved_bytes' => 0, 'thumbs' => 0];

        foreach (VenueImage::findAll() as $img) {
            $dir = BASE_PATH . '/uploads/venues/' . (int)$img['venue_id'] . '/';
            $old = $dir . basename($img['file_path']);
            if (!is_file($old)) {
                $report['skipped']++;
                continue;
            }
            $current = self::optimizeOne($img, $old, $dir, $report);
            if (!is_file(ImageHelper::thumbPath($current)) && ImageHelper::makeThumb($current)) {
                $report['thumbs']++;
            }
        }
        return $report;
    }

    /**
     * Otimiza uma foto (se precisar) e devolve o caminho da foto que ficou valendo.
     * Conta o resultado em $report.
     */
    private static function optimizeOne(array $img, string $old, string $dir, array &$report): string
    {
        $size = filesize($old);
        $dims = @getimagesize($old);
        $isJpeg = $dims && $dims['mime'] === 'image/jpeg';
        if ($dims && $isJpeg && max($dims[0], $dims[1]) <= ImageHelper::PHOTO_MAX_SIDE && $size <= 600 * 1024) {
            $report['skipped']++;
            return $old;
        }

        $newName = uniqid('venue_', true) . '.jpg';
        if (!ImageHelper::savePhotoAsJpeg($old, $dir . $newName)) {
            $report['failed']++;
            return $old;
        }
        // Ficou maior (foto que já era leve): mantém a original
        if (filesize($dir . $newName) >= $size) {
            unlink($dir . $newName);
            $report['skipped']++;
            return $old;
        }
        if (!VenueImage::updateFilePath((int)$img['id'], $newName)) {
            unlink($dir . $newName);
            $report['failed']++;
            return $old;
        }
        $report['saved_bytes'] += $size - filesize($dir . $newName);
        unlink($old);
        ImageHelper::deleteThumb($old);
        $report['optimized']++;
        return $dir . $newName;
    }

    /** Apaga o arquivo da foto, se existir. */
    private static function deleteFile(int $venueId, string $filePath): void
    {
        $path = BASE_PATH . '/uploads/venues/' . $venueId . '/' . basename($filePath);
        if (is_file($path)) {
            unlink($path);
        }
        ImageHelper::deleteThumb($path);
    }

    private function redirect(array $venue, array $query, string $anchor = ''): void
    {
        $url = BASE_URL . VenueAccess::routePrefix() . '/quadras/' . (int)$venue['id'] . '/fotos';
        if ($query) {
            $url .= '?' . http_build_query($query);
        }
        header('Location: ' . $url . ($anchor !== '' ? '#' . $anchor : ''));
        exit;
    }
}
