<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RegistroEntradaSalida extends Model{
    use HasFactory;

    protected $table = 'registro_entrada_salida';

    // Definición de las columnas de la tabla
    protected $fillable = [
        'empleado_id',
        'fecha',
        'hora_entrada',
        'hora_salida',
    ];

    public function empleado(){
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }
}
