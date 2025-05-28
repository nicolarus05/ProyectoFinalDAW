<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Cita;
use App\Models\Servicio;

class Empleado extends Model{
    use HasFactory;

    protected $table = 'empleados';

    // Definición de las columnas de la tabla
    protected $fillable = [
        'id_user',
        'especializacion',
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
