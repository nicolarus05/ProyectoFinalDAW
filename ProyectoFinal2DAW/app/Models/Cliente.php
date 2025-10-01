<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Cita;

class Cliente extends Model{
    use HasFactory, SoftDeletes, Notifiable, CanResetPassword, HasApiTokens;

    protected $table = 'clientes';

    // Definición de las columnas de la tabla
    protected $fillable = [
        'id_user',
        'direccion',
        'notas_adicionales',
        'fecha_registro',
    ];
    
    public function user(){
        return $this->belongsTo(user::class, 'id_user');
    }

    public function citas(){
        return $this->hasMany(Cita::class, 'id_cliente');
    }
}
