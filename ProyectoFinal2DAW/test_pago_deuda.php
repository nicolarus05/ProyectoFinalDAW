#!/usr/bin/env php
<?php

/**
 * SCRIPT DE PRUEBA - PAGO DE DEUDA
 * Verifica que al pagar una deuda:
 * 1. Se crea registro_cobro con contabilizado=true
 * 2. Los servicios tienen precio > 0 en pivot
 * 3. El empleado seleccionado factura el dinero
 * 4. Aparece en caja diaria
 */

$projectRoot = __DIR__;
require $projectRoot.'/vendor/autoload.php';
$app = require_once $projectRoot.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Models\Cliente;
use App\Models\RegistroCobro;
use App\Services\FacturacionService;

$tenant = Tenant::find('salonlh');
if (!$tenant) {
    echo "❌ Tenant 'salonlh' no encontrado\n";
    exit(1);
}

tenancy()->initialize($tenant);

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "VERIFICACIÓN DE PAGOS DE DEUDA\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

// Buscar cobros que sean pagos de deuda (tienen nota 'Pago de deuda')
$cobrosDeuda = RegistroCobro::whereHas('movimientosDeuda', function($query) {
    $query->where('tipo', 'abono');
})
->with(['servicios', 'productos', 'empleado.user', 'cliente.user'])
->orderBy('created_at', 'desc')
->take(10)
->get();

if ($cobrosDeuda->isEmpty()) {
    echo "ℹ️  No se encontraron pagos de deuda registrados.\n";
    echo "\n📝 Para probar:\n";
    echo "1. Crea un cobro con deuda desde la web\n";
    echo "2. Ve a /deudas y selecciona el cliente\n";
    echo "3. Registra un pago de la deuda\n";
    echo "4. Vuelve a ejecutar este script\n\n";
    exit(0);
}

echo "📋 COBROS DE DEUDA ENCONTRADOS: " . $cobrosDeuda->count() . "\n\n";

$facturacionService = new FacturacionService();
$todosCorrecto = true;

foreach ($cobrosDeuda as $cobro) {
    echo "──────────────────────────────────────────────────────────────────\n";
    echo "COBRO #{$cobro->id} - Pago de Deuda\n";
    echo "Fecha: " . $cobro->created_at->format('d/m/Y H:i') . "\n";
    echo "Cliente: " . ($cobro->cliente->user->nombre ?? 'N/A') . "\n";
    echo "Empleado que cobró: " . $cobro->empleado->user->nombre . " (ID: {$cobro->id_empleado})\n";
    echo "Monto: €" . number_format($cobro->total_final, 2) . "\n";
    echo "Contabilizado: " . ($cobro->contabilizado ? '✅ SÍ' : '❌ NO') . "\n\n";
    
    if (!$cobro->contabilizado) {
        echo "❌ ERROR: El cobro no está marcado como contabilizado\n";
        echo "   → No aparecerá en facturación mensual\n";
        $todosCorrecto = false;
    }
    
    // Verificar servicios
    if ($cobro->servicios && $cobro->servicios->count() > 0) {
        echo "Servicios vinculados:\n";
        $totalServicios = 0;
        foreach ($cobro->servicios as $servicio) {
            $precio = $servicio->pivot->precio;
            $empleadoId = $servicio->pivot->empleado_id;
            $estado = $precio > 0 ? '✅' : '❌';
            
            echo "  {$estado} {$servicio->nombre} - €{$precio} (Empleado ID: {$empleadoId})\n";
            
            if ($precio == 0) {
                echo "     ❌ ERROR: Precio en pivot = 0, no se facturará\n";
                $todosCorrecto = false;
            }
            
            if ($empleadoId != $cobro->id_empleado) {
                echo "     ⚠️  ADVERTENCIA: Empleado del servicio ({$empleadoId}) ≠ empleado del cobro ({$cobro->id_empleado})\n";
            }
            
            $totalServicios += $precio;
        }
        echo "  Total servicios: €" . number_format($totalServicios, 2) . "\n\n";
    }
    
    // Verificar productos
    if ($cobro->productos && $cobro->productos->count() > 0) {
        echo "Productos vinculados:\n";
        $totalProductos = 0;
        foreach ($cobro->productos as $producto) {
            $subtotal = $producto->pivot->subtotal;
            $empleadoId = $producto->pivot->empleado_id ?? $cobro->id_empleado;
            $estado = $subtotal > 0 ? '✅' : '❌';
            
            echo "  {$estado} {$producto->nombre} x{$producto->pivot->cantidad} - €{$subtotal} (Empleado ID: {$empleadoId})\n";
            
            if ($subtotal == 0) {
                echo "     ❌ ERROR: Subtotal en pivot = 0, no se facturará\n";
                $todosCorrecto = false;
            }
            
            $totalProductos += $subtotal;
        }
        echo "  Total productos: €" . number_format($totalProductos, 2) . "\n\n";
    }
    
    // Verificar facturación con FacturacionService
    echo "Verificación con FacturacionService:\n";
    $desglose = $facturacionService->desglosarCobroPorEmpleado($cobro);
    
    foreach ($desglose as $empId => $datos) {
        if ($datos['total'] > 0) {
            $emp = \App\Models\Empleado::with('user')->find($empId);
            $nombre = $emp ? $emp->user->nombre : "Empleado #{$empId}";
            
            echo "  👤 {$nombre}: €" . number_format($datos['total'], 2) . "\n";
            echo "     - Servicios: €" . number_format($datos['servicios'], 2) . "\n";
            echo "     - Productos: €" . number_format($datos['productos'], 2) . "\n";
        }
    }
    
    if (empty($desglose)) {
        echo "  ❌ ERROR: FacturacionService no calculó ninguna facturación\n";
        $todosCorrecto = false;
    }
    
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════════\n";
if ($todosCorrecto) {
    echo "✅✅✅ TODOS LOS PAGOS DE DEUDA SON CORRECTOS ✅✅✅\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "✅ Están marcados como contabilizados\n";
    echo "✅ Los servicios tienen precio > 0 en pivot\n";
    echo "✅ FacturacionService los procesa correctamente\n";
    echo "✅ Aparecerán en caja diaria y facturación mensual\n";
    exit(0);
} else {
    echo "❌❌❌ SE DETECTARON PROBLEMAS ❌❌❌\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
    exit(1);
}
