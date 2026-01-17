 x# ANÁLISIS COMPLETO DEL SISTEMA DE DEUDAS

## 📊 RESUMEN EJECUTIVO

El sistema tiene una **arquitectura dual** para el manejo de deudas que causa confusión y problemas de cálculo:

1. **Campo `deuda` en `registro_cobros`**: Indica cuánto quedó sin pagar en ese cobro específico
2. **Tablas `deudas` y `movimientos_deuda`**: Sistema separado que acumula el saldo total del cliente

### ⚠️ PROBLEMA CRÍTICO IDENTIFICADO

**El campo `deuda` en `registro_cobros` NUNCA se actualiza cuando se paga la deuda**, causando que:
- Las deudas pagadas sigan apareciendo como pendientes en reportes de facturación
- No hay forma directa de saber si una deuda del cobro ya fue pagada sin consultar `movimientos_deuda`

---

## 🗃️ ESTRUCTURA DE DATOS

### Tabla: `registro_cobros`
```php
- id
- id_cita
- id_cliente
- id_empleado
- coste
- total_final
- metodo_pago (ENUM: 'efectivo','tarjeta','bono','deuda','mixto')
- deuda (decimal 10,2) // ⚠️ NUNCA SE ACTUALIZA CUANDO SE PAGA
- dinero_cliente
- pago_efectivo
- pago_tarjeta
- cambio
- timestamps
```

**Propósito**: Registrar cada transacción de cobro (ingreso de caja)

### Tabla: `deudas`
```php
- id
- id_cliente (FK a clientes)
- saldo_total (decimal 10,2) // Suma acumulada de todas las deudas
- saldo_pendiente (decimal 10,2) // Lo que aún debe el cliente
- timestamps
- deleted_at (soft delete)
```

**Propósito**: Mantener el saldo consolidado de deuda del cliente (UNO por cliente)

### Tabla: `movimientos_deuda`
```php
- id
- id_deuda (FK a deudas)
- id_registro_cobro (FK a registro_cobros, nullable)
- tipo (ENUM: 'cargo', 'abono')
- monto (decimal 10,2)
- metodo_pago (string, nullable)
- nota (text, nullable)
- fecha_vencimiento (date, nullable)
- usuario_registro_id (FK a users)
- timestamps
```

**Propósito**: Historial de movimientos (cargos y abonos) de la deuda del cliente

---

## 🔄 FLUJO COMPLETO DEL SISTEMA

### 1️⃣ CREACIÓN DE UNA DEUDA

**Ubicación**: `RegistroCobroController::store()` (líneas 890-906)

```php
// PASO 1: Se crea el registro de cobro con campo 'deuda' > 0
$cobro = RegistroCobro::create([
    'id_cita' => $data['id_cita'],
    'id_cliente' => $clienteId,
    'id_empleado' => $empleadoId,
    'coste' => $costeTotal,
    'total_final' => $totalFinal,
    'metodo_pago' => 'deuda', // O el método usado parcialmente
    'deuda' => $deuda, // ⚠️ Este valor NUNCA cambiará
    'dinero_cliente' => $dineroCliente,
    // ... otros campos
]);

// PASO 2: Se registra el cargo en el sistema de deudas
if ($deuda > 0 && $clienteId) {
    $cliente = Cliente::find($clienteId);
    $deudaCliente = $cliente->obtenerDeuda(); // Crea deuda si no existe
    
    $nota = "Cobro #" . $cobro->id . " - Cita #" . $data['id_cita'];
    $deudaCliente->registrarCargo($deuda, $nota, null, $cobro->id);
    
    // Esto ejecuta en Deuda.php:
    // - $this->saldo_total += $monto;
    // - $this->saldo_pendiente += $monto;
    // - Crea MovimientoDeuda con tipo='cargo' y id_registro_cobro
}
```

**Resultado**:
- ✅ `registro_cobros`: Tiene el cobro con campo `deuda` = X€
- ✅ `deudas`: `saldo_total` y `saldo_pendiente` aumentan en X€
- ✅ `movimientos_deuda`: Se crea un registro tipo='cargo' vinculado al cobro

---

### 2️⃣ PAGO DE UNA DEUDA

**Ubicación**: `DeudaController::registrarPago()` (líneas 64-157)

