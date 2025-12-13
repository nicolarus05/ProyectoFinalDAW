<?php

use App\Models\Cliente;
use App\Models\Cita;
use App\Models\Empleado;
use App\Models\HorarioTrabajo;
use App\Models\Deuda;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Cliente Scopes', function () {
    test('scope conDeuda returns only clients with pending debt', function () {
        // Crear clientes sin deuda
        $clienteSinDeuda1 = Cliente::factory()->create();
        $clienteSinDeuda2 = Cliente::factory()->create();
        
        // Crear cliente con deuda saldada
        $clienteDeudaSaldada = Cliente::factory()->create();
        $clienteDeudaSaldada->deuda()->create([
            'saldo_total' => 100.00,
            'saldo_pendiente' => 0.00,
        ]);
        
        // Crear clientes con deuda pendiente
        $clienteConDeuda1 = Cliente::factory()->create();
        $clienteConDeuda1->deuda()->create([
            'saldo_total' => 100.00,
            'saldo_pendiente' => 50.00,
        ]);
        
        $clienteConDeuda2 = Cliente::factory()->create();
        $clienteConDeuda2->deuda()->create([
            'saldo_total' => 200.00,
            'saldo_pendiente' => 150.00,
        ]);
        
        $clientesConDeuda = Cliente::conDeuda()->get();
        
        expect($clientesConDeuda->count())->toBe(2)
            ->and($clientesConDeuda->pluck('id')->toArray())->toContain($clienteConDeuda1->id)
            ->and($clientesConDeuda->pluck('id')->toArray())->toContain($clienteConDeuda2->id)
            ->and($clientesConDeuda->pluck('id')->toArray())->not->toContain($clienteSinDeuda1->id)
            ->and($clientesConDeuda->pluck('id')->toArray())->not->toContain($clienteDeudaSaldada->id);
    });
    
    test('tieneDeudaPendiente returns true when client has debt', function () {
        $cliente = Cliente::factory()->create();
        $cliente->deuda()->create([
            'saldo_total' => 100.00,
            'saldo_pendiente' => 50.00,
        ]);
        
        expect($cliente->tieneDeudaPendiente())->toBeTrue();
    });
    
    test('tieneDeudaPendiente returns false when client has no debt', function () {
        $cliente = Cliente::factory()->create();
        
        expect($cliente->tieneDeudaPendiente())->toBeFalse();
    });
    
    test('deudaPendiente attribute returns correct amount', function () {
        $cliente = Cliente::factory()->create();
        $cliente->deuda()->create([
            'saldo_total' => 100.00,
            'saldo_pendiente' => 75.50,
        ]);
        
        expect((float)$cliente->deudaPendiente)->toBe(75.50);
    });
    
    test('nombreCompleto attribute concatenates user name and apellidos', function () {
        $cliente = Cliente::factory()->create();
        
        $nombreEsperado = $cliente->user->nombre . ' ' . $cliente->user->apellidos;
        
        expect($cliente->nombreCompleto)->toBe($nombreEsperado);
    });
});

describe('Cita Scopes', function () {
    test('scope porFecha returns citas for specific date', function () {
        $fecha = now()->format('Y-m-d');
        $otraFecha = now()->addDays(3)->format('Y-m-d');
        
        // Crear citas para la fecha objetivo
        $cita1 = Cita::factory()->create([
            'fecha_hora' => $fecha . ' 10:00:00',
        ]);
        $cita2 = Cita::factory()->create([
            'fecha_hora' => $fecha . ' 14:00:00',
        ]);
        
        // Crear citas para otra fecha
        $citaOtraFecha = Cita::factory()->create([
            'fecha_hora' => $otraFecha . ' 10:00:00',
        ]);
        
        $citasFecha = Cita::porFecha($fecha)->get();
        
        expect($citasFecha->count())->toBe(2)
            ->and($citasFecha->pluck('id')->toArray())->toContain($cita1->id)
            ->and($citasFecha->pluck('id')->toArray())->toContain($cita2->id)
            ->and($citasFecha->pluck('id')->toArray())->not->toContain($citaOtraFecha->id);
    });
    
    test('scope porEmpleado returns citas for specific employee', function () {
        $empleado1 = Empleado::factory()->create();
        $empleado2 = Empleado::factory()->create();
        
        $cita1 = Cita::factory()->create(['id_empleado' => $empleado1->id]);
        $cita2 = Cita::factory()->create(['id_empleado' => $empleado1->id]);
        $cita3 = Cita::factory()->create(['id_empleado' => $empleado2->id]);
        
        $citasEmpleado1 = Cita::porEmpleado($empleado1->id)->get();
        
        expect($citasEmpleado1->count())->toBe(2)
            ->and($citasEmpleado1->pluck('id')->toArray())->toContain($cita1->id)
            ->and($citasEmpleado1->pluck('id')->toArray())->toContain($cita2->id)
            ->and($citasEmpleado1->pluck('id')->toArray())->not->toContain($cita3->id);
    });
    
    test('duracionMinutos uses duracion_real when available', function () {
        $cita = Cita::factory()->create([
            'duracion_real' => 45,
        ]);
        
        expect($cita->duracionMinutos)->toBe(45);
    });
    
    test('horaFin calculates correctly based on duracion', function () {
        $cita = Cita::factory()->create([
            'fecha_hora' => '2025-12-13 10:00:00',
            'duracion_real' => 60,
        ]);
        
        $horaFinEsperada = \Carbon\Carbon::parse('2025-12-13 11:00:00');
        
        expect($cita->horaFin->equalTo($horaFinEsperada))->toBeTrue();
    });
});

