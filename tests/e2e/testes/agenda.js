// Teste de clique da aba Agenda (S4). Uso: node tests/e2e/rodar.js agenda (ou direto: node tests/e2e/testes/agenda.js <id Arena> <id Duda>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const { execFileSync } = require('child_process');
const BASE = lib.BASE;
const [ARENA, DUDA] = process.argv.slice(2);
const MYSQL = lib.MYSQL;
const sql = q => execFileSync(MYSQL, ['-u', 'root', '--default-character-set=utf8mb4', '-N', lib.DB, '-e', q]).toString().split(String.fromCharCode(13)).join('').trim();
const api = path => JSON.parse(execFileSync('curl', ['-s', BASE + '/api/v1' + path]).toString()).data;

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
const text = p => p.$eval('main', m => m.innerText);
const ymd = d => d.toLocaleDateString('sv-SE', { timeZone: 'America/Sao_Paulo' });
const today = new Date(); const tomorrow = new Date(Date.now() + 86400000); const yesterday = new Date(Date.now() - 86400000);
const post = (p, url, fields) => p.evaluate(async (url, fields) => {
  const fd = new FormData(); Object.entries(fields).forEach(([k, v]) => fd.append(k, v));
  // Token CSRF da sessão: da página atual ou, se ela não tem formulário, do painel
  const token = document.querySelector('input[name=_csrf]')?.value
    || ((await (await fetch(document.querySelector('link[href*="/assets/css/"]').href.split('/assets/')[0] + '/dashboard/perfil')).text()).match(/name="_csrf" value="([0-9a-f]+)"/) || [])[1] || '';
  fd.append('_csrf', token);
  const r = await fetch(url, { method: 'POST', body: fd }); return { status: r.status, url: r.url, html: await r.text() };
}, url, fields);

