<?php

use Illuminate\Support\Facades\DB;
use App\Models\{Empleado, Cliente, Servicio, Productos, RegistroCobro, BonoPlantilla, BonoCliente};
use App\Services\FacturacionService;
use Carbon\Carbon;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize('salonlh');

echo "\n" . str_repeat('=', 80) . "\n";
echo "TEST DE EDGE CASES - FACTURACIÓN POR CATEGORÍA\n";
echo str_repeat('=', 80) . "\n\n";

$service = new FacturacionService();
$errores = [];
$warnings = [];

// ============================================================================
// CASO 1: Servicio sin categoría
// ============================================================================
echo "CASO 1: Servicio sin categoría (debe usar default 'peluqueria')\n";
echo str_repeat('-', 80) . "\n";

$servicioSinCat = Servicio::where('categoria', null)->orWhere('categoria', '')->first();

if ($servicioSinCat) {
    $cliente = Cliente::first();
    $empleado = Empleado::first();
    
    $cobro = RegistroCobro::create([
        'id_cliente' => $cliente->id,
        'id_empleado' => $empleado->id,
        'metodo_pago' => 'efectivo',
        'total_servicios' => $servicioSinCat->precio,
        'total_productos' => 0,
        'total_final' => $servicioSinCat->precio,
        'dinero_cliente' => $servicioSinCat->precio,
        'cambio' => 0,
        'coste' => $servicioSinCat->precio,
        'contabilizado' => true,
    ]);
    
    $cobro->servicios()->attach($servicioSinCat->id, [
        'precio' => $servicioSinCat->precio,
        'empleado_id' => $empleado->id,
    ]);
    
    $desglose = $service->desglosarCobroPorCategoria($cobro);
    
    if ($desglose['peluqueria']['servicios'] > 0) {
        echo "✅ Servicio sin categoría se asignó a 'peluqueria' por defecto\n";
        echo "   Monto: {$desglose['peluqueria']['servicios']}€\n";
    } else {
        $errores[] = "Servicio sin categoría no se procesó correctamente";
        echo "❌ ERROR: Servicio sin categoría no se procesó\n";
    }
    
    $cobro->delete();
} else {
    echo "⚠️ No hay servicios sin categoría para probar (esto es correcto)\n";
}

// ============================================================================
// CASO 2: Producto sin categoría
// ============================================================================
echo "\n\nCASO 2: Producto sin categoría (debe usar default 'peluqueria')\n";
echo str_repeat('-', 80) . "\n";

$productoSinCat = Productos::where('categoria', null)->orWhere('categoria', '')->first();

if ($productoSinCat) {
    $cliente = Cliente::first();
    $empleado = Empleado::first();
    
    $cobro = RegistroCobro::create([
        'id_cliente' => $cliente->id,
        'id_empleado' => $empleado->id,
        'metodo_pago' => 'efectivo',
        'total_servicios' => 0,
        'total_productos' => $productoSinCat->precio_venta,
        'total_final' => $productoSinCat->precio_venta,
        'dinero_cliente' => $productoSinCat->precio_venta,
        'cambio' => 0,
        'coste' => $productoSinCat->precio_venta,
        'contabilizado' => true,
    ]);
    
    $cobro->productos()->attach($productoSinCat->id, [
        'cantidad' => 1,
        'precio_unitario' => $productoSinCat->precio_venta,
        'subtotal' => $productoSinCat->precio_venta,
        'empleado_id' => $empleado->id,
    ]);
    
    $desglose = $service->desglosarCobroPorCategoria($cobro);
    
    if ($desglose['peluqueria']['productos'] > 0) {
        echo "✅ Producto sin categoría se asignó a 'peluqueria' por defecto\n";
        echo "   Monto: {$desglose['peluqueria']['productos']}€\n";
    } else {
        $errores[] = "Producto sin categoría no se procesó correctamente";
        echo "❌ ERROR: Producto sin categoría no se procesó\n";
    }
    
    $cobro->delete();
} else {
    echo "⚠️ No hay productos sin categoría para probar (esto es correcto)\n";
}

// ============================================================================
// CASO 3: Bono sin categoría
// ============================================================================
echo "\n\nCASO 3: Bono sin categoría (debe usar default 'peluqueria')\n";
echo str_repeat('-', 80) . "\n";

$bonoSinCat = BonoPlantilla::where('categoria', null)->orWhere('categoria', '')->first();

if ($bonoSinCat) {
    echo "⚠️ Encontrado bono sin categoría: {$bonoSinCat->nombre}\n";
    $warnings[] = "Bono '{$bonoSinCat->nombre}' (ID: {$bonoSinCat->id}) no tiene categoría asignada";
    echo "   Recomendación: Asignar categoría a este bono\n";
} else {
    echo "✅ Todos los bonos tienen categoría asignada\n";
}

