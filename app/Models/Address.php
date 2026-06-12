<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Class Address
 * Gerencia operações de banco de dados para endereços com relacionamentos hierárquicos e Geocodificação.
 */
class Address
{
    /**
     * Cria um novo endereço lidando com as tabelas relacionais (states, cities, neighborhoods, streets)
     * e busca coordenadas automaticamente.
     */
    public static function create(array $data)
    {
        $pdo = Database::getConnection();

        try {
            // Inicia uma transação para garantir que tudo seja salvo ou nada
            $pdo->beginTransaction();

            // 1. Resolve a hierarquia geográfica para obter o street_id
            $streetId = self::resolveAddressHierarchy($pdo, $data);

            if (!$streetId) {
                throw new \Exception("Falha ao resolver a hierarquia de endereço.");
            }

            // 2. Busca coordenadas
            $coords = self::getCoordinates($data);
            $latitude = $coords['lat'] ?? null;
            $longitude = $coords['lng'] ?? null;

            // 3. Insere na tabela de endereços final
            $query = "INSERT INTO addresses (street_id, cep, number, complement, latitude, longitude) 
                      VALUES (:street_id, :cep, :number, :complement, :latitude, :longitude)";

            $stmt = $pdo->prepare($query);

            $params = [
                ':street_id'  => $streetId,
                ':cep'        => $data['cep'] ?? null,
                ':number'     => $data['number'] ?? null,
                ':complement' => $data['complement'] ?? null,
                ':latitude'   => $latitude,
                ':longitude'  => $longitude
            ];

            if ($stmt->execute($params)) {
                $addressId = $pdo->lastInsertId();
                $pdo->commit();
                return $addressId;
            }

            $pdo->rollBack();
            return false;

        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("Erro ao salvar endereço: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza um endereço existente.
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            // 1. Resolve a hierarquia para ver se a rua/cidade/etc mudou e pega o novo street_id
            $streetId = self::resolveAddressHierarchy($pdo, $data);

            if (!$streetId) {
                throw new \Exception("Falha ao resolver a hierarquia de endereço.");
            }

            // 2. Recalcula coordenadas pois o endereço pode ter mudado
            $coords = self::getCoordinates($data);
            $latitude = $coords['lat'] ?? null;
            $longitude = $coords['lng'] ?? null;

            // 3. Atualiza a tabela addresses
            $query = "UPDATE addresses SET 
                        street_id = :street_id,
                        cep = :cep, 
                        number = :number, 
                        complement = :complement, 
                        latitude = :latitude,
                        longitude = :longitude
                      WHERE id = :id";

            $stmt = $pdo->prepare($query);

            $params = [
                ':street_id'  => $streetId,
                ':cep'        => $data['cep'] ?? null,
                ':number'     => $data['number'] ?? null,
                ':complement' => $data['complement'] ?? null,
                ':latitude'   => $latitude,
                ':longitude'  => $longitude,
                ':id'         => $id
            ];

            $result = $stmt->execute($params);
            $pdo->commit();
            return $result;

        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("Erro ao atualizar endereço: " . $e->getMessage());
            return false;
        }
    }

    public static function findById(int $id)
    {
        $pdo = Database::getConnection();
        // Faz JOIN com todas as tabelas para retornar os nomes reais ao invés de apenas IDs
        $query = "SELECT a.id, a.cep, a.number, a.complement, a.latitude, a.longitude,
                         s.name AS street, n.name AS neighborhood, c.name AS city, st.name AS state, co.name AS country
                  FROM addresses a
                  JOIN streets s ON a.street_id = s.id
                  JOIN neighborhoods n ON s.neighborhood_id = n.id
                  JOIN cities c ON n.city_id = c.id
                  JOIN states st ON c.state_id = st.id
                  JOIN countries co ON st.country_id = co.id
                  WHERE a.id = :id";
                  
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lida com a inserção hierárquica (Country -> State -> City -> Neighborhood -> Street)
     * Retorna o ID da Street para ser usado na tabela Address.
     */
    private static function resolveAddressHierarchy(PDO $pdo, array $data): ?int
    {
        // 1. Country (Assumindo 'Brasil' como padrão se não for fornecido, já que as Cidades costumam vir do ViaCEP)
        $countryName = $data['country'] ?? 'Brasil';
        $countryId = self::findOrCreate($pdo, 'countries', ['name' => $countryName]);

        // 2. State
        $stateName = $data['state'] ?? 'Desconhecido';
        $stateId = self::findOrCreate($pdo, 'states', ['name' => $stateName, 'country_id' => $countryId]);

        // 3. City
        $cityName = $data['city'] ?? 'Desconhecido';
        $cityId = self::findOrCreate($pdo, 'cities', ['name' => $cityName, 'state_id' => $stateId]);

        // 4. Neighborhood
        $neighborhoodName = $data['neighborhood'] ?? 'Desconhecido';
        $neighborhoodId = self::findOrCreate($pdo, 'neighborhoods', ['name' => $neighborhoodName, 'city_id' => $cityId]);

        // 5. Street
        $streetName = $data['street'] ?? 'Desconhecido';
        $streetId = self::findOrCreate($pdo, 'streets', ['name' => $streetName, 'neighborhood_id' => $neighborhoodId]);

        return $streetId;
    }

    /**
     * Busca um registro ou o cria se não existir. Retorna o ID.
     */
    private static function findOrCreate(PDO $pdo, string $table, array $conditions): ?int
    {
        // Constrói a query de SELECT
        $whereClauses = [];
        $params = [];
        foreach ($conditions as $key => $value) {
            $whereClauses[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        $whereSql = implode(' AND ', $whereClauses);

        $selectQuery = "SELECT id FROM $table WHERE $whereSql LIMIT 1";
        $stmt = $pdo->prepare($selectQuery);
        $stmt->execute($params);
        $id = $stmt->fetchColumn();

        if ($id) {
            return (int)$id; // Retorna se já existe
        }

        // Se não existe, faz o INSERT
        $insertColumns = implode(', ', array_keys($conditions));
        $insertPlaceholders = implode(', ', array_keys($params));
        
        $insertQuery = "INSERT INTO $table ($insertColumns) VALUES ($insertPlaceholders)";
        $insertStmt = $pdo->prepare($insertQuery);
        
        if ($insertStmt->execute($params)) {
            return (int)$pdo->lastInsertId();
        }

        return null; // Falha na inserção
    }

    /**
     * Método auxiliar privado para obter Latitude e Longitude.
     */
    private static function getCoordinates(array $data): ?array
    {
        if (!defined('GOOGLE_MAPS_API_KEY') || empty(GOOGLE_MAPS_API_KEY)) {
            return null;
        }

        $street = $data['street'] ?? '';
        $number = $data['number'] ?? '';
        $neighborhood = $data['neighborhood'] ?? '';
        $city = $data['city'] ?? '';
        $state = $data['state'] ?? '';
        $cep = $data['cep'] ?? '';

        $addressString = "{$street}, {$number} - {$neighborhood}, {$city} - {$state}, {$cep}";
        $fullAddress = urlencode(trim($addressString, " ,-"));

        if (empty($fullAddress)) return null;

        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$fullAddress}&key=" . GOOGLE_MAPS_API_KEY;

        $responseJson = @file_get_contents($url);

        if ($responseJson) {
            $response = json_decode($responseJson);
            if ($response && $response->status == 'OK' && !empty($response->results)) {
                $location = $response->results[0]->geometry->location;
                return [
                    'lat' => $location->lat,
                    'lng' => $location->lng
                ];
            }
        }

        return null;
    }
}