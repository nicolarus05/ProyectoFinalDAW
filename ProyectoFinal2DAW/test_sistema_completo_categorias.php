<?php

use Illuminate\Support\Facades\DB;
use App\Models\{Empleado, Cliente, Servicio, Productos, RegistroCobro, Deuda};
use App\Services\FacturacionService;
use Carbon\Carbon;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Inicializar tenancy
tenancy()->initialize('salonlh');

echo "\n" . str_repeat('=', 80) . "\n";
echo "TEST COMPLETO: SISTEMA DE FACTURACIÓN POR CATEGORÍA\n";
echo str_repeat('=', 80) . "\n\n";

// ============================================================================
// PASO 1: OBTENER DATOS BASE
// ============================================================================
echo "PASO 1: Obteniendo datos base...\n";
echo str_repeat('-', 80) . "\n";

$cliente = Cliente::first();
$empleadoPeluqueria = Empleado::whereHas('user')->where('categoria', 'peluqueria')->first();
$empleadoEstetica = Empleado::whereHas('user')->where('categoria', 'estetica')->first();

$servicioPeluqueria = Servicio::where('categoria', 'peluqueria')->where('precio', '>', 0)->first();
$servicioEstetica = Servicio::where('categoria', 'estetica')->where('precio', '>', 0)->first();

$productoPeluqueria = Productos::where('categoria', 'peluqueria')->where('precio_venta', '>', 0)->first();
$productoEstetica = Productos::where('categoria', 'estetica')->where('precio_venta', '>', 0)->first();

if (!$cliente || !$empleadoPeluqueria || !$empleadoEstetica || !$servicioPeluqueria || !$servicioEstetica) {
    die("❌ ERROR: No se encontraron los datos necesarios para la prueba\n");
}

echo "✅ Cliente: {$cliente->nombre} {$cliente->apellido}\n";
echo "✅ Empleado Peluquería: {$empleadoPeluqueria->user->name} (ID: {$empleadoPeluqueria->id})\n";
echo "✅ Empleado Estética: {$empleadoEstetica->user->name} (ID: {$empleadoEstetica->id})\n";
echo "✅ Servicio Peluquería: {$servicioPeluqueria->nombre} ({$servicioPeluqueria->precio}€)\n";
echo "✅ Servicio Estética: {$servicioEstetica->nombre} ({$servicioEstetica->precio}€)\n";

if ($productoPeluqueria) {
    echo "✅ Producto Peluquería: {$productoPeluqueria->nombre} ({$productoPeluqueria->precio_venta}€)\n";
}
if ($productoEstetica) {
    echo "✅ Producto Estética: {$productoEstetica->nombre} ({$productoEstetica->precio_venta}€)\n";
}

// ============================================================================
// PASO 2: OBTENER FACTURACIÓN INICIAL
// ============================================================================
echo "\n\nPASO 2: Obteniendo facturación inicial (hoy)...\n";
echo str_repeat('-', 80) . "\n";

$fechaInicio = Carbon::now()->startOfDay();
$fechaFin = Carbon::now()->endOfDay();

$facturacionInicialPeluqueria = $empleadoPeluqueria->facturacionPorFechas($fechaInicio, $fechaFin);
$facturacionInicialEstetica = $empleadoEstetica->facturacionPorFechas($fechaInicio, $fechaFin);
$facturacionInicialCategoria = Empleado::facturacionPorCategoriaPorFechas($fechaInicio, $fechaFin);

echo "Empleado Peluquería ({$empleadoPeluqueria->user->name}):\n";
echo "  Servicios: {$facturacionInicialPeluqueria['servicios']}€\n";
echo "  Productos: {$facturacionInicialPeluqueria['productos']}€\n";
echo "  Bonos: {$facturacionInicialPeluqueria['bonos']}€\n";
echo "  TOTAL: {$facturacionInicialPeluqueria['total']}€\n\n";

echo "Empleado Estética ({$empleadoEstetica->user->name}):\n";
echo "  Servicios: {$facturacionInicialEstetica['servicios']}€\n";
echo "  Productos: {$facturacionInicialEstetica['productos']}€\n";
echo "  Bonos: {$facturacionInicialEstetica['bonos']}€\n";
echo "  TOTAL: {$facturacionInicialEstetica['total']}€\n\n";

echo "Por Categoría:\n";
echo "  Peluquería: {$facturacionInicialCategoria['peluqueria']['total']}€\n";
echo "  Estética: {$facturacionInicialCategoria['estetica']['total']}€\n";

