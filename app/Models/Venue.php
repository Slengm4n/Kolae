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
   public static function getAll()
    {
        try {
            $conn = \App\Core\Database::getConnection();
            
            // Fazemos os JOINs encadeados E buscamos a imagem de capa!
            $sql = "SELECT v.*, 
                           s.name AS street, 
                           a.number, 
                           a.cep, 
                           a.complement,
                           n.name AS neighborhood,
                           c.name AS city,
                           st.name AS state,
                           (SELECT file_path FROM venues_images vi WHERE vi.venue_id = v.id ORDER BY id ASC LIMIT 1) AS image_path
                    FROM venues v
                    JOIN addresses a ON v.address_id = a.id
                    JOIN streets s ON a.street_id = s.id
                    JOIN neighborhoods n ON s.neighborhood_id = n.id
                    JOIN cities c ON n.city_id = c.id
                    JOIN states st ON c.state_id = st.id";
            
            $stmt = $conn->query($sql);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Erro ao buscar todas as quadras para o mapa: " . $e->getMessage());
            return []; // Retorna array vazio para não quebrar a tela do mapa
        }
    }

    /**
     * Busca TODAS as quadras no sistema para o painel de admin.
     * @return array
     */
    public static function getAllForAdmin()
    {
        try {
            $conn = \App\Core\Database::getConnection();
            
            // Fazemos os JOINs encadeados para as tabelas de endereço e buscamos a capa
            $sql = "SELECT v.*, 
                           s.name AS street, 
                           a.number, 
                           a.cep, 
                           a.complement,
                           n.name AS neighborhood,
                           c.name AS city,
                           st.name AS state,
                           (SELECT file_path FROM venues_images vi WHERE vi.venue_id = v.id ORDER BY id ASC LIMIT 1) AS image_path
                    FROM venues v
                    JOIN addresses a ON v.address_id = a.id
                    JOIN streets s ON a.street_id = s.id
                    JOIN neighborhoods n ON s.neighborhood_id = n.id
                    JOIN cities c ON n.city_id = c.id
                    JOIN states st ON c.state_id = st.id
                    ORDER BY v.created_at DESC";
            
            $stmt = $conn->query($sql);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Erro ao buscar todas as quadras para o admin: " . $e->getMessage());
            return []; // Retorna um array vazio para não quebrar a tela de listagem
        }
    }

    /**
     * Busca todas as quadras de um utilizador específico.
     * @param int $userId
     * @return array
     */
    public static function findByUserId($userId)
    {
        try {
            $conn = \App\Core\Database::getConnection();
            
            // Adicionamos uma subquery (SELECT file_path...) para buscar a capa da quadra
            $sql = "SELECT v.*, 
                           s.name AS street, 
                           a.number, 
                           a.cep, 
                           a.complement,
                           (SELECT file_path FROM venues_images vi WHERE vi.venue_id = v.id ORDER BY id ASC LIMIT 1) AS image_path
                    FROM venues v
                    JOIN addresses a ON v.address_id = a.id
                    JOIN streets s ON a.street_id = s.id
                    WHERE v.user_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Erro ao buscar as quadras do usuário: " . $e->getMessage());
            return []; // Retorna array vazio em caso de erro para não quebrar a tela
        }
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
    public static function getAllWithCoordinates()
    {
        try {
            $conn = \App\Core\Database::getConnection();
            
            // Fazemos os JOINs encadeados, buscamos as coordenadas reais e a imagem de capa
            $sql = "SELECT v.*, 
                           s.name AS street, 
                           a.number, 
                           a.cep, 
                           a.complement,
                           a.latitude,
                           a.longitude,
                           n.name AS neighborhood,
                           c.name AS city,
                           st.name AS state,
                           (SELECT file_path FROM venues_images vi WHERE vi.venue_id = v.id ORDER BY id ASC LIMIT 1) AS image_path
                    FROM venues v
                    JOIN addresses a ON v.address_id = a.id
                    JOIN streets s ON a.street_id = s.id
                    JOIN neighborhoods n ON s.neighborhood_id = n.id
                    JOIN cities c ON n.city_id = c.id
                    JOIN states st ON c.state_id = st.id
                    WHERE a.latitude IS NOT NULL AND a.longitude IS NOT NULL";
            
            $stmt = $conn->query($sql);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Erro ao buscar quadras com coordenadas para o mapa: " . $e->getMessage());
            return []; // Retorna array vazio para não quebrar a tela
        }
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