describe('HorarioTrabajo Scopes', function () {
    test('scope disponibles returns only available schedules', function () {
        $empleado = Empleado::factory()->create();
        
        $horarioDisponible = HorarioTrabajo::create([
            'id_empleado' => $empleado->id,
            'fecha' => now()->addDays(2),
            'hora_inicio' => '09:00:00',
            'hora_fin' => '18:00:00',
            'disponible' => true,
        ]);
        
        $horarioNoDisponible = HorarioTrabajo::create([
            'id_empleado' => $empleado->id,
            'fecha' => now()->addDays(3),
            'hora_inicio' => '09:00:00',
            'hora_fin' => '18:00:00',
            'disponible' => false,
        ]);
        
        $disponibles = HorarioTrabajo::disponibles()->get();
        
        expect($disponibles->pluck('id')->toArray())->toContain($horarioDisponible->id)
            ->and($disponibles->pluck('id')->toArray())->not->toContain($horarioNoDisponible->id);
    });
    
    test('scope porRangoFechas returns schedules within date range', function () {
        $empleado = Empleado::factory()->create();
        
        $fechaInicio = now()->startOfDay();
        $fechaFin = now()->addDays(7)->endOfDay();
        
        $horarioDentro = HorarioTrabajo::create([
            'id_empleado' => $empleado->id,
            'fecha' => now()->addDays(3),
            'hora_inicio' => '09:00:00',
            'hora_fin' => '18:00:00',
            'disponible' => true,
        ]);
        
        $horarioFuera = HorarioTrabajo::create([
            'id_empleado' => $empleado->id,
            'fecha' => now()->addDays(10),
            'hora_inicio' => '09:00:00',
            'hora_fin' => '18:00:00',
            'disponible' => true,
        ]);
        
        $horariosRango = HorarioTrabajo::porRangoFechas($fechaInicio, $fechaFin)->get();
        
        expect($horariosRango->pluck('id')->toArray())->toContain($horarioDentro->id)
            ->and($horariosRango->pluck('id')->toArray())->not->toContain($horarioFuera->id);
    });
});

describe('Complex Relationships', function () {
    test('cliente can access citas through relationship', function () {
        $cliente = Cliente::factory()->create();
        $empleado = Empleado::factory()->create();
        
        $cita1 = Cita::factory()->create([
            'id_cliente' => $cliente->id,
            'id_empleado' => $empleado->id,
        ]);
        $cita2 = Cita::factory()->create([
            'id_cliente' => $cliente->id,
            'id_empleado' => $empleado->id,
        ]);
        
        expect($cliente->citas->count())->toBe(2)
            ->and($cliente->citas->pluck('id')->toArray())->toContain($cita1->id)
            ->and($cliente->citas->pluck('id')->toArray())->toContain($cita2->id);
    });
    
    test('empleado can access citas and servicios', function () {
        $empleado = Empleado::factory()->create();
        $cliente = Cliente::factory()->create();
        
        $cita = Cita::factory()->create([
            'id_empleado' => $empleado->id,
            'id_cliente' => $cliente->id,
        ]);
        
        expect($empleado->citas->count())->toBeGreaterThan(0)
            ->and($empleado->servicios())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });
    
    test('cita can access servicios through many-to-many relationship', function () {
        $cita = Cita::factory()->create();
        
        expect($cita->servicios())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });
});
