export function initRegister() {
    const registerForm = document.getElementById('register-form');
    const registerButton = document.getElementById('register-button');

    if (registerForm && registerButton) {
        registerForm.addEventListener('submit', function () {
            registerButton.disabled = true;
            registerButton.classList.add('opacity-75', 'cursor-not-allowed', 'scale-100');

            registerButton.innerHTML = `
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Criando conta...
            `;
        });
    }
}
