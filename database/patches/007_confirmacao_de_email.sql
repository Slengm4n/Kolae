-- =====================================================================
-- Patch 007 — Confirmação de e-mail (D58)
-- =====================================================================
-- O que faz:
--   * garante a coluna users.email_verified_at (data da confirmação; vazia = não confirmado).
--     Ela já existe desde a criação do banco; o patch só a cria se faltar.
--
-- Quando aplicar na produção: ANTES do merge da confirmação de e-mail. Se a coluna já
-- existir (o normal), o patch não muda nada.
--
-- Como aplicar: phpMyAdmin > banco > aba SQL > colar e executar.
-- Funciona no MySQL e no MariaDB (confere no information_schema, como o 006).
-- Pode ser executado mais de uma vez.
-- =====================================================================

SET @tem_coluna := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verified_at');
SET @sql := IF(@tem_coluna = 0,
  'ALTER TABLE `users` ADD COLUMN `email_verified_at` timestamp NULL DEFAULT NULL AFTER `status`',
  'SELECT ''coluna email_verified_at já existe''');
PREPARE passo FROM @sql;
EXECUTE passo;
DEALLOCATE PREPARE passo;
