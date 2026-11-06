// Teste do QR Code da quadra (D54). Uso: node tests/e2e/rodar.js qr (ou direto: node tests/e2e/testes/qr.js <id Arena> <id Excluida>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const BASE = lib.BASE;
const [ARENA, EXCLUIDA] = process.argv.slice(2);

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
  await p.setViewport({ width: 1366, height: 900 });
  await p.goto(BASE + '/login?lang=pt-br', { waitUntil: 'networkidle2' });
  await p.evaluate(() => localStorage.setItem('theme', 'light'));
  await p.type('#email', 'dono-s3@kolae.test'); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);

  await p.goto(`${BASE}/dashboard/quadras/${ARENA}/visao-geral`, { waitUntil: 'networkidle2' });
  check('botão QR Code na Visão geral', await p.$eval('[data-qr-open]', b => b.textContent.trim()), 'QR Code');
  await p.click('[data-qr-open]');
  await sleep(800);
  check('janela abre', await p.$eval('#qr-modal', m => !m.classList.contains('hidden')), true);
  check('foco no Fechar', await p.evaluate(() => document.activeElement.id), 'qr-close');
  check('download pronto em PNG', await p.$eval('#qr-download', a => a.href.startsWith('data:image/png')), true);
  check('nome do arquivo', await p.$eval('#qr-download', a => a.getAttribute('download')), `kolae-qr-${ARENA}.png`);

  // Lê o QR desenhado no cartaz
  await p.addScriptTag({ url: 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js' }).catch(() => {});
  const decoded = await p.evaluate(() => {
    if (!window.jsQR) return 'sem jsQR';
    const c = document.getElementById('qr-canvas');
    const img = c.getContext('2d').getImageData(0, 0, c.width, c.height);
    const r = jsQR(img.data, img.width, img.height);
    return r ? r.data : null;
  });
  // ?ref=qr: a visita pelo cartaz conta como origem "QR Code" nas estatísticas (D62)
  check('QR leva à página pública da quadra, marcado como QR', decoded, `${BASE}/quadra/${ARENA}?ref=qr`);
  await p.screenshot({ path: lib.OUT + '/qr-janela.png' });
  // Salva o cartaz como ficou
  const png = await p.$eval('#qr-download', a => a.href.split(',')[1]);
  require('fs').writeFileSync(lib.OUT + '/qr-cartaz.png', Buffer.from(png, 'base64'));

  await p.keyboard.press('Escape'); await sleep(100);
  check('Esc fecha e devolve o foco', [await p.$eval('#qr-modal', m => m.classList.contains('hidden')), await p.evaluate(() => document.activeElement.hasAttribute('data-qr-open'))], [true, true]);
  await p.click('[data-qr-open]'); await sleep(300);
  await p.mouse.click(20, 450); await sleep(100);
  check('clicar fora fecha', await p.$eval('#qr-modal', m => m.classList.contains('hidden')), true);

  // Quadra desativada não tem QR (não tem página pública)
  await p.goto(`${BASE}/dashboard/quadras/${EXCLUIDA}/visao-geral`, { waitUntil: 'networkidle2' }).catch(() => {});
  const url = p.url();
  if (url.includes('visao-geral')) check('desativada: sem botão de QR', !!(await p.$('[data-qr-open]')), false);
  else console.log('     (quadra desativada não abre para o dono; pulando)');

  // Celular
  await p.setViewport({ width: 390, height: 844, isMobile: true });
  await p.goto(`${BASE}/dashboard/quadras/${ARENA}/visao-geral`, { waitUntil: 'networkidle2' });
  await p.click('[data-qr-open]'); await sleep(600);
  check('celular: janela cabe na tela', await p.$eval('#qr-modal > div', d => { const r = d.getBoundingClientRect(); return r.left >= 0 && r.right <= innerWidth; }), true);
  await p.screenshot({ path: lib.OUT + '/qr-celular.png' });

  check('sem erros de JS', errs, []);
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
})();
