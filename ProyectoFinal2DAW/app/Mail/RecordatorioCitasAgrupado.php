<?php

namespace App\Mail;

use App\Traits\TenantAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class RecordatorioCitasAgrupado extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, TenantAware;

    public Collection $citas;
    public string $nombreCliente;

    /**
     * Create a new message instance.
     * Recibe todas las citas de un mismo cliente para el mismo día.
     */
    public function __construct(Collection $citas)
    {
        $this->citas = $citas;
        $this->nombreCliente = $citas->first()->cliente->user->nombre ?? 'Cliente';

        if (tenancy()->initialized) {
            $this->tenantId = tenant('id');
        }
    }

    public function envelope(): Envelope
    {
        $totalCitas = $this->citas->count();
        $hora = \Carbon\Carbon::parse($this->citas->first()->fecha_hora)->format('H:i');

        $subject = $totalCitas > 1
            ? "Recordatorio: Tienes {$totalCitas} citas manana"
            : "Recordatorio de Cita - Manana a las {$hora}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recordatorio-agrupado',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
