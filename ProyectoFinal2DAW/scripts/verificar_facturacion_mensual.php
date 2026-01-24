<?php

/**
 * Script para verificar que el desglose de facturación mensual es correcto
 * 
 * Verifica que:
 * 1. La suma de facturación de todos los empleados = Total de cobros del mes
 * 2. El desglose por categoría (peluqueria/estetica) cuadra
 * 3. Los cobros con precio=0 se manejan correctamente
 * 4. Los cobros sin servicios (coste directo) se contabilizan bien
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Empleado;
use App\Models\RegistroCobro;
use App\Services\FacturacionService;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

// Configurar tenant
$tenantId = 'salonlh';
$tenant = Tenant::find($tenantId);

if (!$tenant) {
    echo "❌ Tenant '$tenantId' no encontrado\n";
    exit(1);
}

tenancy()->initialize($tenant);

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     VERIFICACIÓN DE FACTURACIÓN MENSUAL - TENANT: salonlh    ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Solicitar mes y año
$mes = (int)readline("Ingrese el mes (1-12) [default: mes actual]: ") ?: date('m');
$anio = (int)readline("Ingrese el año [default: año actual]: ") ?: date('Y');

$fechaInicio = date('Y-m-01', mktime(0, 0, 0, $mes, 1, $anio));
$fechaFin = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $anio));

echo "\n📅 Período: " . date('d/m/Y', strtotime($fechaInicio)) . " - " . date('d/m/Y', strtotime($fechaFin)) . "\n";
echo str_repeat("─", 66) . "\n\n";

// ========================================
// 1. CALCULAR TOTAL DE COBROS DEL MES
// ========================================
echo "📊 1. CALCULANDO TOTAL DE COBROS DEL MES...\n";
echo str_repeat("─", 66) . "\n";

$cobros = RegistroCobro::whereBetween('created_at', [$fechaInicio, $fechaFin])
    ->where('contabilizado', true)
    ->with(['servicios', 'productos'])
    ->get();

$totalCobrosReal = $cobros->sum('total_final');
$totalCostesDirectos = $cobros->where('servicios', fn($s) => $s->count() === 0)
    ->where('productos', fn($p) => $p->count() === 0)
    ->where('coste', '>', 0)
    ->sum('coste');

echo "Total cobros (total_final): €" . number_format($totalCobrosReal, 2) . "\n";
echo "Cobros sin servicios/productos (coste directo): " . $cobros->where('servicios', fn($s) => $s->count() === 0)->where('productos', fn($p) => $p->count() === 0)->count() . " cobros\n";
echo "Total de coste directo: €" . number_format($totalCostesDirectos, 2) . "\n";
echo "Cantidad de cobros: " . $cobros->count() . "\n\n";

// ========================================
// 2. CALCULAR FACTURACIÓN POR EMPLEADO
// ========================================
echo "👥 2. CALCULANDO FACTURACIÓN POR EMPLEADO...\n";
echo str_repeat("─", 66) . "\n";

$empleados = Empleado::with('user')->get();
$facturacionService = app(FacturacionService::class);

$facturacionPorEmpleado = [];
$totalFacturacionEmpleados = 0;
$totalPeluqueria = 0;
$totalEstetica = 0;

foreach ($empleados as $empleado) {
    $facturacion = $empleado->facturacionPorCategoriaPorFechas($fechaInicio, $fechaFin);
    
    $totalEmpleado = $facturacion['peluqueria']['total'] + $facturacion['estetica']['total'];
    
    if ($totalEmpleado > 0) {
        $facturacionPorEmpleado[] = [
            'empleado' => $empleado->user->nombre,
            'peluqueria' => $facturacion['peluqueria']['total'],
            'estetica' => $facturacion['estetica']['total'],
            'total' => $totalEmpleado
        ];
        
        $totalFacturacionEmpleados += $totalEmpleado;
        $totalPeluqueria += $facturacion['peluqueria']['total'];
        $totalEstetica += $facturacion['estetica']['total'];
    }
}

// Ordenar por total descendente
usort($facturacionPorEmpleado, fn($a, $b) => $b['total'] <=> $a['total']);

// Mostrar tabla
echo sprintf("%-20s %15s %15s %15s\n", "EMPLEADO", "PELUQUERÍA", "ESTÉTICA", "TOTAL");
echo str_repeat("─", 66) . "\n";

foreach ($facturacionPorEmpleado as $fact) {
    echo sprintf(
        "%-20s %15s %15s %15s\n",
        $fact['empleado'],
        '€' . number_format($fact['peluqueria'], 2),
        '€' . number_format($fact['estetica'], 2),
        '€' . number_format($fact['total'], 2)
    );
}

echo str_repeat("─", 66) . "\n";
echo sprintf("%-20s %15s %15s %15s\n", 
    "TOTAL:",
    '€' . number_format($totalPeluqueria, 2),
    '€' . number_format($totalEstetica, 2),
    '€' . number_format($totalFacturacionEmpleados, 2)
);
echo "\n";

// ========================================
// 3. COMPARACIÓN Y VERIFICACIÓN
// ========================================
echo "🔍 3. VERIFICACIÓN Y COMPARACIÓN...\n";
echo str_repeat("─", 66) . "\n";

$diferencia = abs($totalCobrosReal - $totalFacturacionEmpleados);
$porcentajeDiferencia = $totalCobrosReal > 0 ? ($diferencia / $totalCobrosReal) * 100 : 0;

echo "Total cobros del mes:              €" . number_format($totalCobrosReal, 2) . "\n";
echo "Total facturación empleados:       €" . number_format($totalFacturacionEmpleados, 2) . "\n";
echo "Diferencia:                        €" . number_format($diferencia, 2) . " (" . number_format($porcentajeDiferencia, 2) . "%)\n\n";

if ($diferencia < 0.01) {
    echo "✅ ¡PERFECTO! La facturación cuadra exactamente.\n";
} elseif ($diferencia < 1) {
    echo "✅ La facturación cuadra (diferencia mínima de redondeo).\n";
} else {
    echo "⚠️  HAY DIFERENCIAS. Revisando posibles causas...\n\n";
    
    // ========================================
    // 4. ANÁLISIS DETALLADO DE DISCREPANCIAS
    // ========================================
    echo "🔎 4. ANÁLISIS DETALLADO DE COBROS...\n";
    echo str_repeat("─", 66) . "\n";
    
    // Cobros con precio pivot = 0
    $cobrosConPreciosCero = $cobros->filter(function($cobro) {
        if ($cobro->servicios->count() === 0) return false;
        
        $sumaPivot = 0;
        foreach ($cobro->servicios as $servicio) {
            $sumaPivot += $servicio->pivot->precio;
        }
        
        return $sumaPivot < 0.01 && $cobro->total_final > 0;
    });
    
    if ($cobrosConPreciosCero->count() > 0) {
        echo "\n📋 Cobros con servicios precio=0 (manejados con CASO ESPECIAL):\n";
        foreach ($cobrosConPreciosCero as $cobro) {
            echo "  - Cobro #{$cobro->id}: €" . number_format($cobro->total_final, 2) . 
                 " con {$cobro->servicios->count()} servicios\n";
        }
    }
    
    // Cobros sin servicios ni productos
    $cobrosSinServicios = $cobros->filter(function($cobro) {
        return $cobro->servicios->count() === 0 && 
               $cobro->productos->count() === 0 && 
               $cobro->coste > 0;
    });
    
    if ($cobrosSinServicios->count() > 0) {
        echo "\n📋 Cobros sin servicios (coste directo al empleado):\n";
        foreach ($cobrosSinServicios as $cobro) {
            $empleado = $cobro->id_empleado ? Empleado::find($cobro->id_empleado) : null;
            $nombreEmpleado = $empleado ? $empleado->user->nombre : 'Sin empleado';
            echo "  - Cobro #{$cobro->id}: €" . number_format($cobro->coste, 2) . 
                 " → {$nombreEmpleado}\n";
        }
    }
    
    // Verificar desglose individual de cada cobro
    echo "\n🔍 Verificando desglose individual de cobros...\n";
    $cobrosConProblemas = [];
    
    foreach ($cobros as $cobro) {
        $desglose = $facturacionService->desglosarCobroPorCategoria($cobro);
        $totalDesglose = $desglose['peluqueria']['servicios'] + 
                        $desglose['peluqueria']['productos'] +
                        $desglose['estetica']['servicios'] +
                        $desglose['estetica']['productos'];
        
        $difCobro = abs($cobro->total_final - $totalDesglose);
        
        if ($difCobro > 0.01) {
            $cobrosConProblemas[] = [
                'id' => $cobro->id,
                'total_final' => $cobro->total_final,
                'desglose' => $totalDesglose,
                'diferencia' => $difCobro,
                'servicios' => $cobro->servicios->count(),
                'productos' => $cobro->productos->count()
            ];
        }
    }
    
    if (count($cobrosConProblemas) > 0) {
        echo "\n⚠️  COBROS CON DIFERENCIAS EN EL DESGLOSE:\n";
        echo sprintf("%-10s %12s %12s %12s %15s\n", "Cobro ID", "Total", "Desglose", "Diferencia", "Serv/Prod");
        echo str_repeat("─", 66) . "\n";
        
        foreach ($cobrosConProblemas as $problema) {
            echo sprintf(
                "%-10s %12s %12s %12s %15s\n",
                "#" . $problema['id'],
                '€' . number_format($problema['total_final'], 2),
                '€' . number_format($problema['desglose'], 2),
                '€' . number_format($problema['diferencia'], 2),
                $problema['servicios'] . '/' . $problema['productos']
            );
        }
    } else {
        echo "✅ Todos los cobros individuales desglosan correctamente.\n";
    }
}

// ========================================
// 5. DESGLOSE POR CATEGORÍA
// ========================================
echo "\n📊 5. DESGLOSE POR CATEGORÍA...\n";
echo str_repeat("─", 66) . "\n";

$totalPeluqueriaReal = 0;
$totalEsteticaReal = 0;

foreach ($cobros as $cobro) {
    $desglose = $facturacionService->desglosarCobroPorCategoria($cobro);
    $totalPeluqueriaReal += $desglose['peluqueria']['servicios'] + $desglose['peluqueria']['productos'];
    $totalEsteticaReal += $desglose['estetica']['servicios'] + $desglose['estetica']['productos'];
}

echo "Peluquería (desglose directo): €" . number_format($totalPeluqueriaReal, 2) . "\n";
echo "Peluquería (suma empleados):   €" . number_format($totalPeluqueria, 2) . "\n";
echo "Diferencia peluquería:         €" . number_format(abs($totalPeluqueriaReal - $totalPeluqueria), 2) . "\n\n";

echo "Estética (desglose directo):   €" . number_format($totalEsteticaReal, 2) . "\n";
echo "Estética (suma empleados):     €" . number_format($totalEstetica, 2) . "\n";
echo "Diferencia estética:           €" . number_format(abs($totalEsteticaReal - $totalEstetica), 2) . "\n\n";

// ========================================
// 6. RESUMEN FINAL
// ========================================
echo "\n" . str_repeat("═", 66) . "\n";
echo "                        RESUMEN FINAL                            \n";
echo str_repeat("═", 66) . "\n";

$todasLasVerificaciones = [
    'Total cuadra' => $diferencia < 1,
    'Peluquería cuadra' => abs($totalPeluqueriaReal - $totalPeluqueria) < 1,
    'Estética cuadra' => abs($totalEsteticaReal - $totalEstetica) < 1,
    'Sin cobros problemáticos' => count($cobrosConProblemas ?? []) === 0
];

foreach ($todasLasVerificaciones as $verificacion => $resultado) {
    $icono = $resultado ? '✅' : '❌';
    echo "{$icono} {$verificacion}\n";
}

echo "\n";

if (array_reduce($todasLasVerificaciones, fn($carry, $item) => $carry && $item, true)) {
    echo "🎉 ¡PERFECTO! La facturación mensual es correcta y cuadra en todos los aspectos.\n";
} else {
    echo "⚠️  Hay discrepancias que requieren revisión.\n";
}

echo str_repeat("═", 66) . "\n";
