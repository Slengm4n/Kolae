<?php
/**
 * Uma conversa do chat no painel do dono (D66). Balões (os do dono à direita), separados por dia;
 * responder (fetch, sem recarregar), bloquear/desbloquear e a consulta de mensagens novas a cada
 * 10 s enquanto a aba está visível. Quadra desativada: só leitura. Quem pergunta aparece com nome
 * e foto, nunca com o e-mail. Dados do ChatController::show.
 */
use App\Core\Csrf;

$lang = require BASE_PATH . '/includes/i18n.php';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$pageTitle = $person['name'] . ' · ' . $lang['chat_title'] . ' · Kolae';
$toastContext = 'chat';
$convId = (int)$conversation['id'];
$base = BASE_URL . '/dashboard/mensagens';
$blocked = !empty($conversation['blocked_at']);
$lastId = $messages ? (int)end($messages)['id'] : 0;

$dayLabel = function (string $ymd) use ($lang): string {
    if ($ymd === date('Y-m-d')) return $lang['chat_today'];
    if ($ymd === date('Y-m-d', strtotime('-1 day'))) return $lang['chat_yesterday'];
    return date('d/m/Y', strtotime($ymd));
};
$initial = mb_strtoupper(mb_substr(trim((string)$person['name']) ?: '?', 0, 1));
$btnGhost = 'inline-flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold border transition-colors';
?>
<!DOCTYPE html>
<html lang="<?php echo $e($_SESSION['idioma'] ?? 'pt-br'); ?>">

<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>

