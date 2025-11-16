<?php

namespace App\Models;

use App\Core\Database;

use PDO;

/**
 * Class VenueImage
 * Gerencia as operações de banco de dados para as imagens associadas a uma quadra.
 */
class VenueImage
{
    /**
     * Salva o caminho de uma imagem no banco de dados, associando-a a uma quadra.
     * @param int $venueId O ID da quadra.
     * @param string $filePath O caminho do arquivo da imagem.
     * @return bool Retorna true em caso de sucesso, false em caso de falha.
     */
    public static function create(int $venueId, string $filePath): bool
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO venue_images (venue_id, file_path) VALUES (:venue_id, :file_path)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':venue_id', $venueId, PDO::PARAM_INT);
        $stmt->bindParam(':file_path', $filePath);
        return $stmt->execute();
    }

    /**
     * Busca todas as imagens associadas a uma quadra específica.
     * @param int $venueId O ID da quadra.
     * @return array Retorna um array com os registros das imagens.
     */
    public static function findByVenueId(int $venueId): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM venue_images WHERE venue_id = :venue_id ORDER BY id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':venue_id', $venueId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id)
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM venue_images WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    /**
     * Deleta uma imagem específica pelo seu ID.
     * @param int $imageId O ID da imagem a ser deletada.
     * @return bool Retorna true em caso de sucesso, false em caso de falha.
     */
    public static function delete(int $imageId): bool
    {
        $pdo = Database::getConnection();
        $sql = "DELETE FROM venue_images WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $imageId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
