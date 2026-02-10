<?php
$lang = require BASE_PATH . '/includes/i18n.php';
// Recupera dados da sessão
$userName = $_SESSION['user_name'] ?? 'Usuário';
$userAvatar = $_SESSION['user_avatar'] ?? null;
$userId = $_SESSION['user_id'] ?? 0;
// Prefixo para rotas
$routePrefix = '/dashboard';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma']; ?>" class="transition-colors duration-500">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kolae</title>

    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">

    <?php include 'app/views/partials/theme_script.php'; ?>

    <style>
        html {
            overflow-y: scroll;
        }

        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .step {
            display: none;
        }

        .step.active {
            display: block;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Shake Animation for Errors */
        .shake {
            animation: shake 0.5s cubic-bezier(.36, .07, .19, .97) both;
        }

        @keyframes shake {

            10%,
            90% {
                transform: translate3d(-1px, 0, 0);
            }

            20%,
            80% {
                transform: translate3d(2px, 0, 0);
            }

            30%,
            50%,
            70% {
                transform: translate3d(-4px, 0, 0);
            }

            40%,
            60% {
                transform: translate3d(4px, 0, 0);
            }
        }

        /* Estilização específica para os cards de opção no Light/Dark mode */
        .option-card,
        .checkbox-card {
            transition: all 0.3s ease;
        }

        .option-card:hover,
        .checkbox-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>

<body class="bg-surface-base text-content-primary flex flex-col min-h-screen transition-colors duration-500">

    <div id="toast" style="position: fixed; bottom: 6rem; right: 1.25rem; z-index: 60;" class="bg-red-500 text-white px-6 py-4 rounded-xl shadow-xl transform transition-all duration-300 translate-x-full opacity-0 flex items-center gap-3">
        <i class="fas fa-exclamation-circle text-xl"></i>
        <span id="toast-message" class="font-medium">Erro na validação</span>
    </div>

    <header class="bg-surface-elevated/80 backdrop-blur-md border-b border-content-secondary/10 sticky top-0 z-30 py-4 transition-all">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <a href="<?php echo BASE_URL; ?>/" class="flex items-center gap-2 group">
                <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo" class="h-8 w-auto filter dark:filter-none invert dark:invert-0 opacity-90 group-hover:opacity-100 transition-opacity">
            </a>

            <nav class="hidden md:flex items-center space-x-8">
                <a href="<?php echo BASE_URL; ?>/dashboard" class="font-semibold text-cyan-600 dark:text-cyan-400 relative after:content-[''] after:absolute after:-bottom-1 after:left-0 after:w-full after:h-0.5 after:bg-cyan-600 dark:after:bg-cyan-400 after:scale-x-100 transition-transform">
                    <?php echo $lang['global_home_panel'] ?></a>
            </nav>

            <div class="relative">
                <button id="user-menu-button" class="flex items-center gap-3 p-2 px-3 border border-content-secondary/20 rounded-full cursor-pointer transition-all hover:bg-surface-secondary">
                    <?php if (!empty($userAvatar)): ?>
                        <img src="<?php echo BASE_URL . '/uploads/avatars/' . $userId . '/' . $userAvatar; ?>"
                            class="w-8 h-8 rounded-full object-cover border border-content-secondary/20"
                            alt="Avatar"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm" style="display:none;">
                            <?php echo strtoupper(substr($userName, 0, 1)); ?>
                        </div>
                    <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm">
                            <?php echo strtoupper(substr($userName, 0, 1)); ?>
                        </div>
                    <?php endif; ?>

                    <i class="fas fa-chevron-down text-xs text-content-secondary"></i>
                    </button>

                    <div id="profile-dropdown" class="absolute top-full right-0 mt-3 w-64 bg-surface-elevated border border-content-secondary/10 rounded-xl shadow-2xl opacity-0 invisible transform -translate-y-2 transition-all duration-200 z-50">
                        <div class="p-4 border-b border-content-secondary/10">
                            <p class="font-semibold text-content-primary truncate"><?php echo htmlspecialchars($userName); ?></p>
                        </div>
                        <ul class="py-2 text-sm">
                            <li><a href="<?php echo BASE_URL; ?>/dashboard" class="flex items-center gap-3 px-5 py-3 text-content-secondary hover:bg-surface-secondary hover:text-content-primary transition-colors"><i class="fas fa-home w-4 text-center"></i><?php echo $lang['global_back'] ?></a></li>

                            <li>
                                <button id="theme-toggle" class="w-full flex items-center gap-3 px-5 py-3 text-content-secondary hover:bg-surface-secondary hover:text-content-primary transition-colors text-left">
                                    <i class="fas fa-adjust w-4 text-center"></i>
                                    <span class="dark:hidden">Modo Escuro</span>
                                    <span class="hidden dark:inline">Modo Claro</span>
                                </button>
                            </li>

                            <li class="border-t border-content-secondary/10 my-2"></li>
                            <li><a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-3 px-5 py-3 text-red-500 hover:bg-red-500/10 transition-colors"><i class="fas fa-sign-out-alt w-4 text-center"></i> <?php echo $lang['global_menu_exit'] ?></a></li>
                        </ul>
                    </div>
            </div>
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center py-10 px-4">
        <form id="venue-form" action="<?php echo BASE_URL . $routePrefix; ?>/quadras/salvar" method="POST" enctype="multipart/form-data" class="w-full max-w-4xl">

            <div id="step-1" class="step active w-full max-w-3xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold text-content-primary mb-2 text-center">Tipo de Piso</h2>
                <p class="text-content-secondary text-center mb-8">Qual o revestimento principal da sua quadra?</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4" data-input-name="floor_type">
                    <?php
                    $pisos = ['Grama Sintética', 'Cimento / Poliesportivo', 'Areia', 'Saibro', 'Grama Natural', 'Madeira / Taco'];
                    foreach ($pisos as $piso):
                    ?>
                        <div class="option-card bg-surface-elevated border border-content-secondary/20 rounded-2xl p-6 cursor-pointer hover:border-cyan-500 group flex items-center justify-between" data-value="<?php echo strtolower($piso); ?>">
                            <span class="font-semibold text-lg text-content-primary group-hover:text-cyan-600 dark:group-hover:text-cyan-400"><?php echo $piso; ?></span>
                            <div class="w-5 h-5 rounded-full border-2 border-content-secondary/30 group-hover:border-cyan-500 flex items-center justify-center">
                                <div class="w-2.5 h-2.5 rounded-full bg-cyan-500 opacity-0 transition-opacity check-dot"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="step-2" class="step w-full max-w-2xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold text-content-primary mb-2 text-center">Capacidade</h2>
                <p class="text-content-secondary text-center mb-10">Quantos jogadores cabem na quadra simultaneamente?</p>

                <div class="bg-surface-elevated border border-content-secondary/10 rounded-3xl p-8 md:p-12 shadow-lg">
                    <div class="flex flex-col items-center gap-6">
                        <i class="fas fa-users text-5xl text-cyan-500 mb-2"></i>
                        <div class="counter-input flex items-center gap-6" data-input-name="court_capacity" data-min-value="2">
                            <button type="button" class="counter-btn minus w-14 h-14 rounded-2xl bg-surface-base border border-content-secondary/20 text-content-secondary hover:text-cyan-500 hover:border-cyan-500 text-2xl transition-all font-bold">-</button>
                            <span class="counter-value text-5xl font-bold text-content-primary w-20 text-center">2</span>
                            <button type="button" class="counter-btn plus w-14 h-14 rounded-2xl bg-surface-base border border-content-secondary/20 text-content-secondary hover:text-cyan-500 hover:border-cyan-500 text-2xl transition-all font-bold">+</button>
                        </div>
                        <span class="text-sm font-semibold uppercase tracking-widest text-content-secondary">Jogadores</span>
                    </div>
                </div>
            </div>

            <div id="step-3" class="step w-full max-w-4xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold text-content-primary mb-2 text-center">Comodidades</h2>
                <p class="text-content-secondary text-center mb-8">O que o seu espaço oferece aos atletas?</p>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <?php
                    $comodidades = [
                        ['icon' => 'lightbulb', 'label' => 'Iluminação', 'name' => 'has_lighting'],
                        ['icon' => 'cloud-sun', 'label' => 'Coberta', 'name' => 'is_covered'],
                        ['icon' => 'wifi', 'label' => 'Wi-fi', 'name' => 'has_wifi'],
                        ['icon' => 'parking', 'label' => 'Estacionamento', 'name' => 'has_parking'],
                        ['icon' => 'shower', 'label' => 'Vestiário', 'name' => 'has_locker_room'],
                        ['icon' => 'glass-cheers', 'label' => 'Bar/Lanchonete', 'name' => 'has_bar'],
                        ['icon' => 'utensils', 'label' => 'Área de Lazer', 'name' => 'has_leisure_area', 'id' => 'leisure-area-card'],
                        ['icon' => 'tshirt', 'label' => 'Coletes', 'name' => 'has_vests'],
                        ['icon' => 'futbol', 'label' => 'Bola', 'name' => 'has_ball']
                    ];
                    foreach ($comodidades as $com):
                    ?>
                        <label <?php echo isset($com['id']) ? 'id="' . $com['id'] . '"' : ''; ?> class="checkbox-card bg-surface-elevated border border-content-secondary/20 rounded-2xl p-6 flex flex-col items-center justify-center gap-3 cursor-pointer hover:border-cyan-500 transition-all text-center h-32 relative overflow-hidden group">
                            <input type="checkbox" name="<?php echo $com['name']; ?>" value="1" class="hidden peer">
                            <div class="absolute inset-0 bg-cyan-500/5 opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                            <div class="absolute top-3 right-3 w-5 h-5 rounded-full bg-cyan-500 text-white text-xs flex items-center justify-center opacity-0 transform scale-0 peer-checked:opacity-100 peer-checked:scale-100 transition-all">
                                <i class="fas fa-check"></i>
                            </div>

                            <i class="fas fa-<?php echo $com['icon']; ?> text-3xl text-content-secondary group-hover:text-cyan-500 peer-checked:text-cyan-500 transition-colors"></i>
                            <span class="font-semibold text-sm text-content-primary"><?php echo $com['label']; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div id="leisure-capacity-container" class="hidden mt-8 animate-fadeIn bg-surface-elevated border border-content-secondary/10 rounded-2xl p-6">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="text-center md:text-left">
                            <h3 class="text-lg font-bold text-content-primary">Capacidade da Área de Lazer</h3>
                            <p class="text-sm text-content-secondary">Quantas pessoas a churrasqueira/área suporta?</p>
                        </div>
                        <div class="counter-input flex items-center gap-4" data-input-name="leisure_area_capacity" data-min-value="0">
                            <button type="button" class="counter-btn minus w-10 h-10 rounded-xl bg-surface-base border border-content-secondary/20 text-content-secondary hover:text-cyan-500">-</button>
                            <span class="counter-value text-xl font-bold text-content-primary w-12 text-center">0</span>
                            <button type="button" class="counter-btn plus w-10 h-10 rounded-xl bg-surface-base border border-content-secondary/20 text-content-secondary hover:text-cyan-500">+</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="step-4" class="step w-full max-w-2xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold text-content-primary mb-2 text-center">Detalhes Finais</h2>
                <p class="text-content-secondary text-center mb-8">Defina o nome e o valor para começar.</p>

                <div class="space-y-6">
                    <div>
                        <label for="name" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-2">Nome do Local <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" class="w-full px-4 py-4 bg-input-base border border-content-secondary/20 rounded-xl text-content-primary focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all" placeholder="Ex: Arena Kolaê">
                    </div>
                    <div>
                        <label for="average_price_per_hour" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-2">Preço Médio por Hora (R$) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-4 text-content-secondary font-semibold">R$</span>
                            <input type="number" step="0.01" min="0.01" id="average_price_per_hour" name="average_price_per_hour" placeholder="00,00" class="w-full pl-12 pr-4 py-4 bg-input-base border border-content-secondary/20 rounded-xl text-content-primary focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all font-mono text-lg">
                        </div>
                    </div>
                </div>
            </div>

            <div id="step-5" class="step w-full max-w-2xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold text-content-primary mb-2 text-center">Localização</h2>
                <p class="text-content-secondary text-center mb-8">Onde os jogadores vão te encontrar?</p>

                <div class="space-y-4">
                    <div>
                        <label for="cep" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-2">CEP <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" id="cep" name="cep" placeholder="00000-000" class="w-full px-4 py-3 bg-input-base border border-content-secondary/20 rounded-xl text-content-primary focus:outline-none focus:border-cyan-500 transition-all">
                            <div id="cep-loading" class="absolute right-4 top-3.5 hidden">
                                <i class="fas fa-circle-notch fa-spin text-cyan-500"></i>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label for="street" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-2">Rua <span class="text-red-500">*</span></label>
                            <input type="text" id="street" name="street" class="w-full px-4 py-3 bg-input-base border border-content-secondary/20 rounded-xl text-content-primary focus:outline-none focus:border-cyan-500 transition-all">
                        </div>
                        <div>
                            <label for="number" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-2">Número <span class="text-red-500">*</span></label>
                            <input type="text" id="number" name="number" class="w-full px-4 py-3 bg-input-base border border-content-secondary/20 rounded-xl text-content-primary focus:outline-none focus:border-cyan-500 transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="neighborhood" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-2">Bairro <span class="text-red-500">*</span></label>
                            <input type="text" id="neighborhood" name="neighborhood" class="w-full px-4 py-3 bg-input-base border border-content-secondary/20 rounded-xl text-content-primary focus:outline-none focus:border-cyan-500 transition-all">
                        </div>
                        <div>
                            <label for="complement" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-2">Complemento</label>
                            <input type="text" id="complement" name="complement" placeholder="Ex: Apto 101" class="w-full px-4 py-3 bg-input-base border border-content-secondary/20 rounded-xl text-content-primary focus:outline-none focus:border-cyan-500 transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label for="city" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-2">Cidade <span class="text-red-500">*</span></label>
                            <input type="text" id="city" name="city" class="w-full px-4 py-3 bg-input-base border border-content-secondary/20 rounded-xl text-content-primary focus:outline-none focus:border-cyan-500 transition-all">
                        </div>
                        <div>
                            <label for="state" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-2">Estado <span class="text-red-500">*</span></label>
                            <input type="text" id="state" name="state" class="w-full px-4 py-3 bg-input-base border border-content-secondary/20 rounded-xl text-content-primary focus:outline-none focus:border-cyan-500 transition-all">
                        </div>
                    </div>
                </div>
            </div>

            <div id="step-6" class="step w-full max-w-3xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold text-content-primary mb-2 text-center">Fotos da Quadra</h2>
                <p class="text-content-secondary text-center mb-8">Boas fotos aumentam em 80% as chances de reserva.</p>

                <div id="drop-area" class="border-2 border-dashed border-content-secondary/30 rounded-2xl p-12 text-center cursor-pointer hover:border-cyan-500 hover:bg-cyan-500/5 transition-all group bg-surface-elevated">
                    <label for="images" class="cursor-pointer flex flex-col items-center">
                        <div class="w-16 h-16 rounded-full bg-surface-base flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <i class="fas fa-cloud-upload-alt text-3xl text-content-secondary group-hover:text-cyan-500"></i>
                        </div>
                        <p class="font-bold text-lg text-content-primary">Arraste e solte ou clique para selecionar</p>
                        <p class="text-sm text-content-secondary mt-1">JPG ou PNG (Máx 5MB)</p>
                        <input type="file" id="images" name="images[]" multiple accept="image/jpeg, image/png" class="hidden">
                    </label>
                </div>

                <div id="preview-container" class="mt-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4"></div>
            </div>

        </form>
    </main>

    <footer class="w-full p-4 border-t border-content-secondary/10 bg-surface-elevated z-50">
        <div class="max-w-2xl mx-auto">
            <div class="w-full bg-surface-base rounded-full h-1.5 mb-6 overflow-hidden">
                <div id="progress-bar" class="bg-gradient-to-r from-cyan-500 to-blue-600 h-1.5 rounded-full transition-all duration-500" style="width: 16%"></div>
            </div>

            <div class="flex justify-between items-center">
                <button id="prev-btn" class="font-semibold text-content-secondary hover:text-content-primary py-2 px-4 rounded-xl hover:bg-surface-base transition-colors invisible">
                    <i class="fas fa-arrow-left mr-2"></i> Voltar
                </button>

                <button id="next-btn" class="bg-cyan-500 hover:bg-cyan-400 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-cyan-500/20 transition-all transform hover:-translate-y-0.5 flex items-center">
                    Avançar <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const userBtn = document.getElementById('user-menu-button');
            const userDropdown = document.getElementById('profile-dropdown');
            const themeToggleBtn = document.getElementById('theme-toggle');

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

            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (document.documentElement.classList.contains('dark')) {
                        document.documentElement.classList.remove('dark');
                        localStorage.theme = 'light';
                    } else {
                        document.documentElement.classList.add('dark');
                        localStorage.theme = 'dark';
                    }
                });
            }

            // Lógica do Formulário Multi-etapas
            const form = document.getElementById('venue-form');
            const steps = Array.from(document.querySelectorAll('.step'));
            const prevBtn = document.getElementById('prev-btn');
            const nextBtn = document.getElementById('next-btn');
            const progressBar = document.getElementById('progress-bar');

            // Armazena dados temporários (exceto arquivos)
            let currentStep = 0;
            const totalSteps = steps.length;
            const formData = {};
            // Para arquivos, usaremos o DataTransfer para manipular o input file diretamente
            const fileDataTransfer = new DataTransfer();

            function updateUI() {
                steps.forEach((step, index) => step.classList.toggle('active', index === currentStep));

                // Botão Voltar
                if (currentStep === 0) {
                    prevBtn.classList.add('invisible');
                } else {
                    prevBtn.classList.remove('invisible');
                }

                // Botão Avançar/Concluir
                if (currentStep === totalSteps - 1) {
                    nextBtn.innerHTML = 'Concluir <i class="fas fa-check ml-2"></i>';
                    nextBtn.classList.remove('bg-cyan-500', 'hover:bg-cyan-400');
                    nextBtn.classList.add('bg-green-500', 'hover:bg-green-400');
                } else {
                    nextBtn.innerHTML = 'Avançar <i class="fas fa-arrow-right ml-2"></i>';
                    nextBtn.classList.add('bg-cyan-500', 'hover:bg-cyan-400');
                    nextBtn.classList.remove('bg-green-500', 'hover:bg-green-400');
                }

                // Barra de Progresso
                progressBar.style.width = `${((currentStep + 1) / totalSteps) * 100}%`;

                // Scroll para o topo suave
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            function showToast(message) {
                const toast = document.getElementById('toast');
                const toastMsg = document.getElementById('toast-message');
                toastMsg.textContent = message;
                toast.classList.remove('translate-x-full', 'opacity-0');
                setTimeout(() => {
                    toast.classList.add('translate-x-full', 'opacity-0');
                }, 3000);
            }

            function highlightError(element) {
                element.classList.add('border-red-500', 'shake');
                element.classList.remove('border-content-secondary/20');
                element.addEventListener('input', function() {
                    this.classList.remove('border-red-500', 'shake');
                    this.classList.add('border-content-secondary/20');
                }, {
                    once: true
                });
            }

            // --- VALIDAÇÃO ---
            function validateStep(index) {
                let isValid = true;
                const currentStepEl = steps[index];

                // Validação Piso (Step 0)
                if (index === 0) {
                    const selected = currentStepEl.querySelector('.option-card.border-cyan-500'); // Verifica classe de ativo
                    if (!selected) {
                        showToast('Selecione um tipo de piso.');
                        return false;
                    }
                }

                // Validação Detalhes (Step 3 - Nome e Preço)
                if (index === 3) {
                    const name = document.getElementById('name');
                    const price = document.getElementById('average_price_per_hour');
                    if (!name.value.trim()) {
                        highlightError(name);
                        isValid = false;
                    }
                    if (!price.value.trim()) {
                        highlightError(price);
                        isValid = false;
                    }
                    if (!isValid) showToast('Preencha os campos obrigatórios.');
                }

                // Validação Endereço (Step 4)
                if (index === 4) {
                    ['cep', 'street', 'number', 'neighborhood', 'city', 'state'].forEach(id => {
                        const el = document.getElementById(id);
                        if (!el.value.trim()) {
                            highlightError(el);
                            isValid = false;
                        }
                    });
                    if (!isValid) showToast('Preencha o endereço completo.');
                }

                return isValid;
            }

            // --- NAVEGAÇÃO ---
            nextBtn.addEventListener('click', () => {
                if (!validateStep(currentStep)) return;

                if (currentStep < totalSteps - 1) {
                    currentStep++;
                    updateUI();
                } else {
                    // SUBMISSÃO FINAL
                    // Prepara inputs hidden para os dados que não são inputs reais do form (Ex: cards selecionáveis)

                    // 1. Piso
                    const pisoSelected = document.querySelector('#step-1 .option-card.border-cyan-500');
                    if (pisoSelected) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'floor_type';
                        input.value = pisoSelected.dataset.value;
                        form.appendChild(input);
                    }

                    // 2. Capacidade e Lazer (Já estão em inputs hidden via JS dos contadores, mas precisamos garantir)
                    // Os dados já foram atualizados nos inputs/form, então o submit nativo deve levar tudo.

                    form.submit();
                }
            });

            prevBtn.addEventListener('click', () => {
                if (currentStep > 0) {
                    currentStep--;
                    updateUI();
                }
            });

            // --- INTERATIVIDADE ---

            // Cards de Piso
            document.querySelectorAll('#step-1 .option-card').forEach(card => {
                card.addEventListener('click', () => {
                    // Remove ativo de todos
                    document.querySelectorAll('#step-1 .option-card').forEach(c => {
                        c.classList.remove('border-cyan-500', 'bg-cyan-500/10');
                        c.querySelector('.check-dot').classList.remove('opacity-100');
                    });
                    // Ativa o clicado
                    card.classList.add('border-cyan-500', 'bg-cyan-500/10');
                    card.querySelector('.check-dot').classList.add('opacity-100');
                });
            });

            // Checkboxes (Comodidades) - Lógica Visual
            document.querySelectorAll('.checkbox-card').forEach(card => {
                const checkbox = card.querySelector('input[type="checkbox"]');

                // Inicializa estado visual se já estiver marcado (recarregamento)
                const updateVisual = () => {
                    const isChecked = checkbox.checked;

                    if (isChecked) {
                        card.classLi.st.add('border-cyan-400', 'bg-cyan-500/10');
                        card.cardSelector('i').classList.add('text-cyan-400');
                    } else {
                        card.classList.remove('border-cyan-400', 'bg-cyan-500/10');
                        card.querySelector('i').classList.remove('text-cyan-400');
                    }

                    formData[checkbox.name] = isChecked ? 1 : 0;

                    if (card.id === 'leisure-area-checkbox-card') {
                        const leisureContainer = document.getElementById('leisure-capacity-container');
                        if (leisureContainer) {
                            leisureCounter.querySelector('.counter-valeu').textContent = '0';
                            formData['leisure_area_capacity'] = 0;
                        }
                    }
                }
                // Inicializa o visual caso o navegador tenha salvo o estado (reload)
                updateVisual();

                // O PULO DO GATO: Escuta a mudança no input, não o clique no card
                checkbox.addEventListener('change', updateVisual);
            });

            // Contadores (Capacidade)
            document.querySelectorAll('.counter-input').forEach(counter => {
                const valueSpan = counter.querySelector('.counter-value');
                const inputName = counter.dataset.inputName;
                const minValue = parseInt(counter.dataset.minValue, 10);

                // Cria um input hidden para enviar esse valor no form
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = inputName;
                hiddenInput.value = valueSpan.textContent;
                counter.appendChild(hiddenInput);

                counter.querySelector('.minus').addEventListener('click', () => {
                    let val = parseInt(valueSpan.textContent);
                    if (val > minValue) {
                        val--;
                        valueSpan.textContent = val;
                        hiddenInput.value = val;
                    }
                });

                counter.querySelector('.plus').addEventListener('click', () => {
                    let val = parseInt(valueSpan.textContent);
                    val++;
                    valueSpan.textContent = val;
                    hiddenInput.value = val;
                });
            });

            // CEP com Autocomplete
            const cepInput = document.getElementById('cep');
            cepInput.addEventListener('blur', async function() {
                const cep = this.value.replace(/\D/g, '');
                if (cep.length === 8) {
                    document.getElementById('cep-loading').classList.remove('hidden');
                    try {
                        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                        const data = await response.json();
                        if (!data.erro) {
                            document.getElementById('street').value = data.logradouro;
                            document.getElementById('neighborhood').value = data.bairro;
                            document.getElementById('city').value = data.localidade;
                            document.getElementById('state').value = data.uf;

                            // Remove erros visuais
                            ['street', 'neighborhood', 'city', 'state'].forEach(id => {
                                document.getElementById(id).classList.remove('border-red-500', 'shake');
                                document.getElementById(id).classList.add('border-content-secondary/20');
                            });
                            document.getElementById('number').focus();
                        } else {
                            showToast('CEP não encontrado.');
                        }
                    } catch (error) {
                        console.error(error);
                    } finally {
                        document.getElementById('cep-loading').classList.add('hidden');
                    }
                }
            });

            // --- LÓGICA DE UPLOAD ARRASTAR E SOLTAR ---
            const dropArea = document.getElementById('drop-area');
            const fileInput = document.getElementById('images');
            const previewContainer = document.getElementById('preview-container');

            // Previne comportamentos padrão
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            // Efeitos visuais
            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, () => dropArea.classList.add('border-cyan-500', 'bg-cyan-500/10'), false);
            });
            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, () => dropArea.classList.remove('border-cyan-500', 'bg-cyan-500/10'), false);
            });

            // Handle Drop e Input Change
            dropArea.addEventListener('drop', handleDrop, false);
            fileInput.addEventListener('change', (e) => handleFiles(e.target.files), false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                handleFiles(files);
            }

            function handleFiles(files) {
                // Adiciona novos arquivos ao DataTransfer global
                [...files].forEach(file => {
                    // Validação simples de tipo
                    if (file.type.startsWith('image/')) {
                        fileDataTransfer.items.add(file);
                        previewFile(file);
                    }
                });

                // Atualiza o input file real com os dados do DataTransfer
                fileInput.files = fileDataTransfer.files;
            }

            function previewFile(file) {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onloadend = function() {
                    const div = document.createElement('div');
                    div.className = 'relative aspect-square rounded-xl overflow-hidden border border-content-secondary/20 group';

                    // Imagem
                    const img = document.createElement('img');
                    img.src = reader.result;
                    img.className = 'w-full h-full object-cover';

                    // Botão Remover
                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'absolute top-2 right-2 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg';
                    removeBtn.innerHTML = '<i class="fas fa-trash-alt text-xs"></i>';
                    removeBtn.type = 'button'; // Importante para não submeter o form

                    removeBtn.onclick = function() {
                        div.remove();
                        // Remove do DataTransfer (Isso é tricky, precisamos recriar o DT)
                        const newDataTransfer = new DataTransfer();
                        [...fileDataTransfer.files].forEach(f => {
                            if (f.name !== file.name) newDataTransfer.items.add(f);
                        });
                        // Atualiza referência global e input
                        // Nota: isso é uma simplificação. Se tiver arquivos com mesmo nome pode dar bug, 
                        // mas para uso geral funciona bem.
                        fileDataTransfer.items.clear();
                        [...newDataTransfer.files].forEach(f => fileDataTransfer.items.add(f));
                        fileInput.files = fileDataTransfer.files;
                    };

                    div.appendChild(img);
                    div.appendChild(removeBtn);
                    previewContainer.appendChild(div);
                }
            }



            // Inicializa UI
            updateUI();
        });
    </script>
</body>

</html>