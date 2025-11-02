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
    <script src="https://cdn.tailwindcss.com"></script>

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

<body class="bg-[#0D1117] text-white">

    <div class="flex min-h-screen">
        <div class="hidden lg:flex w-1/2 bg-cover bg-center relative items-center justify-center" style="background-image: url('https://images.pexels.com/photos/1571658/pexels-photo-1571658.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1');">
            <div class="absolute inset-0 bg-black/60"></div>
            <div class="relative z-10 text-center px-12 animate-fadeInUp">
                <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-16 mx-auto mb-6">
                <h1 class="text-4xl font-bold leading-tight">Junte-se à comunidade que não para.</h1>
                <p class="mt-4 text-lg text-gray-300">Crie sua conta e comece a se conectar com outros atletas hoje mesmo.</p>
            </div>
        </div>
        <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8">
            <div class="relative w-full max-w-md bg-[#161B22] p-8 rounded-2xl border border-gray-800 lg:border-none lg:bg-transparent lg:p-0 animate-fadeInUp" style="animation-delay: 200ms;">

                <!-- Botão de Voltar para Home -->
                <a href="<?php echo BASE_URL; ?>/" class="absolute top-4 left-4 lg:top-0 lg:left-0 text-gray-400 hover:text-white transition-colors" title="Voltar para a Home">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>

                <a href="<?php echo BASE_URL; ?>/index" class="lg:hidden mb-6 inline-block w-full text-center">
                    <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-10 mx-auto">
                </a>

                <h2 class="text-3xl font-bold text-center mb-2">Crie sua Conta</h2>
                <p class="text-gray-400 text-center mb-8">É rápido e fácil!</p>

                <?php if (isset($_GET['error'])): ?>
                    <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg text-center mb-6 text-sm">
                        <?php
                        switch ($_GET['error']) {
                            case 'password_mismatch':
                                echo 'As senhas não coincidem. Por favor, tente novamente.';
                                break;
                            case 'generic':
                                echo 'Ocorreu um erro ao criar a conta. Tente mais tarde.';
                                break;
                            case 'email_exists':
                                echo 'Este e-mail já está cadastrado. Tente fazer login.';
                                break;
                            case 'underage':
                                echo 'Você precisa ter pelo menos 18 anos para se cadastrar.';
                                break;
                            default:
                                echo 'Ocorreu um erro desconhecido.';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <form id="register-form" action="<?php echo BASE_URL; ?>/register/store" method="POST" class="space-y-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300">Nome Completo</label>
                        <div class="mt-1">
                            <input id="name" name="name" type="text" autocomplete="name" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300">Email</label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" autocomplete="email" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                        </div>
                    </div>

                    <div>
                        <label for="birthdate" class="block text-sm font-medium text-gray-300">Data de Nascimento</label>
                        <div class="mt-1">
                            <input id="birthdate" name="birthdate" type="date" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm text-gray-400 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300">Senha</label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" autocomplete="new-password" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                        </div>
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-300">Confirmar Senha</label>
                        <div class="mt-1">
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button id="register-button" type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-black bg-cyan-400 hover:bg-cyan-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-cyan-400 transition-colors">
                            Registrar
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-sm text-gray-400">
                    Já possui uma conta?
                    <a href="<?php echo BASE_URL; ?>/login" class="font-medium text-cyan-400 hover:text-cyan-300">Faça Login</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        const registerForm = document.getElementById('register-form');
        const registerButton = document.getElementById('register-button');

        if (registerForm && registerButton) {
            registerForm.addEventListener('submit', function() {
                registerButton.disabled = true;
                registerButton.innerHTML = `
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Aguarde...
                `;
            });
        }
    </script>

</body>

</html>