<?php
/**
 * "Notificações e solicitações" do dono (D64): aba Solicitações (pedidos esperando resposta, com
 * aprovar e recusar, e o histórico de 30 dias) e aba Avisos (notifications). Quem pediu aparece
 * só com o primeiro nome; o WhatsApp, só depois de aprovar e se a pessoa cadastrou. Nunca o e-mail.
 * Dados do RequestController::index.
 */
use App\Core\Csrf;
use App\Core\MatchRequests;

$lang = require BASE_PATH . '/includes/i18n.php';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$pageTitle = $lang['req_title'] . ' · Kolae';
$toastContext = 'requests';
$base = BASE_URL . '/dashboard/notificacoes';

$when = fn(array $r) => strtr($lang['req_when'], [
    '{date}' => date('d/m', strtotime($r['start_time'])),
    '{start}' => date('H:i', strtotime($r['start_time'])),
    '{end}' => date('H:i', strtotime($r['end_time'])),
]);
$ago = function (string $datetime) use ($lang): string {
    $day = date('Y-m-d', strtotime($datetime));
    if ($day === date('Y-m-d')) return date('H:i', strtotime($datetime));
    if ($day === date('Y-m-d', strtotime('-1 day'))) return $lang['chat_yesterday'];
    return date('d/m', strtotime($datetime));
};
$statusPill = [
    'approved' => ['req_status_approved', 'bg-green-500/15 text-green-700 dark:text-green-300'],
    'declined' => ['req_status_declined', 'bg-red-500/10 text-red-700 dark:text-red-300'],
    'cancelled' => ['req_status_cancelled', 'bg-content-secondary/10 text-content-secondary'],
    'expired' => ['req_status_expired', 'bg-content-secondary/10 text-content-secondary'],
];
$tabClass = fn(bool $active) => 'flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors '
    . ($active ? 'bg-brand-600 text-white' : 'text-content-secondary hover:text-content-primary hover:bg-surface-secondary');
$countPill = fn(int $n, bool $active) => $n ? '<span class="min-w-[1.25rem] h-5 px-1.5 rounded-full text-[0.6875rem] font-bold flex items-center justify-center '
    . ($active ? 'bg-white text-brand-700' : 'bg-brand-600 text-white') . '">' . ($n > 99 ? '99+' : $n) . '</span>' : '';
$card = 'bg-surface-elevated rounded-2xl border border-content-secondary/10 shadow-sm';
$whatsappUrl = function (array $r) use ($lang): ?string {
    $digits = preg_replace('/\D/', '', (string)($r['user_whatsapp'] ?? ''));
    if ($digits === '') return null;
    $text = strtr($lang['req_whatsapp_message'], ['{venue}' => $r['venue_name'], '{date}' => date('d/m', strtotime($r['start_time'])), '{start}' => date('H:i', strtotime($r['start_time']))]);
    return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
};
?>
<!DOCTYPE html>
<html lang="<?php echo $e($_SESSION['idioma'] ?? 'pt-br'); ?>">

<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>

