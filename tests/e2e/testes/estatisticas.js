// Teste de clique das estatísticas (D62): registro de visualizações e cliques (página pública e API),
// os filtros (dono, robô, repetição) e a aba Estatísticas no Grátis e no Premium.
// Uso: node tests/e2e/rodar.js estatisticas (ou direto: node tests/e2e/testes/estatisticas.js <id Arena>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const { execFileSync } = require('child_process');
const BASE = lib.BASE;
const [ARENA] = process.argv.slice(2);
const sql = lib.sql;
// Navegador "comum": o filtro de robôs ignora o HeadlessChrome do Puppeteer
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};
const count = (type, source) => sql(`SELECT COUNT(*) FROM venue_events WHERE venue_id=${ARENA} AND type='${type}'${source ? ` AND source='${source}'` : ''}`);
const curl = (args) => execFileSync('curl', ['-s', ...args]).toString();
const postEvent = (body, ua = UA, extra = []) => curl(['-o', '/dev/null', '-w', '%{http_code}', '-A', ua, '-X', 'POST', '-H', 'Content-Type: application/json', ...extra, '-d', JSON.stringify(body), `${BASE}/api/v1/venues/${ARENA}/events`]);

async function browserPage(b, viewport = { width: 1366, height: 900 }) {
  const ctx = await b.createBrowserContext();
  const p = await ctx.newPage();
  await p.setUserAgent(UA);
  await p.setViewport(viewport);
  p.jsErrors = [];
  p.on('pageerror', e => p.jsErrors.push(e.message));
  return p;
}
async function login(p, email) {
  await p.goto(`${BASE}/login?lang=pt-br`, { waitUntil: 'networkidle2' });
  await p.evaluate(() => localStorage.setItem('theme', 'light'));
  await p.type('#email', email); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);
}

