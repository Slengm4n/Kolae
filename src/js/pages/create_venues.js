// src/js/pages/create_venue.js

export function initCreateVenue() {
    document.addEventListener('DOMContentLoaded', function () {
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

        // Função de Notificação (Toast)
        function showToast(message) {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toast-message');
            toastMsg.textContent = message;
            toast.classList.remove('translate-x-full', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
            }, 3000);
        }

        // Destacar campos inválidos
        function highlightError(element) {
            element.classList.add('border-red-500', 'shake');
            element.classList.remove('border-gray-700');
            element.addEventListener(
                'input',
                function () {
                    this.classList.remove('border-red-500', 'shake');
                    this.classList.add('border-gray-700');
                },
                {
                    once: true,
                },
            );
        }

        // --- FUNÇÃO CENTRAL DE VALIDAÇÃO ---
        function validateStep(stepIndex) {
            let isValid = true;

            // Validação Etapa 1 (Index 0): Piso
            if (stepIndex === 0) {
                const selected = document.querySelector('#step-1 .option-card.border-cyan-400');
                if (!selected) {
                    showToast('Por favor, selecione um tipo de piso.');
                    isValid = false;
                }
            }

            // Validação Etapa 4 (Index 3): Nome e Preço
            if (stepIndex === 3) {
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

            // Validação Etapa 5 (Index 4): Endereço
            if (stepIndex === 4) {
                const requiredFields = ['cep', 'street', 'number', 'neighborhood', 'city', 'state'];
                let firstInvalid = null;

                requiredFields.forEach((id) => {
                    const el = document.getElementById(id);
                    if (!el.value.trim()) {
                        highlightError(el);
                        if (!firstInvalid) firstInvalid = el;
                        isValid = false;
                    }
                });

                if (!isValid) {
                    showToast('Preencha o endereço completo.');
                    if (firstInvalid) firstInvalid.focus();
                }
            }

            return isValid;
        }

        // Evento de clique no botão Avançar/Concluir
        nextBtn.addEventListener('click', () => {
            // Só avança se a validação passar
            if (!validateStep(currentStep)) {
                return;
            }

            if (currentStep < totalSteps - 1) {
                currentStep++;
                updateUI();
            } else {
                // Captura final dos dados para submissão
                formData['name'] = document.getElementById('name').value;
                formData['average_price_per_hour'] = document.getElementById('average_price_per_hour').value;
                formData['cep'] = document.getElementById('cep').value;
                formData['street'] = document.getElementById('street').value;
                formData['number'] = document.getElementById('number').value;
                formData['neighborhood'] = document.getElementById('neighborhood').value;
                formData['complement'] = document.getElementById('complement').value;
                formData['city'] = document.getElementById('city').value;
                formData['state'] = document.getElementById('state').value;

                // Cria inputs hidden dinamicamente
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

        // Lógica dos Cards (Piso)
        document.querySelectorAll('.option-card').forEach((card) => {
            card.addEventListener('click', () => {
                const group = card.parentElement;
                group
                    .querySelectorAll('.option-card')
                    .forEach((c) => c.classList.remove('border-cyan-400', 'bg-cyan-500/10'));
                card.classList.add('border-cyan-400', 'bg-cyan-500/10');
                formData[group.dataset.inputName] = card.dataset.value;
            });
        });

        // Contadores
        document.querySelectorAll('.counter-input').forEach((counter) => {
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

        // Checkboxes
        document.querySelectorAll('.checkbox-card').forEach((card) => {
            const checkbox = card.querySelector('input[type="checkbox"]');
            card.addEventListener('click', () => {
                checkbox.checked = !checkbox.checked;
                card.classList.toggle('border-cyan-400', checkbox.checked);
                card.classList.toggle('bg-cyan-500/10', checkbox.checked);
                card.querySelector('i').classList.toggle('text-cyan-400', checkbox.checked);
                formData[checkbox.name] = checkbox.checked ? 1 : 0;

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

        // CEP (Com Autocomplete de Complemento)
        document.getElementById('cep').addEventListener('blur', async function () {
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
                        document.getElementById('complement').value = data.complemento || '';

                        ['street', 'neighborhood', 'city', 'state'].forEach((id) => {
                            document.getElementById(id).classList.remove('border-red-500', 'shake');
                            document.getElementById(id).classList.add('border-gray-700');
                        });
                        document.getElementById('number').focus();
                    }
                } catch (error) {
                    console.error('Erro CEP:', error);
                }
            }
        });

        // Upload
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('images');
        const previewContainer = document.getElementById('preview-container');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eName) =>
            dropArea.addEventListener(eName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            }),
        );
        ['dragenter', 'dragover'].forEach((eName) =>
            dropArea.addEventListener(eName, () => dropArea.classList.add('border-cyan-400', 'bg-gray-800/50')),
        );
        ['dragleave', 'drop'].forEach((eName) =>
            dropArea.addEventListener(eName, () => dropArea.classList.remove('border-cyan-400', 'bg-gray-800/50')),
        );

        const handleFiles = (files) => {
            previewContainer.innerHTML = '';
            [...files].forEach((file) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const preview = document.createElement('div');
                    preview.className = 'relative w-full aspect-square rounded-lg overflow-hidden';
                    preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover"><button type="button" class="remove-btn absolute top-1 right-1 bg-black/50 text-white rounded-full w-6 h-6 flex items-center justify-center">&times;</button>`;
                    previewContainer.appendChild(preview);
                };
                reader.readAsDataURL(file);
            });
        };
        dropArea.addEventListener('drop', (e) => handleFiles(e.dataTransfer.files));
        fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

        updateUI();
    });
}
