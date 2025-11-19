<?php
// Supondo que $data['userName'] e $data['venues'] vêm do VenueController
$userName = $data['userName'] ?? 'Admin';
$venues = $data['venues'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kolae</title>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print" onload="this.media='all'" />
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    </noscript>

    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        .link-button {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        /* Animações */
        .animate-up {
            animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .delay-100 {
            animation-delay: 100ms;
        }

        tbody tr {
            opacity: 0;
            animation: fadeInUp 0.4s ease-out forwards;
        }

        tbody tr:nth-child(1) {
            animation-delay: 50ms;
        }

        tbody tr:nth-child(2) {
            animation-delay: 100ms;
        }

        tbody tr:nth-child(3) {
            animation-delay: 150ms;
        }

        tbody tr:nth-child(4) {
            animation-delay: 200ms;
        }

        tbody tr:nth-child(n+5) {
            animation-delay: 250ms;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-[#0D1117] text-gray-200">

    <div class="flex min-h-screen w-full overflow-hidden">

        <aside id="sidebar" class="fixed top-0 left-0 z-50 w-64 h-screen bg-[#161B22] border-r border-gray-800 flex flex-col transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0 shadow-2xl">
            <button id="sidebar-close-btn" class="md:hidden absolute top-4 right-4 text-gray-500 hover:text-white transition-colors"><i class="fas fa-times text-2xl"></i></button>
            <div class="p-8 text-center border-b border-gray-800/50">
                <div class="w-20 h-20 rounded-full bg-gray-800 border border-gray-700 mx-auto flex items-center justify-center mb-4 shadow-inner"><i class="fas fa-user-shield text-3xl text-cyan-400"></i></div>
                <h2 class="text-lg font-bold text-white tracking-wide"><?php echo htmlspecialchars($userName); ?></h2>
                <p class="text-xs text-gray-500 uppercase tracking-wider mt-1">Admin Kolae</p>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <a href="<?php echo BASE_URL; ?>/admin" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group"><i class="fas fa-home w-5 text-center group-hover:text-cyan-400"></i><span>Início</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group"><i class="fas fa-users w-5 text-center group-hover:text-cyan-400"></i><span>Usuários</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/esportes" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group"><i class="fas fa-running w-5 text-center group-hover:text-purple-400"></i><span>Esportes</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/mapa" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group"><i class="fas fa-map-marker-alt w-5 text-center group-hover:text-green-400"></i><span>Mapa</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/quadras" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold bg-cyan-500/10 text-cyan-400 border-l-4 border-cyan-400 rounded-r-lg transition-all shadow-md"><i class="fa-solid fa-flag w-5 text-center"></i><span>Quadras</span></a>
            </nav>
            <div class="p-4 border-t border-gray-800/50">
                <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors"><i class="fas fa-sign-out-alt w-5 text-center"></i><span>Sair</span></a>
            </div>
        </aside>
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/80 z-40 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

        <main class="md:ml-64 flex-1 p-4 md:p-10 relative z-10 w-full max-w-[100vw]">

            <div class="flex items-center gap-3 md:hidden mb-6 animate-up">
                <button id="sidebar-toggle" class="p-2.5 bg-gray-800 rounded-lg text-cyan-400 border border-gray-700 active:bg-gray-700"><i class="fas fa-bars text-lg"></i></button>
                <div>
                    <h1 class="text-xl font-bold text-white leading-tight">Gerenciar Quadras</h1>
                </div>
            </div>

            <div class="hidden md:flex justify-between items-center mb-8 animate-up">
                <div>
                    <h1 class="text-3xl font-bold mb-1">Gerenciar Quadras</h1>
                    <p class="text-gray-400 text-sm">Cadastre e monitore os locais esportivos.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/quadras/criar" class="bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-3 px-6 rounded-xl inline-flex justify-center items-center transition-all hover:shadow-lg hover:-translate-y-0.5">
                    <i class="fas fa-plus mr-2"></i> Adicionar Quadra
                </a>
            </div>

            <div class="md:hidden mb-4 animate-up delay-100">
                <a href="<?php echo BASE_URL; ?>/admin/quadras/criar" class="w-full bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-2.5 px-4 rounded-lg flex justify-center items-center shadow-md text-sm">
                    <i class="fas fa-plus mr-2"></i> Adicionar Quadra
                </a>
            </div>

            <div class="mb-6 space-y-4 animate-up delay-100">
                <?php if (isset($_GET['status'])): ?>
                    <?php
                    $message = '';
                    $bgColor = 'bg-green-500/10 border-green-500/20 text-green-400';
                    $icon = 'check-circle';

                    switch ($_GET['status']) {
                        case 'created':
                            $message = 'Quadra cadastrada com sucesso!';
                            break;
                        case 'updated':
                            $message = 'Dados da quadra atualizados!';
                            break;
                        case 'deleted':
                            $message = 'Quadra removida com sucesso!';
                            break;
                        case 'not_found':
                            $message = 'Quadra não encontrada.';
                            $bgColor = 'bg-yellow-500/10 border-yellow-500/20 text-yellow-400';
                            $icon = 'exclamation-triangle';
                            break;
                        case 'error':
                            $message = 'Ocorreu um erro inesperado.';
                            $bgColor = 'bg-red-500/10 border-red-500/20 text-red-400';
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
                        <i class="fas fa-search text-gray-500 text-sm group-focus-within:text-cyan-400 transition-colors"></i>
                    </div>
                    <input type="text" id="searchInput" class="block w-full pl-9 pr-4 py-2.5 border border-gray-700 rounded-lg bg-[#161B22] text-sm text-white placeholder-gray-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 shadow-sm" placeholder="Pesquisar por nome ou endereço...">
                </div>
            </div>

            <div class="bg-[#161B22] rounded-xl border border-gray-800 shadow-lg animate-up delay-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table id="venuesTable" class="min-w-full divide-y divide-gray-800">
                        <thead>
                            <tr class="bg-gray-900/50 text-left">
                                <th class="px-4 md:px-6 py-3 md:py-4 text-xs font-medium text-gray-400 uppercase tracking-wider w-1/2">Nome da Quadra</th>
                                <th class="hidden lg:table-cell px-6 py-4 text-xs font-medium text-gray-400 uppercase tracking-wider">Endereço</th>
                                <th class="px-3 md:px-6 py-3 md:py-4 text-xs font-medium text-gray-400 uppercase tracking-wider text-center">Status</th>
                                <th class="px-4 md:px-6 py-3 md:py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider pr-4 md:pr-8">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            <?php if (isset($venues) && count($venues) > 0) : ?>
                                <?php foreach ($venues as $venue) : ?>
                                    <tr class="hover:bg-gray-800/50 transition-colors group">
                                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 md:h-10 md:w-10 rounded-lg bg-gray-700 flex-shrink-0 flex items-center justify-center text-cyan-400 mr-3">
                                                    <i class="fa-solid fa-flag text-xs md:text-sm"></i>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-sm font-medium text-white truncate max-w-[160px] md:max-w-xs group-hover:text-cyan-400 transition-colors">
                                                        <?php echo htmlspecialchars($venue['name']); ?>
                                                    </div>
                                                    <div class="lg:hidden text-xs text-gray-500 truncate max-w-[160px]">
                                                        <?php echo htmlspecialchars(($venue['street'] ?? '') . ', ' . ($venue['number'] ?? '')); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                            <?php echo htmlspecialchars(($venue['street'] ?? 'N/D') . ', ' . ($venue['number'] ?? 'S/N')); ?>
                                        </td>

                                        <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap text-center">
                                            <?php
                                            $isAvailable = $venue['status'] === 'available';
                                            $statusClass = $isAvailable ? 'bg-green-500/10 text-green-400 border-green-500/20' : 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20';
                                            $dotClass = $isAvailable ? 'bg-green-400' : 'bg-yellow-400';
                                            $statusText = $isAvailable ? 'Disponível' : 'Indisponível';
                                            ?>
                                            <div class="inline-flex items-center justify-center px-2.5 py-1 rounded-full border <?php echo $statusClass; ?>">
                                                <div class="w-1.5 h-1.5 rounded-full <?php echo $dotClass; ?> mr-1.5"></div>
                                                <span class="text-[10px] md:text-xs font-medium"><?php echo $statusText; ?></span>
                                            </div>
                                        </td>

                                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-right text-sm font-medium pr-4 md:pr-8">
                                            <div class="flex items-center justify-end gap-3 md:gap-4">
                                                <a href="<?php echo BASE_URL; ?>/admin/quadras/editar/<?php echo $venue['id']; ?>" class="text-gray-400 hover:text-cyan-400 p-1 transition-colors" title="Editar">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                                <form action="<?php echo BASE_URL; ?>/admin/quadras/excluir/<?php echo $venue['id']; ?>" method="POST" class="inline" onsubmit="return confirm('Tem a certeza que deseja excluir esta quadra?');">
                                                    <button type="submit" class="link-button text-gray-400 hover:text-red-400 p-1 transition-colors" title="Excluir">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
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
    </div>

    <script>
        // Sidebar Logic
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const overlay = document.getElementById('sidebar-overlay');

        function openSidebar() {
            sidebar?.classList.remove('-translate-x-full');
            overlay?.classList.remove('hidden');
        }

        function closeSidebar() {
            sidebar?.classList.add('-translate-x-full');
            overlay?.classList.add('hidden');
        }
        toggleBtn?.addEventListener('click', openSidebar);
        closeBtn?.addEventListener('click', closeSidebar);
        overlay?.addEventListener('click', closeSidebar);

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