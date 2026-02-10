<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\NotificacionEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnviarRecordatoriosCitas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'citas:enviar-recordatorios {tenant_id? : ID del tenant específico (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar emails de recordatorio para citas programadas en las próximas 24 horas (multi-tenant)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando envío de recordatorios de citas...');
        
        // Verificar si se especificó un tenant
        $tenantId = $this->argument('tenant_id');
        
        if ($tenantId) {
            // Procesar un solo tenant
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                $this->error("❌ Tenant '{$tenantId}' no encontrado");
                return 1;
            }
            
            $this->procesarTenant($tenant);
        } else {
            // Procesar todos los tenants
            $tenants = Tenant::all();
            
            if ($tenants->isEmpty()) {
                $this->warn('⚠️  No hay tenants registrados');
                return 0;
            }
            
            $this->info("📋 Procesando {$tenants->count()} tenants...\n");
            
            foreach ($tenants as $tenant) {
                $this->procesarTenant($tenant);
            }
        }
        
        $this->info("\n✅ Proceso de recordatorios completado");
        return 0;
    }
    
    /**
     * Procesar recordatorios para un tenant específico
     */
    protected function procesarTenant(Tenant $tenant): void
    {
        $this->info("🏢 Procesando tenant: {$tenant->id}");
        
        try {
            // Inicializar contexto del tenant
            tenancy()->initialize($tenant);
            
            // Obtener citas agrupadas por cliente
            $citasPorCliente = NotificacionEmailService::obtenerCitasAgrupadasPorCliente();
            
            if ($citasPorCliente->isEmpty()) {
                $this->line("   ℹ️  No hay citas programadas para mañana");
                tenancy()->end();
                return;
            }
            
            $totalCitas = $citasPorCliente->flatten()->count();
            $totalClientes = $citasPorCliente->count();
            $this->info("   📧 {$totalCitas} citas para {$totalClientes} clientes");
            
            $notificacionService = new NotificacionEmailService();
            $enviados = 0;
            $errores = 0;
            
            foreach ($citasPorCliente as $clienteId => $citas) {
                $primeraCita = $citas->first();
                $clienteNombre = $primeraCita->cliente->user->nombre ?? 'Sin nombre';
                $clienteEmail = $primeraCita->cliente->user->email ?? '';
                $numCitas = $citas->count();
                
                try {
                    if ($notificacionService->enviarRecordatorioAgrupado($clienteEmail, $clienteNombre, $citas)) {
                        $this->line("   ✓ Enviado a: {$clienteNombre} ({$clienteEmail}) - {$numCitas} cita(s)");
                        $enviados++;
                    } else {
                        $this->line("   ✗ Error al enviar a: {$clienteNombre} ({$clienteEmail})");
                        $errores++;
                    }
                } catch (\Exception $e) {
                    $this->error("   ✗ Excepción al enviar a {$clienteNombre}: {$e->getMessage()}");
                    $errores++;
                    
                    Log::error("Error al enviar recordatorio agrupado", [
                        'tenant_id' => $tenant->id,
                        'cliente_id' => $clienteId,
                        'num_citas' => $numCitas,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $this->info("   📊 Resumen: ✅ {$enviados} emails enviados ({$totalCitas} citas) | ❌ {$errores} errores");
            
            // Finalizar contexto del tenant
            tenancy()->end();
            
        } catch (\Exception $e) {
            $this->error("   ❌ Error procesando tenant {$tenant->id}: {$e->getMessage()}");
            
            Log::error("Error al procesar recordatorios para tenant", [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Asegurar que se finalice el contexto del tenant
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }
        
        $this->newLine();
    }
}
