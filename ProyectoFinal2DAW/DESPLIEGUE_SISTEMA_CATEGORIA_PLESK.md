# 🚀 Guía de Despliegue: Sistema de Facturación por Categoría en Plesk

## 📋 Resumen de Cambios

Este despliegue implementa el sistema de facturación por categoría (peluquería/estética) en el servidor Plesk.

**Archivos modificados:**
- `app/Services/FacturacionService.php` - Nuevo método desglosarCobroPorCategoria()
- `app/Models/Empleado.php` - Nuevo método estático facturacionPorCategoriaPorFechas()
- `app/Models/BonoPlantilla.php` - Campo categoria añadido
- `app/Http/Controllers/FacturacionController.php` - Simplificado (326 → 155 líneas)
- `resources/views/facturacion/index.blade.php` - Desglose de bonos por categoría
- **Nueva migración:** `2026_01_24_165712_add_categoria_to_bonos_plantilla_table.php`

---

## 🔧 PASO 1: Acceder al Servidor Plesk

### Opción A: SSH (Recomendado)
```bash
ssh tu_usuario@tu_dominio.com
cd /var/www/vhosts/tu_dominio/httpdocs
```

### Opción B: File Manager de Plesk
1. Accede a Plesk: `https://tu_dominio:8443`
2. Ve a **Sitios Web y Dominios**
3. Click en **Administrador de archivos**
4. Navega a la raíz de tu aplicación Laravel

---

## 📥 PASO 2: Actualizar Código desde Git

### Si usas Git en Plesk:

```bash
# Cambiar al directorio de la aplicación
cd /var/www/vhosts/tu_dominio/httpdocs

# Hacer backup por seguridad
cp -r . ../backup_antes_categoria_$(date +%Y%m%d_%H%M%S)

# Actualizar desde repositorio
git fetch origin
git pull origin main
```

### Si NO usas Git (subida manual):

1. **Descargar cambios locales:**
   ```bash
   # En tu máquina local
   cd /home/nicolas/Descargas/ProyectoFInal2DAW/ProyectoFinalDAW/ProyectoFinal2DAW
   git archive --format=zip HEAD -o categoria_update.zip
   ```

2. **Subir a Plesk:**
   - Usa File Manager de Plesk o SFTP
   - Sube `categoria_update.zip`
   - Extrae en la raíz de la aplicación
   - **CUIDADO**: No sobrescribas `.env`

---

## 🗄️ PASO 3: Ejecutar la Migración (CRÍTICO)

Esta migración añade el campo `categoria` a la tabla `bonos_plantilla`:

### Método 1: Desde SSH (Recomendado)

```bash
# En el servidor, en la raíz de Laravel
cd /var/www/vhosts/tu_dominio/httpdocs

# Ejecutar migración para el tenant
php artisan tenants:run "php artisan migrate --path=database/migrations/tenant/2026_01_24_165712_add_categoria_to_bonos_plantilla_table.php"
```

### Método 2: Desde Scheduled Tasks de Plesk

Si no tienes acceso SSH:

1. Ve a **Tools & Settings** → **Scheduled Tasks** en Plesk
2. Crea una nueva tarea:
   - **Command type:** Run a PHP script
   - **Script path:** 
     ```
     /var/www/vhosts/tu_dominio/httpdocs/artisan
     ```
   - **Arguments:**
     ```
     tenants:run "php artisan migrate --path=database/migrations/tenant/2026_01_24_165712_add_categoria_to_bonos_plantilla_table.php"
     ```
   - **Run:** Una sola vez, ahora
3. Ejecuta la tarea

### Método 3: Script de migración manual

Si los métodos anteriores fallan, sube y ejecuta este script:

**Crear: `ejecutar_migracion_categoria.php`**
```php
<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Inicializar tenant
$tenant = \App\Models\Tenant::find('salonlh'); // Cambia por tu tenant ID
tenancy()->initialize($tenant);

// Ejecutar migración
Artisan::call('migrate', [
    '--path' => 'database/migrations/tenant/2026_01_24_165712_add_categoria_to_bonos_plantilla_table.php',
    '--force' => true
]);

echo "✅ Migración ejecutada correctamente\n";
echo Artisan::output();
```

