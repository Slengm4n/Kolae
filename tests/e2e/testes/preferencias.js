// Teste do painel do dono (D57): menu do perfil e botão de tema em todas as páginas
// (antes não abriam na Visão geral e em Fotos) e Configurações > Preferências (tema e idioma).
// Uso: node preferencias.js <id da quadra A do dono>
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const BASE = lib.BASE;
const venue = process.argv[2];

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
  await p.setViewport({ width: 1280, height: 900 });
  await p.goto(BASE + '/login?lang=pt-br', { waitUntil: 'networkidle2' });
  await p.evaluate(() => { localStorage.setItem('theme', 'light'); localStorage.setItem('kolae_tour_hours', 'seen'); localStorage.setItem('kolae_tour_agenda', 'seen'); });
  await p.type('#email', 'dono-s3@kolae.test'); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);

  // Menu e tema em todas as páginas do dono, com o mesmo cabeçalho
  const pages = ['/dashboard', '/dashboard/perfil', '/dashboard/perfil/seguranca', '/dashboard/perfil/preferencias',
    `/dashboard/quadras/editar/${venue}`, `/dashboard/quadras/${venue}/visao-geral`, `/dashboard/quadras/${venue}/fotos`,
    `/dashboard/quadras/${venue}/horario`, `/dashboard/quadras/${venue}/agenda`];
  for (const u of pages) {
    await p.goto(BASE + u, { waitUntil: 'networkidle2' });
    await p.click('#user-menu-button');
    await new Promise(r => setTimeout(r, 250));
    const open = await p.$eval('#profile-dropdown', e => !e.classList.contains('invisible'));
    await p.click('#theme-toggle');
    await p.evaluate(() => document.activeViewTransition && document.activeViewTransition.finished); // espera o esmaecimento do tema
    const dark = await p.evaluate(() => document.documentElement.classList.contains('dark'));
    await p.evaluate(() => localStorage.setItem('theme', 'light'));
    const header = await p.$eval('header', h => h.className.includes('py-3'));
    check(`${u}: menu abre, tema troca, cabeçalho comum`, [open, dark, header], [true, true, true]);
  }

  // Preferências: tema
  await p.goto(BASE + '/dashboard/perfil/preferencias', { waitUntil: 'networkidle2' });
  check('preferências: título e item ativo no menu', [await p.title(), await p.$eval('nav [aria-current=page]', a => a.textContent.trim())], ['Preferências · Kolae', 'Preferências']);
  check('preferências: "Claro" marcado (salvo)', await p.$eval('input[name=theme-choice]:checked', e => e.value), 'light');
  await p.click('input[name=theme-choice][value=dark] + span');
  await p.evaluate(() => document.activeViewTransition && document.activeViewTransition.finished);
  check('escolher Escuro: aplica e guarda', await p.evaluate(() => [document.documentElement.classList.contains('dark'), localStorage.getItem('theme')]), [true, 'dark']);
  await p.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'light' }]);
  await p.click('input[name=theme-choice][value=system] + span');
  await p.evaluate(() => document.activeViewTransition && document.activeViewTransition.finished);
  check('escolher Automático: segue o sistema (claro) e não guarda', await p.evaluate(() => [document.documentElement.classList.contains('dark'), localStorage.getItem('theme')]), [false, null]);
  await p.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'dark' }]);
  await new Promise(r => setTimeout(r, 200));
  check('Automático: sistema muda para escuro com a página aberta', await p.evaluate(() => document.documentElement.classList.contains('dark')), true);
  await p.goto(BASE + '/dashboard', { waitUntil: 'networkidle2' });
  check('Automático continua valendo em outra página (não grava ao carregar)', await p.evaluate(() => [document.documentElement.classList.contains('dark'), localStorage.getItem('theme')]), [true, null]);
  await p.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'light' }]);

  // Preferências: idioma
  await p.goto(BASE + '/dashboard/perfil/preferencias', { waitUntil: 'networkidle2' });
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('a[lang="en-us"]')]);
  check('idioma: inglês na hora, marcado na lista', [await p.$eval('h1', h => h.textContent.trim()), await p.$eval('a[lang][aria-current]', a => a.getAttribute('lang'))], ['Settings', 'en-us']);
  await p.screenshot({ path: lib.OUT + '/preferencias.png', fullPage: true });
  await p.goto(BASE + '/dashboard/perfil/preferencias?lang=pt-br', { waitUntil: 'networkidle2' });
  await p.evaluate(() => localStorage.setItem('theme', 'light'));

  check('sem erros de JS', errs, []);
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
  process.exit(fail ? 1 : 0);
})();
