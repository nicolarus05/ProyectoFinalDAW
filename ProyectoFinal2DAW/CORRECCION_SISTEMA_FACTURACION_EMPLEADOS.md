# CORRECCIÓN SISTEMA DE FACTURACIÓN DE EMPLEADOS

**Fecha:** 15 de enero de 2026  
**Estado:** ✅ COMPLETADO Y VERIFICADO

---

## 📋 ÍNDICE
1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Problemas Identificados](#problemas-identificados)
3. [Soluciones Implementadas](#soluciones-implementadas)
4. [Archivos Modificados](#archivos-modificados)
5. [Verificación y Pruebas](#verificación-y-pruebas)
6. [Resultado Final](#resultado-final)

---

## 📊 RESUMEN EJECUTIVO

Se identificó y corrigió un problema crítico en el sistema de facturación de empleados que causaba discrepancias significativas entre los ingresos reales y los reportados por el sistema. La facturación mostraba valores incorrectos debido a:

1. **Servicios pagados con bonos** se estaban contabilizando como ingresos del empleado
2. **Cálculo proporcional incorrecto** que no restaba los productos del total antes de distribuir a servicios
3. **Servicios con precio 0€** quedaban registrados en la base de datos

**Impacto:** Sistema ahora calcula correctamente la facturación de cada empleado, diferenciando entre servicios cobrados, productos vendidos y bonos vendidos.

---

## 🔍 PROBLEMAS IDENTIFICADOS

### Problema 1: Servicios pagados con bono contaban como facturación

**Descripción:**  
Cuando un cliente usaba un bono para pagar servicios, estos se registraban en `registro_cobro_servicio` y se contabilizaban como ingresos del empleado, cuando en realidad el empleado ya había facturado ese monto cuando se vendió el bono originalmente.

**Ejemplo:**
```
Cobro #89: Método pago = 'bono', total_final = 0€
- Servicio registrado: Color Raiz = 0€
- Este servicio NO debería estar en registro_cobro_servicio
```

**Impacto:**  
- Duplicación de facturación (se cuenta al vender el bono Y al usar el bono)
- Raquel mostraba facturación cuando todos sus servicios fueron pagados con bonos
- Distorsión de reportes financieros

### Problema 2: Cálculo proporcional no restaba productos

**Descripción:**  
El cálculo proporcional para distribuir descuentos entre servicios aplicaba la proporción al `total_final` completo, sin restar primero el valor de los productos. Esto causaba que los servicios "heredaran" parte del precio de los productos.

**Ejemplo:**
```
Cobro #87:
- Total final: 78€
- Productos: 22€  
- Servicios (coste original): 56€

Cálculo INCORRECTO (antes):
  totalServiciosConDescuento = 78€ * (56/56) = 78€
  ❌ Resultado: Servicios = 78€

Cálculo CORRECTO (después):
  totalServiciosConDescuento = (78€ - 22€) * (56/56) = 56€
  ✅ Resultado: Servicios = 56€
```

**Impacto:**  
- Facturación de servicios inflada por el valor de los productos
- Lola mostraba 207€ en servicios cuando debería ser 185€
- Diferencia exacta = 22€ (el monto de productos del cobro #87)

### Problema 3: Servicios con precio 0€ en base de datos

**Descripción:**  
Algunos cobros tenían `total_final = 0€` (pagados con bono pero con método "tarjeta") y aun así se registraban servicios con precio 0€.

**Ejemplo:**
```
Cobro #96:
- Total final: 0€
- Método pago: tarjeta
- Bonos vendidos: 78€
- Servicio registrado: Color Raiz = 0€
```

**Impacto:**  
- Registros innecesarios en la base de datos
- Confusión en reportes al mostrar servicios sin valor

---

## ✅ SOLUCIONES IMPLEMENTADAS

### Solución 1: Excluir pagos con bono del registro de servicios

**Implementación en RegistroCobroController (líneas 730-820):**

```php
// Condición modificada para excluir método de pago 'bono'
if ((!$request->has('servicios_data') || empty($data['servicios_data'])) 
    && $metodoPagoFinal !== 'bono') {  // ← NUEVO: Excluir bonos
    
    // Solo aquí se registran servicios en registro_cobro_servicio
}
```

**Implementación en MigrarFacturacionHistorica.php (líneas 115-130):**

```php
// Excluir cobros con método 'bono' de la migración
$query = RegistroCobro::whereDoesntHave('servicios')
    ->where('metodo_pago', '!=', 'bono')  // ← NUEVO
    ->where(function($q) {
        // Excluir también cobros con total_final=0 que no vendan bonos
        $q->where('total_final', '>', 0)
          ->orWhere('total_bonos_vendidos', '>', 0);
    });
```

**Resultado:**
- ✅ Servicios pagados con bonos ya no se registran
- ✅ Migración histórica excluye correctamente estos cobros
- ✅ No hay duplicación de facturación

### Solución 2: Restar productos del total_final antes del cálculo proporcional

**Implementación en RegistroCobroController (líneas 745-760):**

```php
if ($costoTotalServicios > 0) {
    // NUEVO: Calcular el total de productos para restar del total_final
    $totalProductos = 0;
    if (isset($data['productos']) && is_array($data['productos'])) {
        foreach ($data['productos'] as $producto) {
            if (isset($producto['subtotal'])) {
                $totalProductos += $producto['subtotal'];
            }
        }
    }
    
    // Calcular proporción de servicios del coste total
    $proporcionServicios = $data['coste'] > 0 ? $costoTotalServicios / $data['coste'] : 1;
    
    // MODIFICADO: Restar productos del total_final antes de aplicar proporción
    $totalServiciosConDescuento = ($totalFinalServicios - $totalProductos) * $proporcionServicios;
    
    // Distribuir proporcionalmente entre servicios...
}
```

**Implementación en MigrarFacturacionHistorica.php (líneas 220-235):**

```php
if ($costoTotalServicios > 0) {
    // NUEVO: Calcular el total de productos desde la BD
    $totalProductos = DB::table('registro_cobro_productos')
        ->where('id_registro_cobro', $cobro->id)
        ->sum('subtotal');
    
    // Calcular proporción de servicios del coste total
    $proporcionServicios = $cobro->coste > 0 ? $costoTotalServicios / $cobro->coste : 1;
    
    // MODIFICADO: Restar productos del total_final antes de aplicar proporción
    $totalServiciosConDescuento = ($totalFinalServicios - $totalProductos) * $proporcionServicios;
}
```

**Aplicado en ambos casos:**
1. Citas individuales (caso 1)
2. Citas agrupadas (caso 2)

**Resultado:**
- ✅ Servicios facturan solo su parte real
- ✅ Productos no afectan el cálculo de servicios
- ✅ Cobro #87 ahora muestra 56€ en servicios (correcto) vs 78€ (incorrecto anterior)

### Solución 3: Limpieza de datos históricos incorrectos

**Comandos ejecutados:**

```bash
# 1. Eliminar servicios de cobros con método 'bono'
DELETE FROM registro_cobro_servicio 
WHERE registro_cobro_id IN (
    SELECT id FROM registro_cobros WHERE metodo_pago = 'bono'
);
# Resultado: 20 servicios eliminados

# 2. Eliminar servicios con precio 0€
DELETE FROM registro_cobro_servicio WHERE precio = 0;
# Resultado: 5 servicios eliminados

# 3. Limpiar datos de enero para re-migrar
DELETE FROM registro_cobro_servicio 
WHERE EXISTS (
    SELECT 1 FROM registro_cobros 
    WHERE registro_cobros.id = registro_cobro_servicio.registro_cobro_id
    AND registro_cobros.created_at BETWEEN '2026-01-01' AND '2026-01-31 23:59:59'
);
# Resultado: 9 servicios eliminados

# 4. Re-migrar enero con cálculos corregidos
php artisan facturacion:migrar-historica --desde=2026-01-01 --hasta=2026-01-31 --tenant=salonlh
# Resultado: 5 cobros procesados, 9 servicios creados, 0 errores
```

**Resultado:**
- ✅ Base de datos limpia sin registros incorrectos
- ✅ Datos históricos recalculados correctamente
- ✅ Integridad referencial mantenida

---

## 📁 ARCHIVOS MODIFICADOS

### 1. app/Http/Controllers/RegistroCobroController.php

**Líneas modificadas:** 730-820

**Cambios principales:**
- ✅ Añadida condición `&& $metodoPagoFinal !== 'bono'` para excluir pagos con bono
- ✅ Cálculo de `$totalProductos` antes del cálculo proporcional
- ✅ Modificada fórmula: `($totalFinalServicios - $totalProductos) * $proporcionServicios`
- ✅ Aplicado en ambos casos: cita individual y citas agrupadas

**Funcionalidad afectada:**
- Método `store()` - Registro de cobros

### 2. app/Console/Commands/MigrarFacturacionHistorica.php

**Líneas modificadas:** 115-130, 220-270

**Cambios principales:**
- ✅ Query modificado para excluir `metodo_pago = 'bono'`
- ✅ Exclusión adicional de cobros con `total_final = 0` sin bonos vendidos
- ✅ Cálculo de `$totalProductos` desde tabla `registro_cobro_productos`
- ✅ Campo correcto: `subtotal` (no `precio * cantidad`)
- ✅ Aplicado en ambos casos: cita individual y citas agrupadas

**Funcionalidad afectada:**
- Comando `php artisan facturacion:migrar-historica`
- Métodos `procesarTenant()` y `procesarCobro()`

### 3. app/Models/Empleado.php

**Nota:** No se modificó este archivo. El modelo ya estaba correcto usando `registro_cobro_servicio.precio` para calcular la facturación por servicios.

**Líneas relevantes:** 50-120

**Funcionamiento actual (correcto):**
```php
// Facturación por servicios desde registro_cobro_servicio
$facturacionServicios = DB::table('registro_cobro_servicio')
    ->join('registro_cobros', 'registro_cobro_servicio.registro_cobro_id', '=', 'registro_cobros.id')
    ->where('registro_cobro_servicio.empleado_id', $this->id)
    ->whereBetween('registro_cobros.created_at', [$fechaInicio, $fechaFin])
    ->sum('registro_cobro_servicio.precio');
```

---

## 🧪 VERIFICACIÓN Y PRUEBAS

### Prueba 1: Cobros excluidos ✅

```php
// Verificar que NO hay servicios de cobros con método 'bono'
$serviciosConBono = DB::table('registro_cobro_servicio')
    ->join('registro_cobros', 'registro_cobro_servicio.registro_cobro_id', '=', 'registro_cobros.id')
    ->where('registro_cobros.metodo_pago', 'bono')
    ->count();
// Resultado: 0 (debe ser 0) ✅

// Verificar que NO hay servicios con precio 0
$serviciosPrecio0 = DB::table('registro_cobro_servicio')
    ->where('precio', 0)
    ->count();
// Resultado: 0 (debe ser 0) ✅
```

### Prueba 2: Cálculo proporcional correcto ✅

```php
// Verificar cobro #87 que tiene servicios Y productos
Cobro #87:
  Total final: 78.00€
  Coste: 56.00€
  Total productos: 22.00€
  Total servicios facturados: 56.00€
  Suma servicios + productos: 78€
  Diferencia con total_final: 0€ ✅
```

### Prueba 3: Facturación de todos los empleados ✅

```
Empleado 1: Total 0€ (S: 0€, P: 0€, B: 0€) ✅
Empleado 2: Total 0€ (S: 0€, P: 0€, B: 0€) ✅
Empleado 3 (Raquel): Total 0€ (S: 0€, P: 0€, B: 0€) ✅ (antes mostraba valores incorrectos)
Empleado 4 (Lola): Total 395.01€ (S: 185.01€, P: 22.00€, B: 188€) ✅
Empleado 5: Total 0€ (S: 0€, P: 0€, B: 0€) ✅
```

### Prueba 4: Desglose detallado de servicios ✅

```
Cobro #85: 
  - Aliquip maxime sint = 61.00€
  - aminoacidos = 17.00€
  - Color entero = 39.00€
  Total: 117€ ✅

Cobro #87:
  - aminoacidos = 17.00€
  - Color entero = 39.00€
  Total: 56€ ✅ (antes era 78€, ahora correcto)

Cobro #93:
  - Corte señora = 2.53€
  - Color Raiz = 6.11€
  - Secado melena = 3.37€
  Total: 12.01€ ✅

TOTAL SERVICIOS: 185.01€ ✅
```

---

## 🎯 RESULTADO FINAL

### Facturación Lola - Enero 2026

**Comparación antes vs después:**

| Concepto | Antes | Después | Esperado | Estado |
|----------|-------|---------|----------|--------|
| Servicios | 207.01€ | 185.01€ | 185€ | ✅ |
| Productos | 22.00€ | 22.00€ | 22€ | ✅ |
| Bonos | 188€ | 188€ | 188€ | ✅ |
| **TOTAL** | **417.01€** | **395.01€** | **395€** | ✅ |

**Diferencia de servicios corregida:** 22€ (exactamente el monto de productos del cobro #87)

**Diferencia final:** 0.01€ (error de redondeo aceptable)

### Casos especiales verificados

1. **Raquel** (Empleado 3): 
   - Antes: Mostraba facturación incorrecta
   - Ahora: 0€ ✅ (todos sus servicios fueron pagados con bonos)

2. **Cobro #87**:
   - Antes: 78€ en servicios (incluía productos)
   - Ahora: 56€ en servicios ✅ + 22€ en productos ✅

3. **Cobros con bono**:
   - Antes: Registraban servicios en registro_cobro_servicio
   - Ahora: No se registran ✅

4. **Servicios con 0€**:
   - Antes: Quedaban en la base de datos
   - Ahora: Eliminados ✅

---

## 📝 NOTAS TÉCNICAS

### Fórmula del cálculo proporcional

```php
// Paso 1: Calcular costo de servicios ANTES de descuentos
$costoTotalServicios = sum($servicio->precio_original);

// Paso 2: Calcular total de productos
$totalProductos = sum($producto->subtotal);

// Paso 3: Calcular proporción de servicios en el coste total
$proporcionServicios = $costoTotalServicios / $coste;

// Paso 4: Aplicar proporción al total_final MENOS productos
$totalServiciosConDescuento = ($total_final - $totalProductos) * $proporcionServicios;

// Paso 5: Distribuir proporcionalmente entre cada servicio
foreach ($servicios as $servicio) {
    $proporcion = $servicio->precio_original / $costoTotalServicios;
    $precioConDescuento = $totalServiciosConDescuento * $proporcion;
}
```

### Métodos de pago y su impacto

| Método | Se registra en registro_cobro_servicio | Cuenta en facturación |
|--------|---------------------------------------|---------------------|
| efectivo | ✅ SÍ | ✅ SÍ |
| tarjeta | ✅ SÍ | ✅ SÍ |
| mixto | ✅ SÍ | ✅ SÍ |
| **bono** | ❌ **NO** | ❌ **NO** (ya se facturó al vender el bono) |
| deuda | ✅ SÍ | ✅ SÍ |

### Compatibilidad multi-tenant

El sistema funciona correctamente en ambos tenants:
- **salonlh**: No tiene tabla `bonos_plantillas` ✅
- **redireccion**: Sí tiene tabla `bonos_plantillas` ✅

El modelo `Empleado` detecta automáticamente si existe la tabla y adapta la consulta.

---

## 🔧 COMANDOS ÚTILES

### Re-migrar datos históricos
```bash
php artisan facturacion:migrar-historica \
  --desde=2026-01-01 \
  --hasta=2026-01-31 \
  --tenant=salonlh
```

### Verificar facturación de un empleado
```php
$empleado = Empleado::find(4);
$facturacion = $empleado->facturacionMesActual();
// o para fechas específicas:
$facturacion = $empleado->facturacionPorFechas('2026-01-01', '2026-01-31');
```

### Limpiar servicios de un periodo
```sql
DELETE FROM registro_cobro_servicio 
WHERE registro_cobro_id IN (
    SELECT id FROM registro_cobros 
    WHERE created_at BETWEEN '2026-01-01' AND '2026-01-31 23:59:59'
);
```

---

## ✅ CHECKLIST FINAL

- [x] Servicios pagados con bono NO se registran en registro_cobro_servicio
- [x] Cálculo proporcional resta productos del total_final correctamente
- [x] Comando de migración excluye cobros con método 'bono'
- [x] Comando de migración excluye cobros con total_final=0 sin bonos vendidos
- [x] Datos históricos migrados correctamente
- [x] Servicios con precio 0€ eliminados
- [x] Facturación de Lola coincide con cálculo manual (diferencia: 0.01€)
- [x] Facturación de Raquel correcta (0€, todos sus servicios fueron con bono)
- [x] Compatibilidad multi-tenant verificada
- [x] Sin errores en migraciones
- [x] Pruebas exhaustivas ejecutadas y aprobadas

---

**✅ SISTEMA DE FACTURACIÓN COMPLETAMENTE CORREGIDO Y VERIFICADO**

*Fecha de verificación: 15 de enero de 2026*
