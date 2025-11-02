<?php

$message = null;
$messageType = 'error';

if (isset($_GET['error'])) {
    $messageType = 'error';
    switch ($_GET['error']) {
        case 'credentials':
            $message = 'E-mail ou senha incorretos. Tente novamente.';
            break;
        case 'invalid_token':
            $message = 'O link de redefinição é inválido ou já foi usado.';
            break;
        case 'inactive_account':
            $message = 'Esta conta está inativa. Contate o suporte.';
            break;
        default:
            $message = 'Ocorreu um erro desconhecido.';
    }
} elseif (isset($_GET['status'])) {
    $messageType = 'success';
    switch ($_GET['status']) {
        case 'password_reset':
            $message = 'Senha redefinida com sucesso! Faça o login.';
            break;
        case 'registered':
            $message = 'Cadastro realizado! Faça o login para continuar.';
            break;
    }
}
?>

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
        <div class="hidden lg:flex w-1/2 bg-cover bg-center relative items-center justify-center" style="background-image: url('https://images.pexels.com/photos/841130/pexels-photo-841130.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1');">
            <div class="absolute inset-0 bg-black/60"></div>
            <div class="relative z-10 text-center px-12 animate-fadeInUp">
                <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-16 mx-auto mb-6">
                <h1 class="text-4xl font-bold leading-tight">Sua jornada esportiva começa aqui.</h1>
                <p class="mt-4 text-lg text-gray-300">Conecte-se, treine e evolua com a maior comunidade de atletas.</p>
            </div>
        </div>
        <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8">
            <div class="relative w-full max-w-md bg-[#161B22] p-8 rounded-2xl border border-gray-800 lg:border-none lg:bg-transparent lg:p-0 animate-fadeInUp" style="animation-delay: 200ms;">
                <a href="<?php echo BASE_URL; ?>/" class="absolute top-4 left-4 lg:top-0 lg:left-0 text-gray-400 hover:text-white transition-colors" title="Voltar para a Home">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>

                <a href="<?php echo BASE_URL; ?>/index" class="lg:hidden mb-6 inline-block w-full text-center">>
                    <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-10 mx-auto">
                </a>

                <h2 class="text-3xl font-bold text-center mb-2">Bem-vindo de volta!</h2>
                <p class="text-gray-400 text-center mb-8">Acesse sua conta para continuar.</p>

                <?php if ($message): ?>
                    <?php
                    $bgColor = ($messageType === 'success') ? 'bg-green-500/20' : 'bg-red-500/20';
                    $borderColor = ($messageType === 'success') ? 'border-green-500' : 'border-red-500';
                    $textColor = ($messageType === 'success') ? 'text-green-300' : 'text-red-300';
                    ?>
                    <div class="<?= $bgColor ?> <?= $borderColor ?> <?= $textColor ?> border px-4 py-3 rounded-lg text-center mb-6 text-sm" role="alert">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                <form id="login-form" action="<?php echo BASE_URL; ?>/login/authenticate" method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300">Email</label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" autocomplete="email" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300">Senha</label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" autocomplete="current-password" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <label for="remember-me" class="flex items-center cursor-pointer">
                                <input id="remember-me" name="remember-me" type="checkbox" class="sr-only peer">
                                <span class="flex items-center justify-center w-4 h-4 bg-gray-700 border-2 border-gray-600 rounded-sm mr-2 transition-colors peer-checked:bg-cyan-500 peer-checked:border-cyan-500">
                                    <i class="fas fa-check text-xs text-black opacity-0 transition-opacity peer-checked:opacity-100"></i>
                                </span>
                                <span class="block text-sm text-gray-300">Lembrar de mim</span>
                            </label>
                        </div>
                        <div class="text-sm">
                            <a href="<?php echo BASE_URL; ?>/forgot-password" class="font-medium text-cyan-400 hover:text-cyan-300">Esqueceu a senha?</a>
                        </div>
                    </div>

                    <div>
                        <!-- Adicionado id="login-button" -->
                        <button id="login-button" type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-black bg-cyan-400 hover:bg-cyan-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-cyan-400 transition-colors">
                            Entrar
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-sm text-gray-400">
                    Não tem uma conta?
                    <a href="<?php echo BASE_URL; ?>/register" class="font-medium text-cyan-400 hover:text-cyan-300">Cadastre-se</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('login-form');
        const loginButton = document.getElementById('login-button');

        if (loginForm && loginButton) {
            loginForm.addEventListener('submit', function() {
                loginButton.disabled = true;
                loginButton.innerHTML = `
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Aguarde...
                `;
            });
        }
    </script>

</body>

</html>