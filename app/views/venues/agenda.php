<?php
/**
 * Aba Agenda da quadra (D35, D37): um dia por vez, linha do tempo, partidas e "Agendar partida".
 * Dados vindos do VenueAgendaController.
 */
$lang = require BASE_PATH . '/includes/i18n.php';
$pageTitle = $lang['venue_tab_agenda'] . ' · ' . $venue['name'] . ' · Kolae';
$venueId = (int)$venue['id'];
$base = BASE_URL . $routePrefix . '/quadras/' . $venueId . '/agenda';
$date = $day->format('Y-m-d');
$prev = $day->modify('-1 day')->format('Y-m-d');
$next = $day->modify('+1 day')->format('Y-m-d');
$opens = $timeline['opens_at'];
$closes = $timeline['closes_at'];
$flash = $_GET['status'] ?? null;
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

// Linha do tempo: largura de cada faixa proporcional à duração
$segments = [];
$nowPercent = null;
if ($opens) {
    $dayStart = new DateTimeImmutable("$date $opens:00");
    $dayEnd = new DateTimeImmutable("$date $closes:00");
    $total = max(1, $dayEnd->getTimestamp() - $dayStart->getTimestamp());
    foreach ($timeline['slots'] as $slot) {
        $s = new DateTimeImmutable($slot['start']);
        $f = new DateTimeImmutable($slot['end']);
        $segments[] = [
            'status' => $slot['status'],
            'percent' => ($f->getTimestamp() - $s->getTimestamp()) / $total * 100,
            'label' => $s->format('H:i') . '–' . $f->format('H:i'),
        ];
    }
    if ($isToday && $now >= $dayStart && $now <= $dayEnd) {
        $nowPercent = ($now->getTimestamp() - $dayStart->getTimestamp()) / $total * 100;
    }
}

