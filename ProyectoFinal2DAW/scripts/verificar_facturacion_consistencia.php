<?php

/**
 * SCRIPT DE VERIFICACIÓN DE CONSISTENCIA DE FACTURACIÓN
 * 
 * Compara la suma de facturación de todos los empleados con el total mensual
 * para detectar discrepancias
 */

// Bootstrap Laravel
if (php_sapi_name() === 'cli' && !class_exists('Illuminate\Foundation\Application')) {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
}

use App\Models\Empleado;
use App\Models\RegistroCobro;
use Carbon\Carbon;

echo "=====================================\n";
echo "  VERIFICACIÓN DE FACTURACIÓN\n";
echo "=====================================\n\n";

// Configurar mes a verificar
$mes = (int) readline("Mes (1-12) [" . now()->month . "]: ") ?: now()->month;
$anio = (int) readline("Año [" . now()->year . "]: ") ?: now()->year;

$fechaInicio = Carbon::create($anio, $mes, 1)->startOfMonth();
$fechaFin = Carbon::create($anio, $mes, 1)->endOfMonth();

echo "\n📅 Verificando: " . $fechaInicio->isoFormat('MMMM YYYY') . "\n";
echo str_repeat("-", 60) . "\n\n";

// Obtener todos los empleados (sin filtro de estado si no existe la columna)
$empleados = Empleado::with('user')->get();

echo "👥 FACTURACIÓN POR EMPLEADOS:\n\n";

$totalEmpleadosServicios = 0;
$totalEmpleadosProductos = 0;
$totalEmpleadosBonos = 0;
$totalEmpleadosGeneral = 0;

foreach ($empleados as $empleado) {
    $facturacion = $empleado->facturacionPorFechas($fechaInicio, $fechaFin);
    
    $totalEmpleadosServicios += $facturacion['servicios'];
    $totalEmpleadosProductos += $facturacion['productos'];
    $totalEmpleadosBonos += $facturacion['bonos'];
    $totalEmpleadosGeneral += $facturacion['total'];
    
    if ($facturacion['total'] > 0) {
        $nombre = $empleado->user->nombre ?? 'Sin nombre';
        echo sprintf("  %-20s | Servicios: €%8.2f | Productos: €%8.2f | Bonos: €%8.2f | TOTAL: €%8.2f\n",
            $nombre,
            $facturacion['servicios'],
            $facturacion['productos'],
            $facturacion['bonos'],
            $facturacion['total']
        );
    }
}

echo str_repeat("-", 60) . "\n";
echo sprintf("  %-20s | Servicios: €%8.2f | Productos: €%8.2f | Bonos: €%8.2f | TOTAL: €%8.2f\n",
    "SUMA EMPLEADOS:",
    $totalEmpleadosServicios,
    $totalEmpleadosProductos,
    $totalEmpleadosBonos,
    $totalEmpleadosGeneral
);

echo "\n\n📊 FACTURACIÓN MENSUAL (TOTAL):\n\n";

// Obtener cobros del mes
$cobros = RegistroCobro::with(['cita.servicios', 'citasAgrupadas.servicios', 'servicios', 'productos'])
    ->whereBetween('created_at', [$fechaInicio, $fechaFin])
    ->get();

// Calcular servicios
$totalServiciosGeneral = 0;
foreach ($cobros as $cobro) {
    if ($cobro->metodo_pago !== 'bono' && $cobro->coste > 0) {
        // Calcular proporción de servicios
        $costoServiciosCobro = 0;
        
        if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
            $costoServiciosCobro = $cobro->cita->servicios->sum(function($s) {
                return $s->pivot->precio ?? $s->precio;
            });
        } elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
            foreach ($cobro->citasAgrupadas as $cita) {
                if ($cita->servicios) {
                    $costoServiciosCobro += $cita->servicios->sum(function($s) {
                        return $s->pivot->precio ?? $s->precio;
                    });
                }
            }
        } elseif ($cobro->servicios && $cobro->servicios->count() > 0) {
            $costoServiciosCobro = $cobro->servicios->sum(function($s) {
                return $s->pivot->precio ?? $s->precio;
            });
        }
        
        if ($costoServiciosCobro > 0) {
            $proporcionServicios = $costoServiciosCobro / $cobro->coste;
            $totalServiciosGeneral += $cobro->total_final * $proporcionServicios;
        }
    }
}

