<?php

use App\Models\Cliente;
use App\Models\Deuda;
use App\Models\MovimientoDeuda;
use App\Models\RegistroCobro;
use App\Http\Controllers\RegistroCobroController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('destroy de cobro revierte deuda usando base consistente de movimiento', function () {
    $admin = User::factory()->admin()->create();
    $cliente = Cliente::factory()->create();

    $deuda = Deuda::create([
        'id_cliente' => $cliente->id,
        'saldo_total' => 100.00,
        'saldo_pendiente' => 40.00,
    ]);

    $cobro = RegistroCobro::create([
        'id_cita' => null,
        'id_cliente' => $cliente->id,
        'id_empleado' => null,
        'coste' => 100.00,
        'total_final' => 60.00,
        'metodo_pago' => 'efectivo',
        'dinero_cliente' => 60.00,
        'cambio' => 0,
        'deuda' => 40.00,
    ]);

    MovimientoDeuda::create([
        'id_deuda' => $deuda->id,
        'id_registro_cobro' => $cobro->id,
        'tipo' => 'cargo',
        'monto' => 100.00,
        'nota' => 'Cargo original',
        'usuario_registro_id' => $admin->id,
    ]);

    $controller = new RegistroCobroController();
    $controller->destroy($cobro);

    $deuda->refresh();

    // saldo_total se revierte completo por monto del cargo
    expect((float) $deuda->saldo_total)->toBe(0.0);

    // saldo_pendiente solo se reduce por la parte pendiente real del cargo
    expect((float) $deuda->saldo_pendiente)->toBe(0.0);
});

it('destroy de cobro con cargo menor que deuda actual no deja saldo negativo', function () {
    $admin = User::factory()->admin()->create();
    $cliente = Cliente::factory()->create();

    $deuda = Deuda::create([
        'id_cliente' => $cliente->id,
        'saldo_total' => 20.00,
        'saldo_pendiente' => 20.00,
    ]);

    $cobro = RegistroCobro::create([
        'id_cita' => null,
        'id_cliente' => $cliente->id,
        'id_empleado' => null,
        'coste' => 10.00,
        'total_final' => 0.00,
        'metodo_pago' => 'deuda',
        'dinero_cliente' => 0.00,
        'cambio' => 0,
        'deuda' => 20.00,
    ]);

    MovimientoDeuda::create([
        'id_deuda' => $deuda->id,
        'id_registro_cobro' => $cobro->id,
        'tipo' => 'cargo',
        'monto' => 10.00,
        'nota' => 'Cargo parcial',
        'usuario_registro_id' => $admin->id,
    ]);

    $controller = new RegistroCobroController();
    $controller->destroy($cobro);

    $deuda->refresh();

    expect((float) $deuda->saldo_total)->toBe(10.0)
        ->and((float) $deuda->saldo_pendiente)->toBe(10.0);
});
