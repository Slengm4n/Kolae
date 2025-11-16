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

    <!-- ==================== CABEÇALHO DO UTILIZADOR ==================== -->
    <header class="bg-[#161B22] border-b border-gray-800 sticky top-0 z-30 py-4">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <a href="<?php echo BASE_URL; ?>/" class="text-2xl font-bold tracking-widest text-white">KOLAE</a>

            <nav class="hidden md:flex items-center space-x-8">
                <a href="<?php echo BASE_URL; ?>/dashboard" class="font-semibold text-cyan-400 transition-colors">Meu Painel</a>
            </nav>

            <div class="relative">
                <div id="user-menu-button" class="flex items-center gap-3 p-2 border border-gray-700 rounded-full cursor-pointer transition-colors hover:bg-gray-700/50">
                    <i class="fas fa-bars text-lg"></i>
                    <i class="fas fa-user-circle text-xl"></i>
                </div>

                <div id="profile-dropdown" class="absolute top-full right-0 mt-3 w-72 bg-[#1c2128] border border-gray-700 rounded-xl shadow-2xl opacity-0 invisible transform -translate-y-2 transition-all duration-300">
                    <div class="p-4 border-b border-gray-700">
                        <p class="font-semibold text-white"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Utilizador'); ?></p>
                        <a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="text-sm text-gray-400 hover:underline">Ver perfil</a>
                    </div>
                    <ul class="py-2">
                        <li><a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="flex items-center gap-4 px-5 py-3 text-sm hover:bg-gray-800 transition-colors"><i class="fas fa-cog w-5 text-center text-gray-400"></i> Configurações</a></li>
                        <li><a href="#" class="flex items-center gap-4 px-5 py-3 text-sm hover:bg-gray-800 transition-colors"><i class="fas fa-question-circle w-5 text-center text-gray-400"></i> Ajuda</a></li>
                        <li class="border-t border-gray-700 my-2"></li>
                        <li><a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-5 py-3 text-sm text-red-400 hover:bg-gray-800 transition-colors"><i class="fas fa-sign-out-alt w-5 text-center"></i>Sair</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- ==================== CONTEÚDO PRINCIPAL ==================== -->
    <main class="container mx-auto px-4 py-10">

        <!-- Notificações -->
        <div class="mb-10 space-y-4">
            <!-- ==================== CORREÇÃO AQUI ==================== -->
            <!-- A verificação agora usa a variável $showCnpjModal que vem do Controller -->
            <?php if (isset($showCnpjModal) && $showCnpjModal): ?>
                <div class="bg-yellow-500/10 border border-yellow-500/30 text-yellow-300 px-6 py-4 rounded-lg flex flex-col sm:flex-row items-center justify-center gap-4 text-center sm:text-left">
                    <i class="fas fa-exclamation-triangle text-xl flex-shrink-0"></i>
                    <div>
                        <p class="font-semibold">Complete seu perfil para continuar</p>
                        <p class="text-sm">Para cadastrar e gerenciar seus locais, precisamos que valide seu CNPJ.</p>
                    </div>
                    <!-- O link continua a ir para a página de CNPJ, como no seu design original -->
                    <a href="<?php echo BASE_URL; ?>/dashboard/cnpj" class="bg-yellow-400 text-black font-bold py-2 px-4 rounded-lg text-sm transition-colors hover:bg-yellow-300 mt-2 sm:mt-0 flex-shrink-0">Adicionar CNPJ</a>
                </div>
            <?php endif; ?>
            <!-- ==================== FIM DA CORREÇÃO ==================== -->

            <?php if (isset($_GET['status']) && $_GET['status'] === 'cnpj_success'): ?>
                <div class="bg-green-500/10 border border-green-500/30 text-green-300 px-6 py-4 rounded-lg flex items-center gap-4">
                    <i class="fas fa-check-circle text-xl"></i>
                    <span>Seu CNPJ foi validado com sucesso! Agora você pode cadastrar seus locais.</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Seção de Boas-Vindas -->
        <section class="mb-12">
            <h1 class="text-4xl font-bold text-white">Bem-vindo(a), <span class="text-cyan-400"><?php echo explode(' ', htmlspecialchars($userName ?? 'Utilizador'))[0]; ?>!</span></h1>
            <p class="mt-2 text-lg text-gray-400">Gerencie suas informações e locais aqui.</p>
        </section>

        <!-- Seção de Ações Rápidas -->
        <section class="mb-12">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <a href="<?php echo BASE_URL; ?>/dashboard/quadras/criar" class="bg-[#161B22] p-6 rounded-2xl border border-gray-800 flex items-center gap-6 hover:border-green-400 transition-all duration-300 transform hover:-translate-y-1">
                    <div class="bg-green-500/10 p-4 rounded-xl"><i class="fas fa-plus-circle text-3xl text-green-400"></i></div>
                    <div>
                        <h3 class="font-bold text-lg text-white">Cadastrar Novo Local</h3>
                        <p class="text-sm text-gray-400">Adicione uma nova quadra à plataforma.</p>
                    </div>
                </a>
                <a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="bg-[#161B22] p-6 rounded-2xl border border-gray-800 flex items-center gap-6 hover:border-gray-600 transition-all duration-300 transform hover:-translate-y-1">
                    <div class="bg-gray-500/10 p-4 rounded-xl"><i class="fas fa-cog text-3xl text-gray-400"></i></div>
                    <div>
                        <h3 class="font-bold text-lg text-white">Configurações da Conta</h3>
                        <p class="text-sm text-gray-400">Edite suas informações pessoais.</p>
                    </div>
                </a>
            </div>
        </section>

        <!-- Seção de Visão Geral de "Meus Locais" -->
        <section>
            <h2 class="text-2xl font-bold mb-6 text-white">Visão Geral dos Seus Locais</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php if (!empty($userVenues)): ?>
                    <?php foreach ($userVenues as $venue): ?>
                        <div class="group">
                            <a href="<?php echo BASE_URL; ?>/dashboard/quadras/editar/<?php echo $venue['id']; ?>">
                                <div class="relative overflow-hidden rounded-xl">

                                    <?php
                                    // --- ESTE É O BLOCO CORRIGIDO ---
                                    $placeholder = 'https://placehold.co/600x400/161B22/E0E0E0?text=Sem+Imagem';
                                    $imageSrc = $placeholder; // Define o placeholder como padrão

                                    if (!empty($venue['image_path'])) {
                                        // Se existe um image_path, constrói a URL completa e correta
                                        $imageSrc = BASE_URL . '/uploads/venues/' . $venue['id'] . '/' . htmlspecialchars($venue['image_path']);
                                    }
                                    ?>

                                    <img src="<?php echo $imageSrc; ?>"
                                        alt="Foto de <?php echo htmlspecialchars($venue['name']); ?>"
                                        class="w-full h-full object-cover aspect-video transform group-hover:scale-105 transition-transform duration-300"
                                        onerror="this.onerror=null; this.src='<?php echo $placeholder; ?>';">
                                </div>
                                <div class="mt-3">
                                    <h3 class="font-bold text-white truncate"><?php echo htmlspecialchars($venue['name']); ?></h3>
                                    <p class="text-sm text-gray-400 truncate"><?php echo htmlspecialchars($venue['street'] . ', ' . $venue['number']); ?></p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-12 bg-[#161B22] rounded-2xl border border-gray-800">
                        <i class="fas fa-store-slash text-5xl text-gray-600 mb-4"></i>
                        <h3 class="text-xl font-bold text-white">Nenhum local cadastrado</h3>
                        <p class="text-gray-400 mt-2">Parece que você ainda não adicionou nenhuma quadra ou espaço esportivo.</p>
                        <a href="<?php echo BASE_URL; ?>/quadras/criar" class="mt-6 bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-2 px-4 rounded-lg inline-flex items-center transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            Cadastre seu primeiro local
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lógica do menu dropdown do cabeçalho
            const userMenuButton = document.getElementById('user-menu-button');
            const profileDropdown = document.getElementById('profile-dropdown');
            if (userMenuButton) {
                userMenuButton.addEventListener('click', (event) => {
                    event.stopPropagation();
                    profileDropdown.classList.toggle('opacity-0');
                    profileDropdown.classList.toggle('invisible');
                    profileDropdown.classList.toggle('-translate-y-2');
                });
            }
            window.addEventListener('click', (event) => {
                if (profileDropdown && !profileDropdown.classList.contains('invisible')) {
                    if (!profileDropdown.contains(event.target) && !userMenuButton.contains(event.target)) {
                        profileDropdown.classList.add('opacity-0', 'invisible', '-translate-y-2');
                    }
                }
            });
        });
    </script>

</body>

</html>