<?php

namespace App\Core;

class ImageHelper
{
    // MIME real => extensão gerada pelo servidor. Nunca usamos a extensão enviada pelo cliente.
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
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

    /** Fotos das quadras (D48): lado maior em pixels e qualidade do JPEG. */
    public const PHOTO_MAX_SIDE = 1600;
    public const PHOTO_QUALITY = 82;

    /**
     * Otimiza uma imagem (redimensiona e comprime), mantendo o formato. Usado no avatar.
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
        $image = self::load($sourcePath, $mime);
        if (!$image) return false;
        $image = self::resizeToFit($image, $maxWidth, $mime !== 'image/jpeg');

        switch ($mime) {
            case 'image/jpeg':
                return \imagejpeg($image, $destinationPath, $quality);
            case 'image/png':
                \imagesavealpha($image, true);
                return \imagepng($image, $destinationPath, 6);
            case 'image/gif':
                return \imagegif($image, $destinationPath);
            case 'image/webp':
                return \imagewebp($image, $destinationPath, $quality);
        }
        return false;
    }

    /**
     * Foto de quadra (D48): gira pela orientação do celular, reduz para caber em
     * PHOTO_MAX_SIDE e grava como JPEG. Transparência vira fundo branco.
     * @return bool false se não deu para processar (sem GD, memória, arquivo estranho):
     *              quem chama guarda o arquivo original para o upload não falhar.
     */
    public static function savePhotoAsJpeg(string $sourcePath, string $destinationPath): bool
    {
        $info = @\getimagesize($sourcePath);
        if ($info === false || !\extension_loaded('gd')) {
            return false;
        }
        // Foto de 12 MP aberta no GD ocupa uns 50 MB
        $limit = ini_get('memory_limit');
        if ($limit !== '-1' && self::bytes($limit) < 256 * 1024 * 1024) {
            @ini_set('memory_limit', '256M');
        }

        $image = self::load($sourcePath, $info['mime']);
        if (!$image) {
            return false;
        }
        $image = self::resizeToFit($image, self::PHOTO_MAX_SIDE, false);

        // JPEG não tem transparência: pinta o fundo de branco
        $canvas = \imagecreatetruecolor(\imagesx($image), \imagesy($image));
        \imagefill($canvas, 0, 0, \imagecolorallocate($canvas, 255, 255, 255));
        \imagecopy($canvas, $image, 0, 0, 0, 0, \imagesx($image), \imagesy($image));
        \imageinterlace($canvas, true); // carrega aos poucos no 4G
        return \imagejpeg($canvas, $destinationPath, self::PHOTO_QUALITY);
    }

    /** Abre a imagem no GD já na orientação certa (EXIF de foto de celular). */
    private static function load(string $path, string $mime): ?\GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => @\imagecreatefromjpeg($path),
            'image/png' => @\imagecreatefrompng($path),
            'image/gif' => @\imagecreatefromgif($path),
            'image/webp' => @\imagecreatefromwebp($path),
            default => false,
        };
        if (!$image) {
            return null;
        }
        if ($mime === 'image/jpeg' && \function_exists('exif_read_data')) {
            $orientation = (int)(@\exif_read_data($path)['Orientation'] ?? 1);
            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle !== 0) {
                $image = \imagerotate($image, $angle, 0);
            }
        }
        return $image;
    }

    /** Reduz para o lado maior caber em $maxSide (nunca aumenta). */
    private static function resizeToFit(\GdImage $image, int $maxSide, bool $keepAlpha): \GdImage
    {
        $width = \imagesx($image);
        $height = \imagesy($image);
        $scale = $maxSide / max($width, $height);
        if ($scale >= 1) {
            return $image;
        }
        $newWidth = max(1, (int)round($width * $scale));
        $newHeight = max(1, (int)round($height * $scale));
        $resized = \imagecreatetruecolor($newWidth, $newHeight);
        if ($keepAlpha) {
            \imagealphablending($resized, false);
            \imagesavealpha($resized, true);
            \imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, \imagecolorallocatealpha($resized, 255, 255, 255, 127));
        }
        \imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        return $resized;
    }

    /** "256M" → bytes. */
    private static function bytes(string $value): int
    {
        $number = (int)$value;
        return match (strtoupper(substr(trim($value), -1))) {
            'G' => $number * 1024 ** 3,
            'M' => $number * 1024 ** 2,
            'K' => $number * 1024,
            default => $number,
        };
    }
}
