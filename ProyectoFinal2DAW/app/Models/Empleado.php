<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Cita;
use App\Models\Servicio;
use App\Models\RegistroCobro;
use App\Services\FacturacionService;

class Empleado extends Model
{
    use HasFactory, SoftDeletes, Notifiable, CanResetPassword, HasApiTokens;

    protected $table = 'empleados';

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

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function citas()
    {
        return $this->hasMany(Cita::class, 'id_empleado');
    }

    public function servicios()
    {
        return $this->belongsToMany(
            Servicio::class,
            'empleado_servicio',
            'id_empleado',
            'id_servicio'
        );
    }

    /**
     * Calcular facturación del empleado en un rango de fechas
     */
    public function facturacionPorFechas($fechaInicio, $fechaFin): array
    {
        $fechaInicio = $fechaInicio instanceof Carbon
            ? $fechaInicio->startOfDay()
            : Carbon::parse($fechaInicio)->startOfDay();

        $fechaFin = $fechaFin instanceof Carbon
            ? $fechaFin->endOfDay()
            : Carbon::parse($fechaFin)->endOfDay();

        $facturacion = [
            'servicios' => 0.0,
            'productos' => 0.0,
            'bonos'     => 0.0,
            'total'     => 0.0,
        ];

        $service = new FacturacionService();

        $cobros = RegistroCobro::with(['servicios', 'productos', 'bonosVendidos', 'cita.servicios', 'citasAgrupadas.servicios'])
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->where('metodo_pago', '!=', 'bono')
            ->where('contabilizado', true)
            ->get();

        foreach ($cobros as $cobro) {
            $desglose = $service->desglosarCobroPorEmpleado($cobro);

            // Sumar servicios, productos y bonos del empleado
            if (isset($desglose[$this->id])) {
                $facturacion['servicios'] += $desglose[$this->id]['servicios'] ?? 0;
                $facturacion['productos'] += $desglose[$this->id]['productos'] ?? 0;
                $facturacion['bonos'] += $desglose[$this->id]['bonos'] ?? 0;
            }
        }
        
        // Calcular total
        $facturacion['total'] = $facturacion['servicios'] + $facturacion['productos'] + $facturacion['bonos'];

        // Redondeo final
        foreach ($facturacion as &$valor) {
            $valor = round($valor, 2);
        }

        return $facturacion;
    }

    public function facturacionMesActual()
    {
        return $this->facturacionPorFechas(
            now()->startOfMonth(),
            now()->endOfMonth()
        );
    }

    public function facturacionMesAnterior()
    {
        return $this->facturacionPorFechas(
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth()
        );
    }

    public function citasAtendidasMesActual()
    {
        return DB::table('citas')
            ->where('id_empleado', $this->id)
            ->whereBetween('fecha_hora', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ])
            ->select('id_cliente', DB::raw('DATE(fecha_hora) as fecha'))
            ->groupBy('id_cliente', 'fecha')
            ->get()
            ->count();
    }

    public function obtenerHorario($fecha)
    {
        $carbon = Carbon::parse($fecha);
        $diaSemana = $carbon->dayOfWeek;
        $mes = $carbon->month;
        $esVerano = in_array($mes, [7, 8]);

        $horarios = $esVerano
            ? $this->horario_verano
            : $this->horario_invierno;

        if ($horarios && is_array($horarios)) {
            $horarioDia = $horarios[$diaSemana] ?? null;

            if (!$horarioDia) {
                $dias = ['domingo','lunes','martes','miercoles','jueves','viernes','sabado'];
                $horarioDia = $horarios[$dias[$diaSemana]] ?? null;
            }

            if (
                is_array($horarioDia) &&
                !empty($horarioDia['inicio']) &&
                !empty($horarioDia['fin'])
            ) {
                return [
                    'inicio' => $horarioDia['inicio'],
                    'fin' => $horarioDia['fin'],
                    'tipo' => $esVerano ? 'verano_personalizado' : 'invierno_personalizado'
                ];
            }
        }

        return null;
    }
}
