// Teste das páginas de erro (D53): 404, 404 de quadra, 403 e CSRF, com status HTTP certo.
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const BASE = lib.BASE;

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};

(async () => {
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  const p = await b.newPage();
  const errs = [];
  p.on('pageerror', e => errs.push(e.message));
  await p.setViewport({ width: 1366, height: 900 });
  const page = async url => {
    const r = await p.goto(url, { waitUntil: 'networkidle2' });
    return { status: r.status(), title: await p.$eval('h1', e => e.textContent.trim()), buttons: await p.$$eval('main a', as => as.map(a => a.textContent.trim())) };
  };

  await p.goto(BASE + '/?lang=pt-br', { waitUntil: 'networkidle2' });
  await p.evaluate(() => localStorage.setItem('theme', 'light'));

  let r = await page(BASE + '/pagina-que-nao-existe');
  check('404: status e título', [r.status, r.title], [404, 'Página não encontrada']);
  check('404: botões', r.buttons, ['Ir para o início', 'Ver quadras']);
  check('404: com cabeçalho e tema (head comum)', await p.evaluate(() => !!document.querySelector('link[href*="style.css"]') && document.title.endsWith('· Kolae')), true);
  check('404: não indexar', await p.$eval('meta[name=robots]', m => m.content), 'noindex');
  await p.screenshot({ path: lib.OUT + '/erro-404.png' });

  r = await page(BASE + '/quadra/99999999');
  check('quadra inexistente: 404 com título próprio', [r.status, r.title], [404, 'Quadra não encontrada']);
  const api = await p.goto(BASE + '/api/v1/rota-que-nao-existe');
  check('API continua respondendo JSON no 404', [api.status(), (await api.json()).code], [404, 'NOT_FOUND']);

  // 403: dono tentando abrir o admin
  await p.goto(BASE + '/login', { waitUntil: 'networkidle2' });
  await p.type('#email', 'dono-s3@kolae.test'); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);
  r = await page(BASE + '/admin');
  check('403: status, título e "Voltar ao painel"', [r.status, r.title, r.buttons[0]], [403, 'Acesso negado', 'Voltar ao painel']);
  await p.screenshot({ path: lib.OUT + '/erro-403.png' });

  // CSRF continua igual por fora
  await p.goto(BASE + '/dashboard/perfil', { waitUntil: 'networkidle2' });
  const csrf = await p.evaluate(async () => {
    const res = await fetch(document.querySelector('link[href*="/assets/css/"]').href.split('/assets/')[0] + '/dashboard/perfil/atualizar', { method: 'POST', body: new FormData() });
    const html = await res.text();
    return [res.status, /<h1[^>]*>\s*Página expirada\s*<\/h1>/.test(html)];
  });
  check('CSRF: 403 com "Página expirada"', csrf, [403, true]);

  // Inglês e celular escuro
  r = await page(BASE + '/nada?lang=en-us');
  check('inglês', [r.title, r.buttons], ['Page not found', ['Go to home', 'See venues']]);
  await p.goto(BASE + '/?lang=pt-br', { waitUntil: 'networkidle2' });
  await p.evaluate(() => localStorage.setItem('theme', 'dark'));
  await p.setViewport({ width: 390, height: 844, isMobile: true });
  await page(BASE + '/nada');
  check('celular: sem rolagem horizontal', await p.evaluate(() => document.documentElement.scrollWidth), 390);
  await p.screenshot({ path: lib.OUT + '/erro-404-celular-escuro.png' });

  check('sem erros de JS', errs, []);
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
})();
