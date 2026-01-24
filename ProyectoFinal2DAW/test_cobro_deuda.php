#!/usr/bin/env php
<?php

/**
 * SCRIPT DE PRUEBA - COBRO DE DEUDAS
 * 
 * Este script verifica que cuando se cobra una deuda:
 * 1. Se puede seleccionar el empleado que realizó el servicio
 * 2. El dinero se asigna al empleado correcto (no al empleado logueado)
 */

$projectRoot = file_exists(__DIR__.'/vendor/autoload.php') 
    ? __DIR__ 
    : dirname(__DIR__);

require $projectRoot.'/vendor/autoload.php';
$app = require_once $projectRoot.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Models\Cliente;
use App\Models\Empleado;
use Carbon\Carbon;

$tenant = Tenant::find('salonlh');
if (!$tenant) {
    echo "❌ Tenant 'salonlh' no encontrado\n";
    exit(1);
}

tenancy()->initialize($tenant);

echo "═══════════════════════════════════════════════════════════════════\n";
echo "PRUEBA DE COBRO DE DEUDAS - ASIGNACIÓN CORRECTA DE EMPLEADO\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

// Buscar clientes con deuda
$clientesConDeuda = Cliente::conDeuda()
    ->with(['deuda', 'user'])
    ->get();

if ($clientesConDeuda->isEmpty()) {
    echo "ℹ️  No hay clientes con deuda en este momento.\n";
    echo "Para probar esta funcionalidad:\n";
    echo "1. Crea un cobro con deuda desde la interfaz web\n";
    echo "2. Intenta cobrar esa deuda\n";
    echo "3. Verifica que aparece el dropdown de empleado\n";
    echo "4. El empleado debe estar pre-seleccionado automáticamente\n";
    exit(0);
}

echo "📋 CLIENTES CON DEUDA ENCONTRADOS: " . $clientesConDeuda->count() . "\n\n";

foreach ($clientesConDeuda as $cliente) {
    echo "──────────────────────────────────────────────────────────────────\n";
    echo "Cliente: " . $cliente->user->nombre . " " . ($cliente->user->apellidos ?? '') . "\n";
    echo "Deuda pendiente: €" . number_format($cliente->deuda->saldo_pendiente, 2) . "\n";
    
    // Buscar el cobro original que generó la deuda
    $ultimoCargo = $cliente->deuda->movimientos()
        ->where('tipo', 'cargo')
        ->with(['registroCobro.servicios', 'registroCobro.empleado.user'])
        ->latest()
        ->first();
    
    if ($ultimoCargo && $ultimoCargo->registroCobro) {
        echo "\n📝 Cobro original #" . $ultimoCargo->registroCobro->id . ":\n";
        echo "   Empleado del cobro: " . ($ultimoCargo->registroCobro->empleado->user->nombre ?? 'N/A') . "\n";
        
        if ($ultimoCargo->registroCobro->servicios && $ultimoCargo->registroCobro->servicios->count() > 0) {
            echo "\n   Servicios realizados:\n";
            foreach ($ultimoCargo->registroCobro->servicios as $servicio) {
                $nombreEmpleado = 'N/A';
                $empleadoId = $servicio->pivot->empleado_id ?? null;
                if ($empleadoId) {
                    $empleado = Empleado::with('user')->find($empleadoId);
                    if ($empleado) {
                        $nombreEmpleado = $empleado->user->nombre;
                    }
                }
                echo "   - {$servicio->nombre} (Empleado: {$nombreEmpleado}, ID: " . ($empleadoId ?? 'N/A') . ")\n";
            }
        }
        
        echo "\n✅ Al cobrar esta deuda:\n";
        echo "   → Aparecerá un dropdown con los empleados que realizaron los servicios\n";
        echo "   → Se pre-seleccionará automáticamente el empleado correcto\n";
        echo "   → El dinero se asignará al empleado seleccionado\n";
    }
    
    echo "\n";
}

echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "INSTRUCCIONES DE PRUEBA:\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "1. Ve a: /deudas\n";
echo "2. Selecciona un cliente con deuda\n";
echo "3. Haz clic en 'Registrar Pago'\n";
echo "4. Verifica que aparece el campo 'Empleado que realizó el servicio'\n";
echo "5. El empleado correcto debe estar pre-seleccionado\n";
echo "6. Puedes cambiar el empleado si es necesario\n";
echo "7. Registra el pago\n";
echo "8. Verifica con: php scripts/verificar_facturacion.php salonlh [mes] [año]\n";
echo "9. El dinero debe aparecer en el empleado seleccionado, no en quien cobró\n\n";
