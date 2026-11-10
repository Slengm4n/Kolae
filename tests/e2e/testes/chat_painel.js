// Teste de clique das Mensagens no painel do dono (D66, parte D) e o caminho completo com a API:
// mensagem pelo app (API) -> aparece no painel -> resposta no painel -> aparece na API.
// Cabeçalho com as não lidas, lista (mais recente primeiro, sem e-mail), filtro por quadra,
// conversa (lida ao abrir), responder com Enter, link recusado, mensagem nova pela consulta de
// 10 s, bloquear e desbloquear, quadra desativada só para leitura, outro dono e admin barrados,
// celular sem rolagem para o lado e sem erros de JS.
// Uso: node tests/e2e/rodar.js chat_painel (ou direto: node tests/e2e/testes/chat_painel.js <ids A B>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const BASE = lib.BASE;
const API = `${BASE}/api/v1`;
const [A, B] = process.argv.slice(2).map(Number);
const sql = lib.sql;

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};
const call = async (method, path, token, body) => {
  const headers = { Authorization: `Bearer ${token}` };
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  const r = await fetch(API + path, { method, headers, body: body === undefined ? undefined : JSON.stringify(body) });
  const text = await r.text();
  return { status: r.status, json: text ? JSON.parse(text) : null };
};
const apiLogin = async email => (await fetch(`${API}/auth/login`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email, password: 'teste123' }) }).then(r => r.json())).data.token;

const PESSOA = 'dono@teste.local'; // outro dono, aqui no papel de quem pergunta pelo app
const DONO = 'dono-s3@kolae.test';

