<?php

namespace App\Controllers;

use App\Core\AuthHelper;
use App\Core\View;
use App\Core\ViewHelper;
use App\Models\User;
use App\Models\Sport;
use App\Models\Venue;
use App\Models\VenueImage;
use Exception;

class AdminController
{
    //Exibe o dashboard
    public function dashboard()
    {
        //Garante que apenas administradores possam acessar esta página
        AuthHelper::checkAdmin();

        try {
            //Prepara os dados que serão enviados para a view
            $data = [
                'userName' => $_SESSION['user_name'] ?? 'Admin',
                //Busca os totais diretamente do banco para mais eficiência
                'totalUsers' => User::countAll(),
                'totalSports' => Sport::countAll(),
                'totalLocations' => Venue::countAll(),
                'recentUsers' => User::getRecent(5),
                'photoStats' => self::photoStats()
            ];

            //Renderiza a view do dashboard, passando os dados para ela
            ViewHelper::render('admin/dashboard', $data);
        } catch (Exception $e) {
            echo "Erro ao carregar o dashboard: " . $e->getMessage();
        }
    }

    /**
     * POST /admin/fotos/otimizar
     * Otimiza as fotos das quadras que já estavam no servidor (D48) e volta ao dashboard com o resumo.
     */
    public function optimizePhotos()
    {
        AuthHelper::checkAdmin();
        $_SESSION['photo_report'] = VenuePhotosController::optimizeExisting();
        header('Location: ' . BASE_URL . '/admin#fotos');
        exit;
    }

    /** Quantas fotos de quadra existem e quanto ocupam (card do dashboard). */
    private static function photoStats(): array
    {
        $count = 0;
        $bytes = 0;
        $heavy = 0;
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
        }
        return ['count' => $count, 'bytes' => $bytes, 'heavy' => $heavy];
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
