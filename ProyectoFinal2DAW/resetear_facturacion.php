<?php

/**
 * Script para resetear la facturación de empleados
 * 
 * Marca todos los cobros como NO contabilizados (contabilizado = 0)
 * Esto permite empezar de cero sin borrar datos
 */

$baseDir = file_exists(__DIR__.'/vendor/autoload.php') ? __DIR__ : dirname(__DIR__);
require $baseDir.'/vendor/autoload.php';
$app = require_once $baseDir.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Inicializar tenant
$tenant = \App\Models\Tenant::first();
if (!$tenant) {
    echo "ERROR: No se encontró ningún tenant\n";
    exit(1);
}
tenancy()->initialize($tenant);

echo "\n=== RESETEAR FACTURACIÓN DE EMPLEADOS ===\n\n";

// Contar cobros actuales
$totalCobros = DB::table('registro_cobros')->count();
$cobrosContabilizados = DB::table('registro_cobros')->where('contabilizado', true)->count();

echo "Total cobros en la BD: $totalCobros\n";
echo "Cobros contabilizados: $cobrosContabilizados\n\n";

// Confirmar acción
echo "¿Deseas marcar TODOS los cobros como NO contabilizados?\n";
echo "Esto pondrá a 0 la facturación de todos los empleados.\n";
echo "Los datos NO se borrarán, solo se marca contabilizado=0.\n\n";
echo "Escribe 'SI' para confirmar: ";

$confirmacion = trim(fgets(STDIN));

if (strtoupper($confirmacion) !== 'SI') {
    echo "\n❌ Operación cancelada.\n\n";
    exit(0);
}

echo "\n⏳ Actualizando cobros...\n";

// Marcar todos los cobros como no contabilizados
$affected = DB::table('registro_cobros')
    ->update(['contabilizado' => false]);

echo "✅ $affected cobros marcados como NO contabilizados\n";
echo "\n🎯 Facturación reseteada correctamente.\n";
echo "\nPara volver a contabilizar cobros específicos:\n";
echo "  UPDATE registro_cobros SET contabilizado = 1 WHERE id IN (...);\n";
echo "  O por fecha:\n";
echo "  UPDATE registro_cobros SET contabilizado = 1 WHERE created_at >= '2026-02-01';\n\n";
