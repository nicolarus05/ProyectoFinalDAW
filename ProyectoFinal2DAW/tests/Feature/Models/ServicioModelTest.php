<?php

use App\Models\Servicio;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Servicio Model', function () {
    test('can create servicio with factory', function () {
        $servicio = Servicio::factory()->create();
        
        expect($servicio)->toBeInstanceOf(Servicio::class)
            ->and($servicio->id)->toBeInt()
            ->and($servicio->nombre)->not->toBeNull();
    });

    test('servicio can be peluqueria category', function () {
        $servicio = Servicio::factory()->peluqueria()->create();
        
        expect($servicio->categoria)->toBe('peluqueria');
    });

    test('servicio can be estetica category', function () {
        $servicio = Servicio::factory()->estetica()->create();
        
        expect($servicio->categoria)->toBe('estetica');
    });

    test('servicio is active by default', function () {
        $servicio = Servicio::factory()->create();
        
        expect($servicio->activo)->toBeTrue();
    });

    test('servicio can be inactive', function () {
        $servicio = Servicio::factory()->inactive()->create();
        
        expect($servicio->activo)->toBeFalse();
    });

    test('short servicio has duration less than 30 minutes', function () {
        $servicio = Servicio::factory()->short()->create();
        
        expect($servicio->tiempo_estimado)->toBeLessThan(30);
    });

    test('long servicio has duration more than 90 minutes', function () {
        $servicio = Servicio::factory()->long()->create();
        
        expect($servicio->tiempo_estimado)->toBeGreaterThan(90);
    });

    test('cheap servicio has price less than 20', function () {
        $servicio = Servicio::factory()->cheap()->create();
        
        expect($servicio->precio)->toBeLessThan(20);
    });

    test('premium servicio has price more than 100', function () {
        $servicio = Servicio::factory()->premium()->create();
        
        expect($servicio->precio)->toBeGreaterThan(100);
    });

    test('servicio has empleados relationship', function () {
        $servicio = Servicio::factory()->create();
        
        expect($servicio->empleados())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });

    test('servicio has citas relationship', function () {
        $servicio = Servicio::factory()->create();
        
        expect($servicio->citas())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });

    test('servicio uses soft deletes', function () {
        $servicio = Servicio::factory()->create();
        $servicioId = $servicio->id;
        
        $servicio->delete();
        
        expect(Servicio::find($servicioId))->toBeNull()
            ->and(Servicio::withTrashed()->find($servicioId))->not->toBeNull();
    });

    test('servicio precio is positive', function () {
        $servicio = Servicio::factory()->create();
        
        expect($servicio->precio)->toBeGreaterThan(0);
    });

    test('servicio tiempo estimado is positive', function () {
        $servicio = Servicio::factory()->create();
        
        expect($servicio->tiempo_estimado)->toBeGreaterThan(0);
    });

    test('servicio has optional descripcion', function () {
        $servicio = Servicio::factory()->create();
        
        // Puede ser null o tener contenido
        if ($servicio->descripcion !== null) {
            expect($servicio->descripcion)->toBeString();
        } else {
            expect($servicio->descripcion)->toBeNull();
        }
    });
});
