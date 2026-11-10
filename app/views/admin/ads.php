<?php
/**
 * Anúncios no painel do admin (D67, parte C): antes era um card do Início. Só em português (D19).
 */
$pageTitle = 'Anúncios · Admin · Kolae';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>

<body class="bg-surface-base text-content-primary font-sans antialiased">
    <?php $adminActive = 'anuncios'; $toastContext = 'plan'; include BASE_PATH . '/app/views/partials/admin_header.php'; ?>

    <main class="container mx-auto px-4 py-6 md:py-10 max-w-6xl">
        <div class="mb-6">
            <h1 class="text-3xl font-bold">Anúncios</h1>
        </div>

            <?php // Anúncios (D63): em aberto (ativos ou pausados, no período); o admin remove com motivo
            $adToday = \App\Core\VenueSchedule::now();
            $openAds = \App\Models\VenueAd::openForAdmin($adToday);
            $removeOpen = isset($_GET['remover']) && ctype_digit((string)$_GET['remover']) ? (int)$_GET['remover'] : 0;
            $adStateLabel = [
                'running' => ['No ar', 'bg-green-500/15 text-green-700 dark:text-green-300'],
                'scheduled' => ['Agendado', 'bg-brand-500/10 text-brand-600 dark:text-brand-400'],
                'paused' => ['Pausado', 'bg-content-secondary/10 text-content-secondary'],
                'premium_expired' => ['Premium vencido (fora do ar)', 'bg-content-secondary/10 text-content-secondary'],
            ]; ?>
            <section id="anuncios" class="bg-surface-elevated p-6 rounded-2xl border border-content-secondary/10 shadow-sm mb-10 scroll-mt-6">
                <p class="text-sm text-content-secondary mt-1">Anúncios ativos, agendados e pausados. Remover tira do ar na hora; o dono lê o motivo na aba Anúncio e recebe uma notificação.</p>
                <?php if (!$openAds): ?>
                    <p class="text-sm text-content-secondary mt-4">Nenhum anúncio em aberto.</p>
                <?php else: ?>
                    <ul class="mt-4 divide-y divide-content-secondary/10">
                        <?php foreach ($openAds as $ad):
                            $adId = (int)$ad['id'];
                            $ownerPremium = \App\Core\PremiumPlan::isPremium(['plan' => $ad['owner_plan'], 'plan_until' => $ad['owner_plan_until']], $adToday);
                            $adState = ($ad['venue_status'] ?? '') === 'unavailable' ? 'venue_off' : \App\Core\VenueAds::ownerState($ad, $ownerPremium, $adToday);
                            [$stateText, $stateClass] = $adStateLabel[$adState] ?? ['Quadra desativada (fora do ar)', 'bg-content-secondary/10 text-content-secondary']; ?>
                            <li class="py-4" data-ad-row="<?php echo $adId; ?>" data-state="<?php echo $adState; ?>">
                                <div class="flex flex-col lg:flex-row lg:items-start gap-3">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-content-primary break-words"><?php echo htmlspecialchars($ad['title']); ?></p>
                                        <p class="text-sm text-content-secondary break-words"><?php echo htmlspecialchars($ad['body']); ?></p>
                                        <p class="mt-1 text-xs text-content-secondary">
                                            <a href="<?php echo BASE_URL; ?>/admin/quadras/<?php echo (int)$ad['venue_id']; ?>/anuncio" class="font-semibold text-brand-600 dark:text-brand-400 hover:underline"><?php echo htmlspecialchars($ad['venue_name']); ?></a>
                                            · <?php echo htmlspecialchars($ad['owner_name']); ?> (<?php echo htmlspecialchars($ad['owner_email']); ?>)
                                            · <?php echo date('d/m', strtotime($ad['starts_on'])); ?> a <?php echo date('d/m/Y', strtotime($ad['ends_on'])); ?>
                                        </p>
                                    </div>
                                    <span class="self-start text-xs font-semibold rounded-full px-2.5 py-1 <?php echo $stateClass; ?>"><?php echo $stateText; ?></span>
                                </div>
                                <details class="mt-3" <?php echo $removeOpen === $adId ? 'open' : ''; ?>>
                                    <summary class="inline-flex cursor-pointer text-sm font-semibold text-red-600 dark:text-red-400 hover:underline">Remover com motivo</summary>
                                    <form method="POST" action="<?php echo BASE_URL; ?>/admin/anuncios/<?php echo $adId; ?>/remover" class="mt-3 max-w-xl"
                                        data-confirm="Remover este anúncio? Ele sai do ar na hora e não pode voltar; o dono pode criar outro." data-confirm-danger data-confirm-ok="Remover">
                                        <?php echo \App\Core\Csrf::field(); ?>
                                        <label for="reason-<?php echo $adId; ?>" class="block text-sm font-medium text-content-primary">Motivo (o dono vai ler)</label>
                                        <textarea id="reason-<?php echo $adId; ?>" name="reason" rows="2" required minlength="<?php echo \App\Core\VenueAds::REASON_MIN; ?>" maxlength="<?php echo \App\Core\VenueAds::REASON_MAX; ?>"
                                            placeholder="Ex.: o texto promete um desconto que a quadra não oferece."
                                            class="mt-1 w-full rounded-lg border border-content-secondary/25 bg-surface-base px-3 py-2 text-sm"></textarea>
                                        <button type="submit" data-ad-remove class="mt-2 h-9 px-3 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold">Remover anúncio</button>
                                    </form>
                                </details>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
    </main>
</body>

</html>
