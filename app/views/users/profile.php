<?php
$lang = require BASE_PATH . '/includes/i18n.php';
// Recupera dados passados pelo Controller
$user = $data['user'] ?? [];
$userName = $_SESSION['user_name'] ?? 'Usuário';
$userAvatar = $_SESSION['user_avatar'] ?? null; 
$userId = $_SESSION['user_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma']; ?>" class="transition-colors duration-500">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($lang['global_menu_profile'] . ' · Kolae'); ?></title>

    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="<?php echo \App\Core\ViewHelper::asset('assets/css/style.css'); ?>" rel="stylesheet">
    
    <?php include 'app/views/partials/theme_script.php'; ?>

    <style>
        body { font-family: 'Poppins', sans-serif; -webkit-font-smoothing: antialiased; overflow-x: hidden; }
        .animate-up { animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
        .delay-100 { animation-delay: 100ms; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="bg-surface-base text-content-primary font-sans antialiased">

    <?php $backUrl = '/dashboard';
    $toastContext = 'profile';
    include BASE_PATH . '/app/views/partials/owner_header.php'; ?>
    <main class="container mx-auto px-4 py-10 max-w-6xl">
        <div class="flex flex-col md:flex-row items-start gap-8">

            <aside class="w-full md:w-64 flex-shrink-0 animate-up">
                <h1 class="text-2xl font-bold text-content-primary mb-6 px-2"><?php echo $lang['global_menu_config'] ?></h1>
                <?php $configActive = 'profile'; include BASE_PATH . '/app/views/partials/config_nav.php'; ?>
            </aside>

            <section class="flex-1 w-full animate-up delay-100">



                <div class="bg-surface-elevated p-6 md:p-10 rounded-2xl border border-content-secondary/10 shadow-xl relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-brand-500 to-brand-700"></div>

                    <?php if (!empty($user)) : ?>

                        <form action="<?php echo BASE_URL; ?>/dashboard/perfil/atualizar" method="POST" enctype="multipart/form-data">
                            <?php echo \App\Core\Csrf::field(); ?>

                            <div class="flex flex-col lg:flex-row gap-10 mb-10">
                                <div class="flex flex-col items-center lg:items-start">
                                    <h2 class="text-lg font-bold text-content-primary mb-4 lg:hidden"><?php echo $lang['profile_photo_title']; ?></h2>

                                    <div class="relative group w-40 h-40 rounded-full overflow-hidden border-4 border-surface-base shadow-2xl cursor-pointer">
                                        <?php
                                        $placeholder = BASE_URL . '/assets/img/avatar_placeholder.svg';
                                        $avatarFormPath = !empty($user['avatar_path'])
                                            ? BASE_URL . '/uploads/avatars/' . $user['id'] . '/' . $user['avatar_path']
                                            : $placeholder;
                                        ?>
                                        <img id="avatar-preview"
                                            src="<?php echo $avatarFormPath; ?>"
                                            alt="Avatar"
                                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                            onerror="this.onerror=null; this.src='<?php echo $placeholder; ?>';">

                                        <label for="avatar-upload" class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300 cursor-pointer">
                                            <i class="fas fa-camera text-2xl mb-1"></i>
                                            <span class="text-xs font-medium"><?php echo $lang['profile_image_ph'] ?></span>
                                        </label>
                                        <input type="file" name="avatar" id="avatar-upload" class="hidden" accept="image/png, image/jpeg, image/gif">
                                    </div>
                                    <p class="text-xs text-content-secondary mt-3 text-center lg:text-left w-40"><?php echo $lang['profile_image_desc'] ?><br>Máx 5MB.</p>
                                </div>

                                <div class="flex-1 w-full space-y-6">
                                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-content-secondary/10 pb-4 mb-4">
                                        <div>
                                            <h2 class="text-2xl font-bold text-content-primary"><?php echo $lang['profile_title'] ?></h2>
                                            <p class="text-sm text-content-secondary"><?php echo $lang['profile_subtitle'] ?></p>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="name" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5"><?php echo $lang['register_name'] ?></label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-user"></i></div>
                                            <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required
                                                class="w-full bg-input-base border border-content-secondary/20 rounded-xl pl-10 pr-4 py-3 text-sm text-content-primary placeholder-gray-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="email" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5"><?php echo $lang['global_email'] ?></label>
                                            <div class="relative opacity-60 cursor-not-allowed" title="Não editável">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-envelope"></i></div>
                                                <input id="email" type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled
                                                    class="w-full bg-surface-secondary border border-content-secondary/20 rounded-xl pl-10 pr-4 py-3 text-sm text-content-secondary cursor-not-allowed">
                                            </div>
                                            <?php // Selo da confirmação de e-mail (D58) ?>
                                            <?php if (!empty($user['email_verified_at'])): ?>
                                                <p class="mt-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5"><i class="fas fa-circle-check" aria-hidden="true"></i><?php echo htmlspecialchars($lang['verify_badge_ok']); ?></p>
                                            <?php else: ?>
                                                <p class="mt-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300 flex items-center gap-1.5"><i class="fas fa-circle-exclamation" aria-hidden="true"></i><?php echo htmlspecialchars($lang['verify_badge_pending']); ?> <a href="<?php echo BASE_URL; ?>/dashboard#email-verify" class="underline underline-offset-2"><?php echo htmlspecialchars($lang['verify_resend']); ?></a></p>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <label for="birthdate" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5"><?php echo $lang['register_birthday'] ?></label>
                                            <div class="relative opacity-60 cursor-not-allowed" title="Não editável">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-calendar"></i></div>
                                                <input id="birthdate" type="date" value="<?php echo htmlspecialchars($user['birthdate'] ?? ''); ?>" disabled
                                                    class="w-full bg-surface-secondary border border-content-secondary/20 rounded-xl pl-10 pr-4 py-3 text-sm text-content-secondary cursor-not-allowed">
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (in_array($_SESSION['user_role'] ?? '', ['owner', 'admin'], true)): ?>
                                        <?php $hasWhatsapp = !empty($user['whatsapp']); ?>
                                        <div>
                                            <label for="whatsapp" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5"><?php echo $lang['profile_whatsapp'] ?></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500"><i class="fab fa-whatsapp"></i></div>
                                                <input id="whatsapp" name="whatsapp" type="tel" inputmode="tel" autocomplete="tel" maxlength="20"
                                                    value="<?php echo htmlspecialchars(\App\Models\User::formatWhatsapp($user['whatsapp'] ?? null)); ?>"
                                                    placeholder="(11) 91234-5678"
                                                    class="w-full bg-input-base border <?php echo $hasWhatsapp ? 'border-content-secondary/20' : 'border-amber-500/60'; ?> rounded-xl pl-10 pr-4 py-3 text-sm text-content-primary placeholder-gray-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                                            </div>
                                            <p class="text-xs mt-1.5 <?php echo $hasWhatsapp ? 'text-content-secondary' : 'text-amber-600 dark:text-amber-400'; ?>">
                                                <?php echo $hasWhatsapp ? $lang['profile_whatsapp_hint'] : $lang['profile_whatsapp_missing']; ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-content-secondary/10 flex justify-end items-center gap-4">
                                <a href="<?php echo BASE_URL; ?>/dashboard" class="text-content-secondary hover:text-content-primary text-sm font-semibold px-4 py-2 transition-colors">
                                    <?php echo $lang['global_cancel'] ?>
                                </a>
                                <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-brand-500/20 transition-all transform hover:-translate-y-0.5">
                                    <?php echo $lang['profile_btn_save'] ?>
                                </button>
                            </div>

                        </form>
                    <?php else : ?>
                        <div class="text-center py-12">
                            <i class="fas fa-user-slash text-4xl text-content-secondary mb-4"></i>
                            <p class="text-content-secondary"><?php echo $lang['profile_error_data'] ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
    <script>
        // O menu do usuário e o botão de tema vêm do bundle.js (initNavbar e initTheme);
        // repetir aqui fazia cada clique ser tratado duas vezes.
        document.addEventListener('DOMContentLoaded', function() {
            // Image Preview Logic
            const avatarUpload = document.getElementById('avatar-upload');
            const avatarPreview = document.getElementById('avatar-preview');
            if (avatarUpload && avatarPreview) {
                avatarUpload.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => avatarPreview.src = e.target.result;
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>

</body>
</html>