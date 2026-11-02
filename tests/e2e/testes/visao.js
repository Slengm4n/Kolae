// Teste de clique da aba Visão geral (D47). Uso: node tests/e2e/rodar.js visao (ou direto: node tests/e2e/testes/visao.js <id Arena> <id Duda>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const BASE = lib.BASE;
const [ARENA, DUDA] = process.argv.slice(2);
const DIR = lib.FOTOS;
const foto = n => path.join(DIR, `foto${n}.${n % 2 ? 'jpg' : 'png'}`);
const sql = q => execFileSync(lib.MYSQL,
  ['--default-character-set=utf8mb4', '-uroot', '-N', lib.DB, '-e', q]).toString().split(String.fromCharCode(13)).join('').trim();

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};
const sleep = ms => new Promise(r => setTimeout(r, ms));

(async () => {
  sql(`DELETE FROM venues_images WHERE venue_id IN (${ARENA}, ${DUDA})`);
  fs.rmSync(lib.ROOT + '/uploads/venues/' + ARENA, { recursive: true, force: true });
  sql(`UPDATE venues SET description='Quadra society com vestiario.\nEstacionamento gratis.', average_price_per_hour=120, is_covered=1, has_lighting=1, has_leisure_area=1, leisure_area_capacity=30, court_capacity=14, floor_type='synthetic' WHERE id=${ARENA}`);

  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  const p = await b.newPage();
  const errs = [];
  p.on('pageerror', e => errs.push(e.message));
  await p.setViewport({ width: 1366, height: 900 });
  const go = url => p.goto(url, { waitUntil: 'networkidle2' });
  const text = () => p.$eval('main', m => m.innerText);

  await go(BASE + '/login?lang=pt-br');
  await p.evaluate(() => localStorage.setItem('theme', 'light'));
  await p.type('#email', 'dono-s3@kolae.test'); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);

  // Painel: o card abre a Visão geral
  const cardLink = await p.$eval(`a[href$="/quadras/${ARENA}/visao-geral"]`, a => a.href).catch(() => null);
  check('card do painel leva à Visão geral', !!cardLink, true);
  await go(cardLink);
  const tabs = await p.$$eval('nav[aria-label] a, nav[aria-label] span[aria-disabled]', els => els.map(e => e.textContent.trim()));
  check('abas na ordem', tabs.slice(0, 5), ['Visão geral', 'Dados', 'Fotos', 'Horário', 'Agenda']);
  check('Visão geral marcada', await p.$eval('[aria-current="page"]', e => e.textContent.trim()), 'Visão geral');
  check('sem fotos: convite para adicionar', await p.$$eval(`main a[href$="/quadras/${ARENA}/fotos"]`, as => as.some(a => a.innerText.includes("Adicionar fotos"))), true);

  // Informações como no app
  const t = await text();
  check('preço R$ 120,00 / hora', t.includes('R$ 120,00') && t.includes('/ hora'), true);
  check('estrutura: piso, coberta, iluminação, capacidade, lazer', ['Grama Sintética', 'Coberta', 'Com iluminação', 'Até 14 pessoas na quadra', 'Área de lazer para 30 pessoas'].every(s => t.includes(s)), true);
  check('descrição com quebra de linha', t.includes('Quadra society com vestiario.\nEstacionamento gratis.'), true);
  const sportsDb = sql(`SELECT COUNT(*) FROM venue_sports vs JOIN sports s ON s.id=vs.sport_id AND s.status='active' WHERE vs.venue_id=${ARENA}`);
  check('chips de esporte = banco', String(await p.$$eval('main section ul.flex li', els => els.length)), sportsDb);
  check('horário da semana aparece', t.includes('Horário de funcionamento') && /\d{2}:\d{2}–\d{2}:\d{2}/.test(t), true);
  check('link do Google Maps com as coordenadas', await p.$eval('a[href*="google.com/maps"]', a => /query=-?\d/.test(a.href)), true);
  check('hoje: partida em andamento com "Agora"', await p.$eval('aside', a => a.innerText.includes('Time do Carlos') && a.innerText.includes('Agora')), true);
  check('botão Abrir agenda', await p.$eval('aside a[href$="/agenda"]', a => a.textContent.includes('Abrir agenda')), true);

  // Checklist: sem fotos e (dono do seed) com ou sem WhatsApp
  const hasWhats = sql(`SELECT IF(whatsapp IS NULL OR whatsapp='', 0, 1) FROM users WHERE email='dono-s3@kolae.test'`) === '1';
  const expectedDone = 4 + (hasWhats ? 1 : 0) + (sportsDb !== '0' ? 0 : -1); // horário, esportes, descrição, preço (+ WhatsApp) — fotos falta
  check('checklist conta o que está pronto', await p.$eval('aside', a => (a.innerText.match(/(\d) de 6/) || [])[1]), String(expectedDone));
  check('item que falta leva à aba certa (fotos)', await p.$eval('aside ul a[href$="/fotos"]', a => a.textContent.trim()), 'Pelo menos 3 fotos');

  // Envia 5 fotos pela aba Fotos e volta
  await go(`${BASE}/dashboard/quadras/${ARENA}/fotos`);
  await (await p.$('#images')).uploadFile(...[1, 2, 3, 4, 5].map(foto));
  await p.waitForSelector('#chosen:not(.hidden)');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#upload-btn')]);
  await go(`${BASE}/dashboard/quadras/${ARENA}/visao-geral`);
  check('grade: 1 grande + 4 menores', await p.$$eval('.md\\:grid .open-photo', els => els.length), 5);
  check('botão "Ver as 5 fotos"', await p.$eval('main', m => m.innerText.includes('Ver as 5 fotos')), true);
  check('checklist marca as fotos', await p.$eval('aside', a => !a.querySelector('ul a[href$="/fotos"]')), true);
  await p.screenshot({ path: lib.OUT + '/visao-desktop.png' });
  await p.screenshot({ path: lib.OUT + '/visao-desktop-inteira.png', fullPage: true });

  // Tela cheia: abre na foto clicada, setas, teclado, Esc devolve o foco
  await p.click('.md\\:grid .open-photo[data-index="2"]');
  await sleep(300);
  check('tela cheia abre', await p.$eval('#lightbox', e => !e.classList.contains('hidden')), true);
  check('abre na foto clicada (3 / 5)', await p.$eval('#lightbox-counter', e => e.textContent), '3 / 5');
  check('foco vai para o Fechar', await p.evaluate(() => document.activeElement.id), 'lightbox-close');
  check('página atrás não rola', await p.evaluate(() => document.body.style.overflow), 'hidden');
  await p.click('#lightbox-next'); await sleep(700);
  check('seta Próxima (4 / 5)', await p.$eval('#lightbox-counter', e => e.textContent), '4 / 5');
  await p.keyboard.press('ArrowRight'); await sleep(700);
  check('tecla → (5 / 5)', await p.$eval('#lightbox-counter', e => e.textContent), '5 / 5');
  await p.keyboard.press('ArrowRight'); await sleep(700);
  check('não passa da última', await p.$eval('#lightbox-counter', e => e.textContent), '5 / 5');
  await p.keyboard.press('ArrowLeft'); await sleep(700);
  check('tecla ← (4 / 5)', await p.$eval('#lightbox-counter', e => e.textContent), '4 / 5');
  await p.screenshot({ path: lib.OUT + '/visao-tela-cheia.png' });
  await p.keyboard.press('Escape'); await sleep(200);
  check('Esc fecha', await p.$eval('#lightbox', e => e.classList.contains('hidden')), true);
  check('foco volta para a foto clicada', await p.evaluate(() => document.activeElement.dataset.index), '2');

  // Quadra sem nada (Duda): estados vazios
  await go(`${BASE}/dashboard/quadras/${DUDA}/visao-geral`);
  const d = await text();
  check('Duda abre sem erro', d.includes('Visão geral'), true);

  // Admin
  const a = await (await b.createBrowserContext()).newPage();
  await a.goto(BASE + '/login', { waitUntil: 'networkidle2' });
  await a.type('#email', 'admin@teste.local'); await a.type('#password', 'teste123');
  await Promise.all([a.waitForNavigation({ waitUntil: 'networkidle2' }), a.$eval('#login-form', f => f.submit())]);
  await a.goto(`${BASE}/admin/quadras`, { waitUntil: 'networkidle2' });
  check('lista do admin tem o atalho Visão geral', !!(await a.$(`a[href$="/admin/quadras/${ARENA}/visao-geral"]`)), true);
  await a.goto(`${BASE}/admin/quadras/${ARENA}/visao-geral`, { waitUntil: 'networkidle2' });
  check('admin abre a Visão geral', await a.$$eval('.md\\:grid .open-photo', els => els.length), 5);

  // Outro dono
  const o = await (await b.createBrowserContext()).newPage();
  await o.goto(BASE + '/login', { waitUntil: 'networkidle2' });
  await o.type('#email', 'dono@teste.local'); await o.type('#password', 'teste123');
  await Promise.all([o.waitForNavigation({ waitUntil: 'networkidle2' }), o.$eval('#login-form', f => f.submit())]);
  await o.goto(`${BASE}/dashboard/quadras/${ARENA}/visao-geral`, { waitUntil: 'networkidle2' });
  // Volta ao painel com o aviso (a URL fica limpa depois de mostrar, ui.js)
  await o.waitForSelector('.kolae-toast', { timeout: 3000 }).catch(() => {});
  check('outro dono é mandado de volta, com aviso', [new URL(o.url()).pathname, await o.$eval('.kolae-toast-text', e => e.textContent).catch(() => '')], [lib.BASE_PATH + '/dashboard', 'Não encontramos essa quadra na sua conta.']);

  // Celular: carrossel de arrastar
  await p.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
  await p.evaluate(() => localStorage.setItem('theme', 'dark'));
  await go(`${BASE}/dashboard/quadras/${ARENA}/visao-geral`);
  check('celular: carrossel com 5 fotos', await p.$$eval('#carousel .open-photo', els => els.length), 5);
  check('celular: contador 1 / 5', await p.$eval('#carousel-counter', e => e.textContent), '1 / 5');
  await p.$eval('#carousel', c => c.scrollTo({ left: c.clientWidth * 2 }));
  await sleep(600);
  check('celular: arrastar atualiza o contador (3 / 5)', await p.$eval('#carousel-counter', e => e.textContent), '3 / 5');
  await p.screenshot({ path: lib.OUT + '/visao-celular-escuro.png' });
  await p.tap('#carousel .open-photo[data-index="2"]');
  await sleep(400);
  check('celular: toque abre na foto que está aparecendo', await p.$eval('#lightbox-counter', e => e.textContent), '3 / 5');
  await p.click('#lightbox-close');
  check('celular: sem rolagem horizontal', await p.evaluate(() => document.documentElement.scrollWidth), 390);
  await p.screenshot({ path: lib.OUT + '/visao-celular-inteira.png', fullPage: true });
  await p.evaluate(() => localStorage.setItem('theme', 'light'));

  check('sem erros de JS', errs, []);
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
})();
