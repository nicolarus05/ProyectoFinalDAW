<?php

/**
 * SCRIPT DE MIGRACIÓN DE DATOS HISTÓRICOS
 * 
 * Llena la tabla registro_cobro_servicio con datos de cobros antiguos
 * para que la facturación por empleado funcione correctamente.
 * 
 * EJECUCIÓN:
 * 
 * Opción 1 - Desde Artisan:
 *   php artisan facturacion:migrar-historica --dry-run
 *   php artisan facturacion:migrar-historica
 * 
 * Opción 2 - Desde Tinker:
 *   php artisan tinker
 *   include('scripts/migrar_facturacion_historica.php');
 * 
 * Opción 3 - Directamente:
 *   cd /ruta/proyecto
 *   php scripts/migrar_facturacion_historica.php
 */

// Bootstrap Laravel si se ejecuta directamente
if (php_sapi_name() === 'cli' && !class_exists('Illuminate\Foundation\Application')) {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
}

use App\Models\RegistroCobro;
use App\Models\Cita;
use Illuminate\Support\Facades\DB;

echo "=====================================\n";
echo "  MIGRACIÓN DE FACTURACIÓN HISTÓRICA\n";
echo "=====================================\n\n";

// Configuración
$DRY_RUN = false; // Cambiar a false para ejecutar realmente
$LIMITE = null; // null para todos, o número para limitar

if ($DRY_RUN) {
    echo "⚠️  MODO PRUEBA (DRY RUN) - No se guardarán cambios\n\n";
}

// Contador de resultados
$stats = [
    'total' => 0,
    'con_servicios' => 0,
    'sin_servicios' => 0,
    'servicios_creados' => 0,
    'errores' => 0,
];

echo "🔍 Buscando cobros sin servicios vinculados...\n";

// Obtener cobros que NO tienen servicios en registro_cobro_servicio
$query = RegistroCobro::whereDoesntHave('servicios')
    ->with([
        'cita.servicios',
        'cita.empleado',
        'citasAgrupadas.servicios',
        'citasAgrupadas.empleado'
    ]);

if ($LIMITE) {
    $query->limit($LIMITE);
}

$cobros = $query->get();
$total = $cobros->count();

echo "📊 Encontrados: {$total} cobros\n\n";

if ($total === 0) {
    echo "✅ No hay cobros pendientes de migrar\n";
    exit(0);
}

echo "🚀 Iniciando migración...\n";
echo str_repeat("-", 50) . "\n";

DB::beginTransaction();

try {
    foreach ($cobros as $index => $cobro) {
        $stats['total']++;
        $progreso = round(($index + 1) / $total * 100);
        
        try {
            $totalFinalServicios = $cobro->total_final;
            $serviciosCreados = 0;

            // CASO 1: Cobro de cita individual
            if ($cobro->id_cita && $cobro->cita) {
                $cita = $cobro->cita;
                
                if ($cita->servicios && $cita->servicios->count() > 0) {
                    $costoTotalServicios = $cita->servicios->sum(function($s) {
                        return $s->pivot->precio ?? $s->precio;
                    });
                    
                    if ($costoTotalServicios > 0) {
                        $proporcionServicios = $cobro->coste > 0 ? $costoTotalServicios / $cobro->coste : 1;
                        $totalServiciosConDescuento = $totalFinalServicios * $proporcionServicios;
                        
                        foreach ($cita->servicios as $servicio) {
                            $precioOriginal = $servicio->pivot->precio ?? $servicio->precio;
                            $proporcion = $precioOriginal / $costoTotalServicios;
                            $precioConDescuento = $totalServiciosConDescuento * $proporcion;
                            
                            if (!$DRY_RUN) {
                                DB::table('registro_cobro_servicio')->insert([
                                    'registro_cobro_id' => $cobro->id,
                                    'servicio_id' => $servicio->id,
                                    'empleado_id' => $cita->id_empleado,
                                    'precio' => $precioConDescuento,
                                    'created_at' => $cobro->created_at,
                                    'updated_at' => $cobro->updated_at,
                                ]);
                            }
                            
                            $serviciosCreados++;
                            $stats['servicios_creados']++;
                        }
                    }
                }
            }
            // CASO 2: Cobro de múltiples citas agrupadas
            elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                $citasAgrupadas = $cobro->citasAgrupadas;
                
                $costoTotalTodosServicios = 0;
                foreach ($citasAgrupadas as $citaGrupo) {
                    if ($citaGrupo->servicios) {
                        $costoTotalTodosServicios += $citaGrupo->servicios->sum(function($s) {
                            return $s->pivot->precio ?? $s->precio;
                        });
                    }
                }
                
                if ($costoTotalTodosServicios > 0) {
                    $proporcionServicios = $cobro->coste > 0 ? $costoTotalTodosServicios / $cobro->coste : 1;
                    $totalServiciosConDescuento = $totalFinalServicios * $proporcionServicios;
                    
                    foreach ($citasAgrupadas as $citaGrupo) {
                        if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                            foreach ($citaGrupo->servicios as $servicio) {
                                $precioOriginal = $servicio->pivot->precio ?? $servicio->precio;
                                $proporcion = $precioOriginal / $costoTotalTodosServicios;
                                $precioConDescuento = $totalServiciosConDescuento * $proporcion;
                                
                                if (!$DRY_RUN) {
                                    DB::table('registro_cobro_servicio')->insert([
                                        'registro_cobro_id' => $cobro->id,
                                        'servicio_id' => $servicio->id,
                                        'empleado_id' => $citaGrupo->id_empleado,
                                        'precio' => $precioConDescuento,
                                        'created_at' => $cobro->created_at,
                                        'updated_at' => $cobro->updated_at,
                                    ]);
                                }
                                
                                $serviciosCreados++;
                                $stats['servicios_creados']++;
                            }
                        }
                    }
                }
            }

            if ($serviciosCreados > 0) {
                $stats['con_servicios']++;
            } else {
                $stats['sin_servicios']++;
            }

            // Mostrar progreso cada 10 cobros
            if (($index + 1) % 10 === 0 || ($index + 1) === $total) {
                echo "\r[{$progreso}%] Procesados: " . ($index + 1) . "/{$total}";
            }

        } catch (\Exception $e) {
            $stats['errores']++;
            echo "\n❌ Error en cobro #{$cobro->id}: " . $e->getMessage() . "\n";
        }
    }

    echo "\n" . str_repeat("-", 50) . "\n\n";

    // Mostrar estadísticas
    echo "📈 ESTADÍSTICAS DE MIGRACIÓN:\n";
    echo "  • Cobros procesados:        {$stats['total']}\n";
    echo "  • Con servicios migrados:   {$stats['con_servicios']}\n";
    echo "  • Sin servicios (directos): {$stats['sin_servicios']}\n";
    echo "  • Servicios creados:        {$stats['servicios_creados']}\n";
    echo "  • Errores:                  {$stats['errores']}\n\n";

    if ($DRY_RUN) {
        DB::rollBack();
        echo "🔄 Cambios revertidos (modo prueba)\n";
        echo "💡 Cambia \$DRY_RUN = false para aplicar cambios\n";
    } else {
        DB::commit();
        echo "✅ Migración completada exitosamente\n";
        echo "🎉 Los datos históricos ya están corregidos\n";
    }

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=====================================\n";
echo "  MIGRACIÓN FINALIZADA\n";
echo "=====================================\n";
