# ✅ MEJORA: BONOS EN VISTA DE COBROS - COMPLETADO

## 📋 Resumen

Se han agregado funcionalidades para visualizar bonos vendidos y distinguir servicios pagados con bono en la vista de registro de cobros.

**Fecha**: 6 de febrero de 2026
**Archivo modificado**: `resources/views/cobros/index.blade.php`

---

## 🎯 Objetivos

1. ✅ **Mostrar bonos vendidos** en cada cobro que incluya venta de bonos
2. ✅ **Distinguir visualmente** servicios pagados con bono vs servicios pagados en efectivo/tarjeta
3. ✅ **Incluir total de bonos vendidos** en el resumen del día
4. ✅ **Desglose de información** de cada bono: precio, estado de pago, servicios incluidos

---

## 🎨 Cambios Implementados

### 1. Estilos CSS Adicionales

```css
.desglose-item.bono { 
    border-left-color: #f59e0b; /* Naranja para bonos */
}

.bono-badge { 
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); 
    color: white; 
    padding: 2px 8px; 
    border-radius: 9999px; 
    font-size: 0.7rem; 
    font-weight: 700; 
}
```

**Propósito**: 
- Borde naranja distingue bonos de servicios/productos
- Badge con degradado dorado indica servicios pagados con bono

---

### 2. Grid Dinámico de 2 o 3 Columnas

**ANTES**:
```html
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
  <!-- Servicios | Productos -->
</div>
```

**DESPUÉS**:
```html
<div class="grid grid-cols-1 {{ $tieneBonos ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }} gap-4">
  <!-- Servicios | Productos | Bonos Vendidos (si hay) -->
</div>
```

**Beneficio**: 
- Diseño adaptativo según contenido
- Sin bonos: Layout 2 columnas (más ancho para servicios/productos)
- Con bonos: Layout 3 columnas (espacio para todo)

---

### 3. Detección de Servicios Pagados con Bono

#### CASO 1: Citas Normales

```php
// Verificar si fue pagado con bono
$pagadoConBono = \DB::table('bono_uso_detalle')
    ->where('cita_id', $cobro->cita->id)
    ->where('servicio_id', $servicio->id)
    ->exists();
```

#### CASO 2: Citas Agrupadas

```php
$pagadoConBono = \DB::table('bono_uso_detalle')
    ->where('cita_id', $citaGrupo->id)
    ->where('servicio_id', $servicio->id)
    ->exists();
```

#### CASO 3: Cobros Directos

```php
// Para cobros directos, verificar si el método de pago es bono
$pagadoConBono = $cobro->metodo_pago === 'bono';
```

**Resultado**:
```php
$serviciosDetalle[] = [
    'nombre' => $servicio->nombre,
    'precio' => $precioServicio,
    'empleado' => $empleado,
    'es_bono' => $pagadoConBono  // ← NUEVO CAMPO
];
```

---

### 4. Visualización de Servicios con Badge de Bono

**ANTES**:
```html
<div class="desglose-item servicio bg-blue-50 p-3 rounded-lg">
    <div class="font-medium">Peinado melena</div>
    <div class="empleado-tag">👨‍💼 Raquel</div>
    <div class="text-lg font-bold text-blue-700">16.00 €</div>
</div>
```

**DESPUÉS**:
```html
<div class="desglose-item servicio bg-blue-50 p-3 rounded-lg border-2 border-yellow-400">
    <div class="flex items-center gap-2">
        <div class="font-medium">Peinado melena</div>
        <span class="bono-badge">🎫 BONO</span>  <!-- ← NUEVO -->
    </div>
    <div class="empleado-tag">👨‍💼 Raquel</div>
    <div class="text-lg font-bold text-yellow-600">0.00 €</div>  <!-- Color dorado -->
</div>
```

**Características visuales**:
- ✅ Borde amarillo (2px) alrededor del servicio
- ✅ Badge dorado con gradiente "🎫 BONO"
- ✅ Precio en color amarillo/dorado en lugar de azul
- ✅ Indica claramente que el servicio fue pagado con bono

---

### 5. Nueva Sección: BONOS VENDIDOS

```html
<!-- BONOS VENDIDOS -->
@if($tieneBonos)
<div class="space-y-2">
    <div class="font-semibold text-gray-700 mb-2 flex items-center gap-2">
        <span class="text-yellow-600">🎫</span> BONOS VENDIDOS
    </div>
    
    @foreach($cobro->bonosVendidos as $bono)
        <div class="desglose-item bono bg-yellow-50 p-3 rounded-lg border-2 border-yellow-300">
            <!-- Información del bono -->
        </div>
    @endforeach
</div>
@endif
```

#### Información Mostrada por Bono

1. **Nombre del bono**: De la plantilla asociada
2. **Servicios incluidos**: Lista de servicios y cantidades
3. **Método de pago**: Efectivo, Tarjeta, o A deber
4. **Precio total**: Precio del bono
5. **Precio pagado**: Si es pago parcial
6. **Deuda**: Si quedó dinero por pagar

**Ejemplo Visual**:

