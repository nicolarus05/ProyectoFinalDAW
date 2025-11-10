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

    public $cita;

    /**
     * Create a new message instance.
     */
    public function __construct(Cita $cita)
    {
        $this->cita = $cita;
        
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
