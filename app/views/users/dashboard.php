<?php
$lang = require BASE_PATH . '/includes/i18n.php';

$userName = $data['userName'] ?? 'Utilizador';
$userVenues = $data['userVenues'] ?? [];
$showCnpjModal = $data['showCnpjModal'] ?? false;
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma']; ?>" class="transition-colors duration-500">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($lang['global_home_panel'] . ' · Kolae'); ?></title>

    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">

    <link href="<?php echo \App\Core\ViewHelper::asset('assets/css/style.css'); ?>" rel="stylesheet">
    <?php include 'app/views/partials/theme_script.php'; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <style>
        
        .animate-up {
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .delay-100 {
            animation-delay: 100ms;
        }

        .delay-200 {
            animation-delay: 200ms;
        }

        .delay-300 {
            animation-delay: 300ms;
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

<body class="bg-surface-base text-content-primary font-poppins antialiased min-h-screen flex flex-col transition-colors duration-500">

    <?php $backUrl = '/dashboard';
    $toastContext = 'dashboard';
    include BASE_PATH . '/app/views/partials/owner_header.php'; ?>

    <main class="container mx-auto px-4 py-8 md:py-12 max-w-7xl flex-grow">

        <div class="mb-8 animate-up">
            <?php if ($showCnpjModal): ?>
                <div class="bg-yellow-500/10 border border-yellow-500/30 text-yellow-700 dark:text-yellow-200 p-4 md:p-6 rounded-2xl flex flex-col md:flex-row items-center justify-between gap-4 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-yellow-500/20 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-2xl text-yellow-600 dark:text-yellow-400"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-yellow-800 dark:text-yellow-100"><?php echo $lang['dashboard_cnpj_title']; ?></h3>
                            <p class="text-sm opacity-80"><?php echo $lang['dashboard_cnpj_text']; ?></p>
                        </div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/dashboard/cnpj" class="w-full md:w-auto bg-yellow-500 hover:bg-yellow-400 text-white dark:text-black font-bold py-2.5 px-6 rounded-xl text-center transition-all hover:shadow-lg hover:-translate-y-0.5 whitespace-nowrap">
                        <?php echo $lang['dashboard_cnpj_add']; ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (!empty($data['emailPending'])): ?>
                <!-- Confirmação de e-mail (D58): só um aviso, não bloqueia nada -->
                <div id="email-verify" class="<?php echo $showCnpjModal ? 'mt-4 ' : ''; ?>bg-brand-500/5 border border-brand-500/20 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                    <div class="flex items-start sm:items-center gap-3 flex-1 min-w-0">
                        <span class="w-10 h-10 rounded-full bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center shrink-0" aria-hidden="true"><i class="fas fa-envelope-circle-check"></i></span>
                        <p class="text-sm text-content-primary">
                            <span class="font-semibold"><?php echo htmlspecialchars($lang['verify_title']); ?></span>
                            <span class="text-content-secondary"><?php echo htmlspecialchars(strtr($lang['verify_text'], ['{email}' => $data['userEmail']])); ?></span>
                        </p>
                    </div>
                    <form method="POST" action="<?php echo BASE_URL; ?>/dashboard/verificar-email/reenviar" class="shrink-0">
                        <?php echo \App\Core\Csrf::field(); ?>
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-brand-600 dark:text-brand-400 border border-brand-500/30 hover:bg-brand-500/10 transition-colors">
                            <i class="fas fa-paper-plane text-xs" aria-hidden="true"></i><?php echo htmlspecialchars($lang['verify_resend']); ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        </div>

        <section class="mb-12 animate-up delay-100">
            <h1 class="text-3xl md:text-4xl font-bold text-content-primary mb-2">
                <?php echo $lang['dashboard_user_greetings']; ?> <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-500 to-brand-700"><?php echo explode(' ', htmlspecialchars($userName))[0]; ?>!</span>
            </h1>
            <p class="text-content-secondary"><?php echo $lang['dashboard_user_activity']; ?></p>
        </section>

        <section class="mb-16 animate-up delay-200">
            <h2 class="text-xl font-semibold text-content-primary mb-6 flex items-center gap-2"><i class="fas fa-bolt text-yellow-500"></i> <?php echo $lang['dashboard_user_actions']; ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <a href="<?php echo BASE_URL; ?>/dashboard/quadras/criar" class="group bg-surface-elevated p-6 rounded-2xl border border-content-secondary/10 hover:border-brand-500/50 hover:shadow-lg hover:shadow-brand-500/5 transition-all duration-300 hover:-translate-y-1 flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-brand-500/10 flex items-center justify-center text-brand-600 dark:text-brand-400 group-hover:bg-brand-700 group-hover:text-white transition-colors">
                        <i class="fas fa-plus text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-content-primary group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors"><?php echo $lang['dashboard_user_location_title']; ?></h3>
                        <p class="text-sm text-content-secondary"><?php echo $lang['dashboard_user_location_text']; ?></p>
                    </div>
                    <i class="fas fa-arrow-right ml-auto text-content-secondary group-hover:text-brand-500 group-hover:translate-x-1 transition-all"></i>
                </a>

                <a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="group bg-surface-elevated p-6 rounded-2xl border border-content-secondary/10 hover:border-purple-500/50 hover:shadow-lg hover:shadow-purple-500/5 transition-all duration-300 hover:-translate-y-1 flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-purple-500/10 flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:bg-purple-500 group-hover:text-white transition-colors">
                        <i class="fas fa-user-cog text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-content-primary group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors"><?php echo $lang['dashboard_user_account_title']; ?></h3>
                        <p class="text-sm text-content-secondary"><?php echo $lang['dashboard_user_account_text']; ?></p>
                    </div>
                    <i class="fas fa-arrow-right ml-auto text-content-secondary group-hover:text-purple-500 group-hover:translate-x-1 transition-all"></i>
                </a>
            </div>
        </section>

        <section class="animate-up delay-300">
            <div class="flex justify-between items-end mb-6">
                <h2 class="text-2xl font-bold text-content-primary flex items-center gap-2"><i class="fas fa-map-marked-alt text-brand-500"></i> <?php echo $lang['dashboard_user_venues_title']; ?></h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php if (!empty($userVenues)): ?>
                    <?php foreach ($userVenues as $venue): ?>
                        <?php
                        $placeholder = BASE_URL . '/assets/img/venue_placeholder.svg';
                        $imageSrc = !empty($venue['image_path'])
                            ? htmlspecialchars(\App\Core\ImageHelper::thumbUrl(BASE_URL . '/uploads/venues/' . (int)$venue['id'] . '/' . rawurlencode(basename($venue['image_path']))))
                            : $placeholder;
                        ?>
                        <?php
                        // Selo ao vivo (D40): mesma regra da API; desativada tem selo próprio (D34)
                        $live = $venue['live_status'];
                        $disabled = $venue['status'] === 'unavailable';
                        $badgeClass = $disabled ? 'status-badge--closed' : (['available' => 'status-badge--available', 'in_use' => 'status-badge--in-use'][$live['status']] ?? 'status-badge--closed');
                        $badgeText = $disabled ? $lang['panel_status_disabled'] : $lang['home_status_' . $live['status']];
                        $badgeUntil = $disabled ? '' : \App\Core\VenueAccess::statusUntilText($live, $lang);
                        $venueBase = BASE_URL . '/dashboard/quadras';
                        ?>
                        <article class="venue-card group bg-surface-elevated rounded-2xl overflow-hidden border border-content-secondary/10 hover:border-content-secondary/30 hover:shadow-xl transition-all duration-300 flex flex-col" data-venue-id="<?php echo (int)$venue['id']; ?>">
                            <a href="<?php echo $venueBase . '/' . (int)$venue['id']; ?>/visao-geral" class="block">
                                <div class="relative aspect-video overflow-hidden">
                                    <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($venue['name']); ?>"
                                        class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700"
                                        onerror="this.onerror=null; this.src='<?php echo $placeholder; ?>';">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-60"></div>
                                    <span class="absolute top-3 left-3 status-badge <?php echo $badgeClass; ?> shadow-sm"><?php echo htmlspecialchars($badgeText); ?><?php if ($badgeUntil !== ''): ?> <span class="font-normal opacity-80">· <?php echo htmlspecialchars($badgeUntil); ?></span><?php endif; ?></span>
                                </div>
                                <div class="p-5 pb-3">
                                    <h3 class="font-bold text-lg text-content-primary truncate group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors"><?php echo htmlspecialchars($venue['name']); ?></h3>
                                    <div class="flex items-center gap-2 mt-2 text-content-secondary text-sm">
                                        <span><i class="fas fa-map-marker-alt"></i></span>
                                        <span class="truncate"><?php echo htmlspecialchars($venue['street'] . ', ' . $venue['number']); ?></span>
                                    </div>
                                </div>
                            </a>
                            <?php if (!$venue['has_hours'] && !$disabled): ?>
                                <a href="<?php echo $venueBase . '/' . (int)$venue['id']; ?>/horario" class="no-hours mx-5 mb-3 px-3 py-2 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs font-medium flex items-start gap-2 hover:bg-amber-500/15 transition-colors">
                                    <span class="mt-0.5"><i class="fas fa-clock"></i></span><span><?php echo $lang['panel_no_hours']; ?></span>
                                </a>
                            <?php endif; ?>
                            <nav class="mt-auto grid grid-cols-3 border-t border-content-secondary/10 text-sm font-semibold" aria-label="<?php echo htmlspecialchars($lang['venue_tabs_label']); ?>">
                                <a href="<?php echo $venueBase; ?>/editar/<?php echo (int)$venue['id']; ?>" class="py-3 flex items-center justify-center gap-1.5 text-content-secondary hover:text-brand-600 dark:hover:text-brand-400 hover:bg-surface-secondary transition-colors"><span><i class="fas fa-pen text-xs"></i></span><?php echo $lang['venue_tab_data']; ?></a>
                                <a href="<?php echo $venueBase . '/' . (int)$venue['id']; ?>/horario" class="py-3 flex items-center justify-center gap-1.5 border-x border-content-secondary/10 text-content-secondary hover:text-brand-600 dark:hover:text-brand-400 hover:bg-surface-secondary transition-colors"><span><i class="fas fa-clock text-xs"></i></span><?php echo $lang['venue_tab_hours']; ?></a>
                                <a href="<?php echo $venueBase . '/' . (int)$venue['id']; ?>/agenda" class="py-3 flex items-center justify-center gap-1.5 text-content-secondary hover:text-brand-600 dark:hover:text-brand-400 hover:bg-surface-secondary transition-colors"><span><i class="fas fa-calendar-days text-xs"></i></span><?php echo $lang['venue_tab_agenda']; ?></a>
                            </nav>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full py-16 bg-surface-secondary/30 border-2 border-dashed border-content-secondary/20 rounded-3xl text-center flex flex-col items-center justify-center group hover:border-brand-500/50 transition-colors">
                        <div class="w-20 h-20 bg-surface-elevated rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300 shadow-sm">
                            <i class="fas fa-store-slash text-4xl text-content-secondary group-hover:text-brand-500 transition-colors"></i>
                        </div>
                        <h3 class="text-xl font-bold text-content-primary"><?php echo $lang['dashboard_venue_register_title']; ?></h3>
                        <p class="text-content-secondary mt-2 mb-6 max-w-sm"><?php echo $lang['dashboard_venue_register_subtitle']; ?></p>
                        <a href="<?php echo BASE_URL; ?>/dashboard/quadras/criar" class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-brand-500/20 transition-all hover:-translate-y-0.5">
                            <i class="fas fa-plus mr-2"></i> <?php echo $lang['dashboard_venue_register_btn']; ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>

</body>

</html>