```
┌────────────────────────────────────────────┐
│ Bono Premium 5 Sesiones                    │
│ Incluye: Corte (x5), Lavado (x5)          │
│                                            │
│ 💵 Efectivo    Deuda: 20.00 €             │
│                              80.00 €       │
│                    Pagado: 60.00 €        │
└────────────────────────────────────────────┘
```

---

### 6. Resumen del Día: Total Bonos Vendidos

**Nueva sección agregada**:

```html
<div class="mt-4 bg-white border-2 border-yellow-300 rounded-lg p-4">
    <div class="flex justify-between items-center">
        <div>
            <div class="text-sm text-gray-600 font-semibold">
                💰 Total Bonos Vendidos
            </div>
            <div class="text-xs text-gray-500 mt-1">
                (Ingresos por venta de bonos + deuda de bonos)
            </div>
        </div>
        <div class="text-right">
            <div class="text-3xl font-bold text-yellow-600">
                🎫 150.00 €
            </div>
            <div class="text-xs text-gray-600 mt-1">
                ✓ Cobrado: 120.00 €
                ⚠ A deber: 30.00 €
                3 bonos vendidos
            </div>
        </div>
    </div>
</div>
```

#### Cálculos Incluidos

```php
$totalBonosVendidos = 0;          // Precio total de todos los bonos
$totalBonosVendidosPagados = 0;   // Lo que se cobró efectivamente
$totalBonosVendidosDeuda = 0;     // Lo que quedó a deber
$cantidadBonosVendidos = 0;       // Cantidad de bonos vendidos

foreach($cobros as $cobro) {
    if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
        foreach ($cobro->bonosVendidos as $bono) {
            $cantidadBonosVendidos++;
            $precioTotal = $bono->pivot->precio ?? 0;
            $precioPagado = $bono->precio_pagado ?? 0;
            $deudaBono = max(0, $precioTotal - $precioPagado);
            
            $totalBonosVendidos += $precioTotal;
            $totalBonosVendidosPagados += $precioPagado;
            $totalBonosVendidosDeuda += $deudaBono;
        }
    }
}
```

---

## 📊 Casos de Uso

### Caso 1: Cliente con Servicio Pagado con Bono

**Escenario**: Raquel atiende a una cliente que paga Peinado melena con su bono

**Vista anterior**:
```
✂️ Peinado melena    👨‍💼 Raquel    0.00 €
```
❌ No está claro POR QUÉ está a €0

**Vista mejorada**:
```
┌──────────────────────────────────────────┐
│ ✂️ Peinado melena    🎫 BONO             │
│ 👨‍💼 Raquel                      0.00 €   │
└──────────────────────────────────────────┘
(Borde amarillo + Badge dorado visible)
```
✅ Claramente indica que fue con bono

---

### Caso 2: Venta de Bono con Pago Parcial

**Escenario**: Cliente compra bono de €100, paga €80 en efectivo y €20 quedan a deber

**Vista**:
```
┌────────────────────────────────────────────┐
│ 🎫 BONOS VENDIDOS                          │
├────────────────────────────────────────────┤
│ Bono Premium 5 Sesiones                    │
│ Incluye: Corte (x5), Tinte (x2)           │
│                                            │
│ 💵 Efectivo    Deuda: 20.00 €             │
│                              100.00 €      │
│                    Pagado: 80.00 €        │
└────────────────────────────────────────────┘
```

**Resumen del día**:
```
💰 Total Bonos Vendidos: 🎫 100.00 €
   ✓ Cobrado: 80.00 €
   ⚠ A deber: 20.00 €
   1 bono vendido
```

---

### Caso 3: Cobro Mixto con Bono Vendido

**Escenario**: 
- Servicio 1: Corte €20 (efectivo)
- Servicio 2: Tinte €45 (tarjeta)
- Bono vendido: €100 (efectivo)

**Vista del cobro**:
```
┌─────────────────────────────────────────────────────┐
│ SERVICIOS                 │ BONOS VENDIDOS          │
├───────────────────────────┼─────────────────────────┤
│ ✂️ Corte                  │ Bono Premium            │
│ 👨‍💼 María      20.00 €    │ Incluye: 5 servicios   │
│                           │ 💵 Efectivo             │
│ ✂️ Tinte                  │           100.00 €      │
│ 👨‍💼 María      45.00 €    │                         │
└───────────────────────────┴─────────────────────────┘

Total Facturado: 165.00 €
  (65€ servicios + 100€ bono)
```

---

## 🔍 Detalles Técnicos

### Lógica de Detección de Bonos

#### Relación con `bono_uso_detalle`

```sql
SELECT * FROM bono_uso_detalle
WHERE cita_id = [ID_CITA]
  AND servicio_id = [ID_SERVICIO]
```

Si existe registro → Servicio fue pagado con bono

#### Campos Importantes en `bonosVendidos`

```php
$bono->pivot->precio          // Precio total del bono
$bono->precio_pagado          // Lo que se pagó
$bono->metodo_pago            // 'efectivo', 'tarjeta', 'deuda'
$bono->plantilla->nombre      // Nombre del bono
$bono->plantilla->servicios   // Servicios incluidos
```

