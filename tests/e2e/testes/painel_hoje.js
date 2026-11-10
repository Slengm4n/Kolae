// Teste de clique do bloco "Hoje" do painel do dono (D67, parte B): pedidos esperando, mensagens
// não lidas, partidas de hoje (a que está acontecendo e a próxima) e o Kolae Premium com o anúncio,
// cada cartão com o número, o texto e o link certos; "Novo local" ao lado de "Meus locais";
// celular e inglês. Os dados vêm da API (como o app faz) e do banco de teste.
// Uso: node tests/e2e/rodar.js painel_hoje (ou direto: node tests/e2e/testes/painel_hoje.js <ids A B>)
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
const day = d => { const x = new Date(Date.now() - 3 * 3600e3); x.setUTCDate(x.getUTCDate() + d); return x.toISOString().slice(0, 10); };
const api = async (method, p, token, body) => (await fetch(API + p, { method, headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' }, body: body ? JSON.stringify(body) : undefined })).json().catch(() => null);
const apiLogin = async email => (await (await fetch(`${API}/auth/login`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email, password: 'teste123' }) })).json()).data.token;
const card = (p, key) => p.$eval(`#today [data-today="${key}"]`, e => ({
  value: e.dataset.value, text: e.querySelector('[data-text]').textContent.trim(),
  sub: (e.querySelector('[data-sub]') || {}).textContent || null, href: e.getAttribute('href'),
})).catch(() => null);
const rel = href => href && href.replace(BASE, '');

