-- Kolae - estrutura do banco (sem dados)
-- Gerado do kolae_db local apos database/patches/001 a 004, com os patches seguintes (005 a 009) aplicados a mao. Desenho: database/kolae.dbml
-- Uso: criar um banco vazio (utf8mb4) e importar este arquivo.

SET FOREIGN_KEY_CHECKS = 0;
CREATE TABLE `addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cep` varchar(9) DEFAULT NULL,
  `street_id` int(11) NOT NULL,
  `number` varchar(50) DEFAULT NULL,
  `complement` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_addresses_street` (`street_id`),
  CONSTRAINT `fk_addresses_street` FOREIGN KEY (`street_id`) REFERENCES `streets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `cities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cities_state_name` (`state_id`,`name`),
  KEY `fk_cities_state` (`state_id`),
  CONSTRAINT `fk_cities_state` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_hash` char(64) NOT NULL COMMENT 'SHA-256 do e-mail em minúsculas',
  `ip` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_account` (`email_hash`, `ip`, `created_at`),
  KEY `idx_login_attempts_ip` (`ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `match_requests` (
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
CREATE TABLE `matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_id` int(11) NOT NULL,
  `sport_id` int(11) DEFAULT NULL COMMENT 'Opcional: o dono pode não saber o esporte',
  `creator_user_id` int(11) NOT NULL,
  `renter_name` varchar(100) DEFAULT NULL COMMENT 'Para quem foi alugado (só aparece no site, nunca na API)',
  `status` varchar(20) NOT NULL DEFAULT 'scheduled' COMMENT 'scheduled | cancelled',
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_matches_venue` (`venue_id`),
  KEY `fk_matches_sport` (`sport_id`),
  KEY `fk_matches_creator` (`creator_user_id`),
  KEY `idx_matches_venue_start` (`venue_id`,`start_time`),
  CONSTRAINT `fk_matches_creator` FOREIGN KEY (`creator_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_matches_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_matches_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `neighborhoods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `city_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_neighborhoods_city_name` (`city_id`,`name`),
  KEY `fk_neighborhoods_city` (`city_id`),
  CONSTRAINT `fk_neighborhoods_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `notifications` (
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
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`email`),
  UNIQUE KEY `token` (`token`),
  CONSTRAINT `fk_prt_users` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` mediumtext DEFAULT NULL,
  `payload` longtext DEFAULT NULL,
  `last_activity` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sessions_users` (`user_id`),
  CONSTRAINT `fk_sessions_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `sports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active | inactive',
  `description` mediumtext DEFAULT NULL,
  `icon_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_states_country_name` (`country_id`,`name`),
  KEY `fk_states_country` (`country_id`),
  CONSTRAINT `fk_states_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `streets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `neighborhood_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_streets_neighborhood_name` (`neighborhood_id`,`name`),
  KEY `fk_streets_neighborhood` (`neighborhood_id`),
  CONSTRAINT `fk_streets_neighborhood` FOREIGN KEY (`neighborhood_id`) REFERENCES `neighborhoods` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `user_tokens` (
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
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `cnpj` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL COMMENT 'Só dígitos, com DDI e DDD: 5511999999999',
  `birthdate` date DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'player' COMMENT 'admin | owner | player',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'enum: active | inactive | suspended',
  `avatar_path` varchar(255) DEFAULT NULL,
  `force_password_change` tinyint(1) DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `uq_users_cnpj` (`cnpj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `venue_ads` (
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
CREATE TABLE `venue_events` (
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
CREATE TABLE `venue_hours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_id` int(11) NOT NULL,
  `weekday` tinyint(1) NOT NULL COMMENT '0 = domingo ... 6 = sábado (igual ao date("w") do PHP)',
  `opens_at` time NOT NULL,
  `closes_at` time NOT NULL COMMENT 'Depois de opens_at, no mesmo dia (não passa da meia-noite)',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_venue_hours_day` (`venue_id`,`weekday`),
  CONSTRAINT `fk_venue_hours_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_venue_hours_weekday` CHECK (`weekday` between 0 and 6),
  CONSTRAINT `chk_venue_hours_interval` CHECK (`closes_at` > `opens_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `venue_sports` (
  `venue_id` int(11) NOT NULL,
  `sport_id` int(11) NOT NULL,
  PRIMARY KEY (`venue_id`,`sport_id`),
  KEY `fk_venue_sports_sport` (`sport_id`),
  CONSTRAINT `fk_venue_sports_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_venue_sports_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `venues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `address_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(1000) DEFAULT NULL COMMENT 'Texto livre do dono (opcional)',
  `instagram` varchar(30) DEFAULT NULL COMMENT 'Usuário do Instagram, sem @ (D60)',
  `tiktok` varchar(30) DEFAULT NULL COMMENT 'Usuário do TikTok, sem @ (D60)',
  `facebook_url` varchar(255) DEFAULT NULL COMMENT 'Página do Facebook, https:// (D60)',
  `website_url` varchar(255) DEFAULT NULL COMMENT 'Site da quadra, https:// (D60)',
  `average_price_per_hour` decimal(10,2) DEFAULT NULL,
  `court_capacity` int(11) DEFAULT NULL,
  `has_leisure_area` tinyint(1) DEFAULT 0,
  `leisure_area_capacity` int(11) DEFAULT NULL,
  `floor_type` varchar(20) DEFAULT NULL COMMENT 'synthetic | court | sand | clay | grass | wood',
  `has_lighting` tinyint(1) DEFAULT 0,
  `is_covered` tinyint(1) DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'available' COMMENT 'available | unavailable | maintenance',
  `plan` varchar(10) NOT NULL DEFAULT 'free' COMMENT 'free | premium (D61)',
  `plan_until` date DEFAULT NULL COMMENT 'Validade do Premium; NULL = sem prazo (D61)',
  `plan_requested_at` timestamp NULL DEFAULT NULL COMMENT 'Quando o dono pediu o Premium (D61)',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_venues_user` (`user_id`),
  KEY `fk_venues_address` (`address_id`),
  CONSTRAINT `fk_venues_address` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`),
  CONSTRAINT `fk_venues_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `venues_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0 COMMENT 'Ordem na galeria; a menor é a capa',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_venues_images_venue` (`venue_id`),
  KEY `idx_venues_images_order` (`venue_id`,`position`),
  CONSTRAINT `fk_venues_images_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 1;
