<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RegistroCobro extends Model {
    use HasFactory;

    protected $table = 'registro_cobros';

    protected $fillable = [
        'id_cita',
        'id_cliente',
        'id_empleado',
        'coste',
        'descuento_porcentaje',
        'descuento_euro',
        'descuento_servicios_porcentaje',
        'descuento_servicios_euro',
        'descuento_productos_porcentaje',
        'descuento_productos_euro',
        'total_final',
        'total_bonos_vendidos',
        'metodo_pago',
        'dinero_cliente',
        'pago_efectivo',
        'pago_tarjeta',
        'cambio',
        'deuda',
        'contabilizado',
    ];

    // Relación directa con cita (para cobros de una sola cita)
    public function cita() {
        return $this->belongsTo(Cita::class, 'id_cita');
    }

    // Relación con múltiples citas (para cobros agrupados)
    public function citasAgrupadas()
    {
        return $this->belongsToMany(
            Cita::class,
            'registro_cobro_citas',
            'registro_cobro_id',
            'cita_id'
        )->withTimestamps();
    }

    // Relación directa con cliente (si existe id_cliente en tabla)
    public function cliente() {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    // Relación directa con empleado
    public function empleado() {
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    // Relación con productos (tabla pivot)
    public function productos()
    {
        return $this->belongsToMany(
            \App\Models\Productos::class,
            'registro_cobro_productos',
            'id_registro_cobro',
            'id_producto'
        )
        ->withPivot(['cantidad','precio_unitario','subtotal','empleado_id'])
        ->withTimestamps();
    }

    // Relación con servicios (tabla pivot con empleado_id)
    public function servicios()
    {
        return $this->belongsToMany(
            Servicio::class,
            'registro_cobro_servicio',
            'registro_cobro_id',
            'servicio_id'
        )
        ->withPivot(['empleado_id', 'precio'])
        ->withTimestamps();
    }

    // Relación con bonos vendidos en este cobro
    public function bonosVendidos()
    {
        return $this->belongsToMany(
            BonoCliente::class,
            'registro_cobro_bonos',
            'registro_cobro_id',
            'bono_cliente_id'
        )
        ->withPivot('precio')
        ->withTimestamps();
    }

    // Relación con movimientos de deuda (para identificar pagos de deuda)
    public function movimientosDeuda()
    {
        return $this->hasMany(
            \App\Models\MovimientoDeuda::class,
            'id_registro_cobro'
        );
    }
}
