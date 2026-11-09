// Teste de clique da remoção de anúncio pelo admin (D63, parte E): card "Anúncios" no painel do
// admin (em aberto, situação), motivo obrigatório no navegador e no servidor, confirmação, sai do ar,
// notificação ad_removed para o dono, motivo na aba Anúncio do dono, remover de novo, dono sem
// acesso e celular.
// Uso: node tests/e2e/rodar.js anuncio_admin (ou direto: node tests/e2e/testes/anuncio_admin.js <ids A B C D>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const path = require('path');
const BASE = lib.BASE;
const [A, B, C, D] = process.argv.slice(2).map(Number);
const sql = lib.sql;

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};
const ymd = (days = 0) => { const d = new Date(Date.now() - 3 * 3600e3); d.setUTCDate(d.getUTCDate() + days); return d.toISOString().slice(0, 10); }; // hoje em Brasília
const TEST_VENUES = [A, B, C, D].join(',');
const ownerId = Number(sql(`SELECT user_id FROM venues WHERE id=${A}`));
const setPlan = plan => sql(`UPDATE users SET plan='${plan}', plan_until=NULL WHERE id=${ownerId}`);
const addAd = (venue, title, { starts = 0, ends = 5, status = 'active' } = {}) => {
  sql(`INSERT INTO venue_ads (venue_id, title, body, starts_on, ends_on, status) VALUES (${venue}, '${title}', 'Texto de ${title}', '${ymd(starts)}', '${ymd(ends)}', '${status}')`);
  return Number(sql(`SELECT MAX(id) FROM venue_ads WHERE venue_id=${venue}`));
};
const adStatus = id => sql(`SELECT CONCAT_WS('|', status, IFNULL(removed_reason, 'NULL')) FROM venue_ads WHERE id=${id}`);
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
const rows = p => p.$$eval('#anuncios [data-ad-row]', els => els.map(e => [Number(e.dataset.adRow), e.dataset.state]));
// POST direto (como quem pula a tela), com o token de CSRF da sessão tirado de uma página com formulário
const postDirect = (p, url, fields, tokenPage) => p.evaluate(async (url, fields, tokenPage) => {
  const html = await (await fetch(tokenPage)).text();
  const fd = new FormData();
  fd.set('_csrf', (html.match(/name="_csrf" value="([^"]+)"/) || [])[1] || '');
  Object.entries(fields).forEach(([k, v]) => fd.set(k, v));
  return (await fetch(url, { method: 'POST', body: fd })).url;
}, url, fields, tokenPage);

(async () => {
  sql(`DELETE FROM venue_ads WHERE venue_id IN (${TEST_VENUES})`);
  const lastNotification = Number(sql('SELECT IFNULL(MAX(id), 0) FROM notifications'));
  const notifications = () => sql(`SELECT CONCAT_WS('|', user_id, type, data) FROM notifications WHERE id > ${lastNotification} ORDER BY id`);
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });

  try {
    setPlan('premium');
    const idA = addAd(A, 'Sexta do society');
    const idB = addAd(B, 'Beach em dobro', { status: 'paused' });
    const idC = addAd(C, 'Clay agendado', { starts: 2, ends: 6 });
    const idD = addAd(D, 'Duda encerrado', { status: 'ended' });

    // --- Painel do admin: card Anúncios ---
    const a = await login(b, 'admin@teste.local');
    await go(a, `${BASE}/admin`);
    const mine = (await rows(a)).filter(([id]) => [idA, idB, idC, idD].includes(id));
    check('card Anúncios: ativo, pausado e agendado (sem o encerrado)', mine.sort((x, y) => x[0] - y[0]), [[idA, 'running'], [idB, 'paused'], [idC, 'scheduled']].sort((x, y) => x[0] - y[0]));
    check('linha mostra título, quadra com link para a aba e situação', await a.$eval(`[data-ad-row="${idA}"]`, (e, A) => [
      e.innerText.includes('Sexta do society'), e.querySelector('a').getAttribute('href').endsWith(`/admin/quadras/${A}/anuncio`), e.innerText.includes('No ar')], A), [true, true, true]);
    check('formulário de remover começa fechado', await a.$eval(`[data-ad-row="${idA}"] details`, d => d.open), false);

    // Motivo vazio: o navegador não deixa enviar
    await a.click(`[data-ad-row="${idA}"] summary`);
    await a.click(`[data-ad-row="${idA}"] [data-ad-remove]`);
    await new Promise(r => setTimeout(r, 400));
    check('motivo vazio: nada enviado (sem confirmação)', [!!(await a.$('.kolae-modal')), adStatus(idA)], [false, 'active|NULL']);

    // O servidor confere de novo
    const curto = await postDirect(a, `${BASE}/admin/anuncios/${idA}/remover`, { reason: 'ruim' }, `${BASE}/admin`);
    check('servidor: motivo curto volta com erro e o formulário aberto', [curto.includes('error=ad_reason_too_short'), curto.includes(`remover=${idA}`), adStatus(idA)], [true, true, 'active|NULL']);
    await go(a, curto);
    check('aviso do motivo curto e formulário daquele anúncio aberto', [await toast(a), await a.$eval(`[data-ad-row="${idA}"] details`, d => d.open)], ['O motivo precisa de pelo menos 5 caracteres.', true]);
    const longo = await postDirect(a, `${BASE}/admin/anuncios/${idA}/remover`, { reason: 'x'.repeat(201) }, `${BASE}/admin`);
    check('servidor: motivo com 201 caracteres não passa', [longo.includes('error=ad_reason_too_long'), adStatus(idA)], [true, 'active|NULL']);

    // Remove com motivo, pela tela, com a confirmação do Kolae
    await go(a, `${BASE}/admin`);
    await a.click(`[data-ad-row="${idA}"] summary`);
    await a.type(`#reason-${idA}`, '  O texto promete   um desconto que a quadra não oferece. ');
    await a.click(`[data-ad-row="${idA}"] [data-ad-remove]`);
    await a.waitForSelector('.kolae-modal [data-ok]', { timeout: 3000 });
    check('pede confirmação antes de remover', await a.$eval('.kolae-modal [data-ok]', e => e.textContent.trim()), 'Remover');
    await Promise.all([a.waitForNavigation({ waitUntil: 'networkidle2' }), a.click('.kolae-modal [data-ok]')]);
    check('remove com o aviso e some do card', [await toast(a), (await rows(a)).some(([id]) => id === idA)], ['Anúncio removido. O dono foi avisado.', false]);
    check('banco: removido com o motivo limpo', adStatus(idA), 'removed|O texto promete um desconto que a quadra não oferece.');
    const notif = notifications().split('|');
    check('notificação ad_removed para o dono', [Number(notif[0]), notif[1]], [ownerId, 'ad_removed']);
    check('notificação guarda quadra, anúncio e motivo', JSON.parse(notif.slice(2).join('|')), { venue_id: A, venue_name: 'Arena Teste S3', ad_id: idA, ad_title: 'Sexta do society', reason: 'O texto promete um desconto que a quadra não oferece.' });
    check('sai do ar: API sem ad', (await (await fetch(`${BASE}/api/v1/venues/${A}`)).json()).data.ad, null);

    // Remover de novo não cria outra notificação
    const deNovo = await postDirect(a, `${BASE}/admin/anuncios/${idA}/remover`, { reason: 'Mais uma vez, por engano' }, `${BASE}/admin`);
    check('remover de novo: erro e uma notificação só', [deNovo.includes('error=ad_failed'), notifications().split('\n').length], [true, 1]);
    const naoExiste = await postDirect(a, `${BASE}/admin/anuncios/99999999/remover`, { reason: 'Não existe esse' }, `${BASE}/admin`);
    check('anúncio que não existe: erro', naoExiste.includes('error=ad_failed'), true);
    check('nenhum erro de JavaScript (admin)', a.jsErrors, []);

    // --- Dono: vê o motivo e pode criar outro; não remove pelo admin ---
    const d = await login(b, 'dono-s3@kolae.test');
    await go(d, `${BASE}/dashboard/quadras/${A}/anuncio`);
    check('dono vê o motivo na aba Anúncio', await d.$eval('#ad-removed', e => e.innerText.includes('O texto promete um desconto que a quadra não oferece.')).catch(() => false), true);
    check('dono pode criar outro anúncio', !!(await d.$('#ad-form')), true);
    await postDirect(d, `${BASE}/admin/anuncios/${idB}/remover`, { reason: 'Dono tentando remover' }, `${BASE}/dashboard/quadras/editar/${A}`);
    check('dono não usa a rota do admin', adStatus(idB), 'paused|NULL');

    // --- Celular ---
    const m = await login(b, 'admin@teste.local', { width: 390, height: 844, isMobile: true, hasTouch: true });
    await go(m, `${BASE}/admin`);
    await m.click(`[data-ad-row="${idB}"] summary`);
    check('celular: sem rolagem para o lado', await m.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
    await (await m.$('#anuncios')).screenshot({ path: path.join(lib.OUT, 'anuncio_admin_celular.png') });
    await go(a, `${BASE}/admin`);
    await (await a.$('#anuncios')).screenshot({ path: path.join(lib.OUT, 'anuncio_admin.png') });
  } finally {
    // Deixa como encontrou
    sql(`DELETE FROM venue_ads WHERE venue_id IN (${TEST_VENUES})`);
    sql(`DELETE FROM notifications WHERE id > ${lastNotification} AND user_id=${ownerId}`);
    setPlan('free');
    await b.close();
  }
  console.log(fail ? `\n${fail} FALHA(S)` : '\nTodos os passos passaram');
  process.exit(fail ? 1 : 0);
})();
