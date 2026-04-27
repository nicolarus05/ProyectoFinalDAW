<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subcategoria extends Model
{
    use HasFactory;

    protected $table = 'subcategorias';

    protected $fillable = [
        'nombre',
        'categoria',
        'color',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'subcategoria_id');
    }
}
