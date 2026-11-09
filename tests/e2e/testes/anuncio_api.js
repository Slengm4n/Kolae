// Teste da API dos anúncios (D63, parte D): campo `ad` em GET /venues e GET /venues/{id},
// GET /ads (carrossel "Destaques" do app), foto do anúncio, capa ou null, quem aparece e quem não
// (pausado, agendado, quadra desativada, Premium vencido) e ad_view/ad_click pelo POST /events.
// Sem navegador: só chamadas HTTP.
// Uso: node tests/e2e/rodar.js anuncio_api (ou direto: node tests/e2e/testes/anuncio_api.js <ids A B C D E>)
const lib = require('../lib');
const fs = require('fs');
const path = require('path');
const BASE = lib.BASE;
const API = `${BASE}/api/v1`;
const [A, B, C, D, E] = process.argv.slice(2).map(Number);
const sql = lib.sql;
const FOTO = path.join(lib.FOTOS, 'foto1.jpg');

let fail = 0;
const check = (label, got, want) => {
  const ok = JSON.stringify(got) === JSON.stringify(want);
  if (!ok) fail++;
  console.log(`${ok ? 'OK  ' : 'FALHA'} ${label}${ok ? '' : `  -> ${JSON.stringify(got)} (esperado ${JSON.stringify(want)})`}`);
};
const ymd = (days = 0) => { const d = new Date(Date.now() - 3 * 3600e3); d.setUTCDate(d.getUTCDate() + days); return d.toISOString().slice(0, 10); }; // hoje em Brasília
const TEST_VENUES = [A, B, C, D, E].join(',');
const owner = `(SELECT user_id FROM venues WHERE id=${A})`;
const setPlan = (plan, until = 'NULL') => sql(`UPDATE users SET plan='${plan}', plan_until=${until} WHERE id=${owner}`);
const addAd = (venue, title, { starts = 0, ends = 5, status = 'active', image = null } = {}) => {
  sql(`INSERT INTO venue_ads (venue_id, title, body, starts_on, ends_on, status, image_path) VALUES (${venue}, '${title}', 'Texto de ${title}', '${ymd(starts)}', '${ymd(ends)}', '${status}', ${image ? `'${image}'` : 'NULL'})`);
  return Number(sql(`SELECT MAX(id) FROM venue_ads WHERE venue_id=${venue}`));
};
const events = (venue, type) => Number(sql(`SELECT COUNT(*) FROM venue_events WHERE venue_id=${venue} AND type='${type}'`));
const get = async url => (await fetch(url, { headers: { 'Accept-Language': 'pt-BR' } })).json();
const ads = async () => (await get(`${API}/ads`)).data;
const listAd = async id => ((await get(`${API}/venues?per_page=100`)).data.find(v => v.id === id) || {}).ad;
const detailAd = async id => (await get(`${API}/venues/${id}`)).data.ad;
const postEvent = async (id, type, ua) => (await fetch(`${API}/venues/${id}/events`, {
  method: 'POST', headers: { 'Content-Type': 'application/json', 'User-Agent': ua }, body: JSON.stringify({ type, source: 'app' }),
})).status;

