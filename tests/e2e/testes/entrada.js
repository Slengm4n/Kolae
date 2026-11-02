// Teste das telas de entrada (login, cadastro, esqueci a senha e nova senha) depois do redesign:
// mostrar senha, confirmação de senha, e-mail devolvido após erro, idioma mantendo o token,
// botão travado no envio, celular sem rolagem horizontal e tema escuro.
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const BASE = lib.BASE;
const TOKEN = 'teste-e2e-entrada';

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
  p.on('pageerror', e => errs.push(e.message));
  await p.setViewport({ width: 1366, height: 900 });
  await p.goto(BASE + '/login?lang=pt-br', { waitUntil: 'networkidle2' });
  await p.evaluate(() => localStorage.setItem('theme', 'light'));

  // --- Login ---
  await p.goto(BASE + '/login', { waitUntil: 'networkidle2' });
  check('login: um h1 só, com o título do formulário', await p.$$eval('h1', hs => hs.map(h => h.textContent.trim())), ['Bem-vindo de volta']);
  check('login: título da aba', await p.title(), 'Entrar · Kolae');
  check('login: e-mail com foco', await p.evaluate(() => document.activeElement.id), 'email');

  await p.type('#password', 'segredo1');
  await p.click('[data-password-toggle="password"]');
  check('mostrar senha: vira texto e troca o rótulo', await p.$eval('#password', e => e.type), 'text');
  check('mostrar senha: aria', await p.$eval('[data-password-toggle="password"]', e => [e.getAttribute('aria-pressed'), e.getAttribute('aria-label')]), ['true', 'Esconder senha']);
  await p.click('[data-password-toggle="password"]');
  check('esconder senha de novo', await p.$eval('#password', e => e.type), 'password');

  // Senha errada: volta com erro e com o e-mail já preenchido
  await p.type('#email', 'dono@teste.local');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#login-button')]);
  check('senha errada: mensagem de erro', await p.$eval('[role=alert]', e => e.textContent.trim()), 'E-mail ou senha incorretos. Tente novamente.');
  check('senha errada: e-mail devolvido e foco na senha', await p.evaluate(() => [document.getElementById('email').value, document.activeElement.id]), ['dono@teste.local', 'password']);
  lib.sql('DELETE FROM login_attempts');

  // Envio: o botão trava com o texto de espera (o formulário inválido não chega a enviar)
  await p.goto(BASE + '/login', { waitUntil: 'networkidle2' });
  await p.$eval('#login-form', f => { f.addEventListener('submit', e => e.preventDefault()); });
  await p.type('#email', 'x@teste.local'); await p.type('#password', 'qualquer');
  await p.click('#login-button');
  check('envio: botão travado com "Aguarde..."', await p.$eval('#login-button', e => [e.disabled, e.textContent.trim()]), [true, 'Aguarde...']);

  // --- Cadastro ---
  await p.goto(BASE + '/register', { waitUntil: 'networkidle2' });
  check('cadastro: dica da senha', await p.$eval('#password-hint', e => [e.textContent.trim(), e.classList.contains('is-ok')]), ['Pelo menos 8 caracteres', false]);
  await p.type('#password', 'abcdefgh');
  check('cadastro: dica fica verde com 8 caracteres', await p.$eval('#password-hint', e => e.classList.contains('is-ok')), true);
  await p.type('#password_confirmation', 'abcdefg');
  check('confirmação diferente: aviso e campo inválido', await p.evaluate(() => {
    const h = document.getElementById('password_confirmation-match');
    const c = document.getElementById('password_confirmation');
    return [h.hidden || h.classList.contains('hidden'), h.textContent.trim(), c.getAttribute('aria-invalid'), c.validity.valid];
  }), [false, 'As senhas ainda não coincidem', 'true', false]);
  await p.type('#password_confirmation', 'h');
  check('confirmação igual: aviso verde e campo válido', await p.evaluate(() => {
    const h = document.getElementById('password_confirmation-match');
    return [h.classList.contains('is-ok'), h.textContent.trim(), document.getElementById('password_confirmation').validity.valid];
  }), [true, 'As senhas coincidem', true]);
  // Data de nascimento em três listas (dia, mês, ano)
  const ano = new Date().getFullYear();
  check('nascimento: ordem dia, mês, ano em português', await p.$$eval('[data-birthdate] select', s => s.map(e => e.id)), ['birth_day', 'birth_month', 'birth_year']);
  check('nascimento: meses pelo nome', await p.$$eval('#birth_month option', o => [o[1].textContent, o[12].textContent]), ['Janeiro', 'Dezembro']);
  check('nascimento: anos de quem tem 18 até 100', await p.$$eval('#birth_year option', o => [Number(o[1].value), Number(o[o.length - 1].value)]), [ano - 18, ano - 100]);
  const birth = async (d, m, y) => {
    await p.select('#birth_day', String(d)); await p.select('#birth_month', String(m)); await p.select('#birth_year', String(y));
    return p.evaluate(() => [document.getElementById('birthdate').value, document.getElementById('birthdate-hint').textContent.trim()]);
  };
  check('nascimento: 31 de fevereiro não existe', await birth(31, 2, 1990), ['', 'Essa data não existe. Confira o dia.']);
  check('nascimento: dia inválido marcado e bloqueia o envio', await p.$eval('#birth_day', e => [e.getAttribute('aria-invalid'), e.validity.valid]), ['true', false]);
  check('nascimento: data válida monta AAAA-MM-DD', await birth(5, 3, 1990), ['1990-03-05', 'É preciso ter 18 anos ou mais.']);
  // Ano limite: amanhã, há 18 anos, ainda é menor de idade
  const amanha = new Date(); amanha.setDate(amanha.getDate() + 1);
  if (amanha.getFullYear() === ano) {
    check('nascimento: faz 18 só amanhã = menor', (await birth(amanha.getDate(), amanha.getMonth() + 1, ano - 18))[0], '');
    check('nascimento: aviso de menor no lugar da dica', await p.$eval('#birthdate-hint', e => e.classList.contains('is-bad')), true);
  }

  // Menor de idade enviado mesmo assim (sem o JS) volta com a mensagem certa e os dados preenchidos
  await p.goto(BASE + '/register', { waitUntil: 'networkidle2' });
  await p.type('#name', 'Pessoa Menor'); await p.type('#email', 'menor@teste.local');
  await p.select('#birth_day', '1'); await p.select('#birth_month', '1'); await p.select('#birth_year', String(ano - 18));
  await p.type('#password', 'abcdefgh'); await p.type('#password_confirmation', 'abcdefgh');
  await p.$eval('#terms', e => { e.checked = true; });
  await p.$eval('#birthdate', e => { e.value = `${new Date().getFullYear() + 1}-01-01`; }); // data no futuro: antes passava
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#register-button')]);
  check('servidor recusa data no futuro com a mensagem de idade', await p.$eval('[role=alert]', e => e.textContent.trim()), 'Com essa data você ainda não tem 18 anos. O Kolae é para maiores de idade.');
  check('depois do erro: nome, e-mail e data voltam preenchidos', await p.evaluate(() => ['name', 'email', 'birth_day', 'birth_month'].map(id => document.getElementById(id).value)), ['Pessoa Menor', 'menor@teste.local', '1', '1']);
  check('conta não foi criada', lib.sql("SELECT COUNT(*) FROM users WHERE email='menor@teste.local'"), '0');

  // Sem JS: o servidor monta a data a partir das três listas
  const r = await p.evaluate(async (b) => {
    const fd = new FormData(document.getElementById('register-form'));
    fd.set('birthdate', ''); fd.set('birth_day', '10'); fd.set('birth_month', '6'); fd.set('birth_year', '1995');
    fd.set('email', 'semjs@teste.local'); fd.set('password', 'abcdefgh'); fd.set('password_confirmation', 'abcdefgh');
    const res = await fetch(b + '/register/store', { method: 'POST', body: fd, redirect: 'manual' });
    return res.type;
  }, BASE);
  check('sem JS: conta criada com a data das listas', lib.sql("SELECT birthdate FROM users WHERE email='semjs@teste.local'"), '1995-06-10');
  lib.sql("DELETE FROM users WHERE email='semjs@teste.local'");

  // Ordem do inglês: mês, dia, ano
  await p.goto(BASE + '/register?lang=en-us', { waitUntil: 'networkidle2' });
  check('nascimento: ordem mês, dia, ano em inglês', await p.$$eval('[data-birthdate] select', s => s.map(e => e.id)), ['birth_month', 'birth_day', 'birth_year']);
  await p.goto(BASE + '/register?lang=pt-br', { waitUntil: 'networkidle2' });
  check('cadastro: termos com os dois links', await p.$$eval('label[for="terms"] a', as => as.map(a => a.textContent.trim())), ['Termos de uso', 'Política de privacidade']);

  // --- Esqueci a senha ---
  await p.goto(BASE + '/forgot-password?status=sent', { waitUntil: 'networkidle2' });
  check('esqueci: aviso de envio com a dica do spam', await p.$eval('[role=status]', e => /spam/.test(e.textContent)), true);
  check('esqueci: voltar leva ao login', await p.$eval('main a', a => new URL(a.href).pathname), lib.BASE_PATH + '/login');

  // --- Nova senha: trocar o idioma mantém o token ---
  lib.sql(`DELETE FROM password_reset_tokens WHERE email='dono@teste.local'`);
  lib.sql(`INSERT INTO password_reset_tokens (email, token) VALUES ('dono@teste.local', '${TOKEN}')`);
  await p.goto(BASE + '/reset-password?token=' + TOKEN, { waitUntil: 'networkidle2' });
  check('nova senha: abre com o token', await p.$eval('input[name=token]', e => e.value), TOKEN);
  const enLink = await p.$eval('#lang-modal a[lang="en-us"]', a => a.getAttribute('href'));
  check('idioma: link mantém o token', new URLSearchParams(enLink.slice(1)).get('token'), TOKEN);
  await p.click('#lang-btn');
  await new Promise(r => setTimeout(r, 350));
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.click('#lang-modal a[lang="en-us"]')]);
  check('idioma: continua na nova senha, em inglês', [new URL(p.url()).pathname, await p.$eval('input[name=token]', e => e.value), await p.$eval('html', e => e.lang)], [lib.BASE_PATH + '/reset-password', TOKEN, 'en-us']);
  await p.goto(BASE + '/login?lang=pt-br', { waitUntil: 'networkidle2' });
  lib.sql(`DELETE FROM password_reset_tokens WHERE email='dono@teste.local'`);

  // --- Celular e tema escuro ---
  await p.setViewport({ width: 390, height: 844, isMobile: true });
  for (const url of ['/login', '/register', '/forgot-password']) {
    await p.goto(BASE + url, { waitUntil: 'networkidle2' });
    check(`celular ${url}: sem rolagem horizontal`, await p.evaluate(() => document.documentElement.scrollWidth), 390);
  }
  await p.screenshot({ path: lib.OUT + '/entrada-cadastro-celular.png', fullPage: true });
  await p.evaluate(() => localStorage.setItem('theme', 'dark'));
  await p.goto(BASE + '/login', { waitUntil: 'networkidle2' });
  await p.click('#theme-toggle');
  check('botão de tema funciona na tela de entrada', await p.evaluate(() => [document.documentElement.classList.contains('dark'), localStorage.getItem('theme')]), [false, 'light']);
  await p.click('#theme-toggle');
  check('checkbox nativo no tema escuro', await p.$eval('#remember-me', e => getComputedStyle(e).colorScheme), 'dark');
  await new Promise(r => setTimeout(r, 900));
  await p.screenshot({ path: lib.OUT + '/entrada-login-celular-escuro.png' });

  check('sem erros de JS', errs, []);
  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
  process.exit(fail ? 1 : 0);
})();
