<!DOCTYPE html>
<html lang="pt-BR" class="transition-colors duration-500">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kolae - Gerir Usuários</title>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">

    <?php include 'app/views/partials/theme_script.php'; ?>

    <style>
        body { font-family: 'Poppins', sans-serif; -webkit-font-smoothing: antialiased; overflow-x: hidden; }
        .link-button { background: none; border: none; padding: 0; cursor: pointer; text-decoration: none; }
        
        /* Animações */
        .animate-up { animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
        tbody tr { opacity: 0; animation: fadeInUp 0.4s ease-out forwards; }
        tbody tr:nth-child(1) { animation-delay: 50ms; }
        tbody tr:nth-child(2) { animation-delay: 100ms; }
        tbody tr:nth-child(3) { animation-delay: 150ms; }
        tbody tr:nth-child(4) { animation-delay: 200ms; }
        tbody tr:nth-child(n+5) { animation-delay: 250ms; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="bg-surface-base text-content-primary transition-colors duration-500">

    <div id="toast" style="position: fixed; bottom: 2rem; right: 2rem; z-index: 100;"
        class="flex items-center w-full max-w-xs p-4 mb-4 bg-surface-elevated text-content-primary rounded-xl shadow-2xl border border-content-secondary/10 transform transition-all duration-300 translate-x-full opacity-0" role="alert">
        <div id="toast-icon-container" class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg">
            <i id="toast-icon" class="fas fa-check"></i>
        </div>
        <div class="ml-3 text-sm font-normal" id="toast-message">Notificação</div>
        <button type="button" class="ml-auto -mx-1.5 -my-1.5 text-content-secondary hover:text-content-primary rounded-lg p-1.5 hover:bg-surface-base inline-flex h-8 w-8 transition-colors" onclick="hideToast()">
            <span class="sr-only">Fechar</span>
            <i class="fas fa-times"></i>
        </button>
    </div>

    <?php
    if (isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] === 'success_with_password') {
        $password = json_encode($_SESSION['flash_message']['password']);
        echo "<script>document.addEventListener('DOMContentLoaded', () => { showPasswordModal($password); });</script>";
        unset($_SESSION['flash_message']);
    }
    ?>

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
                    <i class="fas fa-home w-5 text-center group-hover:text-cyan-500"></i>
                    <span>Início</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-l-4 border-cyan-500 rounded-r-lg transition-all">
                    <i class="fas fa-users w-5 text-center"></i>
                    <span>Usuários</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/esportes" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:bg-surface-base hover:text-content-primary hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-running w-5 text-center group-hover:text-purple-500"></i>
                    <span>Esportes</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/mapa" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:bg-surface-base hover:text-content-primary hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-map-marker-alt w-5 text-center group-hover:text-green-500"></i>
                    <span>Mapa</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/quadras" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:bg-surface-base hover:text-content-primary hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fa-solid fa-flag w-5 text-center group-hover:text-yellow-500"></i>
                    <span>Quadras</span>
                </a>
            </nav>

            <div class="p-4 border-t border-content-secondary/10 space-y-2">
                <button id="theme-toggle" class="w-full flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:bg-surface-base hover:text-content-primary rounded-lg transition-colors group text-left cursor-pointer">
                    <i class="fas fa-moon w-5 text-center group-hover:text-yellow-400 transition-colors"></i>
                    <span class="dark:hidden">Modo Escuro</span>
                    <span class="hidden dark:inline">Modo Claro</span>
                </button>

                <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i>
                    <span>Sair</span>
                </a>
            </div>
        </aside>

        <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 z-40 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

        <main class="md:ml-64 flex-1 p-4 md:p-10 relative z-10 w-full max-w-[100vw]">

            <div class="flex items-center gap-3 md:hidden mb-4 animate-up">
                <button id="sidebar-toggle" class="p-2.5 bg-surface-elevated rounded-lg text-content-secondary border border-content-secondary/10 active:bg-surface-base">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div>
                    <h1 class="text-xl font-bold text-content-primary leading-tight">Gerir Usuários</h1>
                </div>
            </div>

            <div class="hidden md:flex justify-between items-center mb-8 animate-up">
                <div>
                    <h1 class="text-3xl font-bold mb-1 text-content-primary">Gerir Usuários</h1>
                    <p class="text-content-secondary text-sm">Administre o acesso e permissões do sistema.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/usuarios/criar" class="bg-cyan-500 hover:bg-cyan-400 text-white font-bold py-3 px-6 rounded-xl inline-flex justify-center items-center transition-all hover:shadow-lg hover:shadow-cyan-500/20 hover:-translate-y-0.5">
                    <i class="fas fa-plus mr-2"></i> Novo Usuário
                </a>
            </div>

            <div class="md:hidden mb-4 animate-up delay-100">
                <a href="<?php echo BASE_URL; ?>/admin/usuarios/criar" class="w-full bg-cyan-500 hover:bg-cyan-400 text-white font-bold py-2.5 px-4 rounded-xl flex justify-center items-center shadow-md text-sm">
                    <i class="fas fa-plus mr-2"></i> Novo Usuário
                </a>
            </div>

            <?php if (isset($_GET['status']) && !empty($message)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const type = "<?php echo (strpos($bgColor ?? '', 'red') !== false || $_GET['status'] === 'error') ? 'error' : 'success'; ?>";
                        showToast("<?php echo htmlspecialchars($message); ?>", type);
                    });
                </script>
            <?php endif; ?>

            <div class="mb-4 animate-up delay-100">
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-content-secondary text-sm"></i>
                    </div>
                    <input type="text" id="searchInput" class="block w-full pl-9 pr-4 py-2.5 border border-content-secondary/20 rounded-xl bg-surface-elevated text-sm text-content-primary placeholder-gray-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 shadow-sm transition-all" placeholder="Pesquisar...">
                </div>
            </div>

            <div class="bg-surface-elevated rounded-2xl border border-content-secondary/10 shadow-lg animate-up delay-200 overflow-hidden">
                <div class="overflow-x-auto overflow-y-hidden">
                    <table id="userTable" class="min-w-full divide-y divide-content-secondary/10">
                        <thead>
                            <tr class="bg-surface-base/50 text-left">
                                <th class="hidden md:table-cell px-6 py-4 text-xs font-medium text-content-secondary uppercase tracking-wider pl-8">ID</th>
                                <th class="px-4 md:px-6 py-3 md:py-4 text-xs font-medium text-content-secondary uppercase tracking-wider w-1/2">Usuário</th>
                                <th class="hidden md:table-cell px-6 py-4 text-xs font-medium text-content-secondary uppercase tracking-wider">Data</th>
                                <th class="hidden md:table-cell px-6 py-4 text-xs font-medium text-content-secondary uppercase tracking-wider">Cargo</th>
                                <th class="px-3 md:px-6 py-3 md:py-4 text-xs font-medium text-content-secondary uppercase tracking-wider text-center">Status</th>
                                <th class="px-4 md:px-6 py-3 md:py-4 text-right text-xs font-medium text-content-secondary uppercase tracking-wider pr-4 md:pr-8">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-content-secondary/10">
                            <?php if (isset($users) && count($users) > 0) : ?>
                                <?php foreach ($users as $user) : ?>
                                    <tr class="hover:bg-surface-base transition-colors group">
                                        <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm font-mono text-content-secondary pl-8">#<?php echo htmlspecialchars($user['id']); ?></td>
                                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 md:h-10 md:w-10 rounded-full bg-surface-base border border-content-secondary/20 flex-shrink-0 flex items-center justify-center text-xs md:text-sm font-bold text-content-primary mr-3">
                                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-sm font-medium text-content-primary truncate max-w-[140px] md:max-w-xs">
                                                        <?php echo htmlspecialchars($user['name']); ?>
                                                    </div>
                                                    <div class="text-xs text-content-secondary truncate max-w-[140px]"><?php echo htmlspecialchars($user['email']); ?></div>
                                                    <div class="md:hidden mt-1">
                                                        <span class="text-[10px] border border-content-secondary/20 rounded px-1.5 py-0.5 bg-surface-base text-content-secondary uppercase">
                                                            <?php echo htmlspecialchars($user['role']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-content-secondary">
                                            <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                        </td>
                                        <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-content-secondary capitalize">
                                            <span class="px-2 py-1 rounded bg-surface-base border border-content-secondary/20 text-xs">
                                                <?php echo htmlspecialchars($user['role']); ?>
                                            </span>
                                        </td>
                                        <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap text-center">
                                            <?php $isActive = $user['status'] === 'active'; ?>
                                            <div class="inline-flex items-center justify-center px-2 py-1 rounded-full <?php echo $isActive ? 'bg-green-500/10 border border-green-500/20' : 'bg-red-500/10 border border-red-500/20'; ?>">
                                                <div class="w-1.5 h-1.5 rounded-full <?php echo $isActive ? 'bg-green-500' : 'bg-red-500'; ?> mr-1.5"></div>
                                                <span class="text-[10px] md:text-xs font-medium <?php echo $isActive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                                    <?php echo $isActive ? 'Ativo' : 'Inativo'; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-right text-sm font-medium pr-4 md:pr-8">
                                            <div class="flex items-center justify-end gap-3 md:gap-4">
                                                <a href="<?php echo BASE_URL; ?>/admin/usuarios/editar/<?php echo $user['id']; ?>" class="text-content-secondary hover:text-cyan-500 p-1 transition-colors" title="Editar">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                                <form action="<?php echo BASE_URL; ?>/admin/usuarios/excluir/<?php echo $user['id']; ?>" method="POST" class="inline" onsubmit="return confirm('Tem a certeza?');">
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
                                    <td colspan="6" class="px-6 py-8 text-center text-content-secondary">
                                        <p class="text-sm">Nenhum usuário encontrado.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="password-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm hidden transition-opacity duration-300 p-4">
        <div class="bg-surface-elevated border border-content-secondary/20 rounded-2xl shadow-2xl p-6 w-full max-w-sm transform scale-100 transition-transform duration-300">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-content-primary flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i> Sucesso!</h3>
                <button id="close-modal-btn" class="text-content-secondary hover:text-content-primary text-xl">&times;</button>
            </div>
            <div class="bg-yellow-500/10 border border-yellow-500/20 text-yellow-600 dark:text-yellow-200 p-3 rounded-lg mb-4 text-xs">
                <strong class="block font-bold mb-1 text-yellow-600 dark:text-yellow-400">Importante</strong> Copie a senha abaixo agora.
            </div>
            <div class="relative mb-4">
                <input type="password" id="modal-password-display" readonly class="w-full bg-input-base border border-content-secondary/20 rounded-lg pl-3 pr-20 py-3 text-lg text-cyan-600 dark:text-cyan-300 font-mono text-center tracking-widest">
                <div class="absolute top-1/2 right-2 -translate-y-1/2 flex items-center space-x-1 bg-surface-base rounded p-1 border border-content-secondary/10">
                    <button id="toggle-visibility-btn" class="p-1.5 text-content-secondary hover:text-content-primary rounded"><i class="fas fa-eye text-xs"></i></button>
                    <div class="w-px h-3 bg-content-secondary/20"></div>
                    <button id="copy-password-btn" class="p-1.5 text-content-secondary hover:text-content-primary rounded"><i class="far fa-copy text-xs"></i></button>
                </div>
            </div>
            <div class="text-center">
                <p class="text-[10px] text-content-secondary mb-2">Fecha em <span id="modal-timer-countdown" class="text-content-primary font-bold">30</span>s</p>
                <div class="w-full bg-surface-base rounded-full h-1">
                    <div id="modal-timer-bar" class="bg-cyan-500 h-1 rounded-full" style="width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('password-modal');
        const passwordDisplay = document.getElementById('modal-password-display');
        const copyBtn = document.getElementById('copy-password-btn');
        const closeBtn = document.getElementById('close-modal-btn');
        const toggleVisibilityBtn = document.getElementById('toggle-visibility-btn');
        const timerBar = document.getElementById('modal-timer-bar');
        const timerCountdown = document.getElementById('modal-timer-countdown');
        let countdownTimer, progressBarInterval;
        let toastTimer;

        // --- FUNÇÕES DO TOAST ---
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            const toastIcon = document.getElementById('toast-icon');
            const toastIconContainer = document.getElementById('toast-icon-container');

            toastMessage.textContent = message;

            if (type === 'success') {
                toast.classList.remove('border-red-500');
                toast.classList.add('border-green-500');
                toastIconContainer.classList.remove('bg-red-100', 'text-red-500');
                toastIconContainer.classList.add('bg-green-100', 'text-green-500');
                toastIcon.className = 'fas fa-check';
            } else {
                toast.classList.remove('border-green-500');
                toast.classList.add('border-red-500');
                toastIconContainer.classList.remove('bg-green-100', 'text-green-500');
                toastIconContainer.classList.add('bg-red-100', 'text-red-500');
                toastIcon.className = 'fas fa-exclamation';
            }

            toast.classList.remove('translate-x-full', 'opacity-0');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(hideToast, 4000);
        }

        function hideToast() {
            const toast = document.getElementById('toast');
            toast.classList.add('translate-x-full', 'opacity-0');
        }

        // --- FUNÇÕES DO MODAL DE SENHA ---
        function showPasswordModal(password) {
            passwordDisplay.value = password;
            modal.classList.remove('hidden');
            passwordDisplay.type = 'password';
            let timeLeft = 30;
            clearTimeout(countdownTimer);
            clearInterval(progressBarInterval);
            countdownTimer = setTimeout(closeModal, 30000);
            progressBarInterval = setInterval(() => {
                timeLeft--;
                timerBar.style.width = `${(timeLeft / 30) * 100}%`;
                timerCountdown.textContent = timeLeft;
                if (timeLeft <= 0) clearInterval(progressBarInterval);
            }, 1000);
        }

        function closeModal() {
            modal.classList.add('hidden');
        }
        if (copyBtn) copyBtn.addEventListener('click', () => {
            navigator.clipboard.writeText(passwordDisplay.value);
        });
        if (toggleVisibilityBtn) toggleVisibilityBtn.addEventListener('click', () => {
            passwordDisplay.type = passwordDisplay.type === 'password' ? 'text' : 'password';
        });
        if (closeBtn) closeBtn.addEventListener('click', closeModal);

        // --- SIDEBAR & SEARCH ---
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        });
        document.getElementById('sidebar-close-btn')?.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
        overlay?.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });

        const searchInput = document.getElementById('searchInput');
        const tableRows = document.querySelectorAll('#userTable tbody tr');
        if (searchInput) {
            searchInput.addEventListener('keyup', () => {
                const term = searchInput.value.toLowerCase();
                tableRows.forEach(row => {
                    if (row.cells.length > 1) row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
                });
            });
        }

        // --- THEME TOGGLE ---
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
    </script>
</body>
</html>