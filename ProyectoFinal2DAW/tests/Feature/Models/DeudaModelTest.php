<?php

use App\Models\Deuda;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Deuda Model', function () {
    test('can create deuda with factory', function () {
        $deuda = Deuda::factory()->create();
        
        expect($deuda)->toBeInstanceOf(Deuda::class)
            ->and($deuda->id)->toBeInt()
            ->and($deuda->cliente)->toBeInstanceOf(Cliente::class);
    });

    test('deuda belongs to cliente', function () {
        $cliente = Cliente::factory()->create();
        $deuda = Deuda::factory()->forCliente($cliente)->create();
        
        expect($deuda->cliente->id)->toBe($cliente->id)
            ->and($deuda->id_cliente)->toBe($cliente->id);
    });

    test('deuda can be saldada', function () {
        $deuda = Deuda::factory()->saldada()->create();
        
        expect((float)$deuda->saldo_pendiente)->toBe(0.0);
    });

    test('deuda can be pendiente', function () {
        $deuda = Deuda::factory()->pendiente()->create();
        
        expect((float)$deuda->saldo_pendiente)->toBe((float)$deuda->saldo_total)
            ->and($deuda->saldo_pendiente)->toBeGreaterThan(0);
    });

    test('deuda can be parcial', function () {
        $deuda = Deuda::factory()->parcial()->create();
        
        expect($deuda->saldo_pendiente)->toBeLessThan($deuda->saldo_total)
            ->and($deuda->saldo_pendiente)->toBeGreaterThan(0);
    });

    test('small deuda has saldo less than 100', function () {
        $deuda = Deuda::factory()->small()->create();
        
        expect($deuda->saldo_total)->toBeLessThan(100);
    });

    test('large deuda has saldo more than 200', function () {
        $deuda = Deuda::factory()->large()->create();
        
        expect($deuda->saldo_total)->toBeGreaterThan(200);
    });

    test('saldo pendiente is never greater than saldo total', function () {
        $deuda = Deuda::factory()->create();
        
        expect($deuda->saldo_pendiente)->toBeLessThanOrEqual($deuda->saldo_total);
    });

    test('deuda has registros abonos relationship', function () {
        $deuda = Deuda::factory()->create();
        
        expect($deuda->registrosAbonos())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    test('registrar abono reduces saldo pendiente', function () {
        $deuda = Deuda::factory()->pendiente()->create([
            'saldo_total' => 100.00,
            'saldo_pendiente' => 100.00,
        ]);
        
        $deuda->registrarAbono(50.00, 'efectivo');
        
        expect($deuda->fresh()->saldo_pendiente)->toBeNumeric()
            ->and((float)$deuda->fresh()->saldo_pendiente)->toBe(50.00);
    });

    test('registrar abono creates registro abono', function () {
        $deuda = Deuda::factory()->pendiente()->create([
            'saldo_total' => 100.00,
            'saldo_pendiente' => 100.00,
        ]);
        
        $deuda->registrarAbono(50.00, 'efectivo');
        
        expect($deuda->registrosAbonos()->count())->toBe(1)
            ->and((float)$deuda->registrosAbonos->first()->monto)->toBe(50.00);
    });

    test('tiene deuda returns true when saldo pendiente is greater than zero', function () {
        $deuda = Deuda::factory()->pendiente()->create();
        
        expect($deuda->tieneDeuda())->toBeTrue();
    });

    test('tiene deuda returns false when saldo pendiente is zero', function () {
        $deuda = Deuda::factory()->saldada()->create();
        
        expect($deuda->tieneDeuda())->toBeFalse();
    });

    test('deuda uses soft deletes', function () {
        $deuda = Deuda::factory()->create();
        $deudaId = $deuda->id;
        
        $deuda->delete();
        
        expect(Deuda::find($deudaId))->toBeNull()
            ->and(Deuda::withTrashed()->find($deudaId))->not->toBeNull();
    });

    test('cannot register abono greater than saldo pendiente', function () {
        $deuda = Deuda::factory()->create([
            'saldo_total' => 100.00,
            'saldo_pendiente' => 50.00,
        ]);
        
        // El método limita automáticamente el abono al saldo pendiente
        $deuda->registrarAbono(60.00, 'efectivo');
        
        expect((float)$deuda->fresh()->saldo_pendiente)->toBe(0.0);
    });

    test('saldo total and pendiente are numeric', function () {
        $deuda = Deuda::factory()->create();
        
        expect($deuda->saldo_total)->toBeNumeric()
            ->and($deuda->saldo_pendiente)->toBeNumeric();
    });
});
