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

    public function deuda()
    {
        return $this->hasOne(Deuda::class, 'id_cliente');
    }

    public function obtenerDeuda()
    {
        if (!$this->deuda) {
            $this->deuda()->create([
                'saldo_total' => 0,
                'saldo_pendiente' => 0,
            ]);
            $this->load('deuda');
        }

        return $this->deuda;
    }

    public function tieneDeudaPendiente()
    {
        return $this->deuda && $this->deuda->saldo_pendiente > 0;
    }

    public function getDeudaPendienteAttribute()
    {
        return $this->deuda ? $this->deuda->saldo_pendiente : 0;
    }

    public function scopeConDeuda($query)
    {
        return $query->whereHas('deuda', function ($q) {
            $q->where('saldo_pendiente', '>', 0);
        });
    }

    public function getNombreCompletoAttribute()
    {
        return $this->user->nombre . ' ' . $this->user->apellidos;
    }
}
