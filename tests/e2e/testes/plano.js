// Teste de clique do plano da quadra (D61): o dono pede o Premium, o admin ativa (com validade),
// o dono vê o Premium e o selo, a validade vence sozinha e o admin desativa.
// Uso: node tests/e2e/rodar.js plano (ou direto: node tests/e2e/testes/plano.js <id Arena>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const BASE = lib.BASE;
const [ARENA] = process.argv.slice(2);
const sql = lib.sql;

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
  await p.goto(`${BASE}/login?lang=pt-br`, { waitUntil: 'networkidle2' });
  await p.evaluate(() => localStorage.setItem('theme', 'light'));
  await p.type('#email', email); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);
  return p;
}
const go = (p, url) => p.goto(BASE + url, { waitUntil: 'networkidle2' });
const clickNav = (p, sel) => Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click(sel)]);
const toast = async p => {
  await p.waitForSelector('.kolae-toast', { timeout: 3000 }).catch(() => {});
  return p.$eval('.kolae-toast-text', e => e.textContent).catch(() => '');
};
const state = p => p.$eval('#plan-state', e => e.dataset.state);
const plan = () => sql(`SELECT CONCAT_WS('|', plan, IFNULL(plan_until,'NULL'), IF(plan_requested_at IS NULL,'sem pedido','pedido')) FROM venues WHERE id=${ARENA}`);
const ymd = d => d.toLocaleDateString('sv-SE');
const daysFromNow = n => { const d = new Date(); d.setDate(d.getDate() + n); return ymd(d); };

(async () => {
  sql(`UPDATE venues SET plan='free', plan_until=NULL, plan_requested_at=NULL WHERE id=${ARENA}`);
  sql(`DELETE FROM notifications WHERE type='plan_activated'`);
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  const dono = await login(b, 'dono-s3@kolae.test');
  const page = `/dashboard/quadras/${ARENA}/plano`;

  // Dono: aba Premium, plano Grátis
  await go(dono, page);
  check('aba Premium ativa no topo da quadra', await dono.$eval('nav [aria-current=page]', a => a.textContent.trim()), 'Premium');
  check('título da aba', (await dono.title()).startsWith('Premium · '), true);
  check('começa no Grátis, com o botão de pedir', [await state(dono), !!(await dono.$('#plan-request'))], ['free', true]);
  check('comparação com 4 linhas', await dono.$$eval('table tbody tr', rs => rs.length), 4);
  check('dono não vê a área do admin', await dono.$('#plan-admin'), null);

  // Dono pede
  await clickNav(dono, '#plan-request');
  check('pedido: aviso de enviado', await toast(dono), 'Pedido enviado! Você será avisado quando o Premium for ativado.');
  check('pedido: estado "Pedido enviado" e sem o botão', [await state(dono), await dono.$('#plan-request')], ['requested', null]);
  check('banco: pedido gravado', plan(), 'free|NULL|pedido');

  // Admin: card no painel e ativação
  const admin = await login(b, 'admin@teste.local');
  await go(admin, '/admin');
  check('admin: pedido aparece no card do Premium', await admin.$eval(`[data-plan-row="${ARENA}"]`, r => r.innerText.includes('Pediu em')).catch(() => false), true);
  await go(admin, `/admin/quadras/${ARENA}/plano`);
  check('admin: área de ativação, com validade padrão de 30 dias', await admin.$eval('#plan_until', e => e.value), daysFromNow(30));
  await admin.$eval('#plan_until', e => { e.removeAttribute('min'); e.value = '2020-01-01'; });
  await clickNav(admin, '#plan-activate');
  check('admin: data no passado recusada', [await toast(admin), plan()], ['Escolha uma data de hoje em diante, ou marque "Sem prazo".', 'free|NULL|pedido']);
  await admin.$eval('#plan_until', (e, v) => { e.value = v; }, daysFromNow(15));
  await clickNav(admin, '#plan-activate');
  check('admin: Premium ativado por 15 dias, pedido fechado', [await toast(admin), plan()], ['Premium ativado.', `premium|${daysFromNow(15)}|sem pedido`]);
  check('dono recebe a notificação de ativação', sql(`SELECT COUNT(*) FROM notifications WHERE type='plan_activated' AND user_id=(SELECT id FROM users WHERE email='dono-s3@kolae.test')`), '1');

  // Dono vê o Premium e o selo no painel
  await go(dono, page);
  check('dono: estado Premium com a validade', [await state(dono), (await dono.$eval('#plan-state', e => e.innerText)).includes('Ativo até')], ['premium', true]);
  await go(dono, '/dashboard');
  check('painel: selo Premium só no card da quadra Premium', await dono.$$eval('[data-premium-badge]', bs => bs.length), 1);

  // Validade vence sozinha
  sql(`UPDATE venues SET plan_until = CURDATE() - INTERVAL 1 DAY WHERE id=${ARENA}`);
  await go(dono, page);
  check('vencido: estado "Premium vencido" e pode pedir de novo', [await state(dono), !!(await dono.$('#plan-request'))], ['expired', true]);
  await go(dono, '/dashboard');
  check('vencido: sem selo no painel', await dono.$$eval('[data-premium-badge]', bs => bs.length), 0);

  // Admin: sem prazo e depois desativar (com a confirmação do Kolae)
  await go(admin, `/admin/quadras/${ARENA}/plano`);
  await admin.click('input[name=no_limit]');
  await clickNav(admin, '#plan-activate');
  check('admin: Premium sem prazo', plan(), 'premium|NULL|sem pedido');
  await admin.click('#plan-deactivate');
  await admin.waitForSelector('.kolae-modal [data-ok]');
  check('desativar pede confirmação', await admin.$eval('.kolae-modal [data-ok]', b => b.textContent.trim()), 'Voltar para o Grátis');
  await clickNav(admin, '.kolae-modal [data-ok]');
  check('admin: voltou para o Grátis', [await toast(admin), plan()], ['A quadra voltou para o Grátis.', 'free|NULL|sem pedido']);

  // Outro dono não mexe; dono não usa a rota do admin
  const outro = await login(b, 'dono@teste.local');
  await go(outro, page);
  check('outro dono é mandado de volta', new URL(outro.url()).pathname, lib.BASE_PATH + '/dashboard');
  const r = await dono.evaluate(async (base, id) => {
    const fd = new FormData();
    const res = await fetch(`${base}/admin/quadras/${id}/plano/ativar`, { method: 'POST', body: fd });
    return res.status;
  }, BASE, ARENA);
  check('dono não ativa pela rota do admin', [r === 403, plan()], [true, 'free|NULL|sem pedido']);

  // Celular: a aba cabe sem rolagem horizontal da página
  await dono.setViewport({ width: 390, height: 844, isMobile: true });
  await go(dono, page);
  check('celular: sem rolagem horizontal', await dono.evaluate(() => document.documentElement.scrollWidth), 390);
  await dono.screenshot({ path: lib.OUT + '/plano-celular.png', fullPage: true });

  check('sem erros de JS', [...dono.jsErrors, ...admin.jsErrors], []);
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
  process.exit(fail ? 1 : 0);
})();
