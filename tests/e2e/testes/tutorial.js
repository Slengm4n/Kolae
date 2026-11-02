// Teste do guia passo a passo das abas Horário e Agenda (D50). Uso: node tests/e2e/rodar.js tutorial (ou direto: node tests/e2e/testes/tutorial.js <id Arena> <id Duda>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const BASE = lib.BASE;
const [ARENA, DUDA] = process.argv.slice(2);

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};
const sleep = ms => new Promise(r => setTimeout(r, ms));

(async () => {
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  const p = await b.newPage();
  const errs = [];
  p.on('pageerror', e => errs.push(e.message));
  p.on('dialog', d => d.accept()); // "sair sem salvar?" da aba Horário
  await p.setViewport({ width: 1366, height: 900 });
  await p.goto(BASE + '/login?lang=pt-br', { waitUntil: 'networkidle2' });
  await p.evaluate(() => { localStorage.clear(); localStorage.setItem('theme', 'light'); });
  await p.type('#email', 'dono-s3@kolae.test'); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);

  const open = () => p.$eval('#tour', e => !e.classList.contains('hidden'));
  const state = () => p.evaluate(() => ({ progress: document.getElementById('tour-progress').textContent, title: document.getElementById('tour-title').textContent, next: document.getElementById('tour-next').textContent }));
  // O destaque cobre o elemento?
  const covers = sel => p.evaluate(sel => {
    const t = document.querySelector(sel).getBoundingClientRect();
    const s = document.getElementById('tour-spot').getBoundingClientRect();
    return s.top <= t.top && s.left <= t.left && s.bottom >= t.bottom && s.right >= t.right;
  }, sel);
  const cardInside = () => p.evaluate(() => {
    const c = document.getElementById('tour-card').getBoundingClientRect();
    return c.top >= 0 && c.left >= 0 && c.bottom <= innerHeight && c.right <= innerWidth;
  });

  // Horário: abre sozinho na primeira visita
  await p.goto(`${BASE}/dashboard/quadras/${ARENA}/horario`, { waitUntil: 'networkidle2' });
  await sleep(1200);
  check('Horário: guia abre sozinho', await open(), true);
  check('passo 1 de 5', (await state()).progress, 'Passo 1 de 5');
  check('título do passo 1', (await state()).title, 'Ligue os dias em que a quadra abre');
  check('destaque no interruptor de segunda', await covers('.day-row[data-weekday="1"] label'), true);
  check('foco vai para o balão', await p.evaluate(() => document.activeElement.id), 'tour-card');
  check('"Voltar" escondido no primeiro passo', await p.$eval('#tour-back', e => e.classList.contains('invisible')), true);
  await p.screenshot({ path: lib.OUT + '/tutorial-horario-1.png' });

  await p.click('#tour-next'); await sleep(500);
  check('passo 2: horários de segunda', [(await state()).progress, await covers('.day-row[data-weekday="1"] .day-times')], ['Passo 2 de 5', true]);
  await p.keyboard.press('ArrowRight'); await sleep(500);
  check('seta → vai ao passo 3 (preencher rápido)', [(await state()).progress, await covers('#hours-quick')], ['Passo 3 de 5', true]);
  await p.keyboard.press('ArrowLeft'); await sleep(500);
  check('seta ← volta ao passo 2', (await state()).progress, 'Passo 2 de 5');
  await p.click('#tour-next'); await sleep(450);
  await p.click('#tour-next'); await sleep(600);
  check('passo 4: resumo do app', [(await state()).progress, await covers('#hours-summary')], ['Passo 4 de 5', true]);
  check('balão dentro da tela', await cardInside(), true);
  await p.click('#tour-next'); await sleep(600);
  check('último passo: Salvar e botão "Entendi"', [(await state()).progress, await covers('#hours-save'), (await state()).next], ['Passo 5 de 5', true, 'Entendi']);
  await p.screenshot({ path: lib.OUT + '/tutorial-horario-5.png' });
  await p.click('#tour-next'); await sleep(200);
  check('"Entendi" fecha', await open(), false);
  check('lembra que viu', await p.evaluate(() => localStorage.getItem('kolae_tour_hours')), 'seen');

  await p.reload({ waitUntil: 'networkidle2' }); await sleep(1200);
  check('não abre de novo sozinho', await open(), false);
  await p.click('[data-tour-start="hours"]'); await sleep(500);
  check('"Como funciona?" abre de novo', [await open(), (await state()).progress], [true, 'Passo 1 de 5']);
  await p.keyboard.press('Escape'); await sleep(100);
  check('Esc fecha e devolve o foco ao botão', [await open(), await p.evaluate(() => document.activeElement.dataset.tourStart)], [false, 'hours']);
  await p.click('[data-tour-start="hours"]'); await sleep(400);
  await p.mouse.click(10, 880); await sleep(100);
  check('clicar fora fecha', await open(), false);

  // Quadra sem horário (segunda fechada): passo 2 destaca a linha inteira
  await p.goto(`${BASE}/dashboard/quadras/${DUDA}/horario`, { waitUntil: 'networkidle2' });
  const mondayOpen = await p.$eval('.day-row[data-weekday="1"] .day-toggle', e => e.checked);
  await p.click('[data-tour-start="hours"]'); await sleep(400);
  await p.click('#tour-next'); await sleep(500);
  check(mondayOpen ? 'passo 2 com segunda aberta' : 'segunda fechada: passo 2 destaca a linha', await covers(mondayOpen ? '.day-row[data-weekday="1"] .day-times' : '.day-row[data-weekday="1"]'), true);
  await p.keyboard.press('Escape');

  // O guia não atrapalha usar a página depois de fechado
  await p.goto(`${BASE}/dashboard/quadras/${ARENA}/horario`, { waitUntil: 'networkidle2' });
  await p.click('.quick-btn[data-days="6,0"]');
  check('página funciona com o guia fechado', await p.$eval('.day-row[data-weekday="6"] .day-toggle', e => e.checked), true);

  // Agenda: guia próprio, abre sozinho uma vez
  await p.goto(`${BASE}/dashboard/quadras/${ARENA}/agenda`, { waitUntil: 'networkidle2' });
  await sleep(1200);
  check('Agenda: guia abre sozinho', [await open(), (await state()).progress], [true, 'Passo 1 de 4']);
  check('passo 1: navegação do dia', await covers('#agenda-day'), true);
  await p.click('#tour-next'); await sleep(500);
  check('passo 2: linha do tempo', await covers('#agenda-timeline'), true);
  await p.click('#tour-next'); await sleep(600);
  check('passo 3: agendar partida', (await state()).title, 'Agende uma partida');
  await p.click('#tour-next'); await sleep(600);
  check('passo 4: partidas do dia', [await covers('#agenda-matches'), (await state()).next], [true, 'Entendi']);
  await p.screenshot({ path: lib.OUT + '/tutorial-agenda-4.png' });
  await p.click('#tour-skip');
  check('"Pular" fecha e lembra', [await open(), await p.evaluate(() => localStorage.getItem('kolae_tour_agenda'))], [false, 'seen']);

  // Celular
  await p.evaluate(() => localStorage.removeItem('kolae_tour_hours'));
  await p.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
  await p.goto(`${BASE}/dashboard/quadras/${ARENA}/horario`, { waitUntil: 'networkidle2' });
  await sleep(1200);
  check('celular: abre sozinho', await open(), true);
  check('celular: balão dentro da tela', await cardInside(), true);
  await p.screenshot({ path: lib.OUT + '/tutorial-celular-1.png' });
  await p.click('#tour-next'); await sleep(500); await p.click('#tour-next'); await sleep(600);
  check('celular: passo 3 com balão dentro da tela', [await cardInside(), await covers('#hours-quick')], [true, true]);
  await p.screenshot({ path: lib.OUT + '/tutorial-celular-3.png' });

  // Inglês
  await p.goto(`${BASE}/dashboard/quadras/${ARENA}/horario?lang=en-us`, { waitUntil: 'networkidle2' });
  await sleep(1200);
  check('inglês: textos traduzidos', [(await state()).progress, (await state()).next], ['Step 1 of 5', 'Next']);
  await p.goto(`${BASE}/dashboard?lang=pt-br`, { waitUntil: 'networkidle2' });

  check('sem erros de JS', errs, []);
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
})();