(async () => {
  sql(`DELETE FROM venue_events WHERE venue_id=${ARENA}`);
  sql(`UPDATE users SET plan='free', plan_until=NULL, plan_requested_at=NULL WHERE id=(SELECT user_id FROM venues WHERE id=${ARENA})`); // o Premium é do dono (D61)
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });

  // --- Registro pela página pública ---
  const visitante = await browserPage(b);
  await visitante.goto(`${BASE}/quadra/${ARENA}`, { waitUntil: 'networkidle2' });
  check('visita na página pública conta como "site"', count('view', 'site'), '1');
  await visitante.goto(`${BASE}/quadra/${ARENA}`, { waitUntil: 'networkidle2' });
  check('a mesma visita em menos de 30 min não conta de novo', count('view'), '1');
  await visitante.click('#share-btn').catch(() => {});
  await new Promise(r => setTimeout(r, 600));
  check('compartilhar conta como clique', count('share', 'site'), '1');
  const outro = await browserPage(b);
  await outro.setUserAgent(UA + ' Outro');
  await outro.goto(`${BASE}/quadra/${ARENA}?ref=qr`, { waitUntil: 'networkidle2' });
  check('visita pelo QR Code (?ref=qr) conta como "qr"', count('view', 'qr'), '1');
  curl(['-o', '/dev/null', '-A', 'Googlebot/2.1 (+http://www.google.com/bot.html)', `${BASE}/quadra/${ARENA}`]);
  check('robô não conta', count('view'), '2');
  check('o QR do cartaz aponta para ?ref=qr', (await (async () => {
    const dono = await browserPage(b);
    await login(dono, 'dono-s3@kolae.test');
    await dono.goto(`${BASE}/dashboard/quadras/${ARENA}/visao-geral`, { waitUntil: 'networkidle2' });
    const url = await dono.$eval('#qr-modal', () => JSON.parse(document.getElementById('qr-data')?.textContent || 'null')?.url || '').catch(() => '');
    const html = await dono.content();
    await dono.goto(`${BASE}/quadra/${ARENA}`, { waitUntil: 'networkidle2' });
    return url.endsWith('?ref=qr') || html.includes(`/quadra/${ARENA}?ref=qr`);
  })()), true);
  check('o dono abrindo a própria página não conta', count('view'), '2');

  // --- Registro pela API ---
  check('POST /events: clique no WhatsApp responde 204', postEvent({ type: 'whatsapp', source: 'app' }), '204');
  check('POST /events: gravado como app', count('whatsapp', 'app'), '1');
  check('POST /events: tipo inválido responde 422', postEvent({ type: 'xyz' }), '422');
  check('POST /events: "view" não é aceito (vem do GET)', postEvent({ type: 'view' }), '422');
  check('POST /events: quadra inexistente responde 404', curl(['-o', '/dev/null', '-w', '%{http_code}', '-X', 'POST', '-H', 'Content-Type: application/json', '-d', '{"type":"share"}', `${BASE}/api/v1/venues/99999999/events`]), '404');
  curl(['-o', '/dev/null', '-A', 'okhttp/4.12 Kolae', `${BASE}/api/v1/venues/${ARENA}`]);
  check('GET /venues/{id} conta como visualização do app', count('view', 'app'), '1');
  const token = JSON.parse(curl(['-X', 'POST', '-H', 'Content-Type: application/json', '-d', JSON.stringify({ email: 'dono-s3@kolae.test', password: 'teste123' }), `${BASE}/api/v1/auth/login`])).data.token;
  postEvent({ type: 'instagram', source: 'app' }, UA, ['-H', `Authorization: Bearer ${token}`]);
  check('com o token do dono, o clique não conta', count('instagram'), '0');

  // --- Aba Estatísticas no Grátis ---
  const dono = await browserPage(b);
  await login(dono, 'dono-s3@kolae.test');
  await dono.goto(`${BASE}/dashboard/quadras/${ARENA}/estatisticas`, { waitUntil: 'networkidle2' });
  check('aba Estatísticas ativa', await dono.$eval('#venue-tabs [aria-current=page]', a => a.textContent.trim()), 'Estatísticas');
  check('6 cartões', await dono.$$eval('[data-card]', c => c.length), 6);
  check('cartões com os números registrados', await dono.$$eval('[data-card] [data-value]', v => v.slice(0, 3).map(x => x.textContent.trim())), ['3', '3', '1']);
  check('Grátis: convite para o Premium no lugar do gráfico', [!!(await dono.$('#stats-locked')), await dono.$('#daily-chart')], [true, null]);
  check('Grátis: 28 e 90 dias levam ao Kolae Premium nas Configurações', await dono.$$eval('[data-period]', as => as.map(a => new URL(a.href).pathname.endsWith('/dashboard/perfil/plano'))), [false, true, true]);
  await dono.goto(`${BASE}/dashboard/quadras/${ARENA}/estatisticas?periodo=90`, { waitUntil: 'networkidle2' });
  check('Grátis: ?periodo=90 continua em 7 dias', await dono.$eval('[data-period][aria-current]', a => a.dataset.period), '7');

  // --- Premium ---
  sql(`UPDATE users SET plan='premium', plan_until=NULL WHERE id=(SELECT user_id FROM venues WHERE id=${ARENA})`);
  sql(`INSERT INTO venue_events (venue_id, type, source, visitor, created_at) VALUES (${ARENA}, 'view', 'site', 'antigo0000000001', NOW() - INTERVAL 40 DAY)`);
  await dono.goto(`${BASE}/dashboard/quadras/${ARENA}/estatisticas?periodo=90`, { waitUntil: 'networkidle2' });
  await dono.waitForFunction(() => window.Chart && Chart.getChart(document.getElementById('daily-chart')), { timeout: 5000 }).catch(() => {});
  check('Premium: 90 dias ativo', await dono.$eval('[data-period][aria-current]', a => a.dataset.period), '90');
  check('Premium: visita de 40 dias atrás entra nos 90 dias', await dono.$eval('[data-card="views"] [data-value]', v => v.textContent.trim()), '4');
  check('Premium: gráfico com 90 dias e as duas séries', await dono.evaluate(() => { const c = Chart.getChart(document.getElementById('daily-chart')); return [c.data.labels.length, c.data.datasets.length]; }), [90, 2]);
  check('Premium: tabela com os números por dia', await dono.$$eval('#stats-daily tbody tr', r => r.length), 90);
  check('Premium: origens site, app e QR', await dono.$$eval('[data-source]', s => s.map(x => x.dataset.source)), ['site', 'app', 'qr']);
  check('Premium: mapa de calor com 7 dias da semana', await dono.$$eval('#stats-heatmap tbody tr', r => r.length), 7);
  await dono.goto(`${BASE}/dashboard/quadras/${ARENA}/estatisticas?periodo=28`, { waitUntil: 'networkidle2' });
  check('Premium: em 28 dias a visita de 40 dias atrás fica de fora', await dono.$eval('[data-card="views"] [data-value]', v => v.textContent.trim()), '3');

  // --- Outro dono e celular ---
  const intruso = await browserPage(b);
  await login(intruso, 'dono@teste.local');
  await intruso.goto(`${BASE}/dashboard/quadras/${ARENA}/estatisticas`, { waitUntil: 'networkidle2' });
  check('outro dono é mandado de volta', new URL(intruso.url()).pathname, lib.BASE_PATH + '/dashboard');
  await dono.setViewport({ width: 390, height: 844, isMobile: true });
  await dono.goto(`${BASE}/dashboard/quadras/${ARENA}/estatisticas`, { waitUntil: 'networkidle2' });
  check('celular: sem rolagem horizontal da página', await dono.evaluate(() => document.documentElement.scrollWidth), 390);
  check('celular: aba ativa à vista na faixa de abas', await dono.$eval('#venue-tabs [aria-current=page]', a => { const n = a.parentElement; return a.offsetLeft >= n.scrollLeft && a.offsetLeft + a.offsetWidth <= n.scrollLeft + n.clientWidth + 1; }), true);
  await dono.screenshot({ path: lib.OUT + '/estatisticas-celular.png', fullPage: true });

  sql(`UPDATE users SET plan='free' WHERE id=(SELECT user_id FROM venues WHERE id=${ARENA})`);
  check('sem erros de JS', [...visitante.jsErrors, ...dono.jsErrors], []);
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
  process.exit(fail ? 1 : 0);
})();
