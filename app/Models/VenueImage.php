<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Class VenueImage
 * Gerencia as operações de banco de dados para as imagens associadas a uma quadra.
 * As fotos têm ordem (coluna position, patch 006): a primeira é a capa (D46).
 */
class VenueImage
{
    /** Máximo de fotos por quadra. */
    public const MAX_PER_VENUE = 10;

    /**
     * Salva o caminho de uma imagem no banco de dados, no fim da galeria da quadra.
     * @param int $venueId O ID da quadra.
     * @param string $filePath O caminho do arquivo da imagem.
     * @return bool Retorna true em caso de sucesso, false em caso de falha.
     */
    public static function create(int $venueId, string $filePath): bool
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO venues_images (venue_id, file_path, position)
                SELECT :venue_id, :file_path, COALESCE(MAX(position) + 1, 0) FROM venues_images WHERE venue_id = :venue_id2";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':venue_id', $venueId, PDO::PARAM_INT);
        $stmt->bindValue(':venue_id2', $venueId, PDO::PARAM_INT);
        $stmt->bindValue(':file_path', $filePath);
        return $stmt->execute();
    }

    /**
     * Busca todas as imagens de uma quadra, na ordem da galeria (a capa primeiro).
     * @param int $venueId O ID da quadra.
     * @return array Retorna um array com os registros das imagens.
     */
    public static function findByVenueId(int $venueId): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM venues_images WHERE venue_id = :venue_id ORDER BY position ASC, id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':venue_id', $venueId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id)
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM venues_images WHERE id = :id";
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
        $sql = "DELETE FROM venues_images WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $imageId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /** Todas as fotos (otimização das antigas, D48). */
    public static function findAll(): array
    {
        return Database::getConnection()->query("SELECT * FROM venues_images ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Troca o arquivo de uma foto (mesma posição na galeria). */
    public static function updateFilePath(int $imageId, string $filePath): bool
    {
        $stmt = Database::getConnection()->prepare("UPDATE venues_images SET file_path = :file_path WHERE id = :id");
        return $stmt->execute([':file_path' => $filePath, ':id' => $imageId]);
    }

    /**
     * Põe a foto no começo da galeria (vira a capa). As outras mantêm a ordem.
     * @return bool false se a foto não for desta quadra.
     */
    public static function makeCover(int $venueId, int $imageId): bool
    {
        $ids = self::orderedIds($venueId);
        $index = array_search($imageId, $ids, true);
        if ($index === false) {
            return false;
        }
        array_splice($ids, $index, 1);
        array_unshift($ids, $imageId);
        return self::saveOrder($venueId, $ids);
    }

    /**
     * Troca a foto de lugar com a vizinha. $step = -1 (para a esquerda) ou 1 (para a direita).
     * @return bool false se a foto não for desta quadra ou já estiver na ponta.
     */
    public static function move(int $venueId, int $imageId, int $step): bool
    {
        $ids = self::orderedIds($venueId);
        $index = array_search($imageId, $ids, true);
        $target = $index === false ? -1 : $index + ($step < 0 ? -1 : 1);
        if ($target < 0 || $target >= count($ids)) {
            return false;
        }
        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
        return self::saveOrder($venueId, $ids);
    }

    /** IDs das fotos da quadra, na ordem da galeria. */
    private static function orderedIds(int $venueId): array
    {
        return array_map(fn($row) => (int)$row['id'], self::findByVenueId($venueId));
    }

    /** Regrava as posições 0, 1, 2... na ordem dada. */
    private static function saveOrder(int $venueId, array $ids): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE venues_images SET position = :position WHERE id = :id AND venue_id = :venue_id");
        $pdo->beginTransaction();
        try {
            foreach (array_values($ids) as $position => $id) {
                $stmt->execute([':position' => $position, ':id' => $id, ':venue_id' => $venueId]);
            }
            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log('Erro ao ordenar as fotos: ' . $e->getMessage());
            return false;
        }
    }
}
