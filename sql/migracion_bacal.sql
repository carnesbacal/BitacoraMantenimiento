-- ============================================================================
--  MIGRACION DE CATALOGOS  ->  Sistema de Mantenimiento Bacal (CMMS nuevo)
--  Origen : Datos_utiles_Bacal.xlsx (export del sistema viejo, 31-may-2026)
--  Destino: BD 'mantenimientobacal' (tablas proveedores, proveedor_contactos,
--           equipos, herramientas, refacciones)
--  Generado: 2026-06-04
--
--  NOTAS:
--   * Equipos: se fusionaron las hojas 'Equipos' y 'Equipos (codigos ACR)'
--     deduplicando por ID viejo; nomenclatura codigo_inventario = BAC...
--   * Registros 'Eliminado' se cargan como inactivos (activo = 0).
--   * Todo el inventario se asigna a la sucursal Bacal (sucursal_id = 1).
--   * Los contactos se enlazan al proveedor por nombre (subconsulta), por lo
--     que no dependen de IDs fijos.
--   * Ejecutar sobre la base ya creada. Revisar el COMMIT al final.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;
START TRANSACTION;

-- ---------------------------------------------------------------------------
-- PROVEEDORES (87)
-- ---------------------------------------------------------------------------
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('ACRYLART', 'ACRILICOS', 'Calle Alcalá #4823, Col. Alcalá, TIJUANA, BAJA CALIFORNIA NTE, 22106, MÉXICO', '664 625 1116', 'https://acrylart.com.mx/', 'Clave sistema viejo: 000000032.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('AGROINDUSTRAS ALLENDE S. DE R.I. M.I.', 'MAQUINARIA-SERVICIO', 'ALLENDE SUR NO. 2599 COL. NUEVO REPUEBLO, NUEVO LEON, NUEVO LEON, 67350, MÉXICO', '8262683392', 'http://allende.mexicored.com.mx/agroindustrias-allende--s--de-r-l--m-i-.html', 'Clave sistema viejo: 000000016.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('ALBAÑIL ELIAS', 'CONSTRUCCIONES', 'TIJUANA, BAJA CALIFORNIA NTE, MÉXICO', '664 160 3513', NULL, 'Clave sistema viejo: 000000029.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('ALBAÑIL JORGE JUAREZ', 'CONSTRUCCIONES', 'TIJUANA, BAJA CALIFORNIA NTE, MÉXICO', '664 853 1585', NULL, 'Clave sistema viejo: 000000030.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('ALTERNATIVA INDUSTRIAL', NULL, 'C.Ocotlan #15101 Int.4 Fracc, Jalisco, TIJUANA, BAJA CALIFORNIA NTE, 22116, MÉXICO', '6646478835', 'http://www.alternativaindustrial.com/', 'Clave sistema viejo: 000000069.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('AMARO SAUCEDO SANDRA', 'PALLET JACK', 'TIJUANA, BAJA CALIFORNIA NTE, MÉXICO', NULL, NULL, 'Clave sistema viejo: 000000055.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('ANEXA', 'RUEDAS Y RODAJAS', 'Ermita Norte No.27-B Fracc. Santa Cruz, Tijuana, BAJA CALIFORNIA NTE, 22105, MÉXICO', '664 622 4215', 'www.ruedasanexa.com', 'Clave sistema viejo: 000000049.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('APL', 'SERVICIO', 'CALLE KEIROS #23425 B26-D FRAA. VILLA DEL PRADO, TIJUANA, BAJA CALIFORNIA NTE, 22205, MÉXICO', '664 196 2966', NULL, 'Clave sistema viejo: 000000009.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('ARL DE BAJA CALIFORNIA', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000083.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('BACESA', 'BALEROS Y BANDAS', 'Mision de San Luis No. 20-D Fracc. Kino, TIJUANA, BAJA CALIFORNIA NTE, 22260, MÉXICO', '664 625 0714, 664 407 8614', 'https://www.secciondenegocios.com.mx/mexico/baja-california-norte/bacesa-de-tijuana/', 'Clave sistema viejo: 000000033.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('BAJA PAINT', 'PINTURAS', 'BLVD. DIAZ ORDAZ 1111 3, COL. LA MESA, TIJUANA, BAJA CALIFORNIA NTE, 22105, MÉXICO', '622 7000', NULL, 'Clave sistema viejo: 000000021.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('BAJA REFRIGERACION', 'REFRIGERACION', 'ALFREDO BONFIL No. 10924 EJIDO LAZARO CARDENAS, TIJUANA, BAJA CALIFORNIA NTE, 22654, MÉXICO', '664 693 3960', NULL, 'Clave sistema viejo: 0024.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('BAKER DISTRIBUTING COMPANY', 'REFRIGERACION', '131 W. 33rd. Street unit 17, NATIONAL CITY, CALIFORNIA, 19150, MÉXICO', '619 245 6003', NULL, 'Clave sistema viejo: 000000057.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('BALEROS LAS FUENTES', 'BALEROS Y BANDAS', 'PRIV. FUENTES DEL PSICOLOGO #22,COL. HACIENDA DE LAS FUENTES, TIJUANA, BAJA CALIFORNIA NTE, MÉXICO', '664 166 9997', NULL, 'Clave sistema viejo: 000000054.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('BINASA', 'BALEROS Y BANDAS', 'Abasolo No. 15 Int 2, Col. Zermeño, TIJUANA, BAJA CALIFORNIA NTE, 22120, MÉXICO', '664 906 1192', 'WWW.GRUPO-BINASA.COM.MX', 'Clave sistema viejo: 000000026.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('BIO REGENERADORA DE BAJA CALIFORNIA', NULL, 'FRAY MAYORGA, 17026/2, GARITA DE OTAY, TIJUANA, BAJA CALIFORNIA NTE, 22430, MÉXICO', '664 647 5727', 'abrahamflores@bioregeneradorabc.com', 'Clave sistema viejo: 000000017.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('BOMBAS Y MATERIALES', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000090.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('CARPINTERIA DEL TORO', 'CARPINTERIA', 'Cerro Capirote 20304, Amp. Guaycura, Tijuana, BAJA CALIFORNIA NTE, 22535, MÉXICO', '664 188 0812', 'https://carpinterias.com.mx/municipio.php?estado=Baja%20California&cat=Carpinterias&municipio=Tijuana', 'Clave sistema viejo: 000000050.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('CBA', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000075.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('COCINAS INSTITUCIONALES', 'EQUIPO', 'BLVD FEDERICO BENITEZ 13112 COL. 20 DE NOVIEMBRE, TIJUANA, BAJA CALIFORNIA NTE, 22100, MÉXICO', '664 622 2123', 'WWW.COCINASI.COM', 'Clave sistema viejo: 000000020.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('CONSTRUCCIONES ECONOMICAS DE B.C.', 'CONSTRUCCIONES', 'Privada san marcos 8732 2, colinas de california, TIJUANA, BAJA CALIFORNIA NTE, 22000, MÉXICO', '664 636 6226', NULL, 'Clave sistema viejo: 000000018.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('CONSTRUCCIONES LUGO', 'CONSTRUCCIONES', 'MIGUEL CERVANTES #16, REFORMA, TIJUANA, BAJA CALIFORNIA NTE, 22183, MÉXICO', NULL, NULL, 'Clave sistema viejo: 000000058.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('CONSTRUCTORA DE CALDERAS Y MAQUINARIA CELIS', 'MAQUINARIA', 'Canal Bolga Valtico Mz. 35 Lt. 5, Col. Insurgentes, Ciudad de México, CDMX, 09750, MÉXICO', '55 757 20359', NULL, 'Clave sistema viejo: 000000034.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('CORTINAS DE ACERO FRONTERIZA', 'SERVICIO', 'Blvrd Federico Benítez López 8000, Gas y Anexas, TIJUANA, BAJA CALIFORNIA NTE, 22620, MÉXICO', '664 626 2126', 'https://www.cafsafronteriza.com/', 'Clave sistema viejo: 000000028.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('DISTRIBUIDORA OHANA INOXIDABLES', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000093.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('DUCTIMEX', 'SERVICIO', 'Blvrd Federico Benítez López 16204, Santa Elena, TIJUANA, BAJA CALIFORNIA NTE, 22114, MÉXICO', '664 626 2908', NULL, 'Clave sistema viejo: 000000037.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('DUELAS BCN', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000078.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('ECD (ESPECIALISTAS EN CORRIENTE DIRECTA)', 'ELECTRICISTAS', 'Blvd. Agua Caliente #705-15 Col. Revolucion, Tijuana, BAJA CALIFORNIA NTE, 22015, MÉXICO', '664 281 1596', 'https://www.ecd.com.mx/index.php/en/', 'Clave sistema viejo: 000000014.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('EGA INDUSTRIAL TIJUANA S.A. DE C.V.', NULL, 'TIJUANA, BAJA CALIFORNIA NTE, MÉXICO', '664 103 0928', 'https://egaindustrial.com/', 'Clave sistema viejo: 000000065.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('EL JEFE ACCESORIOS Y POLARIZADO', 'ACCESORIOS Y POLARIZADO', 'Calle lilias #33 Fracc. Ramirez, TIJUANA, BAJA CALIFORNIA NTE, 22115, MÉXICO', '664 383 5882', NULL, 'Clave sistema viejo: 000000060.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('EL MANGUERAS', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000092.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('EMBOBINADOS INDUSTRIALES MASEI', NULL, 'ANGOSTURA, 15326, CAMPESTRE MURUA, TIJUANA, BAJA CALIFORNIA NTE, 22455, MÉXICO', '664 686 8778', NULL, 'Clave sistema viejo: 000000068.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('EQUIPOS MENDOZA', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000087.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('EXTINGUIDORES GENESIS', 'SEGURIDAD', 'Alberto Cruz Chavez mz67 lt104, Col. Ejido Lazaro Cardenas, Tijuana, BAJA CALIFORNIA NTE, 22664, MÉXICO', '664 974 7205', 'https://www.facebook.com/Extinguidores.Genesis', 'Clave sistema viejo: 000000008.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('FUMIGADORA TIJUANA', 'FUMIGACION Y LIMPIEZA', 'CALLE 17 # 1204 COLONIA LIBERTAD, TIJUANA, BAJA CALIFORNIA NTE, 22400, MÉXICO', '664 687 2288', 'https://fumigadoratijuana.miadn.mx/', 'Clave sistema viejo: 000000012.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('GRUPO D\'LUX', 'FUMIGACION Y LIMPIEZA', 'BLVD. FUNDADORES 2480, CO. JUARES, PLAZA PALMILLAS, TIJUANA, BAJA CALIFORNIA NTE, MÉXICO', '646 247 0872', 'www.grupodlux.com', 'Clave sistema viejo: 000000062.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('HEFESTO SERVICIOS INDUSTRIALES', NULL, 'TIJUANA, BAJA CALIFORNIA, MÉXICO', NULL, NULL, 'Clave sistema viejo: 000000061.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('HIDRAULICOS BAJA PALLET JACK', NULL, 'AGUSTIN PEREZ RIVERO #2510-D, XICOTENCATL LEYVA ALEMAN, TIJUANA, BAJA CALIFORNIA NTE, MÉXICO', '664 253 2695', NULL, 'Clave sistema viejo: 000000015.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('HOBART DAYTON MEXICANA', 'EQUIPO', 'TRAPANI # 4708-A FRACC PALERMO RESIDENCIAL VIVEROS DE LA COLINA 238 COLONIA VIVEROS DE LA LOMA CD., CULIACAN, SINALOA, 80104, MÉXICO', '01 800 110 4220', 'WWW.HOBART.COM.MX', 'Clave sistema viejo: 000000010.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('HOTSY', NULL, '359 TROUSDALE DRIVE, SUITE B, CHULA VISTA, CALIFORNIA, 91910, ESTADOS UNIDOS', '619 691 8100', 'WWW.HOTSYSD.COM', 'Clave sistema viejo: 000000066.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('HYDRAUMEX', NULL, 'CALLE LAZARO CARDENAS No.13011, COLONIA LOMAS TAURINAS, Tijuana, BAJA CALIFORNIA NTE, 22410, MÉXICO', '664 403 5750', NULL, 'Clave sistema viejo: 000000039.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('IMPORTADORA RAZA', 'SERVICIO-EQUIPO', 'CALLE JUAN GARCIA436-B COL. LIBERTAD, TIJUANA, BAJA CALIFORNIA NTE, 22400, MÉXICO', '664 683 1246', 'S/N', 'Clave sistema viejo: 000000006.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('ING. VALENTIN BARCENAS PEREZ (VERIFICADOR GAS LP)', 'GAS LP', 'FRACC. VILLA COLONIAL, TIJUANA, BAJA CALIFORNIA NTE, MÉXICO', '664 681 4156', NULL, 'Clave sistema viejo: 000000063.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('INOXIDABLES TIMEX', 'SERVICIO-EQUIPO', 'CALLE LA CUESTA #2 COLONIA MIRADOR, TIJUANA, BAJA CALIFORNIA NTE, 22204, MÉXICO', '664 360 2873', 'WWW.INOXIDABLESTIMEX.COM', 'Clave sistema viejo: 000000004.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('ISA', NULL, 'FRAC. HACIENDA CASA GRANDE, TIJUABA, BAJA CALIFORNIA, 22244, MÉXICO', NULL, NULL, 'Clave sistema viejo: 000000071.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('ITW FOOD EQUIPMENT GROUP', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000085.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('JADE INDUSTRIAL', NULL, 'PRIV. PIEDRAS NEGRAS 7731-77 EL DORADO RESIDENCIAL, TIJUANA, BAJA CALIFORNIA, 22235, MÉXICO', '664 274 4192', NULL, 'Clave sistema viejo: 000000073.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('JM INDUSTRIAL', 'REFACCIONES', 'CALLE MOCHICAHUI SUR 19039 CAMPESTRE MURUA, TIJUANA, BAJA CALIFORNIA NTE, 22455, MÉXICO', '664 624 6288', 'WWW.JMINDUSTRIAL.COM.MX', 'Clave sistema viejo: 000000005.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('L.F.P. HYDRAULIC SYSTEMS', NULL, 'Misión de San Luis No. 6906 Fracc. Padre Kino, TIJUANA, BAJA CALIFORNIA NTE, 22223, MÉXICO', '664 978 2593', 'ventas@lfphydraulics com', 'Clave sistema viejo: 000000041.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('LA PATRULLA DEL DRENAJE', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000091.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('LIMPIEZA CALIFORNIA', NULL, 'Calle Ing. Juan Ojeda Robles 14761, Guadalupe Victoria, TIJUANA, BAJA CALIFORNIA NTE, 22110, MÉXICO', '664 682 7782', NULL, 'Clave sistema viejo: 000000064.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('LLANTAS Y SERVICIOS "FLORES"', NULL, 'Calle Gral. Rodolfo Sanchez Taboada 8060, Col. Cañon Salado, TIJUANA, BAJA CALIFORNIA NTE, 22116, MÉXICO', '664 626 1875', NULL, 'Clave sistema viejo: 000000040.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('LYM (LIMPIEZA Y MANTENIMIENTO INDUSTRIAL)', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000077.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('MACIAS INSTALACIONES ELECTRICAS', NULL, 'AVE. DE LAS FERIAS No.440-A COL. HIPODROMO, TIJUANA, BAJA CALIFORNIA NTE, 22025, MÉXICO', '664 608 0305', NULL, 'Clave sistema viejo: 000000035.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('MANGUERAS Y CONEXIONES DE TIJUANA (GRUPO HERGOSA)', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000082.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('MANTENIMIENTO INDUSTRIAL Y MAQUINADOS VALENCIA', 'MANTENIMIENTO Y MECANICA INDUSTRIAL', 'Calle Andromeda 43, Sanchez Taboada Produtsa, TIJUANA, BAJA CALIFORNIA NTE, 22190, MÉXICO', '664 709 9820', NULL, 'Clave sistema viejo: 000000059.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('MATERIALES AZPE', NULL, 'REAL DE SAN FRANCISCO 23478 18B, COL REAL DE SAN FRANCISCO, TIJUANA, BAJA CALIFORNIA NTE, 22236, MÉXICO', NULL, NULL, 'Clave sistema viejo: 000000070.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('MIKE MERCADO', 'REFACCIONES', NULL, NULL, NULL, 'Clave sistema viejo: 000000080.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('MOBILIARIO Y EQUIPO DE BAJA CALIFORNIA (RAUL ESTRADA GONZALEZ)', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000089.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('MOBILIARIOS G.G.', NULL, 'Calle Duraznos 70, Las Huertas 3era. Secc., TIJUANA, BAJA CALIFORNIA NTE, 22115, MÉXICO', '664 684 8297', 'http://www.mobiliariosgg.com/', 'Clave sistema viejo: 000000023.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('MOTORES Y CONTROLES ELECTRICOS', NULL, 'BLVD. PACIFICO NO. 9515, PARQUE INDUSTRIAL PACIFICO, TIJUANA, BAJA CALIFORNIA NTE, 22643, MÉXICO', '664 626 0949', 'www.mycesrlcv.com', 'Clave sistema viejo: 000000019.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('OFIMUEBLES', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000076.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('OPERE', NULL, 'Calle José Clemente Orozco #1506 Int. 105 Zona Urbana Río, TIJUANA, BAJA CALIFORNIA NTE, 22010, MÉXICO', '664 681 8559', NULL, 'Clave sistema viejo: 000000024.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('PLOMERIA VILLAREAL', 'PLOMERIA', 'PROLONGACION MIGUEL CERVANTES 16, REFORMA, TIJUANA, BAJA CALIFORNIA NTE, 22183, MÉXICO', '664 163 8631', NULL, 'Clave sistema viejo: 000000053.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('PLOMERIA Y ELECTRICIDAD OLAGUE S', NULL, 'Union de Comerciantes 400 L-51. La Mesa Sur, Tijuana, BAJA CALIFORNIA NTE, 22105, MÉXICO', '664 216 1846', NULL, 'Clave sistema viejo: 000000022.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('POLARIZADOS EL JEFE', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000079.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('PROYECTOS DE INGENIERIA Y CONSTRUCCIONES', NULL, 'C. Huazarichi #9303, Col. Azteca, TIjuana, BAJA CALIFORNIA NTE, MÉXICO', NULL, NULL, 'Clave sistema viejo: 000000067.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('PROYECTOS DE SEGURIDAD (LEONEL FIERRO)', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000088.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('PUERTAS UNIVERSAL (EVA MARTINEZ RAMON)', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000084.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('REFRIGERACION Y COCINAS', 'REFRIGERACION', 'PRIVADA ALBORADA #198 12-17, NATURA VISTAS DEL SOL, TIJUANA, BAJA CALIFORNIA NTE, MÉXICO', '664 559 2429', NULL, 'Clave sistema viejo: 000000056.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('SERPROY', 'SERVICIOS Y PROYECTOS', 'CALLE DE LA REVOLUCION 1254 COL LAS TORRES, TIJUANA, BAJA CALIFORNIA NTE, MÉXICO', '664 683 1246', 'SN', 'Clave sistema viejo: 000000007.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('SERVICIOS Y SUMINISTROS INDUSTRIALES.', NULL, 'Águila Pescadora 19309 - 3 El Aguila., TIJUANA, BAJA CALIFORNIA NTE, 22215, MÉXICO', '664 903 9454', NULL, 'Clave sistema viejo: 000000027.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('SINOX', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000086.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('TALLER DE HERRERIA', 'HERRERIA', 'TIJUANA, BAJA CALIFORNIA NTE, MÉXICO', '664 281 3385', NULL, 'Clave sistema viejo: 000000047.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('TALLER DE TORNO', 'TORNO', 'TIJUANA, BAJA CALIFORNIA, 22660', NULL, NULL, 'Clave sistema viejo: 000000074.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('TALLER HERMEX', 'EQUIPO', 'TIJUANA, BAJA CALIFORNIA NTE, MÉXICO', '664 188 6197', NULL, 'Clave sistema viejo: 000000046.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('TALLER MARIN (JAVIER MARIN RAMIREZ)', 'SOLDADURA', NULL, NULL, NULL, 'Clave sistema viejo: 000000081.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('TRELIZA', NULL, 'FRANCISCO FERNANDEZ DEL CASTILLO 2758 COL. VILLA DE CORTES, BENITO JUAREZ, CDMX, 03530, MÉXICO', '55 5696 4340', 'WWW.TRELIZA.COM', 'Clave sistema viejo: 000000013.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('TRUCK LIFT', 'SERVICIO', 'CALLE LAS PALMAS # 1C FRACC TONA, TIJUANA, BAJA CALIFORNIA NTE, 22123, MÉXICO', '664 645 8607', 'WWW.TRUCKLIFTTJ.COM.MX', 'Clave sistema viejo: 000000011.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('TRUMA INDUSTRIAL', 'MAQUINARIA', 'Calle derecho de via lote 04 mza 953 Col. Rancho del carmen Calle derecho de via lote 04 mza 953 Col. Terrazas del valle, TIJUANA, BAJA CALIFORNIA NTE, 22245, MÉXICO', '664 387 4971', 'WWW.TRUMAINDUSTRIAL.COM', 'Clave sistema viejo: 00001.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('TUVANOSA', NULL, 'Carretera libre Tijuana-Tecate 27427, Col. Maclovio Rojas, Tijuana, BAJA CALIFORNIA NTE, 22254, MÉXICO', '664 689 8150', 'www.tuvanosa.com', 'Clave sistema viejo: 000000044.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('ULINE', NULL, 'TIJUANA, BAJA CALIFORNIA NTE, MÉXICO', NULL, NULL, 'Clave sistema viejo: 000000043.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('USAMEX', NULL, 'CEREZO 1294-A JARDIN DORADO, TIJUANA, BAJA CALIFORNIA, 22200, MÉXICO', NULL, NULL, 'Clave sistema viejo: 000000072.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('VICTOR ALFONSO PERALES LEYVA', NULL, NULL, NULL, NULL, 'Clave sistema viejo: 000000094.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('VIDRIERIA DE GUERRERO', NULL, 'Av. 20 de Noviembre 305-332. 20 de Noviembre, TIJUANA, BAJA CALIFORNIA NTE, 22100, MÉXICO', '664 681 3811', NULL, 'Clave sistema viejo: 000000038.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('VIDRIERIA GAFER', 'VIDRIERIA', 'Blvrd Bernardo O\'Higgins 810, Jalisco, TIJUANA, BAJA CALIFORNIA NTE, 22116, MÉXICO', '664 626 2326', NULL, 'Clave sistema viejo: 000000052.', 1, NULL);
INSERT INTO `proveedores` (`nombre`,`servicio`,`direccion`,`telefono`,`sitio_web`,`notas`,`activo`,`creado_por_id`) VALUES
  ('Z GAS', NULL, 'Blvd. Lázaro Cárdenas s/n, Guadalajara, TIJUANA, BAJA CALIFORNIA NTE, 22105, MÉXICO', '664 608 7000', 'http://www.grupozeta.com/', 'Clave sistema viejo: 000000031.', 1, NULL);

-- ---------------------------------------------------------------------------
-- PROVEEDOR_CONTACTOS (73) -- enlazados por nombre de proveedor
-- ---------------------------------------------------------------------------
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'JORGE LEDEZMA', NULL, '664 853 1585', NULL, NULL, 1, 0 FROM `proveedores` WHERE `nombre` = 'ACRYLART' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'NORMA TELLEZ', 'CONTADORA', '664 559 2472', 'NORMISSTRONCOSO@GMAIL.COM', 'Depto: CONTABILIDAD', 1, 0 FROM `proveedores` WHERE `nombre` = 'ALBAÑIL ELIAS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'RENE', 'GERENTE', '664 368 7062', 'RENE@ALTERNATIVAINDUSTRIAL.MX', 'Depto: GERENCIA', 1, 0 FROM `proveedores` WHERE `nombre` = 'ALTERNATIVA INDUSTRIAL' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ALFONSO PERALES', 'GTE GRAL', '664 196 2966', 'ALFONSO.PERA93@GMAIL.COM', 'Depto: SERVICIO', 1, 0 FROM `proveedores` WHERE `nombre` = 'APL' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ESMERALDA MEDINA', 'ENCARGADO DEL DEPARTAMENTO', '622 700', 'CREDITOYCOBRANZA@BAJAPAINT.COM.MX', 'Depto: CREDITO Y COBRANZA', 1, 0 FROM `proveedores` WHERE `nombre` = 'BAJA PAINT' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ING. JULIO GARCIA SEGURA', 'GERENTE', '664 693 3960', 'REFBAJA@MNS.COM', 'Depto: DIRECCION', 1, 0 FROM `proveedores` WHERE `nombre` = 'BAJA REFRIGERACION' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ROBERTO', 'TECNICO', '664 111 3369', NULL, 'Depto: SERVICIO', 0, 1 FROM `proveedores` WHERE `nombre` = 'BAJA REFRIGERACION' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'JOSE', 'TECNICO', '664 801 1044', NULL, 'Depto: SERVICIO', 0, 2 FROM `proveedores` WHERE `nombre` = 'BAJA REFRIGERACION' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'JOSE MONTELONGO', 'REFRIGERATION SALES SPECIALIST', '619 245 6003, 619 474 4239', 'JMONTELONGO@BAKERDIST.COM', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'BAKER DISTRIBUTING COMPANY' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'PEDRO', 'VENDEDOR', '664 808 5041', NULL, 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'BALEROS LAS FUENTES' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'PEDRO', 'VENDEDOR', '664 166 3337', NULL, 'Depto: VENTAS', 0, 1 FROM `proveedores` WHERE `nombre` = 'BALEROS LAS FUENTES' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'JANETH MIRAMONTES', 'AGENTA DE VENTAS', '686 280 1754', 'JANETH@BINASA.MX', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'BINASA' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'KEVIN GAYTAN', 'ASESOR DE VENTAS', '664 622 2123', 'VENTAS2@COCINASI.COM', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'COCINAS INSTITUCIONALES' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'AARÓN CASTAÑEDA', 'ASESOR DE VENTAS', '664 622 2123', 'AARON@COCINASI.COM', 'Depto: VENTAS', 0, 1 FROM `proveedores` WHERE `nombre` = 'COCINAS INSTITUCIONALES' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ING. LUIS ALFONSO CELIS ORTEGA', 'INGENIERO', '55 142 27908', 'CALDERASYGENERADORESDEVAPOR@HOTMAIL.COM', 'Depto: SERVICIO', 1, 0 FROM `proveedores` WHERE `nombre` = 'CONSTRUCTORA DE CALDERAS Y MAQUINARIA CELIS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'CIPRIANO', NULL, '664 106 0868', NULL, NULL, 1, 0 FROM `proveedores` WHERE `nombre` = 'CORTINAS DE ACERO FRONTERIZA' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'OSCAR ZAPATA', 'GERENTE', '664 170 7777', 'OSCARZAPATA_29@HOTMAIL.COM', 'Depto: GERENCIA', 1, 0 FROM `proveedores` WHERE `nombre` = 'DUCTIMEX' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'SOCORRO LORENZANA CARDENAS', 'ADMINISTRACION', '664 902 0045', 'ADMINISTRACION@ECD.COM.MX', 'Depto: ADMINISTRACION', 1, 0 FROM `proveedores` WHERE `nombre` = 'ECD (ESPECIALISTAS EN CORRIENTE DIRECTA)' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'JOSE ANGEL HERNANDEZ', 'GERENTE', '664 357 7678', 'VENTAS.TIJUANA@EGAINDUSTRIAL.COM', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'EGA INDUSTRIAL TIJUANA S.A. DE C.V.' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'RAUL MEDINA', NULL, '663 167 0961', 'MAT_SERV_IND@HOTMAIL.COM', NULL, 1, 0 FROM `proveedores` WHERE `nombre` = 'EMBOBINADOS INDUSTRIALES MASEI' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ARISTEO SALGADO MORALES', 'GERENTE GENERAL', '664 160 1788', 'ARISTEO.SALGADO@HOTMAIL.COM', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'EXTINGUIDORES GENESIS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'EDGAR GARCIA MADERO', 'TECNICO', '664 165 1983', 'FUMIGADORATIJUANA@HOTMAIL.COM', 'Depto: SERVICIO', 1, 0 FROM `proveedores` WHERE `nombre` = 'FUMIGADORA TIJUANA' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'MILTON RUIZ', 'TECNICO', '664 687 2288', 'FUMIGADORATIJUANA@HOTMAIL.COM', 'Depto: SERVICIO', 0, 1 FROM `proveedores` WHERE `nombre` = 'FUMIGADORA TIJUANA' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'MIGUEL BERNAL', 'TECNICO', '664 687 2288', 'FUMIGADORATIJUANA@HOTMAIL.COM', 'Depto: SERVICIO', 0, 2 FROM `proveedores` WHERE `nombre` = 'FUMIGADORA TIJUANA' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ADRIAN CLEMENTE BANDA CANCINO', 'AGENTE DE VENTAS', '646 247 0872', 'SERVICIOS@CLEANDLUX.COM', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'GRUPO D\'LUX' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ROMAN BOISSON', 'GERENTE', '664 188 6197', NULL, NULL, 1, 0 FROM `proveedores` WHERE `nombre` = 'HEFESTO SERVICIOS INDUSTRIALES' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'JUAN CARLOS SOLANO SMITH', 'VENDEDOR', '664 253 2695', 'HIDBAJASOLANO@HOTMAIL.COM', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'HIDRAULICOS BAJA PALLET JACK' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'SANDRA AMADO SAUCEDO', 'GERENTE', NULL, 'VENTAS@HIDRAULICOSBAJA.COM', 'Depto: GERENCIA', 0, 1 FROM `proveedores` WHERE `nombre` = 'HIDRAULICOS BAJA PALLET JACK' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'GABRIELA RUIZ', 'GTE VENTAS', '667 161 5840', 'GABRIELA.RUIZ@ITWFEG.COM', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'HOBART DAYTON MEXICANA' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'JORGE LEDEZMA', 'VENDEDOR', '619 691 8100', 'JORGE@HOTSYSD.COM', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'HOTSY' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'MONICA DENISSE ANGEL GUTIERREZ', NULL, '664 295 1055', 'FACTURACION@HYDRAUMEX.COM', 'Depto: ADMINISTRACION', 1, 0 FROM `proveedores` WHERE `nombre` = 'HYDRAUMEX' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'AMERICA', 'GERENTE GENERAL', '001 619 721 5906', 'AMERICA@EMCBAJA.COM', 'Depto: GERENCIA', 1, 0 FROM `proveedores` WHERE `nombre` = 'IMPORTADORA RAZA' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ROSINA RAMIREZ', 'VEDEDORA', '664 396 5220', 'VENTAS.EMC@EMCBAJA.COM', 'Depto: VENTAS', 0, 1 FROM `proveedores` WHERE `nombre` = 'IMPORTADORA RAZA' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'LOURDES MEDRANO', 'CONTADOR', '664 404 7647', 'CONTABILIDAD@EMCBAJA.COM', 'Depto: CONTANILIDAD', 0, 2 FROM `proveedores` WHERE `nombre` = 'IMPORTADORA RAZA' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'EDUARDO MONDRAGON', 'GTE. GENERAL', '664 372 2867', 'DOBLADORASTIMEX@GMAIL.COM', 'Depto: SERVICIO', 1, 0 FROM `proveedores` WHERE `nombre` = 'INOXIDABLES TIMEX' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ANGELES ISLAS', 'SUPERVISOR', '664 206 5093', 'LAMINADOSTIMEX@GMAIL.COM', 'Depto: VENTAS', 0, 1 FROM `proveedores` WHERE `nombre` = 'INOXIDABLES TIMEX' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'MIGUEL', NULL, '664 731 1354', NULL, NULL, 0, 2 FROM `proveedores` WHERE `nombre` = 'INOXIDABLES TIMEX' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'IVAN LOZANO', 'DIRECTOR', '664.369.3522', 'IVANLOZANOC@GMAIL.COM', 'Depto: DIRECCION', 1, 0 FROM `proveedores` WHERE `nombre` = 'ISA' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'LUIS ANGEL ZAPIEN ROMERO', 'GTE. GENERAL', '664 413 5042', 'ANGELZAPIEN@JMINDUSTRIAL.COM.MX', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'JM INDUSTRIAL' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'DANIELA ESCAMILLA RUIZ', 'CUENTAS POR COBRAR', '664 624 6288', 'DANIELAESCAMILLA@JMINDUSTRIAL.COM.MX', 'Depto: CONTABILIDAD', 0, 1 FROM `proveedores` WHERE `nombre` = 'JM INDUSTRIAL' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'CHOFER', 'CHOFER', '664 403 3859', NULL, 'Depto: REPARTO', 0, 2 FROM `proveedores` WHERE `nombre` = 'JM INDUSTRIAL' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ROMAN MARTIN MACIAS CONTRERAS', 'SUPERVISOR', '664 204 8518', 'MACIASELECTRIC@HOTMAIL.COM', 'Depto: GERENCIA', 1, 0 FROM `proveedores` WHERE `nombre` = 'MACIAS INSTALACIONES ELECTRICAS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'MERCED GONZALEZ ORTEGA', 'ASIST. ADMINISTRATIVO', '664 291 9444', NULL, 'Depto: ADMINISTRACION', 0, 1 FROM `proveedores` WHERE `nombre` = 'MACIAS INSTALACIONES ELECTRICAS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'LIBRADO VILLA GONZALEZ', 'SUPERVISOR TECNICO', '664 176 0405', NULL, 'Depto: SERVICIO', 0, 2 FROM `proveedores` WHERE `nombre` = 'MACIAS INSTALACIONES ELECTRICAS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'JOSE ALBERTO VAZQUEZ REYES', 'SUPERVISOR TECNICO', '664 729 4058', NULL, 'Depto: SERVICIO', 0, 3 FROM `proveedores` WHERE `nombre` = 'MACIAS INSTALACIONES ELECTRICAS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'JUAN VILLA GONZALEZ', 'TEC. ELECTROMECANICO', '646 196 0801', NULL, 'Depto: SERVICIO', 0, 4 FROM `proveedores` WHERE `nombre` = 'MACIAS INSTALACIONES ELECTRICAS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'IVAN RAUL TORRES RODRIGUEZ', 'TEC. ELECTROMECANICO', '664 284 4608', NULL, 'Depto: SERVICIO', 0, 5 FROM `proveedores` WHERE `nombre` = 'MACIAS INSTALACIONES ELECTRICAS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'JOSE LUIS DIAZ VELAZCO', 'TEC. ELECTROMECANICO', '981 134 4865', NULL, 'Depto: SERVICIO', 0, 6 FROM `proveedores` WHERE `nombre` = 'MACIAS INSTALACIONES ELECTRICAS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ROBERTO DONADO', 'REPRESENTANTE', '664 709 9820', 'MAQUINADOS.VALENCIA04@GMAIL.COM', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'MANTENIMIENTO INDUSTRIAL Y MAQUINADOS VALENCIA' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ALICIA ZAPIEN', 'VENDEDOR', '664 591 4226', 'ALICIAZAPIEN.AZPE@GMAIL.COM', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'MATERIALES AZPE' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'PALOMA', 'GERENTE', '664 375 9221', NULL, 'Depto: GERENCIA', 1, 0 FROM `proveedores` WHERE `nombre` = 'MOBILIARIOS G.G.' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'GUILLERMO HIGUERA RIVAS', 'VENDEDOR', '664 228 7747', 'GUILLERMO.HIGUERA@MYCESRLCV.COM', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'MOTORES Y CONTROLES ELECTRICOS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ING. MIGUEL A. BURGUEÑO', 'INGENIERO', '664 160 7592', 'MIGUEL.BURGUENO@MYCESRLCV.COM', 'Depto: INGENIERIA Y VENTAS EXTERNAS', 0, 1 FROM `proveedores` WHERE `nombre` = 'MOTORES Y CONTROLES ELECTRICOS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'SANTIAGO MONTOYA', 'AUXILIAR DE INGENIERIA', '664 517 6987', 'SANTIAGO.MONTOYA@MYCESRLCV.COM', 'Depto: INGENIERIA Y VENTAS EXTERNAS', 0, 2 FROM `proveedores` WHERE `nombre` = 'MOTORES Y CONTROLES ELECTRICOS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'MARÍA GUADALUPE MORENO ORNELAS', 'ASISTENTE', '664 681 8559', 'OPERENOROESTE@GMAIL.COM', 'Depto: ADMINISTRATIVO', 1, 0 FROM `proveedores` WHERE `nombre` = 'OPERE' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'JORGE LUGO', 'GERENTE', '664 559 2429', NULL, NULL, 1, 0 FROM `proveedores` WHERE `nombre` = 'REFRIGERACION Y COCINAS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'VENTAS', 'ADMINISTRATIVO', '664 233 6071', NULL, 'Depto: ADMINISTRACION', 0, 1 FROM `proveedores` WHERE `nombre` = 'REFRIGERACION Y COCINAS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ANTONIO SOTO', 'GTE GENERAL', '664 405 42 51', 'SERPROY_STO@HOTMAIL.COM', 'Depto: PROYECTOS', 1, 0 FROM `proveedores` WHERE `nombre` = 'SERPROY' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'RAYNER RAMIREZ SANCHEZ', 'INGENIERO', '664 491 3337', 'ING.RAYNERRS@GMAIL.COM.', 'Depto: MANTENIMIENTO', 1, 0 FROM `proveedores` WHERE `nombre` = 'SERVICIOS Y SUMINISTROS INDUSTRIALES.' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'SAMUEL', NULL, '664 673 1329', NULL, NULL, 0, 1 FROM `proveedores` WHERE `nombre` = 'SERVICIOS Y SUMINISTROS INDUSTRIALES.' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'CARLOS', NULL, '664 281 3385', NULL, NULL, 1, 0 FROM `proveedores` WHERE `nombre` = 'TALLER DE HERRERIA' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ENRIQUE ELIZALDE', 'SUPERVISOR', '55 4088 1194', 'EELIZALDE@TRELIZA.COM', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'TRELIZA' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ALEJANDRA CABRERA', 'SERVICIO A CLIENTES', '55 3082 3598', 'SERVICIOACLIENTES3@TRELIZA.COM', 'Depto: VENTAS', 0, 1 FROM `proveedores` WHERE `nombre` = 'TRELIZA' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'FABIAN', 'TECNICO "AUTOMOTRIZ"', '664 412 7719', NULL, 'Depto: SERVICIO', 1, 0 FROM `proveedores` WHERE `nombre` = 'TRUCK LIFT' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'HERSON', 'TECNICO "ELECTRICO"', '664 450 3515', NULL, 'Depto: SERVICIO', 0, 1 FROM `proveedores` WHERE `nombre` = 'TRUCK LIFT' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'JOSE', 'SUPERVISOR', '664 406 6693', NULL, 'Depto: SERVICIO', 0, 2 FROM `proveedores` WHERE `nombre` = 'TRUCK LIFT' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ABEL TRUJILLO', 'GTE GENERAL', '664 330 8869', 'ABEL@TRUMAINDUSTRIAL.COM', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'TRUMA INDUSTRIAL' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'WENDY ROMERO', 'ADMINISTRACION', '664 387 4971', 'VENTAS@TRUMAINDUSTRIAL.COM', 'Depto: VENTAS', 0, 1 FROM `proveedores` WHERE `nombre` = 'TRUMA INDUSTRIAL' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'ROSARIO GPE. CASTRO NAVA', 'EJECUTIVO EN VENTAS', '664 309 0223', 'VENTASTIJ0402@TUVANOSA.COM', 'Depto: VENTAS', 1, 0 FROM `proveedores` WHERE `nombre` = 'TUVANOSA' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'CLAUDIA', 'ADMINISTRATIVO', '664 105 5029', 'SERVICIOS@USAMEXELECTRICA.COM', 'Depto: ADMINISTRACION', 1, 0 FROM `proveedores` WHERE `nombre` = 'USAMEX' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'FAUSTO', 'TECNICO', '664 105 5028', NULL, 'Depto: SERVICIO', 0, 1 FROM `proveedores` WHERE `nombre` = 'USAMEX' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'FALCON', 'REPARTIDOR', '664 188 1927', NULL, 'Depto: REPARTO', 1, 0 FROM `proveedores` WHERE `nombre` = 'Z GAS' LIMIT 1;
INSERT INTO `proveedor_contactos` (`proveedor_id`,`nombre`,`puesto`,`telefono`,`email`,`notas`,`es_principal`,`orden`)
  SELECT `id`, 'GERENTE DE VENTAS', 'GERENTE', '664 751 7117', NULL, 'Depto: VENTAS', 0, 1 FROM `proveedores` WHERE `nombre` = 'Z GAS' LIMIT 1;

-- ---------------------------------------------------------------------------
-- EQUIPOS (165) -- fusion de ambas hojas, nomenclatura BAC, sucursal_id=1
-- ---------------------------------------------------------------------------
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0023', 'A/C-1', 'AIRE ACONDICIONADO MINISPLIT', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 33, hoja STD). Codigo original: CBA60-140-0023. QR: CBA-60-0023. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: CLIMA. Area (viejo): DIRECCION. Nombre completo: A/C-1 CBA60-140-0023 0023.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0024', 'A/C-2', 'AIRE ACONDICIONADO MINISPLIT', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 34, hoja STD). Codigo original: CBA60-140-0024. QR: CBA-60-0024. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: CLIMA. Area (viejo): SERVIDOR. Nombre completo: A/C-2 CBA60-140-0024 0024.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0025', 'A/C-3', 'AIRE ACONDICIONADO MINISPLIT', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 35, hoja STD). Codigo original: CBA60-140-0025. QR: CBA-60-0025. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: CLIMA. Area (viejo): COMEDOR PRODUCCION. Nombre completo: A/C-3 CBA60-140-0025 0025.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0026', 'A/C-3-1', 'AIRE ACONDICIONADO MINISPLIT', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 36, hoja STD). Codigo original: CBA60-140-0026. QR: CBA-60-0026. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: CLIMA. Area (viejo): COMEDOR PRODUCCION. Nombre completo: A/C-3-1 CBA60-140-0026 0026.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0027', 'A/C-4', 'AIRE ACONDICIONADO MINISPLIT', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 37, hoja STD). Codigo original: CBA60-140-0027. QR: CBA-60-0027. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: CLIMA. Area (viejo): MERCADOTECNIA. Nombre completo: A/C-4 CBA60-140-0027 0027.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0028', 'A/C-5', 'AIRE ACONDICIONADO MINISPLIT', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 39, hoja STD). Codigo original: CBA60-140-0028. QR: CBA-60-0028. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: CLIMA. Area (viejo): SALA DE JUNTAS. Nombre completo: A/C-5 CBA60-140-0028 0028.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0029', 'A/C-6', 'AIRES ACONDICIONADOS TIPO PAQUETE', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 40, hoja STD). Codigo original: CBA60-140-0029. QR: CBA-60-0029. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: CLIMA. Area (viejo): COMEDOR/ADMMINISTRACION. Nombre completo: A/C-6 CBA60-140-0029 0029.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0150', 'A/C-7', 'AIRE ACONDICIONADO MINISPLIT', NULL, 'BON', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA', '2023-09-25', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 284, hoja STD). Codigo original: CBA60-140-0150. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: A/C-7. Area (viejo): ALMACEN SECOS. Nombre completo: A/C-7 CBA60-140-0150 0150.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0085', 'A/C-8', 'AIRE ACONDICIONADO MINISPLIT', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 150, hoja STD). Codigo original: CBA60-140-0085. QR: CBA-60-0085. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: A/C-8. Area (viejo): VENTAS. Nombre completo: A/C-8 CBA60-140-0085 0085.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0151', 'A/C-8', 'AIRE ACONDICIONADO MINISPLIT', NULL, 'BOHN', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA', '2023-09-25', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 285, hoja STD). Codigo original: CBA60-140-0151. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: A/C-8. Area (viejo): GERENCIA GRAL. Nombre completo: A/C-8 CBA60-140-0151 0151.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-100-0041', 'BATIDORA', 'BATIDORA', 'OVICKBURST', '13351', '64550', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / COMIDA RAPIDA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 78, hoja STD). Codigo original: CBA60-100-0041. QR: CBA-60-0041. Clasif1: MEZCLADOR. Clasif2: PREPARACIÓN. Area (viejo): COMIDA RAPIDA. Nombre completo: BATIDORA CBA60-100-0041 0041.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-100-0038', 'BAÑO MARIA', 'BAÑOS MARIA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / COMIDA RAPIDA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 74, hoja STD). Codigo original: CBA60-100-0038. QR: CBA-60-0038. Clasif1: CONSERVACION. Clasif2: PREPARACIÓN. Area (viejo): COMIDA RAPIDA. Nombre completo: BAÑO MARIA CBA60-100-0038 0038.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0078', 'BOILER', 'CALENTADORES DE AGUA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / TALLER MANTENIMIENTO', '2021-02-07', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 128, hoja STD). Codigo original: CBA60-120-0078. QR: CBA-60-0078. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: AGUA POTABLE CALIENTE. Area (viejo): TALLER MANTENIMIENTO. Nombre completo: BOILER CBA60-120-0078 0078.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0052', 'BOILER #1', 'CALENTADORES DE AGUA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / CUARTO DE MAQUINAS #2', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 89, hoja STD). Codigo original: CBA60-120-0052. QR: CBA-60-0052. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: AGUA POTABLE CALIENTE. Area (viejo): CUARTO DE MAQUINAS. Nombre completo: BOILER #1 CBA60-120-0052 0052.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0146', 'BOILER #2', 'CALENTADORES DE AGUA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / CUARTO DE MAQUINAS #2', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 90, hoja STD). Codigo original: CBA60-120-0146. QR: CBA-60-0053. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: AGUA POTABLE CALIENTE. Area (viejo): CUARTO DE MAQUINAS. Nombre completo: BOILER #2 CBA60-120-0146 0146.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0048', 'BOMBA DE AGUA', 'BOMBAS DE AGUA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / CUARTO DE MAQUINAS #2', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 85, hoja STD). Codigo original: CBA60-120-0048. QR: CBA-60-0048. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: AGUA POTABLE. Area (viejo): CUARTO DE MAQUINAS-2. Nombre completo: BOMBA DE AGUA CBA60-120-0048 0048.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0049', 'BOMBA SUMERGIBLE', 'BOMBAS DE AGUA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / TRAMPA DE GRASAS', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 86, hoja STD). Codigo original: CBA60-120-0049. QR: CBA-60-0049. Clasif1: TRASLADO. Clasif2: AGUA RESIDUAL. Area (viejo): SOTANO BACAL-2. Nombre completo: BOMBA SUMERGIBLE CBA60-120-0049 0049.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0072', 'BOMBA SUMERGIBLE', 'BOMBAS DE AGUA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / KARCAMO', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 114, hoja STD). Codigo original: CBA60-120-0072. QR: CBA-60-0072. Clasif1: TRASLADO. Clasif2: AGUA RESIDUAL. Area (viejo): SOTANO BACAL-1. Nombre completo: BOMBA SUMERGIBLE CBA60-120-0072 0072.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-170-0101', 'BOMBA SUMERGIBLE', 'BOMBAS DE AGUA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / KARCAMO', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 166, hoja STD). Codigo original: CBA60-170-0101. QR: CBA-60-0101. Clasif1: TRASLADO. Clasif2: AGUA CON GRASAS. Area (viejo): SOTANO BACAL-1. Nombre completo: BOMBA SUMERGIBLE CBA60-170-0101 0101.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0086', 'BOMBAS/DUCTOS DE SUCCION-1', 'TRASLADO NEUMATICO', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / ALMACEN / SECOS', '2021-03-17', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 151, hoja STD). Codigo original: CBA60-120-0086. QR: CBA-60-0086. Clasif1: ENVIO. Clasif2: CORTE CAJA. Area (viejo): BACAL-2. Nombre completo: BOMBAS/DUCTOS DE SUCCION-1 CBA60-120-0086 0086.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0087', 'BOMBAS/DUCTOS DE SUCCION-2', 'TRASLADO NEUMATICO', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / ALMACEN / SECOS', '2021-03-17', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 152, hoja STD). Codigo original: CBA60-120-0087. QR: CBA-60-0087. Clasif1: ENVIO. Clasif2: CORTE CAJA. Area (viejo): BACAL-2. Nombre completo: BOMBAS/DUCTOS DE SUCCION-2 CBA60-120-0087 0087.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0088', 'BOMBAS/DUCTOS DE SUCCION-3', 'TRASLADO NEUMATICO', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / ALMACEN / SECOS', '2021-03-17', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 153, hoja STD). Codigo original: CBA60-120-0088. QR: CBA-60-0088. Clasif1: ENVIO. Clasif2: CORTE CAJA. Area (viejo): BACAL-2. Nombre completo: BOMBAS/DUCTOS DE SUCCION-3 CBA60-120-0088 0088.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-110-006-0089', 'BOMBAS/DUCTOS DE SUCCION-4', 'TRASLADO NEUMATICO', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / ALMACEN / SECOS', '2021-03-17', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 154, hoja STD). Codigo original: CBA60-110-006-0089. QR: CBA-60-0089. Clasif1: ENVIO. Clasif2: CORTE CAJA. Area (viejo): BACAL-2. Nombre completo: BOMBAS/DUCTOS DE SUCCION-4 CBA60-110-006-0089 0089.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-110-0119', 'CAFETERA', 'CAFETERA', 'WESTBEND', '58002', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA', '2021-12-21', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 273, hoja STD). Codigo original: CBA60-110-0119. QR: CBA-60-0119. Clasif1: CALENTAMIENTO. Clasif2: BEBIDAS. Centro costo: .. Area (viejo): PISO DE VENTA. Nombre completo: CAFETERA CBA60-110-0119 0119.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-110-0161', 'CAJAS REGISTRADORAS', 'CAJA REGISTRADORA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 296, hoja STD). Codigo original: CBA60-110-0161. Clasif1: PAGO/COBRO. Clasif2: MERCANCIA. Centro costo: .. Area (viejo): PISO VENTA. Nombre completo: CAJAS REGISTRADORAS CBA60-110-0161 0161.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-100-0105', 'CAMPANA DE EXTRACCION', 'CAMPANA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / COMIDA RAPIDA', '2021-03-31', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 170, hoja STD). Codigo original: CBA60-100-0105. QR: CBA-60-0105. Clasif1: CONDUCTO. Clasif2: EXTRACCION DE GRASAS. Area (viejo): COMIDA RAPIDA. Nombre completo: CAMPANA DE EXTRACCION CBA60-100-0105 0105.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-100-0140', 'CAMPANA DE EXTRACCION', 'CAMPANA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / EXTERIOR', '2021-03-31', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 275, hoja STD). Codigo original: CBA60-100-0140. QR: CBA-60-0140. Clasif1: CONDUCTO. Clasif2: EXTRACCION DE GRASAS. Area (viejo): TAQUERIA. Nombre completo: CAMPANA DE EXTRACCION CBA60-100-0140 0140.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-230-0230', 'CAMPANA DE EXTRACCION', 'CAMPANA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / ANDEN', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 288, hoja STD). Codigo original: CBA60-230-0230. Clasif1: CONDUCTO. Clasif2: EXTRACCION VAPOR. Area (viejo): ANDEN BACAL-1. Nombre completo: CAMPANA DE EXTRACCION CBA60-230-0230 0153.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-230-0154', 'CAMPANA DE EXTRACCION', 'CAMPANA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / ANDEN', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 289, hoja STD). Codigo original: CBA60-230-0154. Clasif1: CONDUCTO. Clasif2: EXTRACCION VAPOR. Area (viejo): ANDEN BACAL-1. Nombre completo: CAMPANA DE EXTRACCION CBA60-230-0154 0154.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-160-0175', 'CARGADOR DE BATERIA P/MONTACARGAS ELECTRICO', 'GENERADOR', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / TALLER MANTENIMIENTO', '2021-02-25', '2023-12-09', NULL, NULL, 'Migrado del sistema viejo (ID 305, hoja STD). Codigo original: CBA60-160-0175. QR: CBA-60-0175. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: CARGA DE BATERIA. Area (viejo): ALMACEN CARNES. Nombre completo: CARGADOR DE BATERIA P/MONTACARGAS ELECTRICO CBA60-160-0175 0175.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0071', 'COMPACTADORA DE CARTON', 'COMPACTADORAS', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / ESTACIONAMIENTO', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 113, hoja STD). Codigo original: CBA60-120-0071. QR: CBA-60-0071. Clasif1: RECICLAJE. Clasif2: DISPOSICION. Area (viejo): ESTACIONAMIENTO BACAL-1. Nombre completo: COMPACTADORA DE CARTON CBA60-120-0071 0071.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0122', 'COMPRESOR 1-1', 'COMPRESORES RECIPROCANTES', 'COPELAND DISCUS', '2DD3R63KL-TFC-C27', 'ET 14H00473R', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / CUARTO DE MAQUINAS #1', '2021-09-28', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 265, hoja STD). Codigo original: CBA60-140-0122. QR: CBA-60-0122. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: MEDIA TEMPERATURA. Area (viejo): CUARTO DE MAQUINAS. Nombre completo: COMPRESOR 1-1 CBA60-140-0122 0122.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0123', 'COMPRESOR 1-2', 'COMPRESORES RECIPROCANTES', 'COPELAND DISCUS', '3DS4S12ML-TFC-C27', 'ET 14H01360R', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / CUARTO DE MAQUINAS #1', '2021-09-28', '2023-10-05', NULL, NULL, 'Migrado del sistema viejo (ID 266, hoja STD). Codigo original: CBA60-140-0123. QR: CBA-60-0123. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: MEDIA TEMPERATURA. Area (viejo): CUARTO DE MAQUINAS. Nombre completo: COMPRESOR 1-2 CBA60-140-0123 0123.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0124', 'COMPRESOR 1-3', 'COMPRESORES RECIPROCANTES', 'COPELAND DISCUS', '3DSDF46KL-TFC-C27', 'ET 14H00415R', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / CUARTO DE MAQUINAS #1', '2021-09-28', '2023-10-05', NULL, NULL, 'Migrado del sistema viejo (ID 268, hoja STD). Codigo original: CBA60-140-0124. QR: CBA-60-0124. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: MEDIA TEMPERATURA. Area (viejo): CUARTO DE MAQUINAS. Nombre completo: COMPRESOR 1-3 CBA60-140-0124 0124.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0118', 'COMPRESOR 2-4', 'COMPRESORES RECIPROCANTES', 'COPELAND DISCUS', '3DS3F46KL-TFC-C27', 'ET 14G03545R', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / CUARTO DE MAQUINAS #1', '2021-09-28', '2023-10-05', NULL, NULL, 'Migrado del sistema viejo (ID 269, hoja STD). Codigo original: CBA60-140-0118. QR: CBA-60-0118. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: BAJA TEMPERATURA. Area (viejo): CUARTO DE MAQUINAS. Nombre completo: COMPRESOR 2-4 CBA60-140-0118 0118.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0125', 'COMPRESOR 2-5', 'COMPRESORES RECIPROCANTES', 'COPELAND DISCUS', '4DRNF76KL-TSK-C27', 'ET 14G03634R', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / CUARTO DE MAQUINAS #1', '2021-09-28', '2023-10-05', NULL, NULL, 'Migrado del sistema viejo (ID 270, hoja STD). Codigo original: CBA60-140-0125. QR: CBA-60-0125. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: BAJA TEMPERATURA. Area (viejo): CUARTO DE MAQUINAS. Nombre completo: COMPRESOR 2-5 CBA60-140-0125 0125.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0126', 'COMPRESOR 2-6', 'COMPRESORES RECIPROCANTES', 'COPELAND DISCUS', '4DK3R22ME-TSK-800', '21G60443R', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / CUARTO DE MAQUINAS #1', '2021-09-28', '2023-10-05', NULL, NULL, 'Migrado del sistema viejo (ID 271, hoja STD). Codigo original: CBA60-140-0126. QR: CBA-60-0126. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: BAJA TEMPERATURA. Area (viejo): CUARTO DE MAQUINAS. Nombre completo: COMPRESOR 2-6 CBA60-140-0126 0126.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0047', 'COMPRESOR NEUMATICO', 'COMPRESOR', 'ATLAS COPCO', 'AR5EV2361P1', '9710502100', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / ALMACEN / SECOS', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 84, hoja STD). Codigo original: CBA60-120-0047. QR: CBA-60-0047. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: AIRE. Area (viejo): ALMACEN SECOS. Nombre completo: COMPRESOR NEUMATICO CBA60-120-0047 0047.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0127', 'CONDENSADOR REMOTO RACK', 'CONDENSADOR', 'BOHN', 'BNH-D06-A051', 'T14H04191', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA / MEZZANINE', '2020-12-03', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 8, hoja STD). Codigo original: CBA60-140-0127. QR: CBA-60-030. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: REACK. Area (viejo): MEZANINE EXTERIOR BACAL-2. Nombre completo: CONDENSADOR REMOTO RACK CBA60-140-0127 0127.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-110--0144', 'CONTENEDOR DE HIELOS', 'CONTENEDOR', 'CRIOTEC', 'CBH-100', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / CAJAS', '2022-10-28', '2023-10-06', NULL, NULL, 'Migrado del sistema viejo (ID 279, hoja STD). Codigo original: CBA60-110--0144. Clasif1: CONSERVACION. Clasif2: CONGELACION. Centro costo: .. Area (viejo): PISO VENTA. Nombre completo: CONTENEDOR DE HIELOS CBA60-110--0144 0144.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0090', 'CORTINA DE AIRE', 'CORTINAS DE AIRE', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / ACCESO A CLIENTES', '2021-03-17', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 155, hoja STD). Codigo original: CBA60-120-0090. QR: CBA-60-0090. Clasif1: INYECCION. Clasif2: AIRE. Area (viejo): PUERTA PRINCIPAL. Nombre completo: CORTINA DE AIRE CBA60-120-0090 0090.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-170-0156', 'CORTINA METALICA', 'CORTINA METALICA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / ANDEN', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 290, hoja STD). Codigo original: CBA60-170-0156. Clasif1: PUERTA ACCESO. Clasif2: MERCANCIA. Area (viejo): ANDEN BACAL-2. Nombre completo: CORTINA METALICA CBA60-170-0156 0156.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-170-0157', 'CORTINA METALICA', 'CORTINA METALICA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / ANDEN', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 291, hoja STD). Codigo original: CBA60-170-0157. Clasif1: PUERTA ACCESO. Clasif2: MERCANCIA. Area (viejo): ANDEN BACAL-1. Nombre completo: CORTINA METALICA CBA60-170-0157 0157.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-170-0158', 'CORTINA METALICA', 'CORTINA METALICA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / EXTERIOR', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 293, hoja STD). Codigo original: CBA60-170-0158. Clasif1: PUERTA ACCESO. Clasif2: SERVICIO. Area (viejo): COMPACTADORA CARTON. Nombre completo: CORTINA METALICA CBA60-170-0158 0158.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-100-0160', 'CORTINA METALICA', 'CORTINA METALICA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / EXTERIOR', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 294, hoja STD). Codigo original: CBA60-100-0160. Clasif1: PUERTA ACCESO. Clasif2: SERVICIO. Area (viejo): TAQUERIA. Nombre completo: CORTINA METALICA CBA60-100-0160 0160.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-170-0159', 'CORTINA METALICA', 'CORTINA METALICA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 295, hoja STD). Codigo original: CBA60-170-0159. Clasif1: PUERTA ACCESO. Clasif2: SERVICIO. Area (viejo): VENTANAS/PUERTA DE ACCESO BACAL-2. Nombre completo: CORTINA METALICA CBA60-170-0159 0159.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-170-0158-D1', 'COTINA METALICA', 'CORTINA METALICA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / EXTERIOR', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 292, hoja STD). Codigo original: CBA60-170-0158. Clasif1: PUERTA ACCESO. Clasif2: SERVICIO. Area (viejo): COMPACTADORA CARTON. Nombre completo: COTINA METALICA CBA60-170-0158 0158.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0066', 'DESEBRADOR DE CARNES', 'DESHEBRADOR DE CARNE', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / EMBUTIDOS', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 108, hoja STD). Codigo original: CBA60-130-0066. QR: CBA-60-0066. Clasif1: PROCESAMIENTO. Clasif2: ABLANDADOR. Centro costo: .. Area (viejo): EMBUTIDOS. Nombre completo: DESEBRADOR DE CARNES CBA60-130-0066 0066.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0043', 'ELEVADOR DE CARGA', 'ELEVADORES', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / CUARTO DE MAQUINAS #4', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 80, hoja STD). Codigo original: CBA60-120-0043. QR: CBA-60-0043. Clasif1: TRANSPORTE. Clasif2: MERCANCIA. Area (viejo): ANDEN BACAL-2. Nombre completo: ELEVADOR DE CARGA CBA60-120-0043 0043.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0070', 'ELEVADOR DE CARGA #1', 'ELEVADORES', 'BUCHER HYDRAULICS', NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / CUARTO DE MAQUINAS #5', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 112, hoja STD). Codigo original: CBA60-120-0070. QR: CBA-60-0070. Clasif1: TRANSPORTE. Clasif2: MERCANCIA. Area (viejo): ANDEN BACAL-1. Nombre completo: ELEVADOR DE CARGA #1 CBA60-120-0070 0070.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0093', 'EMPACADORA DC-800', 'SELLADORA', 'PROMAX', 'DC-800 FB', '20110202', 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / EMBUTIDOS', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 158, hoja STD). Codigo original: CBA60-130-0093. QR: CBA-60-0093. Clasif1: EMPAQUE. Clasif2: ALTO VACIO. Centro costo: .. Area (viejo): EMBUTIDOS. Nombre completo: EMPACADORA DC-800 CBA60-130-0093 0093.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0064', 'EMPACADORA SC-800', 'SELLADORA', 'PROMAX', 'SC-800', 'PF12032916', 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 106, hoja STD). Codigo original: CBA60-130-0064. QR: CBA-60-0064. Clasif1: EMPAQUE. Clasif2: ALTO VACIO. Centro costo: .. Area (viejo): EMBUTIDOS. Nombre completo: EMPACADORA SC-800 CBA60-130-0064 0064.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0032', 'EMPLAYADORA', NULL, 'TORREY', 'T5 500E', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / MOSTRADOR CARNES', '2021-01-15', '2025-02-20', NULL, NULL, 'Migrado del sistema viejo (ID 64, hoja STD). Codigo original: CBA60-130-0032. QR: CBA-60-0032. Clasif1: FUERA DE SERVICIO. Centro costo: .. Area (viejo): MOSTRADOR. Nombre completo: EMPLAYADORA CBA60-130-0032 0032.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0177', 'EMPLAYADORA', 'EMPLAYADORA', 'TORREY', 'TS-500', NULL, 1, NULL, '2025-02-20', '2025-02-20', NULL, NULL, 'Migrado del sistema viejo (ID 307, hoja STD). Codigo original: CBA60-130-0177. QR: CBA60-130-0177. Centro costo: SERVICIOS. Area (viejo): CARNICERÍA. Nombre completo: EMPLAYADORA CBA60-130-0177 0177.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-100-0035', 'ESTUFA INDUSTRIAL', 'ESTUFA', 'THERMATEK', NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / COMIDA RAPIDA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 71, hoja STD). Codigo original: CBA60-100-0035. QR: CBA-60-0035. Clasif1: COCIMIENTO. Clasif2: PREPARACIÓN. Area (viejo): COMIDA RAPIDA. Nombre completo: ESTUFA INDUSTRIAL CBA60-100-0035 0035.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0099', 'EVAPORADOR', 'EVAPORADOR.', 'BOHN', 'ADT104AK', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / AREA DE PROCESOS', '2020-12-03', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 164, hoja STD). Codigo original: CBA60-140-0099. QR: CBA-60-0099. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): PROCESO. Nombre completo: EVAPORADOR CBA60-140-0099 0099.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0104', 'EVAPORADOR', 'EVAPORADOR.', 'BOHN', 'ADT2081F', 'D94K06323', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / CUARTO FRIO VERDURAS', '2021-03-24', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 169, hoja STD). Codigo original: CBA60-140-0104. QR: CBA-60-0104. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO FRIO VERDURAS. Nombre completo: EVAPORADOR CBA60-140-0104 0104.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0061', 'EVAPORADOR #4', 'EVAPORADOR.', 'BOHN', 'BME620DA', 'D07A00990', 1, NULL, '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 98, hoja STD). Codigo original: CBA60-140-0061. QR: CBA-60-0061. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO FRIO #4. Nombre completo: EVAPORADOR #4 CBA60-140-0061 0061.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0059', 'EVAPORADOR #5', 'EVAPORADOR.', 'BOHN', 'BME620DA', 'D07A00989', 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / CUARTO FRIO #5 CONGELACIÓN', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 96, hoja STD). Codigo original: CBA60-140-0059. QR: CBA-60-0059. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO FRIO #5. Nombre completo: EVAPORADOR #5 CBA60-140-0059 0059.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0060', 'EVAPORADOR #5-1', 'EVAPORADOR.', 'BOHN', 'BME620DA', 'D07A00992', 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / CUARTO FRIO #5 CONGELACIÓN', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 97, hoja STD). Codigo original: CBA60-140-0060. QR: CBA-60-0060. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO FRIO #5. Nombre completo: EVAPORADOR #5-1 CBA60-140-0060 0060.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0057', 'EVAPORADOR #6', 'EVAPORADOR.', 'BOHN', 'BME620DA', 'D07A00988', 1, NULL, '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 94, hoja STD). Codigo original: CBA60-140-0057. QR: CBA-60-0057. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO FRIO #6. Nombre completo: EVAPORADOR #6 CBA60-140-0057 0057.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0058', 'EVAPORADOR #6-1', 'EVAPORADOR.', 'BOHN', 'BME620DA', 'D07A0091', 1, NULL, '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 95, hoja STD). Codigo original: CBA60-140-0058. QR: CBA-60-0058. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO FRIO #6. Nombre completo: EVAPORADOR #6-1 CBA60-140-0058 0058.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0130', 'EVAPORADOR #1', 'EVAPORADOR.', 'BOHN', 'BME520CA', 'T14H04198', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / CUARTO FRIO #2 CONGELACIÓN', '2020-12-03', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 11, hoja STD). Codigo original: CBA60-140-0130. QR: CBA-60-0130. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO FRIO#2. Nombre completo: EVAPORADOR #1 CBA60-140-0130 0130.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0132', 'EVAPORADOR #1', 'EVAPORADOR.', 'BOHN', 'BME520CA', 'T14H04197', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / CUARTO FRIO #1 CONGELACIÓN', '2020-12-03', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 13, hoja STD). Codigo original: CBA60-140-0132. QR: CBA-60-0132. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO FRIO #1. Nombre completo: EVAPORADOR #1 CBA60-140-0132 0132.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0134', 'EVAPORADOR #1', 'EVAPORADOR.', 'BOHN', 'BME310CA', 'T14H04196', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / CUARTO FRIO #3 CONSERVACIÓN', '2020-12-03', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 15, hoja STD). Codigo original: CBA60-140-0134. QR: CBA-60-0134. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO FRIO #3. Nombre completo: EVAPORADOR #1 CBA60-140-0134 0134.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0136', 'EVAPORADOR #1', 'EVAPORADOR.', 'BOHN', 'LET180BK', NULL, 1, NULL, '2020-12-03', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 17, hoja STD). Codigo original: CBA60-140-0136. QR: CBA-60-0136. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO DE CONSERVACION. Nombre completo: EVAPORADOR #1 CBA60-140-0136 0136.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0138', 'EVAPORADOR #1', 'EVAPORADOR.', 'BOHN', 'ADT104AK', NULL, 1, NULL, '2020-12-03', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 19, hoja STD). Codigo original: CBA60-140-0138. QR: CBA-60-0138. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO DE PROCESO. Nombre completo: EVAPORADOR #1 CBA60-140-0138 0138.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0139', 'EVAPORADOR #1', 'EVAPORADOR.', 'BOHN', 'LLE170BK', NULL, 1, NULL, '2020-12-03', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 20, hoja STD). Codigo original: CBA60-140-0139. QR: CBA-60-0139. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO DE CONGELACION. Nombre completo: EVAPORADOR #1 CBA60-140-0139 0139.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0014', 'EVAPORADOR #1', 'EVAPORADOR.', 'BOHN', 'ADT130AK', 'T14J12039', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / REFRIGERADOR BEBIDAS', '2020-12-06', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 21, hoja STD). Codigo original: CBA60-140-0014. QR: CBA-60-0014. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): WALK-IN BEBIDAS. Nombre completo: EVAPORADOR #1 CBA60-140-0014 0014.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0133', 'EVAPORADOR #2', 'EVAPORADOR.', 'BOHN', 'BME520CA', 'T14H04193', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / CUARTO FRIO #1 CONGELACIÓN', '2020-12-03', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 14, hoja STD). Codigo original: CBA60-140-0133. QR: CBA-60-0133. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO FRIO #1. Nombre completo: EVAPORADOR #2 CBA60-140-0133 0133.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0135', 'EVAPORADOR #2', 'EVAPORADOR.', 'BOHN', 'BME310CA', 'T14H04195', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / CUARTO FRIO #3 CONSERVACIÓN', '2020-12-03', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 16, hoja STD). Codigo original: CBA60-140-0135. QR: CBA-60-0135. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO FRIO #3. Nombre completo: EVAPORADOR #2 CBA60-140-0135 0135.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0137', 'EVAPORADOR #2', 'EVAPORADOR.', 'BOHN', 'LET180BK', NULL, 1, NULL, '2020-12-03', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 18, hoja STD). Codigo original: CBA60-140-0137. QR: CBA-60-0137. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO DE CONSERVACION. Nombre completo: EVAPORADOR #2 CBA60-140-0137 0137.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0015', 'EVAPORADOR #2', 'EVAPORADOR.', 'BOHN', 'ADT130AK', 'T14J12040', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / REFRIGERADOR BEBIDAS', '2020-12-06', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 22, hoja STD). Codigo original: CBA60-140-0015. QR: CBA-60-0015. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): WALK-IN BEBIDAS. Nombre completo: EVAPORADOR #2 CBA60-140-0015 0015.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0131', 'EVAPORADOR#2', 'EVAPORADOR.', 'BOHN', 'BME520CA', 'T14H04192', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SOTANO / CUARTO FRIO #2 CONGELACIÓN', '2020-12-03', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 12, hoja STD). Codigo original: CBA60-140-0131. QR: CBA-60-0131. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO FRIO #2. Nombre completo: EVAPORADOR#2 CBA60-140-0131 0131.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-100-0103', 'EXTRACTOR DE GRASAS', 'EXTRACTORES DE AIRE', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA', '2021-03-19', '2023-10-05', NULL, NULL, 'Migrado del sistema viejo (ID 168, hoja STD). Codigo original: CBA60-100-0103. QR: CBA-60-0103. Clasif1: EXTRACTOR. Clasif2: GRASAS. Area (viejo): COMIDA RAPIDA. Nombre completo: EXTRACTOR DE GRASAS CBA60-100-0103 0103.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-100-0152', 'EXTRACTOR DE GRASAS', 'EXTRACTORES DE AIRE', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / EXTERIOR', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 287, hoja STD). Codigo original: CBA60-100-0152. Clasif1: EXTRACTOR. Clasif2: GRASAS. Area (viejo): TAQUERIA. Nombre completo: EXTRACTOR DE GRASAS CBA60-100-0152 0152.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-230-0155', 'EXTRACTOR DE VAPOR', 'EXTRACTORES DE AIRE', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / EXTERIOR', '2023-10-04', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 286, hoja STD). Codigo original: CBA60-230-0155. Clasif1: EXTRACTOR. Clasif2: VAPOR. Area (viejo): ANDEN BACAL-1. Nombre completo: EXTRACTOR DE VAPOR CBA60-230-0155 0155.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-100-0037', 'FREIDOR INDUSTRIAL', 'FREIDORAS', 'THERMATEK', NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / COMIDA RAPIDA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 73, hoja STD). Codigo original: CBA60-100-0037. QR: CBA-60-0037. Clasif1: COCIMIENTO. Clasif2: PREPARACIÓN. Area (viejo): COMIDA RAPIDA. Nombre completo: FREIDOR INDUSTRIAL CBA60-100-0037 0037.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0053', 'GENERADOR', 'GENERADOR', 'KOHLER', '60R0ZJ81', '170284', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 82, hoja STD). Codigo original: CBA60-120-0053. QR: CBA-60-0045. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: PLANTA DE ENERGIA AUXILIAR. Area (viejo): AZOTEA. Nombre completo: GENERADOR CBA60-120-0053 0053.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0065', 'INYECTORA', 'INYECTORAS', 'TAURINOX', NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 107, hoja STD). Codigo original: CBA60-130-0065. QR: CBA-60-0065. Clasif1: PROCESAMIENTO. Clasif2: INYECCION SALMUERA. Centro costo: .. Area (viejo): EMBUTIDOS. Nombre completo: INYECTORA CBA60-130-0065 0065.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0100', 'LICUADORA', 'LICUADORA', 'TAPISA', NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / EMBUTIDOS', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 165, hoja STD). Codigo original: CBA60-130-0100. QR: CBA-60-0100. Clasif1: PROCESAMIENTO. Clasif2: MOLIENDA. Centro costo: .. Area (viejo): EMBUTIDOS. Nombre completo: LICUADORA CBA60-130-0100 0100.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-100-0109', 'LICUADORA', 'LICUADORA', 'VITA-MIX', 'VM101', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / COMIDA RAPIDA', '2021-06-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 200, hoja STD). Codigo original: CBA60-100-0109. QR: CBA-60-0109. Clasif1: PROCESAMIENTO. Clasif2: MOLIENDA. Area (viejo): COMIDA RAPIDA. Nombre completo: LICUADORA CBA60-100-0109 0109.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0069', 'LICUADORA INDUSTRIAL', 'LICUADORA', 'INTERNATIONAL', 'LI-17VA', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / EMBUTIDOS', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 111, hoja STD). Codigo original: CBA60-130-0069. QR: CBA-60-0069. Clasif1: PROCESAMIENTO. Clasif2: MOLIENDA. Centro costo: .. Area (viejo): EMBUTIDOS. Nombre completo: LICUADORA INDUSTRIAL CBA60-130-0069 0069.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-200-0102', 'LICUADORA PARA MACHACA', 'LICUADORA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / EMPAQUE DE ESPECIAS', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 167, hoja STD). Codigo original: CBA60-200-0102. QR: CBA-60-0102. Clasif1: PROCESAMIENTO. Clasif2: MOLIENDA. Area (viejo): EMPAQUE DE SECOS. Nombre completo: LICUADORA PARA MACHACA CBA60-200-0102 0102.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC-120-0075', 'MARMITA CHICA', 'MARMITA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / ANDEN', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 118, hoja STD). Codigo original: CBA-120-0075. QR: CBA-60-0075. Clasif1: COCIMIENTO. Clasif2: VAPOR. Area (viejo): ANDEN BACAL-1. Nombre completo: MARMITA CHICA CBA-120-0075 0075.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0074', 'MARMITA GRANDE', 'MARMITA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / ANDEN', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 117, hoja STD). Codigo original: CBA60-120-0074. QR: CBA-60-0074. Clasif1: COCIMIENTO. Clasif2: VAPOR. Area (viejo): ANDEN BACAL-1. Nombre completo: MARMITA GRANDE CBA60-120-0074 0074.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0063', 'MEZCLADORA DE CARNE', 'BATIDORAS INDUSTRIALES', 'TECNOMAIZ', NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / EMBUTIDOS', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 104, hoja STD). Codigo original: CBA60-130-0063. QR: CBA-60-0063. Clasif1: PROCESAMIENTO. Clasif2: MOLIENDA. Centro costo: .. Area (viejo): EMBUTIDOS. Nombre completo: MEZCLADORA DE CARNE CBA60-130-0063 0063.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0092', 'MEZCLADORA DE ADOBO', 'BATIDORAS INDUSTRIALES', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / EMBUTIDOS', '2021-03-17', '2023-12-11', '2021-03-17', 'Fuera de servicio (sistema viejo)', 'Migrado del sistema viejo (ID 157, hoja STD). Codigo original: CBA60-130-0092. QR: CBA60-0092. Clasif1: BAJA. Clasif2: MEZCLADOR. Centro costo: .. Area (viejo): EMBUTIDOS. Nombre completo: MEZCLADORA DE ADOBO CBA60-130-0092 0092.', 0, 'dado_de_baja');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0168', 'MEZCLADORA DE ADOBO', 'BATIDORAS INDUSTRIALES', NULL, 'RUBIMIX 7', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / EMBUTIDOS', '2021-03-17', '2023-11-23', NULL, NULL, 'Migrado del sistema viejo (ID 303, hoja STD). Codigo original: CBA60-130-0168. QR: CBA-60-0092. Clasif1: PROCESAMIENTO. Clasif2: MEZCLADOR. Centro costo: .. Area (viejo): EMBUTIDOS. Nombre completo: MEZCLADORA DE ADOBO CBA60-130-0168 0168.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-100-0042', 'MICROONDAS', 'MICROONDAS', 'AMANA', NULL, 'S/N', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / COMIDA RAPIDA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 79, hoja STD). Codigo original: CBA60-100-0042. QR: CBA-60-0042. Clasif1: COCIMIENTO. Clasif2: FUERA DE SERVICIO. Area (viejo): COMIDA RAPIDA. Nombre completo: MICROONDAS CBA60-100-0042 0042.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0165', 'MICROONDAS', 'MICROONDAS', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / TERCER PISO / COMEDOR', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 300, hoja STD). Codigo original: CBA60-120-0165. Clasif1: CALENTAMIENTO. Clasif2: ALIMENTOS. Area (viejo): COMEDOR PRODUCCION. Nombre completo: MICROONDAS CBA60-120-0165 0165.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0166', 'MICROONDAS-1', 'MICROONDAS', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SEGUNDO PISO / COMEDOR', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 301, hoja STD). Codigo original: CBA60-120-0166. Clasif1: CALENTAMIENTO. Clasif2: ALIMENTOS. Area (viejo): COMEDOR/ADMMINISTRACION. Nombre completo: MICROONDAS-1 CBA60-120-0166 0166.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0167', 'MICROONDAS-2', 'MICROONDAS', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SEGUNDO PISO / COMEDOR', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 302, hoja STD). Codigo original: CBA60-120-0167. Clasif1: CALENTAMIENTO. Clasif2: ALIMENTOS. Area (viejo): COMEDOR/ADMMINISTRACION. Nombre completo: MICROONDAS-2 CBA60-120-0167 0167.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0062', 'MOLINO BIRO', 'MOLINOS', 'BIRO', '548BF05', '29531', 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO / EMBUTIDOS', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 103, hoja STD). Codigo original: CBA60-130-0062. QR: CBA-60-0062. Clasif1: PROCESAMIENTO. Clasif2: MOLIENDA. Centro costo: .. Area (viejo): EMBUTIDOS. Nombre completo: MOLINO BIRO CBA60-130-0062 0062.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0030', 'MOLINO HOBART', 'MOLINOS', 'HOBART', '4732-18STD', '311607454', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / MOSTRADOR CARNES', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 62, hoja STD). Codigo original: CBA60-130-0030. QR: CBA-60-0030. Clasif1: PROCESAMIENTO. Clasif2: MOLIENDA. Centro costo: .. Area (viejo): MOSTRADOR. Nombre completo: MOLINO HOBART CBA60-130-0030 0030.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-200-0079', 'MOLINO JR', 'MOLINOS', 'JR', 'MJ-22', 'S/N', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / EMPAQUE DE ESPECIAS', '2021-02-07', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 129, hoja STD). Codigo original: CBA60-200-0079. QR: CBA-60-0079. Clasif1: PROCESAMIENTO. Clasif2: MOLIENDA. Area (viejo): EMPAQUE DE ESPECIES. Nombre completo: MOLINO JR CBA60-200-0079 0079.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0081', 'MOLINO PRO CUT', 'MOLINOS', 'PRO-CUT', 'KG-22W', 'G18-017884', 1, 'CARNES BACAL / EDIFICIO BACAL-1 / TALLER MANTENIMIENTO', '2021-02-07', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 131, hoja STD). Codigo original: CBA60-130-0081. QR: CBA-60-0081. Clasif1: FUERA DE SERVICIO. Clasif2: FUERA DE SERVICIO. Centro costo: .. Area (viejo): MANTENIMIENTO. Nombre completo: MOLINO PRO CUT CBA60-130-0081 0081.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-160-0142', 'MONTACARGAS DE GAS CATERPILAR', 'MONTACARGAS', 'CATERPILLAR', 'C5000', NULL, 1, NULL, '2022-07-20', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 277, hoja STD). Codigo original: CBA60-160-0142. QR: CBA-60-0084. Clasif1: TRANSPORTE. Clasif2: CARGA. Area (viejo): ALMACEN CARNES. Anio fab: 2005. Nombre completo: MONTACARGAS DE GAS CATERPILAR CBA60-160-0142 0142.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-160-0084', 'MONTACARGAS DE GAS TOYOTA', 'MONTACARGAS', 'TOYOTA', '5FGCU15', '11088', 1, NULL, '2021-02-25', '2023-09-29', NULL, NULL, 'Migrado del sistema viejo (ID 149, hoja STD). Codigo original: CBA60-160-0084. QR: BCA-0005. Clasif1: TRANSPORTE. Clasif2: CARGA. Centro costo: TRANSPORTE-. Area (viejo): ALMACEN CARNES. Nombre completo: MONTACARGAS DE GAS TOYOTA CBA60-160-0084 0084.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-160-0106', 'MONTACARGAS ELECTRICO', 'MONTACARGAS', 'TOYOTA', '5FBCU15', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / TALLER MANTENIMIENTO', '2021-02-25', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 171, hoja STD). Codigo original: CBA60-160-0106. QR: CBA-60-0106. Clasif1: FUERA DE SERVICIO. Clasif2: REPARACION.. Area (viejo): ALMACEN CARNES. Nombre completo: MONTACARGAS ELECTRICO CBA60-160-0106 0106.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-160-0110', 'PALLET JACK-1', 'PATINES HIDRAULICOS', NULL, NULL, NULL, 1, 'CARNES BACAL / UNIDAD DE TRANSPORTE C-13', '2021-08-24', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 256, hoja STD). Codigo original: CBA60-160-0110. Clasif1: TRANSPORTE. Clasif2: CARGA. Area (viejo): CAMION DE CARGA C-13. Nombre completo: PALLET JACK-1 CBA60-160-0110 0110.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-160-0111', 'PALLET JACK-2', 'PATINES HIDRAULICOS', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / EXTERIOR', '2021-08-24', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 257, hoja STD). Codigo original: CBA60-160-0111. Clasif1: TRANSPORTE. Clasif2: CARGA. Area (viejo): ALMACEN EXTERIOR. Nombre completo: PALLET JACK-2 CBA60-160-0111 0111.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-160-0112', 'PALLET JACK-3', 'PATINES HIDRAULICOS', 'MUTORO', NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO', '2021-08-24', '2024-04-11', NULL, NULL, 'Migrado del sistema viejo (ID 258, hoja STD). Codigo original: CBA60-160-0112. Clasif1: TRANSPORTE. Clasif2: CARGA. Area (viejo): SOTANO BACAL-1. Nombre completo: PALLET JACK-3 CBA60-160-0112 0112.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-160-0113', 'PALLET JACK-4', 'PATINES HIDRAULICOS', NULL, NULL, NULL, 1, NULL, '2021-08-24', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 259, hoja STD). Codigo original: CBA60-160-0113. Clasif1: TRANSPORTE. Clasif2: CARGA. Area (viejo): ANDEN BACAL-2. Nombre completo: PALLET JACK-4 CBA60-160-0113 0113.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-160-0114', 'PALLET JACK-5', 'PATINES HIDRAULICOS', NULL, NULL, NULL, 1, NULL, '2021-08-24', '2023-10-05', NULL, NULL, 'Migrado del sistema viejo (ID 260, hoja STD). Codigo original: CBA60-160-0114. Clasif1: TRATAMIENTO DE AGUA. Clasif2: CARGA. Area (viejo): ANDEN BACAL-2. Nombre completo: PALLET JACK-5 CBA60-160-0114 0114.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-160-0115', 'PALLET JACK-6', 'PATINES HIDRAULICOS', 'WESCO', NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SOTANO', '2021-08-24', '2024-04-01', NULL, NULL, 'Migrado del sistema viejo (ID 261, hoja STD). Codigo original: CBA60-160-0115. Clasif1: TRANSPORTE. Clasif2: CARGA. Area (viejo): SOTANO BACAL-2. Nombre completo: PALLET JACK-6 CBA60-160-0115 0115.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-160-0116', 'PALLET JACK-7', 'PATINES HIDRAULICOS', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SOTANO', '2021-08-24', '2023-10-05', NULL, NULL, 'Migrado del sistema viejo (ID 262, hoja STD). Codigo original: CBA60-160-0116. Clasif1: TRANSPORTE. Clasif2: CARGA. Area (viejo): SOTANO BACAL-2. Nombre completo: PALLET JACK-7 CBA60-160-0116 0116.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-160-0117', 'PALLET JACK-8', 'PATINES HIDRAULICOS', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SEGUNDO PISO / ALMACEN DE SECOS', '2021-08-24', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 263, hoja STD). Codigo original: CBA60-160-0117. Clasif1: TRANSPORTE. Clasif2: CARGA. Area (viejo): ALMACEN SECOS. Nombre completo: PALLET JACK-8 CBA60-160-0117 0117.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-100-0036', 'PLANCHA INDUSTRIAL', 'PLANCHAS', 'THERMATEK', NULL, 'MS-2418', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / COMIDA RAPIDA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 72, hoja STD). Codigo original: CBA60-100-0036. QR: CBA-60-0036. Clasif1: COCIMIENTO. Clasif2: PREPARACIÓN. Area (viejo): COMIDA RAPIDA. Nombre completo: PLANCHA INDUSTRIAL CBA60-100-0036 0036.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0044', 'PRESS WASH', 'LAVADORAS', 'HOTSY', 'HWE 403C', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SOTANO', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 81, hoja STD). Codigo original: CBA60-120-0044. QR: CBA-60-0044. Clasif1: LIMPIEZA. Clasif2: LAVADO A PRESION. Area (viejo): SOTANO BACAL-2. Nombre completo: PRESS WASH CBA60-120-0044 0044.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0040', 'PUERTA ACCESO EMPLEADOS', 'PUERTA MANUAL', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / ACCESO A CLIENTES', '2021-03-17', '2023-12-09', NULL, NULL, 'Migrado del sistema viejo (ID 304, hoja STD). Codigo original: CBA60-120-0040. QR: CBA-60-0040. Clasif1: PUERTA ACCESO. Clasif2: SERVICIO. Area (viejo): BACAL-2. Nombre completo: PUERTA ACCESO EMPLEADOS CBA60-120-0040 0040.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0091', 'PUERTA ACCESO PRINCIPAL', 'PUERTA AUTOMATICA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / ACCESO A CLIENTES', '2021-03-17', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 156, hoja STD). Codigo original: CBA60-120-0091. QR: CBA-60-0091. Clasif1: PUERTA ACCESO. Clasif2: PUERTA AUTOMATICA. Area (viejo): BACAL-2. Nombre completo: PUERTA ACCESO PRINCIPAL CBA60-120-0091 0091.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0121', 'RACK', 'COMPRESORES RECIPROCANTES', 'HEATCRAFT', 'TD330-056-SS-3-NH2E', 'S14H00114', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / CUARTO DE MAQUINAS #1', '2015-06-18', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 1, hoja STD). Codigo original: CBA60-140-0121. QR: CBA-60-0121. Clasif1: CONTROLADOR. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): CUARTO DE MAQUINAS. Anio fab: 2015. Nombre completo: RACK CBA60-140-0121 0121.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0016', 'REACH IN #1', 'EXHIBIDOR', 'HEATCRAFT', 'QFGCEI-04AUN', 'C14J00135', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA', '2020-12-06', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 26, hoja STD). Codigo original: CBA60-140-0016. QR: CBA-60-0016. Clasif1: CONSERVACION. Clasif2: CONGELACION. Area (viejo): PRODUCTOS CONGELADOS. Nombre completo: REACH IN #1 CBA60-140-0016 0016.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0018', 'REACH IN #2', 'EXHIBIDOR', 'HEATCRAFT', 'QFGCEI-04AUN', 'C14J00136', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA', '2020-12-06', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 27, hoja STD). Codigo original: CBA60-140-0018. QR: CBA-60-0018. Clasif1: CONSERVACION. Clasif2: REFRIGERACION. Area (viejo): PRODUCTOS REFRIGERADOS. Nombre completo: REACH IN #2 CBA60-140-0018 0018.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0019', 'REACH IN #3', 'EXHIBIDOR', 'HEATCRAFT', 'QNGCEI-05AUN', 'C14J01944', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / LACTEOS', '2021-01-14', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 29, hoja STD). Codigo original: CBA60-140-0019. QR: CBA-60-0019. Clasif1: CONSERVACION. Clasif2: REFRIGERACION. Area (viejo): LACTEOS. Nombre completo: REACH IN #3 CBA60-140-0019 0019.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0001', 'REBANADORA #01', 'REBANADORA', 'BERKEL', '808', 'S/N', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / MOSTRADOR CARNES', '2021-02-20', '2025-02-20', NULL, NULL, 'Migrado del sistema viejo (ID 134, hoja STD). Codigo original: CBA60-130-0001. QR: CBA-60-0001. Clasif1: REBANADO. Clasif2: CORTES. Centro costo: .. Area (viejo): MOSTRADOR. Nombre completo: REBANADORA #01 CBA60-130-0001 0001.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0002', 'REBANADORA #02', 'REBANADORA', 'BERKEL', '808', 'S/N', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / MOSTRADOR CARNES', '2021-02-20', '2025-02-07', NULL, NULL, 'Migrado del sistema viejo (ID 135, hoja STD). Codigo original: CBA60-130-0002. QR: CBA-60-0002. Clasif1: REBANADO. Clasif2: CORTES. Centro costo: .. Area (viejo): MOSTRADOR. Nombre completo: REBANADORA #02 CBA60-130-0002 0002.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0003', 'REBANADORA #03', 'REBANADORA', 'BERKEL', '808', 'S/N', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / AREA DE PROCESOS', '2021-02-20', '2025-02-20', NULL, NULL, 'Migrado del sistema viejo (ID 136, hoja STD). Codigo original: CBA60-130-0003. QR: CBA-60-0003. Clasif1: REBANADO. Clasif2: CORTES. Centro costo: .. Area (viejo): PROCESO. Nombre completo: REBANADORA #03 CBA60-130-0003 0003.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0004', 'REBANADORA #04', 'REBANADORA', 'BERKEL', '808', 'S/N', 1, 'CARNES BACAL / EDIFICIO BACAL-1 / TALLER MANTENIMIENTO', '2021-02-20', '2025-02-05', NULL, NULL, 'Migrado del sistema viejo (ID 264, hoja STD). Codigo original: CBA60-130-0004. QR: CBA-60-0004. Clasif1: FUERA DE SERVICIO. Clasif2: REPARACION.. Centro costo: .. Area (viejo): TALLER. Nombre completo: REBANADORA #04 CBA60-130-0004 0004.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0005', 'REBANADORA #05', 'REBANADORA', 'BERKEL', '808', 'S/N', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / MOSTRADOR CARNES', '2021-02-20', '2025-02-20', NULL, NULL, 'Migrado del sistema viejo (ID 137, hoja STD). Codigo original: CBA60-130-0005. QR: CBA-60-0005. Clasif1: REBANADO. Clasif2: CORTES. Centro costo: .. Area (viejo): MOSTRADOR. Nombre completo: REBANADORA #05 CBA60-130-0005 0005.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0006', 'REBANADORA #06', 'REBANADORA', 'BERKEL', '808', 'S/N', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / MOSTRADOR CARNES', '2021-02-20', '2025-02-20', NULL, NULL, 'Migrado del sistema viejo (ID 138, hoja STD). Codigo original: CBA60-130-0006. QR: CBA-60-0006. Clasif1: REBANADO. Clasif2: CORTES. Centro costo: .. Area (viejo): MOSTRADOR. Nombre completo: REBANADORA #06 CBA60-130-0006 0006.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0007', 'REBANADORA #07', 'REBANADORA', 'BERKEL', '808', 'S/N', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / AREA DE PROCESOS', '2021-02-20', '2025-02-20', NULL, NULL, 'Migrado del sistema viejo (ID 139, hoja STD). Codigo original: CBA60-130-0007. QR: CBA-60-0007. Clasif1: REBANADO. Clasif2: CORTES. Centro costo: .. Area (viejo): PROCESO. Nombre completo: REBANADORA #07 CBA60-130-0007 0007.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0008', 'REBANADORA #08', 'REBANADORA', 'GLOBE', '3600N', 'S/N', 1, 'CARNES BACAL / EDIFICIO BACAL-1 / TALLER MANTENIMIENTO', '2021-02-20', '2025-02-20', NULL, NULL, 'Migrado del sistema viejo (ID 141, hoja STD). Codigo original: CBA60-130-0008. QR: CBA-60-0012. Clasif1: FUERA DE SERVICIO. Clasif2: REPARACION.. Centro costo: .. Area (viejo): TALLER. Nombre completo: REBANADORA #08 CBA60-130-0008 0008.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0009', 'REBANADORA #09', 'REBANADORA', 'GLOBE', '3600N', 'S/N', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / AREA DE PROCESOS', '2021-02-20', '2025-02-20', '2021-02-20', 'Fuera de servicio (sistema viejo)', 'Migrado del sistema viejo (ID 145, hoja STD). Codigo original: CBA60-130-0009. QR: CBA-60-0013. Clasif1: REBANADO. Clasif2: CORTES. Centro costo: .. Area (viejo): PROCESO. Nombre completo: REBANADORA #09 CBA60-130-0009 0009.', 0, 'dado_de_baja');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0010', 'REBANADORA #10', 'REBANADORA', 'GLOBE', 'S-13', 'S/N', 1, 'CARNES BACAL / EDIFICIO BACAL-1 / TALLER MANTENIMIENTO', '2021-02-20', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 143, hoja STD). Codigo original: CBA60-130-0010. QR: CBA-60-0010. Clasif1: FUERA DE SERVICIO. Clasif2: ALMACENAMIENTO. Centro costo: .. Area (viejo): TALLER. Nombre completo: REBANADORA #10 CBA60-130-0010 0010.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0011', 'REBANADORA #11', 'REBANADORA', 'GLOBE', 'S-13', 'S/N', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / MOSTRADOR DE QUESOS', '2021-02-20', '2023-10-12', NULL, NULL, 'Migrado del sistema viejo (ID 144, hoja STD). Codigo original: CBA60-130-0011. QR: CBA-60-0011. Clasif1: REBANADO. Clasif2: CORTES. Centro costo: .. Area (viejo): MOSTRADOR. Nombre completo: REBANADORA #11 CBA60-130-0011 0011.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0176', 'REBANADORA #12', 'REBANADORA', 'HOBART', 'HS8', NULL, 1, NULL, '2025-02-20', '2025-02-20', NULL, NULL, 'Migrado del sistema viejo (ID 306, hoja STD). Codigo original: CBA60-130-0176. QR: CBA60-130-0176. Centro costo: SERVICIOS. Area (viejo): CARNICERÍA. Nombre completo: REBANADORA #12 CBA60-130-0176 0176.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-100-0039', 'REFRIGERADOR', 'REFRIGERADORES', 'ARTIC AIR', 'AR49E', '499904', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / COMIDA RAPIDA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 75, hoja STD). Codigo original: CBA60-100-0039. QR: CBA-60-0039. Clasif1: CONSERVACION. Clasif2: ALIMENTOS. Area (viejo): COMIDA RAPIDA. Nombre completo: REFRIGERADOR CBA60-100-0039 0039.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0162', 'REFRIGERADOR', 'REFRIGERADORES', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / SEGUNDO PISO / COMEDOR', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 297, hoja STD). Codigo original: CBA60-120-0162. Clasif1: CONSERVACION. Clasif2: ALIMENTOS. Area (viejo): COMEDOR/ADMMINISTRACION. Nombre completo: REFRIGERADOR CBA60-120-0162 0162.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0163', 'REFRIGERADOR', 'REFRIGERADORES', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / TERCER PISO / COMEDOR', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 298, hoja STD). Codigo original: CBA60-120-0163. Clasif1: CONSERVACION. Clasif2: ALIMENTOS. Area (viejo): COMEDOR PRODUCCION. Nombre completo: REFRIGERADOR CBA60-120-0163 0163.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-100-0164', 'REFRIGERADOR', 'REFRIGERADORES', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / EXTERIOR', '2023-10-06', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 299, hoja STD). Codigo original: CBA60-100-0164. Clasif1: CONSERVACION. Clasif2: MERCANCIA. Area (viejo): TAQUERIA. Nombre completo: REFRIGERADOR CBA60-100-0164 0164.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-200-0080', 'SELLADORA', 'SELLADORA', 'BROTHER', 'FRD-1000LW', NULL, 1, NULL, '2021-02-07', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 130, hoja STD). Codigo original: CBA60-200-0080. QR: CBA-60-0080. Clasif1: EMPAQUE. Clasif2: SELLADO. Area (viejo): EMPAQUE DE ESPECIES. Nombre completo: SELLADORA CBA60-200-0080 0080.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-200-0107', 'SELLADORA DE IMPULSO 8\'\'', 'SELLADORA', 'ULINE', 'H-190', '400105', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / EMPAQUE DE ESPECIAS', '2021-02-07', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 174, hoja STD). Codigo original: CBA60-200-0107. QR: CBA-60-0107. Clasif1: EMPAQUE. Clasif2: SELLADO. Area (viejo): EMPAQUE DE SECOS. Nombre completo: SELLADORA DE IMPULSO 8\'\' CBA60-200-0107 0107.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0120', 'SIERRA LION', 'REBANADORA', 'TREIF', 'LION RS -3560', '356008.61895.101982', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / AREA DE PROCESOS', '2022-01-18', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 274, hoja STD). Codigo original: CBA60-130-0120. QR: CBA-60-0120. Clasif1: CORTES. Clasif2: CORTE Y REBANADO. Centro costo: .. Area (viejo): PROCESO. Nombre completo: SIERRA LION CBA60-130-0120 0120.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130--0108', 'SIERRA VERTICAL BIRO', 'SIERRA DE CARNES', 'BIRO', NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / MOSTRADOR CARNES', '2021-01-15', '2024-07-24', NULL, NULL, 'Migrado del sistema viejo (ID 177, hoja STD). Codigo original: CBA60-130--0108. QR: CBA-60-0108. Clasif1: CORTES. Clasif2: CORTE Y REBANADO. Centro costo: .. Area (viejo): MOSTRADOR. Nombre completo: SIERRA VERTICAL BIRO CBA60-130--0108 0108.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0082', 'SIERRA VERTICAL BUTCHER BOY', 'SIERRA DE CARNES', 'BUTCHER BOY', NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / SOTANO', '2021-01-15', '2024-08-13', NULL, NULL, 'Migrado del sistema viejo (ID 146, hoja STD). Codigo original: CBA60-130-0082. QR: CBA-60-0082. Clasif1: CORTES. Clasif2: CORTE Y REBANADO. Centro costo: .. Area (viejo): SOTANO BACAL-1. Nombre completo: SIERRA VERTICAL BUTCHER BOY CBA60-130-0082 0082.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-130-0031', 'SIERRA VERTICAL JR', 'SIERRA DE CARNES', 'JR', 'SJ-295', '10831', 1, 'CARNES BACAL / EDIFICIO BACAL-1 / TALLER MANTENIMIENTO', '2021-01-15', '2024-04-10', NULL, NULL, 'Migrado del sistema viejo (ID 63, hoja STD). Codigo original: CBA60-130-0031. QR: CBA-60-0031. Clasif1: CORTES. Clasif2: CORTE Y REBANADO. Centro costo: .. Area (viejo): MANTENIMIENTO. Nombre completo: SIERRA VERTICAL JR CBA60-130-0031 0031.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0051', 'SUABIZADOR DE AGUA', 'SUAVIZADOR DE AGUA', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / CUARTO DE MAQUINAS #2', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 88, hoja STD). Codigo original: CBA60-120-0051. QR: CBA-60-0051. Clasif1: TRATAMIENTO DE AGUA. Clasif2: FILTRACION. Area (viejo): CUARTO DE MAQUINAS. Nombre completo: SUABIZADOR DE AGUA CBA60-120-0051 0051.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0050', 'SUB ESTACION ELECTRICA', 'SUBESTACIONES ELECTRICAS', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 87, hoja STD). Codigo original: CBA60-120-0050. QR: CBA-60-0050. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: ENERGIA ELECTRICA. Area (viejo): AZOTEA BACAL-2. Nombre completo: SUB ESTACION ELECTRICA CBA60-120-0050 0050.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-120-0073', 'SUB ESTACION ELECTRICA -1', 'SUBESTACIONES ELECTRICAS', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / ESTACIONAMIENTO', '2021-01-15', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 116, hoja STD). Codigo original: CBA60-120-0073. QR: CBA-60-0073. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: ENERGIA ELECTRICA. Area (viejo): AZOTEA BACAL-1. Nombre completo: SUB ESTACION ELECTRICA -1 CBA60-120-0073 0073.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-110-0034', 'TERMO TORTILLA AMARILLA', 'CONTENEDOR', 'ARTIC SATR', '70R-30R', '01-9955423', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 70, hoja STD). Codigo original: CBA60-110-0034. QR: CBA-60-0034. Clasif1: CONSERVACION. Clasif2: EXHIBIDOR. Centro costo: .. Area (viejo): PISO DE VENTA. Nombre completo: TERMO TORTILLA AMARILLA CBA60-110-0034 0034.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-110-0033', 'TERMO TORTILLA BLANCA', 'CONTENEDOR', 'ARTIC STAR', '70R-30R', '01-9955424', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 69, hoja STD). Codigo original: CBA60-110-0033. QR: CBA-60-0033. Clasif1: CONSERVACION. Clasif2: EXHIBIDOR. Centro costo: .. Area (viejo): PISO DE VENTA. Nombre completo: TERMO TORTILLA BLANCA CBA60-110-0033 0033.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-110-0145', 'TERMO TORTILLA VITRINA', 'CONTENEDOR', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / CAJAS', '2022-10-28', '2023-10-10', NULL, NULL, 'Migrado del sistema viejo (ID 280, hoja STD). Codigo original: CBA60-110-0145. Clasif1: CONSERVACION. Clasif2: EXHIBIDOR. Centro costo: .. Area (viejo): PISO VENTA. Nombre completo: TERMO TORTILLA VITRINA CBA60-110-0145 0145.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0129', 'UNIDAD CONDENSADORA FYV', 'UNIDAD CONDENSADORA', 'BOHN', 'MOZ055M63C', 'T144J14814', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA / MEZZANINE', '2020-12-03', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 10, hoja STD). Codigo original: CBA60-140-0129. QR: CBA-60-0129. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): MEZANINE EXTERIOR BACAL-2. Nombre completo: UNIDAD CONDENSADORA FYV CBA60-140-0129 0129.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0076', 'UNIDAD CONDENSADORA FYV-1', 'CONDENSADOR', NULL, NULL, '0080626AM3114', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA / MEZZANINE', '2021-01-26', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 125, hoja STD). Codigo original: CBA60-140-0076. QR: CBA-60-0076. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): MEZANINE EXTERIOR BACAL-2. Nombre completo: UNIDAD CONDENSADORA FYV-1 CBA60-140-0076 0076.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-028', 'UNIDAD CONDENSADORA PROCESAMIENTO', 'UNIDAD CONDENSADORA', 'BOHN', 'MOZ055M63C', 'T14J5127', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / AZOTEA / MEZZANINE', '2020-12-03', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 9, hoja STD). Codigo original: CBA60-140-028. QR: CBA-60-0128. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): MEZANINE EXTERIOR BACAL-2. Nombre completo: UNIDAD CONDENSADORA PROCESAMIENTO CBA60-140-028 0128.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0056', 'UNIDAD CONDENSADORA-4', 'UNIDAD CONDENSADORA', 'BOHN', 'MBDX0750L6D', 'M07A21095', 1, 'CARNES BACAL / EDIFICIO BACAL-1 / ANDEN', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 93, hoja STD). Codigo original: CBA60-140-0056. QR: CBA-60-0056. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): ANDEN BACAL-1. Nombre completo: UNIDAD CONDENSADORA-4 CBA60-140-0056 0056.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0054', 'UNIDAD CONDENSADORA-5', 'UNIDAD CONDENSADORA', 'BOHN', 'BDT1500L6D', 'T07A01580', 1, 'CARNES BACAL / EDIFICIO BACAL-1 / ANDEN', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 91, hoja STD). Codigo original: CBA60-140-0054. QR: CBA-60-0054. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): ANDEN BACAL-1. Nombre completo: UNIDAD CONDENSADORA-5 CBA60-140-0054 0054.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0055', 'UNIDAD CONDENSADORA-6', 'UNIDAD CONDENSADORA', 'BOHN', 'BDT1500L6D', 'T07A01579', 1, 'CARNES BACAL / EDIFICIO BACAL-1 / ANDEN', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 92, hoja STD). Codigo original: CBA60-140-0055. QR: CBA-60-0055. Clasif1: SUMINISTRO DE SERVICIO. Clasif2: SISTEMAS FRIGORIFICOS. Area (viejo): ANDEN BACAL-1. Nombre completo: UNIDAD CONDENSADORA-6 CBA60-140-0055 0055.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0020', 'VITRINA #1', 'EXHIBIDOR', 'KYSOR WARREN', 'PX2LN-08RUN', 'C14J01353', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / FRUTAS Y VERDURAS', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 30, hoja STD). Codigo original: CBA60-140-0020. QR: CBA-60-0020. Clasif1: CONSERVACION. Clasif2: REFRIGERACION. Area (viejo): FRUTAS Y LEGUMBRES. Nombre completo: VITRINA #1 CBA60-140-0020 0020.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0021', 'VITRINA #2', 'EXHIBIDOR', 'KYSOR WARREN', 'PX2LN-08RUN', 'C14J01354', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / FRUTAS Y VERDURAS', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 31, hoja STD). Codigo original: CBA60-140-0021. QR: CBA-60-0021. Clasif1: CONSERVACION. Clasif2: REFRIGERACION. Area (viejo): FRUTAS Y LEGUMBRES. Nombre completo: VITRINA #2 CBA60-140-0021 0021.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0022', 'VITRINA #3', 'EXHIBIDOR', 'KYSOR WARREN', 'PX2LN-12RUN', 'C14J01355', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / FRUTAS Y VERDURAS', '2021-01-15', '2023-10-13', NULL, NULL, 'Migrado del sistema viejo (ID 32, hoja STD). Codigo original: CBA60-140-0022. QR: CBA-60-0022. Clasif1: CONSERVACION. Clasif2: REFRIGERACION. Area (viejo): FRUTAS Y LEGUMBRES. Nombre completo: VITRINA #3 CBA60-140-0022 0022.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-110-0143', 'VITRINA DE LICOR', 'CONTENEDOR', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / CAJAS', '2022-10-28', '2023-10-03', NULL, NULL, 'Migrado del sistema viejo (ID 278, hoja STD). Codigo original: CBA60-110-0143. Clasif1: EXHIBIDOR. Clasif2: LICOR. Centro costo: .. Area (viejo): PISO DE VENTA. Nombre completo: VITRINA DE LICOR CBA60-110-0143 0143.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-110-0077', 'VITRINA DE PAN', 'MUEBLE', 'ESTRCUTURAL CONCEP', 'SS3630', '908210BW7825', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA', '2021-01-26', '2023-10-14', NULL, NULL, 'Migrado del sistema viejo (ID 126, hoja STD). Codigo original: CBA60-110-0077. QR: CBA-60-0077. Clasif1: EXHIBIDOR. Clasif2: PAN. Centro costo: .. Area (viejo): PISO DE VENTA. Nombre completo: VITRINA DE PAN CBA60-110-0077 0077.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0017', 'VITRINA GRAVEDAD #6', 'EXHIBIDOR', 'KYSOR WARREN', 'NS39S1-12UN', 'C14J00183', 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / QUESOS', '2020-12-06', '2023-10-05', NULL, NULL, 'Migrado del sistema viejo (ID 25, hoja STD). Codigo original: CBA60-140-0017. QR: CBA-60-0017. Clasif1: CONSERVACION. Clasif2: REFRIGERACION. Area (viejo): MOSTRADOR. Nombre completo: VITRINA GRAVEDAD #6 CBA60-140-0017 0017.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0094', 'VITRINA GRAVEDAD-1', 'REFRIGERADORES', 'KYSOR WARREN', 'NS39S1-12UN', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / MOSTRADOR CARNES', '2020-12-06', '2023-10-05', NULL, NULL, 'Migrado del sistema viejo (ID 159, hoja STD). Codigo original: CBA60-140-0094. QR: CBA-60-0094. Clasif1: CONSERVACION. Clasif2: REFRIGERACION. Area (viejo): MOSTRADOR. Nombre completo: VITRINA GRAVEDAD-1 CBA60-140-0094 0094.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0095', 'VITRINA GRAVEDAD-2', 'REFRIGERADORES', 'KYSOR WARREN', 'NS39S1-12UN', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / MOSTRADOR CARNES', '2020-12-06', '2023-10-05', NULL, NULL, 'Migrado del sistema viejo (ID 160, hoja STD). Codigo original: CBA60-140-0095. QR: CBA-60-0095. Clasif1: CONSERVACION. Clasif2: REFRIGERACION. Area (viejo): MOSTRADOR. Nombre completo: VITRINA GRAVEDAD-2 CBA60-140-0095 0095.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0096', 'VITRINA GRAVEDAD-3', 'REFRIGERADORES', 'KYSOR WARREN', 'NS39S1-12UN', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / MOSTRADOR CARNES', '2020-12-06', '2023-10-05', NULL, NULL, 'Migrado del sistema viejo (ID 161, hoja STD). Codigo original: CBA60-140-0096. QR: CBA-60-0096. Clasif1: CONSERVACION. Clasif2: REFRIGERACION. Area (viejo): MOSTRADOR. Nombre completo: VITRINA GRAVEDAD-3 CBA60-140-0096 0096.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0097', 'VITRINA GRAVEDAD-4', 'REFRIGERADORES', 'KYSOR WARREN', 'NS39S1-12UN', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / MOSTRADOR CARNES', '2020-12-06', '2023-10-05', NULL, NULL, 'Migrado del sistema viejo (ID 162, hoja STD). Codigo original: CBA60-140-0097. QR: CBA-60-0097. Clasif1: CONSERVACION. Clasif2: REFRIGERACION. Area (viejo): MOSTRADOR. Nombre completo: VITRINA GRAVEDAD-4 CBA60-140-0097 0097.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC60-140-0098', 'VITRINA GRAVEDAD-5', 'REFRIGERADORES', 'KYSOR WARREN', 'NS39S1-12UN', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / MOSTRADOR CARNES', '2020-12-06', '2023-10-05', NULL, NULL, 'Migrado del sistema viejo (ID 163, hoja STD). Codigo original: CBA60-140-0098. QR: CBA-60-0098. Clasif1: CONSERVACION. Clasif2: REFRIGERACION. Area (viejo): MOSTRADOR. Nombre completo: VITRINA GRAVEDAD-5 CBA60-140-0098 0098.', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC-60-0046', 'CARGADOR DE BATERIA', 'CARGADORES DE BATERIAS', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / ANDEN', '2021-01-15', '2022-10-24', NULL, NULL, 'Migrado del sistema viejo (ID 83, hoja ACR). Codigo original: CBA-60-0046. QR: CBA-60-0046. Clasif1: EN SERVICIO. Clasif2: SERVICIO. Centro costo: Piso de venta - Carniceria. Nombre completo: CARGADOR DE BATERIA [BCA-0054].', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC-60-0141', 'EXTRACTOR DE HUMO', 'EXTRACTORES DE AIRE', NULL, NULL, NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / EXTERIOR', '2021-03-19', '2022-10-27', NULL, NULL, 'Migrado del sistema viejo (ID 276, hoja ACR). Codigo original: CBA-60-0141. QR: CBA-60-0141. Clasif1: EN SERVICIO. Clasif2: COCINA. Centro costo: Piso de venta - Carniceria. Nombre completo: EXTRACTOR DE HUMO [BCA-0047].', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC-60-0009', 'REBANADORA #9', 'REBANADORA', 'GLOBE', '3600N', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-1 / EXTERIOR / TALLER MANTENIMIENTO', '2021-02-20', '2022-11-04', NULL, NULL, 'Migrado del sistema viejo (ID 142, hoja ACR). Codigo original: CBA-60-0009. QR: CBA-60-0009. Clasif1: FUERA DE SERVICIO. Clasif2: PRODUCCIÓN. Centro costo: Bacal - Piso de venta. Area (viejo): 1/2 H.P. Nombre completo: REBANADORA #9 [FT60-110-0009].', 1, 'en_uso');
INSERT INTO `equipos` (`codigo_inventario`,`nombre`,`tipo`,`marca`,`modelo`,`numero_serie`,`sucursal_id`,`ubicacion`,`fecha_compra`,`fecha_adquisicion`,`fecha_baja`,`motivo_baja`,`notas`,`activo`,`estado_vida`) VALUES
  ('BAC-60-0040', 'TURBOLICUADORA', 'LICUADORA', 'WARING', 'WSB60', NULL, 1, 'CARNES BACAL / EDIFICIO BACAL-2 / PRIMER PISO / PISO DE VENTA / COCINA', '2021-01-15', '2022-10-28', NULL, NULL, 'Migrado del sistema viejo (ID 77, hoja ACR). Codigo original: CBA-60-0040. QR: CBA-60-0040. Clasif1: FUERA DE SERVICIO. Clasif2: COCINA. Centro costo: Bacal - Piso de venta. Nombre completo: TURBOLICUADORA [BCA-0041].', 1, 'en_uso');

