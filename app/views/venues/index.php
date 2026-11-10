<?php
// Supondo que $data['userName'] e $data['venues'] vêm do VenueController
$userName = $data['userName'] ?? 'Admin';
$venues = $data['venues'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR" class="transition-colors duration-500">

<head>
    <?php $pageTitle = 'Quadras · Kolae'; include BASE_PATH . '/app/views/partials/head.php'; ?>

    <style>
        body { font-family: 'Poppins', sans-serif; -webkit-font-smoothing: antialiased; overflow-x: hidden; }
        .link-button { background: none; border: none; padding: 0; cursor: pointer; text-decoration: none; color: inherit; }
        
        /* Animações */
        .animate-up { animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
        .delay-100 { animation-delay: 100ms; }
        
        tbody tr { opacity: 0; animation: fadeInUp 0.4s ease-out forwards; }
        tbody tr:nth-child(1) { animation-delay: 50ms; }
        tbody tr:nth-child(2) { animation-delay: 100ms; }
        tbody tr:nth-child(3) { animation-delay: 150ms; }
        tbody tr:nth-child(4) { animation-delay: 200ms; }
        tbody tr:nth-child(n+5) { animation-delay: 250ms; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="bg-surface-base text-content-primary transition-colors duration-500">
    <?php $adminActive = 'quadras'; include BASE_PATH . '/app/views/partials/admin_header.php'; ?>





        <main class="container mx-auto px-4 py-6 md:py-10 max-w-6xl">

            <div class="flex items-center gap-3 md:hidden mb-6 animate-up">
                <div>
                    <h1 class="text-xl font-bold text-content-primary leading-tight">Gerenciar Quadras</h1>
                </div>
            </div>

            <div class="hidden md:flex justify-between items-center mb-8 animate-up">
                <div>
                    <h1 class="text-3xl font-bold mb-1 text-content-primary">Gerenciar Quadras</h1>
                    <p class="text-content-secondary text-sm">Cadastre e monitore os locais esportivos.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/quadras/criar" class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-6 rounded-xl inline-flex justify-center items-center transition-all hover:shadow-lg hover:shadow-brand-500/20 hover:-translate-y-0.5">
                    <i class="fas fa-plus mr-2"></i> Adicionar Quadra
                </a>
            </div>

            <div class="md:hidden mb-4 animate-up delay-100">
                <a href="<?php echo BASE_URL; ?>/admin/quadras/criar" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-2.5 px-4 rounded-lg flex justify-center items-center shadow-md text-sm">
                    <i class="fas fa-plus mr-2"></i> Adicionar Quadra
                </a>
            </div>

            <div class="mb-6 space-y-4 animate-up delay-100">
                <?php if (isset($_GET['status'])): ?>
                    <?php
                    $message = '';
                    $bgColor = 'bg-green-500/10 border-green-500/20 text-green-600 dark:text-green-400';
                    $icon = 'check-circle';

                    switch ($_GET['status']) {
                        case 'created': $message = 'Quadra cadastrada com sucesso!'; break;
                        case 'updated': $message = 'Dados da quadra atualizados!'; break;
                        case 'deleted': $message = 'Quadra removida com sucesso!'; break;
                        case 'not_found':
                            $message = 'Quadra não encontrada.';
                            $bgColor = 'bg-yellow-500/10 border-yellow-500/20 text-yellow-600 dark:text-yellow-400';
                            $icon = 'exclamation-triangle';
                            break;
                        case 'error':
                            $message = 'Ocorreu um erro inesperado.';
                            $bgColor = 'bg-red-500/10 border-red-500/20 text-red-600 dark:text-red-400';
                            $icon = 'times-circle';
                            break;
                    }
                    ?>
                    <?php if (!empty($message)): ?>
                        <div class="p-4 border rounded-xl flex items-center <?php echo $bgColor; ?>" role="alert">
                            <i class="fas fa-<?php echo $icon; ?> mr-3 text-lg"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="mb-4 animate-up delay-100">
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-content-secondary text-sm group-focus-within:text-brand-500 transition-colors"></i>
                    </div>
                    <input type="text" id="searchInput" class="block w-full pl-9 pr-4 py-2.5 border border-content-secondary/20 rounded-xl bg-surface-elevated text-sm text-content-primary placeholder-gray-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 shadow-sm transition-all" placeholder="Pesquisar por nome ou endereço...">
                </div>
            </div>

            <div class="bg-surface-elevated rounded-xl border border-content-secondary/10 shadow-lg animate-up delay-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table id="venuesTable" class="min-w-full divide-y divide-content-secondary/10">
                        <thead>
                            <tr class="bg-surface-base/50 text-left">
                                <th class="px-4 md:px-6 py-3 md:py-4 text-xs font-medium text-content-secondary uppercase tracking-wider w-1/2">Nome da Quadra</th>
                                <th class="hidden lg:table-cell px-6 py-4 text-xs font-medium text-content-secondary uppercase tracking-wider">Endereço</th>
                                <th class="px-3 md:px-6 py-3 md:py-4 text-xs font-medium text-content-secondary uppercase tracking-wider text-center">Status</th>
                                <th class="px-4 md:px-6 py-3 md:py-4 text-right text-xs font-medium text-content-secondary uppercase tracking-wider pr-4 md:pr-8">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-content-secondary/10">
                            <?php if (isset($venues) && count($venues) > 0) : ?>
                                <?php foreach ($venues as $venue) : ?>
                                    <tr class="hover:bg-surface-base transition-colors group">
                                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 md:h-10 md:w-10 rounded-lg bg-surface-base border border-content-secondary/20 flex-shrink-0 flex items-center justify-center text-brand-500 mr-3">
                                                    <i class="fa-solid fa-flag text-xs md:text-sm"></i>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-sm font-medium text-content-primary truncate max-w-[160px] md:max-w-xs group-hover:text-brand-500 transition-colors">
                                                        <?php echo htmlspecialchars($venue['name']); ?>
                                                    </div>
                                                    <div class="lg:hidden text-xs text-content-secondary truncate max-w-[160px]">
                                                        <?php echo htmlspecialchars(($venue['street'] ?? '') . ', ' . ($venue['number'] ?? '')); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-content-secondary">
                                            <?php echo htmlspecialchars(($venue['street'] ?? 'N/D') . ', ' . ($venue['number'] ?? 'S/N')); ?>
                                        </td>

                                        <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap text-center">
                                            <?php
                                            $isAvailable = $venue['status'] === 'available';
                                            $statusClass = $isAvailable ? 'bg-green-500/10 text-green-600 dark:text-green-400 border-green-500/20' : 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 border-yellow-500/20';
                                            $dotClass = $isAvailable ? 'bg-green-500' : 'bg-yellow-500';
                                            $statusText = $isAvailable ? 'Disponível' : 'Indisponível';
                                            ?>
                                            <div class="inline-flex items-center justify-center px-2.5 py-1 rounded-full border <?php echo $statusClass; ?>">
                                                <div class="w-1.5 h-1.5 rounded-full <?php echo $dotClass; ?> mr-1.5"></div>
                                                <span class="text-[10px] md:text-xs font-medium"><?php echo $statusText; ?></span>
                                            </div>
                                        </td>

                                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-right text-sm font-medium pr-4 md:pr-8">
                                            <div class="flex items-center justify-end gap-3 md:gap-4">
                                                <a href="<?php echo BASE_URL; ?>/admin/quadras/<?php echo (int)$venue['id']; ?>/visao-geral" class="text-content-secondary hover:text-brand-500 p-1 transition-colors" title="Visão geral"><i class="fas fa-eye"></i></a>
                                                <a href="<?php echo BASE_URL; ?>/admin/quadras/editar/<?php echo $venue['id']; ?>" class="text-content-secondary hover:text-brand-500 p-1 transition-colors" title="Editar">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                                <form action="<?php echo BASE_URL; ?>/admin/quadras/excluir/<?php echo $venue['id']; ?>" method="POST" class="inline" data-confirm="Tem a certeza que deseja excluir esta quadra?" data-confirm-danger data-confirm-ok="Excluir">
                                                    <?php echo \App\Core\Csrf::field(); ?>
                                                    <button type="submit" class="link-button text-content-secondary hover:text-red-500 p-1 transition-colors" title="Excluir">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-content-secondary">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-store-slash text-3xl mb-2 opacity-50"></i>
                                            <p class="text-sm">Nenhuma quadra encontrada.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

    <script>

        // Search Logic
        const searchInput = document.getElementById('searchInput');
        const tableRows = document.querySelectorAll('#venuesTable tbody tr');
        if (searchInput) {
            searchInput.addEventListener('keyup', () => {
                const term = searchInput.value.toLowerCase();
                tableRows.forEach(row => {
                    if (row.cells.length > 1) row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
                });
            });
        }

    </script>
</body>
</html>