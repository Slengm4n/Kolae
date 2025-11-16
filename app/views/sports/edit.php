<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae</title>

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
                <h2 class="text-xl font-bold">
                    <?php echo htmlspecialchars($userName ?? 'Admin'); ?>
                </h2>
                <p class="text-sm text-gray-400">Admin Kolae</p>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-2">
                <a href="<?php echo BASE_URL; ?>/admin" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors"><i class="fas fa-home w-5 text-center"></i><span>Início</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors"><i class="fas fa-users w-5 text-center"></i><span>Usuários</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/esportes" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold bg-cyan-500/10 text-cyan-400 rounded-lg"><i class="fas fa-running w-5 text-center"></i><span>Esportes</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/mapa" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors"><i class="fas fa-map-marker-alt w-5 text-center"></i><span>Mapa</span></a>
                <a href="<?php echo BASE_URL; ?>/admin/quadras" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors"><i class="fa-solid fa-flag w-5 text-center"></i><span>Quadras</span></a>
            </nav>
            <div class="p-4 border-t border-gray-800">
                <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-red-400 hover:bg-red-500/10 rounded-lg transition-colors"><i class="fas fa-sign-out-alt w-5 text-center"></i><span>Sair</span></a>
            </div>
        </aside>

        <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 z-30 hidden md:hidden"></div>

        <main class="md:ml-64 flex-1 p-6 sm:p-10">
            <button id="sidebar-toggle" class="md:hidden mb-6 text-gray-400 hover:text-white">
                <i class="fas fa-bars text-2xl"></i>
            </button>
            <h1 class="text-3xl font-bold mb-8">Editar Esporte</h1>

            <div class="bg-[#161B22] p-8 rounded-2xl border border-gray-800 max-w-4xl mx-auto">
                <?php if (!isset($data['sport']) || !$data['sport']): ?>
                    <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg text-center">
                        Dados do esporte não encontrados.
                    </div>
                <?php else: $sport = $data['sport']; ?>
                    <form action="<?php echo BASE_URL; ?>/admin/esportes/atualizar" method="POST" class="space-y-6">

                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($sport['id']); ?>">

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-300">Nome do Esporte</label>
                            <div class="mt-1">
                                <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($sport['name']); ?>" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                            </div>
                        </div>

                        <div>
                            <legend classs="block text-sm font-medium text-gray-300">Selecione um Ícone</legend>
                            <input type="hidden" name="icon" id="selected-icon-input" value="<?php echo htmlspecialchars($sport['icon'] ?? ''); ?>" required>

                            <fieldset id="icon-selector" class="mt-2 grid grid-cols-5 sm:grid-cols-8 lg:grid-cols-10 gap-4 bg-gray-800 p-4 rounded-lg border border-gray-700">
                            </fieldset>
                            <p id="icon-error" class="text-red-400 text-sm mt-2 hidden">Por favor, selecione um ícone.</p>
                        </div>

                        <div class="pt-4 flex items-center space-x-4">
                            <button type="submit" class="bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-2 px-6 rounded-lg transition-colors">
                                Salvar Alterações
                            </button>
                            <a href="<?php echo BASE_URL; ?>/admin/esportes" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                                Cancelar
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
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
                if (sidebar) sidebar.classList.remove('-translate-x-full');
                if (overlay) overlay.classList.remove('hidden');
            }

            function closeSidebar() {
                if (sidebar) sidebar.classList.add('-translate-x-full');
                if (overlay) overlay.classList.add('hidden');
            }

            if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            if (overlay) overlay.addEventListener('click', closeSidebar);

            // --- INÍCIO DA LÓGICA DE ÍCONE CORRIGIDA ---
            const iconSelector = document.getElementById('icon-selector');
            if (iconSelector) {
                const selectedIconInput = document.getElementById('selected-icon-input');
                const form = document.querySelector('form');
                const iconError = document.getElementById('icon-error');
                const currentIcon = selectedIconInput.value;

                const icons = [
                    'fa-futbol', 'fa-basketball-ball', 'fa-volleyball-ball', 'fa-table-tennis-paddle-ball',
                    'fa-person-running', 'fa-skating', 'fa-biking', 'fa-swimmer', 'fa-dumbbell',
                    'fa-hiking', 'fa-bowling-ball', 'fa-football-ball', 'fa-golf-ball-tee',
                    'fa-snowboarding', 'fa-skiing', 'fa-person-snowboarding', 'fa-water', 'fa-fish'
                ];

                // 1. Popula os ícones
                icons.forEach(iconClass => {
                    // MUDANÇA: O 'div' agora é um <button type="button">
                    // Isso o torna focável pelo teclado (Tab) e clicável (Enter/Espaço)
                    const iconWrapper = document.createElement('button');
                    iconWrapper.type = 'button'; // Previne que ele envie o formulário
                    iconWrapper.className = 'cursor-pointer p-4 bg-gray-900 rounded-md flex items-center justify-center aspect-square border-2 border-transparent hover:border-cyan-400 focus:border-cyan-400 focus:outline-none transition-all';
                    iconWrapper.dataset.icon = iconClass; // Armazena o nome do ícone no próprio elemento

                    // Adiciona um nome acessível para leitores de tela
                    iconWrapper.setAttribute('aria-label', iconClass.replace('fa-', ' '));

                    const iconElement = document.createElement('i');
                    iconElement.className = `fas ${iconClass} text-3xl text-gray-400`;
                    iconElement.setAttribute('aria-hidden', 'true'); // O ícone é decorativo

                    iconWrapper.appendChild(iconElement);
                    iconSelector.appendChild(iconWrapper);

                    // 2. Destaca o ícone que já estava salvo
                    if (iconClass === currentIcon) {
                        iconWrapper.classList.add('border-cyan-400', 'bg-cyan-500/10');
                        iconElement.classList.add('text-cyan-400');
                    }
                });

                // 3. Adiciona UM listener no PAI (o <fieldset>)
                iconSelector.addEventListener('click', function(event) {
                    // Encontra o <button> que foi clicado (ou que é pai do <i> clicado)
                    const clickedButton = event.target.closest('button[data-icon]');

                    if (!clickedButton) {
                        return; // Sai se o clique não foi em um botão de ícone
                    }

                    const clickedIconClass = clickedButton.dataset.icon;

                    // 4. Limpa a seleção de TODOS os ícones
                    iconSelector.querySelectorAll('button[data-icon]').forEach(button => {
                        button.classList.remove('border-cyan-400', 'bg-cyan-500/10');
                        button.querySelector('i').classList.remove('text-cyan-400');
                    });

                    // 5. Adiciona a seleção APENAS no ícone clicado
                    clickedButton.classList.add('border-cyan-400', 'bg-cyan-500/10');
                    clickedButton.querySelector('i').classList.add('text-cyan-400');

                    // 6. Define o valor do input hidden e esconde o erro
                    selectedIconInput.value = clickedIconClass;
                    iconError.classList.add('hidden');
                });
                // --- FIM DA LÓGICA DE ÍCONE CORRIGIDA ---

                // Validação do formulário (como antes)
                if (form) {
                    form.addEventListener('submit', function(event) {
                        if (!selectedIconInput.value) {
                            event.preventDefault();
                            iconError.classList.remove('hidden');
                        }
                    });
                }
            }
        });
    </script>
</body>

</html>