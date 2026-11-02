<?php
/**
 * Guia passo a passo (D50): escurece a página, destaca um elemento por vez e mostra um balão
 * com o texto e os botões Pular / Voltar / Próximo. Sem biblioteca.
 * Abre sozinho na primeira visita (lembrado no navegador) e por qualquer botão
 * com data-tour-start="<id>". Esc fecha; setas do teclado mudam de passo.
 *
 * Variáveis: $lang, $tourId (ex: 'hours'), $tourSteps (lista de [seletor, título, texto];
 *            se o elemento estiver escondido, usa o seletor alternativo depois de '||'),
 *            $tourAuto (true = abre sozinho se a pessoa nunca viu).
 */
$tourConfig = [
    'id' => $tourId,
    'auto' => (bool)($tourAuto ?? true),
    'steps' => array_map(fn($s) => ['target' => $s[0], 'title' => $s[1], 'text' => $s[2]], $tourSteps),
    'labels' => [
        'next' => $lang['tour_next'],
        'back' => $lang['tour_back'],
        'skip' => $lang['tour_skip'],
        'done' => $lang['tour_done'],
        'progress' => $lang['tour_progress'],
    ],
];
?>
<div id="tour" class="hidden" aria-hidden="true">
    <div id="tour-backdrop" class="fixed inset-0 z-[70]"></div>
    <div id="tour-spot" class="fixed z-[71] rounded-xl pointer-events-none"></div>
    <div id="tour-card" class="fixed z-[72] w-[min(22rem,calc(100vw-2rem))] bg-surface-elevated text-content-primary border border-content-secondary/15 rounded-2xl shadow-2xl p-5" role="dialog" aria-modal="true" aria-labelledby="tour-title" aria-describedby="tour-text" tabindex="-1">
        <p id="tour-progress" class="text-xs font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-400"></p>
        <h2 id="tour-title" class="mt-1 text-lg font-bold"></h2>
        <p id="tour-text" class="mt-1.5 text-sm text-content-secondary leading-relaxed"></p>
        <div class="mt-4 flex items-center gap-2">
            <button type="button" id="tour-skip" class="text-sm font-semibold text-content-secondary hover:text-content-primary px-2 py-2 -ml-2"></button>
            <span class="flex-1"></span>
            <button type="button" id="tour-back" class="text-sm font-semibold px-3 py-2 rounded-lg border border-content-secondary/20 hover:border-content-secondary/40"></button>
            <button type="button" id="tour-next" class="text-sm font-semibold px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-700 text-white"></button>
        </div>
    </div>
</div>
<style>
    #tour-spot { box-shadow: 0 0 0 9999px rgba(0, 0, 0, .6); outline: 3px solid #0084FF; outline-offset: 2px; transition: all .25s ease; }
    #tour-card { transition: top .25s ease, left .25s ease; }
    @media (prefers-reduced-motion: reduce) { #tour-spot, #tour-card { transition: none; } }
</style>
<script>
    (function () {
        const cfg = <?php echo json_encode($tourConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
        const storageKey = 'kolae_tour_' + cfg.id;
        const root = document.getElementById('tour');
        const spot = document.getElementById('tour-spot');
        const card = document.getElementById('tour-card');
        const els = {
            progress: document.getElementById('tour-progress'),
            title: document.getElementById('tour-title'),
            text: document.getElementById('tour-text'),
            skip: document.getElementById('tour-skip'),
            back: document.getElementById('tour-back'),
            next: document.getElementById('tour-next'),
        };
        els.skip.textContent = cfg.labels.skip;
        els.back.textContent = cfg.labels.back;
        let index = 0;
        let opener = null;

        const visible = el => el && el.getClientRects().length > 0;
        // "#a || #b": usa o primeiro que estiver aparecendo
        const targetOf = step => step.target.split('||').map(s => document.querySelector(s.trim())).find(visible) || null;

        const place = () => {
            const el = targetOf(cfg.steps[index]);
            const pad = 6;
            const vw = window.innerWidth, vh = window.innerHeight;
            const cardH = card.offsetHeight, cardW = card.offsetWidth;
            if (!el) {
                spot.style.cssText = 'top:50%;left:50%;width:0;height:0;';
                card.style.top = Math.max(16, (vh - cardH) / 2) + 'px';
                card.style.left = Math.max(16, (vw - cardW) / 2) + 'px';
                return;
            }
            const r = el.getBoundingClientRect();
            spot.style.top = (r.top - pad) + 'px';
            spot.style.left = (r.left - pad) + 'px';
            spot.style.width = (r.width + pad * 2) + 'px';
            spot.style.height = (r.height + pad * 2) + 'px';
            // Balão embaixo do elemento; se não couber, em cima; no limite, preso à tela
            let top = r.bottom + pad + 12;
            if (top + cardH > vh - 16) top = r.top - pad - 12 - cardH;
            top = Math.min(Math.max(16, top), vh - cardH - 16);
            const left = Math.min(Math.max(16, r.left), vw - cardW - 16);
            card.style.top = top + 'px';
            card.style.left = left + 'px';
        };

        const show = i => {
            index = i;
            const step = cfg.steps[index];
            els.progress.textContent = cfg.labels.progress.replace('{n}', index + 1).replace('{total}', cfg.steps.length);
            els.title.textContent = step.title;
            els.text.textContent = step.text;
            els.back.classList.toggle('invisible', index === 0);
            els.next.textContent = index === cfg.steps.length - 1 ? cfg.labels.done : cfg.labels.next;
            const el = targetOf(step);
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (el) el.scrollIntoView({ block: 'center', behavior: reduce ? 'auto' : 'smooth' });
            place();
            // Reposiciona depois da rolagem
            setTimeout(place, reduce ? 0 : 350);
            card.focus();
        };

        const remember = () => { try { localStorage.setItem(storageKey, 'seen'); } catch (e) {} };
        const start = from => {
            opener = from || null;
            root.classList.remove('hidden');
            root.setAttribute('aria-hidden', 'false');
            show(0);
        };
        const close = () => {
            root.classList.add('hidden');
            root.setAttribute('aria-hidden', 'true');
            remember();
            if (opener) opener.focus();
        };

        els.next.addEventListener('click', () => index < cfg.steps.length - 1 ? show(index + 1) : close());
        els.back.addEventListener('click', () => index > 0 && show(index - 1));
        els.skip.addEventListener('click', close);
        document.getElementById('tour-backdrop').addEventListener('click', close);
        document.addEventListener('keydown', e => {
            if (root.classList.contains('hidden')) return;
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowRight') els.next.click();
            if (e.key === 'ArrowLeft') els.back.click();
        });
        window.addEventListener('resize', () => { if (!root.classList.contains('hidden')) place(); });
        window.addEventListener('scroll', () => { if (!root.classList.contains('hidden')) place(); }, { passive: true });
        document.querySelectorAll('[data-tour-start="' + cfg.id + '"]').forEach(btn => btn.addEventListener('click', () => start(btn)));

        let seen = false;
        try { seen = localStorage.getItem(storageKey) === 'seen'; } catch (e) {}
        if (cfg.auto && !seen) window.addEventListener('load', () => setTimeout(() => start(null), 400));
    })();
</script>
