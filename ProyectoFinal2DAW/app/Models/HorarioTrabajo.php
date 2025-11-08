<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class HorarioTrabajo extends Model{
    use HasFactory;

    protected $table = 'horario_trabajo';

    // Constantes para horarios fijos
    const HORA_INICIO_NORMAL = '08:00';
    const HORA_FIN_NORMAL = '20:00';
    const HORA_FIN_VERANO = '15:00'; // Julio-Agosto
    
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
     * Bloques de 30 minutos
     */
    public static function generarBloquesHorarios($horaInicio, $horaFin){
        $bloques = [];
        $hora = Carbon::parse($horaInicio);
        $horaLimite = Carbon::parse($horaFin);
        
        while ($hora <= $horaLimite) {
            $bloques[] = $hora->format('H:i:s');
            $hora->addMinutes(30); // Cambiado de addHour() a addMinutes(30)
        }
        
        return $bloques;
    }

    /**
     * Determina el tipo de horario según el mes
     */
    public static function tipoHorarioPorMes($mes){
        return in_array($mes, self::MESES_VERANO) ? 'verano' : 'normal';
    }

    /**
     * Obtiene la hora de fin según el tipo de horario
     */
    public static function horaFinPorTipo($tipoHorario){
        return $tipoHorario === 'verano' ? self::HORA_FIN_VERANO : self::HORA_FIN_NORMAL;
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
