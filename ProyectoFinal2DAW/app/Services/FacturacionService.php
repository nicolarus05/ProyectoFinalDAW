<?php

namespace App\Services;

use App\Models\RegistroCobro;

class FacturacionService
{
    /**
     * Desglosa un cobro por empleado aplicando descuentos proporcionalmente
     * LÓGICA: 
     * - Solo facturar servicios/productos con precio > 0 en el pivot
     * - Si hay descuento (total_final < suma_pivot), aplicar factor proporcional
     * - Factor = total_final / suma_pivot_total
     */
    public function desglosarCobroPorEmpleado(RegistroCobro $cobro): array
    {
        $resultado = [];

        // Calcular suma total de servicios y productos desde pivot
        $sumaPivotServicios = 0;
        $sumaPivotProductos = 0;
        
        if ($cobro->servicios) {
            foreach ($cobro->servicios as $servicio) {
                if ($servicio->pivot->precio > 0) {
                    $sumaPivotServicios += $servicio->pivot->precio;
                }
            }
        }
        
        if ($cobro->productos) {
            foreach ($cobro->productos as $producto) {
                $sumaPivotProductos += $producto->pivot->subtotal;
            }
        }
        
        $sumaPivotTotal = $sumaPivotServicios + $sumaPivotProductos;
        
        // Calcular factor de ajuste si hay descuento
        // Si total_final < suma_pivot, hay descuento aplicado
        $factorAjuste = 1.0;
        if ($sumaPivotTotal > 0 && $cobro->total_final < $sumaPivotTotal - 0.01) {
            $factorAjuste = $cobro->total_final / $sumaPivotTotal;
        }

        /*
        |--------------------------------------------------------------------------
        | SERVICIOS - Con ajuste proporcional por descuento
        | Los servicios en deuda NO están en el pivot
        | Los servicios pagados con bono tienen precio = 0 (no facturar)
        |--------------------------------------------------------------------------
        */
        if ($cobro->servicios) {
            foreach ($cobro->servicios as $servicio) {
                // Solo facturar servicios con precio > 0
                if ($servicio->pivot->precio > 0) {
                    $empleadoId = $servicio->pivot->empleado_id;

                    if (!isset($resultado[$empleadoId])) {
                        $resultado[$empleadoId] = $this->estructuraBase();
                    }

                    // Aplicar factor de ajuste si hay descuento
                    $precioAjustado = $servicio->pivot->precio * $factorAjuste;
                    $resultado[$empleadoId]['servicios'] += $precioAjustado;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PRODUCTOS - Con ajuste proporcional por descuento
        | Los productos en deuda NO están en el pivot
        |--------------------------------------------------------------------------
        */
        if ($cobro->productos) {
            foreach ($cobro->productos as $producto) {
                // Si el pivot tiene empleado_id, usar ese; si no, usar el empleado del cobro
                $empleadoId = $producto->pivot->empleado_id ?? $cobro->id_empleado;

                if (!isset($resultado[$empleadoId])) {
                    $resultado[$empleadoId] = $this->estructuraBase();
                }

                // Aplicar factor de ajuste si hay descuento
                $precioAjustado = $producto->pivot->subtotal * $factorAjuste;
                $resultado[$empleadoId]['productos'] += $precioAjustado;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BONOS VENDIDOS - Asignados al empleado que cobra
        | IMPORTANTE: Solo se contabilizan los bonos que se cobraron
        | Si el bono está en deuda (dinero_cliente < total_bonos_vendidos), NO se factura
        |--------------------------------------------------------------------------
        */
        if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
            // Verificar que el cliente pagó los bonos (no están en deuda)
            // Solo facturar bonos si: dinero_cliente >= (total_final + total_bonos_vendidos)
            $totalCobrado = $cobro->total_final + ($cobro->total_bonos_vendidos ?? 0);
            $dineroRecibido = $cobro->dinero_cliente ?? 0;
            
            // Si el dinero recibido cubre el total (servicios/productos + bonos), facturar bonos
            if ($dineroRecibido >= $totalCobrado - 0.01) {
                $empleadoId = $cobro->id_empleado;

                if (!isset($resultado[$empleadoId])) {
                    $resultado[$empleadoId] = $this->estructuraBase();
                }

                foreach ($cobro->bonosVendidos as $bono) {
                    $resultado[$empleadoId]['bonos'] += $bono->pivot->precio;
                }
            }
            // Si hay deuda, los bonos NO se facturan hasta que se cobre la deuda
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

    /**
     * Desglosa un cobro por categoría (peluqueria/estetica) aplicando descuentos proporcionalmente
     * LÓGICA:
     * - Agrupa servicios, productos y bonos según su categoría
     * - Aplica el mismo factor de ajuste proporcional que desglosarCobroPorEmpleado
     * - Para bonos vendidos: usa la categoría del bono_plantilla
     */
    public function desglosarCobroPorCategoria(RegistroCobro $cobro): array
    {
        $resultado = [
            'peluqueria' => $this->estructuraBase(),
            'estetica' => $this->estructuraBase(),
        ];

        // Calcular suma total de servicios y productos desde pivot
        $sumaPivotServicios = 0;
        $sumaPivotProductos = 0;
        
        if ($cobro->servicios) {
            foreach ($cobro->servicios as $servicio) {
                if ($servicio->pivot->precio > 0) {
                    $sumaPivotServicios += $servicio->pivot->precio;
                }
            }
        }
        
        if ($cobro->productos) {
            foreach ($cobro->productos as $producto) {
                $sumaPivotProductos += $producto->pivot->subtotal;
            }
        }
        
        $sumaPivotTotal = $sumaPivotServicios + $sumaPivotProductos;
        
        // Calcular factor de ajuste si hay descuento
        $factorAjuste = 1.0;
        if ($sumaPivotTotal > 0 && $cobro->total_final < $sumaPivotTotal - 0.01) {
            $factorAjuste = $cobro->total_final / $sumaPivotTotal;
        }

        /*
        |--------------------------------------------------------------------------
        | SERVICIOS - Por categoría del servicio
        |--------------------------------------------------------------------------
        */
        if ($cobro->servicios) {
            foreach ($cobro->servicios as $servicio) {
                if ($servicio->pivot->precio > 0) {
                    $categoria = $servicio->categoria ?? 'peluqueria'; // Default si no tiene
                    $precioAjustado = $servicio->pivot->precio * $factorAjuste;
                    $resultado[$categoria]['servicios'] += $precioAjustado;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PRODUCTOS - Por categoría del producto
        |--------------------------------------------------------------------------
        */
        if ($cobro->productos) {
            foreach ($cobro->productos as $producto) {
                $categoria = $producto->categoria ?? 'peluqueria'; // Default si no tiene
                $precioAjustado = $producto->pivot->subtotal * $factorAjuste;
                $resultado[$categoria]['productos'] += $precioAjustado;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BONOS VENDIDOS - Por categoría del bono_plantilla
        |--------------------------------------------------------------------------
        */
        if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
            $totalCobrado = $cobro->total_final + ($cobro->total_bonos_vendidos ?? 0);
            $dineroRecibido = $cobro->dinero_cliente ?? 0;
            
            if ($dineroRecibido >= $totalCobrado - 0.01) {
                foreach ($cobro->bonosVendidos as $bono) {
                    // Obtener categoría del bono_plantilla
                    $categoria = $bono->bonoPlantilla->categoria ?? 'peluqueria'; // Default si no tiene
                    $resultado[$categoria]['bonos'] += $bono->pivot->precio;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */
        foreach ($resultado as $categoria => $datos) {
            $resultado[$categoria]['total'] =
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