```php
// PASO 1: Validar que hay deuda pendiente
$deuda = $cliente->obtenerDeuda();
if (!$deuda->tieneDeuda()) {
    return error;
}

// PASO 2: Obtener empleado y servicios del cobro original
$ultimoCargo = $deuda->movimientos()
    ->where('tipo', 'cargo')
    ->with(['registroCobro.servicios', 'registroCobro.cita'])
    ->latest()
    ->first();

$empleadoId = $ultimoCargo->registroCobro->id_empleado;
$citaId = $ultimoCargo->registroCobro->id_cita;
$serviciosOriginales = $ultimoCargo->registroCobro->servicios;

// PASO 3: Crear NUEVO registro de cobro para el pago
$registroCobro = RegistroCobro::create([
    'id_cita' => $citaId, // Vincula con cita original
    'id_cliente' => $cliente->id,
    'id_empleado' => $empleadoId,
    'coste' => $monto,
    'total_final' => $monto,
    'metodo_pago' => $validated['metodo_pago'], // efectivo/tarjeta
    'deuda' => 0, // ⚠️ Este NUEVO cobro no genera deuda
    // ...
]);

// PASO 4: Copiar servicios originales al nuevo cobro
foreach ($serviciosOriginales as $servicio) {
    $registroCobro->servicios()->attach($servicio->id, [
        'empleado_id' => $servicio->pivot->empleado_id,
        'precio' => 0 // ⚠️ El precio ya está en 'monto'
    ]);
}

// PASO 5: Registrar abono en sistema de deudas
$deuda->registrarAbono($monto, $metodoPago, 'Pago de deuda', $registroCobro->id);

// Esto ejecuta en Deuda.php:
// - $this->saldo_pendiente -= $monto;
// - Crea MovimientoDeuda con tipo='abono' y id_registro_cobro
```

**Resultado**:
- ✅ `registro_cobros`: Se crea un NUEVO cobro (el pago) con:
  - `metodo_pago` = 'efectivo' o 'tarjeta'
  - `deuda` = 0
  - Mismos servicios que el cobro original
- ✅ `deudas`: `saldo_pendiente` disminuye en el monto pagado
- ✅ `movimientos_deuda`: Se crea registro tipo='abono' vinculado al nuevo cobro
- ❌ **PROBLEMA**: El cobro ORIGINAL sigue con campo `deuda` sin cambiar

---

## 🚨 PROBLEMAS IDENTIFICADOS

### Problema 1: Duplicación de Servicios en Facturación

**Causa**: Al pagar una deuda, se crea un nuevo cobro con los mismos servicios del original.

**Impacto**:
```
Cobro original #368:
- Total: 20€, Deuda: 20€
- 3 servicios de peluquería

Pago deuda #375:
- Total: 20€, Deuda: 0€  
- 3 servicios de peluquería (COPIADOS)

RESULTADO: Facturación cuenta 40€ en servicios (duplicado)
```

**Solución Aplicada**:
```php
// En FacturacionController.php
$cobrosDeudas = DB::table('movimientos_deuda')
    ->where('tipo', 'abono')
    ->whereBetween('created_at', [$fechaInicio, $fechaFin])
    ->pluck('id_registro_cobro')
    ->toArray();

// Excluir estos cobros de facturación (pero sí incluirlos en cajas diarias)
if (in_array($cobro->id, $cobrosDeudas)) {
    continue; // No procesar servicios/productos
}
```

### Problema 2: Deudas Pagadas Aparecen Como Pendientes

**Causa**: El campo `deuda` del cobro original nunca se actualiza a 0 cuando se paga.

**Impacto**:
```php
// En FacturacionController
$deudaTotal = $cobros->sum('deuda'); // ⚠️ Incluye deudas ya pagadas
```

**Intentos de Solución**:
1. ❌ Intentamos excluir cobros originales mediante JOIN con `deudas.id_registro_cobro` (NO EXISTE)
2. ⚠️ Actualmente simplificado: suma TODAS las deudas (pendientes y pagadas)

### Problema 3: No Hay Vinculación Directa

**Estructura actual**:
```
movimientos_deuda (cargo) → id_registro_cobro → Cobro ORIGINAL con deuda
movimientos_deuda (abono) → id_registro_cobro → Cobro NUEVO del pago
```

