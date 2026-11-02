<?php
// Cria as imagens usadas pelos testes de clique (D56) em tests/e2e/.fotos (fora do git):
// foto1..foto12 numeradas e coloridas (ímpar .jpg, par .png), uma "foto de celular"
// de 4000x3000 com EXIF de girar 90°, um PNG grande e um arquivo falso.
// Uso: php tests/e2e/gerar_fotos.php   (precisa da extensão GD)
$dir = __DIR__ . '/.fotos';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}
if (is_file("$dir/celular_girada.jpg")) {
    exit("Fotos de teste já existem em $dir\n");
}

$colors = [[0, 132, 255], [211, 47, 47], [46, 160, 67], [245, 158, 11], [124, 58, 237], [236, 72, 153],
           [20, 184, 166], [100, 116, 139], [234, 88, 12], [14, 165, 233], [132, 204, 22], [30, 41, 59]];
foreach ($colors as $i => [$r, $g, $b]) {
    $im = imagecreatetruecolor(800, 600);
    imagefill($im, 0, 0, imagecolorallocate($im, $r, $g, $b));
    $white = imagecolorallocate($im, 255, 255, 255);
    imagefilledrectangle($im, 340, 260, 460, 340, $white);
    imagestring($im, 5, 375, 292, 'FOTO ' . ($i + 1), imagecolorallocate($im, $r, $g, $b));
    $i % 2 ? imagepng($im, "$dir/foto" . ($i + 1) . '.png') : imagejpeg($im, "$dir/foto" . ($i + 1) . '.jpg', 85);
}

// Foto "de celular": metade de cima vermelha e de baixo azul (para conferir a rotação), com ruído
$w = 4000; $h = 3000;
$im = imagecreatetruecolor($w, $h);
imagefilledrectangle($im, 0, 0, $w, $h / 2, imagecolorallocate($im, 200, 40, 40));
imagefilledrectangle($im, 0, $h / 2, $w, $h, imagecolorallocate($im, 40, 60, 200));
for ($i = 0; $i < 400000; $i++) {
    $c = mt_rand(0, 255);
    imagesetpixel($im, mt_rand(0, $w - 1), mt_rand(0, $h - 1), imagecolorallocate($im, $c, $c, $c));
}
ob_start();
imagejpeg($im, null, 95);
$jpg = ob_get_clean();
// EXIF mínimo com Orientation = 6 (girar 90° no sentido horário)
$tiff = "MM\x00\x2A\x00\x00\x00\x08" . "\x00\x01" . "\x01\x12\x00\x03\x00\x00\x00\x01\x00\x06\x00\x00" . "\x00\x00\x00\x00";
$app1 = "Exif\x00\x00" . $tiff;
file_put_contents("$dir/celular_girada.jpg", substr($jpg, 0, 2) . "\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1 . substr($jpg, 2));

// PNG grande 3000x2000
$p = imagecreatetruecolor(3000, 2000);
for ($i = 0; $i < 200000; $i++) {
    imagesetpixel($p, mt_rand(0, 2999), mt_rand(0, 1999), imagecolorallocate($p, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
}
imagepng($p, "$dir/grande.png");

file_put_contents("$dir/falsa.png", 'isto nao e uma imagem');
echo "Fotos de teste criadas em $dir\n";
