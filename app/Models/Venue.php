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
     * Tipos de piso aceitos (chave gravada no banco => rótulo exibido).
     * Fonte da verdade: database/kolae.dbml (venues.floor_type).
     */
    public const FLOOR_TYPES = [
        'synthetic' => 'Grama Sintética',
        'court'     => 'Cimento / Poliesportivo',
        'sand'      => 'Areia',
        'clay'      => 'Saibro',
        'grass'     => 'Grama Natural',
        'wood'      => 'Madeira / Taco',
    ];

    /** Status aceitos (venues.status). */
    public const STATUSES = ['available', 'unavailable', 'maintenance'];

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
        // LEFT JOIN na hierarquia: uma quadra com endereço incompleto ainda abre para edição
        $query = "SELECT v.*,
                         a.cep, a.number, a.complement, a.latitude, a.longitude,
                         s.name AS street,
                         n.name AS neighborhood,
                         c.name AS city,
                         st.name AS state
                  FROM venues v
                  JOIN addresses a ON v.address_id = a.id
                  LEFT JOIN streets s ON a.street_id = s.id
                  LEFT JOIN neighborhoods n ON s.neighborhood_id = n.id
                  LEFT JOIN cities c ON n.city_id = c.id
                  LEFT JOIN states st ON c.state_id = st.id
                  WHERE v.id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Colunas e JOINs comuns às consultas da API. LEFT JOIN na hierarquia do endereço
     * para uma quadra com endereço incompleto continuar aparecendo.
     * Quadra desativada no site (status 'unavailable', D34) nunca vai para a API.
     */
    private const API_SELECT = "SELECT v.id, v.user_id, v.name, v.description, v.average_price_per_hour,
                                       v.court_capacity, v.has_leisure_area, v.leisure_area_capacity,
                                       v.floor_type, v.has_lighting, v.is_covered, v.status,
                                       a.cep, a.number, a.complement, a.latitude, a.longitude,
                                       s.name AS street, n.name AS neighborhood, c.name AS city, st.name AS state,
                                       (SELECT file_path FROM venues_images vi WHERE vi.venue_id = v.id ORDER BY vi.id ASC LIMIT 1) AS image_path
                                FROM venues v
                                JOIN addresses a ON v.address_id = a.id
                                LEFT JOIN streets s ON a.street_id = s.id
                                LEFT JOIN neighborhoods n ON s.neighborhood_id = n.id
                                LEFT JOIN cities c ON n.city_id = c.id
                                LEFT JOIN states st ON c.state_id = st.id
                                WHERE v.status <> 'unavailable' AND v.deleted_at IS NULL";

    /**
     * Quadras para GET /api/v1/venues, com os filtros que o banco resolve.
     * Status e distância são calculados depois, no VenueApiController.
     * @param array $filters q, sport_id, max_price_cents, is_covered, has_lighting, has_leisure_area, floor_type
     * @return array Linhas em ordem alfabética
     */
    public static function searchForApi(array $filters): array
    {
        $sql = self::API_SELECT;
        $params = [];

        if (isset($filters['q'])) {
            $sql .= " AND (v.name LIKE :q1 OR n.name LIKE :q2 OR c.name LIKE :q3)";
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $params += [':q1' => $like, ':q2' => $like, ':q3' => $like];
        }
        if (isset($filters['sport_id'])) {
            $sql .= " AND EXISTS (SELECT 1 FROM venue_sports vs WHERE vs.venue_id = v.id AND vs.sport_id = :sport_id)";
            $params[':sport_id'] = $filters['sport_id'];
        }
        if (isset($filters['max_price_cents'])) {
            // Inclui as grátis (0); as sem preço (NULL) ficam de fora (D31)
            $sql .= " AND v.average_price_per_hour IS NOT NULL AND v.average_price_per_hour * 100 <= :max_price";
            $params[':max_price'] = $filters['max_price_cents'];
        }
        foreach (['is_covered', 'has_lighting', 'has_leisure_area'] as $flag) {
            if (isset($filters[$flag])) {
                $sql .= " AND v.$flag = :$flag";
                $params[":$flag"] = $filters[$flag] ? 1 : 0;
            }
        }
        if (isset($filters['floor_type'])) {
            $sql .= " AND v.floor_type = :floor_type";
            $params[':floor_type'] = $filters['floor_type'];
        }

        $stmt = Database::getConnection()->prepare($sql . " ORDER BY v.name ASC, v.id ASC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Uma quadra para GET /api/v1/venues/{id}, com os dados públicos do dono.
     * @return array|false
     */
    public static function findForApi(int $id)
    {
        $sql = str_replace(
            "FROM venues v",
            ", u.name AS owner_name, u.avatar_path AS owner_avatar_path, u.created_at AS owner_created_at, u.whatsapp AS owner_whatsapp
             FROM venues v JOIN users u ON u.id = v.user_id",
            self::API_SELECT
        ) . " AND v.id = :id";

        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([':id' => $id]);
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
        $data['description'] = $data['description'] ?? null; // D30, opcional

        $query = "INSERT INTO venues (user_id, address_id, name, description, average_price_per_hour, court_capacity, has_leisure_area, leisure_area_capacity, floor_type, has_lighting, is_covered, status)
                  VALUES (:user_id, :address_id, :name, :description, :average_price_per_hour, :court_capacity, :has_leisure_area, :leisure_area_capacity, :floor_type, :has_lighting, :is_covered, :status)";

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
