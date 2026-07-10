# Importación automática de Monsat por correo (IMAP + cron)

Objetivo: que los reportes que Monsat envía por correo se importen **solos**, sin que nadie suba archivos. Todo se configura **dentro de la app** (Flotilla → Import. Monsat → "Correo automático"), con **una cuenta por sucursal** para poder escalar.

Cómo funciona: un cron corre el script `cron/monsat_importar_correo.php`, que lee las cuentas IMAP configuradas, toma los adjuntos XLS de los correos nuevos, importa el km diario (idempotente) y avanza los odómetros.

---

## Pasos (una sola vez)

### 1. Crear la tabla
Corre en phpMyAdmin: **`migracion_monsat_cuentas.sql`**.

### 2. Habilitar la extensión IMAP de PHP
cPanel → **Select PHP Version** → pestaña **Extensions** → activa **`imap`** y guarda.

### 3. Configurar la cuenta de correo (en la app)
Entra a **Flotilla → Import. Monsat**, baja a **"Correo automático"** y llena una cuenta:

- **Sucursal:** elige la sucursal (o "Global / Todas" si un solo buzón recibe todo).
- **Host IMAP:** normalmente `mail.granodeoro.com.mx` (lo ves en cPanel → Email Accounts → Connect Devices → *Incoming Server (IMAP)*).
- **Puerto:** `993` (SSL).
- **Usuario:** el correo, p. ej. `monsat@granodeoro.com.mx`.
- **Contraseña:** la del buzón (se guarda **cifrada** con AES).
- **Carpeta:** `INBOX`. **Solo no leídos** y **marcar como leídos** activados (recomendado).

Guarda y usa **"Probar conexión"** (ícono de enchufe) para verificar que conecta. Con **"Importar ahora"** (ícono de descarga) puedes correr la importación manualmente cuando quieras.

> Para otra sucursal con su propio Monsat/correo: agrega **otra cuenta** con su sucursal y su buzón. El cron las procesa todas.

### 4. Programar el cron en cPanel
cPanel → **Cron Jobs** → nuevo:

- **Frecuencia:** el correo llega de madrugada; corre el script después, p. ej. diario 6am (`0 6 * * *`) o cada 4 horas (`0 */4 * * *`).
- **Comando** (ajusta la ruta real de tu cuenta y del PHP de cPanel):

```
/usr/local/bin/php /home/USUARIO/public_html/RUTA/cron/monsat_importar_correo.php >> /home/USUARIO/monsat_cron.log 2>&1
```

### 5. Verificar
- Con **"Importar ahora"** en la app, o esperando al cron y revisando `monsat_cron.log`.
- En la lista de cuentas se muestra la **última ejecución** y su resultado.
- Revisa en **Flotilla → Reportes** que el km del día ya esté cargado.

---

## Notas
- **Seguridad:** la contraseña se guarda cifrada. Usa un buzón dedicado solo para estos reportes.
- **Idempotente:** reprocesar el mismo correo no duplica datos.
- **Dispositivos no reconocidos:** si un nombre en Monsat no empata con el alias de un vehículo, se ignora y se anota; ajusta el alias o el nombre en Monsat.
- **Respaldo manual:** la subida de archivos en Import. Monsat sigue disponible en cualquier momento.
