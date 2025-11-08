<?php

namespace App\Services;

use App\Mail\CitaConfirmada;
use App\Mail\CitaRecordatorio;
use App\Mail\CitaCancelada;
use App\Models\Cita;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificacionEmailService
{
    /**
     * Enviar email de confirmación de cita
     */
    public function enviarConfirmacionCita(Cita $cita)
    {
        try {
            // Verificar que el cliente tenga email
            if (!$cita->cliente || !$cita->cliente->user || !$cita->cliente->user->email) {
                Log::warning("No se pudo enviar email de confirmación - Cliente sin email", [
                    'cita_id' => $cita->id
                ]);
                return false;
            }

            $email = $cita->cliente->user->email;
            
            Mail::to($email)->send(new CitaConfirmada($cita));
            
            Log::info("Email de confirmación enviado exitosamente", [
                'cita_id' => $cita->id,
                'cliente_email' => $email,
                'fecha_cita' => $cita->fecha_hora
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error al enviar email de confirmación", [
                'cita_id' => $cita->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Enviar email de recordatorio de cita (24h antes)
     */
    public function enviarRecordatorioCita(Cita $cita)
    {
        try {
            // Verificar que el cliente tenga email
            if (!$cita->cliente || !$cita->cliente->user || !$cita->cliente->user->email) {
                Log::warning("No se pudo enviar email de recordatorio - Cliente sin email", [
                    'cita_id' => $cita->id
                ]);
                return false;
            }

            $email = $cita->cliente->user->email;
            
            Mail::to($email)->send(new CitaRecordatorio($cita));
            
            Log::info("Email de recordatorio enviado exitosamente", [
                'cita_id' => $cita->id,
                'cliente_email' => $email,
                'fecha_cita' => $cita->fecha_hora
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error al enviar email de recordatorio", [
                'cita_id' => $cita->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Enviar email de cancelación de cita
     */
    public function enviarCancelacionCita(Cita $cita, $motivo = null)
    {
        try {
            // Verificar que el cliente tenga email
            if (!$cita->cliente || !$cita->cliente->user || !$cita->cliente->user->email) {
                Log::warning("No se pudo enviar email de cancelación - Cliente sin email", [
                    'cita_id' => $cita->id
                ]);
                return false;
            }

            $email = $cita->cliente->user->email;
            
            Mail::to($email)->send(new CitaCancelada($cita, $motivo));
            
            Log::info("Email de cancelación enviado exitosamente", [
                'cita_id' => $cita->id,
                'cliente_email' => $email,
                'motivo' => $motivo
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error al enviar email de cancelación", [
                'cita_id' => $cita->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtener todas las citas que necesitan recordatorio (24h antes)
     */
    public static function obtenerCitasParaRecordatorio()
    {
        $manana = now()->addDay()->startOfDay();
        $pasadoManana = now()->addDay()->endOfDay();
        
        return Cita::whereBetween('fecha_hora', [$manana, $pasadoManana])
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->with(['cliente.user', 'servicios', 'empleado.user'])
            ->get();
    }
}
