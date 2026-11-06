<?php
/**
 * Aba Premium da quadra (D61): plano atual, Grátis x Premium e o pedido de ativação.
 * Para o admin (rota /admin), também ativar com validade e desativar.
 * Dados vindos do VenuePlanController.
 */
$lang = require BASE_PATH . '/includes/i18n.php';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$pageTitle = $lang['plan_tab'] . ' · ' . $venue['name'] . ' · Kolae';
$toastContext = 'plan';
$venueId = (int)$venue['id'];
$base = BASE_URL . $routePrefix . '/quadras/' . $venueId . '/plano';
$dateBr = fn(?string $ymd) => $ymd ? date('d/m/Y', strtotime($ymd)) : '';

// Linhas da comparação: [ícone, texto, Grátis tem?, Premium tem?, detalhe do Premium]
$rows = [
    ['fa-map-location-dot', $lang['plan_row_map'], true, true, null],
    ['fa-inbox', $lang['plan_row_requests'], true, true, null],
    ['fa-chart-line', $lang['plan_row_stats'], $lang['plan_row_stats_free'], true, $lang['plan_row_stats_premium']],
    ['fa-bullhorn', $lang['plan_row_ads'], false, true, $lang['plan_row_ads_premium']],
];
$stateInfo = [
    'free' => ['fa-seedling', 'text-content-secondary bg-content-secondary/10', $lang['plan_state_free'], $lang['plan_state_free_text']],
    'requested' => ['fa-hourglass-half', 'text-amber-700 dark:text-amber-300 bg-amber-500/10', $lang['plan_state_requested'], strtr($lang['plan_state_requested_text'], ['{date}' => $dateBr($venue['plan_requested_at'] ?? null)])],
    'premium' => ['fa-crown', 'text-brand-600 dark:text-brand-400 bg-brand-500/10', $lang['plan_state_premium'], empty($venue['plan_until']) ? $lang['plan_state_premium_nolimit'] : strtr($lang['plan_state_premium_text'], ['{date}' => $dateBr($venue['plan_until'])])],
    'expired' => ['fa-clock-rotate-left', 'text-content-secondary bg-content-secondary/10', $lang['plan_state_expired'], strtr($lang['plan_state_expired_text'], ['{date}' => $dateBr($venue['plan_until'] ?? null)])],
][$planState];
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

        <!-- Plano atual -->
        <section class="bg-surface-elevated border border-content-secondary/10 rounded-2xl p-5 md:p-7 shadow-sm flex flex-col sm:flex-row sm:items-center gap-4" id="plan-state" data-state="<?php echo $e($planState); ?>">
            <span class="w-14 h-14 shrink-0 rounded-2xl flex items-center justify-center text-2xl <?php echo $stateInfo[1]; ?>" aria-hidden="true"><i class="fas <?php echo $stateInfo[0]; ?>"></i></span>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-content-secondary"><?php echo $e($lang['plan_current']); ?></p>
                <h2 class="text-xl font-bold mt-0.5"><?php echo $e($stateInfo[2]); ?></h2>
                <p class="text-sm text-content-secondary mt-1"><?php echo $e($stateInfo[3]); ?></p>
            </div>
            <?php if (!$isAdmin && in_array($planState, ['free', 'expired'], true)): ?>
                <form method="POST" action="<?php echo $base; ?>/pedir" class="shrink-0">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <button type="submit" id="plan-request" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold shadow-lg shadow-brand-600/20 transition-colors">
                        <i class="fas fa-crown text-sm" aria-hidden="true"></i><?php echo $e($lang['plan_request_btn']); ?>
                    </button>
                </form>
            <?php endif; ?>
        </section>

        <!-- Grátis x Premium -->
        <section class="mt-6 bg-surface-elevated border border-content-secondary/10 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-5 md:p-7 pb-2 md:pb-3">
                <h2 class="text-lg font-bold"><?php echo $e($lang['plan_compare_title']); ?></h2>
                <p class="text-sm text-content-secondary mt-1"><?php echo $e($lang['plan_compare_text']); ?></p>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-content-secondary">
                        <th scope="col" class="px-5 md:px-7 py-3 font-semibold"><span class="sr-only"><?php echo $e($lang['plan_compare_feature']); ?></span></th>
                        <th scope="col" class="px-3 py-3 font-semibold text-center w-24 sm:w-32"><?php echo $e($lang['plan_free']); ?></th>
                        <th scope="col" class="px-3 md:pr-7 py-3 font-semibold text-center w-28 sm:w-44 text-brand-600 dark:text-brand-400"><i class="fas fa-crown mr-1 text-xs" aria-hidden="true"></i><?php echo $e($lang['plan_premium']); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as [$icon, $label, $free, $premium, $premiumNote]): ?>
                        <tr class="border-t border-content-secondary/10">
                            <th scope="row" class="px-5 md:px-7 py-3.5 font-medium text-left">
                                <span class="flex items-center gap-3"><span class="w-5 text-center text-content-secondary" aria-hidden="true"><i class="fas <?php echo $icon; ?>"></i></span><?php echo $e($label); ?></span>
                            </th>
                            <td class="px-3 py-3.5 text-center text-content-secondary">
                                <?php if ($free === true): ?><i class="fas fa-check text-emerald-600 dark:text-emerald-400" aria-label="<?php echo $e($lang['plan_yes']); ?>"></i>
                                <?php elseif ($free === false): ?><span aria-label="<?php echo $e($lang['plan_no']); ?>">—</span>
                                <?php else: ?><span class="text-xs"><?php echo $e($free); ?></span><?php endif; ?>
                            </td>
                            <td class="px-3 md:pr-7 py-3.5 text-center bg-brand-500/5">
                                <i class="fas fa-check text-brand-600 dark:text-brand-400" aria-label="<?php echo $e($lang['plan_yes']); ?>"></i>
                                <?php if ($premiumNote): ?><span class="block text-xs text-content-secondary mt-0.5"><?php echo $e($premiumNote); ?></span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="px-5 md:px-7 py-4 text-xs text-content-secondary border-t border-content-secondary/10 flex items-start gap-2">
                <i class="fas fa-circle-info mt-0.5" aria-hidden="true"></i><span><?php echo $e($lang['plan_how_text']); ?></span>
            </p>
        </section>

        <?php if ($isAdmin): ?>
            <!-- Admin: ativar e desativar (D61) -->
            <section class="mt-6 bg-surface-elevated border-2 border-dashed border-brand-500/30 rounded-2xl p-5 md:p-7" id="plan-admin">
                <h2 class="text-lg font-bold flex items-center gap-2"><i class="fas fa-user-shield text-brand-600 dark:text-brand-400" aria-hidden="true"></i><?php echo $e($lang['plan_admin_title']); ?></h2>
                <form method="POST" action="<?php echo $base; ?>/ativar" class="mt-4 flex flex-col sm:flex-row sm:items-end gap-4">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <div>
                        <label for="plan_until" class="block text-sm font-medium mb-1.5"><?php echo $e($lang['plan_admin_until']); ?></label>
                        <input type="date" id="plan_until" name="plan_until" value="<?php echo $e($planState === 'premium' && !empty($venue['plan_until']) ? $venue['plan_until'] : $defaultUntil); ?>" min="<?php echo date('Y-m-d'); ?>"
                            class="h-11 rounded-xl border border-content-secondary/25 bg-surface-base px-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15">
                    </div>
                    <label class="flex items-center gap-2 text-sm h-11 cursor-pointer">
                        <input type="checkbox" name="no_limit" value="1" class="w-[18px] h-[18px] accent-brand-500" <?php echo $planState === 'premium' && empty($venue['plan_until']) ? 'checked' : ''; ?>>
                        <?php echo $e($lang['plan_admin_nolimit']); ?>
                    </label>
                    <button type="submit" id="plan-activate" class="h-11 px-5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold transition-colors">
                        <?php echo $e($planState === 'premium' ? $lang['plan_admin_update'] : $lang['plan_admin_activate']); ?>
                    </button>
                </form>
                <?php if ($planState !== 'free'): ?>
                    <form method="POST" action="<?php echo $base; ?>/desativar" class="mt-4 pt-4 border-t border-content-secondary/10"
                        data-confirm="<?php echo $e($lang['plan_admin_deactivate_confirm']); ?>" data-confirm-danger data-confirm-ok="<?php echo $e($lang['plan_admin_deactivate']); ?>">
                        <?php echo \App\Core\Csrf::field(); ?>
                        <button type="submit" id="plan-deactivate" class="text-sm font-semibold text-red-600 dark:text-red-400 hover:underline">
                            <?php echo $e($planState === 'requested' ? $lang['plan_admin_dismiss'] : $lang['plan_admin_deactivate']); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <?php // Menu do usuário e botão de tema do cabeçalho (initNavbar, initTheme) ?>
    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
</body>
</html>
