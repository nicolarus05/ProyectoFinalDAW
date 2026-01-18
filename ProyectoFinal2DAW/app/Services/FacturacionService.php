<?php

namespace App\Services;

use App\Models\RegistroCobro;

class FacturacionService
{
    /**
     * Desglosa un cobro por empleado aplicando descuentos proporcionalmente
     */
    public function desglosarCobroPorEmpleado(RegistroCobro $cobro): array
    {
        $resultado = [];

        /*
        |--------------------------------------------------------------------------
        | SERVICIOS - Precios exactos sin descuentos proporcionados
        | Excluir servicios pagados con bono (precio = 0 en pivot)
        |--------------------------------------------------------------------------
        */
        if ($cobro->servicios) {
            foreach ($cobro->servicios as $servicio) {
                // Solo facturar servicios con precio > 0 (excluir los pagados con bono)
                if ($servicio->pivot->precio > 0) {
                    $empleadoId = $servicio->pivot->empleado_id;

                    if (!isset($resultado[$empleadoId])) {
                        $resultado[$empleadoId] = $this->estructuraBase();
                    }

                    $resultado[$empleadoId]['servicios'] += $servicio->pivot->precio;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PRODUCTOS - Precios exactos asignados al empleado que cobra
        |--------------------------------------------------------------------------
        */
        if ($cobro->productos) {
            foreach ($cobro->productos as $producto) {
                $empleadoId = $cobro->id_empleado;

                if (!isset($resultado[$empleadoId])) {
                    $resultado[$empleadoId] = $this->estructuraBase();
                }

                $resultado[$empleadoId]['productos'] += $producto->pivot->subtotal;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BONOS VENDIDOS - Asignados al empleado que cobra
        |--------------------------------------------------------------------------
        */
        if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
            $empleadoId = $cobro->id_empleado;

            if (!isset($resultado[$empleadoId])) {
                $resultado[$empleadoId] = $this->estructuraBase();
            }

            foreach ($cobro->bonosVendidos as $bono) {
                $resultado[$empleadoId]['bonos'] += $bono->pivot->precio;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */
        foreach ($resultado as $empleadoId => $datos) {
            $resultado[$empleadoId]['total'] =
                $datos['servicios'] +
                $datos['productos'] +
                $datos['bonos'];
        }

        return $resultado;
    }

    private function estructuraBase(): array
    {
        return [
            'servicios' => 0.0,
            'productos' => 0.0,
            'bonos'     => 0.0,
            'total'     => 0.0,
        ];
    }
}