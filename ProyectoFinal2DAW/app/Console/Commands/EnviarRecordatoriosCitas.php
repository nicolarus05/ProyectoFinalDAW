<?php

namespace App\Console\Commands;

use App\Services\NotificacionEmailService;
use Illuminate\Console\Command;

class EnviarRecordatoriosCitas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'citas:enviar-recordatorios';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar emails de recordatorio para citas programadas en las próximas 24 horas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Buscando citas para recordatorio...');
        
        $citasParaRecordatorio = NotificacionEmailService::obtenerCitasParaRecordatorio();
        
        if ($citasParaRecordatorio->isEmpty()) {
            $this->info('✅ No hay citas programadas para mañana.');
            return 0;
        }
        
        $this->info("📧 Se encontraron {$citasParaRecordatorio->count()} citas para enviar recordatorio.");
        
        $notificacionService = new NotificacionEmailService();
        $enviados = 0;
        $errores = 0;
        
        foreach ($citasParaRecordatorio as $cita) {
            $clienteNombre = $cita->cliente->user->nombre ?? 'Sin nombre';
            
            if ($notificacionService->enviarRecordatorioCita($cita)) {
                $this->info("✓ Recordatorio enviado a: {$clienteNombre}");
                $enviados++;
            } else {
                $this->error("✗ Error al enviar recordatorio a: {$clienteNombre}");
                $errores++;
            }
        }
        
        $this->info("\n📊 Resumen:");
        $this->info("   ✅ Enviados: {$enviados}");
        $this->info("   ❌ Errores: {$errores}");
        
        return 0;
    }
}
