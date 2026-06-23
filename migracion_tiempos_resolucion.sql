-- ============================================================
-- Recalcular los tiempos de incidencias existentes con los nuevos criterios:
--   * Tiempo de respuesta  = desde que se REGISTRÓ (creado_en) hasta ATENCIÓN.
--   * Tiempo de resolución = desde que OCURRIÓ el evento hasta RESUELTA.
-- (Las nuevas/editadas ya se calculan así automáticamente.)
-- Seguro de re-ejecutar.
-- ============================================================

-- Tiempo de respuesta: creación -> atención (inmune a fechas de evento hacia atrás)
UPDATE `incidencias`
SET `tiempo_respuesta_min` = GREATEST(0, TIMESTAMPDIFF(MINUTE, `creado_en`, `fecha_atencion`))
WHERE `fecha_atencion` IS NOT NULL
  AND `creado_en` IS NOT NULL;

-- Tiempo de resolución: evento -> resuelta
UPDATE `incidencias`
SET `tiempo_resolucion_min` = GREATEST(0, TIMESTAMPDIFF(MINUTE, `fecha_evento`, `fecha_resolucion`))
WHERE `fecha_resolucion` IS NOT NULL
  AND `fecha_evento` IS NOT NULL;
