<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BonoCliente extends Model
{
    protected $table = 'bonos_clientes';

    protected $fillable = [
        'cliente_id',
        'bono_plantilla_id',
        'fecha_compra',
        'fecha_expiracion',
        'estado',
        'metodo_pago',
        'precio_pagado',
        'dinero_cliente',
        'cambio',
        'id_empleado'
    ];

    protected $casts = [
        'fecha_compra' => 'date',
        'fecha_expiracion' => 'date'
    ];

    /**
     * Cliente propietario del bono
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Plantilla del bono
     */
    public function plantilla()
    {
        return $this->belongsTo(BonoPlantilla::class, 'bono_plantilla_id');
    }

    /**
     * Servicios del bono con sus cantidades
     */
    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'bono_cliente_servicios', 'bono_cliente_id', 'servicio_id')
                    ->withPivot('cantidad_total', 'cantidad_usada')
                    ->withTimestamps();
    }

    /**
     * Verificar si el bono está expirado
     */
    public function estaExpirado()
    {
        return Carbon::now()->greaterThan($this->fecha_expiracion);
    }

    /**
     * Verificar si el bono está completamente usado
     */
    public function estaCompletamenteUsado()
    {
        foreach ($this->servicios as $servicio) {
            if ($servicio->pivot->cantidad_usada < $servicio->pivot->cantidad_total) {
                return false;
            }
        }
        return true;
    }

    /**
     * Obtener cantidad disponible de un servicio
     */
    public function cantidadDisponible($servicioId)
    {
        $servicio = $this->servicios()->where('servicio_id', $servicioId)->first();
        if (!$servicio) {
            return 0;
        }
        return $servicio->pivot->cantidad_total - $servicio->pivot->cantidad_usada;
    }

    /**
     * Empleado que vendió el bono
     */
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }
}
