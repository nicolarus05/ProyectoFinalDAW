<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class RegistroCobro extends Model{
    use HasFactory;

    protected $table = 'registro_cobro';

    // Definición de las columnas de la tabla
    protected $fillable = [
        'cita_id',
        'coste',
        'descuento_porcentaje',
        'descuento_euro',
        'total_final',
        'metodo_pago',
        'cambio',
    ];

    public function cita(){
        return $this->belongsTo(Cita::class, 'cita_id');
    }
}