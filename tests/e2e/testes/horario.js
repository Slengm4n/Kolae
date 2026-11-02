// Teste de clique da aba Horário (S4). Uso: node tests/e2e/rodar.js horario (ou direto: node tests/e2e/testes/horario.js <id Arena> <id Duda>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const { execFileSync } = require('child_process');
const BASE = lib.BASE;
const [ARENA, DUDA] = process.argv.slice(2);
const MYSQL = lib.MYSQL;
const sql = q => execFileSync(MYSQL, ['-u', 'root', '--default-character-set=utf8mb4', '-N', lib.DB, '-e', q]).toString().split('\r').join('').trim();

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};

async function login(browser, email) {
  const ctx = await browser.createBrowserContext();
  const p = await ctx.newPage();
  await p.setViewport({ width: 1366, height: 900 });
  p.jsErrors = [];
  p.on('pageerror', e => p.jsErrors.push(e.message));
  p.on('dialog', d => d.accept());
  await p.goto(`${BASE}/login`, { waitUntil: 'networkidle2' });
  await p.type('#email', email); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);
  // Já viu os guias das abas Horário e Agenda (D50): o teste é de quem usa a página
  await p.evaluate(() => { localStorage.setItem('kolae_tour_hours', 'seen'); localStorage.setItem('kolae_tour_agenda', 'seen'); });
  return p;
}
const rowState = (p, w) => p.$eval(`.day-row[data-weekday="${w}"]`, r => ({
  open: r.querySelector('.day-toggle').checked,
  opens: r.querySelector('.opens').value, closes: r.querySelector('.closes').value,
  timesVisible: !r.querySelector('.day-times').classList.contains('hidden'),
  error: r.querySelector('.day-error').textContent.trim(),
}));
const text = p => p.$eval('main', m => m.innerText);

