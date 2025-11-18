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

        .animate-up {
            animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .delay-100 {
            animation-delay: 100ms;
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
            <button id="sidebar-close-btn" class="md:hidden absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>

            <div class="p-8 text-center border-b border-gray-800/50">
                <div class="w-20 h-20 rounded-full bg-gray-800 border border-gray-700 mx-auto flex items-center justify-center mb-4 shadow-inner">
                    <i class="fas fa-user-shield text-3xl text-cyan-400"></i>
                </div>
                <h2 class="text-lg font-bold text-white tracking-wide">
                    <?php echo htmlspecialchars($userName ?? 'Admin'); ?>
                </h2>
                <p class="text-xs text-gray-500 uppercase tracking-wider mt-1">Admin Kolae</p>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <a href="<?php echo BASE_URL; ?>/admin" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-home w-5 text-center group-hover:text-cyan-400 transition-colors"></i>
                    <span>Início</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold bg-cyan-500/10 text-cyan-400 border-l-4 border-cyan-400 rounded-r-lg transition-all shadow-md">
                    <i class="fas fa-users w-5 text-center"></i>
                    <span>Usuários</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/admin/esportes" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-running w-5 text-center group-hover:text-purple-400 transition-colors"></i>
                    <span>Esportes</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/mapa" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-map-marker-alt w-5 text-center group-hover:text-green-400 transition-colors"></i>
                    <span>Mapa</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/quadras" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fa-solid fa-flag w-5 text-center group-hover:text-yellow-400 transition-colors"></i>
                    <span>Quadras</span>
                </a>
            </nav>

            <div class="p-4 border-t border-gray-800/50">
                <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors group">
                    <i class="fas fa-sign-out-alt w-5 text-center group-hover:rotate-180 transition-transform duration-300"></i>
                    <span>Sair</span>
                </a>
            </div>
        </aside>

        <div id="sidebar-overlay" class="fixed inset-0 bg-black/80 z-40 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

        <main class="md:ml-64 flex-1 p-4 md:p-10 relative z-10 w-full max-w-[100vw]">

            <div class="flex items-center gap-3 md:hidden mb-6 animate-up">
                <button id="sidebar-toggle" class="p-2.5 bg-gray-800 rounded-lg text-cyan-400 border border-gray-700 active:bg-gray-700 transition-colors">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div>
                    <h1 class="text-xl font-bold text-white leading-tight">Criar Usuário</h1>
                </div>
            </div>

            <h1 class="hidden md:block text-3xl font-bold mb-8 animate-up">Criar Novo Usuário</h1>

            <div class="bg-[#161B22] p-6 md:p-8 rounded-2xl border border-gray-800 max-w-3xl mx-auto shadow-xl animate-up delay-100">

                <form action="<?php echo BASE_URL; ?>/admin/usuarios/salvar" method="POST" class="space-y-5 md:space-y-6">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">

                        <div class="col-span-1 md:col-span-2">
                            <label for="name" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">Nome Completo</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-user text-gray-500 text-sm"></i></div>
                                <input id="name" name="name" type="text" placeholder="Ex: João Silva" required
                                    class="w-full bg-gray-900/50 border border-gray-700 rounded-xl pl-9 pr-4 py-2.5 md:py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                            </div>
                        </div>

                        <div class="col-span-1 md:col-span-2">
                            <label for="email" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">E-mail</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-envelope text-gray-500 text-sm"></i></div>
                                <input id="email" name="email" type="email" placeholder="email@exemplo.com" required
                                    class="w-full bg-gray-900/50 border border-gray-700 rounded-xl pl-9 pr-4 py-2.5 md:py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                            </div>
                        </div>

                        <div>
                            <label for="birthdate" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">Data de Nascimento</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-calendar text-gray-500 text-sm"></i></div>
                                <input id="birthdate" name="birthdate" type="date" required
                                    class="w-full bg-gray-900/50 border border-gray-700 rounded-xl pl-9 pr-4 py-2.5 md:py-3 text-sm text-gray-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                            </div>
                        </div>

                        <div>
                            <label for="role" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">Cargo</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-shield-alt text-gray-500 text-sm"></i></div>
                                <select id="role" name="role" required
                                    class="w-full bg-gray-900/50 border border-gray-700 rounded-xl pl-9 pr-8 py-2.5 md:py-3 text-sm text-white focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 appearance-none transition-all cursor-pointer">
                                    <option value="user" class="bg-[#161B22] text-gray-200 py-2" selected>Usuário Padrão</option>
                                    <option value="admin" class="bg-[#161B22] text-gray-200 py-2">Administrador</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"><i class="fas fa-chevron-down text-gray-500 text-xs"></i></div>
                            </div>
                        </div>

                    </div>

                    <div class="pt-6 flex gap-3 md:justify-end">
                        <a href="<?php echo BASE_URL; ?>/admin/usuarios"
                            class="flex-1 md:flex-none text-center bg-gray-800 hover:bg-gray-700 text-gray-300 font-semibold py-3 px-6 rounded-xl transition-colors border border-gray-700 text-sm">
                            Cancelar
                        </a>
                        <button type="submit"
                            class="flex-1 md:flex-none text-center bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-3 px-8 rounded-xl transition-all hover:shadow-lg hover:-translate-y-0.5 text-sm">
                            Criar Usuário
                        </button>
                    </div>

                </form>
            </div>
        </main>
    </div>

    <script>
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

        if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);
    </script>

</body>

</html>