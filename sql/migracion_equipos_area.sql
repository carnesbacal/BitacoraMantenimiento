-- ============================================================================
--  ENLACE equipos.area_id  ->  catalogo `areas`
--  Empareja cada equipo con su area por la RUTA de Localización.
--  PRE-REQUISITO: ejecutar antes migracion_bacal.sql y migracion_areas.sql.
--  Coincidencias: 151 de 165 equipos (151 por ruta, 0 por segmento).
--  Sin area (se dejan en NULL): 14.
-- ============================================================================

SET NAMES utf8mb4;
START TRANSACTION;

UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AZOTEA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0023';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AZOTEA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0024';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AZOTEA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0025';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AZOTEA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0026';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AZOTEA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0027';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AZOTEA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0028';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AZOTEA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0029';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AZOTEA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0150';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AZOTEA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0085';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AZOTEA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0151';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'COMIDA RAPIDA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-100-0041';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'COMIDA RAPIDA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-100-0038';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'TALLER MANTENIMIENTO' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0078';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO DE MAQUINAS #2' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0052';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO DE MAQUINAS #2' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0146';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO DE MAQUINAS #2' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0048';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'TRAMPA DE GRASAS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0049';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'KARCAMO' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0072';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'KARCAMO' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-170-0101';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'SECOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0086';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'SECOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0087';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'SECOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0088';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'SECOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-110-006-0089';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'PISO DE VENTA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-110-0119';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'PISO DE VENTA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-110-0161';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'COMIDA RAPIDA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-100-0105';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EXTERIOR' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-100-0140';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ANDEN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-230-0230';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ANDEN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-230-0154';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'TALLER MANTENIMIENTO' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-160-0175';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ESTACIONAMIENTO (BACAL-1)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0071';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO DE MAQUINAS #1' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0122';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO DE MAQUINAS #1' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0123';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO DE MAQUINAS #1' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0124';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO DE MAQUINAS #1' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0118';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO DE MAQUINAS #1' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0125';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO DE MAQUINAS #1' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0126';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'SECOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0047';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MEZZANINE (BACAL-2 - AZOTEA)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0127';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CAJAS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-110--0144';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ACCESO A CLIENTES' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0090';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ANDEN (BACAL-2)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-170-0156';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ANDEN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-170-0157';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EXTERIOR (BACAL-1)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-170-0158';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EXTERIOR' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-100-0160';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'PISO DE VENTA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-170-0159';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EXTERIOR' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-170-0158-D1';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EMBUTIDOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0066';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO DE MAQUINAS #4' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0043';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO DE MAQUINAS #5' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0070';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EMBUTIDOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0093';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'SOTANO (BACAL-1 - SOTANO)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0064';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MOSTRADOR CARNES' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0032';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'COMIDA RAPIDA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-100-0035';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AREA DE PROCESOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0099';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO FRIO VERDURAS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0104';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO FRIO #5 CONGELACIÓN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0059';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO FRIO #5 CONGELACIÓN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0060';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO FRIO #2 CONGELACIÓN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0130';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO FRIO #1 CONGELACIÓN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0132';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO FRIO #3 CONSERVACIÓN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0134';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'REFRIGERADOR BEBIDAS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0014';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO FRIO #1 CONGELACIÓN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0133';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO FRIO #3 CONSERVACIÓN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0135';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'REFRIGERADOR BEBIDAS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0015';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO FRIO #2 CONGELACIÓN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0131';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AZOTEA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-100-0103';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EXTERIOR' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-100-0152';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EXTERIOR (BACAL-1)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-230-0155';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'COMIDA RAPIDA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-100-0037';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AZOTEA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0053';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'SOTANO (BACAL-1 - SOTANO)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0065';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EMBUTIDOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0100';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'COMIDA RAPIDA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-100-0109';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EMBUTIDOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0069';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EMPAQUE DE ESPECIAS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-200-0102';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ANDEN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC-120-0075';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ANDEN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0074';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EMBUTIDOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0063';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EMBUTIDOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0092';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EMBUTIDOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0168';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'COMIDA RAPIDA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-100-0042';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'COMEDOR (BACAL-2 - TERCER PISO)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0165';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'COMEDOR' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0166';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'COMEDOR' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0167';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EMBUTIDOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0062';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MOSTRADOR CARNES' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0030';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EMPAQUE DE ESPECIAS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-200-0079';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'TALLER MANTENIMIENTO' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0081';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'TALLER MANTENIMIENTO' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-160-0106';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'UNIDAD DE TRANSPORTE C-13' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-160-0110';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EXTERIOR' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-160-0111';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'SOTANO (BACAL-1 - SOTANO)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-160-0112';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'SOTANO (BACAL-2 - SOTANO)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-160-0115';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'SOTANO (BACAL-2 - SOTANO)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-160-0116';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ALMACEN DE SECOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-160-0117';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'COMIDA RAPIDA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-100-0036';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'SOTANO (BACAL-2 - SOTANO)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0044';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ACCESO A CLIENTES' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0040';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ACCESO A CLIENTES' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0091';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO DE MAQUINAS #1' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0121';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'PISO DE VENTA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0016';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'PISO DE VENTA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0018';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'LACTEOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0019';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MOSTRADOR CARNES' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0001';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MOSTRADOR CARNES' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0002';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AREA DE PROCESOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0003';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'TALLER MANTENIMIENTO' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0004';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MOSTRADOR CARNES' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0005';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MOSTRADOR CARNES' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0006';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AREA DE PROCESOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0007';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'TALLER MANTENIMIENTO' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0008';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AREA DE PROCESOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0009';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'TALLER MANTENIMIENTO' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0010';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MOSTRADOR DE QUESOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0011';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'COMIDA RAPIDA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-100-0039';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'COMEDOR' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0162';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'COMEDOR (BACAL-2 - TERCER PISO)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0163';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EXTERIOR' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-100-0164';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EMPAQUE DE ESPECIAS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-200-0107';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AREA DE PROCESOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0120';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MOSTRADOR CARNES' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130--0108';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'SOTANO (BACAL-1 - SOTANO)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0082';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'TALLER MANTENIMIENTO' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-130-0031';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CUARTO DE MAQUINAS #2' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0051';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'AZOTEA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0050';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ESTACIONAMIENTO (BACAL-1)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-120-0073';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'PISO DE VENTA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-110-0034';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'PISO DE VENTA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-110-0033';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CAJAS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-110-0145';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MEZZANINE (BACAL-2 - AZOTEA)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0129';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MEZZANINE (BACAL-2 - AZOTEA)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0076';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MEZZANINE (BACAL-2 - AZOTEA)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-028';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ANDEN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0056';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ANDEN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0054';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ANDEN' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0055';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'FRUTAS Y VERDURAS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0020';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'FRUTAS Y VERDURAS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0021';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'FRUTAS Y VERDURAS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0022';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'CAJAS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-110-0143';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'PISO DE VENTA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-110-0077';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'QUESOS' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0017';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MOSTRADOR CARNES' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0094';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MOSTRADOR CARNES' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0095';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MOSTRADOR CARNES' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0096';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MOSTRADOR CARNES' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0097';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'MOSTRADOR CARNES' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC60-140-0098';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'ANDEN (BACAL-2 - PRIMER PISO)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC-60-0046';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'EXTERIOR (BACAL-1)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC-60-0141';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'TALLER MANTENIMIENTO (BACAL-1)' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC-60-0009';
UPDATE `equipos` SET `area_id` = (SELECT `id` FROM `areas` WHERE `nombre` = 'COCINA' LIMIT 1)
  WHERE `codigo_inventario` = 'BAC-60-0040';

COMMIT;
-- Fin del enlace equipos-areas.
