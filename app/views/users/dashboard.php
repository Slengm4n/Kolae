<?php
$userName = $data['userName'] ?? 'Utilizador';
$userVenues = $data['userVenues'] ?? [];
$showCnpjModal = $data['showCnpjModal'] ?? false;
?>
<!DOCTYPE html>
<html lang="pt-BR" class="transition-colors duration-500">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kolae</title>

    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">

    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <?php include 'app/views/partials/theme_script.php'; ?>
    <style>
        .animate-up {
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .delay-100 {
            animation-delay: 100ms;
        }

        .delay-200 {
            animation-delay: 200ms;
        }

        .delay-300 {
            animation-delay: 300ms;
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

<body class="bg-surface-base text-content-primary font-poppins antialiased min-h-screen flex flex-col transition-colors duration-500">

    <header class="bg-surface-elevated/80 backdrop-blur-md border-b border-content-secondary/10 sticky top-0 z-40 py-4 transition-all">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <a href="<?php echo BASE_URL; ?>/" class="flex items-center gap-2 group">
                <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo" class="h-8 w-auto filter dark:filter-none invert dark:invert-0 opacity-90 group-hover:opacity-100 transition-opacity">
            </a>

            <nav class="hidden md:flex items-center space-x-8">
                <a href="<?php echo BASE_URL; ?>/dashboard" class="font-semibold text-cyan-600 dark:text-cyan-400 relative after:content-[''] after:absolute after:-bottom-1 after:left-0 after:w-full after:h-0.5 after:bg-cyan-600 dark:after:bg-cyan-400 after:scale-x-100 transition-transform">Meu Painel</a>
            </nav>

            <div class="relative">
                <button id="user-menu-button" class="flex items-center gap-3 p-1.5 pr-3 border border-content-secondary/20 rounded-full cursor-pointer transition-all hover:bg-surface-secondary hover:border-content-secondary/40">
                    <?php if (!empty($_SESSION['user_avatar'])): ?>
                        <img src="<?php echo BASE_URL . '/uploads/avatars/' . $_SESSION['user_id'] . '/' . $_SESSION['user_avatar']; ?>"
                            class="w-8 h-8 rounded-full object-cover border border-content-secondary/20"
                            alt="Avatar"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm" style="display:none;">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                        </div>
                    <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                        </div>
                    <?php endif; ?>

                    <i class="fas fa-chevron-down text-xs text-content-secondary"></i>
                </button>

                <div id="profile-dropdown" class="absolute top-full right-0 mt-3 w-64 bg-surface-elevated border border-content-secondary/10 rounded-xl shadow-xl opacity-0 invisible transform -translate-y-2 transition-all duration-200 z-50 overflow-hidden">
                    <div class="p-4 border-b border-content-secondary/10 bg-surface-secondary/30">
                        <p class="font-semibold text-content-primary truncate"><?php echo htmlspecialchars($userName); ?></p>
                        <a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="text-xs text-cyan-600 dark:text-cyan-400 hover:underline">Ver perfil completo</a>
                    </div>
                    <ul class="py-2 text-sm text-content-secondary">
                        <li><a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="flex items-center gap-3 px-5 py-3 hover:bg-surface-secondary hover:text-content-primary transition-colors"><i class="fas fa-cog w-4 text-center"></i> Configurações</a></li>
                        <li><a href="#" class="flex items-center gap-3 px-5 py-3 hover:bg-surface-secondary hover:text-content-primary transition-colors"><i class="fas fa-question-circle w-4 text-center"></i> Ajuda</a></li>
                        <li class="border-t border-content-secondary/10 my-2"></li>
                        <li>
                            <button id="theme-toggle" class="w-full flex items-center gap-3 px-5 py-3 hover:bg-surface-secondary hover:text-content-primary transition-colors text-left">
                                <i class="fas fa-moon w-4 text-center"></i>
                                <span class="dark:hidden">Modo Escuro</span>
                                <span class="hidden dark:inline">Modo Claro</span>
                            </button>
                        </li>
                        <li><a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-3 px-5 py-3 text-red-500 hover:bg-red-500/10 transition-colors"><i class="fas fa-sign-out-alt w-4 text-center"></i> Sair</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8 md:py-12 max-w-7xl flex-grow">

        <div class="mb-8 animate-up">
            <?php if ($showCnpjModal): ?>
                <div class="bg-yellow-500/10 border border-yellow-500/30 text-yellow-700 dark:text-yellow-200 p-4 md:p-6 rounded-2xl flex flex-col md:flex-row items-center justify-between gap-4 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-yellow-500/20 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-2xl text-yellow-800 dark:text-yellow-400"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-yellow-950 dark:text-yellow-100 mb-1">Complete seu perfil</h3>
                            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Valide seu CNPJ para começar a cadastrar e gerenciar seus locais.</p>
                        </div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/dashboard/cnpj" class="w-full md:w-auto bg-yellow-500 hover:bg-yellow-400 text-white dark:text-black font-bold py-2.5 px-6 rounded-xl text-center transition-all hover:shadow-lg hover:-translate-y-0.5 whitespace-nowrap">
                        Adicionar CNPJ
                    </a>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['status']) && $_GET['status'] === 'cnpj_success'): ?>
                <div class="bg-green-500/10 border border-green-500/30 text-green-700 dark:text-green-300 p-4 rounded-2xl flex items-center gap-3 shadow-sm">
                    <i class="fas fa-check-circle text-xl"></i>
                    <span>Parabéns! Seu CNPJ foi validado. Você já pode cadastrar locais.</span>
                </div>
            <?php endif; ?>
        </div>

        <section class="mb-12 animate-up delay-100">
            <h1 class="text-3xl md:text-4xl font-bold text-content-primary mb-2">
                Olá, <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-500 to-blue-600"><?php echo explode(' ', htmlspecialchars($userName))[0]; ?>!</span>
            </h1>
            <p class="text-content-secondary">Aqui está o resumo das suas atividades hoje.</p>
        </section>

        <section class="mb-16 animate-up delay-200">
            <h2 class="text-xl font-semibold text-content-primary mb-6 flex items-center gap-2"><i class="fas fa-bolt text-yellow-500"></i> Ações Rápidas</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <a href="<?php echo BASE_URL; ?>/dashboard/quadras/criar" class="group bg-surface-elevated p-6 rounded-2xl border border-content-secondary/10 hover:border-cyan-500/50 hover:shadow-lg hover:shadow-cyan-500/5 transition-all duration-300 hover:-translate-y-1 flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-cyan-500/10 flex items-center justify-center text-cyan-600 dark:text-cyan-400 group-hover:bg-cyan-500 group-hover:text-white transition-colors">
                        <i class="fas fa-plus text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-content-primary group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">Novo Local</h3>
                        <p class="text-sm text-content-secondary">Cadastre uma nova quadra</p>
                    </div>
                    <i class="fas fa-arrow-right ml-auto text-content-secondary group-hover:text-cyan-500 group-hover:translate-x-1 transition-all"></i>
                </a>

                <a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="group bg-surface-elevated p-6 rounded-2xl border border-content-secondary/10 hover:border-purple-500/50 hover:shadow-lg hover:shadow-purple-500/5 transition-all duration-300 hover:-translate-y-1 flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-purple-500/10 flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:bg-purple-500 group-hover:text-white transition-colors">
                        <i class="fas fa-user-cog text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-content-primary group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">Minha Conta</h3>
                        <p class="text-sm text-content-secondary">Gerenciar dados e senha</p>
                    </div>
                    <i class="fas fa-arrow-right ml-auto text-content-secondary group-hover:text-purple-500 group-hover:translate-x-1 transition-all"></i>
                </a>
            </div>
        </section>

        <section class="animate-up delay-300">
            <div class="flex justify-between items-end mb-6">
                <h2 class="text-2xl font-bold text-content-primary flex items-center gap-2"><i class="fas fa-map-marked-alt text-cyan-500"></i> Meus Locais</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php if (!empty($userVenues)): ?>
                    <?php foreach ($userVenues as $venue): ?>
                        <?php
                        // Placeholders também precisam se adaptar ou serem neutros
                        $placeholder = 'https://placehold.co/600x400/f1f5f9/94a3b8?text=Sem+Imagem';
                        $imageSrc = !empty($venue['image_path'])
                            ? BASE_URL . '/uploads/venues/' . $venue['id'] . '/' . htmlspecialchars($venue['image_path'])
                            : $placeholder;
                        ?>
                        <a href="<?php echo BASE_URL; ?>/dashboard/quadras/editar/<?php echo $venue['id']; ?>" class="group block bg-surface-elevated rounded-2xl overflow-hidden border border-content-secondary/10 hover:border-content-secondary/30 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                            <div class="relative aspect-video overflow-hidden">
                                <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($venue['name']); ?>"
                                    class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700"
                                    onerror="this.onerror=null; this.src='<?php echo $placeholder; ?>';">

                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-60"></div>

                                <div class="absolute top-3 right-3 bg-white/90 dark:bg-black/60 backdrop-blur-sm px-2 py-1 rounded-lg text-xs font-bold text-black dark:text-white border border-white/20 shadow-sm">
                                    <i class="fas fa-pencil-alt mr-1"></i> Editar
                                </div>
                            </div>
                            <div class="p-5">
                                <h3 class="font-bold text-lg text-content-primary truncate group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors"><?php echo htmlspecialchars($venue['name']); ?></h3>
                                <div class="flex items-center gap-2 mt-2 text-content-secondary text-sm">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span class="truncate"><?php echo htmlspecialchars($venue['street'] . ', ' . $venue['number']); ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full py-16 bg-surface-secondary/30 border-2 border-dashed border-content-secondary/20 rounded-3xl text-center flex flex-col items-center justify-center group hover:border-cyan-500/50 transition-colors">
                        <div class="w-20 h-20 bg-surface-elevated rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300 shadow-sm">
                            <i class="fas fa-store-slash text-4xl text-content-secondary group-hover:text-cyan-500 transition-colors"></i>
                        </div>
                        <h3 class="text-xl font-bold text-content-primary">Nenhum local cadastrado</h3>
                        <p class="text-content-secondary mt-2 mb-6 max-w-sm">Você ainda não tem quadras ou espaços esportivos registrados.</p>
                        <a href="<?php echo BASE_URL; ?>/dashboard/quadras/criar" class="bg-cyan-500 hover:bg-cyan-400 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-cyan-500/20 transition-all hover:-translate-y-0.5">
                            <i class="fas fa-plus mr-2"></i> Cadastrar Primeiro Local
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <script type="module" src="<?php echo BASE_URL; ?>/assets/js/bundle.js"></script>

</body>

</html>