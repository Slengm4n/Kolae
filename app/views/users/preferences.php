<?php
/**
 * Configurações > Preferências: tema (claro, escuro ou automático) e idioma.
 * O tema fica no navegador (localStorage, src/js/modules/toggle_theme.js); o idioma, na sessão (?lang=).
 */
$lang = require BASE_PATH . '/includes/i18n.php';
$e = fn($s) => htmlspecialchars((string)$s);
$pageTitle = $lang['config_preferences'] . ' · Kolae';
$current = $_SESSION['idioma'] ?? 'pt-br';
$themes = [
    'light' => ['fa-sun', $lang['theme_light'], $lang['pref_theme_light_text']],
    'dark' => ['fa-moon', $lang['theme_dark'], $lang['pref_theme_dark_text']],
    'system' => ['fa-circle-half-stroke', $lang['pref_theme_system'], $lang['pref_theme_system_text']],
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
$card = 'bg-surface-elevated p-6 md:p-8 rounded-2xl border border-content-secondary/10 shadow-xl';
?>
<!DOCTYPE html>
<html lang="<?php echo $e($current); ?>">

<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>

<body class="bg-surface-base text-content-primary font-sans antialiased">
    <?php $backUrl = '/dashboard';
    include BASE_PATH . '/app/views/partials/owner_header.php'; ?>

    <main class="container mx-auto px-4 py-10 max-w-6xl">
        <div class="flex flex-col md:flex-row items-start gap-8">
            <aside class="w-full md:w-64 flex-shrink-0">
                <h1 class="text-2xl font-bold text-content-primary mb-6 px-2"><?php echo $e($lang['global_menu_config']); ?></h1>
                <?php $configActive = 'preferences';
                include BASE_PATH . '/app/views/partials/config_nav.php'; ?>
            </aside>

            <section class="flex-1 w-full space-y-6">
                <!-- Tema -->
                <div class="<?php echo $card; ?>">
                    <h2 class="text-xl font-bold"><?php echo $e($lang['pref_theme_title']); ?></h2>
                    <p class="text-sm text-content-secondary mt-1"><?php echo $e($lang['pref_theme_hint']); ?></p>
                    <fieldset class="mt-6">
                        <legend class="sr-only"><?php echo $e($lang['pref_theme_title']); ?></legend>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <?php foreach ($themes as $value => [$icon, $label, $text]): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="theme-choice" value="<?php echo $value; ?>" class="sr-only peer">
                                    <span class="h-full flex sm:flex-col items-center sm:items-start gap-3 p-4 rounded-xl border-2 border-content-secondary/15 transition-colors hover:border-content-secondary/40 peer-checked:border-brand-500 peer-checked:bg-brand-500/5 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500">
                                        <span class="w-10 h-10 shrink-0 rounded-full bg-surface-secondary flex items-center justify-center text-brand-600 dark:text-brand-400" aria-hidden="true"><i class="fas <?php echo $icon; ?>"></i></span>
                                        <span>
                                            <span class="block font-semibold"><?php echo $e($label); ?></span>
                                            <span class="block text-xs text-content-secondary mt-0.5"><?php echo $e($text); ?></span>
                                        </span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                </div>

                <!-- Idioma -->
                <div class="<?php echo $card; ?>">
                    <h2 class="text-xl font-bold"><?php echo $e($lang['pref_lang_title']); ?></h2>
                    <p class="text-sm text-content-secondary mt-1"><?php echo $e($lang['pref_lang_hint']); ?></p>
                    <ul class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <?php foreach ($languages as $code => [$flag, $label]): $on = $code === $current; ?>
                            <li>
                                <a href="?lang=<?php echo $code; ?>" lang="<?php echo $code; ?>" <?php echo $on ? 'aria-current="true"' : ''; ?>
                                    class="flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-colors <?php echo $on ? 'border-brand-500 bg-brand-500/5 font-semibold' : 'border-content-secondary/15 hover:border-content-secondary/40'; ?>">
                                    <span class="text-xl" aria-hidden="true"><?php echo $flag; ?></span><?php echo $label; ?>
                                    <?php if ($on): ?><i class="fas fa-circle-check ml-auto text-brand-500" aria-hidden="true"></i><?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        </div>
    </main>

    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
</body>

</html>
