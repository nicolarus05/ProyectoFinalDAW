<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Servicio;
use App\Models\RegistroCobro;

class Cita extends Model{

    use HasFactory, HasApiTokens, Notifiable, CanResetPassword, SoftDeletes;
    
    protected $table = 'citas';

    // Definición de las columnas de la tabla
    protected $fillable = [
        'fecha_hora',
        'estado',
        'notas_adicionales',
        'duracion_real',
        'id_cliente',
        'id_empleado',
        'grupo_cita_id',
        'orden_servicio',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function empleado(){
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    public function servicios(){
        return $this->belongsToMany(Servicio::class, 'cita_servicio', 'id_cita', 'id_servicio')
            ->withTimestamps();
    }

    public function cobro(){
        return $this->hasOne(RegistroCobro::class, 'id_cita');
    }

    public function cobrosAgrupados()
    {
        return $this->belongsToMany(
            RegistroCobro::class,
            'registro_cobro_citas',
            'cita_id',
            'registro_cobro_id'
        )->withTimestamps();
    }

    public function user(){
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Devuelve los servicios de la cita con el precio realmente cobrado.
     */
    public function serviciosConPrecioCobrado()
    {
        $this->loadMissing(['servicios', 'cobro.servicios', 'cobrosAgrupados.servicios']);

        $cobros = collect([$this->cobro])
            ->filter()
            ->merge($this->cobrosAgrupados ?? collect())
            ->unique('id')
            ->values();

        $lineasCobro = $cobros
            ->flatMap(function ($cobro) {
                return ($cobro->servicios ?? collect())->map(function ($servicio) {
                    return [
                        'servicio_id' => (int) $servicio->id,
                        'empleado_id' => $servicio->pivot->empleado_id !== null ? (int) $servicio->pivot->empleado_id : null,
                        'precio' => (float) ($servicio->pivot->precio ?? 0),
                        'pivot_id' => (int) ($servicio->pivot->id ?? 0),
                        'usado' => false,
                    ];
                });
            })
            ->sortBy('pivot_id')
            ->values();

        return $this->servicios->map(function ($servicio) use (&$lineasCobro) {
            $indice = $lineasCobro->search(function ($linea) use ($servicio) {
                return !$linea['usado']
                    && $linea['servicio_id'] === (int) $servicio->id
                    && ($linea['empleado_id'] === null || $linea['empleado_id'] === (int) $this->id_empleado);
            });

            if ($indice === false) {
                $indice = $lineasCobro->search(function ($linea) use ($servicio) {
                    return !$linea['usado'] && $linea['servicio_id'] === (int) $servicio->id;
                });
            }

            $tienePrecioCobrado = $indice !== false;
            $precioCobrado = null;

            if ($tienePrecioCobrado) {
                $linea = $lineasCobro->get($indice);
                $precioCobrado = $linea['precio'];
                $linea['usado'] = true;
                $lineasCobro->put($indice, $linea);
            }

            return (object) [
                'servicio' => $servicio,
                'precio_cobrado' => $precioCobrado,
                'tiene_precio_cobrado' => $tienePrecioCobrado,
            ];
        });
    }

    /**
     * Calcula la duración total de la cita sumando los servicios
     * Si existe duracion_real, la usa; si no, suma los tiempos de servicios
     * Para citas individuales de un grupo, solo cuenta el servicio propio
     */
    public function getDuracionMinutosAttribute()
    {
        if ($this->duracion_real !== null) {
            return $this->duracion_real;
        }
        
        // Para citas individuales (un solo servicio), usar su duración
        if ($this->servicios->count() === 1) {
            return $this->servicios->first()->duracion_minutos ?? $this->servicios->first()->tiempo_estimado ?? 30;
        }
        
        // Para citas con múltiples servicios (estética), sumar todos
        return $this->servicios->sum('tiempo_estimado');
    }

    /**
     * Calcula la hora de fin de la cita basándose en la duración
     */
    public function getHoraFinAttribute(){
        return \Carbon\Carbon::parse($this->fecha_hora)->addMinutes($this->duracion_minutos);
    }

    /**
     * Verifica si esta cita se superpone con otra
     */
    public function seSuperponeCon($otraCita){
        $inicioA = \Carbon\Carbon::parse($this->fecha_hora);
        $finA = $this->hora_fin;
        $inicioB = \Carbon\Carbon::parse($otraCita->fecha_hora);
        $finB = $otraCita->hora_fin;

        return ($inicioA < $finB) && ($finA > $inicioB);
    }

    /**
     * Scope para citas de una fecha específica
     */
    public function scopePorFecha($query, $fecha){
        return $query->whereDate('fecha_hora', $fecha);
    }

    /**
     * Scope para citas de un empleado
     */
    public function scopePorEmpleado($query, $empleadoId){
        return $query->where('id_empleado', $empleadoId);
    }

    /**
     * Relación con citas del mismo grupo
     */
    public function citasGrupo(){
        return $this->hasMany(Cita::class, 'grupo_cita_id', 'grupo_cita_id')
            ->orderBy('orden_servicio');
    }

    /**
     * Verifica si esta cita pertenece a un grupo
     */
    public function esParteDeGrupo(){
        return $this->grupo_cita_id !== null;
    }

    /**
     * Obtiene la cita principal del grupo (orden 1)
     */
    public function citaPrincipal(){
        if (!$this->esParteDeGrupo()) {
            return $this;
        }
        return Cita::where('grupo_cita_id', $this->grupo_cita_id)
            ->where('orden_servicio', 1)
            ->first();
    }
}
