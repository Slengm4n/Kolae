// Teste da otimização das fotos (D48). Uso: node tests/e2e/rodar.js otimizar (ou direto: node tests/e2e/testes/otimizar.js <id Arena> <id Beach>)
const lib = require('../lib');
const puppeteer = require('puppeteer-core');
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const BASE = lib.BASE;
const ROOT = lib.ROOT + '';
const [ARENA, BEACH] = process.argv.slice(2);
const DIR = lib.FOTOS;
const PHP = lib.PHP;
const sql = q => execFileSync(lib.MYSQL,
  ['--default-character-set=utf8mb4', '-uroot', '-N', lib.DB, '-e', q]).toString().split(String.fromCharCode(13)).join('').trim();
// [largura, altura, mime, bytes] de um arquivo, lido pelo PHP
const info = file => JSON.parse(execFileSync(PHP, ['-r', '$i = getimagesize($argv[1]); echo json_encode([$i[0], $i[1], $i["mime"], filesize($argv[1])]);', file]).toString());
// Cor média da faixa de cima e de baixo (para saber se girou)
const colors = file => JSON.parse(execFileSync(PHP, ['-r', '$im = imagecreatefromjpeg($argv[1]); $w = imagesx($im); $h = imagesy($im); $px = fn($x, $y) => (imagecolorat($im, $x, $y) >> 16) & 255; echo json_encode(["left" => $px(5, (int)($h/2)), "right" => $px($w-6, (int)($h/2)), "top" => $px((int)($w/2), 5)]);', file]).toString());

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};

