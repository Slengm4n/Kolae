<?php
/**
 * Base das páginas de erro (404, 403, CSRF; D53): cartão centralizado no visual do site,
 * com tema claro/escuro, o nome Kolae levando à home e botões para seguir.
 *
 * Variáveis: $lang, $errorCode (ex: '404' ou ''), $errorIcon (classe Font Awesome),
 *            $errorTitle, $errorText, $errorActions (lista de [url, texto, principal?, ícone?]).
 */
$ee = fn($s) => htmlspecialchars((string)$s);
$pageTitle = $errorTitle . ' · Kolae';
?>
<!DOCTYPE html>
<html lang="<?php echo $ee($_SESSION['idioma'] ?? 'pt-br'); ?>">
<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
    <meta name="robots" content="noindex">
</head>
<body class="bg-surface-base text-content-primary font-sans antialiased min-h-screen flex flex-col items-center justify-center p-6">
    <a href="<?php echo BASE_URL; ?>/" class="mb-8 font-display text-3xl font-semibold tracking-tight text-content-primary hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Kolae</a>
    <main class="max-w-md w-full text-center bg-surface-elevated border border-content-secondary/10 rounded-2xl p-8 sm:p-10 shadow-sm">
        <div class="mx-auto w-14 h-14 rounded-full bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center text-2xl">
            <i class="fas <?php echo $ee($errorIcon); ?>"></i>
        </div>
        <?php if ($errorCode !== ''): ?>
            <p class="mt-5 text-sm font-semibold tracking-widest text-content-secondary tabular-nums"><?php echo $ee($errorCode); ?></p>
        <?php endif; ?>
        <h1 class="<?php echo $errorCode !== '' ? 'mt-1' : 'mt-5'; ?> text-2xl font-semibold"><?php echo $ee($errorTitle); ?></h1>
        <p class="mt-3 text-content-secondary leading-relaxed"><?php echo $ee($errorText); ?></p>
        <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
            <?php foreach ($errorActions as $action):
                [$url, $label] = $action;
                $primary = $action[2] ?? false;
                $icon = $action[3] ?? null; ?>
                <a href="<?php echo $ee($url); ?>" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-semibold whitespace-nowrap transition-colors <?php echo $primary ? 'bg-brand-600 hover:bg-brand-700 text-white' : 'border border-content-secondary/20 hover:border-brand-500 hover:text-brand-600 dark:hover:text-brand-400'; ?>">
                    <?php if ($icon): ?><i class="fas <?php echo $ee($icon); ?>"></i><?php endif; ?><?php echo $ee($label); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
