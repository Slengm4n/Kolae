<?php

namespace App\Core;

class ImageHelper
{
    // MIME real => extensão gerada pelo servidor. Nunca usamos a extensão enviada pelo cliente.
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
    ];

    private const MAX_SIZE = 10 * 1024 * 1024; // 10MB

    /**
     * Valida um arquivo temporário de upload pelo conteúdo (não pelo nome nem pelo
     * Content-Type do cliente) e devolve a extensão segura correspondente.
     * @param string $tmpPath Caminho em $_FILES[...]['tmp_name'].
     * @param int $size Tamanho em bytes ($_FILES[...]['size']).
     * @return string|false A extensão ('jpg', 'png', 'gif') ou false se o arquivo for inválido.
     */
    public static function validateUpload($tmpPath, $size)
    {
        if (!is_uploaded_file($tmpPath) || $size <= 0 || $size > self::MAX_SIZE) {
            return false;
        }

        // getimagesize lê o cabeçalho da imagem e falha para scripts/arquivos que não são imagem
        $imageInfo = @\getimagesize($tmpPath);
        if ($imageInfo === false || !isset(self::ALLOWED_TYPES[$imageInfo['mime']])) {
            return false;
        }

        // Confere também com o fileinfo (magic bytes), quando a extensão estiver disponível
        if (function_exists('finfo_open') && ($finfo = finfo_open(FILEINFO_MIME_TYPE))) {
            $mime = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
            if ($mime !== $imageInfo['mime']) {
                return false;
            }
        }

        return self::ALLOWED_TYPES[$imageInfo['mime']];
    }

    /**
     * Processa um ficheiro de upload: valida, move e otimiza a imagem.
     * @param array $file O array do ficheiro de $_FILES (ex: $_FILES['avatar']).
     * @param string $destinationDir O diretório de destino (ex: 'public/uploads/avatars/').
     * @param int $userId O ID do usuário para criar uma subpasta (opcional).
     * @return string|false O novo nome do ficheiro em caso de sucesso, ou false em caso de falha.
     */
    public static function processAndSave($file, $destinationDir, $userId = null)
    {
        // --- Validação pelo conteúdo real do arquivo ---
        $fileExtension = self::validateUpload($file['tmp_name'], $file['size']);
        if ($fileExtension === false) {
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
        $newFileName = uniqid('avatar_', true) . '.' . $fileExtension;
        $destinationPath = $destinationDir . $newFileName;
        $tempPath = $file['tmp_name'];

        // --- Move e Otimiza ---
        if (move_uploaded_file($tempPath, $destinationPath)) {
            // Agora que o ficheiro está no lugar certo, otimizamo-lo
            if (self::optimize($destinationPath, $destinationPath)) {
                return $newFileName; // Retorna apenas o nome do ficheiro para guardar no banco de dados
            }
            // Não deixa para trás um arquivo que falhou na otimização
            @unlink($destinationPath);
        }

        return false; // Retorna false se algo falhar
    }

    /**
     * Otimiza uma imagem (redimensiona e comprime).
     * Requer a extensão GD do PHP; sem ela, mantém a imagem como veio (já validada
     * pelo conteúdo em validateUpload) em vez de quebrar o upload.
     */
    public static function optimize($sourcePath, $destinationPath, $quality = 80, $maxWidth = 800)
    {
        $imageInfo = \getimagesize($sourcePath);
        if ($imageInfo === false) return false;

        if (!\extension_loaded('gd')) {
            error_log('ImageHelper: extensão GD desligada; imagem salva sem otimização. Ligue "extension=gd" no php.ini.');
            return $sourcePath === $destinationPath || \copy($sourcePath, $destinationPath);
        }

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