$errorText = $error ? strtr($lang['agenda_error_' . $error] ?? $lang['agenda_save_error'], ['{opens}' => $opens ?? '', '{closes}' => $closes ?? '']) : null;
$canSchedule = $hasHours && $opens && !$isPast;
?>
<!DOCTYPE html>
<html lang="<?php echo $e($_SESSION['idioma'] ?? 'pt-br'); ?>">
<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>
<body class="bg-surface-base text-content-primary font-poppins">
    <?php include BASE_PATH . '/app/views/partials/owner_header.php'; ?>

    <main class="container mx-auto px-4 py-6 md:py-10 max-w-3xl">
        <?php include BASE_PATH . '/app/views/partials/venue_top.php'; ?>

        <?php
        $flashes = [
            'created' => ['green', 'fa-check-circle', $lang['agenda_created']],
            'cancelled' => ['green', 'fa-check-circle', $lang['agenda_cancelled']],
            'error' => ['red', 'fa-times-circle', $lang['agenda_save_error']],
            'cancel_error' => ['red', 'fa-times-circle', $lang['agenda_cancel_error']],
        ];
        if (isset($flashes[$flash])): [$color, $icon, $msg] = $flashes[$flash]; ?>
            <div class="mb-6 p-4 rounded-xl border flex items-start gap-3 <?php echo $color === 'green' ? 'border-green-500/30 bg-green-500/10 text-green-700 dark:text-green-400' : 'border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-400'; ?>" role="status">
                <span class="mt-0.5"><i class="fas <?php echo $icon; ?>"></i></span><p class="text-sm font-medium"><?php echo $msg; ?></p>
            </div>
        <?php endif; ?>

        <?php if ($venue['status'] === 'maintenance'): ?>
            <div class="mb-6 p-4 rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300 flex items-start gap-3">
                <span class="mt-0.5"><i class="fas fa-screwdriver-wrench"></i></span><p class="text-sm"><?php echo $lang['agenda_maintenance']; ?></p>
            </div>
        <?php endif; ?>

        <!-- Dia -->
        <section class="bg-surface-elevated border border-content-secondary/10 rounded-2xl shadow-sm p-4 md:p-6">
            <div class="flex items-center gap-2">
                <a href="<?php echo "$base?data=$prev"; ?>" class="day-nav" aria-label="<?php echo $e($lang['agenda_prev_day']); ?>" title="<?php echo $e($lang['agenda_prev_day']); ?>"><span><i class="fas fa-chevron-left"></i></span></a>
                <div class="flex-1 text-center min-w-0">
                    <p class="text-lg md:text-xl font-bold text-content-primary leading-tight"><?php echo $lang['weekday_' . $day->format('w')]; ?><?php if ($isToday): ?> <span class="ml-1 align-middle text-xs font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-400 bg-brand-500/10 rounded px-1.5 py-0.5"><?php echo $lang['agenda_today']; ?></span><?php endif; ?></p>
                    <p class="text-sm text-content-secondary"><?php echo $day->format('d/m/Y'); ?></p>
                </div>
                <a href="<?php echo "$base?data=$next"; ?>" class="day-nav" aria-label="<?php echo $e($lang['agenda_next_day']); ?>" title="<?php echo $e($lang['agenda_next_day']); ?>"><span><i class="fas fa-chevron-right"></i></span></a>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-center gap-2 text-sm">
                <?php if (!$isToday): ?>
                    <a href="<?php echo $base; ?>" class="chip-link"><span><i class="fas fa-calendar-day"></i></span><?php echo $lang['agenda_today']; ?></a>
                <?php endif; ?>
                <label class="chip-link cursor-pointer">
                    <span><i class="fas fa-calendar"></i></span><span><?php echo $lang['agenda_pick_date']; ?></span>
                    <input type="date" id="date-picker" value="<?php echo $date; ?>" class="sr-only" aria-label="<?php echo $e($lang['agenda_pick_date']); ?>">
                </label>
            </div>

            <!-- Horário do dia + linha do tempo -->
            <div class="mt-5 pt-5 border-t border-content-secondary/10">
                <?php if (!$hasHours): ?>
                    <p class="text-sm text-amber-700 dark:text-amber-300 flex items-center gap-2"><span><i class="fas fa-clock"></i></span><?php echo $lang['agenda_no_hours']; ?>
                        <a class="font-semibold underline" href="<?php echo BASE_URL . $routePrefix . '/quadras/' . $venueId . '/horario'; ?>"><?php echo $lang['agenda_go_hours']; ?></a></p>
                <?php elseif (!$opens): ?>
                    <p class="font-semibold text-content-primary flex items-center gap-2"><span class="text-content-secondary"><i class="fas fa-moon"></i></span><?php echo $lang['agenda_closed_day']; ?></p>
                    <p class="text-sm text-content-secondary mt-1"><?php echo $lang['agenda_closed_day_text']; ?>
                        <a class="font-semibold text-brand-600 dark:text-brand-400 hover:underline" href="<?php echo BASE_URL . $routePrefix . '/quadras/' . $venueId . '/horario'; ?>"><?php echo $lang['agenda_go_hours']; ?></a></p>
                <?php else: ?>
                    <p class="text-sm text-content-secondary"><?php echo $e(strtr($lang['agenda_open_from'], ['{opens}' => $opens, '{closes}' => $closes])); ?></p>
                    <div class="mt-3 relative" role="img" aria-label="<?php echo $e($lang['agenda_timeline_label']); ?>">
                        <div class="flex h-11 rounded-xl overflow-hidden border border-content-secondary/15">
                            <?php foreach ($segments as $seg):
                                $busy = $seg['status'] === 'in_use'; ?>
                                <div class="timeline-seg <?php echo $busy ? 'is-busy' : 'is-free'; ?>" style="width: <?php echo round($seg['percent'], 3); ?>%" title="<?php echo $e(($busy ? $lang['agenda_busy'] : $lang['agenda_free']) . ' · ' . $seg['label']); ?>">
                                    <?php if ($busy && $seg['percent'] >= 14): ?><span class="truncate px-2"><?php echo $e($seg['label']); ?></span><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($nowPercent !== null): ?>
                            <div class="absolute -top-1 bottom-0 w-0.5 bg-red-500 rounded-full pointer-events-none" style="left: <?php echo round($nowPercent, 3); ?>%">
                                <span class="absolute -top-5 -translate-x-1/2 text-[10px] font-bold uppercase text-red-500"><?php echo $lang['agenda_now']; ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="mt-1.5 flex justify-between text-xs text-content-secondary tabular-nums"><span><?php echo $opens; ?></span><span><?php echo $closes; ?></span></div>
                    </div>
                    <div class="mt-3 flex gap-4 text-xs text-content-secondary">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded timeline-seg is-free border border-brand-500/30"></span><?php echo $lang['agenda_free']; ?></span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded timeline-seg is-busy"></span><?php echo $lang['agenda_busy']; ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Partidas do dia -->
        <section class="mt-6 bg-surface-elevated border border-content-secondary/10 rounded-2xl shadow-sm">
            <h2 class="px-5 md:px-6 pt-5 text-lg font-bold text-content-primary"><?php echo $lang['agenda_matches_title']; ?></h2>
            <?php if (!$matches): ?>
                <p class="px-5 md:px-6 py-5 text-content-secondary flex items-center gap-2"><span><i class="far fa-calendar"></i></span><?php echo $lang['agenda_empty']; ?></p>
            <?php else: ?>
                <ul class="mt-3 divide-y divide-content-secondary/10">
                    <?php foreach ($matches as $m):
                        $ms = new DateTimeImmutable($m['start_time']);
                        $me = new DateTimeImmutable($m['end_time']);
                        $ongoing = $ms <= $now && $now < $me;
                        $finished = $me <= $now; ?>
                        <li class="match-row px-5 md:px-6 py-4 flex items-center gap-4 <?php echo $finished ? 'opacity-60' : ''; ?>" data-match-id="<?php echo (int)$m['id']; ?>">
                            <div class="w-1.5 self-stretch rounded-full <?php echo $finished ? 'bg-content-secondary/30' : 'timeline-seg is-busy'; ?>"></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-content-primary tabular-nums"><?php echo $ms->format('H:i') . '–' . $me->format('H:i'); ?>
                                    <?php if ($ongoing): ?><span class="ml-2 status-badge status-badge--in-use align-middle"><?php echo $lang['agenda_ongoing']; ?></span><?php endif; ?>
                                    <?php if ($finished): ?><span class="ml-2 text-xs font-semibold text-content-secondary"><?php echo $lang['agenda_finished']; ?></span><?php endif; ?>
                                </p>
                                <p class="text-sm text-content-secondary truncate">
                                    <?php echo $e($m['renter_name'] ?: $lang['hours_renter_none']); ?><?php if (!empty($m['sport_name'])): ?> · <?php echo $e($m['sport_name']); ?><?php endif; ?>
                                </p>
                            </div>
                            <?php if (!$finished): ?>
                                <form method="POST" action="<?php echo $base . '/cancelar/' . (int)$m['id']; ?>" class="cancel-form">
                                    <?php echo \App\Core\Csrf::field(); ?>
                                    <input type="hidden" name="data" value="<?php echo $date; ?>">
                                    <button type="submit" class="text-sm font-semibold text-red-600 dark:text-red-400 hover:bg-red-500/10 rounded-lg px-3 py-2 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"><?php echo $lang['agenda_cancel']; ?></button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <!-- Agendar partida -->
        <section class="mt-6 bg-surface-elevated border border-content-secondary/10 rounded-2xl shadow-sm p-5 md:p-6" id="nova-partida">
            <h2 class="text-lg font-bold text-content-primary"><?php echo $lang['agenda_new_title']; ?></h2>
            <?php if ($isPast): ?>
                <p class="mt-2 text-content-secondary"><?php echo $lang['agenda_past_day']; ?></p>
            <?php elseif (!$canSchedule): ?>
                <p class="mt-2 text-content-secondary"><?php echo $hasHours ? $lang['agenda_closed_day_text'] : $lang['agenda_no_hours']; ?></p>
            <?php else: ?>
                <p class="mt-1 text-sm text-content-secondary flex items-start gap-2"><span class="mt-0.5"><i class="fas fa-lock"></i></span><?php echo $lang['agenda_new_text']; ?></p>

                <?php if ($errorText): ?>
                    <div class="mt-4 p-3 rounded-xl border border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-400 text-sm font-medium flex items-start gap-2" role="alert">
                        <span class="mt-0.5"><i class="fas fa-exclamation-circle"></i></span><span><?php echo $e($errorText); ?></span>
                    </div>
                <?php endif; ?>
                <p id="client-error" class="hidden mt-4 p-3 rounded-xl border border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-400 text-sm font-medium" role="alert"></p>

                <form id="schedule-form" method="POST" action="<?php echo $base; ?>" class="mt-5 space-y-5" novalidate
                    data-opens="<?php echo $opens; ?>" data-closes="<?php echo $closes; ?>">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <input type="hidden" name="data" value="<?php echo $date; ?>">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="start_time" class="field-label"><?php echo $lang['agenda_start']; ?></label>
                            <input id="start_time" name="start_time" type="time" step="300" required value="<?php echo $e($old['start_time']); ?>" min="<?php echo $opens; ?>" max="<?php echo $closes; ?>" class="field-input tabular-nums">
                        </div>
                        <div>
                            <label for="end_time" class="field-label"><?php echo $lang['agenda_end']; ?></label>
                            <input id="end_time" name="end_time" type="time" step="300" required value="<?php echo $e($old['end_time']); ?>" min="<?php echo $opens; ?>" max="<?php echo $closes; ?>" class="field-input tabular-nums">
                        </div>
                    </div>

                    <div>
                        <span class="field-label"><?php echo $lang['agenda_duration']; ?></span>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ([60 => '1 h', 90 => '1h30', 120 => '2 h', 180 => '3 h'] as $min => $label): ?>
                                <button type="button" class="duration-btn" data-minutes="<?php echo $min; ?>"><?php echo $label; ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="sport_id" class="field-label"><?php echo $lang['agenda_sport']; ?> <span class="font-normal normal-case text-content-secondary">(<?php echo $lang['agenda_optional']; ?>)</span></label>
                            <select id="sport_id" name="sport_id" class="field-input">
                                <option value=""><?php echo $lang['agenda_sport_none']; ?></option>
                                <?php foreach ($sports as $s): ?>
                                    <option value="<?php echo (int)$s['id']; ?>" <?php echo (int)($old['sport_id'] ?? 0) === (int)$s['id'] ? 'selected' : ''; ?>><?php echo $e($s['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="renter_name" class="field-label"><?php echo $lang['agenda_renter']; ?> <span class="font-normal normal-case text-content-secondary">(<?php echo $lang['agenda_optional']; ?>)</span></label>
                            <input id="renter_name" name="renter_name" type="text" maxlength="100" value="<?php echo $e($old['renter_name'] ?? ''); ?>" placeholder="<?php echo $e($lang['agenda_renter_placeholder']); ?>" class="field-input">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-brand-500/20 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                            <span><i class="fas fa-plus"></i></span><?php echo $lang['agenda_submit']; ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </main>

    <style>
        .day-nav { width: 2.75rem; height: 2.75rem; display: inline-flex; align-items: center; justify-content: center; border-radius: 9999px; border: 1px solid color-mix(in srgb, var(--color-content-secondary) 25%, transparent); color: var(--color-content-primary); transition: background-color .15s; }
        .day-nav:hover { background: var(--color-surface-secondary); }
        .day-nav:focus-visible, .chip-link:focus-within, .duration-btn:focus-visible { outline: 2px solid var(--color-brand-500); outline-offset: 2px; }
        .chip-link { display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .85rem; border-radius: 9999px; font-weight: 600; color: var(--color-brand-600); background: color-mix(in srgb, var(--color-brand-500) 8%, transparent); }
        .dark .chip-link { color: var(--color-brand-300); }
        .timeline-seg { display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; white-space: nowrap; overflow: hidden; }
        .timeline-seg.is-free { background: color-mix(in srgb, var(--color-brand-500) 10%, transparent); }
        .timeline-seg.is-busy { background: var(--color-status-in-use); color: #fff; }
        .timeline-seg + .timeline-seg { border-left: 1px solid var(--color-surface-elevated); }
        .field-label { display: block; margin-bottom: .4rem; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--color-content-secondary); }
        .field-input { width: 100%; background: var(--color-surface-base); border: 1px solid color-mix(in srgb, var(--color-content-secondary) 30%, transparent); border-radius: .75rem; padding: .7rem .9rem; color: var(--color-content-primary); }
        .field-input:focus { outline: none; border-color: var(--color-brand-500); box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-brand-500) 20%, transparent); }
        .field-input.is-invalid { border-color: #dc2626; }
        .dark .field-input { color-scheme: dark; }
        .duration-btn { padding: .45rem .95rem; border-radius: 9999px; font-size: .85rem; font-weight: 600; border: 1px solid color-mix(in srgb, var(--color-content-secondary) 30%, transparent); color: var(--color-content-primary); transition: all .15s; }
        .duration-btn:hover { border-color: var(--color-brand-500); color: var(--color-brand-600); }
        .duration-btn.is-active { background: var(--color-brand-500); border-color: var(--color-brand-500); color: #fff; }
    </style>

    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
    <script>
        (function () {
            const base = <?php echo json_encode($base, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES); ?>;
            const msg = <?php echo json_encode([
                'confirm' => $lang['agenda_cancel_confirm'],
                'invalid' => $lang['agenda_error_invalid_time'],
                'order' => $lang['agenda_error_end_before_start'],
                'outside' => strtr($lang['agenda_error_outside_hours'], ['{opens}' => $opens ?? '', '{closes}' => $closes ?? '']),
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;

            // Escolher data: vai direto para o dia
            const picker = document.getElementById('date-picker');
            if (picker) {
                picker.closest('label').addEventListener('click', () => { try { picker.showPicker(); } catch (e) { picker.focus(); } });
                picker.addEventListener('change', () => { if (picker.value) location.href = base + '?data=' + picker.value; });
            }

            // Confirmar antes de cancelar
            document.querySelectorAll('.cancel-form').forEach(f => f.addEventListener('submit', e => { if (!confirm(msg.confirm)) e.preventDefault(); }));

            const form = document.getElementById('schedule-form');
            if (!form) return;
            const start = document.getElementById('start_time');
            const end = document.getElementById('end_time');
            const errorBox = document.getElementById('client-error');
            const opens = form.dataset.opens, closes = form.dataset.closes;
            const toMin = t => { const [h, m] = t.split(':').map(Number); return h * 60 + m; };
            const toTime = m => String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0');
            const durationBtns = form.querySelectorAll('.duration-btn');

            function markDuration() {
                const ok = /^\d{2}:\d{2}$/.test(start.value) && /^\d{2}:\d{2}$/.test(end.value);
                const d = ok ? toMin(end.value) - toMin(start.value) : -1;
                durationBtns.forEach(b => b.classList.toggle('is-active', Number(b.dataset.minutes) === d));
            }

            // Duração: preenche o fim a partir do início, sem passar do fechamento
            durationBtns.forEach(btn => btn.addEventListener('click', () => {
                if (!/^\d{2}:\d{2}$/.test(start.value)) { start.focus(); return; }
                end.value = toTime(Math.min(toMin(start.value) + Number(btn.dataset.minutes), toMin(closes)));
                validate(); markDuration();
            }));
            [start, end].forEach(i => i.addEventListener('change', () => { validate(); markDuration(); }));

            function showError(text) {
                errorBox.textContent = text;
                errorBox.classList.toggle('hidden', !text);
                start.classList.toggle('is-invalid', !!text);
                end.classList.toggle('is-invalid', !!text);
            }

            // Mesmas regras do servidor (VenueSchedule::validateMatch) que dá para conferir aqui
            function validate() {
                if (!/^\d{2}:\d{2}$/.test(start.value) || !/^\d{2}:\d{2}$/.test(end.value)) { showError(msg.invalid); return false; }
                if (toMin(end.value) <= toMin(start.value)) { showError(msg.order); return false; }
                if (toMin(start.value) < toMin(opens) || toMin(end.value) > toMin(closes)) { showError(msg.outside); return false; }
                showError('');
                return true;
            }

            form.addEventListener('submit', e => { if (!validate()) { e.preventDefault(); errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' }); } });
            markDuration();
            <?php if ($error): ?>document.getElementById('nova-partida').scrollIntoView({ block: 'start' });<?php endif; ?>
        })();
    </script>
</body>
</html>
