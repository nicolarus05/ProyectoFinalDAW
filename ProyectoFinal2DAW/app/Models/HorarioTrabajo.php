<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HorarioTrabajo extends Model{
    use HasFactory;

    protected $table = 'horario_trabajo';

    // Definición de las columnas de la tabla
    protected $fillable = [
        'id_empleado',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'disponible',
    ];

    protected $casts = [
        'disponible' => 'boolean',
    ];

    public function empleado(){
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }
}
