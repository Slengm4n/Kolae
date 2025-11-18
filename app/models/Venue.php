<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Class Venue
 * Gerencia todas as operações de banco de dados para a entidade de quadra/local.
 */
class Venue
{
    /**
     * Conta o total de quadras disponíveis.
     * @return int
     */
    public static function countAll(): int
    {
        $pdo = Database::getConnection();
        $query = "SELECT COUNT(id) FROM venues WHERE status = 'available'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Busca todas as quadras disponíveis (para utilizadores finais).
     * @return array
     */
    public static function getAll(): array
    {
        $pdo = Database::getConnection();
        $query = "SELECT v.*, a.street, a.number, a.city 
                  FROM venues v
                  JOIN addresses a ON v.address_id = a.id
                  WHERE v.status = 'available'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca TODAS as quadras no sistema para o painel de admin.
     * @return array
     */
    public static function getAllForAdmin(): array
    {
        $pdo = Database::getConnection();
        $query = "SELECT v.*, u.name as owner_name, a.street, a.number
              FROM venues v
              LEFT JOIN users u ON v.user_id = u.id
              LEFT JOIN addresses a ON v.address_id = a.id
              ORDER BY v.created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca todas as quadras de um utilizador específico.
     * @param int $userId
     * @return array
     */
    public static function findByUserId(int $userId): array
    {
        $pdo = Database::getConnection();
        $query = "SELECT v.*, a.street, a.number,
                         (SELECT vi.file_path FROM venue_images vi WHERE vi.venue_id = v.id ORDER BY vi.id DESC LIMIT 1) as image_path
                  FROM venues v
                  LEFT JOIN addresses a ON v.address_id = a.id
                  WHERE v.user_id = :user_id AND v.status = 'available'
                  ORDER BY v.created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca uma quadra específica pelo ID, com todos os detalhes do endereço.
     * @param int $id
     * @return mixed
     */
    public static function findById(int $id)
    {
        $pdo = Database::getConnection();
        $query = "SELECT v.*, a.street, a.city, a.state, a.cep, a.number, a.neighborhood, a.complement
                  FROM venues v
                  JOIN addresses a ON v.address_id = a.id
                  WHERE v.id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca todas as quadras com coordenadas para exibição no mapa.
     * @return array
     */
    public static function getAllWithCoordinates(): array
    {
        $pdo = Database::getConnection();
        $query = "SELECT v.id, v.name, a.street, a.number, a.city, a.latitude, a.longitude,
                         u.name as owner_name,
                         (SELECT vi.file_path FROM venue_images vi WHERE vi.venue_id = v.id ORDER BY vi.id DESC LIMIT 1) as image_path
                  FROM venues v
                  JOIN addresses a ON v.address_id = a.id
                  LEFT JOIN users u ON v.user_id = u.id WHERE v.status = 'available'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cria uma nova quadra no banco de dados.
     * @param array $data Dados da quadra a serem inseridos.
     * @return string|false Retorna o ID da nova quadra em caso de sucesso, ou false em caso de falha.
     */
    public static function create(array $data)
    {
        $pdo = Database::getConnection();
        $data['status'] = $data['status'] ?? 'available';

        $query = "INSERT INTO venues (user_id, address_id, name, average_price_per_hour, court_capacity, has_leisure_area, leisure_area_capacity, floor_type, has_lighting, is_covered, status) 
                  VALUES (:user_id, :address_id, :name, :average_price_per_hour, :court_capacity, :has_leisure_area, :leisure_area_capacity, :floor_type, :has_lighting, :is_covered, :status)";

        $stmt = $pdo->prepare($query);

        if ($stmt->execute($data)) {
            return $pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Atualiza os dados de uma quadra existente.
     * @param int $id ID da quadra a ser atualizada.
     * @param array $data Dados a serem atualizados.
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return true;
        }

        $pdo = Database::getConnection();
        $fields = [];
        foreach (array_keys($data) as $key) {
            $fields[] = "$key = :$key";
        }
        $query = "UPDATE venues SET " . implode(', ', $fields) . " WHERE id = :id";

        $stmt = $pdo->prepare($query);
        $data['id'] = $id;

        return $stmt->execute($data);
    }

    /**
     * Realiza um soft delete da quadra, alterando seu status para 'unavailable'.
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        return self::update($id, ['status' => 'unavailable']);
    }
}
