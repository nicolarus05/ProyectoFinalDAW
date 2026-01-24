<?php

use Illuminate\Support\Facades\DB;
use App\Models\Empleado;
use Carbon\Carbon;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Inicializar tenancy
tenancy()->initialize('salonlh');

echo "\n" . str_repeat('=', 80) . "\n";
echo "FACTURACIÓN POR CATEGORÍA - ENERO 2026\n";
echo str_repeat('=', 80) . "\n\n";

$fechaInicio = Carbon::parse('2026-01-01')->startOfDay();
$fechaFin = Carbon::parse('2026-01-31')->endOfDay();

// Obtener facturación por categoría
$facturacionCategoria = Empleado::facturacionPorCategoriaPorFechas($fechaInicio, $fechaFin);

echo "FACTURACIÓN POR CATEGORÍA\n";
echo str_repeat('-', 80) . "\n";

foreach (['peluqueria', 'estetica'] as $categoria) {
    $datos = $facturacionCategoria[$categoria];
    echo "\n" . strtoupper($categoria) . ":\n";
    echo "  Servicios: " . number_format($datos['servicios'], 2) . " €\n";
    echo "  Productos: " . number_format($datos['productos'], 2) . " €\n";
    echo "  Bonos:     " . number_format($datos['bonos'], 2) . " €\n";
    echo "  TOTAL:     " . number_format($datos['total'], 2) . " €\n";
}

$totalGeneral = $facturacionCategoria['peluqueria']['total'] + $facturacionCategoria['estetica']['total'];
echo "\n" . str_repeat('-', 80) . "\n";
echo "TOTAL GENERAL: " . number_format($totalGeneral, 2) . " €\n";

// Comparar con facturación por empleados
echo "\n\n" . str_repeat('=', 80) . "\n";
echo "VERIFICACIÓN: FACTURACIÓN POR EMPLEADO\n";
echo str_repeat('=', 80) . "\n\n";

$empleados = Empleado::with('user')->get();
$totalEmpleados = 0;

foreach ($empleados as $empleado) {
    $facturacion = $empleado->facturacionPorFechas($fechaInicio, $fechaFin);
    
    if ($facturacion['total'] > 0) {
        $nombreEmpleado = $empleado->user ? $empleado->user->name : "Empleado #{$empleado->id}";
        echo $nombreEmpleado . " (" . ($empleado->categoria ?? 'sin categoría') . "):\n";
        echo "  Servicios: " . number_format($facturacion['servicios'], 2) . " €\n";
        echo "  Productos: " . number_format($facturacion['productos'], 2) . " €\n";
        echo "  Bonos:     " . number_format($facturacion['bonos'], 2) . " €\n";
        echo "  Total:     " . number_format($facturacion['total'], 2) . " €\n\n";
        
        $totalEmpleados += $facturacion['total'];
    }
}

echo str_repeat('-', 80) . "\n";
echo "TOTAL POR EMPLEADOS: " . number_format($totalEmpleados, 2) . " €\n";

// Verificación de igualdad
echo "\n" . str_repeat('=', 80) . "\n";
$diferencia = abs($totalGeneral - $totalEmpleados);
if ($diferencia < 0.02) {
    echo "✅ VERIFICACIÓN EXITOSA: Los totales coinciden\n";
    echo "   Total por categoría: " . number_format($totalGeneral, 2) . " €\n";
    echo "   Total por empleados: " . number_format($totalEmpleados, 2) . " €\n";
} else {
    echo "❌ ADVERTENCIA: Los totales NO coinciden\n";
    echo "   Total por categoría: " . number_format($totalGeneral, 2) . " €\n";
    echo "   Total por empleados: " . number_format($totalEmpleados, 2) . " €\n";
    echo "   Diferencia: " . number_format($diferencia, 2) . " €\n";
}
echo str_repeat('=', 80) . "\n\n";

// Detalle de algunos cobros para verificar categorización
echo "\n" . str_repeat('=', 80) . "\n";
echo "DETALLE DE COBROS (Primeros 5)\n";
echo str_repeat('=', 80) . "\n\n";

$cobros = \App\Models\RegistroCobro::with(['servicios', 'productos', 'bonosVendidos.bonoPlantilla'])
    ->whereBetween('created_at', [$fechaInicio, $fechaFin])
    ->where('metodo_pago', '!=', 'bono')
    ->where('contabilizado', true)
    ->limit(5)
    ->get();

$service = new \App\Services\FacturacionService();

foreach ($cobros as $cobro) {
    echo "Cobro #" . $cobro->id . " - " . $cobro->created_at->format('d/m/Y H:i') . "\n";
    echo "Total: " . number_format($cobro->total_final, 2) . " €\n";
    
    $desglosePorCategoria = $service->desglosarCobroPorCategoria($cobro);
    
    echo "\nDesglose por categoría:\n";
    foreach (['peluqueria', 'estetica'] as $categoria) {
        $datos = $desglosePorCategoria[$categoria];
        $totalCat = $datos['total'];
        if ($totalCat > 0) {
            echo "  " . ucfirst($categoria) . ": " . number_format($totalCat, 2) . " € ";
            echo "(S: " . number_format($datos['servicios'], 2) . " ";
            echo "P: " . number_format($datos['productos'], 2) . " ";
            echo "B: " . number_format($datos['bonos'], 2) . ")\n";
        }
    }
    
    // Mostrar detalle de servicios y productos
    if ($cobro->servicios->count() > 0) {
        echo "\n  Servicios:\n";
        foreach ($cobro->servicios as $servicio) {
            if ($servicio->pivot->precio > 0) {
                echo "    - " . $servicio->nombre . " (" . ($servicio->categoria ?? 'sin cat') . "): ";
                echo number_format($servicio->pivot->precio, 2) . " €\n";
            }
        }
    }
    
    if ($cobro->productos->count() > 0) {
        echo "\n  Productos:\n";
        foreach ($cobro->productos as $producto) {
            echo "    - " . $producto->nombre . " (" . ($producto->categoria ?? 'sin cat') . "): ";
            echo number_format($producto->pivot->subtotal, 2) . " €\n";
        }
    }
    
    echo "\n" . str_repeat('-', 80) . "\n\n";
}

echo "Script completado.\n";
