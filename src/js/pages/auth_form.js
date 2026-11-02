// Telas de entrada (login, cadastro, esqueci a senha e nova senha).
// Tudo é aprimoramento: sem JS, os formulários funcionam igual (o servidor valida tudo).
export function initAuthForms() {
    // Mostrar/esconder senha
    document.querySelectorAll('[data-password-toggle]').forEach((btn) => {
        const input = document.getElementById(btn.dataset.passwordToggle);
        if (!input) return;
        btn.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', String(show));
            btn.setAttribute('aria-label', show ? btn.dataset.labelHide : btn.dataset.labelShow);
            btn.querySelector('i').className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
            input.focus();
        });
    });

    // Aviso de Caps Lock nos campos de senha
    document.querySelectorAll('[data-caps-warning]').forEach((warning) => {
        const input = document.getElementById(warning.dataset.capsWarning);
        if (!input) return;
        const check = (e) => {
            if (typeof e.getModifierState !== 'function') return;
            warning.classList.toggle('hidden', !e.getModifierState('CapsLock'));
        };
        input.addEventListener('keydown', check);
        input.addEventListener('keyup', check);
        input.addEventListener('blur', () => warning.classList.add('hidden'));
    });

    // Senha nova: o texto de ajuda fica verde ao chegar no mínimo de caracteres
    document.querySelectorAll('input[data-min-hint]').forEach((input) => {
        const hint = document.getElementById(input.dataset.minHint);
        const min = Number(input.getAttribute('minlength')) || 8;
        if (!hint) return;
        input.addEventListener('input', () => {
            hint.classList.toggle('is-ok', input.value.length >= min);
        });
    });

    // Confirmação de senha: avisa enquanto digita e impede o envio se não coincidir
    document.querySelectorAll('input[data-match]').forEach((confirm) => {
        const original = document.getElementById(confirm.dataset.match);
        const hint = document.getElementById(confirm.id + '-match');
        if (!original || !hint) return;
        const update = () => {
            const filled = confirm.value.length > 0;
            const same = confirm.value === original.value;
            hint.classList.toggle('hidden', !filled);
            hint.classList.toggle('is-ok', filled && same);
            hint.classList.toggle('is-bad', filled && !same);
            hint.querySelector('span').textContent = same ? hint.dataset.ok : hint.dataset.bad;
            hint.querySelector('i').className = same ? 'fas fa-circle-check' : 'fas fa-circle-xmark';
            confirm.setAttribute('aria-invalid', String(filled && !same));
            confirm.setCustomValidity(same ? '' : hint.dataset.bad);
        };
        confirm.addEventListener('input', update);
        original.addEventListener('input', () => confirm.value && update());
    });

    // Data de nascimento em três listas: monta o campo escondido (AAAA-MM-DD) e avisa
    // na hora se a data não existe (31 de fevereiro) ou se a pessoa ainda não tem a idade mínima
    document.querySelectorAll('[data-birthdate]').forEach((box) => {
        const hidden = box.querySelector('#birthdate');
        const [day, month, year] = ['day', 'month', 'year'].map((p) => box.querySelector('#birth_' + p));
        const hint = box.querySelector('#birthdate-hint');
        const hintText = hint.querySelector('span');
        const hintIcon = hint.querySelector('i');
        const original = hintText.textContent;
        const minAge = Number(box.dataset.minAge) || 18;
        const update = () => {
            const [d, m, y] = [day, month, year].map((s) => Number(s.value));
            let error = '';
            if (d && m && y) {
                const date = new Date(y, m - 1, d);
                const today = new Date();
                const adult = new Date(today.getFullYear() - minAge, today.getMonth(), today.getDate());
                if (date.getDate() !== d) error = box.dataset.msgInvalid;
                else if (date > adult) error = box.dataset.msgUnderage;
                hidden.value = error ? '' : `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            } else {
                hidden.value = '';
            }
            const target = error === box.dataset.msgInvalid ? day : year;
            [day, month, year].forEach((s) => {
                s.setCustomValidity(s === target && error ? error : '');
                s.setAttribute('aria-invalid', String(s === target && !!error));
            });
            hintText.textContent = error || original;
            hint.classList.toggle('is-bad', !!error);
            hint.classList.toggle('is-ok', !error && !!hidden.value);
            hintIcon.className = error ? 'fas fa-circle-xmark' : hidden.value ? 'fas fa-circle-check' : 'fas fa-circle-info';
        };
        [day, month, year].forEach((s) => s.addEventListener('change', update));
        box.addEventListener('kolae:restored', update);
    });

    // Depois de um erro (senha errada, e-mail já cadastrado), o servidor volta com o
    // formulário vazio: devolve o que foi digitado nos campos [data-remember] (nunca senha),
    // guardado só nesta aba
    document.querySelectorAll('form[data-auth-form]').forEach((form) => {
        const fields = [...form.querySelectorAll('[data-remember]')];
        if (!fields.length) return;
        const key = 'kolae-auth-' + form.id;
        try {
            const saved = JSON.parse(sessionStorage.getItem(key) || '{}');
            if (new URLSearchParams(location.search).has('error')) {
                fields.forEach((f) => {
                    if (saved[f.name] && !f.value) f.value = saved[f.name];
                });
                form.querySelectorAll('[data-birthdate]').forEach((b) => b.dispatchEvent(new Event('kolae:restored')));
                const empty = form.querySelector('input[type="password"]');
                if (fields.some((f) => f.value) && empty) empty.focus();
            }
            form.addEventListener('submit', () => {
                sessionStorage.setItem(key, JSON.stringify(Object.fromEntries(fields.map((f) => [f.name, f.value]))));
            });
        } catch {
            // Sem sessionStorage (navegação privada restrita): segue sem lembrar
        }
    });

    // Envio: trava o botão (evita clique duplo) e mostra o texto de espera
    document.querySelectorAll('form[data-auth-form]').forEach((form) => {
        const btn = form.querySelector('button[type="submit"]');
        if (!btn) return;
        const original = btn.innerHTML;
        form.addEventListener('submit', () => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>';
            btn.append(btn.dataset.loadingText || '');
        });
        // Ao voltar pelo histórico, a página pode vir do cache com o botão travado
        window.addEventListener('pageshow', (e) => {
            if (!e.persisted) return;
            btn.disabled = false;
            btn.innerHTML = original;
        });
    });
}