**Problema**: No hay forma directa de saber qué cobro original fue pagado por qué abono.

---

## ✅ SOLUCIONES PROPUESTAS

### Opción 1: Actualizar Campo `deuda` del Cobro Original (RECOMENDADA)

**Modificar**: `Deuda::registrarAbono()`

```php
public function registrarAbono($monto, $metodoPago, $nota = null, $idRegistroCobro = null)
{
    if ($monto > $this->saldo_pendiente) {
        $monto = $this->saldo_pendiente;
    }

    $this->saldo_pendiente -= $monto;
    $this->save();

    // NUEVO: Actualizar campo 'deuda' de los cobros originales
    $deudaPorCobrar = $this->saldo_pendiente;
    
    // Obtener cobros con deuda de este cliente
    $cobrosConDeuda = RegistroCobro::where('id_cliente', $this->id_cliente)
        ->where('deuda', '>', 0)
        ->orderBy('created_at', 'asc')
        ->get();
    
    foreach ($cobrosConDeuda as $cobro) {
        if ($deudaPorCobrar == 0) {
            // Deuda saldada completamente
            $cobro->update(['deuda' => 0]);
        } elseif ($deudaPorCobrar < $cobro->deuda) {
            // Deuda parcialmente pagada
            $cobro->update(['deuda' => $deudaPorCobrar]);
            break;
        }
    }

    return $this->movimientos()->create([
        'id_registro_cobro' => $idRegistroCobro,
        'tipo' => 'abono',
        'monto' => $monto,
        'metodo_pago' => $metodoPago,
        'nota' => $nota,
        'usuario_registro_id' => auth()->id() ?? 1,
    ]);
}
```

**Ventajas**:
- ✅ Campo `deuda` refleja el estado real
- ✅ Cálculo de deudas pendientes es simple: `sum('deuda')`
- ✅ No requiere cambios en base de datos

**Desventajas**:
- ⚠️ Lógica más compleja si hay múltiples cobros con deuda

### Opción 2: Agregar Campo `id_cargo_original` en `movimientos_deuda`

**Migración**:
```php
Schema::table('movimientos_deuda', function (Blueprint $table) {
    $table->foreignId('id_movimiento_cargo')->nullable()
        ->after('id_registro_cobro')
        ->constrained('movimientos_deuda')
        ->onDelete('set null');
});
```

**Uso**:
```php
// Al crear abono
$cargoOriginal = $deuda->movimientos()->where('tipo', 'cargo')->latest()->first();

$abono = $deuda->registrarAbono($monto, $metodoPago, $nota, $registroCobro->id);
$abono->update(['id_movimiento_cargo' => $cargoOriginal->id]);

// Para obtener cobro original desde abono
$cobroOriginal = $abono->cargoOriginal->registroCobro;
```

**Ventajas**:
- ✅ Trazabilidad completa cargo ↔ abono
- ✅ Fácil identificar qué deuda pagó cada abono

**Desventajas**:
- ⚠️ Requiere migración
- ⚠️ Complica casos de pagos parciales múltiples

### Opción 3: Campo Booleano `deuda_pagada` en `registro_cobros`

**Migración**:
```php
Schema::table('registro_cobros', function (Blueprint $table) {
    $table->boolean('deuda_pagada')->default(false)->after('deuda');
});
```

**Implementación**:
```php
// En Deuda::registrarAbono()
if ($this->saldo_pendiente == 0) {
    // Marcar todos los cobros de este cliente como pagados
    RegistroCobro::where('id_cliente', $this->id_cliente)
        ->where('deuda', '>', 0)
        ->update(['deuda_pagada' => true]);
}
```

**Cálculo de deudas pendientes**:
```php
$deudaTotal = $cobros
    ->where('metodo_pago', '!=', 'bono')
    ->where('deuda_pagada', false)
    ->sum('deuda');
```

**Ventajas**:
- ✅ Solución simple y clara
- ✅ Fácil de entender y mantener

**Desventajas**:
- ⚠️ No permite pagos parciales identificables
- ⚠️ Requiere migración

---

## 📋 MODELOS Y RELACIONES

### Modelo `Deuda`

**Archivo**: `app/Models/Deuda.php`

**Relaciones**:
```php
- cliente() → belongsTo(Cliente)
- movimientos() → hasMany(MovimientoDeuda)
- registrosAbonos() → hasMany(MovimientoDeuda) donde tipo='abono'
```

