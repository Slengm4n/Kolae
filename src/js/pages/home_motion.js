// Movimento da home: cabeçalho que ganha fundo ao rolar, seções que surgem ao rolar
// e a prévia do app com o selo trocando de status. Só age se os elementos existirem.
// Sem JS ou com "reduzir movimento", a página fica parada e completa.

const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function initStickyHeader() {
    const header = document.querySelector('[data-site-header]');
    if (!header) return;

    const solid = ['bg-neutral-950/85', 'backdrop-blur-md', 'shadow-lg', 'shadow-black/20'];
    const update = () => {
        const scrolled = window.scrollY > 24;
        solid.forEach((c) => header.classList.toggle(c, scrolled));
    };
    update();
    window.addEventListener('scroll', update, { passive: true });
}

function initReveal() {
    const items = document.querySelectorAll('.reveal');
    if (!items.length || !('IntersectionObserver' in window) || prefersReducedMotion()) return;

    // Só agora o CSS passa a esconder os .reveal (ver src/input.css)
    document.documentElement.classList.add('js-motion');

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.12 }
    );
    items.forEach((el) => observer.observe(el));
}

function initLiveStatus() {
    const badge = document.querySelector('[data-live-status]');
    if (!badge || prefersReducedMotion()) return;

    // Estados e textos (já traduzidos) vêm do PHP
    const states = [
        { cls: 'status-badge--available', label: badge.dataset.labelAvailable },
        { cls: 'status-badge--in-use', label: badge.dataset.labelInUse },
    ];
    let current = 0;

    setInterval(() => {
        if (document.hidden) return;
        current = (current + 1) % states.length;
        badge.classList.remove(...states.map((s) => s.cls));
        badge.classList.add(states[current].cls, 'is-swapping');
        badge.textContent = states[current].label;
        setTimeout(() => badge.classList.remove('is-swapping'), 450);
    }, 3200);
}

export function initHomeMotion() {
    initStickyHeader();
    initReveal();
    initLiveStatus();
}
