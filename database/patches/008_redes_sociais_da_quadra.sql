-- =====================================================================
-- Patch 008 — Redes sociais da quadra (D60)
-- =====================================================================
-- O que faz:
--   * adiciona em venues as quatro redes da quadra, todas opcionais (NULL = não informado):
--     instagram e tiktok (só o usuário, sem @) e facebook_url e website_url (endereço com https://).
--
-- Quando aplicar na produção: ANTES do merge das redes sociais. O código novo lê e grava
-- essas colunas e dá erro sem elas; o código atual simplesmente as ignora.
--
-- Como aplicar: phpMyAdmin > clicar no NOME DO BANCO do Kolae na lista da esquerda >
-- aba SQL > colar e executar. Na aba SQL da tela inicial (sem banco escolhido) o
-- patch só mostra um aviso e não muda nada.
-- Funciona no MySQL e no MariaDB (confere no information_schema, como o 006 e o 007).
-- Pode ser executado mais de uma vez.
-- =====================================================================

-- instagram
SET @tem_coluna := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'venues' AND COLUMN_NAME = 'instagram');
SET @sql := IF(DATABASE() IS NULL,
  'SELECT ''Nenhum banco selecionado: clique no banco do Kolae na lista da esquerda e rode de novo na aba SQL dele''',
  IF(@tem_coluna = 0,
    'ALTER TABLE `venues` ADD COLUMN `instagram` varchar(30) DEFAULT NULL COMMENT ''Usuário do Instagram, sem @ (D60)'' AFTER `description`',
    'SELECT ''coluna instagram já existe'''));
PREPARE passo FROM @sql;
EXECUTE passo;
DEALLOCATE PREPARE passo;

-- tiktok
SET @tem_coluna := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'venues' AND COLUMN_NAME = 'tiktok');
SET @sql := IF(DATABASE() IS NULL,
  'SELECT ''Nenhum banco selecionado''',
  IF(@tem_coluna = 0,
    'ALTER TABLE `venues` ADD COLUMN `tiktok` varchar(30) DEFAULT NULL COMMENT ''Usuário do TikTok, sem @ (D60)'' AFTER `instagram`',
    'SELECT ''coluna tiktok já existe'''));
PREPARE passo FROM @sql;
EXECUTE passo;
DEALLOCATE PREPARE passo;

-- facebook_url
SET @tem_coluna := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'venues' AND COLUMN_NAME = 'facebook_url');
SET @sql := IF(DATABASE() IS NULL,
  'SELECT ''Nenhum banco selecionado''',
  IF(@tem_coluna = 0,
    'ALTER TABLE `venues` ADD COLUMN `facebook_url` varchar(255) DEFAULT NULL COMMENT ''Página do Facebook, https:// (D60)'' AFTER `tiktok`',
    'SELECT ''coluna facebook_url já existe'''));
PREPARE passo FROM @sql;
EXECUTE passo;
DEALLOCATE PREPARE passo;

-- website_url
SET @tem_coluna := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'venues' AND COLUMN_NAME = 'website_url');
SET @sql := IF(DATABASE() IS NULL,
  'SELECT ''Nenhum banco selecionado''',
  IF(@tem_coluna = 0,
    'ALTER TABLE `venues` ADD COLUMN `website_url` varchar(255) DEFAULT NULL COMMENT ''Site da quadra, https:// (D60)'' AFTER `facebook_url`',
    'SELECT ''coluna website_url já existe'''));
PREPARE passo FROM @sql;
EXECUTE passo;
DEALLOCATE PREPARE passo;
