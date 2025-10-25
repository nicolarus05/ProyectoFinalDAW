<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoDeuda extends Model
{
    use HasFactory;

    protected $table = 'movimientos_deuda';

    protected $fillable = [
        'id_deuda',
        'id_registro_cobro',
        'tipo',
        'monto',
        'metodo_pago',
        'nota',
        'fecha_vencimiento',
        'usuario_registro_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    public function deuda()
    {
        return $this->belongsTo(Deuda::class, 'id_deuda');
    }

    public function registroCobro()
    {
        return $this->belongsTo(RegistroCobro::class, 'id_registro_cobro');
    }

    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'usuario_registro_id');
    }

    public function getTipoFormateadoAttribute()
    {
        return $this->tipo === 'cargo' ? 'Cargo' : 'Pago';
    }
}
