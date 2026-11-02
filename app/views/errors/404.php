<?php
/**
 * Página não encontrada (D53). Opcional: $messageKey (chave de tradução de um título mais
 * específico, ex: 'error_venue_not_found').
 */
$lang = require BASE_PATH . '/includes/i18n.php';
$errorCode = '404';
$errorIcon = 'fa-map-location-dot';
$errorTitle = $lang[$messageKey ?? ''] ?? $lang['error_404_title'];
$errorText = $lang['error_404_text'];
$errorActions = [
    [BASE_URL . '/', $lang['error_home'], true, 'fa-house'],
    [BASE_URL . '/quadras', $lang['error_venues'], false, 'fa-magnifying-glass'],
];
include BASE_PATH . '/app/views/partials/error_page.php';
