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
     * - Prioriza servicios de: cita individual > citas agrupadas > servicios directos
     */
    public function desglosarCobroPorCategoria(RegistroCobro $cobro): array
    {
        $resultado = [
            'peluqueria' => $this->estructuraBase(),
            'estetica' => $this->estructuraBase(),
        ];

        // Obtener servicios según prioridad (igual que en desglosarCobroPorEmpleado)
        $servicios = [];
        $yaContados = false;
        
        // PRIORIDAD 1: Servicios de cita individual
        if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
            $servicios = $cobro->cita->servicios;
            $yaContados = true;
        }
        
        // PRIORIDAD 2: Servicios de citas agrupadas
        if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
            foreach ($cobro->citasAgrupadas as $citaGrupo) {
                if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                    foreach ($citaGrupo->servicios as $servicio) {
                        $servicios[] = $servicio;
                    }
                }
            }
            $yaContados = true;
        }
        
        // PRIORIDAD 3: Servicios directos
        if (!$yaContados && $cobro->servicios && $cobro->servicios->count() > 0) {
            $servicios = $cobro->servicios;
        }

        // Calcular suma total de servicios y productos desde pivot
        $sumaPivotServicios = 0;
        $sumaPivotProductos = 0;
        
        foreach ($servicios as $servicio) {
            $precio = $servicio->pivot->precio ?? $servicio->precio ?? 0;
            if ($precio > 0) {
                $sumaPivotServicios += $precio;
            }
        }
        
        if ($cobro->productos) {
            foreach ($cobro->productos as $producto) {
                $sumaPivotProductos += $producto->pivot->subtotal ?? 0;
            }
        }
        
        $sumaPivotTotal = $sumaPivotServicios + $sumaPivotProductos;
        
        // Calcular factor de ajuste
        // Si hay descuento: aplicar proporcionalmente
        // Si hay cargo extra (total_final > sumaPivotTotal): también aplicar proporcionalmente
        $factorAjuste = 1.0;
        if ($sumaPivotTotal > 0.01) {
            $factorAjuste = $cobro->total_final / $sumaPivotTotal;
        }

        /*
        |--------------------------------------------------------------------------
        | SERVICIOS - Por categoría del servicio
        |--------------------------------------------------------------------------
        */
        
        // CASO ESPECIAL: Si sumaPivotTotal es 0 pero hay servicios con total_final > 0
        // Distribuir el total_final proporcionalmente por categoría de servicios
        if ($sumaPivotTotal < 0.01 && $cobro->total_final > 0 && count($servicios) > 0) {
            // Contar servicios por categoría
            $serviciosPorCategoria = ['peluqueria' => 0, 'estetica' => 0];
            foreach ($servicios as $servicio) {
                $categoria = $servicio->categoria ?? 'peluqueria';
                $serviciosPorCategoria[$categoria]++;
            }
            
            $totalServicios = $serviciosPorCategoria['peluqueria'] + $serviciosPorCategoria['estetica'];
            
            if ($totalServicios > 0) {
                // Distribuir proporcionalmente
                foreach (['peluqueria', 'estetica'] as $cat) {
                    if ($serviciosPorCategoria[$cat] > 0) {
                        $proporcion = $serviciosPorCategoria[$cat] / $totalServicios;
                        $resultado[$cat]['servicios'] += $cobro->total_final * $proporcion;
                    }
                }
            }
        } else {
            // Caso normal: aplicar factor de ajuste
            foreach ($servicios as $servicio) {
                $precio = $servicio->pivot->precio ?? $servicio->precio ?? 0;
                if ($precio > 0) {
                    $categoria = $servicio->categoria ?? 'peluqueria'; // Default si no tiene
                    $precioAjustado = $precio * $factorAjuste;
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
            foreach ($cobro->bonosVendidos as $bono) {
                // Solo contar bonos que NO quedaron a deber
                if ($bono->metodo_pago !== 'deuda') {
                    // Obtener categoría del bono_plantilla
                    $categoria = $bono->bonoPlantilla->categoria ?? 'peluqueria';
                    
                    // Contar el precio pagado (lo cobrado realmente)
                    $precioPagado = $bono->precio_pagado ?? 0;
                    $resultado[$categoria]['bonos'] += $precioPagado;
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