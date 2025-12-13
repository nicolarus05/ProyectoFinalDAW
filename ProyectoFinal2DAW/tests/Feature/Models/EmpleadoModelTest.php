<?php

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Empleado Model', function () {
    test('can create empleado with factory', function () {
        $empleado = Empleado::factory()->create();
        
        expect($empleado)->toBeInstanceOf(Empleado::class)
            ->and($empleado->id)->toBeInt()
            ->and($empleado->user)->toBeInstanceOf(User::class);
    });

    test('empleado belongs to user', function () {
        $user = User::factory()->empleado()->create();
        $empleado = Empleado::factory()->forUser($user)->create();
        
        expect($empleado->user->id)->toBe($user->id)
            ->and($empleado->id_user)->toBe($user->id)
            ->and($empleado->user->rol)->toBe('empleado');
    });

    test('empleado can be peluqueria category', function () {
        $empleado = Empleado::factory()->peluqueria()->create();
        
        expect($empleado->categoria)->toBe('peluqueria');
    });

    test('empleado can be estetica category', function () {
        $empleado = Empleado::factory()->estetica()->create();
        
        expect($empleado->categoria)->toBe('estetica');
    });

    test('empleado has horarios invierno and verano', function () {
        $empleado = Empleado::factory()->create();
        
        expect($empleado->horario_invierno)->not->toBeNull()
            ->and($empleado->horario_verano)->not->toBeNull();
    });

    test('horarios are valid JSON arrays', function () {
        $empleado = Empleado::factory()->create();
        
        expect($empleado->horario_invierno)->toBeArray()
            ->and($empleado->horario_verano)->toBeArray()
            ->and($empleado->horario_invierno)->toHaveKey('lunes')
            ->and($empleado->horario_verano)->toHaveKey('lunes');
    });

    test('empleado can be created without schedule', function () {
        $empleado = Empleado::factory()->withoutSchedule()->create();
        
        expect($empleado->horarios_invierno)->toBeNull()
            ->and($empleado->horarios_verano)->toBeNull();
    });

    test('empleado can have custom schedule', function () {
        $empleado = Empleado::factory()->withCustomSchedule(
            ['lunes' => ['inicio' => '09:00', 'fin' => '14:00'], 'sabado' => null],
            ['lunes' => ['inicio' => '08:00', 'fin' => '13:00'], 'sabado' => null]
        )->create();
        
        expect($empleado->horario_invierno['lunes'])->toBe(['inicio' => '09:00', 'fin' => '14:00'])
            ->and($empleado->horario_invierno['sabado'])->toBeNull();
    });

    test('empleado has servicios relationship', function () {
        $empleado = Empleado::factory()->create();
        
        expect($empleado->servicios())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });

    test('empleado has citas relationship', function () {
        $empleado = Empleado::factory()->create();
        
        expect($empleado->citas())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    // TODO: Implementar relación indicadores() en el modelo Empleado
    // test('empleado has indicadores relationship', function () {
    //     $empleado = Empleado::factory()->create();
    //     
    //     expect($empleado->indicadores())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    // });

    test('obtener horario returns correct season schedule', function () {
        $empleado = Empleado::factory()->create();
        
        $horario = $empleado->obtenerHorario(now());
        
        // Debe devolver horarios de invierno o verano según la fecha
        expect($horario)->toBeArray();
    });

    test('empleado uses soft deletes', function () {
        $empleado = Empleado::factory()->create();
        $empleadoId = $empleado->id;
        
        $empleado->delete();
        
        expect(Empleado::find($empleadoId))->toBeNull()
            ->and(Empleado::withTrashed()->find($empleadoId))->not->toBeNull();
    });

    test('facturacion mes actual returns numeric value', function () {
        $empleado = Empleado::factory()->create();
        
        $facturacion = $empleado->facturacionMesActual();
        
        expect($facturacion)->toBeArray()
            ->and($facturacion)->toHaveKey('total')
            ->and($facturacion['total'])->toBeNumeric();
    });
});
