// Teste da S5: CSRF, lembrar de mim, senha mínima, limite de tentativas (site e API), XSS do mapa.
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const { execFileSync } = require('child_process');
const BASE = lib.BASE;
const sql = q => execFileSync(lib.MYSQL,
  ['--default-character-set=utf8mb4', '-uroot', '-N', lib.DB, '-e', q]).toString().split(String.fromCharCode(13)).join('').trim();
const curl = args => execFileSync('curl', ['-s', ...args]).toString();
const apiLogin = (password, extra = []) => curl([...extra, '-H', 'Content-Type: application/json',
  '-d', JSON.stringify({ email: DONO, password }), BASE + '/api/v1/auth/login']);
const code = ['-o', 'NUL', '-w', '%{http_code}'];

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};
const DONO = 'dono-s3@kolae.test';
const errCount = () => sql(`SELECT COUNT(*) FROM login_attempts WHERE email_hash=SHA2('${DONO}',256)`);

(async () => {
  sql('DELETE FROM login_attempts');
  const userId = sql(`SELECT id FROM users WHERE email='${DONO}'`);
  sql(`DELETE FROM user_tokens WHERE user_id=${userId}`);

  // --- CSRF ---
  check('POST sem token CSRF -> 403', curl([...code, '-X', 'POST', '-d', `email=${DONO}&password=teste123`, BASE + '/login/authenticate']), '403');
  check('POST com token errado -> 403', curl([...code, '-X', 'POST', '-d', `_csrf=abc&email=${DONO}&password=teste123`, BASE + '/login/authenticate']), '403');
  check('API não exige CSRF (login 200)', apiLogin('teste123', code), '200');

  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  const p = await b.newPage();
  const errs = [];
  p.on('pageerror', e => errs.push(e.message));
  await p.setViewport({ width: 1366, height: 900 });
  const submit = sel => Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval(sel, f => f.submit())]);
  const path = () => new URL(p.url()).pathname;

  // --- Login pelo formulário (com token) + lembrar de mim ---
  await p.goto(BASE + '/login?lang=pt-br', { waitUntil: 'networkidle2' });
  check('formulário de login tem o campo _csrf', await p.$eval('#login-form input[name=_csrf]', e => e.value.length), 64);
  await p.type('#email', DONO); await p.type('#password', 'teste123');
  await p.$eval('input[name="remember-me"]', e => { e.checked = true; });
  await submit('#login-form');
  check('login com token entra no painel', path(), lib.BASE_PATH + '/dashboard');
  check('lembrar de mim grava 1 linha em user_tokens', sql(`SELECT COUNT(*) FROM user_tokens WHERE user_id=${userId}`), '1');
  let cookies = await p.cookies();
  const rm = cookies.find(c => c.name === 'remember_me');
  check('cookie remember_me no formato selector:validator', !!rm && /^[0-9a-f]{32}(:|%3A)[0-9a-f]{64}$/.test(rm.value), true);
  check('cookie remember_me é HttpOnly', !!rm && rm.httpOnly, true);

  // Simula fechar o navegador: apaga só o cookie de sessão
  const sess = cookies.find(c => c.name === 'PHPSESSID');
  await p.deleteCookie({ name: sess.name, domain: sess.domain, path: sess.path });
  await p.goto(BASE + '/dashboard', { waitUntil: 'networkidle2' });
  check('sem sessão, o lembrar de mim loga de novo', path(), lib.BASE_PATH + '/dashboard');

  // Formulário enviado depois do login automático (token da sessão nova)
  const venueId = sql(`SELECT id FROM venues WHERE user_id=${userId} ORDER BY id LIMIT 1`);
  await p.goto(`${BASE}/dashboard/quadras/${venueId}/horario`, { waitUntil: 'networkidle2' });
  await submit('form[method="POST"]');
  check('salvar horário com token continua funcionando', await p.$eval('body', e => !e.innerText.includes('Página expirada')), true);

  await p.goto(BASE + '/logout', { waitUntil: 'networkidle2' });
  check('logout apaga o token do banco', sql(`SELECT COUNT(*) FROM user_tokens WHERE user_id=${userId}`), '0');
  cookies = await p.cookies();
  check('logout apaga o cookie remember_me', cookies.some(c => c.name === 'remember_me'), false);
  await p.goto(BASE + '/dashboard', { waitUntil: 'networkidle2' });
  check('depois do logout o painel pede login', path(), lib.BASE_PATH + '/login');

  // Cookie forjado não loga
  await p.setCookie({ name: 'remember_me', value: 'a'.repeat(32) + ':' + 'b'.repeat(64), url: BASE });
  await p.goto(BASE + '/dashboard', { waitUntil: 'networkidle2' });
  check('cookie forjado não loga', path(), lib.BASE_PATH + '/login');
  await p.deleteCookie({ name: 'remember_me', url: BASE });

  // --- Página 419 ---
  await p.goto(BASE + '/login', { waitUntil: 'networkidle2' });
  await p.$eval('#login-form input[name=_csrf]', e => { e.value = 'x'; });
  await p.type('#email', DONO); await p.type('#password', 'teste123');
  await submit('#login-form');
  check('token adulterado mostra "Página expirada"', await p.$eval('h1', e => e.textContent), 'Página expirada');
  await p.screenshot({ path: lib.OUT + '/s5-419.png' });
  check('botão Voltar leva à página de login', await p.$eval('main a', a => new URL(a.href).pathname), lib.BASE_PATH + '/login');

  // --- Senha mínima no cadastro ---
  await p.goto(BASE + '/register', { waitUntil: 'networkidle2' });
  check('campo de senha com minlength 8', await p.$eval('#password', e => e.minLength), 8);
  check('dica diz 8 caracteres', await p.$eval('#password', e => e.placeholder), 'Mínimo de 8 caracteres');
  const email = `s5-${Date.now()}@kolae.test`;
  await p.$eval('#password', e => e.removeAttribute('minlength'));
  await p.$eval('#password_confirmation', e => e.removeAttribute('minlength'));
  await p.type('#name', 'Teste S5'); await p.type('#email', email);
  await p.$eval('#birthdate', e => { e.value = '1990-01-01'; });
  await p.type('#password', '1234567'); await p.type('#password_confirmation', '1234567');
  await p.$eval('#terms', e => { e.checked = true; });
  await submit('form[method="POST"]');
  check('senha de 7 caracteres é recusada no servidor', new URL(p.url()).searchParams.get('error'), 'weak_password');
  check('mensagem de senha curta aparece', await p.$eval('body', e => e.innerText.includes('pelo menos 8 caracteres')), true);
  check('conta não foi criada', sql(`SELECT COUNT(*) FROM users WHERE email='${email}'`), '0');

  // --- Limite de tentativas no site ---
  sql('DELETE FROM login_attempts');
  for (let i = 1; i <= 5; i++) {
    await p.goto(BASE + '/login', { waitUntil: 'networkidle2' });
    await p.type('#email', DONO); await p.type('#password', 'errada' + i);
    await submit('#login-form');
  }
  check('5 erros gravados (e-mail só como hash)', errCount(), '5');
  await p.goto(BASE + '/login', { waitUntil: 'networkidle2' });
  await p.type('#email', DONO); await p.type('#password', 'teste123');
  await submit('#login-form');
  check('6ª tentativa, mesmo com a senha certa, é bloqueada', new URL(p.url()).searchParams.get('error'), 'too_many');
  check('mensagem mostra os minutos', await p.$eval('body', e => /Tente de novo em 15 min/.test(e.innerText)), true);
  await p.screenshot({ path: lib.OUT + '/s5-limite.png' });

  // --- Limite na API ---
  const r = apiLogin('teste123', ['-i']);
  check('API bloqueada responde 429', / 429 /.test(r.split('\n')[0]), true);
  check('API manda Retry-After', /Retry-After: \d+/i.test(r), true);
  check('API code TOO_MANY_ATTEMPTS', /"code": "TOO_MANY_ATTEMPTS"/.test(r), true);

  // Expira a janela: login volta a funcionar e zera os erros
  sql('UPDATE login_attempts SET created_at = NOW() - INTERVAL 16 MINUTE');
  check('depois de 15 min o login volta (200)', apiLogin('teste123', code), '200');
  check('login certo zera os erros daquela conta', errCount(), '0');
  check('senha errada na API: 401 e conta como erro', [apiLogin('x', code), errCount()], ['401', '1']);
  sql('DELETE FROM login_attempts');

  // --- XSS no mapa do admin ---
  const evil = '</script><img src=x onerror=window.__xss=1>';
  sql(`UPDATE venues SET name='${evil}' WHERE id=${venueId}`);
  await p.goto(BASE + '/login', { waitUntil: 'networkidle2' });
  await p.type('#email', 'admin@teste.local'); await p.type('#password', 'teste123');
  await submit('#login-form');
  await p.goto(BASE + '/admin/mapa', { waitUntil: 'networkidle2' });
  await new Promise(r => setTimeout(r, 800));
  check('nome malicioso não quebra o <script> do mapa', await p.evaluate(() => typeof venues !== 'undefined' && venues.some(v => v.name.includes('onerror'))), true);
  const searchInput = await p.$('input[type="text"], input[type="search"]');
  if (searchInput) { await searchInput.type('img'); await new Promise(r => setTimeout(r, 400)); }
  check('busca do mapa mostra o nome como texto', await p.evaluate(() => window.__xss === undefined), true);
  sql(`UPDATE venues SET name='Quadra Teste A' WHERE id=${venueId}`);

  check('sem erros de JS', errs.filter(e => !/google|maps/i.test(e)), []);
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
})();
