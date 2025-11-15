<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class HorarioTrabajo extends Model{
    use HasFactory;

    protected $table = 'horario_trabajo';

    // Constantes para horarios fijos
    // INVIERNO (Septiembre a Junio):
    // Lunes a Viernes: 9:00 - 20:00
    // Sábado: 8:30 - 14:00
    
    // VERANO (Julio y Agosto):
    // Lunes, Martes, Jueves, Viernes, Sábado: 8:30 - 14:00
    // Miércoles: 8:30 - 19:00
    
    const HORA_INICIO_INVIERNO_LV = '09:00';  // Lunes a Viernes invierno
    const HORA_FIN_INVIERNO_LV = '20:00';     // Lunes a Viernes invierno
    const HORA_INICIO_INVIERNO_SAB = '08:30'; // Sábado invierno
    const HORA_FIN_INVIERNO_SAB = '14:00';    // Sábado invierno
    
    const HORA_INICIO_VERANO = '08:30';       // Todos los días verano
    const HORA_FIN_VERANO_NORMAL = '14:00';   // Lun, Mar, Jue, Vie, Sáb verano
    const HORA_FIN_VERANO_MIERCOLES = '19:00'; // Miércoles verano
    
    const DIAS_LABORABLES = [1, 2, 3, 4, 5, 6]; // Lunes (1) a Sábado (6)
    const MESES_VERANO = [7, 8]; // Julio y Agosto

    // Definición de las columnas de la tabla
    protected $fillable = [
        'id_empleado',
        'fecha',
        'hora',
        'hora_inicio',
        'hora_fin',
        'disponible',
        'tipo_horario',
        'notas',
    ];

    protected $casts = [
        'disponible' => 'boolean',
        'fecha' => 'date',
    ];

    public function empleado(){
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    /**
     * Genera un array de bloques horarios entre dos horas
     * Bloques de 15 minutos
     */
    public static function generarBloquesHorarios($horaInicio, $horaFin){
        $bloques = [];
        $hora = Carbon::parse($horaInicio);
        $horaLimite = Carbon::parse($horaFin);
        
        while ($hora <= $horaLimite) {
            $bloques[] = $hora->format('H:i:s');
            $hora->addMinutes(15);
        }
        
        return $bloques;
    }

    /**
     * Determina el tipo de horario según el mes
     */
    public static function tipoHorarioPorMes($mes){
        return in_array($mes, self::MESES_VERANO) ? 'verano' : 'invierno';
    }

    /**
     * Obtiene el horario (inicio y fin) según la fecha
     * Retorna un array con ['inicio' => 'HH:MM', 'fin' => 'HH:MM']
     */
    public static function obtenerHorarioPorFecha($fecha){
        $carbon = Carbon::parse($fecha);
        $mes = $carbon->month;
        $diaSemana = $carbon->dayOfWeek; // 0=Domingo, 1=Lunes, ..., 6=Sábado
        
        // Verificar si es verano (Julio y Agosto)
        if (in_array($mes, self::MESES_VERANO)) {
            // VERANO
            if ($diaSemana == 3) { // Miércoles
                return [
                    'inicio' => self::HORA_INICIO_VERANO,
                    'fin' => self::HORA_FIN_VERANO_MIERCOLES,
                    'tipo' => 'verano_miercoles'
                ];
            } else if ($diaSemana >= 1 && $diaSemana <= 6) { // Lunes a Sábado (excepto Miércoles)
                return [
                    'inicio' => self::HORA_INICIO_VERANO,
                    'fin' => self::HORA_FIN_VERANO_NORMAL,
                    'tipo' => 'verano'
                ];
            }
        } else {
            // INVIERNO
            if ($diaSemana >= 1 && $diaSemana <= 5) { // Lunes a Viernes
                return [
                    'inicio' => self::HORA_INICIO_INVIERNO_LV,
                    'fin' => self::HORA_FIN_INVIERNO_LV,
                    'tipo' => 'invierno_semana'
                ];
            } else if ($diaSemana == 6) { // Sábado
                return [
                    'inicio' => self::HORA_INICIO_INVIERNO_SAB,
                    'fin' => self::HORA_FIN_INVIERNO_SAB,
                    'tipo' => 'invierno_sabado'
                ];
            }
        }
        
        // Domingo o día no laborable
        return null;
    }

    /**
     * Scope para filtrar por empleado
     */
    public function scopePorEmpleado($query, $empleadoId){
        return $query->where('id_empleado', $empleadoId);
    }

    /**
     * Scope para filtrar por fecha
     */
    public function scopePorFecha($query, $fecha){
        return $query->where('fecha', $fecha);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopePorRangoFechas($query, $fechaInicio, $fechaFin){
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    /**
     * Scope para filtrar solo disponibles
     */
    public function scopeDisponibles($query){
        return $query->where('disponible', true);
    }

    /**
     * Scope para filtrar solo no disponibles
     */
    public function scopeNoDisponibles($query){
        return $query->where('disponible', false);
    }
}
