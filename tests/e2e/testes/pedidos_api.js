// Teste da API dos pedidos de horário e notificações (D64, parte B), sem navegador: só chamadas
// HTTP, seguindo o contrato do docs/api.md. Pedir (token, e-mail confirmado, própria quadra, quadra
// desativada, erros por campo, conflito, esporte, limites), listar (página, filtro), cancelar
// (pendente, aprovado com e sem antecedência, de outra pessoa), expirar sozinho e notificações
// (idioma, não lidas, marcar como lidas). A resposta do dono é gravada direto no banco: o painel
// dele é a parte C.
// Uso: node tests/e2e/rodar.js pedidos_api (ou direto: node tests/e2e/testes/pedidos_api.js <ids A B C D E>)
const lib = require('../lib');
const API = `${lib.BASE}/api/v1`;
const [A, B, C, D, E] = process.argv.slice(2).map(Number);
const sql = lib.sql;

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};
const TEST_VENUES = [A, B, C, D, E].join(',');
const brt = (days = 0, hm = '19:00') => { const d = new Date(Date.now() - 3 * 3600e3); d.setUTCDate(d.getUTCDate() + days); return `${d.toISOString().slice(0, 10)}T${hm}:00-03:00`; };
const db = iso => iso.slice(0, 19).replace('T', ' ');
const call = async (method, path, token, body, headers = {}) => {
  const h = { ...headers };
  if (token) h.Authorization = `Bearer ${token}`;
  if (body !== undefined) h['Content-Type'] = 'application/json';
  const r = await fetch(API + path, { method, headers: h, body: body === undefined ? undefined : JSON.stringify(body) });
  const text = await r.text();
  let json = null;
  try { json = text ? JSON.parse(text) : null; } catch (e) { json = { raw: text }; }
  return { status: r.status, json };
};
const login = async email => (await call('POST', '/auth/login', null, { email, password: 'teste123' })).json.data.token;
const userId = email => Number(sql(`SELECT id FROM users WHERE email='${email}'`));
// JSON só com ASCII para o lib.sql: a linha de comando do Windows perde os acentos e o MySQL come
// uma barra invertida; então o "ç" vai como \\u00e7 e o banco guarda \u00e7 (o PHP lê o "ç" de volta)
const asciiJson = o => JSON.stringify(o).replace(/[^\x00-\x7f]/g, c => '\\\\u' + c.charCodeAt(0).toString(16).padStart(4, '0'));
const fieldErr = r => [r.status, r.json.code, Object.keys(r.json.errors || {})];

