<?php
/**
 * Configurações > Kolae Premium (D61): o plano do dono, que vale para todas as quadras dele.
 * Mostra o plano atual, a comparação Grátis x Premium e o pedido de ativação (o admin ativa).
 * Dados vindos do PremiumController.
 */
$lang = require BASE_PATH . '/includes/i18n.php';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$pageTitle = $lang['plan_tab'] . ' · Kolae';
$toastContext = 'plan';
$dateBr = fn(?string $ymd) => $ymd ? date('d/m/Y', strtotime($ymd)) : '';

// Linhas da comparação: [ícone, texto, Grátis tem? (true, false ou texto), detalhe do Premium]
$rows = [
    ['fa-map-location-dot', $lang['plan_row_map'], true, null],
    ['fa-inbox', $lang['plan_row_requests'], true, null],
    ['fa-chart-line', $lang['plan_row_stats'], $lang['plan_row_stats_free'], $lang['plan_row_stats_premium']],
    ['fa-bullhorn', $lang['plan_row_ads'], false, $lang['plan_row_ads_premium']],
];
$stateInfo = [
    'free' => ['fa-seedling', 'text-content-secondary bg-content-secondary/10', $lang['plan_state_free'], $lang['plan_state_free_text']],
    'requested' => ['fa-hourglass-half', 'text-amber-700 dark:text-amber-300 bg-amber-500/10', $lang['plan_state_requested'], strtr($lang['plan_state_requested_text'], ['{date}' => $dateBr($user['plan_requested_at'] ?? null)])],
    'premium' => ['fa-crown', 'text-amber-600 dark:text-amber-400 bg-amber-400/15', $lang['plan_state_premium'], empty($user['plan_until']) ? $lang['plan_state_premium_nolimit'] : strtr($lang['plan_state_premium_text'], ['{date}' => $dateBr($user['plan_until'])])],
    'expired' => ['fa-clock-rotate-left', 'text-content-secondary bg-content-secondary/10', $lang['plan_state_expired'], strtr($lang['plan_state_expired_text'], ['{date}' => $dateBr($user['plan_until'] ?? null)])],
][$planState];
$card = 'bg-surface-elevated p-6 md:p-8 rounded-2xl border border-content-secondary/10 shadow-xl';
?>
<!DOCTYPE html>
<html lang="<?php echo $e($_SESSION['idioma'] ?? 'pt-br'); ?>">

<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>

<body class="bg-surface-base text-content-primary font-sans antialiased">
    <?php $backUrl = '/dashboard';
    include BASE_PATH . '/app/views/partials/owner_header.php'; ?>

    <main class="container mx-auto px-4 py-10 max-w-6xl">
        <div class="flex flex-col md:flex-row items-start gap-8">
            <aside class="w-full md:w-64 flex-shrink-0">
                <h1 class="text-2xl font-bold text-content-primary mb-6 px-2"><?php echo $e($lang['global_menu_config']); ?></h1>
                <?php $configActive = 'premium';
                include BASE_PATH . '/app/views/partials/config_nav.php'; ?>
            </aside>

            <section class="flex-1 w-full space-y-6">
                <!-- Plano atual -->
                <div class="<?php echo $card; ?> flex flex-col sm:flex-row sm:items-center gap-5" id="plan-state" data-state="<?php echo $e($planState); ?>">
                    <span class="w-14 h-14 shrink-0 rounded-2xl flex items-center justify-center text-2xl <?php echo $stateInfo[1]; ?>" aria-hidden="true"><i class="fas <?php echo $stateInfo[0]; ?>"></i></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-content-secondary"><?php echo $e($lang['plan_current']); ?></p>
                        <h2 class="text-xl font-bold mt-0.5"><?php echo $e($stateInfo[2]); ?></h2>
                        <p class="text-sm text-content-secondary mt-1"><?php echo $e($stateInfo[3]); ?></p>
                        <?php if ($venueCount > 0): ?>
                            <p class="text-xs text-content-secondary mt-2 flex items-center gap-1.5"><i class="fas fa-layer-group" aria-hidden="true"></i><?php echo $e(strtr($lang[$venueCount === 1 ? 'plan_applies_one' : 'plan_applies_many'], ['{n}' => $venueCount])); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (in_array($planState, ['free', 'expired'], true)): ?>
                        <form method="POST" action="<?php echo BASE_URL; ?>/dashboard/perfil/plano/pedir" class="shrink-0">
                            <?php echo \App\Core\Csrf::field(); ?>
                            <button type="submit" id="plan-request" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold shadow-lg shadow-brand-600/20 transition-colors">
                                <i class="fas fa-crown text-sm" aria-hidden="true"></i><?php echo $e($lang['plan_request_btn']); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Grátis x Premium -->
                <div class="bg-surface-elevated rounded-2xl border border-content-secondary/10 shadow-xl overflow-hidden">
                    <div class="p-6 md:p-8 pb-2 md:pb-3">
                        <h2 class="text-xl font-bold"><?php echo $e($lang['plan_compare_title']); ?></h2>
                        <p class="text-sm text-content-secondary mt-1"><?php echo $e($lang['plan_compare_text']); ?></p>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-content-secondary">
                                <th scope="col" class="px-6 md:px-8 py-3 font-semibold"><span class="sr-only"><?php echo $e($lang['plan_compare_feature']); ?></span></th>
                                <th scope="col" class="px-3 py-3 font-semibold text-center w-24 sm:w-32"><?php echo $e($lang['plan_free']); ?></th>
                                <th scope="col" class="px-3 md:pr-8 py-3 font-semibold text-center w-28 sm:w-44 text-amber-600 dark:text-amber-400"><i class="fas fa-crown mr-1 text-xs" aria-hidden="true"></i><?php echo $e($lang['plan_premium']); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as [$icon, $label, $free, $premiumNote]): ?>
                                <tr class="border-t border-content-secondary/10">
                                    <th scope="row" class="px-6 md:px-8 py-3.5 font-medium text-left">
                                        <span class="flex items-center gap-3"><span class="w-5 text-center text-content-secondary" aria-hidden="true"><i class="fas <?php echo $icon; ?>"></i></span><?php echo $e($label); ?></span>
                                    </th>
                                    <td class="px-3 py-3.5 text-center text-content-secondary">
                                        <?php if ($free === true): ?><i class="fas fa-check text-emerald-600 dark:text-emerald-400" aria-label="<?php echo $e($lang['plan_yes']); ?>"></i>
                                        <?php elseif ($free === false): ?><span aria-label="<?php echo $e($lang['plan_no']); ?>">—</span>
                                        <?php else: ?><span class="text-xs"><?php echo $e($free); ?></span><?php endif; ?>
                                    </td>
                                    <td class="px-3 md:pr-8 py-3.5 text-center bg-amber-400/5">
                                        <i class="fas fa-check text-amber-600 dark:text-amber-400" aria-label="<?php echo $e($lang['plan_yes']); ?>"></i>
                                        <?php if ($premiumNote): ?><span class="block text-xs text-content-secondary mt-0.5"><?php echo $e($premiumNote); ?></span><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="px-6 md:px-8 py-4 text-xs text-content-secondary border-t border-content-secondary/10 flex items-start gap-2">
                        <i class="fas fa-circle-info mt-0.5" aria-hidden="true"></i><span><?php echo $e($lang['plan_how_text']); ?></span>
                    </p>
                </div>
            </section>
        </div>
    </main>

    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
</body>

</html>
