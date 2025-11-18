<?php
// ====================================================
// LÓGICA PHP DE FEEDBACK
// ====================================================
$message = null;
$messageType = 'error';

if (isset($_GET['error'])) {
    $messageType = 'error';
    switch ($_GET['error']) {
        case 'email_exists':
            $message = 'Este e-mail já está cadastrado. Tente fazer login.';
            break;
        case 'password_mismatch':
            $message = 'As senhas não coincidem.';
            break;
        case 'registration_failed':
            $message = 'Erro ao criar conta. Tente novamente mais tarde.';
            break;
        case 'weak_password':
            $message = 'A senha deve ter pelo menos 6 caracteres.';
            break;
        default:
            $message = 'Ocorreu um erro desconhecido.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae - Criar Conta</title>

    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print" onload="this.media='all'" />
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    </noscript>

    <link href="<?php echo BASE_URL; ?>/assets/css/style.css?v=" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            animation: pageFadeIn 0.4s ease-out both;
        }

        @keyframes pageFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.5s ease-out both;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="bg-gray-900 text-white">

    <div class="flex min-h-screen relative">

        <div class="absolute inset-0 z-0 lg:hidden">
            <img src="<?php echo BASE_URL; ?>/assets/img/register_bg.webp"
                alt="Background"
                class="w-full h-full object-cover opacity-40">
            <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/80"></div>
        </div>

        <div class="hidden lg:flex w-1/2 bg-cover bg-center relative items-center justify-center"
            style="background-image: url('<?php echo BASE_URL; ?>/assets/img/register_bg.webp');">
            <div class="absolute inset-0 bg-black/60"></div>
            <div class="relative z-10 text-center px-12 animate-fadeInUp">
                <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-16 mx-auto mb-6 drop-shadow-lg">
                <h1 class="text-4xl font-bold leading-tight drop-shadow-md">Faça parte do time.</h1>
                <p class="mt-4 text-lg text-gray-200 drop-shadow-md">Crie sua conta gratuita e comece a transformar sua rotina esportiva hoje mesmo.</p>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8 relative z-10">

            <div class="relative w-full max-w-md bg-[#161B22]/85 backdrop-blur-md p-8 rounded-2xl border border-white/10 shadow-2xl lg:border-none lg:bg-transparent lg:backdrop-blur-none lg:shadow-none animate-fadeInUp" style="animation-delay: 200ms;">

                <a href="<?php echo BASE_URL; ?>/"
                    class="absolute top-4 left-4 lg:top-0 lg:left-0 text-gray-400 hover:text-white transition-colors"
                    title="Voltar para a Home">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>

                <a href="<?php echo BASE_URL; ?>/" class="lg:hidden mb-8 inline-block w-full text-center">
                    <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-12 mx-auto drop-shadow-lg">
                </a>

                <h2 class="text-3xl font-bold text-center mb-2">Crie sua conta</h2>
                <p class="text-gray-400 text-center mb-8">Preencha seus dados para começar.</p>

                <?php if ($message): ?>
                    <?php
                    $bgColor = ($messageType === 'success') ? 'bg-green-500/20' : 'bg-red-500/20';
                    $borderColor = ($messageType === 'success') ? 'border-green-500/50' : 'border-red-500/50';
                    $textColor = ($messageType === 'success') ? 'text-green-300' : 'text-red-300';
                    ?>
                    <div class="<?= $bgColor ?> <?= $borderColor ?> <?= $textColor ?> border px-4 py-3 rounded-lg text-center mb-6 text-sm backdrop-blur-sm" role="alert">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form id="register-form" action="<?php echo BASE_URL; ?>/register/store" method="POST" class="space-y-5">

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300 ml-1">Nome Completo</label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-500"></i>
                            </div>
                            <input id="name" name="name" type="text" autocomplete="name" required
                                class="w-full bg-gray-900/50 border border-gray-600 rounded-lg pl-10 pr-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-transparent transition-all"
                                placeholder="Como quer ser chamado?">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 ml-1">Email</label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-500"></i>
                            </div>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                class="w-full bg-gray-900/50 border border-gray-600 rounded-lg pl-10 pr-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-transparent transition-all"
                                placeholder="seu@email.com">
                        </div>
                    </div>

                    <div>
                        <label for="birthdate" class="block text-sm font-medium text-gray-300 ml-1">Data de Nascimento</label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar text-gray-500"></i>
                            </div>
                            <input id="birthdate" name="birthdate" type="date" required
                                class="w-full bg-gray-900/50 border border-gray-600 rounded-lg pl-10 pr-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-transparent transition-all">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300 ml-1">Senha</label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-500"></i>
                            </div>
                            <input id="password" name="password" type="password" autocomplete="new-password" required
                                class="w-full bg-gray-900/50 border border-gray-600 rounded-lg pl-10 pr-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-transparent transition-all"
                                placeholder="Mínimo 6 caracteres">
                        </div>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-300 ml-1">Confirmar Senha</label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-key text-gray-500"></i>
                            </div>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                                class="w-full bg-gray-900/50 border border-gray-600 rounded-lg pl-10 pr-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-transparent transition-all"
                                placeholder="Repita a senha">
                        </div>
                    </div>

                    <div class="flex items-start pt-2">
                        <div class="flex items-center h-5">
                            <input id="terms" name="terms" type="checkbox" required class="w-4 h-4 bg-gray-800 border border-gray-600 rounded focus:ring-cyan-400 focus:ring-2 cursor-pointer">
                        </div>
                        <div class="ml-2 text-sm">
                            <label for="terms" class="font-medium text-gray-400">
                                Eu concordo com os <a href="#" class="text-cyan-400 hover:underline">Termos de Uso</a> e <a href="#" class="text-cyan-400 hover:underline">Privacidade</a>.
                            </label>
                        </div>
                    </div>

                    <div>
                        <button id="register-button" type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-bold text-black bg-cyan-400 hover:bg-cyan-300 hover:shadow-cyan-400/20 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-cyan-400 transition-all duration-200 transform">
                            Criar Conta
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-sm text-gray-400">
                    Já tem uma conta?
                    <a href="<?php echo BASE_URL; ?>/login" class="font-medium text-cyan-400 hover:text-cyan-300 transition-colors hover:underline">Fazer Login</a>
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
                registerButton.classList.add('opacity-75', 'cursor-not-allowed', 'scale-100');
                registerButton.innerHTML = `
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Criando conta...
                `;
            });
        }
    </script>

</body>

</html>
