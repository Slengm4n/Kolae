<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Estilos customizados que não são facilmente replicáveis com classes de utilidade */

        /* Adiciona a regra de overflow ao HTML e o scroll suave */
        html {
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            animation: pageFadeIn 0.4s ease-out both;
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
            transform: translate(-50%, -50%);
            z-index: -2;
            width: 100vw;
            height: 56.25vw;
            min-height: 100vh;
            min-width: 177.78vh;

            @keyframes pageFadeIn {
                from {
                    opacity: 0;
                }

                to {
                    opacity: 1;
                }
            }
        }
    </style>
</head>

<body class="bg-[#0D1117] text-white">

    <!-- ==================== CABEÇALHO ==================== -->
    <header class="absolute top-0 left-0 w-full z-30 py-6">
        <div class="container mx-auto px-4 flex justify-between items-center">

            <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-10">

            <nav class="hidden md:block">
                <ul class="flex items-center space-x-10">
                    <li><a href="#encontrar" class="font-semibold hover:text-cyan-400 transition-colors">Encontrar</a></li>
                    <li><a href="#sobre-nos" class="font-semibold hover:text-cyan-400 transition-colors">Sobre Nós</a></li>
                </ul>
            </nav>
            <div class="relative">
                <div id="user-menu-button" class="flex items-center gap-4 p-2 border border-gray-700 rounded-full cursor-pointer transition-colors hover:bg-gray-800">
                    <i class="fas fa-bars text-lg"></i>
                    <i class="fas fa-user-circle text-lg"></i>
                </div>

                <div id="profile-dropdown" class="absolute top-full right-0 mt-4 w-72 bg-[#1c2128] border border-gray-700 rounded-xl shadow-2xl opacity-0 invisible transform -translate-y-2 transition-all duration-300">
                    <ul class="py-2">
                        <li class="md:hidden"><a href="#sobre-nos" class="flex items-center gap-4 px-5 py-3 text-sm hover:bg-gray-800 transition-colors"><i class="fas fa-info-circle w-5 text-center text-gray-400"></i> Sobre Nós</a></li>
                        <li class="border-t border-gray-700 my-2 md:hidden"></li>

                        <li><a href="<?php echo BASE_URL; ?>/login" class="flex items-center gap-4 px-5 py-3 text-sm hover:bg-gray-800 transition-colors"><i class="fas fa-sign-out-alt w-5 text-center text-gray-400"></i>Entrar ou Cadastre-se</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <main>
        <!-- ==================== SEÇÃO HERO (VÍDEO) ==================== -->
        <section class="relative h-screen flex items-center justify-center text-center md:justify-start md:text-left p-0">
            <iframe
                src="https://player.vimeo.com/video/1129399439?background=1&autoplay=1&loop=1&muted=1&autopause=0"
                class="vimeo-bg-cover"
                frameborder="0"
                allow="autoplay; fullscreen; picture-in-picture" allowfullscreen>
            </iframe>
            <div class="absolute top-0 left-0 w-full h-full bg-black/60 z-[-1]"></div>
            <div class="container mx-auto px-4 relative z-10">
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold max-w-lg leading-tight mx-auto md:mx-0">Cole com quem ama esporte</h1>
                <div class="flex flex-wrap gap-4 mt-8 justify-center md:justify-start">
                    <li><a href="<?php echo BASE_URL; ?>/login" class="py-3 px-8 rounded-full font-semibold transition-all duration-300 bg-white text-black border-2 border-white hover:bg-transparent hover:text-white">Comece de graça</a>
                </div>
            </div>
        </section>

        <!-- ==================== SEÇÃO DE INTRODUÇÃO ==================== -->
        <section class="bg-[#161B22] py-16 md:py-24 overflow-hidden">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-3xl md:text-4xl font-bold max-w-2xl mx-auto">Conecte-se com quem compartilha a sua paixão pelo esporte.</h2>
                <p class="max-w-3xl mx-auto mt-4 text-gray-400">Encontre atletas com os mesmos interesses, nível de habilidade e na sua região. Mais do que uma conexão, é o seu novo time.</p>
            </div>
            <!-- Swiper Carousel -->
            <div class="container mx-auto px-4 mt-12 md:mt-16 pb-10">
                <div class="swiper intro-carousel h-[480px]">
                    <div class="swiper-wrapper">
                        <!-- Slides -->
                        <div class="swiper-slide h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-1">
                            <img src="https://images.pexels.com/photos/47730/the-ball-stadion-football-the-pitch-47730.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Bola de futebol no gramado do estádio" class="w-full h-full object-cover">
                        </div>
                        <div class="swiper-slide h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-1">
                            <img src="https://images.pexels.com/photos/270085/pexels-photo-270085.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Mulher correndo em trilha" class="w-full h-full object-cover">
                        </div>
                        <div class="swiper-slide h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-1">
                            <img src="https://images.pexels.com/photos/163452/basketball-dunk-blue-game-163452.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Homem fazendo uma enterrada no basquete" class="w-full h-full object-cover">
                        </div>
                        <div class="swiper-slide h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-1">
                            <img src="https://images.pexels.com/photos/1263349/pexels-photo-1263349.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Pessoa surfando uma onda" class="w-full h-full object-cover">
                        </div>
                        <div class="swiper-slide h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-1">
                            <img src="https://images.pexels.com/photos/863988/pexels-photo-863988.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Pessoa nadando em uma piscina" class="w-full h-full object-cover">
                        </div>
                        <div class="swiper-slide h-[450px] rounded-xl overflow-hidden transition-transform duration-300 hover:scale-105 hover:-translate-y-1">
                            <img src="https://images.pexels.com/photos/1080884/pexels-photo-1080884.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Jogadores de vôlei em ação" class="w-full h-full object-cover">
                        </div>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </section>

        <!-- ==================== SEÇÃO SOBRE NÓS ==================== -->
        <section id="sobre-nos" class="py-16 md:py-24">
            <div class="container mx-auto px-4 grid lg:grid-cols-2 gap-12 items-center">
                <div class="order-last lg:order-first">
                    <img src="./assets/img/about_us_img.png" alt="Atletas celebrando juntos" class="w-full rounded-xl">
                </div>
                <div class="text-center lg:text-left">
                    <h2 class="text-3xl md:text-4xl font-bold">Nossa Missão é Conectar Atletas</h2>
                    <p class="mt-4 text-gray-400">Acreditamos que o esporte tem o poder de unir pessoas, criar comunidades e transformar vidas. A Kolae nasceu do desejo de facilitar essa conexão, oferecendo uma plataforma onde atletas de todos os níveis podem encontrar parceiros de treino, equipes e eventos na sua região.</p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-8 justify-center lg:justify-start">
                        <div>
                            <i class="fas fa-users text-cyan-400 text-3xl mb-3"></i>
                            <h3 class="text-lg font-bold">Comunidade</h3>
                            <p class="text-sm text-gray-400">Construa seu time e faça parte de uma rede de atletas apaixonados.</p>
                        </div>
                        <div>
                            <i class="fas fa-map-marker-alt text-cyan-400 text-3xl mb-3"></i>
                            <h3 class="text-lg font-bold">Conexão Local</h3>
                            <p class="text-sm text-gray-400">Encontre treinos e jogos perto de você, a qualquer hora.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== SEÇÃO DE PARCEIROS (CARROSSEL) ==================== -->
        <section class="bg-[#161B22] py-16 md:py-24">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-3xl md:text-4xl font-bold">Parceiros que fortalecem o esporte</h2>
                <p class="max-w-3xl mx-auto mt-4 text-gray-400">Conheça as marcas que apoiam o crescimento do esporte e da nossa comunidade.</p>
            </div>
            <div class="w-full overflow-hidden relative mt-16 [mask-image:linear-gradient(to_right,transparent,black_20%,black_80%,transparent)]">
                <div class="flex animate-scroll hover:[animation-play-state:paused]">
                    <!-- Logos duplicados para efeito infinito -->
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_fatec.png" alt="Logo Fatec" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a8/Original_Adidas_logo.svg/1280px-Original_Adidas_logo.svg.png" alt="Logo Adidas" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Logo_NIKE.svg/1200px-Logo_NIKE.svg.png" alt="Logo Nike" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_atletica_sagui.png" alt="Logo Atletica Sagui" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_brtz.png" alt="Logo BRTZ" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_leos_de_ferraz.png" alt="Logo Loes de Ferraz" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_fatec.png" alt="Logo Fatec" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a8/Original_Adidas_logo.svg/1280px-Original_Adidas_logo.svg.png" alt="Logo Adidas" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Logo_NIKE.svg/1200px-Logo_NIKE.svg.png" alt="Logo Nike" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_atletica_sagui.png" alt="Logo Atletica Sagui" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_brtz.png" alt="Logo BRTZ" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100"></div>
                    <div class="w-52 flex-shrink-0 flex items-center justify-center mx-4"><img src="<?php echo BASE_URL; ?>/assets/img/logo_leos_de_ferraz.png" alt="Logo Leos de Ferraz" class="h-12 transition-all duration-300 filter grayscale brightness-75 opacity-70 hover:filter-none hover:opacity-100"></div>
                </div>
            </div>
        </section>
    </main>

    <!-- ==================== RODAPÉ ==================== -->
    <footer class="bg-gray-800 pt-16 md:pt-20 border-t border-gray-700">
        <div class="container mx-auto px-4 grid md:grid-cols-2 lg:grid-cols-3 gap-12">
            <div class="mb-8 text-center md:text-left">
                <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-10 mx-auto md:mx-0">
                <p class="text-sm text-gray-400 mt-4">Conectando atletas, fortalecendo o esporte.</p>
                <div class="flex space-x-4 mt-6 justify-center md:justify-start">
                    <a href="#" class="text-xl hover:text-cyan-400 transition-colors" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-xl hover:text-cyan-400 transition-colors" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-xl hover:text-cyan-400 transition-colors" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                </div>
            </div>
            <div class="mb-8 text-center md:text-left">
                <h4 class="text-lg font-semibold mb-4">Contato</h4>
                <p class="text-sm text-gray-400"><a href="mailto:kolae.gg@gmail.com" class="hover:text-cyan-400 transition-colors">kolae.gg@gmail.com</a></p>
                <p class="text-sm text-gray-400 mt-2">+55 (11) 99860-0253</p>
            </div>
            <div class="mb-8 text-center md:text-left">
                <h4 class="text-lg font-semibold mb-4">Receba nossas novidades</h4>
                <p class="text-sm text-gray-400">Cadastre-se para ficar por dentro dos próximos eventos e atualizações.</p>
                <form class="flex mt-4">
                    <input type="email" placeholder="Seu melhor e-mail" required class="w-full bg-gray-900 border border-gray-700 rounded-l-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-400">
                    <button type="submit" aria-label="Enviar email" class="bg-cyan-400 text-black font-bold px-4 py-2 rounded-r-md hover:bg-cyan-300 transition-colors"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
        <div class="mt-8 md:mt-12 py-6 border-t border-gray-700 text-center">
            <p class="text-sm text-gray-400">&copy; 2025 Kolae. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- Script de Inicialização -->
    <script>
        // Swiper Carousel Logic
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
                768: {
                    slidesPerView: 2,
                    slidesPerGroup: 2
                },
                1024: {
                    slidesPerView: 3,
                    slidesPerGroup: 3
                }
            }
        });

        // User Menu Dropdown Logic
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
    </script>
</body>

</html>