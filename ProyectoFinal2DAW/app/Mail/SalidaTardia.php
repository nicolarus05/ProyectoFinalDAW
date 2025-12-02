<?php

namespace App\Mail;

use App\Models\RegistroEntradaSalida;
use App\Traits\TenantAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SalidaTardia extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, TenantAware;

    public $registroId;
    public $registro;

    /**
     * Create a new message instance.
     */
    public function __construct($registroId)
    {
        $this->registroId = $registroId;
        
        // Inicializar el trait TenantAware
        if (method_exists($this, 'initializeTenantFromTrait')) {
            $this->initializeTenantFromTrait();
        } else {
            if (tenancy()->initialized) {
                $this->tenantId = tenant('id');
            }
        }
    }
    
    /**
     * Construir el mensaje cuando se procesa
     */
    public function build()
    {
        // Cargar el registro con sus relaciones cuando se procesa el job
        $this->registro = RegistroEntradaSalida::with(['empleado.user'])
            ->findOrFail($this->registroId);
            
        return $this;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Alerta: Empleado salió fuera de horario',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.salida-tardia',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
