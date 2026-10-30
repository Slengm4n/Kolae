<?php
/**
 * Cabeçalho das páginas públicas, sobreposto ao topo escuro da página (texto claro).
 * Usa: $lang (traduções). Mantém os IDs usados pelos módulos JS:
 * theme-toggle (tema), lang-btn / lang-modal / lang-box / close-lang-btn (idioma),
 * user-menu-button / profile-dropdown (menu).
 */
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
$anchorBase = $anchorBase ?? ''; // '' na home; BASE_URL . '/' nas outras páginas, para as âncoras levarem à home
$navLinks = [
    '#como-funciona' => $lang['home_nav_how'],
    '#quadras'       => $lang['home_nav_venues'],
    '#para-donos'    => $lang['home_nav_owners'],
    '#sobre-nos'     => $lang['global_menu_about'],
];
$languages = [
    'pt-br' => ['🇧🇷', 'Português'],
    'en-us' => ['🇺🇸', 'English'],
    'es-es' => ['🇪🇸', 'Español'],
    'it-it' => ['🇮🇹', 'Italiano'],
    'ja-jp' => ['🇯🇵', '日本語'],
    'zh-cn' => ['🇨🇳', '中文'],
    'hi-in' => ['🇮🇳', 'हिन्दी'],
];
$iconButton = 'flex items-center justify-center w-10 h-10 rounded-full text-white/90 bg-white/10 border border-white/15 backdrop-blur-md hover:bg-white/20 hover:text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300';
?>
<?php // Fixo no topo; ganha fundo escuro ao rolar (src/js/pages/home_motion.js) ?>
<header data-site-header class="fixed inset-x-0 top-0 z-40 transition-[background-color,box-shadow,backdrop-filter] duration-300">
    <div class="container mx-auto px-4 sm:px-6 h-20 flex items-center justify-between gap-4">

        <a href="<?php echo BASE_URL; ?>/" aria-label="Kolae" class="shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 rounded-lg">
            <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Kolae" class="h-9 w-auto">
        </a>

        <nav class="hidden lg:flex items-center gap-8" aria-label="<?php echo htmlspecialchars($lang['home_footer_nav']); ?>">
            <?php foreach ($navLinks as $href => $label): ?>
                <a href="<?php echo $anchorBase . $href; ?>" class="text-sm font-medium text-white/80 hover:text-white transition-colors"><?php echo htmlspecialchars($label); ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="flex items-center gap-2 sm:gap-3">
            <button id="theme-toggle" type="button" class="<?php echo $iconButton; ?>" aria-label="<?php echo htmlspecialchars($lang['home_theme_toggle']); ?>">
                <?php // O "hidden" fica no <span>: no <i>, o display do Font Awesome venceria a classe do Tailwind v4 ?>
                <span class="dark:hidden" aria-hidden="true"><i class="fas fa-moon"></i></span>
                <span class="hidden dark:inline" aria-hidden="true"><i class="fas fa-sun"></i></span>
            </button>

            <button id="lang-btn" type="button" class="<?php echo $iconButton; ?>" aria-haspopup="dialog" aria-label="<?php echo htmlspecialchars($lang['home_lang_title']); ?>">
                <i class="fas fa-globe" aria-hidden="true"></i>
            </button>

            <?php if ($isLoggedIn): ?>
                <a href="<?php echo BASE_URL; ?>/dashboard" class="hidden sm:inline-flex items-center rounded-full bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-500/25 hover:bg-brand-400 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300">
                    <?php echo htmlspecialchars($lang['global_home_panel']); ?>
                </a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/login" class="hidden sm:inline-flex items-center rounded-full px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/10 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300">
                    <?php echo htmlspecialchars($lang['global_menu_login']); ?>
                </a>
                <a href="<?php echo BASE_URL; ?>/register" class="hidden sm:inline-flex items-center rounded-full bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-500/25 hover:bg-brand-400 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300">
                    <?php echo htmlspecialchars($lang['global_menu_register']); ?>
                </a>
            <?php endif; ?>

            <!-- Menu: links da página no celular e opções da conta -->
            <div class="relative">
                <button id="user-menu-button" type="button" aria-haspopup="true" aria-label="<?php echo htmlspecialchars($lang['home_menu']); ?>" class="<?php echo $isLoggedIn ? 'flex items-center gap-2 h-10 pl-3 pr-1 rounded-full text-white/90 bg-white/10 border border-white/15 backdrop-blur-md hover:bg-white/20 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300' : $iconButton . ' lg:hidden'; ?>">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                    <?php if ($isLoggedIn): ?>
                        <?php $initial = htmlspecialchars(mb_strtoupper(mb_substr($userName, 0, 1))); ?>
                        <?php if (!empty($_SESSION['user_avatar'])): ?>
                            <!-- Se a foto não carregar, mostra a inicial que está escondida logo depois -->
                            <img src="<?php echo BASE_URL . '/uploads/avatars/' . (int)$_SESSION['user_id'] . '/' . htmlspecialchars(basename($_SESSION['user_avatar'])); ?>" alt="" class="w-8 h-8 rounded-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <span class="w-8 h-8 rounded-full bg-brand-500 items-center justify-center text-sm font-bold text-white" style="display:none"><?php echo $initial; ?></span>
                        <?php else: ?>
                            <span class="w-8 h-8 rounded-full bg-brand-500 flex items-center justify-center text-sm font-bold text-white"><?php echo $initial; ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </button>

                <div id="profile-dropdown" class="absolute right-0 top-full mt-3 w-64 rounded-2xl bg-surface-elevated text-content-primary border border-content-secondary/15 shadow-2xl overflow-hidden opacity-0 invisible transform -translate-y-2 transition-all duration-200 z-50">
                    <ul class="py-2 text-sm">
                        <?php foreach ($navLinks as $href => $label): ?>
                            <li class="lg:hidden">
                                <a href="<?php echo $anchorBase . $href; ?>" class="block px-5 py-3 hover:bg-surface-secondary transition-colors"><?php echo htmlspecialchars($label); ?></a>
                            </li>
                        <?php endforeach; ?>
                        <li class="lg:hidden border-t border-content-secondary/15 my-1"></li>

                        <?php if ($isLoggedIn): ?>
                            <li class="px-5 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-content-secondary"><?php echo htmlspecialchars($userName); ?></li>
                            <li><a href="<?php echo BASE_URL; ?>/dashboard" class="flex items-center gap-3 px-5 py-3 hover:bg-surface-secondary transition-colors"><i class="fas fa-table-columns w-4 text-brand-500" aria-hidden="true"></i><?php echo htmlspecialchars($lang['global_home_panel']); ?></a></li>
                            <li><a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="flex items-center gap-3 px-5 py-3 hover:bg-surface-secondary transition-colors"><i class="fas fa-user-gear w-4 text-content-secondary" aria-hidden="true"></i><?php echo htmlspecialchars($lang['global_menu_profile']); ?></a></li>
                            <li class="border-t border-content-secondary/15 my-1"></li>
                            <li><a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-3 px-5 py-3 text-red-500 hover:bg-red-500/10 transition-colors"><i class="fas fa-arrow-right-from-bracket w-4" aria-hidden="true"></i><?php echo htmlspecialchars($lang['global_menu_exit']); ?></a></li>
                        <?php else: ?>
                            <li><a href="<?php echo BASE_URL; ?>/login" class="flex items-center gap-3 px-5 py-3 hover:bg-surface-secondary transition-colors"><i class="fas fa-arrow-right-to-bracket w-4 text-content-secondary" aria-hidden="true"></i><?php echo htmlspecialchars($lang['global_menu_login']); ?></a></li>
                            <li><a href="<?php echo BASE_URL; ?>/register" class="flex items-center gap-3 px-5 py-3 font-semibold text-brand-600 dark:text-brand-400 hover:bg-surface-secondary transition-colors"><i class="fas fa-user-plus w-4" aria-hidden="true"></i><?php echo htmlspecialchars($lang['global_menu_register']); ?></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Seletor de idioma (aberto pelo #lang-btn; ver src/js/modules/language_switcher.js) -->
<div id="lang-modal" role="dialog" aria-modal="true" aria-labelledby="lang-modal-title" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4 opacity-0 invisible transition-all duration-300">
    <div id="lang-box" class="w-full max-w-xs rounded-2xl bg-surface-elevated text-content-primary border border-content-secondary/15 shadow-2xl p-6 transform scale-95 opacity-0 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <h2 id="lang-modal-title" class="text-lg font-semibold"><?php echo htmlspecialchars($lang['home_lang_title']); ?></h2>
            <button id="close-lang-btn" type="button" aria-label="×" class="w-8 h-8 rounded-full flex items-center justify-center text-content-secondary hover:bg-surface-secondary hover:text-content-primary transition-colors">
                <i class="fas fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <ul class="space-y-1">
            <?php foreach ($languages as $code => [$flag, $label]): ?>
                <?php $active = ($_SESSION['idioma'] ?? 'pt-br') === $code; ?>
                <li>
                    <a href="?lang=<?php echo $code; ?>" lang="<?php echo $code; ?>" <?php echo $active ? 'aria-current="true"' : ''; ?>
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm transition-colors <?php echo $active ? 'bg-brand-500/10 text-brand-600 dark:text-brand-400 font-semibold' : 'hover:bg-surface-secondary'; ?>">
                        <span class="text-xl" aria-hidden="true"><?php echo $flag; ?></span><?php echo $label; ?>
                        <?php if ($active): ?><i class="fas fa-check ml-auto" aria-hidden="true"></i><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
