// Teste de clique da aba Anúncio (D63, parte B): convite no Grátis, criar com prévia ao vivo e foto,
// erros no navegador e no servidor, editar, voltar para a capa, pausar, retomar, Premium vencido,
// números, encerrar, histórico, celular, inglês e admin.
// Uso: node tests/e2e/rodar.js anuncio (ou direto: node tests/e2e/testes/anuncio.js <id Arena>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');
const BASE = lib.BASE;
const [ARENA] = process.argv.slice(2);
const sql = lib.sql;
const FOTO = path.join(lib.FOTOS, 'foto1.jpg');
const FALSA = path.join(lib.FOTOS, 'falsa.png');

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};
const ymd = (days = 0) => { const d = new Date(Date.now() - 3 * 3600e3); d.setUTCDate(d.getUTCDate() + days); return d.toISOString().slice(0, 10); }; // hoje em Brasília
const br = s => s.slice(8, 10) + '/' + s.slice(5, 7);
const owner = `(SELECT user_id FROM venues WHERE id=${ARENA})`;
const setPlan = (plan, until = 'NULL') => sql(`UPDATE users SET plan='${plan}', plan_until=${until}, plan_requested_at=NULL WHERE id=${owner}`);
const adRow = () => sql(`SELECT CONCAT_WS('|', id, title, body, starts_on, ends_on, status, IFNULL(image_path,'NULL')) FROM venue_ads WHERE venue_id=${ARENA} ORDER BY id DESC LIMIT 1`);
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
const submitForm = (p, sel) => Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval(sel, f => f.requestSubmit())]);
const setValue = (p, sel, v) => p.$eval(sel, (e, v) => { e.value = v; e.dispatchEvent(new Event('input', { bubbles: true })); e.dispatchEvent(new Event('change', { bubbles: true })); }, v);
// POST direto (como quem pula a tela), com o token de CSRF da sessão tirado da aba Dados
const postDirect = (p, url, fields) => p.evaluate(async (url, fields, edit) => {
  const html = await (await fetch(edit)).text();
  const fd = new FormData();
  fd.set('_csrf', (html.match(/name="_csrf" value="([^"]+)"/) || [])[1] || '');
  Object.entries(fields).forEach(([k, v]) => fd.set(k, v));
  return (await fetch(url, { method: 'POST', body: fd })).url;
}, url, fields, `${BASE}/dashboard/quadras/editar/${ARENA}`);
const preview = p => p.$eval('#ad-preview [data-ad-card]', c => ({
  title: c.querySelector('[data-ad-title]').textContent, body: c.querySelector('[data-ad-body]').textContent,
  ends: c.querySelector('[data-ad-ends]').textContent, image: !c.querySelector('[data-ad-image]').hidden,
}));

(async () => {
  sql(`DELETE FROM venue_ads WHERE venue_id=${ARENA}`);
  sql(`DELETE FROM venue_events WHERE venue_id=${ARENA}`);
  setPlan('free');
  // Capa da quadra: sem foto própria, o anúncio usa a capa (o seed não tem fotos)
  const capaDir = path.join(lib.UPLOADS, 'venues', String(ARENA));
  fs.mkdirSync(capaDir, { recursive: true });
  fs.copyFileSync(FOTO, path.join(capaDir, 'capa_teste_anuncio.jpg'));
  sql(`DELETE FROM venues_images WHERE venue_id=${ARENA} AND file_path='capa_teste_anuncio.jpg'`);
  sql(`INSERT INTO venues_images (venue_id, file_path, position) VALUES (${ARENA}, 'capa_teste_anuncio.jpg', -1)`);
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  const p = await login(b, 'dono-s3@kolae.test');
  const page = `${BASE}/dashboard/quadras/${ARENA}/anuncio`;

  // --- Grátis: convite, sem formulário ---
  await go(p, page);
  check('aba Anúncio aparece e fica ativa', await p.$eval('#venue-tabs [aria-current=page]', a => a.textContent.trim()), 'Anúncio');
  check('Grátis: convite para o Premium, sem formulário', [!!(await p.$('#ad-locked')), !!(await p.$('#ad-form'))], [true, false]);
  check('Grátis: botão leva ao plano', await p.$eval('#ad-locked a', a => a.href.endsWith('/dashboard/perfil/plano')), true);
  const direto = await postDirect(p, page, { title: 'Pulei a tela', body: 'Sem Premium', starts_on: ymd(0), ends_on: ymd(5) });
  check('Grátis: o servidor recusa criar (mesmo sem a tela)', [direto.includes('error=ad_premium'), adRow()], [true, '']);

  // --- Premium: formulário com prévia ao vivo ---
  setPlan('premium');
  await go(p, page);
  check('Premium: formulário e selo Premium', [!!(await p.$('#ad-form')), !!(await p.$('#ad-locked')), (await p.$eval('main h2', h => h.innerText)).includes('Premium')], [true, false, true]);
  check('datas começam em hoje e hoje + 29 dias', [await p.$eval('#ad-starts', e => e.value), await p.$eval('#ad-ends', e => e.value), await p.$eval('#ad-ends', e => e.max)], [ymd(0), ymd(29), ymd(29)]);
  check('prévia começa com o exemplo e a capa da quadra', await preview(p), { title: 'Terça com 20% off', body: 'Reserve pelo WhatsApp e ganhe desconto às terças.', ends: `Até ${br(ymd(29))}`, image: true });
  await setValue(p, '#ad-title', 'Sexta do society');
  await setValue(p, '#ad-body', 'Duas horas pelo preço de uma, toda sexta.');
  check('prévia acompanha o que é digitado', [(await preview(p)).title, (await preview(p)).body, await p.$eval('#ad-title-count', e => e.textContent)], ['Sexta do society', 'Duas horas pelo preço de uma, toda sexta.', '16']);
  await setValue(p, '#ad-starts', ymd(5));
  check('o fim acompanha o início (máximo de 30 dias)', [await p.$eval('#ad-ends', e => e.min), await p.$eval('#ad-ends', e => e.max), await p.$eval('#ad-ends', e => e.value)], [ymd(5), ymd(34), ymd(29)]);
  await setValue(p, '#ad-ends', ymd(8));
  await setValue(p, '#ad-starts', ymd(20));
  check('início depois do fim: o fim pula para o último dia possível', [await p.$eval('#ad-ends', e => e.value), (await preview(p)).ends], [ymd(49), `Até ${br(ymd(49))}`]);
  await setValue(p, '#ad-starts', ymd(0));
  await setValue(p, '#ad-ends', ymd(10));

  // Título vazio: o navegador avisa e não envia
  await setValue(p, '#ad-title', '   ');
  await p.$eval('#ad-form', f => f.requestSubmit());
  await new Promise(r => setTimeout(r, 300));
  check('título vazio: erro no navegador, nada enviado', [await p.$eval('#ad-title-error', e => !e.classList.contains('hidden')), p.url(), adRow()], [true, page, '']);
  await setValue(p, '#ad-title', 'Sexta do society');

  // O servidor confere de novo: período maior que 30 dias e foto falsa
  await p.$eval('#ad-ends', e => { e.max = ''; });
  await setValue(p, '#ad-ends', ymd(30));
  await (await p.$('#ad-image')).uploadFile(FALSA);
  await submitForm(p, '#ad-form');
  check('servidor: 31 dias e foto falsa voltam com os erros, sem gravar', [
    await p.$eval('#ad-errors', e => e.textContent.trim()).catch(() => ''),
    await p.$eval('[data-error=ends_on]', e => e.textContent.trim()).catch(() => ''),
    await p.$eval('[data-error=image]', e => e.textContent.trim()).catch(() => ''),
    adRow(),
  ], ['Confira os campos marcados abaixo.', 'O anúncio pode durar até 30 dias, contando o primeiro.', 'Envie uma foto JPEG, PNG, WebP ou GIF de até 10 MB.', '']);
  check('servidor: o que foi digitado volta preenchido', [await p.$eval('#ad-title', e => e.value), await p.$eval('#ad-body', e => e.value)], ['Sexta do society', 'Duas horas pelo preço de uma, toda sexta.']);

  // Cria com foto
  await setValue(p, '#ad-ends', ymd(10));
  await (await p.$('#ad-image')).uploadFile(FOTO);
  await new Promise(r => setTimeout(r, 200));
  check('prévia mostra a foto escolhida', await p.$eval('#ad-preview [data-ad-image]', i => i.src.startsWith('blob:')), true);
  await submitForm(p, '#ad-form');
  check('publica com o aviso', await toast(p), 'Anúncio publicado!');
  let [id, title, body, startsOn, endsOn, status, image] = adRow().split('|');
  check('banco: anúncio ativo com o período e a foto', [title, body, startsOn, endsOn, status, /^ad_.+\.jpg$/.test(image)], ['Sexta do society', 'Duas horas pelo preço de uma, toda sexta.', ymd(0), ymd(10), 'active', true]);
  const fotoArquivo = path.join(lib.UPLOADS, 'ads', id, image);
  check('foto gravada em uploads/ads/{id}/', fs.existsSync(fotoArquivo), true);
  check('mostra o anúncio "no ar" e não o formulário', [await p.$eval('[data-ad-state]', e => e.dataset.adState), await p.$eval('[data-ad-state]', e => e.textContent.trim()), !!(await p.$('#ad-form'))], ['running', `No ar até ${br(ymd(10))}/${ymd(10).slice(0, 4)}`, false]);
  check('card com o texto e a foto do anúncio', await p.$eval('#ad-current [data-ad-card]', (c, id) => [c.querySelector('[data-ad-title]').textContent, c.querySelector('[data-ad-image]').src.includes(`/uploads/ads/${id}/`)], id), ['Sexta do society', true]);
  check('números começam em zero', await p.$$eval('#ad-numbers dd', d => d.map(x => x.textContent.trim())), ['0', '0', '0%']);
  await (await p.$('#ad-current')).screenshot({ path: path.join(lib.OUT, 'anuncio_no_ar.png') });

  // --- Editar e voltar para a capa ---
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#ad-edit')]);
  check('editar abre o formulário preenchido', [await p.$eval('#ad-title', e => e.value), await p.$eval('#ad-ends', e => e.value), await p.$eval('#ad-submit', e => e.textContent.trim())], ['Sexta do society', ymd(10), 'Salvar alterações']);
  await setValue(p, '#ad-title', 'Sexta e sábado do society');
  await p.click('#ad-remove-image');
  check('"usar a capa" troca a foto da prévia', await p.$eval('#ad-preview [data-ad-image]', (i, id) => i.src.includes('/uploads/venues/') && !i.src.includes(`/uploads/ads/${id}/`), id), true);
  await submitForm(p, '#ad-form');
  check('salva com o aviso', await toast(p), 'Anúncio atualizado.');
  check('banco: título novo, mesmo anúncio, sem foto própria', adRow().split('|').filter((_, i) => [0, 1, 6].includes(i)), [id, 'Sexta e sábado do society', 'NULL']);
  check('a foto antiga foi apagada do servidor', fs.existsSync(fotoArquivo), false);

  // --- Pausar e retomar ---
  await submitForm(p, `form[action$="/anuncio/${id}/pausar"]`);
  check('pausa', [await toast(p), adRow().split('|')[5], await p.$eval('[data-ad-state]', e => e.dataset.adState), !!(await p.$('#ad-resume')), !!(await p.$('#ad-pause'))], ['Anúncio pausado. Ele não aparece até você retomar.', 'paused', 'paused', true, false]);
  await submitForm(p, `form[action$="/anuncio/${id}/retomar"]`);
  check('retoma', [await toast(p), adRow().split('|')[5], await p.$eval('[data-ad-state]', e => e.dataset.adState)], ['Anúncio de volta no ar.', 'active', 'running']);

  // --- Números do anúncio ---
  sql(`INSERT INTO venue_events (venue_id, type, source) VALUES (${ARENA},'ad_view','site'),(${ARENA},'ad_view','app'),(${ARENA},'ad_view','site'),(${ARENA},'ad_view','site'),(${ARENA},'ad_click','app')`);
  await go(p, page);
  check('números: 4 vezes, 1 clique, 25%', await p.$$eval('#ad-numbers dd', d => d.map(x => x.textContent.trim())), ['4', '1', '25%']);

  // --- Premium vencido: salvo, mas fora do ar ---
  setPlan('premium', `'${ymd(-1)}'`);
  await go(p, page);
  check('Premium vencido: avisa, sem editar e sem formulário', [await p.$eval('[data-ad-state]', e => e.dataset.adState), (await p.$eval('#ad-current', e => e.innerText)).includes('Renovar o Premium'), !!(await p.$('#ad-edit')), !!(await p.$('#ad-form'))], ['premium_expired', true, false, false]);
  await submitForm(p, `form[action$="/anuncio/${id}/pausar"]`);
  check('Premium vencido: dá para pausar', adRow().split('|')[5], 'paused');
  check('Premium vencido: não dá para retomar (sem botão)', !!(await p.$('#ad-resume')), false);
  const retomar = await postDirect(p, `${page}/${id}/retomar`, {});
  check('Premium vencido: o servidor também não retoma', [retomar.includes('error=ad_premium'), adRow().split('|')[5]], [true, 'paused']);
  setPlan('premium');
  await go(p, page);
  await submitForm(p, `form[action$="/anuncio/${id}/retomar"]`);

  // --- Encerrar (com confirmação) libera um novo ---
  await p.click('#ad-end');
  await p.waitForSelector('.kolae-modal [data-ok]');
  check('encerrar pede confirmação', await p.$$eval('.kolae-modal button', bs => bs.map(x => x.textContent.trim())), ['Cancelar', 'Encerrar']);
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('.kolae-modal [data-ok]')]);
  check('encerra', [await toast(p), adRow().split('|')[5]], ['Anúncio encerrado.', 'ended']);
  check('depois de encerrar: formulário novo', [!!(await p.$('#ad-form')), await p.$eval('#ad-submit', e => e.textContent.trim())], [true, 'Publicar anúncio']);
  check('histórico com o anúncio encerrado', await p.$eval('#ad-history', e => e.innerText.includes('Sexta e sábado do society') && e.innerText.includes('encerrado')), true);

  // --- Removido pelo admin (a remoção em si é a parte E): o dono vê o motivo ---
  sql(`UPDATE venue_ads SET status='removed', removed_reason='Texto enganoso' WHERE id=${id}`);
  await go(p, page);
  check('removido: aviso com o motivo', (await p.$eval('#ad-removed', e => e.innerText)).includes('Motivo: Texto enganoso'), true);
  check('nenhum erro de JavaScript (dono)', p.jsErrors, []);

  // --- Celular ---
  const m = await login(b, 'dono-s3@kolae.test', { width: 390, height: 844, isMobile: true, hasTouch: true });
  await go(m, page);
  await setValue(m, '#ad-title', 'Um título bem comprido para ver se quebra direito');
  check('celular: sem rolagem para o lado', await m.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
  await m.screenshot({ path: path.join(lib.OUT, 'anuncio_celular.png'), fullPage: true });
  await go(m, `${page}?lang=en-us`);
  check('inglês: título, aba e botão traduzidos', [await m.$eval('main h2', h => h.innerText.trim()).then(t => t.startsWith('Venue ad')), await m.$eval('#venue-tabs [aria-current=page]', a => a.textContent.trim()), await m.$eval('#ad-submit', e => e.textContent.trim())], [true, 'Ad', 'Publish ad']);
  await go(m, `${page}?lang=pt-br`);
  check('nenhum erro de JavaScript (celular)', m.jsErrors, []);

  // --- Admin: mesma aba no painel do admin ---
  const a = await login(b, 'admin@teste.local');
  await go(a, `${BASE}/admin/quadras/${ARENA}/anuncio`);
  check('admin: abre a aba no painel do admin', [!!(await a.$('#ad-form')), await a.$eval('#ad-form', (f, v) => f.action.endsWith(`/admin/quadras/${v}/anuncio`), ARENA)], [true, true]);
  await setValue(a, '#ad-title', 'Pelo admin');
  await setValue(a, '#ad-body', 'Criado no painel do admin.');
  await submitForm(a, '#ad-form');
  check('admin: cria e continua no admin', [new URL(a.url()).pathname.replace(lib.BASE_PATH, ''), await toast(a), adRow().split('|')[1]], [`/admin/quadras/${ARENA}/anuncio`, 'Anúncio publicado!', 'Pelo admin']);
  check('nenhum erro de JavaScript (admin)', a.jsErrors, []);

  // Deixa como encontrou
  sql(`DELETE FROM venue_ads WHERE venue_id=${ARENA}`);
  sql(`DELETE FROM venue_events WHERE venue_id=${ARENA}`);
  setPlan('free');
  fs.rmSync(path.join(lib.UPLOADS, 'ads', id), { recursive: true, force: true }); // só a pasta do anúncio deste teste
  sql(`DELETE FROM venues_images WHERE venue_id=${ARENA} AND file_path='capa_teste_anuncio.jpg'`);
  fs.rmSync(path.join(capaDir, 'capa_teste_anuncio.jpg'), { force: true });
  await b.close();
  console.log(fail ? `\n${fail} FALHA(S)` : '\nTodos os passos passaram');
  process.exit(fail ? 1 : 0);
})();
