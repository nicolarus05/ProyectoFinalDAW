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
        // FACTURACIÓN POR SERVICIOS
        // Usar registro_cobro_servicio que tiene el precio real cobrado (con descuentos) Y el empleado específico
        // Esto permite contabilizar correctamente cuando múltiples empleados hacen servicios en una misma cita
        $facturacionServicios = DB::table('registro_cobro_servicio')
            ->join('registro_cobros', 'registro_cobro_servicio.registro_cobro_id', '=', 'registro_cobros.id')
            ->where('registro_cobro_servicio.empleado_id', $this->id)
            ->where('registro_cobros.metodo_pago', '!=', 'bono') // Excluir cobros pagados con bono
            ->whereBetween('registro_cobros.created_at', [$fechaInicio, $fechaFin])
            ->sum('registro_cobro_servicio.precio');

        // FACTURACIÓN POR PRODUCTOS VENDIDOS
        // Los productos se asocian al cobro, y el cobro tiene id_empleado
        // NO aplicar proporciones, contar directamente el subtotal de productos
        $facturacionProductos = DB::table('registro_cobro_productos')
            ->join('registro_cobros', 'registro_cobro_productos.id_registro_cobro', '=', 'registro_cobros.id')
            ->where('registro_cobros.id_empleado', $this->id)
            ->where('registro_cobros.metodo_pago', '!=', 'bono')
            ->whereBetween('registro_cobros.created_at', [$fechaInicio, $fechaFin])
            ->sum('registro_cobro_productos.subtotal');

        // FACTURACIÓN POR BONOS VENDIDOS
        // IMPORTANTE: Usar precio_pagado (lo que realmente se cobró) en lugar del precio de plantilla
        // para que coincida con la facturación mensual general
        $facturacionBonos = 0;
        try {
            if (DB::getSchemaBuilder()->hasTable('bonos_plantillas')) {
                $facturacionBonos = DB::table('bonos_clientes')
                    ->where('bonos_clientes.id_empleado', $this->id)
                    ->whereBetween('bonos_clientes.fecha_compra', [$fechaInicio, $fechaFin])
                    ->sum('bonos_clientes.precio_pagado');
            } else {
                // Si no existe bonos_plantillas, usar total_bonos_vendidos de registro_cobros
                // 1. Sumar bonos vendidos en cobros de citas individuales de este empleado
                $bonosCitasIndividuales = DB::table('registro_cobros')
                    ->join('citas', 'registro_cobros.id_cita', '=', 'citas.id')
                    ->where('citas.id_empleado', $this->id)
                    ->whereBetween('registro_cobros.created_at', [$fechaInicio, $fechaFin])
                    ->sum('registro_cobros.total_bonos_vendidos');
                
                // 2. Sumar bonos vendidos en cobros directos (sin cita) de este empleado
                $bonosCobrosDirectos = DB::table('registro_cobros')
                    ->whereNull('registro_cobros.id_cita')
                    ->where('registro_cobros.id_empleado', $this->id)
                    ->whereBetween('registro_cobros.created_at', [$fechaInicio, $fechaFin])
                    ->sum('registro_cobros.total_bonos_vendidos');
                
                // 3. Sumar bonos vendidos en cobros de citas agrupadas donde TODAS las citas son de este empleado
                $bonosCitasAgrupadas = DB::table('registro_cobros')
                    ->whereNull('registro_cobros.id_cita')
                    ->whereNull('registro_cobros.id_empleado') // Sin empleado directo, verificar citas agrupadas
                    ->whereBetween('registro_cobros.created_at', [$fechaInicio, $fechaFin])
                    ->where('registro_cobros.total_bonos_vendidos', '>', 0)
                    ->whereExists(function($query) {
                        $query->select(DB::raw(1))
                            ->from('registro_cobro_citas')
                            ->whereColumn('registro_cobro_citas.registro_cobro_id', 'registro_cobros.id');
                    })
                    ->get()
                    ->filter(function($cobro) {
                        // Verificar si TODAS las citas agrupadas son de este empleado
                        $citasIds = DB::table('registro_cobro_citas')
                            ->where('registro_cobro_id', $cobro->id)
                            ->pluck('cita_id');
                        
                        if($citasIds->isEmpty()) {
                            return false;
                        }
                        
                        $citasDeOtrosEmpleados = DB::table('citas')
                            ->whereIn('id', $citasIds)
                            ->where('id_empleado', '!=', $this->id)
                            ->count();
                        
                        return $citasDeOtrosEmpleados === 0;
                    })
                    ->sum('total_bonos_vendidos');
                
                $facturacionBonos = $bonosCitasIndividuales + $bonosCobrosDirectos + $bonosCitasAgrupadas;
            }
        } catch (\Exception $e) {
            // Si hay error al consultar bonos, simplemente usar 0
            $facturacionBonos = 0;
        }

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
        $fechaInicio = now()->startOfMonth();
        $fechaFin = now()->endOfMonth();
        
        // Contar citas individuales (registro_cobros.id_cita)
        $citasIndividuales = DB::table('registro_cobros')
            ->join('citas', 'registro_cobros.id_cita', '=', 'citas.id')
            ->where('citas.id_empleado', $this->id)
            ->whereBetween('registro_cobros.created_at', [$fechaInicio, $fechaFin])
            ->count();
        
        // Contar citas agrupadas (registro_cobro_citas)
        $citasAgrupadas = DB::table('registro_cobro_citas')
            ->join('registro_cobros', 'registro_cobro_citas.registro_cobro_id', '=', 'registro_cobros.id')
            ->join('citas', 'registro_cobro_citas.cita_id', '=', 'citas.id')
            ->where('citas.id_empleado', $this->id)
            ->whereBetween('registro_cobros.created_at', [$fechaInicio, $fechaFin])
            ->count();
        
        // Sumar ambos totales
        return $citasIndividuales + $citasAgrupadas;
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
