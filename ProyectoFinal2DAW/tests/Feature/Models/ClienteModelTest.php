<?php

use App\Models\Cliente;
use App\Models\User;
use App\Models\Deuda;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Cliente Model', function () {
    test('can create a cliente with factory', function () {
        $cliente = Cliente::factory()->create();
        
        expect($cliente)->toBeInstanceOf(Cliente::class)
            ->and($cliente->id)->toBeInt()
            ->and($cliente->user)->toBeInstanceOf(User::class);
    });

    test('cliente belongs to a user', function () {
        $user = User::factory()->cliente()->create();
        $cliente = Cliente::factory()->forUser($user)->create();
        
        expect($cliente->user->id)->toBe($user->id)
            ->and($cliente->id_user)->toBe($user->id);
    });

    test('cliente has direccion attribute', function () {
        $cliente = Cliente::factory()->create([
            'direccion' => 'Calle Test 123',
        ]);
        
        expect($cliente->direccion)->toBe('Calle Test 123');
    });

    test('cliente can have notas adicionales', function () {
        $cliente = Cliente::factory()->withNotas()->create();
        
        expect($cliente->notas_adicionales)->not->toBeNull();
    });

    test('cliente can be created without notas', function () {
        $cliente = Cliente::factory()->withoutNotas()->create();
        
        expect($cliente->notas_adicionales)->toBeNull();
    });

    test('cliente has fecha_registro', function () {
        $cliente = Cliente::factory()->create();
        
        expect($cliente->fecha_registro)->not->toBeNull();
    });

    test('cliente can have deuda relationship', function () {
        $cliente = Cliente::factory()->create();
        
        // Verificar que la relación existe
        expect($cliente->deuda())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class);
    });

    test('obtener deuda creates deuda if not exists', function () {
        $cliente = Cliente::factory()->create();
        
        $deuda = $cliente->obtenerDeuda();
        
        expect($deuda)->toBeInstanceOf(Deuda::class)
            ->and((float)$deuda->saldo_total)->toBe(0.0)
            ->and((float)$deuda->saldo_pendiente)->toBe(0.0);
    });

    test('obtener deuda returns existing deuda', function () {
        $cliente = Cliente::factory()->create();
        $existingDeuda = Deuda::factory()->forCliente($cliente)->create([
            'saldo_total' => 100.00,
            'saldo_pendiente' => 50.00,
        ]);
        
        $deuda = $cliente->obtenerDeuda();
        
        expect($deuda->id)->toBe($existingDeuda->id)
            ->and((float)$deuda->saldo_total)->toBe(100.00)
            ->and((float)$deuda->saldo_pendiente)->toBe(50.00);
    });

    test('cliente has citas relationship', function () {
        $cliente = Cliente::factory()->create();
        
        expect($cliente->citas())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    test('cliente has bonos relationship', function () {
        $cliente = Cliente::factory()->create();
        
        expect($cliente->bonos())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    test('recent factory creates cliente registered in last 30 days', function () {
        $cliente = Cliente::factory()->recent()->create();
        
        $thirtyDaysAgo = now()->subDays(30);
        
        expect($cliente->fecha_registro)->toBeGreaterThanOrEqual($thirtyDaysAgo);
    });

    test('old factory creates cliente registered more than 1 year ago', function () {
        $cliente = Cliente::factory()->old()->create();
        
        $oneYearAgo = now()->subYear();
        
        expect($cliente->fecha_registro)->toBeLessThan($oneYearAgo);
    });

    test('cliente uses soft deletes', function () {
        $cliente = Cliente::factory()->create();
        $clienteId = $cliente->id;
        
        $cliente->delete();
        
        // Verificar que está marcado como eliminado pero sigue en BD
        expect(Cliente::find($clienteId))->toBeNull()
            ->and(Cliente::withTrashed()->find($clienteId))->not->toBeNull();
    });
});
