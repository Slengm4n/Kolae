// Teste de clique da aba Fotos (D46). Uso: node tests/e2e/rodar.js fotos (ou direto: node tests/e2e/testes/fotos.js <id Arena>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const BASE = lib.BASE;
const [ARENA] = process.argv.slice(2);
const DIR = lib.FOTOS;
const foto = n => path.join(DIR, `foto${n}.${n % 2 ? 'jpg' : 'png'}`);
const sql = q => execFileSync(lib.MYSQL,
  ['--default-character-set=utf8mb4', '-uroot', '-N', lib.DB, '-e', q]).toString().split(String.fromCharCode(13)).join('').trim();
const api = p => JSON.parse(execFileSync('curl', ['-s', BASE + '/api/v1' + p]).toString()).data;
const UPLOADS = lib.ROOT + '/uploads/venues/' + ARENA;

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};

(async () => {
  // Começa sem fotos na Arena
  sql(`DELETE FROM venues_images WHERE venue_id=${ARENA}`);
  fs.rmSync(UPLOADS, { recursive: true, force: true });

  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  const p = await b.newPage();
  const errs = [];
  p.on('pageerror', e => errs.push(e.message));
  p.on('dialog', d => d.accept());
  await p.setViewport({ width: 1366, height: 900 });
  const go = async url => p.goto(url, { waitUntil: 'networkidle2' });
  const clickNav = sel => Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click(sel)]);
  const order = () => sql(`SELECT GROUP_CONCAT(id ORDER BY position, id) FROM venues_images WHERE venue_id=${ARENA}`).split(',').filter(Boolean);
  const file = id => sql(`SELECT file_path FROM venues_images WHERE id=${id}`);
  const apiImages = () => api(`/venues/${ARENA}`).images.map(u => u.split('/').pop());

  await go(BASE + '/login?lang=pt-br');
  await p.evaluate(() => localStorage.setItem('theme', 'light'));
  await p.type('#email', 'dono-s3@kolae.test'); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);

  const page = `${BASE}/dashboard/quadras/${ARENA}/fotos`;
  await go(page);
  check('aba Fotos marcada', await p.$eval('[aria-current="page"]', e => e.textContent.trim()), 'Fotos');
  check('estado vazio', await p.$eval('main', m => m.innerText.includes('Nenhuma foto ainda')), true);
  check('contador 0 de 10', await p.$eval('main', m => m.innerText.includes('0 de 10 fotos')), true);
  await p.screenshot({ path: lib.OUT + '/fotos-vazio.png' });

  // Envia 3 pelo formulário
  const input = await p.$('#images');
  await input.uploadFile(foto(1), foto(2), foto(3));
  await p.waitForSelector('#chosen:not(.hidden)');
  check('prévia mostra 3 escolhidas', await p.$$eval('#chosen-list img', els => els.length), 3);
  check('botão diz "Enviar 3 foto(s)"', await p.$eval('#upload-label', e => e.textContent), 'Enviar 3 foto(s)');
  await clickNav('#upload-btn');
  check('confirmação "3 fotos enviadas."', await p.$eval('[role=status]', e => e.textContent.trim()), '3 fotos enviadas.');
  check('3 cards na galeria', await p.$$eval('ol > li', els => els.length), 3);
  check('primeira tem o selo Capa', await p.$eval('ol > li:first-child', e => e.innerText.includes('Capa')), true);
  check('primeira: seta esquerda desativada', await p.$eval('ol > li:first-child button[aria-label="Mover para a esquerda"]', e => e.disabled), true);
  check('última: seta direita desativada', await p.$eval('ol > li:last-child button[aria-label="Mover para a direita"]', e => e.disabled), true);
  let ids = order();
  check('banco: posições 0, 1, 2', sql(`SELECT GROUP_CONCAT(position ORDER BY position) FROM venues_images WHERE venue_id=${ARENA}`), '0,1,2');
  check('arquivos salvos na pasta', fs.readdirSync(UPLOADS).length, 3);
  check('API: images na ordem de envio', apiImages(), ids.map(file));
  check('API: capa é a primeira', api(`/venues/${ARENA}`).cover_image_url.split('/').pop(), file(ids[0]));
  await p.screenshot({ path: lib.OUT + '/fotos-galeria.png', fullPage: true });

  // Move a terceira para a esquerda
  await clickNav(`#foto-${ids[2]} button[aria-label="Mover para a esquerda"]`);
  check('mover para a esquerda troca com a vizinha', order(), [ids[0], ids[2], ids[1]]);
  check('volta para a mesma foto (âncora)', new URL(p.url()).hash, `#foto-${ids[2]}`);
  check('API acompanha a ordem nova', apiImages(), [ids[0], ids[2], ids[1]].map(file));

  // Tornar capa
  await clickNav(`#foto-${ids[1]} form[action$="/capa"] button`);
  check('"Tornar capa" põe a foto em primeiro', order(), [ids[1], ids[0], ids[2]]);
  check('mensagem "Capa trocada."', await p.$eval('[role=status]', e => e.textContent.trim()), 'Capa trocada.');
  check('API: capa nova', api(`/venues/${ARENA}`).cover_image_url.split('/').pop(), file(ids[1]));
  await go(`${BASE}/dashboard`);
  check('painel usa a capa nova', await p.evaluate(f => [...document.querySelectorAll('img')].some(i => i.src.endsWith(f)), file(ids[1])), true);

  // Apagar
  await go(page);
  const gone = file(ids[0]);
  await clickNav(`#foto-${ids[0]} .delete-form button`);
  check('apagar: some da galeria', [order().length, await p.$eval('[role=status]', e => e.textContent.trim())], [2, 'Foto apagada.']);
  check('apagar: arquivo sai do disco', fs.existsSync(`${UPLOADS}/${gone}`), false);

  // Limite: tem 2, cabem 8. Escolhe 9 + 1 arquivo falso
  await go(page);
  await (await p.$('#images')).uploadFile(...[4, 5, 6, 7, 8, 9, 10, 11, 12].map(foto), path.join(DIR, 'falsa.png'));
  await p.waitForSelector('#chosen:not(.hidden)');
  check('navegador fica só com as 8 que cabem', await p.$$eval('#chosen-list img', els => els.length), 8);
  check('aviso do que ficou de fora', await p.$eval('#chosen-warning', e => !e.classList.contains('hidden') && e.textContent.includes('cabem só mais 8')), true);
  await clickNav('#upload-btn');
  check('envia 8', await p.$eval('[role=status]', e => e.textContent.trim()), '8 fotos enviadas.');
  check('cheia: 10 de 10 e sem formulário', [await p.$eval('main', m => m.innerText.includes('10 de 10 fotos')), !!(await p.$('#upload-form'))], [true, false]);
  check('mensagem de galeria cheia', await p.$eval('main', m => m.innerText.includes('já tem 10 fotos')), true);

  // Servidor também barra (sem o navegador): cheia e arquivo falso
  const token = await p.$eval('input[name=_csrf]', e => e.value);
  const postFiles = names => p.evaluate(async (url, token, names) => {
    const fd = new FormData(); fd.append('_csrf', token);
    for (const [name, type, body] of names) fd.append('images[]', new File([body], name, { type }));
    const r = await fetch(url, { method: 'POST', body: fd }); return new URL(r.url).search;
  }, page, token, names);
  check('servidor: galeria cheia recusa', await postFiles([['a.png', 'image/png', 'x']]), '?status=uploaded&over_limit=1');
  sql(`DELETE FROM venues_images WHERE id=${order()[9]}`);
  check('servidor: arquivo que não é imagem é recusado', await postFiles([['falsa.png', 'image/png', 'isto nao e imagem']]), '?status=uploaded&rejected=1');
  check('nenhuma foto falsa no banco', order().length, 9);

  // Outro dono não mexe
  const p2 = await b.newPage();
  const ctx = await b.createBrowserContext();
  const o = await ctx.newPage();
  await o.goto(BASE + '/login', { waitUntil: 'networkidle2' });
  await o.type('#email', 'dono@teste.local'); await o.type('#password', 'teste123');
  await Promise.all([o.waitForNavigation({ waitUntil: 'networkidle2' }), o.$eval('#login-form', f => f.submit())]);
  await o.goto(page, { waitUntil: 'networkidle2' });
  check('outro dono é mandado de volta', o.url().replace(BASE, ''), '/dashboard?error=not_found');
  const before = order();
  await o.evaluate(async (url) => {
    const html = await (await fetch(document.querySelector('link[href*="/assets/css/"]').href.split('/assets/')[0] + '/dashboard/perfil')).text();
    const fd = new FormData(); fd.append('_csrf', (html.match(/name="_csrf" value="([0-9a-f]+)"/) || [])[1]);
    await fetch(url, { method: 'POST', body: fd });
  }, `${page}/${before[3]}/capa`);
  check('outro dono não troca a capa', order(), before);
  await p2.close();

  // Aba Dados mostra o atalho
  await go(`${BASE}/dashboard/quadras/editar/${ARENA}`);
  check('Dados: contador e atalho para Fotos', await p.$eval('main', m => m.innerText.includes('9 de 10 fotos') && !!m.querySelector(`a[href$="/quadras/${location.pathname.split('/').pop()}/fotos"]`)), true);
  check('Dados: sem o upload antigo', !!(await p.$('#drop-area')), false);

  // Admin também acessa
  const a = await (await b.createBrowserContext()).newPage();
  await a.goto(BASE + '/login', { waitUntil: 'networkidle2' });
  await a.type('#email', 'admin@teste.local'); await a.type('#password', 'teste123');
  await Promise.all([a.waitForNavigation({ waitUntil: 'networkidle2' }), a.$eval('#login-form', f => f.submit())]);
  await a.goto(`${BASE}/admin/quadras/${ARENA}/fotos`, { waitUntil: 'networkidle2' });
  check('admin abre a aba Fotos', await a.$$eval('ol > li', els => els.length), 9);

  // Celular e escuro
  await go(page);
  await p.evaluate(() => localStorage.setItem('theme', 'dark'));
  await p.setViewport({ width: 390, height: 844, isMobile: true });
  await go(page);
  check('celular: sem rolagem horizontal', await p.evaluate(() => document.documentElement.scrollWidth), 390);
  await p.screenshot({ path: lib.OUT + '/fotos-celular-escuro.png', fullPage: false });
  await p.evaluate(() => localStorage.setItem('theme', 'light'));

  check('sem erros de JS', errs, []);
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
})();
