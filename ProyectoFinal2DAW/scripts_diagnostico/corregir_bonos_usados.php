#!/usr/bin/env php
<?php

/**
 * Script para corregir bonos que están completamente usados pero tienen estado 'activo'
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BonoCliente;
use Illuminate\Support\Facades\DB;

// Obtener todos los tenants
$tenants = DB::connection('mysql')->table('tenants')->get();

foreach ($tenants as $tenantData) {
    $tenant = tenancy()->find($tenantData->id);
    
    if (!$tenant) {
        echo "⚠️  Tenant {$tenantData->id} no encontrado\n";
        continue;
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "🔧 CORRIGIENDO BONOS DEL TENANT: {$tenantData->id}\n";
    echo str_repeat("=", 70) . "\n\n";
    
    $tenant->run(function () {
        // Buscar bonos activos
        $bonosActivos = BonoCliente::where('estado', 'activo')
            ->with('servicios')
            ->get();
        
        echo "📊 Total de bonos activos: " . $bonosActivos->count() . "\n\n";
        
        $bonosCorregidos = 0;
        
        foreach ($bonosActivos as $bono) {
            echo "Bono ID: {$bono->id}\n";
            
            $todosUsados = true;
            foreach ($bono->servicios as $servicio) {
                $usado = $servicio->pivot->cantidad_usada;
                $total = $servicio->pivot->cantidad_total;
                $disponible = $total - $usado;
                
                echo "  - {$servicio->nombre}: {$usado}/{$total} (disponibles: {$disponible})\n";
                
                if ($disponible > 0) {
                    $todosUsados = false;
                }
            }
            
            if ($todosUsados && $bono->servicios->count() > 0) {
                echo "  ❌ Este bono está completamente usado pero marcado como activo\n";
                echo "  ✅ Actualizando estado a 'usado'...\n";
                
                $bono->update(['estado' => 'usado']);
                $bonosCorregidos++;
            } else {
                echo "  ✓ Este bono está correcto\n";
            }
            
            echo "\n";
        }
        
        echo str_repeat("-", 70) . "\n";
        echo "🎯 RESUMEN:\n";
        echo "  • Bonos revisados: " . $bonosActivos->count() . "\n";
        echo "  • Bonos corregidos: $bonosCorregidos\n";
        echo str_repeat("-", 70) . "\n";
    });
}

echo "\n✅ Proceso completado\n";