-- ---------------------------------------------------------------------------
-- HERRAMIENTAS (115) -- sucursal_id=1
-- ---------------------------------------------------------------------------
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000001', 'DESARMADOR DE CAJA DE ALTA RESISTENCIA DE 1/2\'\'', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000001.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000002', 'DESARMADOR DE CAJA DE ALTA RESISTENCIA DE 7/16\'\'', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000002.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000003', 'DESARMADOR DE CAJA DE ALTA RESISTENCIA DE 11/32\'\'', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000003.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000004', 'DESARMADOR DE CAJA DE ALTA RESISTENCIA DE 5/16\'\'', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000004.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000005', 'DESARMADOR DE CAJA DE ALTA RESISTENCIA DE 3/8\'\'', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000005.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000006', 'DESARMADOR DE CAJA DE ALTA RESISTENCIA DE 1/4\'\'', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000006.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000007', 'DESARMADOR DE CAJA DE ALTA RESISTENCIA DE 3/16\'\'', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000007.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000008', 'PINZA DE PRESIÓN PUNTA RECTA 10" MILWAUKEE', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000008.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000009', 'LLAVE AJUSTABLE 12"', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000009.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000010', 'LLAVE AJUSTABLE 10"', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000010.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000012', 'MULTÍMETRO DIGITAL DE GANCHO DE RANGO AUTOMÁTICO DE 400 A CA', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000012.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000011', 'PINZA DE CORTE DIAGONAL, LONGITUD TOTAL 8-1/16", MATERIAL ALEACIÓN DE ACERO', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000011.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000013', 'CAUTÍN DE ESTACIÓN MARCA WELLER, CON CONTROL DE TEMPERATURA DE HASTA 40 WATTS', NULL, 1, 'disponible', '2021-12-26', 'Unidad: PIEZA. Clave sistema viejo: 000000013.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000014', 'KLEIN TOOLS 603-7 DESARMADOR PUNTA PHILLIPS #2 DE 7” X 11-5/16” BARRA REDONDA', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000014.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000015', 'KLEIN TOOLS 600-4 DESARMADOR PUNTA PLANA DE 1/4" X 4" BARRA CUADRADA', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000015.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000016', 'KLEIN TOOLS 605-6 DESARMADOR PUNTA PLANA DE 1/4" X 6" BARRA CUADRADA', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000016.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000017', 'KLEIN TOOLS 603-4 DESARMADOR PUNTA PHILLIPS #2 DE 1/4” X 4"', NULL, 1, 'disponible', '2021-11-29', 'Unidad: PIEZA. Clave sistema viejo: 000000017.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000018', 'ROTOMARTILLO CINCELADOR MAKITA SDS PLUS HR2630', NULL, 1, 'disponible', '2021-12-26', 'Unidad: PIEZA. Clave sistema viejo: 000000018.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000019', 'KIT DE BROCAS, CINCELES Y BROQUERO SDS PLUS MAKITA', NULL, 1, 'disponible', '2021-12-26', 'Unidad: LOTE. Clave sistema viejo: 000000019.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000020', 'TORNILLO DE BANCO INDUSTRIAL DE ACERO TRUPER', NULL, 1, 'disponible', '2021-12-26', 'Unidad: PIEZA. Clave sistema viejo: 000000020.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000021', 'ESMERIL DE BANCO DE 6" DE 1/2 HP', NULL, 1, 'disponible', '2021-12-26', 'Unidad: PIEZA. Clave sistema viejo: 000000021.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000022', 'JUEGO DE NÚMEROS DE GOLPE, ALTURA DE CARACTERES 3/8", MATERIAL ACERO', NULL, 1, 'disponible', '2021-12-31', 'Unidad: JUEGO. Clave sistema viejo: 000000022.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000023', 'CINTA MÉTRICA, LONGITUD 10M, ANCHO 1-3/16"', NULL, 1, 'disponible', '2022-08-06', 'Unidad: PIEZA. Clave sistema viejo: 000000023.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000024', 'ARCO DE SEGUETA', NULL, 1, 'disponible', '2022-03-09', 'Unidad: PIEZA. Clave sistema viejo: 000000024.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000025', 'EXTRACTOR QUIJADA REVERSIBLE', NULL, 1, 'disponible', '2022-03-09', 'Unidad: PIEZA. Clave sistema viejo: 000000025.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000026', 'DADO MAGNETICO 3/8', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000026.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000027', '3" COMEX BROCHA PLUS', NULL, 1, 'baja', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000027.', 0);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000028', 'JUEGO DE LIMAS KIT 5 PIEZAS', NULL, 1, 'disponible', '2022-08-11', 'Unidad: LOTE. Clave sistema viejo: 000000028.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000029', 'JUEGO DE BROCAS 13 PIEZAS', NULL, 1, 'disponible', '2022-08-11', 'Unidad: JUEGO. Clave sistema viejo: 000000029.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000030', 'JUEGO DE MACHUELOS 9 PIEZAS', NULL, 1, 'disponible', '2022-08-11', 'Unidad: JUEGO. Clave sistema viejo: 000000030.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000031', 'TALADRO MAGNETICO', NULL, 1, 'baja', '2022-08-09', 'Unidad: PIEZA. Clave sistema viejo: 000000031.', 0);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000032', 'LLAVE ESPAÑOLA 7/8', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000032.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000033', 'LLAVE ESPAÑOLA 3/4', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000033.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000034', 'LLAVE ESPAÑOLA 11/16', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000034.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000035', 'LLAVE ESPAÑOLA 5/8', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000035.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000036', 'LLAVE ESPAÑOLA 9/16', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000036.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000037', 'LLAVE ESPAÑOLA 1/2', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000037.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000038', 'LLAVE ESPAÑOLA 3/8', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000038.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000039', 'LLAVE ESPAÑOLA 7/16', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000039.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000040', 'MARTILLO', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000040.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000041', 'LLAVE HEXAGONAL EN "T" 3/8', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000041.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000042', 'LLAVE HEXAGONAL EN "T" 5/16', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000042.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000043', 'LLAVE HEXAGONAL EN "T" 1/4', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000043.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000044', 'LLAVE HEXAGONAL EN "T" 7/32', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000044.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000045', 'LLAVE HEXAGONAL EN "T" 3/16', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000045.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000046', 'LLAVE HEXAGONAL EN "T" 5/32', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000046.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000047', 'LLAVE HEXAGONAL EN "T" 9/64', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000047.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000048', 'LLAVE HEXAGONAL EN "T" 1/8', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000048.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000049', 'LLAVE HEXAGONAL EN "T" 7/64', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000049.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000050', 'LLAVE HEXAGONAL EN "T" 3/32', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000050.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000051', 'LLAVE HEXAGONAL EN "T" 5/64', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000051.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000052', 'TALADRO INALAMBRICO DEWALT 1/2 (13MM) 20V 500 1750 RPM', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000052.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000053', 'ESMERILADORA 4-1/4 10000 RPM 840W 50/60HZ', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000053.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000054', 'PINZA PARA ANILLOS DE RETENCION INTERIORES ANGULO DE 0°', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000054.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000055', 'PINZA PARA ANILLOS DE RETENCION EXTERIORES ANGULO DE 0°', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000055.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000056', 'PINZA PARA ANILLOS DE RETENCION INTERIORES ANGULO DE 90°', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000056.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000057', 'PINZA PARA ANILLOS DE RETENCION EXTERIORES ANGULO DE 90°', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000057.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000058', 'JUEGO DE DADOS Y ACCESORIOS', NULL, 1, 'disponible', '2022-08-11', 'Unidad: JUEGO. Clave sistema viejo: 000000058.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000059', 'PINZA DE CORTE', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000059.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000060', 'PINZA DE PUNTA', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000060.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000061', 'PINZA DE ELECTRISISTA', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000061.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000062', 'PINZA DE MECANICO', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000062.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000063', 'JUEGO DE LLAVES ALLEN URREA', NULL, 1, 'disponible', '2022-08-11', 'Unidad: JUEGO. Clave sistema viejo: 000000063.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000064', 'JUEGO DE LLABVES TORX TRUPER', NULL, 1, 'disponible', '2022-08-11', 'Unidad: JUEGO. Clave sistema viejo: 000000064.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000065', 'LLAVE AJUSTABLE 8"', NULL, 1, 'disponible', '2022-08-11', 'Unidad: PIEZA. Clave sistema viejo: 000000065.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000066', 'PISTOLA DE CALOR DOBLE TEMPERATURA DE 11.6A, 120V, MILWUAKEE', NULL, 1, 'disponible', '2022-08-25', 'Unidad: PIEZA. Clave sistema viejo: 000000066.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000067', 'MÁQUINA DE SOLDAR INVERSORA 15A', NULL, 1, 'disponible', '2023-09-22', 'Unidad: PIEZA. Clave sistema viejo: 000000067.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000068', 'LLAVE COMBINADA 1/4\'\'', NULL, 1, 'disponible', '2023-09-22', 'Unidad: PIEZA. Clave sistema viejo: 000000068.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000069', 'JUEGO 7 PUNTAS DE ESTRELLA', NULL, 1, 'disponible', '2023-09-22', 'Unidad: JUEGO. Clave sistema viejo: 000000069.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000070', 'ARNÉS', NULL, 1, 'disponible', '2023-10-04', 'Unidad: PIEZA. Clave sistema viejo: 000000070.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000071', 'CASCO AMARILLO', NULL, 1, 'disponible', '2023-10-04', 'Unidad: PIEZA. Clave sistema viejo: 000000071.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000072', 'HIDROLAVADORA KARCHER', NULL, 1, 'disponible', '2023-10-17', 'Unidad: PIEZA. Clave sistema viejo: 000000072.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000073', 'PINZA P/PELAR CABLE 6" TRUPER', NULL, 1, 'disponible', '2023-10-17', 'Unidad: PIEZA. Clave sistema viejo: 000000073.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000074', 'PINZAS CORTA CANDADOS 30"', NULL, 1, 'disponible', '2023-10-17', 'Unidad: PIEZA. Clave sistema viejo: 000000074.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000075', 'GUANTES DE CAUCHO', NULL, 1, 'disponible', '2023-10-20', 'Unidad: GALON. Clave sistema viejo: 000000075.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000076', 'ARNESES DE CAIMANES MEDIANOS 5A, 43MM', NULL, 1, 'disponible', '2023-11-14', 'Unidad: LOTE. Clave sistema viejo: 000000076.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000077', 'CEPILLO DE BRONCE', NULL, 1, 'disponible', '2023-11-14', 'Unidad: PIEZA. Clave sistema viejo: 000000077.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000078', 'GOOGLES DE PROTECCIÓN', NULL, 1, 'disponible', '2023-11-15', 'Unidad: PIEZA. Clave sistema viejo: 000000078.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000079', 'CARETA DE PROTECCIÓN', NULL, 1, 'disponible', '2023-11-15', 'Unidad: PIEZA. Clave sistema viejo: 000000079.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000080', 'GUANTES DE PLÁSTICO LARGOS', NULL, 1, 'disponible', '2023-11-15', 'Unidad: PIEZA. Clave sistema viejo: 000000080.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000081', 'MASCARILLA P/GAS REUSABLE', NULL, 1, 'disponible', '2023-11-15', 'Unidad: PIEZA. Clave sistema viejo: 000000081.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000082', 'GUANTES DE CARNAZA', NULL, 1, 'disponible', '2023-11-15', 'Unidad: PIEZA. Clave sistema viejo: 000000082.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000083', 'PINZAS DE ELECTRICISTA ALTA PALANCA 9"', NULL, 1, 'disponible', '2024-01-05', 'Unidad: PIEZA. Clave sistema viejo: 000000083.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000084', 'CAJA DE HERRAMIENTA METÁLICA 40 X 18 X 16 CM', NULL, 1, 'disponible', '2024-01-18', 'Unidad: PIEZA. Clave sistema viejo: 000000084.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000085', 'PISTOLA DE GRAVEDAD LVLP 515', NULL, 1, 'disponible', '2024-01-29', 'Unidad: PIEZA. Clave sistema viejo: 000000085.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000086', 'COLADOR MÉTALICO CON REPUESTO', NULL, 1, 'disponible', '2024-01-29', 'Unidad: PIEZA. Clave sistema viejo: 000000086.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000087', 'LIJADORA ORBITAL MAKITA 12000 OPM', NULL, 1, 'disponible', '2024-02-27', 'Unidad: PIEZA. Clave sistema viejo: 000000087.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000088', 'SET 6 DESARMADORES TIPO RELOJERO', NULL, 1, 'disponible', '2024-03-05', 'Unidad: LOTE. Clave sistema viejo: 000000088.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000089', 'KIT 6 DESARMADORES TIPO RELOJERO', NULL, 1, 'disponible', '2024-03-05', 'Unidad: LOTE. Clave sistema viejo: 000000089.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000090', 'SONDA RIDGID MODELO: 58920 K-50 115V', NULL, 1, 'disponible', '2024-03-26', 'Unidad: PIEZA. Clave sistema viejo: 000000090.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000091', 'SET 12PZS DE LLAVES ALLEN PUNTA ESFÉRICA', NULL, 1, 'disponible', '2024-03-26', 'Unidad: KIT. Clave sistema viejo: 000000091.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000092', 'CORTADOR DE TUBO 1/8 A 1 1/4', NULL, 1, 'disponible', '2024-03-26', 'Unidad: PIEZA. Clave sistema viejo: 000000092.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000093', 'SET 4PZS DESARMADORES DE PRECISIÓN KLEIN TOOLS', NULL, 1, 'disponible', '2024-03-26', 'Unidad: KIT. Clave sistema viejo: 000000093.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000094', 'LLANA LISA', NULL, 1, 'disponible', '2024-05-31', 'Unidad: PIEZA. Clave sistema viejo: 000000094.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000095', 'JUEGO DE MANOMETROS ESTÁNDAR R22/R404/R410', NULL, 1, 'disponible', '2024-06-03', 'Unidad: JUEGO. Clave sistema viejo: 000000095.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000096', 'MANGUERAS ALTA PRESIÓN P/MANOMETROS', NULL, 1, 'disponible', '2024-06-03', 'Unidad: JUEGO. Clave sistema viejo: 000000096.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000097', 'POLYASPERSORA', NULL, 1, 'disponible', '2024-06-03', 'Unidad: PIEZA. Clave sistema viejo: 000000097.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000098', 'PUNTA PHILLIPS P/ DESARMADOR', NULL, 1, 'disponible', '2024-06-03', 'Unidad: PIEZA. Clave sistema viejo: 000000098.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000099', 'BROCA HSS1/4', NULL, 1, 'disponible', '2024-06-03', 'Unidad: PIEZA. Clave sistema viejo: 000000099.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000100', 'BROCA HSS 5/32', NULL, 1, 'disponible', '2024-06-03', 'Unidad: PIEZA. Clave sistema viejo: 000000100.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000101', 'BROCA HSS 9/64', NULL, 1, 'disponible', '2024-06-03', 'Unidad: PIEZA. Clave sistema viejo: 000000101.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000102', 'BROCA HSS 7/64', NULL, 1, 'disponible', '2024-06-03', 'Unidad: PIEZA. Clave sistema viejo: 000000102.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000103', 'BROCA HSS 3/32', NULL, 1, 'disponible', '2024-06-03', 'Unidad: PIEZA. Clave sistema viejo: 000000103.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000104', 'PINZA PORTA ELECTRODO', NULL, 1, 'disponible', '2024-06-03', 'Unidad: PIEZA. Clave sistema viejo: 000000104.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000105', 'LINTERNA MANOS LIBRES', NULL, 1, 'disponible', '2024-06-04', 'Unidad: PIEZA. Clave sistema viejo: 000000105.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000106', 'ESCUADRA C/NIVEL 12"', NULL, 1, 'disponible', '2024-06-25', 'Unidad: PIEZA. Clave sistema viejo: 000000106.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000107', 'PROBADOR DE CORRIENTE TIPO PLUMA', NULL, 1, 'disponible', '2024-07-30', 'Unidad: PIEZA. Clave sistema viejo: 000000107.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000108', 'MANGAS P/SOLDAR', NULL, 1, 'disponible', '2024-07-30', 'Unidad: GALON. Clave sistema viejo: 000000108.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000109', 'CARETA P/SOLDAR', NULL, 1, 'disponible', '2024-07-30', 'Unidad: PIEZA. Clave sistema viejo: 000000109.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000110', 'MANDIL DE CARNAZA', NULL, 1, 'disponible', '2024-07-30', 'Unidad: PIEZA. Clave sistema viejo: 000000110.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000111', 'DESARMADOR PUNTA PHILLIPS #2 X 4P', NULL, 1, 'disponible', '2024-08-19', 'Unidad: PIEZA. Clave sistema viejo: 000000111.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000112', 'LLAVE ALLEN PUNTA BOLA 1/4', NULL, 1, 'disponible', '2024-10-16', 'Unidad: PIEZA. Clave sistema viejo: 000000112.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000113', 'LLAVE ALLEN PAVONADA 1/4', NULL, 1, 'disponible', '2024-10-16', 'Unidad: PIEZA. Clave sistema viejo: 000000113.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000114', 'GUÍA JALA CABLE FIBRA DE VIDRIO 150M', NULL, 1, 'disponible', '2024-11-08', 'Unidad: PIEZA. Clave sistema viejo: 000000114.', 1);
INSERT INTO `herramientas` (`codigo`,`nombre`,`tipo`,`sucursal_id`,`estado`,`fecha_adquisicion`,`notas`,`activo`) VALUES
  ('HER-000000115', 'DADO MAGNETICO P/PIJA 5/16 X 2 9/16', NULL, 1, 'disponible', '2025-02-20', 'Unidad: PIEZA. Clave sistema viejo: 000000115.', 1);