**Métodos Principales**:
```php
- registrarCargo($monto, $nota, $fechaVencimiento, $idRegistroCobro)
  * Aumenta saldo_total y saldo_pendiente
  * Crea MovimientoDeuda tipo='cargo'
  
- registrarAbono($monto, $metodoPago, $nota, $idRegistroCobro)
  * Disminuye saldo_pendiente (limita al saldo disponible)
  * Crea MovimientoDeuda tipo='abono'
  
- tieneDeuda() → bool
  * Retorna si saldo_pendiente > 0
```

### Modelo `MovimientoDeuda`

**Archivo**: `app/Models/MovimientoDeuda.php`

**Relaciones**:
```php
- deuda() → belongsTo(Deuda)
- registroCobro() → belongsTo(RegistroCobro)
- usuarioRegistro() → belongsTo(User)
```

**Métodos**:
```php
- getTipoFormateadoAttribute() → 'Cargo' o 'Pago'
```

### Modelo `Cliente`

**Archivo**: `app/Models/Cliente.php`

**Relaciones**:
```php
- deuda() → hasOne(Deuda)
```

**Métodos de Deuda**:
```php
- obtenerDeuda() 
  * Crea deuda si no existe
  * Retorna instancia de Deuda
  
- tieneDeudaPendiente() → bool
  
- getDeudaPendienteAttribute() → decimal
  * Accessor para $cliente->deuda_pendiente
  
- scopeConDeuda($query)
  * Filtrar clientes con deuda > 0
```

---

## 🎯 CONTROLADOR PRINCIPAL

### `DeudaController`

**Archivo**: `app/Http/Controllers/DeudaController.php`

**Métodos**:

1. **index()**: Lista clientes con deuda
   ```php
   Cliente::conDeuda()->with(['deuda', 'user'])->get()
   $totalDeuda = $clientes->sum('deuda.saldo_pendiente')
   ```

2. **show($cliente)**: Detalle de deuda del cliente
   ```php
   $movimientos = $deuda->movimientos()
       ->with(['usuarioRegistro', 'registroCobro.cita.servicios'])
       ->paginate(15)
   ```

3. **crearPago($cliente)**: Formulario para pagar deuda
   
4. **registrarPago($request, $cliente)**: Procesa el pago
   - Valida monto
   - Obtiene datos del último cargo
   - Crea nuevo RegistroCobro con los servicios copiados
   - Vincula servicios originales
   - Registra abono en sistema de deudas

5. **historial($cliente)**: Historial completo de movimientos

---

## 📱 VISTAS

### `resources/views/deudas/`

1. **index.blade.php**: Lista de clientes con deuda
   - Muestra: nombre, saldo pendiente, fecha última actualización
   - Link a detalle

2. **show.blade.php**: Detalle de deuda del cliente
   - Información del cliente
   - Saldo total y pendiente
   - Lista de movimientos (cargos y abonos)
   - Botón "Registrar pago"

3. **pago.blade.php**: Formulario de pago
   - Input monto (max: saldo_pendiente)
   - Select método pago (efectivo/tarjeta)
   - Textarea nota (opcional)
   - Botón enviar

4. **historial.blade.php**: Historial completo
   - Tabla con todos los movimientos
   - Detalles de cada cargo/abono

---

## 🔍 CASOS DE USO

### Caso 1: Cliente hace servicio y deja a deber

**Flujo**:
1. Empleado crea cita con servicios
2. Al cobrar, selecciona método "deuda" o pago parcial
3. Sistema:
   - Crea `registro_cobros` con campo `deuda` > 0
   - Llama `$cliente->obtenerDeuda()->registrarCargo()`
   - Crea/actualiza registro en `deudas`
   - Crea `movimientos_deuda` tipo='cargo'

**Estado Final**:
```
registro_cobros:
  id: 368, total_final: 20, deuda: 20, metodo_pago: 'deuda'
  
deudas:
  id: 16, id_cliente: 280, saldo_pendiente: 20
  
movimientos_deuda:
  id: 40, id_deuda: 16, tipo: 'cargo', monto: 20, id_registro_cobro: 368
```

### Caso 2: Cliente paga la deuda

