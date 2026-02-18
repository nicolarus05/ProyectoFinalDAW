# Análisis Exhaustivo de Bugs del Sistema

**Fecha:** 18 de febrero de 2026  
**Áreas analizadas:** Registro de cobros, Facturación de empleados, Sistema de deudas, Caja diaria (desglose peluquería/estética)

---

## Índice

1. [Bugs Críticos](#1-bugs-críticos)
2. [Bugs Importantes](#2-bugs-importantes)
3. [Bugs Menores](#3-bugs-menores)
4. [Resumen por Archivo](#4-resumen-por-archivo)

---

## 1. Bugs Críticos

### ~~BUG-001: `calcularDistribucion()` usa `->user->name` en vez de `->user->nombre`~~ ✅ RESUELTO

- **Archivo:** `app/Http/Controllers/DeudaController.php` (líneas 231 y 259)
- **Severidad:** 🔴 CRÍTICA
- **Área:** Sistema de deudas
- **Estado:** ✅ **CORREGIDO** (18/02/2026)
- **Descripción:** El método `calcularDistribucion()` (endpoint AJAX para previsualiziar cómo se distribuirá un pago de deuda) usa `$empleado->user->name` para obtener el nombre del empleado. El campo correcto en la tabla `users` es `nombre`, no `name`.
- **Impacto:** La vista previa de distribución de pago de deuda muestra nombres vacíos o genera un error en producción.
- **Corrección aplicada:** Cambiado `->user->name` por `->user->nombre` en ambas ocurrencias (servicios y productos).
- **Verificación de impacto:** Sin efectos colaterales. No queda ningún `->user->name` en código de producción (`app/`).

---

### ~~BUG-002: `facturacionPorCategoriaPorFechas()` no filtra por `contabilizado = true`~~ ✅ RESUELTO

- **Archivo:** `app/Models/Empleado.php` (línea 160 aprox.)
- **Severidad:** 🔴 CRÍTICA
- **Área:** Facturación / Caja diaria
- **Estado:** ✅ **CORREGIDO** (18/02/2026)
- **Descripción:** El método estático `facturacionPorCategoriaPorFechas()` (usado para el desglose peluquería/estética) NO incluye `->where('contabilizado', true)` en su query. Sin embargo, `facturacionPorFechas()` (usado para facturación por empleado) SÍ lo incluye.
- **Impacto:** Si existieran cobros con `contabilizado = false`, se incluirían en el desglose por categoría (peluquería/estética) pero NO en la facturación por empleado, causando que los totales no cuadren entre ambas vistas.
- **Corrección aplicada:** Añadido `->where('contabilizado', true)` a la query, consistente con `facturacionPorFechas()`.
- **Verificación de impacto:** Sin efectos colaterales. El campo `contabilizado` tiene `default(true)` en la migración, por lo que ningún dato existente se ve afectado. Solo protege contra cobros marcados manualmente como no contabilizados.

---

## 2. Bugs Importantes

### ~~BUG-003: `destroy()` no revierte deuda, movimientos ni usos de bono~~ ✅ RESUELTO

- **Archivo:** `app/Http/Controllers/RegistroCobroController.php` (línea 1654)
- **Severidad:** 🟠 IMPORTANTE
- **Área:** Registro de cobros
- **Estado:** ✅ **CORREGIDO** (18/02/2026)
- **Descripción:** El método `destroy()` al eliminar un cobro solo restauraba el stock de productos. NO revertía:
  - La deuda creada en el sistema (`Deuda::registrarCargo`)
  - Los movimientos de deuda asociados (`MovimientoDeuda`)
  - Los usos de bono (`bono_uso_detalle`: `cantidad_usada` no se decrementaba)
  - El estado de bonos que se marcaron como `usado` (no se restauraban a `activo`)
  - Los bonos vendidos asociados al cobro
- **Impacto:** Eliminar un cobro dejaba datos inconsistentes: deuda fantasma en el cliente, usos de bono consumidos sin cobro asociado, bonos agotados que en realidad deberían tener usos disponibles.
- **Corrección aplicada:** Reescrito `destroy()` completo con transacción DB que ahora revierte:
  1. Stock de productos (ya existía)
  2. Movimientos de deuda (cargo → revierte saldo_total y saldo_pendiente; abono → re-incrementa saldo_pendiente)
  3. Usos de bono (decrementa cantidad_usada, restaura estado a 'activo' si correspondía)
  4. Bonos vendidos (elimina si no tienen usos, desvincula si tienen)
  5. Estado de citas (completada → confirmada)
- **Verificación de impacto:** Sin efectos colaterales. `destroy()` solo se invoca desde la ruta resource + vista blade. Las FKs cascade en las tablas pivot son compatibles con el orden manual de eliminación. No hay eventos boot/deleting en el modelo.

---

### ~~BUG-004: Fallback de categoría distribuye por cantidad de servicios, no por precio~~ ✅ RESUELTO

- **Archivo:** `app/Services/FacturacionService.php` (líneas 200-225)
- **Severidad:** 🟠 IMPORTANTE
- **Área:** Caja diaria (desglose peluquería/estética)
- **Estado:** ✅ **CORREGIDO** (18/02/2026)
- **Descripción:** Cuando un cobro tiene `sumaPivotTotal < 0.01` (todos los servicios son de bono o no hay pivot) pero `total_final > 0`, el fallback distribuye `total_final` entre peluquería y estética basándose en la **cantidad** de servicios de cada categoría, no en su **precio**.
- **Impacto:** Si un cobro legacy tiene 1 servicio de peluquería a €50 y 3 servicios de estética a €5 cada uno, la distribución sería:
  - **Actual (por cantidad):** Peluquería 25% (€16.25), Estética 75% (€48.75) ❌
  - **Correcto (por precio):** Peluquería 77% (€50), Estética 23% (€15) ✅
- **Corrección aplicada:** Cambiado `$serviciosPorCategoria[$categoria]++` por `$serviciosPorCategoria[$categoria] += $servicio->precio` para distribuir por precio real.
- **Verificación de impacto:** Sin efectos colaterales. `$servicio->precio` siempre contiene el precio de catálogo (nunca null). El campo es `decimal(8,2) NOT NULL` en la migración.

---

### ~~BUG-005: Lógica diferente para bonos vendidos entre empleado y categoría~~ ✅ RESUELTO

- **Archivo:** `app/Services/FacturacionService.php`
- **Severidad:** 🟠 IMPORTANTE
- **Área:** Facturación
- **Estado:** ✅ **CORREGIDO** (18/02/2026)
- **Descripción:** Los dos métodos de desglose usan criterios diferentes para decidir si facturar bonos vendidos:
  - `desglosarCobroPorEmpleado()` (línea 109): factura bonos si `dinero_cliente >= totalCobrado - 0.01`
  - `desglosarCobroPorCategoria()` (línea 249): factura bonos si `metodo_pago !== 'deuda'`
  
  Además, usan fuentes de precio diferentes:
  - Empleado: `$bono->pivot->precio` (precio de plantilla vinculado al cobro)
  - Categoría: `$bono->precio_pagado` (lo que realmente pagó el cliente)
- **Impacto:** En edge cases (ej: bono con pago parcial, bono con `metodo_pago='mixto'` y `dinero_cliente` exacto), las sumas de "bonos vendidos" no cuadrarían entre la vista de empleados y la vista de categorías.
- **Corrección aplicada:** Unificado `desglosarCobroPorEmpleado()` para usar el mismo criterio que `desglosarCobroPorCategoria()`: `$bono->metodo_pago !== 'deuda'` como condición y `$bono->precio_pagado ?? 0` como fuente de precio. Este criterio es más directo y robusto que la comparación aritmética `dinero_cliente >= totalCobrado`.
- **Verificación de impacto:** Para el caso normal (bono pagado completo), ambos criterios dan el mismo resultado. Para edge cases (pago parcial, bono en deuda parcial), el nuevo criterio es más preciso: `precio_pagado` refleja exactamente lo que entró en caja, mientras que `pivot->precio` siempre era el precio de plantilla completo.

---

### ~~BUG-006: `registrarPago()` solo busca el último cargo para distribuir~~ ✅ RESUELTO

- **Archivo:** `app/Http/Controllers/DeudaController.php` (línea 312)
- **Severidad:** 🟠 IMPORTANTE
- **Área:** Sistema de deudas
- **Estado:** ✅ **CORREGIDO** (18/02/2026)
- **Descripción:** Al pagar una deuda, `registrarPago()` busca el **último** movimiento de tipo `cargo` para obtener los servicios y empleados originales. Si un cliente acumuló deuda de **múltiples cobros diferentes** (con empleados distintos), el pago siempre se distribuirá según el último cobro, ignorando los anteriores.
- **Impacto:** Si Genoveva debe €13 del cobro #836 (Lola) y €10 del cobro #850 (Raquel), al pagar €23 todo se distribuiría según el cobro #850, facturando todo a Raquel.
- **Nota:** Este problema se mitiga parcialmente por `Deuda::registrarAbono()` que distribuye el pago a los cobros más antiguos primero, pero el nuevo `RegistroCobro` creado para la caja reflejará la distribución incorrecta.
- **Corrección aplicada:** Cambiado `->latest()->first()` por `->whereHas('registroCobro', fn($q) => $q->where('deuda', '>', 0))->reorder()->orderBy('created_at', 'asc')->first()` en ambos métodos (`registrarPago()` y `calcularDistribucion()`). El filtro `deuda > 0` asegura que solo se consideren cargos con deuda pendiente. El `->reorder()` elimina el `ORDER BY created_at DESC` por defecto de la relación `movimientos()`, y luego `->orderBy('created_at', 'asc')` obtiene el cargo más antiguo — alineándose con `Deuda::registrarAbono()` que paga los cobros más antiguos primero.
- **Verificación de impacto:** Sin `->reorder()`, el SQL generaría `ORDER BY created_at DESC, created_at ASC` donde la primera cláusula domina (seguiría obteniendo el último cargo). Con `->reorder()`, se genera correctamente `ORDER BY created_at ASC`.

---

### ~~BUG-007: `dinero_cliente` se pone a 0 por defecto si no viene del frontend~~ ✅ RESUELTO

- **Archivo:** `app/Http/Controllers/RegistroCobroController.php` (línea 451)
- **Severidad:** 🟠 IMPORTANTE
- **Área:** Registro de cobros
- **Estado:** ✅ **CORREGIDO** (18/02/2026)
- **Descripción:** Cuando `metodo_pago` es `efectivo` y el campo `dinero_cliente` no viene en el request, se asigna `0`. Esto convierte un cobro normal en deuda completa sin intención del usuario.
- **Impacto:** Fue la causa raíz del cobro #836 (Genoveva): `dinero_cliente=0` → `deuda=13€` → pivot a 0 → facturación incorrecta.
- **Corrección aplicada:** Campo hecho obligatorio en múltiples capas:
  1. **Backend (FormRequest):** Regla cambiada de `nullable|numeric|min:0` a `required_if:metodo_pago,efectivo|numeric|min:0` en `StoreRegistroCobroRequest`. Añadido mensaje personalizado `dinero_cliente.required_if`.
  2. **Backend (Controller):** Eliminado bloque `if (!isset($data['dinero_cliente'])) { $data['dinero_cliente'] = 0; }` y check manual de negativos (cubierto por `min:0` en validación).
  3. **Frontend (vistas):** Añadido `required` y `min="0"` a los inputs de `dinero_cliente` en `create.blade.php` y `create-direct.blade.php`.
  4. **Frontend (JS):** Añadido toggle de `required` en `toggleMetodoPagoCampos()` (cobros.js) y `cambiarMetodoPago()` (create-direct.blade.php) para desactivar `required` al seleccionar tarjeta/mixto.
- **Verificación de impacto:** Para tarjeta, el controller sobreescribe `dinero_cliente = total_final`. Para mixto, sobreescribe `dinero_cliente = totalPagado`. Solo efectivo depende del input del usuario, que ahora es obligatorio.

---

## 3. Bugs Menores

### ~~BUG-008: `update()` no gestiona cambios en deuda~~ ✅ RESUELTO

- **Archivo:** `app/Http/Controllers/RegistroCobroController.php` (línea 1585)
- **Severidad:** 🟡 MENOR
- **Área:** Registro de cobros
- **Estado:** ✅ **CORREGIDO** (18/02/2026)
- **Descripción:** Al editar un cobro, `update()` recalcula el pivot proporcionalmente si `total_final` cambió, pero NO recalcula el campo `deuda` del cobro ni actualiza el `saldo_pendiente` de la deuda del cliente.
- **Impacto:** Editar el total de un cobro con deuda genera inconsistencia entre lo registrado y la deuda real.
- **Corrección aplicada:**
  1. **Recalculo de deuda:** Añadida fórmula `nuevaDeuda = max(0, total_final - dineroCliente)` y campo `'deuda' => $nuevaDeuda` al update del cobro.
  2. **Ajuste de Deuda del cliente:** Si la deuda cambió, se ajustan `saldo_total` y `saldo_pendiente` en la `Deuda` del cliente por la diferencia.
  3. **Movimiento de cargo:** Se actualiza el `monto` del movimiento de cargo existente, o se crea uno nuevo si el cobro pasó de deuda=0 a deuda>0.
  4. **Transacción DB:** Envuelto todo el método `update()` en `DB::beginTransaction()/commit()/rollBack()` para atomicidad.
  5. **Fix cambio negativo:** Corregido bug preexistente donde `cambio` podía ser negativo. Ahora usa `max(0, dineroCliente - total_final)`.
- **Verificación de impacto:** En el edge case de sobrepago por edición (ej: bajar deuda cuando ya se pagaron abonos), `max(0, ...)` previene saldos negativos. Los abonos previos se respetan correctamente.

---

### ~~BUG-009: Caja diaria no tiene desglose peluquería/estética por día~~ ✅ RESUELTO

- **Archivo:** `app/Http/Controllers/FacturacionController.php` (líneas 48-110)
- **Severidad:** 🟡 MENOR (es más una feature que falta)
- **Área:** Caja diaria
- **Estado:** ✅ **CORREGIDO** (18/02/2026)
- **Descripción:** El array `$cajasDiarias` solo contenía `[total, efectivo, tarjeta]` por fecha. No había desglose diario por categoría (peluquería/estética). Solo los totales mensuales tenían esta separación.
- **Impacto:** No se podía verificar día a día si el desglose peluquería/estética cuadraba.
- **Corrección aplicada:**
  1. **Controller:** Importado `FacturacionService`. Añadidos campos `peluqueria` y `estetica` a cada día del array `$cajasDiarias`.
  2. **Controller:** Instanciado `FacturacionService` una única vez y llamado `desglosarCobroPorCategoria()` por cada cobro contabilizado (no deuda, no bono). Acumula servicios+productos por categoría por día.
  3. **Controller:** Eager-load ampliado a `['bonosVendidos', 'servicios', 'productos', 'cita.servicios', 'citasAgrupadas.servicios']` para evitar N+1 queries en el path de fallback del servicio.
  4. **Vista:** Añadidas filas de `✂️ Pelu.` y `💆 Esté.` en cada caja diaria con `text-pink-600` y `text-purple-600`, visibles solo si hay datos.
- **Verificación de impacto:** Los filtros del desglose (`!bono && !deuda && contabilizado`) coinciden exactamente con `Empleado::facturacionPorCategoriaPorFechas()`. La suma `peluqueria + estetica` no iguala `total` intencionadamente porque `total` incluye bonos vendidos.

---

### ~~BUG-010: `saldo_total` nunca disminuye en abonos~~ ✅ RESUELTO

- **Archivo:** `app/Models/Deuda.php` (línea 65)
- **Severidad:** 🟡 MENOR (diseño, no bug funcional)
- **Área:** Sistema de deudas
- **Estado:** ✅ **CORREGIDO** (18/02/2026)
- **Descripción:** `registrarAbono()` solo decrementa `saldo_pendiente`, nunca `saldo_total`. Es un diseño válido para historial contable, pero la vista de historial usaba `saldo_total` como punto de partida para calcular el "Saldo Resultante", mostrando valores incorrectos.
- **Impacto:** En la vista historial, el saldo resultante después de abonos era incorrecto (ej: cliente con saldo_total=80, saldo_pendiente=60 mostraba €80 después de un abono en vez de €60).
- **Corrección aplicada:**
  1. **historial.blade.php:** Cambiado punto de partida de `$deuda->saldo_total` a `$deuda->saldo_pendiente`. Recalcular hacia atrás desde `saldo_pendiente` produce el running balance correcto.
  2. **historial.blade.php:** Corregido bug preexistente: `$movimientos->total()` (método de paginación) cambiado a `$movimientos->count()` (método de Collection), ya que el controller usa `->get()` sin paginar.
  3. **show.blade.php:** Cambiada etiqueta de "Deuda Total Acumulada" a "Deuda Histórica Acumulada" con subtítulo "Total de cargos registrados" para clarificar que `saldo_total` es un historial acumulado.
- **Verificación de impacto:** Trazado ejemplo: Cargo €50 → Cargo €30 → Abono €20. Con saldo_pendiente=60: running balance = 0→50→80→60 ✅. El diseño de `saldo_total` acumulado se mantiene intacto para contabilidad.

---

### ~~BUG-011: Detección de bono vs deuda en `registrarPago()` puede dar falsos positivos~~ ✅ RESUELTO

- **Archivo:** `app/Http/Controllers/DeudaController.php` (líneas 173 y 341)
- **Severidad:** 🟡 MENOR
- **Área:** Sistema de deudas
- **Estado:** ✅ **CORREGIDO** (18/02/2026)
- **Descripción:** La consulta a `bono_uso_detalle` para distinguir "servicio pagado con bono" de "servicio en deuda" busca por `servicio_id` + `cita_id`, pero si el mismo servicio se usó con bono en la misma cita en un cobro anterior (caso improbable pero posible), daría un falso positivo.
- **Impacto:** Un servicio en deuda podría identificarse erróneamente como pagado con bono, excluyéndolo de la distribución del pago.
- **Corrección aplicada:**
  1. **`calcularDistribucion()` (línea 173):** Añadido `->whereBetween('created_at', [subMinutes(5), addMinutes(5)])` a la rama con `cita_id`, igualando el patrón ya usado en la rama de ventas directas (sin cita).
  2. **`registrarPago()` (línea 341):** Misma corrección aplicada. Ambos métodos ahora son idénticos en su lógica de detección.
- **Verificación de impacto:** La ventana de 5 min es consistente con la rama de ventas directas. `->copy()` se usa correctamente para no mutar el Carbon original. No se introducen nuevos problemas. Nota: las vistas Blade (index, show, edit de cobros) tienen queries similares sin ventana temporal, pero solo afectan etiquetas de display, no cálculos financieros.

---

### ~~BUG-012: Falta filtro `contabilizado` en query de cajas diarias~~ ✅ RESUELTO

- **Archivo:** `app/Http/Controllers/FacturacionController.php` (línea 48)
- **Severidad:** 🟡 MENOR
- **Área:** Caja diaria
- **Estado:** ✅ **CORREGIDO** (18/02/2026)
- **Descripción:** La query que obtiene los cobros para calcular las cajas diarias no filtra por `contabilizado = true`. En la práctica esto no causa problemas porque `contabilizado` tiene `default(true)` en la migración y solo se podría poner a `false` manualmente, pero es una inconsistencia con la facturación por empleado.
- **Corrección aplicada:** Añadido `->where('contabilizado', true)` a la query de cobros para cajas diarias, alineándola con `Empleado::facturacionPorFechas()` y `Empleado::facturacionPorCategoriaPorFechas()`.
- **Verificación de impacto:** Las 3 queries del sistema ahora son 100% consistentes en su filtro `contabilizado`. Además, `$deudaTotal` (calculado desde `$cobros`) ahora también excluye cobros no contabilizados, mejorando la coherencia con `$totalGeneral`.

---

## 4. Resumen por Archivo

| Archivo | Bugs | IDs |
|---------|------|-----|
| `DeudaController.php` | 3 | BUG-001, BUG-006, BUG-011 |
| `RegistroCobroController.php` | 3 | BUG-003, BUG-007, BUG-008 |
| `FacturacionService.php` | 2 | BUG-004, BUG-005 |
| `Empleado.php` | 1 | BUG-002 |
| `FacturacionController.php` | 2 | BUG-009, BUG-012 |
| `Deuda.php` | 1 | BUG-010 |

### Estadísticas

| Severidad | Cantidad | Resueltos |
|-----------|----------|-----------|
| 🔴 Crítica | 2 | ✅ 2/2 |
| 🟠 Importante | 5 | ✅ 5/5 |
| 🟡 Menor | 5 | ✅ 5/5 |
| **Total** | **12** | **✅ 12/12** |
