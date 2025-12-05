<?php
$lang = require BASE_PATH . '/includes/i18n.php';
$venue = $data['venue'] ?? null;
$prefix = $data['routePrefix'] ?? '/dashboard';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kolae</title>

    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print" onload="this.media='all'" />
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    </noscript>
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }

        /* Cards Interativos */
        .option-card,
        .checkbox-card {
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .option-card:hover,
        .checkbox-card:hover {
            transform: translateY(-2px);
            border-color: #4b5563;
        }

        .option-card.selected,
        .checkbox-card.selected {
            border-color: #22d3ee;
            background-color: rgba(34, 211, 238, 0.05);
            box-shadow: 0 4px 10px rgba(34, 211, 238, 0.1);
        }

        .checkbox-card.selected i {
            color: #22d3ee;
        }

        /* Upload */
        #drop-area.highlight {
            border-color: #22d3ee;
            background-color: rgba(34, 211, 238, 0.05);
            transform: scale(1.01);
        }

        .animate-up {
            animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .delay-100 {
            animation-delay: 100ms;
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

<body class="bg-[#0D1117] text-gray-200">

    <!-- Cabeçalho -->
    <header class="bg-[#161B22]/80 backdrop-blur-md border-b border-gray-800 sticky top-0 z-40 py-4 transition-all">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <a href="<?php echo BASE_URL; ?>/" class="flex items-center gap-2 group">
                <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo" class="h-8 w-auto group-hover:opacity-80 transition-opacity">
            </a>

            <nav class="hidden md:flex items-center space-x-8">
                <a href="<?php echo BASE_URL; ?>/dashboard" class="font-medium text-gray-400 hover:text-white transition-colors"><?php echo $lang['global_home_panel']; ?></a>
            </nav>

            <div class="relative">
                <button id="user-menu-button" class="flex items-center gap-3 p-2 px-3 border border-gray-700 rounded-full cursor-pointer transition-all hover:bg-gray-700/50 hover:border-gray-600">
                    <?php if (!empty($_SESSION['user_avatar'])): ?>
                        <img src="<?php echo BASE_URL . '/uploads/avatars/' . $_SESSION['user_id'] . '/' . $_SESSION['user_avatar']; ?>"
                            class="w-8 h-8 rounded-full object-cover border border-gray-600"
                            alt="Avatar"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm" style="display:none;">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                        </div>
                    <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                        </div>
                    <?php endif; ?>

                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                </button>

                <div id="profile-dropdown" class="absolute top-full right-0 mt-3 w-64 bg-[#161B22] border border-gray-700 rounded-xl shadow-2xl opacity-0 invisible transform -translate-y-2 transition-all duration-200 z-50">
                    <div class="p-4 border-b border-gray-800">
                        <p class="font-semibold text-white truncate"><?php echo htmlspecialchars($userName); ?></p>
                        <a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="text-xs text-cyan-400 hover:underline"><?php echo $lang['dashboard_view_profile']; ?></a>
                    </div>
                    <ul class="py-2 text-sm">
                        <li><a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-800 transition-colors"><i class="fas fa-cog w-4 text-center text-gray-400"></i> <?php echo $lang['global_menu_config']; ?></a></li>
                        <li><a href="#" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-800 transition-colors"><i class="fas fa-question-circle w-4 text-center text-gray-400"></i> <?php echo $lang['global_menu_help']; ?></a></li>
                        <li class="border-t border-gray-800 my-2"></li>
                        <li><a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-3 px-5 py-3 text-red-400 hover:bg-red-500/10 transition-colors"><i class="fas fa-sign-out-alt w-4 text-center"></i><?php echo $lang['global_menu_exit']; ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8 max-w-4xl">

        <?php if (!$venue): ?>
            <div class="text-center py-12 bg-[#161B22] rounded-2xl border border-red-900/50">
                <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                <h2 class="text-2xl font-bold text-white"><?php echo $lang['global_error'] ?></h2>
                <p class="text-gray-400 mt-2"><?php echo $lang['edit_venue_not_found'] ?></p>
                <a href="<?php echo BASE_URL . $prefix; ?>" class="mt-4 inline-block text-cyan-400 hover:underline"><?php echo $lang['global_back'] ?></a>
            </div>
        <?php else: ?>

            <form id="venue-form" action="<?php echo BASE_URL . $prefix; ?>/quadras/atualizar/<?php echo $venue['id']; ?>" method="POST" enctype="multipart/form-data" class="space-y-8 pb-24 md:pb-8 animate-up">

                <input type="hidden" name="address_id" value="<?php echo $venue['address_id']; ?>">
                <input type="hidden" id="floor_type_input" name="floor_type" value="<?php echo htmlspecialchars($venue['floor_type']); ?>">
                <input type="hidden" id="court_capacity_input" name="court_capacity" value="<?php echo htmlspecialchars($venue['court_capacity']); ?>">
                <input type="hidden" id="leisure_area_capacity_input" name="leisure_area_capacity" value="<?php echo htmlspecialchars($venue['leisure_area_capacity']); ?>">

                <div class="bg-[#161B22] p-6 md:p-8 rounded-2xl border border-gray-800 shadow-lg">
                    <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-3"><i class="fas fa-info-circle text-cyan-400"></i><?php echo $lang['edit_venue_info_title'] ?></h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1"><?php echo $lang['venue_name'] ?></label>
                            <input type="text" name="name" required value="<?php echo htmlspecialchars($venue['name']); ?>" class="w-full bg-gray-900/50 border border-gray-700 rounded-xl px-4 py-3 text-white focus:border-cyan-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1"><?php echo $lang['edit_venue_price'] ?></label>
                            <div class="relative">
                                <span class="absolute left-4 top-3 text-gray-500">R$</span>
                                <input type="number" step="0.01" name="average_price_per_hour" value="<?php echo htmlspecialchars($venue['average_price_per_hour']); ?>" class="w-full bg-gray-900/50 border border-gray-700 rounded-xl pl-10 pr-4 py-3 text-white focus:border-cyan-500 outline-none transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-[#161B22] p-6 rounded-2xl border border-gray-800 shadow-lg">
                        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-3"><i class="fas fa-layer-group text-purple-400"></i><?php echo $lang['edit_venue_floor_title'] ?></h2>
                        <div class="grid grid-cols-2 gap-3">
                            <?php
                            $floors = ['grama sintética' => $lang['venue_floor_answer_1'], 'cimento' => $lang['venue_floor_answer_2'], 'areia' => $lang['venue_floor_answer_3'], 'saibro' => $lang['venue_floor_answer_4'], 'grama natural' => $lang['venue_floor_answer_5'], 'taco' => $lang['venue_floor_answer_6']];
                            foreach ($floors as $val => $label):
                            ?>
                                <div class="option-card border border-gray-700 bg-gray-900/30 p-3 rounded-xl text-center" data-value="<?php echo $val; ?>">
                                    <span class="text-sm font-medium text-gray-300"><?php echo $label; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="bg-[#161B22] p-6 rounded-2xl border border-gray-800 shadow-lg flex flex-col justify-center">
                        <h2 class="text-xl font-bold text-white mb-2 flex items-center gap-3"><i class="fas fa-users text-green-400"></i> <?php echo $lang['edit_venue_capacity_title'] ?></h2>
                        <p class="text-sm text-gray-400 mb-6"><?php echo $lang['edit_venue_capacity_text'] ?></p>
                        <div class="flex items-center justify-between bg-gray-900/50 p-4 rounded-xl border border-gray-700">
                            <span class="font-semibold"><?php echo $lang['edit_venue_capacity'] ?></span>
                            <div class="counter-input flex items-center gap-4" data-input-name="court_capacity" data-min-value="2" data-target-input="court_capacity_input">
                                <button type="button" class="minus w-10 h-10 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700 flex items-center justify-center text-xl">-</button>
                                <span class="counter-value text-2xl font-bold w-8 text-center"><?php echo $venue['court_capacity']; ?></span>
                                <button type="button" class="plus w-10 h-10 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700 flex items-center justify-center text-xl">+</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-[#161B22] p-6 md:p-8 rounded-2xl border border-gray-800 shadow-lg">
                    <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-3"><i class="fas fa-star text-yellow-400"></i> <?php echo $lang['edit_venues_additional_title'] ?></h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div class="checkbox-card border border-gray-700 bg-gray-900/30 rounded-xl p-4 flex flex-col items-center gap-2">
                            <input type="checkbox" name="has_lighting" value="1" class="hidden" <?php echo $venue['has_lighting'] ? 'checked' : ''; ?>>
                            <i class="fas fa-lightbulb text-2xl text-gray-500 transition-colors"></i>
                            <span class="text-sm font-medium"><?php echo $lang['edit_venue_answer_1'] ?></span>
                        </div>
                        <div class="checkbox-card border border-gray-700 bg-gray-900/30 rounded-xl p-4 flex flex-col items-center gap-2">
                            <input type="checkbox" name="is_covered" value="1" class="hidden" <?php echo $venue['is_covered'] ? 'checked' : ''; ?>>
                            <i class="fas fa-umbrella text-2xl text-gray-500 transition-colors"></i>
                            <span class="text-sm font-medium"><?php echo $lang['edit_venue_answer_2'] ?></span>
                        </div>
                        <div class="checkbox-card border border-gray-700 bg-gray-900/30 rounded-xl p-4 flex flex-col items-center gap-2" id="leisure-card">
                            <input type="checkbox" name="has_leisure_area" value="1" class="hidden" <?php echo $venue['has_leisure_area'] ? 'checked' : ''; ?>>
                            <i class="fas fa-glass-cheers text-2xl text-gray-500 transition-colors"></i>
                            <span class="text-sm font-medium"><?php echo $lang['edit_venue_answer_3'] ?></span>
                        </div>
                    </div>

                    <div id="leisure-capacity-container" class="hidden mt-6 pt-6 border-t border-gray-700">
                        <div class="flex items-center justify-between bg-gray-900/50 p-4 rounded-xl border border-gray-700 max-w-md mx-auto">
                            <span class="text-sm font-semibold"><?php echo $lang['edit_venue_capacity_leisure'] ?></span>
                            <div class="counter-input flex items-center gap-4" data-input-name="leisure_area_capacity" data-min-value="0" data-target-input="leisure_area_capacity_input">
                                <button type="button" class="minus w-8 h-8 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700">-</button>
                                <span class="counter-value text-xl font-bold"><?php echo $venue['leisure_area_capacity']; ?></span>
                                <button type="button" class="plus w-8 h-8 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700">+</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-[#161B22] p-6 md:p-8 rounded-2xl border border-gray-800 shadow-lg">
                    <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-3"><i class="fas fa-map-marker-alt text-red-400"></i><?php echo $lang['edit_venue_location'] ?></h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1"><?php echo $lang['edit_venue_location_cep'] ?></label>
                            <div class="relative">
                                <input type="text" id="cep" name="cep" required value="<?php echo htmlspecialchars($venue['cep']); ?>" class="w-full bg-gray-900/50 border border-gray-700 rounded-xl pl-10 pr-4 py-3 text-white focus:border-cyan-500 outline-none transition-all">
                                <i class="fas fa-search absolute left-3.5 top-3.5 text-gray-500"></i>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2"><input type="text" id="street" name="street" value="<?php echo htmlspecialchars($venue['street']); ?>" class="w-full bg-gray-900/50 border border-gray-700 rounded-xl px-4 py-3 text-white focus:border-cyan-500 outline-none" placeholder="Rua"></div>
                            <div><input type="text" id="number" name="number" value="<?php echo htmlspecialchars($venue['number']); ?>" class="w-full bg-gray-900/50 border border-gray-700 rounded-xl px-4 py-3 text-white focus:border-cyan-500 outline-none" placeholder="Nº"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><input type="text" id="neighborhood" name="neighborhood" value="<?php echo htmlspecialchars($venue['neighborhood']); ?>" class="w-full bg-gray-900/50 border border-gray-700 rounded-xl px-4 py-3 text-white focus:border-cyan-500 outline-none" placeholder="Bairro"></div>
                            <div><input type="text" id="city" name="city" value="<?php echo htmlspecialchars($venue['city']); ?>" class="w-full bg-gray-900/50 border border-gray-700 rounded-xl px-4 py-3 text-white focus:border-cyan-500 outline-none" placeholder="Cidade"></div>
                            <div><input type="text" id="state" name="state" value="<?php echo htmlspecialchars($venue['state']); ?>" class="w-full bg-gray-900/50 border border-gray-700 rounded-xl px-4 py-3 text-white focus:border-cyan-500 outline-none" placeholder="UF"></div>
                        </div>
                        <div><input type="text" id="complement" name="complement" value="<?php echo htmlspecialchars($venue['complement']); ?>" class="w-full bg-gray-900/50 border border-gray-700 rounded-xl px-4 py-3 text-white focus:border-cyan-500 outline-none" placeholder="Complemento"></div>
                    </div>
                </div>

                <div class="bg-[#161B22] p-6 md:p-8 rounded-2xl border border-gray-800 shadow-lg">
                    <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-3"><i class="fas fa-camera text-blue-400"></i><?php echo $lang['edit_venue_photo'] ?></h2>

                    <div id="drop-area" class="border-2 border-dashed border-gray-700 rounded-2xl p-8 text-center cursor-pointer hover:border-cyan-500 hover:bg-cyan-500/5 transition-all group">
                        <label for="images" class="cursor-pointer w-full h-full block">
                            <div class="w-16 h-16 bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 group-hover:text-cyan-400"></i>
                            </div>
                            <p class="font-bold text-white"><?php echo $lang['edit_venue_photo_text_1'] ?></p>
                            <p class="text-sm text-gray-500"><?php echo $lang['edit_venue_photo_text_2'] ?></p>
                            <input type="file" id="images" name="images[]" multiple accept="image/jpeg, image/png" class="hidden">
                        </label>
                    </div>

                    <div id="preview-container" class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <?php if (!empty($venue['images'])): foreach ($venue['images'] as $image): ?>
                                <div class="relative group aspect-square rounded-xl overflow-hidden">
                                    <img src="<?php echo BASE_URL . '/uploads/venues/' . $venue['id'] . '/' . htmlspecialchars($image['file_path']); ?>" class="w-full h-full object-cover">
                                    <button type="button" class="delete-existing-image absolute top-1 right-1 bg-red-500 text-white w-8 h-8 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg" data-image-id="<?php echo $image['id']; ?>">&times;</button>
                                </div>
                        <?php endforeach;
                        endif; ?>
                    </div>
                </div>

                <div class="hidden md:flex justify-end mt-8">
                    <a href="<?php echo BASE_URL; ?>/dashboard" class="text-gray-400 hover:text-white text-sm font-semibold px-4 py-2 transition-colors">
                        <?php echo $lang['global_cancel'] ?>
                    </a>
                    <button type="submit" class="bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-3 px-10 rounded-xl shadow-lg transition-transform hover:-translate-y-0.5"><?php echo $lang['profile_btn_save'] ?></button>
                </div>
            </form>

            <div class="mt-12 mb-24 md:mb-10 bg-red-500/5 border border-red-500/20 rounded-2xl p-6 flex flex-col md:flex-row justify-between items-center gap-4 animate-up delay-200">
                <div>
                    <h3 class="font-bold text-white text-lg"><?php echo $lang['edit_venue_desativar_Text_1'] ?></h3>
                    <p class="text-sm text-gray-400"><?php echo $lang['edit_venue_desativar_Text_2'] ?></p>
                </div>
                <button type="button" id="open-delete-modal-btn" data-venue-name="<?php echo htmlspecialchars($venue['name']); ?>" class="w-full md:w-auto bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white border border-red-500 font-bold py-2 px-6 rounded-xl transition-colors">
                    <?php echo $lang['edit_venue_desativar_btn'] ?>
                </button>
            </div>

        <?php endif; ?>
    </main>

    <div class="fixed bottom-0 left-0 w-full p-4 bg-[#161B22]/90 backdrop-blur-md border-t border-gray-800 flex justify-center z-40 md:hidden">
        <button type="submit" form="venue-form" class="w-full bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-3 px-6 rounded-xl shadow-xl transition-transform active:scale-95"><?php echo $lang['edit_venue_photo'] ?></button>
    </div>

    <div id="delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm transition-opacity">
        <div class="bg-[#161B22] border border-gray-700 rounded-2xl shadow-2xl w-full max-w-md transform scale-100">
            <div class="p-6">
                <h3 class="text-xl font-bold text-white flex items-center"><i class="fas fa-trash-alt mr-3 text-red-500"></i> <?php echo $lang['edit_venue_desativar_confirm'] ?></h3>
                <p class="text-gray-400 mt-2 text-sm"><?php echo $lang['edit_venue_desativar_alert'] ?>.</p>
                <div class="mt-6">
                    <p class="text-sm text-gray-300 mb-2"><?php echo $lang['edit_venue_desativar_text_1'] ?> <strong class="text-white" id="confirmation-prompt-name"></strong> <?php echo $lang['edit_venue_desativar_text_2'] ?></p>
                    <input type="text" id="confirmation-input" autocomplete="off" class="w-full px-4 py-3 bg-black/30 border border-gray-600 rounded-xl text-white outline-none focus:border-red-500 transition-all">
                </div>
            </div>
            <div class="p-4 bg-gray-900/50 rounded-b-2xl border-t border-gray-700 flex justify-end gap-3">
                <button type="button" id="cancel-delete-btn" class="px-4 py-2 text-gray-400 hover:text-white"><?php echo $lang['global_cancel'] ?></button>
                <form id="delete-venue-form" action="<?php echo BASE_URL . $prefix; ?>/quadras/excluir/<?php echo $venue['id']; ?>" method="POST">
                    <button type="submit" id="confirm-delete-btn" class="px-6 py-2 bg-red-600 text-white font-bold rounded-lg opacity-50 cursor-not-allowed transition-all" disabled><?php echo $lang['edit_venue_desativar_btn'] ?></button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const userBtn = document.getElementById('user-menu-button');
            const userDropdown = document.getElementById('profile-dropdown');

            if (userBtn && userDropdown) {
                userBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    userDropdown.classList.toggle('opacity-0');
                    userDropdown.classList.toggle('invisible');
                    userDropdown.classList.toggle('-translate-y-2');
                });

                window.addEventListener('click', (e) => {
                    if (!userBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                        userDropdown.classList.add('opacity-0', 'invisible', '-translate-y-2');
                    }
                });
            }

            const venueData = <?php echo json_encode($venue ?? null); ?>;
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
                    e.preventDefault(); // Impede recarregar
                    floorCards.forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');
                    floorInput.value = card.dataset.value;
                });
            });

            // 2. Lógica de Checkboxes
            const leisureContainer = document.getElementById('leisure-capacity-container');
            document.querySelectorAll('.checkbox-card').forEach(card => {
                const checkbox = card.querySelector('input');

                // Função visual
                const updateVisual = () => {
                    card.classList.toggle('selected', checkbox.checked);
                    const icon = card.querySelector('i');
                    icon.classList.toggle('text-cyan-400', checkbox.checked);
                    icon.classList.toggle('text-gray-500', !checkbox.checked);

                    if (checkbox.name === 'has_leisure_area') {
                        leisureContainer.classList.toggle('hidden', !checkbox.checked);
                    }
                };

                // Inicializa visual
                updateVisual();

                card.addEventListener('click', (e) => {
                    e.preventDefault(); // Impede recarregar
                    checkbox.checked = !checkbox.checked; // Troca manual
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
                            // Popula o complemento e limpa se vier vazio
                            document.getElementById('complement').value = data.complemento || '';
                        }
                    } catch (e) {}
                }
            });

            // Upload Logic
            const fileInput = document.getElementById('images');
            const dropArea = document.getElementById('drop-area');
            const previewContainer = document.getElementById('preview-container');
            const dt = new DataTransfer();

            const handleFiles = (files) => {
                for (const file of files) {
                    if (file.type.startsWith('image/')) {
                        dt.items.add(file);
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const div = document.createElement('div');
                            div.className = 'relative group aspect-square rounded-xl overflow-hidden';
                            div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover"><button type="button" class="delete-new absolute top-1 right-1 bg-red-500 text-white w-8 h-8 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">&times;</button>`;
                            previewContainer.appendChild(div);
                        };
                        reader.readAsDataURL(file);
                    }
                }
                fileInput.files = dt.files;
            };

            fileInput.addEventListener('change', e => handleFiles(e.target.files));
            dropArea.addEventListener('click', () => fileInput.click());
            dropArea.addEventListener('dragover', e => {
                e.preventDefault();
                dropArea.classList.add('highlight');
            });
            dropArea.addEventListener('dragleave', e => dropArea.classList.remove('highlight'));
            dropArea.addEventListener('drop', e => {
                e.preventDefault();
                dropArea.classList.remove('highlight');
                handleFiles(e.dataTransfer.files);
            });

            previewContainer.addEventListener('click', e => {
                if (e.target.closest('.delete-new')) {
                    const parentDiv = e.target.closest('div.relative');
                    parentDiv.remove();
                    // Melhorar a remoção do DataTransfer se necessário para evitar submissão de arquivos removidos
                    const remainingFiles = Array.from(dt.files).filter(f => f.name !== parentDiv.querySelector('img').src.split('/').pop()); // Adapte se o nome do arquivo não for o último na URL
                    dt.items.clear();
                    remainingFiles.forEach(f => dt.items.add(f));
                    fileInput.files = dt.files;

                }
                if (e.target.closest('.delete-existing-image')) {
                    const id = e.target.closest('.delete-existing-image').dataset.imageId;
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'delete_images[]';
                    input.value = id;
                    document.getElementById('venue-form').appendChild(input);
                    e.target.closest('.group').remove();
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
                    confirmInput.value = ''; // Limpa o input cada vez que abre
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
        });
    </script>
</body>

</html>