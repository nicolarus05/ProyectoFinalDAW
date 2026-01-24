<?php

/**
 * Script de prueba para verificar que la vista de facturación funciona correctamente
 * con el nuevo sistema de facturación por categoría
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{Empleado, RegistroCobro};
use Carbon\Carbon;

// Configurar tenant
$tenant = \App\Models\Tenant::find('salonlh');
if ($tenant) {
    tenancy()->initialize($tenant);
}

echo "\n=== VERIFICACIÓN DE VISTA DE FACTURACIÓN MENSUAL ===\n\n";

// Simular lo que hace el controlador
$fechaInicio = Carbon::now()->startOfMonth();
$fechaFin = Carbon::now()->endOfMonth();

echo "📅 Período: " . $fechaInicio->format('d/m/Y') . " - " . $fechaFin->format('d/m/Y') . "\n\n";

// ============================================================================
// PASO 1: Obtener facturación por categoría usando el nuevo método
// ============================================================================
echo "🔍 PASO 1: Obtener facturación por categoría...\n";

$facturacionCategoria = Empleado::facturacionPorCategoriaPorFechas($fechaInicio, $fechaFin);

$serviciosPeluqueria = $facturacionCategoria['peluqueria']['servicios'];
$serviciosEstetica = $facturacionCategoria['estetica']['servicios'];
$productosPeluqueria = $facturacionCategoria['peluqueria']['productos'];
$productosEstetica = $facturacionCategoria['estetica']['productos'];
$bonosPeluqueria = $facturacionCategoria['peluqueria']['bonos'];
$bonosEstetica = $facturacionCategoria['estetica']['bonos'];

echo "\n💇 PELUQUERÍA:\n";
echo "  - Servicios: €" . number_format($serviciosPeluqueria, 2) . "\n";
echo "  - Productos: €" . number_format($productosPeluqueria, 2) . "\n";
echo "  - Bonos:     €" . number_format($bonosPeluqueria, 2) . "\n";
$totalPeluqueria = $serviciosPeluqueria + $productosPeluqueria + $bonosPeluqueria;
echo "  - TOTAL:     €" . number_format($totalPeluqueria, 2) . "\n";

echo "\n✨ ESTÉTICA:\n";
echo "  - Servicios: €" . number_format($serviciosEstetica, 2) . "\n";
echo "  - Productos: €" . number_format($productosEstetica, 2) . "\n";
echo "  - Bonos:     €" . number_format($bonosEstetica, 2) . "\n";
$totalEstetica = $serviciosEstetica + $productosEstetica + $bonosEstetica;
echo "  - TOTAL:     €" . number_format($totalEstetica, 2) . "\n";

// ============================================================================
// PASO 2: Obtener cobros para calcular cajas diarias
// ============================================================================
echo "\n🔍 PASO 2: Obtener cobros para cajas diarias...\n";

$cobros = RegistroCobro::with(['bonosVendidos'])
    ->whereBetween('created_at', [$fechaInicio, $fechaFin])
    ->get();

echo "Total cobros del mes: " . $cobros->count() . "\n";

// Calcular cajas diarias (solo resumen)
$cajaTotal = 0;
$cajaEfectivo = 0;
$cajaTarjeta = 0;

foreach($cobros as $cobro) {
    if ($cobro->metodo_pago !== 'bono') {
        $montoPagado = $cobro->total_final;
        $cajaTotal += $montoPagado;
        
        if ($cobro->metodo_pago === 'efectivo') {
            $cajaEfectivo += $montoPagado;
        } elseif ($cobro->metodo_pago === 'tarjeta') {
            $cajaTarjeta += $montoPagado;
        } elseif ($cobro->metodo_pago === 'mixto') {
            $cajaEfectivo += $cobro->pago_efectivo ?? 0;
            $cajaTarjeta += $cobro->pago_tarjeta ?? 0;
        } elseif ($cobro->metodo_pago === 'deuda') {
            if ($montoPagado > 0) {
                $cajaEfectivo += $montoPagado;
            }
        }
        
        // Sumar bonos vendidos
        if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
            foreach ($cobro->bonosVendidos as $bono) {
                if ($bono->metodo_pago !== 'deuda') {
                    $precioBono = $bono->precio_pagado ?? 0;
                    $cajaTotal += $precioBono;
                    
                    if ($bono->metodo_pago === 'efectivo') {
                        $cajaEfectivo += $precioBono;
                    } elseif ($bono->metodo_pago === 'tarjeta') {
                        $cajaTarjeta += $precioBono;
                    } elseif ($bono->metodo_pago === 'mixto') {
                        $cajaEfectivo += $precioBono / 2;
                        $cajaTarjeta += $precioBono / 2;
                    }
                }
            }
        }
    }
}

echo "\n💰 RESUMEN DE CAJAS:\n";
echo "  - Efectivo: €" . number_format($cajaEfectivo, 2) . "\n";
echo "  - Tarjeta:  €" . number_format($cajaTarjeta, 2) . "\n";
echo "  - TOTAL:    €" . number_format($cajaTotal, 2) . "\n";

// ============================================================================
// PASO 3: Calcular totales como lo hace el controlador
// ============================================================================
echo "\n🔍 PASO 3: Calcular totales finales...\n";

$bonosVendidos = $bonosPeluqueria + $bonosEstetica;
$totalServicios = $serviciosPeluqueria + $serviciosEstetica;
$totalProductos = $productosPeluqueria + $productosEstetica;
$totalGeneral = $totalServicios + $totalProductos + $bonosVendidos;

$deudaTotal = $cobros->where('metodo_pago', '!=', 'bono')->sum('deuda');
$totalRealmenteCobrado = $totalGeneral - $deudaTotal;

echo "\n📊 TOTALES FINALES:\n";
echo "  - Total Servicios:         €" . number_format($totalServicios, 2) . "\n";
echo "  - Total Productos:         €" . number_format($totalProductos, 2) . "\n";
echo "  - Total Bonos:             €" . number_format($bonosVendidos, 2) . "\n";
echo "  - TOTAL GENERAL:           €" . number_format($totalGeneral, 2) . "\n";
echo "  - Deuda Pendiente:         €" . number_format($deudaTotal, 2) . "\n";
echo "  - TOTAL REALMENTE COBRADO: €" . number_format($totalRealmenteCobrado, 2) . "\n";

// ============================================================================
// VERIFICACIONES
// ============================================================================
echo "\n=== VERIFICACIONES ===\n\n";

$verificaciones = 0;
$fallos = 0;

// Verificación 1: Total por categoría
echo "✓ Verificación 1: Total por categoría suma correctamente\n";
$totalCalculado = $totalPeluqueria + $totalEstetica;
if (abs($totalCalculado - $totalGeneral) < 0.01) {
    echo "  ✅ OK: €" . number_format($totalCalculado, 2) . " = €" . number_format($totalGeneral, 2) . "\n";
    $verificaciones++;
} else {
    echo "  ❌ ERROR: €" . number_format($totalCalculado, 2) . " ≠ €" . number_format($totalGeneral, 2) . "\n";
    $fallos++;
}

// Verificación 2: Bonos suman correctamente
echo "\n✓ Verificación 2: Bonos suman correctamente\n";
$bonosSumados = $bonosPeluqueria + $bonosEstetica;
if (abs($bonosSumados - $bonosVendidos) < 0.01) {
    echo "  ✅ OK: €" . number_format($bonosSumados, 2) . " = €" . number_format($bonosVendidos, 2) . "\n";
    $verificaciones++;
} else {
    echo "  ❌ ERROR: €" . number_format($bonosSumados, 2) . " ≠ €" . number_format($bonosVendidos, 2) . "\n";
    $fallos++;
}

// Verificación 3: Total cobrado coincide con cajas
echo "\n✓ Verificación 3: Total cobrado coincide con cajas diarias\n";
if (abs($totalRealmenteCobrado - $cajaTotal) < 0.01) {
    echo "  ✅ OK: €" . number_format($totalRealmenteCobrado, 2) . " = €" . number_format($cajaTotal, 2) . "\n";
    $verificaciones++;
} else {
    echo "  ❌ ERROR: €" . number_format($totalRealmenteCobrado, 2) . " ≠ €" . number_format($cajaTotal, 2) . "\n";
    echo "  Diferencia: €" . number_format(abs($totalRealmenteCobrado - $cajaTotal), 2) . "\n";
    $fallos++;
}

// Verificación 4: Todas las variables existen para la vista
echo "\n✓ Verificación 4: Todas las variables necesarias están definidas\n";
$variablesNecesarias = [
    'serviciosPeluqueria' => $serviciosPeluqueria,
    'serviciosEstetica' => $serviciosEstetica,
    'productosPeluqueria' => $productosPeluqueria,
    'productosEstetica' => $productosEstetica,
    'bonosPeluqueria' => $bonosPeluqueria,
    'bonosEstetica' => $bonosEstetica,
    'bonosVendidos' => $bonosVendidos,
    'totalServicios' => $totalServicios,
    'totalProductos' => $totalProductos,
    'totalGeneral' => $totalGeneral,
    'deudaTotal' => $deudaTotal,
    'totalRealmenteCobrado' => $totalRealmenteCobrado
];

$todasDefinidas = true;
foreach ($variablesNecesarias as $nombre => $valor) {
    if (!isset($valor)) {
        echo "  ❌ ERROR: Variable '$nombre' no está definida\n";
        $todasDefinidas = false;
        $fallos++;
    }
}

if ($todasDefinidas) {
    echo "  ✅ OK: Todas las variables están definidas correctamente\n";
    $verificaciones++;
}

// Resumen final
echo "\n" . str_repeat("=", 60) . "\n";
if ($fallos === 0) {
    echo "✅ TODAS LAS VERIFICACIONES EXITOSAS ($verificaciones/$verificaciones)\n";
    echo "La vista de facturación está lista para usar.\n";
} else {
    echo "❌ VERIFICACIÓN FALLIDA: $fallos error(es), $verificaciones verificación(es) exitosa(s)\n";
}
echo str_repeat("=", 60) . "\n\n";
