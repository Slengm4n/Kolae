// Teste da API do chat (D66, parte C), sem navegador: só chamadas HTTP, seguindo o contrato do
// docs/api.md (seção Chat). Abrir conversa (201/200, própria quadra, quadra desativada), lista sem
// as vazias, mensagens (após, antes, mais recentes, per_page), enviar (vazio, longo, link, bloqueio,
// quadra desativada, limite com Retry-After), lida (só avança), quem não faz parte (404), token.
// A resposta do dono é gravada direto no banco: o painel dele é a parte D.
// Uso: node tests/e2e/rodar.js chat_api (ou direto: node tests/e2e/testes/chat_api.js <ids A B E>)
const lib = require('../lib');
const API = `${lib.BASE}/api/v1`;
const [A, B, E] = process.argv.slice(2).map(Number);
const sql = lib.sql;

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};
const TEST_VENUES = [A, B, E].join(',');
const call = async (method, path, token, body) => {
  const headers = {};
  if (token) headers.Authorization = `Bearer ${token}`;
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  const r = await fetch(API + path, { method, headers, body: body === undefined ? undefined : (typeof body === 'string' ? body : JSON.stringify(body)) });
  const text = await r.text();
  let json = null;
  try { json = text ? JSON.parse(text) : null; } catch (e) { json = { raw: text }; }
  return { status: r.status, json, retryAfter: r.headers.get('retry-after') };
};
const login = async email => (await call('POST', '/auth/login', null, { email, password: 'teste123' })).json.data.token;
const userId = email => Number(sql(`SELECT id FROM users WHERE email='${email}'`));
// Resposta do dono direto no banco (o painel é a parte D)
const ownerReply = (conversationId, ownerId, body) => {
  sql(`INSERT INTO messages (conversation_id, sender_id, body) VALUES (${conversationId}, ${ownerId}, '${body}')`);
  sql(`UPDATE conversations SET last_message_at = NOW() WHERE id = ${conversationId}`);
  return Number(sql(`SELECT MAX(id) FROM messages WHERE conversation_id = ${conversationId}`));
};

