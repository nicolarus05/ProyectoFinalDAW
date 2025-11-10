<?php

namespace App\Jobs;

use App\Mail\CitaConfirmada;
use App\Models\Cita;
use App\Traits\TenantAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Job para enviar email de confirmación de cita
 * 
 * Este job mantiene el contexto del tenant usando el trait TenantAware
 */
class EnviarEmailCita implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAware;

    /**
     * El número de veces que se puede intentar ejecutar el job
     */
    public int $tries = 3;

    /**
     * El número de segundos que se puede ejecutar el job antes de timeout
     */
    public int $timeout = 60;

    /**
     * ID de la cita
     */
    protected int $citaId;

    /**
     * Tipo de email a enviar
     */
    protected string $tipoEmail;

    /**
     * Motivo opcional (para cancelaciones)
     */
    protected ?string $motivo;

    /**
     * Create a new job instance.
     */
    public function __construct(int $citaId, string $tipoEmail = 'confirmacion', ?string $motivo = null)
    {
        $this->citaId = $citaId;
        $this->tipoEmail = $tipoEmail;
        $this->motivo = $motivo;
        
        // Capturar el tenant actual
        if (tenancy()->initialized) {
            $this->tenantId = tenant('id');
        }
        
        Log::info('EnviarEmailCita: Job creado', [
            'cita_id' => $citaId,
            'tipo_email' => $tipoEmail,
            'tenant_id' => $this->tenantId
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Inicializar el contexto del tenant
        $this->initializeTenantContext();
        
        Log::info('EnviarEmailCita: Procesando job', [
            'cita_id' => $this->citaId,
            'tipo_email' => $this->tipoEmail,
            'tenant_id' => $this->tenantId,
            'tenant_actual' => tenancy()->initialized ? tenant('id') : 'ninguno'
        ]);
        
        try {
            // Buscar la cita en el contexto del tenant
            $cita = Cita::with(['cliente', 'servicio', 'empleado'])
                ->findOrFail($this->citaId);
            
            // Verificar que la cita tiene un cliente con email
            if (!$cita->cliente || !$cita->cliente->email) {
                Log::warning('EnviarEmailCita: Cliente sin email', [
                    'cita_id' => $this->citaId,
                    'tenant_id' => $this->tenantId
                ]);
                return;
            }
            
            // Enviar el email correspondiente
            switch ($this->tipoEmail) {
                case 'confirmacion':
                    Mail::to($cita->cliente->email)
                        ->send(new \App\Mail\CitaConfirmada($cita));
                    break;
                    
                case 'cancelacion':
                    Mail::to($cita->cliente->email)
                        ->send(new \App\Mail\CitaCancelada($cita, $this->motivo));
                    break;
                    
                case 'recordatorio':
                    Mail::to($cita->cliente->email)
                        ->send(new \App\Mail\CitaRecordatorio($cita));
                    break;
                    
                default:
                    Log::error('EnviarEmailCita: Tipo de email desconocido', [
                        'tipo_email' => $this->tipoEmail
                    ]);
                    return;
            }
            
            Log::info('EnviarEmailCita: Email enviado exitosamente', [
                'cita_id' => $this->citaId,
                'tipo_email' => $this->tipoEmail,
                'destinatario' => $cita->cliente->email,
                'tenant_id' => $this->tenantId
            ]);
            
        } catch (\Exception $e) {
            Log::error('EnviarEmailCita: Error al enviar email', [
                'cita_id' => $this->citaId,
                'tipo_email' => $this->tipoEmail,
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-lanzar la excepción para que Laravel reintente el job
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('EnviarEmailCita: Job falló después de todos los intentos', [
            'cita_id' => $this->citaId,
            'tipo_email' => $this->tipoEmail,
            'tenant_id' => $this->tenantId,
            'error' => $exception->getMessage()
        ]);
        
        // Aquí podrías notificar al administrador, etc.
    }
}
