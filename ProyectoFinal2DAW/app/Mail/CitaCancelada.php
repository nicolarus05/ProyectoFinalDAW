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

class CitaCancelada extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, TenantAware;

    public $cita;
    public $motivo;

    /**
     * Create a new message instance.
     */
    public function __construct(Cita $cita, $motivo = null)
    {
        $this->cita = $cita;
        $this->motivo = $motivo;
        
        // Capturar el tenant actual
        if (tenancy()->initialized) {
            $this->tenantId = tenant('id');
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cancelacion de Cita - Salon de Belleza',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.cita-cancelada',
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
