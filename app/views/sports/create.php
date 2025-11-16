<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome (para ícones) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
    </style>
</head>

<body class="bg-[#0D1117] text-gray-200">

    <div>
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen bg-[#161B22] border-r border-gray-800 flex flex-col transition-transform -translate-x-full md:translate-x-0">
            <!-- Botão de Fechar para Mobile -->
            <button id="sidebar-close-btn" class="md:hidden absolute top-4 right-4 text-gray-500 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>

            <!-- Logo & User Info -->
            <div class="p-6 text-center border-b border-gray-800">
                <div class="w-24 h-24 rounded-full bg-gray-700 mx-auto flex items-center justify-center mb-4">
                    <i class="fas fa-user-shield text-4xl text-cyan-400"></i>
                </div>
                <h2 class="text-xl font-bold">
                    <?php echo htmlspecialchars($userName ?? 'Admin'); ?>
                </h2>
                <p class="text-sm text-gray-400">Admin Kolae</p>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-2">
                <a href="<?php echo BASE_URL; ?>/admin" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors">
                    <i class="fas fa-home w-5 text-center"></i>
                    <span>Início</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors">
                    <i class="fas fa-users w-5 text-center"></i>
                    <span>Usuários</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/esportes" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold bg-cyan-500/10 text-cyan-400 rounded-lg">
                    <i class="fas fa-running w-5 text-center"></i>
                    <span>Esportes</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/mapa" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors">
                    <i class="fas fa-map-marker-alt w-5 text-center"></i>
                    <span>Locais</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/quadras" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors">
                    <i class="fa-solid fa-flag w-5 text-center"></i>
                    <span>Quadras</span>
                </a>
            </nav>

            <!-- Logout -->
            <div class="p-4 border-t border-gray-800">
                <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i>
                    <span>Sair</span>
                </a>
            </div>
        </aside>

        <!-- Overlay para fechar o menu em mobile -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 z-30 hidden md:hidden"></div>

        <!-- Main Content -->
        <main class="md:ml-64 flex-1 p-6 sm:p-10">
            <!-- Botão Hamburger para Mobile -->
            <button id="sidebar-toggle" class="md:hidden mb-6 text-gray-400 hover:text-white">
                <i class="fas fa-bars text-2xl"></i>
            </button>
            <h1 class="text-3xl font-bold mb-8">Criar Novo Esporte</h1>

            <div class="bg-[#161B22] p-8 rounded-2xl border border-gray-800 max-w-4xl mx-auto">
                <form action="<?php echo BASE_URL; ?>/admin/esportes/salvar" method="POST" class="space-y-6">

                    <!-- Campo Nome do Esporte -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300">Nome do Esporte</label>
                        <div class="mt-1">
                            <input id="name" name="name" type="text" placeholder="Ex: Futebol de Salão" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                        </div>
                    </div>

                    <!-- Seletor de Ícones -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300">Selecione um Ícone</label>
                        <input type="hidden" name="icon" id="selected-icon-input" required>
                        <div id="icon-selector" class="mt-2 grid grid-cols-5 sm:grid-cols-8 lg:grid-cols-10 gap-4 bg-gray-800 p-4 rounded-lg border border-gray-700">
                            <!-- Ícones serão inseridos aqui via JS -->
                        </div>
                        <p id="icon-error" class="text-red-400 text-sm mt-2 hidden">Por favor, selecione um ícone.</p>
                    </div>

                    <div class="pt-4 flex items-center space-x-4">
                        <button type="submit" class="bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-2 px-6 rounded-lg transition-colors">
                            Criar Esporte
                        </button>
                        <a href="<?php echo BASE_URL; ?>/admin/esportes" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebar-toggle');
            const closeBtn = document.getElementById('sidebar-close-btn');
            const overlay = document.getElementById('sidebar-overlay');

            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            }

            function closeSidebar() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }

            toggleBtn.addEventListener('click', openSidebar);
            closeBtn.addEventListener('click', closeSidebar);
            overlay.addEventListener('click', closeSidebar);


            const iconSelector = document.getElementById('icon-selector');
            const selectedIconInput = document.getElementById('selected-icon-input');
            const form = document.querySelector('form');
            const iconError = document.getElementById('icon-error');

            const icons = [
                'fa-futbol', 'fa-basketball-ball', 'fa-volleyball-ball', 'fa-table-tennis-paddle-ball',
                'fa-person-running', 'fa-skating', 'fa-biking', 'fa-swimmer', 'fa-dumbbell',
                'fa-hiking', 'fa-bowling-ball', 'fa-football-ball', 'fa-golf-ball-tee',
                'fa-snowboarding', 'fa-skiing', 'fa-person-snowboarding', 'fa-water', 'fa-fish'
            ];

            // Popula o seletor de ícones
            icons.forEach(iconClass => {
                const iconWrapper = document.createElement('div');
                iconWrapper.className = 'cursor-pointer p-4 bg-gray-900 rounded-md flex items-center justify-center aspect-square border-2 border-transparent hover:border-cyan-400 transition-all';
                iconWrapper.dataset.icon = iconClass;

                const iconElement = document.createElement('i');
                iconElement.className = `fas ${iconClass} text-3xl text-gray-400`;

                iconWrapper.appendChild(iconElement);
                iconSelector.appendChild(iconWrapper);

                // Adiciona o evento de clique
                iconWrapper.addEventListener('click', () => {
                    // Remove a seleção de todos os outros
                    document.querySelectorAll('#icon-selector > div').forEach(el => {
                        el.classList.remove('border-cyan-400', 'bg-cyan-500/10');
                        el.querySelector('i').classList.remove('text-cyan-400');
                    });

                    // Adiciona a seleção ao clicado
                    iconWrapper.classList.add('border-cyan-400', 'bg-cyan-500/10');
                    iconElement.classList.add('text-cyan-400');

                    // Define o valor do input escondido
                    selectedIconInput.value = iconClass;
                    iconError.classList.add('hidden');
                });
            });

            // Validação do formulário antes do envio
            form.addEventListener('submit', function(event) {
                if (!selectedIconInput.value) {
                    event.preventDefault(); // Impede o envio do formulário
                    iconError.classList.remove('hidden');
                }
            });
        });
    </script>

</body>

</html>