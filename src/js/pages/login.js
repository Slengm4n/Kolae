export function initLogin() {
    const loginForm = document.getElementById('login-form');
    const loginButton = document.getElementById('login-button');

    if (loginForm && loginButton) {
        loginForm.addEventListener('submit', function () {
            // Evita duplo clique e mostra loading
            loginButton.disabled = true;
            loginButton.classList.add('opacity-75', 'cursor-not-allowed', 'scale-100');

            // Salva o texto original caso precise restaurar (opcional)
            const originalText = loginButton.innerText;

            loginButton.innerHTML = `
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Aguarde...
            `;
        });
    }
}
