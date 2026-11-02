<?php
/**
 * Acesso negado (D53): área que a pessoa não pode abrir (ex: dono tentando entrar no admin).
 */
$lang = require BASE_PATH . '/includes/i18n.php';
$loggedIn = isset($_SESSION['user_id']);
$errorCode = '403';
$errorIcon = 'fa-lock';
$errorTitle = $lang['error_403_title'];
$errorText = $lang['error_403_text'];
$errorActions = [
    $loggedIn
        ? [BASE_URL . '/dashboard', $lang['error_panel'], true, 'fa-table-columns']
        : [BASE_URL . '/login', $lang['error_login'], true, 'fa-right-to-bracket'],
    [BASE_URL . '/', $lang['error_home'], false, 'fa-house'],
];
include BASE_PATH . '/app/views/partials/error_page.php';
