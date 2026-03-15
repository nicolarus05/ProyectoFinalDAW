<?php

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Productos;
use App\Models\RegistroCobro;
use App\Models\Servicio;
use App\Services\FacturacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('FacturacionService', function () {
    test('descuento de productos no reduce facturacion de servicios por empleado', function () {
        $empleadoServicio = Empleado::factory()->create();
        $empleadoProducto = Empleado::factory()->create();
        $cliente = Cliente::factory()->create();

        $servicio = Servicio::factory()->create([
            'precio' => 100.00,
            'categoria' => 'peluqueria',
        ]);

        $producto = Productos::factory()->create([
            'precio_venta' => 100.00,
            'precio_coste' => 50.00,
            'categoria' => 'estetica',
            'stock' => 10,
        ]);

        $cobro = RegistroCobro::create([
            'id_cita' => null,
            'id_cliente' => $cliente->id,
            'id_empleado' => $empleadoServicio->id,
            'coste' => 100.00,
            'descuento_productos_porcentaje' => 0,
            'descuento_productos_euro' => 20.00,
            'total_final' => 180.00,
            'metodo_pago' => 'efectivo',
            'dinero_cliente' => 180.00,
            'cambio' => 0,
            'deuda' => 0,
        ]);

        $cobro->servicios()->attach($servicio->id, [
            'empleado_id' => $empleadoServicio->id,
            'precio' => 100.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cobro->productos()->attach($producto->id, [
            'cantidad' => 1,
            'precio_unitario' => 100.00,
            'subtotal' => 100.00,
            'empleado_id' => $empleadoProducto->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = new FacturacionService();
        $resultado = $service->desglosarCobroPorEmpleado(
            $cobro->fresh(['servicios', 'productos', 'bonosVendidos'])
        );

        expect($resultado)->toHaveKey($empleadoServicio->id)
            ->and($resultado)->toHaveKey($empleadoProducto->id);

        expect(round($resultado[$empleadoServicio->id]['servicios'], 2))->toBe(100.00)
            ->and(round($resultado[$empleadoProducto->id]['productos'], 2))->toBe(80.00)
            ->and(round($resultado[$empleadoServicio->id]['total'], 2))->toBe(100.00)
            ->and(round($resultado[$empleadoProducto->id]['total'], 2))->toBe(80.00);
    });
});
