<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae - Recuperar Senha</title>

    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/img/favicon.png" type="image/png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print" onload="this.media='all'" />
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    </noscript>

    <link href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo APP_VERSION; ?>" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            animation: pageFadeIn 0.4s ease-out both;
        }

        @keyframes pageFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.5s ease-out both;
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

<body class="bg-gray-900 text-white">

    <div class="flex min-h-screen relative">

        <div class="absolute inset-0 z-0 lg:hidden">
            <img src="<?php echo BASE_URL; ?>/assets/img/forgot_bg.webp"
                alt="Background"
                class="w-full h-full object-cover opacity-40">
            <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/80"></div>
        </div>

        <div class="hidden lg:flex w-1/2 bg-cover bg-center relative items-center justify-center"
            style="background-image: url('<?php echo BASE_URL; ?>/assets/img/forgot_bg.webp');">
            <div class="absolute inset-0 bg-black/60"></div>
            <div class="relative z-10 text-center px-12 animate-fadeInUp">
                <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-16 mx-auto mb-6 drop-shadow-lg">
                <h1 class="text-4xl font-bold leading-tight drop-shadow-md">Vamos te colocar de volta no jogo.</h1>
                <p class="mt-4 text-lg text-gray-200 drop-shadow-md">Recupere seu acesso e não perca nenhuma oportunidade de se conectar.</p>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8 relative z-10">

            <div class="relative w-full max-w-md bg-[#161B22]/85 backdrop-blur-md p-8 rounded-2xl border border-white/10 shadow-2xl lg:border-none lg:bg-transparent lg:backdrop-blur-none lg:shadow-none animate-fadeInUp" style="animation-delay: 200ms;">

                <a href="<?php echo BASE_URL; ?>/login"
                    class="absolute top-4 left-4 lg:top-0 lg:left-0 text-gray-400 hover:text-white transition-colors"
                    title="Voltar para o Login">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>

                <a href="<?php echo BASE_URL; ?>/" class="lg:hidden mb-8 inline-block w-full text-center">
                    <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-12 mx-auto drop-shadow-lg">
                </a>

                <h2 class="text-3xl font-bold text-center mb-2">Esqueceu sua senha?</h2>
                <p class="text-gray-400 text-center mb-8">Sem problemas! Digite seu e-mail e enviaremos um link para você criar uma nova.</p>

                <?php
                if (isset($_GET['status']) && $_GET['status'] === 'sent') {
                    echo '<div class="bg-green-500/20 border border-green-500/50 text-green-300 px-4 py-3 rounded-lg text-center mb-6 text-sm backdrop-blur-sm animate-fadeInUp">'
                        . 'Se um e-mail com este endereço existir, um link foi enviado.'
                        . '</div>';
                }
                if (isset($_GET['error']) && $_GET['error'] === 'invalid_email') {
                    echo '<div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-lg text-center mb-6 text-sm backdrop-blur-sm animate-fadeInUp">'
                        . 'Por favor, insira um e-mail válido.'
                        . '</div>';
                }
                ?>

                <form action="<?= BASE_URL ?>/forgot-password" method="POST" class="space-y-6" id="forgot-form">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 ml-1">Email</label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-500"></i>
                            </div>
                            <input id="email" name="email" type="email" autocomplete="email" placeholder="Digite seu E-mail cadastrado" required
                                class="w-full bg-gray-900/50 border border-gray-600 rounded-lg pl-10 pr-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-transparent transition-all">
                        </div>
                    </div>

                    <div>
                        <button id="submit-btn" type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-bold text-black bg-cyan-400 hover:bg-cyan-300 hover:shadow-cyan-400/20 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-cyan-400 transition-all duration-200 transform">
                            Enviar Link de Recuperação
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-sm text-gray-400">
                    Lembrou da senha?
                    <a href="<?= BASE_URL ?>/login" class="font-medium text-cyan-400 hover:text-cyan-300 transition-colors hover:underline">Voltar para o Login</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Script simples para feedback visual no botão
        document.getElementById('forgot-form').addEventListener('submit', function() {
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Enviando...';
        });
    </script>

</body>

</html>