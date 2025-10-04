<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Servicio extends Model{
    use HasFactory, SoftDeletes;

    protected $table = 'servicios';

    // Definición de las columnas que se pueden asignar en masa
    protected $fillable = [
        'nombre',
        'tiempo_estimado',
        'precio',
        'tipo',
        'descripcion',
        'activo',
    ];

    // Relaciones
    public function empleados()
    {
        return $this->belongsToMany(
            Empleado::class,
            'empleado_servicio',
            'id_servicio',
            'id_empleado'
        );
    }

    public function citas()
    {
        return $this->belongsToMany(Cita::class, 'cita_servicio', 'id_servicio', 'id_cita');
    }
}
