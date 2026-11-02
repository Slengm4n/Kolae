<?php
/**
 * Seletor de idioma (aberto pelo #lang-btn; ver src/js/modules/language_switcher.js).
 * Usado no cabeçalho público e nas telas de entrada.
 * O link mantém os outros parâmetros da página (filtros de /quadras, token da nova senha).
 *
 * Usa: $lang (traduções).
 */
$languages = [
    'pt-br' => ['🇧🇷', 'Português'],
    'en-us' => ['🇺🇸', 'English'],
    'es-es' => ['🇪🇸', 'Español'],
    'it-it' => ['🇮🇹', 'Italiano'],
    'ja-jp' => ['🇯🇵', '日本語'],
    'zh-cn' => ['🇨🇳', '中文'],
    'hi-in' => ['🇮🇳', 'हिन्दी'],
];
?>
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
                    <a href="?<?php echo htmlspecialchars(http_build_query(['lang' => $code] + $_GET)); ?>" lang="<?php echo $code; ?>" <?php echo $active ? 'aria-current="true"' : ''; ?>
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm transition-colors <?php echo $active ? 'bg-brand-500/10 text-brand-600 dark:text-brand-400 font-semibold' : 'hover:bg-surface-secondary'; ?>">
                        <span class="text-xl" aria-hidden="true"><?php echo $flag; ?></span><?php echo $label; ?>
                        <?php if ($active): ?><i class="fas fa-check ml-auto" aria-hidden="true"></i><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
