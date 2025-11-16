<?php
namespace App\Controllers;

use App\Core\BaseApiController;
use App\Models\Match;
use App\Models\Venue;
use App\Models\Sport;
use DateTime;
use App\Models\Game;

class GameApiController extends BaseApiController {

    /**
     * Cria uma nova partida.
     * Endpoint: POST /api/v1/matches
     * Requer autenticação JWT.
     */
    public function createGame() {
        // 1. Autentica a requisição (verifica o token JWT)
        $this->authenticateRequest();
        $creatorId = $this->userId; // Pega o ID do usuário logado do token

        // 2. Pega e valida os dados de entrada (idealmente do corpo JSON)
         $input = json_decode(file_get_contents('php://input'), true);
         if (json_last_error() !== JSON_ERROR_NONE) {
             $this->sendError('Corpo da requisição JSON inválido.', 'INVALID_JSON', 400);
         }

        $venueId = filter_var($input['venue_id'] ?? null, FILTER_VALIDATE_INT);
        $sportId = filter_var($input['sport_id'] ?? null, FILTER_VALIDATE_INT);
        $startTimeStr = $input['start_time'] ?? null; // Ex: "2025-10-28 20:00:00"
        $duration = filter_var($input['duration_minutes'] ?? 60, FILTER_VALIDATE_INT, ['options' => ['default' => 60]]); // Padrão 60

        // Validações básicas
        if (!$venueId || !$sportId || !$startTimeStr) {
            $this->sendError('Campos obrigatórios ausentes: venue_id, sport_id, start_time.', 'MISSING_FIELDS', 400);
        }

        // Valida o formato da data/hora e se é no futuro
        $startTime = DateTime::createFromFormat('Y-m-d H:i:s', $startTimeStr);
        $now = new DateTime();
        if ($startTime === false || $startTime < $now) {
            $this->sendError('Formato de start_time inválido (use YYYY-MM-DD HH:MM:SS) ou data no passado.', 'INVALID_START_TIME', 400);
        }

        // Valida se venue e sport existem (exemplo)
        if (!Venue::findById($venueId)) {
             $this->sendError('Quadra (venue) não encontrada.', 'VENUE_NOT_FOUND', 404);
        }
         if (!Sport::findById($sportId)) {
             $this->sendError('Esporte não encontrado.', 'SPORT_NOT_FOUND', 404);
        }

        // 3. Prepara os dados para o Model
        $gameData = [
            'venue_id' => $venueId,
            'sport_id' => $sportId,
            'creator_user_id' => $creatorId,
            'start_time' => $startTime->format('Y-m-d H:i:s'), // Formata para o DB
            'duration_minutes' => $duration
        ];

        // 4. Tenta criar a partida usando o Model
        try {
            $matchId = Game::create($gameData);
            if ($matchId) {
                $this->sendSuccess(['match_id' => $matchId], 201); // 201 Created
            } else {
                $this->sendError('Falha ao salvar a partida no banco de dados.', 'DB_INSERT_FAILED', 500);
            }
        } catch (\Exception $e) {
            error_log("Erro crítico ao criar partida: " . $e->getMessage());
            $this->sendError('Ocorreu um erro inesperado ao criar a partida.', 'MATCH_CREATION_FAILED', 500);
        }
    }
}