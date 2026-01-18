<?php

/**
 * Script para corregir facturación histórica después de implementar nuevos cambios
 * 
 * Ejecutar en servidor con: php corregir_facturacion_historica.php
 * O desde raíz: php scripts/corregir_facturacion_historica.php
 */

// Detectar si estamos en scripts/ o en raíz
$vendorPath = file_exists(__DIR__.'/vendor/autoload.php') 
    ? __DIR__.'/vendor/autoload.php'
    : __DIR__.'/../vendor/autoload.php';

$bootstrapPath = file_exists(__DIR__.'/bootstrap/app.php')
    ? __DIR__.'/bootstrap/app.php'
    : __DIR__.'/../bootstrap/app.php';

require $vendorPath;
$app = require_once $bootstrapPath;
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  CORRECCIÓN DE FACTURACIÓN HISTÓRICA                      ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";
echo "\n";

// Obtener tenant
echo "Ingrese el ID del tenant (ej: salonlh): ";
$tenantId = trim(fgets(STDIN));

$tenant = Tenant::find($tenantId);
if (!$tenant) {
    echo "❌ Tenant no encontrado\n";
    exit(1);
}

tenancy()->initialize($tenant);
echo "✅ Tenant inicializado: {$tenant->id}\n\n";

echo "Este script realizará las siguientes correcciones:\n";
echo "1. Actualizar a precio 0 los servicios pagados con bono\n";
echo "2. Marcar todos los cobros como contabilizados\n";
echo "\n";
echo "⚠️  ADVERTENCIA: Esto modificará datos históricos\n";
echo "¿Desea continuar? Escriba 'SI' para confirmar: ";
$confirmacion = trim(fgets(STDIN));

if ($confirmacion !== 'SI') {
    echo "❌ Operación cancelada\n";
    exit(0);
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "INICIANDO CORRECCIONES...\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// 1. Corregir servicios pagados con bono
echo "📋 Buscando servicios pagados con bono...\n";

$usoBonos = DB::table('bono_uso_detalle')
    ->select('servicio_id')
    ->distinct()
    ->whereNotNull('servicio_id')
    ->get();

$totalServicios = $usoBonos->count();
echo "   Encontrados {$totalServicios} servicios únicos con uso de bono\n\n";

$corregidos = 0;
$yaCorrectos = 0;

foreach ($usoBonos as $uso) {
    $servicioId = $uso->servicio_id;
    
    // Buscar registros en pivot que tengan precio > 0
    $pivots = DB::table('registro_cobro_servicio')
        ->where('servicio_id', $servicioId)
        ->where('precio', '>', 0)
        ->get();
    
    if ($pivots->count() > 0) {
        // Actualizar solo el primero de cada grupo de cobro
        $grupos = $pivots->groupBy('registro_cobro_id');
        
        foreach ($grupos as $cobroId => $pivotsCobro) {
            $primerPivot = $pivotsCobro->first();
            DB::table('registro_cobro_servicio')
                ->where('id', $primerPivot->id)
                ->update(['precio' => 0]);
            $corregidos++;
        }
    } else {
        $yaCorrectos++;
    }
}

echo "   ✅ Servicios actualizados: {$corregidos}\n";
echo "   ℹ️  Ya estaban correctos: {$yaCorrectos}\n\n";

// 2. Marcar todos los cobros como contabilizados
echo "📋 Actualizando flag de contabilización...\n";

$totalCobros = DB::table('registro_cobros')->count();
$actualizados = DB::table('registro_cobros')
    ->where('contabilizado', false)
    ->orWhereNull('contabilizado')
    ->update(['contabilizado' => true]);

echo "   ✅ Cobros actualizados: {$actualizados} de {$totalCobros}\n\n";

// 3. Verificación final
echo "═══════════════════════════════════════════════════════════\n";
echo "VERIFICACIÓN FINAL\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$serviciosConBonoYPrecio = DB::table('registro_cobro_servicio as rcs')
    ->join('bono_uso_detalle as bud', 'rcs.servicio_id', '=', 'bud.servicio_id')
    ->where('rcs.precio', '>', 0)
    ->count();

$cobrosNoContabilizados = DB::table('registro_cobros')
    ->where('contabilizado', false)
    ->orWhereNull('contabilizado')
    ->count();

echo "Servicios con bono y precio > 0: {$serviciosConBonoYPrecio}\n";
echo "Cobros no contabilizados: {$cobrosNoContabilizados}\n\n";

if ($serviciosConBonoYPrecio === 0 && $cobrosNoContabilizados === 0) {
    echo "✅ ¡CORRECCIÓN COMPLETADA EXITOSAMENTE!\n";
} else {
    echo "⚠️  Aún quedan inconsistencias. Revise manualmente.\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "RESUMEN DE CORRECCIONES\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "• Servicios con bono corregidos: {$corregidos}\n";
echo "• Cobros marcados como contabilizados: {$actualizados}\n";
echo "═══════════════════════════════════════════════════════════\n\n";