-- ---------------------------------------------------------------------------
-- REFACCIONES / REPUESTOS (790)
-- ---------------------------------------------------------------------------
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000001', 'GALON WD-40', 'Ubicacion/Clasif1: LUBRICANTES. Clave sistema viejo: 000000001.', 'LUBRICANTES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-US10-15', 'BANDA BERKEL', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: US10-15.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000003', 'BALERO 6203.2RS 5/8"', 'Ubicacion/Clasif1: BALERO. Clave sistema viejo: 000000003.', 'BALERO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000004', 'PARO DE EMERGENCIA', 'Ubicacion/Clasif1: ELEVADOR. Clave sistema viejo: 000000004.', 'ELEVADOR', 'lote', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000005', 'BOTON VERDE ILUMINADO DE 22MM', 'Ubicacion/Clasif1: ELEVADOR. Clave sistema viejo: 000000005.', 'ELEVADOR', 'lote', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000006', 'TAPAS DE ALUMINO PARA BOTON ILUMIADO Y PARO DE EMERGENCIA', 'Ubicacion/Clasif1: ELEVADOR. Clave sistema viejo: 000000006.', 'ELEVADOR', 'lote', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000007', 'INTERRUPTOR DE LIMITE', 'Ubicacion/Clasif1: ELEVADOR. Clave sistema viejo: 000000007.', 'ELEVADOR', 'lote', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000008', 'CLAVIJA 30A, 3P-4H 250V, L15-30P LKG', 'Ubicacion/Clasif1: ELECTRICIDAD. Clasif2: EDIFICIO. Clave sistema viejo: 000000008.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000009', 'CONTACTO EXT 30A, 3P-4H 250V, L15-30R LKG', 'Ubicacion/Clasif1: ELECTRICIDAD. Clasif2: EDIFICIO. Clave sistema viejo: 000000009.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-US20A-107', 'TORNILLO CON RONDANA PARA REBANADORA', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: REBANADORAS. Clave sistema viejo: US20A-107.', 'REBANADORAS', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000011', 'GUIA DE PLASTICO', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000011.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-BB002-21V', 'GUIA DE METAL', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: BB002-21V.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-US13-31', 'RONDANA BERKEL', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: US13-31.', 'REFACCIONES (O)', 'juego', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000014', 'EJE LICUADORA INDUSTRIAL', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: LICUADORA INDUSTRIAL. Clave sistema viejo: 000000014.', 'LICUADORA INDUSTRIAL', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000015', 'CARBONES PARA MOLEDOR RUBI', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: MOLEDOR. Clave sistema viejo: 000000015.', 'MOLEDOR', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-US65A', 'ENSAMBLE DE PIEDRAS P/ASENTAR', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: US65A.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000311', 'CARRETE C/BALERO PARA CARRO SIERRA, NO. M-571839', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000311.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-US14-24', 'TAPON PARA REBANADORA BERKEL', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: US14-24.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000019', 'EMPAQUE DE PLASTICO', 'Ubicacion/Clasif1: ESTANTE 2. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000019.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-GS08-747B', 'ENGRANE DE BRONCE', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: GS08-747B.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-GS250HD', 'ENGRANE DE PLASTICO', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: GS250HD.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-US106', 'INTERRUPTOR DE ENCENDIDO', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: US106.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-BST-1', 'PIEDRA DE AFILAR BERKEL', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: BST-1.', 'REFACCIONES (O)', 'juego', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-GST-1', 'PIEDRA DE AFILAR GLOBE', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: GST-1.', 'REFACCIONES (O)', 'juego', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000025', 'BALERO 6013-2RSR-L038 C3', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000025.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000026', 'BALERO 6206-2RSR-L038 C3', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000026.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000027', 'BALERO 6205-2RSR-L038 C3', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000027.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000028', 'BALERO 6006-2RS C3', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000028.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000029', 'BALERO 6203-2RSR-L038 C3, 11/16', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000029.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000030', 'BALERO 6203-2RS 5/8', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000030.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-800092', 'BANDA GLOBE', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 800092.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000032', 'BANDA B-22', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000032.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000033', 'LIMITADOR SWITCH', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000033.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000034', 'TERMOMETRO DIGITAL', 'Clave sistema viejo: 000000034.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000035', 'INTERRUPTOR DE PRESION', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000035.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000036', 'CONTACTO DUPLEX DE PARED 120V', 'Ubicacion/Clasif1: ELECTRICIDAD. Clasif2: EDIFICIO. Clave sistema viejo: 000000036.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000037', 'INTERRUPTOR DE CORTINA METALICA', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000037.', 'ELÉCTRICO (D)', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000038', 'BOMBILLA 40W', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000038.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000039', 'VALVULA DE LLENADO PARA SANITARIO 7/8"', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000039.', 'PLOMERÍA (L)', 'juego', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000040', 'REGULADOR DE CORRIENTE 5-11/16"', 'Ubicacion/Clasif1: PLOMERIA. Clasif2: EDIFICIO. Clave sistema viejo: 000000040.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000041', 'PERA DE DESCARGA', 'Ubicacion/Clasif1: PLOMERIA. Clasif2: EDIFICIO. Clave sistema viejo: 000000041.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000042', 'TAPA CIEGA PLASTICA', 'Ubicacion/Clasif1: ELECTRICIDAD. Clasif2: EDIFICIO. Clave sistema viejo: 000000042.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000043', 'PASADOR DE SEGURIDAD', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000043.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000044', 'PLATE CHOPPER 3/8 #32', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: MOLINO. Clave sistema viejo: 000000044.', 'MOLINO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000045', 'PLATE CHOPPER 1/2 #32', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: MOLINO. Clave sistema viejo: 000000045.', 'MOLINO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000046', 'PLATE CHOPPER 1/8 #32', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: MOLINO. Clave sistema viejo: 000000046.', 'MOLINO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000047', 'PLATE CHOPPER 3/16 #32', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: MOLINO. Clave sistema viejo: 000000047.', 'MOLINO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000048', 'LLANTA CARRITO DE TIENDA', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: CARRITO DE TIENDA. Clave sistema viejo: 000000048.', 'CARRITO DE TIENDA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000049', 'CERRADURA DE MANIJA', 'Ubicacion/Clasif1: CERRAJERIA. Clasif2: EDIFICIO. Clave sistema viejo: 000000049.', 'EDIFICIO', 'juego', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000050', 'LLAVE MEZCLADORA P/LAVABO 4"', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000050.', 'PLOMERÍA (L)', 'juego', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000051', 'BOTON DE PARO', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000051.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000052', 'LAMPARA F32T8 32W', 'Ubicacion/Clasif1: ELECTRICIDAD. Clasif2: EDIFICIO. Clave sistema viejo: 000000052.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000053', 'LAMPARA F96T8 58W', 'Ubicacion/Clasif1: ELECTRICIDAD. Clasif2: EDIFICIO. Clave sistema viejo: 000000053.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000054', 'LAMPARA L 70W 8F', 'Ubicacion/Clasif1: ELECTRICIDAD. Clasif2: EDIFICIO. Clave sistema viejo: 000000054.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000055', 'SEPARADOR DE ACEITE Y AIRE', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000055.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000056', 'LAMPARA FLUORECENTE FB32T 32W', 'Ubicacion/Clasif1: ELECTRICIDAD. Clasif2: EDIFICIO. Clave sistema viejo: 000000056.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000057', 'LAMPARA FLUORECENTE FB31T8 31W', 'Ubicacion/Clasif1: ELECTRICIDAD. Clasif2: EDIFICIO. Clave sistema viejo: 000000057.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-160J6', 'BANDA BIZERBA', 'Ubicacion/Clasif1: BANDA. Clasif2: REBANADORAS. Clave sistema viejo: 160J6.', 'REBANADORAS', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000059', 'TORNILLO HEX 1/4 * 1-1/2', 'Clave sistema viejo: 000000059.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000060', 'TORNILLO HEX 1/4 * 2-1/2', 'Clave sistema viejo: 000000060.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000061', 'TORNILLO HEX 5/16 * 1-1/2', 'Clave sistema viejo: 000000061.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000062', 'TORNILLO HEX 5/16 * 2-1/2', 'Clave sistema viejo: 000000062.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000063', 'TORNILLO HEX 3/8 * 1-1/2', 'Clave sistema viejo: 000000063.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000064', 'TORNILLO HEX 3/8 * 2-1/2', 'Clave sistema viejo: 000000064.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000065', 'TORNILLO ALLEN SOCKET 1/4-20 * 1-1/2', 'Clave sistema viejo: 000000065.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000066', 'TORNILLO ALLEN SOCKET 1/4-20 * 2-1/2', 'Clave sistema viejo: 000000066.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000067', 'TORNILLO ALLEN SOCKET 5/16-18 * 1-1/2', 'Clave sistema viejo: 000000067.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000068', 'TORNILLO ALLEN SOCKET 5/16-18 * 2-1/2', 'Clave sistema viejo: 000000068.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000069', 'TORNILLO ALLEN SOCKET 3/8-16 * 1-1/2', 'Clave sistema viejo: 000000069.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000070', 'TORNILLO ALLEN SOCKET 3/8-16 * 2-1/2', 'Clave sistema viejo: 000000070.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000071', 'RONDANA PLANA 1/4', 'Clave sistema viejo: 000000071.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000072', 'RONDANA PLANA 5/16', 'Clave sistema viejo: 000000072.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000073', 'RONDANA PLANA 3/8', 'Clave sistema viejo: 000000073.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000074', 'RONDANA PRESION 1/4', 'Clave sistema viejo: 000000074.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000075', 'RONDANA PRESION 5/16', 'Clave sistema viejo: 000000075.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000076', 'RONDANA PRESION 3/8', 'Clave sistema viejo: 000000076.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000077', 'TUERCA HEX 1/4 SEGURIDAD FLANGE C/100', 'Clave sistema viejo: 000000077.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000078', 'TUERCA HEX 5/16 SEGURIDAD FLANGE C/50', 'Clave sistema viejo: 000000078.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000079', 'TUERCA HEX 3/8 SEGURIDAD FLANGE C/50', 'Clave sistema viejo: 000000079.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000080', 'GAS PROPANO', 'Clave sistema viejo: 000000080.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000081', 'EXTRACTOR DE TECHO/PARESD 80 CFM S/LUZ', 'Ubicacion/Clasif1: INSTALACION. Clasif2: EDIFICIO. Clave sistema viejo: 000000081.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000082', 'RACK TIPO BANCA - 37 X 19" CON GAVETAS DE 5 1/2 X 4 X 3"', 'Ubicacion/Clasif1: MUEBLE. Clasif2: EDIFICIO. Clave sistema viejo: 000000082.', 'EDIFICIO', 'juego', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-US08310', 'PERILLA P/PLATO DISCO', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: US08310.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000084', 'EMPAQUE DE BRONZE', 'Ubicacion/Clasif1: ESTANTE 2. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000084.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000085', 'DISCO BERKEL', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: REBANADORAS. Clave sistema viejo: 000000085.', 'REBANADORAS', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000086', 'PLATE CHOPPER #32', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: MOLINO. Clave sistema viejo: 000000086.', 'MOLINO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000087', 'PLATE CHOPPER 3/8 #12', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: MOLINO. Clave sistema viejo: 000000087.', 'MOLINO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000088', 'PLATE CHOPPER 3/16 #22', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: MOLINO. Clave sistema viejo: 000000088.', 'MOLINO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000089', 'PLATE CHOPPER #22', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: MOLINO. Clave sistema viejo: 000000089.', 'MOLINO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000090', 'WD-40', 'Ubicacion/Clasif1: LUBRICANTES. Clave sistema viejo: 000000090.', 'LUBRICANTES', 'galon', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000091', 'ROLLO DE MEMBRANA 1.1 X 100 MTS', 'Clave sistema viejo: 000000091.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000092', 'CUBETA DE IMPERMEABLIZANTE', 'Clave sistema viejo: 000000092.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000093', 'RODILLO PARA PINTAR 9"', 'Clave sistema viejo: 000000093.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000094', 'MANERAL PARA RODILLO 9"', 'Clave sistema viejo: 000000094.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000095', 'JALADERA PARA PUERTA', 'Ubicacion/Clasif1: CERRAJERIA. Clasif2: EDIFICIO. Clave sistema viejo: 000000095.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000096', 'CERRADURA DEXTER PARA PUERTA', 'Ubicacion/Clasif1: CERRAJERIA. Clasif2: EDIFICIO. Clave sistema viejo: 000000096.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000097', 'CINTO PLASTICO', 'Clave sistema viejo: 000000097.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000098', 'BROCHA PARA PINTAR 3"', 'Clave sistema viejo: 000000098.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000099', 'LLAVE PARA ZINK', 'Clave sistema viejo: 000000099.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000100', 'TORNILLO HEX 1/4-20 * 1-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000100.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000101', 'TORNILLO HEX STD 1/4-20 * 2-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000101.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000102', 'TORNILLO HEX STD 5/16 * 1-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000102.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000103', 'TORNILLO HEX STD 5/16 * 2-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000103.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000104', 'TORNILLO HEX STD 3/8 * 2-1/2"', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000104.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000105', 'TORNILLO HEX STD 3/8 * 1-1/G-8', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000105.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000106', 'TORNILLO ALLEN STD NEGR 1/2 * 1-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000106.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000107', 'TORNILLO ALLEN STD NEGR 1/4-20 * 2-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000107.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000108', 'TORNILLO ALLEN STD NEGR 5/16-18 * 1-1/2"', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000108.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000109', 'TORNILLO ALLEN STD NEGR 5/16-18 * 2-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000109.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000110', 'TORNILLO ALLEN STD NEGR 3/8-16 * 1-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000110.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000111', 'TORNILLO ALLEN STD NEGR 3/8-16 * 2-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000111.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000112', 'RONDANA PLANA 1/4', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: RONDANA. Clave sistema viejo: 000000112.', 'RONDANA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000113', 'RONDANA PLANA 5/16', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: RONDANA. Clave sistema viejo: 000000113.', 'RONDANA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000114', 'RONDANA PLANA 3/8', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: RONDANA. Clave sistema viejo: 000000114.', 'RONDANA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000115', 'RONDANA PRESION 1/4', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: RONDANA. Clave sistema viejo: 000000115.', 'RONDANA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000116', 'RONDANA PRESION 5/16', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: RONDANA. Clave sistema viejo: 000000116.', 'RONDANA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000117', 'RONDANA PRESION 3/8', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: RONDANA. Clave sistema viejo: 000000117.', 'RONDANA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000118', 'GAS PROPANO', 'Ubicacion/Clasif1: GAS. Clave sistema viejo: 000000118.', 'GAS', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000119', 'CHILILLO PAN HEAD #10*1', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000119.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000120', 'CHILILLO PAN HEAD #10*1-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000120.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000121', 'CHILILLO PAN HEAD #10*2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000121.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000122', 'CHILILLO PAN HEAD #10*2-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000122.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000123', 'CHILILLO PAN HEAD #14*1', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000123.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000124', 'CHILILLO PAN HEAD #14*1-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000124.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000125', 'CHILILLO PAN HEAD #14*2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000125.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000126', 'CHILILLO PAN HEAD #14*2-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000126.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000127', 'CHILILLO CABEZON 8-18 * 1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000127.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000128', 'CHILILLO CABEZON P/BROCA 3/4 C/1000', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000128.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000129', 'CARRETE DE MANGUERA DE RETORNO 1/2 * 35 FT 300 PSI', 'Ubicacion/Clasif1: PLOMERIA. Clasif2: EDIFICIO. Clave sistema viejo: 000000129.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000130', 'TUERCA HEX 1/4 SEGURIDAD FLANGE', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000130.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000131', 'TUERCA HEX 5/16 SEGURIDAD FLANGE', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000131.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000132', 'TUERCA HEX 3/8 SEGURIDAD FLANGE', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000132.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000133', 'MANGUERA FLEXIBLE 1/2" X 36" HEMBRA-HEMBRA', 'Ubicacion/Clasif1: PLOMERIA. Clasif2: EDIFICIO. Clave sistema viejo: 000000133.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000001-D1', 'BANDA TEFLON 750 MM', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000001.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000135', 'DISCO GLOBE', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: REBANADORAS. Clave sistema viejo: 000000135.', 'REBANADORAS', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000136', 'TORNILLO EXPANSOR 3/8 X 3 3/4', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000136.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000137', 'EMBUDO #32 ALUMINIO. TUBO DIAMETRO 1/2" X 4 LARGO', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: MOLINO. Clave sistema viejo: 000000137.', 'MOLINO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000138', 'TERMOSTATO 150°C', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000138.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000139', 'NIPLE BOTELLA GALV 1/2 MUELLER', 'Ubicacion/Clasif1: PLOMERIA. Clasif2: EDIFICIO. Clave sistema viejo: 000000139.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000140', 'RED BUSHING GALV 3/4 X 1/2 MUELLER', 'Ubicacion/Clasif1: PLOMERIA. Clasif2: EDIFICIO. Clave sistema viejo: 000000140.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000141', 'PISTOLA METALICA PARA ROCIADO', 'Ubicacion/Clasif1: PLOMERIA. Clasif2: EDIFICIO. Clave sistema viejo: 000000141.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000142', 'TAQUETE PLOMO 3/8 X 2', 'Ubicacion/Clasif1: ESTANTE 2. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000142.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000143', 'PIJA HEX 3/8 X 2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000143.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000144', 'RODILLO PARA PINTAR', 'Ubicacion/Clasif1: PINTURA. Clasif2: EDIFICIO. Clave sistema viejo: 000000144.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000135-D1', 'DISCO GLOBE', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: REBANADORAS. Clave sistema viejo: 000000135.', 'REBANADORAS', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-S00422', 'LAMPARA LED A PRUEBA DE VAPOR 40W 4000L 65K C/FOCO', 'Ubicacion/Clasif1: ELECTRICIDAD. Clasif2: EDIFICIO. Clave sistema viejo: S00422.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000147', 'PRUEBA', 'Ubicacion/Clasif1: PINTURA. Clave sistema viejo: 000000147.', 'PINTURA', 'lote', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000148', 'TIRAS DE VINIL PARA PUERTA', 'Ubicacion/Clasif1: CORTINA. Clasif2: EDIFICIO. Clave sistema viejo: 000000148.', 'EDIFICIO', 'kit', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000149', 'CADENA INDUSTRIAL DE RODILLOS PASO 40', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: BATIDORA. Clave sistema viejo: 000000149.', 'BATIDORA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000150', 'BANDA B-54', 'Ubicacion/Clasif1: BANDA. Clasif2: BATIDORA. Clave sistema viejo: 000000150.', 'BATIDORA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000151', 'TORNILLO CABEZA DE GOTA 2 X 5/16 "', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000151.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000152', 'PORTACANADADO 2"', 'Ubicacion/Clasif1: SEGURIDAD. Clasif2: EDIFICIO. Clave sistema viejo: 000000152.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000153', 'CARBONES #268-C', 'Ubicacion/Clasif1: ELECTRICIDAD. Clasif2: MOTOR. Clave sistema viejo: 000000153.', 'MOTOR', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000154', 'TORNILLO HEX 1/4-20 * 2-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000154.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000155', 'TORNILLO HEX STD 5/16 * 1-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000155.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000156', 'TAQUETE DE PLASTICO', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TAQUETE. Clave sistema viejo: 000000156.', 'TAQUETE', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000157', 'PLACA DE PARED CIEGA GALVANIZADA', 'Ubicacion/Clasif1: ELECTRICIDAD. Clasif2: EDIFICIO. Clave sistema viejo: 000000157.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000158', 'TORNILLO OPRESOR 1/2"', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: TORNILLO. Clave sistema viejo: 000000158.', 'TORNILLO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000159', 'COPLE GALVANIZADO 1/2"', 'Ubicacion/Clasif1: PLOMERIA. Clasif2: EDIFICIO. Clave sistema viejo: 000000159.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000160', 'GUSANO #48 PARA MOLINO BIRO', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000160.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-US118A', 'ENSAMBLE DE POLEA BERKEL 909', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: US118A.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000162', 'RUEDAS DE 8X2 POLIURETANO ROJO C/BALERO DE 3/4', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000162.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000163', 'BUJE METALICO REDUCTOR DE 1/2', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000163.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000164', 'TORNILLO C/TUERCA DE 1/2X2-7/16', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000164.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000165', 'SOCKET PARA LICUADORA VITAMIX', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: LICUADORA. Clave sistema viejo: 000000165.', 'LICUADORA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-C40-1', 'CANDADO', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: C40-1.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000167', 'CARRETE MANGUERA 1/2 X 35 FT 300 PSR', 'Ubicacion/Clasif1: PLOMERIA. Clasif2: EDIFICIO. Clave sistema viejo: 000000167.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000168', 'TAPA CIEGA METALICA 4X4', 'Ubicacion/Clasif1: ELECTRICIDAD. Clasif2: EDIFICIO. Clave sistema viejo: 000000168.', 'EDIFICIO', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000169', 'PUSH BUTTON N/O 22 MM', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000169.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000170', 'TUERCA HEX 5/16', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000170.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000171', 'TAQUETE PLASTICO 5/16', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000171.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000172', 'CHILILLO GOTA CRUZ #12 * 1-1/4', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000172.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000173', 'TAQUETE PLASTICO 3/8', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000173.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000174', 'CHILILLO GOTA CRUZ #14 * 1-1/2', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000174.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000175', 'TAPA CIEGA METALICA 2X4', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000175.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000176', 'CAJA STEEL 2X4', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000176.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-US36A', 'ENSAMBLE DE POLEA BERKEL 808', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: US36A.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000178', 'CLAVIJA C/TIERRA 125 V', 'Ubicacion/Clasif1: ELECTRICIDAD. Clave sistema viejo: 000000178.', 'ELECTRICIDAD', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-AN38334', 'ANCLA P-CONCRETO 3/8 X 3"', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: AN38334.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000180', 'VENTILADOR D/AIRE, AMP OP 4.2/3.5, 1/2 HP', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000180.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000181', 'VENTILADOR D/AIRE, AMP OP 4.2/3.5, 1/2 HP', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000181.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000182', 'BANDA TEFLON 32 1/2 X 210 1/4', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000182.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000183', 'TACOMETRO DIGITAL', 'Clave sistema viejo: 000000183.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000184', 'TERMOMETRO TERMOPAR', 'Clave sistema viejo: 000000184.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000185', 'INTERRUPTOR MAGNETICO P/PUERTAS O VENTANAS', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000185.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-18-A38A', 'KIT SLOAN PARA FLUXOMETRO A38A', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 18-A38A.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-DCT2', 'DUCT TAPE 2" 60 YDS', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: DCT2.', 'ELÉCTRICO (D)', 'metro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-TQIMP', 'TAQUETE IMPACTO 1/4 X 2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: TQIMP.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-AN12414', 'ANCLA P-CONCRETO 1/2" * 4-1/4"', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: AN12414.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000190', 'CHUM.UCF206-20', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000190.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000191', 'FILTRO MICRONICO', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000191.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000192', 'FILTRO REGULADO', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000192.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000193', 'EMERSON FISHER REGULADOR DE GAS R622-DFF 9-13" WC 3/4 X 3/4', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000193.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000194', 'GLASS CLOTH 3M 3/4 X 66FT', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000194.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000195', 'CINCHO PLASTICO NATURAL DE 10 CM', 'Clave sistema viejo: 000000195.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000196', 'CINCHO PLASTICO NATURAL DE 20CM', 'Clave sistema viejo: 000000196.', NULL, 'bolsa', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000197', 'MINIPEEPER SENSOR UV 1/2"', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000197.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000198', 'SWITCH DE PRESION PARA BOMBA DE AGUA', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000198.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000199', 'BANDA B-29', 'Clave sistema viejo: 000000199.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000200', 'QUEMADOR TIPO JET DE 32 ESPREAS GSW', 'Ubicacion/Clasif1: REFACCIONES. Clave sistema viejo: 000000200.', 'REFACCIONES', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-TR27', 'TUERCA PARA FLECHA INF. SIERRA JR', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: SIERRA. Clave sistema viejo: TR27.', 'SIERRA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-TR29', 'RONDANA PARA FLECHA INF. SIERRA JR', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: SIERRA. Clave sistema viejo: TR29.', 'SIERRA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-TR12', 'SEGURO PARA FLECHA INF. SIERRA JR', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: SIERRA. Clave sistema viejo: TR12.', 'SIERRA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-BSK1', 'DISCO PARA REABANADORA BERKEL', 'Ubicacion/Clasif1: Rack 2/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: BSK1.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-571832', 'FLECHA INFERIOR DE SIERRA JR', 'Ubicacion/Clasif1: REFACCIONES. Clasif2: SIERRA. Clave sistema viejo: 571832.', 'SIERRA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000206', 'BANDA A-20', 'Clave sistema viejo: 000000206.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000207', 'BANDA A-23', 'Clave sistema viejo: 000000207.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000208', 'BANDA B-28', 'Clave sistema viejo: 000000208.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000209', 'BANDA B-27', 'Clave sistema viejo: 000000209.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000210', 'BANDA BERKEL 909/919', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000210.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000211', 'VENTILADOR OSCILANTE DE PARED INDUSTRIAL 1/4 HP', 'Ubicacion/Clasif1: VENTILADOR. Clave sistema viejo: 000000211.', 'VENTILADOR', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000212', 'CIERRA PUERTA', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000212.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000213', 'TRAMPA FLEXIBLE', 'Clave sistema viejo: 000000213.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000214', 'CONECTOR ESPIGA 1/2 BRONCE', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000214.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000215', 'ABRAZADERA 3/4 - 1-1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000215.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000216', 'MEZCLADORA PARA FREGADERO DE 8"', 'Clave sistema viejo: 000000216.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000217', 'LAVABO BLANCO', 'Clave sistema viejo: 000000217.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000218', 'P.O PLUG METALICO', 'Clave sistema viejo: 000000218.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000219', 'CUBRE TALADRO P-SING', 'Clave sistema viejo: 000000219.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000220', 'ROLLO DE HULE 8\' X 150\'\'', 'Clave sistema viejo: 000000220.', NULL, 'pie', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000221', 'SINCHO PLASTICO #11', 'Clave sistema viejo: 000000221.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000222', 'POLEA DE MOTOR P/REBANADORA 2375-0025', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000222.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000223', 'DISCO PARA REABANADORA GLOBE #ITEM: GSK5', 'Ubicacion/Clasif1: Rack 2/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000223.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000224', 'RUEDA P/PLATAFORMA ULINE', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000224.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000225', 'ESPEJO DE SEGURIDAD CONVEXO JUMBO', 'Clave sistema viejo: 000000225.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000226', 'ESPEJO DE SEGURIDAD CONVEXO', 'Clave sistema viejo: 000000226.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000227', 'BALERO P/DIABLITO 3/4', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000227.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000228', 'WEG MOTOR ELECTRICO 1.5HP, 1800RPM, FRAME 56, TEFC 1PH', 'Ubicacion/Clasif1: Rack 3/ Nivel D. Clasif2: MOTORES (I). Clave sistema viejo: 000000228.', 'MOTORES (I)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000229', 'LIJA PLOMERO 10 YDS', 'Clave sistema viejo: 000000229.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000230', 'SILICON GRIS', 'Clave sistema viejo: 000000230.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000231', 'TAPA ALARGADA PARA WC', 'Clave sistema viejo: 000000231.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000232', 'DUCT TAPE 2"', 'Clave sistema viejo: 000000232.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000233', 'MASKING TAPE AZUL 2"', 'Clave sistema viejo: 000000233.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000234', 'DISCO DE CORTE 4-1/2 * 7/8', 'Clave sistema viejo: 000000234.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000235', 'CLAVIJA AMARILLA 515 PV', 'Clave sistema viejo: 000000235.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000236', 'BASE C/TORNILLO SUJETADOR TAPA DE DISCO', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000236.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000237', 'ASPAS LI-17', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000237.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000238', 'ABRAZADERA 8-12 MM', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000238.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000239', 'ABRAZADERA 10-16 MM', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000239.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000240', 'ABRAZADERA 13-19 MM', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000240.', 'TORNILLERIA (P)', 'juego', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000241', 'ABRAZADERA 16-25 MM', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000241.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000242', 'ABRAZADERA 19-29 MM', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000242.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000243', 'ABRAZADERA 18-32 MM', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000243.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000244', 'ABRAZADERA 21-38 MM', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000244.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000245', 'ABRAZADERA 21-44 MM', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000245.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000246', '3 1/2 X 5/8 POLEA', 'Clave sistema viejo: 000000246.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000247', 'MANGUERA ROJA 200 PSI 1/2"', 'Clave sistema viejo: 000000247.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000248', '1/4 PTA PEGAMENTO PVC 4 OZ 118ML', 'Clave sistema viejo: 000000248.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000249', 'COPLE 3/4" PVC', 'Clave sistema viejo: 000000249.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000250', 'CODO 90 X 3/4" PVC', 'Clave sistema viejo: 000000250.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000251', 'BUSHING PARA CARRO REBANADORA BERKEL', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000251.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000252', 'TORNILLO CABEZA HEX 3/8 X 2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000252.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000253', 'CHILILLO P/MADERA #1', 'Clave sistema viejo: 000000253.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000254', 'EXTENSION REFORZADA', 'Clave sistema viejo: 000000254.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000255', 'ANILLO DE CERA C/GUIA', 'Clave sistema viejo: 000000255.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000256', 'JUEGO DE HERRAJE PARA WC', 'Clave sistema viejo: 000000256.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000257', 'ANCLA PARA WC', 'Clave sistema viejo: 000000257.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000258', 'BALERO 6005', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000258.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000259', 'MACHUELO 1/4 NPT', 'Clave sistema viejo: 000000259.', NULL, 'pie', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000260', 'MACHUELO 5/16-18', 'Clave sistema viejo: 000000260.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000261', 'BROCA HS #7', 'Clave sistema viejo: 000000261.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000262', 'BROCA PARA METAL #F', 'Clave sistema viejo: 000000262.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000263', '4 VIB ANTIB SAT CC 764 BLANCO OSTION LIN', 'Clave sistema viejo: 000000263.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000264', '4 FLASH COAT NF CC 114 AMARILLO LI', 'Clave sistema viejo: 000000264.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000265', '3/4" MASKING AZUL 3M', 'Clave sistema viejo: 000000265.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000266', '9" X 1/4" REP POLI QUALI', 'Clave sistema viejo: 000000266.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000267', 'THINNER STD PET', 'Clave sistema viejo: 000000267.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000268', 'LLANTA NEUMATICA ANTIPONCHADURAS 10" X 3 1/2"', 'Clave sistema viejo: 000000268.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000269', 'ACEITE HIDRAULICO AW 68', 'Ubicacion/Clasif1: RACK 4/ NIVEL C. Clasif2: LUBRICANTES (G). Clave sistema viejo: 000000269.', 'LUBRICANTES (G)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000270', '3/8 X 12 CUÑA', 'Clave sistema viejo: 000000270.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000271', 'CHAPA 715 PHILLIPS TIPO COMERCIAL', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000271.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000272', 'LLAVE MEZCLADORA P/FREGADERO TRUPER 8"', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000272.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000273', 'BANDA B-23', 'Clave sistema viejo: 000000273.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000274', 'PISTOLA PLASTICA 2 FUNCIONES', 'Clave sistema viejo: 000000274.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000275', 'CONECTOR MANGUERA 1/2 MAC/PL TRUPER 12710', 'Clave sistema viejo: 000000275.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000276', 'BOTON PARA ELEVADOR', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000276.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000277', 'APAGADOR SENCILLO', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000277.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000278', 'TAPA PARA CONTACTO 4X4', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000278.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000279', 'CONTACTO DOBLE LEVITON BLANCO', 'Clave sistema viejo: 000000279.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000280', 'CLAVIJA LEVITON AMARILLA 110V', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000280.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000281', 'VENTILADOR PORTATIL DE PISO 18"', 'Ubicacion/Clasif1: Rack 2/ Nivel A. Clasif2: MOTORES (I). Clave sistema viejo: 000000281.', 'MOTORES (I)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000282', 'CABLE USO RUDO 3 X 12', 'Clave sistema viejo: 000000282.', NULL, 'metro', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000283', 'PERILLA PARA BAÑO SIN LLAVE PHILLIPS', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000283.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000284', 'BALERO P/DIABLITO 5/8', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000284.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000285', 'VENTILADOR OSILATORIO MYTEK 18" CON 3 VELOCIDADES, EMPOTRADO 3196', 'Ubicacion/Clasif1: Rack 2/ Nivel A. Clasif2: MOTORES (I). Clave sistema viejo: 000000285.', 'MOTORES (I)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000286', 'DESPACHADOR AUTOMATICO DE TOALLAS DE PAPEL KIMBERLY-CLARK', 'Clave sistema viejo: 000000286.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000287', 'CONTACTO PLASTICO', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000287.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000288', 'CERRADURA DE BARRA', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000288.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-H1050', 'PISTOLA HOTSY 5000 PSI', 'Ubicacion/Clasif1: Rack 3/ Nivel A. Clasif2: PLOMERÍA (L). Clave sistema viejo: H1050.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000290', 'CUCHILLA REBANADORA LION', 'Ubicacion/Clasif1: Rack 2/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000290.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000291', '4 VIA COLOR B/S LF ROJO', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000291.', 'PINTURAS (K)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000292', 'JUEGO LLAVE ALLEN STANDAR', 'Clave sistema viejo: 000000292.', NULL, 'juego', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-4975-00389', 'MOTOR REBANADORA BERKEL 808', 'Ubicacion/Clasif1: Rack 3/ Nivel D. Clasif2: MOTORES (I). Clave sistema viejo: 4975-00389.', 'MOTORES (I)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000294', 'RONDANA PLANA 1/2', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000294.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000295', 'RONDANA PLANA 3/8', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000295.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000296', 'RONDANA PLANA 5/16', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000296.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000297', 'TORNILLO PAN HEAD P/BROCA #8 X 3/4', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000297.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000298', 'TORNILLO PAN HEAD P/ DE BROCA #8 X 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000298.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000299', 'TAQUETE PLASTICO AZUL #12 X 1/4', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000299.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000300', 'OPRESOR 1/4-20 X 3/8', 'Ubicacion/Clasif1: ESTANTE 2. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000300.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000301', 'CONTACTO LEVITON 110V AMARILLO', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000301.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000302', 'CERROJO LLAVE DE 1-1/4" A 1-3/4"', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000302.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000303', 'MANGUERA PARA COMPRESORES TIPO RESORTE 1/4" X 15M', 'Clave sistema viejo: 000000303.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000304', 'PISTOLA METALICA PARA SOPLETEAR CON 2 BOQUILLAS', 'Clave sistema viejo: 000000304.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000305', 'CONECTOR MACHO 1/4', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000305.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000306', 'REPARACION DE MOTOR DE 1/3 HP CAMBIO DE PLACA DE PLATINOS Y LA FLECHA DEL ROTOR', 'Clave sistema viejo: 000000306.', NULL, 'servicio', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000307', 'HORQUILLA DE TENSOR PARA SIERRA JR, NO. M571536', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000307.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000308', 'TIRAS EN ACRILICO ROJO 1/8" X 6" X 48"', 'Clave sistema viejo: 000000308.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000309', 'TAPAS EN POLICARBONATO 3/16"', 'Clave sistema viejo: 000000309.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000310', 'WD-40', 'Ubicacion/Clasif1: RACK 4/ NIVEL B. Clasif2: LUBRICANTES (G). Clave sistema viejo: 000000310.', 'LUBRICANTES (G)', 'galon', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000002', 'RUEDA DE CAUCHO P/DIABLITO', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000002.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000312', 'PISTOLA DE CALOR DOBLE TEMPERATURA DE 11.6A, 120V, MILWUAKEE', 'Clave sistema viejo: 000000312.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000313', 'RUEDA DE CAUCHO P/PLATAFORMA', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000313.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000314', 'GABINETE VENTILADO 36" X 18" X 72", #ITEM: H-7808', 'Clave sistema viejo: 000000314.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000315', 'VENTILADOR INDUSTRIAL OSCILANTE 30"', 'Ubicacion/Clasif1: Rack 2/ Nivel A. Clasif2: MOTORES (I). Clave sistema viejo: 000000315.', 'MOTORES (I)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000316', 'CLAVIJA L15-30P', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000316.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000317', 'BOLSA DE CINCHO 10"', 'Clave sistema viejo: 000000317.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000318', 'BOLSA DE CINCHO 15"', 'Clave sistema viejo: 000000318.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000319', 'SUJETADOR TIPO MARIPOSA 3/16', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000319.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000320', 'TAQUETE EXPANSOR 1/4 X 2 1/4', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000320.', 'TORNILLERIA (P)', 'paquete', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000321', 'BISAGRA TRANSPARENTE PAQ C/ 3 X 300MM', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000321.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000322', 'CINTA ASILANTE NEGRA', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000322.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000323', 'TAQUETE EXPANSOR 5/16 X 3 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000323.', 'TORNILLERIA (P)', 'paquete', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000324', 'MALLA/SCREEN PLÁSTICO', 'Clave sistema viejo: 000000324.', NULL, 'metro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000325', 'COMBUSTIBLE DIESEL PARA GENERADOR', 'Clave sistema viejo: 000000325.', NULL, 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000326', 'CINCHO PLASTICO NATURAL DE 45CM', 'Clave sistema viejo: 000000326.', NULL, 'bolsa', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000327', 'CINCHO PLASTICO NATURAL DE 30CM', 'Clave sistema viejo: 000000327.', NULL, 'bolsa', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000328', 'DISCO DE CORTE P/METAL 4-1/2', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: ABRASIVOS (A). Clave sistema viejo: 000000328.', 'ABRASIVOS (A)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000329', 'RUEDAS DE PLASTICO', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000329.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000330', 'KIT DE TIRAS DE VINIL PARA PUERTAS 8 X 8\', ITEM: #H-2816', 'Clave sistema viejo: 000000330.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000331', 'MOTOR 2 HP, 1PH', 'Ubicacion/Clasif1: Rack 3/ Nivel D. Clasif2: MOTORES (I). Clave sistema viejo: 000000331.', 'MOTORES (I)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000332', 'CABLE USO RUDO 3 X 12', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000332.', 'ELÉCTRICO (D)', 'metro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000333', 'EXTENSION 5 M CALIBRE #12 AWG', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000333.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000334', 'CARTUCHO DE SIKAFLEX 1A', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000334.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000335', 'INTERRUPTOR MONOFASICO 30 A, 250-600V', 'Clave sistema viejo: 000000335.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000336', 'TAQUETE EXPANSOR 3/8 X 3', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000336.', 'TORNILLERIA (P)', 'paquete', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000337', 'TORNILLO PAN HEAD P/ DE BROCA #8 X 1', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000337.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000338', 'TUERCA HEX 3/8', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000338.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000339', 'RONDANA PLANA 1/4', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000339.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000340', 'RONDANA DE PRESION 1/4', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000340.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000341', 'RONDANA DE PRESION 5/16', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000341.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000342', 'KRONOS ESTRUCTURADO MC DONALDS 20X20 PRIMERA', 'Clave sistema viejo: 000000342.', NULL, 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000343', 'ADHESIVO NORMAL GRIS SACO 20 KG', 'Clasif2: CONSTRUCCIÓN. Clave sistema viejo: 000000343.', 'CONSTRUCCIÓN', 'saco', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000344', 'BOQUILLA SELLADOR INT. LADRILLO SACO 10 KG', 'Clave sistema viejo: 000000344.', NULL, 'saco', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000345', 'SEPARADOR INTERCERAMIC 3 MM 200 PIEZAS', 'Clave sistema viejo: 000000345.', NULL, 'bolsa', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000346', 'KIT DE TIRAS DE VINIL PARA PUERTAS LISAS 4\' X 7\', ITEM: #H-2074', 'Clave sistema viejo: 000000346.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000347', 'KIT DE TIRAS DE VINIL PARA PUERTAS LISAS 10\' X 8\', ITEM: #H-3667', 'Clave sistema viejo: 000000347.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000348', 'TAPON P/CABLE AMAR-22-10', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000348.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000349', 'CINTA GRIS PARA DUCTO 48 MM X 58 MTS', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000349.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000350', 'CINTA DE TEFLON 1/2"', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000350.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000351', 'CUBETA 19L VIN ANTIB SAT CC Q5-14 TITANIO AZUL', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000351.', 'PINTURAS (K)', 'cubeta', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000352', 'BOTE 4L FLASH COAT NF CC 114 AMARILLO CROMO LI', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000352.', 'PINTURAS (K)', 'bote', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000353', 'KIT DE TIRAS DE VINIL PARA PUERTAS 6 X 8\', #ITEM: H-2815', 'Clave sistema viejo: 000000353.', NULL, 'kit', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000354', 'EXTENSION ELECTRICA DE USO RUDO 5 MTS', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000354.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000355', 'PINZAS CORTA CANDADOS 30\'\'', 'Ubicacion/Clasif1: CERRAJERIA. Clave sistema viejo: 000000355.', 'CERRAJERIA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000356', 'ESPUMA EXPANSIVA 500 ML TRUPER', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000356.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000357', 'TORNILLO CABEZA ALLEN 3/8 X 2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000357.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000358', 'TORNILLO CABEZA ALLEN 3/8 X 1 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000358.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000359', 'TORNILLO CABEZA ALLEN 5/16 X 1 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000359.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000360', 'TORNILLO CABEZA ALLEN 5/16 X 2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000360.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000361', 'TORNILLO CABEZA HEX 3/8 X 1 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000361.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000362', 'TORNILLO CABEZA HEX 5/16 X 2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000362.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000363', 'TORNILLO CABEZA HEX 5/16 X 1 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000363.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000364', 'TORNILLO CABEZA ALLEN 1/4 X 1 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000364.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000365', 'TORNILLO CABEZA ALLEN 1/4 X 2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000365.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000366', 'TORNILLO CABEZA HEX 1/4 X 1 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000366.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000367', 'TORNILLO CABEZA HEX 1/4 X 2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000367.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000368', '9\'\' X 1/4\'\' REP POLI MOHAIR', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000368.', 'PINTURAS (K)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000369', 'FLASH COAT NF CC 313-03 ESPADA GRISES', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000369.', 'PINTURAS (K)', 'bote', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000370', 'LLAVE ANGULAR 1/2 X 1/2', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000370.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000371', 'MANGUERA FLEXIBLE PARA FREGADERO 1/2 X 1/2\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000371.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000372', 'POLEA 3 1/2 B X 1', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000372.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000373', 'GRASERA MANUAL TRUPER', 'Ubicacion/Clasif1: GRASERA. Clasif2: HERRAMIENTA. Clave sistema viejo: 000000373.', 'HERRAMIENTA', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000374', 'CARTUCHO DE GRASA NLGI2', 'Ubicacion/Clasif1: RACK 4/ NIVEL B. Clasif2: LUBRICANTES (G). Clave sistema viejo: 000000374.', 'LUBRICANTES (G)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000375', 'CHUMACERA UCP205-16', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000375.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000376', 'FLASH COAT NF CC', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000376.', 'PINTURAS (K)', 'bote', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000377', 'BROCHA CLÁSICA 3\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000377.', 'PINTURAS (K)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000378', 'STANDAR ARMAZON COMEX', 'Clave sistema viejo: 000000378.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000379', 'CERRADURA DE LEVA 16MM', 'Clave sistema viejo: 000000379.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000380', 'CERRADURA DE LEVA 16MM', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000380.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000381', 'POLEA PARA BANDA REBANADORA BERKEL 808/818', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000381.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000382', 'FLECHA INFERIOR CON TUERCA, SIERRA JR', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000382.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000383', 'LÁMPARA LED 8FT SAGLITE 36W, 65K, CLARO, #ITEM: S00326', 'Ubicacion/Clasif1: Rack 2/ Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000383.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000384', 'CONTACTO DÚPLEX BLANCO, 110V, 2P, 3H', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000384.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000385', 'TAPA P/CONTACTO DUPLEX, 2X4', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000385.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000386', 'ROLLO DE LIJA FANDELI, G220', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: ABRASIVOS (A). Clave sistema viejo: 000000386.', 'ABRASIVOS (A)', 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000387', 'ARRANCADOR MANUAL 30A 220V, 3POLOS', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000387.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000388', 'TORNILLO ALLEN CABEZA PLANA FINO, 7/16-20 X 3/4, DISCO DE REBANADORA', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000388.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000389', 'VÁLVULA DE LLENADO DE ADMISIÓN P/SANITARIO CON FLOTADOR Y SAPO', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000389.', 'PLOMERÍA (L)', 'kit', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000390', 'PALANCA CROMADA PARA WC', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000390.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000391', 'RETEN SUPERIOR NITRILO', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000391.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000392', 'RETEN INFERIOR NITRILO', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000392.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000393', 'ETIQUETAS GRABADAS EN ALUMINIO ANODIZADO', 'Clave sistema viejo: 000000393.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000394', 'POLEA TIPO B, 3\'\' X 5/8\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000394.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000395', 'CONECTOR HEMBRA ABS 2\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000395.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000396', 'CODO ABS 45°, 2\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000396.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000397', 'CODO ABS 90°, 2\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000397.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000398', 'TERMOSTATO #ITEM: AP14465B', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000398.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000399', 'PISTON PARA PUERTA DE CIERRE AUTOMATICO', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000399.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000400', 'FLECHA HEXAGONAL P/TRANSMISIÓN DE LICUADORA INTERNATIONAL', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000400.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000401', 'BOMBA CENTRIFUGA FPS 2HP DR2S2-C2', 'Ubicacion/Clasif1: Rack 3/ Nivel D. Clasif2: BOMBAS (B). Clave sistema viejo: 000000401.', 'BOMBAS (B)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000402', 'ADAPTADOR MACHO PVC C80 1 1/4', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000402.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000403', 'TUERCA UNION PVC C40 1 1/4\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000403.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000404', 'COPLE PVC C40 1 1/4\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000404.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000405', 'ROLLO TEFLON 3/4 X 520\'\'', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000405.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000406', 'TRANSDUCTOR DE PRESIÓN', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000406.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000407', 'CODO 90° COBRE 1 1/4\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000407.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000408', 'CODO 45° COBRE, 1 1/4\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000408.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000409', 'COPLE DE COBRE 1 1/4\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000409.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000410', 'ROLLO DE SOLDADURA 60/40, 450G', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000410.', 'PLOMERÍA (L)', 'rollo', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000411', 'PICHANCHA BRONCE STRATAFLO 200 1 1/4\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000411.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000412', 'AFILADOR COMPLETO P/REBANADORA GLOBE 3600N', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000412.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000413', 'FUSIBLE CERAMICA 20A 250VCA', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000413.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-US64A', 'ENSAMBLE DE PIEDRAS P/AFILAR', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: US64A.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000415', 'KIT SEGUROS OMEGA', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000415.', 'TORNILLERIA (P)', 'kit', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000416', 'SEGUETA BIMETALICA P/ARCO 24DPP', 'Ubicacion/Clasif1: RACK 4/ NIVEL C. Clasif2: HERRAMIENTA (E). Clave sistema viejo: 000000416.', 'HERRAMIENTA (E)', 'paquete', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000417', 'LÁMPARA EMPOTADRO P/TECHO LED 12W, 720L REDONDA', 'Ubicacion/Clasif1: Rack 3/ Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000417.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000418', 'IMÁN DE EXTENSIÓN', 'Clave sistema viejo: 000000418.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000419', 'TERMINAL BULLET 16-14 HEMBRA/MACHO', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000419.', 'ELÉCTRICO (D)', 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000420', 'CAJA P/BOTÓN TIPO HONGO STECK', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000420.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000421', 'BLOCK CONTACTO 1N/0 STECK', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000421.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000422', 'BOTÓN PULSADOR AL RAS VERDE STECK', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000422.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000423', 'TRIPLAY 3/4 X 8\' X 4\'', 'Clave sistema viejo: 000000423.', NULL, 'hoja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000424', 'BARROTE 2 X 4 X 8', 'Clave sistema viejo: 000000424.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000425', 'PINTURA AMARILLA (19 VIN TOTAL ANTIVIRAL S CC VIVID B2)', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000425.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000426', 'VIN TOTAL ANTIVIRAL S CC VIVID B1 (GALÓN)', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000426.', 'PINTURAS (K)', 'galon', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000427', 'TORNILLO P/METAL 7 X 7/16', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000427.', 'TORNILLERIA (P)', 'kg', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000428', 'TORNILLO P/TABLAROCA 6 X 1 1/4', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000428.', 'TORNILLERIA (P)', 'kg', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000429', 'ESQUINERO RECTO VINIL 10\'', 'Clave sistema viejo: 000000429.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000430', 'CANAL 3 5/8 X 10, CALIBRE 20', 'Clave sistema viejo: 000000430.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000431', 'CANAL 1 5/8 X 10, CALIBRE 20', 'Clave sistema viejo: 000000431.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000432', 'CANAL 6 X 10, CALIBRE 20', 'Clave sistema viejo: 000000432.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000433', 'READYMIX', 'Clave sistema viejo: 000000433.', NULL, 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000434', 'MALLA CON PEGAMENTO 2\'\' X 300\'', 'Clave sistema viejo: 000000434.', NULL, 'rollo', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000435', 'CLAVO C/RONDANA 1 1/4\'\'', 'Clave sistema viejo: 000000435.', NULL, 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000436', 'FULMINANTE INDUSTRIAL CALIBRE 22', 'Ubicacion/Clasif1: ESTANTE 1. Clave sistema viejo: 000000436.', 'ESTANTE 1', 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000437', 'KIT DE SEGUROS OMEGA 3 A 32MM', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000437.', 'TORNILLERIA (P)', 'kit', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000438', 'KIT DE SEGUROS CIRCLIP', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000438.', 'TORNILLERIA (P)', 'kit', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000439', 'KIT DE SERVICIO P/SELLADORA DE IMPULSO 8\'\', MOD: H-164', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000439.', 'TORNILLERIA (P)', 'kit', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000440', 'POSTE 6 X 10 CALIBRE 20', 'Clave sistema viejo: 000000440.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000441', 'DUROCK 1/2 X 4 X 8 USG', 'Clave sistema viejo: 000000441.', NULL, 'hoja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000442', 'PETATILLO 1/2 X 4 X 8', 'Clave sistema viejo: 000000442.', NULL, 'hoja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000443', 'TORNILLO P/TABLAROCA 6 X 1 5/8', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000443.', 'TORNILLERIA (P)', 'kg', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000444', 'PERFACINTA 2\'\' X 250\'', 'Clave sistema viejo: 000000444.', NULL, 'rollo', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000445', 'YESO', 'Clave sistema viejo: 000000445.', NULL, 'kg', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000446', 'CEMENTO PISO SOBRE PISO', 'Clave sistema viejo: 000000446.', NULL, 'saco', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000447', 'HOJA DE YESO 1/2\'\' X 4\' X 8\'', 'Clave sistema viejo: 000000447.', NULL, 'hoja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000448', 'MINISPLIT 1 TON, INVERTER 220V, FRÍO/CALOR', 'Clave sistema viejo: 000000448.', NULL, 'equipo', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000449', 'PUERTA', 'Clave sistema viejo: 000000449.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000450', 'VENTANA CHICA', 'Clave sistema viejo: 000000450.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000451', 'VENTANA GRANDE', 'Clave sistema viejo: 000000451.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000452', 'PISTOLA CALAFETEADORA', 'Clave sistema viejo: 000000452.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000453', 'ANILLO DE CERA C/GUÍA', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000453.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000454', 'ANILLO DE CERA S/GUÍA', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000454.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000455', 'CANALETA 1 VÍA, 10 X 20 C/ADHESIVO', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000455.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000456', 'ZOCLO LM843-FJ', 'Clave sistema viejo: 000000456.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000457', 'TAQUETE PLASTICO 10-12 X 1\'\'', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000457.', 'TORNILLERIA (P)', 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000458', 'SELLA ACRILICO', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000458.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000459', 'TAQUETE PLASTICO 14-16 X 1 3/8', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000459.', 'TORNILLERIA (P)', 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000460', 'CLAVO S/CABEZA STD 1 1/2\'\'', 'Clave sistema viejo: 000000460.', NULL, 'kg', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000461', 'ESPATULA RIGIDA 3 PLASTICA', 'Ubicacion/Clasif1: RACK 4/ NIVEL C. Clasif2: HERRAMIENTA (E). Clave sistema viejo: 000000461.', 'HERRAMIENTA (E)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000462', 'PERNOS HEXAGONALES S.S. P/REBANADORA', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000462.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000463', 'MURO ALBERO 30 X 45 NEGRO', 'Clave sistema viejo: 000000463.', NULL, 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000464', 'BOQUICHAMP ULTRAFINO NEGRO', 'Clave sistema viejo: 000000464.', NULL, 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000465', 'SEPARADOR DE LOSETA 2MM', 'Clave sistema viejo: 000000465.', NULL, 'bolsa', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000466', 'COPLE BANDA 2\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000466.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000467', 'MANGUERA PLANA TIPO LONA 2\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000467.', 'PLOMERÍA (L)', 'metro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000468', 'POLEA TIPO B, 4\'\' X 5/8', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000468.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000469', 'PINTURA CLARA (VIN TOTAL ANTIVIRAL S CC VIVID B1 (CUBETA))', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000469.', 'PINTURAS (K)', 'cubeta', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000470', 'PINTURA AMARILLA (FLASH COAT NF CC VIVID B4) P/ GAS LP', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000470.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000471', 'FLASH COAT NF CC VIVID B1', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000471.', 'PINTURAS (K)', 'bote', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000472', 'VÁLVULA DE ESFERA ROSCABLE 3/4', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000472.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000473', 'PINZA P/PELAR CABLE 6\'\' TRUPER', 'Clave sistema viejo: 000000473.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000474', 'BOTÓN SELECTOR 2 POSICIONES MAN. CORTA ST', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000474.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000475', 'PIJA HEX P/BROCA 1 X 14', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000475.', 'TORNILLERIA (P)', 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000476', 'ARNESES DE CAIMANES MEDIANOS 5A 43MM', 'Clave sistema viejo: 000000476.', NULL, 'kit', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000477', 'POSTE 3 5/8 X 10 CALIBRE 20', 'Clave sistema viejo: 000000477.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000478', 'POSTE 1 5/8 X 10 CALIBRE 20', 'Clave sistema viejo: 000000478.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000479', 'RUEDA GIRATORIA MODELO: H-1234 CASTER', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000479.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000480', 'BALERO 698Z', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000480.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000481', 'PINTURA VERDE (4 FLASH COAT NF CC VIVID B4) CARROS EMBUTIDOS', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000481.', 'PINTURAS (K)', 'galon', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000482', 'LIBRETA C/NUMEROS P/IDENTIFICAR CABLES', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000482.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000483', 'TUBO STEEL 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000483.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000484', 'CAJA METÁLICA 2X4', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000484.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000485', 'ABRAZADERA DE UÑA STEEL 1/2\'\'', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000485.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000486', 'CONECTOR STEEL 1/2\'\'', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000486.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000487', 'CURVA STEEL 1/2\'\'', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000487.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000488', 'COPLE STEEL 1/2\'\'', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000488.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000489', 'CABLE USO RUDO 2 X 16', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000489.', 'ELÉCTRICO (D)', 'metro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000490', 'CARRETE CON 100M DE CABLE CALIBRE 16 AWG, COLOR ROJO', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000490.', 'ELÉCTRICO (D)', 'rollo', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000491', 'CARRETE CON 100M DE CABLE CALIBRE 16 AWG, COLOR BLANCO', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000491.', 'ELÉCTRICO (D)', 'rollo', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000492', 'CARRETE CON 100M DE CABLE CALIBRE 16 AWG, COLOR NEGRO', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000492.', 'ELÉCTRICO (D)', 'rollo', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000493', 'SANITARIO CATO TULIPAN, ALARGADO PLUS, BLANCO 4.8L', 'Clave sistema viejo: 000000493.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000494', 'KIT P/INSTALACIÓN DE SANITARIO', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000494.', 'PLOMERÍA (L)', 'kit', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000495', 'PUERTA DE TAMBOR .75 X 2', 'Clave sistema viejo: 000000495.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000496', 'MARCO P/PUERTA', 'Clave sistema viejo: 000000496.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000497', 'CHAPAS P/PUERTA DE BAÑO C/LLAVE', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000497.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000498', 'MOLDURA DE MADERA GUARDASILLA', 'Clave sistema viejo: 000000498.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000499', 'DISPENSADOR DE PAPEL HIGIENICO JUNIOR', 'Clave sistema viejo: 000000499.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000500', 'PISO ARAGON 50 X 50, BLANCO 1.75 M2', 'Clave sistema viejo: 000000500.', NULL, 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000501', 'BOQUICHAMP ULTRAFINO GRIS', 'Clave sistema viejo: 000000501.', NULL, 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000502', 'TAPA P/SANITARIO ALARGADO, BLANCA', 'Clave sistema viejo: 000000502.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000503', 'PLASTIACERO 5 MIN,R5-45', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000503.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000504', 'BISAGRA VEKER 3 X 3 C/PLANA P/SUELTO', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000504.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000505', 'RESANADOR', 'Clave sistema viejo: 000000505.', NULL, 'bote', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000506', 'MOLDURA CUADRADO 239-FJ', 'Clave sistema viejo: 000000506.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000507', 'POLINES 4 X 4 X 14', 'Clave sistema viejo: 000000507.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000508', 'POLINES 4 X 4 X 8', 'Clave sistema viejo: 000000508.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000509', 'CEMENT BOND GRIS 20KG', 'Clave sistema viejo: 000000509.', NULL, 'saco', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000510', 'ACEITE AFLOJA TODO, AEROSOL', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: LUBRICANTES (G). Clave sistema viejo: 000000510.', 'LUBRICANTES (G)', 'bote', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000511', 'FUSIBLE 3A, 600V', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000511.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000512', 'COPLE P/VARILLA ROSCADA 1/4', 'Clave sistema viejo: 000000512.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000513', 'FUSIBLE 4A, 250V', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000513.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000514', 'PISO KABUL 18 X 50, CEREZO', 'Clave sistema viejo: 000000514.', NULL, 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000515', 'BOQUICHAMP ULTRAFINO CHOCOLATE', 'Clave sistema viejo: 000000515.', NULL, 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000516', 'PINTURA ESMALTE GRIS CLARO (423 GR AERO 2418 GRIS)', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000516.', 'PINTURAS (K)', 'lata', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000517', 'PINTURA NARANJA (VIN ANT SAT CC VIVID B5)', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000517.', 'PINTURAS (K)', 'cubeta', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000518', 'MASKING TAPE AZUL 3/4\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000518.', 'PINTURAS (K)', 'rollo', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000519', 'ROLLO POLIETILENO', 'Clave sistema viejo: 000000519.', NULL, 'metro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000520', 'EXTRACTOR 6\'\' 110V, SILENCIOSO', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000520.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000521', 'REBORDE J VINIL 10\'', 'Clave sistema viejo: 000000521.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000522', 'SANITARIO 1PZ ORMAND GLAUER BAY', 'Clave sistema viejo: 000000522.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000523', 'PISO HABITAT 60 X 60 GRAPHITE', 'Clave sistema viejo: 000000523.', NULL, 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000524', 'CEMENTO MORTAR GRIS', 'Clave sistema viejo: 000000524.', NULL, 'saco', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000525', 'CABEZAL P/MOLINO HOBART, MODELO: 4732', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000525.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000526', 'PIEDRAS DE AFILAR', 'Clave sistema viejo: 000000526.', NULL, 'juego', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000527', 'RUEDA P/CARRITO DE SUPER, 5\'\' X 1 1/4\'\' CENTRO 3/8\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000527.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000528', 'MINA DE REFRIGERANTE 404A', 'Ubicacion/Clasif1: Rack 2/ Nivel D. Clasif2: CLIMA (C). Clave sistema viejo: 000000528.', 'CLIMA (C)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000529', 'TIRA TRIM ALUMINIO NEGRO BRILLANTE', 'Clave sistema viejo: 000000529.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000530', 'BISAGRA LATON 2 X 1.6', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000530.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000531', 'BISAGRA LATON 1 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000531.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000532', 'REMOVEDOR DE PINTURA', 'Ubicacion/Clasif1: RACK 4/ NIVEL A. Clasif2: PINTURAS (K). Clave sistema viejo: 000000532.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000533', 'THINNER', 'Ubicacion/Clasif1: RACK 4/ NIVEL A. Clasif2: PINTURAS (K). Clave sistema viejo: 000000533.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000534', 'PINTURA NEGRO SATINADO (423 GR AERO 2438 NEGRO SATINADO)', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000534.', 'PINTURAS (K)', 'lata', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000535', 'PINTURA BLANCA (PRIMA 280 BLANCO)', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000535.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000536', 'TORNILLO CABEZA ALLEN 5/16 X 1', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000536.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000537', 'HIDROLAVADORA KARCHER', 'Clave sistema viejo: 000000537.', NULL, 'equipo', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000538', 'PALA REDONDA', 'Clave sistema viejo: 000000538.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000539', 'CONTACTOR TRIFASICO 60A C/AUXILIAR', 'Clave sistema viejo: 000000539.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000540', 'CONTACTOR TRIFASICO 30A C/AUXILIAR', 'Clave sistema viejo: 000000540.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000541', 'PINTURA AZUL (FLASH COAT NF CC VIVID B3)', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000541.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000542', 'CADENA 40-1', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000542.', 'REFACCIONES (O)', 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000543', 'CANDADO 40-1', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000543.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000544', 'CANDADO 1/2-40', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000544.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000545', 'CEPILLO DE BRONCE', 'Clave sistema viejo: 000000545.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000546', 'ESCUADRA DE METAL 2 X 2, 4 ORIFICIOS', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000546.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000547', 'TORNILLO CARRUAJE 5/16 X 2 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000547.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000548', 'RONDANA DE PRESIÓN 3/8', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000548.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000549', 'RESORTE P/JUEGO MECÁNICO', 'Clave sistema viejo: 000000549.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000550', 'PUERTA DE SEGURIDAD PLEGABLE', 'Clave sistema viejo: 000000550.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000551', 'RUEDA GIRATORIA 8\'\' X 2\'\' DE HULE C/METAL', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000551.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000552', 'RUEDA 8\'\' X 2\'\' DE HULE C/METAL Y BALERO 3/4', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000552.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000553', 'TAQUETE EXPANSOR 3/8\'\' X 3 3/4\'\'', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000553.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000554', 'SENSOR DE CONTACTO MAGNÉTICO', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000554.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000555', 'PTR 1 X 1 1/2\'\'', 'Clave sistema viejo: 000000555.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000556', 'BALERO 6203-2RSR-L038 C3, 1/2', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000556.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000557', 'ACEITE HIDRAULICO H300 P/MONTACARGAS', 'Ubicacion/Clasif1: RACK 4/ NIVEL C. Clasif2: LUBRICANTES (G). Clave sistema viejo: 000000557.', 'LUBRICANTES (G)', 'cubeta', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000558', 'MUEBLE P/BAÑO', 'Clave sistema viejo: 000000558.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000559', 'ESPEJO P/BAÑO', 'Clave sistema viejo: 000000559.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000560', 'KIT DE INSTALACIÓN P/LAVABO 1/2', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000560.', 'PLOMERÍA (L)', 'kit', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000561', 'CERRADURA DE PUERTA C/LLAVE', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000561.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000562', 'FLOTADOR DE BRONCE 6\'\' P/VARILLA', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000562.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000563', 'VARILLA P/FLOTADOR REFORZADO 1/4 X 1/4 X 12', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000563.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000564', 'VÁLVULA P/FLOTADOR 3/4 BRONCE', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000564.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000565', 'MANGUERA PARA GAS', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000565.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000566', 'PLAFÓN FISURADO 2\' X 2\'', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clave sistema viejo: 000000566.', 'Rack 1/ Nivel E', 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000567', 'BREA EN FRÍO', 'Clave sistema viejo: 000000567.', NULL, 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000568', 'WD-40 EN SPRY', 'Ubicacion/Clasif1: RACK 4/ NIVEL B. Clasif2: LUBRICANTES (G). Clave sistema viejo: 000000568.', 'LUBRICANTES (G)', 'lata', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000569', 'SOPORTE P/LAVABO', 'Clave sistema viejo: 000000569.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000570', 'PASADOR P/AFILADOR BERKEL, #ITEM:US71', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000570.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000571', 'PERILLA P/CARRO DE REBANADORA', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000571.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000572', 'GUANTES DE CARNAZA', 'Clave sistema viejo: 000000572.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000573', 'MASCARILLA P/GAS REUSABLE', 'Clave sistema viejo: 000000573.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000574', 'GOOGLES DE PROTECCIÓN', 'Clave sistema viejo: 000000574.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000575', 'GUANTES DE PLÁSTICO LARGOS', 'Clave sistema viejo: 000000575.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000576', 'CARETA DE PROTECCIÓN', 'Clave sistema viejo: 000000576.', NULL, 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000577', 'CANDADOS LAMINADOS', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000577.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000578', 'TAQUETE P/TABLAROCA 5/16', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000578.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000579', 'TORNILLO PAN HEAD 8 X 1\'\'', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000579.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000580', 'PINTURA ROJA (FLASH COAT NF BERMELLON)', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000580.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000581', 'PINTURA AMARILLA (FLASH COAT NF CC VIVID B4) BARANDALES ESTACIONAMIENTO', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000581.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000582', 'PINTURA AMARILLA (VINIMEX ANTIBACTERIAL SAT CC VIVID B5)', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000582.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000583', 'REPUESTO DE RODILLO 4 X 3/8\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000583.', 'PINTURAS (K)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000584', 'PERFIL 1400 C-18, 3METROS', 'Clave sistema viejo: 000000584.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000585', 'RODAJA 80-R PARA RIEL 1400', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000585.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000586', 'FOCO SAGLITE ESPIRAL 65W', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000586.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000587', 'TORNILLO CABEZA HEX 1/2 X 2"', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000587.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000588', 'RONDANA DE PRESIÓN 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000588.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000589', 'TUERCA HEX 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000589.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000590', 'GABINETE TUBO LED 18W, 60CM', 'Ubicacion/Clasif1: Rack 2/ Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000590.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000591', 'RESORTE DE COMPRESIÓN P/ELEVADOR DE CARGA', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000591.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000592', 'CANDADO DE COMBINACIÓN', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000592.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000593', 'FIJADOR LOCTITE', 'Clave sistema viejo: 000000593.', NULL, 'bote', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000594', 'BALERO 07100', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000594.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000595', 'RUEDA P/DIABLITO 10"', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000595.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000596', 'RUEDA P/DIABLITO 8"', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000596.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000597', 'CABLE USO RUDO 4 X 12', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000597.', 'ELÉCTRICO (D)', 'metro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000598', 'BANDA B-50', 'Clave sistema viejo: 000000598.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000599', 'PRIMER GRIS', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000599.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000600', 'PINTURA GRIS (FLASH COAT NF CC VIVID B2)', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000600.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000601', 'PICHANCHA PVC 4-1', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000601.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000602', 'NIPLE GALVANIZADO 1 1/4" X 3"', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000602.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000603', 'ANILLO P/TAPA DE DISCO BERKEL #US43', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000603.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000604', 'TORNILLO CABEZA DE GOTA S.S. 1/4 X 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000604.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000605', 'TORNILLO CABEZA DE GOTA S.S. 1/4 X 3/4', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000605.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000606', 'REGULADOR DE GAS 20 PSI', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000606.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000607', 'SOLDADURA 6011 DE 1/8', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clave sistema viejo: 000000607.', 'Rack 3/ Nivel C', 'kg', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000608', 'SOLDADURA PARA S.S. 308 DE 1/8', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clave sistema viejo: 000000608.', 'Rack 3/ Nivel C', 'kg', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000609', 'BISAGRA SOLDABLE 5/8', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000609.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000610', 'TAPA P/APAGADOR 2 X 4, S.S.', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000610.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000611', 'CONEXIÓN DE LATÓN HEMBRA 5/8 P/MANGUERA', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000611.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000612', 'LÁMPARA LED 6FT, 25W, 65K, 100-265V, #ITEM: S00584', 'Ubicacion/Clasif1: Rack 2/ Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000612.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000613', 'LÁMPARA LED LINEAL 4\', T8, 18W, 65K, 100-265V, 1800L, #ITEM: S00325', 'Ubicacion/Clasif1: Rack 2/ Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000613.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000614', 'LIMPIADOR DE MANOS', 'Clave sistema viejo: 000000614.', NULL, 'galon', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000615', 'BANDA A-22 DENTADA', 'Clave sistema viejo: 000000615.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000616', 'POLEA TIPO B, 3 1/2" X 5/8', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000616.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000617', 'POLEA TIPO A, 3\'\' X 5/8', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000617.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000618', 'PORTAROLLO DE PAPEL HIGIÉNICO', 'Clave sistema viejo: 000000618.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000619', 'NIPLE GALVANIZADO 1/2 X 2"', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000619.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000620', 'SOLDADURA 50/50', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000620.', 'PLOMERÍA (L)', 'rollo', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000621', 'COPLE 3/4 DE COBRE', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000621.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000622', 'ADAPTADOR P/REGULADOR', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000622.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000623', 'MANGUERA FLEXIBLE PARA GAS', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000623.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000624', 'NIPLE CAMPANA', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000624.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000625', 'POLEA TIPO B, 4" X 1"', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000625.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000626', 'SELECTOR DE EMERGENCIA, 3POLOS, 25A', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000626.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000627', 'TARJETA ELECTRÓNICA P/SIERRA LION', 'Ubicacion/Clasif1: Rack 2/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000627.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000628', 'BOTÓN PULSADOR AL RAS ROJO STECK', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000628.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000629', 'CODO GALVANIZADO 1", 90°', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000629.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000630', 'POLEA TIPO B, 4" X 7/8"', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000630.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000631', 'PIPE WRAP TAPE 2" X 100FT', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000631.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000632', 'FUSIBLE TIPO EUROPEO 6A 250VCA', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000632.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000633', 'CINCHO NEGRO 15CM', 'Clave sistema viejo: 000000633.', NULL, 'bolsa', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000634', 'CINCHO NEGRO 19CM', 'Clave sistema viejo: 000000634.', NULL, 'bolsa', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000635', 'LLAVE NARIZ LATÓN 3/4', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000635.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000636', 'CONTACTO DE MEDIA VUELTA 3FASES, 30A, 250V LEVITON L15-30R', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000636.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000637', 'CAJA DE HERRAMIENTA METÁLICA 40 X 18 X 16 CM', 'Clave sistema viejo: 000000637.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000638', 'LÁMPARA 4\' LUZ BLANCA FRÍA, T12, 40W', 'Ubicacion/Clasif1: Rack 2/ Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000638.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000639', 'COPLE DE COBRE 1/2"', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000639.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000640', 'TEE DE COBRE 1/2"', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000640.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000641', 'ADAPTADOR MACHO DE COBRE 1/2"', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000641.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000642', 'CODO 90° COBRE 1/2"', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000642.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000643', 'TUBO ABS 3" X 20 CEDULA 30', 'Clave sistema viejo: 000000643.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000644', 'SUJETADOR TIPO MARIPOSA 1/4 X 3"', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000644.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000645', 'LÁMPARA FLUORESCENTE 4\', T5, 54W 50K, #ITEM: S00063', 'Ubicacion/Clasif1: Rack 3/ Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000645.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000646', 'COPLE PVC C40 1/2\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000646.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000647', 'ADAPTADOR MACHO PVC C40 1/2\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000647.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000648', 'TAPÓN CACHUCHA PVC C40 1/2\'\'', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000648.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000649', 'BATERIA DURALAST BCI 24F 24F-DL', 'Clave sistema viejo: 000000649.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000650', 'LLAVE TEMPORIZADORA P/LAVABO', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000650.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000651', 'LÁMPARA LED 40W, 65K, 100-265V CON GABINETE A PRUEBA DE VAPOR #ITEM: S00422', 'Ubicacion/Clasif1: Rack 2/ Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000651.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000652', 'PISTOLA GRAVEDAD LVLP 515', 'Clave sistema viejo: 000000652.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000653', 'COLADOR METÁLICO CON REPUESTO', 'Clave sistema viejo: 000000653.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000654', 'PINTURA PERLA (FLASH COAT NF CC VIVID B2)', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000654.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000655', 'EXTRACTOR 4"', 'Clave sistema viejo: 000000655.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000656', 'MOTOR REBANADORA GLOBE 3600N #ITEM: 110040', 'Ubicacion/Clasif1: Rack 3/ Nivel D. Clasif2: MOTORES (I). Clave sistema viejo: 000000656.', 'MOTORES (I)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000657', 'TANQUE DE GAS PROPANO', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000657.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000658', 'TAPÓN CACHUCHA PVC 3/4', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000658.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000659', 'ACEITE ULTRA 32-3MAF POLYOLESTER OIL 998-E022-01', 'Ubicacion/Clasif1: RACK 4/ NIVEL B. Clasif2: LUBRICANTES (G). Clave sistema viejo: 000000659.', 'LUBRICANTES (G)', 'galon', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000660', 'CUÑA 3/16', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000660.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000661', 'PEGAMENTO PVC', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000661.', 'PLOMERÍA (L)', 'bote', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000662', 'GABINETE DE PLÁSTICO 102 X 77 MM', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000662.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000663', 'INTERRUPTOR PUSH N.O', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000663.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000664', 'RODILLO P/PUERTA DE NYLON C/BALERO', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000664.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000665', 'DESTAPADOR DE DRENAJES RIDGID 58920 MODELO K-50', 'Clave sistema viejo: 000000665.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000666', 'MANGUERA INDUSTRIAL 3/4 X 15M REFORZADA', 'Clave sistema viejo: 000000666.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000667', 'PISTOLA METÁLICA 7 MODELOS DE RIEGO', 'Clave sistema viejo: 000000667.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000668', 'PINTURA CAFÉ EXTERIORES (VINIMEX ANTIBACTERIAL SAT CC VIVID B5)', 'Clave sistema viejo: 000000668.', NULL, 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000669', 'PINTURA BLANCA AEROSOL P/PUNTO DE REUNIÓN (AERO 2413 BLANCO BRILLANTE)', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000669.', 'PINTURAS (K)', 'lata', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000670', 'RUEDA 4" X 1 1/4" POLIURETANO NEGRO, GIRATORIA', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000670.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000671', 'RUEDA 4" X 1 1/4" POLIURETANO SIN BASE', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000671.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000672', 'CONJUNTO DE UNION DE AFILADOR #GS520280', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000672.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000673', 'CEMENTO BLANCO', 'Clasif2: CONSTRUCCIÓN. Clave sistema viejo: 000000673.', 'CONSTRUCCIÓN', 'kg', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000674', 'ACEITE VACUUM PUMP 46', 'Ubicacion/Clasif1: RACK 4/ NIVEL C. Clasif2: LUBRICANTES (G). Clave sistema viejo: 000000674.', 'LUBRICANTES (G)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000675', 'RUEDA 3" X 1 1/4" POLIURETANO NEGRO C/BALERO 3/8', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000675.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000676', 'TORNILLO CON RONDANA PUNTA DE BROCA #8 X 1 5/8', 'Ubicacion/Clasif1: ESTANTE 2. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000676.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000677', 'TORNILLO ALLEN 5/16-24 X 1 1/4', 'Ubicacion/Clasif1: ESTANTE 1. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000677.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000678', 'BOTONERA INDUSTRIAL LIFT MASTER MODELO: PBS-3 5A 250V', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000678.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000679', 'KIT DE LIMPIADORES PARA SIERRA BUTCHER BOY #ITEM: BBRK1', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000679.', 'REFACCIONES (O)', 'kit', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000680', 'LÁMPARA LED C/ GABINETE A PRUEBA DE VAPOR 8FT', 'Ubicacion/Clasif1: Rack 2/ Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000680.', 'ELÉCTRICO (D)', 'pieza', 0);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000681', 'TORNILLO CABEZA HEX P/ BROCA #8 X 1 1/2', 'Ubicacion/Clasif1: ESTANTE 2. Clave sistema viejo: 000000681.', 'ESTANTE 2', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000682', 'BOILER 50G 190L GAS LP', 'Clave sistema viejo: 000000682.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000683', 'SELLADOR ELASTOMÉRICO CAFÉ', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000683.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000684', 'TUBO PVC GRIS 1/2 C40', 'Ubicacion/Clasif1: Rack 2/ Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000684.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000685', 'CONECTOR PVC 1/2", C40', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000685.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000686', 'TUERCA CIEGA PARA ASPAS DE LICUADORA INTERNATIONAL', 'Clave sistema viejo: 000000686.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000687', 'PINTURA ROJA FACHADA (VINIMEX ANTIBACTERIAL SAT CC VIVID B5)', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000687.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000688', 'PLOGA MÚLTIPLE', 'Clave sistema viejo: 000000688.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000689', 'TRAMPA P/ LAVABO', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000689.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000690', 'EXTENSIÓN P/ DESAGÜE', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000690.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000691', 'CHUPÓN UNIVERSAL P/LAVABO', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000691.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000692', 'SELLADOR EPÓXICO', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000692.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000693', 'PINTURA AMARILLA PARKING (VIA COLOR B/S LF)', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000693.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000694', 'LIMPIADOR ÁCIDO P/SERPENTÍN', 'Ubicacion/Clasif1: RACK 4/ NIVEL A. Clave sistema viejo: 000000694.', 'RACK 4/ NIVEL A', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000695', 'TUBO PVC BLANCO 1/2', 'Clave sistema viejo: 000000695.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000696', 'LÁMPARA LED 4FT T8 20W 65K CLARA #S00137', 'Ubicacion/Clasif1: Rack 2/ Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000696.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000697', 'MORTERO SIKA BLANCO', 'Clave sistema viejo: 000000697.', NULL, 'saco', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000698', 'TORNILLO CABEZA HEX P/BROCA 10 X 1"', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000698.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000699', 'TORNILLO CABEZA HEX P/BROCA 14 X 1"', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000699.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000700', 'TUERCA HEX 1/4', 'Ubicacion/Clasif1: ESTANTE 1. Clave sistema viejo: 000000700.', 'ESTANTE 1', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000701', 'VÁLVULA SELENOIDE 3 TERMINALES 12V', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000701.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000702', 'CABLE USO RUDO 3 X 14', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000702.', 'ELÉCTRICO (D)', 'metro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000703', 'TAPÓN CUADRADO PARA TUBULAR 3/4', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000703.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000704', 'TAPÓN CUADRADO PARA TUBULAR 1 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000704.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000705', 'DISCO DE DESBASTE 4 1/2 P/ BAFER', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: ABRASIVOS (A). Clave sistema viejo: 000000705.', 'ABRASIVOS (A)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000706', 'DISCO DE LIJA G120, 4 1/2 P/BAFER', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: ABRASIVOS (A). Clave sistema viejo: 000000706.', 'ABRASIVOS (A)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000707', 'FOCO ESPIRAL 100W', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000707.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000708', 'TORNILLO C/RONDANA P/BROCA #8 X 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000708.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000709', 'TUBULAR 3/4 X 3/4 CAL. 18', 'Clave sistema viejo: 000000709.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000710', 'CADENA TIPO JACK #16', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000710.', 'FERRETERÍA (R)', 'pie', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000711', 'TORNILLO GOTA PHILIPS', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000711.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000712', 'TRIPLAY 1/8 X 4\' X 8\'', 'Clave sistema viejo: 000000712.', NULL, 'hoja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000713', 'SOLDADURA 6011 DE 3/32', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clave sistema viejo: 000000713.', 'Rack 3/ Nivel C', 'kg', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000714', 'TUBULAR 1 1/2 X 1 1/2 CAL.18', 'Clave sistema viejo: 000000714.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000715', 'PLACA 3 X 3 PERFORADA', 'Clave sistema viejo: 000000715.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000716', 'DIMMER DESLIZANTE 120V', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000716.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000717', 'TUERCA HEX 3/16', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000717.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000718', 'TORNILLO GOTA 10-24 X 2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000718.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000719', 'RONDANA PLANA 3/16', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000719.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000720', 'EJE P/CUERPO DE LICUADORA', 'Clave sistema viejo: 000000720.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000721', 'PINTURA NEGRA AEROSOL (423 GR AERO 2414 NEGRO BRILLANTE)', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000721.', 'PINTURAS (K)', 'lata', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000722', 'EMPAQUE/RING PARA CUERPO DE LICUADORA', 'Clave sistema viejo: 000000722.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000723', 'ROLDANA A.I. P/CUERPO DE LICUADORA', 'Clave sistema viejo: 000000723.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000724', 'MINA DE REFRIGERANTE R410A', 'Ubicacion/Clasif1: Rack 2/ Nivel D. Clasif2: CLIMA (C). Clave sistema viejo: 000000724.', 'CLIMA (C)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000725', 'VENTILADOR DE PISO 20"', 'Clave sistema viejo: 000000725.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000726', 'BISAGRA SOLDABLE 3/4', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000726.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000727', 'SOLDADURA PARA S.S. 308 DE 3/32', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000727.', 'FERRETERÍA (R)', 'kg', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000728', 'LÁMPARA LED BULBO 14W', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000728.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000729', 'TAPA P/APAGADOR 2 X 4 PLÁSTICO', 'Clave sistema viejo: 000000729.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000730', 'FOCO ESPIRAL 20W', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000730.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000731', 'CÁPSULA DOBLE TAPA P/ENVÍO NEUMÁTICO', 'Clave sistema viejo: 000000731.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000732', 'VÁLVULA DE ESFERA 1/2"', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000732.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000733', 'PINTURA GRIS CLARO (VINIMEX ANTIBACTERIAL SAT CC VIVID B1)', 'Clasif2: PINTURAS (K). Clave sistema viejo: 000000733.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000734', 'HEXAGONAL COMPLETO #PALISLA002', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000734.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000735', 'ÁNGULO GALVANIZADO 1 1/2 X 10FT', 'Clave sistema viejo: 000000735.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000736', 'LLAVE MEZCLADORA INDUSTRIAL CON ROCIADOR 36"', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000736.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000737', 'CADENA GALVANIZADA', 'Clave sistema viejo: 000000737.', NULL, 'metro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000738', 'FOTOCELDA 130V 300W', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000738.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000739', 'PINTURA GRIS CLARO RRHH (VINIMEX ANTIBACTERIAL SAT CC VIVID B1)', 'Clasif2: PINTURAS (K). Clave sistema viejo: 000000739.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000740', 'CINCHO NEGRO 28CM', 'Clave sistema viejo: 000000740.', NULL, 'paquete', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000741', 'THERMOFIT (TUBING TERMOCONTRACTIL)', 'Clave sistema viejo: 000000741.', NULL, 'paquete', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000742', 'MINA DE REFRIGERANTE R22 FREON', 'Ubicacion/Clasif1: Rack 2/ Nivel D. Clasif2: CLIMA (C). Clave sistema viejo: 000000742.', 'CLIMA (C)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000743', 'SUB ENSAMBLE DE AFILADOR', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000743.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000744', 'BALERO 15578 CÓNICO', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000744.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000745', 'PISTA DE BALERO CÓNICO 15523', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000745.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000746', 'CADENA PASO 35-4', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000746.', 'REFACCIONES (O)', 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000747', 'RODILLO P/CARRO DE REBANADORA BERKEL US110A', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000747.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000748', 'ANILLO P/BOQUILLA DE MOLINO', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000748.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000749', 'CLAVIJA L15-20P LKG 20A', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000749.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000750', 'TORNILLO GOTA 10-24 X 3', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000750.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000751', 'CODO P/ESTUFA 3/8 X 3/8', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000751.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000752', 'BOQUILLA ROCIADORA 1/4 P/ALTA PRESIÓN', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000752.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000753', 'BALERO CÓNICO C/PISTA 88649/10', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000753.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000754', 'BALERO CÓNICO C/PISTA 67048/10', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000754.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000755', 'BALERO 6204', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000755.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000756', 'RETEN #24287 P/TRANSMISIÓN DE MOLINO HOBART', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000756.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000757', 'PAPEL PARA EMPAQUE', 'Clave sistema viejo: 000000757.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000758', 'GRASA PARA LUBRICAR', 'Clave sistema viejo: 000000758.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000759', 'SILICÓN ROJO HI-TEMP', 'Clave sistema viejo: 000000759.', NULL, 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000760', 'ACEITE MOBIL GEAR 634', 'Ubicacion/Clasif1: RACK 4/ NIVEL A. Clasif2: LUBRICANTES (G). Clave sistema viejo: 000000760.', 'LUBRICANTES (G)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000761', 'PLACA DE MONTAJE P/BOTÓN', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000761.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000762', 'BLOCK CONTACTO 1N/C', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000762.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000763', 'TORNILLO CABEZA HEX S.S. 1/2 X 4"', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000763.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000764', 'PIEDRA DECORATIVA P/FACHADA', 'Clave sistema viejo: 000000764.', NULL, 'caja', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000765', 'CONTACTO 20A P/CAJA L6-20R LGK', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000765.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000766', 'CLAVIJA L6-20P LGK 20A', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000766.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000767', 'CAJA 4X4', 'Ubicacion/Clasif1: Rack 1/Nivel A. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000767.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000768', 'ADAPTADOR MACHO 2" ABS', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000768.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000769', 'CODO 90° PVC 2"', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000769.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000770', 'SOLDADURA 6013 3/32', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000770.', 'FERRETERÍA (R)', 'kg', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000771', 'CLAMP', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000771.', 'FERRETERÍA (R)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000772', 'CUERPO P/JUEGO MECÁNICO', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000772.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000773', 'TUERCA DE BRONCE PARA CUERPO DE JUEGO MECÁNICO', 'Ubicacion/Clasif1: Rack 3/ Nivel C. Clasif2: REFACCIONES (O). Clave sistema viejo: 000000773.', 'REFACCIONES (O)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000774', 'COMPUESTO PARA REPARACIÓN DE ESTACIONAMIENTO PERMA PATCH', 'Clave sistema viejo: 000000774.', NULL, 'cubeta', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000775', 'BALERO 6202Z 16MM', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000775.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000776', 'REPUESTO DE RODILLO 4"', 'Ubicacion/Clasif1: Rack 1/ Nivel E. Clasif2: PINTURAS (K). Clave sistema viejo: 000000776.', 'PINTURAS (K)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000777', 'BALERO 6202 5/8', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000777.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000778', 'PILA TIPO BOTÓN CR2450', 'Ubicacion/Clasif1: Rack 1/ Nivel B. Clasif2: ELÉCTRICO (D). Clave sistema viejo: 000000778.', 'ELÉCTRICO (D)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000779', 'ASIENTO PARA WC OVALADO', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000779.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000780', 'VÁLVULA AJUSTABLE DE LLENADO P/SANITARIO', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000780.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000781', 'TUBO ABS 2" X 20', 'Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000781.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000782', 'EMPAQUE DE PVC PARA MANGUERA', 'Ubicacion/Clasif1: Rack 1/ Nivel D. Clasif2: PLOMERÍA (L). Clave sistema viejo: 000000782.', 'PLOMERÍA (L)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000783', 'CHUMACERA UCP205-16 PISO 2T, 1"', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000783.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000784', 'CHUMACERA UCF205-16 PARED 4T, 1"', 'Ubicacion/Clasif1: Rack 1/ Nivel C. Clasif2: RODAMIENTOS (T). Clave sistema viejo: 000000784.', 'RODAMIENTOS (T)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000785', 'ABRAZADERA SIN FIN 52-76MM', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000785.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000786', 'PIJA PUNTA DE BROCA #14 X 3/4', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000786.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000787', 'PIJA PUNTA DE BROCA #10 X 3/4', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: TORNILLERIA (P). Clave sistema viejo: 000000787.', 'TORNILLERIA (P)', 'pieza', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000788', 'PINTURA GRIS ACERO (FLASH COAT NF CC VIVID B2)', 'Clasif2: PINTURAS (K). Clave sistema viejo: 000000788.', 'PINTURAS (K)', 'litro', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000789', 'EMPAQUE PARA VÁLVULA HIDRÁULICA BLAIN EV100 3/4', 'Clave sistema viejo: 000000789.', NULL, 'juego', 1);
INSERT INTO `refacciones` (`codigo`,`nombre`,`descripcion`,`categoria`,`unidad_medida`,`activo`) VALUES
  ('REF-000000790', 'BISAGRA SOLDABLE 1/2', 'Ubicacion/Clasif1: Rack 3/ Nivel B. Clasif2: FERRETERÍA (R). Clave sistema viejo: 000000790.', 'FERRETERÍA (R)', 'pieza', 1);

COMMIT;
-- Fin de la migracion.
