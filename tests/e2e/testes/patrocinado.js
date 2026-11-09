// Teste de clique do "Patrocinado" no site (D63, parte C): destaques no topo da home e da /quadras
// (até 3, iguais o dia todo), faixa na página pública, quem aparece e quem não (pausado, agendado,
// quadra desativada, Premium vencido), vezes que apareceu e cliques (sem contar o dono, robô e
// repetição), link limpo depois do clique, celular e inglês.
// Uso: node tests/e2e/rodar.js patrocinado (ou direto: node tests/e2e/testes/patrocinado.js <ids A B C D E>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const path = require('path');
const { execFileSync } = require('child_process');
const { devNull } = require('os'); // o curl do Windows não aceita -o /dev/null
const BASE = lib.BASE;
const [A, B, C, D, E] = process.argv.slice(2).map(Number);
const sql = lib.sql;
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};
const ymd = (days = 0) => { const d = new Date(Date.now() - 3 * 3600e3); d.setUTCDate(d.getUTCDate() + days); return d.toISOString().slice(0, 10); }; // hoje em Brasília
const TEST_VENUES = [A, B, C, D, E].join(',');
const owner = `(SELECT user_id FROM venues WHERE id=${A})`;
const setPlan = (plan, until = 'NULL') => sql(`UPDATE users SET plan='${plan}', plan_until=${until} WHERE id=${owner}`);
const addAd = (venue, title, { starts = 0, ends = 5, status = 'active' } = {}) => {
  sql(`INSERT INTO venue_ads (venue_id, title, body, starts_on, ends_on, status) VALUES (${venue}, '${title}', 'Texto de ${title}', '${ymd(starts)}', '${ymd(ends)}', '${status}')`);
  return Number(sql(`SELECT MAX(id) FROM venue_ads WHERE venue_id=${venue}`));
};
const events = (venue, type) => Number(sql(`SELECT COUNT(*) FROM venue_events WHERE venue_id=${venue} AND type='${type}'`));
const curl = args => execFileSync('curl', ['-s', ...args]).toString();

async function newPage(b, { viewport = { width: 1366, height: 900 }, ua = UA } = {}) {
  const ctx = await b.createBrowserContext();
  const p = await ctx.newPage();
  if (ua) await p.setUserAgent(ua); // o filtro de robôs ignora o HeadlessChrome
  await p.setViewport(viewport);
  p.jsErrors = [];
  p.on('pageerror', e => p.jsErrors.push(e.message));
  return p;
}
const go = (p, url) => p.goto(url, { waitUntil: 'networkidle2' });
const shown = p => p.$$eval('#ad-highlights [data-ad-id]', els => els.map(e => Number(e.dataset.adId)));
const card = (p, id) => p.$eval(`#ad-highlights [data-ad-id="${id}"] [data-ad-card]`, c => ({
  tag: c.tagName, href: c.getAttribute('href'), title: c.querySelector('[data-ad-title]').textContent,
  venue: c.querySelector('[data-ad-venue]').textContent, sponsored: c.innerText.includes('PATROCINADO') || c.innerText.includes('Patrocinado'),
}));

