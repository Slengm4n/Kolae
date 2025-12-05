<?php
$lang = require BASE_PATH . '/includes/i18n.php';

$message = null;
$messageType = 'error';

if (isset($_GET['error'])) {
    $messageType = 'error';
    switch ($_GET['error']) {
        case 'email_exists':
            $message = $lang['register_email_exists'];
            break;
        case 'password_mismatch':
            $message = $lang['password_mismatch'];
            break;
        case 'registration_failed':
            $message = $lang['register_failed'];
            break;
        case 'weak_password':
            $message = $lang['register_password_character'];
            break;
        default:
            $message = $lang['unknown_error'];
    }
} elseif (isset($_GET['status'])) {
    $messageType = 'success';
    switch ($_GET['status']) {
        case 'password_reset':
            $message = $lang['message_password_redefinition'];
            break;
        case 'registered':
            $message = $lang['message_registration_success'];
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma']; ?>" class="transition-colors duration-500">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae</title>

    <?php include 'app/views/partials/theme_script.php'; ?>

    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        <div class="absolute inset-0 z-0 lg:hidden">>
            <img src="<?php echo BASE_URL; ?>/assets/img/register_bg.webp"
                lt="Background" class="w-full h-full object-cover opacity-20 dark:opacity-40">
            <div class="absolute inset-0 bg-white/40 dark:bg-black/60">
            </div>
        </div>

        <div class="hidden lg:flex w-1/2 bg-cover bg-center relative items-center justify-center"
            style="background-image: url('<?php echo BASE_URL; ?>/assets/img/register_bg.webp');">
            <div class="absolute inset-0 bg-black/60"></div>
            <div class="relative z-10 text-center px-12 animate-fadeInUp">
                <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-16 mx-auto mb-6 drop-shadow-lg">
                <h1 class="text-4xl xl:text-5xl font-bold leading-tight text-white drop-shadow-lg"><?php echo $lang['register_img_title']; ?></h1>
                <p class="mt-6 text-lg text-gray-200 drop-shadow-md max-w-md mx-auto font-medium"><?php echo $lang['register_img_text']; ?></p>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8 relative z-10">


            <div class="relative w-full max-w-md bg-surface-elevated/90 backdrop-blur-md p-8 rounded-2xl border border-content-secondary/10 shadow-2xl lg:border-none lg:bg-transparent lg:shadow-none animate-fadeInUp transition-colors duration-500">

                <a href="<?php echo BASE_URL; ?>/"
                    class="absolute top-4 left-4 lg:top-0 lg:left-0 text-content-secondary hover:text-white transition-colors"
                    title="<?php echo $lang['global_back'] ?? 'Voltar'; ?>">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>

                <a href="<?php echo BASE_URL; ?>/" class="lg:hidden mb-8 block w-full text-center">
                    <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-12 mx-auto drop-shadow-lg">
                </a>

                <h2 class="text-3xl font-bold text-center mb-2"><?php echo $lang['register_title_forms']; ?></h2>
                <p class="text-content-secondary text-center mb-8"><?php echo $lang['register_subtitle_forms']; ?></p>

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
                        <label for="name" class="block text-sm font-medium text-content-secondary ml-1"><?php echo $lang['register_name']; ?></label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-content-secondary"></i>
                            </div>
                            <input id="name" name="name" type="text" autocomplete="name" required
                                class="w-full bg-surface-secondary border border-content-secondary/20 rounded-lg pl-10 pr-4 py-3 text-sm text-content-primary placeholder-content-secondary/70 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                placeholder="<?php echo $lang['register_name_ph']; ?>">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-content-secondary ml-1"><?php echo $lang['global_email']; ?></label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-content-secondary"></i>
                            </div>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                class="w-full bg-surface-secondary border border-content-secondary/20 rounded-lg pl-10 pr-4 py-3 text-sm text-content-primary placeholder-content-secondary/70 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                placeholder="<?php echo $lang['register_email_ph']; ?>">
                        </div>
                    </div>

                    <div>
                        <label for="birthdate" class="block text-sm font-medium text-content-secondary ml-1"><?php echo $lang['register_birthday']; ?></label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar text-content-secondary"></i>
                            </div>
                            <input id="birthdate" name="birthdate" type="date" required
                                class="w-full bg-surface-secondary border border-content-secondary/20 rounded-lg pl-10 pr-4 py-3 text-sm text-content-primary placeholder-content-secondary/70 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-content-secondary ml-1"><?php echo $lang['register_password']; ?></label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar text-content-secondary"></i>
                            </div>
                            <input id="password" name="password" type="password" autocomplete="new-password" required
                                class="w-full bg-surface-secondary border border-content-secondary/20 rounded-lg pl-10 pr-4 py-3 text-sm text-content-primary placeholder-content-secondary/70 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                placeholder="<?php echo $lang['register_password_ph']; ?>">
                        </div>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-content-secondary ml-1"><?php echo $lang['register_password_two']; ?></label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar text-content-secondary"></i>
                            </div>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                                class="w-full bg-surface-secondary border border-content-secondary/20 rounded-lg pl-10 pr-4 py-3 text-sm text-content-primary placeholder-content-secondary/70 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
                                placeholder="<?php echo $lang['register_password_ph_two']; ?>">
                        </div>
                    </div>

                    <div class="flex items-start pt-2">
                        <label for="terms" class="flex items-start cursor-pointer group select-none relative">
                            <input id="terms" name="terms" type="checkbox" required class="sr-only peer">

                            <span class="flex items-center justify-center w-5 h-5 bg-surface-secondary border-2 border-content-secondary/30 rounded mr-2 transition-all peer-checked:bg-cyan-500 peer-checked:border-cyan-500 group-hover:border-content-secondary/50">
                                <i class="fas fa-check text-xs text-white opacity-0 transition-opacity peer-checked:opacity-100"></i>
                            </span>

                            <span class="text-sm font-medium text-content-secondary group-hover:text-content-primary transition-colors leading-snug">
                                <?php echo $lang['register_accept_agree']; ?>
                                <a href="#" class="text-cyan-600 dark:text-cyan-400 hover:underline decoration-2 underline-offset-2 z-10 relative"><?php echo $lang['register_terms_btn']; ?></a>
                                <?php echo $lang['global_and']; ?>
                                <a href="#" class="text-cyan-600 dark:text-cyan-400 hover:underline decoration-2 underline-offset-2 z-10 relative"><?php echo $lang['register_privacy_btn']; ?></a>.
                            </span>
                        </label>
                    </div>

                    <div class="pt-4">
                        <button id="register-button" type="submit" class="w-full flex justify-center items-center py-4 px-6 rounded-xl shadow-lg shadow-cyan-500/20 text-base font-bold text-white bg-gradient-to-r from-cyan-500 to-cyan-400 hover:from-cyan-400 hover:to-cyan-300 hover:shadow-cyan-500/40 hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-cyan-500/30 transition-all duration-300 active:scale-[0.98]">
                            <?php echo $lang['register_create_btn']; ?>
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-content-secondary font-medium pb-8">
                    <?php echo $lang['register_confirm_answer']; ?>
                    <a href="<?php echo BASE_URL; ?>/login" class="font-bold text-cyan-600 dark:text-cyan-400 hover:text-cyan-500 transition-colors hover:underline decoration-2 underline-offset-4 ml-1"><?php echo $lang['register_answer_btn']; ?></a>
                </p>
            </div>
        </div>
    </div>

    <script type="module" src="<?php echo BASE_URL; ?>/assets/js/bundle.js"></script>

</body>

</html>