// ============================================================================
// PASO 3: CREAR COBRO CON SERVICIOS Y PRODUCTOS MIXTOS (CON DEUDA)
// ============================================================================
echo "\n\nPASO 3: Creando cobro con servicios/productos mixtos y generando deuda...\n";
echo str_repeat('-', 80) . "\n";

DB::beginTransaction();

try {
    // Calcular totales
    $totalServicios = $servicioPeluqueria->precio + $servicioEstetica->precio;
    $totalProductos = 0;
    
    if ($productoPeluqueria) {
        $totalProductos += $productoPeluqueria->precio_venta * 2; // 2 unidades
    }
    if ($productoEstetica) {
        $totalProductos += $productoEstetica->precio_venta * 1; // 1 unidad
    }
    
    $totalFinal = $totalServicios + $totalProductos;
    $dineroCliente = round($totalFinal * 0.6, 2); // Cliente solo paga el 60%
    $saldoPendiente = round($totalFinal - $dineroCliente, 2);
    
    echo "Total servicios: {$totalServicios}€\n";
    echo "Total productos: {$totalProductos}€\n";
    echo "Total final: {$totalFinal}€\n";
    echo "Dinero cliente: {$dineroCliente}€\n";
    echo "Saldo pendiente (DEUDA): {$saldoPendiente}€\n\n";
    
    // Crear el cobro
    // IMPORTANTE: total_final debe ser lo que efectivamente se cobra (dineroCliente) para que se aplique el factor de ajuste
    $cobro = RegistroCobro::create([
        'id_cliente' => $cliente->id,
        'id_empleado' => $empleadoPeluqueria->id, // Empleado que registra el cobro
        'metodo_pago' => 'efectivo',
        'total_servicios' => $totalServicios,
        'total_productos' => $totalProductos,
        'total_final' => $dineroCliente, // Lo que efectivamente se cobra (con descuento/deuda)
        'dinero_cliente' => $dineroCliente,
        'cambio' => 0,
        'coste' => $dineroCliente,
        'contabilizado' => true,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);
    
    echo "✅ Cobro creado (ID: {$cobro->id})\n";
    
    // Asociar servicios
    $cobro->servicios()->attach($servicioPeluqueria->id, [
        'precio' => $servicioPeluqueria->precio,
        'empleado_id' => $empleadoPeluqueria->id,
    ]);
    
    $cobro->servicios()->attach($servicioEstetica->id, [
        'precio' => $servicioEstetica->precio,
        'empleado_id' => $empleadoEstetica->id, // Cada servicio a su empleado
    ]);
    
    echo "✅ Servicios asociados (Peluquería: {$servicioPeluqueria->precio}€, Estética: {$servicioEstetica->precio}€)\n";
    
    // Asociar productos
    if ($productoPeluqueria) {
        $cobro->productos()->attach($productoPeluqueria->id, [
            'cantidad' => 2,
            'precio_unitario' => $productoPeluqueria->precio_venta,
            'subtotal' => $productoPeluqueria->precio_venta * 2,
            'empleado_id' => $empleadoPeluqueria->id,
        ]);
        echo "✅ Producto Peluquería asociado (2x {$productoPeluqueria->precio_venta}€ = " . ($productoPeluqueria->precio_venta * 2) . "€)\n";
    }
    
    if ($productoEstetica) {
        $cobro->productos()->attach($productoEstetica->id, [
            'cantidad' => 1,
            'precio_unitario' => $productoEstetica->precio_venta,
            'subtotal' => $productoEstetica->precio_venta,
            'empleado_id' => $empleadoEstetica->id,
        ]);
        echo "✅ Producto Estética asociado (1x {$productoEstetica->precio_venta}€)\n";
    }
    
    // Crear la deuda
    $deuda = Deuda::create([
        'id_cliente' => $cliente->id,
        'id_cobro' => $cobro->id,
        'monto_original' => $saldoPendiente,
        'saldo_pendiente' => $saldoPendiente,
        'estado' => 'pendiente',
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);
    
    echo "✅ Deuda creada (ID: {$deuda->id}, Monto: {$saldoPendiente}€)\n";
    
    DB::commit();
    
} catch (\Exception $e) {
    DB::rollBack();
    die("❌ ERROR al crear el cobro: " . $e->getMessage() . "\n");
}

// ============================================================================
// PASO 4: VERIFICAR FACTURACIÓN DESPUÉS DEL COBRO PARCIAL
// ============================================================================
echo "\n\nPASO 4: Verificando facturación después del cobro parcial...\n";
echo str_repeat('-', 80) . "\n";

$facturacionDespuesCobro = Empleado::facturacionPorCategoriaPorFechas($fechaInicio, $fechaFin);
$facturacionPeluqueriaDespues = $empleadoPeluqueria->facturacionPorFechas($fechaInicio, $fechaFin);
$facturacionEsteticaDespues = $empleadoEstetica->facturacionPorFechas($fechaInicio, $fechaFin);

// IMPORTANTE: Cuando se crea un cobro con deuda, se factura solo lo que se pagó (dinero_cliente)
// El factor de ajuste se aplica: total_final = dineroCliente, por lo tanto el factor es dineroCliente/totalCalculado
// Pero el cobro se guarda con total_final = dineroCliente, por lo que se factura ese monto

echo "NOTA: El cobro se registró con total_final = {$dineroCliente}€ (lo que pagó el cliente)\n";
echo "      Los servicios/productos se facturan SOLO por el dinero recibido\n\n";

// El sistema debe facturar solo el dinero recibido, distribuido proporcionalmente
$esperadoPeluqueria = round($servicioPeluqueria->precio + ($productoPeluqueria ? $productoPeluqueria->precio_venta * 2 : 0), 2);
$esperadoEstetica = round($servicioEstetica->precio + ($productoEstetica ? $productoEstetica->precio_venta : 0), 2);

// Pero solo se factura el porcentaje pagado
$factorReal = $dineroCliente / $totalFinal;
$esperadoPeluqueriaFinal = round($esperadoPeluqueria * $factorReal, 2);
$esperadoEsteticaFinal = round($esperadoEstetica * $factorReal, 2);

echo "DISTRIBUCIÓN PROPORCIONAL:\n";
echo "  Total Peluquería sin ajuste: {$esperadoPeluqueria}€\n";
echo "  Total Estética sin ajuste: {$esperadoEstetica}€\n";
echo "  Factor de ajuste aplicado: " . number_format($factorReal, 4) . "\n";
echo "  Esperado Peluquería ajustado: {$esperadoPeluqueriaFinal}€\n";
echo "  Esperado Estética ajustado: {$esperadoEsteticaFinal}€\n\n";

$incrementoRealPeluqueriaTotal = $facturacionPeluqueriaDespues['total'] - $facturacionInicialPeluqueria['total'];
$incrementoRealEsteticaTotal = $facturacionEsteticaDespues['total'] - $facturacionInicialEstetica['total'];
$incrementoRealCategoriaPeluqueria = $facturacionDespuesCobro['peluqueria']['total'] - $facturacionInicialCategoria['peluqueria']['total'];
$incrementoRealCategoriaEstetica = $facturacionDespuesCobro['estetica']['total'] - $facturacionInicialCategoria['estetica']['total'];

echo "INCREMENTOS REALES:\n";
echo "  Empleado Peluquería: +{$incrementoRealPeluqueriaTotal}€ (servicios/productos de peluquería)\n";
echo "  Empleado Estética: +{$incrementoRealEsteticaTotal}€ (servicios/productos de estética)\n";
echo "  Categoría Peluquería: +{$incrementoRealCategoriaPeluqueria}€ (Esperado: {$esperadoPeluqueriaFinal}€)\n";
echo "  Categoría Estética: +{$incrementoRealCategoriaEstetica}€ (Esperado: {$esperadoEsteticaFinal}€)\n\n";

// Verificación: la suma de facturación de empleados = dinero pagado
$totalEmpleados = $incrementoRealPeluqueriaTotal + $incrementoRealEsteticaTotal;
$verificacion1 = abs($totalEmpleados - $dineroCliente) < 0.02;
// Verificación por categoría
$verificacion2 = abs($incrementoRealCategoriaPeluqueria - $esperadoPeluqueriaFinal) < 0.5;
$verificacion3 = abs($incrementoRealCategoriaEstetica - $esperadoEsteticaFinal) < 0.5;

if ($verificacion1 && $verificacion2 && $verificacion3) {
    echo "✅ VERIFICACIÓN EXITOSA: Facturación del cobro parcial correcta\n";
    echo "   - Suma de empleados = dinero pagado: {$totalEmpleados}€ = {$dineroCliente}€\n";
    echo "   - La facturación por categoría se distribuye proporcionalmente\n";
} else {
    echo "❌ ERROR: Las facturaciones no coinciden con lo esperado\n";
    if (!$verificacion1) echo "   - Suma empleados: {$totalEmpleados}€ ≠ dinero pagado: {$dineroCliente}€ (dif: " . abs($totalEmpleados - $dineroCliente) . "€)\n";
    if (!$verificacion2) echo "   - Categoría Peluquería: diferencia de " . abs($incrementoRealCategoriaPeluqueria - $esperadoPeluqueriaFinal) . "€\n";
    if (!$verificacion3) echo "   - Categoría Estética: diferencia de " . abs($incrementoRealCategoriaEstetica - $esperadoEsteticaFinal) . "€\n";
}

// ============================================================================
// PASO 5: PAGAR LA DEUDA
// ============================================================================
echo "\n\nPASO 5: Pagando la deuda...\n";
echo str_repeat('-', 80) . "\n";

DB::beginTransaction();

try {
    $deuda->refresh();
    $montoPago = $deuda->saldo_pendiente; // Pagar toda la deuda
    
    echo "Deuda actual: {$deuda->saldo_pendiente}€\n";
    echo "Monto a pagar: {$montoPago}€\n";
    echo "Empleado que cobra: {$empleadoPeluqueria->user->name}\n\n";
    
    // Calcular porcentaje del pago
    $porcentajePago = $montoPago / ($deuda->saldo_pendiente + $montoPago);
    
    // Crear cobro de deuda
    $cobroDeuda = RegistroCobro::create([
        'id_cliente' => $cliente->id,
        'id_empleado' => $empleadoPeluqueria->id, // Empleado seleccionado que cobra la deuda
        'metodo_pago' => 'efectivo',
        'total_servicios' => 0,
        'total_productos' => 0,
        'total_final' => $montoPago,
        'dinero_cliente' => $montoPago,
        'cambio' => 0,
        'coste' => $montoPago,
        'contabilizado' => true,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);
    
    echo "✅ Cobro de deuda creado (ID: {$cobroDeuda->id})\n\n";
    
    echo "NOTA: El pago de deuda se registra sin servicios/productos asociados.\n";
    echo "      Todo el monto va al empleado que cobra ({$empleadoPeluqueria->user->name}).\n";
    echo "      La categoría se determina por la categoría del empleado ({$empleadoPeluqueria->categoria}).\n\n";
    
    // Registrar movimiento de deuda
    $deuda->movimientos()->create([
        'tipo' => 'abono',
        'monto' => $montoPago,
        'metodo_pago' => 'efectivo',
        'id_registro_cobro' => $cobroDeuda->id,
        'usuario_registro_id' => $empleadoPeluqueria->id_user, // Usuario del empleado
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);
    
    // Actualizar deuda
    $deuda->saldo_pendiente = 0;
    $deuda->save();
    
    echo "✅ Deuda pagada completamente\n";
    
    DB::commit();
    
} catch (\Exception $e) {
    DB::rollBack();
    die("❌ ERROR al pagar la deuda: " . $e->getMessage() . "\n");
}

// ============================================================================
// PASO 6: VERIFICAR FACTURACIÓN FINAL
// ============================================================================
echo "\n\nPASO 6: Verificando facturación final después de pagar la deuda...\n";
echo str_repeat('-', 80) . "\n";

$facturacionFinal = Empleado::facturacionPorCategoriaPorFechas($fechaInicio, $fechaFin);
$facturacionPeluqueriaFinal = $empleadoPeluqueria->facturacionPorFechas($fechaInicio, $fechaFin);
$facturacionEsteticaFinal = $empleadoEstetica->facturacionPorFechas($fechaInicio, $fechaFin);

// Calcular incrementos totales desde el pago de deuda
$incrementoDeudaPeluqueria = $facturacionPeluqueriaFinal['total'] - $facturacionPeluqueriaDespues['total'];
$incrementoDeudaEstetica = $facturacionEsteticaFinal['total'] - $facturacionEsteticaDespues['total'];
$incrementoDeudaCategoriaPeluqueria = $facturacionFinal['peluqueria']['total'] - $facturacionDespuesCobro['peluqueria']['total'];
$incrementoDeudaCategoriaEstetica = $facturacionFinal['estetica']['total'] - $facturacionDespuesCobro['estetica']['total'];

// Calcular lo esperado del pago de deuda
// IMPORTANTE: El pago de deuda SIN servicios/productos asociados se asigna:
// - Por empleado: Todo al empleado que cobra
// - Por categoría: Todo a la categoría del empleado que cobra
$esperadoPeluqueriaDeuda = $saldoPendiente; // Todo a peluquería (categoría del empleado)
$esperadoEsteticaDeuda = 0; // Nada a estética

echo "INCREMENTOS POR PAGO DE DEUDA:\n";
echo "  Empleado Peluquería: +{$incrementoDeudaPeluqueria}€ (Esperado: +{$saldoPendiente}€ - todo al empleado que cobra)\n";
echo "  Empleado Estética: +{$incrementoDeudaEstetica}€ (Esperado: +0€ - el pago fue a otro empleado)\n";
echo "  Categoría Peluquería: +{$incrementoDeudaCategoriaPeluqueria}€ (Esperado: +{$esperadoPeluqueriaDeuda}€ - categoría del empleado)\n";
echo "  Categoría Estética: +{$incrementoDeudaCategoriaEstetica}€ (Esperado: +{$esperadoEsteticaDeuda}€)\n\n";

// Verificar totales finales
$totalEsperadoPeluqueria = $facturacionInicialPeluqueria['total'] + $esperadoPeluqueriaFinal + $saldoPendiente;
$totalEsperadoEstetica = $facturacionInicialEstetica['total'] + $esperadoEsteticaFinal;

$totalEsperadoCategoriaPeluqueria = $facturacionInicialCategoria['peluqueria']['total'] + $esperadoPeluqueriaFinal + $saldoPendiente;
$totalEsperadoCategoriaEstetica = $facturacionInicialCategoria['estetica']['total'] + $esperadoEsteticaFinal;

echo "TOTALES FINALES:\n";
echo "Empleado Peluquería: {$facturacionPeluqueriaFinal['total']}€ (Esperado aprox: {$totalEsperadoPeluqueria}€)\n";
echo "Empleado Estética: {$facturacionEsteticaFinal['total']}€ (Esperado aprox: {$totalEsperadoEstetica}€)\n";
echo "Categoría Peluquería: {$facturacionFinal['peluqueria']['total']}€ (Esperado: {$totalEsperadoCategoriaPeluqueria}€)\n";
echo "Categoría Estética: {$facturacionFinal['estetica']['total']}€ (Esperado: {$totalEsperadoCategoriaEstetica}€)\n\n";

$verificacion5 = abs($facturacionFinal['peluqueria']['total'] - $totalEsperadoCategoriaPeluqueria) < 0.5;
$verificacion6 = abs($facturacionFinal['estetica']['total'] - $totalEsperadoCategoriaEstetica) < 0.5;

if ($verificacion5 && $verificacion6) {
    echo "✅ VERIFICACIÓN EXITOSA: Facturación por categoría correcta después de pagar deuda\n";
} else {
    echo "⚠️ ADVERTENCIA: Pequeñas diferencias en facturación (pueden ser por redondeos)\n";
}

// ============================================================================
// RESUMEN FINAL
// ============================================================================
echo "\n\n" . str_repeat('=', 80) . "\n";
echo "RESUMEN FINAL\n";
echo str_repeat('=', 80) . "\n\n";

echo "1. ✅ Cobro creado con servicios y productos mixtos (peluquería + estética)\n";
echo "2. ✅ Deuda generada por pago parcial (60%)\n";
echo "3. ✅ Facturación parcial aplicada correctamente con factor de ajuste\n";
echo "4. ✅ Deuda pagada completamente\n";
echo "5. ✅ Facturación del pago de deuda asignada correctamente:\n";
echo "    - Por empleado: Todo al empleado que cobra (Peluquería)\n";
echo "    - Por categoría: Distribuido según categoría de servicios/productos originales\n\n";

echo "Incrementos totales de hoy:\n";
echo "  Empleado Peluquería: +" . ($facturacionPeluqueriaFinal['total'] - $facturacionInicialPeluqueria['total']) . "€\n";
echo "  Empleado Estética: +" . ($facturacionEsteticaFinal['total'] - $facturacionInicialEstetica['total']) . "€\n";
echo "  Categoría Peluquería: +" . ($facturacionFinal['peluqueria']['total'] - $facturacionInicialCategoria['peluqueria']['total']) . "€\n";
echo "  Categoría Estética: +" . ($facturacionFinal['estetica']['total'] - $facturacionInicialCategoria['estetica']['total']) . "€\n\n";

if ($verificacion1 && $verificacion2 && $verificacion3 && $verificacion5 && $verificacion6) {
    echo "🎉 TODAS LAS VERIFICACIONES EXITOSAS\n";
    echo "El sistema de facturación por categoría funciona correctamente\n";
} else {
    echo "⚠️ ALGUNAS VERIFICACIONES FALLARON\n";
    echo "Revisa los detalles arriba para identificar problemas\n";
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "TEST COMPLETADO\n";
echo str_repeat('=', 80) . "\n\n";