// ============================================================================
// CASO 4: Cobro vacío (sin servicios ni productos)
// ============================================================================
echo "\n\nCASO 4: Cobro vacío con coste > 0 (pago de deuda)\n";
echo str_repeat('-', 80) . "\n";

$cliente = Cliente::first();
$empleado = Empleado::whereHas('user')->where('categoria', 'estetica')->first();

$cobro = RegistroCobro::create([
    'id_cliente' => $cliente->id,
    'id_empleado' => $empleado->id,
    'metodo_pago' => 'efectivo',
    'total_servicios' => 0,
    'total_productos' => 0,
    'total_final' => 50,
    'dinero_cliente' => 50,
    'cambio' => 0,
    'coste' => 50,
    'contabilizado' => true,
]);

$desglose = $service->desglosarCobroPorCategoria($cobro);

// En este caso, el método NO debe procesar nada (el caso especial se maneja en Empleado::facturacionPorCategoriaPorFechas)
if ($desglose['peluqueria']['total'] == 0 && $desglose['estetica']['total'] == 0) {
    echo "✅ Cobro vacío NO procesado por desglosarCobroPorCategoria (correcto)\n";
    echo "   El caso especial se maneja en facturacionPorCategoriaPorFechas()\n";
} else {
    $errores[] = "Cobro vacío procesado incorrectamente en desglosarCobroPorCategoria";
    echo "❌ ERROR: Cobro vacío procesado cuando no debería\n";
}

$cobro->delete();

// ============================================================================
// CASO 5: Cobro con descuento (factor de ajuste)
// ============================================================================
echo "\n\nCASO 5: Cobro con descuento - verificar factor de ajuste\n";
echo str_repeat('-', 80) . "\n";

$servicio = Servicio::where('categoria', 'peluqueria')->where('precio', '>', 0)->first();
$cliente = Cliente::first();
$empleado = Empleado::first();

$precioOriginal = $servicio->precio;
$precioConDescuento = round($precioOriginal * 0.8, 2); // 20% descuento

$cobro = RegistroCobro::create([
    'id_cliente' => $cliente->id,
    'id_empleado' => $empleado->id,
    'metodo_pago' => 'efectivo',
    'total_servicios' => $precioOriginal,
    'total_productos' => 0,
    'total_final' => $precioConDescuento,
    'dinero_cliente' => $precioConDescuento,
    'cambio' => 0,
    'coste' => $precioConDescuento,
    'contabilizado' => true,
]);

$cobro->servicios()->attach($servicio->id, [
    'precio' => $precioOriginal,
    'empleado_id' => $empleado->id,
]);

$desglose = $service->desglosarCobroPorCategoria($cobro);

$esperado = $precioConDescuento;
$real = $desglose['peluqueria']['servicios'];

if (abs($real - $esperado) < 0.01) {
    echo "✅ Factor de ajuste aplicado correctamente\n";
    echo "   Precio original: {$precioOriginal}€\n";
    echo "   Total final: {$precioConDescuento}€\n";
    echo "   Factor aplicado: " . ($precioConDescuento / $precioOriginal) . "\n";
    echo "   Resultado: {$real}€\n";
} else {
    $errores[] = "Factor de ajuste no se aplicó correctamente (esperado: {$esperado}€, real: {$real}€)";
    echo "❌ ERROR: Factor de ajuste incorrecto\n";
    echo "   Esperado: {$esperado}€, Real: {$real}€\n";
}

$cobro->delete();

// ============================================================================
// CASO 6: Bonos vendidos pagados vs en deuda
// ============================================================================
echo "\n\nCASO 6: Bonos vendidos - verificar que solo se facturen si están pagados\n";
echo str_repeat('-', 80) . "\n";

$bono = BonoPlantilla::where('categoria', '!=', null)->first();

