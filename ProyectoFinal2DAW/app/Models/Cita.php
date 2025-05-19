<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Servicio;
use App\Models\RegistroCobro;

class Cita extends Model{

    use HasFactory;
    
    protected $table = 'citas';

    // Definición de las columnas de la tabla
    protected $fillable = [
        'fecha_hora',
        'estado',
        'notas_adicionales',
        'id_cliente',
        'id_empleado',
        'id_servicio',
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function empleado(){
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    public function servicios(){
        return $this->belongsToMany(Servicio::class, 'cita_servicio', 'id_cita', 'id_servicio');
    }

    public function cobro(){
        return $this->hasOne(RegistroCobro::class, 'id_cita');
    }
}
