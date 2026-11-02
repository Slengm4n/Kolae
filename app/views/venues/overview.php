<?php
/**
 * Aba Visão geral da quadra (D47): a quadra como o atleta vê no app, o dia de hoje e o que
 * falta para ela ficar completa. Dados vindos do VenueOverviewController.
 */
use App\Models\User;
use App\Models\Venue;

$lang = require BASE_PATH . '/includes/i18n.php';
$pageTitle = $lang['venue_tab_overview'] . ' · ' . $venue['name'] . ' · Kolae';
$venueId = (int)$venue['id'];
$tabBase = BASE_URL . $routePrefix . '/quadras';
$tabUrl = [
    'editar' => "$tabBase/editar/$venueId",
    'fotos' => "$tabBase/$venueId/fotos",
    'horario' => "$tabBase/$venueId/horario",
    'agenda' => "$tabBase/$venueId/agenda",
];
$imgUrl = fn(array $img) => BASE_URL . '/uploads/venues/' . $venueId . '/' . rawurlencode(basename($img['file_path']));
$photoCount = count($images);
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

// Estrutura: só o que a quadra tem, com o que falta entre parênteses no fim
$facts = [];
if (!empty($venue['floor_type']) && isset(Venue::FLOOR_TYPES[$venue['floor_type']])) {
    $facts[] = ['fa-layer-group', Venue::FLOOR_TYPES[$venue['floor_type']]];
}
$facts[] = [$venue['is_covered'] ? 'fa-house' : 'fa-sun', $venue['is_covered'] ? $lang['overview_covered'] : $lang['overview_uncovered']];
$facts[] = ['fa-lightbulb', $venue['has_lighting'] ? $lang['overview_lighting'] : $lang['overview_no_lighting'], !$venue['has_lighting']];
if ((int)$venue['court_capacity'] > 0) {
    $facts[] = ['fa-users', strtr($lang['overview_capacity'], ['{n}' => (int)$venue['court_capacity']])];
}
if ($venue['has_leisure_area']) {
    $facts[] = ['fa-umbrella-beach', (int)$venue['leisure_area_capacity'] > 0
        ? strtr($lang['overview_leisure_capacity'], ['{n}' => (int)$venue['leisure_area_capacity']])
        : $lang['overview_leisure']];
}

// "Seg a Sex", "Sáb" (igual à aba Horário)
$dayLabel = function (array $days) use ($lang): string {
    $first = $lang['weekday_short_' . $days[0]];
    if (count($days) === 1) return $first;
    $last = $lang['weekday_short_' . end($days)];
    return count($days) === 2 ? "$first, $last" : "$first {$lang['weekday_range_sep']} $last";
};
$hasHours = (bool)array_filter($summary, fn($g) => $g['opens_at'] !== null);

$addressLine = implode(', ', array_filter([trim(($venue['street'] ?? '') . ' ' . ($venue['number'] ?? '')), $venue['complement'] ?? null]));
$cityLine = implode(' · ', array_filter([$venue['neighborhood'] ?? null, trim(($venue['city'] ?? '') . (!empty($venue['state']) ? ' - ' . $venue['state'] : ''))]));
$mapsUrl = ($venue['latitude'] && $venue['longitude'])
    ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($venue['latitude'] . ',' . $venue['longitude'])
    : null;

