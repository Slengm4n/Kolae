<?php
/**
 * Mensagens do dono (D66): as conversas das quadras dele, a mais recente primeiro, com quem
 * pergunta (nome e foto, nunca o e-mail), a quadra, a última mensagem e as não lidas.
 * Com mais de uma quadra, filtro por quadra. Dados do ChatController::index.
 */
$lang = require BASE_PATH . '/includes/i18n.php';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$pageTitle = $lang['chat_title'] . ' · Kolae';
$toastContext = 'chat';
$base = BASE_URL . '/dashboard/mensagens';

// "14:05" hoje, "Ontem", ou "03/11"
$when = function (string $datetime) use ($lang): string {
    $day = date('Y-m-d', strtotime($datetime));
    if ($day === date('Y-m-d')) return date('H:i', strtotime($datetime));
    if ($day === date('Y-m-d', strtotime('-1 day'))) return $lang['chat_yesterday'];
    return date('d/m', strtotime($datetime));
};
$initial = fn($name) => mb_strtoupper(mb_substr(trim((string)$name) ?: '?', 0, 1));
$pill = 'shrink-0 px-3.5 py-1.5 rounded-full text-sm font-semibold border transition-colors';
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
            <h1 class="text-3xl font-bold"><?php echo $e($lang['chat_title']); ?></h1>
            <p class="text-sm text-content-secondary mt-1"><?php echo $e($lang['chat_subtitle']); ?></p>
        </div>

        <?php if (count($venues) > 1): ?>
            <nav id="chat-venues" class="flex gap-2 overflow-x-auto no-scrollbar pb-1 mb-5" aria-label="<?php echo $e($lang['chat_all_venues']); ?>">
                <a href="<?php echo $base; ?>" <?php echo $venueId === null ? 'aria-current="page"' : ''; ?>
                    class="<?php echo $pill . ($venueId === null ? ' bg-brand-600 border-brand-600 text-white' : ' border-content-secondary/20 text-content-secondary hover:text-content-primary hover:border-content-secondary/40'); ?>"><?php echo $e($lang['chat_all_venues']); ?></a>
                <?php foreach ($venues as $v): $active = $venueId === (int)$v['id']; ?>
                    <a href="<?php echo $base . '?quadra=' . (int)$v['id']; ?>" data-venue="<?php echo (int)$v['id']; ?>" <?php echo $active ? 'aria-current="page"' : ''; ?>
                        class="<?php echo $pill . ($active ? ' bg-brand-600 border-brand-600 text-white' : ' border-content-secondary/20 text-content-secondary hover:text-content-primary hover:border-content-secondary/40'); ?>"><?php echo $e($v['name']); ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <?php if (!$conversations): ?>
            <div id="chat-empty" class="bg-surface-elevated rounded-2xl border border-content-secondary/10 shadow-sm p-10 text-center">
                <span class="w-14 h-14 mx-auto rounded-2xl bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center text-2xl" aria-hidden="true"><i class="fas fa-comments"></i></span>
                <?php if ($venueId !== null): ?>
                    <p class="mt-4 text-content-secondary"><?php echo $e($lang['chat_empty_filter']); ?></p>
                <?php else: ?>
                    <h2 class="text-lg font-bold mt-4"><?php echo $e($lang['chat_empty_title']); ?></h2>
                    <p class="text-sm text-content-secondary mt-1 max-w-md mx-auto"><?php echo $e($lang['chat_empty_text']); ?></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <ul id="chat-list" class="bg-surface-elevated rounded-2xl border border-content-secondary/10 shadow-sm divide-y divide-content-secondary/10 overflow-hidden">
                <?php foreach ($conversations as $c):
                    $unread = (int)$c['unread_count'];
                    $last = $c['last_message'];
                    $mine = (int)$last['sender_id'] === (int)$_SESSION['user_id'];
                    $preview = preg_replace('/\s+/u', ' ', $last['body']); ?>
                    <li data-conversation="<?php echo (int)$c['id']; ?>" data-unread="<?php echo $unread; ?>">
                        <a href="<?php echo $base . '/' . (int)$c['id']; ?>" class="flex items-center gap-4 px-4 sm:px-5 py-4 hover:bg-surface-secondary transition-colors">
                            <?php if (!empty($c['user_avatar_path'])): ?>
                                <img src="<?php echo BASE_URL . '/uploads/avatars/' . (int)$c['user_id'] . '/' . rawurlencode($c['user_avatar_path']); ?>" alt="" class="w-11 h-11 rounded-full object-cover shrink-0" loading="lazy">
                            <?php else: ?>
                                <span class="w-11 h-11 rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-white font-bold flex items-center justify-center shrink-0" aria-hidden="true"><?php echo $e($initial($c['user_name'])); ?></span>
                            <?php endif; ?>
                            <span class="flex-1 min-w-0">
                                <span class="flex items-baseline gap-2">
                                    <span class="truncate <?php echo $unread ? 'font-bold' : 'font-semibold'; ?>" data-name><?php echo $e($c['user_name']); ?></span>
                                    <span class="ml-auto shrink-0 text-xs <?php echo $unread ? 'text-brand-600 dark:text-brand-400 font-semibold' : 'text-content-secondary'; ?>"><?php echo $e($when($last['created_at'])); ?></span>
                                </span>
                                <span class="block text-xs text-content-secondary truncate mt-0.5">
                                    <i class="fas fa-location-dot mr-1" aria-hidden="true"></i><?php echo $e($c['venue_name']); ?>
                                    <?php if (!empty($c['blocked_at'])): ?><span class="ml-1.5 text-red-600 dark:text-red-400"><i class="fas fa-ban mr-0.5" aria-hidden="true"></i><?php echo $e($lang['chat_block']); ?></span><?php endif; ?>
                                </span>
                                <span class="flex items-center gap-2 mt-1">
                                    <span class="flex-1 truncate text-sm <?php echo $unread ? 'text-content-primary font-medium' : 'text-content-secondary'; ?>" data-preview><?php echo $mine ? $e($lang['chat_you']) . ': ' : ''; ?><?php echo $e($preview); ?></span>
                                    <?php if ($unread): ?>
                                        <span class="shrink-0 min-w-[1.375rem] h-[1.375rem] px-1.5 rounded-full bg-brand-600 text-white text-xs font-bold flex items-center justify-center" aria-label="<?php echo $e(strtr($lang['chat_unread_aria'], ['{n}' => $unread])); ?>"><?php echo $unread > 99 ? '99+' : $unread; ?></span>
                                    <?php endif; ?>
                                </span>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </main>

    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
</body>

</html>
