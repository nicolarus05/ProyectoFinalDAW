<?php

use App\Models\Productos;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Productos Model', function () {
    test('can create producto with factory', function () {
        $producto = Productos::factory()->create();
        
        expect($producto)->toBeInstanceOf(Productos::class)
            ->and($producto->id)->toBeInt()
            ->and($producto->nombre)->not->toBeNull();
    });

    test('producto can be capilar category', function () {
        $producto = Productos::factory()->capilar()->create();
        
        expect($producto->categoria)->toBe('capilar');
    });

    test('producto can be estetica category', function () {
        $producto = Productos::factory()->estetica()->create();
        
        expect($producto->categoria)->toBe('estetica');
    });

    test('producto is active by default', function () {
        $producto = Productos::factory()->create();
        
        expect($producto->activo)->toBeTrue();
    });

    test('producto can be inactive', function () {
        $producto = Productos::factory()->inactive()->create();
        
        expect($producto->activo)->toBeFalse();
    });

    test('producto can be out of stock', function () {
        $producto = Productos::factory()->outOfStock()->create();
        
        expect($producto->stock)->toBe(0);
    });

    test('producto can have low stock', function () {
        $producto = Productos::factory()->lowStock()->create();
        
        expect($producto->stock)->toBeGreaterThanOrEqual(1)
            ->and($producto->stock)->toBeLessThanOrEqual(5);
    });

    test('producto can have high stock', function () {
        $producto = Productos::factory()->highStock()->create();
        
        expect($producto->stock)->toBeGreaterThanOrEqual(50)
            ->and($producto->stock)->toBeLessThanOrEqual(200);
    });

    test('cheap producto has price less than 10', function () {
        $producto = Productos::factory()->cheap()->create();
        
        expect($producto->precio_venta)->toBeLessThan(10);
    });

    test('premium producto has price more than 50', function () {
        $producto = Productos::factory()->premium()->create();
        
        expect($producto->precio_venta)->toBeGreaterThan(50);
    });

    test('precio venta is greater than precio coste', function () {
        $producto = Productos::factory()->create();
        
        expect($producto->precio_venta)->toBeGreaterThan($producto->precio_coste);
    });

    test('stock is non negative', function () {
        $producto = Productos::factory()->create();
        
        expect($producto->stock)->toBeGreaterThanOrEqual(0);
    });

    // TODO: Implementar relación ventasProductos() en el modelo Productos
    // test('producto has ventasProductos relationship', function () {
    //     $producto = Productos::factory()->create();
    //     
    //     expect($producto->ventasProductos())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    // });

    test('producto uses soft deletes', function () {
        $producto = Productos::factory()->create();
        $productoId = $producto->id;
        
        $producto->delete();
        
        expect(Productos::find($productoId))->toBeNull()
            ->and(Productos::withTrashed()->find($productoId))->not->toBeNull();
    });

    test('producto has valid categories', function () {
        $categorias = ['capilar', 'estetica', 'unas', 'maquillaje'];
        
        $producto = Productos::factory()->create();
        
        expect($categorias)->toContain($producto->categoria);
    });

    test('producto precios are positive', function () {
        $producto = Productos::factory()->create();
        
        expect($producto->precio_venta)->toBeGreaterThan(0)
            ->and($producto->precio_coste)->toBeGreaterThan(0);
    });

    test('producto can have descripcion', function () {
        $producto = Productos::factory()->create();
        
        // Descripción es opcional
        if ($producto->descripcion !== null) {
            expect($producto->descripcion)->toBeString();
        } else {
            expect($producto->descripcion)->toBeNull();
        }
    });

    test('producto nombre is string', function () {
        $producto = Productos::factory()->create();
        
        expect($producto->nombre)->toBeString()
            ->and($producto->nombre)->not->toBeEmpty();
    });
});
