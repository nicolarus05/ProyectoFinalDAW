<?php

namespace App\Services;

use App\Mail\CitaConfirmada;
use App\Mail\CitaRecordatorio;
use App\Mail\CitaCancelada;
use App\Mail\RecordatorioCitasAgrupado;
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
            
            // Pasar solo el ID de la cita para evitar problemas de serialización
            Mail::to($email)->send(new CitaConfirmada($cita->id));
            
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
     * Enviar email de recordatorio agrupado (todas las citas del día de un cliente)
     */
    public function enviarRecordatorioAgrupado(string $email, string $nombreCliente, $citas)
    {
        try {
            if (empty($email)) {
                Log::warning("No se pudo enviar recordatorio agrupado - Email vacío", [
                    'cliente' => $nombreCliente,
                    'num_citas' => $citas->count()
                ]);
                return false;
            }

            Mail::to($email)->send(new RecordatorioCitasAgrupado($citas));

            Log::info("Recordatorio agrupado enviado exitosamente", [
                'cliente_email' => $email,
                'num_citas' => $citas->count(),
                'citas_ids' => $citas->pluck('id')->toArray()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Error al enviar recordatorio agrupado", [
                'cliente_email' => $email,
                'num_citas' => $citas->count(),
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

    /**
     * Obtener citas para recordatorio agrupadas por cliente
     * Retorna una colección agrupada: [cliente_id => Collection<Cita>]
     */
    public static function obtenerCitasAgrupadasPorCliente()
    {
        $citas = self::obtenerCitasParaRecordatorio();

        return $citas->groupBy('id_cliente');
    }
}
