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
        'categoria',
        'subcategoria_id',
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

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class, 'subcategoria_id');
    }

    /**
     * Accessor para duracion_minutos (alias de tiempo_estimado)
     */
    public function getDuracionMinutosAttribute()
    {
        return $this->tiempo_estimado;
    }
}
