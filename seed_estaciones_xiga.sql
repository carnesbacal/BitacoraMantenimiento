-- ============================================================
-- Alta automática de estaciones Xiga (Baja California) en el catálogo
-- a partir de las cargas ya importadas de Xiga (flotilla_combustible.estacion).
--
-- Requiere haber corrido antes: migracion_combustible.sql
-- Es seguro re-ejecutarlo (no duplica: usa NOT EXISTS).
-- ============================================================

-- 1) Dar de alta cada estación distinta que aparezca en tus cargas,
--    con la ciudad deducida del prefijo del código Xiga.
INSERT INTO `flotilla_estaciones` (`nombre`, `direccion`, `activo`)
SELECT DISTINCT
       c.`estacion` AS nombre,
       CASE
           WHEN c.`estacion` LIKE 'TIJ-%' THEN 'Tijuana, B.C.'
           WHEN c.`estacion` LIKE 'RST-%' THEN 'Playas de Rosarito, B.C.'
           WHEN c.`estacion` LIKE 'MXL-%' THEN 'Mexicali, B.C.'
           WHEN c.`estacion` LIKE 'ENS-%' THEN 'Ensenada, B.C.'
           WHEN c.`estacion` LIKE 'TEC-%' THEN 'Tecate, B.C.'
           ELSE 'Baja California'
       END AS direccion,
       1 AS activo
FROM `flotilla_combustible` c
WHERE c.`estacion` IS NOT NULL
  AND c.`estacion` <> ''
  AND NOT EXISTS (
        SELECT 1 FROM `flotilla_estaciones` e WHERE e.`nombre` = c.`estacion`
  );

-- 2) (Opcional) Enlazar las cargas históricas con la estación del catálogo,
--    para que los reportes por estación incluyan lo ya registrado.
UPDATE `flotilla_combustible` c
JOIN   `flotilla_estaciones` e ON e.`nombre` = c.`estacion`
SET    c.`estacion_id` = e.`id`
WHERE  c.`estacion_id` IS NULL;
