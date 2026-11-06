<?php
/**
 * Menu lateral das Configurações (perfil, segurança, preferências e, para o dono, o Kolae Premium).
 * Variáveis: $lang, $configActive ('profile' | 'security' | 'preferences' | 'premium').
 */
$configItems = [
    'profile' => ['/dashboard/perfil', 'fa-user-edit', $lang['config_custom_profile']],
    'security' => ['/dashboard/perfil/seguranca', 'fa-shield-alt', $lang['config_security']],
    'preferences' => ['/dashboard/perfil/preferencias', 'fa-sliders', $lang['config_preferences']],
];
// Kolae Premium (D61): o plano é do dono, então o item só aparece para quem tem papel de dono
if (($_SESSION['user_role'] ?? '') === 'owner') {
    $configItems['premium'] = ['/dashboard/perfil/plano', 'fa-crown', $lang['plan_tab']];
}
?>
<nav class="bg-surface-elevated p-2 rounded-2xl border border-content-secondary/10 shadow-lg" aria-label="<?php echo htmlspecialchars($lang['global_menu_config']); ?>">
    <ul class="space-y-1">
        <?php foreach ($configItems as $key => [$url, $icon, $label]): $on = $key === $configActive; ?>
            <li>
                <a href="<?php echo BASE_URL . $url; ?>" <?php echo $on ? 'aria-current="page"' : ''; ?>
                    class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition-all group <?php echo $on ? 'bg-brand-500/10 text-brand-600 dark:text-brand-400 border-l-4 border-brand-500' : 'text-content-secondary hover:bg-surface-secondary hover:text-content-primary'; ?>">
                    <i class="fas <?php echo $icon; ?> w-5 text-center <?php echo $on ? '' : 'group-hover:text-brand-500 transition-colors'; ?>"></i>
                    <span><?php echo htmlspecialchars($label); ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
