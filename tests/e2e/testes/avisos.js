// Teste dos avisos (toast) e da regra do CNPJ (D57).
// - Avisos vindos da URL aparecem como pop-up, com o tipo certo, e a URL fica limpa.
// - Dono sem CNPJ não cria nem gerencia quadra (antes só "criar" conferia).
// Uso: node avisos.js <id da quadra A> (do dono-s3@kolae.test; o rodar.js devolve o CNPJ dele depois)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const BASE = lib.BASE;
const A = process.argv[2];
const DONO = 'dono-s3@kolae.test';

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
  let dialogs = 0;
  p.on('pageerror', e => errs.push(e.message));
  p.on('dialog', d => { dialogs++; d.dismiss(); });
  await p.setViewport({ width: 1280, height: 900 });
  await p.goto(BASE + '/login?lang=pt-br', { waitUntil: 'networkidle2' });
  await p.evaluate(() => localStorage.setItem('theme', 'light'));
  await p.type('#email', DONO); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);

  const toasts = async (url) => {
    await p.goto(BASE + url, { waitUntil: 'networkidle2' });
    await p.waitForSelector('.kolae-toast', { timeout: 3000 }).catch(() => {});
    return p.$$eval('.kolae-toast', ts => ts.map(t => [t.classList.contains('kolae-toast--error') ? 'erro' : 'ok', t.querySelector('.kolae-toast-text').textContent]));
  };

  // Avisos
  check('painel: sucesso do CNPJ', await toasts('/dashboard?status=cnpj_success'), [['ok', 'Parabéns! Seu CNPJ foi validado. Você já pode cadastrar locais.']]);
  check('a URL fica limpa', new URL(p.url()).search, '');
  await p.reload({ waitUntil: 'networkidle2' });
  check('recarregar não repete o aviso', await p.$$eval('.kolae-toast', t => t.length), 0);
  check('painel: código desconhecido vira erro genérico', await toasts('/dashboard?error=qualquer'), [['erro', 'Ocorreu um erro desconhecido.']]);
  check('aviso de erro é lido na hora (role=alert)', await p.$eval('.kolae-toast', t => t.getAttribute('role')), 'alert');
  await p.click('.kolae-toast-close');
  await p.waitForFunction(() => !document.querySelector('.kolae-toast'), { timeout: 2000 }).catch(() => {});
  check('o X fecha o aviso', await p.$$eval('.kolae-toast', t => t.length), 0);
  check('perfil: salvo', await toasts('/dashboard/perfil?status=updated'), [['ok', 'Perfil atualizado com sucesso']]);
  check('perfil: sem a faixa antiga na página', await p.$$eval('main .bg-green-500\\/10', e => e.length), 0);
  check('segurança: senha trocada (antes mostrava o subtítulo)', await toasts('/dashboard/perfil/seguranca?status=success'), [['ok', 'Senha alterada com sucesso.']]);
  check('segurança: senha atual errada', await toasts('/dashboard/perfil/seguranca?error=current_mismatch'), [['erro', 'A senha atual informada está incorreta.']]);
  await p.goto(BASE + '/dashboard?status=cnpj_success', { waitUntil: 'networkidle2' });
  await p.waitForSelector('.kolae-toast');
  await p.screenshot({ path: lib.OUT + '/avisos-toast.png' });

  // Regra do CNPJ: sem CNPJ, nenhuma página de gerenciar quadra abre
  lib.sql(`UPDATE users SET cnpj = NULL WHERE email = '${DONO}'`);
  const pages = ['/dashboard/quadras/criar', `/dashboard/quadras/editar/${A}`, `/dashboard/quadras/${A}/horario`,
    `/dashboard/quadras/${A}/agenda`, `/dashboard/quadras/${A}/fotos`, `/dashboard/quadras/${A}/visao-geral`];
  for (const u of pages) {
    const t = await toasts(u);
    check(`sem CNPJ: ${u} volta ao painel com o aviso`, [new URL(p.url()).pathname, t], [lib.BASE_PATH + '/dashboard', [['erro', 'Valide seu CNPJ para cadastrar e gerenciar quadras.']]]);
  }
  // Envio direto (sem passar pela página) também é barrado
  await p.goto(BASE + '/dashboard/perfil', { waitUntil: 'networkidle2' });
  const posted = await p.evaluate(async (base, id) => {
    const fd = new FormData();
    fd.set('_csrf', document.querySelector('input[name=_csrf]').value);
    fd.set('name', 'Mudou sem CNPJ');
    const r = await fetch(`${base}/dashboard/quadras/atualizar/${id}`, { method: 'POST', body: fd });
    return new URL(r.url).search;
  }, BASE, A);
  check('sem CNPJ: salvar a edição é recusado', [posted, lib.sql(`SELECT name FROM venues WHERE id=${A}`) !== 'Mudou sem CNPJ'], ['?error=cnpj_required', true]);

  check('nenhuma janela do navegador', dialogs, 0);
  check('sem erros de JS', errs, []);
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
  process.exit(fail ? 1 : 0);
})();