(async () => {
  // Outros anúncios no ar no banco local ficam pausados durante o teste e voltam no fim
  const others = sql(`SELECT GROUP_CONCAT(id) FROM venue_ads WHERE status='active' AND venue_id NOT IN (${TEST_VENUES})`);
  const othersIds = others && others !== 'NULL' ? others : '';
  if (othersIds) sql(`UPDATE venue_ads SET status='paused' WHERE id IN (${othersIds})`);
  sql(`DELETE FROM venue_ads WHERE venue_id IN (${TEST_VENUES})`);
  sql(`DELETE FROM venue_events WHERE venue_id IN (${TEST_VENUES})`);
  // Capa da Beach (o anúncio sem foto usa a capa) e foto própria do anúncio da Arena
  const capaDir = path.join(lib.UPLOADS, 'venues', String(B));
  fs.mkdirSync(capaDir, { recursive: true });
  fs.copyFileSync(FOTO, path.join(capaDir, 'capa_teste_api.jpg'));
  sql(`DELETE FROM venues_images WHERE venue_id=${B} AND file_path='capa_teste_api.jpg'`);
  sql(`INSERT INTO venues_images (venue_id, file_path, position) VALUES (${B}, 'capa_teste_api.jpg', -1)`);
  let idA;

  try {
    idA = addAd(A, 'Sexta do society', { image: 'ad_teste_api.jpg' });
    fs.mkdirSync(path.join(lib.UPLOADS, 'ads', String(idA)), { recursive: true });
    fs.copyFileSync(FOTO, path.join(lib.UPLOADS, 'ads', String(idA), 'ad_teste_api.jpg'));
    const idB = addAd(B, 'Beach em dobro');
    const idC = addAd(C, 'Clay em reforma'); // manutenção continua mostrando; sem foto e sem capa
    addAd(D, 'Duda agendado', { starts: 2, ends: 6 }); // começa depois: não aparece
    addAd(E, 'Excluida nao aparece'); // quadra desativada (D34)

    // --- Grátis: nada ---
    setPlan('free');
    check('Grátis: GET /ads vazio', await ads(), []);
    check('Grátis: ad null na lista e no detalhe', [await listAd(A), await detailAd(A)], [null, null]);

    // --- Premium ---
    setPlan('premium');
    const lista = (await get(`${API}/venues?per_page=100`)).data;
    check('lista: todas as quadras trazem a chave ad', lista.every(v => 'ad' in v), true);
    const adA = lista.find(v => v.id === A).ad;
    check('lista: ad no formato do contrato', adA && Object.keys(adA), ['id', 'title', 'body', 'image_url', 'ends_on']);
    check('lista: valores do anúncio', [adA.id, adA.title, adA.body, adA.ends_on], [idA, 'Sexta do society', 'Texto de Sexta do society', ymd(5)]);
    check('com foto própria: image_url é a foto do anúncio', adA.image_url, `${BASE}/uploads/ads/${idA}/ad_teste_api.jpg`);
    check('sem foto: image_url é a capa da quadra', (await listAd(B)).image_url, `${BASE}/uploads/venues/${B}/capa_teste_api.jpg`);
    check('sem foto e sem capa: image_url null', (await listAd(C)).image_url, null);
    check('agendado para depois: ad null', await listAd(D), null);
    check('quadra desativada: fora da lista', lista.some(v => v.id === E), false);
    check('detalhe: mesmo ad da lista', await detailAd(A), adA);
    check('detalhe: ad é o último campo, depois de socials', Object.keys((await get(`${API}/venues/${A}`)).data).slice(-2), ['socials', 'ad']);

    const destaques = await ads();
    check('GET /ads: só os no ar (Arena, Beach, Clay)', destaques.map(a => a.id).sort((x, y) => x - y), [idA, idB, idC].sort((x, y) => x - y));
    check('GET /ads: mesma ordem ao chamar de novo', (await ads()).map(a => a.id), destaques.map(a => a.id));
    const item = destaques.find(a => a.id === idA);
    check('GET /ads: campos do item', Object.keys(item), ['id', 'title', 'body', 'image_url', 'ends_on', 'venue']);
    const resumo = lista.find(v => v.id === A);
    const { ad: _ad, distance_km: _d, ...semAd } = resumo;
    check('GET /ads: venue no formato da lista, sem ad e sem distance_km', item.venue, semAd);
    check('GET /ads: no máximo 5', destaques.length <= 5, true);

    // --- Pausado e Premium vencido ---
    sql(`UPDATE venue_ads SET status='paused' WHERE id=${idB}`);
    check('pausado: some do /ads e do detalhe', [(await ads()).some(a => a.id === idB), await detailAd(B)], [false, null]);
    setPlan('premium', `'${ymd(-1)}'`);
    check('Premium vencido: /ads vazio e ad null', [await ads(), await detailAd(A)], [[], null]);
    setPlan('premium');

    // --- ad_view e ad_click pelo app ---
    sql(`DELETE FROM venue_events WHERE venue_id IN (${TEST_VENUES})`);
    check('ad_view com anúncio no ar: 204 e conta', [await postEvent(A, 'ad_view', 'okhttp/4.12 Kolae'), events(A, 'ad_view')], [204, 1]);
    check('ad_click com anúncio no ar: 204 e conta', [await postEvent(A, 'ad_click', 'okhttp/4.12 Kolae'), events(A, 'ad_click')], [204, 1]);
    check('ad_click repetido logo depois: não conta de novo', [await postEvent(A, 'ad_click', 'okhttp/4.12 Kolae'), events(A, 'ad_click')], [204, 1]);
    check('sem anúncio no ar (pausado): 204, mas não conta', [await postEvent(B, 'ad_view', 'okhttp/4.12 Kolae'), await postEvent(B, 'ad_click', 'okhttp/4.12 Kolae'), events(B, 'ad_view'), events(B, 'ad_click')], [204, 204, 0, 0]);
    check('outros cliques continuam contando sem anúncio', [await postEvent(B, 'whatsapp', 'okhttp/4.12 Kolae'), events(B, 'whatsapp')], [204, 1]);
    check('números chegam no anúncio do dono', Number(sql(`SELECT COUNT(*) FROM venue_events WHERE venue_id=${A} AND type IN ('ad_view','ad_click')`)), 2);
  } finally {
    // Deixa como encontrou
    sql(`DELETE FROM venue_ads WHERE venue_id IN (${TEST_VENUES})`);
    sql(`DELETE FROM venue_events WHERE venue_id IN (${TEST_VENUES})`);
    setPlan('free');
    if (othersIds) sql(`UPDATE venue_ads SET status='active' WHERE id IN (${othersIds})`);
    if (idA) fs.rmSync(path.join(lib.UPLOADS, 'ads', String(idA)), { recursive: true, force: true }); // só a pasta deste teste
    sql(`DELETE FROM venues_images WHERE venue_id=${B} AND file_path='capa_teste_api.jpg'`);
    fs.rmSync(path.join(capaDir, 'capa_teste_api.jpg'), { force: true });
  }
  console.log(fail ? `\n${fail} FALHA(S)` : '\nTodos os passos passaram');
  process.exit(fail ? 1 : 0);
})();
