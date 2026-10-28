<?php
/**
 * <head> comum das páginas: fontes da marca (Kanit nos títulos, Poppins no texto),
 * ícones, CSS compilado e o script que aplica o tema antes de desenhar a página.
 *
 * Variáveis opcionais: $pageTitle (título da aba), $pageDescription (meta description).
 */
$pageTitle = $pageTitle ?? 'Kolae';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle); ?></title>
<?php if (!empty($pageDescription)): ?>
<meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
<?php endif; ?>
<meta name="theme-color" content="#0084FF">
<link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
<?php include BASE_PATH . '/app/views/partials/theme_script.php'; ?>