Ejecutar desde navegador: `https://tu_dominio.com/ejecutar_migracion_categoria.php`

**⚠️ IMPORTANTE:** Elimina este archivo después de usarlo por seguridad.

---

## 🏷️ PASO 4: Asignar Categorías a Bonos Existentes

Después de la migración, debes asignar categorías a los bonos existentes:

### Método 1: Subir y ejecutar script

Ya tienes el script `asignar_categorias_bonos.php` en el repositorio.

```bash
# En el servidor
cd /var/www/vhosts/tu_dominio/httpdocs
php asignar_categorias_bonos.php
```

### Método 2: Desde navegador

Sube `asignar_categorias_bonos.php` a la raíz y accede:
```
https://tu_dominio.com/asignar_categorias_bonos.php
```

**⚠️ IMPORTANTE:** Elimina este archivo después de usarlo.

### Método 3: Manualmente desde base de datos

Si prefieres hacerlo manualmente en phpMyAdmin o Plesk Database Manager:

```sql
-- Conectar a la base de datos del tenant (salonlh_tenantXXX)
USE salonlh_tenantXXX;

-- Asignar categorías basándose en los servicios asociados
UPDATE bonos_plantilla bp
LEFT JOIN bono_servicios bs ON bp.id = bs.bono_id
LEFT JOIN servicios s ON bs.servicio_id = s.id
SET bp.categoria = COALESCE(s.categoria, 'peluqueria')
WHERE bp.categoria IS NULL;

-- Verificar
SELECT id, nombre, categoria FROM bonos_plantilla;
```

---

## 🧹 PASO 5: Limpiar Caché

Es crucial limpiar todas las cachés después de actualizar:

```bash
# En el servidor
cd /var/www/vhosts/tu_dominio/httpdocs

# Limpiar todas las cachés
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Si no tienes acceso SSH:

Crea un archivo temporal `limpiar_cache.php`:

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Artisan::call('cache:clear');
Artisan::call('config:clear');
Artisan::call('route:clear');
Artisan::call('view:clear');
Artisan::call('config:cache');
Artisan::call('route:cache');
Artisan::call('view:cache');

echo "✅ Caché limpiada y optimizada\n";
```

Ejecuta desde: `https://tu_dominio.com/limpiar_cache.php`

**⚠️ ELIMINA** este archivo después de usarlo.

---

## ✅ PASO 6: Verificar el Despliegue

### 6.1 Verificar Migración

Comprueba que el campo se añadió correctamente:

```bash
# SSH
php artisan tinker

# En tinker:
DB::connection('tenant')->table('bonos_plantilla')->first();
# Debe mostrar el campo 'categoria'
```

O desde phpMyAdmin:
```sql
DESCRIBE bonos_plantilla;
```

Debes ver la columna `categoria` tipo `VARCHAR(50)`.

### 6.2 Verificar Categorías Asignadas

```sql
SELECT id, nombre, categoria 
FROM bonos_plantilla 
WHERE categoria IS NULL;
```

**Resultado esperado:** 0 filas (todos los bonos deben tener categoría).

### 6.3 Probar la Vista

1. Accede a tu aplicación: `https://tu_dominio.com`
2. Inicia sesión
3. Ve a **Facturación** (o la ruta configurada)
4. Verifica que se muestre:
   - ✅ Servicios Peluquería
   - ✅ Servicios Estética
   - ✅ Productos Peluquería
   - ✅ Productos Estética
   - ✅ **Bonos Peluquería** (NUEVO - en azul)
   - ✅ **Bonos Estética** (NUEVO - en rosa)
   - ✅ Total Bonos
   - ✅ Totales generales

### 6.4 Verificar Logs

Revisa que no haya errores:

```bash
# Ver últimos logs
tail -n 50 storage/logs/laravel.log
```

O desde Plesk: **Logs** → **Error Log**

---

## 🔍 PASO 7: Testing en Producción (Opcional pero Recomendado)

Si quieres verificar que todo funciona correctamente, puedes ejecutar los tests:

```bash
# En el servidor
cd /var/www/vhosts/tu_dominio/httpdocs

# Test completo del sistema
php test_sistema_completo_categorias.php

# Test de casos edge
php test_edge_cases_categorias.php

# Test de vista de facturación
php test_vista_facturacion.php
```

