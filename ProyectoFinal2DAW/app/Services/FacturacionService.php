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
        
        ['servicios' => $factorServicios, 'productos' => $factorProductos] =
            $this->calcularFactoresAjuste($cobro, $sumaPivotServicios, $sumaPivotProductos);

        $sumaPivotTotal = $sumaPivotServicios + $sumaPivotProductos;

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

                    // Ajuste específico para servicios (evita mezclar descuentos de productos)
                    $precioAjustado = $servicio->pivot->precio * $factorServicios;
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

                // Ajuste específico para productos
                $precioAjustado = $producto->pivot->subtotal * $factorProductos;
                $resultado[$empleadoId]['productos'] += $precioAjustado;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BONOS VENDIDOS - Asignados al empleado del bono
        | IMPORTANTE: Solo se contabilizan los bonos que se cobraron realmente
        | Si el bono quedó a deber (metodo_pago='deuda'), NO se factura
        | EXCEPCIÓN: En cobros de pago de deuda (abono), SÍ se factura
        | usando el precio del pivot (lo cobrado en ese pago)
        |--------------------------------------------------------------------------
        */
        if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
            $esCobroPagoDeuda = \Illuminate\Support\Facades\DB::table('movimientos_deuda')
                ->where('id_registro_cobro', $cobro->id)
                ->where('tipo', 'abono')
                ->exists();

            foreach ($cobro->bonosVendidos as $bono) {
                if ($esCobroPagoDeuda) {
                    // Cobro de pago de deuda: contar bonos usando pivot precio
                    $empleadoId = $bono->id_empleado ?? $cobro->id_empleado;

                    if (!isset($resultado[$empleadoId])) {
                        $resultado[$empleadoId] = $this->estructuraBase();
                    }

                    $resultado[$empleadoId]['bonos'] += $bono->pivot->precio ?? 0;
                } elseif ($bono->metodo_pago !== 'deuda') {
                    // Cobro normal: solo contar bonos que NO quedaron a deber
                    $empleadoId = $bono->id_empleado ?? $cobro->id_empleado;

                    if (!isset($resultado[$empleadoId])) {
                        $resultado[$empleadoId] = $this->estructuraBase();
                    }

                    $resultado[$empleadoId]['bonos'] += $bono->precio_pagado ?? 0;
                }
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
        
        ['servicios' => $factorServicios, 'productos' => $factorProductos] =
            $this->calcularFactoresAjuste($cobro, $sumaPivotServicios, $sumaPivotProductos);

        $sumaPivotTotal = $sumaPivotServicios + $sumaPivotProductos;

        // Verificar si hay bonos que serán contados en la sección de bonos más abajo.
        // Si los hay, NO debemos distribuir total_final en el fallback (evita doble conteo).
        $tieneBonos = $cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0;

        /*
        |--------------------------------------------------------------------------
        | SERVICIOS - Por categoría del servicio
        |--------------------------------------------------------------------------
        */
        
        // CASO ESPECIAL: Si el pivot no tiene servicios con precio pero total_final > 0
        // Esto ocurre con datos legacy o cobros donde todos los servicios son de bono (precio=0)
        // Fallback: intentar obtener servicios desde cita/citasAgrupadas para distribuir por categoría
        // IMPORTANTE: No activar si hay bonos vendidos, ya que se contarán en la sección de bonos
        if ($sumaPivotTotal < 0.01 && $cobro->total_final > 0 && !$tieneBonos) {
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
                // Sumar precio de servicios por categoría y distribuir proporcionalmente por PRECIO
                $serviciosPorCategoria = ['peluqueria' => 0, 'estetica' => 0];
                foreach ($serviciosFallback as $servicio) {
                    $categoria = $servicio->categoria ?? 'peluqueria';
                    $serviciosPorCategoria[$categoria] += $servicio->precio;
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
                    $precioAjustado = $servicio->pivot->precio * $factorServicios;
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
                $precioAjustado = $producto->pivot->subtotal * $factorProductos;
                $resultado[$categoria]['productos'] += $precioAjustado;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BONOS VENDIDOS - Por categoría del bono_plantilla
        |--------------------------------------------------------------------------
        */
        if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
            $esCobroPagoDeudaCat = \Illuminate\Support\Facades\DB::table('movimientos_deuda')
                ->where('id_registro_cobro', $cobro->id)
                ->where('tipo', 'abono')
                ->exists();

            foreach ($cobro->bonosVendidos as $bono) {
                if ($esCobroPagoDeudaCat) {
                    // Cobro de pago de deuda: contar bonos usando pivot precio
                    $categoria = $bono->bonoPlantilla->categoria ?? 'peluqueria';
                    $resultado[$categoria]['bonos'] += $bono->pivot->precio ?? 0;
                } elseif ($bono->metodo_pago !== 'deuda') {
                    // Cobro normal: solo contar bonos que NO quedaron a deber
                    $categoria = $bono->bonoPlantilla->categoria ?? 'peluqueria';
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

    /**
     * Calcula factores de ajuste separados por categoria para evitar que
     * descuentos de productos afecten a servicios (y viceversa).
     */
    private function calcularFactoresAjuste(RegistroCobro $cobro, float $sumaPivotServicios, float $sumaPivotProductos): array
    {
        $descServPct = (float) ($cobro->descuento_servicios_porcentaje ?? 0);
        $descServEur = (float) ($cobro->descuento_servicios_euro ?? 0);
        $descProdPct = (float) ($cobro->descuento_productos_porcentaje ?? 0);
        $descProdEur = (float) ($cobro->descuento_productos_euro ?? 0);
        $descGenPct = (float) ($cobro->descuento_porcentaje ?? 0);
        $descGenEur = (float) ($cobro->descuento_euro ?? 0);

        $hayDescServicios = ($descServPct > 0.01 || $descServEur > 0.01);
        $hayDescProductos = ($descProdPct > 0.01 || $descProdEur > 0.01);
        $hayDescGeneralLegacy = ($descGenPct > 0.01 || $descGenEur > 0.01) && !$hayDescServicios && !$hayDescProductos;

        $objetivoServicios = $sumaPivotServicios;
        $objetivoProductos = $sumaPivotProductos;

        if ($hayDescServicios) {
            $objetivoServicios = max(0.0, $sumaPivotServicios - ($sumaPivotServicios * ($descServPct / 100)) - $descServEur);
        } elseif ($hayDescGeneralLegacy) {
            $objetivoServicios = max(0.0, $sumaPivotServicios - ($sumaPivotServicios * ($descGenPct / 100)) - $descGenEur);
        }

        if ($hayDescProductos) {
            $objetivoProductos = max(0.0, $sumaPivotProductos - ($sumaPivotProductos * ($descProdPct / 100)) - $descProdEur);
        }

        // Fallback para datos legacy donde pivots y total_final no cuadren exactamente.
        $objetivoTotal = $objetivoServicios + $objetivoProductos;
        if ($objetivoTotal > 0.01) {
            $diferencia = abs(((float) $cobro->total_final) - $objetivoTotal);
            if ($diferencia > 0.01) {
                $factorGlobal = ((float) $cobro->total_final) / $objetivoTotal;
                $objetivoServicios *= $factorGlobal;
                $objetivoProductos *= $factorGlobal;
            }
        }

        $factorServicios = $sumaPivotServicios > 0.01 ? ($objetivoServicios / $sumaPivotServicios) : 1.0;
        $factorProductos = $sumaPivotProductos > 0.01 ? ($objetivoProductos / $sumaPivotProductos) : 1.0;

        return [
            'servicios' => $factorServicios,
            'productos' => $factorProductos,
        ];
    }
}