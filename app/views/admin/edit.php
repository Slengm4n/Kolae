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
                <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold bg-cyan-500/10 text-cyan-400 rounded-lg">
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
            <h1 class="text-3xl font-bold mb-8">Editar Usuário</h1>

            <!-- Edit User Form -->
            <div class="bg-[#161B22] p-8 rounded-2xl border border-gray-800 max-w-4xl mx-auto">

                <?php if (!isset($userData) || !$userData): ?>
                    <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg text-center">
                        Dados do usuário não encontrados.
                    </div>
                <?php else: ?>

                    <form action="<?php echo BASE_URL; ?>/admin/usuarios/atualizar" method="POST" class="space-y-6">

                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($userData['id']); ?>">

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-300">Nome Completo</label>
                            <div class="mt-1">
                                <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($userData['name']); ?>" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-300">Endereço de E-mail</label>
                            <div class="mt-1">
                                <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                            </div>
                        </div>

                        <div>
                            <label for="birthdate" class="block text-sm font-medium text-gray-300">Data de Nascimento</label>
                            <div class="mt-1">
                                <input id="birthdate" name="birthdate" type="date" value="<?php echo htmlspecialchars($userData['birthdate']); ?>" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm text-gray-400 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                            </div>
                        </div>

                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-300">Cargo do Usuário</label>
                            <div class="mt-1">
                                <select id="role" name="role" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm text-gray-300 focus:outline-none focus:ring-2 focus:ring-cyan-400">

                                    <option value="user" <?php if ($userData['role'] === 'user') echo 'selected'; ?>>
                                        Usuário Padrão
                                    </option>
                                    <option value="admin" <?php if ($userData['role'] === 'admin') echo 'selected'; ?>>
                                        Administrador
                                    </option>

                                </select>
                            </div>
                        </div>

                        <div class="pt-4 flex items-center space-x-4">
                            <button type="submit" class="bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-2 px-6 rounded-lg transition-colors">
                                Salvar Alterações
                            </button>
                            <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                                Cancelar
                            </a>
                        </div>
                    </form>

                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
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
    </script>
</body>

</html>