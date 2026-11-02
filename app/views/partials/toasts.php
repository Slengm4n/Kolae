<?php
/**
 * Entrega ao src/js/ui.js os avisos da página (pop-ups) e os textos dos botões.
 * Variáveis: $lang, $toastContext (ex: 'dashboard', ver App\Core\Flash) e, opcional, $toasts
 * (lista extra de ['type' => 'success'|'error'|'info', 'message' => texto]).
 */
// As páginas do admin (só em português, D19) não carregam as traduções sozinhas
$lang = $lang ?? require BASE_PATH . '/includes/i18n.php';
$toastList = array_merge(
    isset($toastContext) ? \App\Core\Flash::fromQuery($toastContext, $lang) : [],
    $toasts ?? []
);
$toastData = [
    'toasts' => $toastList,
    // Códigos que o JS tira da URL depois de mostrar, para recarregar a página não repetir o aviso
    'clean' => $toastList ? ['status', 'error'] : [],
    'labels' => [
        'close' => $lang['flash_close'],
        'cancel' => $lang['flash_cancel'],
        'confirm' => $lang['flash_confirm'],
        'title' => $lang['flash_confirm_title'],
    ],
];
?>
<script type="application/json" id="kolae-ui"><?php echo json_encode($toastData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?></script>
<script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/ui.js'); ?>"></script>
