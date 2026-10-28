<?php

namespace App\Controllers;

use App\Core\BaseApiController;
use App\Models\Sport;

class SportApiController extends BaseApiController
{

    /**
     * Retorna lista de esportes ativos.
     * Endpoint: GET /api/v1/sports
     */

    public function getActiveSports()
    {
        try {
            // Só os campos do contrato (docs/api.md), sem status nem datas internas
            $sports = array_map(fn($s) => [
                'id' => (int)$s['id'],
                'name' => $s['name'],
                'description' => $s['description'],
                'icon_path' => $s['icon_path']
            ], Sport::getAll());
            $this->sendSuccess($sports);
        } catch (\Exception $e) {
            error_log("Erro ao buscar sports para API: " . $e->getMessage());
            $this->sendError('Não foi possível buscar os esportes.', 'SPORTS_FETCH_FAILED', 500);
        }
    }
}
