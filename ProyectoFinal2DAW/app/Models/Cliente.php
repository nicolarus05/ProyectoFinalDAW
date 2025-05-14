<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Cita;

class Cliente extends Model{
    use HasFactory;

    protected $table = 'clientes';

    // Definición de las columnas de la tabla
    protected $fillable = [
        'id_usuario',
        'direccion',
        'notas_adicionales',
        'fecha_registro',
    ];
    
    public function usuario(){
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function citas(){
        return $this->hasMany(Cita::class, 'cliente_id');
    }
}
