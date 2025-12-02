<?php

namespace App\Mail;

use App\Models\Cita;
use App\Traits\TenantAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CitaConfirmada extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, TenantAware;

    public $citaId;
    public $cita;

    /**
     * Create a new message instance.
     */
    public function __construct($citaId)
    {
        $this->citaId = $citaId;
        
        // Inicializar el trait TenantAware
        if (method_exists($this, 'initializeTenantFromTrait')) {
            $this->initializeTenantFromTrait();
        } else {
            // Capturar el tenant actual
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
        // Cargar la cita con sus relaciones cuando se procesa el job
        $this->cita = Cita::with(['cliente.user', 'servicios', 'empleado.user'])
            ->findOrFail($this->citaId);
            
        return $this;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Confirmación de Cita - Salón de Belleza',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.cita-confirmada',
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
