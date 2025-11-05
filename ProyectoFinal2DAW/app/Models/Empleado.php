<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Cita;
use App\Models\Servicio;

class Empleado extends Model{
    use HasFactory, SoftDeletes, Notifiable, CanResetPassword, HasApiTokens;

    protected $table = 'empleados';

    // Definición de las columnas de la tabla
    protected $fillable = [
        'id_user',
        'categoria',
    ];

    public function user(){
        return $this->belongsTo(user::class, 'id_user');
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
}
