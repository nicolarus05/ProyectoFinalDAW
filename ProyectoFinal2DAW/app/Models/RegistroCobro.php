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
        'total_final',
        'metodo_pago',
        'dinero_cliente',
        'pago_efectivo',
        'pago_tarjeta',
        'cambio',
        'deuda',
    ];

    // Relación directa con cita
    public function cita() {
        return $this->belongsTo(Cita::class, 'id_cita');
    }

    // Relación directa con cliente (si existe id_cliente en tabla)
    public function cliente() {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    // Relación directa con empleado
    public function empleado() {
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

     public function productos()
    {
        return $this->belongsToMany(
            \App\Models\Productos::class,
            'registro_cobro_productos',
            'id_registro_cobro',
            'id_producto'
        )
        ->withPivot(['cantidad','precio_unitario','subtotal'])
        ->withTimestamps();
    }
}
