// 1. Importa seu CSS principal (Tailwind)
import '../input.css';

// 2. Importa os módulos JS
import { initTheme } from './modules/toggle_theme.js';
import { initNavbar } from './modules/dropdown.js';
import { initHomeCarousel } from './pages/home.js';
import { initLogin } from './pages/login.js';
import { initRegister } from './pages/register.js';
import { initCnpjMask } from './pages/cnpj.js';
import { initforgotPassword } from './pages/forgot_password.js';
// 3. Executa quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    initTheme(); // Roda em todas as páginas
    initNavbar(); // Roda em todas as páginas (se tiver navbar)
    initHomeCarousel(); // Só vai ativar se estiver na Home
    initforgotPassword(); // Só vai ativar se estiver na página de esqueci minha senha
    initLogin(); // Só vai ativar se estiver na página de login
    initRegister(); // Só vai ativar se estiver na página de registro
    initCnpjMask(); // Só vai ativar se estiver na página com campo CNPJ
});
