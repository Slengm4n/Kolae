<?php

namespace App\Controllers;

use App\Core\AuthHelper;
use App\Core\PremiumPlan;
use App\Core\View;
use App\Core\VenueSchedule;
use App\Core\ViewHelper;
use App\Models\User;
use App\Models\Sport;
use App\Models\Venue;
use App\Models\VenueAd;
use App\Models\VenueImage;
use Exception;

class AdminController
{
    /**
     * GET /admin
     * Início do admin (D67): "Precisa de atenção" (o que espera uma ação), os números e os usuários
     * recentes. Premium, Anúncios e Fotos têm páginas próprias.
     */
    public function dashboard()
    {
        AuthHelper::checkAdmin();

        try {
            $photoStats = self::photoStats();
            $safe = function (callable $fn) {
                try {
                    return $fn();
                } catch (\Throwable $e) {
                    error_log('Admin (Início): ' . $e->getMessage());
                    return 0;
                }
            };
            $data = [
                'userName' => $_SESSION['user_name'] ?? 'Admin',
                'totalUsers' => User::countAll(),
                'totalSports' => Sport::countAll(),
                'totalLocations' => Venue::countAll(),
                'recentUsers' => User::getRecent(5),
                // O que espera uma ação do admin; cada item leva à página que resolve
                'attention' => [
                    'premium' => $safe(fn() => count(array_filter(PremiumPlan::adminList(), fn($p) => PremiumPlan::state($p) === 'requested'))),
                    'ads' => $safe(fn() => count(VenueAd::openForAdmin(VenueSchedule::now()))),
                    'photos' => $photoStats['no_thumb'] + $photoStats['heavy'],
                ],
            ];
            ViewHelper::render('admin/dashboard', $data);
        } catch (Exception $e) {
            echo "Erro ao carregar o painel do admin: " . $e->getMessage();
        }
    }

    /** GET /admin/premium: pedidos de Premium e donos com o plano (antes, um card do Início) */
    public function premium()
    {
        AuthHelper::checkAdmin();
        ViewHelper::render('admin/premium', []);
    }

    /** GET /admin/anuncios: anúncios em aberto, com remover com motivo (antes, um card do Início) */
    public function ads()
    {
        AuthHelper::checkAdmin();
        ViewHelper::render('admin/ads', []);
    }

    /** GET /admin/fotos: tamanho das fotos e otimizar as antigas (antes, um card do Início) */
    public function photos()
    {
        AuthHelper::checkAdmin();
        ViewHelper::render('admin/photos', ['photoStats' => self::photoStats()]);
    }

    /**
     * POST /admin/fotos/otimizar
     * Otimiza as fotos das quadras que já estavam no servidor (D48) e volta à página Fotos com o resumo.
     */
    public function optimizePhotos()
    {
        AuthHelper::checkAdmin();
        $_SESSION['photo_report'] = VenuePhotosController::optimizeExisting();
        header('Location: ' . BASE_URL . '/admin/fotos');
        exit;
    }

    /** Quantas fotos de quadra existem e quanto ocupam (card do dashboard). */
    private static function photoStats(): array
    {
        $count = 0;
        $bytes = 0;
        $heavy = 0;
        $noThumb = 0;
        foreach (VenueImage::findAll() as $img) {
            $path = BASE_PATH . '/uploads/venues/' . (int)$img['venue_id'] . '/' . basename($img['file_path']);
            if (!is_file($path)) {
                continue;
            }
            $size = filesize($path);
            $count++;
            $bytes += $size;
            if ($size > 600 * 1024) {
                $heavy++;
            }
            if (!is_file(\App\Core\ImageHelper::thumbPath($path))) {
                $noThumb++;
            }
        }
        return ['count' => $count, 'bytes' => $bytes, 'heavy' => $heavy, 'no_thumb' => $noThumb];
    }

    //Exibe o mapa com a localização de todas as quadras
    public function showMap()
    {
        AuthHelper::checkAdmin();

        try {
            $venuesWithCoords = Venue::getAllWithCoordinates();

            //Prepara os dados para a view
            $data = [
                'userName' => $_SESSION['user_name'] ?? 'Admin',
                //Converte os dados das quadras para JSON para ser usado pelo JavaScript no mapa
                'venuesJson' => json_encode($venuesWithCoords, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            ];

            ViewHelper::render('admin/map', $data);
        } catch (Exception $e) {
            echo "Erro ao carregar o mapa: " . $e->getMessage();
        }
    }
}
