export function initLanguageSwitcher() {
    const langBtn = document.getElementById('lang-btn');
    const langModal = document.getElementById('lang-modal');
    const langBox = document.getElementById('lang-box');
    const langButtons = document.querySelectorAll('[data-lang]');

    // 1. Abrir Modal
    if (langBtn && langModal) {
        langBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            langModal.classList.remove('opacity-0', 'invisible');
            langBox.classList.remove('opacity-0', 'scale-90');
        });
    }

    // 2. Fechar ao clicar fora
    if (langModal && langBox) {
        langModal.addEventListener('click', (e) => {
            if (!langBox.contains(e.target)) {
                closeModal();
            }
        });
    }

    function closeModal() {
        langModal.classList.add('opacity-0', 'invisible');
        langBox.classList.add('opacity-0', 'scale-90');
    }

    // 3. Lógica de Redirecionamento (Mantendo parâmetros de URL)
    function generateLangLink(langCode) {
        let currentUri = window.location.pathname + window.location.search;
        // Remove lang atual se existir
        currentUri = currentUri.replace(/(\?|&)?lang=[^&]*/g, '');
        // Define separador
        const separator = currentUri.includes('?') ? '&' : '?';
        // Retorna nova URL
        return currentUri + separator + 'lang=' + langCode;
    }

    // 4. Evento nos botões de bandeira
    langButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const langCode = button.getAttribute('data-lang');
            if (langCode) {
                window.location.href = generateLangLink(langCode);
            }
        });
    });
}
