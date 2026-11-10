// Fotografa /termos e /privacidade (desktop claro, escuro rolado, celular, inglês) e confere links.
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const path = require('path');
const B = lib.BASE;
const out = n => path.join(lib.OUT, 'legal-' + n + '.png');
let ok = 0, fail = 0;
const check = (c, m) => { c ? ok++ : fail++; console.log((c ? 'OK  ' : 'FALHA ') + m); };

(async () => {
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: 'new' });
  const p = await b.newPage();
  const errors = [];
  p.on('pageerror', e => errors.push(e.message));
  await p.setViewport({ width: 1366, height: 900 });
  await p.goto(B + '/termos?lang=pt-br', { waitUntil: 'networkidle2' });
  await p.evaluate(() => localStorage.setItem('theme', 'light'));
  await p.reload({ waitUntil: 'networkidle2' });
  check((await p.$eval('h1', e => e.textContent)) === 'Termos de Uso', 'título termos');
  check((await p.$$('article section')).length === 15, '15 seções nos termos');
  check(await p.$eval('body', e => e.innerText.includes('10/11/2026')), 'data de atualização');
  check(!(await p.$('.fa-language')), 'sem aviso pt-only em português');
  check(await p.$eval('footer', e => !!e.querySelector('a[href$="/privacidade"]') && !!e.querySelector('a[href$="/termos"]')), 'links no rodapé');
  const anchor = await p.$eval('[data-site-header] a[href*="#"]', e => e.getAttribute('href'));
  check(anchor.startsWith(B + '/#'), 'âncora do cabeçalho aponta para a home: ' + anchor);
  await p.screenshot({ path: out('termos-topo') });
  await p.click('nav[aria-label="Nesta página"] a[href="#aluguel"]');
  await new Promise(r => setTimeout(r, 900));
  const top = await p.$eval('#aluguel', e => e.getBoundingClientRect().top);
  check(top > 60 && top < 160, 'índice rola até a seção sem ficar sob o cabeçalho (top=' + Math.round(top) + ')');
  const hdr = await p.$eval('[data-site-header]', e => e.className.includes('bg-neutral-950/85'));
  check(hdr, 'cabeçalho ganha fundo escuro ao rolar');
  await p.screenshot({ path: out('termos-rolado') });

  await p.goto(B + '/privacidade', { waitUntil: 'networkidle2' });
  check((await p.$eval('h1', e => e.textContent)) === 'Política de Privacidade', 'título privacidade');
  check((await p.$$('article section')).length === 13, '13 seções na privacidade');
  await p.evaluate(() => localStorage.setItem('theme', 'dark'));
  await p.reload({ waitUntil: 'networkidle2' });
  await p.evaluate(() => window.scrollTo(0, 700));
  await new Promise(r => setTimeout(r, 500));
  await p.screenshot({ path: out('privacidade-escuro') });

  await p.goto(B + '/privacidade?lang=en-us', { waitUntil: 'networkidle2' });
  check((await p.$eval('h1', e => e.textContent)) === 'Privacy Policy', 'título em inglês');
  check(!!(await p.$('.fa-language')), 'aviso pt-only em inglês');
  await p.screenshot({ path: out('privacidade-en') });

  await p.goto(B + '/termos?lang=pt-br', { waitUntil: 'networkidle2' });
  await p.evaluate(() => localStorage.setItem('theme', 'light'));
  await p.setViewport({ width: 390, height: 844, isMobile: true });
  await p.reload({ waitUntil: 'networkidle2' });
  const sw = await p.evaluate(() => document.documentElement.scrollWidth);
  check(sw <= 390, 'sem rolagem horizontal no celular (' + sw + ')');
  await p.screenshot({ path: out('termos-celular'), fullPage: false });

  await p.setViewport({ width: 1366, height: 900 });
  await p.goto(B + '/register', { waitUntil: 'networkidle2' });
  const regLinks = await p.$$eval('label[for="terms"] a', as => as.map(a => a.getAttribute('href') + '|' + a.target));
  check(regLinks.length === 2 && regLinks[0].endsWith('/termos|_blank') && regLinks[1].endsWith('/privacidade|_blank'), 'cadastro aponta para as páginas: ' + regLinks.join(', '));

  check(errors.length === 0, 'sem erros de JS ' + errors.join(' | '));
  console.log(`\n${ok} ok, ${fail} falhas`);
  await b.close();
})();
