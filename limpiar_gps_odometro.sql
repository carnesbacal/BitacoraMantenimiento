-- ============================================================================
-- limpiar_gps_odometro.sql
-- Monsat/GPS pasa a ser SOLO referencia. Este script borra el efecto que el
-- GPS ya había dejado sobre el ODÓMETRO, para que el km_actual refleje
-- únicamente capturas manuales (papel / Xiga / captura a mano).
-- Correr una vez en phpMyAdmin. (Los datos de GPS en flotilla_km_gps NO se
-- borran; siguen disponibles como referencia en las vistas de GPS.)
-- ============================================================================

-- 1) Borrar las lecturas de odómetro que venían del GPS.
DELETE FROM `flotilla_odometro_historial` WHERE `origen` = 'gps';

-- 2) Reponer el km_actual de cada vehículo al MÁXIMO de sus lecturas MANUALES
--    del odómetro (sin bajar del km_inicial). Nunca usa GPS.
UPDATE `flotilla_vehiculos` v
SET v.`km_actual` = GREATEST(
    v.`km_inicial`,
    COALESCE((SELECT MAX(h.`km`) FROM `flotilla_odometro_historial` h WHERE h.`vehiculo_id` = v.`id`), 0)
);