(async () => {
  for (const v of [ARENA, BEACH]) {
    sql(`DELETE FROM venues_images WHERE venue_id=${v}`);
    fs.rmSync(`${ROOT}/uploads/venues/${v}`, { recursive: true, force: true });
  }
  const b = await puppeteer.launch({ executablePath: lib.BROWSER, headless: true });
  const login = async (email) => {
    const p = await (await b.createBrowserContext()).newPage();
    await p.goto(BASE + '/login?lang=pt-br', { waitUntil: 'networkidle2' });
    await p.type('#email', email); await p.type('#password', 'teste123');
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }), p.$eval('#login-form', f => f.submit())]);
    return p;
  };

  // Upload pela aba Fotos
  const p = await login('dono-s3@kolae.test');
  await p.goto(`${BASE}/dashboard/quadras/${ARENA}/fotos`, { waitUntil: 'networkidle2' });
  await (await p.$('#images')).uploadFile(path.join(DIR, 'celular_girada.jpg'), path.join(DIR, 'grande.png'), path.join(DIR, 'foto1.jpg'));
  await p.waitForSelector('#chosen:not(.hidden)');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2', timeout: 60000 }), p.click('#upload-btn')]);
  check('envio: 3 fotos enviadas', await p.$eval('[role=status]', e => e.textContent.trim()), '3 fotos enviadas.');
  const files = sql(`SELECT file_path FROM venues_images WHERE venue_id=${ARENA} ORDER BY position`).split('\n');
  check('todas viram .jpg', files.map(f => f.endsWith('.jpg')), [true, true, true]);
  const [cel, png, small] = files.map(f => info(`${ROOT}/uploads/venues/${ARENA}/${f}`));
  console.log('     celular:', cel, ' png:', png, ' pequena:', small);
  check('foto de celular girada: em pé, 1200x1600', [cel[0], cel[1]], [1200, 1600]);
  check('foto de celular: de 6,7 MB para menos de 600 KB', cel[3] < 600 * 1024, true);
  const c = colors(`${ROOT}/uploads/venues/${ARENA}/${files[0]}`);
  check('girou para o lado certo (vermelho à direita, azul à esquerda)', [c.right > 150, c.left < 100], [true, true]);
  check('PNG grande vira JPEG 1600x1067', [png[0], png[1], png[2]], [1600, 1067, 'image/jpeg']);
  check('foto pequena não aumenta (800x600)', [small[0], small[1]], [800, 600]);
  check('app recebe as fotos otimizadas', JSON.parse(execFileSync('curl', ['-s', `${BASE}/api/v1/venues/${ARENA}`]).toString()).data.images.every(u => u.endsWith('.jpg')), true);

  // Fotos antigas, pesadas, direto no servidor (como as da produção)
  const dir = `${ROOT}/uploads/venues/${BEACH}`;
  fs.mkdirSync(dir, { recursive: true });
  fs.copyFileSync(path.join(DIR, 'celular_girada.jpg'), `${dir}/venue_antiga1.jpg`);
  fs.copyFileSync(path.join(DIR, 'grande.png'), `${dir}/venue_antiga2.png`);
  fs.copyFileSync(path.join(DIR, 'foto3.jpg'), `${dir}/venue_leve.jpg`);
  sql(`INSERT INTO venues_images (venue_id, file_path, position) VALUES (${BEACH}, 'venue_antiga1.jpg', 0), (${BEACH}, 'venue_antiga2.png', 1), (${BEACH}, 'venue_leve.jpg', 2), (${BEACH}, 'venue_sumiu.jpg', 3)`);
  const beforeOrder = sql(`SELECT GROUP_CONCAT(id ORDER BY position) FROM venues_images WHERE venue_id=${BEACH}`);

  const a = await login('admin@teste.local');
  await a.goto(`${BASE}/admin/fotos`, { waitUntil: 'networkidle2' });
  const card = await a.$eval('#fotos', e => e.innerText);
  check('card mostra as pesadas', /\d+ pesada\(s\)/.test(card), true);
  await Promise.all([a.waitForNavigation({ waitUntil: 'networkidle2', timeout: 120000 }), a.click('#fotos button[type=submit]')]);
  const report = await a.$eval('#fotos [role=status]', e => e.textContent.replace(/\s+/g, ' ').trim());
  console.log('     resumo:', report);
  check('resumo: otimizou pelo menos as 2 antigas', Number((report.match(/^(\d+) otimizada/) || [])[1]) >= 2, true);
  check('resumo mostra a economia', /Economia de [\d,]+ MB/.test(report), true);
  const after = sql(`SELECT file_path FROM venues_images WHERE venue_id=${BEACH} ORDER BY position`).split('\n');
  check('antigas trocadas por .jpg novos; leve e sumida intactas', [after[0].startsWith('venue_') && after[0] !== 'venue_antiga1.jpg' && after[0].endsWith('.jpg'), after[1].endsWith('.jpg') && after[1] !== 'venue_antiga2.png', after[2], after[3]], [true, true, 'venue_leve.jpg', 'venue_sumiu.jpg']);
  check('ordem e capa mantidas', sql(`SELECT GROUP_CONCAT(id ORDER BY position) FROM venues_images WHERE venue_id=${BEACH}`), beforeOrder);
  check('arquivos antigos apagados', [fs.existsSync(`${dir}/venue_antiga1.jpg`), fs.existsSync(`${dir}/venue_antiga2.png`)], [false, false]);
  check('antiga girada também fica em pé', info(`${dir}/${after[0]}`).slice(0, 2), [1200, 1600]);
  check('card sem pesadas depois', await a.$eval('#fotos', e => !/pesada/.test(e.innerText)), true);

  // Rodar de novo não mexe em nada
  await Promise.all([a.waitForNavigation({ waitUntil: 'networkidle2' }), a.click('#fotos button[type=submit]')]);
  check('rodar de novo: 0 otimizadas', /^0 otimizada\(s\)/.test(await a.$eval('#fotos [role=status]', e => e.textContent.trim())), true);
  check('arquivos iguais', sql(`SELECT file_path FROM venues_images WHERE venue_id=${BEACH} ORDER BY position`).split('\n'), after);

  // Só admin; com CSRF
  const r = execFileSync('curl', ['-s', '-o', 'NUL', '-w', '%{http_code}', '-X', 'POST', `${BASE}/admin/fotos/otimizar`]).toString();
  check('sem token CSRF: 403', r, '403');
  await p.goto(`${BASE}/dashboard/perfil`, { waitUntil: 'networkidle2' });
  const status = await p.evaluate(async () => {
    const token = document.querySelector('input[name=_csrf]').value;
    const fd = new FormData(); fd.append('_csrf', token);
    const res = await fetch(document.querySelector('link[href*="/assets/css/"]').href.split('/assets/')[0] + '/admin/fotos/otimizar', { method: 'POST', body: fd });
    return res.status;
  });
  check('dono (não admin) recebe 403', status, 403);

  // Avatar: agora reduz de verdade (antes gravava o original)
  await p.goto(`${BASE}/dashboard/perfil`, { waitUntil: 'networkidle2' });
  const avatarInput = await p.$('input[type=file][name=avatar]');
  if (avatarInput) {
    await avatarInput.uploadFile(path.join(DIR, 'celular_girada.jpg'));
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2', timeout: 60000 }), p.$eval('input[name=avatar]', i => i.form.submit())]);
    const av = sql(`SELECT avatar_path FROM users WHERE email='dono-s3@kolae.test'`);
    const avFile = execFileSync(PHP, ['-r', '$f = glob($argv[1] . "/uploads/avatars/*/" . basename($argv[2])); echo $f[0] ?? "";', ROOT, av]).toString();
    const ai = avFile ? info(avFile) : null;
    console.log('     avatar:', av, ai);
    check('avatar reduzido para 800 de largura e em pé', ai && [ai[0], ai[1]], [600, 800]);
  } else {
    console.log('     (perfil sem input de avatar com name=avatar; pulando)');
  }

  console.log(`\n${fail ? fail + ' FALHA(S)' : 'TUDO OK'}`);
  await b.close();
})();
