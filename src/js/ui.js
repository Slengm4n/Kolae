// Avisos (toast) e confirmação no visual do Kolae, no lugar das caixas do navegador (D57).
// Arquivo à parte (assets/js/ui.js) para servir também às páginas do admin, que não usam o bundle.js.
// Uso: Kolae.toast('Salvo!', 'success'); if (await Kolae.confirm('Apagar?', { danger: true })) ...
// Formulários com data-confirm="texto" pedem confirmação antes de enviar (data-confirm-danger para vermelho;
// data-confirm-ok e data-confirm-cancel trocam o texto dos botões).

const data = (() => {
    try {
        return JSON.parse(document.getElementById('kolae-ui')?.textContent || '{}');
    } catch {
        return {};
    }
})();
const labels = { close: 'Fechar', cancel: 'Cancelar', confirm: 'Confirmar', title: 'Tem certeza?', ...(data.labels || {}) };
const ICONS = { success: 'fa-circle-check', error: 'fa-circle-exclamation', info: 'fa-circle-info' };

function stack() {
    let el = document.getElementById('kolae-toasts');
    if (!el) {
        el = document.createElement('div');
        el.id = 'kolae-toasts';
        el.className = 'kolae-toast-stack';
        document.body.appendChild(el);
    }
    return el;
}

export function toast(message, type = 'info', { duration = 5500 } = {}) {
    if (!ICONS[type]) type = 'info';
    const el = document.createElement('div');
    el.className = `kolae-toast kolae-toast--${type}`;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.innerHTML = `
        <span class="kolae-toast-icon" aria-hidden="true"><i class="fas ${ICONS[type]}"></i></span>
        <p class="kolae-toast-text"></p>
        <button type="button" class="kolae-toast-close" aria-label="${labels.close}"><i class="fas fa-xmark" aria-hidden="true"></i></button>
        <span class="kolae-toast-bar" style="animation-duration:${duration}ms"></span>`;
    el.querySelector('.kolae-toast-text').textContent = message;
    const close = () => {
        if (el.classList.contains('is-leaving')) return;
        el.classList.add('is-leaving');
        el.addEventListener('animationend', () => el.remove(), { once: true });
        setTimeout(() => el.remove(), 400); // sem animação ("reduzir movimento")
    };
    el.querySelector('.kolae-toast-close').addEventListener('click', close);
    // A barra encolhe em CSS (pausa com o mouse em cima); quando acaba, o aviso sai
    el.querySelector('.kolae-toast-bar').addEventListener('animationend', close);
    stack().appendChild(el);
    return close;
}

export function confirm(message, { title = labels.title, confirmText = labels.confirm, cancelText = labels.cancel, danger = false } = {}) {
    return new Promise((resolve) => {
        const previous = document.activeElement;
        const root = document.createElement('div');
        root.className = 'kolae-modal';
        root.innerHTML = `
            <div class="kolae-modal-backdrop" data-close></div>
            <div class="kolae-modal-panel" role="alertdialog" aria-modal="true" aria-labelledby="kolae-modal-title" aria-describedby="kolae-modal-text">
                <span class="kolae-modal-icon ${danger ? 'is-danger' : ''}" aria-hidden="true"><i class="fas ${danger ? 'fa-triangle-exclamation' : 'fa-circle-question'}"></i></span>
                <h2 id="kolae-modal-title" class="kolae-modal-title"></h2>
                <p id="kolae-modal-text" class="kolae-modal-text"></p>
                <div class="kolae-modal-actions">
                    <button type="button" class="kolae-modal-btn" data-close></button>
                    <button type="button" class="kolae-modal-btn ${danger ? 'is-danger' : 'is-primary'}" data-ok></button>
                </div>
            </div>`;
        root.querySelector('.kolae-modal-title').textContent = title;
        root.querySelector('.kolae-modal-text').textContent = message;
        root.querySelector('[data-ok]').textContent = confirmText;
        root.querySelector('button[data-close]').textContent = cancelText;
        const buttons = [...root.querySelectorAll('button')];
        const done = (answer) => {
            document.removeEventListener('keydown', onKey, true);
            root.classList.add('is-leaving');
            setTimeout(() => root.remove(), 200);
            if (previous && previous.focus) previous.focus();
            resolve(answer);
        };
        const onKey = (e) => {
            if (e.key === 'Escape') { e.preventDefault(); done(false); }
            if (e.key === 'Tab') { // mantém o foco dentro da janela
                const i = buttons.indexOf(document.activeElement);
                e.preventDefault();
                buttons[(i + (e.shiftKey ? -1 : 1) + buttons.length) % buttons.length].focus();
            }
        };
        root.querySelectorAll('[data-close]').forEach((b) => b.addEventListener('click', () => done(false)));
        root.querySelector('[data-ok]').addEventListener('click', () => done(true));
        document.addEventListener('keydown', onKey, true);
        document.body.appendChild(root);
        // Em ação perigosa, o foco começa no "Cancelar"
        (danger ? buttons[0] : buttons[1]).focus();
    });
}

// Formulários com data-confirm
document.addEventListener('submit', async (e) => {
    const form = e.target;
    const source = e.submitter?.dataset.confirm ? e.submitter : form;
    if (!source.dataset || !source.dataset.confirm || form.dataset.confirmed === '1') return;
    e.preventDefault();
    const ok = await confirm(source.dataset.confirm, {
        danger: 'confirmDanger' in source.dataset,
        confirmText: source.dataset.confirmOk || labels.confirm,
        cancelText: source.dataset.confirmCancel || labels.cancel,
    });
    if (!ok) return;
    form.dataset.confirmed = '1';
    if (e.submitter && e.submitter.name) {
        // Mantém o botão clicado no envio (ex: name="action" value="cancel")
        const keep = document.createElement('input');
        keep.type = 'hidden';
        keep.name = e.submitter.name;
        keep.value = e.submitter.value;
        form.appendChild(keep);
    }
    form.requestSubmit ? form.requestSubmit() : form.submit();
}, true);

window.Kolae = Object.assign(window.Kolae || {}, { toast, confirm });

// Avisos vindos do servidor (?status= / ?error=), em sequência, e a URL limpa
(data.toasts || []).forEach((t, i) => setTimeout(() => toast(t.message, t.type), 150 + i * 150));
if ((data.clean || []).length) {
    const url = new URL(location.href);
    data.clean.forEach((k) => url.searchParams.delete(k));
    history.replaceState(history.state, '', url.pathname + url.search + url.hash);
}
