<?php
/**
 * Página dos documentos legais (D42): Termos de Uso ou Política de Privacidade.
 * $doc = 'terms' | 'privacy'. O texto fica em português; o resto segue o idioma escolhido.
 */
use App\Controllers\LegalController;

$lang = require BASE_PATH . '/includes/i18n.php';
$contactEmail = LegalController::CONTACT_EMAIL;
$sections = require BASE_PATH . '/app/views/legal/' . ($doc === 'privacy' ? 'privacy' : 'terms') . '_content.php';
$title = $doc === 'privacy' ? $lang['legal_privacy_title'] : $lang['legal_terms_title'];
$otherUrl = BASE_URL . ($doc === 'privacy' ? '/termos' : '/privacidade');
$otherTitle = $doc === 'privacy' ? $lang['legal_terms_title'] : $lang['legal_privacy_title'];
$pageTitle = $title . ' · Kolae';
$anchorBase = BASE_URL . '/'; // âncoras do cabeçalho e do rodapé levam à home
$isPortuguese = ($_SESSION['idioma'] ?? 'pt-br') === 'pt-br';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['idioma'] ?? 'pt-br'); ?>" class="scroll-smooth">
<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>
<body class="bg-surface-base text-content-primary font-sans antialiased overflow-x-hidden">
    <?php include BASE_PATH . '/app/views/partials/public_header.php'; ?>

    <section class="bg-neutral-950 text-white pt-32 pb-12">
        <div class="container mx-auto px-4 sm:px-6 max-w-5xl">
            <p class="text-sm font-semibold uppercase tracking-wider text-brand-300"><?php echo htmlspecialchars($lang['legal_kicker']); ?></p>
            <h1 class="mt-2 text-4xl sm:text-5xl font-semibold"><?php echo htmlspecialchars($title); ?></h1>
            <p class="mt-3 text-white/70 text-sm"><?php echo htmlspecialchars(strtr($lang['legal_updated'], ['{date}' => LegalController::UPDATED_AT])); ?></p>
        </div>
    </section>

    <main class="container mx-auto px-4 sm:px-6 max-w-5xl py-10 lg:py-14">
        <?php if (!$isPortuguese): ?>
            <p class="mb-8 p-4 rounded-xl border border-brand-500/30 bg-brand-500/10 text-sm text-content-primary flex items-start gap-3">
                <span class="mt-0.5 text-brand-600 dark:text-brand-400"><i class="fas fa-language"></i></span><?php echo htmlspecialchars($lang['legal_pt_only']); ?>
            </p>
        <?php endif; ?>

        <div class="lg:grid lg:grid-cols-[14rem_1fr] lg:gap-12">
            <nav class="mb-10 lg:mb-0" aria-label="<?php echo htmlspecialchars($lang['legal_toc']); ?>">
                <div class="lg:sticky lg:top-28 p-5 rounded-2xl bg-surface-elevated border border-content-secondary/10">
                    <p class="text-xs font-semibold uppercase tracking-wider text-content-secondary"><?php echo htmlspecialchars($lang['legal_toc']); ?></p>
                    <ol class="mt-3 space-y-2 text-sm">
                        <?php foreach ($sections as $i => [$id, $sectionTitle]): ?>
                            <li><a href="#<?php echo $id; ?>" class="flex gap-2 text-content-secondary hover:text-brand-600 dark:hover:text-brand-400 transition-colors"><span class="w-5 shrink-0 tabular-nums text-content-secondary/60"><?php echo $i + 1; ?>.</span><span><?php echo htmlspecialchars($sectionTitle); ?></span></a></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </nav>

            <article class="legal-text bg-surface-elevated border border-content-secondary/10 rounded-2xl p-6 sm:p-10" lang="pt-BR">
                <?php foreach ($sections as $i => [$id, $sectionTitle, $html]): ?>
                    <section id="<?php echo $id; ?>" class="scroll-mt-28">
                        <h2><span class="text-brand-600 dark:text-brand-400 tabular-nums"><?php echo $i + 1; ?>.</span> <?php echo htmlspecialchars($sectionTitle); ?></h2>
                        <?php echo $html; ?>
                    </section>
                <?php endforeach; ?>

                <p class="mt-10 pt-6 border-t border-content-secondary/10 text-sm text-content-secondary">
                    <?php echo htmlspecialchars($lang['legal_see_also']); ?>
                    <a href="<?php echo $otherUrl; ?>"><?php echo htmlspecialchars($otherTitle); ?></a>
                </p>
            </article>
        </div>
    </main>

    <?php include BASE_PATH . '/app/views/partials/public_footer.php'; ?>

    <style>
        .legal-text section + section { margin-top: 2.25rem; }
        .legal-text h2 { font-size: 1.25rem; font-weight: 600; color: var(--color-content-primary); margin-bottom: .75rem; }
        .legal-text p, .legal-text li { color: var(--color-content-secondary); line-height: 1.75; }
        .legal-text p + p, .legal-text p + ul, .legal-text ul + p { margin-top: .75rem; }
        .legal-text ul { list-style: disc; padding-left: 1.25rem; }
        .legal-text li + li { margin-top: .4rem; }
        .legal-text strong { color: var(--color-content-primary); font-weight: 600; }
        .legal-text a { color: var(--color-brand-600); text-decoration: underline; text-underline-offset: 2px; }
        .dark .legal-text a { color: var(--color-brand-300); }
    </style>

    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
</body>
</html>
