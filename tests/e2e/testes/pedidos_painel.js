// Teste de clique de "Notificações e solicitações" do dono (D64, parte C): pedidos feitos pela API
// (como o app faz) aparecem no sino e na aba Solicitações; aprovar (com confirmação) vira partida
// na agenda e recusa sozinho o outro pedido no mesmo horário; recusar com motivo; erros no servidor;
// dono de outra quadra e admin sem acesso; aba Avisos marca como lidos; atalho para Mensagens;
// WhatsApp só depois de aprovar; nunca o e-mail; celular e inglês.
// Uso: node tests/e2e/rodar.js pedidos_painel (ou direto: node tests/e2e/testes/pedidos_painel.js <ids A B>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const path = require('path');
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
const day = (days) => { const d = new Date(Date.now() - 3 * 3600e3); d.setUTCDate(d.getUTCDate() + days); return d.toISOString().slice(0, 10); };
const iso = (days, hm) => `${day(days)}T${hm}:00-03:00`;
const api = async (method, p, token, body) => {
  const r = await fetch(API + p, { method, headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' }, body: body ? JSON.stringify(body) : undefined });
  return { status: r.status, json: await r.json().catch(() => null) };
};
const apiLogin = async email => (await (await fetch(`${API}/auth/login`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email, password: 'teste123' }) })).json()).data.token;
const toast = p => p.waitForSelector('.kolae-toast', { timeout: 4000 }).then(() => p.$eval('.kolae-toast', e => e.innerText.trim())).catch(() => '');