(async () => {
  const ana = userId('dono@teste.local'); // outro dono, aqui no papel de quem pede
  const dono = userId('dono-s3@kolae.test');
  const lastNotif = Number(sql('SELECT IFNULL(MAX(id), 0) FROM notifications'));
  const verifAna = sql(`SELECT IFNULL(email_verified_at, 'NULL') FROM users WHERE id=${ana}`);
  const partidas = [];
  sql(`DELETE FROM match_requests WHERE venue_id IN (${TEST_VENUES})`);
  sql(`UPDATE users SET email_verified_at = NULL WHERE id = ${ana}`);
  try {
    const t = await login('dono@teste.local');
    const tDono = await login('dono-s3@kolae.test');
    const pedido = (over = {}) => ({ start_time: brt(2, '19:00'), end_time: brt(2, '20:00'), message: 'Somos 10', ...over });

    // --- Token, e-mail, própria quadra, quadra desativada ---
    check('sem token: 401', (await call('POST', `/venues/${A}/requests`, null, pedido())).status, 401);
    check('e-mail não confirmado: 403 EMAIL_NOT_VERIFIED', (await call('POST', `/venues/${A}/requests`, t, pedido())).json.code, 'EMAIL_NOT_VERIFIED');
    sql(`UPDATE users SET email_verified_at = NOW() WHERE id = ${ana}`);
    check('dono na própria quadra: 403 OWN_VENUE', (await call('POST', `/venues/${A}/requests`, tDono, pedido())).json.code, 'OWN_VENUE');
    check('quadra desativada: 404 NOT_FOUND', [(await call('POST', `/venues/${E}/requests`, t, pedido())).status, (await call('POST', '/venues/99999999/requests', t, pedido())).json.code], [404, 'NOT_FOUND']);

    // --- Erros por campo ---
    check('sem horário: 422 com os dois campos', fieldErr(await call('POST', `/venues/${A}/requests`, t, {})), [422, 'VALIDATION_ERROR', ['start_time', 'end_time']]);
    check('erros vêm como lista de textos', Array.isArray((await call('POST', `/venues/${A}/requests`, t, {})).json.errors.start_time), true);
    check('fora do bloco de 30 min', fieldErr(await call('POST', `/venues/${A}/requests`, t, pedido({ start_time: brt(2, '19:10') }))), [422, 'VALIDATION_ERROR', ['start_time']]);
    check('no passado', fieldErr(await call('POST', `/venues/${A}/requests`, t, pedido({ start_time: brt(-1, '10:00'), end_time: brt(-1, '11:00') }))), [422, 'VALIDATION_ERROR', ['start_time']]);
    check('mais de 30 dias à frente', fieldErr(await call('POST', `/venues/${A}/requests`, t, pedido({ start_time: brt(31, '10:00'), end_time: brt(31, '11:00') }))), [422, 'VALIDATION_ERROR', ['start_time']]);
    check('mais de 4 horas', fieldErr(await call('POST', `/venues/${A}/requests`, t, pedido({ start_time: brt(2, '08:00'), end_time: brt(2, '12:30') }))), [422, 'VALIDATION_ERROR', ['end_time']]);
    check('quadra sem horário cadastrado (Duda): fechada', (await call('POST', `/venues/${D}/requests`, t, pedido())).json.errors.start_time, ['A quadra não abre neste dia.']);
    check('esporte que a quadra não tem', fieldErr(await call('POST', `/venues/${A}/requests`, t, pedido({ sport_id: 99999 }))), [422, 'VALIDATION_ERROR', ['sport_id']]);
    check('mensagem com 201 caracteres', fieldErr(await call('POST', `/venues/${A}/requests`, t, pedido({ message: 'x'.repeat(201) }))), [422, 'VALIDATION_ERROR', ['message']]);
    sql(`INSERT INTO matches (venue_id, creator_user_id, status, start_time, end_time) VALUES (${B}, ${dono}, 'scheduled', '${db(brt(2, '19:00'))}', '${db(brt(2, '20:00'))}')`);
    partidas.push(Number(sql(`SELECT MAX(id) FROM matches WHERE venue_id=${B}`)));
    check('horário ocupado na agenda: 422', (await call('POST', `/venues/${B}/requests`, t, pedido({ start_time: brt(2, '19:30'), end_time: brt(2, '20:30') }))).json.errors.start_time, ['Este horário já está ocupado. Escolha uma faixa livre.']);
    check('nada foi gravado com erro', Number(sql(`SELECT COUNT(*) FROM match_requests WHERE user_id=${ana}`)), 0);

    // --- Pedir ---
    const sportA = sql(`SELECT IFNULL(MIN(sport_id), 'NULL') FROM venue_sports WHERE venue_id=${A}`);
    const novo = await call('POST', `/venues/${A}/requests`, t, pedido(sportA !== 'NULL' ? { sport_id: Number(sportA) } : {}));
    check('pedir: 201 com o objeto do contrato', [novo.status, Object.keys(novo.json.data || {})], [201, ['id', 'venue', 'sport_id', 'start_time', 'end_time', 'message', 'status', 'response_note', 'created_at', 'responded_at']]);
    const r1 = novo.json.data;
    check('valores', [r1.venue.id, Object.keys(r1.venue), r1.start_time, r1.end_time, r1.message, r1.status, r1.response_note, r1.responded_at], [A, ['id', 'name', 'cover_image_url'], brt(2, '19:00'), brt(2, '20:00'), 'Somos 10', 'pending', null, null]);
    check('o dono recebe "novo pedido"', sql(`SELECT type FROM notifications WHERE id > ${lastNotif} AND user_id = ${dono} ORDER BY id DESC LIMIT 1`), 'request_new');
    check('não bloqueia a agenda: outra pessoa pode pedir o mesmo horário (pedido pendente)', Number(sql(`SELECT COUNT(*) FROM matches WHERE venue_id=${A} AND start_time='${db(brt(2, '19:00'))}' AND status='scheduled'`)), 0);

    // --- Limites ---
    check('1 pendente por quadra: 409 REQUEST_LIMIT', (await call('POST', `/venues/${A}/requests`, t, pedido({ start_time: brt(3, '10:00'), end_time: brt(3, '11:00') }))).json.code, 'REQUEST_LIMIT');
    sql(`INSERT INTO match_requests (venue_id, user_id, start_time, end_time) VALUES (${C}, ${ana}, '${db(brt(4, '10:00'))}', '${db(brt(4, '11:00'))}'), (${D}, ${ana}, '${db(brt(4, '12:00'))}', '${db(brt(4, '13:00'))}')`);
    const limite = await call('POST', `/venues/${B}/requests`, t, pedido({ start_time: brt(3, '10:00'), end_time: brt(3, '11:00') }));
    check('3 pendentes no total: 409 REQUEST_LIMIT', [limite.status, limite.json.code, limite.json.message.includes('3 pedidos')], [409, 'REQUEST_LIMIT', true]);

    // --- Listar ---
    const lista = await call('GET', '/me/requests', t);
    check('lista: 3 pedidos, o mais recente primeiro, com meta', [lista.json.data.length, lista.json.meta.total, lista.json.meta.per_page, lista.json.meta.current_page], [3, 3, 20, 1]);
    check('paginação: per_page=2', [(await call('GET', '/me/requests?per_page=2', t)).json.data.length, (await call('GET', '/me/requests?per_page=2', t)).json.meta.last_page], [2, 2]);
    check('filtro status=approved: vazio', (await call('GET', '/me/requests?status=approved', t)).json.data, []);
    check('status inválido e página inválida: 422', [(await call('GET', '/me/requests?status=xyz', t)).status, (await call('GET', '/me/requests?page=0', t)).status], [422, 422]);
    check('o dono não vê os pedidos da Ana', (await call('GET', '/me/requests', tDono)).json.data, []);

    // --- Cancelar ---
    check('pedido de outra pessoa: 404', (await call('DELETE', `/me/requests/${r1.id}`, tDono)).status, 404);
    const canc = await call('DELETE', `/me/requests/${r1.id}`, t);
    check('pendente: 200 com o pedido cancelado', [canc.status, canc.json.data.status], [200, 'cancelled']);
    check('o dono recebe "cancelou"', sql(`SELECT type FROM notifications WHERE id > ${lastNotif} AND user_id = ${dono} ORDER BY id DESC LIMIT 1`), 'request_cancelled');
    check('cancelar de novo: 409 CANNOT_CANCEL', (await call('DELETE', `/me/requests/${r1.id}`, t)).json.code, 'CANNOT_CANCEL');
    // Aprovado perto do início (1 h) e com antecedência (2 dias), gravados como o painel gravaria
    const soon = new Date(Date.now() - 3 * 3600e3 + 3600e3).toISOString().slice(0, 13) + ':00:00';
    const soonEnd = new Date(Date.now() - 3 * 3600e3 + 2 * 3600e3).toISOString().slice(0, 13) + ':00:00';
    sql(`INSERT INTO match_requests (venue_id, user_id, start_time, end_time, status, responded_at) VALUES (${A}, ${ana}, '${soon.replace('T', ' ')}', '${soonEnd.replace('T', ' ')}', 'approved', NOW())`);
    const perto = Number(sql(`SELECT MAX(id) FROM match_requests WHERE user_id=${ana}`));
    check('aprovado a menos de 2 h: 409 CANNOT_CANCEL', [(await call('DELETE', `/me/requests/${perto}`, t)).json.code, sql(`SELECT status FROM match_requests WHERE id=${perto}`)], ['CANNOT_CANCEL', 'approved']);
    sql(`INSERT INTO matches (venue_id, creator_user_id, renter_name, status, start_time, end_time) VALUES (${A}, ${dono}, 'Ana', 'scheduled', '${db(brt(5, '19:00'))}', '${db(brt(5, '20:00'))}')`);
    const jogo = Number(sql(`SELECT MAX(id) FROM matches WHERE venue_id=${A}`));
    partidas.push(jogo);
    sql(`INSERT INTO match_requests (venue_id, user_id, start_time, end_time, status, match_id, responded_at) VALUES (${A}, ${ana}, '${db(brt(5, '19:00'))}', '${db(brt(5, '20:00'))}', 'approved', ${jogo}, NOW())`);
    const longe = Number(sql(`SELECT MAX(id) FROM match_requests WHERE user_id=${ana}`));
    check('aprovado com antecedência: cancela e a partida sai da agenda', [(await call('DELETE', `/me/requests/${longe}`, t)).json.data.status, sql(`SELECT status FROM matches WHERE id=${jogo}`)], ['cancelled', 'cancelled']);

    // --- Expira sozinho ---
    sql(`INSERT INTO match_requests (venue_id, user_id, start_time, end_time) VALUES (${B}, ${ana}, '${db(brt(-1, '10:00'))}', '${db(brt(-1, '11:00'))}')`);
    const velho = Number(sql(`SELECT MAX(id) FROM match_requests WHERE user_id=${ana}`));
    check('pendente que passou do início aparece como expirado', ((await call('GET', '/me/requests', t)).json.data.find(r => r.id === velho) || {}).status, 'expired');

    // --- Notificações ---
    sql(`INSERT INTO notifications (user_id, type, data) VALUES (${ana}, 'request_declined', '${asciiJson({ request_id: 999, venue_id: B, venue_name: 'Beach Teste S3', user_name: 'Ana', start_time: db(brt(2, '19:00')), end_time: db(brt(2, '20:00')), response_note: 'Quadra em manutenção' })}')`);
    const nt = await call('GET', '/me/notifications', t);
    const recusa = nt.json.data.find(n => n.type === 'request_declined');
    const expirou = nt.json.data.find(n => n.type === 'request_expired');
    check('lista de notificações com meta.unread', [nt.status, nt.json.meta.unread >= 2, typeof nt.json.meta.total], [200, true, 'number']);
    check('objeto do contrato', Object.keys(recusa), ['id', 'type', 'title', 'body', 'data', 'read_at', 'created_at']);
    check('título e texto em português, com o motivo', [recusa.title, recusa.body.endsWith('Motivo: Quadra em manutenção')], ['Pedido recusado', true]);
    check('data só com os ids (sem nomes)', recusa.data, { request_id: 999, venue_id: B });
    check('o expirado também virou aviso', !!expirou && expirou.title === 'Pedido expirado', true);
    const en = await call('GET', '/me/notifications', t, undefined, { 'Accept-Language': 'en-US,en;q=0.9' });
    check('em inglês pelo Accept-Language', en.json.data.find(n => n.type === 'request_declined').title, 'Request declined');
    check('marcar uma como lida: 204', (await call('POST', '/me/notifications/read', t, { ids: [recusa.id] })).status, 204);
    check('não lidas diminuem', (await call('GET', '/me/notifications', t)).json.meta.unread, nt.json.meta.unread - 1);
    for (const [body, label] of [[{}, 'sem nada'], [{ ids: [] }, 'lista vazia'], [{ ids: ['1'] }, 'id como texto'], [{ all: 'sim' }, 'all que não é true']]) {
      check(`read ${label}: 422`, (await call('POST', '/me/notifications/read', t, body)).status, 422);
    }
    check('marcar todas: 204 e zero não lidas', [(await call('POST', '/me/notifications/read', t, { all: true })).status, (await call('GET', '/me/notifications', t)).json.meta.unread], [204, 0]);
    check('o dono não vê as notificações da Ana', (await call('GET', '/me/notifications', tDono)).json.data.some(n => n.id === recusa.id), false);
  } finally {
    // Deixa como encontrou
    sql(`DELETE FROM match_requests WHERE venue_id IN (${TEST_VENUES})`);
    if (partidas.length) sql(`DELETE FROM matches WHERE id IN (${partidas.join(',')})`);
    sql(`DELETE FROM notifications WHERE id > ${lastNotif} AND user_id IN (${ana}, ${dono})`);
    sql(`UPDATE users SET email_verified_at = ${verifAna === 'NULL' ? 'NULL' : `'${verifAna}'`} WHERE id = ${ana}`);
  }
  console.log(fail ? `\n${fail} FALHA(S)` : '\nTodos os passos passaram');
  process.exit(fail ? 1 : 0);
})();