(async () => {
  // Outros anúncios no ar no banco local ficam pausados durante o teste e voltam no fim
  const others = sql(`SELECT GROUP_CONCAT(id) FROM venue_ads WHERE status='active' AND venue_id NOT IN (${TEST_VENUES})`);
  const othersIds = others && others !== 'NULL' ? others : '';
  if (othersIds) sql(`UPDATE venue_ads SET status='paused' WHERE id IN (${othersIds})`);
  sql(`DELETE FROM venue_ads WHERE venue_id IN (${TEST_VENUES})`);
  sql(`DELETE FROM venue_events WHERE venue_id IN (${TEST_VENUES})`);
  const nameA = sql(`SELECT name FROM venues WHERE id=${A}`); // o teste do celular usa um nome comprido
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });

  try {
    const idA = addAd(A, 'Sexta do society');
    const idB = addAd(B, 'Beach em dobro');
    const idC = addAd(C, 'Clay em reforma'); // quadra em manutenção continua mostrando
    const idD = addAd(D, 'Duda sem mapa');
    const idE = addAd(E, 'Excluida nao aparece'); // quadra desativada esconde (D34)
    const p = await newPage(b);

    // --- Grátis: nada aparece ---
    setPlan('free');
    await go(p, `${BASE}/?lang=pt-br`);
    check('Grátis: sem destaques na home', !!(await p.$('#ad-highlights')), false);
    await go(p, `${BASE}/quadra/${A}`);
    check('Grátis: sem faixa na página da quadra', !!(await p.$('#venue-ad')), false);

    // --- Premium: até 3, iguais o dia todo ---
    setPlan('premium');
    await go(p, `${BASE}/`);
    const home = await shown(p);
    check('home: 3 destaques (há 4 no ar)', home.length, 3);
    check('home: só anúncios no ar (nunca o da quadra desativada)', home.every(id => [idA, idB, idC, idD].includes(id)), true);
    check('home: título "Destaques" logo depois da abertura', await p.$eval('#ad-highlights h2', h => h.textContent.trim()), 'Destaques');
    await go(p, `${BASE}/`);
    check('home: mesmo sorteio ao recarregar', await shown(p), home);
    await go(p, `${BASE}/quadras`);
    check('/quadras: os mesmos 3, no topo da lista', [await shown(p), await p.$eval('main', m => m.firstElementChild.id)], [home, 'ad-highlights']);
    await go(p, `${BASE}/quadras?agora=1`);
    check('/quadras: continua com filtro', (await shown(p)).length, 3);

    // Com só 2 no ar, aparecem os 2
    sql(`UPDATE venue_ads SET status='paused' WHERE id=${idB}`);
    addAd(D, 'Duda agendado', { starts: 2, ends: 6 }); // começa depois: não aparece, e o mais novo da quadra não esconde o atual
    sql(`UPDATE venue_ads SET status='ended' WHERE id=${idD}`);
    await go(p, `${BASE}/`);
    check('pausado, encerrado e agendado não aparecem', (await shown(p)).sort(), [idA, idC].sort());
    const cA = await card(p, idA);
    check('card: link, título, quadra e selo', [cA.tag, cA.href, cA.title, cA.venue.startsWith('Arena Teste S3'), cA.sponsored], ['A', `${BASE}/quadra/${A}?ref=ad`, 'Sexta do society', true, true]);

    // --- Vezes que apareceu e clique ---
    sql(`DELETE FROM venue_events WHERE venue_id IN (${TEST_VENUES})`);
    const v = await newPage(b, { ua: UA + ' Visitante' });
    await go(v, `${BASE}/`);
    check('home conta uma vez que apareceu para cada destaque', [events(A, 'ad_view'), events(C, 'ad_view'), events(B, 'ad_view')], [1, 1, 0]);
    await go(v, `${BASE}/quadras`);
    check('mesmo visitante logo depois na /quadras não conta de novo', events(A, 'ad_view'), 1);
    await Promise.all([v.waitForNavigation({ waitUntil: 'networkidle2' }), v.click(`#ad-highlights [data-ad-id="${idA}"] a`)]);
    check('clique: abre a quadra com o endereço limpo', new URL(v.url()).pathname.replace(lib.BASE_PATH, '') + new URL(v.url()).search, `/quadra/${A}`);
    check('clique: conta 1 clique e a visita da página', [events(A, 'ad_click'), events(A, 'view')], [1, 1]);
    check('página da quadra: faixa do anúncio', await v.$eval('#venue-ad', e => [e.dataset.adId, e.innerText.includes('Sexta do society'), e.innerText.includes('Texto de Sexta do society')]), [String(idA), true, true]);
    check('faixa: trocar o idioma não leva o ?ref=ad', await v.$$eval('a[href*="lang="]', as => as.some(a => a.href.includes('ref=ad'))), false);
    await go(v, `${BASE}/quadra/${A}?ref=ad`);
    check('clique repetido logo depois não conta de novo', events(A, 'ad_click'), 1);

    // Robô e quadra sem anúncio
    curl(['-o', devNull, `${BASE}/`]);
    curl(['-o', devNull, `${BASE}/quadra/${A}?ref=ad`]);
    check('robô (curl) não conta aparição nem clique', [events(A, 'ad_view'), events(A, 'ad_click')], [1, 1]);
    curl(['-o', devNull, '-A', UA + ' Outro', `${BASE}/quadra/${B}?ref=ad`]);
    check('?ref=ad em quadra sem anúncio no ar não conta clique', events(B, 'ad_click'), 0);
    await go(v, `${BASE}/quadra/${B}`);
    check('quadra com anúncio pausado: sem faixa', !!(await v.$('#venue-ad')), false);

    // O dono não infla os números
    const dono = await newPage(b);
    await go(dono, `${BASE}/login?lang=pt-br`);
    await dono.type('#email', 'dono-s3@kolae.test'); await dono.type('#password', 'teste123');
    await Promise.all([dono.waitForNavigation({ waitUntil: 'networkidle2' }), dono.$eval('#login-form', f => f.submit())]);
    sql(`DELETE FROM venue_events WHERE venue_id IN (${TEST_VENUES})`);
    await go(dono, `${BASE}/`);
    await go(dono, `${BASE}/quadra/${A}?ref=ad`);
    check('dono logado vê o destaque, mas não conta', [events(A, 'ad_view'), events(A, 'ad_click')], [0, 0]);
    check('números chegam na aba Anúncio do dono', await (async () => {
      sql(`INSERT INTO venue_events (venue_id, type, source, visitor) VALUES (${A}, 'ad_view', 'site', 'e2e1'), (${A}, 'ad_view', 'site', 'e2e2'), (${A}, 'ad_click', 'site', 'e2e1')`);
      await go(dono, `${BASE}/dashboard/quadras/${A}/anuncio`);
      return dono.$eval('#ad-numbers', e => e.innerText.replace(/\s+/g, ' ').trim());
    })().then(t => [t.includes('2'), t.includes('1'), t.includes('50')]), [true, true, true]);

    // --- Premium vencido: some tudo ---
    setPlan('premium', `'${ymd(-1)}'`);
    await go(v, `${BASE}/`);
    check('Premium vencido: sem destaques e sem faixa', [!!(await v.$('#ad-highlights')), await go(v, `${BASE}/quadra/${A}`).then(() => v.$('#venue-ad')).then(Boolean)], [false, false]);
    setPlan('premium');

    // --- Celular e inglês ---
    const m = await newPage(b, { viewport: { width: 390, height: 844, isMobile: true, hasTouch: true } });
    // Nome comprido de quadra: o card não pode passar da tela (a home esconde a rolagem, então mede o card)
    sql(`UPDATE venues SET name='Arena Teste S3 Society e Futevolei do Bairro' WHERE id=${A}`);
    const fits = () => m.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth
      && [...document.querySelectorAll('[data-ad-card]')].every(c => c.getBoundingClientRect().right <= window.innerWidth));
    await go(m, `${BASE}/?lang=pt-br`);
    check('celular: home sem rolagem e card dentro da tela', await fits(), true);
    await (await m.$('#ad-highlights')).screenshot({ path: path.join(lib.OUT, 'patrocinado_celular.png') });
    await go(m, `${BASE}/quadras`);
    check('celular: /quadras sem rolagem e card dentro da tela', await fits(), true);
    await go(m, `${BASE}/quadra/${A}`);
    check('celular: página da quadra sem rolagem para o lado', await m.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
    await (await m.$('#venue-ad')).screenshot({ path: path.join(lib.OUT, 'patrocinado_faixa.png') });
    await go(m, `${BASE}/?lang=en-us`);
    check('inglês: "Featured" e "Sponsored"', [await m.$eval('#ad-highlights h2', h => h.textContent.trim()), (await m.$eval('#ad-highlights', e => e.innerText)).toLowerCase().includes('sponsored')], ['Featured', true]);
    await go(m, `${BASE}/?lang=pt-br`);
    await go(p, `${BASE}/`);
    await (await p.$('#ad-highlights')).screenshot({ path: path.join(lib.OUT, 'patrocinado_home.png') });
    check('nenhum erro de JavaScript', [...p.jsErrors, ...v.jsErrors, ...m.jsErrors, ...dono.jsErrors], []);
  } finally {
    // Deixa como encontrou
    sql(`DELETE FROM venue_ads WHERE venue_id IN (${TEST_VENUES})`);
    sql(`DELETE FROM venue_events WHERE venue_id IN (${TEST_VENUES})`);
    setPlan('free');
    sql(`UPDATE venues SET name='${nameA.replace(/'/g, "''")}' WHERE id=${A}`);
    if (othersIds) sql(`UPDATE venue_ads SET status='active' WHERE id IN (${othersIds})`);
    await b.close();
  }
  console.log(fail ? `\n${fail} FALHA(S)` : '\nTodos os passos passaram');
  process.exit(fail ? 1 : 0);
})();
