-- =====================================================================
-- Patch 009 — Kolae Premium (D61–D64)
-- =====================================================================
-- O que faz (tudo só se ainda não existir):
--   * users.plan, users.plan_until, users.plan_requested_at: Kolae Premium do dono, vale para todas as
--     quadras dele (D61). Uma versão anterior deste patch criava essas colunas em venues; se ela já
--     foi aplicada, as colunas de venues ficam sem uso e não atrapalham.
--   * venue_events: registro de visualizações e cliques para as estatísticas (D62)
--   * venue_ads: anúncios das quadras Premium (D63)
--   * match_requests: pedidos de horário dos jogadores (D64)
--   * notifications: avisos do painel e do app (D64)
--
-- Quando aplicar na produção: ANTES do merge da primeira PR do Premium (o plano). Um patch só
-- para as quatro partes: as PRs seguintes não precisam de outro.
--
-- Como aplicar: phpMyAdmin > clicar no NOME DO BANCO do Kolae na lista da esquerda >
-- aba SQL > colar e executar. Sem banco escolhido, o patch só mostra um aviso.
-- Funciona no MySQL e no MariaDB (confere no information_schema, como o 006 e o 007).
-- Pode ser executado mais de uma vez.
-- =====================================================================

SET @banco := DATABASE();
SELECT IF(@banco IS NULL, 'Nenhum banco selecionado: clique no banco do Kolae na lista da esquerda e rode de novo na aba SQL dele', CONCAT('Aplicando no banco ', @banco)) AS aviso;

-- ---------------------------------------------------------------------
-- Kolae Premium do dono (D61)
-- ---------------------------------------------------------------------
SET @sql := IF(@banco IS NULL OR (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @banco AND TABLE_NAME = 'users' AND COLUMN_NAME = 'plan') > 0,
  'SELECT ''users.plan ok''',
  'ALTER TABLE `users` ADD COLUMN `plan` varchar(10) NOT NULL DEFAULT ''free'' COMMENT ''free | premium (D61)'' AFTER `status`');
PREPARE passo FROM @sql; EXECUTE passo; DEALLOCATE PREPARE passo;

SET @sql := IF(@banco IS NULL OR (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @banco AND TABLE_NAME = 'users' AND COLUMN_NAME = 'plan_until') > 0,
  'SELECT ''users.plan_until ok''',
  'ALTER TABLE `users` ADD COLUMN `plan_until` date DEFAULT NULL COMMENT ''Validade do Premium; NULL = sem prazo (D61)'' AFTER `plan`');
PREPARE passo FROM @sql; EXECUTE passo; DEALLOCATE PREPARE passo;

SET @sql := IF(@banco IS NULL OR (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @banco AND TABLE_NAME = 'users' AND COLUMN_NAME = 'plan_requested_at') > 0,
  'SELECT ''users.plan_requested_at ok''',
  'ALTER TABLE `users` ADD COLUMN `plan_requested_at` timestamp NULL DEFAULT NULL COMMENT ''Quando o dono pediu o Premium (D61)'' AFTER `plan_until`');
PREPARE passo FROM @sql; EXECUTE passo; DEALLOCATE PREPARE passo;

-- ---------------------------------------------------------------------
-- Tabelas novas (CREATE TABLE IF NOT EXISTS funciona no MySQL e no MariaDB)
-- Sem banco selecionado, os comandos abaixo falham sem mudar nada.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `venue_events` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `venue_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL COMMENT 'view | whatsapp | instagram | tiktok | facebook | website | share | ad_view | ad_click (D62)',
  `source` varchar(10) NOT NULL DEFAULT 'site' COMMENT 'site | app | qr',
  `visitor` char(16) DEFAULT NULL COMMENT 'Hash do dia (sem IP), para contar visitantes únicos',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_venue_events_venue_date` (`venue_id`, `created_at`),
  CONSTRAINT `fk_venue_events_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `venue_ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_id` int(11) NOT NULL,
  `title` varchar(60) NOT NULL,
  `body` varchar(140) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL COMMENT 'Sem foto, usa a capa da quadra',
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL COMMENT 'Até 30 dias depois do início (D63)',
  `status` varchar(10) NOT NULL DEFAULT 'active' COMMENT 'active | paused | ended | removed',
  `removed_reason` varchar(200) DEFAULT NULL COMMENT 'Motivo, quando o admin remove',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_venue_ads_venue` (`venue_id`, `status`),
  CONSTRAINT `fk_venue_ads_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `match_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sport_id` int(11) DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `message` varchar(200) DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'pending' COMMENT 'pending | approved | declined | cancelled | expired (D64)',
  `response_note` varchar(200) DEFAULT NULL,
  `match_id` int(11) DEFAULT NULL COMMENT 'Partida criada na aprovação',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_match_requests_venue` (`venue_id`, `status`, `start_time`),
  KEY `idx_match_requests_user` (`user_id`, `status`),
  CONSTRAINT `fk_match_requests_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_match_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_match_requests_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(30) NOT NULL COMMENT 'request_new | request_approved | request_declined | request_cancelled | request_expired | plan_activated | plan_ending | ad_removed (D64)',
  `data` text DEFAULT NULL COMMENT 'JSON com ids e textos curtos',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`, `read_at`, `created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Patch 009 concluído' AS resultado;
