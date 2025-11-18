<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae</title>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
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
    </style>
</head>

<body class="bg-[#0D1117] text-gray-200">

    <div class="flex flex-col h-screen">
        <!-- Cabeçalho -->
        <header class="bg-[#161B22]/80 backdrop-blur-md border-b border-gray-800 sticky top-0 z-40 py-4 transition-all">
            <div class="container mx-auto px-4 flex justify-between items-center">
                <a href="<?php echo BASE_URL; ?>/" class="flex items-center gap-2 group">
                    <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo" class="h-8 w-auto group-hover:opacity-80 transition-opacity">
                </a>

                <nav class="hidden md:flex items-center space-x-8">
                    <a href="<?php echo BASE_URL; ?>/dashboard" class="font-medium text-gray-400 hover:text-white transition-colors">Meu Painel</a>
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
                        </div>
                        <ul class="py-2 text-sm">
                            <li><a href="<?php echo BASE_URL; ?>/dashboard" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-800 transition-colors"><i class="fas fa-home w-4 text-center text-gray-400"></i> Voltar para Home</a></li>
                            <li><a href="#" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-800 transition-colors"><i class="fas fa-question-circle w-4 text-center text-gray-400"></i> Ajuda</a></li>
                            <li class="border-t border-gray-800 my-2"></li>
                            <li><a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-3 px-5 py-3 text-red-400 hover:bg-red-500/10 transition-colors"><i class="fas fa-sign-out-alt w-4 text-center"></i> Sair</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <!-- Conteúdo Principal -->
        <main class="flex-grow flex items-center justify-center">
            <form id="venue-form" action="<?php echo BASE_URL . $prefix; ?>/quadras/salvar" method="POST" enctype="multipart/form-data" class="w-full h-full flex flex-col">

                <!-- Etapa 1: Tipo de Piso -->
                <div id="step-1" class="step active w-full max-w-2xl mx-auto p-8">
                    <h2 class="text-4xl font-bold text-white mb-8">Qual o tipo de piso principal do seu local?</h2>
                    <div class="space-y-4" data-input-name="floor_type">
                        <div class="option-card border border-gray-700 rounded-lg p-6 cursor-pointer hover:border-cyan-400 transition-colors" data-value="grama sintética">
                            <h3 class="font-semibold text-lg">Grama Sintética</h3>
                        </div>
                        <div class="option-card border border-gray-700 rounded-lg p-6 cursor-pointer hover:border-cyan-400 transition-colors" data-value="cimento">
                            <h3 class="font-semibold text-lg">Cimento / Poliesportivo</h3>
                        </div>
                        <div class="option-card border border-gray-700 rounded-lg p-6 cursor-pointer hover:border-cyan-400 transition-colors" data-value="areia">
                            <h3 class="font-semibold text-lg">Areia</h3>
                        </div>
                        <div class="option-card border border-gray-700 rounded-lg p-6 cursor-pointer hover:border-cyan-400 transition-colors" data-value="saibro">
                            <h3 class="font-semibold text-lg">Saibro</h3>
                        </div>
                        <div class="option-card border border-gray-700 rounded-lg p-6 cursor-pointer hover:border-cyan-400 transition-colors" data-value="grama natural">
                            <h3 class="font-semibold text-lg">Grama Natural</h3>
                        </div>
                    </div>
                </div>

                <!-- Etapa 2: Detalhes Básicos (Capacidade da Quadra) -->
                <div id="step-2" class="step w-full max-w-2xl mx-auto p-8">
                    <h2 class="text-4xl font-bold text-white mb-8">Qual a capacidade da sua quadra?</h2>
                    <p class="text-gray-400 mb-8">Refere-se ao número de jogadores em campo ao mesmo tempo (ex: 10 para um campo de society).</p>
                    <div class="space-y-6">
                        <div class="flex justify-between items-center">
                            <label class="font-semibold text-lg">Jogadores</label>
                            <div class="counter-input flex items-center gap-4" data-input-name="court_capacity" data-min-value="2">
                                <button type="button" class="counter-btn minus w-10 h-10 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700">-</button>
                                <span class="counter-value text-lg font-bold">2</span>
                                <button type="button" class="counter-btn plus w-10 h-10 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700">+</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Etapa 3: Comodidades (Checkboxes) -->
                <div id="step-3" class="step w-full max-w-3xl mx-auto p-8">
                    <h2 class="text-4xl font-bold text-white mb-8">Informe aos jogadores o que seu espaço tem a oferecer</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <label class="checkbox-card border border-gray-700 rounded-lg p-4 flex flex-col items-start justify-start space-y-2 cursor-pointer hover:border-cyan-400 transition-colors">
                            <input type="checkbox" name="has_lighting" value="1" class="hidden">
                            <i class="fas fa-lightbulb text-2xl text-gray-400 mb-2"></i>
                            <span class="font-semibold text-base">Iluminação</span>
                        </label>
                        <label class="checkbox-card border border-gray-700 rounded-lg p-4 flex flex-col items-start justify-start space-y-2 cursor-pointer hover:border-cyan-400 transition-colors">
                            <input type="checkbox" name="is_covered" value="1" class="hidden">
                            <i class="fas fa-cloud-sun text-2xl text-gray-400 mb-2"></i>
                            <span class="font-semibold text-base">Quadra Coberta</span>
                        </label>
                        <label id="leisure-area-checkbox-card" class="checkbox-card border border-gray-700 rounded-lg p-4 flex flex-col items-start justify-start space-y-2 cursor-pointer hover:border-cyan-400 transition-colors">
                            <input type="checkbox" name="has_leisure_area" value="1" class="hidden">
                            <i class="fas fa-utensils text-2xl text-gray-400 mb-2"></i>
                            <span class="font-semibold text-base">Área de Lazer</span>
                        </label>
                        <label class="checkbox-card border border-gray-700 rounded-lg p-4 flex flex-col items-start justify-start space-y-2 cursor-pointer hover:border-cyan-400 transition-colors">
                            <input type="checkbox" name="has_wifi" value="1" class="hidden">
                            <i class="fas fa-wifi text-2xl text-gray-400 mb-2"></i>
                            <span class="font-semibold text-base">Wi-fi</span>
                        </label>
                        <label class="checkbox-card border border-gray-700 rounded-lg p-4 flex flex-col items-start justify-start space-y-2 cursor-pointer hover:border-cyan-400 transition-colors">
                            <input type="checkbox" name="has_parking" value="1" class="hidden">
                            <i class="fas fa-parking text-2xl text-gray-400 mb-2"></i>
                            <span class="font-semibold text-base">Estacionamento</span>
                        </label>
                        <label class="checkbox-card border border-gray-700 rounded-lg p-4 flex flex-col items-start justify-start space-y-2 cursor-pointer hover:border-cyan-400 transition-colors">
                            <input type="checkbox" name="has_locker_room" value="1" class="hidden">
                            <i class="fas fa-shower text-2xl text-gray-400 mb-2"></i>
                            <span class="font-semibold text-base">Vestiário</span>
                        </label>
                    </div>
                    <!-- Contador de capacidade da área de lazer (inicialmente oculto) -->
                    <div id="leisure-capacity-container" class="hidden mt-8 animate-fadeIn">
                        <hr class="border-gray-800 mb-8">
                        <h3 class="text-2xl font-bold text-white mb-4">Qual a capacidade da sua área de lazer?</h3>
                        <div class="flex justify-between items-center max-w-xs">
                            <label class="font-semibold text-lg">Pessoas</label>
                            <div class="counter-input flex items-center gap-4" data-input-name="leisure_area_capacity" data-min-value="0">
                                <button type="button" class="counter-btn minus w-10 h-10 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700">-</button>
                                <span class="counter-value text-lg font-bold">0</span>
                                <button type="button" class="counter-btn plus w-10 h-10 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700">+</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Etapa 4: Nome e Preço -->
                <div id="step-4" class="step w-full max-w-2xl mx-auto p-8">
                    <h2 class="text-4xl font-bold text-white mb-8">Agora, dê um nome e defina um preço</h2>
                    <div class="space-y-6">
                        <div>
                            <label for="name" class="block text-gray-400 text-sm font-bold mb-2">Nome do Local</label>
                            <input type="text" id="name" name="name" required minlength="3" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500" placeholder="Ex: Arena Kolaê">
                        </div>
                        <div>
                            <label for="average_price_per_hour" class="block text-gray-400 text-sm font-bold mb-2">Preço Médio por Hora (R$)</label>
                            <input type="number" step="0.01" min="0.01" id="average_price_per_hour" name="average_price_per_hour" placeholder="Ex: 50.00" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500">
                        </div>
                    </div>
                </div>

                <!-- Etapa 5: Endereço -->
                <div id="step-5" class="step w-full max-w-2xl mx-auto p-8">
                    <h2 class="text-4xl font-bold text-white mb-8">Onde fica o seu local?</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="cep" class="block text-gray-400 text-sm font-bold mb-2">CEP</label>
                            <input type="text" id="cep" name="cep" required placeholder="00000-000" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500">
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-2"><label for="street" class="block text-gray-400 text-sm font-bold mb-2">Rua</label><input type="text" id="street" name="street" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500"></div>
                            <div><label for="number" class="block text-gray-400 text-sm font-bold mb-2">Número</label><input type="text" id="number" name="number" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500"></div>
                        </div>
                        <div><label for="neighborhood" class="block text-gray-400 text-sm font-bold mb-2">Bairro</label><input type="text" id="neighborhood" name="neighborhood" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500"></div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-2"><label for="city" class="block text-gray-400 text-sm font-bold mb-2">Cidade</label><input type="text" id="city" name="city" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500"></div>
                            <div><label for="state" class="block text-gray-400 text-sm font-bold mb-2">Estado</label><input type="text" id="state" name="state" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500"></div>
                        </div>
                    </div>
                </div>

                <!-- Etapa 6: Upload de Fotos -->
                <div id="step-6" class="step w-full max-w-2xl mx-auto p-8">
                    <h2 class="text-4xl font-bold text-white mb-8">Faça seu local se destacar com fotos</h2>
                    <div id="drop-area" class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center cursor-pointer hover:border-cyan-400 transition-colors">
                        <label for="images" class="cursor-pointer"><i class="fas fa-cloud-upload-alt text-5xl text-gray-500"></i>
                            <p class="mt-4 font-semibold">Arraste e solte suas fotos aqui</p>
                            <p class="text-sm text-gray-500">ou clique para selecionar</p><input type="file" id="images" name="images[]" multiple accept="image/jpeg, image/png" class="hidden">
                        </label>
                    </div>
                    <div id="preview-container" class="mt-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4"></div>
                </div>

            </form>
        </main>

        <!-- Rodapé de Navegação -->
        <footer class="w-full p-4 border-t border-gray-800">
            <div class="max-w-2xl mx-auto">
                <div class="w-full bg-gray-700 rounded-full h-1.5 mb-4">
                    <div id="progress-bar" class="bg-cyan-400 h-1.5 rounded-full" style="width: 16.6%"></div>
                </div>
                <div class="flex justify-between items-center">
                    <button id="prev-btn" class="font-bold py-2 px-4 rounded-lg hover:bg-gray-800 transition-colors underline">Voltar</button>
                    <button id="next-btn" class="bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-3 px-8 rounded-lg transition-colors">Avançar</button>
                </div>
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Menu do Usuário
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
        });


        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('venue-form');
            const steps = Array.from(document.querySelectorAll('.step'));
            const prevBtn = document.getElementById('prev-btn');
            const nextBtn = document.getElementById('next-btn');
            const progressBar = document.getElementById('progress-bar');
            let currentStep = 0;
            const totalSteps = steps.length;
            const formData = {};

            function updateUI() {
                steps.forEach((step, index) => step.classList.toggle('active', index === currentStep));
                prevBtn.classList.toggle('invisible', currentStep === 0);
                nextBtn.textContent = currentStep === totalSteps - 1 ? 'Concluir e Cadastrar' : 'Avançar';
                progressBar.style.width = `${((currentStep + 1) / totalSteps) * 100}%`;
            }

            nextBtn.addEventListener('click', () => {
                if (currentStep < totalSteps - 1) {
                    currentStep++;
                    updateUI();
                } else {
                    for (const key in formData) {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = key;
                        hiddenInput.value = formData[key];
                        form.appendChild(hiddenInput);
                    }
                    form.submit();
                }
            });
            prevBtn.addEventListener('click', () => {
                if (currentStep > 0) {
                    currentStep--;
                    updateUI();
                }
            });

            document.querySelectorAll('.option-card').forEach(card => {
                card.addEventListener('click', () => {
                    const group = card.parentElement;
                    group.querySelectorAll('.option-card').forEach(c => c.classList.remove('border-cyan-400', 'bg-cyan-500/10'));
                    card.classList.add('border-cyan-400', 'bg-cyan-500/10');
                    formData[group.dataset.inputName] = card.dataset.value;
                });
            });

            document.querySelectorAll('.counter-input').forEach(counter => {
                const valueSpan = counter.querySelector('.counter-value');
                const inputName = counter.dataset.inputName;
                const minValue = parseInt(counter.dataset.minValue, 10);
                let value = parseInt(valueSpan.textContent, 10);
                formData[inputName] = value;

                counter.querySelector('.minus').addEventListener('click', () => {
                    if (value > minValue) value--;
                    valueSpan.textContent = value;
                    formData[inputName] = value;
                });
                counter.querySelector('.plus').addEventListener('click', () => {
                    value++;
                    valueSpan.textContent = value;
                    formData[inputName] = value;
                });
            });

            document.querySelectorAll('.checkbox-card').forEach(card => {
                const checkbox = card.querySelector('input[type="checkbox"]');
                card.addEventListener('click', () => {
                    checkbox.checked = !checkbox.checked;
                    card.classList.toggle('border-cyan-400', checkbox.checked);
                    card.classList.toggle('bg-cyan-500/10', checkbox.checked);
                    card.querySelector('i').classList.toggle('text-cyan-400', checkbox.checked);

                    if (card.id === 'leisure-area-checkbox-card') {
                        document.getElementById('leisure-capacity-container').classList.toggle('hidden', !checkbox.checked);
                        if (!checkbox.checked) {
                            const leisureCounter = document.querySelector('[data-input-name="leisure_area_capacity"]');
                            leisureCounter.querySelector('.counter-value').textContent = '0';
                            formData['leisure_area_capacity'] = 0;
                        }
                    }
                });
            });

            document.getElementById('cep').addEventListener('blur', async function() {
                const cep = this.value.replace(/\D/g, '');
                if (cep.length === 8) {
                    try {
                        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                        const data = await response.json();
                        if (!data.erro) {
                            document.getElementById('street').value = data.logradouro;
                            document.getElementById('neighborhood').value = data.bairro;
                            document.getElementById('city').value = data.localidade;
                            document.getElementById('state').value = data.uf;
                            document.getElementById('number').focus();
                        }
                    } catch (error) {
                        console.error('Erro ao buscar CEP:', error);
                    }
                }
            });

            const dropArea = document.getElementById('drop-area');
            const fileInput = document.getElementById('images');
            const previewContainer = document.getElementById('preview-container');

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eName => dropArea.addEventListener(eName, e => {
                e.preventDefault();
                e.stopPropagation();
            }));
            ['dragenter', 'dragover'].forEach(eName => dropArea.addEventListener(eName, () => dropArea.classList.add('border-cyan-400', 'bg-gray-800/50')));
            ['dragleave', 'drop'].forEach(eName => dropArea.addEventListener(eName, () => dropArea.classList.remove('border-cyan-400', 'bg-gray-800/50')));

            const handleFiles = files => {
                previewContainer.innerHTML = '';
                [...files].forEach(file => {
                    const reader = new FileReader();
                    reader.onload = e => {
                        const preview = document.createElement('div');
                        preview.className = 'relative w-full aspect-square rounded-lg overflow-hidden';
                        preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover"><button type="button" class="remove-btn absolute top-1 right-1 bg-black/50 text-white rounded-full w-6 h-6 flex items-center justify-center">&times;</button>`;
                        previewContainer.appendChild(preview);
                    };
                    reader.readAsDataURL(file);
                });
            };
            dropArea.addEventListener('drop', e => handleFiles(e.dataTransfer.files));
            fileInput.addEventListener('change', e => handleFiles(e.target.files));

            updateUI();
        });
    </script>
</body>

</html>