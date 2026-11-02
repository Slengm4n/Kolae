// Teste de clique do cadastro de quadra (S4, D36). Uso: node tests/e2e/rodar.js cadastro (ou direto: node tests/e2e/testes/cadastro.js)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const { execFileSync } = require('child_process');
const BASE = lib.BASE;
const MYSQL = lib.MYSQL;
const sql = q => execFileSync(MYSQL, ['-u', 'root', '--default-character-set=utf8mb4', '-N', lib.DB, '-e', q]).toString().split(String.fromCharCode(13)).join('').trim();

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
  await p.goto(`${BASE}/login`, { waitUntil: 'networkidle2' });
  await p.type('#email', email); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);
  return p;
}
const step = p => p.$eval('.step.active', e => e.id);
const next = async p => { await p.click('#next-btn'); await new Promise(r => setTimeout(r, 250)); };

async function cadastrar(p, prefix, name, opts) {
  await p.goto(`${BASE}${prefix}/quadras/criar`, { waitUntil: 'networkidle2' });
  await p.click('#step-1 .option-card[data-value="sand"]'); await next(p);
  await next(p);
  check(`${name}: passo 3 tem esportes`, await p.$$eval('#step-3 input[name="sports[]"]', els => els.length), 2);
  if (opts.sport) await p.click(`#step-3 input[name="sports[]"][value="${opts.sport}"] + span`);
  await next(p);
  check(`${name}: passo 4`, await step(p), 'step-4');
  await p.type('#name', name);
  if (opts.priceMode !== 'value') await p.click(`input[name=price_mode][value=${opts.priceMode}] + span`);
  else await p.type('#average_price_per_hour', opts.price);
  check(`${name}: campo de valor só em "Valor"`, await p.$eval('#price-value-box', e => !e.classList.contains('hidden')), opts.priceMode === 'value');
  if (opts.description) await p.type('#description', opts.description);
  if (opts.whatsapp) {
    await p.type('#whatsapp', '123');
    await next(p);
    check(`${name}: WhatsApp inválido não avança`, await step(p), 'step-4');
    await p.$eval('#whatsapp', e => { e.value = ''; });
    await p.type('#whatsapp', opts.whatsapp);
  }
  await next(p);
  check(`${name}: passo 5`, await step(p), 'step-5');
  for (const [id, v] of Object.entries({ cep: '08500-000', street: 'Rua Cadastro Teste S3', number: '1', neighborhood: 'Centro Teste S3', city: 'Ferraz Teste S3', state: 'SP Teste S3' })) await p.$eval('#' + id, (el, v) => { el.value = v; }, v);
  await next(p);
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#next-btn')]);
  return sql(`SELECT id FROM venues WHERE name='${name}'`);
}

(async () => {
  sql("UPDATE users SET whatsapp=NULL WHERE email='dono-s3@kolae.test'");
  const volei = sql("SELECT id FROM sports WHERE name LIKE 'V%lei Teste S3'");
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });

  // Dono sem WhatsApp, quadra grátis
  const p = await login(b, 'dono-s3@kolae.test');
  check('dono: pede o WhatsApp no passo 4', await (async () => { await p.goto(`${BASE}/dashboard/quadras/criar`, { waitUntil: 'networkidle2' }); return !!(await p.$('#whatsapp')); })(), true);
  const id = await cadastrar(p, '/dashboard', 'Cadastro Teste S3', { sport: volei, priceMode: 'free', description: 'Quadra de areia nova.', whatsapp: '(11) 91234-5678' });
  check('dono: vai para a aba Horário com o aviso', [p.url().replace(BASE, ''), (await p.$eval('main', m => m.innerText)).includes('Quadra cadastrada! Agora cadastre o horário')], [`/dashboard/quadras/${id}/horario?status=created`, true]);
  check('dono: sem o aviso genérico duplicado', (await p.$eval('main', m => m.innerText)).includes('Cadastre o horário da quadra'), false);
  check('banco: grátis, descrição, piso, dono', sql(`SELECT CONCAT_WS('|', average_price_per_hour, description, floor_type, status, user_id=(SELECT id FROM users WHERE email='dono-s3@kolae.test')) FROM venues WHERE id=${id}`), '0.00|Quadra de areia nova.|sand|available|1');
  check('banco: esporte marcado', sql(`SELECT vs.sport_id=${volei} FROM venue_sports vs WHERE vs.venue_id=${id}`), '1');
  check('banco: WhatsApp no perfil', sql("SELECT whatsapp FROM users WHERE email='dono-s3@kolae.test'"), '5511912345678');
  await p.goto(`${BASE}/dashboard/quadras/criar`, { waitUntil: 'networkidle2' });
  check('dono com WhatsApp: não pede de novo', !!(await p.$('#whatsapp')), false);
  check('nenhum erro de JavaScript (dono)', p.jsErrors, []);

  // Admin, preço com vírgula, sem esportes
  const a = await login(b, 'admin@teste.local');
  const idA = await cadastrar(a, '/admin', 'Cadastro Admin Teste S3', { priceMode: 'value', price: '55,90' });
  check('admin: vai para a aba Horário do admin', a.url().replace(BASE, ''), `/admin/quadras/${idA}/horario?status=created`);
  check('banco: 55,90 e sem esportes', [sql(`SELECT average_price_per_hour FROM venues WHERE id=${idA}`), sql(`SELECT COUNT(*) FROM venue_sports WHERE venue_id=${idA}`)], ['55.90', '0']);
  check('nenhum erro de JavaScript (admin)', a.jsErrors, []);

  // Limpa as duas quadras criadas, os endereços e a rua delas
  sql("DELETE FROM venues WHERE name LIKE 'Cadastro%Teste S3'; DELETE FROM addresses WHERE street_id IN (SELECT id FROM streets WHERE name='Rua Cadastro Teste S3'); DELETE FROM streets WHERE name='Rua Cadastro Teste S3';");
  await b.close();
  console.log(fail ? `\n${fail} FALHA(S)` : '\nTodos os passos passaram');
  process.exit(fail ? 1 : 0);
})();
