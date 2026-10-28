<?php
$lang = require BASE_PATH . '/includes/i18n.php';

$isLoggedIn = isset($_SESSION['user_id']);
$featuredVenues = $featuredVenues ?? [];

// Destinos dos botões principais conforme a sessão
$startUrl = BASE_URL . ($isLoggedIn ? '/dashboard' : '/register');
$ownerUrl = BASE_URL . ($isLoggedIn ? '/dashboard/quadras/criar' : '/register');

$pageTitle = 'Kolae · ' . $lang['global_slogan_headline'];
$pageDescription = $lang['home_hero_subtitle'];

$steps = [
    ['icon' => 'fa-map-location-dot', 'title' => $lang['home_how_step1_title'], 'text' => $lang['home_how_step1_text']],
    ['icon' => 'fa-clock',            'title' => $lang['home_how_step2_title'], 'text' => $lang['home_how_step2_text']],
    ['icon' => 'fa-comments',         'title' => $lang['home_how_step3_title'], 'text' => $lang['home_how_step3_text']],
];
$statuses = [
    ['class' => 'available', 'label' => $lang['home_status_available'], 'text' => $lang['home_status_available_text']],
    ['class' => 'in-use',    'label' => $lang['home_status_in_use'],    'text' => $lang['home_status_in_use_text']],
    ['class' => 'closed',    'label' => $lang['home_status_closed'],    'text' => $lang['home_status_closed_text']],
];
$partners = [
    ['logo_fatec.png', 'Fatec'],
    ['logo_atletica_sagui.png', 'Atlética Sagui'],
    ['logo_brtz.png', 'BRTZ'],
    ['logo_leos_de_ferraz.png', 'Leos de Ferraz'],
];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['idioma'] ?? 'pt-br'); ?>" class="scroll-smooth">

<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
    <style>
        @keyframes kolae-marquee { to { transform: translateX(-50%); } }
        @keyframes kolae-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        @keyframes kolae-drift { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(-28px, 22px); } }
        .kolae-marquee { animation: kolae-marquee 28s linear infinite; }
        .kolae-float { animation: kolae-float 6s ease-in-out infinite; }
        .kolae-drift { animation: kolae-drift 14s ease-in-out infinite; }
        /* Mapa ilustrado da prévia do app */
        .kolae-map {
            background-color: #e8eef6;
            background-image:
                linear-gradient(90deg, rgba(255, 255, 255, .9) 2px, transparent 2px),
                linear-gradient(rgba(255, 255, 255, .9) 2px, transparent 2px),
                linear-gradient(90deg, rgba(0, 0, 0, .035) 1px, transparent 1px),
                linear-gradient(rgba(0, 0, 0, .035) 1px, transparent 1px);
            background-size: 96px 96px, 96px 96px, 24px 24px, 24px 24px;
        }
        @media (prefers-reduced-motion: reduce) {
            .kolae-marquee, .kolae-float, .kolae-drift { animation: none; }
        }
    </style>
</head>

