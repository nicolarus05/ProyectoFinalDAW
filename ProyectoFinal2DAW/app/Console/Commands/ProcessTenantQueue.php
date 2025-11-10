<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Comando para procesar jobs de queues específicas de tenants
 */
class ProcessTenantQueue extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenants:queue-work
                            {--tenant=* : IDs de los tenants a procesar (vacío = todos)}
                            {--queue=default : Nombre de la queue a procesar}
                            {--tries=3 : Número de intentos por job}
                            {--timeout=60 : Timeout en segundos}
                            {--sleep=3 : Segundos de espera cuando no hay jobs}
                            {--daemon : Ejecutar en modo daemon}';

    /**
     * The console command description.
     */
    protected $description = 'Procesa los jobs de las queues de uno o más tenants';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantIds = $this->option('tenant');
        $queue = $this->option('queue');
        $tries = $this->option('tries');
        $timeout = $this->option('timeout');
        $sleep = $this->option('sleep');
        $daemon = $this->option('daemon');

        // Si no se especifican tenants, procesar todos
        if (empty($tenantIds)) {
            $tenants = Tenant::all();
            $tenantIds = $tenants->pluck('id')->toArray();
            
            $this->info('No se especificaron tenants. Procesando todos (' . count($tenantIds) . ')');
        } else {
            $this->info('Procesando tenants: ' . implode(', ', $tenantIds));
        }

        foreach ($tenantIds as $tenantId) {
            // Verificar que el tenant existe
            $tenant = Tenant::find($tenantId);
            
            if (!$tenant) {
                $this->error("⚠️  Tenant {$tenantId} no encontrado");
                continue;
            }

            $queueName = "tenant_{$tenantId}";
            
            $this->info("🔄 Procesando queue: {$queueName} (Tenant: {$tenant->nombre})");

            // Opciones para el comando queue:work
            $workOptions = [
                '--queue' => $queueName,
                '--tries' => $tries,
                '--timeout' => $timeout,
                '--sleep' => $sleep,
            ];

            // Si es daemon, ejecutar en segundo plano
            if ($daemon) {
                $workOptions['--daemon'] = true;
            } else {
                // Si no es daemon, procesar solo 1 vez
                $workOptions['--once'] = true;
            }

            try {
                // Ejecutar queue:work para este tenant
                $exitCode = Artisan::call('queue:work', $workOptions);
                
                if ($exitCode === 0) {
                    $this->info("✅ Queue {$queueName} procesada exitosamente");
                } else {
                    $this->warn("⚠️  Queue {$queueName} terminó con código {$exitCode}");
                }
            } catch (\Exception $e) {
                $this->error("❌ Error procesando queue {$queueName}: " . $e->getMessage());
            }
        }

        $this->info('✅ Procesamiento de queues completado');
        
        return Command::SUCCESS;
    }
}
