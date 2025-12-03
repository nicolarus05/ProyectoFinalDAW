<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroCobro;
use App\Models\Cita;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FacturacionController extends Controller
{
    /**
     * Mostrar facturación mensual desglosada
     */
    public function index(Request $request)
    {
        // Obtener mes y año (por defecto el mes actual)
        $mes = $request->get('mes', now()->month);
        $anio = $request->get('anio', now()->year);
        
        $fechaInicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::create($anio, $mes, 1)->endOfMonth();
        
        // Obtener todos los cobros del mes (por fecha de cobro, no fecha de cita)
        $cobros = RegistroCobro::with(['cita.servicios', 'citasAgrupadas.servicios', 'servicios', 'productos'])
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->get();
        
        // Inicializar contadores
        $serviciosPeluqueria = 0;
        $serviciosEstetica = 0;
        $productosPeluqueria = 0;
        $productosEstetica = 0;
        
        // Procesar cada cobro con sistema de prioridades
        foreach($cobros as $cobro) {
            $yaContados = false;
            
            // PRIORIDAD 1: Servicios de cita individual
            if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                foreach($cobro->cita->servicios as $servicio) {
                    $precio = $servicio->pivot->precio ?? $servicio->precio;
                    
                    // Calcular precio real aplicando descuentos proporcionalmente
                    $proporcion = $cobro->coste > 0 ? ($precio / $cobro->coste) : 0;
                    $precioReal = $cobro->total_final * $proporcion;
                    
                    // Solo sumar si NO es pago con bono
                    if ($cobro->metodo_pago !== 'bono') {
                        if ($servicio->categoria === 'peluqueria') {
                            $serviciosPeluqueria += $precioReal;
                        } elseif ($servicio->categoria === 'estetica') {
                            $serviciosEstetica += $precioReal;
                        }
                    }
                }
                $yaContados = true;
            }
            
            // PRIORIDAD 2: Servicios de citas agrupadas (solo si no tiene cita individual)
            if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                foreach($cobro->citasAgrupadas as $citaGrupo) {
                    if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                        foreach($citaGrupo->servicios as $servicio) {
                            $precio = $servicio->pivot->precio ?? $servicio->precio;
                            
                            $proporcion = $cobro->coste > 0 ? ($precio / $cobro->coste) : 0;
                            $precioReal = $cobro->total_final * $proporcion;
                            
                            if ($cobro->metodo_pago !== 'bono') {
                                if ($servicio->categoria === 'peluqueria') {
                                    $serviciosPeluqueria += $precioReal;
                                } elseif ($servicio->categoria === 'estetica') {
                                    $serviciosEstetica += $precioReal;
                                }
                            }
                        }
                    }
                }
                $yaContados = true;
            }
            
            // PRIORIDAD 3: Servicios directos (solo si no tiene citas)
            if (!$yaContados && $cobro->servicios && $cobro->servicios->count() > 0) {
                foreach($cobro->servicios as $servicio) {
                    $precio = $servicio->pivot->precio ?? $servicio->precio;
                    
                    $proporcion = $cobro->coste > 0 ? ($precio / $cobro->coste) : 0;
                    $precioReal = $cobro->total_final * $proporcion;
                    
                    if ($cobro->metodo_pago !== 'bono') {
                        if ($servicio->categoria === 'peluqueria') {
                            $serviciosPeluqueria += $precioReal;
                        } elseif ($servicio->categoria === 'estetica') {
                            $serviciosEstetica += $precioReal;
                        }
                    }
                }
            }
            
            // PRODUCTOS
            if ($cobro->productos) {
                foreach($cobro->productos as $producto) {
                    $subtotal = $producto->pivot->subtotal ?? 0;
                    
                    // Calcular precio real aplicando descuentos proporcionalmente
                    $proporcion = $cobro->coste > 0 ? ($subtotal / $cobro->coste) : 0;
                    $subtotalReal = $cobro->total_final * $proporcion;
                    
                    // Solo sumar si NO es pago con bono
                    if ($cobro->metodo_pago !== 'bono') {
                        if ($producto->categoria === 'peluqueria') {
                            $productosPeluqueria += $subtotalReal;
                        } elseif ($producto->categoria === 'estetica') {
                            $productosEstetica += $subtotalReal;
                        }
                    }
                }
            }
        }
        
        // BONOS - Desde bonos_clientes
        $bonosVendidos = DB::table('bonos_clientes')
            ->whereBetween('fecha_compra', [$fechaInicio, $fechaFin])
            ->sum('precio_pagado');
        
        // Calcular totales
        $totalServicios = $serviciosPeluqueria + $serviciosEstetica;
        $totalProductos = $productosPeluqueria + $productosEstetica;
        $totalGeneral = $totalServicios + $totalProductos + $bonosVendidos;
        
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
            'bonosVendidos',
            'totalServicios',
            'totalProductos',
            'totalGeneral',
            'mes',
            'anio',
            'meses',
            'fechaInicio',
            'fechaFin'
        ));
    }
}
