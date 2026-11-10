// Teste de clique do visual comum do admin (D67, parte C): as 12 páginas do admin com o cabeçalho
// novo (item certo marcado, sem o menu lateral antigo, sem "Dashboard", sem erro de JavaScript),
// o botão de tema trocando uma vez só, o menu do celular, o Início com "Precisa de atenção" (pedido
// de Premium e anúncios em aberto) e os números, e as páginas Premium, Anúncios e Fotos.
// Uso: node tests/e2e/rodar.js admin_paginas (ou direto: node tests/e2e/testes/admin_paginas.js <id A>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const path = require('path');
const BASE = lib.BASE;
const [A] = process.argv.slice(2).map(Number);
const sql = lib.sql;

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};

(async () => {
  const dono = Number(sql(`SELECT user_id FROM venues WHERE id=${A}`));
  const adminId = Number(sql("SELECT id FROM users WHERE email='admin@teste.local'"));
  const sportId = Number(sql('SELECT MIN(id) FROM sports'));
  const plano = sql(`SELECT CONCAT_WS('|', plan, IFNULL(plan_until, 'NULL'), IFNULL(plan_requested_at, 'NULL')) FROM users WHERE id=${dono}`);
  sql(`UPDATE users SET plan='free', plan_until=NULL, plan_requested_at=NULL WHERE id=${dono}`);
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  try {
    const ctx = await b.createBrowserContext();
    const p = await ctx.newPage();
    await p.setViewport({ width: 1440, height: 900 });
    const errors = [];
    // A animação de troca de tela (View Transitions) é cancelada pelo navegador quando o teste
    // navega mais rápido que uma pessoa; esse aviso não é erro do Kolae. Qualquer outro erro conta.
    p.on('pageerror', e => { if (!/Transition was aborted/.test(e.message)) errors.push(`${p.url()}: ${e.message}`); });
    await p.goto(`${BASE}/login?lang=pt-br`, { waitUntil: 'networkidle2' });
    await p.type('#email', 'admin@teste.local'); await p.type('#password', 'teste123');
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);

    // --- As 12 páginas com o cabeçalho comum ---
    const pages = [
      ['/admin', 'inicio'], ['/admin/usuarios', 'usuarios'], ['/admin/usuarios/criar', 'usuarios'],
      [`/admin/usuarios/editar/${adminId}`, 'usuarios'], ['/admin/esportes', 'esportes'], ['/admin/esportes/criar', 'esportes'],
      [`/admin/esportes/editar/${sportId}`, 'esportes'], ['/admin/quadras', 'quadras'], ['/admin/mapa', 'mapa'],
      ['/admin/premium', 'premium'], ['/admin/anuncios', 'anuncios'], ['/admin/fotos', 'fotos'],
    ];
    for (const [url, active] of pages) {
      const r = await p.goto(BASE + url, { waitUntil: 'networkidle2' });
      const info = await p.evaluate(() => ({
        header: !!document.querySelector('[data-admin-header]'),
        current: (document.querySelector('#admin-nav [aria-current=page]') || {}).dataset?.adminNav || null,
        oldMenu: !!document.querySelector('#sidebar, #sidebar-toggle, #theme-toggle'),
        dashboard: /\bDashboard\b/.test(document.body.innerText),
        ui: !!document.querySelector('#kolae-ui'),
      }));
      check(`${url}: cabeçalho novo, "${active}" marcado, sem o menu antigo`, [r.status(), info.header, info.current, info.oldMenu, info.dashboard, info.ui], [200, true, active, false, false, true]);
    }
    check('nenhum erro de JavaScript nas 12 páginas', errors, []);
    // O menu de cima precisa aparecer no computador (não basta existir no HTML: sem o CSS
    // compilado, a classe xl:flex não existe e o menu fica escondido)
    await p.goto(`${BASE}/admin`, { waitUntil: 'networkidle2' });
    check('computador: menu de cima visível, com os 8 itens', await p.$eval('#admin-nav', e => [getComputedStyle(e).display, e.getBoundingClientRect().height > 0, e.querySelectorAll('a').length]), ['flex', true, 8]);

    // --- Tema: um clique troca uma vez (o código antigo de tema saiu das páginas) ---
    await p.goto(`${BASE}/admin/esportes`, { waitUntil: 'networkidle2' });
    const before = await p.evaluate(() => document.documentElement.classList.contains('dark'));
    await p.click('#admin-theme-toggle');
    const after = await p.evaluate(() => [document.documentElement.classList.contains('dark'), localStorage.theme]);
    check('botão de tema troca o tema uma vez e guarda a escolha', after, [!before, !before ? 'dark' : 'light']);
    await p.click('#admin-theme-toggle');
    check('outro clique volta', await p.evaluate(() => document.documentElement.classList.contains('dark')), before);

    // --- Início: Precisa de atenção e números ---
    sql(`UPDATE users SET plan_requested_at = NOW() WHERE id=${dono}`);
    const abertos = Number(sql("SELECT COUNT(*) FROM venue_ads a JOIN venues v ON v.id=a.venue_id WHERE a.status IN ('active','paused') AND a.ends_on >= CURDATE() AND v.deleted_at IS NULL"));
    await p.goto(`${BASE}/admin`, { waitUntil: 'networkidle2' });
    const att = await p.$$eval('#attention [data-attention]', els => Object.fromEntries(els.map(e => [e.dataset.attention, [Number(e.dataset.value), e.getAttribute('href').replace(/^.*\/admin/, '/admin')]])));
    check('atenção: o pedido de Premium, levando à página Premium', att.premium, [1, '/admin/premium']);
    check('atenção: anúncios em aberto, se houver', abertos ? att.ads : null, abertos ? [abertos, '/admin/anuncios'] : null);
    check('número no item Premium do menu', await p.$eval('#admin-nav [data-admin-nav="premium"]', e => [e.firstChild.textContent.trim(), (e.querySelector('span') || {}).textContent]), ['Premium', '1']);
    check('números do Kolae (usuários, quadras, esportes)', await p.$$eval('#numbers [data-number]', els => els.map(e => e.dataset.number)), ['Usuários', 'Quadras', 'Esportes']);
    check('usuários recentes com o papel em português', await p.$eval('#recent-users', e => !/\b(owner|player)\b/.test(e.innerText) && /Dono|Jogador|Admin/.test(e.innerText)), true);
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#attention [data-attention="premium"]')]);
    check('clicar leva à página Premium com o pedido', await p.$eval('#premium', e => e.innerText.includes('Pediu em')), true);
    sql(`UPDATE users SET plan_requested_at = NULL WHERE id=${dono}`);
    await p.goto(`${BASE}/admin`, { waitUntil: 'networkidle2' });
    check('sem o pedido, o item some', await p.$('#attention [data-attention="premium"]').then(Boolean), false);

    // --- Fotos ---
    await p.goto(`${BASE}/admin/fotos`, { waitUntil: 'networkidle2' });
    check('página Fotos com o botão de otimizar', await p.$eval('#fotos button[type=submit]', e => e.innerText.trim()), 'Otimizar fotos');

    // --- Celular: menu com as páginas ---
    await p.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
    await p.goto(`${BASE}/admin`, { waitUntil: 'networkidle2' });
    check('celular: menu de cima escondido, sem rolagem para o lado', [await p.$eval('#admin-nav', e => getComputedStyle(e).display), await p.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)], ['none', true]);
    await p.click('#admin-menu-button');
    const menu = await p.$eval('#admin-menu', e => [!e.classList.contains('hidden'), [...e.querySelectorAll('a')].map(a => a.innerText.trim()).filter(Boolean)]);
    check('celular: o menu abre com as 8 páginas e o Sair', [menu[0], menu[1].length], [true, 9]);
    await p.keyboard.press('Escape');
    check('Esc fecha o menu', await p.$eval('#admin-menu', e => e.classList.contains('hidden')), true);
    await p.screenshot({ path: path.join(lib.OUT, 'admin_inicio_celular.png'), fullPage: true });
    await p.setViewport({ width: 1440, height: 900 });
    await p.goto(`${BASE}/admin`, { waitUntil: 'networkidle2' });
    await p.screenshot({ path: path.join(lib.OUT, 'admin_inicio.png'), fullPage: true });
    await p.goto(`${BASE}/admin/usuarios`, { waitUntil: 'networkidle2' });
    await p.screenshot({ path: path.join(lib.OUT, 'admin_usuarios.png') });
  } finally {
    const [plan, until, req] = plano.split('|');
    sql(`UPDATE users SET plan='${plan}', plan_until=${until === 'NULL' ? 'NULL' : `'${until}'`}, plan_requested_at=${req === 'NULL' ? 'NULL' : `'${req}'`} WHERE id=${dono}`);
    await b.close();
  }
  console.log(fail ? `\n${fail} FALHA(S)` : '\nTodos os passos passaram');
  process.exit(fail ? 1 : 0);
})();
