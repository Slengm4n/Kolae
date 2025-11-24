<?php
require_once __DIR__ . '/../../../Includes/i18n.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma']; ?>">

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

    <link href="<?php echo BASE_URL; ?>/assets/css/style.css?v=" rel="stylesheet">

    <style>
        /* Estilos Críticos */
        html {
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            /* Removi a animação de fade inicial pois ela pode estar ocultando o LCP para o Lighthouse */
            /* animation: pageFadeIn 0.4s ease-out both; */
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

<body class="bg-[#0D1117] text-white">

    <?php
    $isLoggedIn = isset($_SESSION['user_id']);
    $userName = $_SESSION['user_name'] ?? 'Usuário';
    ?>

    <header class="absolute top-0 left-0 w-full z-30 py-6">
        <div class="container mx-auto px-4 flex justify-between items-center">

            <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-10">

            <nav class="hidden md:block">
                <ul class="flex items-center space-x-10">
                    <li><a href="#sobre-nos" class="font-semibold hover:text-cyan-400 transition-colors"><?php echo $lang['global_menu_about']; ?></a></li>


                    <?php if ($isLoggedIn): ?>
                        <li><a href="<?php echo BASE_URL; ?>/dashboard" class="font-bold text-cyan-400 hover:text-cyan-300 transition-colors border border-cyan-400 px-4 py-2 rounded-full"><?php echo $lang['global_home_panel']; ?></a></li>
                    <?php endif; ?>
                </ul>

            </nav>
            <div class="flex item-center gap-6">

            <button id="lang-btn">
                <img src="assets/img/language.png" class="h-10 w-10 transition hover:opacity-60">
            </button>

            <div id="lang-modal"
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 opacity-0 invisible transition-opacity duration-300">

                <div id="lang-box"
                    class="bg-[#1c2128] border border-gray-700 rounded-xl shadow-2xl w-72 p-4 transform scale-90 opacity-0 transition-all duration-300">

                    <h3 class="text-gray-200 text-lg font-semibold mb-3 text-center" ><?php echo $lang['global_button_language']?></h3>

                    <ul class="space-y-2 text-gray-300">

                        <li>
                            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-800 transition-colors" data-lang="pt-br">
                                Português
                            </button>
                        </li>

                        <li>
                            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-800 transition-colors" data-lang="es-es">
                                Español
                            </button>
                        </li>

                        <li>
                            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-800 transition-colors" data-lang="en-us">
                                English
                            </button>
                        </li>

                        <li>
                            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-800 transition-colors" data-lang="zh-cn">
                                普通话
                            </button>
                        </li>

                        <li>
                            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-800 transition-colors" data-lang="hi-in">
                                हिंदी
                            </button>
                        </li>

                        <li>
                            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-800 transition-colors" data-lang="ja-jp">
                                日本
                            </button>
                        </li>

                        <li>
                            <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-800 transition-colors" data-lang="it-it">
                                Italiano
                            </button>
                        </li>

                    </ul>
                </div>
            </div>

                
                <div class="relative">
                    <div id="user-menu-button" class="flex items-center gap-4 p-2 border border-gray-700 rounded-full cursor-pointer transition-colors hover:bg-gray-800 bg-[#0D1117]/80 backdrop-blur-sm">
                        <i class="fas fa-bars text-lg ml-2"></i>

                        <?php if ($isLoggedIn): ?>
                            <?php if (!empty($_SESSION['user_avatar'])): ?>
                                <img src="<?php echo BASE_URL . '/uploads/avatars/' . $_SESSION['user_id'] . '/' . $_SESSION['user_avatar']; ?>"
                                    class="w-8 h-8 rounded-full object-cover border border-gray-600"
                                    alt="Avatar"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm" style="display:none;">
                                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                                </div>
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm">
                                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <i class="fas fa-user-circle text-3xl text-gray-400"></i>
                        <?php endif; ?>
                    </div>



                    <div id="profile-dropdown" class="absolute top-full right-0 mt-4 w-72 bg-[#1c2128] border border-gray-700 rounded-xl shadow-2xl opacity-0 invisible transform -translate-y-2 transition-all duration-300 z-50">
                        <ul class="py-2">

                            <li class="md:hidden"><a href="#sobre-nos" class="flex items-center gap-4 px-5 py-3 text-sm hover:bg-gray-800 transition-colors"><i class="fas fa-info-circle w-5 text-center text-gray-400"></i><?php echo $lang['global_title_about'];?></a></li>

                            <li class="border-t border-gray-700 my-2 md:hidden"></li>

                            <?php if ($isLoggedIn): ?>
                                <li>
                                    <div class="px-5 py-2 text-xs text-gray-500 uppercase font-bold"><?php echo $lang['global_account'] ?></div>
                                </li>
                                <li><a href="<?php echo BASE_URL; ?>/dashboard" class="flex items-center gap-4 px-5 py-3 text-sm hover:bg-gray-800 transition-colors"><i class="fas fa-columns w-5 text-center text-cyan-400"></i> <?php echo $lang['global_home_panel'];?></a></li>
                                <li><a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="flex items-center gap-4 px-5 py-3 text-sm hover:bg-gray-800 transition-colors"><i class="fas fa-user-cog w-5 text-center text-gray-400"></i><?php echo $lang['global_menu_profile'];?></a></li>
                                <li class="border-t border-gray-700 my-2"></li>
                                <li><a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-5 py-3 text-sm text-red-400 hover:bg-gray-800 transition-colors"><i class="fas fa-sign-out-alt w-5 text-center"></i><?php echo $lang['global_menu_exit'];?> </a></li>

                            <?php else: ?>
                                <li><a href="<?php echo BASE_URL; ?>/login" class="flex items-center gap-4 px-5 py-3 text-sm hover:bg-gray-800 transition-colors"><i class="fas fa-sign-in-alt w-5 text-center text-gray-400"></i><?php echo $lang['global_menu_login'];?></a></li>
                                <li><a href="<?php echo BASE_URL; ?>/register" class="flex items-center gap-4 px-5 py-3 text-sm font-bold text-cyan-400 hover:bg-gray-800 transition-colors"><i class="fas fa-user-plus w-5 text-center"></i><?php echo $lang['global_menu_register'];?></a></li>
                            <?php endif; ?>

                        </ul>
                    </div>
                    
                </div>
            </div> 
        </div>
    </header>

    <main>
        <section class="relative h-screen flex items-center justify-center text-center md:justify-start md:text-left p-0 overflow-hidden">

            <video
                autoplay
                muted
                loop
                playsinline
                class="vimeo-bg-cover">

                <source src="<?php echo BASE_URL; ?>/assets/img/hero-bg.mp4" type="video/mp4">

                Seu navegador não suporta vídeos HTML5.
            </video>

            <div class="absolute top-0 left-0 w-full h-full bg-black/60 z-[-1]"></div>

            <div class="container mx-auto px-4 relative z-10">
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold max-w-lg leading-tight mx-auto md:mx-0"><?php echo $lang['global_slogan_headline'];?></h1>
                <div class="flex flex-wrap gap-4 mt-8 justify-center md:justify-start">
                    <a href="<?php echo BASE_URL; ?>/login" class="py-3 px-8 rounded-full font-semibold transition-all duration-300 bg-white text-black border-2 border-white hover:bg-transparent hover:text-white"><?php echo $lang['home_start_free'];?></a>
                </div>
            </div>
        </section>

        <section class="bg-[#161B22] py-16 md:py-24 overflow-hidden">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-3xl md:text-4xl font-bold max-w-2xl mx-auto"><?php echo $lang['home_connect_message'];?></h2>
                <p class="max-w-3xl mx-auto mt-4 text-gray-400"><?php echo $lang['home_search_message'];?></p>
            </div>
            <div class="container mx-auto px-4 mt-12 md:mt-16 pb-10">
                <div class="swiper intro-carousel h-[480px]">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-1">
                            <img src="https://images.pexels.com/photos/47730/the-ball-stadion-football-the-pitch-47730.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Bola de futebol no gramado do estádio" class="w-full h-full object-cover" loading="lazy" decoding="async">
                        </div>
                        <div class="swiper-slide h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-1">
                            <img src="https://images.pexels.com/photos/270085/pexels-photo-270085.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Mulher correndo em trilha" class="w-full h-full object-cover" loading="lazy" decoding="async">
                        </div>
                        <div class="swiper-slide h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-1">
                            <img src="https://images.pexels.com/photos/163452/basketball-dunk-blue-game-163452.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Homem fazendo uma enterrada no basquete" class="w-full h-full object-cover" loading="lazy" decoding="async">
                        </div>
                        <div class="swiper-slide h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-1">
                            <img src="https://images.pexels.com/photos/1263349/pexels-photo-1263349.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Pessoa surfando uma onda" class="w-full h-full object-cover" loading="lazy" decoding="async">
                        </div>
                        <div class="swiper-slide h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-1">
                            <img src="https://images.pexels.com/photos/863988/pexels-photo-863988.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Pessoa nadando em uma piscina" class="w-full h-full object-cover" loading="lazy" decoding="async">
                        </div>
                        <div class="swiper-slide h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-1">
                            <img src="https://images.pexels.com/photos/1080884/pexels-photo-1080884.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Jogadores de vôlei em ação" class="w-full h-full object-cover" loading="lazy" decoding="async">
                        </div>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </section>

        <section id="sobre-nos" class="py-16 md:py-24">
            <div class="container mx-auto px-4 grid lg:grid-cols-2 gap-12 items-center">
                <div class="order-last lg:order-first">
                    <img src="./assets/img/about_us_img.png" alt="Atletas celebrando juntos" class="w-full rounded-xl" loading="lazy" decoding="async">
                </div>
                <div class="text-center lg:text-left">
                    <h2 class="text-3xl md:text-4xl font-bold"><?php echo $lang['global_title_about'];?></h2>
                    <p class="mt-4 text-gray-400"><?php echo $lang['global_text_about'];?></p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-8 justify-center lg:justify-start">
                        <div>
                            <i class="fas fa-users text-cyan-400 text-3xl mb-3"></i>
                            <h3 class="text-lg font-bold"><?php echo $lang['home_title_community'];?></h3>
                            <p class="text-sm text-gray-400"><?php echo $lang['home_text_community'];?></p>
                        </div>
                        <div>
                            <i class="fas fa-map-marker-alt text-cyan-400 text-3xl mb-3"></i>
                            <h3 class="text-lg font-bold"><?php echo $lang['home_title_location'];?></h3>
                            <p class="text-sm text-gray-400"><?php echo $lang['home_text_location'];?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-[#161B22] py-16 md:py-24">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-3xl md:text-4xl font-bold"><?php echo $lang['home_title_supporters'];?></h2>
                <p class="max-w-3xl mx-auto mt-4 text-gray-400"><?php echo $lang['home_text_supporters'];?></p>
            </div>
            <div class="w-full overflow-hidden relative mt-16 [mask-image:linear-gradient(to_right,transparent,black_20%,black_80%,transparent)]">
                <div class="flex animate-scroll hover:[animation-play-state:paused]">
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_fatec.png" alt="Logo Fatec" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a8/Original_Adidas_logo.svg/1280px-Original_Adidas_logo.svg.png" alt="Logo Adidas" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Logo_NIKE.svg/1200px-Logo_NIKE.svg.png" alt="Logo Nike" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_atletica_sagui.png" alt="Logo Atletica Sagui" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_brtz.png" alt="Logo BRTZ" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_leos_de_ferraz.png" alt="Logo Leos de Ferraz" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_fatec.png" alt="Logo Fatec" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a8/Original_Adidas_logo.svg/1280px-Original_Adidas_logo.svg.png" alt="Logo Adidas" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Logo_NIKE.svg/1200px-Logo_NIKE.svg.png" alt="Logo Nike" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_atletica_sagui.png" alt="Logo Atletica Sagui" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_brtz.png" alt="Logo BRTZ" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_leos_de_ferraz.png" alt="Logo Leos de Ferraz" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100" loading="lazy"></div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-gray-800 pt-16 md:pt-20 border-t border-gray-700">
        <div class="container mx-auto px-4 grid md:grid-cols-2 lg:grid-cols-3 gap-12">
            <div class="mb-8 text-center md:text-left">
                <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-10 mx-auto md:mx-0" loading="lazy">
                <p class="text-sm text-gray-400 mt-4"><?php echo $lang['home_footer_activity'];?></p>
                <div class="flex space-x-4 mt-6 justify-center md:justify-start">
                    <a href="#" class="text-xl hover:text-cyan-400 transition-colors" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-xl hover:text-cyan-400 transition-colors" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-xl hover:text-cyan-400 transition-colors" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                </div>
            </div>
            <div class="mb-8 text-center md:text-left">
                <h3 class="text-lg font-semibold mb-4"><?php echo $lang['global_contact_kolae'];?></h3>
                <p class="text-sm text-gray-400"><a href="mailto:kolae.gg@gmail.com" class="hover:text-cyan-400 transition-colors">kolae.gg@gmail.com</a></p>
                <p class="text-sm text-gray-400 mt-2">+55 (11) 99860-0253</p>
            </div>
            <div class="mb-8 text-center md:text-left">
                <h3 class="text-lg font-semibold mb-4"><?php echo $lang['home_footer_activity'];?></h3>
                <p class="text-sm text-gray-400"><?php echo $lang['home_footer_register'];?></p>
                <form class="flex mt-4">
                    <input type="email" class="sr-only" placeholder="Seu melhor e-mail" required class="w-full bg-gray-900 border border-gray-700 rounded-l-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-400">
                    <button type="submit" aria-label="Enviar email" class="bg-cyan-400 text-black font-bold px-4 py-2 rounded-r-md hover:bg-cyan-300 transition-colors"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
        <div class="mt-8 md:mt-12 py-6 border-t border-gray-700 text-center">
            <p class="text-sm text-gray-400">&copy; <?php echo $lang['global_Copyright_message'];?></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>

<script>
    //Função de geração de link de idioma
    function generateLangLink(langCode) {
        let currentUri = window.location.pathname + window.location.search;

        currentUri = currentUri.replace(/(\?|&)?lang=[^&]*/g, '');

        const separator = currentUri.includes('?') ? '&' : '?';

        return currentUri + separator + 'lang=' + langCode;
    }


    document.addEventListener("DOMContentLoaded", function() {

        //Inicialização do Swiper
        var swiper = new Swiper(".intro-carousel", {

            slidesPerView: 1,
            spaceBetween: 30,
            slidesPerGroup: 1,
            loop: true,
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            autoplay: {
                delay: 3500,
                disableOnInteraction: false,
            },
            breakpoints: {
                768: { slidesPerView: 2, slidesPerGroup: 2 },
                1024: { slidesPerView: 3, slidesPerGroup: 3 }
            }
        });



        const langBtn = document.getElementById('lang-btn');
        const langModal = document.getElementById('lang-modal');
        const langBox = document.getElementById('lang-box');


        if (langBtn) {
            langBtn.addEventListener('click', () => {
                langModal.classList.remove('opacity-0', 'invisible');
                langBox.classList.remove('opacity-0', 'scale-90');
            });
        }


        if (langModal && langBox) {
            langModal.addEventListener('click', (e) => {

                if (!langBox.contains(e.target)) {
                    langModal.classList.add('opacity-0', 'invisible');
                    langBox.classList.add('opacity-0', 'scale-90');
                }
            });
        }


        //Lógica de Troca de Idioma
        const langButtons = document.querySelectorAll('#lang-box button');

        langButtons.forEach(button => {
            const langCode = button.getAttribute('data-lang');

            if (langCode) {
                button.addEventListener('click', () => {
                    // Redireciona para a nova URL gerada
                    window.location.href = generateLangLink(langCode);
                });
            }
        });


        // --- 4. Lógica do Dropdown do Usuário
        const userMenuButton = document.getElementById('user-menu-button');
        const profileDropdown = document.getElementById('profile-dropdown');

        if (userMenuButton) {
            userMenuButton.addEventListener('click', (event) => {
                event.stopPropagation();
                profileDropdown.classList.toggle('opacity-0');
                profileDropdown.classList.toggle('invisible');
                profileDropdown.classList.toggle('transform');
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

</html