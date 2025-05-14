<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Empleado;

class Servicio extends Model{
    use HasFactory;

    protected $table = 'servicios';

    // Definición de las columnas de la tabla
    protected $fillable = [
        'nombre',
        'tiempo_estimado',
        'precio',
    ];

    public function empleados(){
        return $this->belongsToMany(
            Empleado::class,
            'empleado_servicio',
            'servicio_id',
            'empleado_id'
        );
    }

    public function citas(){
        return $this->hasMany(Cita::class, 'servicio_id');
    }
}
