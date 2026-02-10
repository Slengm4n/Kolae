function applyTheme(themeName) {
    const html = document.documentElement;
    html.classList.add('transition-colors', 'duration-500');

    if (themeName === 'dark') {
        html.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        html.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
}

// 2. Função principal exportada
export function initTheme() {
    const themeToggle = document.getElementById('theme-toggle');

    // Descobre qual tema usar ao carregar
    const savedTheme = localStorage.getItem('theme');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    // Define o tema inicial (se tiver salvo usa ele, se não usa do sistema)
    let currentTheme = savedTheme ? savedTheme : systemPrefersDark ? 'dark' : 'light';

    // Aplica o tema inicial
    applyTheme(currentTheme);

    // Se o botão existir, adiciona o evento de clique
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            // Verifica como está AGORA para poder inverter
            const isDarkNow = document.documentElement.classList.contains('dark');
            const newTheme = isDarkNow ? 'light' : 'dark';

            // Aplica o novo tema
            applyTheme(newTheme);
        });
    }
}
