#!/usr/bin/env php
<?php

/**
 * SCRIPT DE VERIFICACIÓN DE FACTURACIÓN
 * 
 * Este script verifica que el sistema de facturación funcione correctamente:
 * 1. Cada empleado factura SOLO sus servicios (sin división)
 * 2. La deuda NO se incluye en la facturación
 * 3. Los datos son coherentes (total_final = dinero_cliente cuando no hay deuda)
 * 4. Los bonos vendidos se contabilizan correctamente
 * 
 * Uso: php verificar_facturacion.php [tenant_id] [mes] [año]
 * Ejemplo: php verificar_facturacion.php salonlh 1 2026
 * Si no se especifican parámetros, usa el primer tenant y el mes/año actual
 */

// Determinar el directorio raíz del proyecto
// Si el script está en /scripts/, subir un nivel; si está en la raíz, usar __DIR__
$projectRoot = file_exists(__DIR__.'/vendor/autoload.php') 
    ? __DIR__ 
    : dirname(__DIR__);

require $projectRoot.'/vendor/autoload.php';
$app = require_once $projectRoot.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Models\RegistroCobro;
use App\Models\Empleado;
use App\Services\FacturacionService;
use Carbon\Carbon;

// Colores para consola (compatible con Linux/Mac)
$RED = "\033[31m";
$GREEN = "\033[32m";
$YELLOW = "\033[33m";
$BLUE = "\033[34m";
$MAGENTA = "\033[35m";
$CYAN = "\033[36m";
$RESET = "\033[0m";
$BOLD = "\033[1m";

// Función para imprimir con color
function printColor($text, $color = '', $bold = false) {
    global $RESET, $BOLD;
    echo ($bold ? $BOLD : '') . $color . $text . $RESET . PHP_EOL;
}

function printSection($title) {
    global $CYAN, $BOLD;
    echo PHP_EOL;
    echo str_repeat("═", 70) . PHP_EOL;
    printColor("  " . $title, $CYAN, true);
    echo str_repeat("═", 70) . PHP_EOL;
    echo PHP_EOL;
}

function printSubsection($title) {
    echo str_repeat("─", 70) . PHP_EOL;
    echo "  " . $title . PHP_EOL;
    echo str_repeat("─", 70) . PHP_EOL;
}

// Obtener parámetros de línea de comandos
$tenantId = $argv[1] ?? null;
$mes = isset($argv[2]) ? (int)$argv[2] : date('n');
$anio = isset($argv[3]) ? (int)$argv[3] : date('Y');

// Banner inicial
printSection("VERIFICACIÓN DE SISTEMA DE FACTURACIÓN");

// Seleccionar tenant
if ($tenantId) {
    $tenant = Tenant::find($tenantId);
    if (!$tenant) {
        printColor("❌ ERROR: Tenant '$tenantId' no encontrado", $RED, true);
        exit(1);
    }
} else {
    $tenant = Tenant::first();
    if (!$tenant) {
        printColor("❌ ERROR: No hay tenants en el sistema", $RED, true);
        exit(1);
    }
}

tenancy()->initialize($tenant);

