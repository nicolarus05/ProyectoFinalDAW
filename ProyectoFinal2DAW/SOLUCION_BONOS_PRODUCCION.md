# 🔍 Solución: Problema de Bonos en Producción

## 📋 Resumen del Problema

En **local** los bonos funcionan correctamente, pero en **producción** NO se descuentan los usos al cobrar.

## ✅ Cambios Realizados

### 1. Script de Diagnóstico
**Archivo:** `diagnostico_bonos_produccion.php`

Este script verifica:
- ✅ Conexión a base de datos
- ✅ Bonos activos existentes
- ✅ Últimos cobros realizados
- ✅ Registros de uso de bonos
- ✅ Test de actualización de pivot
- ✅ Configuración de transacciones
- ✅ Permisos de escritura en BD
- ✅ Configuración de caché/Redis

#### Ejecutar en producción:
```bash
php diagnostico_bonos_produccion.php
```

### 2. Logging Detallado
**Archivo modificado:** `app/Http/Controllers/RegistroCobroController.php`

Se ha añadido logging detallado en ambos casos:
- **CASO A:** Cobro con citas (línea 520)
- **CASO B:** Cobro directo sin cita (línea 621)

#### Logs que verás en `storage/logs/laravel.log`:
```
[timestamp] 🎫 PROCESANDO BONOS {...}
[timestamp] 🔍 Bonos activos encontrados {...}
[timestamp] 🔄 Procesando servicio de cita {...}
[timestamp] ✅ APLICANDO BONO {...}
[timestamp] 📝 Uso de bono registrado {...}
[timestamp] 🏁 Bono marcado como usado completamente {...}
```

## 🔧 Pasos para Diagnosticar en Producción

### Paso 1: Ejecutar el Script de Diagnóstico
```bash
ssh tu-servidor
cd /ruta/del/proyecto
php diagnostico_bonos_produccion.php
```

Esto te dirá:
- Si hay bonos activos
- Si se están registrando usos
- Si hay problemas de permisos
- Si la configuración de BD es correcta

### Paso 2: Limpiar Caché
```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

Si usas **OpCache** en el servidor:
```bash
# En el servidor
sudo systemctl reload php8.2-fpm  # o tu versión de PHP
```

### Paso 3: Hacer un Cobro de Prueba

1. Identifica un cliente con bono activo
2. Haz un cobro con un servicio que incluya ese bono
3. Revisa los logs inmediatamente:

```bash
tail -f storage/logs/laravel.log | grep "🎫\|✅\|❌"
```

### Paso 4: Verificar Directamente en la Base de Datos

```sql
-- Ver bonos activos
SELECT bc.id, bc.estado, bc.fecha_expiracion, 
       u.nombre, u.apellidos, 
       bp.nombre as plantilla
FROM bonos_clientes bc
JOIN clientes c ON bc.cliente_id = c.id
JOIN users u ON c.user_id = u.id
JOIN bonos_plantilla bp ON bc.bono_plantilla_id = bp.id
WHERE bc.estado = 'activo'
  AND bc.fecha_expiracion >= NOW();

-- Ver servicios de un bono específico
SELECT s.nombre, bcs.cantidad_total, bcs.cantidad_usada,
       (bcs.cantidad_total - bcs.cantidad_usada) as disponibles
FROM bono_cliente_servicios bcs
JOIN servicios s ON bcs.servicio_id = s.id
WHERE bcs.bono_cliente_id = [BONO_ID];

-- Ver últimos usos de bonos
SELECT bud.*, bc.id as bono_id, s.nombre as servicio,
       bp.nombre as plantilla, u.nombre as cliente
