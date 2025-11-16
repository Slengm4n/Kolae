<?php
// Garante que a sessão está iniciada e os dados do local existem.
$venue = $data['venue'] ?? null; // A variável $data virá do seu controller

// --- CORREÇÃO DE ROTA ---
// Pega o prefixo que o VenueController::edit() enviou.
$prefix = $data['routePrefix'] ?? '/dashboard';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Local - <?php echo htmlspecialchars($venue['name'] ?? 'Kolae'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* Estilos de card selecionado */
        .option-card.selected,
        .checkbox-card.selected {
            border-color: #22d3ee;
            /* cyan-400 */
            background-color: rgba(34, 211, 238, 0.1);
            /* cyan-500/10 */
        }

        .checkbox-card.selected i {
            color: #22d3ee;
            /* cyan-400 */
        }

        /* Destaque da área de drop */
        #drop-area.highlight {
            border-color: #22d3ee;
            /* cyan-400 */
            background-color: rgba(34, 211, 238, 0.1);
        }
    </style>
</head>

<body class="bg-[#0D1117] text-gray-200">

    <div class="flex flex-col min-h-screen">
        <header class="bg-[#161B22] border-b border-gray-800 sticky top-0 z-30 py-4">
            <div class="container mx-auto px-4 flex justify-between items-center">
                <a href="<?php echo BASE_URL; ?>/" class="text-2xl font-bold tracking-widest text-white">KOLAE</a>

                <nav class="hidden md:flex items-center space-x-8">
                    <a href="<?php echo BASE_URL . $prefix; ?>" class="font-semibold text-cyan-400 transition-colors">Meu Painel</a>
                </nav>

                <div class="relative">
                    <div id="user-menu-button" class="flex items-center gap-3 p-2 border border-gray-700 rounded-full cursor-pointer transition-colors hover:bg-gray-700/50">
                        <i class="fas fa-bars text-lg"></i>
                        <i class="fas fa-user-circle text-xl"></i>
                    </div>

                    <div id="profile-dropdown" class="absolute top-full right-0 mt-3 w-72 bg-[#1c2128] border border-gray-700 rounded-xl shadow-2xl opacity-0 invisible transform -translate-y-2 transition-all duration-300">
                        <div class="p-4 border-b border-gray-700">
                            <p class="font-semibold text-white"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Utilizador'); ?></p>
                            <a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="text-sm text-gray-400 hover:underline">Ver perfil</a>
                        </div>
                        <ul class="py-2">
                            <li><a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="flex items-center gap-4 px-5 py-3 text-sm hover:bg-gray-800 transition-colors"><i class="fas fa-cog w-5 text-center text-gray-400"></i> Configurações</a></li>
                            <li><a href="#" class="flex items-center gap-4 px-5 py-3 text-sm hover:bg-gray-800 transition-colors"><i class="fas fa-question-circle w-5 text-center text-gray-400"></i> Ajuda</a></li>
                            <li class="border-t border-gray-700 my-2"></li>
                            <li><a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-5 py-3 text-sm text-red-400 hover:bg-gray-800 transition-colors"><i class="fas fa-sign-out-alt w-5 text-center"></i>Sair</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>


        <main class="flex-grow w-full max-w-3xl mx-auto p-8">
            <?php if (!$venue): ?>
                <div class="text-center p-8">
                    <h2 class="text-3xl font-bold text-red-400">Erro</h2>
                    <p class="text-gray-400 mt-2">Os dados do local não foram encontrados.</p>
                </div>
            <?php else: ?>

                <form id="venue-form" action="<?php echo BASE_URL . $prefix; ?>/quadras/atualizar/<?php echo $venue['id']; ?>" method="POST" enctype="multipart/form-data" class="w-full h-full flex flex-col space-y-12">

                    <input type="hidden" name="address_id" value="<?php echo $venue['address_id']; ?>">
                    <input type="hidden" id="floor_type_input" name="floor_type" value="<?php echo htmlspecialchars($venue['floor_type']); ?>">
                    <input type="hidden" id="court_capacity_input" name="court_capacity" value="<?php echo htmlspecialchars($venue['court_capacity']); ?>">
                    <input type="hidden" id="leisure_area_capacity_input" name="leisure_area_capacity" value="<?php echo htmlspecialchars($venue['leisure_area_capacity']); ?>">

                    <section id="section-details">
                        <h2 class="text-3xl font-bold text-white mb-6">Detalhes Principais</h2>
                        <div class="space-y-6">
                            <div>
                                <label for="name" class="block text-gray-400 text-sm font-bold mb-2">Nome do Local</label>
                                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($venue['name']); ?>" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500">
                            </div>
                            <div>
                                <label for="average_price_per_hour" class="block text-gray-400 text-sm font-bold mb-2">Preço Médio por Hora (R$)</label>
                                <input type="number" step="0.01" id="average_price_per_hour" name="average_price_per_hour" value="<?php echo htmlspecialchars($venue['average_price_per_hour']); ?>" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500">
                            </div>
                        </div>
                    </section>

                    <section id="section-floor-type">
                        <h2 class="text-3xl font-bold text-white mb-6">Tipo de Piso</h2>
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
                    </section>

                    <section id="section-capacity">
                        <h2 class="text-3xl font-bold text-white mb-6">Capacidade da Quadra</h2>
                        <p class="text-gray-400 mb-6">Refere-se ao número de jogadores em campo ao mesmo tempo.</p>
                        <div class="flex justify-between items-center max-w-xs">
                            <label class="font-semibold text-lg">Jogadores</label>
                            <div class="counter-input flex items-center gap-4" data-input-name="court_capacity" data-min-value="2" data-target-input="court_capacity_input">
                                <button type="button" class="counter-btn minus w-10 h-10 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700">-</button>
                                <span class="counter-value text-lg font-bold"><?php echo $venue['court_capacity']; ?></span>
                                <button type="button" class="counter-btn plus w-10 h-10 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700">+</button>
                            </div>
                        </div>
                    </section>

                    <section id="section-amenities">
                        <h2 class="text-3xl font-bold text-white mb-6">Comodidades</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <label class="checkbox-card border border-gray-700 rounded-lg p-4 flex flex-col items-start justify-start space-y-2 cursor-pointer hover:border-cyan-400 transition-colors">
                                <input type="checkbox" name="has_lighting" value="1" class="hidden" <?php echo $venue['has_lighting'] ? 'checked' : ''; ?>>
                                <i class="fas fa-lightbulb text-2xl text-gray-400 mb-2"></i><span class="font-semibold text-base">Iluminação</span>
                            </label>
                            <label class="checkbox-card border border-gray-700 rounded-lg p-4 flex flex-col items-start justify-start space-y-2 cursor-pointer hover:border-cyan-400 transition-colors">
                                <input type="checkbox" name="is_covered" value="1" class="hidden" <?php echo $venue['is_covered'] ? 'checked' : ''; ?>>
                                <i class="fas fa-cloud-sun text-2xl text-gray-400 mb-2"></i><span class="font-semibold text-base">Quadra Coberta</span>
                            </label>
                            <label id="leisure-area-checkbox-card" class="checkbox-card border border-gray-700 rounded-lg p-4 flex flex-col items-start justify-start space-y-2 cursor-pointer hover:border-cyan-400 transition-colors">
                                <input type="checkbox" name="has_leisure_area" value="1" class="hidden" <?php echo $venue['has_leisure_area'] ? 'checked' : ''; ?>>
                                <i class="fas fa-utensils text-2xl text-gray-400 mb-2"></i><span class="font-semibold text-base">Área de Lazer</span>
                            </label>
                        </div>
                        <div id="leisure-capacity-container" class="hidden mt-8">
                            <hr class="border-gray-800 mb-8">
                            <h3 class="text-2xl font-bold text-white mb-4">Qual a capacidade da sua área de lazer?</h3>
                            <div class="flex justify-between items-center max-w-xs">
                                <label class="font-semibold text-lg">Pessoas</label>
                                <div class="counter-input flex items-center gap-4" data-input-name="leisure_area_capacity" data-min-value="0" data-target-input="leisure_area_capacity_input">
                                    <button type="button" class="counter-btn minus w-10 h-10 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700">-</button>
                                    <span class="counter-value text-lg font-bold"><?php echo $venue['leisure_area_capacity']; ?></span>
                                    <button type="button" class="counter-btn plus w-10 h-10 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700">+</button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="section-address">
                        <h2 class="text-3xl font-bold text-white mb-6">Endereço</h2>
                        <div class="space-y-4">
                            <div><label for="cep" class="block text-gray-400 text-sm font-bold mb-2">CEP</label><input type="text" id="cep" name="cep" required value="<?php echo htmlspecialchars($venue['cep']); ?>" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500"></div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="col-span-2"><label for="street" class="block text-gray-400 text-sm font-bold mb-2">Rua</label><input type="text" id="street" name="street" value="<?php echo htmlspecialchars($venue['street']); ?>" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500"></div>
                                <div><label for="number" class="block text-gray-400 text-sm font-bold mb-2">Número</label><input type="text" id="number" name="number" value="<?php echo htmlspecialchars($venue['number']); ?>" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500"></div>
                            </div>
                            <div><label for="neighborhood" class="block text-gray-400 text-sm font-bold mb-2">Bairro</label><input type="text" id="neighborhood" name="neighborhood" value="<?php echo htmlspecialchars($venue['neighborhood']); ?>" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500"></div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="col-span-2"><label for="city" class="block text-gray-400 text-sm font-bold mb-2">Cidade</label><input type="text" id="city" name="city" value="<?php echo htmlspecialchars($venue['city']); ?>" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500"></div>
                                <div><label for="state" class="block text-gray-400 text-sm font-bold mb-2">Estado</label><input type="text" id="state" name="state" value="<?php echo htmlspecialchars($venue['state']); ?>" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500"></div>
                            </div>
                        </div>
                    </section>

                    <section id="section-photos">
                        <h2 class="text-3xl font-bold text-white mb-6">Fotos</h2>
                        <div id="drop-area" class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center cursor-pointer hover:border-cyan-400 transition-colors">
                            <label for="images" class="cursor-pointer"><i class="fas fa-cloud-upload-alt text-5xl text-gray-500"></i>
                                <p class="mt-4 font-semibold">Arraste e solte novas fotos aqui</p>
                                <p class="text-sm text-gray-500">ou clique para selecionar</p><input type="file" id="images" name="images[]" multiple accept="image/jpeg, image/png" class="hidden">
                            </label>
                        </div>

                        <div id="preview-container" class="mt-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                            <?php
                            if (!empty($venue['images']) && is_array($venue['images'])) {
                                foreach ($venue['images'] as $image) {
                                    $imgUrl = BASE_URL . '/uploads/venues/' . $venue['id'] . '/' . htmlspecialchars($image['file_path']);
                                    $imgId = htmlspecialchars($image['id']);
                                    echo '<div class="relative existing-image-preview">';
                                    echo '  <img src="' . $imgUrl . '" alt="Foto do local" class="w-full h-32 object-cover rounded-lg">';
                                    echo '  <button type="button" class="delete-existing-image absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-lg" data-image-id="' . $imgId . '">&times;</button>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    </section>

                </form>

                <section id="section-danger-zone" class="mt-12">
                    <h2 class="text-3xl font-bold text-red-500 mb-6">Área de Perigo</h2>

                    <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center">
                        <div>
                            <h3 class="font-bold text-lg text-white">Desativar esta quadra</h3>
                            <p class="text-sm text-gray-400 max-w-md mt-1">
                                Uma vez desativada, a quadra não aparecerá mais nos resultados de busca e não poderá ser reservada.
                            </p>
                        </div>

                        <form id="delete-venue-form"
                            action="<?php echo BASE_URL . $prefix; ?>/quadras/excluir/<?php echo $venue['id']; ?>"
                            method="POST"
                            class="mt-4 sm:mt-0">

                            <button type="button"
                                id="open-delete-modal-btn"
                                data-venue-name="<?php echo htmlspecialchars($venue['name']); ?>"
                                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-colors">
                                Desativar Quadra
                            </button>
                        </form>
                    </div>
                </section>

            <?php endif; ?>
        </main>

        <footer class="w-full p-4 border-t border-gray-800 sticky bottom-0 bg-[#0D1117] z-10">
            <div class="max-w-3xl mx-auto flex justify-end">
                <button type="submit" form="venue-form" class="bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-3 px-8 rounded-lg transition-colors">Salvar Alterações</button>
            </div>
        </footer>
    </div>
    <div id="delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-70 backdrop-blur-sm">
        <div class="bg-[#161B22] border border-gray-700 rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <h3 class="text-xl font-bold text-white">Você tem certeza?</h3>
                <p class="text-gray-400 mt-2">
                    Esta ação <strong class="text-red-400">não pode ser desfeita</strong>.
                    Isso irá desativar permanentemente a quadra.
                </p>

                <div class="mt-4">
                    <label for="confirmation-input" class="text-sm font-semibold text-gray-300">
                        <span id="confirmation-prompt"></span>
                    </label>
                    <input type="text" id="confirmation-input" autocomplete="off" class="mt-2 w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg focus:outline-none focus:border-cyan-500">
                </div>
            </div>

            <div class="flex justify-end gap-4 bg-[#0D1117] p-4 rounded-b-lg border-t border-gray-700">
                <button type="button" id="cancel-delete-btn" class="py-2 px-4 bg-gray-600 hover:bg-gray-500 text-white font-semibold rounded-lg transition-colors">
                    Cancelar
                </button>
                <button type="button" id="confirm-delete-btn" class="py-2 px-4 bg-red-600 text-white font-bold rounded-lg transition-colors opacity-50 cursor-not-allowed" disabled>
                    Eu entendo, desativar
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const venueData = <?php echo json_encode($venue ?? null); ?>;
            if (!venueData) return;

            const form = document.getElementById('venue-form');

            // --- Lógica dos Contadores (Capacidade) ---
            document.querySelectorAll('.counter-input').forEach(counter => {
                const valueSpan = counter.querySelector('.counter-value');
                const targetInputId = counter.dataset.targetInput;
                const hiddenInput = document.getElementById(targetInputId);
                const minValue = parseInt(counter.dataset.minValue, 10);
                let value = parseInt(valueSpan.textContent, 10);

                counter.querySelector('.minus').addEventListener('click', () => {
                    if (value > minValue) value--;
                    valueSpan.textContent = value;
                    if (hiddenInput) hiddenInput.value = value;
                });
                counter.querySelector('.plus').addEventListener('click', () => {
                    value++;
                    valueSpan.textContent = value;
                    if (hiddenInput) hiddenInput.value = value;
                });
            });

            // --- Lógica dos Cards de Opção (Tipo de Piso) ---
            const optionCards = document.querySelectorAll('.option-card');
            const floorTypeInput = document.getElementById('floor_type_input');

            optionCards.forEach(card => {
                card.addEventListener('click', () => {
                    optionCards.forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');
                    floorTypeInput.value = card.dataset.value;
                });
            });

            // --- Lógica dos Checkbox-Cards (Comodidades) ---
            const leisureCapacityContainer = document.getElementById('leisure-capacity-container');
            const leisureCapacityInput = document.getElementById('leisure_area_capacity_input');
            const leisureCapacityValueSpan = leisureCapacityContainer.querySelector('.counter-value');

            document.querySelectorAll('.checkbox-card').forEach(card => {
                const checkbox = card.querySelector('input[type="checkbox"]');
                const updateVisual = (isChecked) => {
                    card.classList.toggle('selected', isChecked);
                    if (card.id === 'leisure-area-checkbox-card') {
                        leisureCapacityContainer.classList.toggle('hidden', !isChecked);
                        if (!isChecked) {
                            leisureCapacityValueSpan.textContent = '0';
                            leisureCapacityInput.value = '0';
                        }
                    }
                };
                card.addEventListener('click', (e) => {
                    if (e.target.tagName === 'INPUT') {
                        updateVisual(checkbox.checked);
                        return;
                    }
                    e.preventDefault();
                    checkbox.checked = !checkbox.checked;
                    updateVisual(checkbox.checked);
                });
            });

            // --- Lógica do ViaCEP ---
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

            // --- Lógica de Upload/Preview de Imagens ---
            const dropArea = document.getElementById('drop-area');
            const fileInput = document.getElementById('images');
            const previewContainer = document.getElementById('preview-container');
            const newFilesDataTransfer = new DataTransfer();

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                if (dropArea) dropArea.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });
            ['dragenter', 'dragover'].forEach(eventName => {
                if (dropArea) dropArea.addEventListener(eventName, () => dropArea.classList.add('highlight'), false);
            });
            ['dragleave', 'drop'].forEach(eventName => {
                if (dropArea) dropArea.addEventListener(eventName, () => dropArea.classList.remove('highlight'), false);
            });
            if (dropArea) dropArea.addEventListener('drop', (e) => handleFiles(e.dataTransfer.files), false);
            if (fileInput) fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

            function handleFiles(files) {
                for (const file of files) {
                    if (file.type.startsWith('image/') && !isFileInTransfer(file.name)) {
                        newFilesDataTransfer.items.add(file);
                        previewNewFile(file);
                    }
                }
                if (fileInput) fileInput.files = newFilesDataTransfer.files;
            }

            function isFileInTransfer(fileName) {
                return Array.from(newFilesDataTransfer.files).some(f => f.name === fileName);
            }

            function previewNewFile(file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const previewDiv = document.createElement('div');
                    previewDiv.classList.add('relative', 'new-image-preview');
                    previewDiv.innerHTML = `
                        <img src="${e.target.result}" alt="Preview" class="w-full h-32 object-cover rounded-lg">
                        <button type="button" class="delete-new-image absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-lg" data-file-name="${file.name}">&times;</button>
                    `;
                    if (previewContainer) previewContainer.appendChild(previewDiv);
                };
                reader.readAsDataURL(file);
            }

            if (previewContainer) previewContainer.addEventListener('click', function(e) {
                if (e.target.classList.contains('delete-new-image')) {
                    e.preventDefault();
                    const fileName = e.target.dataset.fileName;
                    const newFiles = Array.from(newFilesDataTransfer.files);
                    const filteredFiles = newFiles.filter(file => file.name !== fileName);
                    newFilesDataTransfer.items.clear();
                    filteredFiles.forEach(file => newFilesDataTransfer.items.add(file));
                    if (fileInput) fileInput.files = newFilesDataTransfer.files;
                    e.target.parentElement.remove();
                }

                if (e.target.classList.contains('delete-existing-image')) {
                    e.preventDefault();
                    const imageId = e.target.dataset.imageId;
                    const deleteInput = document.createElement('input');
                    deleteInput.type = 'hidden';
                    deleteInput.name = 'delete_images[]';
                    deleteInput.value = imageId;
                    form.appendChild(deleteInput);
                    e.target.parentElement.remove();
                }
            });

            // --- Pré-preenchimento Visual ---
            function prefillVisuals() {
                const floorCard = document.querySelector(`.option-card[data-value="${venueData.floor_type}"]`);
                if (floorCard) floorCard.classList.add('selected');
                document.querySelectorAll('.checkbox-card input').forEach(checkbox => {
                    if (checkbox.checked) {
                        checkbox.parentElement.classList.add('selected');
                        if (checkbox.name === 'has_leisure_area' && leisureCapacityContainer) {
                            leisureCapacityContainer.classList.remove('hidden');
                        }
                    }
                });
            }
            prefillVisuals();

            // --- Lógica do Menu Dropdown ---
            const userMenuButton = document.getElementById('user-menu-button');
            const profileDropdown = document.getElementById('profile-dropdown');
            if (userMenuButton && profileDropdown) {
                userMenuButton.addEventListener('click', (event) => {
                    event.stopPropagation();
                    profileDropdown.classList.toggle('opacity-0');
                    profileDropdown.classList.toggle('invisible');
                    profileDropdown.classList.toggle('-translate-y-2');
                });
                window.addEventListener('click', (event) => {
                    if (!profileDropdown.classList.contains('invisible')) {
                        if (!profileDropdown.contains(event.target) && !userMenuButton.contains(event.target)) {
                            profileDropdown.classList.add('opacity-0', 'invisible', '-translate-y-2');
                        }
                    }
                });
            }
        });

        // PASSO 3: ADICIONE ESTE CÓDIGO DENTRO DO SEU SCRIPT 'DOMContentLoaded'

        // --- Lógica do Modal de Exclusão (Estilo GitHub) ---
        const openDeleteModalBtn = document.getElementById('open-delete-modal-btn');
        const deleteModal = document.getElementById('delete-modal');
        const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
        const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
        const confirmationInput = document.getElementById('confirmation-input');
        const confirmationPrompt = document.getElementById('confirmation-prompt');
        const deleteVenueForm = document.getElementById('delete-venue-form');

        // Verifica se todos os elementos existem (importante se a quadra falhar ao carregar)
        if (openDeleteModalBtn && deleteModal && cancelDeleteBtn && confirmDeleteBtn && confirmationInput && confirmationPrompt && deleteVenueForm) {

            let requiredName = '';

            // 1. Abrir o modal
            openDeleteModalBtn.addEventListener('click', () => {
                requiredName = openDeleteModalBtn.dataset.venueName;

                // Atualiza o texto de confirmação
                confirmationPrompt.innerHTML = `Por favor, digite <strong class="text-cyan-400">${requiredName}</strong> para confirmar:`;

                // Reseta o formulário
                confirmationInput.value = '';
                confirmDeleteBtn.disabled = true;
                confirmDeleteBtn.classList.add('opacity-50', 'cursor-not-allowed');

                // Mostra o modal
                deleteModal.classList.remove('hidden');
                confirmationInput.focus(); // Foca no input
            });

            // 2. Fechar o modal (Botão Cancelar)
            cancelDeleteBtn.addEventListener('click', () => {
                deleteModal.classList.add('hidden');
            });

            // 3. Fechar o modal (Clicando fora)
            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) {
                    deleteModal.classList.add('hidden');
                }
            });

            // 4. Lógica de verificação do input
            confirmationInput.addEventListener('input', () => {
                const typedName = confirmationInput.value;
                const isMatch = typedName === requiredName;

                confirmDeleteBtn.disabled = !isMatch;
                confirmDeleteBtn.classList.toggle('opacity-50', !isMatch);
                confirmDeleteBtn.classList.toggle('cursor-not-allowed', !isMatch);

                // Adiciona um feedback visual no botão de confirmação
                if (isMatch) {
                    confirmDeleteBtn.classList.remove('bg-red-600');
                    confirmDeleteBtn.classList.add('bg-red-500', 'hover:bg-red-400');
                } else {
                    confirmDeleteBtn.classList.add('bg-red-600');
                    confirmDeleteBtn.classList.remove('bg-red-500', 'hover:bg-red-400');
                }
            });

            // 5. Ação de confirmar
            confirmDeleteBtn.addEventListener('click', () => {
                // Se estiver tudo certo (só por garantia), envia o formulário original
                if (!confirmDeleteBtn.disabled) {
                    deleteVenueForm.submit();
                }
            });
        }
    </script>
</body>

</html>