**Resultado esperado:** Todas las verificaciones en ✅ verde.

---

## 📊 PASO 8: Monitoreo Post-Despliegue

Durante las primeras 24-48 horas después del despliegue:

### Revisar diariamente:

1. **Logs de errores:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Vista de facturación:**
   - Accede a la vista de facturación
   - Verifica que los totales sumen correctamente
   - Comprueba que el desglose por categoría sea lógico

3. **Base de datos:**
   ```sql
   -- Verificar que todos los bonos tienen categoría
   SELECT COUNT(*) as bonos_sin_categoria 
   FROM bonos_plantilla 
   WHERE categoria IS NULL;
   -- Debe ser 0
   ```

---

## 🚨 ROLLBACK: En Caso de Problemas

Si algo sale mal, puedes revertir los cambios:

### Rollback de Código:

```bash
# SSH
cd /var/www/vhosts/tu_dominio/httpdocs

# Restaurar desde backup
rm -rf *
cp -r ../backup_antes_categoria_YYYYMMDD_HHMMSS/* .

# O revertir git
git reset --hard HEAD~1
git push -f origin main
```

### Rollback de Migración:

```bash
# Revertir la migración
php artisan tenants:run "php artisan migrate:rollback --step=1"
```

O manualmente en SQL:
```sql
ALTER TABLE bonos_plantilla DROP COLUMN categoria;
```

---

## 📝 Checklist Final

Marca cada item cuando lo completes:

- [ ] **Backup creado** antes de cualquier cambio
- [ ] **Código actualizado** desde Git o subido manualmente
- [ ] **Migración ejecutada** correctamente
- [ ] **Categorías asignadas** a todos los bonos (0 NULL)
- [ ] **Caché limpiada** y optimizada
- [ ] **Vista de facturación** muestra bonos por categoría
- [ ] **Logs revisados** sin errores
- [ ] **Tests ejecutados** (opcional) - todos en verde
- [ ] **Scripts temporales eliminados** (por seguridad)
- [ ] **Monitoreo activo** durante 24-48h

---

## 🆘 Soporte y Resolución de Problemas

### Error: "Column 'categoria' not found"

**Causa:** La migración no se ejecutó correctamente.

**Solución:**
1. Verifica que la migración existe: `ls -la database/migrations/tenant/`
2. Ejecuta la migración manualmente (ver Paso 3)
3. Verifica en base de datos: `DESCRIBE bonos_plantilla;`

### Error: "Call to undefined method"

**Causa:** La caché no se limpió correctamente.

**Solución:**
```bash
php artisan cache:clear
php artisan config:clear
composer dump-autoload
```

### Error: Totales no cuadran

**Causa:** Posiblemente bonos sin categoría.

**Solución:**
```sql
-- Verificar bonos sin categoría
SELECT id, nombre, categoria FROM bonos_plantilla WHERE categoria IS NULL;

-- Asignar categoría por defecto
UPDATE bonos_plantilla SET categoria = 'peluqueria' WHERE categoria IS NULL;
```

### Vista no muestra cambios

**Causa:** Caché de vistas no limpiada.

**Solución:**
```bash
php artisan view:clear
php artisan view:cache
# Recargar página con Ctrl+Shift+R (hard refresh)
```

---

## 📞 Contacto

Si encuentras algún problema durante el despliegue:

1. **Revisa los logs:** `storage/logs/laravel.log`
2. **Comprueba la consola del navegador:** F12 → Console
3. **Verifica la base de datos:** phpMyAdmin o Plesk Database Manager
4. **Consulta esta documentación:** `IMPLEMENTACION_FACTURACION_CATEGORIA_COMPLETADA.md`

---

## 🎉 Finalización

Una vez completados todos los pasos, el sistema de facturación por categoría estará completamente operativo en tu servidor Plesk.

**Beneficios implementados:**
- ✅ Facturación desglosada por peluquería y estética
- ✅ Vista mejorada con bonos por categoría
- ✅ Código optimizado (50% menos líneas en controlador)
- ✅ Sistema robusto con manejo de edge cases
- ✅ Consistencia total en cálculos

**¡Despliegue completado con éxito! 🚀**
