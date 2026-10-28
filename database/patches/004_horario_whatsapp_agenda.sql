-- =====================================================================
-- Patch 004 — Horário de funcionamento, WhatsApp do dono, agenda de partidas
-- =====================================================================
-- Modelo aprovado em 28/10/2026: docs/modelo-status.md (D21–D27) e D29.
--
-- O que faz:
--   * cria venue_hours (um intervalo de funcionamento por dia da semana)
--   * cria venue_sports (esportes de cada quadra, D29)
--   * adiciona users.whatsapp
--   * remove match_participants (modelo antigo, vazia)
--   * matches: sport_id opcional, renter_name novo, max_players removido,
--     status só scheduled | cancelled, índice para a agenda
--
-- Quando aplicar na produção: junto com o deploy da S3 (v1.6.0). Nada aqui
-- quebra o código atual, mas as tabelas novas só são usadas a partir dela.
--
-- Como aplicar: phpMyAdmin > banco > aba SQL > colar e executar.
-- Pela linha de comando do XAMPP, usar --default-character-set=utf8mb4: o padrão
-- dele (cp850) corrompe os acentos dos comentários das colunas.
-- Requer MariaDB (usa IF EXISTS / IF NOT EXISTS). Pode ser executado mais de uma vez.
-- Testado no kolae_db local em 28/10/2026 (duas execuções seguidas, CHECKs e FKs).
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Horário de funcionamento (D21, D22, D23)
--    Dia sem linha = fechada o dia todo. Quadra sem nenhuma linha = sem
--    horário cadastrado (a API trata como closed).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `venue_hours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_id` int(11) NOT NULL,
  `weekday` tinyint(1) NOT NULL COMMENT '0 = domingo ... 6 = sábado (igual ao date("w") do PHP)',
  `opens_at` time NOT NULL,
  `closes_at` time NOT NULL COMMENT 'Depois de opens_at, no mesmo dia (não passa da meia-noite)',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_venue_hours_day` (`venue_id`, `weekday`),
  CONSTRAINT `fk_venue_hours_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_venue_hours_weekday` CHECK (`weekday` BETWEEN 0 AND 6),
  CONSTRAINT `chk_venue_hours_interval` CHECK (`closes_at` > `opens_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Esportes de cada quadra (D29)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `venue_sports` (
  `venue_id` int(11) NOT NULL,
  `sport_id` int(11) NOT NULL,
  PRIMARY KEY (`venue_id`, `sport_id`),
  KEY `fk_venue_sports_sport` (`sport_id`),
  CONSTRAINT `fk_venue_sports_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_venue_sports_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. WhatsApp do dono (D24)
-- ---------------------------------------------------------------------
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `whatsapp` varchar(20) NULL DEFAULT NULL
    COMMENT 'Só dígitos, com DDI e DDD: 5511999999999' AFTER `cnpj`;

-- ---------------------------------------------------------------------
-- 4. Remove match_participants (D26). Antes de mexer em matches, porque
--    ela tem FK para matches.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `match_participants`;

-- ---------------------------------------------------------------------
-- 5. matches: partidas agendadas pelo dono (D02, D25, D26, D27)
-- ---------------------------------------------------------------------
-- Status gravado só scheduled | cancelled ("em andamento" e "terminada" são
-- calculados pelo horário). A tabela está vazia na produção; isto é só garantia.
UPDATE `matches` SET `status` = 'scheduled' WHERE `status` NOT IN ('scheduled', 'cancelled');

-- sport_id opcional: a FK é recriada com ON DELETE SET NULL
ALTER TABLE `matches` DROP FOREIGN KEY IF EXISTS `fk_matches_sport`;

ALTER TABLE `matches`
  MODIFY `sport_id` int(11) NULL DEFAULT NULL COMMENT 'Opcional: o dono pode não saber o esporte',
  MODIFY `status` varchar(20) NOT NULL DEFAULT 'scheduled' COMMENT 'scheduled | cancelled',
  ADD COLUMN IF NOT EXISTS `renter_name` varchar(100) NULL DEFAULT NULL
    COMMENT 'Para quem foi alugado (só aparece no site, nunca na API)' AFTER `creator_user_id`,
  DROP COLUMN IF EXISTS `max_players`,
  ADD INDEX IF NOT EXISTS `idx_matches_venue_start` (`venue_id`, `start_time`);

ALTER TABLE `matches`
  ADD CONSTRAINT `fk_matches_sport` FOREIGN KEY IF NOT EXISTS (`sport_id`) REFERENCES `sports` (`id`) ON DELETE SET NULL;
