export function initLanguageSwitcher() {
    document.addEventListener('DOMContentLoaded', () => {
        const langBtn = document.getElementById('lang-btn');
        const langModal = document.getElementById('lang-modal');
        const langBox = document.getElementById('lang-box');
        const closeLangBtn = document.getElementById('close-lang-btn');

        // Função para ABRIR o modal suavemente
        function openModal() {
            // 1. Mostra o container do fundo
            langModal.classList.remove('invisible', 'opacity-0');
            langModal.classList.add('visible', 'opacity-100');

            // 2. Faz a caixa dar "zoom in" e aparecer
            // Um pequeno timeout garante que a transição ocorra após o container ficar visível
            setTimeout(() => {
                langBox.classList.remove('scale-95', 'opacity-0');
                langBox.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        // Função para FECHAR o modal suavemente
        function closeModal() {
            // 1. Faz a caixa dar "zoom out" e desaparecer
            langBox.classList.remove('scale-100', 'opacity-100');
            langBox.classList.add('scale-95', 'opacity-0');

            // 2. Esconde o container do fundo após a animação da caixa terminar (300ms)
            setTimeout(() => {
                langModal.classList.remove('visible', 'opacity-100');
                langModal.classList.add('invisible', 'opacity-0');
            }, 300); // Esse tempo deve bater com o 'duration-300' do CSS
        }

        if (langBtn && langModal && langBox) {
            // Evento do botão do globo
            langBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Evita que o clique feche o modal imediatamente
                openModal();
            });

            // Evento do botão "X"
            if (closeLangBtn) {
                closeLangBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    closeModal();
                });
            }

            // Fechar clicando fora (no fundo escuro)
            langModal.addEventListener('click', (e) => {
                // Verifica se o clique foi no fundo escuro e não dentro da caixa
                if (!langBox.contains(e.target)) {
                    closeModal();
                }
            });
            // Fechar com a tecla ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && langModal.classList.contains('visible')) {
                    closeModal();
                }
            });
        }
    });
}
