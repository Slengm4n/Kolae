import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {

    // ── Modal de idioma ──
    // ── Dropdown de idioma ──
const langBtn      = document.getElementById('lang-btn');
const langDropdown = document.getElementById('lang-dropdown');
const langChevron  = document.getElementById('lang-chevron');

function toggleLang(e) {
    e.stopPropagation();
    const isOpen = !langDropdown.classList.contains('invisible');
    if (isOpen) {
        closeLang();
    } else {
        langDropdown.classList.remove('opacity-0', 'invisible', '-translate-y-2');
        langChevron.classList.add('rotate-180');
    }
}

function closeLang() {
    langDropdown.classList.add('opacity-0', 'invisible', '-translate-y-2');
    langChevron.classList.remove('rotate-180');
}

langBtn?.addEventListener('click', toggleLang);
document.addEventListener('click', closeLang);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLang(); });


    // ── Dropdown do usuário ──
    const userBtn      = document.getElementById('user-menu-button');
    const userDropdown = document.getElementById('profile-dropdown');

    userBtn?.addEventListener('click', e => {
        e.stopPropagation();
        const isOpen = !userDropdown.classList.contains('invisible');
        if (isOpen) {
            userDropdown.classList.add('opacity-0', 'invisible', '-translate-y-2');
        } else {
            userDropdown.classList.remove('opacity-0', 'invisible', '-translate-y-2');
        }
    });

    document.addEventListener('click', () => {
        userDropdown?.classList.add('opacity-0', 'invisible', '-translate-y-2');
    });

});

    const themeToggleBtn = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement;

    // 1. Checa se o usuário já escolheu um tema antes ou qual o tema do sistema (Windows/Mac)
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        htmlElement.classList.add('dark');
    } else {
        htmlElement.classList.remove('dark');
    }

    // 2. Lógica do clique no botão
    themeToggleBtn?.addEventListener('click', () => {
        // Alterna a classe 'dark' na tag <html>
        htmlElement.classList.toggle('dark');

        // Salva a escolha no navegador para a próxima vez que ele entrar no site
        if (htmlElement.classList.contains('dark')) {
            localStorage.setItem('theme', 'dark');
        } else {
            localStorage.setItem('theme', 'light');
        }
    });