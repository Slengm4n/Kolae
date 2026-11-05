<?php
$lang = require BASE_PATH . '/includes/i18n.php';
$venue = $data['venue'] ?? null;
$prefix = $data['routePrefix'] ?? '/dashboard';
$backUrl = $data['backUrl'] ?? '/dashboard'; // Painel de onde a pessoa veio (admin ou usuário)
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma']; ?>" class="transition-colors duration-500">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($lang['venue_tab_data'] . ($venue ? ' · ' . $venue['name'] : '') . ' · Kolae'); ?></title>

    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="<?php echo \App\Core\ViewHelper::asset('assets/css/style.css'); ?>" rel="stylesheet">
    
    <?php include 'app/views/partials/theme_script.php'; ?>

    <style>
        body { font-family: 'Poppins', sans-serif; overflow-x: hidden; }

        /* Cards Interativos Adaptados para Light/Dark */
        .option-card, .checkbox-card {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .option-card:hover, .checkbox-card:hover {
            transform: translateY(-2px);
            border-color: #0084FF; /* brand-500 */
        }
        .option-card.selected, .checkbox-card.selected {
            border-color: #0084FF;
            background-color: rgba(0, 132, 255, 0.05);
            box-shadow: 0 4px 10px rgba(0, 132, 255, 0.1);
        }
        .checkbox-card.selected i { color: #0084FF; }

        /* Upload */
        #drop-area.highlight {
            border-color: #0084FF;
            background-color: rgba(0, 132, 255, 0.05);
            transform: scale(1.01);
        }

        .animate-up { animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
        .delay-100 { animation-delay: 100ms; }
        .delay-200 { animation-delay: 200ms; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Opções de preço (D31) */
        .price-mode { cursor: pointer; }
        .price-mode > span { display: inline-block; padding: .55rem 1rem; border-radius: 9999px; border: 1px solid color-mix(in srgb, var(--color-content-secondary) 30%, transparent); font-size: .875rem; font-weight: 600; transition: all .15s; }
        .price-mode > span:hover { border-color: #0084FF; }
    </style>
</head>

<body class="bg-surface-base text-content-primary transition-colors duration-500">

    <?php include BASE_PATH . '/app/views/partials/owner_header.php'; ?>

    <main class="container mx-auto px-4 py-6 md:py-10 max-w-4xl">

        <?php if (!$venue): ?>
            <div class="text-center py-12 bg-surface-elevated rounded-2xl border border-red-500/20">
                <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                <h2 class="text-2xl font-bold text-content-primary"><?php echo $lang['global_error'] ?></h2>
                <p class="text-content-secondary mt-2"><?php echo $lang['edit_venue_not_found'] ?></p>
                <a href="<?php echo BASE_URL . $backUrl; ?>" class="mt-4 inline-block text-brand-600 dark:text-brand-400 hover:underline"><?php echo $lang['global_back'] ?></a>
            </div>
        <?php else: ?>

            <?php
            // Topo com o selo de status e as abas Dados, Horário e Agenda (D35)
            $activeTab = 'data';
            $routePrefix = $prefix;
            $status = $data['status'];
            include BASE_PATH . '/app/views/partials/venue_top.php';
            ?>

            <form id="venue-form" action="<?php echo BASE_URL . $prefix; ?>/quadras/atualizar/<?php echo $venue['id']; ?>" method="POST" enctype="multipart/form-data" class="space-y-8 pb-24 md:pb-8 animate-up">
                <?php echo \App\Core\Csrf::field(); ?>

                <input type="hidden" name="address_id" value="<?php echo $venue['address_id']; ?>">
                <input type="hidden" id="floor_type_input" name="floor_type" value="<?php echo htmlspecialchars($venue['floor_type']); ?>">
                <input type="hidden" id="court_capacity_input" name="court_capacity" value="<?php echo htmlspecialchars($venue['court_capacity']); ?>">
                <input type="hidden" id="leisure_area_capacity_input" name="leisure_area_capacity" value="<?php echo htmlspecialchars($venue['leisure_area_capacity']); ?>">

                <?php
                $formMessages = [
                    'updated' => ['green', 'fa-check-circle', $lang['data_saved']],
                    'price' => ['red', 'fa-exclamation-circle', $lang['data_price_invalid']],
                    'whatsapp' => ['red', 'fa-exclamation-circle', $lang['data_whatsapp_invalid']],
                    'social_instagram' => ['red', 'fa-exclamation-circle', $lang['data_socials_error_instagram']],
                    'social_tiktok' => ['red', 'fa-exclamation-circle', $lang['data_socials_error_tiktok']],
                    'social_facebook_url' => ['red', 'fa-exclamation-circle', $lang['data_socials_error_facebook']],
                    'social_website_url' => ['red', 'fa-exclamation-circle', $lang['data_socials_error_website']],
                ];
                $formMsgKey = ($_GET['status'] ?? null) === 'updated' ? 'updated' : ($_GET['error'] ?? null);
                if (isset($formMessages[$formMsgKey])): [$c, $ic, $text] = $formMessages[$formMsgKey]; ?>
                    <div class="p-4 rounded-xl border flex items-start gap-3 <?php echo $c === 'green' ? 'border-green-500/30 bg-green-500/10 text-green-700 dark:text-green-400' : 'border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-400'; ?>" role="<?php echo $c === 'green' ? 'status' : 'alert'; ?>">
                        <span class="mt-0.5"><i class="fas <?php echo $ic; ?>"></i></span><p class="text-sm font-medium"><?php echo $text; ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($data['needsWhatsapp'])): ?>
                    <!-- WhatsApp do dono (D38): obrigatório para quem tem quadra -->
                    <div class="bg-surface-elevated p-6 md:p-8 rounded-2xl border-2 border-amber-500/40 shadow-lg">
                        <h2 class="text-xl font-bold text-content-primary mb-1 flex items-center gap-3"><span class="text-green-500"><i class="fab fa-whatsapp"></i></span><?php echo $lang['data_whatsapp_title']; ?></h2>
                        <p class="text-sm text-content-secondary mb-4"><?php echo $lang['data_whatsapp_text']; ?></p>
                        <label for="whatsapp" class="sr-only"><?php echo $lang['data_whatsapp_title']; ?></label>
                        <input type="tel" id="whatsapp" name="whatsapp" required inputmode="tel" autocomplete="tel" placeholder="(11) 99999-0000"
                            class="w-full md:w-80 bg-input-base border border-content-secondary/20 rounded-xl px-4 py-3 text-content-primary focus:border-brand-500 outline-none transition-all">
                        <p id="whatsapp-error" class="hidden mt-2 text-sm text-red-600 dark:text-red-400" role="alert"><?php echo $lang['data_whatsapp_invalid']; ?></p>
                    </div>
                <?php endif; ?>

                <div class="bg-surface-elevated p-6 md:p-8 rounded-2xl border border-content-secondary/10 shadow-lg">
                    <h2 class="text-xl font-bold text-content-primary mb-6 flex items-center gap-3"><i class="fas fa-info-circle text-brand-500"></i><?php echo $lang['edit_venue_info_title'] ?></h2>
                    <div class="space-y-6">
                        <div>
                            <label for="venue-name" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5 ml-1"><?php echo $lang['venue_name'] ?></label>
                            <input type="text" id="venue-name" name="name" required value="<?php echo htmlspecialchars($venue['name']); ?>"
                                class="w-full bg-input-base border border-content-secondary/20 rounded-xl px-4 py-3 text-content-primary focus:border-brand-500 outline-none transition-all">
                        </div>

                        <?php
                        // Preço (D31): valor, grátis (0) ou não informado (NULL)
                        $priceRaw = $venue['average_price_per_hour'];
                        $priceMode = $priceRaw === null ? 'none' : ((float)$priceRaw == 0 ? 'free' : 'value');
                        ?>
                        <fieldset>
                            <legend class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5 ml-1"><?php echo $lang['edit_venue_price'] ?></legend>
                            <div class="flex flex-wrap gap-2" role="radiogroup">
                                <?php foreach (['value' => $lang['data_price_value'], 'free' => $lang['data_price_free'], 'none' => $lang['data_price_none']] as $mode => $label): ?>
                                    <label class="price-mode">
                                        <input type="radio" name="price_mode" value="<?php echo $mode; ?>" class="sr-only peer" <?php echo $priceMode === $mode ? 'checked' : ''; ?>>
                                        <span class="peer-checked:bg-brand-600 peer-checked:text-white peer-checked:border-brand-500 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500"><?php echo $label; ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div id="price-value-box" class="relative mt-3 md:w-64 <?php echo $priceMode === 'value' ? '' : 'hidden'; ?>">
                                <span class="absolute left-4 top-3 text-content-secondary">R$</span>
                                <label for="price-input" class="sr-only"><?php echo $lang['data_price_value']; ?></label>
                                <input type="text" id="price-input" inputmode="decimal" name="average_price_per_hour" value="<?php echo $priceMode === 'value' ? htmlspecialchars(number_format((float)$priceRaw, 2, ',', '')) : ''; ?>" placeholder="0,00"
                                    class="w-full bg-input-base border border-content-secondary/20 rounded-xl pl-11 pr-4 py-3 text-content-primary focus:border-brand-500 outline-none transition-all">
                            </div>
                            <p class="mt-2 text-xs text-content-secondary"><?php echo $lang['data_price_hint']; ?></p>
                            <p id="price-error" class="hidden mt-2 text-sm text-red-600 dark:text-red-400" role="alert"><?php echo $lang['data_price_invalid']; ?></p>
                        </fieldset>

                        <div>
                            <div class="flex items-end justify-between mb-1.5 ml-1">
                                <label for="description" class="block text-xs font-bold text-content-secondary uppercase tracking-wider"><?php echo $lang['data_description']; ?> <span class="font-normal normal-case">(<?php echo $lang['agenda_optional']; ?>)</span></label>
                                <span class="text-xs text-content-secondary tabular-nums"><span id="description-count"><?php echo mb_strlen((string)$venue['description']); ?></span>/1000</span>
                            </div>
                            <textarea id="description" name="description" rows="4" maxlength="1000" placeholder="<?php echo htmlspecialchars($lang['data_description_placeholder']); ?>"
                                class="w-full bg-input-base border border-content-secondary/20 rounded-xl px-4 py-3 text-content-primary focus:border-brand-500 outline-none transition-all resize-y"><?php echo htmlspecialchars((string)$venue['description']); ?></textarea>
                            <p class="mt-1 text-xs text-content-secondary ml-1"><?php echo $lang['data_description_hint']; ?></p>
                        </div>
                    </div>
                </div>

                <?php if ($venue['status'] !== 'unavailable'): ?>
                    <!-- Situação da quadra (D34) -->
                    <div class="bg-surface-elevated p-6 md:p-8 rounded-2xl border border-content-secondary/10 shadow-lg">
                        <h2 class="text-xl font-bold text-content-primary mb-4 flex items-center gap-3"><span class="text-brand-500"><i class="fas fa-toggle-on"></i></span><?php echo $lang['data_status_title']; ?></h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" role="radiogroup">
                            <?php foreach (['available' => ['status-badge--available', $lang['home_status_available'], $lang['data_status_available_text']], 'maintenance' => ['status-badge--closed', $lang['data_status_maintenance'], $lang['data_status_maintenance_text']]] as $st => [$badge, $label, $hint]): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="<?php echo $st; ?>" class="sr-only peer" <?php echo $venue['status'] === $st ? 'checked' : ''; ?>>
                                    <span class="block h-full p-4 rounded-xl border border-content-secondary/20 bg-surface-base transition-colors peer-checked:border-brand-500 peer-checked:bg-brand-500/5 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500">
                                        <span class="status-badge <?php echo $badge; ?>"><?php echo $label; ?></span>
                                        <span class="block mt-2 text-sm text-content-secondary"><?php echo $hint; ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Esportes (D29) -->
                <div class="bg-surface-elevated p-6 md:p-8 rounded-2xl border border-content-secondary/10 shadow-lg">
                    <h2 class="text-xl font-bold text-content-primary mb-1 flex items-center gap-3"><span class="text-green-500"><i class="fas fa-futbol"></i></span><?php echo $lang['data_sports_title']; ?></h2>
                    <p class="text-sm text-content-secondary mb-4"><?php echo $lang['data_sports_hint']; ?></p>
                    <input type="hidden" name="sports_sent" value="1">
                    <?php if (empty($data['sports'])): ?>
                        <p class="text-sm text-content-secondary"><?php echo $lang['data_sports_empty']; ?></p>
                    <?php else: ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($data['sports'] as $sport):
                                $checked = in_array((int)$sport['id'], array_map('intval', $data['selectedSports']), true); ?>
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="sports[]" value="<?php echo (int)$sport['id']; ?>" class="sr-only peer" <?php echo $checked ? 'checked' : ''; ?>>
                                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-content-secondary/25 text-sm font-semibold text-content-primary transition-colors peer-checked:bg-brand-600 peer-checked:border-brand-500 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500">
                                        <?php if (!empty($sport['icon_path'])): ?><span><i class="fas <?php echo htmlspecialchars($sport['icon_path']); ?>"></i></span><?php endif; ?>
                                        <?php echo htmlspecialchars($sport['name']); ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php
                // Redes sociais (D60): quatro campos opcionais e a prévia dos ícones, como no app
                $socialError = substr((string)($_GET['error'] ?? ''), 0, 7) === 'social_' ? substr($_GET['error'], 7) : null;
                $socialFields = [
                    'instagram' => ['Instagram', 'fab fa-instagram', $lang['data_socials_handle_placeholder'], $lang['data_socials_error_instagram'], 'text'],
                    'tiktok' => ['TikTok', 'fab fa-tiktok', $lang['data_socials_handle_placeholder'], $lang['data_socials_error_tiktok'], 'text'],
                    'facebook_url' => ['Facebook', 'fab fa-facebook', $lang['data_socials_facebook_placeholder'], $lang['data_socials_error_facebook'], 'url'],
                    'website_url' => [$lang['data_socials_website'], 'fas fa-globe', $lang['data_socials_website_placeholder'], $lang['data_socials_error_website'], 'url'],
                ];
                ?>
                <div id="redes" class="bg-surface-elevated p-6 md:p-8 rounded-2xl border border-content-secondary/10 shadow-lg scroll-mt-24">
                    <h2 class="text-xl font-bold text-content-primary mb-1 flex items-center gap-3"><span class="text-pink-500"><i class="fas fa-share-alt"></i></span><?php echo $lang['data_socials_title']; ?></h2>
                    <p class="text-sm text-content-secondary mb-5"><?php echo $lang['data_socials_hint']; ?></p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($socialFields as $field => [$label, $icon, $placeholder, $error, $kind]):
                            $hasError = $socialError === $field; ?>
                            <div>
                                <label for="social-<?php echo $field; ?>" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5 ml-1"><?php echo htmlspecialchars($label); ?></label>
                                <div class="relative">
                                    <span class="absolute left-4 top-3 text-content-secondary" aria-hidden="true"><i class="<?php echo $icon; ?>"></i></span>
                                    <input type="text" id="social-<?php echo $field; ?>" name="<?php echo $field; ?>" data-social="<?php echo $field; ?>"
                                        value="<?php echo htmlspecialchars((string)($venue[$field] ?? '')); ?>"
                                        maxlength="<?php echo \App\Core\VenueSocials::URL_MAX; ?>" placeholder="<?php echo htmlspecialchars($placeholder); ?>"
                                        inputmode="<?php echo $kind === 'url' ? 'url' : 'text'; ?>" autocomplete="off" autocapitalize="off" spellcheck="false"
                                        aria-describedby="social-<?php echo $field; ?>-error" <?php echo $hasError ? 'aria-invalid="true"' : ''; ?>
                                        class="w-full bg-input-base border <?php echo $hasError ? 'border-red-500' : 'border-content-secondary/20'; ?> rounded-xl pl-11 pr-4 py-3 text-content-primary focus:border-brand-500 outline-none transition-all">
                                </div>
                                <p id="social-<?php echo $field; ?>-error" class="<?php echo $hasError ? '' : 'hidden'; ?> mt-2 text-sm text-red-600 dark:text-red-400" role="alert"><?php echo $error; ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-6 pt-5 border-t border-content-secondary/10">
                        <p class="text-xs font-bold text-content-secondary uppercase tracking-wider mb-3 ml-1"><?php echo $lang['data_socials_preview']; ?></p>
                        <div id="socials-preview" class="flex flex-wrap gap-3" aria-live="polite"></div>
                        <p id="socials-preview-empty" class="hidden text-sm text-content-secondary ml-1"><?php echo $lang['data_socials_preview_empty']; ?></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-surface-elevated p-6 rounded-2xl border border-content-secondary/10 shadow-lg">
                        <h2 class="text-xl font-bold text-content-primary mb-4 flex items-center gap-3"><i class="fas fa-layer-group text-purple-500"></i><?php echo $lang['edit_venue_floor_title'] ?></h2>
                        <div class="grid grid-cols-2 gap-3">
                            <?php
                            foreach (\App\Models\Venue::FLOOR_TYPES as $val => $label):
                            ?>
                                <div class="option-card border border-content-secondary/20 bg-surface-base p-3 rounded-xl text-center" data-value="<?php echo $val; ?>">
                                    <span class="text-sm font-medium text-content-secondary group-hover:text-content-primary"><?php echo $label; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="bg-surface-elevated p-6 rounded-2xl border border-content-secondary/10 shadow-lg flex flex-col justify-center">
                        <h2 class="text-xl font-bold text-content-primary mb-2 flex items-center gap-3"><i class="fas fa-users text-green-500"></i> <?php echo $lang['edit_venue_capacity_title'] ?></h2>
                        <p class="text-sm text-content-secondary mb-6"><?php echo $lang['edit_venue_capacity_text'] ?></p>
                        <div class="flex items-center justify-between bg-surface-base p-4 rounded-xl border border-content-secondary/10">
                            <span class="font-semibold text-content-primary"><?php echo $lang['edit_venue_capacity'] ?></span>
                            <div class="counter-input flex items-center gap-4" data-input-name="court_capacity" data-min-value="2" data-target-input="court_capacity_input">
                                <button type="button" class="minus w-10 h-10 rounded-full border border-content-secondary/30 text-content-secondary hover:text-brand-500 hover:border-brand-500 flex items-center justify-center text-xl transition-colors">-</button>
                                <span class="counter-value text-2xl font-bold w-8 text-center text-content-primary"><?php echo $venue['court_capacity']; ?></span>
                                <button type="button" class="plus w-10 h-10 rounded-full border border-content-secondary/30 text-content-secondary hover:text-brand-500 hover:border-brand-500 flex items-center justify-center text-xl transition-colors">+</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-surface-elevated p-6 md:p-8 rounded-2xl border border-content-secondary/10 shadow-lg">
                    <h2 class="text-xl font-bold text-content-primary mb-6 flex items-center gap-3"><i class="fas fa-star text-yellow-500"></i> <?php echo $lang['edit_venues_additional_title'] ?></h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div class="checkbox-card border border-content-secondary/20 bg-surface-base rounded-xl p-4 flex flex-col items-center gap-2">
                            <input type="checkbox" name="has_lighting" value="1" class="hidden" <?php echo $venue['has_lighting'] ? 'checked' : ''; ?>>
                            <i class="fas fa-lightbulb text-2xl text-content-secondary transition-colors"></i>
                            <span class="text-sm font-medium text-content-primary"><?php echo $lang['edit_venue_answer_1'] ?></span>
                        </div>
                        <div class="checkbox-card border border-content-secondary/20 bg-surface-base rounded-xl p-4 flex flex-col items-center gap-2">
                            <input type="checkbox" name="is_covered" value="1" class="hidden" <?php echo $venue['is_covered'] ? 'checked' : ''; ?>>
                            <i class="fas fa-umbrella text-2xl text-content-secondary transition-colors"></i>
                            <span class="text-sm font-medium text-content-primary"><?php echo $lang['edit_venue_answer_2'] ?></span>
                        </div>
                        <div class="checkbox-card border border-content-secondary/20 bg-surface-base rounded-xl p-4 flex flex-col items-center gap-2" id="leisure-card">
                            <input type="checkbox" name="has_leisure_area" value="1" class="hidden" <?php echo $venue['has_leisure_area'] ? 'checked' : ''; ?>>
                            <i class="fas fa-glass-cheers text-2xl text-content-secondary transition-colors"></i>
                            <span class="text-sm font-medium text-content-primary"><?php echo $lang['edit_venue_answer_3'] ?></span>
                        </div>
                    </div>

                    <div id="leisure-capacity-container" class="hidden mt-6 pt-6 border-t border-content-secondary/10">
                        <div class="flex items-center justify-between bg-surface-base p-4 rounded-xl border border-content-secondary/10 max-w-md mx-auto">
                            <span class="text-sm font-semibold text-content-primary"><?php echo $lang['edit_venue_capacity_leisure'] ?></span>
                            <div class="counter-input flex items-center gap-4" data-input-name="leisure_area_capacity" data-min-value="0" data-target-input="leisure_area_capacity_input">
                                <button type="button" class="minus w-8 h-8 rounded-full border border-content-secondary/30 text-content-secondary hover:text-brand-500 hover:border-brand-500 transition-colors">-</button>
                                <span class="counter-value text-xl font-bold text-content-primary"><?php echo $venue['leisure_area_capacity']; ?></span>
                                <button type="button" class="plus w-8 h-8 rounded-full border border-content-secondary/30 text-content-secondary hover:text-brand-500 hover:border-brand-500 transition-colors">+</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-surface-elevated p-6 md:p-8 rounded-2xl border border-content-secondary/10 shadow-lg">
                    <h2 class="text-xl font-bold text-content-primary mb-6 flex items-center gap-3"><i class="fas fa-map-marker-alt text-red-500"></i><?php echo $lang['edit_venue_location'] ?></h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5 ml-1"><?php echo $lang['edit_venue_location_cep'] ?></label>
                            <div class="relative">
                                <input type="text" id="cep" name="cep" required value="<?php echo htmlspecialchars($venue['cep']); ?>" 
                                    class="w-full bg-input-base border border-content-secondary/20 rounded-xl pl-10 pr-4 py-3 text-content-primary focus:border-brand-500 outline-none transition-all">
                                <i class="fas fa-search absolute left-3.5 top-3.5 text-content-secondary"></i>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2"><input type="text" id="street" name="street" value="<?php echo htmlspecialchars($venue['street']); ?>" class="w-full bg-input-base border border-content-secondary/20 rounded-xl px-4 py-3 text-content-primary focus:border-brand-500 outline-none" placeholder="Rua"></div>
                            <div><input type="text" id="number" name="number" value="<?php echo htmlspecialchars($venue['number']); ?>" class="w-full bg-input-base border border-content-secondary/20 rounded-xl px-4 py-3 text-content-primary focus:border-brand-500 outline-none" placeholder="Nº"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><input type="text" id="neighborhood" name="neighborhood" value="<?php echo htmlspecialchars($venue['neighborhood']); ?>" class="w-full bg-input-base border border-content-secondary/20 rounded-xl px-4 py-3 text-content-primary focus:border-brand-500 outline-none" placeholder="Bairro"></div>
                            <div><input type="text" id="city" name="city" value="<?php echo htmlspecialchars($venue['city']); ?>" class="w-full bg-input-base border border-content-secondary/20 rounded-xl px-4 py-3 text-content-primary focus:border-brand-500 outline-none" placeholder="Cidade"></div>
                            <div><input type="text" id="state" name="state" value="<?php echo htmlspecialchars($venue['state']); ?>" class="w-full bg-input-base border border-content-secondary/20 rounded-xl px-4 py-3 text-content-primary focus:border-brand-500 outline-none" placeholder="UF"></div>
                        </div>
                        <div><input type="text" id="complement" name="complement" value="<?php echo htmlspecialchars($venue['complement']); ?>" class="w-full bg-input-base border border-content-secondary/20 rounded-xl px-4 py-3 text-content-primary focus:border-brand-500 outline-none" placeholder="Complemento"></div>
                    </div>
                </div>

                <!-- Fotos ficam na aba Fotos (D46): aqui só a prévia e o atalho -->
                <div class="bg-surface-elevated p-6 md:p-8 rounded-2xl border border-content-secondary/10 shadow-lg">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-content-primary flex items-center gap-3"><i class="fas fa-camera text-blue-500"></i><?php echo $lang['edit_venue_photo'] ?></h2>
                            <p class="text-sm text-content-secondary mt-1"><?php echo strtr($lang['photos_count'], ['{n}' => count($venue['images'] ?? []), '{max}' => \App\Models\VenueImage::MAX_PER_VENUE]); ?></p>
                        </div>
                        <a href="<?php echo BASE_URL . $prefix . '/quadras/' . (int)$venue['id'] . '/fotos'; ?>" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border border-content-secondary/20 text-sm font-semibold text-content-primary hover:border-brand-500 hover:text-brand-600 dark:hover:text-brand-400 transition-colors">
                            <span><i class="fas fa-images"></i></span><?php echo $lang['photos_manage']; ?>
                        </a>
                    </div>
                    <?php if (!empty($venue['images'])): ?>
                        <div class="mt-5 grid grid-cols-4 gap-3">
                            <?php foreach (array_slice($venue['images'], 0, 4) as $i => $image): ?>
                                <div class="relative aspect-square rounded-xl overflow-hidden border border-content-secondary/10 bg-surface-base">
                                    <img src="<?php echo BASE_URL . '/uploads/venues/' . (int)$venue['id'] . '/' . htmlspecialchars(basename($image['file_path'])); ?>" alt="" class="w-full h-full object-cover" loading="lazy">
                                    <?php if ($i === 0): ?><span class="absolute left-1.5 top-1.5 text-[10px] font-bold uppercase tracking-wide bg-brand-600 text-white rounded px-1.5 py-0.5"><?php echo $lang['photos_cover']; ?></span><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="hidden md:flex justify-end mt-8">
                    <a href="<?php echo BASE_URL . $backUrl; ?>" class="text-content-secondary hover:text-content-primary text-sm font-semibold px-4 py-2 transition-colors">
                        <?php echo $lang['global_cancel'] ?>
                    </a>
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg transition-transform hover:-translate-y-0.5"><?php echo $lang['profile_btn_save'] ?></button>
                </div>
            </form>

            <div class="mt-12 mb-24 md:mb-10 bg-red-500/5 border border-red-500/20 rounded-2xl p-6 flex flex-col md:flex-row justify-between items-center gap-4 animate-up delay-200">
                <div>
                    <h3 class="font-bold text-red-600 dark:text-red-400 text-lg"><?php echo $lang['edit_venue_desativar_Text_1'] ?></h3>
                    <p class="text-sm text-content-secondary"><?php echo $lang['edit_venue_desativar_Text_2'] ?></p>
                </div>
                <button type="button" id="open-delete-modal-btn" data-venue-name="<?php echo htmlspecialchars($venue['name']); ?>" 
                    class="w-full md:w-auto bg-red-500/10 hover:bg-red-500 text-red-600 dark:text-red-400 hover:text-white border border-red-500/50 font-bold py-2 px-6 rounded-xl transition-colors">
                    <?php echo $lang['edit_venue_desativar_btn'] ?>
                </button>
            </div>

        <?php endif; ?>
    </main>

    <div class="fixed bottom-0 left-0 w-full p-4 bg-surface-elevated/90 backdrop-blur-md border-t border-content-secondary/10 flex justify-center z-40 md:hidden">
        <button type="submit" form="venue-form" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-6 rounded-xl shadow-xl transition-transform active:scale-95"><?php echo $lang['profile_btn_save'] ?></button>
    </div>

    <div id="delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm transition-opacity">
        <div class="bg-surface-elevated border border-content-secondary/20 rounded-2xl shadow-2xl w-full max-w-md transform scale-100">
            <div class="p-6">
                <h3 class="text-xl font-bold text-content-primary flex items-center"><i class="fas fa-trash-alt mr-3 text-red-500"></i> <?php echo $lang['edit_venue_desativar_confirm'] ?></h3>
                <p class="text-content-secondary mt-2 text-sm"><?php echo $lang['edit_venue_desativar_alert'] ?>.</p>
                <div class="mt-6">
                    <p class="text-sm text-content-secondary mb-2"><?php echo $lang['edit_venue_desativar_text_1'] ?> <strong class="text-content-primary" id="confirmation-prompt-name"></strong> <?php echo $lang['edit_venue_desativar_text_2'] ?></p>
                    <input type="text" id="confirmation-input" autocomplete="off" class="w-full px-4 py-3 bg-input-base border border-content-secondary/30 rounded-xl text-content-primary outline-none focus:border-red-500 transition-all">
                </div>
            </div>
            <div class="p-4 bg-surface-base rounded-b-2xl border-t border-content-secondary/10 flex justify-end gap-3">
                <button type="button" id="cancel-delete-btn" class="px-4 py-2 text-content-secondary hover:text-content-primary transition-colors"><?php echo $lang['global_cancel'] ?></button>
                <form id="delete-venue-form" action="<?php echo BASE_URL . $prefix; ?>/quadras/excluir/<?php echo $venue['id']; ?>" method="POST">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <button type="submit" id="confirm-delete-btn" class="px-6 py-2 bg-red-600 text-white font-bold rounded-lg opacity-50 cursor-not-allowed transition-all" disabled><?php echo $lang['edit_venue_desativar_btn'] ?></button>
                </form>
            </div>
        </div>
    </div>

    <?php // Menu do usuário e botão de tema do cabeçalho (initNavbar, initTheme) ?>
    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const venueData = <?php echo json_encode($venue ?? null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            if (!venueData) return;

            // 1. Lógica de Piso
            const floorCards = document.querySelectorAll('.option-card');
            const floorInput = document.getElementById('floor_type_input');

            // Marca o atual
            floorCards.forEach(c => {
                if (c.dataset.value === floorInput.value) c.classList.add('selected');
            });

            floorCards.forEach(card => {
                card.addEventListener('click', (e) => {
                    e.preventDefault();
                    floorCards.forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');
                    floorInput.value = card.dataset.value;
                });
            });

            // 2. Lógica de Checkboxes
            const leisureContainer = document.getElementById('leisure-capacity-container');
            document.querySelectorAll('.checkbox-card').forEach(card => {
                const checkbox = card.querySelector('input');

                const updateVisual = () => {
                    card.classList.toggle('selected', checkbox.checked);
                    const icon = card.querySelector('i');
                    icon.classList.toggle('text-brand-500', checkbox.checked); // Ajustado para brand-500
                    icon.classList.toggle('text-content-secondary', !checkbox.checked);

                    if (checkbox.name === 'has_leisure_area') {
                        leisureContainer.classList.toggle('hidden', !checkbox.checked);
                    }
                };

                updateVisual();

                card.addEventListener('click', (e) => {
                    e.preventDefault();
                    checkbox.checked = !checkbox.checked;
                    updateVisual();
                });
            });

            // 3. Lógica de Contadores
            document.querySelectorAll('.counter-input').forEach(counter => {
                const span = counter.querySelector('.counter-value');
                const hiddenInput = document.getElementById(counter.dataset.targetInput);
                const min = parseInt(counter.dataset.minValue);
                let val = parseInt(span.textContent);

                counter.querySelector('.minus').addEventListener('click', (e) => {
                    e.preventDefault();
                    if (val > min) {
                        val--;
                        span.textContent = val;
                        hiddenInput.value = val;
                    }
                });
                counter.querySelector('.plus').addEventListener('click', (e) => {
                    e.preventDefault();
                    val++;
                    span.textContent = val;
                    hiddenInput.value = val;
                });
            });

            // ViaCEP com Complemento
            document.getElementById('cep').addEventListener('blur', async function() {
                const cep = this.value.replace(/\D/g, '');
                if (cep.length === 8) {
                    try {
                        const data = await (await fetch(`https://viacep.com.br/ws/${cep}/json/`)).json();
                        if (!data.erro) {
                            document.getElementById('street').value = data.logradouro;
                            document.getElementById('neighborhood').value = data.bairro;
                            document.getElementById('city').value = data.localidade;
                            document.getElementById('state').value = data.uf;
                            document.getElementById('complement').value = data.complemento || '';
                        }
                    } catch (e) {}
                }
            });

            // Modal Logic
            const deleteModal = document.getElementById('delete-modal');
            const openDeleteBtn = document.getElementById('open-delete-modal-btn');
            const confirmInput = document.getElementById('confirmation-input');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');

            if (openDeleteBtn) {
                openDeleteBtn.addEventListener('click', () => {
                    document.getElementById('confirmation-prompt-name').textContent = openDeleteBtn.dataset.venueName;
                    confirmInput.value = '';
                    confirmDeleteBtn.disabled = true;
                    confirmDeleteBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    deleteModal.classList.remove('hidden');
                });
                document.getElementById('cancel-delete-btn').addEventListener('click', () => deleteModal.classList.add('hidden'));
                confirmInput.addEventListener('input', () => {
                    const match = confirmInput.value === openDeleteBtn.dataset.venueName;
                    confirmDeleteBtn.disabled = !match;
                    confirmDeleteBtn.classList.toggle('opacity-50', !match);
                    confirmDeleteBtn.classList.toggle('cursor-not-allowed', !match);
                });
            }

            // --- Preço (D31), descrição (D30) e WhatsApp (D38) ---
            const venueForm = document.getElementById('venue-form');
            const priceBox = document.getElementById('price-value-box');
            const priceInput = document.getElementById('price-input');
            const priceError = document.getElementById('price-error');
            const priceMode = () => (venueForm.querySelector('input[name="price_mode"]:checked') || {}).value;
            venueForm.querySelectorAll('input[name="price_mode"]').forEach(r => r.addEventListener('change', () => {
                priceBox.classList.toggle('hidden', priceMode() !== 'value');
                priceError.classList.add('hidden');
                if (priceMode() === 'value') priceInput.focus();
            }));

            const description = document.getElementById('description');
            const descriptionCount = document.getElementById('description-count');
            description.addEventListener('input', () => { descriptionCount.textContent = description.value.length; });

            const whatsapp = document.getElementById('whatsapp');
            const whatsappError = document.getElementById('whatsapp-error');
            // Mesma regra do servidor (User::normalizeWhatsapp): DDD + número, com ou sem 55
            const validWhatsapp = v => {
                let d = v.replace(/\D/g, '');
                if (d.length === 10 || d.length === 11) d = '55' + d;
                return /^55[1-9][1-9](9\d{8}|[2-8]\d{7})$/.test(d);
            };

            // --- Redes sociais (D60): mesmas regras do servidor (App\Core\VenueSocials) ---
            const socialMeta = <?php echo json_encode(array_map(fn($f) => ['label' => $f[0], 'icon' => $f[1]], $socialFields), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
            const socialOpenLabel = <?php echo json_encode($lang['socials_open_label'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
            const INSTAGRAM_RESERVED = ['p', 'reel', 'reels', 'stories', 'explore', 'tv', 'accounts', 'direct'];
            const looksLikeUrl = v => v.includes('/') || /^https?:/i.test(v);
            const validHandle = h => /^[A-Za-z0-9._]{1,30}$/.test(h) ? h : false;
            const firstSegment = (v, hosts) => {
                if (!/^https?:\/\//i.test(v)) v = 'https://' + v.replace(/^\/+/, '');
                let u;
                try { u = new URL(v); } catch (err) { return null; }
                if (!hosts.includes(u.hostname.toLowerCase().replace(/^(www\.|m\.)/, ''))) return null;
                return u.pathname.split('/').filter(Boolean)[0] || null;
            };
            const normalizeUrl = raw => {
                let v = raw.trim();
                if (v === '') return null;
                if (/\s/.test(v)) return false;
                if (/^https?:\/\//i.test(v)) v = 'https://' + v.replace(/^https?:\/\//i, '');
                else if (v.includes('://') || /^[a-z][a-z0-9+.-]*:/i.test(v)) return false;
                else v = 'https://' + v.replace(/^\/+/, '');
                if (v.length > 255) return false;
                let u;
                try { u = new URL(v); } catch (err) { return false; }
                return /^[a-z0-9-]+(\.[a-z0-9-]+)+$/i.test(u.hostname) ? v : false;
            };
            const normalizeSocial = {
                instagram: raw => {
                    let v = raw.trim();
                    if (v === '') return null;
                    if (looksLikeUrl(v)) {
                        const s = firstSegment(v, ['instagram.com', 'instagr.am']);
                        if (!s || INSTAGRAM_RESERVED.includes(s.toLowerCase())) return false;
                        v = s;
                    }
                    return validHandle(v.replace(/^@+/, ''));
                },
                tiktok: raw => {
                    let v = raw.trim();
                    if (v === '') return null;
                    if (looksLikeUrl(v)) {
                        const s = firstSegment(v, ['tiktok.com']);
                        if (!s || s[0] !== '@') return false;
                        v = s;
                    }
                    return validHandle(v.replace(/^@+/, ''));
                },
                facebook_url: raw => {
                    const v = normalizeUrl(raw);
                    if (typeof v !== 'string') return v;
                    const u = new URL(v);
                    const host = u.hostname.toLowerCase();
                    const isFacebook = ['facebook.com', 'fb.com'].includes(host) || host.endsWith('.facebook.com') || host.endsWith('.fb.com');
                    return isFacebook && (u.pathname.replace(/\//g, '') !== '' || u.search !== '') ? v : false;
                },
                website_url: normalizeUrl,
            };
            const socialUrl = (field, value) => field === 'instagram' ? 'https://instagram.com/' + encodeURIComponent(value)
                : field === 'tiktok' ? 'https://www.tiktok.com/@' + encodeURIComponent(value) : value;

            const socialInputs = venueForm.querySelectorAll('[data-social]');
            const socialPreview = document.getElementById('socials-preview');
            const socialPreviewEmpty = document.getElementById('socials-preview-empty');
            const setSocialError = (input, bad) => {
                document.getElementById(input.id + '-error').classList.toggle('hidden', !bad);
                input.classList.toggle('border-red-500', bad);
                input.classList.toggle('border-content-secondary/20', !bad);
                if (bad) input.setAttribute('aria-invalid', 'true'); else input.removeAttribute('aria-invalid');
            };
            const renderSocialPreview = () => {
                socialPreview.replaceChildren();
                socialInputs.forEach(input => {
                    const field = input.dataset.social;
                    const value = normalizeSocial[field](input.value);
                    if (typeof value !== 'string') return;
                    const meta = socialMeta[field];
                    const a = document.createElement('a');
                    a.href = socialUrl(field, value);
                    a.target = '_blank';
                    a.rel = 'noopener noreferrer';
                    a.dataset.network = field;
                    a.className = 'inline-flex items-center gap-2 pl-1.5 pr-4 py-1.5 rounded-full border border-content-secondary/20 text-sm font-semibold text-content-primary hover:border-brand-500 transition-colors';
                    a.setAttribute('aria-label', socialOpenLabel.replace('{network}', meta.label));
                    const icon = document.createElement('span');
                    icon.className = 'w-8 h-8 rounded-full bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center';
                    icon.innerHTML = '<i class="' + meta.icon + '" aria-hidden="true"></i>';
                    a.append(icon, document.createTextNode(field === 'instagram' || field === 'tiktok' ? '@' + value : meta.label));
                    socialPreview.append(a);
                });
                socialPreviewEmpty.classList.toggle('hidden', socialPreview.children.length > 0);
            };
            socialInputs.forEach(input => {
                input.addEventListener('input', () => { setSocialError(input, false); renderSocialPreview(); });
                input.addEventListener('blur', () => setSocialError(input, normalizeSocial[input.dataset.social](input.value) === false));
            });
            renderSocialPreview();

            venueForm.addEventListener('submit', e => {
                let firstError = null;
                if (priceMode() === 'value') {
                    const n = parseFloat(priceInput.value.replace(/\./g, '').replace(',', '.'));
                    const bad = !(n > 0);
                    priceError.classList.toggle('hidden', !bad);
                    if (bad) firstError = firstError || priceInput;
                }
                if (whatsapp) {
                    const bad = !validWhatsapp(whatsapp.value);
                    whatsappError.classList.toggle('hidden', !bad);
                    if (bad) firstError = firstError || whatsapp;
                }
                // Redes sociais ficam abaixo do preço na página: entram depois na ordem do foco
                socialInputs.forEach(input => {
                    const bad = normalizeSocial[input.dataset.social](input.value) === false;
                    setSocialError(input, bad);
                    if (bad) firstError = firstError || input;
                });
                if (firstError) {
                    e.preventDefault();
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus({ preventScroll: true });
                }
            });
        });
    </script>
</body>
</html>