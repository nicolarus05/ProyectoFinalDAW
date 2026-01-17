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
use App\Services\FacturacionService;

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
    /**
     * Calcular facturación del empleado en un rango de fechas
     * 
     * Usa FacturacionService para aplicar distribución proporcional de descuentos
     * igual que FacturacionController, garantizando consistencia entre 
     * facturación mensual general y facturación individual por empleado.
     * 
     * @param string|Carbon $fechaInicio
     * @param string|Carbon $fechaFin
     * @return array ['servicios' => float, 'productos' => float, 'bonos' => float, 'total' => float]
     */
    public function facturacionPorFechas($fechaInicio, $fechaFin)
    {
        // Convertir fechas si son strings
        if (is_string($fechaInicio)) {
            $fechaInicio = \Carbon\Carbon::parse($fechaInicio);
        }
        if (is_string($fechaFin)) {
            $fechaFin = \Carbon\Carbon::parse($fechaFin);
        }
        
        // Usar el servicio centralizado de facturación
        $service = new FacturacionService();
        return $service->facturacionPorFechasEmpleado($this, $fechaInicio, $fechaFin);
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
        
        // Contar clientes únicos (una cita puede tener múltiples servicios/bloques pero es 1 cliente)
        // Agrupamos por cliente y fecha para contar cada visita del cliente como 1 cita
        return DB::table('citas')
            ->where('id_empleado', $this->id)
            ->whereBetween('fecha_hora', [$fechaInicio, $fechaFin])
            ->select('id_cliente', DB::raw('DATE(fecha_hora) as fecha'))
            ->groupBy('id_cliente', 'fecha')
            ->get()
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
