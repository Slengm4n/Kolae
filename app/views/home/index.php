<?php

$lang = require BASE_PATH . '/includes/i18n.php';

// 4. Dados do Usuário
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma']; ?>" class="transition-colors duration-500">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae</title>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://player.vimeo.com">
    <link rel="preconnect" href="https://i.postimg.cc">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
    <?php include 'app/views/partials/theme_script.php'; ?>
    <style>
        html {
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        @keyframes scroll {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(calc(-240px * 6));
            }
        }

        .animate-scroll {
            animation: scroll 30s linear infinite;
        }

        .swiper-pagination-bullet {
            background: #B0B0B0;
        }

        .swiper-pagination-bullet-active {
            background: #38BDF8;
        }

        .vimeo-bg-cover {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(1.1);
            z-index: -2;
            width: 100vw;
            height: 56.25vw;
            min-height: 100vh;
            min-width: 177.78vh;
            pointer-events: none;
            filter: blur(8px);
        }
    </style>
</head>

<body class="bg-surface-base text-content-primary transition-colors duration-500">

    <button id="theme-toggle" class="fixed top-24 right-4 z-50 p-3 rounded-full bg-surface-elevated text-content-primary shadow-lg border border-gray-700/20 hover:scale-110 transition-transform cursor-pointer">
        <i class="fas fa-sun hidden dark:block text-yellow-400"></i>
        <i class="fas fa-moon block dark:hidden text-blue-600"></i>
    </button>

   <header class="absolute top-0 left-0 w-full z-40 py-6 transition-all duration-300">
    <div class="container mx-auto px-4 flex justify-between items-center">

        <!-- Logo com animação suave de hover -->
        <a href="<?php echo BASE_URL; ?>" aria-label="Página Inicial" class="group">
            <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-10 drop-shadow-md filter dark:filter-none invert dark:invert-0 transition-transform duration-300 group-hover:scale-105">
        </a>

        <!-- Navegação Desktop -->
        <nav class="hidden md:block">
            <ul class="flex items-center space-x-10">
                <li>
                    <!-- Link com brilho suave (glow) no hover -->
                    <a href="#sobre-nos" class="font-semibold text-white/90 hover:text-cyan-400 transition-all duration-300 drop-shadow-md hover:drop-shadow-[0_0_8px_rgba(34,211,238,0.5)]">
                        <?php echo $lang['global_menu_about']; ?>
                    </a>
                </li>

                <?php if ($isLoggedIn): ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/dashboard" class="font-bold text-cyan-400 border border-cyan-400/70 px-5 py-2.5 rounded-full bg-cyan-400/10 backdrop-blur-md hover:bg-cyan-400 hover:text-black hover:shadow-[0_0_15px_rgba(34,211,238,0.5)] transition-all duration-300">
                            <?php echo $lang['global_home_panel']; ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="flex items-center gap-4">

            <!-- Botão de Idioma -->
            <div class="relative z-50"> 
                <button id="lang-btn" aria-haspopup="dialog" aria-expanded="false" class="flex items-center justify-center w-11 h-11 rounded-full bg-surface-elevated/40 backdrop-blur-md border border-gray-700/50 hover:bg-surface-elevated hover:border-cyan-400/50 hover:text-cyan-400 transition-all duration-300 text-white shadow-lg">
                    <i class="fas fa-globe text-lg transition-transform duration-300 hover:rotate-12"></i>
                </button>

                <!-- Modal de Idioma -->
                <div id="lang-modal" role="dialog" aria-modal="true" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 opacity-0 invisible transition-all duration-300 ease-in-out">
                    <!-- Fundo Glassmorphism na caixa -->
                    <div id="lang-box" class="bg-[#1e293b]/95 backdrop-blur-xl border border-gray-700/50 text-white rounded-2xl shadow-2xl w-72 p-6 transform scale-95 opacity-0 transition-all duration-300 ease-in-out">

                        <div class="flex justify-between items-center mb-5">
                            <h3 class="text-lg font-bold">Selecione o Idioma</h3>
                            <button id="close-lang-btn" aria-label="Fechar" class="text-gray-400 hover:text-red-500 bg-gray-800/50 hover:bg-gray-800 rounded-full w-8 h-8 flex items-center justify-center transition-all duration-200">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <ul class="space-y-2 font-medium max-h-[60vh] overflow-y-auto pr-1 custom-scrollbar">
                            <!-- Efeito de deslizar (translate-x) no hover dos itens -->
                            <li>
                                <a href="?lang=pt-br" class="flex items-center gap-3 w-full text-left px-4 py-3 rounded-xl bg-gray-800/40 hover:bg-cyan-500/10 hover:border-cyan-500/30 border border-transparent hover:text-cyan-400 transition-all duration-300 hover:translate-x-1">
                                    <span class="text-2xl drop-shadow-md">🇧🇷</span> Português
                                </a>
                            </li>
                            <li>
                                <a href="?lang=en-us" class="flex items-center gap-3 w-full text-left px-4 py-3 rounded-xl bg-gray-800/40 hover:bg-cyan-500/10 hover:border-cyan-500/30 border border-transparent hover:text-cyan-400 transition-all duration-300 hover:translate-x-1">
                                    <span class="text-2xl drop-shadow-md">🇺🇸</span> English
                                </a>
                            </li>
                            <li>
                                <a href="?lang=es-es" class="flex items-center gap-3 w-full text-left px-4 py-3 rounded-xl bg-gray-800/40 hover:bg-cyan-500/10 hover:border-cyan-500/30 border border-transparent hover:text-cyan-400 transition-all duration-300 hover:translate-x-1">
                                    <span class="text-2xl drop-shadow-md">🇪🇸</span> Español
                                </a>
                            </li>
                            <li>
                                <a href="?lang=hi-in" class="flex items-center gap-3 w-full text-left px-4 py-3 rounded-xl bg-gray-800/40 hover:bg-cyan-500/10 hover:border-cyan-500/30 border border-transparent hover:text-cyan-400 transition-all duration-300 hover:translate-x-1">
                                    <span class="text-2xl drop-shadow-md border border-gray-700 bg-gray-800 rounded px-1 text-sm">HI</span> मानक हिन्दी
                                </a>
                            </li>
                            <li>
                                <a href="?lang=zh-cn" class="flex items-center gap-3 w-full text-left px-4 py-3 rounded-xl bg-gray-800/40 hover:bg-cyan-500/10 hover:border-cyan-500/30 border border-transparent hover:text-cyan-400 transition-all duration-300 hover:translate-x-1">
                                    <span class="text-2xl drop-shadow-md border border-gray-700 bg-gray-800 rounded px-1 text-sm">ZH</span> 官话
                                </a>
                            </li>
                            <li>
                                <a href="?lang=it-it" class="flex items-center gap-3 w-full text-left px-4 py-3 rounded-xl bg-gray-800/40 hover:bg-cyan-500/10 hover:border-cyan-500/30 border border-transparent hover:text-cyan-400 transition-all duration-300 hover:translate-x-1">
                                    <span class="text-2xl drop-shadow-md border border-gray-700 bg-gray-800 rounded px-1 text-sm">IT</span> Italiano
                                </a>
                            </li>
                            <li>
                                <a href="?lang=ja-jp" class="flex items-center gap-3 w-full text-left px-4 py-3 rounded-xl bg-gray-800/40 hover:bg-cyan-500/10 hover:border-cyan-500/30 border border-transparent hover:text-cyan-400 transition-all duration-300 hover:translate-x-1">
                                    <span class="text-2xl drop-shadow-md border border-gray-700 bg-gray-800 rounded px-1 text-sm">JA</span> 日本語
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Botão do Usuário -->
            <div class="relative">
                <button id="user-menu-button" aria-haspopup="true" aria-expanded="false" class="flex items-center gap-3 p-1.5 pr-3 border border-gray-700/50 rounded-full cursor-pointer transition-all duration-300 hover:bg-surface-elevated hover:border-cyan-400/50 bg-surface-elevated/40 backdrop-blur-md text-white shadow-lg group">
                    <i class="fas fa-bars text-[15px] pl-2 text-gray-300 group-hover:text-cyan-400 transition-colors"></i>

                    <?php if ($isLoggedIn): ?>
                        <?php if (!empty($_SESSION['user_avatar'])): ?>
                            <img src="<?php echo BASE_URL . '/uploads/avatars/' . $_SESSION['user_id'] . '/' . $_SESSION['user_avatar']; ?>"
                                class="w-8 h-8 rounded-full object-cover border-2 border-gray-600 group-hover:border-cyan-400 transition-colors"
                                alt="Avatar"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm shadow-inner" style="display:none;">
                                <?php echo strtoupper(substr($userName, 0, 1)); ?>
                            </div>
                        <?php else: ?>
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm shadow-inner">
                                <?php echo strtoupper(substr($userName, 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <i class="fas fa-user-circle text-3xl text-gray-400 group-hover:text-cyan-400 transition-colors"></i>
                    <?php endif; ?>
                </button>

                <!-- Dropdown de Perfil -->
                <div id="profile-dropdown" class="absolute top-full right-0 mt-3 w-64 bg-[#1e293b]/95 backdrop-blur-xl border border-gray-700/50 rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.5)] opacity-0 invisible transform -translate-y-2 transition-all duration-300 z-50 overflow-hidden">
                    <ul class="py-2 text-content-primary">

                        <!-- Item visível apenas no Mobile -->
                        <li class="md:hidden">
                            <a href="#sobre-nos" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-cyan-500/10 hover:text-cyan-400 transition-colors">
                                <i class="fas fa-info-circle w-5 text-center opacity-70"></i> <?php echo $lang['global_menu_about']; ?>
                            </a>
                        </li>

                        <li class="border-t border-gray-700/50 my-1 md:hidden"></li>

                        <?php if ($isLoggedIn): ?>
                            <li>
                                <div class="px-5 py-2 text-xs text-gray-400 uppercase font-bold tracking-wider"><?php echo $lang['global_account']; ?></div>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>/dashboard" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-cyan-500/10 transition-colors">
                                    <i class="fas fa-columns w-5 text-center text-cyan-400"></i> <span class="font-medium"><?php echo $lang['global_home_panel']; ?></span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-cyan-500/10 hover:text-cyan-400 transition-colors">
                                    <i class="fas fa-user-cog w-5 text-center opacity-70"></i> <?php echo $lang['global_menu_profile']; ?>
                                </a>
                            </li>
                            <li class="border-t border-gray-700/50 my-1"></li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-3 px-5 py-3 text-sm text-red-400 hover:bg-red-500/10 transition-colors">
                                    <i class="fas fa-sign-out-alt w-5 text-center"></i> <?php echo $lang['global_menu_exit']; ?>
                                </a>
                            </li>

                        <?php else: ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>/login" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-cyan-500/10 hover:text-cyan-400 transition-colors">
                                    <i class="fas fa-sign-in-alt w-5 text-center opacity-70"></i> <?php echo $lang['global_menu_login']; ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>/register" class="flex items-center gap-3 px-5 py-3 text-sm font-bold text-cyan-400 hover:bg-cyan-500/10 transition-colors">
                                    <i class="fas fa-user-plus w-5 text-center"></i> <?php echo $lang['global_menu_register']; ?>
                                </a>
                            </li>
                        <?php endif; ?>

                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>

    <main>
        <section class="relative h-screen flex items-center justify-center text-center md:justify-start md:text-left p-0 overflow-hidden">

            <video autoplay muted loop playsinline class="vimeo-bg-cover">
                <source src="<?php echo BASE_URL; ?>/assets/img/hero-bg.mp4" type="video/mp4">
            </video>

            <div class="absolute top-0 left-0 w-full h-full bg-black/60 z-[-1]"></div>

            <div class="container mx-auto px-4 relative z-10 text-white">
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold max-w-lg leading-tight mx-auto md:mx-0">
                    <?php echo $lang['global_slogan_headline']; ?>
                </h1>
                <div class="flex flex-wrap gap-4 mt-8 justify-center md:justify-start">
                    <a href="<?php echo BASE_URL; ?>/login" class="py-3 px-8 rounded-full font-semibold transition-all duration-300 bg-white text-black border-2 border-white hover:bg-transparent hover:text-white">
                        <?php echo $lang['home_start_free']; ?>
                    </a>
                </div>
            </div>
        </section>

      <section class="bg-surface-secondary py-16 md:py-24 overflow-hidden transition-colors duration-500">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl md:text-4xl font-bold max-w-2xl mx-auto text-content-primary">
            <?php echo $lang['home_connect_message']; ?>
        </h2>
        <p class="max-w-3xl mx-auto mt-4 text-content-secondary">
            <?php echo $lang['home_search_message']; ?>
        </p>
    </div>
    
    <div class="container mx-auto px-4 mt-12 md:mt-16 pb-10">
        <div class="swiper intro-carousel">
            <!-- Adicionado py-4 para dar espaço para o efeito de scale não ser cortado -->
            <div class="swiper-wrapper py-4">
                
                <!-- Slide 1 -->
                <div class="swiper-slide">
                    <!-- O efeito de hover fica na div interna -->
                    <div class="h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-2 shadow-lg cursor-pointer">
                        <img src="https://images.pexels.com/photos/47730/the-ball-stadion-football-the-pitch-47730.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Futebol" class="w-full h-full object-cover" loading="lazy">
                    </div>
                </div>

                <!-- Slide 2 -->
                <div class="swiper-slide">
                    <div class="h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-2 shadow-lg cursor-pointer">
                        <img src="https://images.pexels.com/photos/270085/pexels-photo-270085.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Estádio" class="w-full h-full object-cover" loading="lazy">
                    </div>
                </div>

                <!-- Slide 3 -->
                <div class="swiper-slide">
                    <div class="h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-2 shadow-lg cursor-pointer">
                        <img src="https://images.pexels.com/photos/163452/basketball-dunk-blue-game-163452.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Basquete" class="w-full h-full object-cover" loading="lazy">
                    </div>
                </div>

                <!-- Slide 4 -->
                <div class="swiper-slide">
                    <div class="h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-2 shadow-lg cursor-pointer">
                        <img src="https://images.pexels.com/photos/1263349/pexels-photo-1263349.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Vôlei" class="w-full h-full object-cover" loading="lazy">
                    </div>
                </div>

                <!-- Slide 5 -->
                <div class="swiper-slide">
                    <div class="h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-2 shadow-lg cursor-pointer">
                        <img src="https://images.pexels.com/photos/863988/pexels-photo-863988.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Natação" class="w-full h-full object-cover" loading="lazy">
                    </div>
                </div>

                <!-- Slide 6 -->
                <div class="swiper-slide">
                    <div class="h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-2 shadow-lg cursor-pointer">
                        <img src="https://images.pexels.com/photos/1080884/pexels-photo-1080884.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Esportes diversos" class="w-full h-full object-cover" loading="lazy">
                    </div>
                </div>

            </div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
</section>
        <section id="sobre-nos" class="py-16 md:py-24 bg-surface-base text-content-primary transition-colors duration-500">
    <div class="container mx-auto px-4 grid lg:grid-cols-2 gap-12 items-center">
        
        <!-- Coluna da Imagem -->
        <div class="order-last lg:order-first relative group">
            <!-- Efeito de brilho suave atrás da imagem (opcional, dá um toque moderno) -->
            <div class="absolute inset-0 bg-cyan-400/20 blur-3xl rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
            
            <!-- Caminho corrigido com BASE_URL e animação de scale suave -->
            <img src="<?php echo BASE_URL; ?>/assets/img/about_us_img.png" alt="Ilustração da equipe Kolae" class="w-full rounded-xl shadow-lg relative z-10 transition-transform duration-500 hover:scale-[1.02]" loading="lazy">
        </div>
        
        <!-- Coluna de Texto -->
        <div class="text-center lg:text-left">
            <h2 class="text-3xl md:text-4xl font-bold"><?php echo $lang['global_title_about']; ?></h2>
            <p class="mt-4 text-content-secondary leading-relaxed"><?php echo $lang['global_text_about']; ?></p>

            <!-- Grid de Recursos (Transformados em Cards Suaves) -->
            <div class="mt-8 flex flex-col sm:flex-row gap-6 justify-center lg:justify-start">
                
                <!-- Card Comunidade -->
                <div class="flex-1 p-6 rounded-2xl bg-surface-elevated/40 border border-gray-700/30 hover:border-cyan-400/50 hover:bg-surface-elevated/60 hover:-translate-y-1 transition-all duration-300 group cursor-default">
                    <!-- Círculo com o ícone -->
                    <div class="w-12 h-12 rounded-full bg-cyan-400/10 flex items-center justify-center mb-4 mx-auto lg:mx-0 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-users text-cyan-400 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-2"><?php echo $lang['home_title_community']; ?></h3>
                    <p class="text-sm text-content-secondary"><?php echo $lang['home_text_community']; ?></p>
                </div>
                
                <!-- Card Localização -->
                <div class="flex-1 p-6 rounded-2xl bg-surface-elevated/40 border border-gray-700/30 hover:border-cyan-400/50 hover:bg-surface-elevated/60 hover:-translate-y-1 transition-all duration-300 group cursor-default">
                    <!-- Círculo com o ícone -->
                    <div class="w-12 h-12 rounded-full bg-cyan-400/10 flex items-center justify-center mb-4 mx-auto lg:mx-0 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-map-marker-alt text-cyan-400 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-2"><?php echo $lang['home_title_location']; ?></h3>
                    <p class="text-sm text-content-secondary"><?php echo $lang['home_text_location']; ?></p>
                </div>

            </div>
        </div>
        
    </div>
</section>
        <section class="bg-surface-secondary py-16 md:py-24 transition-colors duration-500">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-content-primary"><?php echo $lang['home_title_supporters']; ?></h2>
        <p class="max-w-3xl mx-auto mt-4 text-content-secondary"><?php echo $lang['home_text_supporters']; ?></p>
    </div>

    <!-- Container com o efeito de máscara (fade nas bordas) -->
    <div class="w-full overflow-hidden relative mt-16 [mask-image:linear-gradient(to_right,transparent,black_20%,black_80%,transparent)]">
        
        <!-- Adicionado 'w-max' aqui. Isso é essencial para o cálculo do translate(-50%) funcionar -->
        <div class="flex w-max animate-scroll hover:[animation-play-state:paused]">
            
            <!-- Logo Fatec (1) -->
            <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4">
                <img src="<?php echo BASE_URL; ?>/assets/img/logo_fatec.png" alt="Logo Fatec" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy">
            </div>
            <!-- Logo Adidas (2) -->
            <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a8/Original_Adidas_logo.svg/1280px-Original_Adidas_logo.svg.png" alt="Logo Adidas" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy">
            </div>
            <!-- Logo Nike (3) -->
            <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Logo_NIKE.svg/1200px-Logo_NIKE.svg.png" alt="Logo Nike" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy">
            </div>
            <!-- Logo Atletica (4) -->
            <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4">
                <img src="<?php echo BASE_URL; ?>/assets/img/logo_atletica_sagui.png" alt="Logo Atletica Sagui" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy">
            </div>
            <!-- Logo BRTZ (5) -->
            <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4">
                <img src="<?php echo BASE_URL; ?>/assets/img/logo_brtz.png" alt="Logo BRTZ" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy">
            </div>
            <!-- Logo Leos (6) -->
            <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4">
                <img src="<?php echo BASE_URL; ?>/assets/img/logo_leos_de_ferraz.png" alt="Logo Leos de Ferraz" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy">
            </div>

            <!-- ========================================== -->
            <!-- CÓPIAS EXATAS PARA O LOOP INFINITO (7 a 12)  -->
            <!-- ========================================== -->
            
            <!-- Logo Fatec (7) -->
            <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4">
                <img src="<?php echo BASE_URL; ?>/assets/img/logo_fatec.png" alt="Logo Fatec" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy">
            </div>
            <!-- Logo Adidas (8) -->
            <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a8/Original_Adidas_logo.svg/1280px-Original_Adidas_logo.svg.png" alt="Logo Adidas" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy">
            </div>
            <!-- Logo Nike (9) -->
            <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Logo_NIKE.svg/1200px-Logo_NIKE.svg.png" alt="Logo Nike" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy">
            </div>
            <!-- Logo Atletica (10) -->
            <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4">
                <img src="<?php echo BASE_URL; ?>/assets/img/logo_atletica_sagui.png" alt="Logo Atletica Sagui" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy">
            </div>
            <!-- Logo BRTZ (11) -->
            <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4">
                <img src="<?php echo BASE_URL; ?>/assets/img/logo_brtz.png" alt="Logo BRTZ" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy">
            </div>
            <!-- Logo Leos (12) -->
            <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4">
                <img src="<?php echo BASE_URL; ?>/assets/img/logo_leos_de_ferraz.png" alt="Logo Leos de Ferraz" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy">
            </div>

        </div>
    </div>
</section>
    </main>

<footer class="bg-surface-elevated pt-16 md:pt-20 border-t border-gray-700/30 transition-colors duration-500">
        <div class="container mx-auto px-4 grid md:grid-cols-2 lg:grid-cols-3 gap-12">
            
            <!-- Coluna 1: Logo e Redes Sociais -->
            <div class="mb-8 text-center md:text-left">
                <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-10 mx-auto md:mx-0 filter dark:filter-none invert dark:invert-0" loading="lazy">
                <p class="text-sm text-content-secondary mt-4"><?php echo $lang['home_footer_activity']; ?></p>
                <div class="flex space-x-4 mt-6 justify-center md:justify-start text-content-primary">
                    <a href="#" class="text-xl hover:text-cyan-400 transition-colors" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-xl hover:text-cyan-400 transition-colors" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-xl hover:text-cyan-400 transition-colors" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                </div>
            </div>

            <!-- Coluna 2: Contato -->
            <div class="mb-8 text-center md:text-left">
                <h3 class="text-lg font-semibold mb-4 text-content-primary"><?php echo $lang['global_contact_kolae']; ?></h3>
                <p class="text-sm text-content-secondary"><a href="mailto:kolae.gg@gmail.com" class="hover:text-cyan-400 transition-colors">kolae.gg@gmail.com</a></p>
                <p class="text-sm text-content-secondary mt-2">+55 (11) 99860-0253</p>
            </div>

            <!-- Coluna 3: Newsletter -->
            <div class="mb-8 text-center md:text-left">
                <h3 class="text-lg font-semibold mb-4 text-content-primary"><?php echo $lang['home_footer_register']; ?></h3>
                <p class="text-sm text-content-secondary">Cadastre-se para ficar por dentro dos próximos eventos e atualizações.</p>
                
                <form class="flex mt-4">
                    <!-- Label adicionada para acessibilidade (leitores de tela) -->
                    <label for="newsletter-email" class="sr-only">Seu melhor e-mail</label>
                    
                    <!-- Input corrigido: sem duplicação de 'class' e visível na tela -->
                    <input type="email" id="newsletter-email" placeholder="Seu melhor e-mail" required class="w-full bg-surface-base text-content-primary border border-gray-700 rounded-l-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-400 transition-colors">
                    
                    <button type="submit" aria-label="Enviar email" class="bg-cyan-400 text-black font-bold px-4 py-2 rounded-r-md hover:bg-cyan-300 transition-colors">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>

        </div>

        <!-- Copyright -->
        <div class="mt-8 md:mt-12 py-6 border-t border-gray-700/30 text-center">
            <p class="text-sm text-content-secondary">&copy; <?php echo $lang['global_Copyright_message']; ?></p>
        </div>
    </footer>

    <script type="module" src="<?php echo BASE_URL; ?>/assets/js/bundle.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const langBtn = document.getElementById('lang-btn');
            const langModal = document.getElementById('lang-modal');
            const langBox = document.getElementById('lang-box');
            const closeLangBtn = document.getElementById('close-lang-btn');

            // Função para ABRIR o modal suavemente
            function openModal() {
                // 1. Mostra o container do fundo
                langModal.classList.remove('invisible', 'opacity-0');
                langModal.classList.add('visible', 'opacity-100');

                // 2. Faz a caixa dar "zoom in" e aparecer
                // Um pequeno timeout garante que a transição ocorra após o container ficar visível
                setTimeout(() => {
                    langBox.classList.remove('scale-95', 'opacity-0');
                    langBox.classList.add('scale-100', 'opacity-100');
                }, 10);
            }

            // Função para FECHAR o modal suavemente
            function closeModal() {
                // 1. Faz a caixa dar "zoom out" e desaparecer
                langBox.classList.remove('scale-100', 'opacity-100');
                langBox.classList.add('scale-95', 'opacity-0');

                // 2. Esconde o container do fundo após a animação da caixa terminar (300ms)
                setTimeout(() => {
                    langModal.classList.remove('visible', 'opacity-100');
                    langModal.classList.add('invisible', 'opacity-0');
                }, 300); // Esse tempo deve bater com o 'duration-300' do CSS
            }


            if (langBtn && langModal && langBox) {
                // Evento do botão do globo
                langBtn.addEventListener('click', (e) => {
                    e.stopPropagation(); // Evita que o clique feche o modal imediatamente
                    openModal();
                });

                // Evento do botão "X"
                if (closeLangBtn) {
                    closeLangBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        closeModal();
                    });
                }

                // Fechar clicando fora (no fundo escuro)
                langModal.addEventListener('click', (e) => {
                    // Verifica se o clique foi no fundo escuro e não dentro da caixa
                    if (!langBox.contains(e.target)) {
                        closeModal();
                    }
                });
                // Fechar com a tecla ESC
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && langModal.classList.contains('visible')) {
                        closeModal();
                    }
                });
            }
        });
    </script>
</body>

</html>