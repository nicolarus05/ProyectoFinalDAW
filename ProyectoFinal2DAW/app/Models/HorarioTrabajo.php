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
    // Lunes a Viernes: 8:30 - 20:00
    // Sábado: 8:30 - 14:00
    
    // VERANO (Julio y Agosto):
    // Lunes, Martes, Jueves, Viernes, Sábado: 8:30 - 14:00
    // Miércoles: 8:30 - 19:00
    
    const HORA_INICIO_INVIERNO_LV = '08:30';  // Lunes a Viernes invierno
    const HORA_FIN_INVIERNO_LV = '20:00';     // Lunes a Viernes invierno
    const HORA_INICIO_INVIERNO_SAB = '08:30'; // Sábado invierno
    const HORA_FIN_INVIERNO_SAB = '14:00';    // Sábado invierno
    
    const HORA_INICIO_VERANO = '08:30';       // Todos los días verano
    const HORA_FIN_VERANO_NORMAL = '14:00';   // Lun, Mar, Jue, Vie, Sáb verano
    const HORA_FIN_VERANO_MIERCOLES = '19:00'; // Miércoles verano
    
    const DIAS_LABORABLES = [1, 2, 3, 4, 5, 6]; // Lunes (1) a Sábado (6)
    const MESES_VERANO = [7, 8]; // Julio y Agosto
    const DURACION_BLOQUE_MINUTOS = 15;

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
            $hora->addMinutes(self::DURACION_BLOQUE_MINUTOS);
        }
        
        return $bloques;
    }

    /**
     * Comprueba si un bloque de 15 minutos se solapa con alguna cita activa.
     *
     * Las citas no se eliminan al regenerar horarios; sus bloques deben
     * conservarse ocupados para evitar dobles reservas.
     */
    public static function bloqueOcupadoPorCitas(iterable $citas, string $fecha, string $hora): bool
    {
        $inicioBloque = Carbon::parse($fecha . ' ' . $hora);
        $finBloque = $inicioBloque->copy()->addMinutes(self::DURACION_BLOQUE_MINUTOS);

        foreach ($citas as $cita) {
            $inicioCita = Carbon::parse($cita->fecha_hora);
            $finCita = $inicioCita->copy()->addMinutes((int) $cita->duracion_minutos);

            if ($inicioCita->lessThan($finBloque) && $finCita->greaterThan($inicioBloque)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Valida que un rango de trabajo sea compatible con los bloques del calendario.
     *
     * Devuelve null cuando el rango es válido y un mensaje cuando no lo es.
     * Un día sin horario se representa con ambos valores vacíos.
     */
    public static function validarRangoHorario(?string $horaInicio, ?string $horaFin): ?string
    {
        $horaInicio = trim((string) $horaInicio);
        $horaFin = trim((string) $horaFin);

        if ($horaInicio === '' && $horaFin === '') {
            return null;
        }

        if (
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $horaInicio)
            || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $horaFin)
        ) {
            return 'La hora de inicio y la hora de fin deben tener el formato HH:MM.';
        }

        [$inicioHora, $inicioMinuto] = array_map('intval', explode(':', $horaInicio));
        [$finHora, $finMinuto] = array_map('intval', explode(':', $horaFin));

        if (
            $inicioMinuto % self::DURACION_BLOQUE_MINUTOS !== 0
            || $finMinuto % self::DURACION_BLOQUE_MINUTOS !== 0
        ) {
            return 'Las horas deben estar alineadas con bloques de 15 minutos (00, 15, 30 o 45).';
        }

        $inicioEnMinutos = ($inicioHora * 60) + $inicioMinuto;
        $finEnMinutos = ($finHora * 60) + $finMinuto;

        if ($finEnMinutos <= $inicioEnMinutos) {
            return 'La hora de fin debe ser posterior a la hora de inicio.';
        }

        return null;
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
