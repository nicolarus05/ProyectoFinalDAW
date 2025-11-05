<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonoUsoDetalle extends Model
{
    protected $table = 'bono_uso_detalle';

    protected $fillable = [
        'bono_cliente_id',
        'cita_id',
        'servicio_id',
        'cantidad_usada'
    ];

    /**
     * Relación con el bono del cliente
     */
    public function bonoCliente()
    {
        return $this->belongsTo(BonoCliente::class, 'bono_cliente_id');
    }

    /**
     * Relación con la cita
     */
    public function cita()
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    /**
     * Relación con el servicio
     */
    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }
}
