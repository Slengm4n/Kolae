-- =====================================================================
-- Patch 005 — Limite de tentativas de login (S5)
-- =====================================================================
-- O que faz:
--   * cria login_attempts: um registro por login errado, no site ou na API
--     (App\Core\LoginThrottle). O e-mail fica só como hash SHA-256.
--
-- Quando aplicar na produção: junto com o deploy da S5. Sem a tabela o login
-- continua funcionando, só fica sem o limite.
--
-- Como aplicar: phpMyAdmin > banco > aba SQL > colar e executar.
-- Pode ser executado mais de uma vez.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_hash` char(64) NOT NULL COMMENT 'SHA-256 do e-mail em minúsculas',
  `ip` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_account` (`email_hash`, `ip`, `created_at`),
  KEY `idx_login_attempts_ip` (`ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
