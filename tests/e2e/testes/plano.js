// Teste de clique do Kolae Premium do dono (D61): o dono pede em Configurações > Kolae Premium,
// o admin ativa no card do painel (com validade), o dono vê o Premium e o selo, a validade vence
// sozinha e o admin desativa. O plano é do perfil e vale para todas as quadras do dono.
// Uso: node tests/e2e/rodar.js plano
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const BASE = lib.BASE;
const sql = lib.sql;
const DONO = 'dono-s3@kolae.test';

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
  const text = await p.$eval('.kolae-toast-text', e => e.textContent).catch(() => '');
  // Lido o aviso, tira da tela: ele fica no canto de cima e pode cobrir o próximo botão
  await p.evaluate(() => document.querySelectorAll('.kolae-toast').forEach(t => t.remove()));
  return text;
};
const plan = () => sql(`SELECT CONCAT_WS('|', plan, IFNULL(plan_until,'NULL'), IF(plan_requested_at IS NULL,'sem pedido','pedido')) FROM users WHERE email='${DONO}'`);
const donoId = () => sql(`SELECT id FROM users WHERE email='${DONO}'`);
const ymd = d => d.toLocaleDateString('sv-SE');
const daysFromNow = n => { const d = new Date(); d.setDate(d.getDate() + n); return ymd(d); };
const row = id => `[data-plan-row="${id}"]`;

(async () => {
  sql(`UPDATE users SET plan='free', plan_until=NULL, plan_requested_at=NULL WHERE email='${DONO}'`);
  sql(`DELETE FROM notifications WHERE type='plan_activated'`);
  const ID = donoId();
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  const dono = await login(b, DONO);

  // Dono: Configurações > Kolae Premium
  await go(dono, '/dashboard/perfil');
  check('item "Kolae Premium" no menu das Configurações do dono', await dono.$$eval('nav[aria-label] a', as => as.map(a => a.textContent.trim()).includes('Kolae Premium')), true);
  await go(dono, '/dashboard/perfil/plano');
  check('item ativo e título da aba', [await dono.$eval('nav [aria-current=page]', a => a.textContent.trim()), await dono.title()], ['Kolae Premium', 'Kolae Premium · Kolae']);
  check('começa no Grátis, com o botão de pedir', [await dono.$eval('#plan-state', e => e.dataset.state), !!(await dono.$('#plan-request'))], ['free', true]);
  check('diz que vale para todas as quadras do dono', await dono.$eval('#plan-state', e => /Vale para as suas \d+ quadras/.test(e.innerText)), true);
  check('a quadra não tem mais aba de plano', await (async () => {
    const id = sql(`SELECT id FROM venues WHERE name='Arena Teste S3'`);
    await go(dono, `/dashboard/quadras/${id}/visao-geral`);
    return dono.$$eval('#venue-tabs a', as => as.some(a => a.href.includes('/plano')));
  })(), false);

  // Dono pede
  await go(dono, '/dashboard/perfil/plano');
  await clickNav(dono, '#plan-request');
  check('pedido: aviso de enviado', await toast(dono), 'Pedido enviado! Você será avisado quando o Premium for ativado.');
  check('pedido: estado "Pedido enviado" e sem o botão', [await dono.$eval('#plan-state', e => e.dataset.state), await dono.$('#plan-request')], ['requested', null]);
  check('banco: pedido gravado no perfil', plan(), 'free|NULL|pedido');

  // Admin: card do painel
  const admin = await login(b, 'admin@teste.local');
  await go(admin, '/admin');
  check('admin: dono aparece com o pedido', await admin.$eval(row(ID), r => [r.dataset.state, r.innerText.includes('Pediu em')]).catch(() => null), ['requested', true]);
  check('admin: validade padrão de 30 dias', await admin.$eval(`${row(ID)} input[type=date]`, e => e.value), daysFromNow(30));
  await admin.$eval(`${row(ID)} input[type=date]`, e => { e.removeAttribute('min'); e.value = '2020-01-01'; });
  await clickNav(admin, `${row(ID)} [data-plan-activate]`);
  check('admin: data no passado recusada', [await toast(admin), plan()], ['Escolha uma data de hoje em diante, ou marque "Sem prazo".', 'free|NULL|pedido']);
  await admin.$eval(`${row(ID)} input[type=date]`, (e, v) => { e.value = v; }, daysFromNow(15));
  await clickNav(admin, `${row(ID)} [data-plan-activate]`);
  check('admin: Premium ativado por 15 dias, pedido fechado', [await toast(admin), plan()], ['Premium ativado.', `premium|${daysFromNow(15)}|sem pedido`]);
  check('dono recebe a notificação de ativação', sql(`SELECT COUNT(*) FROM notifications WHERE type='plan_activated' AND user_id=${ID}`), '1');

  // Dono vê o Premium e o selo
  await go(dono, '/dashboard/perfil/plano');
  check('dono: estado Premium com a validade', [await dono.$eval('#plan-state', e => e.dataset.state), (await dono.$eval('#plan-state', e => e.innerText)).includes('Ativo até')], ['premium', true]);
  await go(dono, '/dashboard');
  check('painel: um selo Premium, levando às Configurações', await dono.$$eval('[data-premium-badge]', bs => bs.map(b => new URL(b.href).pathname)), [lib.BASE_PATH + '/dashboard/perfil/plano']);

  // Validade vence sozinha
  sql(`UPDATE users SET plan_until = CURDATE() - INTERVAL 1 DAY WHERE email='${DONO}'`);
  await go(dono, '/dashboard/perfil/plano');
  check('vencido: "Premium vencido" e pode pedir de novo', [await dono.$eval('#plan-state', e => e.dataset.state), !!(await dono.$('#plan-request'))], ['expired', true]);
  await go(dono, '/dashboard');
  check('vencido: sem selo no painel', await dono.$$eval('[data-premium-badge]', bs => bs.length), 0);

  // Admin: sem prazo e depois desativar (com a confirmação do Kolae)
  await go(admin, '/admin');
  await admin.click(`${row(ID)} input[name=no_limit]`);
  await clickNav(admin, `${row(ID)} [data-plan-activate]`);
  check('admin: Premium sem prazo', plan(), 'premium|NULL|sem pedido');
  await admin.click(`${row(ID)} [data-plan-deactivate]`);
  await admin.waitForSelector('.kolae-modal [data-ok]');
  check('desativar pede confirmação', await admin.$eval('.kolae-modal [data-ok]', b => b.textContent.trim()), 'Voltar para o Grátis');
  await clickNav(admin, '.kolae-modal [data-ok]');
  check('admin: voltou para o Grátis', [await toast(admin), plan()], ['A conta voltou para o Grátis.', 'free|NULL|sem pedido']);

  // Dono não usa as rotas do admin
  const r = await dono.evaluate(async (base, id) => {
    const res = await fetch(`${base}/admin/premium/${id}/ativar`, { method: 'POST', body: new FormData() });
    return res.status;
  }, BASE, ID);
  check('dono não ativa pela rota do admin', [r === 403, plan()], [true, 'free|NULL|sem pedido']);

  // Celular
  await dono.setViewport({ width: 390, height: 844, isMobile: true });
  await go(dono, '/dashboard/perfil/plano');
  check('celular: sem rolagem horizontal', await dono.evaluate(() => document.documentElement.scrollWidth), 390);
  await dono.screenshot({ path: lib.OUT + '/plano-celular.png', fullPage: true });

  check('sem erros de JS', [...dono.jsErrors, ...admin.jsErrors], []);
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
  process.exit(fail ? 1 : 0);
})();
