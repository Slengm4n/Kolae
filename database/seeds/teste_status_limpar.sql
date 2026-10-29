-- Apaga tudo o que database/seeds/teste_status.sql criou.
SET @dono = (SELECT id FROM users WHERE email = 'dono-s3@kolae.test');
DELETE FROM matches WHERE creator_user_id = @dono;
DELETE FROM venues_images WHERE venue_id IN (SELECT id FROM venues WHERE user_id = @dono);
DELETE FROM venues WHERE user_id = @dono;
DELETE FROM addresses WHERE street_id IN (SELECT id FROM streets WHERE name = 'Rua Teste S3');
DELETE FROM streets WHERE name = 'Rua Teste S3';
DELETE FROM neighborhoods WHERE name = 'Centro Teste S3';
DELETE FROM cities WHERE name = 'Ferraz Teste S3';
DELETE FROM states WHERE name = 'SP Teste S3';
DELETE FROM sports WHERE name LIKE '%Teste S3';
DELETE FROM users WHERE email LIKE '%@kolae.test';
SELECT (SELECT COUNT(*) FROM users WHERE email LIKE '%@kolae.test') AS usuarios_teste,
       (SELECT COUNT(*) FROM venues WHERE name LIKE '%Teste S3') AS quadras_teste;
