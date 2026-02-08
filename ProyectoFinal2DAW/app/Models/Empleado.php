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
            ->where('metodo_pago', '!=', 'deuda') // Deuda = dinero NO cobrado, no facturar
            ->where('contabilizado', true)
            ->get();

        foreach ($cobros as $cobro) {
            $desglose = $service->desglosarCobroPorEmpleado($cobro);

            // Sumar servicios, productos y bonos del empleado
            if (isset($desglose[$this->id])) {
                $facturacion['servicios'] += $desglose[$this->id]['servicios'] ?? 0;
                $facturacion['productos'] += $desglose[$this->id]['productos'] ?? 0;
                $facturacion['bonos'] += $desglose[$this->id]['bonos'] ?? 0;
            } else {
                // CASO ESPECIAL: Cobro sin servicios/productos (ej: pago de deuda sin cobro original)
                // Si el cobro no tiene servicios ni productos pero está asignado a este empleado,
                // facturar el coste completo como "servicios"
                if ($cobro->id_empleado == $this->id && 
                    $cobro->servicios->count() == 0 && 
                    $cobro->productos->count() == 0 && 
                    $cobro->total_final > 0) {
                    $facturacion['servicios'] += $cobro->total_final;
                }
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

    /**
     * Calcular facturación por categoría (peluqueria/estetica) en un rango de fechas
     * IMPORTANTE: Esta facturación NO está ligada al empleado específico,
     * sino que suma TODOS los cobros del sistema agrupados por categoría
     */
    public static function facturacionPorCategoriaPorFechas($fechaInicio, $fechaFin): array
    {
        $fechaInicio = $fechaInicio instanceof Carbon
            ? $fechaInicio->startOfDay()
            : Carbon::parse($fechaInicio)->startOfDay();

        $fechaFin = $fechaFin instanceof Carbon
            ? $fechaFin->endOfDay()
            : Carbon::parse($fechaFin)->endOfDay();

        $facturacion = [
            'peluqueria' => [
                'servicios' => 0.0,
                'productos' => 0.0,
                'bonos'     => 0.0,
                'total'     => 0.0,
            ],
            'estetica' => [
                'servicios' => 0.0,
                'productos' => 0.0,
                'bonos'     => 0.0,
                'total'     => 0.0,
            ],
        ];

        $service = new FacturacionService();

        $cobros = RegistroCobro::with([
                'cita.servicios',           // Servicios de cita individual
                'citasAgrupadas.servicios', // Servicios de citas agrupadas
                'servicios',                // Servicios directos
                'productos',
                'bonosVendidos.bonoPlantilla' // Necesitamos el bono_plantilla para su categoría
            ])
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->where('metodo_pago', '!=', 'bono') // Excluir cobros pagados con bono (son consumos, no ingresos)
            ->where('metodo_pago', '!=', 'deuda') // Excluir cobros que GENERAN deuda (no se cobró nada)
            ->get();

        foreach ($cobros as $cobro) {
            $desglose = $service->desglosarCobroPorCategoria($cobro);

            // Sumar por categoría
            foreach (['peluqueria', 'estetica'] as $categoria) {
                $facturacion[$categoria]['servicios'] += $desglose[$categoria]['servicios'] ?? 0;
                $facturacion[$categoria]['productos'] += $desglose[$categoria]['productos'] ?? 0;
                $facturacion[$categoria]['bonos'] += $desglose[$categoria]['bonos'] ?? 0;
            }

            // CASO ESPECIAL: Cobro sin servicios/productos (ej: pago de deuda sin cobro original)
            // Verificar si tiene servicios en cualquiera de las relaciones
            $tieneServicios = false;
            if (($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) ||
                ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) ||
                ($cobro->servicios && $cobro->servicios->count() > 0)) {
                $tieneServicios = true;
            }
            
            $tieneProductos = $cobro->productos && $cobro->productos->count() > 0;
            
            // Si no tiene servicios ni productos pero se cobró algo, usar categoría del empleado
            if (!$tieneServicios && !$tieneProductos && $cobro->total_final > 0) {
                $empleado = Empleado::find($cobro->id_empleado);
                $categoriaEmpleado = $empleado?->categoria ?? 'peluqueria';
                $facturacion[$categoriaEmpleado]['servicios'] += $cobro->total_final;
            }
        }

        // Calcular totales y redondear
        foreach (['peluqueria', 'estetica'] as $categoria) {
            $facturacion[$categoria]['total'] = 
                $facturacion[$categoria]['servicios'] + 
                $facturacion[$categoria]['productos'] + 
                $facturacion[$categoria]['bonos'];

            foreach ($facturacion[$categoria] as $key => &$valor) {
                $valor = round($valor, 2);
            }
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
