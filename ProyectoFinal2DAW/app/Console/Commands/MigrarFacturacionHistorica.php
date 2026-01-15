<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RegistroCobro;
use App\Models\Cita;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class MigrarFacturacionHistorica extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'facturacion:migrar-historica 
                            {--dry-run : Ejecutar en modo prueba sin guardar cambios}
                            {--limit= : Limitar número de cobros a procesar}
                            {--desde= : Fecha desde (Y-m-d)}
                            {--hasta= : Fecha hasta (Y-m-d)}
                            {--tenant= : ID del tenant específico (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra datos históricos de cobros a la tabla registro_cobro_servicio para todos los tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');
        $desde = $this->option('desde');
        $hasta = $this->option('hasta');
        $tenantId = $this->option('tenant');

        if ($dryRun) {
            $this->warn('🔍 MODO PRUEBA: No se guardarán cambios en la base de datos');
        }

        // Obtener tenants a procesar
        $tenants = $tenantId 
            ? Tenant::where('id', $tenantId)->get() 
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('❌ No se encontraron tenants para procesar');
            return 1;
        }

        $this->info('🏢 Procesando ' . $tenants->count() . ' tenant(s)...');
        $this->newLine();

        $statsGlobal = [
            'tenants_procesados' => 0,
            'cobros_totales' => 0,
            'servicios_creados' => 0,
            'errores' => 0,
        ];

        foreach ($tenants as $tenant) {
            $this->info("\n📊 Tenant: {$tenant->nombre} (ID: {$tenant->id})");
            $this->line('   Base de datos: ' . $tenant->tenancy_db_name);
            
            // Ejecutar dentro del contexto del tenant
            $tenant->run(function () use ($dryRun, $limit, $desde, $hasta, &$statsGlobal) {
                $stats = $this->procesarTenant($dryRun, $limit, $desde, $hasta);
                
                $statsGlobal['tenants_procesados']++;
                $statsGlobal['cobros_totales'] += $stats['total'];
                $statsGlobal['servicios_creados'] += $stats['servicios_creados'];
                $statsGlobal['errores'] += $stats['errores'];
            });
        }

        // Mostrar estadísticas globales
        $this->newLine(2);
        $this->info('═══════════════════════════════════════════════');
        $this->info('📈 ESTADÍSTICAS GLOBALES');
        $this->info('═══════════════════════════════════════════════');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Tenants procesados', $statsGlobal['tenants_procesados']],
                ['Cobros totales', $statsGlobal['cobros_totales']],
                ['Servicios creados', $statsGlobal['servicios_creados']],
                ['Errores', $statsGlobal['errores']],
            ]
        );

        if ($dryRun) {
            $this->warn('\n🔄 Modo prueba: No se guardaron cambios');
            $this->info('💡 Ejecuta sin --dry-run para aplicar los cambios');
        } else {
            $this->info('\n✅ Migración completada exitosamente');
        }

        return 0;
    }

    /**
     * Procesar un tenant específico
     */
    private function procesarTenant($dryRun, $limit, $desde, $hasta)
    {
        $stats = [
            'total' => 0,
            'con_servicios' => 0,
            'sin_servicios' => 0,
            'servicios_creados' => 0,
            'errores' => 0,
        ];

        // Obtener cobros que NO tienen servicios en registro_cobro_servicio
        // Y que NO sean pagos con bono
        $query = RegistroCobro::whereDoesntHave('servicios')
            ->where('metodo_pago', '!=', 'bono')
            ->where(function($q) {
                // Excluir también cobros con total_final=0 que no vendan bonos (pagados con bono)
                $q->where('total_final', '>', 0)
                  ->orWhere('total_bonos_vendidos', '>', 0);
            });

        if ($desde) {
            $query->where('created_at', '>=', $desde);
        }

        if ($hasta) {
            $query->where('created_at', '<=', $hasta);
        }

        if ($limit) {
            $query->limit((int)$limit);
        }

        $cobros = $query->with([
            'cita.servicios',
            'cita.empleado',
            'citasAgrupadas.servicios',
            'citasAgrupadas.empleado'
        ])->get();

        $total = $cobros->count();
        $this->info("📊 Cobros sin servicios vinculados: {$total}");

        if ($total === 0) {
            $this->line('   ✅ No hay cobros pendientes');
            $stats['total'] = 0;
            return $stats;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::beginTransaction();

        try {
            foreach ($cobros as $cobro) {
                $stats['total']++;
                
                try {
                    $resultado = $this->procesarCobro($cobro, $dryRun);
                    
                    if ($resultado['servicios_count'] > 0) {
                        $stats['con_servicios']++;
                        $stats['servicios_creados'] += $resultado['servicios_count'];
                    } else {
                        $stats['sin_servicios']++;
                    }
                } catch (\Exception $e) {
                    $stats['errores']++;
                    $this->newLine();
                    $this->error("   ❌ Error en cobro #{$cobro->id}: " . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            // Mostrar estadísticas del tenant
            $this->line("   ✅ Procesados: {$stats['total']} | Servicios: {$stats['servicios_creados']} | Errores: {$stats['errores']}");

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('   ❌ ERROR CRÍTICO: ' . $e->getMessage());
            $stats['errores']++;
        }

        return $stats;
    }

    /**
     * Procesar un cobro individual
     */
    private function procesarCobro(RegistroCobro $cobro, bool $dryRun): array
    {
        $serviciosCreados = 0;
        $totalFinalServicios = $cobro->total_final;

        // CASO 1: Cobro de cita individual
        if ($cobro->id_cita && $cobro->cita) {
            $cita = $cobro->cita;
            
            if ($cita->servicios && $cita->servicios->count() > 0) {
                // Calcular costo total de servicios ANTES de descuentos
                $costoTotalServicios = $cita->servicios->sum(function($s) {
                    return $s->pivot->precio ?? $s->precio;
                });
                
                if ($costoTotalServicios > 0) {
                    // Calcular el total de productos para restar del total_final
                    $totalProductos = DB::table('registro_cobro_productos')
                        ->where('id_registro_cobro', $cobro->id)
                        ->sum('subtotal');
                    
                    // Calcular proporción de servicios del coste total
                    $proporcionServicios = $cobro->coste > 0 ? $costoTotalServicios / $cobro->coste : 1;
                    
                    // Aplicar proporción al total_final MENOS productos (que ya tiene descuentos aplicados)
                    $totalServiciosConDescuento = ($totalFinalServicios - $totalProductos) * $proporcionServicios;
                    
                    foreach ($cita->servicios as $servicio) {
                        $precioOriginal = $servicio->pivot->precio ?? $servicio->precio;
                        $proporcion = $precioOriginal / $costoTotalServicios;
                        $precioConDescuento = $totalServiciosConDescuento * $proporcion;
                        
                        if (!$dryRun) {
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
                    }
                }
            }
        }
        // CASO 2: Cobro de múltiples citas agrupadas
        elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
            $citasAgrupadas = $cobro->citasAgrupadas;
            
            // Calcular costo total de todos los servicios de todas las citas ANTES de descuentos
            $costoTotalTodosServicios = 0;
            foreach ($citasAgrupadas as $citaGrupo) {
                if ($citaGrupo->servicios) {
                    $costoTotalTodosServicios += $citaGrupo->servicios->sum(function($s) {
                        return $s->pivot->precio ?? $s->precio;
                    });
                }
            }
            
            if ($costoTotalTodosServicios > 0) {
                // Calcular el total de productos para restar del total_final
                $totalProductos = DB::table('registro_cobro_productos')
                    ->where('id_registro_cobro', $cobro->id)
                    ->sum('subtotal');
                
                // Calcular proporción de servicios del coste total
                $proporcionServicios = $cobro->coste > 0 ? $costoTotalTodosServicios / $cobro->coste : 1;
                
                // Aplicar proporción al total_final MENOS productos (que ya tiene descuentos aplicados)
                $totalServiciosConDescuento = ($totalFinalServicios - $totalProductos) * $proporcionServicios;
                
                foreach ($citasAgrupadas as $citaGrupo) {
                    if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                        foreach ($citaGrupo->servicios as $servicio) {
                            $precioOriginal = $servicio->pivot->precio ?? $servicio->precio;
                            $proporcion = $precioOriginal / $costoTotalTodosServicios;
                            $precioConDescuento = $totalServiciosConDescuento * $proporcion;
                            
                            if (!$dryRun) {
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
                        }
                    }
                }
            }
        }

        return [
            'servicios_count' => $serviciosCreados,
        ];
    }
}
