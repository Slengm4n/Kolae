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
<html lang="pt-BR" class="transition-colors duration-500">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae</title>
    <?php include 'app/views/partials/theme_script.php'; ?>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">

    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <style>
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

<body class="bg-surface-base text-content-primary font-poppins transition-colors duration-500">

    <div class="flex min-h-screen relative">

        <div class="absolute inset-0 z-0 lg:hidden">
            <img src="<?php echo BASE_URL; ?>/assets/img/login_bg.webp" alt="Background" class="w-full h-full object-cover opacity-20 dark:opacity-40">
            <div class="absolute inset-0 bg-white/40 dark:bg-black/60"></div>
        </div>

        <div class="hidden lg:flex w-1/2 bg-cover bg-center relative items-center justify-center"
            style="background-image: url('<?php echo BASE_URL; ?>/assets/img/login_bg.webp');">
            <div class="absolute inset-0 bg-black/60"></div>
            <div class="relative z-10 text-center px-12 animate-fadeInUp">
                <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-16 mx-auto mb-6 drop-shadow-lg">
                <h1 class="text-4xl font-bold leading-tight text-white drop-shadow-md">Sua jornada esportiva começa aqui.</h1>
                <p class="mt-4 text-lg text-gray-200 drop-shadow-md">Conecte-se, treine e evolua com a maior comunidade de atletas.</p>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8 relative z-10">

            <div class="relative w-full max-w-md bg-surface-elevated/90 backdrop-blur-md p-8 rounded-2xl border border-content-secondary/10 shadow-2xl lg:border-none lg:bg-transparent lg:shadow-none animate-fadeInUp transition-colors duration-500">

                <a href="<?php echo BASE_URL; ?>/"
                    class="absolute top-4 left-4 lg:top-0 lg:left-0 text-content-secondary hover:text-content-primary transition-colors"
                    title="Voltar para a Home"> <i class="fas fa-arrow-left text-xl"></i>
                </a>

                <a href="<?php echo BASE_URL; ?>/" class="lg:hidden mb-8 inline-block w-full text-center">
                    <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-12 mx-auto drop-shadow-lg filter dark:filter-none invert dark:invert-0">
                </a>

                <h2 class="text-3xl font-bold text-center mb-2 text-content-primary">Bem-vindo de volta!</h2>
                <p class="text-content-secondary text-center mb-8">Acesse sua conta para continuar.</p>

                <?php if ($message): ?>
                    <?php
                    // Ajuste de cores de alerta
                    $bgColor = ($messageType === 'success') ? 'bg-green-100 dark:bg-green-500/20' : 'bg-red-100 dark:bg-red-500/20';
                    $borderColor = ($messageType === 'success') ? 'border-green-500/50' : 'border-red-500/50';
                    $textColor = ($messageType === 'success') ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300';
                    ?>
                    <div class="<?= $bgColor ?> <?= $borderColor ?> <?= $textColor ?> border px-4 py-3 rounded-lg text-center mb-6 text-sm backdrop-blur-sm" role="alert">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form id="login-form" action="<?php echo BASE_URL; ?>/login/authenticate" method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-content-secondary ml-1">Email</label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-content-secondary"></i>
                            </div>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                class="w-full bg-surface-secondary border border-content-secondary/20 rounded-lg pl-10 pr-4 py-3 text-sm text-content-primary placeholder-content-secondary/70 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                placeholder="Digite seu e-mail">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-content-secondary ml-1">Senha</label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-content-secondary"></i>
                            </div>
                            <input id="password" name="password" type="password" autocomplete="current-password" required
                                class="w-full bg-surface-secondary border border-content-secondary/20 rounded-lg pl-10 pr-4 py-3 text-sm text-content-primary placeholder-content-secondary/70 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                placeholder="Digite sua senha">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <label for="remember-me" class="flex items-center cursor-pointer group">
                                <input id="remember-me" name="remember-me" type="checkbox" class="sr-only peer">
                                <span class="flex items-center justify-center w-5 h-5 bg-surface-secondary border-2 border-content-secondary/30 rounded mr-2 transition-all peer-checked:bg-cyan-500 peer-checked:border-cyan-500 group-hover:border-content-secondary/50">
                                    <i class="fas fa-check text-xs text-white opacity-0 transition-opacity peer-checked:opacity-100"></i>
                                </span>
                                <span class="block text-sm text-content-secondary select-none">Lembrar de mim</span>
                            </label>
                        </div>
                        <div class="text-sm">
                            <a href="<?php echo BASE_URL; ?>/forgot-password" class="font-medium text-cyan-600 dark:text-cyan-400 hover:text-cyan-500 transition-colors">Esqueceu a senha?</a>
                        </div>
                    </div>

                    <div>
                        <button id="login-button" type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-bold text-white bg-cyan-500 hover:bg-cyan-400 hover:shadow-cyan-500/20 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface-base focus:ring-cyan-500 transition-all duration-200 transform">
                            Entrar
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-sm text-content-secondary">
                    Não tem uma conta?
                    <a href="<?php echo BASE_URL; ?>/register" class="font-medium text-cyan-600 dark:text-cyan-400 hover:text-cyan-500 transition-colors hover:underline">Cadastre-se</a>
                </p>
            </div>
        </div>
    </div>

    <script type="module" src="<?php echo BASE_URL; ?>/assets/js/bundle.js"></script>

</body>

</html>