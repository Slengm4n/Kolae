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
            <?php if (!empty($data['isPremium'])): // Kolae Premium do dono (D61): o selo leva às Configurações ?>
                <a href="<?php echo BASE_URL; ?>/dashboard/perfil/plano" data-premium-badge class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-amber-400 text-amber-950 px-3 py-1 text-xs font-bold shadow-sm hover:bg-amber-300 transition-colors"><i class="fas fa-crown text-[10px]" aria-hidden="true"></i><?php echo htmlspecialchars($lang['plan_badge']); ?></a>
            <?php endif; ?>
            <p class="text-content-secondary"><?php echo $lang['dashboard_user_activity']; ?></p>
        </section>

        <?php if (!empty($data['today'])):
            // Bloco "Hoje" (D67): o que pede atenção no dia, cada número levando à tela que resolve
            $t = $data['today'];
            $q = BASE_URL . '/dashboard/quadras';
            $tE = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
            $plural = fn(int $n, string $one, string $many) => strtr($lang[$n === 1 ? $one : $many], ['{n}' => $n]);
            $next = $t['matches']['next'];
            $agendaVenue = $next['venue_id'] ?? $t['first_venue_id'];
            $premium = $t['premium'];
            $ad = $t['ad'];
            $adVenue = $ad['venue_id'] ?? $t['first_venue_id'];
            $cards = [
                [
                    'key' => 'requests', 'icon' => 'fa-calendar-plus', 'title' => $lang['today_requests_title'],
                    'value' => $t['requests'], 'href' => BASE_URL . '/dashboard/notificacoes?aba=solicitacoes',
                    'text' => $t['requests'] ? $plural($t['requests'], 'today_requests_one', 'today_requests_many') : $lang['today_requests_none'],
                ],
                [
                    'key' => 'messages', 'icon' => 'fa-comments', 'title' => $lang['today_messages_title'],
                    'value' => $t['messages'], 'href' => BASE_URL . '/dashboard/mensagens',
                    'text' => $t['messages'] ? $plural($t['messages'], 'today_messages_one', 'today_messages_many') : $lang['today_messages_none'],
                ],
                [
                    'key' => 'matches', 'icon' => 'fa-futbol', 'title' => $lang['today_matches_title'],
                    'value' => $t['matches']['count'], 'href' => $agendaVenue ? $q . '/' . (int)$agendaVenue . '/agenda' : null,
                    'text' => $next
                        ? strtr($lang[$next['ongoing'] ? 'today_matches_now' : 'today_matches_next'], ['{start}' => $next['start'], '{end}' => $next['end'], '{venue}' => $next['venue_name']])
                        : $lang[$t['matches']['done'] ? 'today_matches_done' : 'today_matches_none'],
                ],
                [
                    'key' => 'premium', 'icon' => 'fa-crown', 'title' => $lang['today_premium_title'], 'value' => null,
                    'href' => $premium['state'] === 'premium' && $adVenue ? $q . '/' . (int)$adVenue . '/anuncio' : BASE_URL . '/dashboard/perfil/plano',
                    'text' => [
                        'free' => $lang['today_premium_free'],
                        'requested' => $lang['today_premium_requested'],
                        'expired' => strtr($lang['today_premium_expired'], ['{date}' => $premium['until'] ? date('d/m', strtotime($premium['until'])) : '']),
                        'premium' => $premium['until'] ? strtr($lang['today_premium_until'], ['{date}' => date('d/m', strtotime($premium['until']))]) : $lang['today_premium_nolimit'],
                    ][$premium['state']] ?? '',
                    'sub' => $premium['state'] === 'premium'
                        ? strtr($lang['today_ad_' . $ad['state']], ['{venue}' => (string)$ad['venue_name']]) : null,
                    'highlight' => $premium['state'] === 'premium',
                ],
            ]; ?>
            <section id="today" class="mb-14 animate-up delay-200" aria-labelledby="today-title">
                <h2 id="today-title" class="text-xl font-semibold text-content-primary mb-4"><?php echo $tE($lang['today_title']); ?></h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <?php foreach ($cards as $c):
                        $on = $c['value'] !== null ? $c['value'] > 0 : !empty($c['highlight']);
                        $tag = $c['href'] ? 'a' : 'div'; ?>
                        <<?php echo $tag; ?> <?php echo $c['href'] ? 'href="' . $tE($c['href']) . '"' : ''; ?> data-today="<?php echo $c['key']; ?>" data-value="<?php echo $c['value'] ?? ''; ?>"
                            class="group flex items-start gap-4 p-5 rounded-2xl border transition-all <?php echo $on ? 'bg-brand-500/5 border-brand-500/30' : 'bg-surface-elevated border-content-secondary/10'; ?> <?php echo $c['href'] ? 'hover:border-brand-500/50 hover:shadow-lg hover:-translate-y-0.5' : ''; ?>">
                            <span class="w-11 h-11 shrink-0 rounded-xl flex items-center justify-center text-lg <?php echo $on ? 'bg-brand-600 text-white' : 'bg-surface-secondary text-content-secondary'; ?>" aria-hidden="true"><i class="fas <?php echo $c['icon']; ?>"></i></span>
                            <span class="flex-1 min-w-0">
                                <span class="flex items-baseline justify-between gap-2">
                                    <span class="text-sm font-semibold text-content-secondary"><?php echo $tE($c['title']); ?></span>
                                    <?php if ($c['value'] !== null): ?><span class="text-2xl font-bold tabular-nums <?php echo $on ? 'text-brand-600 dark:text-brand-400' : 'text-content-secondary/60'; ?>"><?php echo (int)$c['value']; ?></span><?php endif; ?>
                                </span>
                                <span class="block mt-1 text-sm <?php echo $on ? 'text-content-primary font-medium' : 'text-content-secondary'; ?>" data-text><?php echo $tE($c['text']); ?></span>
                                <?php if (!empty($c['sub'])): ?><span class="block mt-0.5 text-xs text-content-secondary truncate" data-sub><?php echo $tE($c['sub']); ?></span><?php endif; ?>
                            </span>
                        </<?php echo $tag; ?>>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="animate-up delay-300">
            <div class="flex justify-between items-center gap-4 mb-6">
                <h2 class="text-2xl font-bold text-content-primary flex items-center gap-2"><i class="fas fa-map-marked-alt text-brand-500"></i> <?php echo $lang['dashboard_user_venues_title']; ?></h2>
                <?php if (!empty($userVenues)): ?>
                    <a href="<?php echo BASE_URL; ?>/dashboard/quadras/criar" id="new-venue" class="shrink-0 inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold transition-colors"><span><i class="fas fa-plus" aria-hidden="true"></i></span><?php echo $lang['today_new_venue']; ?></a>
                <?php endif; ?>
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