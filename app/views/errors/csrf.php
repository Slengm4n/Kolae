<?php
/**
 * Formulário recusado pela proteção CSRF (App\Core\Csrf, status 403): sessão expirou, a página ficou
 * aberta muito tempo ou o envio passou do limite de tamanho. Visual comum das páginas de erro (D53).
 */
$lang = require BASE_PATH . '/includes/i18n.php';
$back = $_SERVER['HTTP_REFERER'] ?? '';
// Só volta para uma página do próprio site
if (strpos($back, BASE_URL . '/') !== 0) {
    $back = BASE_URL . '/';
}
$errorCode = '';
$errorIcon = 'fa-clock-rotate-left';
$errorTitle = $lang['csrf_title'];
$errorText = $lang['csrf_text'];
$errorActions = [[$back, $lang['csrf_back'], true, 'fa-arrow-left']];
include BASE_PATH . '/app/views/partials/error_page.php';