<body class="bg-surface-base text-content-primary font-sans antialiased">
    <?php $backUrl = '/dashboard';
    include BASE_PATH . '/app/views/partials/owner_header.php'; ?>

    <main class="container mx-auto px-4 py-4 md:py-8 max-w-4xl">
        <section id="chat" class="bg-surface-elevated rounded-2xl border border-content-secondary/10 shadow-xl flex flex-col overflow-hidden h-[calc(100dvh-7.5rem)] md:h-[calc(100dvh-9.5rem)] min-h-[26rem]"
            data-conversation="<?php echo $convId; ?>" data-last-id="<?php echo $lastId; ?>" data-blocked="<?php echo $blocked ? '1' : '0'; ?>" data-read-only="<?php echo $readOnly ? '1' : '0'; ?>"
            data-poll-url="<?php echo $base . '/' . $convId . '/novas'; ?>" data-today="<?php echo $e($lang['chat_today']); ?>" data-yesterday="<?php echo $e($lang['chat_yesterday']); ?>"
            data-err-generic="<?php echo $e($lang['chat_err_generic']); ?>" data-sending="<?php echo $e($lang['chat_sending']); ?>">

            <!-- Quem pergunta e a quadra -->
            <header class="flex items-center gap-3 px-3 sm:px-5 py-3 border-b border-content-secondary/10">
                <a href="<?php echo $base; ?>" id="chat-back" class="w-10 h-10 shrink-0 rounded-full flex items-center justify-center text-content-secondary hover:bg-surface-secondary hover:text-content-primary transition-colors" aria-label="<?php echo $e($lang['chat_title']); ?>">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                </a>
                <?php if (!empty($person['avatar_path'])): ?>
                    <img src="<?php echo BASE_URL . '/uploads/avatars/' . $person['id'] . '/' . rawurlencode($person['avatar_path']); ?>" alt="" class="w-10 h-10 rounded-full object-cover shrink-0">
                <?php else: ?>
                    <span class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-white font-bold flex items-center justify-center shrink-0" aria-hidden="true"><?php echo $e($initial); ?></span>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <h1 class="font-bold text-lg leading-tight truncate" id="chat-person"><?php echo $e($person['name']); ?></h1>
                    <p class="text-xs text-content-secondary truncate"><?php echo $e(strtr($lang['chat_about'], ['{venue}' => $conversation['venue_name']])); ?></p>
                </div>
                <?php if ($blocked): ?>
                    <form method="POST" action="<?php echo $base . '/' . $convId; ?>/desbloquear">
                        <?php echo Csrf::field(); ?>
                        <button type="submit" id="chat-unblock" class="<?php echo $btnGhost; ?> border-content-secondary/20 text-content-secondary hover:text-content-primary"><i class="fas fa-unlock" aria-hidden="true"></i><span class="hidden sm:inline"><?php echo $e($lang['chat_unblock']); ?></span></button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="<?php echo $base . '/' . $convId; ?>/bloquear" data-confirm="<?php echo $e($lang['chat_block_confirm']); ?>" data-confirm-danger data-confirm-ok="<?php echo $e($lang['chat_block']); ?>">
                        <?php echo Csrf::field(); ?>
                        <button type="submit" id="chat-block" class="<?php echo $btnGhost; ?> border-red-500/30 text-red-600 dark:text-red-400 hover:bg-red-500/10"><i class="fas fa-ban" aria-hidden="true"></i><span class="hidden sm:inline"><?php echo $e($lang['chat_block']); ?></span></button>
                    </form>
                <?php endif; ?>
            </header>

            <!-- Mensagens -->
            <div id="chat-messages" class="flex-1 overflow-y-auto px-3 sm:px-5 py-4 space-y-1.5" aria-live="polite">
                <?php $prevDay = null;
                foreach ($messages as $m):
                    $mine = (int)$m['sender_id'] === $ownerId;
                    $day = date('Y-m-d', strtotime($m['created_at']));
                    if ($day !== $prevDay): $prevDay = $day; ?>
                        <p class="chat-day text-center text-xs font-semibold text-content-secondary py-2" data-day="<?php echo $day; ?>"><span class="px-3 py-1 rounded-full bg-surface-base"><?php echo $e($dayLabel($day)); ?></span></p>
                    <?php endif; ?>
                    <div class="flex <?php echo $mine ? 'justify-end' : 'justify-start'; ?>" data-id="<?php echo (int)$m['id']; ?>" data-mine="<?php echo $mine ? '1' : '0'; ?>">
                        <div class="max-w-[80%] sm:max-w-[70%] px-3.5 py-2 rounded-2xl shadow-sm <?php echo $mine ? 'bg-brand-600 text-white rounded-br-md' : 'bg-surface-base text-content-primary rounded-bl-md border border-content-secondary/10'; ?>">
                            <p class="whitespace-pre-wrap break-words text-[0.9375rem] leading-snug"><?php echo $e($m['body']); ?></p>
                            <p class="text-[0.6875rem] mt-0.5 text-right <?php echo $mine ? 'text-white/70' : 'text-content-secondary'; ?>"><?php echo date('H:i', strtotime($m['created_at'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <span id="fim"></span>
            </div>

            <!-- Responder -->
            <?php if ($readOnly): ?>
                <p id="chat-readonly" class="px-5 py-4 text-sm text-content-secondary border-t border-content-secondary/10 flex items-center gap-2"><i class="fas fa-lock" aria-hidden="true"></i><?php echo $e($lang['chat_readonly_note']); ?></p>
            <?php else: ?>
                <?php if ($blocked): ?>
                    <p id="chat-blocked-note" class="px-5 py-2 text-xs text-red-700 dark:text-red-300 bg-red-500/10 flex items-center gap-2"><i class="fas fa-ban" aria-hidden="true"></i><?php echo $e($lang['chat_blocked_note']); ?></p>
                <?php endif; ?>
                <form id="chat-form" method="POST" action="<?php echo $base . '/' . $convId; ?>" class="border-t border-content-secondary/10 px-3 sm:px-5 py-3">
                    <?php echo Csrf::field(); ?>
                    <p id="chat-error" class="hidden mb-2 text-sm text-red-600 dark:text-red-400" role="alert"></p>
                    <div class="flex items-end gap-2">
                        <label for="chat-body" class="sr-only"><?php echo $e($lang['chat_placeholder']); ?></label>
                        <textarea id="chat-body" name="body" rows="1" maxlength="1000" required placeholder="<?php echo $e($lang['chat_placeholder']); ?>"
                            class="flex-1 resize-none max-h-40 rounded-2xl border border-content-secondary/25 bg-surface-base px-4 py-2.5 text-[0.9375rem] focus:outline-none focus:ring-2 focus:ring-brand-500/40 focus:border-brand-500"></textarea>
                        <button type="submit" id="chat-send" class="h-11 shrink-0 inline-flex items-center gap-2 px-4 rounded-2xl bg-brand-600 hover:bg-brand-700 disabled:opacity-60 text-white font-semibold transition-colors">
                            <i class="fas fa-paper-plane" aria-hidden="true"></i><span class="hidden sm:inline"><?php echo $e($lang['chat_send']); ?></span>
                        </button>
                    </div>
                    <p class="mt-1.5 px-1 flex justify-between gap-3 text-[0.6875rem] text-content-secondary"><span class="hidden md:inline"><?php echo $e($lang['chat_hint']); ?></span><span class="md:hidden"></span><span id="chat-count" class="tabular-nums">0/1000</span></p>
                </form>
            <?php endif; ?>
        </section>
    </main>

    <script>
        // Conversa: rolar para o fim, enviar sem recarregar e buscar novas a cada 10 s
        (() => {
            const box = document.getElementById('chat');
            const list = document.getElementById('chat-messages');
            const end = document.getElementById('fim');
            const form = document.getElementById('chat-form');
            const body = document.getElementById('chat-body');
            const send = document.getElementById('chat-send');
            const error = document.getElementById('chat-error');
            const count = document.getElementById('chat-count');
            let lastId = Number(box.dataset.lastId) || 0;

            const ymd = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            const dayLabel = (day) => {
                const now = new Date();
                const yest = new Date(now.getTime() - 864e5);
                if (day === ymd(now)) return box.dataset.today;
                if (day === ymd(yest)) return box.dataset.yesterday;
                const [y, m, d] = day.split('-');
                return `${d}/${m}/${y}`;
            };
            const nearBottom = () => list.scrollHeight - list.scrollTop - list.clientHeight < 120;
            const toBottom = () => { list.scrollTop = list.scrollHeight; };

            function add(m) {
                if (list.querySelector(`[data-id="${m.id}"]`)) return;
                const days = list.querySelectorAll('.chat-day');
                const lastDay = days.length ? days[days.length - 1].dataset.day : null;
                if (m.day !== lastDay) {
                    const p = document.createElement('p');
                    p.className = 'chat-day text-center text-xs font-semibold text-content-secondary py-2';
                    p.dataset.day = m.day;
                    const s = document.createElement('span');
                    s.className = 'px-3 py-1 rounded-full bg-surface-base';
                    s.textContent = dayLabel(m.day);
                    p.appendChild(s);
                    list.insertBefore(p, end);
                }
                const row = document.createElement('div');
                row.className = 'flex ' + (m.mine ? 'justify-end' : 'justify-start');
                row.dataset.id = m.id;
                row.dataset.mine = m.mine ? '1' : '0';
                const bubble = document.createElement('div');
                bubble.className = 'max-w-[80%] sm:max-w-[70%] px-3.5 py-2 rounded-2xl shadow-sm ' + (m.mine
                    ? 'bg-brand-600 text-white rounded-br-md' : 'bg-surface-base text-content-primary rounded-bl-md border border-content-secondary/10');
                const text = document.createElement('p');
                text.className = 'whitespace-pre-wrap break-words text-[0.9375rem] leading-snug';
                text.textContent = m.body;
                const time = document.createElement('p');
                time.className = 'text-[0.6875rem] mt-0.5 text-right ' + (m.mine ? 'text-white/70' : 'text-content-secondary');
                time.textContent = m.time;
                bubble.append(text, time);
                row.appendChild(bubble);
                list.insertBefore(row, end);
                lastId = Math.max(lastId, m.id);
                box.dataset.lastId = lastId;
            }

            toBottom();

            // Mensagens novas a cada 10 s, só com a aba visível
            let polling = false;
            async function poll() {
                if (polling || document.hidden) return;
                polling = true;
                try {
                    const r = await fetch(`${box.dataset.pollUrl}?after_id=${lastId}`, { headers: { Accept: 'application/json' } });
                    if (r.ok) {
                        const data = await r.json();
                        const stick = nearBottom();
                        data.messages.forEach(add);
                        if (data.messages.length && stick) toBottom();
                    }
                } catch { /* sem internet: tenta de novo na próxima */ }
                polling = false;
            }
            setInterval(poll, 10000);
            document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

            if (!form) return;
            const showError = (msg) => { error.textContent = msg; error.classList.toggle('hidden', !msg); };
            const grow = () => {
                body.style.height = 'auto';
                body.style.height = Math.min(body.scrollHeight, 160) + 'px';
                count.textContent = `${body.value.length}/1000`;
            };
            body.addEventListener('input', () => { grow(); showError(''); });
            // Enter envia só com teclado de verdade; no celular, Enter pula linha (o botão envia)
            const enterSends = window.matchMedia('(pointer: fine)').matches;
            body.addEventListener('keydown', (ev) => {
                if (enterSends && ev.key === 'Enter' && !ev.shiftKey && !ev.isComposing) {
                    ev.preventDefault();
                    form.requestSubmit();
                }
            });

            form.addEventListener('submit', async (ev) => {
                ev.preventDefault();
                if (!body.value.trim() || send.disabled) return;
                send.disabled = true;
                try {
                    const r = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
                    const data = await r.json().catch(() => null);
                    if (r.status === 201 && data) {
                        add(data.message);
                        body.value = '';
                        grow();
                        toBottom();
                    } else {
                        showError((data && data.message) || box.dataset.errGeneric);
                    }
                } catch {
                    showError(box.dataset.errGeneric);
                }
                send.disabled = false;
                body.focus();
            });
        })();
    </script>
    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
</body>

</html>
