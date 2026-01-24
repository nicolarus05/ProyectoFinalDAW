<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{RegistroCobro, Cita, Empleado};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FacturacionController extends Controller
{
    /**
     * Mostrar facturación mensual desglosada
     */
    public function index(Request $request)
    {
        Carbon::setLocale('es');
        
        // Obtener mes y año (por defecto el mes actual)
        $mes = $request->get('mes', now()->month);
        $anio = $request->get('anio', now()->year);
        
        $fechaInicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::create($anio, $mes, 1)->endOfMonth();
        
        // ============================================================================
        // USAR EL NUEVO SISTEMA DE FACTURACIÓN POR CATEGORÍA
        // ============================================================================
        $facturacionCategoria = Empleado::facturacionPorCategoriaPorFechas($fechaInicio, $fechaFin);
        
        // Extraer datos por categoría
        $serviciosPeluqueria = $facturacionCategoria['peluqueria']['servicios'];
        $serviciosEstetica = $facturacionCategoria['estetica']['servicios'];
        $productosPeluqueria = $facturacionCategoria['peluqueria']['productos'];
        $productosEstetica = $facturacionCategoria['estetica']['productos'];
        $bonosPeluqueria = $facturacionCategoria['peluqueria']['bonos'];
        $bonosEstetica = $facturacionCategoria['estetica']['bonos'];
        
        // ============================================================================
        // CÁLCULO DE CAJAS DIARIAS
        // ============================================================================
        // Obtener todos los cobros del mes para calcular cajas diarias
        $cobros = RegistroCobro::with(['bonosVendidos'])
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->get();
        
        // Initializar array de cajas diarias con desglose efectivo/tarjeta
        $cajasDiarias = [];
        $diasDelMes = $fechaFin->day;
        
        for ($i = 1; $i <= $diasDelMes; $i++) {
            $fecha = Carbon::create($anio, $mes, $i)->format('Y-m-d');
            $cajasDiarias[$fecha] = [
                'total' => 0,
                'efectivo' => 0,
                'tarjeta' => 0
            ];
        }

        // Procesar cada cobro para cajas diarias
        foreach($cobros as $cobro) {
            if ($cobro->metodo_pago !== 'bono') {
                $fechaCobro = $cobro->created_at->format('Y-m-d');
                if (isset($cajasDiarias[$fechaCobro])) {
                    $montoPagadoServicios = $cobro->total_final;
                    
                    $cajasDiarias[$fechaCobro]['total'] += $montoPagadoServicios;
                    
                    if ($cobro->metodo_pago === 'efectivo') {
                        $cajasDiarias[$fechaCobro]['efectivo'] += $montoPagadoServicios;
                    } elseif ($cobro->metodo_pago === 'tarjeta') {
                        $cajasDiarias[$fechaCobro]['tarjeta'] += $montoPagadoServicios;
                    } elseif ($cobro->metodo_pago === 'mixto') {
                        $cajasDiarias[$fechaCobro]['efectivo'] += $cobro->pago_efectivo ?? 0;
                        $cajasDiarias[$fechaCobro]['tarjeta'] += $cobro->pago_tarjeta ?? 0;
                    } elseif ($cobro->metodo_pago === 'deuda') {
                        if ($montoPagadoServicios > 0) {
                            $cajasDiarias[$fechaCobro]['efectivo'] += $montoPagadoServicios;
                        }
                    }
                    
                    // Sumar bonos vendidos por su propio método de pago
                    if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                        foreach ($cobro->bonosVendidos as $bono) {
                            $metodoPagoBono = $bono->metodo_pago;
                            
                            if ($metodoPagoBono !== 'deuda') {
                                $precioBonoPagado = $bono->precio_pagado ?? 0;
                                $cajasDiarias[$fechaCobro]['total'] += $precioBonoPagado;
                                
                                if ($metodoPagoBono === 'efectivo') {
                                    $cajasDiarias[$fechaCobro]['efectivo'] += $precioBonoPagado;
                                } elseif ($metodoPagoBono === 'tarjeta') {
                                    $cajasDiarias[$fechaCobro]['tarjeta'] += $precioBonoPagado;
                                } elseif ($metodoPagoBono === 'mixto') {
                                    $cajasDiarias[$fechaCobro]['efectivo'] += $precioBonoPagado / 2;
                                    $cajasDiarias[$fechaCobro]['tarjeta'] += $precioBonoPagado / 2;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // ============================================================================
        // CALCULAR BONOS VENDIDOS TOTALES (desglose por categoría incluido)
        // ============================================================================
        $bonosVendidos = $bonosPeluqueria + $bonosEstetica;
        
        // Calcular totales
        $totalServicios = $serviciosPeluqueria + $serviciosEstetica;
        $totalProductos = $productosPeluqueria + $productosEstetica;
        $totalGeneral = $totalServicios + $totalProductos + $bonosVendidos;
        
        // Calcular deuda total del mes (solo deudas pendientes)
        $deudaTotal = $cobros->where('metodo_pago', '!=', 'bono')->sum('deuda');
        
        // Calcular suma de cajas diarias (debe ser igual a totalGeneral - deudaTotal)
        $sumaCajasDiarias = array_sum(array_column($cajasDiarias, 'total'));
        
        // Total realmente cobrado (lo que ingresó en caja)
        $totalRealmenteCobrado = $totalGeneral - $deudaTotal;
        
        // Obtener lista de meses para el selector
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        return view('facturacion.index', compact(
            'serviciosPeluqueria',
            'serviciosEstetica',
            'productosPeluqueria',
            'productosEstetica',
            'bonosPeluqueria',
            'bonosEstetica',
            'bonosVendidos',
            'totalServicios',
            'totalProductos',
            'totalGeneral',
            'deudaTotal',
            'sumaCajasDiarias',
            'totalRealmenteCobrado',
            'mes',
            'anio',
            'meses',
            'fechaInicio',
            'fechaFin',
            'cajasDiarias'
        ));
    }
}
