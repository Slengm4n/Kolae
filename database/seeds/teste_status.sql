-- =====================================================================
-- Dados de teste do status da quadra (S3) — SÓ PARA O AMBIENTE LOCAL
-- =====================================================================
-- Cria um dono (dono-s3@kolae.test), 2 esportes e 5 quadras, uma para cada caso:
--   Arena (em uso agora), Beach (disponível, grátis), Clay (manutenção),
--   Duda (sem horário, sem coordenadas), Excluida (desativada: fora da API).
-- As partidas são criadas em relação ao horário em que o script roda:
-- a Arena fica "em uso" por 30 minutos e a Beach fica ocupada daqui a 1 hora.
-- Rode de novo (depois do teste_status_limpar.sql) para renovar os horários.
--
-- Uso: phpMyAdmin > kolae_db > SQL, ou mysql --default-character-set=utf8mb4.
-- Para apagar tudo: database/seeds/teste_status_limpar.sql
-- NUNCA rodar na produção.
-- =====================================================================

SET time_zone = '-03:00';

INSERT INTO users (name, email, password, role, whatsapp, created_at)
VALUES ('Dono Teste S3', 'dono-s3@kolae.test', '$2y$10$abcdefghijklmnopqrstuuJ4f5b5yQ0dJ7WvD7vQe9mQe9mQe9mQe', 'owner', '5511999990000', '2026-05-22 14:57:07');
SET @dono = LAST_INSERT_ID();

INSERT INTO sports (name, status, icon_path) VALUES ('Futebol Teste S3', 'active', 'fa-futbol');
SET @fut = LAST_INSERT_ID();
INSERT INTO sports (name, status, icon_path) VALUES ('Vôlei Teste S3', 'active', 'fa-volleyball-ball');
SET @vol = LAST_INSERT_ID();

INSERT INTO countries (name) SELECT 'Brasil' WHERE NOT EXISTS (SELECT 1 FROM countries WHERE name = 'Brasil');
SET @co = (SELECT id FROM countries WHERE name = 'Brasil' LIMIT 1);
INSERT INTO states (name, country_id) VALUES ('SP Teste S3', @co);            SET @st = LAST_INSERT_ID();
INSERT INTO cities (name, state_id) VALUES ('Ferraz Teste S3', @st);          SET @ci = LAST_INSERT_ID();
INSERT INTO neighborhoods (name, city_id) VALUES ('Centro Teste S3', @ci);    SET @nb = LAST_INSERT_ID();
INSERT INTO streets (name, neighborhood_id) VALUES ('Rua Teste S3', @nb);     SET @sr = LAST_INSERT_ID();

-- A: em uso agora (partida de agora-30min a agora+30min), coberta, iluminada, lazer, grama sintética
INSERT INTO addresses (cep, street_id, number, latitude, longitude) VALUES ('08500-000', @sr, '100', -23.54280000, -46.36860000); SET @a1 = LAST_INSERT_ID();
INSERT INTO venues (user_id, address_id, name, description, average_price_per_hour, court_capacity, has_leisure_area, leisure_area_capacity, floor_type, has_lighting, is_covered, status)
VALUES (@dono, @a1, 'Arena Teste S3', 'Quadra society com grama nova.', 120.00, 14, 1, 30, 'synthetic', 1, 1, 'available'); SET @vA = LAST_INSERT_ID();

-- B: disponível até a próxima partida (agora+60min), grátis, areia, sem cobertura
INSERT INTO addresses (cep, street_id, number, latitude, longitude) VALUES ('08550-000', @sr, '200', -23.52860000, -46.34560000); SET @a2 = LAST_INSERT_ID();
INSERT INTO venues (user_id, address_id, name, average_price_per_hour, floor_type, has_lighting, is_covered, status)
VALUES (@dono, @a2, 'Beach Teste S3', 0.00, 'sand', 0, 0, 'available'); SET @vB = LAST_INSERT_ID();

-- C: em manutenção (fechada pelo dono), sem preço informado
INSERT INTO addresses (cep, street_id, number, latitude, longitude) VALUES ('08600-000', @sr, '300', -23.54250000, -46.31080000); SET @a3 = LAST_INSERT_ID();
INSERT INTO venues (user_id, address_id, name, average_price_per_hour, floor_type, status)
VALUES (@dono, @a3, 'Clay Teste S3', NULL, 'clay', 'maintenance'); SET @vC = LAST_INSERT_ID();

-- D: sem horário cadastrado e sem coordenadas
INSERT INTO addresses (cep, street_id, number) VALUES ('08500-100', @sr, '400'); SET @a4 = LAST_INSERT_ID();
INSERT INTO venues (user_id, address_id, name, average_price_per_hour, floor_type, status)
VALUES (@dono, @a4, 'Duda Teste S3', 80.00, 'court', 'available'); SET @vD = LAST_INSERT_ID();

-- E: desativada no site (unavailable): nunca aparece na API (D34)
INSERT INTO addresses (cep, street_id, number, latitude, longitude) VALUES ('08500-200', @sr, '500', -23.54300000, -46.36900000); SET @a5 = LAST_INSERT_ID();
INSERT INTO venues (user_id, address_id, name, average_price_per_hour, floor_type, status)
VALUES (@dono, @a5, 'Excluida Teste S3', 50.00, 'grass', 'unavailable'); SET @vE = LAST_INSERT_ID();

-- Horário: A e B abrem todos os dias 00:00–23:59; C também (mas está em manutenção)
INSERT INTO venue_hours (venue_id, weekday, opens_at, closes_at)
SELECT v.id, d.wd, '00:00:00', '23:59:00'
FROM (SELECT @vA AS id UNION SELECT @vB UNION SELECT @vC) v
CROSS JOIN (SELECT 0 wd UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) d;

INSERT INTO venue_sports (venue_id, sport_id) VALUES (@vA, @fut), (@vB, @vol), (@vB, @fut);

-- Partidas: A agora; B mais tarde (2 emendadas); uma cancelada em B agora (não conta)
INSERT INTO matches (venue_id, creator_user_id, renter_name, status, start_time, end_time) VALUES
  (@vA, @dono, 'Time do Carlos', 'scheduled', NOW() - INTERVAL 30 MINUTE, NOW() + INTERVAL 30 MINUTE),
  (@vB, @dono, NULL, 'scheduled', NOW() + INTERVAL 60 MINUTE, NOW() + INTERVAL 75 MINUTE),
  (@vB, @dono, NULL, 'scheduled', NOW() + INTERVAL 75 MINUTE, NOW() + INTERVAL 90 MINUTE),
  (@vB, @dono, NULL, 'cancelled', NOW() - INTERVAL 10 MINUTE, NOW() + INTERVAL 10 MINUTE);

INSERT INTO venues_images (venue_id, file_path) VALUES (@vA, 'venue_capa.jpg'), (@vA, 'venue_2.jpg');

SELECT @vA AS A, @vB AS B, @vC AS C, @vD AS D, @vE AS E, @fut AS futebol, @vol AS volei;
