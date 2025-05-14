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
        'cliente_id',
        'empleado_id',
        'servicio_id',
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function empleado(){
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function servicio(){
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function cobro(){
        return $this->hasOne(RegistroCobro::class, 'cita_id');
    }
}