(async () => {
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  const p = await login(b, 'dono-s3@kolae.test');
  const agenda = `${BASE}/dashboard/quadras/${ARENA}/agenda`;

  // Hoje na Arena (partida em andamento do seed)
  await p.goto(agenda, { waitUntil: 'networkidle2' });
  check('abre a Agenda com a aba marcada', await p.$eval('[aria-current="page"]', e => e.textContent.trim()), 'Agenda');
  check('mostra "Hoje"', (await text(p)).toLowerCase().includes('hoje'), true);
  check('horário do dia', (await text(p)).includes('Aberta das 00:00 às 23:59'), true);
  check('linha do tempo tem faixa ocupada', await p.$$eval('.timeline-seg.is-busy', els => els.filter(e => e.closest('[role=img]')).length), 1);
  check('marca do "agora"', (await text(p)).includes('AGORA') || (await text(p)).includes('Agora'), true);
  check('partida em andamento listada', (await text(p)).includes('Time do Carlos') && (await text(p)).includes('Em andamento'), true);
  check('sugestão de horário preenchida', await p.$eval('#start_time', e => /^\d{2}:\d{2}$/.test(e.value)), true);

  // Conflito com a partida atual (servidor)
  const cur = sql(`SELECT CONCAT(TIME_FORMAT(start_time,'%H:%i'),'|',TIME_FORMAT(end_time,'%H:%i')) FROM matches WHERE venue_id=${ARENA} AND status='scheduled' ORDER BY id LIMIT 1`).split('|');
  let r = await post(p, agenda, { data: ymd(today), start_time: cur[0], end_time: cur[1] });
  check('conflito: 422 com mensagem', [r.status, r.html.includes('Já existe uma partida nesse horário')], [422, true]);

  // Ir ao dia seguinte e agendar pelo formulário
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('a[aria-label="Próximo dia"]')]);
  check('navega para amanhã', p.url().endsWith(`?data=${ymd(tomorrow)}`), true);
  if (!(await p.$('#start_time'))) { console.log('URL:', p.url()); console.log((await text(p)).slice(0, 600)); }
  await p.$eval('#start_time', e => { e.value = '10:00'; e.dispatchEvent(new Event('change')); });
  await p.click('.duration-btn[data-minutes="90"]');
  check('duração 1h30 preenche o fim', await p.$eval('#end_time', e => e.value), '11:30');
  check('chip de duração fica ativo', await p.$eval('.duration-btn[data-minutes="90"]', e => e.classList.contains('is-active')), true);

  // Erro no navegador: fim antes do início
  await p.$eval('#end_time', e => { e.value = '09:00'; e.dispatchEvent(new Event('change')); });
  check('erro na hora: fim antes do início', await p.$eval('#client-error', e => !e.classList.contains('hidden') && e.textContent.includes('depois do início')), true);
  await p.click('#schedule-form button[type=submit]');
  await new Promise(r => setTimeout(r, 300));
  check('não envia com erro', p.url().includes('status=created'), false);

  await p.$eval('#end_time', e => { e.value = '11:30'; e.dispatchEvent(new Event('change')); });
  await p.select('#sport_id', await p.$eval('#sport_id option:nth-child(2)', o => o.value));
  await p.type('#renter_name', 'Clique Teste');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#schedule-form button[type=submit]')]);
  check('agenda e confirma', [p.url().includes('status=created'), (await text(p)).includes('Partida agendada')], [true, true]);
  check('lista mostra a partida nova', (await text(p)).includes('10:00–11:30') && (await text(p)).includes('Clique Teste'), true);
  check('banco gravou com quem agendou e esporte', sql(`SELECT CONCAT(TIME_FORMAT(start_time,'%H:%i'),'-',TIME_FORMAT(end_time,'%H:%i'),'|',renter_name,'|',IF(sport_id IS NULL,'sem','com'),'|',creator_user_id=(SELECT id FROM users WHERE email='dono-s3@kolae.test')) FROM matches WHERE renter_name='Clique Teste'`), '10:00-11:30|Clique Teste|com|1');
  const slots = api(`/venues/${ARENA}/schedule?date=${ymd(tomorrow)}`).slots.filter(s => s.status === 'in_use').map(s => s.start.slice(11, 16) + '-' + s.end.slice(11, 16));
  check('API mostra 10:00–11:30 ocupado', slots, ['10:00-11:30']);
  check('API não mostra quem alugou', JSON.stringify(api(`/venues/${ARENA}/schedule?date=${ymd(tomorrow)}`)).includes('Clique'), false);

  // Cancelar
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('.match-row .cancel-form button', b => b.click())]);
  check('cancela e confirma', (await text(p)).includes('Partida cancelada'), true);
  check('banco: status cancelled', sql(`SELECT status FROM matches WHERE renter_name='Clique Teste'`), 'cancelled');
  check('API: horário livre de novo', api(`/venues/${ARENA}/schedule?date=${ymd(tomorrow)}`).slots.map(s => s.status), ['available']);

  // Validações do servidor
  r = await post(p, agenda, { data: ymd(today), start_time: '00:00', end_time: '00:05' });
  check('horário que já passou: 422', [r.status, r.html.includes('Esse horário já passou')], [422, true]);
  r = await post(p, agenda, { data: ymd(tomorrow), start_time: '25:00', end_time: '10:00' });
  check('hora inválida: 422', [r.status, r.html.includes('Informe um início e um fim válidos')], [422, true]);

  // Quadra sem horário
  await p.goto(`${BASE}/dashboard/quadras/${DUDA}/agenda`, { waitUntil: 'networkidle2' });
  check('sem horário: aviso e sem formulário', [(await text(p)).includes('Cadastre o horário da quadra antes'), !!(await p.$('#schedule-form'))], [true, false]);

  // Fora do horário e dia fechado (Duda: só seg a sex, 08:00–18:00)
  sql(`INSERT INTO venue_hours (venue_id, weekday, opens_at, closes_at) VALUES ${[1,2,3,4,5].map(w => `(${DUDA},${w},'08:00','18:00')`).join(',')}`);
  let weekday = new Date(Date.now() + 86400000); while ([0, 6].includes(weekday.getDay())) weekday = new Date(weekday.getTime() + 86400000);
  r = await post(p, `${BASE}/dashboard/quadras/${DUDA}/agenda`, { data: ymd(weekday), start_time: '19:00', end_time: '20:00' });
  check('fora do horário: 422 com o horário no texto', [r.status, r.html.includes('(08:00–18:00)')], [422, true]);
  let sunday = new Date(Date.now() + 86400000); while (sunday.getDay() !== 0) sunday = new Date(sunday.getTime() + 86400000);
  await p.goto(`${BASE}/dashboard/quadras/${DUDA}/agenda?data=${ymd(sunday)}`, { waitUntil: 'networkidle2' });
  check('domingo: "Fechada neste dia" e sem formulário', [(await text(p)).includes('Fechada neste dia'), !!(await p.$('#schedule-form'))], [true, false]);
  r = await post(p, `${BASE}/dashboard/quadras/${DUDA}/agenda`, { data: ymd(sunday), start_time: '10:00', end_time: '11:00' });
  check('servidor recusa dia fechado', [r.status, r.html.includes('Fechada neste dia'), r.html.includes('id="schedule-form"')], [422, true, false]);

  // Dia que já passou e data inválida
  await p.goto(`${agenda}?data=${ymd(yesterday)}`, { waitUntil: 'networkidle2' });
  check('ontem: sem formulário', [(await text(p)).includes('Este dia já passou'), !!(await p.$('#schedule-form'))], [true, false]);
  await p.goto(`${agenda}?data=2026-02-31`, { waitUntil: 'networkidle2' });
  check('data inválida cai em hoje', (await text(p)).toLowerCase().includes('hoje'), true);
  check('nenhum erro de JavaScript (dono)', p.jsErrors, []);

  // Outro dono não vê nem cancela
  const o = await login(b, 'dono@teste.local');
  await o.goto(agenda, { waitUntil: 'networkidle2' });
  check('outro dono é mandado de volta', o.url().replace(BASE, ''), '/dashboard?error=not_found');
  const matchId = sql(`SELECT id FROM matches WHERE venue_id=${ARENA} AND renter_name='Time do Carlos'`);
  await post(o, `${agenda}/cancelar/${matchId}`, { data: ymd(today) });
  check('outro dono não cancela', sql(`SELECT status FROM matches WHERE id=${matchId}`), 'scheduled');

  // Partida de outra quadra não é cancelada por esta rota
  const otherMatch = sql(`SELECT id FROM matches WHERE venue_id<>${ARENA} AND status='scheduled' LIMIT 1`);
  await post(p, `${agenda}/cancelar/${otherMatch}`, { data: ymd(today) });
  check('não cancela partida de outra quadra', sql(`SELECT status FROM matches WHERE id=${otherMatch}`), 'scheduled');

  // Admin
  const a = await login(b, 'admin@teste.local');
  await a.goto(`${BASE}/admin/quadras/${ARENA}/agenda`, { waitUntil: 'networkidle2' });
  check('admin abre a Agenda', [a.url().replace(BASE, ''), await a.$eval('#schedule-form', f => f.getAttribute('action').includes('/admin/quadras/'))], [`/admin/quadras/${ARENA}/agenda`, true]);
  check('nenhum erro de JavaScript (admin)', a.jsErrors, []);

  await b.close();
  console.log(fail ? `\n${fail} FALHA(S)` : '\nTodos os passos passaram');
  process.exit(fail ? 1 : 0);
})();
