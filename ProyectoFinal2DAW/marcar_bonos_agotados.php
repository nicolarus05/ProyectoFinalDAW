<?php

/**
 * Script para marcar como "usado" los bonos que están completamente agotados
 * pero aún tienen estado "activo"
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\BonoCliente;
use Carbon\Carbon;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n" . str_repeat("=", 80) . "\n";
echo "🔧 MARCAR BONOS AGOTADOS COMO 'USADO'\n";
echo str_repeat("=", 80) . "\n\n";

// Buscar bonos activos
$bonosActivos = BonoCliente::with(['servicios', 'cliente.user', 'plantilla'])
    ->where('estado', 'activo')
    ->get();

echo "📊 Total de bonos activos: " . $bonosActivos->count() . "\n\n";

$bonosCorregidos = 0;
$bonosConServiciosDisponibles = 0;

foreach ($bonosActivos as $bono) {
    $clienteNombre = $bono->cliente->user->nombre . ' ' . $bono->cliente->user->apellidos;
    $plantillaNombre = $bono->plantilla->nombre;
    
    // Verificar si está completamente usado
    $todosUsados = true;
    $detalleServicios = [];
    
    foreach ($bono->servicios as $servicio) {
        $usado = $servicio->pivot->cantidad_usada;
        $total = $servicio->pivot->cantidad_total;
        $disponible = $total - $usado;
        
        $detalleServicios[] = "      - {$servicio->nombre}: {$usado}/{$total} (disponibles: {$disponible})";
        
        if ($disponible > 0) {
            $todosUsados = false;
        }
    }
    
    if ($todosUsados) {
        echo "🔴 Bono ID: {$bono->id} - AGOTADO\n";
        echo "   Cliente: {$clienteNombre}\n";
        echo "   Plantilla: {$plantillaNombre}\n";
        echo "   Servicios:\n";
        foreach ($detalleServicios as $detalle) {
            echo "{$detalle}\n";
        }
        
        // Marcar como usado
        $bono->update(['estado' => 'usado']);
        echo "   ✅ Marcado como 'usado'\n\n";
        $bonosCorregidos++;
    } else {
        $bonosConServiciosDisponibles++;
    }
}

echo str_repeat("-", 80) . "\n";
echo "📈 RESUMEN:\n";
echo "   ✅ Bonos marcados como 'usado': {$bonosCorregidos}\n";
echo "   🟢 Bonos con servicios disponibles: {$bonosConServiciosDisponibles}\n";
echo "   📊 Total procesado: " . $bonosActivos->count() . "\n\n";

if ($bonosCorregidos > 0) {
    echo "✅ Los bonos agotados ahora aparecerán correctamente como 'usado'\n";
    echo "   y ya no se mostrarán como activos en el sistema.\n\n";
} else {
    echo "✅ No hay bonos agotados que corregir. Todo está bien.\n\n";
}

echo str_repeat("=", 80) . "\n";
echo "✅ PROCESO COMPLETADO\n";
echo str_repeat("=", 80) . "\n\n";