(async () => {
  const statusA = sql(`SELECT status FROM venues WHERE id=${A}`);
  const statusB = sql(`SELECT status FROM venues WHERE id=${B}`);
  sql(`DELETE FROM conversations WHERE venue_id IN (${A}, ${B})`);
  sql(`UPDATE venues SET status='available' WHERE id IN (${A}, ${B}) AND status='unavailable'`);
  const browser = await puppeteer.launch({ executablePath: lib.BROWSER, headless: 'new' });
  const errs = [];
  try {
    const pessoa = await apiLogin(PESSOA);
    const pessoaNome = sql(`SELECT name FROM users WHERE email='${PESSOA}'`);

    // Pelo app: 2 mensagens na quadra A e, depois, 1 na quadra B
    const convA = (await call('POST', `/venues/${A}/conversations`, pessoa)).json.data.id;
    await call('POST', `/conversations/${convA}/messages`, pessoa, { body: 'Oi! Tem horário sábado de manhã?' });
    await call('POST', `/conversations/${convA}/messages`, pessoa, { body: 'Somos 10 pessoas.' });
    sql(`UPDATE conversations SET last_message_at = NOW() - INTERVAL 1 MINUTE WHERE id=${convA}`);
    const convB = (await call('POST', `/venues/${B}/conversations`, pessoa)).json.data.id;
    await call('POST', `/conversations/${convB}/messages`, pessoa, { body: 'Vocês alugam bola?' });

    // --- Dono no site ---
    const p = await browser.newPage();
    p.on('pageerror', e => errs.push(e.message));
    p.on('console', m => { if (m.type() === 'error' && !/favicon|Failed to load resource/.test(m.text())) errs.push(m.text()); });
    await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
    await p.setViewport({ width: 1280, height: 800 });
    await p.goto(`${BASE}/login?lang=pt-br`, { waitUntil: 'networkidle2' });
    await p.type('#email', DONO); await p.type('#password', 'teste123');
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);
    const go = url => p.goto(BASE + url, { waitUntil: 'networkidle2' });

    check('cabeçalho: "Mensagens" com 3 não lidas', await p.$eval('#nav-messages', a => [a.textContent.replace(/\s+/g, ' ').trim(), a.querySelector('[data-chat-unread]')?.textContent]), ['Mensagens3', '3']);
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#nav-messages')]);
    check('lista: título da aba', await p.title(), 'Mensagens · Kolae');
    const rows = await p.$$eval('#chat-list [data-conversation]', ls => ls.map(l => [Number(l.dataset.conversation), Number(l.dataset.unread)]));
    check('lista: as 2 conversas, a mais recente (B) primeiro, com as não lidas', rows, [[convB, 1], [convA, 2]]);
    check('lista: nome de quem pergunta e prévia da última mensagem', await p.$eval(`[data-conversation="${convA}"]`, l => [l.querySelector('[data-name]').textContent.trim(), l.querySelector('[data-preview]').textContent.trim()]), [pessoaNome, 'Somos 10 pessoas.']);
    check('lista: o e-mail de quem pergunta não aparece', (await p.content()).includes(PESSOA), false);

    // Filtro por quadra (o dono de teste tem várias)
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click(`#chat-venues a[data-venue="${A}"]`)]);
    check('filtro: só a conversa da quadra A, pílula marcada', [await p.$$eval('#chat-list [data-conversation]', ls => ls.map(l => Number(l.dataset.conversation))), await p.$eval('#chat-venues [aria-current=page]', a => a.dataset.venue)], [[convA], String(A)]);
    await go(`/dashboard/mensagens?quadra=99999999`);
    check('filtro com quadra de outra pessoa: mostra todas', await p.$$eval('#chat-list [data-conversation]', ls => ls.length), 2);

    // --- Conversa ---
    await go(`/dashboard/mensagens/${convA}`);
    check('conversa: nome no topo e as 2 mensagens dela (à esquerda)', [await p.$eval('#chat-person', h => h.textContent.trim()), await p.$$eval('#chat-messages [data-id]', ds => ds.map(d => d.dataset.mine))], [pessoaNome, ['0', '0']]);
    check('conversa: separador "Hoje"', await p.$eval('.chat-day', d => d.textContent.trim()), 'Hoje');
    check('ao abrir, fica lida: cabeçalho cai para 1', await p.$eval('#nav-messages [data-chat-unread]', s => s.textContent), '1');
    check('API: o app vê a conversa como lida pelo dono? (o dono leu; a pessoa ainda tem 0 não lidas)', (await call('GET', '/conversations', pessoa)).json.data.find(c => c.id === convA).unread_count, 0);

    // Responder com Enter (sem recarregar)
    await p.type('#chat-body', 'Tem sim, das 8h às 12h.');
    await p.keyboard.press('Enter');
    await p.waitForFunction(() => document.querySelectorAll('#chat-messages [data-mine="1"]').length === 1, { timeout: 5000 }).catch(() => {});
    check('responder: balão do dono aparece e o campo limpa', [await p.$$eval('#chat-messages [data-mine="1"]', ds => ds.map(d => d.innerText.split('\n')[0])), await p.$eval('#chat-body', t => t.value)], [['Tem sim, das 8h às 12h.'], '']);
    const doApp = (await call('GET', `/conversations/${convA}/messages`, pessoa)).json.data;
    check('API: a resposta chega ao app (mine false) e conta como não lida', [doApp[doApp.length - 1].body, doApp[doApp.length - 1].mine, (await call('GET', '/conversations', pessoa)).json.data.find(c => c.id === convA).unread_count], ['Tem sim, das 8h às 12h.', false, 1]);

    // Link recusado
    await p.type('#chat-body', 'Veja em kolae.online');
    await p.click('#chat-send');
    await p.waitForFunction(() => !document.getElementById('chat-error').classList.contains('hidden'), { timeout: 5000 }).catch(() => {});
    check('link: aviso e nenhum balão novo', [await p.$eval('#chat-error', e => e.textContent), await p.$$eval('#chat-messages [data-mine="1"]', ds => ds.length)], ['Mensagens não podem ter links nem e-mails.', 1]);
    await p.$eval('#chat-body', t => { t.value = ''; t.dispatchEvent(new Event('input')); });
    check('ao digitar de novo, o aviso some', await p.$eval('#chat-error', e => e.classList.contains('hidden')), true);

    // Mensagem nova pelo app aparece sozinha (consulta a cada 10 s)
    await call('POST', `/conversations/${convA}/messages`, pessoa, { body: 'Fechado, obrigado!' });
    await p.waitForFunction(() => [...document.querySelectorAll('#chat-messages [data-mine="0"]')].some(d => d.innerText.includes('Fechado, obrigado!')), { timeout: 13000 }).catch(() => {});
    check('consulta: a mensagem nova do app aparece sem recarregar', await p.$$eval('#chat-messages [data-mine="0"]', ds => ds.length), 3);

    // Bloquear e desbloquear
    await p.click('#chat-block');
    await p.waitForSelector('[data-ok]', { visible: true });
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('[data-ok]')]);
    await p.waitForSelector('.kolae-toast', { timeout: 3000 }).catch(() => {});
    check('bloquear: aviso, faixa de bloqueada e botão desbloquear', [await p.$eval('.kolae-toast', t => t.innerText.trim()).catch(() => null), !!(await p.$('#chat-blocked-note')), !!(await p.$('#chat-unblock'))], ['Conversa bloqueada.', true, true]);
    check('API: bloqueada, o app recebe 403 CHAT_BLOCKED', (await call('POST', `/conversations/${convA}/messages`, pessoa, { body: 'Oi?' })).json.code, 'CHAT_BLOCKED');
    await p.evaluate(() => document.querySelectorAll('.kolae-toast').forEach(t => t.remove())); // o aviso fica por cima do botão
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#chat-unblock')]);
    check('desbloquear: o app volta a enviar (201)', [!!(await p.$('#chat-block')), (await call('POST', `/conversations/${convA}/messages`, pessoa, { body: 'Oi de novo' })).status], [true, 201]);

    // Quadra desativada: só leitura
    sql(`UPDATE venues SET status='unavailable' WHERE id=${A}`);
    await go(`/dashboard/mensagens/${convA}`);
    const tentou = await p.evaluate(async (url) => {
      const fd = new FormData();
      fd.set('_csrf', document.querySelector('input[name=_csrf]').value);
      fd.set('body', 'Ainda dá?');
      const r = await fetch(url, { method: 'POST', body: fd, headers: { Accept: 'application/json' } });
      return r.status;
    }, `${BASE}/dashboard/mensagens/${convA}`);
    check('quadra desativada: sem campo de resposta, aviso, e o envio direto dá 409', [!!(await p.$('#chat-form')), !!(await p.$('#chat-readonly')), tentou], [false, true, 409]);
    sql(`UPDATE venues SET status='${statusA}' WHERE id=${A}`);

    // Celular: cabe na tela
    await p.setViewport({ width: 390, height: 780, isMobile: true, hasTouch: true });
    await go(`/dashboard/mensagens/${convA}`);
    check('celular: sem rolagem para o lado, campo de resposta visível', await p.evaluate(() => {
      const f = document.getElementById('chat-form').getBoundingClientRect();
      return [document.documentElement.scrollWidth <= innerWidth, f.bottom <= innerHeight + 1];
    }), [true, true]);
    await go('/dashboard/mensagens');
    check('celular: a lista cabe na tela', await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
    await p.screenshot({ path: lib.OUT + '/chat-painel-celular.png' });

    // --- Quem não é o dono da quadra ---
    const ctx = await browser.createBrowserContext();
    const o = await ctx.newPage();
    await o.goto(`${BASE}/login?lang=pt-br`, { waitUntil: 'networkidle2' });
    await o.type('#email', PESSOA); await o.type('#password', 'teste123');
    await Promise.all([o.waitForNavigation({ waitUntil: 'networkidle2' }), o.$eval('#login-form', f => f.submit())]);
    await o.goto(`${BASE}/dashboard/mensagens/${convA}`, { waitUntil: 'networkidle2' });
    check('outro dono: volta à lista com "Conversa não encontrada."', [new URL(o.url()).pathname, await o.$eval('.kolae-toast', t => t.innerText.trim()).catch(() => null)], [lib.BASE_PATH + '/dashboard/mensagens', 'Conversa não encontrada.']);
    check('outro dono: a novas da conversa alheia dá 404', await o.evaluate(async u => (await fetch(u, { headers: { Accept: 'application/json' } })).status, `${BASE}/dashboard/mensagens/${convA}/novas?after_id=0`), 404);
    await ctx.close();

    const ctx2 = await browser.createBrowserContext();
    const a = await ctx2.newPage();
    await a.goto(`${BASE}/login?lang=pt-br`, { waitUntil: 'networkidle2' });
    await a.type('#email', 'admin@teste.local'); await a.type('#password', 'teste123');
    await Promise.all([a.waitForNavigation({ waitUntil: 'networkidle2' }), a.$eval('#login-form', f => f.submit())]);
    await a.goto(`${BASE}/dashboard/mensagens/${convA}`, { waitUntil: 'networkidle2' });
    check('admin não lê conversas: vai para o painel do admin', new URL(a.url()).pathname, lib.BASE_PATH + '/admin');
    await ctx2.close();

    check('sem erros de JS', errs, []);
  } catch (e) {
    fail++;
    console.log('FALHA erro no teste: ' + e.stack);
  } finally {
    sql(`UPDATE venues SET status='${statusA}' WHERE id=${A}`);
    sql(`UPDATE venues SET status='${statusB}' WHERE id=${B}`);
    sql(`DELETE FROM conversations WHERE venue_id IN (${A}, ${B})`);
    await browser.close();
  }
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  process.exit(fail ? 1 : 0);
})();
