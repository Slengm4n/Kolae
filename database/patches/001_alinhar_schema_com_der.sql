-- =====================================================================
-- Patch 001 — Alinha o banco com database/kolae.dbml
-- =====================================================================
-- O que faz (nada aqui quebra o código atual, pode ir antes do deploy):
--   * cria user_tokens ("lembrar de mim", lida por AuthHelper::checkRememberMe)
--   * adiciona matches.max_players
--   * corrige defaults/comentários de status (venues, matches, match_participants, users, sports)
--   * troca UNIQUE(name) de states por UNIQUE(country_id, name) e cria os
--     UNIQUE (pai, name) de cities, neighborhoods e streets
--   * recria FKs com ON DELETE/UPDATE CASCADE onde faz sentido
--   * remove audit_log (vazia e sem uso no código)
--
-- Como aplicar: phpMyAdmin > banco > aba SQL > colar e executar.
-- Requer MariaDB (usa IF EXISTS / IF NOT EXISTS). Pode ser executado mais de uma vez.
-- Testado no kolae_db local (cópia da produção de 27/10/2026).
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. user_tokens
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `selector` varchar(32) NOT NULL,
  `hashed_validator` varchar(64) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_tokens_selector` (`selector`),
  KEY `fk_user_tokens_user` (`user_id`),
  CONSTRAINT `fk_user_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Colunas novas
-- ---------------------------------------------------------------------
ALTER TABLE `matches`
  ADD COLUMN IF NOT EXISTS `max_players` int(11) NULL DEFAULT NULL
    COMMENT 'NULL = sem limite. Quando lotar, status vira full' AFTER `creator_user_id`;

-- ---------------------------------------------------------------------
-- 3. Status: valores padronizados + defaults
-- ---------------------------------------------------------------------
UPDATE `venues` SET `status` = 'available'   WHERE `status` = 'active';
UPDATE `venues` SET `status` = 'unavailable' WHERE `status` = 'inactive';
UPDATE `venues` SET `status` = 'maintenance' WHERE `status` IN ('under_maintenance', 'in_maintenance');

ALTER TABLE `venues`
  MODIFY `status` varchar(20) NOT NULL DEFAULT 'available' COMMENT 'available | unavailable | maintenance';

ALTER TABLE `matches`
  MODIFY `status` varchar(20) NOT NULL DEFAULT 'scheduled' COMMENT 'scheduled | full | in_progress | finished | cancelled';

ALTER TABLE `match_participants`
  MODIFY `status` varchar(20) NOT NULL DEFAULT 'confirmed' COMMENT 'confirmed | pending | cancelled';

ALTER TABLE `users`
  MODIFY `role` varchar(20) NOT NULL DEFAULT 'player' COMMENT 'admin | owner | player';

-- sports.status era ENUM; vira varchar como as demais tabelas
ALTER TABLE `sports`
  MODIFY `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active | inactive';

-- ---------------------------------------------------------------------
-- 4. Índices únicos da hierarquia de endereço (evita duplicatas no findOrCreate)
-- ---------------------------------------------------------------------
ALTER TABLE `states` DROP INDEX IF EXISTS `name`;
ALTER TABLE `states`        ADD UNIQUE KEY IF NOT EXISTS `uq_states_country_name` (`country_id`, `name`);
ALTER TABLE `cities`        ADD UNIQUE KEY IF NOT EXISTS `uq_cities_state_name` (`state_id`, `name`);
ALTER TABLE `neighborhoods` ADD UNIQUE KEY IF NOT EXISTS `uq_neighborhoods_city_name` (`city_id`, `name`);
ALTER TABLE `streets`       ADD UNIQUE KEY IF NOT EXISTS `uq_streets_neighborhood_name` (`neighborhood_id`, `name`);

-- ---------------------------------------------------------------------
-- 5. FKs com cascade (drop + add; o nome continua o mesmo)
-- ---------------------------------------------------------------------
ALTER TABLE `venues_images` DROP FOREIGN KEY IF EXISTS `fk_venues_images_venue`;
ALTER TABLE `venues_images`
  ADD CONSTRAINT `fk_venues_images_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE;

ALTER TABLE `match_participants` DROP FOREIGN KEY IF EXISTS `fk_mp_match`;
ALTER TABLE `match_participants`
  ADD CONSTRAINT `fk_mp_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE;

ALTER TABLE `match_participants` DROP FOREIGN KEY IF EXISTS `fk_mp_user`;
ALTER TABLE `match_participants`
  ADD CONSTRAINT `fk_mp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `password_reset_tokens` DROP FOREIGN KEY IF EXISTS `fk_prt_users`;
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_prt_users` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `sessions` DROP FOREIGN KEY IF EXISTS `fk_sessions_users`;
ALTER TABLE `sessions`
  ADD CONSTRAINT `fk_sessions_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- ---------------------------------------------------------------------
-- 6. Remove tabela sem uso
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_log`;
