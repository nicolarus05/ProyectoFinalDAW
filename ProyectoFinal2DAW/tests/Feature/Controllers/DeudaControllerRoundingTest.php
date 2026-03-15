<?php

use App\Http\Controllers\DeudaController;

it('distribuye montos proporcionales sin perder centimos', function () {
    $controller = new class extends DeudaController {
        public function distribuirPublic(array $lineas, float $monto): array
        {
            return $this->distribuirImportesProporcionalmente($lineas, $monto);
        }
    };

    $lineas = [
        ['id' => 's1', 'referencia' => 100.0],
        ['id' => 'p1', 'referencia' => 100.0],
        ['id' => 'p2', 'referencia' => 100.0],
    ];

    $resultado = $controller->distribuirPublic($lineas, 10.00);

    expect(round(array_sum($resultado), 2))->toBe(10.00)
        ->and($resultado['s1'])->toBeGreaterThanOrEqual(0)
        ->and($resultado['p1'])->toBeGreaterThanOrEqual(0)
        ->and($resultado['p2'])->toBeGreaterThanOrEqual(0);

    // Distribucion esperada sin drift para 10 EUR entre 3 lineas iguales.
    $valores = array_values($resultado);
    sort($valores);
    expect($valores)->toBe([3.33, 3.33, 3.34]);
});

it('asigna 0 cuando no hay referencia total valida', function () {
    $controller = new class extends DeudaController {
        public function distribuirPublic(array $lineas, float $monto): array
        {
            return $this->distribuirImportesProporcionalmente($lineas, $monto);
        }
    };

    $lineas = [
        ['id' => 'a', 'referencia' => 0.0],
        ['id' => 'b', 'referencia' => 0.0],
    ];

    $resultado = $controller->distribuirPublic($lineas, 12.34);

    expect($resultado)->toBe([
        'a' => 0.0,
        'b' => 0.0,
    ]);
});
