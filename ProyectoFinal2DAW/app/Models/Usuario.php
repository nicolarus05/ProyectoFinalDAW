<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Cliente;

class Usuario extends Authenticatable{
    use HasFactory, HasApiTokens; 

    protected $table = 'usuarios';

    // Definición de las columnas de la tabla
    protected $fillable = [
        'nombre',
        'apellidos',
        'telefono',
        'email',
        'password',
        'genero',
        'edad', 
        'rol',
        'foto_perfil',
    ];

    // Definición de las columnas que no se deben mostrar
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Definición de las columnas que se deben convertir a un tipo específico
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Definición de las relaciones
    public function cliente(){
        return $this->hasOne(Cliente::class, 'id_usuario');
    }

    public function empleado(){
        return $this->hasOne(Empleado::class, 'id_usuario');
    }
}
