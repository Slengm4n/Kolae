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

    <link href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo APP_VERSION; ?>" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* Animações */
        .animate-up {
            animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        /* Stagger effect para o grid (até 12 itens) */
        .stagger-grid>div:nth-child(1) {
            animation-delay: 50ms;
        }

        .stagger-grid>div:nth-child(2) {
            animation-delay: 100ms;
        }

        .stagger-grid>div:nth-child(3) {
            animation-delay: 150ms;
        }

        .stagger-grid>div:nth-child(4) {
            animation-delay: 200ms;
        }

        .stagger-grid>div:nth-child(5) {
            animation-delay: 250ms;
        }

        .stagger-grid>div:nth-child(6) {
            animation-delay: 300ms;
        }

        .stagger-grid>div:nth-child(n+7) {
            animation-delay: 350ms;
        }

        .card-sport {
            opacity: 0;
            animation: fadeInUp 0.5s ease-out forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
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
            <button id="sidebar-close-btn" class="md:hidden absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>

            <div class="p-8 text-center border-b border-gray-800/50">
                <div class="w-20 h-20 rounded-full bg-gray-800 border border-gray-700 mx-auto flex items-center justify-center mb-4 shadow-inner">
                    <i class="fas fa-user-shield text-3xl text-cyan-400"></i>
                </div>
                <h2 class="text-lg font-bold text-white tracking-wide">
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                </h2>
                <p class="text-xs text-gray-500 uppercase tracking-wider mt-1">Admin Kolae</p>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <a href="<?php echo BASE_URL; ?>/admin" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-home w-5 text-center group-hover:text-cyan-400"></i>
                    <span>Início</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-users w-5 text-center group-hover:text-cyan-400"></i>
                    <span>Usuários</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/esportes" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold bg-cyan-500/10 text-cyan-400 border-l-4 border-cyan-400 rounded-r-lg transition-all shadow-md">
                    <i class="fas fa-running w-5 text-center"></i>
                    <span>Esportes</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/mapa" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-map-marker-alt w-5 text-center group-hover:text-green-400"></i>
                    <span>Mapa</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/quadras" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fa-solid fa-flag w-5 text-center group-hover:text-yellow-400"></i>
                    <span>Quadras</span>
                </a>
            </nav>

            <div class="p-4 border-t border-gray-800/50">
                <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i>
                    <span>Sair</span>
                </a>
            </div>
        </aside>

        <div id="sidebar-overlay" class="fixed inset-0 bg-black/80 z-40 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

        <main class="md:ml-64 flex-1 p-4 md:p-10 relative z-10 w-full max-w-[100vw]">

            <div class="flex items-center gap-3 md:hidden mb-6 animate-up">
                <button id="sidebar-toggle" class="p-2.5 bg-gray-800 rounded-lg text-cyan-400 border border-gray-700 active:bg-gray-700">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div>
                    <h1 class="text-xl font-bold text-white leading-tight">Gerir Esportes</h1>
                </div>
            </div>

            <div class="hidden md:flex justify-between items-center mb-8 animate-up">
                <div>
                    <h1 class="text-3xl font-bold mb-1">Gerir Esportes</h1>
                    <p class="text-gray-400 text-sm">Adicione ou edite modalidades disponíveis.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/esportes/criar"
                    class="bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-3 px-6 rounded-xl inline-flex justify-center items-center transition-all hover:shadow-lg hover:-translate-y-0.5">
                    <i class="fas fa-plus mr-2"></i> Novo Esporte
                </a>
            </div>

            <div class="md:hidden mb-6 animate-up delay-100">
                <a href="<?php echo BASE_URL; ?>/admin/esportes/criar"
                    class="w-full bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-2.5 px-4 rounded-lg flex justify-center items-center shadow-md text-sm">
                    <i class="fas fa-plus mr-2"></i> Novo Esporte
                </a>
            </div>

            <div class="mb-6 animate-up delay-100">
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-500 text-sm group-focus-within:text-cyan-400 transition-colors"></i>
                    </div>
                    <input type="text" placeholder="Pesquisar esporte..." class="block w-full pl-9 pr-4 py-2.5 border border-gray-700 rounded-lg bg-[#161B22] text-sm text-white placeholder-gray-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 shadow-sm transition-all">
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6 stagger-grid">
                <?php if (!empty($sports)) : ?>
                    <?php foreach ($sports as $sport) : ?>
                        <div class="card-sport group relative bg-[#161B22] p-6 rounded-2xl border border-gray-800 text-center flex flex-col items-center justify-center aspect-square transition-all duration-300 hover:border-cyan-400/50 hover:shadow-lg hover:shadow-cyan-500/10 hover:-translate-y-1">

                            <i class="fas <?php echo htmlspecialchars($sport['icon'] ?? 'fa-question-circle'); ?> text-4xl md:text-5xl text-gray-600 transition-colors duration-300 group-hover:text-cyan-400 group-hover:scale-110 transform"></i>

                            <h3 class="mt-4 font-bold text-sm md:text-lg text-white"><?php echo htmlspecialchars($sport['name']); ?></h3>

                            <div class="absolute top-2 right-2 md:top-3 md:right-3 md:opacity-0 md:group-hover:opacity-100 transition-all duration-300 flex space-x-1 md:space-x-2">
                                <a href="<?php echo BASE_URL; ?>/admin/esportes/editar/<?php echo $sport['id']; ?>" class="w-7 h-7 md:w-8 md:h-8 flex items-center justify-center bg-gray-800/80 md:bg-gray-700 rounded-full text-gray-400 hover:bg-cyan-500 hover:text-black transition-colors" title="Editar">
                                    <i class="fas fa-pencil-alt text-xs"></i>
                                </a>

                                <form action="<?php echo BASE_URL; ?>/admin/esportes/excluir/<?php echo $sport['id']; ?>" method="POST" onsubmit="return confirm('Tem a certeza?');">
                                    <button type="submit" class="w-7 h-7 md:w-8 md:h-8 flex items-center justify-center bg-gray-800/80 md:bg-gray-700 rounded-full text-gray-400 hover:bg-red-500 hover:text-white transition-colors" title="Excluir">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </form>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="col-span-full py-12 text-center text-gray-500 flex flex-col items-center animate-up delay-200">
                        <i class="fas fa-running text-4xl mb-3 opacity-50"></i>
                        <p>Nenhum esporte encontrado.</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <script>
        // Sidebar Logic (Igual as outras)
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

        if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);
    </script>

</body>

</html>