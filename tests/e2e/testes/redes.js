// Teste de clique das redes sociais (D60): aba Dados e o "socials" da API, de ponta a ponta.
// Uso: node tests/e2e/rodar.js redes (ou direto: node tests/e2e/testes/redes.js <id Duda> <id Arena>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const path = require('path');
const { execFileSync } = require('child_process');
const BASE = lib.BASE;
const [DUDA, ARENA] = process.argv.slice(2);
const sql = lib.sql;
const api = p => JSON.parse(execFileSync('curl', ['-s', BASE + '/api/v1' + p]).toString());
const NULLS = { instagram: null, tiktok: null, facebook_url: null, website_url: null };

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};
async function login(browser, email, viewport = { width: 1366, height: 900 }) {
  const ctx = await browser.createBrowserContext();
  const p = await ctx.newPage();
  await p.setViewport(viewport);
  p.jsErrors = [];
  p.on('pageerror', e => p.jsErrors.push(e.message));
  await p.goto(`${BASE}/login`, { waitUntil: 'networkidle2' });
  await p.type('#email', email); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);
  return p;
}
const submit = p => Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#venue-form', f => f.requestSubmit())]);
const setValue = (p, sel, v) => p.$eval(sel, (e, v) => { e.value = v; e.dispatchEvent(new Event('input', { bubbles: true })); }, v);
const preview = p => p.$$eval('#socials-preview a', els => els.map(a => [a.dataset.network, a.href, a.textContent.trim()]));
const errorShown = (p, field) => p.$eval(`#social-${field}-error`, e => !e.classList.contains('hidden'));
const row = () => sql(`SELECT CONCAT_WS('|', IFNULL(instagram,'NULL'), IFNULL(tiktok,'NULL'), IFNULL(facebook_url,'NULL'), IFNULL(website_url,'NULL')) FROM venues WHERE id=${DUDA}`);