FROM bono_uso_detalle bud
JOIN bonos_clientes bc ON bud.bono_cliente_id = bc.id
JOIN servicios s ON bud.servicio_id = s.id
JOIN bonos_plantilla bp ON bc.bono_plantilla_id = bp.id
JOIN clientes c ON bc.cliente_id = c.id
JOIN users u ON c.user_id = u.id
ORDER BY bud.created_at DESC
LIMIT 10;
```

## 🔍 Posibles Causas del Problema

### 1. Código No Actualizado en Producción
**Síntoma:** El diagnóstico muestra que no hay registros en `bono_uso_detalle`

**Solución:**
```bash
# Asegúrate de que el código está actualizado
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
```

### 2. Caché de Código (OpCache)
**Síntoma:** Los logs no aparecen después de actualizar el código

**Solución:**
```bash
sudo systemctl reload php-fpm
# O reiniciar el servicio web
sudo systemctl restart apache2  # o nginx
```

### 3. Base de Datos Diferente
**Síntoma:** El diagnóstico muestra una BD distinta a la esperada

**Solución:**
- Verificar `.env` en producción
- Asegurarse de que `DB_DATABASE` apunta a la base de datos correcta

### 4. Problema con Transacciones
**Síntoma:** El updateExistingPivot falla silenciosamente

**Solución:**
- Verificar que la tabla usa InnoDB (soporta transacciones)
- Revisar permisos del usuario de BD

### 5. Condiciones de la Consulta
**Síntoma:** Los bonos no se encuentran (where fecha_expiracion >= NOW())

**Solución:**
```sql
-- Verificar fechas de expiración
SELECT id, fecha_expiracion, NOW() as ahora,
       CASE WHEN fecha_expiracion >= NOW() THEN 'VÁLIDO' ELSE 'EXPIRADO' END as estado
FROM bonos_clientes
WHERE estado = 'activo';
```

Si las fechas están en el pasado, el WHERE filtrará todos los bonos.

### 6. Cliente ID No se Pasa Correctamente
**Síntoma:** En logs ves `cliente_id: null`

**Solución:**
- Verificar que el frontend envía correctamente `id_cliente`
- Revisar la red del navegador (F12 > Network) para ver los datos enviados

## 📊 Qué Debe Suceder

### Flujo Normal (Funcionando):
```
1. Usuario selecciona cliente con bono
2. Añade servicio que incluye el bono
3. Frontend detecta bono y resta el precio del total
4. Al confirmar cobro:
   📤 Se envía: cliente_id, servicios, total (ya con descuento)
   
5. Backend (RegistroCobroController):
   🔍 Busca bonos activos del cliente
   ✅ Encuentra bono con servicio disponible
   📝 Ejecuta: updateExistingPivot (cantidad_usada + 1)
   💾 Crea: BonoUsoDetalle
   🏁 Si está completo: marca bono como 'usado'
   
6. Resultado:
   ✅ cantidad_usada incrementada en bono_cliente_servicios
   ✅ Registro creado en bono_uso_detalle
   ✅ Servicio en registro_cobro_servicio con precio = 0
```

### Si NO Funciona:
Uno de estos pasos falla. El logging te dirá exactamente cuál.

## 📝 Interpretación de Logs

### Logs Buenos (Todo Funciona):
```
[2026-02-05] 🎫 PROCESANDO BONOS {"se_vende_bono":false,"cliente_id":123}
[2026-02-05] 🔍 Bonos activos encontrados {"cantidad_bonos":1}
[2026-02-05] 🔄 Procesando servicio de cita {"servicio_id":5}
[2026-02-05] ✅ APLICANDO BONO {"bono_id":45,"cantidad_usada_antes":2,"cantidad_usada_despues":3}
[2026-02-05] 📝 Uso de bono registrado {"bono_id":45}
```

### Logs Problemáticos:

#### A) No llega a procesar bonos:
```
[2026-02-05] 🎫 PROCESANDO BONOS {"se_vende_bono":true,...}
```
→ **Problema:** Se está vendiendo un bono, no aplicando uno existente

#### B) No encuentra bonos:
```
[2026-02-05] 🔍 Bonos activos encontrados {"cantidad_bonos":0}
```
→ **Problema:** No hay bonos activos o el WHERE los filtra

#### C) No encuentra servicio en bono:
```
[2026-02-05] ⏭️ Servicio no encontrado en este bono
```
→ **Problema:** El bono no incluye ese servicio o ya está agotado

## 🚀 Después de Solucionar

Una vez identificado y solucionado el problema:

1. **Quitar el logging excesivo** (opcional, para rendimiento):
   - Los logs de diagnóstico son útiles pero pueden generar muchos datos
   - Puedes dejar solo los logs de "APLICANDO BONO" para trazabilidad

2. **Verificar que todo funcione:**
```bash
php diagnostico_bonos_produccion.php
```

3. **Hacer pruebas reales:**
   - Hacer varios cobros con bonos
   - Verificar que se descuenten correctamente
   - Comprobar que los bonos se marquen como "usado" al agotarse

## 📞 Contacto

Si después de seguir estos pasos el problema persiste:
1. Comparte el output del script de diagnóstico
2. Comparte los últimos 50 logs de `storage/logs/laravel.log`
3. Comparte el resultado de las queries SQL

---

**Última actualización:** 05/02/2026
