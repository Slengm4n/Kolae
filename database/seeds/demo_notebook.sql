-- =====================================================================
-- Dados de demonstração no notebook — SÓ PARA O AMBIENTE LOCAL
-- =====================================================================
-- Deixa o mapa do app colorido enquanto as telas do dono (S4) não chegam:
-- mexe só nas quadras "de verdade" (não nas do seed teste_status.sql).
--   1. Horário 06:00–23:59 todos os dias, nas quadras ativas sem horário.
--   2. Esportes pelo piso, nas quadras sem nenhum (D29).
--   3. WhatsApp fictício (5511900000 + id do dono) nos donos sem número, para o
--      "Falar com o dono" aparecer no app (D24).
--   4. Partidas a partir da hora em que roda, pelo id da quadra:
--      id % 3 = 0 → "Em uso" agora (de agora-20min até agora+40min);
--      id % 3 = 1 → "Disponível", com partida daqui a 1h;
--      id % 3 = 2 → "Disponível" sem partida.
--      Entre 00:00 e 06:00 todas aparecem "Fechada" (fora do horário).
--
-- Pode rodar de novo: apaga só as partidas que ele criou ("Demo: ...")
-- e cria outras a partir da hora atual. Horário, esportes e WhatsApp já
-- preenchidos não mudam. Para desfazer tudo, reimportar a cópia do banco.
--
-- Só age num banco chamado kolae_db (o local); em outro banco não faz nada.
-- Uso: phpMyAdmin > kolae_db > SQL, ou mysql --default-character-set=utf8mb4.
-- NUNCA rodar na produção. Na apresentação (S6), os dados vêm pelo site.
-- =====================================================================

SET time_zone = '-03:00';
SET @local = (DATABASE() = 'kolae_db');

-- Quadras de verdade: ativas e de donos que não são de teste
DROP TEMPORARY TABLE IF EXISTS demo_venues;
CREATE TEMPORARY TABLE demo_venues AS
SELECT v.id, v.user_id, v.floor_type
FROM venues v
JOIN users u ON u.id = v.user_id
WHERE @local AND v.status = 'available' AND v.deleted_at IS NULL AND u.email NOT LIKE '%@kolae.test';

-- 1. Horário
INSERT INTO venue_hours (venue_id, weekday, opens_at, closes_at)
SELECT dv.id, d.wd, '06:00:00', '23:59:00'
FROM demo_venues dv
CROSS JOIN (SELECT 0 wd UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) d
WHERE NOT EXISTS (SELECT 1 FROM venue_hours h WHERE h.venue_id = dv.id);

-- 2. Esportes pelo piso (pelo nome, porque o id muda de banco para banco)
INSERT INTO venue_sports (venue_id, sport_id)
SELECT dv.id, s.id
FROM demo_venues dv
JOIN sports s ON s.status = 'active' AND (
       (s.name = 'Futebol' AND dv.floor_type IN ('court', 'synthetic', 'grass'))
    OR (s.name = 'Basquete' AND dv.floor_type = 'court')
    OR (s.name = 'Vôlei' AND (dv.floor_type IS NULL OR dv.floor_type NOT IN ('court', 'synthetic', 'grass')))
)
WHERE NOT EXISTS (SELECT 1 FROM venue_sports vs WHERE vs.venue_id = dv.id);

-- 3. WhatsApp fictício dos donos sem número
UPDATE users
SET whatsapp = CONCAT('5511900000', LPAD(id, 3, '0'))
WHERE @local AND whatsapp IS NULL AND id IN (SELECT user_id FROM demo_venues);

-- 4. Partidas: renova as do seed
DELETE FROM matches WHERE @local AND renter_name LIKE 'Demo:%';

INSERT INTO matches (venue_id, creator_user_id, renter_name, status, start_time, end_time)
SELECT id, user_id, 'Demo: Time Azul', 'scheduled', NOW() - INTERVAL 20 MINUTE,
       LEAST(NOW() + INTERVAL 40 MINUTE, CONCAT(CURDATE(), ' 23:59:00'))
FROM demo_venues WHERE id % 3 = 0;

INSERT INTO matches (venue_id, creator_user_id, renter_name, status, start_time, end_time)
SELECT id, user_id, 'Demo: Time Verde', 'scheduled', NOW() + INTERVAL 60 MINUTE,
       LEAST(NOW() + INTERVAL 120 MINUTE, CONCAT(CURDATE(), ' 23:59:00'))
FROM demo_venues WHERE id % 3 = 1 AND NOW() + INTERVAL 60 MINUTE < CONCAT(CURDATE(), ' 23:59:00');

DROP TEMPORARY TABLE demo_venues;

SELECT IF(@local, 'ok', 'NADA FEITO: o banco não é o kolae_db') AS resultado,
       (SELECT COUNT(DISTINCT venue_id) FROM venue_hours) AS quadras_com_horario,
       (SELECT COUNT(*) FROM matches WHERE renter_name LIKE 'Demo:%' AND status = 'scheduled') AS partidas_demo;