async function login(b, email, viewport = { width: 1366, height: 900 }) {
  const ctx = await b.createBrowserContext();
  const p = await ctx.newPage();
  await p.setViewport(viewport);
  p.jsErrors = [];
  p.on('pageerror', e => p.jsErrors.push(e.message));
  await p.goto(`${BASE}/login?lang=pt-br`, { waitUntil: 'networkidle2' });
  await p.type('#email', email); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);
  return p;
}
const go = (p, url) => p.goto(url, { waitUntil: 'networkidle2' });
const bell = p => p.$eval('#nav-bell [data-bell-count]', e => e.textContent.trim()).catch(() => null);
const status = id => sql(`SELECT CONCAT_WS('|', status, IFNULL(response_note, 'NULL'), IFNULL(match_id, 'NULL')) FROM match_requests WHERE id=${id}`);
// POST direto (como quem pula a tela), com o token de CSRF da sessão
const postDirect = (p, url, fields) => p.evaluate(async (url, fields, page) => {
  const html = await (await fetch(page)).text();
  const fd = new FormData();
  fd.set('_csrf', (html.match(/name="_csrf" value="([^"]+)"/) || [])[1] || '');
  Object.entries(fields).forEach(([k, v]) => fd.set(k, v));
  return (await fetch(url, { method: 'POST', body: fd })).url;
}, url, fields, `${BASE}/dashboard/perfil`); // o perfil sempre tem formulário (a página de notificações pode não ter)

(async () => {
  const ids = Object.fromEntries(['dono@teste.local', 'admin@teste.local', 'dono-s3@kolae.test'].map(e => [e, Number(sql(`SELECT id FROM users WHERE email='${e}'`))]));
  const ana = ids['dono@teste.local'];  // outro dono, aqui no papel de quem pede pelo app
  const beto = ids['admin@teste.local']; // segundo jogador (pela API, o papel não importa)
  const dono = ids['dono-s3@kolae.test'];
  const original = Object.fromEntries([ana, beto].map(id => [id, sql(`SELECT CONCAT_WS('|', IFNULL(email_verified_at, 'NULL'), IFNULL(whatsapp, 'NULL')) FROM users WHERE id=${id}`)]));
  const emailAna = sql(`SELECT email FROM users WHERE id=${ana}`);
  const lastNotif = Number(sql('SELECT IFNULL(MAX(id), 0) FROM notifications'));
  sql(`DELETE FROM match_requests WHERE venue_id IN (${A}, ${B})`);
  sql(`DELETE FROM conversations WHERE venue_id IN (${A}, ${B})`);
  sql(`UPDATE users SET email_verified_at = NOW() WHERE id IN (${ana}, ${beto})`);
  sql(`UPDATE users SET whatsapp = '5511988887777' WHERE id = ${ana}`);
  sql(`UPDATE users SET whatsapp = NULL WHERE id = ${beto}`);
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });

  try {
    // --- Pedidos pelo app (API) ---
    const tAna = await apiLogin('dono@teste.local');
    const tBeto = await apiLogin('admin@teste.local');
    const r1 = (await api('POST', `/venues/${A}/requests`, tAna, { start_time: iso(2, '19:00'), end_time: iso(2, '20:00'), message: 'Somos 10, society' })).json.data;
    const r2 = (await api('POST', `/venues/${A}/requests`, tBeto, { start_time: iso(2, '19:30'), end_time: iso(2, '20:30') })).json.data;
    const r3 = (await api('POST', `/venues/${B}/requests`, tAna, { start_time: iso(3, '10:00'), end_time: iso(3, '11:00') })).json.data;
    check('três pedidos criados pela API', [r1 && r1.status, r2 && r2.status, r3 && r3.status], ['pending', 'pending', 'pending']);

    // --- Painel do dono: sino e aba Solicitações ---
    const p = await login(b, 'dono-s3@kolae.test');
    await go(p, `${BASE}/dashboard`);
    check('sino no cabeçalho com 3 pedidos', await bell(p), '3');
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#nav-bell')]);
    check('abre direto em Solicitações (há pedido esperando)', await p.$eval('#req-tabs [aria-current=page]', e => e.dataset.tab), 'solicitacoes');
    check('pendentes do horário mais próximo para o mais distante', await p.$$eval('#req-pending [data-request]', els => els.map(e => Number(e.dataset.request))), [r1.id, r2.id, r3.id]);
    const card = await p.$eval(`[data-request="${r1.id}"]`, e => ({ person: e.querySelector('[data-person]').textContent.trim(), when: e.querySelector('[data-when]').textContent.trim(), msg: (e.querySelector('[data-message]') || {}).textContent }));
    check('cartão: primeiro nome, dia e horário, mensagem', [card.person.endsWith('pediu'), card.when, card.msg], [true, `${day(2).slice(8, 10)}/${day(2).slice(5, 7)}, das 19:00 às 20:00`, '"Somos 10, society"']);
    check('nunca mostra o e-mail de quem pediu', await p.evaluate(e => !document.body.innerHTML.includes(e), emailAna), true);
    check('WhatsApp não aparece antes de aprovar', !!(await p.$('[data-whatsapp]')), false);

    // Aprovar com a confirmação do Kolae
    await p.click(`[data-request="${r1.id}"] [data-approve]`);
    await p.waitForSelector('.kolae-modal [data-ok]', { timeout: 3000 });
    check('pede confirmação com o horário', await p.$eval('.kolae-modal', e => e.innerText.includes('19:00 às 20:00')), true);
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('.kolae-modal [data-ok]')]);
    check('aprova com o aviso (e o outro no mesmo horário recusado)', await toast(p), 'Pedido aprovado. Outros pedidos no mesmo horário foram recusados.');
    const [st1, , match1] = status(r1.id).split('|');
    check('banco: aprovado com a partida; o do Beto recusado', [st1, match1 !== 'NULL', status(r2.id).split('|')[0]], ['approved', true, 'declined']);
    check('o pedido sai dos pendentes e vai para o histórico', [await p.$$eval('#req-pending [data-request]', els => els.map(e => Number(e.dataset.request))), await p.$eval(`#req-history [data-request="${r1.id}"]`, e => e.dataset.status)], [[r3.id], 'approved']);
    check('depois de aprovar: WhatsApp de quem pediu', await p.$eval(`#req-history [data-request="${r1.id}"] [data-whatsapp]`, a => a.href.startsWith('https://wa.me/5511988887777?text=')), true);
    check('recusado no histórico, sem WhatsApp', [await p.$eval(`#req-history [data-request="${r2.id}"]`, e => e.dataset.status), !!(await p.$(`#req-history [data-request="${r2.id}"] [data-whatsapp]`))], ['declined', false]);
    await go(p, `${BASE}/dashboard/quadras/${A}/agenda?data=${day(2)}`);
    check('a partida aparece na agenda com o primeiro nome', await p.evaluate(n => document.body.innerText.includes(n), sql(`SELECT renter_name FROM matches WHERE id=${match1}`)), true);
    const pedidosAna = (await api('GET', '/me/requests', tAna)).json.data;
    check('no app, a Ana vê o pedido aprovado', (pedidosAna.find(r => r.id === r1.id) || {}).status, 'approved');

    // Recusar com motivo
    await go(p, `${BASE}/dashboard/notificacoes?aba=solicitacoes`);
    await p.click(`[data-request="${r3.id}"] summary`);
    await p.type(`#note-${r3.id}`, 'Quadra fechada para manutenção nesse dia');
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click(`[data-request="${r3.id}"] [data-decline]`)]);
    check('recusa com o aviso', await toast(p), 'Pedido recusado.');
    check('banco: recusado com o motivo', status(r3.id), 'declined|Quadra fechada para manutenção nesse dia|NULL');
    const avisoAna = (await api('GET', '/me/notifications', tAna)).json.data.find(n => n.type === 'request_declined' && n.data.request_id === r3.id);
    check('no app, a Ana recebe a recusa com o motivo', !!avisoAna && avisoAna.body.endsWith('Motivo: Quadra fechada para manutenção nesse dia'), true);
    check('sem pedidos: mensagem de vazio', !!(await p.$('#req-empty')), true);

    // --- Erros no servidor ---
    const r4 = (await api('POST', `/venues/${B}/requests`, tAna, { start_time: iso(4, '10:00'), end_time: iso(4, '11:00') })).json.data;
    check('motivo com 201 caracteres: erro, nada muda', [(await postDirect(p, `${BASE}/dashboard/notificacoes/pedidos/${r4.id}/recusar`, { note: 'x'.repeat(201) })).includes('error=req_note_long'), status(r4.id).split('|')[0]], [true, 'pending']);
    check('aprovar um já respondido: erro', (await postDirect(p, `${BASE}/dashboard/notificacoes/pedidos/${r3.id}/aprovar`, {})).includes('error=req_not_pending'), true);
    check('pedido que não existe: erro', (await postDirect(p, `${BASE}/dashboard/notificacoes/pedidos/99999999/aprovar`, {})).includes('error=req_not_found'), true);
    sql(`INSERT INTO matches (venue_id, creator_user_id, status, start_time, end_time) VALUES (${B}, ${dono}, 'scheduled', '${day(4)} 10:30:00', '${day(4)} 11:30:00')`);
    const extra = Number(sql(`SELECT MAX(id) FROM matches WHERE venue_id=${B}`));
    await go(p, `${BASE}/dashboard/notificacoes?aba=solicitacoes`);
    await p.click(`[data-request="${r4.id}"] [data-approve]`);
    await p.waitForSelector('.kolae-modal [data-ok]', { timeout: 3000 });
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('.kolae-modal [data-ok]')]);
    check('o dono marcou outra partida no meio: aprovar avisa o conflito', [await toast(p), status(r4.id).split('|')[0]], ['Esse horário já está ocupado na agenda. Recuse o pedido ou mude a agenda.', 'pending']);
    sql(`DELETE FROM matches WHERE id=${extra}`);

    // Outro dono e admin
    const outro = await login(b, 'dono@teste.local');
    check('dono de outra quadra não aprova: não encontrado, nada muda', [(await postDirect(outro, `${BASE}/dashboard/notificacoes/pedidos/${r4.id}/aprovar`, {})).includes('error=req_not_found'), status(r4.id).split('|')[0]], [true, 'pending']);
    await go(outro, `${BASE}/dashboard/notificacoes?aba=solicitacoes`);
    check('a lista do outro dono não tem os pedidos desta quadra', await outro.$$eval('#req-pending [data-request]', els => els.length).catch(() => 0), 0);
    const adm = await login(b, 'admin@teste.local');
    await go(adm, `${BASE}/dashboard/notificacoes`);
    check('admin é levado para o painel dele', new URL(adm.url()).pathname.replace(lib.BASE_PATH, ''), '/admin');

    // --- Avisos ---
    await api('DELETE', `/me/requests/${r4.id}`, tAna); // a Ana cancela: o dono recebe "cancelou"
    const conv = (await api('POST', `/venues/${A}/conversations`, tAna)).json.data;
    await api('POST', `/conversations/${conv.id}/messages`, tAna, { body: 'Oi, vocês têm colete?' });
    await go(p, `${BASE}/dashboard`);
    check('sino: só o aviso de cancelamento (sem pedidos esperando)', await bell(p), '1');
    await go(p, `${BASE}/dashboard/notificacoes`);
    check('sem pedido esperando: abre em Avisos', await p.$eval('#req-tabs [aria-current=page]', e => e.dataset.tab), 'avisos');
    const avisos = await p.$$eval('#notif-list [data-notification]', els => els.map(e => [e.dataset.type, e.dataset.unread]));
    check('avisos: cancelado (novo) e os pedidos novos', [avisos[0][0], avisos[0][1], avisos.filter(a => a[0] === 'request_new').length], ['request_cancelled', '1', 4]);
    check('texto do aviso no idioma do painel', await p.$eval('#notif-list [data-type="request_cancelled"]', e => e.innerText.includes('cancelou o pedido')), true);
    check('atalho para as mensagens não lidas do chat', await p.$eval('#req-chat', a => [a.getAttribute('href').endsWith('/dashboard/mensagens'), a.innerText.includes('1 mensagem não lida')]), [true, true]);
    await go(p, `${BASE}/dashboard/notificacoes?aba=avisos`);
    check('abrir os avisos marca como lidos: o sino some', [await bell(p), Number(sql(`SELECT COUNT(*) FROM notifications WHERE user_id=${dono} AND read_at IS NULL`))], [null, 0]);
    check('nenhum erro de JavaScript', [...p.jsErrors, ...outro.jsErrors], []);

    // --- Celular e inglês ---
    const m = await login(b, 'dono-s3@kolae.test', { width: 390, height: 844, isMobile: true, hasTouch: true });
    const r5 = (await api('POST', `/venues/${B}/requests`, tAna, { start_time: iso(5, '18:00'), end_time: iso(5, '19:30'), message: 'Um texto comprido para ver se o cartão quebra direito no celular sem sair da tela' })).json.data;
    await go(m, `${BASE}/dashboard/notificacoes?aba=solicitacoes`);
    check('celular: sino visível e sem rolagem para o lado', [await bell(m), await m.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)], ['1', true]);
    await m.screenshot({ path: path.join(lib.OUT, 'pedidos_painel_celular.png'), fullPage: true });
    await go(m, `${BASE}/dashboard/notificacoes?aba=solicitacoes&lang=en-us`);
    check('inglês: título, abas e botão', [await m.$eval('h1', h => h.textContent.trim()), await m.$eval('#req-tabs [data-tab="avisos"]', e => e.innerText.trim()), await m.$eval(`[data-request="${r5.id}"] [data-approve]`, e => e.innerText.trim())], ['Notifications and requests', 'Notices', 'Approve']);
    await go(m, `${BASE}/dashboard/notificacoes?aba=solicitacoes&lang=pt-br`);
    await go(p, `${BASE}/dashboard/notificacoes?aba=solicitacoes`);
    await p.screenshot({ path: path.join(lib.OUT, 'pedidos_painel.png'), fullPage: true });
  } finally {
    // Deixa como encontrou
    sql(`DELETE FROM matches WHERE id IN (SELECT match_id FROM match_requests WHERE venue_id IN (${A}, ${B}) AND match_id IS NOT NULL)`);
    sql(`DELETE FROM match_requests WHERE venue_id IN (${A}, ${B})`);
    sql(`DELETE FROM conversations WHERE venue_id IN (${A}, ${B})`);
    sql(`DELETE FROM notifications WHERE id > ${lastNotif} AND user_id IN (${ana}, ${beto}, ${dono})`);
    for (const [id, orig] of Object.entries(original)) {
      const [verif, wa] = orig.split('|');
      sql(`UPDATE users SET email_verified_at = ${verif === 'NULL' ? 'NULL' : `'${verif}'`}, whatsapp = ${wa === 'NULL' ? 'NULL' : `'${wa}'`} WHERE id = ${id}`);
    }
    await b.close();
  }
  console.log(fail ? `\n${fail} FALHA(S)` : '\nTodos os passos passaram');
  process.exit(fail ? 1 : 0);
})();
