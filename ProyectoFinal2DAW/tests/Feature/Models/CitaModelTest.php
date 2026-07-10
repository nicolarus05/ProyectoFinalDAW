<?php

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\RegistroCobro;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Cita Model', function () {
    test('can create cita with factory', function () {
        $cita = Cita::factory()->create();
        
        expect($cita)->toBeInstanceOf(Cita::class)
            ->and($cita->id)->toBeInt()
            ->and($cita->cliente)->toBeInstanceOf(Cliente::class)
            ->and($cita->empleado)->toBeInstanceOf(Empleado::class);
    });

    test('cita belongs to cliente', function () {
        $cliente = Cliente::factory()->create();
        $cita = Cita::factory()->forCliente($cliente)->create();
        
        expect($cita->cliente->id)->toBe($cliente->id)
            ->and($cita->id_cliente)->toBe($cliente->id);
    });

    test('cita belongs to empleado', function () {
        $empleado = Empleado::factory()->create();
        $cita = Cita::factory()->forEmpleado($empleado)->create();
        
        expect($cita->empleado->id)->toBe($empleado->id)
            ->and($cita->id_empleado)->toBe($empleado->id);
    });

    test('cita can be pending', function () {
        $cita = Cita::factory()->pending()->create();
        
        expect($cita->estado)->toBe('pendiente');
    });

    test('cita can be confirmed', function () {
        $cita = Cita::factory()->confirmed()->create();
        
        expect($cita->estado)->toBe('confirmada');
    });

    test('cita can be completed', function () {
        $cita = Cita::factory()->completed()->create();
        
        expect($cita->estado)->toBe('completada')
            ->and($cita->duracion_real)->not->toBeNull()
            ->and($cita->duracion_real)->toBeGreaterThan(0);
    });

    test('cita can be cancelled', function () {
        $cita = Cita::factory()->cancelled()->create();
        
        expect($cita->estado)->toBe('cancelada');
    });

    test('cita today is created for current date', function () {
        $cita = Cita::factory()->today()->create();
        
        expect($cita->fecha_hora->isToday())->toBeTrue();
    });

    test('cita future is created for future date', function () {
        $cita = Cita::factory()->future()->create();
        
        expect($cita->fecha_hora->isFuture())->toBeTrue();
    });

    test('cita past is created for past date', function () {
        $cita = Cita::factory()->past()->create();
        
        expect($cita->fecha_hora->isPast())->toBeTrue();
    });

    test('cita can have notas adicionales', function () {
        $cita = Cita::factory()->withNotas()->create();
        
        expect($cita->notas_adicionales)->not->toBeNull();
    });

    test('cita has servicios relationship', function () {
        $cita = Cita::factory()->create();
        
        expect($cita->servicios())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });

    test('cita has cobro relationship', function () {
        $cita = Cita::factory()->create();
        
        expect($cita->cobro())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class);
    });

    test('cita has cobros agrupados relationship', function () {
        $cita = Cita::factory()->create();

        expect($cita->cobrosAgrupados())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });

    test('cita exposes charged service price from direct cobro', function () {
        $cita = Cita::factory()->completed()->create();
        $servicio = Servicio::factory()->create(['precio' => 75.00]);
        $cita->servicios()->attach($servicio->id);

        $cobro = RegistroCobro::create([
            'id_cita' => $cita->id,
            'id_cliente' => $cita->id_cliente,
            'id_empleado' => $cita->id_empleado,
            'coste' => 75.00,
            'total_final' => 42.50,
            'metodo_pago' => 'efectivo',
            'dinero_cliente' => 42.50,
            'cambio' => 0,
            'deuda' => 0,
        ]);

        $cobro->servicios()->attach($servicio->id, [
            'empleado_id' => $cita->id_empleado,
            'precio' => 42.50,
        ]);

        $lineas = $cita->fresh()
            ->load(['servicios', 'cobro.servicios', 'cobrosAgrupados.servicios'])
            ->serviciosConPrecioCobrado();

        expect($lineas)->toHaveCount(1)
            ->and($lineas->first()->tiene_precio_cobrado)->toBeTrue()
            ->and($lineas->first()->precio_cobrado)->toBe(42.50);
    });

    test('cita exposes charged service price from grouped cobro', function () {
        $cliente = Cliente::factory()->create();
        $cita = Cita::factory()->completed()->forCliente($cliente)->create();
        $otraCita = Cita::factory()->completed()->forCliente($cliente)->create();
        $servicio = Servicio::factory()->create(['precio' => 60.00]);
        $otroServicio = Servicio::factory()->create(['precio' => 40.00]);

        $cita->servicios()->attach($servicio->id);
        $otraCita->servicios()->attach($otroServicio->id);

        $cobro = RegistroCobro::create([
            'id_cita' => null,
            'id_cliente' => $cliente->id,
            'id_empleado' => $cita->id_empleado,
            'coste' => 100.00,
            'total_final' => 55.00,
            'metodo_pago' => 'efectivo',
            'dinero_cliente' => 55.00,
            'cambio' => 0,
            'deuda' => 0,
        ]);

        $cobro->citasAgrupadas()->attach([$cita->id, $otraCita->id]);
        $cobro->servicios()->attach($servicio->id, [
            'empleado_id' => $cita->id_empleado,
            'precio' => 18.75,
        ]);
        $cobro->servicios()->attach($otroServicio->id, [
            'empleado_id' => $otraCita->id_empleado,
            'precio' => 36.25,
        ]);

        $lineas = $cita->fresh()
            ->load(['servicios', 'cobro.servicios', 'cobrosAgrupados.servicios'])
            ->serviciosConPrecioCobrado();

        expect($lineas)->toHaveCount(1)
            ->and($lineas->first()->tiene_precio_cobrado)->toBeTrue()
            ->and($lineas->first()->precio_cobrado)->toBe(18.75);
    });

    test('cita does not use default service price when there is no cobro', function () {
        $cita = Cita::factory()->completed()->create();
        $servicio = Servicio::factory()->create(['precio' => 75.00]);
        $cita->servicios()->attach($servicio->id);

        $lineas = $cita->fresh()
            ->load(['servicios', 'cobro.servicios', 'cobrosAgrupados.servicios'])
            ->serviciosConPrecioCobrado();

        expect($lineas)->toHaveCount(1)
            ->and($lineas->first()->tiene_precio_cobrado)->toBeFalse()
            ->and($lineas->first()->precio_cobrado)->toBeNull();
    });

    test('pending and confirmed citas have null duracion_real', function () {
        $citaPendiente = Cita::factory()->pending()->create();
        $citaConfirmada = Cita::factory()->confirmed()->create();
        
        expect($citaPendiente->duracion_real)->toBeNull()
            ->and($citaConfirmada->duracion_real)->toBeNull();
    });

    test('completed cita has duracion_real', function () {
        $cita = Cita::factory()->completed()->create();
        
        expect($cita->duracion_real)->not->toBeNull()
            ->and($cita->duracion_real)->toBeNumeric();
    });

    test('cita uses soft deletes', function () {
        $cita = Cita::factory()->create();
        $citaId = $cita->id;
        
        $cita->delete();
        
        expect(Cita::find($citaId))->toBeNull()
            ->and(Cita::withTrashed()->find($citaId))->not->toBeNull();
    });

    test('cita fecha_hora is during work hours', function () {
        $cita = Cita::factory()->create();
        
        $hour = $cita->fecha_hora->hour;
        
        // Horario laboral: 9:00 - 18:00
        expect($hour)->toBeGreaterThanOrEqual(9)
            ->and($hour)->toBeLessThan(18);
    });

    test('cita can belong to grupo_cita', function () {
        $cita = Cita::factory()->create(['grupo_cita_id' => 123]);
        
        expect($cita->grupo_cita_id)->toBe(123);
    });
});