<body class="bg-surface-base text-content-primary font-sans antialiased overflow-x-hidden transition-colors duration-500">

    <?php include BASE_PATH . '/app/views/partials/public_header.php'; ?>

    <main>
        <!-- ===================== Abertura ===================== -->
        <section class="relative isolate overflow-hidden bg-neutral-950 text-white">
            <video autoplay muted loop playsinline aria-hidden="true" class="absolute inset-0 -z-20 h-full w-full object-cover opacity-35">
                <source src="<?php echo BASE_URL; ?>/assets/img/hero-bg.mp4" type="video/mp4">
            </video>
            <div class="absolute inset-0 -z-10 bg-gradient-to-br from-black via-black/80 to-brand-900/70" aria-hidden="true"></div>
            <div class="absolute -right-32 top-1/3 -z-10 h-[28rem] w-[28rem] rounded-full bg-brand-500/30 blur-3xl" aria-hidden="true"></div>

            <div class="container mx-auto px-4 sm:px-6 pt-32 pb-20 lg:pt-36 lg:pb-28 grid lg:grid-cols-2 gap-14 items-center lg:min-h-[100svh]">
                <div class="text-center lg:text-left">
                    <p class="hero-rise inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-xs sm:text-sm font-medium text-white/90 backdrop-blur-md">
                        <span class="pin-ping h-2 w-2 rounded-full bg-brand-400 !relative" aria-hidden="true"></span>
                        <?php echo htmlspecialchars($lang['home_hero_kicker']); ?>
                    </p>
                    <h1 class="hero-rise mt-6 text-[2.6rem] leading-[1.05] sm:text-6xl lg:text-7xl font-semibold tracking-tight [overflow-wrap:anywhere]" style="--rise-delay: 120ms">
                        <?php echo htmlspecialchars($lang['global_slogan_headline']); ?>
                    </h1>
                    <p class="hero-rise mt-6 mx-auto lg:mx-0 max-w-xl text-base sm:text-lg text-white/75 leading-relaxed" style="--rise-delay: 240ms">
                        <?php echo htmlspecialchars($lang['home_hero_subtitle']); ?>
                    </p>
                    <div class="hero-rise mt-10 flex flex-col sm:flex-row gap-3 justify-center lg:justify-start" style="--rise-delay: 360ms">
                        <a href="<?php echo $startUrl; ?>" class="group inline-flex items-center justify-center gap-2 rounded-full bg-brand-500 px-8 py-3.5 font-semibold text-white shadow-xl shadow-brand-500/30 hover:bg-brand-400 hover:shadow-brand-500/50 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300">
                            <?php echo htmlspecialchars($isLoggedIn ? $lang['global_home_panel'] : $lang['home_start_free']); ?>
                            <span class="transition-transform duration-300 group-hover:translate-x-1" aria-hidden="true"><i class="fas fa-arrow-right text-sm"></i></span>
                        </a>
                        <a href="#para-donos" class="inline-flex items-center justify-center gap-2 rounded-full border border-white/25 px-8 py-3.5 font-semibold text-white hover:bg-white/10 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300">
                            <i class="fas fa-store text-sm" aria-hidden="true"></i>
                            <?php echo htmlspecialchars($lang['home_nav_owners']); ?>
                        </a>
                    </div>
                </div>

                <!-- Prévia do app (ilustração; dados fictícios) -->
                <div class="hero-rise hidden md:flex flex-col items-center" style="--rise-delay: 480ms" aria-label="<?php echo htmlspecialchars($lang['home_preview_label']); ?>" role="img">
                    <span class="mb-5 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-white/80 backdrop-blur-md">
                        <i class="fas fa-mobile-screen-button mr-1" aria-hidden="true"></i><?php echo htmlspecialchars($lang['home_preview_label']); ?>
                    </span>
                    <div class="kolae-float relative w-[290px] rounded-[2.75rem] border-[10px] border-neutral-900 bg-neutral-900 shadow-2xl shadow-brand-500/25 ring-1 ring-white/10">
                        <div class="absolute left-1/2 top-0 z-20 h-6 w-28 -translate-x-1/2 rounded-b-2xl bg-neutral-900" aria-hidden="true"></div>
                        <div class="kolae-map relative h-[560px] overflow-hidden rounded-[2rem] text-neutral-900">
                            <!-- Ruas e pinos -->
                            <div class="absolute -left-10 top-40 h-3 w-[140%] -rotate-12 bg-white/95" aria-hidden="true"></div>
                            <div class="absolute left-24 -top-10 h-[120%] w-3 rotate-6 bg-white/95" aria-hidden="true"></div>
                            <div class="absolute left-[30%] top-[22%] h-24 w-24 rounded-3xl bg-emerald-200/60" aria-hidden="true"></div>
                            <span class="pin-ping left-[18%] top-[32%] flex h-9 w-9 items-center justify-center rounded-full bg-[#0084FF] text-white shadow-lg ring-4 ring-white" aria-hidden="true"><i class="fas fa-futbol text-sm"></i></span>
                            <span class="pin-ping left-[62%] top-[20%] flex h-9 w-9 items-center justify-center rounded-full bg-[#D96500] text-white shadow-lg ring-4 ring-white" style="--ping-delay: 1.1s" aria-hidden="true"><i class="fas fa-volleyball text-sm"></i></span>
                            <span class="absolute left-[70%] top-[45%] flex h-9 w-9 items-center justify-center rounded-full bg-[#8A8A8A] text-white shadow-lg ring-4 ring-white" aria-hidden="true"><i class="fas fa-basketball text-sm"></i></span>

                            <!-- Barra superior -->
                            <div class="absolute inset-x-3 top-9 flex items-center gap-2 rounded-2xl bg-white px-3 py-2.5 shadow-md">
                                <i class="fas fa-magnifying-glass text-xs text-neutral-400" aria-hidden="true"></i>
                                <span class="font-display text-sm font-semibold text-[#0084FF]">Kolae</span>
                            </div>

                            <!-- Cartões de quadra (como no app) -->
                            <div class="absolute inset-x-0 bottom-0 rounded-t-3xl bg-white p-4 pt-3 shadow-[0_-10px_30px_rgba(0,0,0,0.12)]">
                                <div class="mx-auto mb-3 h-1.5 w-10 rounded-full bg-neutral-200" aria-hidden="true"></div>
                                <?php
                                $previewCards = [
                                    ['Quadra Central', 'fa-futbol', 'available', $lang['home_status_available'] . ' · ' . $lang['home_preview_until'] . ' 19:00'],
                                    ['Arena Norte', 'fa-volleyball', 'in-use', $lang['home_status_in_use'] . ' · ' . $lang['home_preview_until'] . ' 21:00'],
                                ];
                                // O selo do primeiro cartão alterna entre os estados (home_motion.js) para mostrar o status mudando
                                $liveLabels = 'data-live-status data-label-available="' . htmlspecialchars($previewCards[0][3]) . '" data-label-in-use="' . htmlspecialchars($lang['home_status_in_use'] . ' · ' . $lang['home_preview_until'] . ' 20:00') . '"';
                                foreach ($previewCards as $i => [$name, $icon, $status, $label]): ?>
                                    <div class="flex items-center gap-3 rounded-2xl border border-neutral-100 p-2.5 mb-2 last:mb-0">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#0084FF] to-[#004F99] text-white"><i class="fas <?php echo $icon; ?>" aria-hidden="true"></i></div>
                                        <div class="min-w-0">
                                            <p class="font-display text-sm font-semibold truncate"><?php echo $name; ?></p>
                                            <span class="status-badge status-badge--<?php echo $status; ?> mt-1 !text-[10px] !py-0.5" <?php echo $i === 0 ? $liveLabels : ''; ?>><?php echo htmlspecialchars($label); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== Como funciona ===================== -->
        <section id="como-funciona" class="scroll-mt-20 py-20 md:py-28">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="reveal max-w-2xl">
                    <p class="text-sm font-semibold uppercase tracking-widest text-brand-600 dark:text-brand-400"><?php echo htmlspecialchars($lang['home_how_title']); ?></p>
                    <h2 class="mt-3 text-3xl md:text-5xl font-semibold tracking-tight"><?php echo htmlspecialchars($lang['home_how_subtitle']); ?></h2>
                </div>
                <ol class="mt-14 grid gap-6 md:grid-cols-3">
                    <?php foreach ($steps as $i => $step): ?>
                        <li class="reveal group relative rounded-3xl border border-content-secondary/15 bg-surface-elevated p-8 shadow-sm hover:-translate-y-1 hover:shadow-xl hover:border-brand-500/30" style="--reveal-delay: <?php echo $i * 120; ?>ms">
                            <span class="font-display absolute right-7 top-6 text-6xl font-semibold text-content-secondary/15 transition-colors duration-300 group-hover:text-brand-500/25" aria-hidden="true"><?php echo $i + 1; ?></span>
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-500 text-white shadow-lg shadow-brand-500/25 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6" aria-hidden="true">
                                <i class="fas <?php echo $step['icon']; ?>"></i>
                            </span>
                            <h3 class="mt-6 text-xl font-semibold"><?php echo htmlspecialchars($step['title']); ?></h3>
                            <p class="mt-2 text-sm leading-relaxed text-content-secondary"><?php echo htmlspecialchars($step['text']); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </section>

        <!-- ===================== Status ===================== -->
        <section class="py-20 md:py-28 bg-surface-secondary">
            <div class="container mx-auto px-4 sm:px-6 grid gap-12 lg:grid-cols-2 items-center">
                <div class="reveal">
                    <h2 class="text-3xl md:text-5xl font-semibold tracking-tight"><?php echo htmlspecialchars($lang['home_status_title']); ?></h2>
                    <p class="mt-5 max-w-lg text-content-secondary leading-relaxed"><?php echo htmlspecialchars($lang['home_status_text']); ?></p>
                </div>
                <ul class="space-y-4">
                    <?php foreach ($statuses as $i => $s): ?>
                        <li class="reveal flex items-center gap-5 rounded-3xl border border-content-secondary/15 bg-surface-elevated p-5 sm:p-6" style="--reveal-delay: <?php echo 100 + $i * 120; ?>ms">
                            <span class="status-badge status-badge--<?php echo $s['class']; ?> <?php echo $s['class'] === 'available' ? 'is-live' : ''; ?> shrink-0 min-w-[7.5rem] justify-center !text-sm !py-1.5"><?php echo htmlspecialchars($s['label']); ?></span>
                            <p class="text-sm text-content-secondary"><?php echo htmlspecialchars($s['text']); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <!-- ===================== Quadras reais ===================== -->
        <?php if ($featuredVenues): ?>
            <section id="quadras" class="scroll-mt-20 py-20 md:py-28">
                <div class="container mx-auto px-4 sm:px-6">
                    <div class="reveal flex flex-col md:flex-row md:items-end md:justify-between gap-6">
                        <div class="max-w-2xl">
                            <h2 class="text-3xl md:text-5xl font-semibold tracking-tight"><?php echo htmlspecialchars($lang['home_venues_title']); ?></h2>
                            <p class="mt-4 text-content-secondary"><?php echo htmlspecialchars($lang['home_venues_subtitle']); ?></p>
                        </div>
                        <a href="<?php echo $startUrl; ?>" class="group inline-flex items-center gap-2 self-start md:self-auto font-semibold text-brand-600 dark:text-brand-400 hover:underline">
                            <?php echo htmlspecialchars($isLoggedIn ? $lang['global_home_panel'] : $lang['home_venues_cta']); ?>
                            <span class="transition-transform duration-300 group-hover:translate-x-1" aria-hidden="true"><i class="fas fa-arrow-right text-sm"></i></span>
                        </a>
                    </div>

                    <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($featuredVenues as $i => $venue): ?>
                            <article class="reveal group overflow-hidden rounded-3xl border border-content-secondary/15 bg-surface-elevated shadow-sm hover:-translate-y-1 hover:shadow-xl hover:border-brand-500/30" style="--reveal-delay: <?php echo $i * 120; ?>ms">
                                <div class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br from-brand-500 to-brand-800">
                                    <?php if ($venue['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($venue['image_url']); ?>" alt="<?php echo htmlspecialchars($venue['name']); ?>" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                    <?php else: ?>
                                        <div class="flex h-full w-full items-center justify-center text-white/80" aria-hidden="true">
                                            <i class="fas fa-volleyball text-5xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-6">
                                    <h3 class="text-xl font-semibold leading-snug"><?php echo htmlspecialchars($venue['name']); ?></h3>
                                    <?php if ($venue['city']): ?>
                                        <p class="mt-1 flex items-center gap-1.5 text-sm text-content-secondary">
                                            <i class="fas fa-location-dot text-brand-500" aria-hidden="true"></i>
                                            <?php echo htmlspecialchars(trim($venue['neighborhood'] . ', ' . $venue['city'], ', ')); ?>
                                        </p>
                                    <?php endif; ?>
                                    <ul class="mt-4 flex flex-wrap gap-2 text-xs font-medium">
                                        <?php if ($venue['floor_lang_key'] && isset($lang[$venue['floor_lang_key']])): ?>
                                            <li class="rounded-full bg-surface-secondary px-3 py-1 text-content-secondary"><?php echo htmlspecialchars($lang[$venue['floor_lang_key']]); ?></li>
                                        <?php endif; ?>
                                        <?php if ($venue['is_covered']): ?>
                                            <li class="rounded-full bg-surface-secondary px-3 py-1 text-content-secondary"><i class="fas fa-umbrella mr-1" aria-hidden="true"></i><?php echo htmlspecialchars($lang['home_venues_covered']); ?></li>
                                        <?php endif; ?>
                                        <?php if ($venue['has_lighting']): ?>
                                            <li class="rounded-full bg-surface-secondary px-3 py-1 text-content-secondary"><i class="fas fa-lightbulb mr-1" aria-hidden="true"></i><?php echo htmlspecialchars($lang['home_venues_lighting']); ?></li>
                                        <?php endif; ?>
                                    </ul>
                                    <p class="mt-5 border-t border-content-secondary/15 pt-4 text-sm text-content-secondary">
                                        <?php if ($venue['price']): ?>
                                            <span class="font-display text-2xl font-semibold text-content-primary">R$ <?php echo $venue['price']; ?></span><?php echo htmlspecialchars($lang['home_venues_per_hour']); ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($lang['home_venues_price_on_request']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- ===================== Para donos ===================== -->
        <section id="para-donos" class="scroll-mt-20 pb-20 md:pb-28 <?php echo $featuredVenues ? '' : 'pt-20 md:pt-28'; ?>">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="reveal relative isolate overflow-hidden rounded-[2rem] bg-brand-500 px-6 py-14 sm:px-12 md:py-16 text-white">
                    <div class="kolae-drift absolute -right-24 -top-24 -z-10 h-80 w-80 rounded-full bg-white/10" aria-hidden="true"></div>
                    <div class="kolae-drift absolute -bottom-32 left-1/3 -z-10 h-80 w-80 rounded-full bg-brand-700/40" style="animation-delay: -6s" aria-hidden="true"></div>
                    <div class="grid gap-10 lg:grid-cols-2 items-center">
                        <div>
                            <h2 class="text-3xl md:text-5xl font-semibold tracking-tight"><?php echo htmlspecialchars($lang['home_owner_title']); ?></h2>
                            <p class="mt-5 max-w-lg text-white/85 leading-relaxed"><?php echo htmlspecialchars($lang['home_owner_text']); ?></p>
                            <a href="<?php echo $ownerUrl; ?>" class="group mt-8 inline-flex items-center gap-2 rounded-full bg-white px-8 py-3.5 font-semibold text-brand-700 shadow-xl hover:bg-brand-50 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                                <?php echo htmlspecialchars($lang['home_owner_cta']); ?>
                                <span class="transition-transform duration-300 group-hover:translate-x-1" aria-hidden="true"><i class="fas fa-arrow-right text-sm"></i></span>
                            </a>
                        </div>
                        <ul class="space-y-4">
                            <?php $p = 0; foreach (['fa-map-location-dot' => 'home_owner_point1', 'fa-calendar-days' => 'home_owner_point2', 'fa-whatsapp' => 'home_owner_point3'] as $icon => $key): ?>
                                <li class="reveal flex items-center gap-4 rounded-2xl bg-white/10 px-5 py-4 backdrop-blur-sm hover:bg-white/15" style="--reveal-delay: <?php echo 200 + ($p++) * 120; ?>ms">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-brand-600" aria-hidden="true">
                                        <i class="<?php echo $icon === 'fa-whatsapp' ? 'fab' : 'fas'; ?> <?php echo $icon; ?>"></i>
                                    </span>
                                    <span class="font-medium"><?php echo htmlspecialchars($lang[$key]); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== Sobre nós ===================== -->
        <section id="sobre-nos" class="scroll-mt-20 py-20 md:py-28 bg-surface-secondary">
            <div class="container mx-auto px-4 sm:px-6 grid gap-12 lg:grid-cols-2 items-center">
                <div class="reveal relative">
                    <div class="absolute -inset-4 -z-10 rounded-[2rem] bg-brand-500/15 blur-2xl" aria-hidden="true"></div>
                    <img src="<?php echo BASE_URL; ?>/assets/img/about_us_img.png" alt="" class="w-full rounded-[2rem] shadow-xl" loading="lazy">
                </div>
                <div class="reveal" style="--reveal-delay: 150ms">
                    <p class="text-sm font-semibold uppercase tracking-widest text-brand-600 dark:text-brand-400"><?php echo htmlspecialchars($lang['global_menu_about']); ?></p>
                    <h2 class="mt-3 text-3xl md:text-5xl font-semibold tracking-tight"><?php echo htmlspecialchars($lang['global_title_about']); ?></h2>
                    <p class="mt-5 text-content-secondary leading-relaxed"><?php echo htmlspecialchars($lang['global_text_about']); ?></p>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        <?php foreach ([['fa-users', 'home_title_community', 'home_text_community'], ['fa-location-dot', 'home_title_location', 'home_text_location']] as [$icon, $title, $text]): ?>
                            <div class="rounded-3xl border border-content-secondary/15 bg-surface-elevated p-6">
                                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-500/10 text-brand-600 dark:text-brand-400" aria-hidden="true"><i class="fas <?php echo $icon; ?>"></i></span>
                                <h3 class="mt-4 text-lg font-semibold"><?php echo htmlspecialchars($lang[$title]); ?></h3>
                                <p class="mt-1 text-sm text-content-secondary"><?php echo htmlspecialchars($lang[$text]); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== Parceiros ===================== -->
        <section class="py-20 md:py-24">
            <div class="reveal container mx-auto px-4 sm:px-6 text-center">
                <h2 class="text-2xl md:text-4xl font-semibold tracking-tight"><?php echo htmlspecialchars($lang['home_title_supporters']); ?></h2>
                <p class="mx-auto mt-4 max-w-2xl text-content-secondary"><?php echo htmlspecialchars($lang['home_text_supporters']); ?></p>
            </div>
            <div class="relative mt-12 overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_15%,black_85%,transparent)]">
                <ul class="kolae-marquee flex w-max items-center hover:[animation-play-state:paused]">
                    <?php for ($copy = 0; $copy < 2; $copy++): // duas cópias para o loop sem emenda ?>
                        <?php foreach ($partners as [$file, $name]): ?>
                            <li class="mx-8 sm:mx-12 flex h-16 w-40 shrink-0 items-center justify-center" <?php echo $copy ? 'aria-hidden="true"' : ''; ?>>
                                <img src="<?php echo BASE_URL; ?>/assets/img/<?php echo $file; ?>" alt="<?php echo $copy ? '' : htmlspecialchars($name); ?>" loading="lazy" class="max-h-14 w-auto object-contain grayscale opacity-70 transition hover:grayscale-0 hover:opacity-100 dark:opacity-60">
                            </li>
                        <?php endforeach; ?>
                    <?php endfor; ?>
                </ul>
            </div>
        </section>
    </main>

    <?php include BASE_PATH . '/app/views/partials/public_footer.php'; ?>

    <script type="module" src="<?php echo BASE_URL; ?>/assets/js/bundle.js"></script>
</body>

</html>