(async () => {
  sql(`DELETE FROM conversations WHERE venue_id IN (${TEST_VENUES})`);
  const statusE = sql(`SELECT status FROM venues WHERE id=${B}`);
  try {
    const pessoa = await login('dono@teste.local'); // outro dono, aqui no papel de quem pergunta
    const dono = await login('dono-s3@kolae.test');
    const admin = await login('admin@teste.local');
    const donoId = userId('dono-s3@kolae.test');

    // --- Token ---
    check('sem token: 401 AUTH_MISSING_TOKEN', [(await call('GET', '/conversations')).status, (await call('GET', '/conversations')).json.code], [401, 'AUTH_MISSING_TOKEN']);
    check('token inválido: 401 AUTH_INVALID_TOKEN', (await call('GET', '/conversations', 'abc.def.ghi')).json.code, 'AUTH_INVALID_TOKEN');

    // --- Abrir conversa ---
    const nova = await call('POST', `/venues/${A}/conversations`, pessoa);
    check('abrir: 201 com o objeto conversa', [nova.status, Object.keys(nova.json.data)], [201, ['id', 'venue', 'last_message', 'unread_count', 'blocked']]);
    const conv = nova.json.data;
    check('conversa nova: venue, sem mensagem, 0 não lidas, não bloqueada', [conv.venue.id, Object.keys(conv.venue), conv.last_message, conv.unread_count, conv.blocked], [A, ['id', 'name', 'cover_image_url'], null, 0, false]);
    const deNovo = await call('POST', `/venues/${A}/conversations`, pessoa);
    check('abrir de novo: 200 e a mesma conversa', [deNovo.status, deNovo.json.data.id], [200, conv.id]);
    check('dono na própria quadra: 422 CHAT_OWN_VENUE', [(await call('POST', `/venues/${A}/conversations`, dono)).status, (await call('POST', `/venues/${A}/conversations`, dono)).json.code], [422, 'CHAT_OWN_VENUE']);
    check('quadra desativada: 404 VENUE_NOT_FOUND', (await call('POST', `/venues/${E}/conversations`, pessoa)).json.code, 'VENUE_NOT_FOUND');
    check('quadra que não existe: 404 VENUE_NOT_FOUND', [(await call('POST', '/venues/99999999/conversations', pessoa)).status, (await call('POST', '/venues/abc/conversations', pessoa)).json.code], [404, 'VENUE_NOT_FOUND']);
    check('conversa vazia fica fora da lista', (await call('GET', '/conversations', pessoa)).json.data, []);

    // --- Enviar ---
    const env = await call('POST', `/conversations/${conv.id}/messages`, pessoa, { body: '  Tem horário sábado?\r\nSomos 10  ' });
    check('enviar: 201 com o objeto mensagem', [env.status, Object.keys(env.json.data), env.json.data.body, env.json.data.mine], [201, ['id', 'body', 'mine', 'created_at'], 'Tem horário sábado?\nSomos 10', true]);
    check('created_at com -03:00', /^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d-03:00$/.test(env.json.data.created_at), true);
    const m1 = env.json.data.id;
    for (const [body, label] of [['   ', 'vazio'], ['x'.repeat(1001), 'mais de 1000'], ['entra em kolae.online', 'com link']]) {
      const r = await call('POST', `/conversations/${conv.id}/messages`, pessoa, { body });
      check(`não envia ${label}: 422 com errors.body (lista de textos, como no resto da API)`, [r.status, r.json.code, Array.isArray((r.json.errors || {}).body) && typeof r.json.errors.body[0] === 'string'], [422, 'VALIDATION_ERROR', true]);
    }
    check('sem o campo body: 422', (await call('POST', `/conversations/${conv.id}/messages`, pessoa, {})).status, 422);
    check('corpo que não é JSON: 400 INVALID_JSON_BODY', (await call('POST', `/conversations/${conv.id}/messages`, pessoa, 'oi')).json.code, 'INVALID_JSON_BODY');
    check('frase com ponto sem espaço passa ("Pode ser.De tarde")', (await call('POST', `/conversations/${conv.id}/messages`, pessoa, { body: 'Pode ser.De tarde?' })).status, 201);

    // --- Resposta do dono, lista e não lidas ---
    const r1 = ownerReply(conv.id, donoId, 'Tem sim, das 8h às 12h.');
    const r2 = ownerReply(conv.id, donoId, 'Quantas horas?');
    const lista = (await call('GET', '/conversations', pessoa)).json.data;
    check('lista: a conversa com a última mensagem e 2 não lidas', [lista.length, lista[0].id, lista[0].last_message.id, lista[0].last_message.mine, lista[0].unread_count], [1, conv.id, r2, false, 2]);
    check('o dono não vê a conversa pelo app', (await call('GET', '/conversations', dono)).json.data, []);

    // --- Ler mensagens ---
    const todas = (await call('GET', `/conversations/${conv.id}/messages`, pessoa)).json.data;
    const ids = todas.map(m => m.id);
    check('sem cursor: todas, em ordem crescente, com mine certo', [ids.length, [...ids].sort((x, y) => x - y), todas.map(m => m.mine)], [4, ids, [true, true, false, false]]);
    check('per_page=2: as 2 mais recentes', (await call('GET', `/conversations/${conv.id}/messages?per_page=2`, pessoa)).json.data.map(m => m.id), [r1, r2]);
    check('after_id: só as novas (polling)', (await call('GET', `/conversations/${conv.id}/messages?after_id=${ids[1]}`, pessoa)).json.data.map(m => m.id), [r1, r2]);
    check('after_id da última: vazio', (await call('GET', `/conversations/${conv.id}/messages?after_id=${r2}`, pessoa)).json.data, []);
    check('before_id: as anteriores', (await call('GET', `/conversations/${conv.id}/messages?before_id=${r1}`, pessoa)).json.data.map(m => m.id), [m1, ids[1]]);
    for (const [q, label] of [['after_id=1&before_id=9', 'after_id e before_id juntos'], ['per_page=51', 'per_page 51'], ['after_id=abc', 'after_id não numérico'], ['before_id=0', 'before_id 0']]) {
      check(`${label}: 422`, (await call('GET', `/conversations/${conv.id}/messages?${q}`, pessoa)).json.code, 'VALIDATION_ERROR');
    }

    // --- Marcar como lida ---
    check('read: 204 sem corpo', [(await call('POST', `/conversations/${conv.id}/read`, pessoa, { last_message_id: r1 })).status], [204]);
    check('sobra 1 não lida', (await call('GET', '/conversations', pessoa)).json.data[0].unread_count, 1);
    await call('POST', `/conversations/${conv.id}/read`, pessoa, { last_message_id: m1 });
    check('id menor não volta (continua 1)', (await call('GET', '/conversations', pessoa)).json.data[0].unread_count, 1);
    await call('POST', `/conversations/${conv.id}/read`, pessoa, { last_message_id: 99999999 });
    check('id enorme vale como a última: 0 não lidas', (await call('GET', '/conversations', pessoa)).json.data[0].unread_count, 0);
    for (const [body, label] of [[{}, 'sem o campo'], [{ last_message_id: 0 }, 'zero'], [{ last_message_id: 'abc' }, 'texto'], [{ last_message_id: 1.5 }, 'decimal']]) {
      check(`read ${label}: 422`, (await call('POST', `/conversations/${conv.id}/read`, pessoa, body)).status, 422);
    }

    // --- Quem não faz parte: 404 ---
    for (const [token, who] of [[dono, 'dono da quadra (responde pelo site)'], [admin, 'admin']]) {
      check(`${who}: 404 NOT_FOUND nas 3 rotas da conversa`, [
        (await call('GET', `/conversations/${conv.id}/messages`, token)).json.code,
        (await call('POST', `/conversations/${conv.id}/messages`, token, { body: 'oi' })).json.code,
        (await call('POST', `/conversations/${conv.id}/read`, token, { last_message_id: 1 })).json.code,
      ], ['NOT_FOUND', 'NOT_FOUND', 'NOT_FOUND']);
    }
    check('conversa que não existe: 404', [(await call('GET', '/conversations/99999999/messages', pessoa)).status, (await call('GET', '/conversations/abc/messages', pessoa)).status], [404, 404]);

    // --- Bloqueio ---
    sql(`UPDATE conversations SET blocked_at = NOW() WHERE id = ${conv.id}`);
    check('bloqueada: 403 CHAT_BLOCKED e blocked true', [(await call('POST', `/conversations/${conv.id}/messages`, pessoa, { body: 'oi' })).json.code, (await call('GET', '/conversations', pessoa)).json.data[0].blocked], ['CHAT_BLOCKED', true]);
    check('bloqueada: continua dando para ler', (await call('GET', `/conversations/${conv.id}/messages`, pessoa)).json.data.length, 4);
    sql(`UPDATE conversations SET blocked_at = NULL WHERE id = ${conv.id}`);

    // --- Quadra desativada depois da conversa: só leitura ---
    const cb = (await call('POST', `/venues/${B}/conversations`, pessoa)).json.data;
    await call('POST', `/conversations/${cb.id}/messages`, pessoa, { body: 'Oi, Beach!' });
    sql(`UPDATE venues SET status = 'unavailable' WHERE id = ${B}`);
    check('quadra desativada: enviar dá 404 VENUE_NOT_FOUND', (await call('POST', `/conversations/${cb.id}/messages`, pessoa, { body: 'oi' })).json.code, 'VENUE_NOT_FOUND');
    check('quadra desativada: a conversa continua na lista e legível', [(await call('GET', '/conversations', pessoa)).json.data.some(c => c.id === cb.id), (await call('GET', `/conversations/${cb.id}/messages`, pessoa)).json.data.length], [true, 1]);
    sql(`UPDATE venues SET status = '${statusE}' WHERE id = ${B}`);
    check('lista: a mais recente primeiro', (await call('GET', '/conversations', pessoa)).json.data.map(c => c.id), [cb.id, conv.id]);

    // --- Limite de envio: 20 por minuto, com Retry-After ---
    const enviadas = Number(sql(`SELECT COUNT(*) FROM messages WHERE sender_id = ${userId('dono@teste.local')} AND created_at > NOW() - INTERVAL 60 SECOND`));
    for (let i = enviadas; i < 20; i++) await call('POST', `/conversations/${conv.id}/messages`, pessoa, { body: `Mensagem ${i}` });
    const limite = await call('POST', `/conversations/${conv.id}/messages`, pessoa, { body: 'A 21ª' });
    check('21ª mensagem no minuto: 429 com Retry-After de 1 a 60 s', [limite.status, limite.json.code, Number(limite.retryAfter) >= 1 && Number(limite.retryAfter) <= 60], [429, 'TOO_MANY_ATTEMPTS', true]);
    check('a 21ª não foi gravada', Number(sql(`SELECT COUNT(*) FROM messages WHERE body = 'A 21ª'`)), 0);
  } finally {
    // Deixa como encontrou
    sql(`UPDATE venues SET status = '${statusE}' WHERE id = ${B}`);
    sql(`DELETE FROM conversations WHERE venue_id IN (${TEST_VENUES})`);
  }
  console.log(fail ? `\n${fail} FALHA(S)` : '\nTodos os passos passaram');
  process.exit(fail ? 1 : 0);
})();
