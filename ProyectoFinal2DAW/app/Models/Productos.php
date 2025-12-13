<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Productos extends Model{
    use HasFactory, SoftDeletes;
    
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
