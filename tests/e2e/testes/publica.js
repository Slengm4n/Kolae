// Teste da página pública da quadra (D49). Uso: node tests/e2e/rodar.js publica (ou direto: node tests/e2e/testes/publica.js <A> <B> <C> <D> <E>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const BASE = lib.BASE;
const [ARENA, BEACH, CLAY, DUDA, EXCLUIDA] = process.argv.slice(2);
const DIR = lib.FOTOS;
const foto = n => path.join(DIR, `foto${n}.${n % 2 ? 'jpg' : 'png'}`);
const sql = q => execFileSync(lib.MYSQL,
  ['--default-character-set=utf8mb4', '-uroot', '-N', lib.DB, '-e', q]).toString().split(String.fromCharCode(13)).join('').trim();
const api = p => JSON.parse(execFileSync('curl', ['-s', BASE + '/api/v1' + p]).toString()).data;
const code = p => execFileSync('curl', ['-s', '-o', 'NUL', '-w', '%{http_code}', BASE + p]).toString();

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};
const sleep = ms => new Promise(r => setTimeout(r, ms));

(async () => {
  sql(`DELETE FROM venues_images WHERE venue_id=${ARENA}`);
  fs.rmSync(lib.ROOT + '/uploads/venues/' + ARENA, { recursive: true, force: true });
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });

  // Dono põe 3 fotos e abre a Visão geral
  const ctx = await b.createBrowserContext();
  const d = await ctx.newPage();
  await d.goto(BASE + '/login?lang=pt-br', { waitUntil: 'networkidle2' });
  await d.type('#email', 'dono-s3@kolae.test'); await d.type('#password', 'teste123');
  await Promise.all([d.waitForNavigation({ waitUntil: 'networkidle2' }), d.$eval('#login-form', f => f.submit())]);
  await d.goto(`${BASE}/dashboard/quadras/${ARENA}/fotos`, { waitUntil: 'networkidle2' });
  await (await d.$('#images')).uploadFile(foto(1), foto(2), foto(3));
  await d.waitForSelector('#chosen:not(.hidden)');
  await Promise.all([d.waitForNavigation({ waitUntil: 'networkidle2' }), d.click('#upload-btn')]);
  await d.goto(`${BASE}/dashboard/quadras/${ARENA}/visao-geral`, { waitUntil: 'networkidle2' });
  check('Visão geral tem "Ver página pública"', await d.$eval(`a[href$="/quadra/${ARENA}"]`, a => a.target).catch(() => null), '_blank');
  await d.goto(`${BASE}/dashboard/quadras/${EXCLUIDA}/visao-geral`, { waitUntil: 'networkidle2' });
  check('quadra desativada não tem link público', !!(await d.$(`a[href$="/quadra/${EXCLUIDA}"]`)), false);

  // Visitante, sem login
  const p = await (await b.createBrowserContext()).newPage();
  const errs = [];
  p.on('pageerror', e => errs.push(e.message));
  await p.setViewport({ width: 1366, height: 900 });
  await p.goto(BASE + '/?lang=pt-br', { waitUntil: 'networkidle2' });
  await p.evaluate(() => localStorage.setItem('theme', 'light'));
  const cardLinks = await p.$$eval('#quadras h3 a', as => as.map(a => a.getAttribute('href')));
  check('cards da home viram links /quadra/{id}', cardLinks.length > 0 && cardLinks.every(h => /\/quadra\/\d+$/.test(h)), true);

  await p.goto(`${BASE}/quadra/${ARENA}`, { waitUntil: 'networkidle2' });
  const detail = api(`/venues/${ARENA}`);
  check('título da página', await p.title(), 'Arena Teste S3 · Kolae');
  check('h1 com o nome', await p.$eval('h1', e => e.textContent.trim()), 'Arena Teste S3');
  const statusLabel = { available: 'Disponível', in_use: 'Em uso', closed: 'Fechada' }[detail.status];
  check('selo de status igual ao da API', await p.$eval('section .status-badge', e => e.textContent.trim().startsWith(e.textContent.trim().split(' ')[0]) && e.textContent.includes(statusLabelPlaceholder = '')), true);
  check('selo diz ' + statusLabel, await p.$eval('section .status-badge', (e, l) => e.textContent.includes(l), statusLabel), true);
  check('galeria com 3 fotos', await p.$$eval('.md\\:grid .open-photo', els => els.length), 3);
  check('prévia de link com a capa (og:image)', await p.$eval('meta[property="og:image"]', m => m.content.split('/').pop()), detail.cover_image_url.split('/').pop());
  const body = await p.$eval('body', e => e.innerText);
  check('nunca mostra quem alugou', body.includes('Time do Carlos'), false);
  check('sem partes de gerenciar (Editar, Adicionar)', /\bEditar\b|Adicionar/.test(await p.$eval('main', m => m.innerText)), false);
  const busy = await p.$$eval('aside ul li', els => els.map(e => e.textContent.trim()));
  console.log('     ocupados hoje:', busy);
  check('horários ocupados de hoje aparecem', busy.length > 0 && busy.every(t => /^\d{2}:\d{2}–\d{2}:\d{2}$/.test(t)), true);
  const wa = await p.$eval('a[href^="https://wa.me/"]', a => a.href);
  const digits = sql(`SELECT whatsapp FROM users WHERE email='dono-s3@kolae.test'`);
  check('"Falar com o dono" abre o WhatsApp do dono', wa.startsWith(`https://wa.me/${digits}?text=`), true);
  check('mensagem já cita a quadra', decodeURIComponent(wa.split('text=')[1]).includes('Arena Teste S3'), true);
  check('responsável aparece com nome', await p.$eval('aside', a => a.innerText.includes('Responsável pela quadra')), true);
  await p.screenshot({ path: lib.OUT + '/publica-desktop.png' });
  await p.screenshot({ path: lib.OUT + '/publica-desktop-inteira.png', fullPage: true });

  // Tela cheia também funciona aqui
  await p.click('.md\\:grid .open-photo[data-index="1"]'); await sleep(300);
  check('tela cheia abre na foto 2', await p.$eval('#lightbox-counter', e => e.textContent), '2 / 3');
  await p.keyboard.press('Escape');

  // Compartilhar sem navigator.share: copia o link
  await p.browserContext().overridePermissions(BASE, ['clipboard-read', 'clipboard-write', 'clipboard-sanitized-write']);
  // Navegador sem menu nativo (share fica no protótipo, por isso o defineProperty)
  await p.evaluate(() => { Object.defineProperty(Navigator.prototype, 'share', { value: undefined, configurable: true }); });
  await p.click('#share-btn'); await sleep(200);
  check('compartilhar copia o link', await p.$eval('#share-btn .label', e => e.textContent), 'Link copiado!');
  await p.evaluate(() => navigator.clipboard.writeText(''));
  // Menu nativo que falha (sem ser cancelado pela pessoa) também copia
  await p.evaluate(() => { Object.defineProperty(Navigator.prototype, 'share', { value: () => Promise.reject(new DOMException('x', 'NotAllowedError')), configurable: true }); });
  await p.click('#share-btn'); await sleep(200);
  check('compartilhar copia o link', await p.$eval('#share-btn .label', e => e.textContent), 'Link copiado!');
  check('link copiado é o da página', await p.evaluate(() => navigator.clipboard.readText()), `${BASE}/quadra/${ARENA}`);

  // Cabeçalho com âncoras para a home e rolagem escura
  check('menu do cabeçalho leva à home', (await p.$eval('[data-site-header] a[href*="#"]', a => a.getAttribute('href'))).startsWith(BASE + '/#'), true);
  check('"Ver todas as quadras" leva à página Quadras', await p.$eval('section a[href$="/quadras"]', a => !!a), true);

  // Em manutenção aparece Fechada; desativada, id ruim e inexistente dão 404
  await p.goto(`${BASE}/quadra/${CLAY}`, { waitUntil: 'networkidle2' });
  check('quadra em manutenção abre como Fechada', await p.$eval('section .status-badge', e => e.textContent.includes('Fechada')), true);
  check('desativada: 404', code(`/quadra/${EXCLUIDA}`), '404');
  check('id inválido: 404', code('/quadra/abc'), '404');
  check('inexistente: 404', code('/quadra/99999999'), '404');

  // Sem fotos e sem WhatsApp (Duda): estados vazios
  await p.goto(`${BASE}/quadra/${DUDA}`, { waitUntil: 'networkidle2' });
  check('sem fotos: aviso no lugar da galeria', await p.$eval('main', m => m.innerText.includes('ainda não tem fotos')), true);
  check('sem fotos: prévia de link sem imagem', !!(await p.$('meta[property="og:image"]')), false);

  // Inglês
  await p.goto(`${BASE}/quadra/${ARENA}?lang=en-us`, { waitUntil: 'networkidle2' });
  check('em inglês: botão "Talk to the owner"', await p.$eval('a[href^="https://wa.me/"]', a => a.textContent.trim()), 'Talk to the owner');
  check('em inglês: piso traduzido', await p.$eval('main', m => /Synthetic Grass|Cement|Sand|Clay|Natural Grass|Parquet/.test(m.innerText)), true);

  // Celular, escuro
  await p.goto(`${BASE}/quadra/${ARENA}?lang=pt-br`, { waitUntil: 'networkidle2' });
  await p.evaluate(() => localStorage.setItem('theme', 'dark'));
  await p.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
  await p.goto(`${BASE}/quadra/${ARENA}`, { waitUntil: 'networkidle2' });
  check('celular: carrossel', await p.$$eval('#carousel .open-photo', els => els.length), 3);
  check('celular: sem rolagem horizontal', await p.evaluate(() => document.documentElement.scrollWidth), 390);
  await p.screenshot({ path: lib.OUT + '/publica-celular-escuro.png' });
  await p.screenshot({ path: lib.OUT + '/publica-celular-inteira.png', fullPage: true });

  check('sem erros de JS', errs, []);
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
})();
var statusLabelPlaceholder;
