<?php
/**
 * Aba Horário da quadra (D35): 7 dias, cada um aberto (abre/fecha) ou fechado (D21, D22).
 * Dados vindos do VenueHoursController.
 */
use App\Controllers\VenueHoursController;

$lang = require BASE_PATH . '/includes/i18n.php';
$pageTitle = $lang['venue_tab_hours'] . ' · ' . $venue['name'] . ' · Kolae';
$action = BASE_URL . $routePrefix . '/quadras/' . (int)$venue['id'] . '/horario';
$errorMessages = ['invalid_time' => $lang['hours_error_invalid'], 'closes_before_opens' => $lang['hours_error_order']];
$flash = $_GET['status'] ?? null;

// "Seg a Sex", "Sáb"
$dayLabel = function (array $days) use ($lang): string {
    $first = $lang['weekday_short_' . $days[0]];
    if (count($days) === 1) return $first;
    $last = $lang['weekday_short_' . end($days)];
    return count($days) === 2 ? "$first, $last" : "$first {$lang['weekday_range_sep']} $last";
};
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['idioma'] ?? 'pt-br'); ?>">
<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>
<body class="bg-surface-base text-content-primary font-poppins">
    <?php include BASE_PATH . '/app/views/partials/owner_header.php'; ?>

    <main class="container mx-auto px-4 py-6 md:py-10 max-w-3xl pb-28 md:pb-10">
        <?php include BASE_PATH . '/app/views/partials/venue_top.php'; ?>

        <?php if ($flash === 'saved'): ?>
            <div class="mb-6 p-4 rounded-xl border border-green-500/30 bg-green-500/10 text-green-700 dark:text-green-400 flex items-start gap-3" role="status">
                <span class="mt-0.5"><i class="fas fa-check-circle"></i></span><p class="text-sm font-medium"><?php echo $lang['hours_saved']; ?></p>
            </div>
        <?php elseif ($flash === 'error'): ?>
            <div class="mb-6 p-4 rounded-xl border border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-400 flex items-start gap-3" role="alert">
                <span class="mt-0.5"><i class="fas fa-times-circle"></i></span><p class="text-sm font-medium"><?php echo $lang['hours_save_error']; ?></p>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="mb-6 p-4 rounded-xl border border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-400 flex items-start gap-3" role="alert">
                <span class="mt-0.5"><i class="fas fa-exclamation-circle"></i></span><p class="text-sm font-medium"><?php echo $lang['hours_errors_banner']; ?></p>
            </div>
        <?php endif; ?>

        <?php if (!$hasHours && !$errors): ?>
            <div class="mb-6 p-4 rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300 flex items-start gap-3">
                <span class="mt-0.5"><i class="fas fa-clock"></i></span>
                <div>
                    <p class="font-semibold"><?php echo $lang['hours_none_title']; ?></p>
                    <p class="text-sm mt-0.5"><?php echo $lang['hours_none_text']; ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($outsideMatches): ?>
            <div class="mb-6 p-4 rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300">
                <p class="font-semibold flex items-center gap-2"><span><i class="fas fa-triangle-exclamation"></i></span><?php echo $lang['hours_outside_title']; ?></p>
                <p class="text-sm mt-1"><?php echo $lang['hours_outside_text']; ?></p>
                <ul class="mt-3 space-y-1 text-sm">
                    <?php foreach ($outsideMatches as $m):
                        $start = new DateTimeImmutable($m['start_time']);
                        $end = new DateTimeImmutable($m['end_time']); ?>
                        <li class="flex flex-wrap gap-x-2">
                            <span class="font-semibold"><?php echo $lang['weekday_short_' . $start->format('w')] . ' ' . $start->format('d/m'); ?></span>
                            <span><?php echo $start->format('H:i') . '–' . $end->format('H:i'); ?></span>
                            <span class="opacity-80">· <?php echo htmlspecialchars($m['renter_name'] ?: $lang['hours_renter_none']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="hours-form" method="POST" action="<?php echo $action; ?>" novalidate>
            <section class="bg-surface-elevated border border-content-secondary/10 rounded-2xl shadow-sm">
                <div class="p-5 md:p-6 border-b border-content-secondary/10">
                    <h2 class="text-lg font-bold text-content-primary"><?php echo $lang['hours_title']; ?></h2>
                    <p class="text-sm text-content-secondary mt-1"><?php echo $lang['hours_subtitle']; ?></p>
                </div>

                <!-- Preencher rápido -->
                <div class="p-5 md:p-6 border-b border-content-secondary/10 bg-surface-base/60">
                    <p class="text-sm font-semibold text-content-primary"><?php echo $lang['hours_quick_title']; ?></p>
                    <p class="text-xs text-content-secondary mt-0.5"><?php echo $lang['hours_quick_text']; ?></p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <label class="sr-only" for="quick-opens"><?php echo $lang['hours_opens_at']; ?></label>
                        <input id="quick-opens" type="time" value="08:00" step="300" class="time-input">
                        <span class="text-content-secondary">–</span>
                        <label class="sr-only" for="quick-closes"><?php echo $lang['hours_closes_at']; ?></label>
                        <input id="quick-closes" type="time" value="22:00" step="300" class="time-input">
                        <div class="flex flex-wrap gap-2 w-full sm:w-auto sm:ml-2">
                            <button type="button" class="quick-btn" data-days="1,2,3,4,5"><?php echo $lang['hours_quick_weekdays']; ?></button>
                            <button type="button" class="quick-btn" data-days="6,0"><?php echo $lang['hours_quick_weekend']; ?></button>
                            <button type="button" class="quick-btn" data-days="1,2,3,4,5,6,0"><?php echo $lang['hours_quick_all']; ?></button>
                        </div>
                    </div>
                </div>

                <!-- Dias da semana -->
                <ul class="divide-y divide-content-secondary/10">
                    <?php foreach (VenueHoursController::DISPLAY_ORDER as $w):
                        $day = $form[$w];
                        $error = $errors[$w] ?? null; ?>
                        <li class="day-row px-5 md:px-6 py-4 flex flex-wrap items-center gap-x-4 gap-y-3 <?php echo $error ? 'bg-red-500/5' : ''; ?>" data-weekday="<?php echo $w; ?>">
                            <label class="flex items-center gap-3 cursor-pointer select-none min-w-[11rem]">
                                <input type="checkbox" name="hours[<?php echo $w; ?>][open]" value="1" class="day-toggle peer sr-only" <?php echo $day['open'] ? 'checked' : ''; ?>>
                                <span class="relative w-11 h-6 shrink-0 rounded-full bg-content-secondary/30 transition-colors peer-checked:bg-brand-500 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-5"></span>
                                <span class="font-semibold text-content-primary"><?php echo $lang['weekday_' . $w]; ?></span>
                            </label>

                            <div class="day-times flex items-center gap-2 ml-auto <?php echo $day['open'] ? '' : 'hidden'; ?>">
                                <label class="sr-only" for="opens-<?php echo $w; ?>"><?php echo $lang['hours_opens_at'] . ' · ' . $lang['weekday_' . $w]; ?></label>
                                <input id="opens-<?php echo $w; ?>" type="time" step="300" name="hours[<?php echo $w; ?>][opens_at]" value="<?php echo htmlspecialchars($day['opens_at']); ?>" class="time-input opens">
                                <span class="text-content-secondary">–</span>
                                <label class="sr-only" for="closes-<?php echo $w; ?>"><?php echo $lang['hours_closes_at'] . ' · ' . $lang['weekday_' . $w]; ?></label>
                                <input id="closes-<?php echo $w; ?>" type="time" step="300" name="hours[<?php echo $w; ?>][closes_at]" value="<?php echo htmlspecialchars($day['closes_at']); ?>" class="time-input closes">
                            </div>
                            <span class="day-closed ml-auto text-sm text-content-secondary <?php echo $day['open'] ? 'hidden' : ''; ?>"><?php echo $lang['hours_closed']; ?></span>

                            <p class="day-error w-full text-sm text-red-600 dark:text-red-400 <?php echo $error ? '' : 'hidden'; ?>" role="alert"><?php echo $error ? $errorMessages[$error] : ''; ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <p class="px-5 md:px-6 py-3 text-xs text-content-secondary border-t border-content-secondary/10 flex items-center gap-2">
                    <span><i class="fas fa-moon"></i></span><?php echo $lang['hours_midnight_hint']; ?>
                </p>
            </section>

            <!-- Como aparece no app -->
            <section class="mt-6 bg-surface-elevated border border-content-secondary/10 rounded-2xl shadow-sm p-5 md:p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-content-secondary"><?php echo $lang['hours_summary_title']; ?></h2>
                <?php if (!$hasHours): ?>
                    <p class="mt-2 text-content-secondary"><?php echo $lang['hours_summary_empty']; ?></p>
                <?php else: ?>
                    <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
                        <?php foreach ($summary as $group): ?>
                            <dt class="font-semibold text-content-primary"><?php echo htmlspecialchars($dayLabel($group['days'])); ?></dt>
                            <dd class="<?php echo $group['opens_at'] ? 'text-content-primary' : 'text-content-secondary'; ?>">
                                <?php echo $group['opens_at'] ? $group['opens_at'] . '–' . $group['closes_at'] : $lang['hours_closed']; ?>
                            </dd>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>
            </section>

            <!-- Salvar: fixo embaixo no celular -->
            <div class="fixed md:static bottom-0 inset-x-0 z-30 p-4 md:p-0 md:mt-6 bg-surface-elevated/95 md:bg-transparent backdrop-blur md:backdrop-blur-none border-t md:border-0 border-content-secondary/10 flex justify-end">
                <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center gap-2 bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-brand-500/20 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                    <span><i class="fas fa-check"></i></span><?php echo $lang['hours_save']; ?>
                </button>
            </div>
        </form>
    </main>

    <style>
        .time-input {
            background: var(--color-surface-base);
            border: 1px solid color-mix(in srgb, var(--color-content-secondary) 30%, transparent);
            border-radius: 0.75rem;
            padding: 0.5rem 0.75rem;
            color: var(--color-content-primary);
            font-variant-numeric: tabular-nums;
            min-width: 7rem;
        }
        .time-input:focus { outline: none; border-color: var(--color-brand-500); box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-brand-500) 20%, transparent); }
        .time-input.is-invalid { border-color: #dc2626; }
        .dark .time-input { color-scheme: dark; }
        .quick-btn {
            padding: 0.5rem 0.9rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid color-mix(in srgb, var(--color-brand-500) 40%, transparent);
            color: var(--color-brand-600);
            background: color-mix(in srgb, var(--color-brand-500) 8%, transparent);
            transition: background-color .15s;
        }
        .dark .quick-btn { color: var(--color-brand-300); }
        .quick-btn:hover { background: color-mix(in srgb, var(--color-brand-500) 16%, transparent); }
        .quick-btn:focus-visible { outline: 2px solid var(--color-brand-500); outline-offset: 2px; }
    </style>

    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
    <script>
        (function () {
            const form = document.getElementById('hours-form');
            const rows = Array.from(form.querySelectorAll('.day-row'));
            const msgOrder = <?php echo json_encode($lang['hours_error_order'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
            const msgInvalid = <?php echo json_encode($lang['hours_error_invalid'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
            let dirty = false;

            const parts = row => ({
                toggle: row.querySelector('.day-toggle'),
                times: row.querySelector('.day-times'),
                closed: row.querySelector('.day-closed'),
                opens: row.querySelector('.opens'),
                closes: row.querySelector('.closes'),
                error: row.querySelector('.day-error'),
            });

            function render(row) {
                const p = parts(row);
                p.times.classList.toggle('hidden', !p.toggle.checked);
                p.closed.classList.toggle('hidden', p.toggle.checked);
                if (!p.toggle.checked) setError(row, '');
            }

            function setError(row, msg) {
                const p = parts(row);
                p.error.textContent = msg;
                p.error.classList.toggle('hidden', !msg);
                row.classList.toggle('bg-red-500/5', !!msg);
                p.opens.classList.toggle('is-invalid', !!msg);
                p.closes.classList.toggle('is-invalid', !!msg);
            }

            // Mesma regra do servidor (VenueSchedule::validateHours): fecha depois de abrir, no mesmo dia
            function validate(row) {
                const p = parts(row);
                if (!p.toggle.checked) return true;
                if (!/^\d{2}:\d{2}$/.test(p.opens.value) || !/^\d{2}:\d{2}$/.test(p.closes.value)) {
                    setError(row, msgInvalid);
                    return false;
                }
                if (p.closes.value <= p.opens.value) {
                    setError(row, msgOrder);
                    return false;
                }
                setError(row, '');
                return true;
            }

            rows.forEach(row => {
                const p = parts(row);
                p.toggle.addEventListener('change', () => { render(row); dirty = true; });
                [p.opens, p.closes].forEach(input => input.addEventListener('change', () => { validate(row); dirty = true; }));
            });

            // Preencher rápido: aplica o horário e abre os dias escolhidos
            form.querySelectorAll('.quick-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const opens = document.getElementById('quick-opens').value;
                    const closes = document.getElementById('quick-closes').value;
                    btn.dataset.days.split(',').forEach(w => {
                        const row = form.querySelector('.day-row[data-weekday="' + w + '"]');
                        const p = parts(row);
                        p.toggle.checked = true;
                        p.opens.value = opens;
                        p.closes.value = closes;
                        render(row);
                        validate(row);
                    });
                    dirty = true;
                });
            });

            form.addEventListener('submit', e => {
                const invalid = rows.filter(row => !validate(row));
                if (invalid.length) {
                    e.preventDefault();
                    invalid[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
                dirty = false;
            });

            // Avisa antes de sair com mudanças não salvas
            window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
        })();
    </script>
</body>
</html>
