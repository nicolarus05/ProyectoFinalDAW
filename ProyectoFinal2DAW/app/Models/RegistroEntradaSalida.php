<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class RegistroEntradaSalida extends Model{
    use HasFactory;

    protected $table = 'registro_entrada_salida';

    // Definición de las columnas de la tabla
    protected $fillable = [
        'id_empleado',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'salida_fuera_horario',
        'minutos_extra',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function empleado(){
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    /**
     * Verificar si el empleado está actualmente en jornada (tiene entrada pero no salida)
     */
    public function estaEnJornada(){
        return !is_null($this->hora_entrada) && is_null($this->hora_salida);
    }

    /**
     * Calcular las horas trabajadas
     * Retorna un array con horas y minutos
     */
    public function calcularHorasTrabajadas(){
        if (is_null($this->hora_entrada) || is_null($this->hora_salida)) {
            return null;
        }

        // Convertir fecha a formato Y-m-d si es un objeto Carbon
        $fechaStr = $this->fecha instanceof Carbon ? $this->fecha->format('Y-m-d') : $this->fecha;
        
        $entrada = Carbon::parse($fechaStr . ' ' . $this->hora_entrada);
        $salida = Carbon::parse($fechaStr . ' ' . $this->hora_salida);
        
        $diff = $entrada->diff($salida);
        
        return [
            'horas' => $diff->h,
            'minutos' => $diff->i,
            'total_minutos' => ($diff->h * 60) + $diff->i,
            'formatted' => sprintf('%dh %02dmin', $diff->h, $diff->i)
        ];
    }

    /**
     * Calcular horas trabajadas hasta el momento (si aún está en jornada)
     */
    public function calcularHorasActuales(){
        if (is_null($this->hora_entrada)) {
            return null;
        }

        // Convertir fecha a formato Y-m-d si es un objeto Carbon
        $fechaStr = $this->fecha instanceof Carbon ? $this->fecha->format('Y-m-d') : $this->fecha;
        
        $entrada = Carbon::parse($fechaStr . ' ' . $this->hora_entrada);
        $ahora = Carbon::now();
        
        $diff = $entrada->diff($ahora);
        
        return [
            'horas' => $diff->h,
            'minutos' => $diff->i,
            'total_minutos' => ($diff->h * 60) + $diff->i,
            'formatted' => sprintf('%dh %02dmin', $diff->h, $diff->i)
        ];
    }

    /**
     * Obtener el último registro del día de un empleado
     */
    public static function registroDelDia($empleadoId, $fecha = null){
        $fecha = $fecha ?? Carbon::today();
        return self::where('id_empleado', $empleadoId)
            ->whereDate('fecha', $fecha)
            ->orderBy('hora_entrada', 'desc')
            ->first();
    }

    /**
     * Verificar si un empleado ya tiene un registro activo hoy (entrada sin salida)
     */
    public static function tieneRegistroActivoHoy($empleadoId){
        $registro = self::where('id_empleado', $empleadoId)
            ->whereDate('fecha', Carbon::today())
            ->whereNotNull('hora_entrada')
            ->whereNull('hora_salida')
            ->orderBy('hora_entrada', 'desc')
            ->first();
        return $registro ? true : false;
    }
    
    /**
     * Obtener el registro activo actual (entrada sin salida) de un empleado
     */
    public static function registroActivoActual($empleadoId){
        return self::where('id_empleado', $empleadoId)
            ->whereDate('fecha', Carbon::today())
            ->whereNotNull('hora_entrada')
            ->whereNull('hora_salida')
            ->orderBy('hora_entrada', 'desc')
            ->first();
    }
}
