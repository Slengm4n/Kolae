<!DOCTYPE html>
<html lang="pt-BR" class="transition-colors duration-500">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kolae - Criar Esporte</title>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">

    <?php include 'app/views/partials/theme_script.php'; ?>

    <style>
        body { font-family: 'Poppins', sans-serif; -webkit-font-smoothing: antialiased; overflow-x: hidden; }
        
        .animate-up { animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
        .delay-100 { animation-delay: 100ms; }
        
        /* Custom Scrollbar for Icon Selector */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(156, 163, 175, 0.5); border-radius: 20px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: rgba(156, 163, 175, 0.8); }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="bg-surface-base text-content-primary transition-colors duration-500">

    <div class="flex min-h-screen w-full overflow-hidden">

        <aside id="sidebar" class="fixed top-0 left-0 z-50 w-64 h-screen bg-surface-elevated border-r border-content-secondary/10 flex flex-col transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0 shadow-2xl">
            <button id="sidebar-close-btn" class="md:hidden absolute top-4 right-4 text-content-secondary hover:text-content-primary transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>

            <div class="p-8 text-center border-b border-content-secondary/10">
                <div class="w-20 h-20 rounded-full bg-surface-base border border-content-secondary/20 mx-auto flex items-center justify-center mb-4 shadow-inner">
                    <i class="fas fa-user-shield text-3xl text-cyan-500"></i>
                </div>
                <h2 class="text-lg font-bold text-content-primary tracking-wide">
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                </h2>
                <p class="text-xs text-content-secondary uppercase tracking-wider mt-1">Admin Kolae</p>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <a href="<?php echo BASE_URL; ?>/admin" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:bg-surface-base hover:text-content-primary hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-home w-5 text-center group-hover:text-cyan-500"></i><span>Início</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:bg-surface-base hover:text-content-primary hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-users w-5 text-center group-hover:text-cyan-500"></i><span>Usuários</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/esportes" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-l-4 border-cyan-500 rounded-r-lg transition-all shadow-sm">
                    <i class="fas fa-running w-5 text-center"></i><span>Esportes</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/mapa" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:bg-surface-base hover:text-content-primary hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-map-marker-alt w-5 text-center group-hover:text-green-500"></i><span>Mapa</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/quadras" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:bg-surface-base hover:text-content-primary hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fa-solid fa-flag w-5 text-center group-hover:text-yellow-500"></i><span>Quadras</span>
                </a>
            </nav>

            <div class="p-4 border-t border-content-secondary/10 space-y-2">
                <button id="theme-toggle" class="w-full flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:bg-surface-base hover:text-content-primary rounded-lg transition-colors group text-left cursor-pointer">
                    <i class="fas fa-moon w-5 text-center group-hover:text-yellow-400 transition-colors"></i>
                    <span class="dark:hidden">Modo Escuro</span>
                    <span class="hidden dark:inline">Modo Claro</span>
                </button>
                <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i><span>Sair</span>
                </a>
            </div>
        </aside>

        <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 z-40 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

        <main class="md:ml-64 flex-1 p-4 md:p-10 relative z-10 w-full max-w-[100vw]">

            <div class="flex items-center gap-3 md:hidden mb-6 animate-up">
                <button id="sidebar-toggle" class="p-2.5 bg-surface-elevated rounded-lg text-content-secondary border border-content-secondary/10 active:bg-surface-base">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div>
                    <h1 class="text-xl font-bold text-content-primary leading-tight">Criar Esporte</h1>
                </div>
            </div>

            <h1 class="hidden md:block text-3xl font-bold mb-8 animate-up text-content-primary">Criar Novo Esporte</h1>

            <div class="bg-surface-elevated p-6 md:p-8 rounded-2xl border border-content-secondary/10 max-w-4xl mx-auto shadow-xl animate-up delay-100">

                <form action="<?php echo BASE_URL; ?>/admin/esportes/salvar" method="POST" class="space-y-6">

                    <div>
                        <label for="name" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5 ml-1">Nome do Esporte</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-trophy text-content-secondary text-sm"></i>
                            </div>
                            <input id="name" name="name" type="text" placeholder="Ex: Futebol de Salão" required
                                class="w-full bg-input-base border border-content-secondary/20 rounded-xl pl-9 pr-4 py-3 text-sm text-content-primary placeholder-gray-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                        </div>
                    </div>

                    <div>
                        <span class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-3 ml-1">Selecione um Ícone</span>
                        <input type="hidden" name="icon" id="selected-icon-input" required>

                        <div id="icon-selector" class="grid grid-cols-5 sm:grid-cols-8 lg:grid-cols-10 gap-3 bg-surface-base p-4 rounded-xl border border-content-secondary/10 max-h-64 overflow-y-auto custom-scrollbar">
                            </div>
                        <p id="icon-error" class="text-red-500 text-xs mt-2 hidden flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i> Por favor, selecione um ícone.
                        </p>
                    </div>

                    <div class="pt-4 flex gap-3 md:justify-end">
                        <a href="<?php echo BASE_URL; ?>/admin/esportes"
                            class="flex-1 md:flex-none text-center bg-surface-base hover:bg-content-secondary/10 text-content-secondary font-semibold py-3 px-6 rounded-xl transition-colors border border-content-secondary/20 text-sm">
                            Cancelar
                        </a>
                        <button type="submit"
                            class="flex-1 md:flex-none text-center bg-cyan-500 hover:bg-cyan-400 text-white font-bold py-3 px-8 rounded-xl transition-all hover:shadow-lg hover:shadow-cyan-500/20 hover:-translate-y-0.5 text-sm">
                            Criar Esporte
                        </button>
                    </div>

                </form>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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

            // Theme Toggle Logic
            const themeToggleBtn = document.getElementById('theme-toggle');
            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (document.documentElement.classList.contains('dark')) {
                        document.documentElement.classList.remove('dark');
                        localStorage.theme = 'light';
                    } else {
                        document.documentElement.classList.add('dark');
                        localStorage.theme = 'dark';
                    }
                });
            }

            // === LÓGICA DO SELETOR DE ÍCONES (ADAPTADA PARA TEMAS) ===
            const iconSelector = document.getElementById('icon-selector');
            if (iconSelector) {
                const selectedIconInput = document.getElementById('selected-icon-input');
                const iconError = document.getElementById('icon-error');

                const icons = [
                    'fa-futbol', 'fa-basketball-ball', 'fa-volleyball-ball', 'fa-table-tennis-paddle-ball',
                    'fa-person-running', 'fa-skating', 'fa-biking', 'fa-swimmer', 'fa-dumbbell',
                    'fa-hiking', 'fa-bowling-ball', 'fa-football-ball', 'fa-golf-ball-tee',
                    'fa-snowboarding', 'fa-skiing', 'fa-person-snowboarding', 'fa-water', 'fa-fish',
                    'fa-medal', 'fa-trophy', 'fa-stopwatch', 'fa-heart-pulse', 'fa-flag-checkered'
                ];

                // Classes Padrão (Sem seleção) - Adaptadas
                const defaultClasses = 'p-3 rounded-lg flex items-center justify-center aspect-square border transition-all duration-200 bg-surface-elevated border-transparent text-content-secondary hover:bg-surface-base hover:text-content-primary focus:outline-none focus:ring-2 focus:ring-cyan-500';
                
                // Classes Selecionado - Adaptadas
                const selectedClasses = 'p-3 rounded-lg flex items-center justify-center aspect-square border transition-all duration-200 bg-cyan-500/10 border-cyan-500 text-cyan-500 shadow-sm scale-105 focus:outline-none focus:ring-2 focus:ring-cyan-500';

                // 1. Cria os botões
                icons.forEach(iconClass => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = defaultClasses;
                    btn.dataset.icon = iconClass;
                    btn.setAttribute('aria-label', 'Ícone ' + iconClass.replace('fa-', ''));
                    btn.innerHTML = `<i class="fas ${iconClass} text-xl pointer-events-none"></i>`;
                    iconSelector.appendChild(btn);
                });

                // 2. Event Delegation
                iconSelector.addEventListener('click', (e) => {
                    const btn = e.target.closest('button');
                    if (!btn) return;

                    // Limpa seleção anterior
                    const prevSelected = iconSelector.querySelector('.border-cyan-500');
                    if (prevSelected) {
                        prevSelected.className = defaultClasses;
                    }

                    // Ativa o novo
                    btn.className = selectedClasses;

                    selectedIconInput.value = btn.dataset.icon;
                    iconError.classList.add('hidden');
                });

                // 3. Validação
                document.querySelector('form').addEventListener('submit', (e) => {
                    if (!selectedIconInput.value) {
                        e.preventDefault();
                        iconError.classList.remove('hidden');
                    }
                });
            }
        });
    </script>

</body>
</html>