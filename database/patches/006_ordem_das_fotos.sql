-- =====================================================================
-- Patch 006 — Ordem das fotos da quadra (aba Fotos, D46)
-- =====================================================================
-- O que faz:
--   * adiciona venues_images.position: a ordem das fotos; a de menor posição é a capa
--   * preenche a posição das fotos que já existem na ordem de envio (a capa continua a mesma)
--
-- Quando aplicar na produção: ANTES do merge da aba Fotos. O código novo ordena
-- por essa coluna e dá erro sem ela; o código atual simplesmente ignora a coluna.
--
-- Como aplicar: phpMyAdmin > banco > aba SQL > colar e executar.
-- Pode ser executado mais de uma vez (só renumera na ordem atual).
-- =====================================================================

ALTER TABLE `venues_images`
  ADD COLUMN IF NOT EXISTS `position` int(11) NOT NULL DEFAULT 0 COMMENT 'Ordem na galeria; a menor é a capa' AFTER `file_path`;

-- Numera 0, 1, 2... por quadra, mantendo a ordem atual (posição e depois envio)
UPDATE `venues_images` vi
JOIN (
  SELECT a.id, COUNT(b.id) AS pos
  FROM `venues_images` a
  LEFT JOIN `venues_images` b
    ON b.venue_id = a.venue_id
   AND (b.position < a.position OR (b.position = a.position AND b.id < a.id))
  GROUP BY a.id
) t ON t.id = vi.id
SET vi.position = t.pos;

ALTER TABLE `venues_images` DROP INDEX IF EXISTS `idx_venues_images_order`;
ALTER TABLE `venues_images` ADD INDEX `idx_venues_images_order` (`venue_id`, `position`);
