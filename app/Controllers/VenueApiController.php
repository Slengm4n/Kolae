<?php

namespace App\Controllers;

use App\Core\BaseApiController;
use App\Models\Venue;

class VenueApiController extends BaseApiController
{

    /**
     * Retorna lista de quadras ativas com coordenadas.
     * Endpoint: GET /api/v1/venues
     */
    public function getActiveVenuesForMap()
    {
        try {

            $venues = Venue::getAllWithCoordinates();


            foreach ($venues as &$venue) {
                if (!empty($venue['image_path'])) {
                    $venue['full_image_url'] = BASE_URL . '/uploads/venues/' . $venue['id'] . '/' . $venue['image_path'];
                } else {
                    $venue['full_image_url'] = null;
                }

                if (!empty($venue['avatar_path']) && !empty($venue['user_id'])){
                    $venue['owner_image_url'] = BASE_URL . '/uploads/avatars/' . $venue['user_id'] . '/' .$venue['avatar_path'];
                } else {
                    $venue['owner_image_url'] = null;
                }
            }

            $this->sendSuccess($venues);
        } catch (\Exception $e) {
            error_log("Erro ao buscar venues para API: " . $e->getMessage());
            $this->sendError('Não foi possível buscar os locais.', 'VENUES_FETCH_FAILED', 500);
        }
    }
}
