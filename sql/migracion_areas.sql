-- ============================================================================
--  MIGRACION DE AREAS (zonas/ubicaciones del sistema viejo)  ->  tabla `areas`
--  Origen : registros 'Ubicación' de Datos_utiles_Bacal.xlsx (ambas hojas)
--  Nota   : `areas.nombre` es UNIQUE; nombres repetidos se desambiguan con
--           el edificio/piso. Se usa INSERT IGNORE para no chocar con las
--           areas que ya existen (ids 20-37).
--  Total  : 75 areas
-- ============================================================================

SET NAMES utf8mb4;
START TRANSACTION;

INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('ACCESO A CLIENTES', 'Ubicación migrada del sistema viejo (ID 1000148). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / ACCESO A CLIENTES', '#0EA5E9', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('ALMACEN', 'Ubicación migrada del sistema viejo (ID 1000132). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / ALMACEN', '#06B6D4', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('ALMACEN DE DISTRIBUCION', 'Ubicación migrada del sistema viejo (ID 1000073). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SEGUNDO PISO / ALMACEN DE DISTRIBUCION', '#14B8A6', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('ALMACEN DE SECOS', 'Ubicación migrada del sistema viejo (ID 1000150). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SEGUNDO PISO / ALMACEN DE SECOS', '#22C55E', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('ANDEN', 'Ubicación migrada del sistema viejo (ID 1000123). Ruta: CARNES BACAL / EDIFICIO BACAL-1 / ANDEN', '#84CC16', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('ANDEN (BACAL-2)', 'Ubicación migrada del sistema viejo (ID 1000152). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / ANDEN', '#EAB308', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('AREA DE PROCESOS', 'Ubicación migrada del sistema viejo (ID 1000146). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / AREA DE PROCESOS', '#F59E0B', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('AZOTEA', 'Ubicación migrada del sistema viejo (ID 1000052). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA', '#F97316', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CAJAS', 'Ubicación migrada del sistema viejo (ID 1000111). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / CAJAS', '#EF4444', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CARNES', 'Ubicación migrada del sistema viejo (ID 1000075). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / CARNES', '#EC4899', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CARNES BACAL', 'Ubicación migrada del sistema viejo (ID 1000107). Ruta: CARNES BACAL', '#A855F7', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('COMEDOR', 'Ubicación migrada del sistema viejo (ID 1000104). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SEGUNDO PISO / COMEDOR', '#8B5CF6', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('COMEDOR (BACAL-2 - TERCER PISO)', 'Ubicación migrada del sistema viejo (ID 1000106). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / TERCER PISO / COMEDOR', '#6366F1', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('COMIDA RAPIDA', 'Ubicación migrada del sistema viejo (ID 1000129). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / COMIDA RAPIDA', '#3B82F6', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CUARTO DE MAQUINAS #1', 'Ubicación migrada del sistema viejo (ID 1000092). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / CUARTO DE MAQUINAS #1', '#64748B', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CUARTO DE MAQUINAS #2', 'Ubicación migrada del sistema viejo (ID 1000093). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / CUARTO DE MAQUINAS #2', '#0EA5E9', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CUARTO DE MAQUINAS #4', 'Ubicación migrada del sistema viejo (ID 1000062). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / CUARTO DE MAQUINAS #4', '#06B6D4', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CUARTO DE MAQUINAS #5', 'Ubicación migrada del sistema viejo (ID 1000140). Ruta: CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / CUARTO DE MAQUINAS #5', '#14B8A6', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CUARTO FRIO #1 CONGELACIÓN', 'Ubicación migrada del sistema viejo (ID 1000056). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / CUARTO FRIO #1 CONGELACIÓN', '#22C55E', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CUARTO FRIO #2 CONGELACIÓN', 'Ubicación migrada del sistema viejo (ID 1000054). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / CUARTO FRIO #2 CONGELACIÓN', '#84CC16', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CUARTO FRIO #3 CONSERVACIÓN', 'Ubicación migrada del sistema viejo (ID 1000057). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / CUARTO FRIO #3 CONSERVACIÓN', '#EAB308', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CUARTO FRIO #4 CONGELACIÓN', 'Ubicación migrada del sistema viejo (ID 1000086). Ruta: CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / CUARTO FRIO #4 CONGELACIÓN', '#F59E0B', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CUARTO FRIO #5 CONGELACIÓN', 'Ubicación migrada del sistema viejo (ID 1000085). Ruta: CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / CUARTO FRIO #5 CONGELACIÓN', '#F97316', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CUARTO FRIO #6 CONSERVACIÓN', 'Ubicación migrada del sistema viejo (ID 1000091). Ruta: CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / CUARTO FRIO #6 CONSERVACIÓN', '#EF4444', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CUARTO FRIO VERDURAS', 'Ubicación migrada del sistema viejo (ID 1000059). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / CUARTO FRIO VERDURAS', '#EC4899', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CUARTO FRÍO CONGELACIÓN', 'Ubicación migrada del sistema viejo (ID 1000154). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / ANDEN / CUARTO FRÍO CONGELACIÓN', '#A855F7', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CUARTO FRÍO CONSERVACIÓN', 'Ubicación migrada del sistema viejo (ID 1000155). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / ANDEN / CUARTO FRÍO CONSERVACIÓN', '#8B5CF6', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CUARTO FRÍO PREPARACIÓN', 'Ubicación migrada del sistema viejo (ID 1000156). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / ANDEN / CUARTO FRÍO PREPARACIÓN', '#6366F1', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('DEPTO. MANTENIMIENTO', 'Ubicación migrada del sistema viejo (ID 1000116). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SEGUNDO PISO / DEPTO. MANTENIMIENTO', '#3B82F6', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('EDIFICIO BACAL-1', 'Ubicación migrada del sistema viejo (ID 1000060). Ruta: CARNES BACAL / EDIFICIO BACAL-1', '#64748B', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('EDIFICIO BACAL-2', 'Ubicación migrada del sistema viejo (ID 1000008). Ruta: CARNES BACAL / EDIFICIO BACAL-2', '#0EA5E9', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('EMBUTIDOS', 'Ubicación migrada del sistema viejo (ID 1000139). Ruta: CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / EMBUTIDOS', '#06B6D4', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('EMPAQUE DE ESPECIAS', 'Ubicación migrada del sistema viejo (ID 1000144). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / EMPAQUE DE ESPECIAS', '#14B8A6', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('ESTACIONAMIENTO (BACAL-1)', 'Ubicación migrada del sistema viejo (ID 1000141). Ruta: CARNES BACAL / EDIFICIO BACAL-1 / ESTACIONAMIENTO', '#22C55E', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('EXTERIOR', 'Ubicación migrada del sistema viejo (ID 1000103). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / EXTERIOR', '#84CC16', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('EXTERIOR (BACAL-1)', 'Ubicación migrada del sistema viejo (ID 1000151). Ruta: CARNES BACAL / EDIFICIO BACAL-1 / EXTERIOR', '#EAB308', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('FRUTAS Y VERDURAS', 'Ubicación migrada del sistema viejo (ID 1000070). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / FRUTAS Y VERDURAS', '#F59E0B', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('KARCAMO', 'Ubicación migrada del sistema viejo (ID 1000143). Ruta: CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / KARCAMO', '#F97316', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('LACTEOS', 'Ubicación migrada del sistema viejo (ID 1000069). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / LACTEOS', '#EF4444', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('MEZZANINE', 'Ubicación migrada del sistema viejo (ID 1000063). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / MEZZANINE', '#EC4899', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('MEZZANINE (BACAL-2 - AZOTEA)', 'Ubicación migrada del sistema viejo (ID 1000124). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA / MEZZANINE', '#A855F7', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('MOSTRADOR CARNES', 'Ubicación migrada del sistema viejo (ID 1000126). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / MOSTRADOR CARNES', '#8B5CF6', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('MOSTRADOR DE QUESOS', 'Ubicación migrada del sistema viejo (ID 1000147). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / MOSTRADOR DE QUESOS', '#6366F1', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('OFICINA ALMACÉN ABARROTES', 'Ubicación migrada del sistema viejo (ID 1000115). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SEGUNDO PISO / OFICINA ALMACÉN ABARROTES', '#3B82F6', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('OFICINA DIRECCIÓN', 'Ubicación migrada del sistema viejo (ID 1000121). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SEGUNDO PISO / OFICINA DIRECCIÓN', '#64748B', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('OFICINA GERENCIA', 'Ubicación migrada del sistema viejo (ID 1000071). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / OFICINA GERENCIA', '#0EA5E9', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('OFICINA SEGURIDAD E HIGIENE', 'Ubicación migrada del sistema viejo (ID 1000114). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SEGUNDO PISO / OFICINA SEGURIDAD E HIGIENE', '#06B6D4', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('OFICINA SISTEMAS', 'Ubicación migrada del sistema viejo (ID 1000122). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SEGUNDO PISO / OFICINA SISTEMAS', '#14B8A6', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('OFICINA VENTAS', 'Ubicación migrada del sistema viejo (ID 1000153). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / OFICINA VENTAS', '#22C55E', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('OFICINAS ADMINISTRATIVAS', 'Ubicación migrada del sistema viejo (ID 1000102). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SEGUNDO PISO / OFICINAS ADMINISTRATIVAS', '#84CC16', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('PISO DE VENTA', 'Ubicación migrada del sistema viejo (ID 1000094). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA', '#EAB308', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('PREPARACION DE SALSAS', 'Ubicación migrada del sistema viejo (ID 1000118). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PREPARACION DE SALSAS', '#F59E0B', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('PRIMER PISO', 'Ubicación migrada del sistema viejo (ID 1000047). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO', '#F97316', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('QUESOS', 'Ubicación migrada del sistema viejo (ID 1000078). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / QUESOS', '#EF4444', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('RED DE DRENAJE SANITARIO', 'Ubicación migrada del sistema viejo (ID 1000108). Ruta: CARNES BACAL / RED DE DRENAJE SANITARIO', '#EC4899', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('REFRIGERADOR BEBIDAS', 'Ubicación migrada del sistema viejo (ID 1000084). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / REFRIGERADOR BEBIDAS', '#A855F7', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('SECOS', 'Ubicación migrada del sistema viejo (ID 1000133). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / ALMACEN / SECOS', '#8B5CF6', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('SEGUNDO PISO', 'Ubicación migrada del sistema viejo (ID 1000072). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SEGUNDO PISO', '#6366F1', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('SOTANO', 'Ubicación migrada del sistema viejo (ID 1000053). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SOTANO', '#3B82F6', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('SOTANO (BACAL-1 - SOTANO)', 'Ubicación migrada del sistema viejo (ID 1000061). Ruta: CARNES BACAL / EDIFICIO BACAL-1 / SOTANO', '#64748B', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('SOTANO (BACAL-2 - SOTANO)', 'Ubicación migrada del sistema viejo (ID 1000130). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SOTANO', '#0EA5E9', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('TALLER MANTENIMIENTO', 'Ubicación migrada del sistema viejo (ID 1000119). Ruta: CARNES BACAL / EDIFICIO BACAL-1 / TALLER MANTENIMIENTO', '#06B6D4', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('TERCER PISO', 'Ubicación migrada del sistema viejo (ID 1000105). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / TERCER PISO', '#14B8A6', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('TRAMPA DE GRASAS', 'Ubicación migrada del sistema viejo (ID 1000082). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / TRAMPA DE GRASAS', '#22C55E', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('UNIDAD DE TRANSPORTE C-13', 'Ubicación migrada del sistema viejo (ID 1000149). Ruta: CARNES BACAL / UNIDAD DE TRANSPORTE C-13', '#84CC16', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('\\', 'Ubicación migrada del sistema viejo (ID 1000001). Ruta: -', '#EAB308', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('ANDEN (BACAL-2 - PRIMER PISO)', 'Ubicación migrada del sistema viejo (ID 1000083). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / ANDEN', '#F59E0B', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CAMARA DE CONGELACION', 'Ubicación migrada del sistema viejo (ID 1000088). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / ANDEN / CAMARA DE CONGELACION', '#F97316', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CAMARA DE CONSERVACION', 'Ubicación migrada del sistema viejo (ID 1000089). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / ANDEN / CAMARA DE CONSERVACION', '#EF4444', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('COCINA', 'Ubicación migrada del sistema viejo (ID 1000065). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / COCINA', '#EC4899', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CRIVA', 'Ubicación migrada del sistema viejo (ID 1000096). Ruta: CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / CRIVA', '#A855F7', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('CUARTO DE MAQUINAS #3', 'Ubicación migrada del sistema viejo (ID 1000090). Ruta: CARNES BACAL / EDIFICIO BACAL-1 / EXTERIOR / CUARTO DE MAQUINAS #3', '#8B5CF6', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('PREPARACION DE CARNES', 'Ubicación migrada del sistema viejo (ID 1000077). Ruta: CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / ANDEN / PREPARACION DE CARNES', '#6366F1', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('TALLER MANTENIMIENTO (BACAL-1)', 'Ubicación migrada del sistema viejo (ID 1000074). Ruta: CARNES BACAL / EDIFICIO BACAL-1 / EXTERIOR / TALLER MANTENIMIENTO', '#3B82F6', 1);
INSERT IGNORE INTO `areas` (`nombre`,`descripcion`,`color`,`activo`) VALUES
  ('TRAMPA DE GRASAS (BACAL-1 - SOTANO)', 'Ubicación migrada del sistema viejo (ID 1000080). Ruta: CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / TRAMPA DE GRASAS', '#64748B', 1);

COMMIT;
-- Fin migracion de areas.