// Calcular productos
$totalProductosGeneral = 0;
foreach ($cobros as $cobro) {
    if ($cobro->metodo_pago !== 'bono' && $cobro->coste > 0 && $cobro->productos && $cobro->productos->count() > 0) {
        $costoProductosCobro = $cobro->productos->sum(function($p) {
            return $p->pivot->subtotal ?? 0;
        });
        
        if ($costoProductosCobro > 0) {
            $proporcionProductos = $costoProductosCobro / $cobro->coste;
            $totalProductosGeneral += $cobro->total_final * $proporcionProductos;
        }
    }
}

// Bonos vendidos
$totalBonosGeneral = $cobros->sum('total_bonos_vendidos');

$totalGeneralMensual = $totalServiciosGeneral + $totalProductosGeneral + $totalBonosGeneral;

echo sprintf("  %-20s | Servicios: €%8.2f | Productos: €%8.2f | Bonos: €%8.2f | TOTAL: €%8.2f\n",
    "TOTAL MENSUAL:",
    $totalServiciosGeneral,
    $totalProductosGeneral,
    $totalBonosGeneral,
    $totalGeneralMensual
);

echo "\n\n" . str_repeat("=", 60) . "\n";
echo "🔍 ANÁLISIS DE DIFERENCIAS:\n\n";

$difServicios = $totalEmpleadosServicios - $totalServiciosGeneral;
$difProductos = $totalEmpleadosProductos - $totalProductosGeneral;
$difBonos = $totalEmpleadosBonos - $totalBonosGeneral;
$difTotal = $totalEmpleadosGeneral - $totalGeneralMensual;

echo sprintf("  Servicios:  €%8.2f (%.2f%%)\n", $difServicios, $totalServiciosGeneral > 0 ? ($difServicios / $totalServiciosGeneral * 100) : 0);
echo sprintf("  Productos:  €%8.2f (%.2f%%)\n", $difProductos, $totalProductosGeneral > 0 ? ($difProductos / $totalProductosGeneral * 100) : 0);
echo sprintf("  Bonos:      €%8.2f (%.2f%%)\n", $difBonos, $totalBonosGeneral > 0 ? ($difBonos / $totalBonosGeneral * 100) : 0);
echo sprintf("  TOTAL:      €%8.2f (%.2f%%)\n", $difTotal, $totalGeneralMensual > 0 ? ($difTotal / $totalGeneralMensual * 100) : 0);

echo "\n";

$tolerancia = 0.10; // 10 céntimos de tolerancia por redondeos

if (abs($difTotal) < $tolerancia) {
    echo "✅ CONSISTENCIA PERFECTA: Las diferencias están dentro de la tolerancia de redondeo (±€{$tolerancia})\n";
} elseif (abs($difTotal) < 1.00) {
    echo "⚠️  DIFERENCIA MENOR: Hay una pequeña diferencia (€" . number_format(abs($difTotal), 2) . "). Revisar redondeos.\n";
} else {
    echo "❌ DIFERENCIA SIGNIFICATIVA: Hay discrepancias importantes (€" . number_format(abs($difTotal), 2) . "). Requiere investigación.\n";
    
    if (abs($difServicios) > 1) {
        echo "   • Revisar cálculo de servicios\n";
    }
    if (abs($difProductos) > 1) {
        echo "   • Revisar cálculo de productos\n";
    }
    if (abs($difBonos) > 1) {
        echo "   • Revisar cálculo de bonos\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
