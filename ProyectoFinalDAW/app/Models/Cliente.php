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
        'usuario_id',
        'direccion',
        'notas_adicionales',
        'fecha_resgistro',
    ];
    
    public function usuario(){
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function citas(){
        return $this->hasMany(Cita::class, 'cliente_id');
    }
}