**Flujo**:
1. Empleado va a "Deudas" → Cliente → "Registrar pago"
2. Ingresa monto y método de pago
3. Sistema:
   - Busca último cargo para obtener empleado/servicios
   - Crea NUEVO `registro_cobros` (#375) con:
     * Misma cita y empleado
     * Servicios copiados (con precio = 0)
     * `deuda` = 0
   - Llama `$deuda->registrarAbono()`
   - Crea `movimientos_deuda` tipo='abono'

**Estado Final**:
```
registro_cobros (ORIGINAL):
  id: 368, total_final: 20, deuda: 20 ← ⚠️ NO CAMBIA
  
registro_cobros (NUEVO - PAGO):
  id: 375, total_final: 20, deuda: 0, metodo_pago: 'efectivo'
  3 servicios (copiados del #368)
  
deudas:
  id: 16, id_cliente: 280, saldo_pendiente: 0 ← ✅ ACTUALIZADO
  
movimientos_deuda:
  id: 40 (cargo original)
  id: 41 (abono nuevo) → id_registro_cobro: 375
```

### Caso 3: Facturación Mensual (CON PROBLEMA)

**Sin Fix**:
```php
$cobros = RegistroCobro::whereBetween('created_at', [$inicio, $fin])->get();

// Cobro #368: servicios = 20€
// Cobro #375: servicios = 20€ (duplicado)
// TOTAL FACTURACIÓN: 40€ ← ❌ INCORRECTO
```

**Con Fix Actual**:
```php
$cobrosDeudas = DB::table('movimientos_deuda')
    ->where('tipo', 'abono')
    ->pluck('id_registro_cobro'); // [375]

foreach($cobros as $cobro) {
    if (in_array($cobro->id, $cobrosDeudas)) {
        continue; // Salta cobro #375
    }
    // Procesar servicios...
}
// TOTAL FACTURACIÓN: 20€ ← ✅ CORRECTO
```

**Cajas Diarias**:
```php
// Día 02/01: Cobro #368 (20€ deuda) → 0€ en caja ✅
// Día 02/01: Cobro #375 (20€ efectivo) → 20€ en caja ✅
// TOTAL DÍA 02: 20€ efectivo ← ✅ CORRECTO
```

---

## 🎯 RECOMENDACIÓN FINAL

**IMPLEMENTAR OPCIÓN 1**: Actualizar campo `deuda` del cobro original

**Razones**:
1. ✅ No requiere cambios en base de datos
2. ✅ Soluciona ambos problemas (facturación y deuda total)
3. ✅ Mantiene integridad referencial
4. ✅ Fácil de implementar y probar

**Implementación**:
1. Modificar `Deuda::registrarAbono()` para actualizar cobros originales
2. Modificar `FacturacionController` para calcular:
   ```php
   $deudaTotal = $cobros->where('metodo_pago', '!=', 'bono')->sum('deuda');
   ```
3. Agregar migración de datos para actualizar deudas históricas ya pagadas

**Alternativa**: Si se requiere trazabilidad completa, implementar Opción 2

---

## 📝 NOTAS ADICIONALES

### Request Validator

**Archivo**: `app/Http/Requests/RegistrarPagoDeudaRequest.php`

Valida:
- `monto`: required, numeric, min:0.01
- `metodo_pago`: required, in:efectivo,tarjeta
- `nota`: nullable, string, max:500

### Tests

**Archivo**: `tests/Feature/Models/DeudaModelTest.php`

Prueba:
- Creación de deudas
- Registro de cargos y abonos
- Cálculo de saldos
- Relaciones entre modelos

### Factory

**Archivo**: `database/factories/DeudaFactory.php`

Genera datos de prueba para modelo Deuda

---

## 🔧 PRÓXIMOS PASOS

1. ✅ **Fix Aplicado**: Excluir cobros de pago de deudas en facturación
2. ⏳ **Pendiente**: Solucionar cálculo de deuda total (deudas pagadas aparecen como pendientes)
3. ⏳ **Pendiente**: Implementar actualización de campo `deuda` en cobro original
4. ⏳ **Pendiente**: Migración de datos para actualizar deudas históricas
5. ⏳ **Pendiente**: Agregar tests para nuevos cambios

---

**Fecha Análisis**: 16 enero 2026  
**Autor**: GitHub Copilot  
**Versión**: 1.0
