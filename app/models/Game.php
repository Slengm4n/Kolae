<?php
namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

class Game{



    /**
     * Cria uma nova partida no banco de dados.
     * @param array $data Dados da partida (venue_id, sport_id, creator_user_id, start_time, [duration_minutes])
     * @return string|false Retorna o ID da nova partida ou false em caso de erro.
     */
    public static function create(array $data) {
        // Define valores padrão se não forem fornecidos
        $data['duration_minutes'] = $data['duration_minutes'] ?? 60; // Padrão 60 min
        $data['status'] = 'scheduled'; // Sempre começa como agendada

        $sql = "INSERT INTO matches (venue_id, sport_id, creator_user_id, start_time, duration_minutes, status)
                VALUES (:venue_id, :sport_id, :creator_user_id, :start_time, :duration_minutes, :status)";

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare($sql);

            // Bind dos parâmetros para segurança
            $stmt->bindParam(':venue_id', $data['venue_id'], PDO::PARAM_INT);
            $stmt->bindParam(':sport_id', $data['sport_id'], PDO::PARAM_INT);
            $stmt->bindParam(':creator_user_id', $data['creator_user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':start_time', $data['start_time']); // Assumindo formato YYYY-MM-DD HH:MM:SS
            $stmt->bindParam(':duration_minutes', $data['duration_minutes'], PDO::PARAM_INT);
            $stmt->bindParam(':status', $data['status']);

            if ($stmt->execute()) {
                return $pdo->lastInsertId();
            } else {
                return false;
            }
        } catch (PDOException $e) {
            // Em produção, logar o erro $e->getMessage() em vez de mostrá-lo
            error_log("Erro ao criar partida: " . $e->getMessage());
            return false;
        }
    }


    public static function findById(int $id)
    {
        $pdo = Database::getConnection();
        $query = "SELECT * FROM matches WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        return $match;
    }
}
