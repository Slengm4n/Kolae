<?php
/**
 * Aba Visão geral da quadra (D47): a quadra como o atleta vê no app, o dia de hoje e o que
 * falta para ela ficar completa. Dados vindos do VenueOverviewController.
 * Galeria e informações vêm dos partials venue_gallery e venue_details (também na página pública).
 */
use App\Models\User;

$lang = require BASE_PATH . '/includes/i18n.php';
$pageTitle = $lang['venue_tab_overview'] . ' · ' . $venue['name'] . ' · Kolae';
$venueId = (int)$venue['id'];
$tabBase = BASE_URL . $routePrefix . '/quadras';
$tabUrl = [
    'editar' => "$tabBase/editar/$venueId",
    'fotos' => "$tabBase/$venueId/fotos",
    'horario' => "$tabBase/$venueId/horario",
    'agenda' => "$tabBase/$venueId/agenda",
    'redes' => "$tabBase/editar/$venueId#redes",
];
$e = fn($s) => htmlspecialchars((string)$s);

// Preço como no app (D31): valor por hora, grátis ou não informado
$price = $venue['average_price_per_hour'];
if ($price === null) {
    $priceText = $lang['overview_price_none'];
} elseif ((float)$price == 0) {
    $priceText = $lang['data_price_free'];
} else {
    $priceText = 'R$ ' . number_format((float)$price, 2, ',', '.');
}

// Itens opcionais (ex: redes sociais, D60) aparecem na lista, mas não entram no "x de y"
$required = array_filter($checklist, fn($item) => empty($item[3]));
$done = count(array_filter($required, fn($item) => $item[1]));
$total = count($required);
$nowTs = $now->getTimestamp();

$galleryEmpty = '<a href="' . $tabUrl['fotos'] . '" class="flex flex-col items-center justify-center gap-2 text-center aspect-[16/7] rounded-2xl border-2 border-dashed border-content-secondary/25 hover:border-brand-500 hover:bg-brand-500/5 transition-colors px-6">'
    . '<span class="text-3xl text-content-secondary/60"><i class="fas fa-image"></i></span>'
    . '<span class="font-semibold">' . $lang['overview_no_photos_title'] . '</span>'
    . '<span class="text-sm text-content-secondary">' . $lang['overview_no_photos_text'] . '</span>'
    . '<span class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-brand-600 dark:text-brand-400"><i class="fas fa-plus"></i>' . $lang['overview_add_photos'] . '</span></a>';
