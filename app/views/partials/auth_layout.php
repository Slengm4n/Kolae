<?php
/**
 * Base das telas de entrada (login, cadastro, esqueci a senha e nova senha).
 * No computador: foto com a mensagem da marca à esquerda e o formulário à direita.
 * No celular: a foto vira uma faixa no topo, com o formulário num cartão logo abaixo.
 *
 * Variáveis:
 *   $lang, $pageTitle
 *   $authImage        arquivo em assets/img (ex: 'login_bg.webp')
 *   $authSideTitle    título sobre a foto; $authSideText: texto sobre a foto
 *   $authBack         [url, texto] do link de voltar
 *   $authTitle        título do formulário; $authSubtitle: texto abaixo dele
 *   $authAlert        null ou [tipo ('success'|'error'), mensagem, detalhe opcional]
 *   $authContent      HTML do formulário (montado com ob_start na view)
 *   $authFooter       HTML da linha de baixo (ex: "Não tem uma conta? Cadastre-se")
 */
$ae = fn($s) => htmlspecialchars((string)$s);
$authAlert = $authAlert ?? null;
$pageDescription = $pageDescription ?? $authSideText; // descrição para buscadores e prévia de link
$authFeatures = [
    ['fa-map-location-dot', $lang['auth_feature_map']],
    ['fa-signal', $lang['auth_feature_status']],
    ['fa-heart', $lang['auth_feature_free']],
];
$authBg = BASE_URL . '/assets/img/' . $authImage;
// Claro sobre a foto (celular); neutro ao lado do formulário (computador)
$iconButton = 'w-10 h-10 rounded-full flex items-center justify-center border transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 '
    . 'text-white/90 bg-white/10 border-white/15 backdrop-blur-md hover:bg-white/20 '
    . 'lg:text-content-secondary lg:bg-transparent lg:border-content-secondary/15 lg:backdrop-blur-none lg:hover:bg-content-secondary/10 lg:hover:text-content-primary';
?>
<!DOCTYPE html>
<html lang="<?php echo $ae($_SESSION['idioma'] ?? 'pt-br'); ?>">

<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>

<body class="bg-surface-base text-content-primary font-sans antialiased">
    <div class="relative min-h-dvh lg:grid lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">

        <!-- Tema e idioma: sobre a foto no celular, no canto do formulário no computador -->
        <div class="absolute z-10 top-5 right-5 sm:top-8 sm:right-8 lg:top-10 lg:right-10 flex items-center gap-2">
            <button id="theme-toggle" type="button" class="<?php echo $iconButton; ?>" aria-label="<?php echo $ae($lang['home_theme_toggle']); ?>">
                <?php // O "hidden" fica no <span>: no <i>, o display do Font Awesome venceria a classe do Tailwind v4 ?>
                <span class="dark:hidden" aria-hidden="true"><i class="fas fa-moon"></i></span>
                <span class="hidden dark:inline" aria-hidden="true"><i class="fas fa-sun"></i></span>
            </button>
            <button id="lang-btn" type="button" class="<?php echo $iconButton; ?>" aria-haspopup="dialog" aria-label="<?php echo $ae($lang['home_lang_title']); ?>">
                <i class="fas fa-globe" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Foto com a mensagem da marca (faixa no topo no celular) -->
        <aside class="relative h-56 sm:h-64 lg:sticky lg:top-0 lg:h-dvh lg:p-3">
            <div class="relative h-full overflow-hidden lg:rounded-3xl bg-slate-900">
                <img src="<?php echo $ae($authBg); ?>" alt="" class="absolute inset-0 w-full h-full object-cover" fetchpriority="high">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/95 via-slate-950/55 to-slate-950/30"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-brand-500/25 via-transparent to-transparent mix-blend-screen"></div>

                <div class="relative h-full flex flex-col p-5 sm:p-8 lg:p-12">
                    <div class="flex items-center justify-between">
                        <a href="<?php echo BASE_URL; ?>/" aria-label="Kolae" class="rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300">
                            <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Kolae" class="h-8 lg:h-10 w-auto">
                        </a>
                    </div>

                    <div class="mt-auto hidden lg:block max-w-lg hero-rise">
                        <p class="font-display text-4xl xl:text-5xl font-semibold leading-[1.1] text-white text-balance"><?php echo $ae($authSideTitle); ?></p>
                        <p class="mt-4 text-lg text-white/75 leading-relaxed"><?php echo $ae($authSideText); ?></p>
                        <ul class="mt-10 space-y-3">
                            <?php foreach ($authFeatures as [$icon, $text]): ?>
                                <li class="flex items-center gap-3 text-white/90">
                                    <span class="w-9 h-9 rounded-xl bg-white/10 border border-white/15 backdrop-blur-md flex items-center justify-center text-brand-300 text-sm" aria-hidden="true"><i class="fas <?php echo $icon; ?>"></i></span>
                                    <?php echo $ae($text); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Formulário -->
        <main class="relative -mt-8 lg:mt-0 rounded-t-3xl lg:rounded-none bg-surface-base flex flex-col px-5 sm:px-8 pt-6 pb-10 lg:p-10 lg:min-h-dvh">
            <div class="h-10 flex items-center">
                <a href="<?php echo $ae($authBack[0]); ?>" class="inline-flex items-center gap-2 text-sm font-medium text-content-secondary hover:text-content-primary transition-colors rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i><?php echo $ae($authBack[1]); ?>
                </a>
            </div>

            <div class="flex-1 flex items-center justify-center">
                <div class="w-full max-w-[400px] py-8 lg:py-12 hero-rise" style="--rise-delay: 80ms">
                    <h1 class="text-3xl sm:text-[2rem] font-semibold tracking-tight"><?php echo $ae($authTitle); ?></h1>
                    <?php if (!empty($authSubtitle)): ?>
                        <p class="mt-2 text-content-secondary leading-relaxed"><?php echo $ae($authSubtitle); ?></p>
                    <?php endif; ?>

                    <?php if ($authAlert): ?>
                        <?php $ok = $authAlert[0] === 'success'; ?>
                        <div role="<?php echo $ok ? 'status' : 'alert'; ?>" class="mt-6 flex gap-3 rounded-xl border px-4 py-3 text-sm <?php echo $ok ? 'border-emerald-500/30 bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300' : 'border-red-500/30 bg-red-50 text-red-800 dark:bg-red-500/10 dark:text-red-300'; ?>">
                            <i class="fas <?php echo $ok ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mt-0.5" aria-hidden="true"></i>
                            <div>
                                <p class="font-medium"><?php echo $ae($authAlert[1]); ?></p>
                                <?php if (!empty($authAlert[2])): ?><p class="mt-1 opacity-80"><?php echo $ae($authAlert[2]); ?></p><?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mt-8">
                        <?php echo $authContent; ?>
                    </div>

                    <?php if (!empty($authFooter)): ?>
                        <p class="mt-8 text-center text-sm text-content-secondary"><?php echo $authFooter; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php include BASE_PATH . '/app/views/partials/lang_modal.php'; ?>

    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
</body>

</html>
