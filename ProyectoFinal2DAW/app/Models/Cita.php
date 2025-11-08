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
        'duracion_minutos',
        'estado',
        'notas_adicionales',
        'id_cliente',
        'id_empleado',
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function empleado(){
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    public function servicios(){
        return $this->belongsToMany(Servicio::class, 'cita_servicio', 'id_cita', 'id_servicio');
    }

    public function cobro(){
        return $this->hasOne(RegistroCobro::class, 'id_cita');
    }

    public function user(){
        return $this->belongsTo(user::class, 'id_user');
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
}
