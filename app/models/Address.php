<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Class Address
 * Gerencia operações de banco de dados para endereços com Geocodificação.
 */
class Address
{
    /**
     * Cria um novo endereço e busca coordenadas automaticamente.
     */
    public static function create(array $data)
    {
        // Busca coordenadas antes de salvar
        $coords = self::getCoordinates($data);
        $latitude = $coords['lat'] ?? null;
        $longitude = $coords['lng'] ?? null;

        $pdo = Database::getConnection();

        $query = "INSERT INTO addresses (cep, street, number, neighborhood, complement, city, state, latitude, longitude) 
                  VALUES (:cep, :street, :number, :neighborhood, :complement, :city, :state, :latitude, :longitude)";

        $stmt = $pdo->prepare($query);

        $params = [
            ':cep' => $data['cep'],
            ':street' => $data['street'],
            ':number' => $data['number'],
            ':neighborhood' => $data['neighborhood'],
            ':complement' => $data['complement'] ?? null,
            ':city' => $data['city'],
            ':state' => $data['state'],
            ':latitude' => $latitude,
            ':longitude' => $longitude
        ];

        if ($stmt->execute($params)) {
            return $pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Atualiza um endereço e RECALCULA as coordenadas.
     */
    public static function update(int $id, array $data): bool
    {
        // Recalcula coordenadas pois o endereço mudou
        $coords = self::getCoordinates($data);
        $latitude = $coords['lat'] ?? null;
        $longitude = $coords['lng'] ?? null;

        $pdo = Database::getConnection();

        $query = "UPDATE addresses SET 
                    cep = :cep, 
                    street = :street, 
                    number = :number, 
                    neighborhood = :neighborhood, 
                    complement = :complement, 
                    city = :city, 
                    state = :state,
                    latitude = :latitude,
                    longitude = :longitude
                  WHERE id = :id";

        $stmt = $pdo->prepare($query);

        $params = [
            ':cep' => $data['cep'],
            ':street' => $data['street'],
            ':number' => $data['number'],
            ':neighborhood' => $data['neighborhood'],
            ':complement' => $data['complement'] ?? null,
            ':city' => $data['city'],
            ':state' => $data['state'],
            ':latitude' => $latitude,
            ':longitude' => $longitude,
            ':id' => $id
        ];

        return $stmt->execute($params);
    }

    public static function findById(int $id)
    {
        $pdo = Database::getConnection();
        $query = "SELECT * FROM addresses WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Método auxiliar privado para obter Latitude e Longitude.
     */
    private static function getCoordinates(array $data): ?array
    {
        // Verifica se a chave está definida e não está vazia
        if (!defined('GOOGLE_MAPS_API_KEY') || empty(GOOGLE_MAPS_API_KEY)) {
            return null;
        }

        // Monta string do endereço
        $addressString = "{$data['street']}, {$data['number']} - {$data['neighborhood']}, {$data['city']} - {$data['state']}, {$data['cep']}";
        $fullAddress = urlencode($addressString);

        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$fullAddress}&key=" . GOOGLE_MAPS_API_KEY;

        // Usa @ para suprimir warnings, mas idealmente usar cURL em produção
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
