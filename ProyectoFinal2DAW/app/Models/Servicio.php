<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Empleado;

class Servicio extends Model{
    use HasFactory, SoftDeletes, Notifiable, CanResetPassword, HasApiTokens;

    protected $table = 'servicios';

    // Definición de las columnas de la tabla
    protected $fillable = [
        'nombre',
        'tiempo_estimado',
        'precio',
        'tipo', 
    ];

    public function empleados(){
        return $this->belongsToMany(
            Empleado::class,
            'empleado_servicio',
            'id_servicio',
            'id_empleado',
        );
    }

    public function citas(){
        return $this->belongsToMany(Cita::class, 'cita_servicio', 'id_servicio', 'id_cita');
    }
}
