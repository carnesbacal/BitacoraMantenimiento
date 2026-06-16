# 🔧 Bitácora de Mantenimiento — Carnes Bacal

Sistema interno de gestión de mantenimiento para Carnes Bacal Tijuana.

---

## 🚀 Acceso

**URL local:** `http://localhost/UtilidadesBacal/BitacoraMantenimiento/login.php`

**Credenciales iniciales:**
- Usuario: `admin`
- Contraseña: `admin123`

⚠️ **Cambia la contraseña al primer login** desde el avatar → Perfil.

---

## 📂 Estructura general del sistema

### Sidebar — secciones principales

| Sección | Descripción |
|---|---|
| **Dashboard** | KPIs en tiempo real, gráficas, equipos problemáticos |
| **Bitácora** | Lista de órdenes de trabajo (incidencias) |
| **Equipos** | Inventario de maquinaria con depreciación, fotos, transferencias |
| **Mantenimientos** | Programados preventivos con recurrencia |
| **Refacciones** | Catálogo maestro de piezas con stock por sucursal |
| **Almacén** | Dashboard de inventario, alertas de stock bajo, movimientos |
| **Herramientas** | Catálogo con sistema de préstamos a técnicos |
| **Mapa** | Vista física multi-planta con drag&drop de equipos |
| **Plantillas** | Plantillas reutilizables para órdenes frecuentes |
| **Base de conocimiento** | Guías y procedimientos |
| **Bóveda** | Documentos cifrados (manuales, MSDS, planos, garantías) |
| **Comunicación** | Anuncios y recordatorios |
| **Reportes** | Análisis y exportación |
| **Admin** | Usuarios, catálogos, backups, importación CSV |

---

## 🔧 Módulos específicos de mantenimiento

### Componentes de equipo

Cada equipo puede tener sus **partes/componentes** registrados (motor, banda, rodamientos, sensores, etc.).

**Cómo usar:**
1. Equipos → abre un equipo → botón "Componentes"
2. "Nuevo componente" → registra marca, modelo, número de parte, fecha instalación, vida útil, criticidad

**Cada componente lleva:**
- Estado: operando / con desgaste / en falla / reemplazado / retirado
- Criticidad: baja / media / alta / crítica
- Próxima revisión (alerta automática)
- Historial de cambios

### Refacciones y almacén

Catálogo maestro de piezas con **stock por sucursal**.

**Cómo usar:**
1. Refacciones → "Nueva refacción" → llena código, nombre, marca, costo, unidad
2. Abre la ficha → configura el stock mínimo por sucursal (⚙️)
3. "Registrar movimiento" → Entrada al recibir compra, Salida cuando se usa, Ajuste para conteo físico

**Cuando se usa en una orden de trabajo:**
1. Abre la incidencia → botón "Refacciones"
2. La sidebar muestra **sugerencias** (compatibles con el equipo + más usadas históricamente)
3. Click el "+" verde junto a la sugerencia
4. **Automáticamente se descuenta del stock** y se vincula al equipo como compatible

### Herramientas y préstamos

Catálogo de herramientas con sistema de préstamos a técnicos.

**Cómo usar:**
1. Herramientas → "Nueva herramienta" → llena código, nombre, tipo, marca, ubicación
2. Cuando se presta: botón **"Prestar"** → selecciona técnico, fecha esperada de devolución, motivo
3. Cuando devuelve: botón **"Registrar devolución"** → 4 condiciones posibles:
   - **Buena** → herramienta vuelve a disponible
   - **Dañada** → herramienta pasa a "en reparación"
   - **Extraviada** → herramienta marcada como extraviada
   - **Reparada** → herramienta vuelve a disponible (el técnico la reparó)

**Estados de la herramienta:**
- 🟢 Disponible — lista para prestar
- 🟡 Prestada — está con un técnico
- 🟠 En reparación — no disponible temporalmente
- 🔴 Extraviada — perdida
- ⚫ Dada de baja — fuera de servicio definitivo

---

## 👥 Roles del sistema

| Rol | Permisos |
|---|---|
| **Administrador** | Todo |
| **Técnico de Mantenimiento** | Resuelve órdenes, ve todas las sucursales, gestiona refacciones |
| **Arquitecto / Auxiliar** | Mismos permisos que Técnico (para personal que apoya en mantenimiento) |
| **Supervisor de Planta** | Ve reportes y gestiona catálogos de su planta |
| **Operador / Reportante** | Reporta fallas y consulta el estado de sus reportes |
| **Solo Lectura** | Consulta sin modificar |

---

## 🔄 Flujo típico de una falla

```
1. Operador reporta falla → crea Incidencia
2. Sistema sugiere categoría/severidad/técnico automáticamente
3. Técnico recibe notificación
4. Abre la orden → cambia estado a "En proceso"
5. Va a "Refacciones" → registra qué piezas usa (stock se descuenta solo)
6. Si presta una herramienta → se registra en su nombre
7. Resuelve → cambia estado a "Completada"
8. Sistema cierra y archiva
```

---

## 💾 Backups y mantenimiento

**Backups automáticos:** Admin → Backups → "Crear backup ahora" o programados.

**Restaurar:** desde phpMyAdmin importando el `.sql` generado.

**Importar datos masivamente:** Admin → Importar CSV (equipos, refacciones, etc.).

---

## 🆘 Soporte

Si algo falla:
1. Revisa el log de errores de PHP (`C:\xampp\php\logs\php_error_log`)
2. Verifica que MySQL esté corriendo en XAMPP
3. Asegúrate de que `config/db.php` apunta a `mantenimiento_bacal`

---

## 📊 BD: `mantenimiento_bacal`

Estructura técnica de tablas clave:
- `usuarios`, `roles`, `sucursales`
- `areas`, `categorias`, `tipos_trabajo`, `severidades`, `estados`
- `incidencias`, `incidencias_comentarios`, `incidencias_historial`
- `equipos`, `equipo_componentes`, `equipo_componentes_historial`
- `refacciones`, `refacciones_stock`, `refacciones_movimientos`, `refacciones_compatibles`
- `incidencia_refacciones` (relación)
- `herramientas`, `herramientas_prestamos`
- `mantenimientos` (preventivos programados)
- `proveedores`, `plantillas_incidencias`
- `vault_categorias`, `vault_entradas` (bóveda cifrada AES-256)
- `notificaciones`, `auditoria_sistema`, `sesiones`

---

**Sistema operativo, ¡a usarlo!** 🚀
