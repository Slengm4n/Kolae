<?php
/**
 * Formulário recusado pela proteção CSRF (App\Core\Csrf, status 403): sessão expirou, a página ficou
 * aberta muito tempo ou o envio passou do limite de tamanho.
 */
$lang = require BASE_PATH . '/includes/i18n.php';
$pageTitle = $lang['csrf_title'] . ' · Kolae';
$back = $_SERVER['HTTP_REFERER'] ?? '';
// Só volta para uma página do próprio site
if (strpos($back, BASE_URL . '/') !== 0) {
    $back = BASE_URL . '/';
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['idioma'] ?? 'pt-br'); ?>">
<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>
<body class="bg-surface-base text-content-primary font-sans antialiased min-h-screen flex items-center justify-center p-6">
    <main class="max-w-md w-full text-center bg-surface-elevated border border-content-secondary/10 rounded-2xl p-8 sm:p-10">
        <div class="mx-auto w-14 h-14 rounded-full bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center text-2xl">
            <i class="fas fa-clock-rotate-left"></i>
        </div>
        <h1 class="mt-5 text-2xl font-semibold"><?php echo htmlspecialchars($lang['csrf_title']); ?></h1>
        <p class="mt-3 text-content-secondary leading-relaxed"><?php echo htmlspecialchars($lang['csrf_text']); ?></p>
        <a href="<?php echo htmlspecialchars($back); ?>" class="mt-8 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-brand-500 hover:bg-brand-600 text-white font-semibold transition-colors">
            <i class="fas fa-arrow-left"></i><?php echo htmlspecialchars($lang['csrf_back']); ?>
        </a>
    </main>
</body>
</html>
