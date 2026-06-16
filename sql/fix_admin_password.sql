-- ============================================================================
-- fix_admin_password.sql
-- ============================================================================
-- Resetea la contraseña de admin a 'admin123' con un hash recién generado.
-- Ejecutar SOLO si el login con admin/admin123 no funciona.
-- ============================================================================

USE mantenimiento_bacal;

-- Verificar usuario admin
SELECT id, usuario, LEFT(password_hash, 30) AS hash_actual, activo, debe_cambiar_password
FROM usuarios
WHERE usuario = 'admin';

-- Resetear password a 'admin123'
UPDATE usuarios
SET password_hash = '$2y$10$Uc7tMA/MM8.IuyXWMMm6FeQjAqF0E/OhM9K4UNYdoQz5Tcdl2EBlO',
    activo = 1,
    intentos_fallidos = 0,
    bloqueado_hasta = NULL,
    debe_cambiar_password = 0
WHERE usuario = 'admin';

-- Confirmar
SELECT id, usuario, LEFT(password_hash, 30) AS hash_nuevo, activo
FROM usuarios
WHERE usuario = 'admin';

SELECT 'Listo. Login: admin / Contraseña: admin123' AS instrucciones;