(async () => {
  const ana = Number(sql("SELECT id FROM users WHERE email='dono@teste.local'"));
  const dono = Number(sql(`SELECT user_id FROM venues WHERE id=${A}`));
  const verifAna = sql(`SELECT IFNULL(email_verified_at, 'NULL') FROM users WHERE id=${ana}`);
  const lastNotif = Number(sql('SELECT IFNULL(MAX(id), 0) FROM notifications'));
  const setPlan = (plan, until = 'NULL') => sql(`UPDATE users SET plan='${plan}', plan_until=${until}, plan_requested_at=NULL WHERE id=${dono}`);
  const clean = () => {
    sql(`DELETE FROM match_requests WHERE venue_id IN (${A}, ${B})`);
    sql(`DELETE FROM conversations WHERE venue_id IN (${A}, ${B})`);
    sql(`DELETE FROM venue_ads WHERE venue_id IN (${A}, ${B})`);
  };
  clean();
  setPlan('free');
  sql(`UPDATE users SET email_verified_at = NOW() WHERE id = ${ana}`);
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  try {
    const ctx = await b.createBrowserContext();
    const p = await ctx.newPage();
    await p.setViewport({ width: 1366, height: 900 });
    p.jsErrors = [];
    p.on('pageerror', e => p.jsErrors.push(e.message));
    await p.goto(`${BASE}/login?lang=pt-br`, { waitUntil: 'networkidle2' });
    await p.type('#email', 'dono-s3@kolae.test'); await p.type('#password', 'teste123');
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);
    const go = () => p.goto(`${BASE}/dashboard?lang=pt-br`, { waitUntil: 'networkidle2' });

    // --- Tudo em dia (Grátis, sem pedidos e sem mensagens) ---
    await go();
    check('bloco "Hoje" com os quatro cartões', await p.$$eval('#today [data-today]', els => els.map(e => e.dataset.today)), ['requests', 'messages', 'matches', 'premium']);
    check('as "Ações rápidas" saíram', await p.evaluate(() => document.body.innerText.includes('Ações Rápidas')), false);
    check('pedidos: zero, discreto, leva às solicitações', await card(p, 'requests'), { value: '0', text: 'Nenhum pedido esperando', sub: null, href: `${BASE}/dashboard/notificacoes?aba=solicitacoes` });
    check('mensagens: zero', [(await card(p, 'messages')).value, (await card(p, 'messages')).text, rel((await card(p, 'messages')).href)], ['0', 'Nenhuma mensagem nova', '/dashboard/mensagens']);
    check('cartão com zero não fica destacado', await p.$eval('#today [data-today="requests"]', e => e.className.includes('bg-brand-500/5')), false);
    // O seed põe uma partida acontecendo agora na Arena e outra daqui a 1 hora na Beach
    const partidas = await card(p, 'matches');
    check('partidas: a que está acontecendo agora, na Arena, levando à agenda dela', [Number(partidas.value) >= 2, partidas.text.startsWith('Agora, até '), partidas.text.endsWith('Arena Teste S3'), rel(partidas.href)], [true, true, true, `/dashboard/quadras/${A}/agenda`]);
    check('Premium (Grátis): convite, leva ao plano', [(await card(p, 'premium')).text, rel((await card(p, 'premium')).href)], ['Destaque suas quadras com anúncio e estatísticas', '/dashboard/perfil/plano']);
    check('"Novo local" ao lado de "Meus locais"', await p.$eval('#new-venue', a => [a.textContent.trim(), a.getAttribute('href').endsWith('/dashboard/quadras/criar')]), ['Novo local', true]);

    // --- Pedido e mensagem pela API (como o app) ---
    const tAna = await apiLogin('dono@teste.local');
    await api('POST', `/venues/${A}/requests`, tAna, { start_time: `${day(2)}T19:00:00-03:00`, end_time: `${day(2)}T20:00:00-03:00` });
    const conv = await api('POST', `/venues/${B}/conversations`, tAna);
    await api('POST', `/conversations/${conv.data.id}/messages`, tAna, { body: 'Tem colete?' });
    await api('POST', `/conversations/${conv.data.id}/messages`, tAna, { body: 'E bola?' });
    await go();
    check('pedidos: 1 esperando, destacado', [(await card(p, 'requests')).value, (await card(p, 'requests')).text, await p.$eval('#today [data-today="requests"]', e => e.className.includes('bg-brand-500/5'))], ['1', '1 pedido esperando resposta', true]);
    check('mensagens: 2 não lidas', [(await card(p, 'messages')).value, (await card(p, 'messages')).text], ['2', '2 mensagens não lidas']);
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#today [data-today="requests"]')]);
    check('clicar em pedidos abre a aba Solicitações com o pedido', [await p.$eval('#req-tabs [aria-current=page]', e => e.dataset.tab), await p.$$eval('#req-pending [data-request]', els => els.length)], ['solicitacoes', 1]);

    // --- Premium e anúncio ---
    setPlan('premium', `'${day(20)}'`);
    sql(`INSERT INTO venue_ads (venue_id, title, body, starts_on, ends_on, status) VALUES (${B}, 'Sexta 50%', 'Texto', '${day(0)}', '${day(5)}', 'active')`);
    await go();
    const prem = await card(p, 'premium');
    check('Premium: validade, anúncio no ar e link para a aba Anúncio', [prem.text, prem.sub, rel(prem.href)], [`Ativo até ${day(20).slice(8, 10)}/${day(20).slice(5, 7)}`, 'Anúncio no ar: Beach Teste S3', `/dashboard/quadras/${B}/anuncio`]);
    sql(`UPDATE venue_ads SET status='paused' WHERE venue_id=${B}`);
    await go();
    check('anúncio pausado', (await card(p, 'premium')).sub, 'Anúncio pausado: Beach Teste S3');
    sql(`DELETE FROM venue_ads WHERE venue_id IN (${A}, ${B})`);
    await go();
    check('Premium sem anúncio: leva à aba Anúncio da primeira quadra', [(await card(p, 'premium')).sub, rel((await card(p, 'premium')).href).endsWith('/anuncio')], ['Nenhum anúncio no ar', true]);
    setPlan('premium', `'${day(-1)}'`);
    await go();
    check('Premium vencido: aviso para renovar, leva ao plano', [(await card(p, 'premium')).text.startsWith('Venceu em'), rel((await card(p, 'premium')).href)], [true, '/dashboard/perfil/plano']);
    check('nenhum erro de JavaScript', p.jsErrors, []);

    // --- Celular e inglês ---
    await p.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
    await go();
    check('celular: sem rolagem para o lado', await p.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
    await p.screenshot({ path: path.join(lib.OUT, 'painel_hoje_celular.png'), fullPage: true });
    await p.goto(`${BASE}/dashboard?lang=en-us`, { waitUntil: 'networkidle2' });
    check('inglês', [await p.$eval('#today-title', h => h.textContent.trim()), (await card(p, 'requests')).text], ['Today', '1 request waiting for a reply']);
    await p.setViewport({ width: 1366, height: 900 });
    await go();
    await (await p.$('#today')).screenshot({ path: path.join(lib.OUT, 'painel_hoje.png') });
  } finally {
    clean();
    sql(`DELETE FROM notifications WHERE id > ${lastNotif} AND user_id IN (${ana}, ${dono})`);
    setPlan('free');
    sql(`UPDATE users SET email_verified_at = ${verifAna === 'NULL' ? 'NULL' : `'${verifAna}'`} WHERE id = ${ana}`);
    await b.close();
  }
  console.log(fail ? `\n${fail} FALHA(S)` : '\nTodos os passos passaram');
  process.exit(fail ? 1 : 0);
})();