(async () => {
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });

  // --- Dono, quadra sem horário ---
  const p = await login(b, 'dono-s3@kolae.test');
  await p.goto(`${BASE}/dashboard/quadras/${DUDA}/horario`, { waitUntil: 'networkidle2' });
  check('abre a aba Horário', p.url().replace(BASE, ''), `/dashboard/quadras/${DUDA}/horario`);
  check('aviso de quadra sem horário', (await text(p)).includes('Cadastre o horário da quadra'), true);
  check('selo: Fechada', await p.$eval('.status-badge', e => e.className.includes('status-badge--closed')), true);
  check('aba Horário marcada', await p.$eval('[aria-current="page"]', e => e.textContent.trim()), 'Horário');
  check('todos os dias começam fechados', (await Promise.all([0,1,2,3,4,5,6].map(w => rowState(p, w)))).every(s => !s.open && !s.timesVisible), true);

  // Liga um dia pelo interruptor
  await p.click('.day-row[data-weekday="1"] label');
  let s = await rowState(p, 1);
  check('interruptor abre segunda com 08:00–22:00', [s.open, s.timesVisible, s.opens, s.closes], [true, true, '08:00', '22:00']);

  // Preencher rápido
  await p.$eval('#quick-opens', e => { e.value = '07:00'; });
  await p.$eval('#quick-closes', e => { e.value = '23:00'; });
  await p.click('.quick-btn[data-days="1,2,3,4,5"]');
  await p.$eval('#quick-opens', e => { e.value = '09:00'; });
  await p.$eval('#quick-closes', e => { e.value = '18:00'; });
  await p.click('.quick-btn[data-days="6,0"]');
  check('Seg a sex = 07:00–23:00', (await Promise.all([1,2,3,4,5].map(w => rowState(p, w)))).map(x => `${x.open}${x.opens}-${x.closes}`), Array(5).fill('true07:00-23:00'));
  check('Sáb e dom = 09:00–18:00', (await Promise.all([6,0].map(w => rowState(p, w)))).map(x => `${x.open}${x.opens}-${x.closes}`), Array(2).fill('true09:00-18:00'));

  // Erro no navegador: quarta fecha antes de abrir
  await p.$eval('#closes-3', e => { e.value = '06:00'; e.dispatchEvent(new Event('change')); });
  s = await rowState(p, 3);
  check('erro na hora: fecha antes de abrir', s.error.startsWith('O fechamento precisa ser depois'), true);
  await p.click('#hours-form button[type=submit]');
  await new Promise(r => setTimeout(r, 400));
  check('não envia com erro', p.url().includes('status=saved'), false);

  // Corrige e fecha o domingo
  await p.$eval('#closes-3', e => { e.value = '23:00'; e.dispatchEvent(new Event('change')); });
  await p.click('.day-row[data-weekday="0"] label');
  check('domingo fechado mostra "Fechado"', await p.$eval('.day-row[data-weekday="0"] .day-closed', e => !e.classList.contains('hidden')), true);
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#hours-form button[type=submit]')]);
  check('salva e mostra confirmação', [p.url().endsWith('?status=saved'), (await text(p)).includes('Horário salvo')], [true, true]);
  check('banco: 6 dias gravados', sql(`SELECT CONCAT(weekday,'=',TIME_FORMAT(opens_at,'%H:%i'),'-',TIME_FORMAT(closes_at,'%H:%i')) FROM venue_hours WHERE venue_id=${DUDA} ORDER BY weekday`).split('\n'),
    ['1=07:00-23:00', '2=07:00-23:00', '3=07:00-23:00', '4=07:00-23:00', '5=07:00-23:00', '6=09:00-18:00']);
  const summary = await p.$$eval('dl dt, dl dd', els => els.map(e => e.textContent.trim()));
  check('resumo do app', summary, ['Seg a Sex', '07:00–23:00', 'Sáb', '09:00–18:00', 'Dom', 'Fechado']);
  check('aviso de sem horário some', (await text(p)).includes('Cadastre o horário da quadra'), false);

  // API reflete o horário
  const api = JSON.parse(execFileSync('curl', ['-s', `${BASE}/api/v1/venues/${DUDA}`]).toString()).data;
  check('API: opening_hours com 6 dias', api.opening_hours.length, 6);

  // Validação do servidor (sem o JavaScript)
  const resp = await p.evaluate(async (url) => {
    const fd = new FormData(); fd.append('hours[2][open]', '1'); fd.append('hours[2][opens_at]', '20:00'); fd.append('hours[2][closes_at]', '10:00');
    fd.append('hours[4][open]', '1'); fd.append('hours[4][opens_at]', '25:00'); fd.append('hours[4][closes_at]', '10:00');
    const r = await fetch(url, { method: 'POST', body: (fd.has('_csrf') || fd.append('_csrf', document.querySelector('input[name=_csrf]')?.value || ''), fd) }); return { status: r.status, html: await r.text() };
  }, `${BASE}/dashboard/quadras/${DUDA}/horario`);
  check('servidor recusa com 422', resp.status, 422);
  check('servidor mostra os dois erros', [resp.html.includes('O fechamento precisa ser depois'), resp.html.includes('Horário inválido')], [true, true]);
  check('servidor não gravou nada', sql(`SELECT COUNT(*) FROM venue_hours WHERE venue_id=${DUDA}`), '6');

  // Partidas que ficam fora do novo horário (Arena tem partida agora)
  const today = Number(sql("SELECT DAYOFWEEK(NOW())-1"));
  await p.goto(`${BASE}/dashboard/quadras/${ARENA}/horario`, { waitUntil: 'networkidle2' });
  check('Arena: selo Em uso', await p.$eval('.status-badge', e => e.className.includes('status-badge--in-use')), true);
  await p.$eval(`#closes-${today}`, e => { e.value = '08:00'; });
  await p.$eval(`#opens-${today}`, e => { e.value = '06:00'; });
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#hours-form button[type=submit]')]);
  check('aviso de partidas fora do horário', (await text(p)).includes('Partidas fora do horário'), true);
  check('aviso mostra quem alugou', (await text(p)).includes('Time do Carlos'), true);
  check('selo vira Fechada', await p.$eval('.status-badge', e => e.className.includes('status-badge--closed')), true);

  // Aba Dados tem as abas e leva ao Horário
  await p.goto(`${BASE}/dashboard/quadras/editar/${DUDA}`, { waitUntil: 'networkidle2' });
  check('Dados: aba Dados marcada', await p.$eval('[aria-current="page"]', e => e.textContent.trim()), 'Dados');
  check('Dados: aba Agenda é um link', await p.$eval('nav a[href$="/agenda"]', e => e.textContent.trim()), 'Agenda');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('nav a[href$="/horario"]')]);
  check('Dados → Horário pela aba', p.url().endsWith(`/quadras/${DUDA}/horario`), true);
  check('nenhum erro de JavaScript (dono)', p.jsErrors, []);

  // Outro dono não acessa
  const o = await login(b, 'dono@teste.local');
  await o.goto(`${BASE}/dashboard/quadras/${DUDA}/horario`, { waitUntil: 'networkidle2' });
  check('outro dono é mandado de volta', o.url().replace(BASE, ''), '/dashboard?error=not_found');
  const post = await o.evaluate(async (url) => { const fd = new FormData(); fd.append('hours[1][open]', '1'); fd.append('hours[1][opens_at]', '01:00'); fd.append('hours[1][closes_at]', '02:00'); const r = await fetch(url, { method: 'POST', body: (fd.has('_csrf') || fd.append('_csrf', document.querySelector('input[name=_csrf]')?.value || ''), fd), redirect: 'manual' }); return r.type; }, `${BASE}/dashboard/quadras/${DUDA}/horario`);
  check('outro dono não consegue gravar', sql(`SELECT COUNT(*) FROM venue_hours WHERE venue_id=${DUDA} AND opens_at='01:00:00'`), '0');

  // Admin
  const a = await login(b, 'admin@teste.local');
  await a.goto(`${BASE}/admin/quadras/${DUDA}/horario`, { waitUntil: 'networkidle2' });
  check('admin abre pelo painel de admin', a.url().replace(BASE, ''), `/admin/quadras/${DUDA}/horario`);
  check('admin: voltar leva à lista do admin', await a.$eval('main a[href$="/admin/quadras"]', e => !!e), true);
  check('admin: form envia para /admin', await a.$eval('#hours-form', f => f.getAttribute('action').includes('/admin/quadras/')), true);
  check('nenhum erro de JavaScript (admin)', a.jsErrors, []);

  await b.close();
  console.log(fail ? `\n${fail} FALHA(S)` : '\nTodos os passos passaram');
  process.exit(fail ? 1 : 0);
})();
