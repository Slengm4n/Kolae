// Teste da confirmação de e-mail, versão leve (D58): aviso no painel, reenviar com espera,
// link válido, vencido e adulterado, selo no perfil e o campo na API.
// Contas @teste.local nunca recebem e-mail de verdade (Mailer::isTestAddress).
const { execFileSync } = require('child_process');
const path = require('path');
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const BASE = lib.BASE;
const EMAIL = 'dono@teste.local';

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};
const link = (extra = []) => execFileSync(lib.PHP, [path.join(__dirname, '..', 'link_confirmacao.php'), EMAIL, ...extra]).toString().trim();
const verified = () => lib.sql(`SELECT email_verified_at IS NOT NULL FROM users WHERE email='${EMAIL}'`);

(async () => {
  lib.sql(`UPDATE users SET email_verified_at = NULL WHERE email='${EMAIL}'`);
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  const p = await b.newPage();
  const errs = [];
  p.on('pageerror', e => errs.push(e.message));
  await p.setViewport({ width: 1280, height: 900 });
  await p.goto(BASE + '/login?lang=pt-br', { waitUntil: 'networkidle2' });
  await p.type('#email', EMAIL); await p.type('#password', 'teste123');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);
  const toast = async () => {
    await p.waitForSelector('.kolae-toast', { timeout: 3000 }).catch(() => {});
    return p.$eval('.kolae-toast-text', e => e.textContent).catch(() => '');
  };

  // Aviso no painel
  await p.goto(BASE + '/dashboard', { waitUntil: 'networkidle2' });
  check('painel: aviso de confirmar, com o e-mail', await p.$eval('#email-verify', (e, email) => e.innerText.includes('Confirme seu e-mail.') && e.innerText.includes(email), EMAIL).catch(() => false), true);
  await p.screenshot({ path: lib.OUT + '/confirmacao-aviso.png' });
  await p.goto(BASE + '/dashboard/perfil', { waitUntil: 'networkidle2' });
  check('perfil: "Ainda não confirmado"', await p.$eval('main', m => m.innerText.includes('Ainda não confirmado.')), true);

  // Reenviar, com espera entre envios
  await p.goto(BASE + '/dashboard', { waitUntil: 'networkidle2' });
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#email-verify button[type=submit]')]);
  check('reenviar: link enviado', await toast(), 'Link enviado. Confira sua caixa de entrada e o spam.');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#email-verify button[type=submit]')]);
  check('reenviar de novo logo em seguida: pede para esperar', await toast(), 'Acabamos de enviar um link. Espere alguns minutos para pedir outro.');

  // Link adulterado e vencido não confirmam
  await p.goto(link().replace(/s=([0-9a-f])/, (m, c) => 's=' + (c === 'a' ? 'b' : 'a')), { waitUntil: 'networkidle2' });
  check('link adulterado: recusado', [await toast(), verified()], ['Link de confirmação inválido. Use o link mais recente que enviamos.', '0']);
  await p.goto(link(['vencido']), { waitUntil: 'networkidle2' });
  check('link vencido: recusado', [await toast(), verified()], ['Esse link de confirmação venceu. Peça outro no seu painel.', '0']);

  // Link válido confirma
  await p.goto(link(), { waitUntil: 'networkidle2' });
  check('link válido: confirma e volta ao painel', [new URL(p.url()).pathname, await toast(), verified()], [lib.BASE_PATH + '/dashboard', 'E-mail confirmado. Obrigado!', '1']);
  check('painel: o aviso some', await p.$('#email-verify') === null, true);
  await p.goto(link(), { waitUntil: 'networkidle2' });
  check('usar o link de novo não dá erro', await toast(), 'E-mail confirmado. Obrigado!');
  await p.goto(BASE + '/dashboard/perfil', { waitUntil: 'networkidle2' });
  check('perfil: selo "E-mail confirmado"', await p.$eval('main', m => m.innerText.includes('E-mail confirmado')), true);

  // Deslogado: o link leva ao login com o aviso
  lib.sql(`UPDATE users SET email_verified_at = NULL WHERE email='${EMAIL}'`);
  const ctx = await b.createBrowserContext();
  const o = await ctx.newPage();
  await o.goto(link(), { waitUntil: 'networkidle2' });
  check('deslogado: vai ao login com "E-mail confirmado"', [new URL(o.url()).pathname, await o.$eval('[role=status]', e => e.textContent.trim()).catch(() => '')], [lib.BASE_PATH + '/login', 'E-mail confirmado. Obrigado!']);
  await ctx.close();

  // API: o app recebe o campo
  const token = JSON.parse(execFileSync('curl', ['-s', '-X', 'POST', '-H', 'Content-Type: application/json', '-d', JSON.stringify({ email: EMAIL, password: 'teste123' }), BASE + '/api/v1/auth/login']).toString());
  const me = JSON.parse(execFileSync('curl', ['-s', '-H', 'Authorization: Bearer ' + (token.data?.token || token.token || ''), BASE + '/api/v1/me']).toString());
  check('API /me: email_verified', (me.data || me).email_verified, true);

  check('sem erros de JS', errs, []);
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
  process.exit(fail ? 1 : 0);
})();
