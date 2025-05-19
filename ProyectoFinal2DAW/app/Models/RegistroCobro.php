<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class RegistroCobro extends Model{
    use HasFactory;

    protected $table = 'registro_cobros';

    // Definición de las columnas de la tabla
    protected $fillable = [
        'id_cita',
        'coste',
        'descuento_porcentaje',
        'descuento_euro',
        'total_final',
        'metodo_pago',
        'dinero_cliente',
        'cambio',
    ];


    public function cita(){
        return $this->belongsTo(Cita::class, 'id_cita');
    }
}