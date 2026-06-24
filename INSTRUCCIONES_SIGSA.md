# Instrucciones para replicar el branding en SIGSA (Bitácora de Sistemas)

Pega esto en el chat del otro proyecto. Está escrito para que el asistente lo adapte a tu estructura de archivos (que es muy parecida).

---

Quiero replicar el branding que ya hicimos en el proyecto hermano (SIGMA). Este proyecto es **SIGSA = Sistema Integral de Gestión de Sistemas y Activos** (bitácora de sistemas). El branding base sigue siendo **Carnes Bacal** (mismo logo). Aplica estos cambios y valida sintaxis sin romper nada (edita por consola, no con herramientas que trunquen archivos; verifica 0 bytes NUL al final):

## 1. Nombre de la app
En el archivo de config donde se define `APP_NAME` (algo como `config/app.php`):
- Cambia `APP_NAME` a: `'SIGSA · Carnes Bacal'`

## 2. Logo (mismos dos archivos que SIGMA)
Coloca en `assets/img/`:
- `logo-negro.png` → logo con letras **negras** (para modo claro)
- `logo-blanco.png` → logo con letras **blancas** (para modo oscuro)

El logo es el wordmark "Carnes Bacal" (no es cuadrado). **No quites el cuadrito con la "B"** que ya existe como ícono.

Requisito: el modo oscuro debe estar en `darkMode: 'class'` (clase `dark` en `<html>`). Si ya lo está, las clases `dark:hidden` / `hidden dark:block` funcionan.

## 3. Marca del sidebar (en el header)
Donde hoy dice el texto "Carnes Bacal / [subtítulo]", **conserva la "B"** y reemplaza el texto por: **SIGSA arriba** y el **logo Carnes Bacal abajo** (cambia según tema). Patrón:

```php
<div x-show="sidebarAbierto" x-transition.opacity class="overflow-hidden">
    <div class="font-display font-extrabold text-bacal-700 text-sm leading-tight tracking-wide">SIGSA</div>
    <img src="<?= url('assets/img/logo-negro.png') ?>" alt="Carnes Bacal" onerror="this.style.display='none'"
         class="h-5 w-auto block dark:hidden mt-0.5">
    <img src="<?= url('assets/img/logo-blanco.png') ?>" alt="Carnes Bacal" onerror="this.style.display='none'"
         class="h-5 w-auto hidden dark:block mt-0.5">
</div>
```
(El `onerror` es para que no se vea imagen rota si aún no existe el archivo. Ajusta `text-bacal-700` al color de marca del proyecto.)

## 4. Login: panel de marca centrado
En el panel oscuro del login, usa el **logo blanco**, y el bloque central va **centrado vertical y horizontalmente**. El contenedor del panel debe ser `flex flex-col` (sin `justify-between`), el logo arriba, el bloque central con `flex-1 flex flex-col justify-center items-center text-center`, y el footer abajo.

Bloque central (ajusta los chips a los módulos reales de SIGSA, ej. Tickets, Equipos, Licencias, Inventario, Usuarios, Redes):

```php
<div class="flex-1 flex flex-col justify-center items-center text-center">
    <div class="w-full max-w-lg space-y-5">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-xs font-semibold uppercase tracking-wider">
            <span class="w-1.5 h-1.5 rounded-full bg-gold-400 animate-pulse"></span>
            Bitácora de Sistemas
        </div>
        <h2 class="font-display text-7xl xl:text-8xl font-extrabold leading-[0.9] tracking-tight">
            <span class="text-gold-400">SIGSA</span>
        </h2>
        <p class="font-display text-2xl xl:text-3xl font-bold leading-tight">
            Control total de tus sistemas y activos, en un solo lugar.
        </p>
        <!-- Revelado del acrónimo: resalta las iniciales S·I·G·S·A -->
        <p class="text-white/90 text-base leading-snug font-semibold">
            <span class="text-gold-400">S</span>istema <span class="text-gold-400">I</span>ntegral de <span class="text-gold-400">G</span>estión de <span class="text-gold-400">S</span>istemas y <span class="text-gold-400">A</span>ctivos.
        </p>
        <p class="text-white/65 text-sm leading-relaxed">
            <!-- describe aquí los módulos de SIGSA -->
        </p>
        <div class="flex flex-wrap gap-2 justify-center pt-1">
            <?php foreach (['Tickets', 'Equipos', 'Licencias', 'Inventario', 'Usuarios'] as $chip): ?>
            <span class="px-2.5 py-1 rounded-full bg-white/10 border border-white/15 text-[11px] font-semibold text-white/85"><?= $chip ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
```

El logo del login (versión blanca) a ~`h-7`. El subtítulo del logo arriba puede decir "SIGSA · Sistema Interno".

## 5. Firma del desarrollador (distintiva, en todos mis proyectos)
Estilo "firma de código": **`‹LFRC/›`** en monoespaciado. Va en dos lugares:

- **Footer del login** (junto al © y la versión):
  ```php
  <span>Desarrollado por <span class="font-mono font-semibold text-white/75">&lt;LFRC/&gt;</span></span>
  ```
- **Pie de toda la app** (en el footer global, antes de cerrar `</main>`), discreto y compatible con modo oscuro:
  ```php
  <div class="px-6 pb-4 text-center text-[11px] text-zinc-400 dark:text-zinc-600 select-none">
      Desarrollado por <span class="font-mono font-semibold tracking-tight text-zinc-500 dark:text-zinc-400">&lt;LFRC/&gt;</span>
  </div>
  ```

## 6. Validación
Al terminar: revisa que no haya errores de PHP, que los archivos no queden truncados ni con bytes NUL, y prueba el login y una página interna con Ctrl+F5 en modo claro y oscuro.