(async () => {
  sql(`UPDATE venues SET instagram=NULL, tiktok=NULL, facebook_url=NULL, website_url=NULL WHERE id=${DUDA}`);
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  const p = await login(b, 'dono-s3@kolae.test');
  const edit = `${BASE}/dashboard/quadras/editar/${DUDA}`;
  await p.goto(edit, { waitUntil: 'networkidle2' });

  check('seção Redes sociais com os 4 campos vazios', await p.$$eval('#redes [data-social]', els => els.map(e => [e.name, e.value])),
    [['instagram', ''], ['tiktok', ''], ['facebook_url', ''], ['website_url', '']]);
  check('prévia vazia avisa que a seção não aparece', [await preview(p), await p.$eval('#socials-preview-empty', e => !e.classList.contains('hidden'))], [[], true]);

  // Cada campo aceita o que a pessoa colar; a prévia mostra o link que o app vai abrir
  await setValue(p, '#social-instagram', '@arenadoxuxa');
  await setValue(p, '#social-tiktok', 'https://www.tiktok.com/@arena.tk/video/7300000000000000000');
  await setValue(p, '#social-facebook_url', 'facebook.com/arenadoxuxa');
  await setValue(p, '#social-website_url', 'arenadoxuxa.com.br');
  check('prévia com as 4 redes e os links certos', await preview(p), [
    ['instagram', 'https://instagram.com/arenadoxuxa', '@arenadoxuxa'],
    ['tiktok', 'https://www.tiktok.com/@arena.tk', '@arena.tk'],
    ['facebook_url', 'https://facebook.com/arenadoxuxa', 'Facebook'],
    ['website_url', 'https://arenadoxuxa.com.br/', 'Site'],
  ]);
  check('ícones da prévia abrem em outra aba, com nome para o leitor de tela', await p.$$eval('#socials-preview a', els => els.map(a => [a.target, a.rel, a.getAttribute('aria-label')])), [
    ['_blank', 'noopener noreferrer', 'Instagram da quadra (abre em outra aba)'],
    ['_blank', 'noopener noreferrer', 'TikTok da quadra (abre em outra aba)'],
    ['_blank', 'noopener noreferrer', 'Facebook da quadra (abre em outra aba)'],
    ['_blank', 'noopener noreferrer', 'Site da quadra (abre em outra aba)'],
  ]);

  // Link de post do Instagram: erro ao sair do campo e o navegador não envia
  await setValue(p, '#social-instagram', 'https://instagram.com/p/C1234abc/');
  await p.$eval('#social-instagram', e => e.dispatchEvent(new Event('blur')));
  check('link de post do Instagram: erro ao sair do campo', [await errorShown(p, 'instagram'), await p.$eval('#social-instagram', e => e.getAttribute('aria-invalid'))], [true, 'true']);
  check('prévia tira o Instagram inválido', (await preview(p)).map(l => l[0]), ['tiktok', 'facebook_url', 'website_url']);
  await p.$eval('#venue-form', f => f.requestSubmit());
  await new Promise(r => setTimeout(r, 300));
  check('Instagram inválido: não envia', [p.url(), row()], [edit, 'NULL|NULL|NULL|NULL']);
  await setValue(p, '#social-instagram', 'instagram.com/arenadoxuxa?igsh=abc');
  check('ao digitar de novo o erro some', await errorShown(p, 'instagram'), false);

  await submit(p);
  check('salva e fica na aba Dados com a confirmação', [p.url().endsWith(`/editar/${DUDA}?status=updated`), (await p.$eval('main', m => m.innerText)).includes('Dados salvos')], [true, true]);
  check('banco: só o usuário no Instagram e no TikTok, https:// no Facebook e no site', row(), 'arenadoxuxa|arena.tk|https://facebook.com/arenadoxuxa|https://arenadoxuxa.com.br');
  check('campos voltam com o que foi gravado', await p.$$eval('#redes [data-social]', els => els.map(e => e.value)), ['arenadoxuxa', 'arena.tk', 'https://facebook.com/arenadoxuxa', 'https://arenadoxuxa.com.br']);

  // API: o que o dono salvou chega no detalhe, no formato do contrato (docs/api.md)
  check('API: socials no detalhe com o que foi salvo', api(`/venues/${DUDA}`).data.socials,
    { instagram: 'arenadoxuxa', tiktok: 'arena.tk', facebook_url: 'https://facebook.com/arenadoxuxa', website_url: 'https://arenadoxuxa.com.br' });
  check('API: socials vem depois do owner, como no contrato', Object.keys(api(`/venues/${DUDA}`).data).slice(-2), ['owner', 'socials']);
  check('API: quadra sem redes manda as quatro chaves com null', api(`/venues/${ARENA}`).data.socials, NULLS);
  const lista = api('/venues?per_page=50').data;
  check('API: a lista (GET /venues) não tem socials', [lista.length > 0, lista.some(v => 'socials' in v)], [true, false]);
  check('prévia montada ao abrir a página', (await preview(p)).length, 4);
  await p.$eval('#redes', e => e.scrollIntoView());
  await (await p.$('#redes')).screenshot({ path: path.join(lib.OUT, 'redes_dados.png') });

  // O servidor também recusa, sem gravar nada (quem desliga o JS ou manda direto)
  for (const [field, value] of [['website_url', 'javascript:alert(1)'], ['facebook_url', 'https://facebook.com.golpe.com/x'], ['tiktok', 'https://vm.tiktok.com/ZMabc/']]) {
    const url = await p.evaluate(async (action, field, value) => {
      const fd = new FormData(document.getElementById('venue-form'));
      fd.set(field, value);
      return (await fetch(action, { method: 'POST', body: fd })).url;
    }, `${BASE}/dashboard/quadras/atualizar/${DUDA}`, field, value);
    check(`servidor recusa ${field} = ${value}`, [url.endsWith(`?error=social_${field}`), row()], [true, 'arenadoxuxa|arena.tk|https://facebook.com/arenadoxuxa|https://arenadoxuxa.com.br']);
  }
  await p.goto(`${edit}?error=social_website_url#redes`, { waitUntil: 'networkidle2' });
  check('erro do servidor: aviso no topo e no campo', [
    (await p.$eval('main', m => m.innerText)).includes('Site inválido'),
    await errorShown(p, 'website_url'),
    await p.$eval('#social-website_url', e => e.getAttribute('aria-invalid')),
  ], [true, true, 'true']);

  // Visão geral: item opcional, não conta no "x de y"
  const overview = `${BASE}/dashboard/quadras/${DUDA}/visao-geral`;
  await p.goto(overview, { waitUntil: 'networkidle2' });
  const item = () => p.$eval('[data-check="socials"]', li => [li.innerText.replace(/\s+/g, ' ').trim(), !!li.querySelector('.fa-check')]);
  check('Visão geral: redes sociais marcadas, como opcional', await item(), ['Redes sociais (opcional)', true]);
  const progress = await p.$eval('[role=progressbar]', e => e.getAttribute('aria-valuemax'));
  check('Visão geral: o opcional não entra no total', progress, '6');

  // Visão geral e página pública mostram os links, como no app (mesmo bloco venue_details)
  const LINKS = [
    ['instagram', 'https://instagram.com/arenadoxuxa', '@arenadoxuxa'],
    ['tiktok', 'https://www.tiktok.com/@arena.tk', '@arena.tk'],
    ['facebook', 'https://facebook.com/arenadoxuxa', 'Facebook'],
    ['website', 'https://arenadoxuxa.com.br/', 'Site'],
  ];
  const socialsBlock = pg => pg.$$eval('#venue-socials a[data-network]', els => els.map(a => [a.dataset.network, a.href, a.textContent.trim()]));
  check('Visão geral: links das redes no bloco da quadra', await socialsBlock(p), LINKS);
  check('Visão geral: abrem em outra aba, com nome para o leitor de tela', await p.$$eval('#venue-socials a[data-network]', els => els.map(a => [a.target, a.getAttribute('aria-label')])), [
    ['_blank', 'Instagram da quadra (abre em outra aba)'], ['_blank', 'TikTok da quadra (abre em outra aba)'],
    ['_blank', 'Facebook da quadra (abre em outra aba)'], ['_blank', 'Site da quadra (abre em outra aba)'],
  ]);
  check('Visão geral: "Editar" leva à seção na aba Dados', await p.$eval('#venue-socials h2 + a', (a, id) => a.href.endsWith(`/editar/${id}#redes`), DUDA), true);
  await (await p.$('#venue-socials')).screenshot({ path: path.join(lib.OUT, 'redes_visao_geral.png') });
  const pub = await (await b.createBrowserContext()).newPage();
  await pub.setViewport({ width: 1366, height: 900 });
  pub.jsErrors = [];
  pub.on('pageerror', e => pub.jsErrors.push(e.message));
  await pub.goto(`${BASE}/quadra/${DUDA}`, { waitUntil: 'networkidle2' });
  check('página pública: mesmos links, sem login', await socialsBlock(pub), LINKS);
  check('página pública: sem "Editar"', await pub.$('#venue-socials h2 + a'), null);
  await (await pub.$('#venue-socials')).screenshot({ path: path.join(lib.OUT, 'redes_publica.png') });

  // Apagar os campos grava NULL
  await p.goto(edit, { waitUntil: 'networkidle2' });
  for (const f of ['instagram', 'tiktok', 'facebook_url', 'website_url']) await setValue(p, `#social-${f}`, '');
  await submit(p);
  check('campos vazios gravam NULL', row(), 'NULL|NULL|NULL|NULL');
  check('API: depois de apagar, as quatro voltam a null', api(`/venues/${DUDA}`).data.socials, NULLS);
  await p.goto(overview, { waitUntil: 'networkidle2' });
  check('Visão geral: sem redes, item opcional leva à seção', [await item(), await p.$eval('[data-check="socials"] a', (a, id) => a.href.endsWith(`/editar/${id}#redes`), DUDA)], [['Redes sociais (opcional)', false], true]);
  check('Visão geral: sem redes, convida a adicionar', await p.$eval('#venue-socials', (s, id) => [s.querySelectorAll('a[data-network]').length, s.innerText.includes('Nenhuma rede social informada.'), s.querySelector('p a').href.endsWith(`/editar/${id}#redes`)], DUDA), [0, true, true]);
  await pub.goto(`${BASE}/quadra/${DUDA}`, { waitUntil: 'networkidle2' });
  check('página pública: sem redes, a seção some', await pub.$('#venue-socials'), null);
  check('nenhum erro de JavaScript (página pública)', pub.jsErrors, []);
  check('nenhum erro de JavaScript (dono)', p.jsErrors, []);

  // Celular: sem rolagem para o lado
  const m = await login(b, 'dono-s3@kolae.test', { width: 390, height: 844, isMobile: true, hasTouch: true });
  await m.goto(edit, { waitUntil: 'networkidle2' });
  await setValue(m, '#social-instagram', 'arenadoxuxa');
  await setValue(m, '#social-website_url', 'https://um-endereco-bem-comprido-de-quadra.com.br/agenda/semana');
  check('celular: sem rolagem horizontal', await m.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
  await m.$eval('#redes', e => e.scrollIntoView());
  await (await m.$('#redes')).screenshot({ path: path.join(lib.OUT, 'redes_dados_celular.png') });

  // Inglês: textos traduzidos
  await m.goto(`${edit}?lang=en-us`, { waitUntil: 'networkidle2' });
  check('inglês: título e prévia vazia traduzidos', [await m.$eval('#redes h2', e => e.textContent.trim()), await m.$eval('#socials-preview-empty', e => e.textContent.trim())],
    ['Social media', 'No profile added: this section does not appear in the app.']);
  await m.goto(`${edit}?lang=pt-br`, { waitUntil: 'networkidle2' });
  check('nenhum erro de JavaScript (celular)', m.jsErrors, []);

  // Admin: mesma seção na edição do admin
  const a = await login(b, 'admin@teste.local');
  await a.goto(`${BASE}/admin/quadras/editar/${DUDA}`, { waitUntil: 'networkidle2' });
  await setValue(a, '#social-instagram', '@pelo.admin');
  await submit(a);
  check('admin: salva e continua no admin', [a.url().replace(BASE, ''), row()], [`/admin/quadras/editar/${DUDA}?status=updated`, 'pelo.admin|NULL|NULL|NULL']);
  check('API: o que o admin salvou também chega', api(`/venues/${DUDA}`).data.socials, { ...NULLS, instagram: 'pelo.admin' });
  check('nenhum erro de JavaScript (admin)', a.jsErrors, []);

  sql(`UPDATE venues SET instagram=NULL, tiktok=NULL, facebook_url=NULL, website_url=NULL WHERE id=${DUDA}`);
  await b.close();
  console.log(fail ? `\n${fail} FALHA(S)` : '\nTodos os passos passaram');
  process.exit(fail ? 1 : 0);
})();