---

## 🎨 Sistema de Colores

| Elemento | Color | Uso |
|----------|-------|-----|
| Servicios normales | Azul (`#3b82f6`) | Servicio pagado en efectivo/tarjeta |
| Servicios con bono | Amarillo (`#f59e0b`) | Servicio pagado con bono |
| Productos | Verde (`#10b981`) | Productos vendidos |
| Bonos vendidos | Naranja/Dorado (`#f59e0b`) | Bono vendido |
| Badge BONO | Gradiente dorado | Indicador visual de bono |

---

## ✅ Beneficios de la Mejora

### Para el Usuario

1. ✅ **Claridad inmediata**: Servicios con bono tienen badge visible
2. ✅ **Información completa**: Ve qué bonos se vendieron y su estado de pago
3. ✅ **Auditoría fácil**: Puede verificar bonos vendidos vs bonos consumidos
4. ✅ **Transparencia financiera**: Totales separados para servicios y bonos

### Para el Negocio

1. ✅ **Control de inventario de bonos**: Cuántos bonos se vendieron por día
2. ✅ **Seguimiento de deudas**: Bonos vendidos a crédito visibles
3. ✅ **Análisis de rentabilidad**: Distinguir ingresos por servicios vs bonos
4. ✅ **Detección de errores**: Servicios a €0 sin bono son más fáciles de detectar

### Para Contabilidad

1. ✅ **Diferenciación clara**: Bonos vendidos (ingreso anticipado) vs bonos consumidos (sin ingreso)
2. ✅ **Cuentas por cobrar**: Deuda de bonos separada de deuda de servicios
3. ✅ **Conciliación**: Total facturado incluye bonos vendidos correctamente
4. ✅ **Reportes precisos**: Desglose exacto por tipo de transacción

---

## 📈 Impacto Visual

### Antes
```
─────────────────────────────────────
Servicios          | Productos
─────────────────────────────────────
Corte €20          | Champú x2 €24
Tinte €0 (???)     |
─────────────────────────────────────
```
❌ No se sabe por qué Tinte está a €0
❌ No se ven bonos vendidos

### Después
```
───────────────────────────────────────────────────────
Servicios          | Productos    | Bonos Vendidos
───────────────────────────────────────────────────────
Corte €20          | Champú x2    | Bono Premium
👨‍💼 María          | €24          | 5 Sesiones
                   |              | 💵 Efectivo
Tinte €0 🎫 BONO   |              | €100
👨‍💼 María          |              |
(Borde amarillo)   |              |
───────────────────────────────────────────────────────

Total Facturado: 144.00 €
  • Servicios/Productos: 44€
  • Bonos Vendidos: 100€

💰 Total Bonos Vendidos: 🎫 100.00 €
   ✓ Cobrado: 100.00 €
   1 bono vendido
```
✅ Todo claro y detallado

---

## 🧪 Testing

### Escenarios Probados

- [x] Servicio pagado con bono (muestra badge)
- [x] Servicio pagado sin bono (sin badge)
- [x] Cobro sin bonos vendidos (grid 2 columnas)
- [x] Cobro con bonos vendidos (grid 3 columnas)
- [x] Bono pagado completamente (sin deuda)
- [x] Bono con pago parcial (muestra deuda)
- [x] Bono a deber completo (método pago = deuda)
- [x] Múltiples bonos en un cobro
- [x] Resumen del día con bonos vendidos
- [x] Resumen del día sin bonos vendidos

---

## 📝 Archivos Modificados

```
resources/views/cobros/index.blade.php
  - CSS: +2 estilos nuevos (bono, bono-badge)
  - Grid: Dinámico 2/3 columnas según contenido
  - Servicios: +1 campo 'es_bono' y badge visual
  - Nueva sección: BONOS VENDIDOS (60 líneas)
  - Resumen: +1 panel Total Bonos Vendidos (40 líneas)
```

**Total de líneas agregadas**: ~150 líneas
**Líneas modificadas**: ~30 líneas

---

## 🚀 Próximas Mejoras Sugeridas

1. **Filtro por tipo**: Filtrar solo cobros con bonos vendidos
2. **Estadísticas de bonos**: Bonos más vendidos, empleado que más bonos vende
3. **Alertas**: Notificación cuando un bono tiene deuda >30 días
4. **Exportación**: PDF/Excel con desglose de bonos vendidos
5. **Gráfico**: Visualización de bonos vendidos vs consumidos por mes

---

## ✅ Conclusión

La mejora implementada proporciona:

- ✅ **Visibilidad completa** de bonos vendidos en el registro de cobros
- ✅ **Distinción clara** entre servicios pagados con bono vs pagados normalmente
- ✅ **Información financiera precisa** en el resumen del día
- ✅ **Mejor UX** con indicadores visuales intuitivos (badges, colores, iconos)

Esto facilita la gestión diaria, auditoría financiera y detección de errores en el sistema de bonos del salón.
