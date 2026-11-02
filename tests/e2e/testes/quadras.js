// Teste da página pública Quadras (D51). Uso: node tests/e2e/rodar.js quadras (ou direto: node tests/e2e/testes/quadras.js <id Arena>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const { execFileSync } = require('child_process');
const BASE = lib.BASE;
const [ARENA] = process.argv.slice(2);
const sql = q => execFileSync(lib.MYSQL,
  ['--default-character-set=utf8mb4', '-uroot', '-N', lib.DB, '-e', q]).toString().split(String.fromCharCode(13)).join('').trim();
const apiList = qs => JSON.parse(execFileSync('curl', ['-s', `${BASE}/api/v1/venues?per_page=100&${qs}`]).toString()).data;

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};
const sleep = ms => new Promise(r => setTimeout(r, ms));

(async () => {
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  const ctx = await b.createBrowserContext();
  await ctx.overridePermissions(BASE, ['geolocation']);
  const p = await ctx.newPage();
  const errs = [];
  p.on('pageerror', e => errs.push(e.message));
  await p.setViewport({ width: 1366, height: 900 });
  const go = url => p.goto(url, { waitUntil: 'networkidle2' });
  const ids = () => p.$$eval('.venue-card', els => els.map(e => Number(e.dataset.venueId)));
  const nav = sel => Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval(sel, el => el.click())]);

  // Links que levam à página
  await go(BASE + '/?lang=pt-br');
  await p.evaluate(() => localStorage.setItem('theme', 'light'));
  check('menu "Quadras" do cabeçalho abre /quadras', await p.$$eval('[data-site-header] a', as => as.some(a => a.getAttribute('href').endsWith('/quadras'))), true);
  check('home: "Ver todas as quadras" abre /quadras', await p.$eval('#quadras a[href$="/quadras"]', a => a.textContent.trim()).catch(() => null), 'Ver todas as quadras');
  check('rodapé também abre /quadras', await p.$$eval('footer a', as => as.some(a => a.getAttribute('href').endsWith('/quadras'))), true);
  await go(`${BASE}/quadra/${ARENA}`);
  check('página da quadra: "Ver todas as quadras" volta para /quadras', await p.$eval('section a[href$="/quadras"]', a => !!a).catch(() => false), true);

  // Lista igual à da API
  await go(BASE + '/quadras');
  const all = apiList('');
  check('título', await p.title(), 'Quadras · Kolae');
  check('mesmas quadras da API, na mesma ordem', await ids(), all.map(v => v.id));
  check('contador', await p.$eval('main p[aria-live]', e => e.textContent.trim()), `${all.length} quadras`);
  check('cada card abre a página pública', await p.$$eval('.venue-card h2 a', as => as.every(a => /\/quadra\/\d+$/.test(a.getAttribute('href')))), true);
  const arena = all.find(v => v.id === Number(ARENA));
  const label = { available: 'Disponível', in_use: 'Em uso', closed: 'Fechada' }[arena.status];
  check('selo de status da Arena igual ao da API', await p.$eval(`.venue-card[data-venue-id="${ARENA}"] .status-badge`, (e, l) => e.textContent.includes(l), label), true);

  // Mapa
  await sleep(800);
  const withCoords = all.filter(v => v.latitude !== null).length;
  check('mapa carregou (Leaflet)', await p.evaluate(() => !!document.querySelector('#venues-map .leaflet-tile-pane')), true);
  check('um ponto por quadra com coordenadas', await p.$$eval('.venue-pin', els => els.length), withCoords);
  check('pontos aparecem de verdade (com tamanho)', await p.$$eval('.venue-pin', els => els.every(e => e.getBoundingClientRect().width >= 16)), true);
  await p.hover(`.venue-card[data-venue-id="${ARENA}"]`);
  check('passar o mouse no card destaca o ponto', await p.$$eval('.venue-pin.is-active', els => els.length), 1);
  await p.screenshot({ path: lib.OUT + '/quadras-desktop.png' });

  // Filtros (aplicam sozinhos ao mudar)
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('input[name=agora] + span')]);
  check('"Disponível agora" vai para a URL', new URL(p.url()).searchParams.get('agora'), '1');
  check('"Disponível agora" = status available da API', await ids(), apiList('status=available').map(v => v.id));
  check('aparece "Limpar filtros"', await p.$eval('#filters a', a => a.textContent.trim()), 'Limpar filtros');
  await nav('#filters a');
  check('"Limpar filtros" volta à lista completa', [new URL(p.url()).search, (await ids()).length], ['', all.length]);

  const sportId = sql(`SELECT sport_id FROM venue_sports WHERE venue_id=${ARENA} LIMIT 1`);
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.select('#sport_id', sportId)]);
  check('filtro de esporte = API', await ids(), apiList(`sport_id=${sportId}`).map(v => v.id));
  await go(BASE + '/quadras');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.select('#preco', '100')]);
  check('preço até R$ 100 = API (max_price_cents=10000)', await ids(), apiList('max_price_cents=10000').map(v => v.id));
  await go(BASE + '/quadras?coberta=1&iluminacao=1');
  check('coberta + iluminação = API', await ids(), apiList('is_covered=true&has_lighting=true').map(v => v.id));

  await go(BASE + '/quadras');
  await p.type('#q', 'Arena Teste');
  await nav('#filters button[type=submit]');
  check('busca pelo nome', await ids(), [Number(ARENA)]);
  await go(BASE + '/quadras?q=zzzzzz');
  check('sem resultado: estado vazio', await p.$eval('main', m => m.innerText.includes('Nenhuma quadra encontrada')), true);
  check('sem resultado: sem mapa', !!(await p.$('#venues-map')), false);
  await go(BASE + '/quadras?sport_id=abc&preco=999&lat=x&agora=talvez');
  check('filtros inválidos são ignorados', (await ids()).length, all.length);

  // Perto de mim
  await go(BASE + '/quadras');
  await p.setGeolocation({ latitude: -23.5406, longitude: -46.3110 });
  await nav('#near-btn');
  const url = new URL(p.url());
  check('"Perto de mim" manda lat e lng', [url.searchParams.has('lat'), url.searchParams.has('lng')], [true, true]);
  check('botão vira "Mais perto primeiro"', await p.$eval('#near-btn .label', e => e.textContent), 'Mais perto primeiro');
  const dist = await p.$$eval('.venue-card', els => els.map(e => e.querySelector('.absolute.right-3')?.textContent.trim() ?? null));
  const nums = dist.filter(Boolean).map(t => parseFloat(t.replace(',', '.')));
  check('cards mostram a distância, da menor para a maior', nums.length > 0 && nums.every((n, i) => i === 0 || n >= nums[i - 1]), true);
  check('ponto "Você está aqui" no mapa', !!(await p.$('.me-pin')), true);
  await nav('#near-btn');
  check('clicar de novo desliga', new URL(p.url()).searchParams.has('lat'), false);

  // Nome malicioso no mapa e na lista
  const original = sql(`SELECT name FROM venues WHERE id=${ARENA}`);
  sql(`UPDATE venues SET name='<img src=x onerror=window.__xss=1>Arena' WHERE id=${ARENA}`);
  await go(BASE + '/quadras');
  await sleep(500);
  await p.evaluate(id => document.querySelector(`.venue-card[data-venue-id="${id}"]`) && null, ARENA);
  const pins = await p.$$('.leaflet-marker-icon');
  for (const pin of pins) { await pin.click(); await sleep(150); }
  check('nome com HTML aparece como texto (lista e mapa)', await p.evaluate(() => window.__xss === undefined), true);
  sql(`UPDATE venues SET name='Arena Teste S3' WHERE id=${ARENA}`);
  void original;

  // Inglês
  await go(BASE + '/quadras?lang=en-us');
  check('inglês', [await p.$eval('h1', e => e.textContent.trim()), await p.$eval('input[name=agora]', i => i.parentElement.textContent.trim())], ['Find your venue', 'Available now']);

  // Celular: lista e mapa alternados
  await go(BASE + '/quadras?lang=pt-br');
  await p.evaluate(() => localStorage.setItem('theme', 'dark'));
  await p.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
  await go(BASE + '/quadras');
  check('celular: mapa escondido, lista aparecendo', [await p.$eval('#map-view', e => getComputedStyle(e).display), await p.$eval('#list-view', e => getComputedStyle(e).display)], ['none', 'block']);
  check('celular: sem rolagem horizontal', await p.evaluate(() => document.documentElement.scrollWidth), 390);
  await p.screenshot({ path: lib.OUT + '/quadras-celular-lista.png' });
  await p.click('.view-btn[data-view="map"]'); await sleep(600);
  check('celular: botão Mapa mostra o mapa', [await p.$eval('#map-view', e => getComputedStyle(e).display), await p.$eval('#list-view', e => getComputedStyle(e).display)], ['block', 'none']);
  check('celular: mapa com altura', await p.$eval('#venues-map', e => e.getBoundingClientRect().height > 300), true);
  await p.screenshot({ path: lib.OUT + '/quadras-celular-mapa.png' });
  await p.evaluate(() => localStorage.setItem('theme', 'light'));

  check('sem erros de JS', errs, []);
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
})();
