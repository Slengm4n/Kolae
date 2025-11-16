<?php
namespace App\Controllers;

use App\Core\BaseApiController;
use App\Models\Venue;

class VenueApiController extends BaseApiController {

    /**
     * Retorna lista de quadras ativas com coordenadas.
     * Endpoint: GET /api/v1/venues
     */
    public function getActiveVenuesForMap() {
        try {
            // Você pode precisar adaptar getAllWithCoordinates para filtrar por status='available'
            // ou criar um novo método no Model Venue.php
            $venues = Venue::getAllWithCoordinates(); // Assumindo que este método existe e funciona

            // Adiciona o caminho completo da imagem (exemplo)
            foreach ($venues as &$venue) { // usa '&' para modificar o array original
               if (!empty($venue['image_path'])) {
                   // Certifique-se que BASE_URL está definida corretamente
                   $venue['full_image_url'] = BASE_URL . '/uploads/venues/' . $venue['id'] . '/' . $venue['image_path'];
               } else {
                   $venue['full_image_url'] = null; // Ou uma URL de placeholder
               }
            }

            $this->sendSuccess($venues);
        } catch (\Exception $e) {
            error_log("Erro ao buscar venues para API: " . $e->getMessage());
            $this->sendError('Não foi possível buscar os locais.', 'VENUES_FETCH_FAILED', 500);
        }
    }

    // --- Adicionar outros métodos se necessário (ex: getVenueDetails) ---
    // public function getVenueDetails($id) { ... }

}