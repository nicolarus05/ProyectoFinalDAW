<?php

use App\Models\User;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Servicio;
use App\Models\Cita;
use App\Models\Deuda;

beforeEach(function () {
    // Crear un usuario admin para las pruebas
    $this->admin = User::factory()->create([
        'rol' => 'admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password123'),
    ]);
    
    $this->actingAs($this->admin);
});

describe('StoreCitaRequest Validation', function () {
    it('rechaza cita sin fecha_hora', function () {
        $response = $this->post(route('citas.store'), [
            'id_cliente' => 1,
            'id_empleado' => 1,
            'servicios' => [1],
            // fecha_hora faltante
        ]);

        $response->assertSessionHasErrors('fecha_hora');
    });

    it('rechaza cita con fecha_hora en el pasado', function () {
        $empleado = Empleado::factory()->create();
        $cliente = Cliente::factory()->create();
        $servicio = Servicio::factory()->create();

        $response = $this->post(route('citas.store'), [
            'id_cliente' => $cliente->id,
            'id_empleado' => $empleado->id,
            'servicios' => [$servicio->id],
            'fecha_hora' => now()->subDay()->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHasErrors('fecha_hora');
    });

    it('sanitiza notas_adicionales eliminando tags HTML', function () {
        $empleado = Empleado::factory()->create();
        $cliente = Cliente::factory()->create();
        $servicio = Servicio::factory()->create();

        $notasConHTML = '<script>alert("XSS")</script>Notas normales';

        $response = $this->post(route('citas.store'), [
            'id_cliente' => $cliente->id,
            'id_empleado' => $empleado->id,
            'servicios' => [$servicio->id],
            'fecha_hora' => now()->addDay()->format('Y-m-d H:i:s'),
            'notas_adicionales' => $notasConHTML,
        ]);

        // Verificar que el HTML fue eliminado
        $cita = Cita::latest()->first();
        expect($cita->notas_adicionales)->not->toContain('<script>');
    });
});

describe('StoreClienteRequest Validation', function () {
    it('rechaza cliente sin nombre', function () {
        $response = $this->post(route('clientes.store'), [
            'apellidos' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password123',
            'genero' => 'masculino',
            'edad' => 25,
            'direccion' => 'Calle Test 123',
        ]);

        $response->assertSessionHasErrors('nombre');
    });

    it('rechaza email duplicado', function () {
        User::factory()->create(['email' => 'duplicado@test.com']);

        $response = $this->post(route('clientes.store'), [
            'nombre' => 'Test',
            'apellidos' => 'User',
            'email' => 'duplicado@test.com',
            'password' => 'password123',
            'genero' => 'masculino',
            'edad' => 25,
            'direccion' => 'Calle Test 123',
        ]);

        $response->assertSessionHasErrors('email');
    });

    it('rechaza contraseña menor a 6 caracteres', function () {
        $response = $this->post(route('clientes.store'), [
            'nombre' => 'Test',
            'apellidos' => 'User',
            'email' => 'nuevo@test.com',
            'password' => '12345', // Muy corta
            'genero' => 'masculino',
            'edad' => 25,
            'direccion' => 'Calle Test 123',
        ]);

        $response->assertSessionHasErrors('password');
    });

    it('sanitiza datos de entrada', function () {
        $response = $this->post(route('clientes.store'), [
            'nombre' => '<script>alert("XSS")</script>Juan',
            'apellidos' => 'Pérez',
            'email' => 'juan@test.com',
            'password' => 'password123',
            'genero' => 'masculino',
            'edad' => 30,
            'direccion' => 'Calle <b>Test</b> 123',
        ]);

        $cliente = Cliente::with('user')->latest()->first();
        expect($cliente->user->nombre)->not->toContain('<script>');
        expect($cliente->direccion)->not->toContain('<b>');
    });
});

describe('RegistrarPagoDeudaRequest Validation', function () {
    it('rechaza pago sin monto', function () {
        $cliente = Cliente::factory()->create();
        
        // Crear una deuda primero
        Deuda::create([
            'id_cliente' => $cliente->id,
            'saldo_pendiente' => 100.00,
        ]);

        $response = $this->post(route('deudas.registrar-pago', $cliente), [
            'metodo_pago' => 'efectivo',
            // monto faltante
        ]);

        $response->assertSessionHasErrors('monto');
    });

    it('rechaza monto negativo', function () {
        $cliente = Cliente::factory()->create();
        
        Deuda::create([
            'id_cliente' => $cliente->id,
            'saldo_pendiente' => 100.00,
        ]);

        $response = $this->post(route('deudas.registrar-pago', $cliente), [
            'monto' => -50,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertSessionHasErrors('monto');
    });

    it('rechaza método de pago inválido', function () {
        $cliente = Cliente::factory()->create();
        
        Deuda::create([
            'id_cliente' => $cliente->id,
            'saldo_pendiente' => 100.00,
        ]);

        $response = $this->post(route('deudas.registrar-pago', $cliente), [
            'monto' => 50,
            'metodo_pago' => 'bitcoin', // Método no válido
        ]);

        $response->assertSessionHasErrors('metodo_pago');
    });
});

describe('UpdateCitaRequest Validation', function () {
    it('rechaza estado inválido', function () {
        $cita = Cita::factory()->create();

        $response = $this->put(route('citas.update', $cita), [
            'estado' => 'inexistente',
        ]);

        $response->assertSessionHasErrors('estado');
    });

    it('acepta estados válidos', function () {
        $cita = Cita::factory()->create();

        foreach (['pendiente', 'completada', 'cancelada'] as $estado) {
            $response = $this->put(route('citas.update', $cita), [
                'estado' => $estado,
            ]);

            $response->assertSessionDoesntHaveErrors();
            expect($cita->fresh()->estado)->toBe($estado);
        }
    });
});

describe('UpdateClienteRequest Validation', function () {
    it('rechaza edad mayor a 120', function () {
        $cliente = Cliente::factory()->create();

        $response = $this->put(route('clientes.update', $cliente), [
            'nombre' => $cliente->user->nombre,
            'apellidos' => $cliente->user->apellidos,
            'email' => $cliente->user->email,
            'genero' => $cliente->user->genero,
            'edad' => 150, // Edad inválida
            'direccion' => $cliente->direccion,
            'fecha_registro' => $cliente->fecha_registro,
        ]);

        $response->assertSessionHasErrors('edad');
    });

    it('rechaza fecha de registro futura', function () {
        $cliente = Cliente::factory()->create();

        $response = $this->put(route('clientes.update', $cliente), [
            'nombre' => $cliente->user->nombre,
            'apellidos' => $cliente->user->apellidos,
            'email' => $cliente->user->email,
            'genero' => $cliente->user->genero,
            'edad' => $cliente->user->edad,
            'direccion' => $cliente->direccion,
            'fecha_registro' => now()->addYear()->format('Y-m-d'), // Fecha futura
        ]);

        $response->assertSessionHasErrors('fecha_registro');
    });
});

describe('StoreRegistroCobroRequest Validation', function () {
    it('rechaza cobro sin cita ni cliente', function () {
        $response = $this->post(route('cobros.store'), [
            'coste' => 50,
            'total_final' => 50,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertSessionHasErrors('id_cliente');
    });

    it('rechaza descuento porcentual mayor a 100%', function () {
        $cliente = Cliente::factory()->create();

        $response = $this->post(route('cobros.store'), [
            'id_cliente' => $cliente->id,
            'coste' => 100,
            'descuento_porcentaje' => 150, // Más de 100%
            'total_final' => 50,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertSessionHasErrors('descuento_porcentaje');
    });

    it('rechaza método de pago inválido', function () {
        $cliente = Cliente::factory()->create();

        $response = $this->post(route('cobros.store'), [
            'id_cliente' => $cliente->id,
            'coste' => 50,
            'total_final' => 50,
            'metodo_pago' => 'cripto', // Método inválido
        ]);

        $response->assertSessionHasErrors('metodo_pago');
    });
});
