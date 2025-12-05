<?php
$lang = require BASE_PATH . '/includes/i18n.php';


$message = null;
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma']; ?>" class="transition-colors duration-500">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae></title>

    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <?php include 'app/views/partials/theme_script.php'; ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">

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
            <img src="<?php echo BASE_URL; ?>/assets/img/forgot_bg.webp" alt="Background" class="w-full h-full object-cover opacity-40">
            <div class="absolute inset-0 bg-white/40 dark:bg-black/60"></div>
        </div>

        <div class="hidden lg:flex w-1/2 bg-cover bg-center relative items-center justify-center"
            style="background-image: url('<?php echo BASE_URL; ?>/assets/img/forgot_bg.webp');">
            <div class="absolute inset-0 bg-black/60"></div>
            <div class="relative z-10 text-center px-12 animate-fadeInUp">
                <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-16 mx-auto mb-6 drop-shadow-lg">
                <h1 class="text-4xl font-bold leading-tight text-white drop-shadow-md"><?php echo $lang['forget_title_back']; ?></h1>
                <p class="mt-4 text-lg text-gray-200 drop-shadow-md"><?php echo $lang['forget_subtitle_back']; ?></p>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8 relative z-10">

            <div class="relative w-full max-w-md bg-surface-elevated/90 backdrop-blur-md p-8 rounded-2xl border border-content-secondary/10 shadow-2xl lg:border-none lg:bg-transparent lg:shadow-none animate-fadeInUp transition-colors duration-500">

                <a href="<?php echo BASE_URL; ?>/login"
                    class="absolute top-4 left-4 lg:top-0 lg:left-0 text-content-secondary hover:text-content-primary transition-colors"
                    title="<?php echo $lang['global_back'] ?? 'Voltar'; ?>">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>

                <a href="<?php echo BASE_URL; ?>/" class="lg:hidden mb-8 inline-block w-full text-center">
                    <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-12 mx-auto drop-shadow-lg filter dark:filter-none invert dark:invert-0">
                </a>

                <h2 class="text-3xl font-bold text-center mb-2 text-content-primary"><?php echo $lang['forget_title_password']; ?></h2>
                <p class="text-content-secondary text-center mb-8"><?php echo $lang['forget_subtitle_password']; ?></p>

                <?php
                if (isset($_GET['status']) && $_GET['status'] === 'sent') {
                    echo '<div class="bg-green-500/20 border border-green-500/50 text-green-600 dark:text-green-300 px-4 py-3 rounded-lg text-center mb-6 text-sm backdrop-blur-sm animate-fadeInUp">'
                        . ($lang['email_exists'] ?? 'Email enviado com sucesso!')
                        . '</div>';
                }
                if (isset($_GET['error']) && $_GET['error'] === 'invalid_email') {
                    echo '<div class="bg-red-500/20 border border-red-500/50 text-red-600 dark:text-red-300 px-4 py-3 rounded-lg text-center mb-6 text-sm backdrop-blur-sm animate-fadeInUp">'
                        . ($lang['email_invalid'] ?? 'Email inválido.')
                        . '</div>';
                }
                ?>

                <form action="<?= BASE_URL ?>/forgot-password" method="POST" class="space-y-6" id="forgot-form">
                    <div>
                        <label for="email" class="block text-sm font-medium text-content-secondary ml-1"><?php echo $lang['global_email']; ?></label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-content-secondary"></i>
                            </div>
                            <input id="email" name="email" type="email" autocomplete="email" placeholder="<?php echo $lang['forget_email_registred']; ?>" required
                                class="w-full bg-surface-secondary border border-content-secondary/20 rounded-lg pl-10 pr-4 py-3 text-sm text-content-primary placeholder-content-secondary/70 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all">
                        </div>
                    </div>

                    <div>
                        <button id="submit-btn" type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-bold text-white bg-cyan-500 hover:bg-cyan-400 hover:shadow-cyan-500/20 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface-base focus:ring-cyan-500 transition-all duration-200 transform">
                            <?php echo $lang['forget_btn_recover']; ?>
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-sm text-content-secondary">
                    <?php echo $lang['remember_passoword_text']; ?>
                    <a href="<?= BASE_URL ?>/login" class="font-medium text-cyan-600 dark:text-cyan-400 hover:text-cyan-500 transition-colors hover:underline"><?php echo $lang['remember_password_btn']; ?></a>
                </p>
            </div>
        </div>
    </div>

    <script type="module" src="<?php echo BASE_URL; ?>/assets/js/bundle.js"></script>

</body>

</html>