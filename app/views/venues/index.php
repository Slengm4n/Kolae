<?php
// Supondo que $data['userName'] e $data['venues'] vêm do VenueController::index()
$userName = $data['userName'] ?? 'Admin';
$venues = $data['venues'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae - Admin Quadras</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .link-button {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            text-decoration: none;
            /* Adicionado para garantir */
            color: inherit;
            /* Adicionado para garantir */
        }
    </style>
</head>

<body class="bg-[#0D1117] text-gray-200">
    <div>
        <aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen bg-[#161B22] border-r border-gray-800 flex flex-col transition-transform -translate-x-full md:translate-x-0">
            <button id="sidebar-close-btn" class="md:hidden absolute top-4 right-4 text-gray-500 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <div class="p-6 text-center border-b border-gray-800">
                <div class="w-24 h-24 rounded-full bg-gray-700 mx-auto flex items-center justify-center mb-4">
                    <i class="fas fa-user-shield text-4xl text-cyan-400"></i>
                </div>
                <h2 class="text-xl font-bold"><?php echo htmlspecialchars($userName); ?></h2>
                <p class="text-sm text-gray-400">Admin Kolae</p>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-2">
                <a href="<?php echo BASE_URL; ?>/admin" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors">
                    <i class="fas fa-home w-5 text-center"></i>
                    <span>Início</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors">
                    <i class="fas fa-users w-5 text-center"></i>
                    <span>Usuários</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/esportes" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors">
                    <i class="fas fa-running w-5 text-center"></i>
                    <span>Esportes</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/mapa" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors">
                    <i class="fas fa-map-marker-alt w-5 text-center"></i>
                    <span>Mapa</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/quadras" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold bg-cyan-500/10 text-cyan-400 rounded-lg">
                    <i class="fa-solid fa-flag w-5 text-center"></i>
                    <span>Quadras</span>
                </a>
            </nav>
            <div class="p-4 border-t border-gray-800">
                <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i>
                    <span>Sair</span>
                </a>
            </div>
        </aside>

        <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 z-30 hidden md:hidden"></div>

        <main class="md:ml-64 flex-1 p-6 sm:p-10">
            <button id="sidebar-toggle" class="md:hidden mb-6 text-gray-400 hover:text-white">
                <i class="fas fa-bars text-2xl"></i>
            </button>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
                <h1 class="text-3xl font-bold mb-4 sm:mb-0">Gerenciar Quadras</h1>
                <a href="<?php echo BASE_URL; ?>/admin/quadras/criar" class="bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-2 px-4 rounded-lg inline-flex items-center transition-colors">
                    <i class="fas fa-plus mr-2"></i>Adicionar Quadra
                </a>
            </div>

            <div class="mb-6">
                <label for="searchInput" class="sr-only">Pesquisar Quadras</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" id="searchInput"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-700 rounded-lg bg-gray-800 text-white placeholder-gray-500 focus:outline-none focus:border-cyan-500 focus:ring-cyan-500 sm:text-sm"
                        placeholder="Pesquisar por nome ou endereço...">
                </div>
            </div>

            <div class="bg-[#161B22] rounded-2xl border border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table id="venuesTable" class="min-w-full divide-y divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nome da Quadra</th>
                                <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Endereço</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if (isset($venues) && count($venues) > 0) : ?>
                                <?php foreach ($venues as $venue) : ?>
                                    <tr class="hover:bg-gray-800/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white"><?php echo htmlspecialchars($venue['name']); ?></td>
                                        <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-400"><?php echo htmlspecialchars(($venue['street'] ?? 'N/D') . ', ' . ($venue['number'] ?? 'S/N')); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo $venue['status'] === 'available' ? 'bg-green-500/20 text-green-300' : 'bg-yellow-500/20 text-yellow-300'; ?>">
                                                <?php echo $venue['status'] === 'available' ? 'Disponível' : 'Indisponível'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="<?php echo BASE_URL; ?>/admin/quadras/editar/<?php echo $venue['id']; ?>" class="text-cyan-400 hover:text-cyan-300 transition-colors" title="Editar">
                                                <i class="fas fa-pencil-alt"></i> <span class="hidden sm:inline">Editar</span>
                                            </a>
                                            <form action="<?php echo BASE_URL; ?>/admin/quadras/excluir/<?php echo $venue['id']; ?>" method="POST" class="inline ml-4" onsubmit="return confirm('Tem a certeza que deseja excluir esta quadra?');">
                                                <button type="submit" class="link-button text-red-400 hover:text-red-300 transition-colors" title="Excluir">
                                                    <i class="fas fa-trash-alt"></i> <span class="hidden sm:inline">Excluir</span>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-store-slash text-3xl mb-2"></i>
                                        <p>Nenhuma quadra encontrada.</p>
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
        // --- Lógica da Sidebar ---
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const sidebarCloseBtn = document.getElementById('sidebar-close-btn');
        const overlay = document.getElementById('sidebar-overlay');

        function openSidebar() {
            if (sidebar && overlay) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            }
        }

        function closeSidebar() {
            if (sidebar && overlay) {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
        if (toggleBtn && sidebarCloseBtn && overlay) {
            toggleBtn.addEventListener('click', openSidebar);
            sidebarCloseBtn.addEventListener('click', closeSidebar);
            overlay.addEventListener('click', closeSidebar);
        }
        // --- Fim Sidebar ---


        // --- LÓGICA DA BARRA DE PESQUISA ---
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('venuesTable');

        if (table && searchInput) {
            const tableBody = table.querySelector('tbody');

            if (tableBody) {
                const tableRows = tableBody.querySelectorAll(':scope > tr');
                const noVenuesRow = tableBody.querySelector('tr > td[colspan="4"]'); // Pega a linha "Nenhuma quadra"

                console.log("Número de linhas (TRs) encontradas no tbody:", tableRows.length);

                searchInput.addEventListener('keyup', function() {
                    const searchTerm = searchInput.value.toLowerCase().trim();
                    let visibleRowCount = 0;

                    tableRows.forEach((row) => {
                        const cells = row.cells;

                        // Pula a linha "Nenhuma quadra encontrada"
                        if (cells.length === 1 && cells[0].getAttribute('colspan')) {
                            return; // Não filtra esta linha
                        }

                        if (cells.length < 2) return; // Pula linhas mal formatadas

                        const nameCellText = (cells[0].textContent || cells[0].innerText).trim();
                        const addressCellText = (cells[1].textContent || cells[1].innerText).trim(); // Coluna 1 é o endereço
                        const rowText = (nameCellText + ' ' + addressCellText).toLowerCase();

                        if (rowText.includes(searchTerm)) {
                            row.style.display = '';
                            visibleRowCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    // Mostra/Esconde a linha "Nenhuma quadra encontrada"
                    if (noVenuesRow) {
                        // Mostra se NENHUMA linha de dados está visível E há mais de uma linha na tabela (a própria linha "nenhuma")
                        noVenuesRow.parentElement.style.display = (visibleRowCount === 0 && tableRows.length > 1) ? '' : 'none';
                    }

                });
            } else {
                console.error("Erro: Elemento <tbody> não encontrado dentro da tabela #venuesTable.");
            }
        } else {
            console.error("Erro: Tabela #venuesTable ou Input #searchInput não encontrado.");
        }
        // --- FIM PESQUISA ---
    </script>
</body>

</html>