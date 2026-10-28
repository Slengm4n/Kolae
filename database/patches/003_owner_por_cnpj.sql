-- =====================================================================
-- Patch 003 — Quem tem CNPJ passa a ser "owner" (responsável por quadra)
-- =====================================================================
-- Decidido em 28/10/2026: role "owner" = quem cadastrou CNPJ no site.
-- A partir da v1.5.0, o cadastro de CNPJ no site já troca player -> owner;
-- este patch converte quem cadastrou antes. Admin continua admin.
--
-- Não depende do código novo: pode ir para a produção antes ou junto do deploy.
-- Pode ser executado mais de uma vez.
-- =====================================================================

UPDATE `users`
SET `role` = 'owner'
WHERE `role` = 'player'
  AND `cnpj` IS NOT NULL
  AND `cnpj` <> '';
