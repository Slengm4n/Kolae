// Tema claro/escuro. A escolha fica em localStorage.theme: 'light', 'dark' ou nada (automático:
// segue o sistema). O partials/theme_script.php aplica a mesma regra antes de desenhar a página.
const systemDark = window.matchMedia('(prefers-color-scheme: dark)');

function savedTheme() {
    try {
        return localStorage.getItem('theme');
    } catch {
        return null;
    }
}

// Troca o tema com um esmaecimento da tela inteira (View Transitions); sem suporte ou com
// "reduzir movimento", troca na hora
function withFade(change) {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!document.startViewTransition || reduce) {
        change();
        return;
    }
    const html = document.documentElement;
    html.classList.add('kolae-theme-switching');
    const t = document.startViewTransition(change);
    t.finished.finally(() => html.classList.remove('kolae-theme-switching'));
}

// Aplica 'light', 'dark' ou 'system'; save = false só redesenha (ao carregar a página)
function applyTheme(choice, save = true) {
    const html = document.documentElement;
    const dark = choice === 'dark' || (choice === 'system' && systemDark.matches);
    if (save && html.classList.contains('dark') !== dark) {
        withFade(() => html.classList.toggle('dark', dark));
    } else {
        html.classList.toggle('dark', dark);
    }
    if (!save) return;
    try {
        if (choice === 'system') localStorage.removeItem('theme');
        else localStorage.setItem('theme', choice);
    } catch {
        // Sem localStorage: vale só nesta página
    }
}

export function initTheme() {
    const current = savedTheme() || 'system';
    applyTheme(current, false);

    // No automático, acompanha a troca do sistema com a página aberta
    systemDark.addEventListener('change', () => {
        if (!savedTheme()) applyTheme('system', false);
    });

    // Botão de alternar (cabeçalhos e telas de entrada)
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            applyTheme(document.documentElement.classList.contains('dark') ? 'light' : 'dark');
            syncChoice();
        });
    }

    // Escolha Claro / Escuro / Automático (Configurações > Preferências)
    const choices = document.querySelectorAll('input[name="theme-choice"]');
    function syncChoice() {
        const now = savedTheme() || 'system';
        choices.forEach((input) => (input.checked = input.value === now));
    }
    choices.forEach((input) => input.addEventListener('change', () => applyTheme(input.value)));
    syncChoice();
}
