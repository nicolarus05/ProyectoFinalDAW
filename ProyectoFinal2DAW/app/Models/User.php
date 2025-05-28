<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Cliente;
use App\Models\Empleado;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the cliente associated with the user.
     */
    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'id_user');
    }

    /**
     * Get the empleado associated with the user.
     */
    public function empleado()
    {
        return $this->hasOne(Empleado::class, 'id_user');
    }
}
