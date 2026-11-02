// Teste de clique da aba Dados (S4). Uso: node tests/e2e/rodar.js dados (ou direto: node tests/e2e/testes/dados.js <id Duda> <id Excluida>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const { execFileSync } = require('child_process');
const BASE = lib.BASE;
const [DUDA, EXCLUIDA] = process.argv.slice(2);
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
  await p.goto(`${BASE}/login`, { waitUntil: 'networkidle2' });
  await p.type('#email', email); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);
  return p;
}
const text = p => p.$eval('main', m => m.innerText);
const submit = p => Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#venue-form', f => f.requestSubmit())]);
const venueRow = id => sql(`SELECT CONCAT_WS('|', IFNULL(average_price_per_hour,'NULL'), IFNULL(description,'NULL'), status) FROM venues WHERE id=${id}`);

(async () => {
  sql("UPDATE users SET whatsapp=NULL WHERE email='dono-s3@kolae.test'");
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  const p = await login(b, 'dono-s3@kolae.test');
  const edit = `${BASE}/dashboard/quadras/editar/${DUDA}`;
  await p.goto(edit, { waitUntil: 'networkidle2' });

  check('pede o WhatsApp (dono sem número)', !!(await p.$('#whatsapp')), true);
  check('preço começa em "Valor" com 80,00', [await p.$eval('input[name=price_mode]:checked', e => e.value), await p.$eval('#price-input', e => e.value)], ['value', '80,00']);
  check('situação começa em Disponível', await p.$eval('input[name=status]:checked', e => e.value), 'available');
  check('esportes aparecem e nenhum marcado', [await p.$$eval('input[name="sports[]"]', els => els.length), await p.$$eval('input[name="sports[]"]:checked', els => els.length)], [2, 0]);

  // WhatsApp inválido barra no navegador
  await p.type('#whatsapp', '123');
  await p.$eval('#venue-form', f => f.requestSubmit());
  await new Promise(r => setTimeout(r, 300));
  check('WhatsApp inválido: erro e não envia', [await p.$eval('#whatsapp-error', e => !e.classList.contains('hidden')), p.url()], [true, edit]);

  // Preenche tudo e salva
  await p.$eval('#whatsapp', e => { e.value = ''; });
  await p.type('#whatsapp', '(11) 98765-4321');
  await p.click('input[name=price_mode][value=free] + span');
  check('"Grátis" esconde o campo de valor', await p.$eval('#price-value-box', e => e.classList.contains('hidden')), true);
  await p.type('#description', 'Quadra com vestiário e estacionamento.');
  check('contador da descrição', await p.$eval('#description-count', e => e.textContent), '38');
  const volei = await p.$$eval('input[name="sports[]"]', els => els.find(e => e.parentElement.textContent.includes('Vôlei')).value);
  await p.click(`input[name="sports[]"][value="${volei}"] + span`);
  await p.click('input[name=status][value=maintenance] + span');
  await submit(p);
  check('fica na aba Dados com confirmação', [p.url().endsWith(`/editar/${DUDA}?status=updated`), (await text(p)).includes('Dados salvos')], [true, true]);
  check('banco: grátis, descrição e manutenção', venueRow(DUDA), '0.00|Quadra com vestiário e estacionamento.|maintenance');
  check('banco: esporte marcado', sql(`SELECT s.name FROM venue_sports vs JOIN sports s ON s.id=vs.sport_id WHERE vs.venue_id=${DUDA}`), 'Vôlei Teste S3');
  check('banco: WhatsApp salvo no perfil', sql("SELECT whatsapp FROM users WHERE email='dono-s3@kolae.test'"), '5511987654321');
  check('pedido de WhatsApp some depois de salvo', !!(await p.$('#whatsapp')), false);
  check('selo no topo vira Fechada (manutenção)', await p.$eval('.status-badge', e => e.className.includes('status-badge--closed')), true);
  let d = api(`/venues/${DUDA}`);
  check('API: grátis, descrição, esporte, fechada', [d.price_per_hour_cents, d.description, d.sports.map(s => s.name), d.status], [0, 'Quadra com vestiário e estacionamento.', ['Vôlei Teste S3'], 'closed']);

  // "Não informar", tira os esportes e volta para Disponível
  await p.click('input[name=price_mode][value=none] + span');
  await p.click(`input[name="sports[]"][value="${volei}"] + span`);
  await p.click('input[name=status][value=available] + span');
  await submit(p);
  check('banco: preço não informado e disponível', venueRow(DUDA), 'NULL|Quadra com vestiário e estacionamento.|available');
  check('banco: sem esportes', sql(`SELECT COUNT(*) FROM venue_sports WHERE venue_id=${DUDA}`), '0');
  check('API: preço null', api(`/venues/${DUDA}`).price_per_hour_cents, null);

  // Valor com milhar e vírgula; descrição apagada vira NULL
  await p.click('input[name=price_mode][value=value] + span');
  await p.$eval('#price-input', e => { e.value = '1.234,56'; });
  await p.$eval('#description', e => { e.value = '   '; });
  await submit(p);
  check('banco: 1.234,56 vira 1234.56 e descrição vazia vira NULL', venueRow(DUDA), '1234.56|NULL|available');

  // Valor inválido: navegador barra e servidor recusa
  await p.$eval('#price-input', e => { e.value = '0'; });
  await p.$eval('#venue-form', f => f.requestSubmit());
  await new Promise(r => setTimeout(r, 300));
  check('valor 0 em "Valor": erro no navegador', await p.$eval('#price-error', e => !e.classList.contains('hidden')), true);
  const r = await p.evaluate(async (url) => { const fd = new FormData(document.getElementById('venue-form')); fd.set('price_mode', 'value'); fd.set('average_price_per_hour', 'abc'); const res = await fetch(url, { method: 'POST', body: fd }); return res.url; }, `${BASE}/dashboard/quadras/atualizar/${DUDA}`);
  check('servidor recusa valor inválido sem gravar', [r.endsWith('?error=price'), venueRow(DUDA)], [true, '1234.56|NULL|available']);
  check('nenhum erro de JavaScript (dono)', p.jsErrors, []);

  // Admin: sem pedido de WhatsApp, continua no admin
  const a = await login(b, 'admin@teste.local');
  await a.goto(`${BASE}/admin/quadras/editar/${DUDA}`, { waitUntil: 'networkidle2' });
  check('admin: não pede WhatsApp', !!(await a.$('#whatsapp')), false);
  await submit(a);
  check('admin: fica na edição do admin', a.url().replace(BASE, ''), `/admin/quadras/editar/${DUDA}?status=updated`);

  // Quadra desativada: sem escolha de situação e continua desativada
  await a.goto(`${BASE}/admin/quadras/editar/${EXCLUIDA}`, { waitUntil: 'networkidle2' });
  check('desativada: sem a escolha de situação', !!(await a.$('input[name=status]')), false);
  await submit(a);
  check('desativada continua desativada', sql(`SELECT status FROM venues WHERE id=${EXCLUIDA}`), 'unavailable');
  check('nenhum erro de JavaScript (admin)', a.jsErrors, []);

  await b.close();
  console.log(fail ? `\n${fail} FALHA(S)` : '\nTodos os passos passaram');
  process.exit(fail ? 1 : 0);
})();