if ($bono) {
    $cliente = Cliente::first();
    $empleado = Empleado::first();
    
    // Crear bono cliente
    $bonoCliente = BonoCliente::create([
        'cliente_id' => $cliente->id,
        'bono_plantilla_id' => $bono->id,
        'fecha_compra' => Carbon::now(),
        'fecha_expiracion' => Carbon::now()->addDays($bono->duracion_dias),
        'estado' => 'activo',
        'metodo_pago' => 'efectivo',
        'precio_pagado' => $bono->precio,
        'id_empleado' => $empleado->id,
    ]);
    
    // Caso 6a: Bono pagado completamente
    $cobro1 = RegistroCobro::create([
        'id_cliente' => $cliente->id,
        'id_empleado' => $empleado->id,
        'metodo_pago' => 'efectivo',
        'total_servicios' => 0,
        'total_productos' => 0,
        'total_final' => 0,
        'total_bonos_vendidos' => $bono->precio,
        'dinero_cliente' => $bono->precio,
        'cambio' => 0,
        'coste' => 0,
        'contabilizado' => true,
    ]);
    
    $cobro1->bonosVendidos()->attach($bonoCliente->id, ['precio' => $bono->precio]);
    
    $desglose1 = $service->desglosarCobroPorCategoria($cobro1);
    $categoriaEsperada = $bono->categoria ?? 'peluqueria';
    
    if ($desglose1[$categoriaEsperada]['bonos'] == $bono->precio) {
        echo "✅ Bono pagado completamente se facturó correctamente\n";
        echo "   Categoría: {$categoriaEsperada}, Monto: {$bono->precio}€\n";
    } else {
        $errores[] = "Bono pagado no se facturó correctamente";
        echo "❌ ERROR: Bono pagado no se facturó\n";
    }
    
    // Caso 6b: Bono en deuda (no pagado)
    $cobro2 = RegistroCobro::create([
        'id_cliente' => $cliente->id,
        'id_empleado' => $empleado->id,
        'metodo_pago' => 'efectivo',
        'total_servicios' => 0,
        'total_productos' => 0,
        'total_final' => 0,
        'total_bonos_vendidos' => $bono->precio,
        'dinero_cliente' => 0, // No pagó nada
        'cambio' => 0,
        'coste' => 0,
        'contabilizado' => true,
    ]);
    
    $cobro2->bonosVendidos()->attach($bonoCliente->id, ['precio' => $bono->precio]);
    
    $desglose2 = $service->desglosarCobroPorCategoria($cobro2);
    
    if ($desglose2[$categoriaEsperada]['bonos'] == 0) {
        echo "✅ Bono en deuda NO se facturó (correcto)\n";
    } else {
        $errores[] = "Bono en deuda se facturó cuando no debería";
        echo "❌ ERROR: Bono en deuda se facturó incorrectamente\n";
    }
    
    $cobro1->delete();
    $cobro2->delete();
    $bonoCliente->delete();
} else {
    $warnings[] = "No hay bonos con categoría para probar";
    echo "⚠️ No hay bonos con categoría para probar\n";
}

// ============================================================================
// CASO 7: Verificar categorías válidas
// ============================================================================
echo "\n\nCASO 7: Verificar que solo existen categorías válidas\n";
echo str_repeat('-', 80) . "\n";

$categoriasValidas = ['peluqueria', 'estetica'];

$serviciosInvalidos = Servicio::whereNotNull('categoria')
    ->where('categoria', '!=', '')
    ->whereNotIn('categoria', $categoriasValidas)
    ->get();

$productosInvalidos = Productos::whereNotNull('categoria')
    ->where('categoria', '!=', '')
    ->whereNotIn('categoria', $categoriasValidas)
    ->get();

$bonosInvalidos = BonoPlantilla::whereNotNull('categoria')
    ->where('categoria', '!=', '')
    ->whereNotIn('categoria', $categoriasValidas)
    ->get();

if ($serviciosInvalidos->count() == 0 && $productosInvalidos->count() == 0 && $bonosInvalidos->count() == 0) {
    echo "✅ Todas las categorías son válidas (peluqueria/estetica)\n";
} else {
    if ($serviciosInvalidos->count() > 0) {
        $errores[] = "Servicios con categorías inválidas: " . $serviciosInvalidos->pluck('id')->implode(', ');
        echo "❌ ERROR: {$serviciosInvalidos->count()} servicios con categorías inválidas\n";
    }
    if ($productosInvalidos->count() > 0) {
        $errores[] = "Productos con categorías inválidas: " . $productosInvalidos->pluck('id')->implode(', ');
        echo "❌ ERROR: {$productosInvalidos->count()} productos con categorías inválidas\n";
    }
    if ($bonosInvalidos->count() > 0) {
        $errores[] = "Bonos con categorías inválidas: " . $bonosInvalidos->pluck('id')->implode(', ');
        echo "❌ ERROR: {$bonosInvalidos->count()} bonos con categorías inválidas\n";
    }
}

// ============================================================================
// RESUMEN
// ============================================================================
echo "\n\n" . str_repeat('=', 80) . "\n";
echo "RESUMEN DE LA REVISIÓN\n";
echo str_repeat('=', 80) . "\n\n";

if (count($errores) == 0) {
    echo "🎉 TODOS LOS CASOS DE PRUEBA PASARON\n";
    echo "El sistema de facturación por categoría está funcionando correctamente\n";
} else {
    echo "❌ SE ENCONTRARON " . count($errores) . " ERROR(ES):\n";
    foreach ($errores as $i => $error) {
        echo "   " . ($i + 1) . ". " . $error . "\n";
    }
}

if (count($warnings) > 0) {
    echo "\n⚠️  ADVERTENCIAS (" . count($warnings) . "):\n";
    foreach ($warnings as $i => $warning) {
        echo "   " . ($i + 1) . ". " . $warning . "\n";
    }
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "REVISIÓN COMPLETADA\n";
echo str_repeat('=', 80) . "\n\n";
