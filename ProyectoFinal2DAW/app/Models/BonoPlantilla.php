<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonoPlantilla extends Model
{
    protected $table = 'bonos_plantilla';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'duracion_dias',
        'activo'
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'activo' => 'boolean',
        'duracion_dias' => 'integer'
    ];

    /**
     * Relación con servicios (muchos a muchos)
     */
    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'bono_plantilla_servicios', 'bono_plantilla_id', 'servicio_id')
                    ->withPivot('cantidad')
                    ->withTimestamps();
    }

    /**
     * Bonos de clientes basados en esta plantilla
     */
    public function bonosClientes()
    {
        return $this->hasMany(BonoCliente::class, 'bono_plantilla_id');
    }
}
