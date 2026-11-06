<?php
$lang = require BASE_PATH . '/includes/i18n.php';
// Recupera dados da sessão para o header (caso não venha do $data)
$userName = $_SESSION['user_name'] ?? 'Usuário';
$userAvatar = $_SESSION['user_avatar'] ?? null;
$userId = $_SESSION['user_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma']; ?>" class="transition-colors duration-500">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($lang['config_security'] . ' · Kolae'); ?></title>

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

        #password-strength-bar {
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.4s ease;
        }
    </style>
</head>

<body class="bg-surface-base text-content-primary font-sans antialiased">

    <?php $backUrl = '/dashboard';
    $toastContext = 'security';
    include BASE_PATH . '/app/views/partials/owner_header.php'; ?>

    <main class="container mx-auto px-4 py-10 max-w-6xl">

        <div class="flex flex-col md:flex-row items-start gap-8">

            <aside class="w-full md:w-64 flex-shrink-0 animate-up">
                <h1 class="text-2xl font-bold text-content-primary mb-6 px-2"><?php echo $lang['global_menu_config'] ?></h1>
                <?php $configActive = 'security'; include BASE_PATH . '/app/views/partials/config_nav.php'; ?>
            </aside>

            <section class="flex-1 w-full animate-up delay-100">
                <div class="bg-surface-elevated p-6 md:p-10 rounded-2xl border border-content-secondary/10 shadow-xl relative overflow-hidden">

                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-brand-500 to-brand-700"></div>

                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-content-secondary/10 pb-4 mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-content-primary"><?php echo $lang['security_change_password_title'] ?></h2>
                            <p class="text-sm text-content-secondary"><?php echo $lang['security_change_password_text'] ?></p>
                        </div>
                    </div>



                    <form action="<?php echo BASE_URL; ?>/dashboard/perfil/seguranca/atualizar" method="POST" class="max-w-lg space-y-6">
                        <?php echo \App\Core\Csrf::field(); ?>

                        <div>
                            <label for="current_password" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5 ml-1"><?php echo $lang['security_present_password'] ?></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-lock"></i></div>
                                <input type="password" name="current_password" required 
                                    class="w-full bg-input-base border border-content-secondary/20 rounded-xl pl-10 pr-4 py-3 text-sm text-content-primary placeholder-gray-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                            </div>
                        </div>

                        <hr class="border-content-secondary/10">

                        <div>
                            <label for="new_password" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5 ml-1"><?php echo $lang['security_new_password'] ?></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-key"></i></div>
                                <input type="password" name="new_password" id="new_password" required 
                                    class="w-full bg-input-base border border-content-secondary/20 rounded-xl pl-10 pr-4 py-3 text-sm text-content-primary placeholder-gray-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                            </div>

                            <div class="mt-3">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs text-content-secondary"><?php echo $lang['strength_password_title'] ?></span>
                                    <span id="password-strength-text" class="text-xs font-bold text-content-secondary"><?php echo $lang['strength_password_weak'] ?></span>
                                </div>
                                <div class="w-full bg-surface-secondary border border-content-secondary/10 rounded-full h-1.5 overflow-hidden">
                                    <div id="password-strength-bar" class="h-full w-0 bg-red-500"></div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5 ml-1"><?php echo $lang['security_new_password_confirm'] ?></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-check-double"></i></div>
                                <input type="password" name="confirm_password" required 
                                    class="w-full bg-input-base border border-content-secondary/20 rounded-xl pl-10 pr-4 py-3 text-sm text-content-primary placeholder-gray-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                            </div>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-4">
                            <a href="<?php echo BASE_URL; ?>/dashboard/" class="text-content-secondary hover:text-content-primary text-sm font-semibold px-4 py-2 transition-colors"><?php echo $lang['global_cancel'] ?></a>
                            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-brand-500/20 transition-all transform hover:-translate-y-0.5">
                                <?php echo $lang['security_update_password_btn'] ?>
                            </button>
                        </div>

                    </form>

                </div>
            </section>
        </div>
    </main>

    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Medidor de Força de Senha
            const newPasswordInput = document.getElementById('new_password');
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('password-strength-text');

            if (newPasswordInput) {
                newPasswordInput.addEventListener('input', () => {
                    const password = newPasswordInput.value;
                    let score = 0;
                    if (password.length > 0) score++;
                    if (password.length >= 8) score++;
                    if (/[A-Z]/.test(password)) score++;
                    if (/[0-9]/.test(password)) score++;
                    if (/[^A-Za-z0-9]/.test(password)) score++;

                    let barColor = 'bg-red-500';
                    let text = '<?php echo $lang['strength_password_title'] ?>';
                    let width = '20%';

                    if (password.length === 0) {
                        width = '0%';
                        text = '<?php echo $lang['strength_password_veryweak'] ?>';
                    } else if (score < 3) {
                        barColor = 'bg-red-500';
                        text = '<?php echo $lang['strength_password_weak'] ?>';
                        width = '30%';
                    } else if (score < 4) {
                        barColor = 'bg-yellow-500';
                        text = '<?php echo $lang['strength_password_reasonable'] ?>';
                        width = '60%';
                    } else if (score < 5) {
                        barColor = 'bg-blue-500';
                        text = '<?php echo $lang['strength_password_strong'] ?>';
                        width = '80%';
                    } else {
                        barColor = 'bg-green-500';
                        text = '<?php echo $lang['strength_password_verystrong'] ?>';
                        width = '100%';
                    }

                    strengthBar.style.width = width;
                    strengthBar.className = `h-full rounded-full ${barColor}`;
                    strengthText.textContent = text;
                    
                    // Mantive a cor do texto do medidor como text-content-secondary ou cor da força, 
                    // para garantir que apareça bem no light mode.
                    strengthText.className = `text-xs font-bold ${barColor.replace('bg-', 'text-')}`;
                });
            }
        });
    </script>

</body>
</html>