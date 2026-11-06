<?php
/**
 * Aba Estatísticas da quadra (D62), no estilo YouTube Analytics. Dados do VenueStatsController.
 * Grátis: cartões dos últimos 7 dias e convite para o Premium. Premium: 7, 28 ou 90 dias, gráfico
 * por dia (Chart.js), origem das visitas, mapa de calor da ocupação e a tabela com os números.
 * Cores do gráfico validadas para daltonismo e contraste, com tons próprios no tema escuro.
 */
$lang = require BASE_PATH . '/includes/i18n.php';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$pageTitle = $lang['stats_tab'] . ' · ' . $venue['name'] . ' · Kolae';
$venueId = (int)$venue['id'];
$base = BASE_URL . $routePrefix . '/quadras/' . $venueId;
$cur = $stats['current'];
$prev = $stats['previous'];
$num = fn($n) => number_format((float)$n, is_float($n) && floor($n) != $n ? 1 : 0, ',', '.');
$dateBr = fn($ymd) => date('d/m', strtotime($ymd));
$noData = $cur['views'] === 0 && $cur['whatsapp'] === 0 && $cur['social'] === 0 && $cur['matches'] === 0;

// Cartões: [chave, ícone, título, valor atual, valor anterior, sufixo, legenda]
$cards = [
    ['views', 'fa-eye', $lang['stats_views'], $cur['views'], $prev['views'], '', $lang['stats_views_hint']],
    ['visitors', 'fa-user', $lang['stats_visitors'], $cur['visitors'], $prev['visitors'], '', $lang['stats_visitors_hint']],
    ['whatsapp', 'fa-whatsapp fab', $lang['stats_whatsapp'], $cur['whatsapp'], $prev['whatsapp'], '', $lang['stats_whatsapp_hint']],
    ['contact_rate', 'fa-percent', $lang['stats_contact_rate'], $cur['contact_rate'], $prev['contact_rate'], '%', $lang['stats_contact_rate_hint']],
    ['social', 'fa-hashtag', $lang['stats_social'], $cur['social'] + $cur['share'], $prev['social'] + $prev['share'], '', $lang['stats_social_hint']],
    ['hours', 'fa-clock', $lang['stats_hours'], $cur['hours'], $prev['hours'], ' h', strtr($lang['stats_hours_hint'], ['{n}' => $cur['matches']])],
];
$sourceLabels = ['site' => $lang['stats_source_site'], 'app' => $lang['stats_source_app'], 'qr' => $lang['stats_source_qr']];
$sourceIcons = ['site' => 'fa-globe', 'app' => 'fa-mobile-screen', 'qr' => 'fa-qrcode'];
$sourceSum = array_sum($stats['sources']);
$sourceTotal = max(1, $sourceSum);
$weekdays = array_map(fn($k) => $lang[$k], ['weekday_short_0', 'weekday_short_1', 'weekday_short_2', 'weekday_short_3', 'weekday_short_4', 'weekday_short_5', 'weekday_short_6']);
$heatMax = max(1, max(array_map('max', $stats['heatmap'])));
$hoursRange = range(6, 23);
?>
<!DOCTYPE html>
<html lang="<?php echo $e($_SESSION['idioma'] ?? 'pt-br'); ?>">
<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>
<body class="bg-surface-base text-content-primary font-sans antialiased">
    <?php include BASE_PATH . '/app/views/partials/owner_header.php'; ?>

    <main class="container mx-auto px-4 py-6 md:py-10 max-w-4xl pb-28 md:pb-10">
        <?php include BASE_PATH . '/app/views/partials/venue_top.php'; ?>

        <!-- Período -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
            <div>
                <h2 class="text-xl font-bold"><?php echo $e($lang['stats_title']); ?></h2>
                <p class="text-sm text-content-secondary mt-0.5" id="stats-range"><?php echo $e(strtr($lang['stats_range'], ['{from}' => $dateBr($stats['from']), '{to}' => $dateBr($stats['to'])])); ?></p>
            </div>
            <nav id="stats-periods" class="inline-flex p-1 rounded-xl bg-surface-secondary border border-content-secondary/10 self-start" aria-label="<?php echo $e($lang['stats_period']); ?>">
                <?php foreach (\App\Core\VenueStats::PERIODS as $p):
                    $on = $p === $days;
                    $locked = !$premium && $p !== 7; ?>
                    <a href="<?php echo $locked ? BASE_URL . '/dashboard/perfil/plano' : $base . '/estatisticas?periodo=' . $p; ?>" data-period="<?php echo $p; ?>"
                        <?php echo $on ? 'aria-current="page"' : ''; ?> <?php echo $locked ? 'title="' . $e($lang['stats_premium_only']) . '"' : ''; ?>
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition-colors whitespace-nowrap <?php echo $on ? 'bg-surface-elevated shadow-sm text-brand-600 dark:text-brand-400' : 'text-content-secondary hover:text-content-primary'; ?>">
                        <?php echo $e(strtr($lang['stats_days'], ['{n}' => $p])); ?><?php if ($locked): ?> <i class="fas fa-crown text-[10px] text-amber-500 ml-0.5" aria-hidden="true"></i><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <?php if ($noData): ?>
            <div class="mb-5 p-4 rounded-2xl border border-brand-500/20 bg-brand-500/5 text-sm flex items-start gap-3" id="stats-empty">
                <i class="fas fa-seedling mt-0.5 text-brand-600 dark:text-brand-400" aria-hidden="true"></i>
                <p><?php echo $e($lang['stats_empty']); ?> <a href="<?php echo $base; ?>/visao-geral" class="font-semibold text-brand-600 dark:text-brand-400 hover:underline"><?php echo $e($lang['stats_empty_link']); ?></a></p>
            </div>
        <?php endif; ?>

        <!-- Cartões -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            <?php foreach ($cards as [$key, $icon, $title, $value, $before, $suffix, $hint]):
                $delta = \App\Core\VenueStats::delta($value, $before); ?>
                <div class="bg-surface-elevated border border-content-secondary/10 rounded-2xl p-4 sm:p-5 shadow-sm" data-card="<?php echo $key; ?>">
                    <p class="text-xs sm:text-sm font-medium text-content-secondary flex items-center gap-2">
                        <span class="w-4 text-center" aria-hidden="true"><i class="<?php echo str_contains($icon, ' ') ? $icon : 'fas ' . $icon; ?>"></i></span><?php echo $e($title); ?>
                    </p>
                    <p class="mt-2 text-2xl sm:text-3xl font-bold tabular-nums" data-value><?php echo $e($num($value) . $suffix); ?></p>
                    <p class="mt-1 text-xs flex flex-wrap items-center gap-x-1.5">
                        <?php if ($delta === null): ?>
                            <span class="text-content-secondary"><?php echo $e($lang['stats_no_compare']); ?></span>
                        <?php else: ?>
                            <span class="font-semibold <?php echo $delta > 0 ? 'text-emerald-700 dark:text-emerald-400' : ($delta < 0 ? 'text-red-700 dark:text-red-400' : 'text-content-secondary'); ?>" data-delta="<?php echo (int)$delta; ?>">
                                <i class="fas <?php echo $delta > 0 ? 'fa-arrow-up' : ($delta < 0 ? 'fa-arrow-down' : 'fa-minus'); ?>" aria-hidden="true"></i>
                                <?php echo $e(($delta > 0 ? '+' : '') . (int)$delta . '%'); ?>
                            </span>
                            <span class="text-content-secondary"><?php echo $e(strtr($lang['stats_vs_previous'], ['{n}' => $days])); ?></span>
                        <?php endif; ?>
                    </p>
                    <p class="mt-2 text-[11px] leading-snug text-content-secondary hidden sm:block"><?php echo $e($hint); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($premium): ?>
            <!-- Por dia -->
            <section class="mt-5 bg-surface-elevated border border-content-secondary/10 rounded-2xl p-5 md:p-6 shadow-sm" id="stats-daily">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-bold"><?php echo $e($lang['stats_daily_title']); ?></h3>
                    <ul class="flex items-center gap-4 text-xs text-content-secondary" aria-hidden="true">
                        <li class="flex items-center gap-1.5"><span class="w-3 h-[3px] rounded-full bg-[#0069CC] dark:bg-[#3B8EF0]"></span><?php echo $e($lang['stats_views']); ?></li>
                        <li class="flex items-center gap-1.5"><span class="w-3 h-[3px] rounded-full bg-[#16A34A] dark:bg-[#22A355]"></span><?php echo $e($lang['stats_whatsapp']); ?></li>
                    </ul>
                </div>
                <div class="mt-4 h-64 relative"><canvas id="daily-chart" role="img" aria-label="<?php echo $e($lang['stats_daily_title']); ?>"></canvas></div>
                <details class="mt-3 text-sm">
                    <summary class="cursor-pointer text-content-secondary hover:text-content-primary font-medium"><?php echo $e($lang['stats_table_toggle']); ?></summary>
                    <div class="mt-3 max-h-72 overflow-y-auto">
                        <table class="w-full text-left tabular-nums">
                            <thead class="text-xs text-content-secondary sticky top-0 bg-surface-elevated"><tr>
                                <th scope="col" class="py-2 font-semibold"><?php echo $e($lang['stats_table_day']); ?></th>
                                <th scope="col" class="py-2 font-semibold text-right"><?php echo $e($lang['stats_views']); ?></th>
                                <th scope="col" class="py-2 font-semibold text-right"><?php echo $e($lang['stats_whatsapp']); ?></th>
                            </tr></thead>
                            <tbody>
                                <?php foreach (array_reverse($stats['daily']) as $d): ?>
                                    <tr class="border-t border-content-secondary/10"><td class="py-1.5"><?php echo $e(date('d/m/Y', strtotime($d['date']))); ?></td><td class="py-1.5 text-right"><?php echo (int)$d['views']; ?></td><td class="py-1.5 text-right"><?php echo (int)$d['contacts']; ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            </section>

            <div class="mt-5 grid lg:grid-cols-5 gap-5">
                <!-- Origem das visitas -->
                <section class="lg:col-span-2 bg-surface-elevated border border-content-secondary/10 rounded-2xl p-5 md:p-6 shadow-sm" id="stats-sources">
                    <h3 class="font-bold"><?php echo $e($lang['stats_sources_title']); ?></h3>
                    <ul class="mt-4 space-y-4">
                        <?php foreach ($stats['sources'] as $src => $n):
                            $pct = round($n / $sourceTotal * 100); ?>
                            <li data-source="<?php echo $src; ?>">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center gap-2"><span class="w-4 text-center text-content-secondary" aria-hidden="true"><i class="fas <?php echo $sourceIcons[$src]; ?>"></i></span><?php echo $e($sourceLabels[$src]); ?></span>
                                    <span class="tabular-nums font-semibold"><?php echo (int)$n; ?> <span class="font-normal text-content-secondary">· <?php echo $sourceSum ? $pct : 0; ?>%</span></span>
                                </div>
                                <div class="mt-1.5 h-2 rounded-full bg-surface-secondary overflow-hidden"><div class="h-full rounded-full bg-[#0069CC] dark:bg-[#3B8EF0]" style="width: <?php echo $sourceSum ? $pct : 0; ?>%"></div></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="mt-4 text-xs text-content-secondary"><?php echo $e($lang['stats_sources_hint']); ?></p>
                </section>

                <!-- Mapa de calor da ocupação -->
                <section class="lg:col-span-3 bg-surface-elevated border border-content-secondary/10 rounded-2xl p-5 md:p-6 shadow-sm" id="stats-heatmap">
                    <h3 class="font-bold"><?php echo $e($lang['stats_heatmap_title']); ?></h3>
                    <p class="text-xs text-content-secondary mt-0.5"><?php echo $e($lang['stats_heatmap_hint']); ?></p>
                    <div class="mt-4 overflow-x-auto">
                        <table class="text-[10px] text-content-secondary border-separate" style="border-spacing: 2px">
                            <thead><tr><th scope="col"></th><?php foreach ($hoursRange as $h): ?><th scope="col" class="font-normal w-5 text-center"><?php echo $h % 3 === 0 ? $h . 'h' : ''; ?></th><?php endforeach; ?></tr></thead>
                            <tbody>
                                <?php foreach ([1, 2, 3, 4, 5, 6, 0] as $wd): ?>
                                    <tr>
                                        <th scope="row" class="font-medium pr-1.5 text-left"><?php echo $e($weekdays[$wd]); ?></th>
                                        <?php foreach ($hoursRange as $h):
                                            $min = $stats['heatmap'][$wd][$h];
                                            $level = $min === 0 ? 0 : min(4, (int)ceil($min / $heatMax * 4)); ?>
                                            <td class="w-5 h-5 rounded-[3px] heat-<?php echo $level; ?>" title="<?php echo $e($weekdays[$wd] . ' ' . $h . 'h: ' . strtr($lang['stats_heatmap_cell'], ['{n}' => $min])); ?>"><span class="sr-only"><?php echo (int)$min; ?></span></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-[10px] text-content-secondary" aria-hidden="true">
                        <?php echo $e($lang['stats_heatmap_less']); ?>
                        <?php for ($l = 0; $l <= 4; $l++): ?><span class="w-3 h-3 rounded-[3px] heat-<?php echo $l; ?>"></span><?php endfor; ?>
                        <?php echo $e($lang['stats_heatmap_more']); ?>
                    </div>
                </section>
            </div>
        <?php else: ?>
            <!-- Grátis: convite para o Premium -->
            <section class="mt-5 relative overflow-hidden rounded-2xl border border-content-secondary/10 bg-surface-elevated" id="stats-locked">
                <div class="p-5 md:p-6 blur-[3px] opacity-50 select-none pointer-events-none" aria-hidden="true">
                    <div class="h-4 w-40 rounded bg-content-secondary/20"></div>
                    <svg viewBox="0 0 400 120" class="mt-4 w-full h-40" preserveAspectRatio="none"><polyline fill="none" stroke="#0069CC" stroke-width="3" points="0,90 40,80 80,85 120,60 160,70 200,45 240,55 280,30 320,40 360,20 400,28"/><polyline fill="none" stroke="#16A34A" stroke-width="3" points="0,110 40,105 80,108 120,98 160,100 200,92 240,95 280,85 320,88 360,80 400,82"/></svg>
                </div>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-6 bg-surface-elevated/40">
                    <span class="w-12 h-12 rounded-full bg-amber-400/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl" aria-hidden="true"><i class="fas fa-crown"></i></span>
                    <h3 class="mt-3 font-bold text-lg"><?php echo $e($lang['stats_locked_title']); ?></h3>
                    <p class="mt-1 text-sm text-content-secondary max-w-md"><?php echo $e($lang['stats_locked_text']); ?></p>
                    <a href="<?php echo BASE_URL; ?>/dashboard/perfil/plano" class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold transition-colors"><?php echo $e($lang['stats_locked_btn']); ?></a>
                </div>
            </section>
        <?php endif; ?>

        <p class="mt-5 text-xs text-content-secondary flex items-start gap-2"><i class="fas fa-shield-halved mt-0.5" aria-hidden="true"></i><?php echo $e($lang['stats_privacy']); ?></p>
    </main>

    <?php if ($premium): ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
            (function () {
                const data = <?php echo json_encode(['labels' => array_map(fn($d) => date('d/m', strtotime($d['date'])), $stats['daily']), 'views' => array_column($stats['daily'], 'views'), 'contacts' => array_column($stats['daily'], 'contacts'), 'names' => [$lang['stats_views'], $lang['stats_whatsapp']]], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
                const canvas = document.getElementById('daily-chart');
                if (!canvas || !window.Chart) return;
                // Tons validados (daltonismo e contraste) para cada tema
                const palette = dark => dark
                    ? { views: '#3B8EF0', contacts: '#22A355', grid: 'rgba(148,163,184,0.15)', text: '#94a3b8' }
                    : { views: '#0069CC', contacts: '#16A34A', grid: 'rgba(100,116,139,0.15)', text: '#64748b' };
                let chart;
                let drawnDark = null;
                const draw = () => {
                    const dark = document.documentElement.classList.contains('dark');
                    if (dark === drawnDark) return; // outras classes do <html> mudam ao carregar: só o tema importa
                    drawnDark = dark;
                    const c = palette(dark);
                    if (chart) chart.destroy();
                    chart = new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [
                                { label: data.names[0], data: data.views, borderColor: c.views, backgroundColor: c.views, borderWidth: 2, cubicInterpolationMode: 'monotone', pointRadius: data.labels.length > 31 ? 0 : 3, pointHoverRadius: 5 },
                                { label: data.names[1], data: data.contacts, borderColor: c.contacts, backgroundColor: c.contacts, borderWidth: 2, cubicInterpolationMode: 'monotone', pointRadius: data.labels.length > 31 ? 0 : 3, pointHoverRadius: 5 },
                            ],
                        },
                        options: {
                            maintainAspectRatio: false,
                            animation: false, // números aparecem prontos (sem os pontos deslizando da esquerda)
                            interaction: { mode: 'index', intersect: false },
                            plugins: { legend: { display: false }, tooltip: { padding: 10, boxPadding: 4 } },
                            scales: {
                                x: { grid: { display: false }, ticks: { color: c.text, maxTicksLimit: 8, maxRotation: 0 } },
                                y: { beginAtZero: true, grid: { color: c.grid }, border: { display: false }, ticks: { color: c.text, precision: 0, maxTicksLimit: 5 } },
                            },
                        },
                    });
                };
                draw();
                // Redesenha ao trocar o tema (botão do cabeçalho ou do sistema)
                new MutationObserver(draw).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            })();
        </script>
    <?php endif; ?>
    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
</body>
</html>
