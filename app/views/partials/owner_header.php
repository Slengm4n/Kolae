<?php
/**
 * Cabeçalho das páginas do painel (dono e admin): logo, link "Painel" e menu do usuário.
 * O menu e o botão de tema são ligados pelo bundle.js (initNavbar e initTheme).
 *
 * Variáveis: $lang, $backUrl (página do painel, ex: '/dashboard' ou '/admin/quadras') e, opcional,
 * $toastContext (avisos da URL como pop-up, ver App\Core\Flash e partials/toasts.php).
 */
$userInitial = strtoupper(mb_substr($_SESSION['user_name'] ?? 'U', 0, 1));
// Mensagens do chat (D66): só o dono, com o número de não lidas (sem as tabelas do chat, fica 0)
$chatUnread = null;
if (($_SESSION['user_role'] ?? '') === 'owner') {
    try {
        $chatUnread = \App\Models\Conversation::unreadForOwner((int)$_SESSION['user_id']);
    } catch (\Throwable $e) {
        $chatUnread = 0;
    }
}
// Sino (D64): pedidos esperando resposta e avisos não lidos, só para o dono
$bellCount = ($_SESSION['user_role'] ?? '') === 'owner' ? \App\Controllers\RequestController::bellCount((int)$_SESSION['user_id']) : null;
$chatBadge = fn(string $extra = '') => $chatUnread ?'<span data-chat-unread class="min-w-[1.25rem] h-5 px-1.5 rounded-full bg-brand-600 text-white text-[0.6875rem] font-bold flex items-center justify-center ' . $extra . '">' . ($chatUnread > 99 ? '99+' : $chatUnread) . '</span>' : '';
?>
<header data-panel-header class="bg-surface-elevated/80 backdrop-blur-md border-b border-content-secondary/10 sticky top-0 z-40 py-3">
    <div class="container mx-auto px-4 flex justify-between items-center gap-4">
        <a href="<?php echo BASE_URL; ?>/" class="flex items-center gap-2 group" aria-label="Kolae">
            <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Kolae" class="h-8 w-auto invert dark:invert-0 opacity-90 group-hover:opacity-100 transition-opacity">
        </a>

        <nav class="hidden md:flex items-center gap-6">
            <a href="<?php echo BASE_URL . $backUrl; ?>" class="font-semibold text-content-secondary hover:text-brand-600 dark:hover:text-brand-400 transition-colors">
                <?php echo $lang['global_home_panel']; ?>
            </a>
            <?php if ($chatUnread !== null): ?>
                <a href="<?php echo BASE_URL; ?>/dashboard/mensagens" id="nav-messages" class="flex items-center gap-2 font-semibold text-content-secondary hover:text-brand-600 dark:hover:text-brand-400 transition-colors"<?php echo $chatUnread ? ' aria-label="' . htmlspecialchars($lang['chat_title'] . ' (' . strtr($lang['chat_unread_aria'], ['{n}' => $chatUnread]) . ')') . '"' : ''; ?>>
                    <?php echo $lang['chat_title']; ?><?php echo $chatBadge(); ?>
                </a>
            <?php endif; ?>
        </nav>

        <div class="flex items-center gap-2">
        <?php if ($bellCount !== null): ?>
            <a href="<?php echo BASE_URL; ?>/dashboard/notificacoes" id="nav-bell"
                class="relative w-10 h-10 rounded-full flex items-center justify-center text-content-secondary hover:text-brand-600 dark:hover:text-brand-400 hover:bg-surface-secondary transition-colors"
                aria-label="<?php echo htmlspecialchars($lang['req_title'] . ($bellCount ? ' (' . strtr($lang['req_bell_aria'], ['{n}' => $bellCount]) . ')' : '')); ?>">
                <span class="text-lg"><i class="fas fa-bell" aria-hidden="true"></i></span>
                <?php if ($bellCount): ?>
                    <span data-bell-count class="absolute -top-0.5 -right-0.5 min-w-[1.25rem] h-5 px-1 rounded-full bg-red-600 text-white text-[0.6875rem] font-bold flex items-center justify-center ring-2 ring-surface-elevated"><?php echo $bellCount > 99 ? '99+' : $bellCount; ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
        <div class="relative">
            <button id="user-menu-button" type="button" class="flex items-center gap-2 p-1.5 pr-3 border border-content-secondary/20 rounded-full transition-colors hover:bg-surface-secondary" aria-haspopup="true">
                <?php if (!empty($_SESSION['user_avatar'])): ?>
                    <img src="<?php echo BASE_URL . '/uploads/avatars/' . (int)$_SESSION['user_id'] . '/' . rawurlencode($_SESSION['user_avatar']); ?>"
                        class="w-8 h-8 rounded-full object-cover" alt=""
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <span class="w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-brand-700 items-center justify-center text-white font-bold text-sm" style="display:none;"><?php echo htmlspecialchars($userInitial); ?></span>
                <?php else: ?>
                    <span class="w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center text-white font-bold text-sm"><?php echo htmlspecialchars($userInitial); ?></span>
                <?php endif; ?>
                <?php if ($chatUnread): ?><span class="md:hidden w-2.5 h-2.5 rounded-full bg-brand-600" aria-hidden="true"></span><?php endif; ?>
                <span class="text-xs text-content-secondary"><i class="fas fa-chevron-down"></i></span>
            </button>

            <div id="profile-dropdown" class="absolute top-full right-0 mt-3 w-64 bg-surface-elevated border border-content-secondary/10 rounded-xl shadow-2xl opacity-0 invisible transform -translate-y-2 transition-all duration-200 z-50">
                <div class="p-4 border-b border-content-secondary/10">
                    <p class="font-semibold text-content-primary truncate"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></p>
                    <a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="text-xs text-brand-600 dark:text-brand-400 hover:underline"><?php echo $lang['dashboard_view_profile']; ?></a>
                </div>
                <ul class="py-2 text-sm text-content-secondary">
                    <li><a href="<?php echo BASE_URL . $backUrl; ?>" class="flex items-center gap-3 px-5 py-3 hover:bg-surface-secondary hover:text-content-primary transition-colors md:hidden"><span class="w-4 text-center"><i class="fas fa-home"></i></span><?php echo $lang['global_home_panel']; ?></a></li>
                    <?php if ($chatUnread !== null): ?>
                        <li><a href="<?php echo BASE_URL; ?>/dashboard/mensagens" class="flex items-center gap-3 px-5 py-3 hover:bg-surface-secondary hover:text-content-primary transition-colors md:hidden"><span class="w-4 text-center"><i class="fas fa-comments"></i></span><span class="flex-1"><?php echo $lang['chat_title']; ?></span><?php echo $chatBadge(); ?></a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="flex items-center gap-3 px-5 py-3 hover:bg-surface-secondary hover:text-content-primary transition-colors"><span class="w-4 text-center"><i class="fas fa-cog"></i></span><?php echo $lang['global_menu_config']; ?></a></li>
                    <li>
                        <button id="theme-toggle" type="button" class="w-full flex items-center gap-3 px-5 py-3 hover:bg-surface-secondary hover:text-content-primary transition-colors text-left">
                            <span class="w-4 text-center"><i class="fas fa-adjust"></i></span>
                            <span><?php echo $lang['home_theme_toggle']; ?></span>
                        </button>
                    </li>
                    <li class="border-t border-content-secondary/10 my-2"></li>
                    <li><a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-3 px-5 py-3 text-red-500 hover:bg-red-500/10 transition-colors"><span class="w-4 text-center"><i class="fas fa-sign-out-alt"></i></span><?php echo $lang['global_menu_exit']; ?></a></li>
                </ul>
            </div>
        </div>
        </div>
    </div>
</header>
<?php include BASE_PATH . '/app/views/partials/toasts.php'; ?>
