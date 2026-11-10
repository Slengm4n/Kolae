<?php
/**
 * Início do painel do admin (D67, parte C): "Precisa de atenção" (o que espera uma ação, com o
 * link para a página que resolve), os números do Kolae e os usuários recentes. Premium, Anúncios e
 * Fotos têm páginas próprias. Só em português (D19). Dados do AdminController::dashboard.
 */
$pageTitle = 'Início · Admin · Kolae';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$attention = $attention ?? ['premium' => 0, 'ads' => 0, 'photos' => 0];
$items = [
    ['key' => 'premium', 'icon' => 'fa-crown', 'n' => $attention['premium'], 'href' => '/admin/premium',
        'one' => '1 pedido de Premium esperando ativação', 'many' => '{n} pedidos de Premium esperando ativação'],
    ['key' => 'ads', 'icon' => 'fa-bullhorn', 'n' => $attention['ads'], 'href' => '/admin/anuncios',
        'one' => '1 anúncio em aberto para revisar', 'many' => '{n} anúncios em aberto para revisar'],
    ['key' => 'photos', 'icon' => 'fa-images', 'n' => $attention['photos'], 'href' => '/admin/fotos',
        'one' => '1 foto pesada ou sem miniatura', 'many' => '{n} fotos pesadas ou sem miniatura'],
];
$pending = array_values(array_filter($items, fn($i) => $i['n'] > 0));
$numbers = [
    ['Usuários', $totalUsers ?? 0, 'fa-users', '/admin/usuarios'],
    ['Quadras', $totalLocations ?? 0, 'fa-flag', '/admin/quadras'],
    ['Esportes', $totalSports ?? 0, 'fa-person-running', '/admin/esportes'],
];
$roles = ['admin' => 'Admin', 'owner' => 'Dono', 'player' => 'Jogador'];
$card = 'bg-surface-elevated rounded-2xl border border-content-secondary/10 shadow-sm';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>

<body class="bg-surface-base text-content-primary font-sans antialiased">
    <?php $adminActive = 'inicio'; $toastContext = 'plan'; include BASE_PATH . '/app/views/partials/admin_header.php'; ?>

    <main class="container mx-auto px-4 py-6 md:py-10 max-w-6xl">
        <div class="mb-6">
            <h1 class="text-3xl font-bold">Início</h1>
            <p class="text-sm text-content-secondary mt-1">Olá, <?php echo $e(explode(' ', trim((string)($userName ?? 'Admin')))[0]); ?>. Aqui fica o que precisa da sua atenção.</p>
        </div>

        <!-- Precisa de atenção -->
        <section id="attention" class="mb-8" aria-labelledby="attention-title">
            <h2 id="attention-title" class="text-sm font-semibold uppercase tracking-widest text-content-secondary mb-3">Precisa de atenção</h2>
            <?php if (!$pending): ?>
                <div id="attention-ok" class="<?php echo $card; ?> p-5 flex items-center gap-3">
                    <span class="w-10 h-10 shrink-0 rounded-full bg-green-500/15 text-green-700 dark:text-green-300 flex items-center justify-center" aria-hidden="true"><i class="fas fa-check"></i></span>
                    <p class="font-semibold">Tudo em dia. Nenhum pedido de Premium, anúncio ou foto esperando.</p>
                </div>
            <?php else: ?>
                <ul class="<?php echo $card; ?> divide-y divide-content-secondary/10 overflow-hidden">
                    <?php foreach ($pending as $i): ?>
                        <li>
                            <a href="<?php echo BASE_URL . $i['href']; ?>" data-attention="<?php echo $i['key']; ?>" data-value="<?php echo (int)$i['n']; ?>" class="flex items-center gap-4 px-5 py-4 hover:bg-surface-secondary transition-colors">
                                <span class="w-10 h-10 shrink-0 rounded-xl bg-amber-500/15 text-amber-700 dark:text-amber-300 flex items-center justify-center" aria-hidden="true"><i class="fas <?php echo $i['icon']; ?>"></i></span>
                                <span class="flex-1 font-semibold"><?php echo $e(strtr($i['n'] === 1 ? $i['one'] : $i['many'], ['{n}' => $i['n']])); ?></span>
                                <span class="text-content-secondary"><i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <!-- Números -->
        <section id="numbers" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8" aria-label="Números do Kolae">
            <?php foreach ($numbers as [$label, $value, $icon, $href]): ?>
                <a href="<?php echo BASE_URL . $href; ?>" data-number="<?php echo $e($label); ?>" class="<?php echo $card; ?> p-5 flex items-center gap-4 hover:border-brand-500/40 hover:shadow-md transition-all">
                    <span class="w-11 h-11 rounded-xl bg-surface-secondary text-content-secondary flex items-center justify-center text-lg" aria-hidden="true"><i class="fas <?php echo $icon; ?>"></i></span>
                    <span>
                        <span class="block text-sm font-semibold text-content-secondary"><?php echo $e($label); ?></span>
                        <span class="block text-3xl font-bold tabular-nums"><?php echo (int)$value; ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </section>

        <!-- Usuários recentes -->
        <section id="recent-users" class="<?php echo $card; ?> p-5 sm:p-6" aria-labelledby="recent-title">
            <div class="flex items-center justify-between gap-3 mb-4">
                <h2 id="recent-title" class="text-lg font-semibold">Usuários recentes</h2>
                <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="text-sm font-semibold text-brand-600 dark:text-brand-400 hover:underline">Ver todos</a>
            </div>
            <?php if (empty($recentUsers)): ?>
                <p class="text-sm text-content-secondary">Nenhum usuário recente.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-content-secondary">
                                <th class="py-2 pr-4 font-semibold">Nome</th>
                                <th class="py-2 pr-4 font-semibold">Papel</th>
                                <th class="hidden md:table-cell py-2 pr-4 font-semibold">E-mail</th>
                                <th class="hidden sm:table-cell py-2 font-semibold">Situação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-content-secondary/10">
                            <?php foreach ($recentUsers as $u): $active = ($u['status'] ?? '') === 'active'; ?>
                                <tr>
                                    <td class="py-3 pr-4 font-medium"><?php echo $e($u['name'] ?? ''); ?></td>
                                    <td class="py-3 pr-4 text-content-secondary"><?php echo $e($roles[$u['role'] ?? ''] ?? ($u['role'] ?? '')); ?></td>
                                    <td class="hidden md:table-cell py-3 pr-4 text-content-secondary"><?php echo $e($u['email'] ?? ''); ?></td>
                                    <td class="hidden sm:table-cell py-3">
                                        <span class="text-xs font-semibold rounded-full px-2.5 py-1 <?php echo $active ? 'bg-green-500/15 text-green-700 dark:text-green-300' : 'bg-content-secondary/10 text-content-secondary'; ?>"><?php echo $active ? 'Ativo' : 'Inativo'; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>
