-- =====================================================================
-- Patch 002 — Padroniza venues.floor_type nas chaves de Venue::FLOOR_TYPES
-- =====================================================================
-- Antes: create.php gravava o rótulo em minúsculas ("cimento / poliesportivo",
-- cortado para 'cimento / poliesport' pelo varchar(20)) e edit.php usava chaves.
-- Depois: só synthetic | court | sand | clay | grass | wood.
--
-- APLICAR JUNTO com o deploy do código que usa Venue::FLOOR_TYPES
-- (create.php, edit.php e VenueController). Se o código antigo continuar no ar,
-- quadras novas voltam a ser gravadas com o rótulo.
--
-- Pode ser executado mais de uma vez.
-- =====================================================================

UPDATE `venues`
SET `floor_type` = CASE
    WHEN `floor_type` LIKE 'grama sint%'                          THEN 'synthetic'
    WHEN `floor_type` LIKE 'cimento%' OR `floor_type` LIKE 'quadra%' THEN 'court'
    WHEN `floor_type` = 'areia'                                   THEN 'sand'
    WHEN `floor_type` = 'saibro'                                  THEN 'clay'
    WHEN `floor_type` = 'grama natural'                           THEN 'grass'
    WHEN `floor_type` LIKE 'madeira%'                             THEN 'wood'
    ELSE NULL
END
WHERE `floor_type` IS NOT NULL
  AND `floor_type` NOT IN ('synthetic', 'court', 'sand', 'clay', 'grass', 'wood');

ALTER TABLE `venues`
  MODIFY `floor_type` varchar(20) NULL DEFAULT NULL COMMENT 'synthetic | court | sand | clay | grass | wood';