$done = count(array_filter($checklist, fn($item) => $item[1]));
$total = count($checklist);
$nowTs = $now->getTimestamp();
?>
<!DOCTYPE html>
<html lang="<?php echo $e($_SESSION['idioma'] ?? 'pt-br'); ?>">
<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>
<body class="bg-surface-base text-content-primary font-poppins">
    <?php include BASE_PATH . '/app/views/partials/owner_header.php'; ?>

    <main class="container mx-auto px-4 py-6 md:py-10 max-w-5xl pb-28 md:pb-10">
        <?php include BASE_PATH . '/app/views/partials/venue_top.php'; ?>

        <!-- Galeria -->
        <?php if (!$images): ?>
            <a href="<?php echo $tabUrl['fotos']; ?>" class="flex flex-col items-center justify-center gap-2 text-center aspect-[16/7] rounded-2xl border-2 border-dashed border-content-secondary/25 hover:border-brand-500 hover:bg-brand-500/5 transition-colors px-6">
                <span class="text-3xl text-content-secondary/60"><i class="fas fa-image"></i></span>
                <span class="font-semibold"><?php echo $lang['overview_no_photos_title']; ?></span>
                <span class="text-sm text-content-secondary"><?php echo $lang['overview_no_photos_text']; ?></span>
                <span class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-brand-600 dark:text-brand-400"><i class="fas fa-plus"></i><?php echo $lang['overview_add_photos']; ?></span>
            </a>
        <?php else: ?>
            <section aria-label="<?php echo $e($lang['overview_gallery_label']); ?>" class="relative">
                <!-- Celular: carrossel de arrastar -->
                <div class="md:hidden -mx-4">
                    <div id="carousel" class="flex overflow-x-auto snap-x snap-mandatory scroll-smooth no-scrollbar">
                        <?php foreach ($images as $i => $img): ?>
                            <button type="button" class="open-photo shrink-0 w-full snap-center aspect-[4/3] bg-surface-secondary" data-index="<?php echo $i; ?>" aria-label="<?php echo $e(strtr($lang['photos_alt'], ['{n}' => $i + 1])); ?>">
                                <img src="<?php echo $imgUrl($img); ?>" alt="" class="w-full h-full object-cover" <?php echo $i ? 'loading="lazy"' : ''; ?>>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($photoCount > 1): ?>
                        <span id="carousel-counter" class="absolute right-3 bottom-3 text-xs font-semibold bg-black/60 text-white rounded-full px-2.5 py-1 tabular-nums" aria-live="polite">1 / <?php echo $photoCount; ?></span>
                    <?php endif; ?>
                </div>

                <!-- Desktop: foto grande + até 4 menores -->
                <?php
                $small = array_slice($images, 1, 4);
                $smallSpan = [1 => ['col-span-2 row-span-2'], 2 => ['col-span-2', 'col-span-2'], 3 => ['col-span-2', '', ''], 4 => ['', '', '', '']][count($small)] ?? [];
                ?>
                <div class="hidden md:grid grid-cols-4 grid-rows-2 gap-2 h-[26rem] rounded-2xl overflow-hidden">
                    <button type="button" class="open-photo group relative <?php echo $small ? 'col-span-2 row-span-2' : 'col-span-4 row-span-2'; ?> bg-surface-secondary overflow-hidden" data-index="0" aria-label="<?php echo $e(strtr($lang['photos_alt'], ['{n}' => 1])); ?>">
                        <img src="<?php echo $imgUrl($images[0]); ?>" alt="" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
                    </button>
                    <?php foreach ($small as $j => $img): ?>
                        <button type="button" class="open-photo group relative <?php echo $smallSpan[$j]; ?> bg-surface-secondary overflow-hidden" data-index="<?php echo $j + 1; ?>" aria-label="<?php echo $e(strtr($lang['photos_alt'], ['{n}' => $j + 2])); ?>">
                            <img src="<?php echo $imgUrl($img); ?>" alt="" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.03]" loading="lazy">
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php if ($photoCount > 1): ?>
                    <button type="button" class="open-photo hidden md:inline-flex absolute right-4 bottom-4 items-center gap-2 bg-white text-neutral-900 text-sm font-semibold rounded-lg px-4 py-2 shadow-lg hover:bg-neutral-100" data-index="0">
                        <span><i class="fas fa-table-cells"></i></span><?php echo strtr($lang['overview_see_all'], ['{n}' => $photoCount]); ?>
                    </button>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <p class="mt-3 text-xs text-content-secondary flex items-center gap-2"><span><i class="fas fa-mobile-screen"></i></span><?php echo $lang['overview_preview_note']; ?></p>

        <div class="mt-8 lg:grid lg:grid-cols-[1fr_21rem] lg:gap-10">
            <!-- Coluna principal: como no app -->
            <div class="space-y-8">
                <section>
                    <h2 class="text-lg font-bold"><?php echo $lang['overview_sports']; ?></h2>
                    <?php if ($sports): ?>
                        <ul class="mt-3 flex flex-wrap gap-2">
                            <?php foreach ($sports as $sport): ?>
                                <li class="inline-flex items-center gap-2 rounded-full bg-brand-500/10 text-brand-700 dark:text-brand-300 px-3 py-1.5 text-sm font-medium">
                                    <?php if (!empty($sport['icon_path'])): ?><span><i class="fas <?php echo $e($sport['icon_path']); ?>"></i></span><?php endif; ?><?php echo $e($sport['name']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="mt-2 text-sm text-content-secondary"><?php echo $lang['overview_no_sports']; ?> <a href="<?php echo $tabUrl['editar']; ?>" class="font-semibold text-brand-600 dark:text-brand-400 hover:underline"><?php echo $lang['overview_add']; ?></a></p>
                    <?php endif; ?>
                </section>

                <section class="pt-8 border-t border-content-secondary/10">
                    <h2 class="text-lg font-bold"><?php echo $lang['overview_facts_title']; ?></h2>
                    <ul class="mt-4 grid sm:grid-cols-2 gap-x-6 gap-y-3">
                        <?php foreach ($facts as $fact): ?>
                            <li class="flex items-center gap-3 <?php echo !empty($fact[2]) ? 'text-content-secondary' : ''; ?>">
                                <span class="w-9 h-9 shrink-0 rounded-xl bg-surface-secondary flex items-center justify-center text-content-secondary"><i class="fas <?php echo $fact[0]; ?>"></i></span>
                                <span class="text-sm"><?php echo $e($fact[1]); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <section class="pt-8 border-t border-content-secondary/10">
                    <h2 class="text-lg font-bold"><?php echo $lang['data_description']; ?></h2>
                    <?php if (trim((string)$venue['description']) !== ''): ?>
                        <p class="mt-3 text-content-secondary leading-relaxed whitespace-pre-line"><?php echo $e($venue['description']); ?></p>
                    <?php else: ?>
                        <p class="mt-2 text-sm text-content-secondary"><?php echo $lang['overview_no_description']; ?> <a href="<?php echo $tabUrl['editar']; ?>" class="font-semibold text-brand-600 dark:text-brand-400 hover:underline"><?php echo $lang['overview_add']; ?></a></p>
                    <?php endif; ?>
                </section>

                <section class="pt-8 border-t border-content-secondary/10">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-bold"><?php echo $lang['overview_hours_title']; ?></h2>
                        <a href="<?php echo $tabUrl['horario']; ?>" class="text-sm font-semibold text-brand-600 dark:text-brand-400 hover:underline"><?php echo $lang['overview_edit']; ?></a>
                    </div>
                    <?php if (!$hasHours): ?>
                        <p class="mt-2 text-sm text-content-secondary"><?php echo $lang['hours_summary_empty']; ?></p>
                    <?php else: ?>
                        <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-8 gap-y-2 text-sm">
                            <?php foreach ($summary as $group): ?>
                                <dt class="font-semibold"><?php echo $e($dayLabel($group['days'])); ?></dt>
                                <dd class="<?php echo $group['opens_at'] ? '' : 'text-content-secondary'; ?>"><?php echo $group['opens_at'] ? $group['opens_at'] . '–' . $group['closes_at'] : $lang['hours_closed']; ?></dd>
                            <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>
                </section>

                <section class="pt-8 border-t border-content-secondary/10">
                    <h2 class="text-lg font-bold"><?php echo $lang['overview_address_title']; ?></h2>
                    <p class="mt-3"><?php echo $e($addressLine); ?></p>
                    <p class="text-sm text-content-secondary"><?php echo $e($cityLine); ?><?php echo !empty($venue['cep']) ? ' · ' . $e($venue['cep']) : ''; ?></p>
                    <?php if ($mapsUrl): ?>
                        <a href="<?php echo $e($mapsUrl); ?>" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-brand-600 dark:text-brand-400 hover:underline">
                            <span><i class="fas fa-map-location-dot"></i></span><?php echo $lang['overview_open_maps']; ?>
                        </a>
                    <?php endif; ?>
                </section>
            </div>

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
                            <?php foreach ($checklist as [$key, $ok, $tab]): ?>
                                <li class="flex items-center gap-3 text-sm">
                                    <span class="w-5 h-5 shrink-0 rounded-full flex items-center justify-center text-[10px] <?php echo $ok ? 'bg-green-500 text-white' : 'border-2 border-content-secondary/30'; ?>"><?php echo $ok ? '<i class="fas fa-check"></i>' : ''; ?></span>
                                    <?php if ($ok): ?>
                                        <span class="text-content-secondary"><?php echo $lang['overview_check_' . $key]; ?></span>
                                    <?php else: ?>
                                        <a href="<?php echo $tabUrl[$tab]; ?>" class="font-medium hover:text-brand-600 dark:hover:text-brand-400 hover:underline"><?php echo $lang['overview_check_' . $key]; ?></a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                </div>
            </aside>
        </div>
    </main>

    <?php if ($images): ?>
        <!-- Fotos em tela cheia -->
        <div id="lightbox" class="fixed inset-0 z-[60] bg-neutral-950 hidden flex-col" role="dialog" aria-modal="true" aria-label="<?php echo $e($lang['overview_gallery_label']); ?>">
            <div class="flex items-center justify-between p-4 text-white">
                <span id="lightbox-counter" class="text-sm tabular-nums" aria-live="polite"></span>
                <button type="button" id="lightbox-close" class="w-11 h-11 rounded-full hover:bg-white/10 flex items-center justify-center text-xl" aria-label="<?php echo $e($lang['overview_close']); ?>"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="relative flex-1 min-h-0">
                <div id="lightbox-track" class="h-full flex overflow-x-auto snap-x snap-mandatory no-scrollbar">
                    <?php foreach ($images as $i => $img): ?>
                        <div class="shrink-0 w-full h-full snap-center flex items-center justify-center p-2 md:p-10">
                            <img src="<?php echo $imgUrl($img); ?>" alt="<?php echo $e(strtr($lang['photos_alt'], ['{n}' => $i + 1])); ?>" class="max-w-full max-h-full object-contain" loading="lazy">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($photoCount > 1): ?>
                    <button type="button" id="lightbox-prev" class="hidden md:flex absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/90 text-neutral-900 items-center justify-center shadow-lg hover:bg-white" aria-label="<?php echo $e($lang['overview_prev']); ?>"><i class="fas fa-chevron-left"></i></button>
                    <button type="button" id="lightbox-next" class="hidden md:flex absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/90 text-neutral-900 items-center justify-center shadow-lg hover:bg-white" aria-label="<?php echo $e($lang['overview_next']); ?>"><i class="fas fa-chevron-right"></i></button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <style>
        .no-scrollbar { scrollbar-width: none; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
    <script>
        (function () {
            const total = <?php echo $photoCount; ?>;
            if (!total) return;
            const counterText = (i) => (i + 1) + ' / ' + total;
            const indexOf = el => Math.round(el.scrollLeft / el.clientWidth);

            // Contador do carrossel do celular
            const carousel = document.getElementById('carousel');
            const carouselCounter = document.getElementById('carousel-counter');
            if (carousel && carouselCounter) {
                carousel.addEventListener('scroll', () => { carouselCounter.textContent = counterText(indexOf(carousel)); }, { passive: true });
            }

            // Tela cheia
            const box = document.getElementById('lightbox');
            const track = document.getElementById('lightbox-track');
            const counter = document.getElementById('lightbox-counter');
            const closeBtn = document.getElementById('lightbox-close');
            let opener = null;

            const goTo = (i, smooth) => {
                i = Math.max(0, Math.min(total - 1, i));
                track.scrollTo({ left: i * track.clientWidth, behavior: smooth ? 'smooth' : 'auto' });
                counter.textContent = counterText(i);
            };
            const open = (i, from) => {
                opener = from;
                box.classList.remove('hidden');
                box.classList.add('flex');
                document.body.style.overflow = 'hidden';
                goTo(i, false);
                closeBtn.focus();
            };
            const close = () => {
                box.classList.add('hidden');
                box.classList.remove('flex');
                document.body.style.overflow = '';
                if (opener) opener.focus();
            };

            document.querySelectorAll('.open-photo').forEach(btn => btn.addEventListener('click', () => {
                // No celular abre na foto que está aparecendo no carrossel
                const i = btn.closest('#carousel') ? indexOf(carousel) : Number(btn.dataset.index);
                open(i, btn);
            }));
            closeBtn.addEventListener('click', close);
            track.addEventListener('scroll', () => { counter.textContent = counterText(indexOf(track)); }, { passive: true });
            document.getElementById('lightbox-prev')?.addEventListener('click', () => goTo(indexOf(track) - 1, true));
            document.getElementById('lightbox-next')?.addEventListener('click', () => goTo(indexOf(track) + 1, true));
            document.addEventListener('keydown', ev => {
                if (box.classList.contains('hidden')) return;
                if (ev.key === 'Escape') close();
                if (ev.key === 'ArrowLeft') goTo(indexOf(track) - 1, true);
                if (ev.key === 'ArrowRight') goTo(indexOf(track) + 1, true);
            });
            // Clicar fora da foto fecha
            track.addEventListener('click', ev => { if (ev.target === ev.currentTarget || ev.target.tagName === 'DIV') close(); });
        })();
    </script>
</body>
</html>
