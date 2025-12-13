<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deuda extends Model
{
    use HasFactory;

    protected $table = 'deudas';

    protected $fillable = [
        'id_cliente',
        'saldo_total',
        'saldo_pendiente',
    ];

    protected $casts = [
        'saldo_total' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoDeuda::class, 'id_deuda')->orderBy('created_at', 'desc');
    }

    public function registrarCargo($monto, $nota = null, $fechaVencimiento = null, $idRegistroCobro = null)
    {
        $this->saldo_total += $monto;
        $this->saldo_pendiente += $monto;
        $this->save();

        return $this->movimientos()->create([
            'id_registro_cobro' => $idRegistroCobro,
            'tipo' => 'cargo',
            'monto' => $monto,
            'nota' => $nota,
            'fecha_vencimiento' => $fechaVencimiento,
            'usuario_registro_id' => auth()->id() ?? 1,
        ]);
    }

    public function registrarAbono($monto, $metodoPago, $nota = null)
    {
        if ($monto > $this->saldo_pendiente) {
            $monto = $this->saldo_pendiente;
        }

        $this->saldo_pendiente -= $monto;
        $this->save();

        return $this->movimientos()->create([
            'tipo' => 'abono',
            'monto' => $monto,
            'metodo_pago' => $metodoPago,
            'nota' => $nota,
            'usuario_registro_id' => auth()->id() ?? 1,
        ]);
    }

    public function tieneDeuda()
    {
        return $this->saldo_pendiente > 0;
    }
}