<body class="bg-surface-base text-content-primary font-sans antialiased">
    <?php $backUrl = '/dashboard';
    include BASE_PATH . '/app/views/partials/owner_header.php'; ?>

    <main class="container mx-auto px-4 py-6 md:py-10 max-w-4xl">
        <div class="mb-6">
            <h1 class="text-3xl font-bold"><?php echo $e($lang['req_title']); ?></h1>
            <p class="text-sm text-content-secondary mt-1"><?php echo $e($lang['req_subtitle']); ?></p>
        </div>

        <nav id="req-tabs" class="flex gap-2 mb-6" aria-label="<?php echo $e($lang['req_title']); ?>">
            <a href="<?php echo $base; ?>?aba=solicitacoes" data-tab="solicitacoes" class="<?php echo $tabClass($tab === 'solicitacoes'); ?>" <?php echo $tab === 'solicitacoes' ? 'aria-current="page"' : ''; ?>>
                <span><i class="fas fa-calendar-plus" aria-hidden="true"></i></span><?php echo $e($lang['req_tab_requests']); ?><?php echo $countPill(count($pending), $tab === 'solicitacoes'); ?>
            </a>
            <a href="<?php echo $base; ?>?aba=avisos" data-tab="avisos" class="<?php echo $tabClass($tab === 'avisos'); ?>" <?php echo $tab === 'avisos' ? 'aria-current="page"' : ''; ?>>
                <span><i class="fas fa-bell" aria-hidden="true"></i></span><?php echo $e($lang['req_tab_notifications']); ?><?php echo $countPill($tab === 'avisos' ? 0 : $unreadOther, $tab === 'avisos'); ?>
            </a>
        </nav>

        <?php if ($tab === 'solicitacoes'): ?>
            <!-- Esperando resposta -->
            <section id="req-pending" aria-labelledby="req-pending-title">
                <h2 id="req-pending-title" class="text-sm font-semibold uppercase tracking-widest text-content-secondary mb-3"><?php echo $e($lang['req_pending_title']); ?></h2>
                <?php if (!$pending): ?>
                    <div id="req-empty" class="<?php echo $card; ?> p-8 text-center">
                        <span class="w-14 h-14 mx-auto rounded-2xl bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center text-2xl" aria-hidden="true"><i class="fas fa-calendar-check"></i></span>
                        <h3 class="text-lg font-bold mt-4"><?php echo $e($lang['req_empty_pending_title']); ?></h3>
                        <p class="text-sm text-content-secondary mt-1 max-w-md mx-auto"><?php echo $e($lang['req_empty_pending_text']); ?></p>
                    </div>
                <?php else: ?>
                    <ul class="space-y-3">
                        <?php foreach ($pending as $r): $rid = (int)$r['id']; ?>
                            <li class="<?php echo $card; ?> p-4 sm:p-5" data-request="<?php echo $rid; ?>">
                                <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-lg leading-tight" data-person><?php echo $e(strtr($lang['req_requested_by'], ['{name}' => MatchRequests::firstName($r['user_name'])])); ?></p>
                                        <p class="mt-1 font-semibold text-brand-600 dark:text-brand-400" data-when><span class="mr-1.5"><i class="far fa-clock" aria-hidden="true"></i></span><?php echo $e($when($r)); ?></p>
                                        <p class="mt-1 text-sm text-content-secondary">
                                            <span class="mr-1"><i class="fas fa-location-dot" aria-hidden="true"></i></span><?php echo $e($r['venue_name']); ?>
                                            <?php if (!empty($r['sport_name'])): ?> · <?php echo $e($r['sport_name']); ?><?php endif; ?>
                                            · <?php echo $e(strtr($lang['req_asked_at'], ['{when}' => $ago($r['created_at'])])); ?>
                                        </p>
                                        <?php if (!empty($r['message'])): ?>
                                            <p class="mt-2 text-sm px-3 py-2 rounded-xl bg-surface-base border border-content-secondary/10 break-words" data-message>"<?php echo $e($r['message']); ?>"</p>
                                        <?php endif; ?>
                                    </div>
                                    <form method="POST" action="<?php echo $base . '/pedidos/' . $rid . '/aprovar'; ?>" class="shrink-0"
                                        data-confirm="<?php echo $e(strtr($lang['req_approve_confirm'], ['{when}' => $when($r)])); ?>" data-confirm-ok="<?php echo $e($lang['req_approve']); ?>">
                                        <?php echo Csrf::field(); ?>
                                        <button type="submit" data-approve class="h-10 px-4 inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold transition-colors"><span><i class="fas fa-check" aria-hidden="true"></i></span><?php echo $e($lang['req_approve']); ?></button>
                                    </form>
                                </div>
                                <details class="mt-3">
                                    <summary class="inline-flex cursor-pointer text-sm font-semibold text-red-600 dark:text-red-400 hover:underline"><?php echo $e($lang['req_decline']); ?></summary>
                                    <form method="POST" action="<?php echo $base . '/pedidos/' . $rid . '/recusar'; ?>" class="mt-3 max-w-xl">
                                        <?php echo Csrf::field(); ?>
                                        <label for="note-<?php echo $rid; ?>" class="block text-sm font-medium"><?php echo $e($lang['req_decline_note_label']); ?></label>
                                        <textarea id="note-<?php echo $rid; ?>" name="note" rows="2" maxlength="<?php echo MatchRequests::NOTE_MAX; ?>" placeholder="<?php echo $e($lang['req_decline_note_ph']); ?>"
                                            class="mt-1 w-full rounded-xl border border-content-secondary/25 bg-surface-base px-3 py-2 text-sm"></textarea>
                                        <button type="submit" data-decline class="mt-2 h-9 px-3 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold"><?php echo $e($lang['req_decline_send']); ?></button>
                                    </form>
                                </details>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="mt-3 text-xs text-content-secondary"><?php echo $e($lang['req_pending_hint']); ?></p>
                <?php endif; ?>
            </section>

            <!-- Histórico de 30 dias -->
            <?php if ($history): ?>
                <section id="req-history" class="mt-10" aria-labelledby="req-history-title">
                    <h2 id="req-history-title" class="text-sm font-semibold uppercase tracking-widest text-content-secondary mb-3"><?php echo $e($lang['req_history_title']); ?></h2>
                    <ul class="<?php echo $card; ?> divide-y divide-content-secondary/10 overflow-hidden">
                        <?php foreach ($history as $r): [$stKey, $stClass] = $statusPill[$r['status']] ?? ['req_status_declined', '']; $wa = $r['status'] === 'approved' ? $whatsappUrl($r) : null; ?>
                            <li class="px-4 sm:px-5 py-3.5 flex flex-col sm:flex-row sm:items-center gap-2" data-request="<?php echo (int)$r['id']; ?>" data-status="<?php echo $e($r['status']); ?>">
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold truncate"><?php echo $e(MatchRequests::firstName($r['user_name'])); ?> · <?php echo $e($when($r)); ?></p>
                                    <p class="text-xs text-content-secondary break-words"><?php echo $e($r['venue_name']); ?><?php if (!empty($r['response_note'])): ?> · <?php echo $e($r['response_note']); ?><?php endif; ?></p>
                                </div>
                                <?php if ($r['status'] === 'approved'): ?>
                                    <?php if ($wa): ?>
                                        <a href="<?php echo $e($wa); ?>" target="_blank" rel="noopener" data-whatsapp class="text-sm font-semibold text-green-700 dark:text-green-400 hover:underline"><span class="mr-1"><i class="fab fa-whatsapp" aria-hidden="true"></i></span><?php echo $e($lang['req_whatsapp']); ?></a>
                                    <?php else: ?>
                                        <span class="text-xs text-content-secondary"><?php echo $e($lang['req_no_whatsapp']); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <span class="self-start sm:self-auto text-xs font-semibold rounded-full px-2.5 py-1 <?php echo $stClass; ?>"><?php echo $e($lang[$stKey]); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        <?php else: ?>
            <!-- Avisos -->
            <?php if ($chatUnread): ?>
                <a href="<?php echo BASE_URL; ?>/dashboard/mensagens" id="req-chat" class="mb-4 flex items-center gap-3 px-4 py-3 rounded-2xl border border-brand-500/30 bg-brand-500/10 text-sm font-semibold text-brand-700 dark:text-brand-300 hover:bg-brand-500/15 transition-colors">
                    <span><i class="fas fa-comments" aria-hidden="true"></i></span>
                    <span class="flex-1"><?php echo $e(strtr($lang[$chatUnread === 1 ? 'req_chat_one' : 'req_chat_many'], ['{n}' => $chatUnread])); ?></span>
                    <span><i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                </a>
            <?php endif; ?>
            <?php if (!$notifications): ?>
                <div id="notif-empty" class="<?php echo $card; ?> p-8 text-center">
                    <span class="w-14 h-14 mx-auto rounded-2xl bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center text-2xl" aria-hidden="true"><i class="fas fa-bell"></i></span>
                    <h3 class="text-lg font-bold mt-4"><?php echo $e($lang['req_empty_notifs_title']); ?></h3>
                    <p class="text-sm text-content-secondary mt-1"><?php echo $e($lang['req_empty_notifs_text']); ?></p>
                </div>
            <?php else: ?>
                <ul id="notif-list" class="<?php echo $card; ?> divide-y divide-content-secondary/10 overflow-hidden">
                    <?php foreach ($notifications as $n):
                        $unread = $n['read_at'] === null;
                        $venueId = (int)($n['data']['venue_id'] ?? 0);
                        $link = str_starts_with($n['type'], 'request_') ? $base . '?aba=solicitacoes'
                            : ($n['type'] === 'ad_removed' && $venueId ? BASE_URL . '/dashboard/quadras/' . $venueId . '/anuncio' : null);
                        $icon = ['request_new' => 'fa-calendar-plus', 'request_cancelled' => 'fa-calendar-xmark', 'ad_removed' => 'fa-bullhorn'][$n['type']] ?? 'fa-bell'; ?>
                        <li data-notification="<?php echo (int)$n['id']; ?>" data-type="<?php echo $e($n['type']); ?>" data-unread="<?php echo $unread ? '1' : '0'; ?>">
                            <<?php echo $link ? 'a href="' . $e($link) . '"' : 'div'; ?> class="flex items-start gap-3 px-4 sm:px-5 py-4 <?php echo $link ? 'hover:bg-surface-secondary transition-colors' : ''; ?> <?php echo $unread ? 'bg-brand-500/5' : ''; ?>">
                                <span class="w-9 h-9 shrink-0 rounded-full flex items-center justify-center <?php echo $unread ? 'bg-brand-600 text-white' : 'bg-surface-secondary text-content-secondary'; ?>" aria-hidden="true"><i class="fas <?php echo $icon; ?> text-sm"></i></span>
                                <span class="flex-1 min-w-0">
                                    <span class="flex items-baseline gap-2">
                                        <span class="<?php echo $unread ? 'font-bold' : 'font-semibold'; ?>"><?php echo $e($n['title']); ?></span>
                                        <?php if ($unread): ?><span class="text-[0.6875rem] font-bold uppercase text-brand-600 dark:text-brand-400"><?php echo $e($lang['req_new_badge']); ?></span><?php endif; ?>
                                        <span class="ml-auto shrink-0 text-xs text-content-secondary"><?php echo $e($ago($n['created_at'])); ?></span>
                                    </span>
                                    <span class="block text-sm text-content-secondary mt-0.5 break-words"><?php echo $e($n['body']); ?></span>
                                </span>
                            </<?php echo $link ? 'a' : 'div'; ?>>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
</body>

</html>
