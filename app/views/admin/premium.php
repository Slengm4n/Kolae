<?php
/**
 * Kolae Premium no painel do admin (D67, parte C): antes era um card do Início. Só em português (D19).
 */
$pageTitle = 'Kolae Premium · Admin · Kolae';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>

<body class="bg-surface-base text-content-primary font-sans antialiased">
    <?php $adminActive = 'premium'; $toastContext = 'plan'; include BASE_PATH . '/app/views/partials/admin_header.php'; ?>

    <main class="container mx-auto px-4 py-6 md:py-10 max-w-6xl">
        <div class="mb-6">
            <h1 class="text-3xl font-bold">Kolae Premium</h1>
        </div>

            <?php // Kolae Premium (D61): o plano é do dono; pedidos em aberto e donos Premium
            $plans = \App\Core\PremiumPlan::adminList();
            $defaultUntil = \App\Core\PremiumPlan::defaultUntil(); ?>
            <section id="premium" class="bg-surface-elevated p-6 rounded-2xl border border-content-secondary/10 shadow-sm mb-10 scroll-mt-6">
                <p class="text-sm text-content-secondary mt-1">O plano é do dono e vale para todas as quadras dele. Validade padrão: 30 dias.</p>
                <?php if (!$plans): ?>
                    <p class="text-sm text-content-secondary mt-4">Nenhum pedido de Premium e nenhum dono Premium por enquanto.</p>
                <?php else: ?>
                    <ul class="mt-4 divide-y divide-content-secondary/10">
                        <?php foreach ($plans as $p):
                            $state = \App\Core\PremiumPlan::state($p); ?>
                            <li class="py-4 flex flex-col lg:flex-row lg:items-center gap-3" data-plan-row="<?php echo (int)$p['id']; ?>" data-state="<?php echo $state; ?>">
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-content-primary truncate"><?php echo htmlspecialchars($p['name']); ?></p>
                                    <p class="text-xs text-content-secondary truncate"><?php echo htmlspecialchars($p['email']); ?> · <?php echo (int)$p['venues']; ?> quadra(s)</p>
                                </div>
                                <span class="self-start lg:self-auto text-xs font-semibold rounded-full px-2.5 py-1 <?php echo ['requested' => 'bg-amber-500/15 text-amber-700 dark:text-amber-300', 'premium' => 'bg-brand-500/10 text-brand-600 dark:text-brand-400', 'expired' => 'bg-content-secondary/10 text-content-secondary', 'free' => 'bg-content-secondary/10 text-content-secondary'][$state]; ?>">
                                    <?php echo [
                                        'requested' => 'Pediu em ' . date('d/m', strtotime($p['plan_requested_at'])),
                                        'premium' => $p['plan_until'] ? 'Premium até ' . date('d/m/Y', strtotime($p['plan_until'])) : 'Premium sem prazo',
                                        'expired' => 'Venceu em ' . date('d/m/Y', strtotime((string)$p['plan_until'])),
                                        'free' => 'Grátis',
                                    ][$state]; ?>
                                </span>
                                <form method="POST" action="<?php echo BASE_URL; ?>/admin/premium/<?php echo (int)$p['id']; ?>/ativar" class="flex flex-wrap items-center gap-2">
                                    <?php echo \App\Core\Csrf::field(); ?>
                                    <label class="sr-only" for="until-<?php echo (int)$p['id']; ?>">Premium até</label>
                                    <input type="date" id="until-<?php echo (int)$p['id']; ?>" name="plan_until" value="<?php echo htmlspecialchars($state === 'premium' && $p['plan_until'] ? $p['plan_until'] : $defaultUntil); ?>" min="<?php echo date('Y-m-d'); ?>" class="h-9 rounded-lg border border-content-secondary/25 bg-surface-base px-2 text-sm">
                                    <label class="flex items-center gap-1.5 text-xs text-content-secondary"><input type="checkbox" name="no_limit" value="1" class="accent-brand-500" <?php echo $state === 'premium' && !$p['plan_until'] ? 'checked' : ''; ?>>Sem prazo</label>
                                    <button type="submit" data-plan-activate class="h-9 px-3 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold"><?php echo $state === 'premium' ? 'Atualizar' : 'Ativar'; ?></button>
                                </form>
                                <form method="POST" action="<?php echo BASE_URL; ?>/admin/premium/<?php echo (int)$p['id']; ?>/desativar"
                                    data-confirm="<?php echo $state === 'requested' ? 'Recusar o pedido de Premium deste dono?' : 'Voltar este dono para o Grátis? Os anúncios saem do ar e as estatísticas voltam ao resumo de 7 dias.'; ?>" data-confirm-danger data-confirm-ok="<?php echo $state === 'requested' ? 'Recusar' : 'Voltar para o Grátis'; ?>">
                                    <?php echo \App\Core\Csrf::field(); ?>
                                    <button type="submit" data-plan-deactivate class="text-sm font-semibold text-red-600 dark:text-red-400 hover:underline"><?php echo $state === 'requested' ? 'Recusar' : 'Desativar'; ?></button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
    </main>
</body>

</html>