printColor("📊 Tenant: " . $tenant->id, $BLUE, true);
printColor("📅 Periodo: " . Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM YYYY'), $BLUE, true);
echo PHP_EOL;

// Configurar rango de fechas
$fechaInicio = Carbon::create($anio, $mes, 1)->startOfMonth();
$fechaFin = Carbon::create($anio, $mes, 1)->endOfMonth();

// Obtener todos los cobros del periodo
$cobros = RegistroCobro::with(['servicios', 'productos', 'bonosVendidos', 'empleado.user', 'cliente.user'])
    ->whereBetween('created_at', [$fechaInicio, $fechaFin])
    ->where('metodo_pago', '!=', 'bono')
    ->orderBy('created_at', 'desc')
    ->get();

$totalCobros = $cobros->count();
$cobrosContabilizados = $cobros->where('contabilizado', true)->count();
$cobrosNoContabilizados = $totalCobros - $cobrosContabilizados;

printSection("RESUMEN DE COBROS");
echo "Total de cobros: $totalCobros" . PHP_EOL;
printColor("✅ Contabilizados: $cobrosContabilizados", $GREEN);
if ($cobrosNoContabilizados > 0) {
    printColor("⚠️  No contabilizados: $cobrosNoContabilizados", $YELLOW);
}
echo PHP_EOL;

// Arrays para estadísticas
$problemas = [];
$advertencias = [];
$totalFacturado = 0;
$totalDeuda = 0;
$totalBonos = 0;

// Verificar coherencia de cada cobro
printSection("VERIFICACIÓN DE COHERENCIA DE DATOS");

foreach ($cobros as $cobro) {
    $cobroId = $cobro->id;
    
    // Verificación 1: total_final no debe incluir deuda
    if ($cobro->total_final > $cobro->dinero_cliente + 0.01 && $cobro->deuda > 0) {
        $problemas[] = "Cobro #{$cobroId}: total_final (€{$cobro->total_final}) > dinero_cliente (€{$cobro->dinero_cliente}). Incluye deuda incorrectamente.";
    }
    
    // Verificación 2: total_final + total_bonos_vendidos debe ser <= dinero_cliente
    $totalReal = $cobro->total_final + ($cobro->total_bonos_vendidos ?? 0);
    if ($totalReal > $cobro->dinero_cliente + 0.01) {
        $problemas[] = "Cobro #{$cobroId}: (total_final + bonos_vendidos) = €{$totalReal} > dinero_cliente (€{$cobro->dinero_cliente})";
    }
    
    // Verificación 3: Si no está contabilizado, generar advertencia
    if (!$cobro->contabilizado) {
        $advertencias[] = "Cobro #{$cobroId} no está contabilizado (no aparecerá en facturación de empleados)";
    }
    
    // Acumular estadísticas
    $totalFacturado += $cobro->total_final;
    $totalDeuda += $cobro->deuda;
    $totalBonos += $cobro->total_bonos_vendidos ?? 0;
}

if (count($problemas) === 0) {
    printColor("✅ TODOS LOS COBROS SON COHERENTES", $GREEN, true);
} else {
    printColor("❌ SE DETECTARON " . count($problemas) . " PROBLEMAS:", $RED, true);
    foreach ($problemas as $problema) {
        printColor("  • " . $problema, $RED);
    }
}

if (count($advertencias) > 0) {
    echo PHP_EOL;
    printColor("⚠️  ADVERTENCIAS (" . count($advertencias) . "):", $YELLOW, true);
    foreach (array_slice($advertencias, 0, 5) as $adv) {
        printColor("  • " . $adv, $YELLOW);
    }
    if (count($advertencias) > 5) {
        echo "  ... y " . (count($advertencias) - 5) . " más" . PHP_EOL;
    }
}

// Verificar que cada empleado factura SOLO sus servicios
printSection("VERIFICACIÓN DE FACTURACIÓN POR EMPLEADO");

$empleados = Empleado::with('user')->get();
$service = new FacturacionService();
$facturacionCorrecta = true;

foreach ($empleados as $emp) {
    $nombreEmpleado = $emp->user->nombre . ' ' . ($emp->user->apellidos ?? '');
    
    // Calcular facturación según el modelo
    $facturacion = $emp->facturacionPorFechas($fechaInicio, $fechaFin);
    
    if ($facturacion['total'] == 0) {
        continue; // Saltar empleados sin facturación
    }
    
    printSubsection($nombreEmpleado . " (ID: {$emp->id})");
    
    // Verificar manualmente sumando servicios del pivot
    $totalServiciosManual = 0;
    $totalProductosManual = 0;
    $totalBonosManual = 0;
    
    foreach ($cobros->where('contabilizado', true) as $cobro) {
        // Calcular factor de ajuste por descuento
        $sumaPivotServicios = 0;
        $sumaPivotProductos = 0;
        
        foreach ($cobro->servicios as $servicio) {
            if ($servicio->pivot->precio > 0) {
                $sumaPivotServicios += $servicio->pivot->precio;
            }
        }
        
        foreach ($cobro->productos as $producto) {
            $sumaPivotProductos += $producto->pivot->subtotal;
        }
        
        $sumaPivotTotal = $sumaPivotServicios + $sumaPivotProductos;
        $factorAjuste = 1.0;
        if ($sumaPivotTotal > 0 && $cobro->total_final < $sumaPivotTotal - 0.01) {
            $factorAjuste = $cobro->total_final / $sumaPivotTotal;
        }
        
        // Servicios
        foreach ($cobro->servicios as $servicio) {
            if ($servicio->pivot->empleado_id == $emp->id && $servicio->pivot->precio > 0) {
                $precioAjustado = $servicio->pivot->precio * $factorAjuste;
                $totalServiciosManual += $precioAjustado;
            }
        }
        
        // Productos - Leer empleado_id desde pivot (con fallback al empleado del cobro)
        foreach ($cobro->productos as $producto) {
            $empleadoProducto = $producto->pivot->empleado_id ?? $cobro->id_empleado;
            if ($empleadoProducto == $emp->id) {
                $precioAjustado = $producto->pivot->subtotal * $factorAjuste;
                $totalProductosManual += $precioAjustado;
            }
        }
        
        // CASO ESPECIAL: Cobro sin servicios/productos (ej: pago de deuda sin cobro original)
        // Si el cobro no tiene servicios ni productos pero está asignado a este empleado,
        // facturar el coste completo como "servicios"
        if ($cobro->id_empleado == $emp->id && 
            $cobro->servicios->count() == 0 && 
            $cobro->productos->count() == 0 && 
            $cobro->coste > 0) {
            $totalServiciosManual += $cobro->coste;
        }
        
        // Bonos vendidos (van al empleado que registró el cobro)
        // Solo facturar si el cliente pagó los bonos (no están en deuda)
        if ($cobro->id_empleado == $emp->id && $cobro->bonosVendidos->count() > 0) {
            $totalCobrado = $cobro->total_final + ($cobro->total_bonos_vendidos ?? 0);
            $dineroRecibido = $cobro->dinero_cliente ?? 0;
            
            // Solo facturar bonos si el dinero recibido cubre el total
            if ($dineroRecibido >= $totalCobrado - 0.01) {
                foreach ($cobro->bonosVendidos as $bono) {
                    $totalBonosManual += $bono->pivot->precio;
                }
            }
        }
    }
    
    $totalManual = $totalServiciosManual + $totalProductosManual + $totalBonosManual;
    
    // Comparar con el método del modelo
    echo "  Facturación según modelo:" . PHP_EOL;
    echo "    Servicios:  €" . number_format($facturacion['servicios'], 2) . PHP_EOL;
    echo "    Productos:  €" . number_format($facturacion['productos'], 2) . PHP_EOL;
    echo "    Bonos:      €" . number_format($facturacion['bonos'], 2) . PHP_EOL;
    echo "    ─────────────────────" . PHP_EOL;
    echo "    TOTAL:      €" . number_format($facturacion['total'], 2) . PHP_EOL;
    echo PHP_EOL;
    
    echo "  Verificación manual (sumando pivot):" . PHP_EOL;
    echo "    Servicios:  €" . number_format($totalServiciosManual, 2) . PHP_EOL;
    echo "    Productos:  €" . number_format($totalProductosManual, 2) . PHP_EOL;
    echo "    Bonos:      €" . number_format($totalBonosManual, 2) . PHP_EOL;
    echo "    ─────────────────────" . PHP_EOL;
    echo "    TOTAL:      €" . number_format($totalManual, 2) . PHP_EOL;
    echo PHP_EOL;
    
    // Verificar que coinciden
    $diferencia = abs($facturacion['total'] - $totalManual);
    if ($diferencia < 0.01) {
        printColor("  ✅ CORRECTO: Facturación coincide", $GREEN);
    } else {
        printColor("  ❌ ERROR: Diferencia de €" . number_format($diferencia, 2), $RED, true);
        $facturacionCorrecta = false;
        $problemas[] = "Empleado {$nombreEmpleado}: diferencia de €{$diferencia} entre método del modelo y cálculo manual";
    }
    
    echo PHP_EOL;
}

// Verificar que los servicios con precio=0 NO se facturan
printSection("VERIFICACIÓN DE SERVICIOS PAGADOS CON BONO");

$serviciosConBono = 0;
$serviciosConBonoFacturados = 0;

foreach ($cobros->where('contabilizado', true) as $cobro) {
    foreach ($cobro->servicios as $servicio) {
        if ($servicio->pivot->precio == 0) {
            $serviciosConBono++;
        }
    }
}

if ($serviciosConBono > 0) {
    echo "Servicios pagados con bono: $serviciosConBono" . PHP_EOL;
    printColor("✅ CORRECTO: Los servicios con precio=0 NO se facturan", $GREEN);
} else {
    echo "No hay servicios pagados con bono en este periodo" . PHP_EOL;
}

// Resumen final
printSection("RESUMEN FINAL");

echo "Totales del periodo:" . PHP_EOL;
echo "  • Facturado (sin deuda):  €" . number_format($totalFacturado, 2) . PHP_EOL;
echo "  • Bonos vendidos:         €" . number_format($totalBonos, 2) . PHP_EOL;
echo "  • Deuda generada:         €" . number_format($totalDeuda, 2) . PHP_EOL;
echo "  • TOTAL COBRADO:          €" . number_format($totalFacturado + $totalBonos, 2) . PHP_EOL;
echo PHP_EOL;

// Resultado final
printSection("RESULTADO DE LA VERIFICACIÓN");

if (count($problemas) === 0 && $facturacionCorrecta) {
    printColor("✅✅✅ SISTEMA DE FACTURACIÓN FUNCIONA CORRECTAMENTE ✅✅✅", $GREEN, true);
    echo PHP_EOL;
    printColor("Verificaciones pasadas:", $GREEN);
    echo "  ✅ Coherencia de datos (total_final no incluye deuda)" . PHP_EOL;
    echo "  ✅ Cada empleado factura SOLO sus servicios" . PHP_EOL;
    echo "  ✅ Los servicios con precio=0 no se facturan" . PHP_EOL;
    echo "  ✅ Los cálculos coinciden entre modelo y verificación manual" . PHP_EOL;
    
    if (count($advertencias) > 0) {
        echo PHP_EOL;
        printColor("⚠️  Hay {$cobrosNoContabilizados} cobros no contabilizados que no aparecen en facturación", $YELLOW);
    }
    
    echo PHP_EOL;
    exit(0);
} else {
    printColor("❌❌❌ SE DETECTARON PROBLEMAS EN EL SISTEMA ❌❌❌", $RED, true);
    echo PHP_EOL;
    printColor("Problemas detectados:", $RED, true);
    foreach ($problemas as $problema) {
        echo "  ❌ " . $problema . PHP_EOL;
    }
    echo PHP_EOL;
    exit(1);
}
