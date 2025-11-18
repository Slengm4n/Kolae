<?php

namespace App\Core;

class ImageHelper
{
    /**
     * Processa um ficheiro de upload: valida, move e otimiza a imagem.
     * @param array $file O array do ficheiro de $_FILES (ex: $_FILES['avatar']).
     * @param string $destinationDir O diretório de destino (ex: 'public/uploads/avatars/').
     * @param int $userId O ID do usuário para criar uma subpasta (opcional).
     * @return string|false O novo nome do ficheiro em caso de sucesso, ou false em caso de falha.
     */
    public static function processAndSave($file, $destinationDir, $userId = null)
    {
        // --- Validações básicas ---
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            // Log de erro: tipo de ficheiro não permitido
            return false;
        }

        if ($file['size'] > 10 * 1024 * 1024) { // 10MB
            // Log de erro: ficheiro muito grande
            return false;
        }

        // --- Preparação do Caminho ---
        if ($userId) {
            $destinationDir .= $userId . '/';
        }

        // Cria o diretório se ele não existir
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0775, true);
        }

        // --- Gera um Nome de Ficheiro Único ---
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('avatar_', true) . '.' . $fileExtension;
        $destinationPath = $destinationDir . $newFileName;
        $tempPath = $file['tmp_name'];

        // --- Move e Otimiza ---
        if (move_uploaded_file($tempPath, $destinationPath)) {
            // Agora que o ficheiro está no lugar certo, otimizamo-lo
            if (self::optimize($destinationPath, $destinationPath)) {
                return $newFileName; // Retorna apenas o nome do ficheiro para guardar no banco de dados
            }
        }

        return false; // Retorna false se algo falhar
    }

    /**
     * Otimiza uma imagem (redimensiona e comprime).
     * Requer a extensão GD do PHP.
     */
    public static function optimize($sourcePath, $destinationPath, $quality = 80, $maxWidth = 800)
    {
        $imageInfo = \getimagesize($sourcePath);
        if ($imageInfo === false) return false;

        $mime = $imageInfo['mime'];
        switch ($mime) {
            case 'image/jpeg':
                $image = \imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = \imagecreatefrompng($sourcePath);
                break;
            case 'image/gif': // Suporte para GIF (sem otimização de qualidade, apenas redimensionamento)
                $image = \imagecreatefromgif($sourcePath);
                break;
            default:
                return false; // Formato não suportado
        }

        $width = \imagesx($image);
        $height = \imagesy($image);

        if ($width > $maxWidth) {
            $newHeight = ($height / $width) * $maxWidth;
            $newImage = \imagecreatetruecolor($maxWidth, $newHeight);

            // Preserva a transparência para PNG e GIF
            if ($mime == 'image/png' || $mime == 'image/gif') {
                \imagealphablending($newImage, false);
                \imagesavealpha($newImage, true);
                $transparent = \imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                \imagefilledrectangle($newImage, 0, 0, $maxWidth, $newHeight, $transparent);
            }

            \imagecopyresampled($newImage, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
            \imagedestroy($image);
        }

        $success = false;
        switch ($mime) {
            case 'image/jpeg':
                $success = \imagejpeg($image, $destinationPath, $quality);
                break;
            case 'image/png':
                $pngQuality = round(($quality / 100) * 9);
                \imagesavealpha($image, true);
                $success = \imagepng($image, $destinationPath, $pngQuality);
                break;
            case 'image/gif':
                $success = \imagegif($image, $destinationPath);
                break;
        }

        \imagedestroy($image);
        return $success;
    }
}