$editLinks = ['editar' => $tabUrl['editar'], 'horario' => $tabUrl['horario'], 'redes' => $tabUrl['redes']];
?>
<!DOCTYPE html>
<html lang="<?php echo $e($_SESSION['idioma'] ?? 'pt-br'); ?>">
<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>
<body class="bg-surface-base text-content-primary font-poppins">
    <?php include BASE_PATH . '/app/views/partials/owner_header.php'; ?>

    <main class="container mx-auto px-4 py-6 md:py-10 max-w-4xl pb-28 md:pb-10">
        <?php include BASE_PATH . '/app/views/partials/venue_top.php'; ?>

        <?php include BASE_PATH . '/app/views/partials/venue_gallery.php'; ?>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-content-secondary">
            <p class="flex items-center gap-2"><span><i class="fas fa-mobile-screen"></i></span><?php echo $lang['overview_preview_note']; ?></p>
            <?php if ($venue['status'] !== 'unavailable'): ?>
                <div class="flex items-center gap-4">
                    <button type="button" data-qr-open class="inline-flex items-center gap-1.5 font-semibold text-brand-600 dark:text-brand-400 hover:underline">
                        <span><i class="fas fa-qrcode"></i></span><?php echo $lang['qr_button']; ?>
                    </button>
                    <a href="<?php echo BASE_URL . '/quadra/' . $venueId; ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-semibold text-brand-600 dark:text-brand-400 hover:underline">
                        <span><i class="fas fa-arrow-up-right-from-square"></i></span><?php echo $lang['overview_public_page']; ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-8 lg:grid lg:grid-cols-[1fr_21rem] lg:gap-10">
            <!-- Coluna principal: como no app -->
            <?php include BASE_PATH . '/app/views/partials/venue_details.php'; ?>

            <!-- Lateral: preço, hoje, contato e o que falta -->
            <aside class="mt-10 lg:mt-0 space-y-5">
                <div class="lg:sticky lg:top-24 space-y-5">
                    <section class="bg-surface-elevated border border-content-secondary/10 rounded-2xl p-5 shadow-sm">
                        <p class="text-2xl font-bold">
                            <?php echo $e($priceText); ?>
                            <?php if ($price !== null && (float)$price > 0): ?><span class="text-sm font-normal text-content-secondary"><?php echo $lang['overview_per_hour']; ?></span><?php endif; ?>
                        </p>

                        <div class="mt-5 pt-5 border-t border-content-secondary/10">
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="font-semibold"><?php echo $lang['overview_today']; ?></h2>
                                <span class="text-sm text-content-secondary">
                                    <?php echo $todayHours
                                        ? $e(strtr($lang['overview_today_hours'], ['{opens}' => substr($todayHours['opens_at'], 0, 5), '{closes}' => substr($todayHours['closes_at'], 0, 5)]))
                                        : $lang['overview_closed_today']; ?>
                                </span>
                            </div>
                            <?php if ($todayMatches): ?>
                                <ul class="mt-3 space-y-2">
                                    <?php foreach ($todayMatches as $m):
                                        $start = new DateTimeImmutable($m['start_time']);
                                        $end = new DateTimeImmutable($m['end_time']);
                                        $ongoing = $start->getTimestamp() <= $nowTs && $end->getTimestamp() > $nowTs;
                                        $past = $end->getTimestamp() <= $nowTs; ?>
                                        <li class="flex items-center gap-3 text-sm <?php echo $past ? 'opacity-50' : ''; ?>">
                                            <span class="tabular-nums font-semibold w-24 shrink-0"><?php echo $start->format('H:i') . '–' . $end->format('H:i'); ?></span>
                                            <span class="truncate text-content-secondary"><?php echo $e($m['renter_name'] ?: ($m['sport_name'] ?: $lang['hours_renter_none'])); ?></span>
                                            <?php if ($ongoing): ?><span class="ml-auto shrink-0 status-badge status-badge--in-use"><?php echo $lang['agenda_now']; ?></span><?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="mt-2 text-sm text-content-secondary"><?php echo $lang['overview_no_matches']; ?></p>
                            <?php endif; ?>
                            <a href="<?php echo $tabUrl['agenda']; ?>" class="mt-4 w-full inline-flex items-center justify-center gap-2 bg-brand-500 hover:bg-brand-600 text-white font-semibold py-2.5 rounded-xl transition-colors">
                                <span><i class="fas fa-calendar-plus"></i></span><?php echo $lang['overview_open_agenda']; ?>
                            </a>
                        </div>
                    </section>

                    <section class="bg-surface-elevated border border-content-secondary/10 rounded-2xl p-5 shadow-sm">
                        <h2 class="font-semibold flex items-center gap-2"><span class="text-green-600"><i class="fab fa-whatsapp"></i></span><?php echo $lang['overview_contact_title']; ?></h2>
                        <?php if ($ownerWhatsapp): ?>
                            <p class="mt-2 text-lg font-semibold tabular-nums"><?php echo $e(User::formatWhatsapp($ownerWhatsapp)); ?></p>
                            <p class="text-sm text-content-secondary mt-1"><?php echo $lang['overview_contact_text']; ?></p>
                        <?php else: ?>
                            <p class="mt-2 text-sm text-amber-800 dark:text-amber-300"><?php echo $lang['overview_contact_missing']; ?></p>
                            <a href="<?php echo $tabUrl['editar']; ?>" class="mt-2 inline-block text-sm font-semibold text-brand-600 dark:text-brand-400 hover:underline"><?php echo $lang['overview_add']; ?></a>
                        <?php endif; ?>
                    </section>

                    <section class="bg-surface-elevated border border-content-secondary/10 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="font-semibold"><?php echo $lang['overview_checklist_title']; ?></h2>
                            <span class="text-sm font-semibold tabular-nums <?php echo $done === $total ? 'text-green-600 dark:text-green-400' : 'text-content-secondary'; ?>"><?php echo strtr($lang['overview_checklist_progress'], ['{done}' => $done, '{total}' => $total]); ?></span>
                        </div>
                        <div class="mt-3 h-2 rounded-full bg-surface-secondary overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo $total; ?>" aria-valuenow="<?php echo $done; ?>" aria-label="<?php echo $e($lang['overview_checklist_title']); ?>">
                            <div class="h-full rounded-full <?php echo $done === $total ? 'bg-green-500' : 'bg-brand-500'; ?>" style="width: <?php echo round($done / $total * 100); ?>%"></div>
                        </div>
                        <?php if ($done === $total): ?>
                            <p class="mt-3 text-sm text-green-700 dark:text-green-400"><?php echo $lang['overview_checklist_done']; ?></p>
                        <?php endif; ?>
                        <ul class="mt-4 space-y-2.5">
                            <?php foreach ($checklist as [$key, $ok, $tab, $optional]): ?>
                                <li class="flex items-center gap-3 text-sm" data-check="<?php echo $key; ?>">
                                    <span class="w-5 h-5 shrink-0 rounded-full flex items-center justify-center text-[10px] <?php echo $ok ? 'bg-green-500 text-white' : ($optional ? 'border-2 border-dashed border-content-secondary/30' : 'border-2 border-content-secondary/30'); ?>"><?php echo $ok ? '<i class="fas fa-check"></i>' : ''; ?></span>
                                    <?php if ($ok): ?>
                                        <span class="text-content-secondary"><?php echo $lang['overview_check_' . $key]; ?></span>
                                    <?php else: ?>
                                        <a href="<?php echo $tabUrl[$tab]; ?>" class="font-medium hover:text-brand-600 dark:hover:text-brand-400 hover:underline"><?php echo $lang['overview_check_' . $key]; ?></a>
                                    <?php endif; ?>
                                    <?php if ($optional): ?><span class="text-xs text-content-secondary">(<?php echo $lang['agenda_optional']; ?>)</span><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                </div>
            </aside>
        </div>
    </main>

    <?php if ($venue['status'] !== 'unavailable'):
        $publicUrl = BASE_URL . '/quadra/' . $venueId;
        include BASE_PATH . '/app/views/partials/venue_qr.php';
    endif; ?>
    <?php // Menu do usuário e botão de tema do cabeçalho (initNavbar, initTheme) ?>
    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
</body>
</html>
