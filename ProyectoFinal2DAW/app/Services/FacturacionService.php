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
        
        // Calcular factor de ajuste proporcional
        // Aplica tanto para descuentos (factor < 1) como recargos (factor > 1)
        // Unificado con desglosarCobroPorCategoria()
        $factorAjuste = 1.0;
        if ($sumaPivotTotal > 0.01) {
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
     * - Usa $cobro->servicios (registro_cobro_servicio) como fuente principal (consistente con desglosarCobroPorEmpleado)
     * - Aplica el mismo factor de ajuste proporcional que desglosarCobroPorEmpleado
     * - Para bonos vendidos: usa la categoría del bono_plantilla
     * - Fallback: si el pivot está vacío, intenta cita/citasAgrupadas para distribución por categoría
     */
    public function desglosarCobroPorCategoria(RegistroCobro $cobro): array
    {
        $resultado = [
            'peluqueria' => $this->estructuraBase(),
            'estetica' => $this->estructuraBase(),
        ];

        // FUENTE PRINCIPAL: Usar $cobro->servicios (tabla registro_cobro_servicio)
        // Esta es la misma fuente que desglosarCobroPorEmpleado() y tiene los precios correctos
        $servicios = $cobro->servicios ?? collect();

        // Calcular suma total de servicios y productos desde pivot
        $sumaPivotServicios = 0;
        $sumaPivotProductos = 0;
        
        foreach ($servicios as $servicio) {
            if ($servicio->pivot->precio > 0) {
                $sumaPivotServicios += $servicio->pivot->precio;
            }
        }
        
        if ($cobro->productos) {
            foreach ($cobro->productos as $producto) {
                $sumaPivotProductos += $producto->pivot->subtotal ?? 0;
            }
        }
        
        $sumaPivotTotal = $sumaPivotServicios + $sumaPivotProductos;
        
        // Calcular factor de ajuste (unificado con desglosarCobroPorEmpleado)
        $factorAjuste = 1.0;
        if ($sumaPivotTotal > 0.01) {
            $factorAjuste = $cobro->total_final / $sumaPivotTotal;
        }

        /*
        |--------------------------------------------------------------------------
        | SERVICIOS - Por categoría del servicio
        |--------------------------------------------------------------------------
        */
        
        // CASO ESPECIAL: Si el pivot no tiene servicios con precio pero total_final > 0
        // Esto ocurre con datos legacy o cobros donde todos los servicios son de bono (precio=0)
        // Fallback: intentar obtener servicios desde cita/citasAgrupadas para distribuir por categoría
        if ($sumaPivotTotal < 0.01 && $cobro->total_final > 0) {
            // Intentar obtener servicios desde cualquier fuente para saber la categoría
            $serviciosFallback = collect();
            
            if ($cobro->servicios && $cobro->servicios->count() > 0) {
                $serviciosFallback = $cobro->servicios;
            } elseif ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                $serviciosFallback = $cobro->cita->servicios;
            } elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                foreach ($cobro->citasAgrupadas as $citaGrupo) {
                    if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                        foreach ($citaGrupo->servicios as $s) {
                            $serviciosFallback->push($s);
                        }
                    }
                }
            }

            if ($serviciosFallback->count() > 0) {
                // Contar servicios por categoría y distribuir proporcionalmente
                $serviciosPorCategoria = ['peluqueria' => 0, 'estetica' => 0];
                foreach ($serviciosFallback as $servicio) {
                    $categoria = $servicio->categoria ?? 'peluqueria';
                    $serviciosPorCategoria[$categoria]++;
                }
                
                $totalServicios = $serviciosPorCategoria['peluqueria'] + $serviciosPorCategoria['estetica'];
                
                if ($totalServicios > 0) {
                    foreach (['peluqueria', 'estetica'] as $cat) {
                        if ($serviciosPorCategoria[$cat] > 0) {
                            $proporcion = $serviciosPorCategoria[$cat] / $totalServicios;
                            $resultado[$cat]['servicios'] += $cobro->total_final * $proporcion;
                        }
                    }
                }
            }
        } else {
            // Caso normal: aplicar factor de ajuste con precios del pivot
            foreach ($servicios as $servicio) {
                if ($servicio->pivot->precio > 0) {
                    $categoria = $servicio->categoria ?? 'peluqueria';
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