-- =====================================================================
-- Seed de demonstração: 90 dias de visitas e cliques para UMA quadra (estatísticas, D62)
-- =====================================================================
-- Para a apresentação e para testar a aba Estatísticas com números. Os números são inventados:
-- usar só em quadras de demonstração, NUNCA para "melhorar" os números de uma quadra real.
--
-- Como usar: troque o 0 abaixo pelo id da quadra e rode no banco (local ou de demonstração).
-- Rodar de novo apaga os eventos dessa quadra e gera de novo. Precisa do patch 009.
-- Funciona no MariaDB 10.2+ e no MySQL 8+ (WITH RECURSIVE).
-- =====================================================================

SET @venue_id := 0;

DELETE FROM venue_events WHERE venue_id = @venue_id;

INSERT INTO venue_events (venue_id, type, source, visitor, created_at)
WITH RECURSIVE
  dias AS (SELECT 0 AS d UNION ALL SELECT d + 1 FROM dias WHERE d < 89),
  seq AS (SELECT 1 AS k UNION ALL SELECT k + 1 FROM seq WHERE k < 45),
  base AS (
    SELECT d, k,
           -- Visualizações do dia: cresce com o tempo, mais no fim de semana
           FLOOR(6 + CONV(SUBSTRING(MD5(CONCAT(d, '-dia')), 1, 4), 16, 10) / 65535 * 14 + (89 - d) * 0.12
                 + IF(DAYOFWEEK(CURDATE() - INTERVAL d DAY) IN (1, 6, 7), 9, 0)) AS views
    FROM dias JOIN seq
  )
SELECT @venue_id,
       CASE
         WHEN CONV(SUBSTRING(MD5(CONCAT(d, '-', k, '-w')), 1, 4), 16, 10) / 65535 < 0.14 THEN 'whatsapp'
         WHEN CONV(SUBSTRING(MD5(CONCAT(d, '-', k, '-s')), 1, 4), 16, 10) / 65535 < 0.05 THEN ELT(1 + ((d + k) % 4), 'instagram', 'tiktok', 'facebook', 'website')
         WHEN CONV(SUBSTRING(MD5(CONCAT(d, '-', k, '-c')), 1, 4), 16, 10) / 65535 < 0.03 THEN 'share'
         ELSE 'view'
       END,
       CASE WHEN k % 10 < 5 THEN 'app' WHEN k % 10 < 8 THEN 'site' ELSE 'qr' END,
       -- Alguns visitantes voltam no mesmo dia (visitantes < visualizações)
       SUBSTRING(MD5(CONCAT(@venue_id, '-', d, '-', FLOOR(k / 1.3))), 1, 16),
       (CURDATE() - INTERVAL d DAY) + INTERVAL (8 + FLOOR(CONV(SUBSTRING(MD5(CONCAT(d, '-', k, '-h')), 1, 4), 16, 10) / 65535 * 14)) HOUR + INTERVAL FLOOR(CONV(SUBSTRING(MD5(CONCAT(d, '-', k, '-m')), 1, 4), 16, 10) / 65535 * 59) MINUTE
FROM base
WHERE k <= views AND @venue_id > 0;

SELECT IF(@venue_id > 0, CONCAT(ROW_COUNT(), ' eventos criados para a quadra ', @venue_id), 'Troque o 0 em SET @venue_id pelo id da quadra') AS resultado;
