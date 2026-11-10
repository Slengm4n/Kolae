<!DOCTYPE html>
<html lang="pt-BR" class="transition-colors duration-500">

<head>
    <?php $pageTitle = 'Editar esporte · Kolae'; include BASE_PATH . '/app/views/partials/head.php'; ?>

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
    <?php $adminActive = 'esportes'; include BASE_PATH . '/app/views/partials/admin_header.php'; ?>





        <main class="container mx-auto px-4 py-6 md:py-10 max-w-6xl">

            <div class="flex items-center gap-3 md:hidden mb-6 animate-up">
                <div>
                    <h1 class="text-xl font-bold text-content-primary leading-tight">Editar Esporte</h1>
                </div>
            </div>

            <h1 class="hidden md:block text-3xl font-bold mb-8 animate-up text-content-primary">Editar Esporte</h1>

            <div class="bg-surface-elevated p-6 md:p-8 rounded-2xl border border-content-secondary/10 max-w-4xl mx-auto shadow-xl animate-up delay-100">

                <?php if (empty($data['sport'])): ?>
                    <div class="bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 px-4 py-3 rounded-xl text-center text-sm flex flex-col items-center">
                        <i class="fas fa-times-circle text-2xl mb-2"></i>
                        <span class="font-medium">Esporte não encontrado.</span>
                        <a href="<?php echo BASE_URL; ?>/admin/esportes" class="mt-3 underline">Voltar para lista</a>
                    </div>
                <?php else: $sport = $data['sport']; ?>
                <form action="<?php echo BASE_URL; ?>/admin/esportes/atualizar" method="POST" class="space-y-6">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$sport['id']; ?>">

                    <div>
                        <label for="name" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5 ml-1">Nome do Esporte</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-trophy text-content-secondary text-sm"></i>
                            </div>
                            <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($sport['name']); ?>" required
                                class="w-full bg-input-base border border-content-secondary/20 rounded-xl pl-9 pr-4 py-3 text-sm text-content-primary placeholder-gray-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                        </div>
                    </div>

                    <div>
                        <span class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-3 ml-1">Selecione um Ícone</span>
                        <input type="hidden" name="icon" id="selected-icon-input" value="<?php echo htmlspecialchars($sport['icon_path'] ?? ''); ?>" required>

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
                            class="flex-1 md:flex-none text-center bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-8 rounded-xl transition-all hover:shadow-lg hover:shadow-brand-500/20 hover:-translate-y-0.5 text-sm">
                            Salvar Alterações
                        </button>
                    </div>

                </form>
                <?php endif; ?>
            </div>
        </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {


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
                const defaultClasses = 'p-3 rounded-lg flex items-center justify-center aspect-square border transition-all duration-200 bg-surface-elevated border-transparent text-content-secondary hover:bg-surface-base hover:text-content-primary focus:outline-none focus:ring-2 focus:ring-brand-500';
                
                // Classes Selecionado - Adaptadas
                const selectedClasses = 'p-3 rounded-lg flex items-center justify-center aspect-square border transition-all duration-200 bg-brand-500/10 border-brand-500 text-brand-500 shadow-sm scale-105 focus:outline-none focus:ring-2 focus:ring-brand-500';

                // 1. Cria os botões, com o ícone atual do esporte já marcado (mesmo se não estiver na lista)
                const currentIcon = selectedIconInput.value;
                if (currentIcon && !icons.includes(currentIcon)) icons.unshift(currentIcon);
                icons.forEach(iconClass => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = iconClass === currentIcon ? selectedClasses : defaultClasses;
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
                    const prevSelected = iconSelector.querySelector('.border-brand-500');
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