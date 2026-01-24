# 🚀 GUÍA RÁPIDA DE DESPLIEGUE EN PLESK

## ✅ Commits Realizados

```
✅ Commit 1 (b7c6d70): Sistema de facturación por categoría
   - 21 archivos modificados
   - 2352 líneas añadidas, 223 eliminadas
   
✅ Commit 2 (a4f4e93): Documentación de despliegue Plesk
   - Guía completa y script automatizado
```

---

## 🎯 DESPLIEGUE RÁPIDO (5 minutos)

### OPCIÓN A: Script Automatizado (Recomendado) 🚀

1. **Accede a tu servidor por SSH:**
   ```bash
   ssh tu_usuario@tu_dominio.com
   cd /var/www/vhosts/tu_dominio/httpdocs
   ```

2. **Actualiza el código:**
   ```bash
   git pull origin main
   ```

3. **Ejecuta el script:**
   ```bash
   bash deploy_categoria_plesk.sh
   ```

4. **Sigue las instrucciones en pantalla**
   - El script hace backup automático
   - Ejecuta la migración
   - Asigna categorías a bonos
   - Limpia cachés
   - Verifica todo

**¡Listo en 5 minutos! ✨**

---

### OPCIÓN B: Despliegue Manual (15 minutos) 📋

Si no tienes acceso SSH o prefieres control total:

#### 1️⃣ Actualizar Código
```bash
cd /var/www/vhosts/tu_dominio/httpdocs
git pull origin main
# O sube archivos manualmente por SFTP
```

#### 2️⃣ Ejecutar Migración (CRÍTICO)
```bash
php artisan tenants:run "php artisan migrate --path=database/migrations/tenant/2026_01_24_165712_add_categoria_to_bonos_plantilla_table.php"
```

#### 3️⃣ Asignar Categorías
```bash
php asignar_categorias_bonos.php
```

#### 4️⃣ Limpiar Cachés
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 5️⃣ Verificar
- Accede a `/facturacion` en tu app
- Verifica que se muestre el desglose de bonos por categoría

---

### OPCIÓN C: Desde Plesk Panel (Sin SSH) 🖱️

#### 1. Subir Archivos
- **Plesk → Sitios Web → Administrador de Archivos**
- Sube los archivos cambiados manualmente
- O haz `git pull` desde terminal de Plesk

#### 2. Ejecutar Migración desde Navegador

Crea archivo temporal `ejecutar_migracion.php` en la raíz:

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenant = \App\Models\Tenant::find('salonlh');
tenancy()->initialize($tenant);

Artisan::call('migrate', [
    '--path' => 'database/migrations/tenant/2026_01_24_165712_add_categoria_to_bonos_plantilla_table.php',
    '--force' => true
]);

echo "✅ Migración ejecutada\n";
echo Artisan::output();
```

Accede: `https://tu_dominio.com/ejecutar_migracion.php`

**⚠️ ELIMINA el archivo después!**

#### 3. Asignar Categorías

Accede: `https://tu_dominio.com/asignar_categorias_bonos.php`

**⚠️ ELIMINA el archivo después!**

#### 4. Limpiar Cachés

Crea `limpiar_cache.php`:

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Artisan::call('cache:clear');
Artisan::call('config:clear');
Artisan::call('view:clear');
Artisan::call('config:cache');
Artisan::call('route:cache');
Artisan::call('view:cache');

echo "✅ Caché limpiada";
```

Accede: `https://tu_dominio.com/limpiar_cache.php`

**⚠️ ELIMINA el archivo después!**

---

## 🔍 Verificación Post-Despliegue

### ✅ Checklist de 2 minutos:

1. [ ] Accede a `/facturacion` en tu aplicación
2. [ ] Verifica que se muestren:
   - [ ] 💇 Bonos Peluquería (azul)
   - [ ] ✨ Bonos Estética (rosa)
   - [ ] Total Bonos
3. [ ] Los totales suman correctamente
4. [ ] No hay errores en `storage/logs/laravel.log`

### 🗄️ Verificación en Base de Datos:

```sql
-- Ver que todos los bonos tienen categoría
SELECT id, nombre, categoria FROM bonos_plantilla;

-- Verificar que no hay NULL
SELECT COUNT(*) FROM bonos_plantilla WHERE categoria IS NULL;
-- Debe ser 0
```

---

## 🚨 Troubleshooting Rápido

### ❌ Error: "Column 'categoria' not found"
**→ Solución:** La migración no se ejecutó. Ejecuta paso 2️⃣

### ❌ Error: Vista no muestra cambios
**→ Solución:** Limpia caché con `php artisan view:clear`

### ❌ Error: Totales no cuadran
**→ Solución:** Verifica que todos los bonos tienen categoría

### 🔄 Rollback (si algo sale mal):
```bash
git reset --hard HEAD~2
git push -f origin main
# O restaura desde backup
```

---

## 📚 Documentación Completa

Para más detalles, consulta:

1. **[DESPLIEGUE_SISTEMA_CATEGORIA_PLESK.md](DESPLIEGUE_SISTEMA_CATEGORIA_PLESK.md)**
   - Guía paso a paso completa
   - Métodos alternativos
   - Troubleshooting detallado

2. **[IMPLEMENTACION_FACTURACION_CATEGORIA_COMPLETADA.md](IMPLEMENTACION_FACTURACION_CATEGORIA_COMPLETADA.md)**
   - Detalles técnicos de la implementación
   - Tests y verificaciones

3. **[ACTUALIZACION_VISTA_FACTURACION.md](ACTUALIZACION_VISTA_FACTURACION.md)**
   - Cambios en el controlador y vista

---

## 🎉 Resultado Final

Después del despliegue, tu sistema tendrá:

✅ **Facturación desglosada por categoría**
- Servicios Peluquería / Estética
- Productos Peluquería / Estética  
- **Bonos Peluquería / Estética** (NUEVO)

✅ **Código optimizado**
- 50% menos líneas en FacturacionController
- Sistema consistente y mantenible

✅ **Manejo robusto**
- Edge cases cubiertos
- Factor de ajuste aplicado
- Validaciones completas

---

## 📞 Soporte

Si tienes problemas:

1. Revisa los logs: `tail -f storage/logs/laravel.log`
2. Consulta la documentación completa
3. Verifica la base de datos en phpMyAdmin
4. Ejecuta los scripts de test para diagnóstico

---

**Tiempo estimado de despliegue:**
- ⚡ Con script automatizado: **5 minutos**
- 📋 Manual con SSH: **15 minutos**
- 🖱️ Desde Plesk Panel: **20 minutos**

**¡Buena suerte con el despliegue! 🚀**
