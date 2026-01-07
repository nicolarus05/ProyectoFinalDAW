<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Models\Cita;
use App\Models\Servicio;

class Empleado extends Model{
    use HasFactory, SoftDeletes, Notifiable, CanResetPassword, HasApiTokens;

    protected $table = 'empleados';

    // Definición de las columnas de la tabla
    protected $fillable = [
        'id_user',
        'categoria',
        'horario_invierno',
        'horario_verano',
    ];

    protected $casts = [
        'horario_invierno' => 'array',
        'horario_verano' => 'array',
    ];

    public function user(){
        return $this->belongsTo(User::class, 'id_user');
    }

    public function citas(){
        return $this->hasMany(Cita::class, 'id_empleado');
    }

    public function servicios(){
        return $this->belongsToMany(
            Servicio::class,
            'empleado_servicio',
            'id_empleado',
            'id_servicio',
        );
    }

    /**
     * Calcular facturación del empleado en un rango de fechas
     */
    public function facturacionPorFechas($fechaInicio, $fechaFin)
    {
        // Facturación por servicios (basado en cobros registrados, no en estado de cita)
        $facturacionServicios = DB::table('registro_cobros')
            ->join('citas', 'registro_cobros.id_cita', '=', 'citas.id')
            ->join('cita_servicio', 'citas.id', '=', 'cita_servicio.id_cita')
            ->join('servicios', 'cita_servicio.id_servicio', '=', 'servicios.id')
            ->where('citas.id_empleado', $this->id)
            ->whereBetween('registro_cobros.created_at', [$fechaInicio, $fechaFin])
            ->sum('servicios.precio');

        // Facturación por productos vendidos
        // Buscar por id_empleado del cobro O por el empleado de la cita
        $facturacionProductos = DB::table('registro_cobro_productos')
            ->join('registro_cobros', 'registro_cobro_productos.id_registro_cobro', '=', 'registro_cobros.id')
            ->leftJoin('citas', 'registro_cobros.id_cita', '=', 'citas.id')
            ->where(function($query) {
                $query->where('registro_cobros.id_empleado', $this->id)
                      ->orWhere('citas.id_empleado', $this->id);
            })
            ->whereBetween('registro_cobros.created_at', [$fechaInicio, $fechaFin])
            ->sum(DB::raw('registro_cobro_productos.cantidad * registro_cobro_productos.precio_unitario'));

        // Facturación por bonos vendidos
        $facturacionBonos = DB::table('bonos_clientes')
            ->where('id_empleado', $this->id)
            ->whereBetween('fecha_compra', [$fechaInicio, $fechaFin])
            ->sum('precio_pagado');

        return [
            'servicios' => $facturacionServicios ?? 0,
            'productos' => $facturacionProductos ?? 0,
            'bonos' => $facturacionBonos ?? 0,
            'total' => ($facturacionServicios ?? 0) + ($facturacionProductos ?? 0) + ($facturacionBonos ?? 0)
        ];
    }

    /**
     * Calcular facturación del mes actual
     */
    public function facturacionMesActual()
    {
        $fechaInicio = now()->startOfMonth();
        $fechaFin = now()->endOfMonth();
        return $this->facturacionPorFechas($fechaInicio, $fechaFin);
    }

    /**
     * Calcular facturación del mes anterior
     */
    public function facturacionMesAnterior()
    {
        $fechaInicio = now()->subMonth()->startOfMonth();
        $fechaFin = now()->subMonth()->endOfMonth();
        return $this->facturacionPorFechas($fechaInicio, $fechaFin);
    }

    /**
     * Calcular número de citas atendidas en el mes actual
     */
    public function citasAtendidasMesActual()
    {
        return DB::table('registro_cobros')
            ->join('citas', 'registro_cobros.id_cita', '=', 'citas.id')
            ->where('citas.id_empleado', $this->id)
            ->whereBetween('registro_cobros.created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    /**
     * Obtener horario personalizado del empleado para una fecha específica
     * Si no tiene configuración personalizada para ese día, devuelve null
     */
    public function obtenerHorario($fecha)
    {
        $carbon = \Carbon\Carbon::parse($fecha);
        $diaSemana = $carbon->dayOfWeek; // 0=Domingo, 1=Lunes, ..., 6=Sábado
        $mes = $carbon->month;
        $esVerano = in_array($mes, [7, 8]); // Julio y Agosto
        
        // Seleccionar horario según temporada
        $horarios = $esVerano ? $this->horario_verano : $this->horario_invierno;
        
        // Si el empleado tiene configuración personalizada para este día
        if ($horarios && is_array($horarios)) {
            // Buscar primero por número de día (formato del formulario)
            $horarioDia = $horarios[$diaSemana] ?? null;
            
            // Si no se encuentra por número, buscar por nombre (compatibilidad)
            if (!$horarioDia) {
                $dias = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
                $nombreDia = $dias[$diaSemana];
                $horarioDia = $horarios[$nombreDia] ?? null;
            }
            
            if ($horarioDia && is_array($horarioDia) && isset($horarioDia['inicio']) && isset($horarioDia['fin'])) {
                // Verificar que las horas no estén vacías
                if (!empty($horarioDia['inicio']) && !empty($horarioDia['fin'])) {
                    return [
                        'inicio' => $horarioDia['inicio'],
                        'fin' => $horarioDia['fin'],
                        'tipo' => $esVerano ? 'verano_personalizado' : 'invierno_personalizado'
                    ];
                }
            }
        }
        
        // Si no hay horario personalizado para este día, devolver null
        // Esto significa que el empleado NO trabaja ese día
        return null;
    }
}
