<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Productos extends Model{
    
    protected $table = 'productos';
    
    protected $fillable = [
        'nombre',
        'categoria',
        'descripcion',
        'precio_venta',
        'precio_coste',
        'stock',
        'activo',
    ];
    
    protected $casts = [
        'precio_venta' => 'decimal:2',
        'precio_coste' => 'decimal:2',
        'stock' => 'integer',
        'activo' => 'boolean',
    ];
}
