// Teste do painel do dono (S4, D40). Uso: node tests/e2e/rodar.js painel (ou direto: node tests/e2e/testes/painel.js <A> <B> <C> <D> <E>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const { execFileSync } = require('child_process');
const BASE = lib.BASE;
const [A, B, C, D, E] = process.argv.slice(2);
const api = path => JSON.parse(execFileSync('curl', ['-s', BASE + '/api/v1' + path]).toString()).data;

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
  await p.goto(`${BASE}/login`, { waitUntil: 'networkidle2' });
  await p.type('#email', 'dono-s3@kolae.test'); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);

  const card = id => p.$eval(`.venue-card[data-venue-id="${id}"]`, c => ({
    badge: c.querySelector('.status-badge').className.match(/status-badge--([a-z-]+)/)[1],
    text: c.querySelector('.status-badge').textContent.replace(/\s+/g, ' ').trim(),
    noHours: !!c.querySelector('.no-hours'),
    links: [...c.querySelectorAll('nav a')].map(a => a.getAttribute('href').replace(/.*\/quadras/, '')),
  }));
  const apiToBadge = { available: 'available', in_use: 'in-use', closed: 'closed' };

  check('5 cards de quadra', await p.$$eval('.venue-card', els => els.length), 5);
  for (const [id, label] of [[A, 'Arena'], [B, 'Beach'], [C, 'Clay'], [D, 'Duda']]) {
    const c = await card(id);
    check(`${label}: selo igual ao da API`, c.badge, apiToBadge[api(`/venues/${id}`).status]);
  }
  check('Arena: Em uso com "até"', (await card(A)).text.startsWith('Em uso · até'), true);
  check('Beach: Disponível com "até"', (await card(B)).text.startsWith('Disponível · até'), true);
  check('Clay (manutenção): Fechada sem previsão', (await card(C)).text, 'Fechada');
  check('Duda: aviso de sem horário', (await card(D)).noHours, true);
  check('Arena: sem aviso de sem horário', (await card(A)).noHours, false);
  const e = await card(E);
  check('Excluída: selo Desativada, sem aviso', [e.text, e.noHours], ['Desativada', false]);
  check('atalhos Dados · Horário · Agenda', (await card(A)).links, [`/editar/${A}`, `/${A}/horario`, `/${A}/agenda`]);

  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click(`.venue-card[data-venue-id="${D}"] .no-hours`)]);
  check('aviso leva à aba Horário', p.url().endsWith(`/quadras/${D}/horario`), true);
  check('nenhum erro de JavaScript', errs, []);
  await b.close();
  console.log(fail ? `\n${fail} FALHA(S)` : '\nTodos os passos passaram');
  process.exit(fail ? 1 : 0);
})();
