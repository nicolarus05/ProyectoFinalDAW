# 🐛 BUG CRÍTICO: Aplicación Incorrecta de Bonos - CORREGIDO

## 📋 Resumen Ejecutivo

**Fecha**: 06/02/2026  
**Impacto**: Crítico - Servicios cobrados a €0 sin bono válido  
**Casos afectados**: Cobros #723 y #727 (€32 en total)  
**Estado**: ✅ CORREGIDO

---

## 🔍 Problema Identificado

### Caso 1: Cobro #727 - Pilar
- **Cliente**: Pilar (ID: 90)
- **Servicio**: Peinado melena (€16)
- **Situación**: Tenía bono "Bono color + 4 peinados" AGOTADO (4/4 usados)
- **Error**: Se aplicó precio €0 incorrectamente
- **Causa**: La búsqueda en `bono_uso_detalle` encontró uso del **bono #18 de OTRO cliente** (#89) dentro de la ventana de 24h

### Caso 2: Cobro #723 - Demelsa
- **Cliente**: Demelsa (ID: 229)
- **Servicio**: Peinado melena (€16)
- **Situación**: NO tenía bonos activos
- **Error**: Se aplicó precio €0 incorrectamente
- **Causa**: La búsqueda en `bono_uso_detalle` encontró 2 usos de **OTROS clientes** (#89 y #262) dentro de la ventana de 24h

---

## 🐛 Causa Root

**Ubicación**: `app/Http/Controllers/RegistroCobroController.php`

### Bug 1: Líneas 938-943 (cobros con cita)

```php
// CÓDIGO INCORRECTO ❌
$usoBono = DB::table('bono_uso_detalle')
    ->where('servicio_id', $servicio->id)
    ->where('cita_id', $cita->id)
    ->where('created_at', '>=', now()->subHours(24))  // ⚠️ PROBLEMA
    ->exists();
```

**Problema**: La condición `created_at >= now()->subHours(24)` NO filtra correctamente porque usa `now()` en lugar de la fecha del cobro. Además, no es necesaria si ya se busca por `cita_id`.

### Bug 2: Líneas 1217-1220 (cobros directos)

```php
// CÓDIGO INCORRECTO ❌
if (!$usoBono) {
    $usoBono = DB::table('bono_uso_detalle')
        ->where('servicio_id', $servicioId)
        ->where('created_at', '>=', now()->subHours(24))  // ⚠️ PROBLEMA CRÍTICO
        ->exists();
}
```

**Problema**: Esta búsqueda NO filtra por cliente ni por cita, capturando usos de **CUALQUIER cliente** que haya usado ese servicio en las últimas 24 horas.

---

## ✅ Solución Aplicada

### Corrección 1: Cobros con cita (línea 935-943)

```php
// CÓDIGO CORREGIDO ✅
$usoBono = DB::table('bono_uso_detalle')
    ->where('servicio_id', $servicio->id)
    ->where('cita_id', $cita->id)
    // ELIMINADO: ->where('created_at', '>=', now()->subHours(24))
    ->exists();
```

**Justificación**: Si el registro existe en `bono_uso_detalle` con `cita_id` y `servicio_id`, significa que se aplicó un bono. No necesitamos filtro temporal.

### Corrección 2: Cobros directos (línea 1205-1225)

```php
// CÓDIGO CORREGIDO ✅
$usoBono = false;

// Solo verificar si el cobro tiene citas agrupadas
if (!empty($data['citas_ids']) && is_array($data['citas_ids'])) {
    $usoBono = DB::table('bono_uso_detalle')
        ->where('servicio_id', $servicioId)
        ->whereIn('cita_id', $data['citas_ids'])
        ->exists();
}
// Para cobros directos sin cita: NO buscar en bono_uso_detalle
// Los bonos ya se aplicaron en las líneas 614-720

// ELIMINADO COMPLETAMENTE:
// if (!$usoBono) {
//     $usoBono = DB::table('bono_uso_detalle')
//         ->where('servicio_id', $servicioId)
//         ->where('created_at', '>=', now()->subHours(24))
//         ->exists();
// }
```

**Justificación**: 
- Para cobros con citas agrupadas: buscar por `cita_id` específica
- Para cobros directos sin cita: NO buscar, porque los bonos ya se procesaron correctamente en las líneas 614-720

---

## 🧪 Validación

### Escenarios de prueba

1. **✅ Cobro con cita y bono aplicado**:
   - Debe marcar el servicio a €0
   - Debe existir registro en `bono_uso_detalle` con la `cita_id` exacta

2. **✅ Cobro con cita SIN bono**:
   - Debe cobrar precio completo del servicio
   - NO debe existir registro en `bono_uso_detalle` con esa `cita_id`

3. **✅ Cobro directo con bono agotado**:
   - Debe cobrar precio completo del servicio
   - La verificación en líneas 614-720 debe detectar que no hay disponibilidad

4. **✅ Cobro directo sin bonos**:
   - Debe cobrar precio completo del servicio
   - NO debe marcar a €0 por usos de otros clientes

---

## 📊 Impacto de la Corrección

### Antes del fix:
- ❌ Servicios marcados a €0 si **cualquier cliente** usó un bono en las últimas 24h
- ❌ Bonos agotados seguían aplicando descuento
- ❌ Clientes sin bonos recibían descuentos incorrectos

### Después del fix:
- ✅ Servicios marcados a €0 **solo si ese cliente/cita tiene bono aplicado**
- ✅ Bonos agotados NO aplican descuento
- ✅ Clientes sin bonos pagan precio completo

---

## 🔄 Acciones Pendientes

### 1. Corrección de datos históricos ✅
- **Ejecutado**: Script `corregir_facturacion_raquel.php`
- **Resultado**: Cobros #723 y #727 corregidos de €0 a €16

### 2. Testing
- [ ] Crear test unitario para verificación de bonos
- [ ] Probar escenarios edge case
- [ ] Validar con datos de producción

### 3. Monitoreo
- [ ] Revisar cobros futuros para asegurar correcto funcionamiento
- [ ] Crear alerta si aparecen servicios a €0 sin bono válido

---

## 📝 Lecciones Aprendidas

### 1. Búsquedas temporales amplias son peligrosas
- La ventana de 24 horas sin filtros de cliente causó colisiones
- **Mejor práctica**: Siempre filtrar por entidad específica (cliente, cita, etc.)

### 2. Validación en múltiples capas
- Los bonos se aplican en líneas 519-720 (con validación de disponibilidad)
- La verificación en líneas 938-943 debe ser **confirmación**, no nueva lógica
- Duplicar lógica de negocio causa inconsistencias

### 3. Logs son esenciales
- Los logs existentes ayudaron a diagnosticar el problema
- **Mejora**: Añadir log cuando NO se encuentra bono pero servicio está a €0

---

## 🛠️ Archivos Modificados

| Archivo | Líneas | Cambio |
|---------|--------|--------|
| `app/Http/Controllers/RegistroCobroController.php` | 938-943 | Eliminada condición `created_at >= now()->subHours(24)` |
| `app/Http/Controllers/RegistroCobroController.php` | 1215-1222 | Eliminada búsqueda genérica de 24h sin filtros |

---

## ✅ Checklist de Corrección

- ✅ Bug identificado y documentado
- ✅ Causa root analizada
- ✅ Código corregido
- ✅ Datos históricos corregidos (cobros #723 y #727)
- ✅ Documentación creada
- ⏳ Tests pendientes
- ⏳ Deploy a producción
- ⏳ Monitoreo post-deploy

---

**Fecha de corrección**: 06/02/2026  
**Responsable**: Sistema automatizado  
**Revisión**: Pendiente
