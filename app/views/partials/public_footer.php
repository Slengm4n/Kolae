<?php
/**
 * Rodapé das páginas públicas. Usa: $lang (traduções).
 */
$anchorBase = $anchorBase ?? ''; // '' na home; BASE_URL . '/' nas outras páginas
$footerLinks = [
    '#como-funciona' => $lang['home_nav_how'],
    '#quadras'       => $lang['home_nav_venues'],
    '#para-donos'    => $lang['home_nav_owners'],
    '#sobre-nos'     => $lang['global_menu_about'],
];
?>
<footer class="bg-surface-elevated border-t border-content-secondary/15">
    <div class="container mx-auto px-4 sm:px-6 py-14 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
        <div class="lg:col-span-2">
            <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Kolae" class="h-9 w-auto invert dark:invert-0" loading="lazy">
            <p class="mt-4 max-w-sm text-sm text-content-secondary"><?php echo htmlspecialchars($lang['home_footer_phrase']); ?></p>
        </div>

        <nav aria-label="<?php echo htmlspecialchars($lang['home_footer_nav']); ?>">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-content-primary"><?php echo htmlspecialchars($lang['home_footer_nav']); ?></h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <?php foreach ($footerLinks as $href => $label): ?>
                    <li><a href="<?php echo $anchorBase . $href; ?>" class="text-content-secondary hover:text-brand-600 dark:hover:text-brand-400 transition-colors"><?php echo htmlspecialchars($label); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider text-content-primary"><?php echo htmlspecialchars($lang['global_contact_kolae']); ?></h2>
            <ul class="mt-4 space-y-2.5 text-sm text-content-secondary">
                <li class="flex items-center gap-2"><i class="fas fa-envelope w-4 text-brand-500" aria-hidden="true"></i><a href="mailto:kolae.gg@gmail.com" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">kolae.gg@gmail.com</a></li>
                <li class="flex items-center gap-2"><i class="fas fa-phone w-4 text-brand-500" aria-hidden="true"></i>+55 (11) 99860-0253</li>
            </ul>
        </div>
    </div>

    <div class="border-t border-content-secondary/15">
        <div class="container mx-auto px-4 sm:px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-content-secondary">
            <p>&copy; <?php echo date('Y'); ?> Kolae. <?php echo htmlspecialchars($lang['home_footer_rights']); ?></p>
            <nav class="flex gap-4" aria-label="<?php echo htmlspecialchars($lang['legal_footer_label']); ?>">
                <a href="<?php echo BASE_URL; ?>/termos" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors"><?php echo htmlspecialchars($lang['legal_terms_title']); ?></a>
                <a href="<?php echo BASE_URL; ?>/privacidade" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors"><?php echo htmlspecialchars($lang['legal_privacy_title']); ?></a>
            </nav>
        </div>
    </div>
</footer>
