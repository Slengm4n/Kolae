<?php
/**
 * Cabeçalho de todas as páginas do admin (D67, parte C): o mesmo visual do cabeçalho do dono
 * (owner_header.php), com o menu do admin. Só em português (D19).
 *
 * Variáveis: $adminActive (item do menu da página: 'inicio', 'usuarios', 'esportes', 'quadras',
 * 'mapa', 'premium', 'anuncios' ou 'fotos') e, opcional, $toastContext (avisos da URL, definido
 * antes de incluir). Já inclui o toasts.php (avisos e confirmações do ui.js): a página não inclui.
 * Tem o próprio script (menu do celular e tema): as páginas do admin não carregam o bundle.js.
 */
$adminActive = $adminActive ?? '';
$adminName = $_SESSION['user_name'] ?? 'Admin';
$adminInitial = strtoupper(mb_substr($adminName, 0, 1));
// Pedidos de Premium esperando: número no item do menu
try {
    $adminPremiumPending = count(array_filter(\App\Core\PremiumPlan::adminList(), fn($p) => \App\Core\PremiumPlan::state($p) === 'requested'));
} catch (\Throwable $e) {
    $adminPremiumPending = 0;
}
$adminMenu = [
    'inicio' => ['Início', 'fa-house', '/admin'],
    'usuarios' => ['Usuários', 'fa-users', '/admin/usuarios'],
    'quadras' => ['Quadras', 'fa-flag', '/admin/quadras'],
    'esportes' => ['Esportes', 'fa-person-running', '/admin/esportes'],
    'mapa' => ['Mapa', 'fa-map-location-dot', '/admin/mapa'],
    'premium' => ['Premium', 'fa-crown', '/admin/premium'],
    'anuncios' => ['Anúncios', 'fa-bullhorn', '/admin/anuncios'],
    'fotos' => ['Fotos', 'fa-images', '/admin/fotos'],
];
$adminBadge = fn(string $key) => $key === 'premium' && $adminPremiumPending
    ? '<span class="min-w-[1.125rem] h-[1.125rem] px-1 rounded-full bg-amber-500 text-white text-[0.625rem] font-bold flex items-center justify-center">' . $adminPremiumPending . '</span>' : '';
?>
<header data-admin-header class="bg-surface-elevated/80 backdrop-blur-md border-b border-content-secondary/10 sticky top-0 z-40 py-3">
    <div class="container mx-auto px-4 flex justify-between items-center gap-4">
        <a href="<?php echo BASE_URL; ?>/admin" class="flex items-center gap-3 group shrink-0" aria-label="Kolae · Admin">
            <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Kolae" class="h-8 w-auto invert dark:invert-0 opacity-90 group-hover:opacity-100 transition-opacity">
            <span class="text-[0.6875rem] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md bg-brand-500/10 text-brand-600 dark:text-brand-400">Admin</span>
        </a>

        <nav id="admin-nav" class="hidden xl:flex items-center gap-1" aria-label="Menu do admin">
            <?php foreach ($adminMenu as $key => [$label, $icon, $path]): $on = $adminActive === $key; ?>
                <a href="<?php echo BASE_URL . $path; ?>" data-admin-nav="<?php echo $key; ?>" <?php echo $on ? 'aria-current="page"' : ''; ?>
                    class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors <?php echo $on ? 'bg-brand-500/10 text-brand-600 dark:text-brand-400' : 'text-content-secondary hover:text-content-primary hover:bg-surface-secondary'; ?>">
                    <?php echo htmlspecialchars($label); ?><?php echo $adminBadge($key); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="flex items-center gap-2">
            <button id="admin-theme-toggle" type="button" class="w-10 h-10 rounded-full flex items-center justify-center text-content-secondary hover:text-content-primary hover:bg-surface-secondary transition-colors" aria-label="Alternar tema claro e escuro">
                <span class="dark:hidden"><i class="fas fa-moon" aria-hidden="true"></i></span>
                <span class="hidden dark:inline"><i class="fas fa-sun" aria-hidden="true"></i></span>
            </button>
            <div class="relative">
                <button id="admin-menu-button" type="button" class="flex items-center gap-2 p-1.5 pr-3 border border-content-secondary/20 rounded-full transition-colors hover:bg-surface-secondary" aria-haspopup="true" aria-expanded="false" aria-controls="admin-menu">
                    <span class="w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center text-white font-bold text-sm"><?php echo htmlspecialchars($adminInitial); ?></span>
                    <?php if ($adminPremiumPending): ?><span class="xl:hidden w-2.5 h-2.5 rounded-full bg-amber-500" aria-hidden="true"></span><?php endif; ?>
                    <span class="text-xs text-content-secondary"><i class="fas fa-chevron-down" aria-hidden="true"></i></span>
                </button>
                <div id="admin-menu" class="absolute top-full right-0 mt-3 w-64 bg-surface-elevated border border-content-secondary/10 rounded-xl shadow-2xl hidden z-50">
                    <div class="p-4 border-b border-content-secondary/10">
                        <p class="font-semibold text-content-primary truncate"><?php echo htmlspecialchars($adminName); ?></p>
                        <p class="text-xs text-content-secondary">Administrador do Kolae</p>
                    </div>
                    <ul class="py-2 text-sm text-content-secondary">
                        <?php foreach ($adminMenu as $key => [$label, $icon, $path]): $on = $adminActive === $key; ?>
                            <li class="xl:hidden"><a href="<?php echo BASE_URL . $path; ?>" class="flex items-center gap-3 px-5 py-2.5 hover:bg-surface-secondary hover:text-content-primary transition-colors <?php echo $on ? 'text-brand-600 dark:text-brand-400 font-semibold' : ''; ?>"><span class="w-4 text-center"><i class="fas <?php echo $icon; ?>" aria-hidden="true"></i></span><span class="flex-1"><?php echo htmlspecialchars($label); ?></span><?php echo $adminBadge($key); ?></a></li>
                        <?php endforeach; ?>
                        <li class="xl:hidden border-t border-content-secondary/10 my-2"></li>
                        <li><a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-3 px-5 py-2.5 text-red-500 hover:bg-red-500/10 transition-colors"><span class="w-4 text-center"><i class="fas fa-sign-out-alt" aria-hidden="true"></i></span>Sair</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>
<script>
    // Menu do admin (celular) e tema claro/escuro, sem o bundle.js
    (() => {
        const btn = document.getElementById('admin-menu-button');
        const menu = document.getElementById('admin-menu');
        const close = () => { menu.classList.add('hidden'); btn.setAttribute('aria-expanded', 'false'); };
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const open = menu.classList.toggle('hidden') === false;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', (e) => { if (!menu.contains(e.target)) close(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
        document.getElementById('admin-theme-toggle').addEventListener('click', () => {
            const dark = document.documentElement.classList.toggle('dark');
            try { localStorage.theme = dark ? 'dark' : 'light'; } catch (err) {}
            document.dispatchEvent(new CustomEvent('kolae:theme', { detail: { dark } }));
        });
    })();
</script>
<?php // Avisos e confirmações do Kolae (ui.js) em todas as páginas do admin
include BASE_PATH . '/app/views/partials/toasts.php'; ?>
