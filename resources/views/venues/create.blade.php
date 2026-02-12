<x-app-layout>
    @push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        .step { display: none; }
        .step.active { display: block; animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .shake { animation: shake 0.5s cubic-bezier(.36, .07, .19, .97) both; }
        @keyframes shake { 10%, 90% { transform: translate3d(-1px, 0, 0); } 20%, 80% { transform: translate3d(2px, 0, 0); } 30%, 50%, 70% { transform: translate3d(-4px, 0, 0); } 40%, 60% { transform: translate3d(4px, 0, 0); } }
        
        /* Estilização para o estado selecionado (Cyan border) */
        .selected-card {
            border-color: #22d3ee; /* cyan-400 */
            background-color: rgba(6, 182, 212, 0.1);
        }
    </style>
    @endpush

    <div class="min-h-screen bg-[#0D1117] text-gray-200 flex flex-col">
        
        <div id="toast" class="fixed bottom-24 right-5 z-50 bg-red-500 text-white px-6 py-4 rounded-lg shadow-xl transform transition-all duration-300 translate-x-full opacity-0 flex items-center gap-3">
            <i class="fas fa-exclamation-circle"></i>
            <span id="toast-message">Erro na validação</span>
        </div>

        <main class="flex-grow flex items-center justify-center py-10">
            <form id="venue-form" action="{{ route('venues.store') }}" method="POST" enctype="multipart/form-data" class="w-full max-w-4xl">
                @csrf

                <input type="hidden" name="floor_type" id="input_floor_type">
                <input type="hidden" name="court_capacity" id="input_court_capacity" value="2">
                <input type="hidden" name="leisure_area_capacity" id="input_leisure_area_capacity" value="0">
                <div id="step-1" class="step active w-full max-w-2xl mx-auto p-8">
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-8 text-center">Qual o tipo de piso principal?</h2>
                    <div class="space-y-4">
                        @foreach(['grama sintética' => 'Grama Sintética', 'cimento' => 'Cimento / Poliesportivo', 'areia' => 'Areia', 'saibro' => 'Saibro', 'grama natural' => 'Grama Natural'] as $value => $label)
                        <div class="option-card border border-gray-700 rounded-lg p-6 cursor-pointer hover:border-cyan-400 transition-colors bg-gray-800" onclick="selectFloor('{{ $value }}', this)">
                            <h3 class="font-semibold text-lg">{{ $label }}</h3>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div id="step-2" class="step w-full max-w-2xl mx-auto p-8">
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-8 text-center">Qual a capacidade da quadra?</h2>
                    <p class="text-gray-400 mb-8 text-center">Jogadores em campo ao mesmo tempo.</p>
                    <div class="flex justify-center">
                        <div class="flex items-center gap-6 bg-gray-800 p-6 rounded-xl">
                            <button type="button" onclick="updateCounter('court_capacity', -1)" class="w-12 h-12 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700 text-xl font-bold">-</button>
                            <span id="display_court_capacity" class="text-3xl font-bold w-12 text-center">2</span>
                            <button type="button" onclick="updateCounter('court_capacity', 1)" class="w-12 h-12 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700 text-xl font-bold">+</button>
                        </div>
                    </div>
                </div>

                <div id="step-3" class="step w-full max-w-3xl mx-auto p-8">
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-8 text-center">O que seu espaço oferece?</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @php
                            $features = [
                                ['name' => 'has_lighting', 'icon' => 'fa-lightbulb', 'label' => 'Iluminação'],
                                ['name' => 'is_covered', 'icon' => 'fa-cloud-sun', 'label' => 'Coberta'],
                                ['name' => 'has_leisure_area', 'icon' => 'fa-utensils', 'label' => 'Área de Lazer', 'id' => 'leisure-check'],
                                ['name' => 'has_wifi', 'icon' => 'fa-wifi', 'label' => 'Wi-fi'],
                                ['name' => 'has_parking', 'icon' => 'fa-parking', 'label' => 'Estacionamento'],
                                ['name' => 'has_locker_room', 'icon' => 'fa-shower', 'label' => 'Vestiário'],
                            ];
                        @endphp

                        @foreach($features as $f)
                        <label class="checkbox-card border border-gray-700 bg-gray-800 rounded-lg p-4 flex flex-col items-start space-y-2 cursor-pointer hover:border-cyan-400 transition-colors" id="{{ $f['id'] ?? '' }}">
                            <input type="checkbox" name="{{ $f['name'] }}" value="1" class="hidden" onchange="toggleCheckbox(this)">
                            <i class="fas {{ $f['icon'] }} text-2xl text-gray-400 mb-2 transition-colors"></i>
                            <span class="font-semibold text-base">{{ $f['label'] }}</span>
                        </label>
                        @endforeach
                    </div>

                    <div id="leisure-capacity-container" class="hidden mt-8 animate-fadeIn text-center">
                        <hr class="border-gray-800 mb-8">
                        <h3 class="text-2xl font-bold text-white mb-4">Capacidade da área de lazer</h3>
                        <div class="flex justify-center">
                            <div class="flex items-center gap-6 bg-gray-800 p-4 rounded-xl">
                                <button type="button" onclick="updateCounter('leisure_area_capacity', -1)" class="w-10 h-10 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700">-</button>
                                <span id="display_leisure_area_capacity" class="text-2xl font-bold w-12 text-center">0</span>
                                <button type="button" onclick="updateCounter('leisure_area_capacity', 1)" class="w-10 h-10 rounded-full border border-gray-600 text-gray-400 hover:bg-gray-700">+</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="step-4" class="step w-full max-w-2xl mx-auto p-8">
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-8 text-center">Dados Básicos</h2>
                    <div class="space-y-6">
                        <div>
                            <x-input-label for="name" class="text-gray-400 text-lg" :value="__('Nome do Local')" />
                            <input type="text" id="name" name="name" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500 transition-colors text-white" placeholder="Ex: Arena Kolaê" required>
                        </div>
                        <div>
                            <x-input-label for="average_price_per_hour" class="text-gray-400 text-lg" :value="__('Preço Médio por Hora (R$)')" />
                            <input type="number" step="0.01" id="average_price_per_hour" name="average_price_per_hour" placeholder="Ex: 50.00" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500 transition-colors text-white" required>
                        </div>
                    </div>
                </div>

                <div id="step-5" class="step w-full max-w-2xl mx-auto p-8">
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-8 text-center">Localização</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-400 text-sm font-bold mb-2">CEP</label>
                            <input type="text" id="cep" name="cep" onblur="buscarCep(this.value)" placeholder="00000-000" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500 transition-colors text-white">
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <label class="block text-gray-400 text-sm font-bold mb-2">Rua</label>
                                <input type="text" id="street" name="street" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500 transition-colors text-white">
                            </div>
                            <div>
                                <label class="block text-gray-400 text-sm font-bold mb-2">Número</label>
                                <input type="text" id="number" name="number" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500 transition-colors text-white">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-400 text-sm font-bold mb-2">Bairro</label>
                                <input type="text" id="neighborhood" name="neighborhood" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500 transition-colors text-white">
                            </div>
                            <div>
                                <label class="block text-gray-400 text-sm font-bold mb-2">Complemento</label>
                                <input type="text" id="complement" name="complement" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500 transition-colors text-white">
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <label class="block text-gray-400 text-sm font-bold mb-2">Cidade</label>
                                <input type="text" id="city" name="city" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500 transition-colors text-white">
                            </div>
                            <div>
                                <label class="block text-gray-400 text-sm font-bold mb-2">UF</label>
                                <input type="text" id="state" name="state" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-cyan-500 transition-colors text-white">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="step-6" class="step w-full max-w-2xl mx-auto p-8">
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-8 text-center">Fotos do Local</h2>
                    
                    <div id="drop-area" class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center cursor-pointer hover:border-cyan-400 transition-colors bg-gray-800">
                        <label for="images" class="cursor-pointer w-full h-full block">
                            <i class="fas fa-cloud-upload-alt text-5xl text-gray-500 mb-4"></i>
                            <p class="font-semibold text-white">Clique ou arraste fotos aqui</p>
                            <p class="text-sm text-gray-500">JPG, PNG ou WebP</p>
                        </label>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" class="hidden" onchange="previewImages(this)">
                    </div>

                    <div id="preview-container" class="mt-6 grid grid-cols-2 sm:grid-cols-3 gap-4"></div>
                </div>

            </form>
        </main>

        <footer class="w-full p-4 border-t border-gray-800 bg-[#0D1117] sticky bottom-0 z-40">
            <div class="max-w-2xl mx-auto">
                <div class="w-full bg-gray-700 rounded-full h-1.5 mb-4">
                    <div id="progress-bar" class="bg-cyan-400 h-1.5 rounded-full transition-all duration-300" style="width: 16%"></div>
                </div>
                <div class="flex justify-between items-center">
                    <button id="prev-btn" onclick="changeStep(-1)" class="text-gray-400 hover:text-white font-bold py-2 px-4 transition-colors invisible">
                        <i class="fas fa-arrow-left mr-2"></i> Voltar
                    </button>
                    <button id="next-btn" onclick="changeStep(1)" class="bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-3 px-8 rounded-lg transition-colors shadow-lg shadow-cyan-500/20">
                        Avançar <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </footer>
    </div>

    @push('scripts')
    <script>
        // Variáveis de Estado
        let currentStep = 0;
        const steps = document.querySelectorAll('.step');
        const progressBar = document.getElementById('progress-bar');
        const nextBtn = document.getElementById('next-btn');
        const prevBtn = document.getElementById('prev-btn');

        // Inicialização
        function updateUI() {
            steps.forEach((step, index) => {
                step.classList.toggle('active', index === currentStep);
            });

            // Botões
            prevBtn.classList.toggle('invisible', currentStep === 0);
            if (currentStep === steps.length - 1) {
                nextBtn.innerHTML = 'Cadastrar <i class="fas fa-check ml-2"></i>';
                nextBtn.classList.remove('bg-cyan-500', 'hover:bg-cyan-400');
                nextBtn.classList.add('bg-green-500', 'hover:bg-green-400', 'text-white');
            } else {
                nextBtn.innerHTML = 'Avançar <i class="fas fa-arrow-right ml-2"></i>';
                nextBtn.classList.add('bg-cyan-500', 'hover:bg-cyan-400');
                nextBtn.classList.remove('bg-green-500', 'hover:bg-green-400', 'text-white');
            }

            // Barra de Progresso
            const progress = ((currentStep + 1) / steps.length) * 100;
            progressBar.style.width = `${progress}%`;
        }

        // Navegação
        function changeStep(direction) {
            if (direction === 1 && !validateStep(currentStep)) return;

            if (direction === 1 && currentStep === steps.length - 1) {
                document.getElementById('venue-form').submit();
                return;
            }

            currentStep += direction;
            updateUI();
        }

        // Lógica de Seleção de Piso
        function selectFloor(value, element) {
            document.getElementById('input_floor_type').value = value;
            document.querySelectorAll('#step-1 .option-card').forEach(c => c.classList.remove('selected-card'));
            element.classList.add('selected-card');
        }

        // Lógica de Contadores
        function updateCounter(id, change) {
            const input = document.getElementById('input_' + id);
            const display = document.getElementById('display_' + id);
            let val = parseInt(input.value);
            let min = id === 'court_capacity' ? 2 : 0;
            
            if (val + change >= min) {
                val += change;
                input.value = val;
                display.innerText = val;
            }
        }

        // Lógica de Checkbox
        function toggleCheckbox(el) {
            const card = el.parentElement;
            const icon = card.querySelector('i');
            const isChecked = el.checked;

            card.classList.toggle('selected-card', isChecked);
            icon.classList.toggle('text-cyan-400', isChecked);
            icon.classList.toggle('text-gray-400', !isChecked);

            // Logica específica da área de lazer
            if (card.id === 'leisure-check') {
                const container = document.getElementById('leisure-capacity-container');
                if (isChecked) {
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                    // Reseta valor se desmarcar
                    document.getElementById('input_leisure_area_capacity').value = 0;
                    document.getElementById('display_leisure_area_capacity').innerText = 0;
                }
            }
        }

        // Validação Simplificada
        function validateStep(index) {
            let isValid = true;
            const step = steps[index];

            // Passo 1: Piso
            if (index === 0 && !document.getElementById('input_floor_type').value) {
                showToast('Selecione um tipo de piso');
                return false;
            }

            // Passo 4: Nome e Preço
            if (index === 3) {
                const name = document.getElementById('name');
                const price = document.getElementById('average_price_per_hour');
                if (!name.value) { highlightError(name); isValid = false; }
                if (!price.value) { highlightError(price); isValid = false; }
            }

            // Passo 5: Endereço
            if (index === 4) {
                ['cep', 'street', 'number', 'neighborhood', 'city'].forEach(id => {
                    const el = document.getElementById(id);
                    if (!el.value) { highlightError(el); isValid = false; }
                });
            }

            if (!isValid) showToast('Preencha os campos obrigatórios');
            return isValid;
        }

        // Toast e Efeitos
        function showToast(msg) {
            const t = document.getElementById('toast');
            document.getElementById('toast-message').innerText = msg;
            t.classList.remove('translate-x-full', 'opacity-0');
            setTimeout(() => t.classList.add('translate-x-full', 'opacity-0'), 3000);
        }

        function highlightError(el) {
            el.classList.add('border-red-500', 'shake');
            setTimeout(() => el.classList.remove('shake'), 500);
            el.addEventListener('input', () => el.classList.remove('border-red-500'), {once:true});
        }

        // Integração ViaCEP
        async function buscarCep(cep) {
            cep = cep.replace(/\D/g, '');
            if (cep.length === 8) {
                try {
                    const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = await res.json();
                    if (!data.erro) {
                        document.getElementById('street').value = data.logradouro;
                        document.getElementById('neighborhood').value = data.bairro;
                        document.getElementById('city').value = data.localidade;
                        document.getElementById('state').value = data.uf;
                        document.getElementById('complement').value = data.complemento || '';
                        document.getElementById('number').focus();
                    }
                } catch(e) { console.error(e); }
            }
        }

        // Preview de Imagens
        function previewImages(input) {
            const container = document.getElementById('preview-container');
            container.innerHTML = '';
            if (input.files) {
                Array.from(input.files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = "relative w-full aspect-square rounded-lg overflow-hidden border border-gray-700";
                        div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                        container.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                });
            }
        }
    </script>
    @endpush
</x-app-layout>