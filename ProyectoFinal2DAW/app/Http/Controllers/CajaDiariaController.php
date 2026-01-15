<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroCobro;
use App\Models\BonoCliente;
use Carbon\Carbon;

class CajaDiariaController extends Controller{
    public function index(Request $request){
        
        // Fecha que queremos ver Por defecto hoy.
        $fecha = $request->input('fecha', Carbon::today()->toDateString());

        // Totales por metodo de pago - SOLO lo que realmente se pagó
        // Inicializamos totales
        $totalEfectivo = 0;
        $totalTarjeta = 0;
        $totalBono = 0;
        
        // Obtenemos todos los cobros del día con TODAS las relaciones necesarias
        $cobrosDelDia = RegistroCobro::with([
            'servicios',
            'productos',
            'cita.servicios',
            'citasAgrupadas.servicios'
        ])
            ->whereDate('created_at', $fecha)
            ->get();
        
        // Calculamos totales por método de pago considerando productos Y bonos vendidos
        foreach($cobrosDelDia as $cobro) {
            // Monto de servicios/productos pagado (restando deuda)
            $montoPagadoServicios = $cobro->total_final - $cobro->deuda;
            
            // Monto de bonos vendidos en este cobro
            $montoBonosVendidos = $cobro->total_bonos_vendidos ?? 0;
            
            // Total completo = servicios/productos + bonos vendidos
            $totalCompleto = $montoPagadoServicios + $montoBonosVendidos;
            
            if ($cobro->metodo_pago === 'efectivo') {
                $totalEfectivo += $totalCompleto;
            } elseif ($cobro->metodo_pago === 'tarjeta') {
                $totalTarjeta += $totalCompleto;
            } elseif ($cobro->metodo_pago === 'mixto') {
                // Para pagos mixtos, distribuir bonos proporcionalmente
                $efectivoServicios = $cobro->pago_efectivo ?? 0;
                $tarjetaServicios = $cobro->pago_tarjeta ?? 0;
                $totalPagadoServicios = $efectivoServicios + $tarjetaServicios;
                
                if ($montoBonosVendidos > 0 && $totalPagadoServicios > 0) {
                    $proporcionEfectivo = $efectivoServicios / $totalPagadoServicios;
                    $proporcionTarjeta = $tarjetaServicios / $totalPagadoServicios;
                    
                    $totalEfectivo += $efectivoServicios + ($montoBonosVendidos * $proporcionEfectivo);
                    $totalTarjeta += $tarjetaServicios + ($montoBonosVendidos * $proporcionTarjeta);
                } else {
                    $totalEfectivo += $efectivoServicios;
                    $totalTarjeta += $tarjetaServicios;
                }
            } elseif ($cobro->metodo_pago === 'bono') {
                $totalBono += $cobro->coste;
            } elseif ($cobro->metodo_pago === 'deuda') {
                // Si es deuda, no sumamos nada porque no se recibió dinero
                continue;
            }
        }

        // Los bonos ya están incluidos en los totales de efectivo y tarjeta calculados arriba
        // Ya no necesitamos procesamiento adicional de bonos
        $totalBonosVendidos = $cobrosDelDia->sum('total_bonos_vendidos');
        $totalBonosEfectivo = 0;
        $totalBonosTarjeta = 0;
        
        // Calcular desglose de bonos vendidos para mostrar en la vista (solo informativo)
        foreach($cobrosDelDia as $cobro) {
            $montoBonosVendidos = $cobro->total_bonos_vendidos ?? 0;
            if ($montoBonosVendidos > 0) {
                if ($cobro->metodo_pago === 'efectivo') {
                    $totalBonosEfectivo += $montoBonosVendidos;
                } elseif ($cobro->metodo_pago === 'tarjeta') {
                    $totalBonosTarjeta += $montoBonosVendidos;
                } elseif ($cobro->metodo_pago === 'mixto') {
                    $efectivo = $cobro->pago_efectivo ?? 0;
                    $tarjeta = $cobro->pago_tarjeta ?? 0;
                    $total = $efectivo + $tarjeta;
                    if ($total > 0) {
                        $totalBonosEfectivo += $montoBonosVendidos * ($efectivo / $total);
                        $totalBonosTarjeta += $montoBonosVendidos * ($tarjeta / $total);
                    }
                }
            }
        }

        // Total pagado: lo que realmente ingresó en caja (ya incluye bonos vendidos)
        $totalPagado = $totalEfectivo + $totalTarjeta;

        // Total de servicios realizados (incluye todo, pagado y no pagado)
        $totalServicios = $cobrosDelDia->sum('total_final');

        // Total de deuda del día (dinero que quedó pendiente)
        $totalDeuda = $cobrosDelDia->sum('deuda');

        // Clientes que han dejado deuda ese día
        $deudas = RegistroCobro::with(['cliente.user', 'cita.cliente.user', 'cita.servicios'])
            ->whereDate('created_at', $fecha)
            ->where('deuda', '>', 0)
            ->get();

        // Detalle de servicios: cliente, servicios (de la cita), empleado, metodo_pago, dinero_pagado, deuda
        $detalleServicios = RegistroCobro::with([
            'cliente.user', 
            'empleado.user', 
            'cita.servicios', 
            'cita.empleado.user', 
            'cita.cliente.user',
            'cita',
            'citasAgrupadas.servicios',
            'citasAgrupadas.empleado.user',
            'citasAgrupadas.cliente.user',
            'servicios',
            'productos'
        ])
            ->whereDate('created_at', $fecha)
            ->orderBy('created_at', 'desc')
            ->get();

        // Detalle de bonos vendidos
        $bonosVendidos = BonoCliente::with(['cliente.user', 'empleado.user', 'plantilla'])
            ->whereDate('fecha_compra', $fecha)
            ->orderBy('fecha_compra', 'desc')
            ->get();

        // Calcular totales por categoría y método de pago
        $totalPeluqueria = 0;
        $totalEstetica = 0;
        $totalPeluqueriaEfectivo = 0;
        $totalPeluqueriaTarjeta = 0;
        $totalPeluqueriaBono = 0;
        $totalEsteticaEfectivo = 0;
        $totalEsteticaTarjeta = 0;
        $totalEsteticaBono = 0;

        foreach($cobrosDelDia as $cobro) {
            $metodoPago = $cobro->metodo_pago;
            $yaContados = false;
            
            // Calcular el monto realmente pagado (sin incluir la deuda)
            $montoPagado = $cobro->total_final - $cobro->deuda;
            
            // Para pagos mixtos, necesitamos distribuir entre categorías
            $montoEfectivo = $metodoPago === 'mixto' ? $cobro->pago_efectivo : ($metodoPago === 'efectivo' ? $montoPagado : 0);
            $montoTarjeta = $metodoPago === 'mixto' ? $cobro->pago_tarjeta : ($metodoPago === 'tarjeta' ? $montoPagado : 0);
            
            // PRIORIDAD 1: Servicios de cita individual
            if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                foreach($cobro->cita->servicios as $servicio) {
                    $precioServicio = $servicio->pivot->precio ?? $servicio->precio;
                    
                    // Calcular proporción de este servicio respecto al total (sin descuentos)
                    $proporcion = $cobro->coste > 0 ? ($precioServicio / $cobro->coste) : 0;
                    
                    // Solo sumar si NO es pago con bono (los bonos no generan ingreso ese día)
                    if ($metodoPago !== 'bono') {
                        $montoServicio = $montoPagado * $proporcion;
                        $montoServicioEfectivo = $montoEfectivo * $proporcion;
                        $montoServicioTarjeta = $montoTarjeta * $proporcion;
                        
                        if ($servicio->categoria === 'peluqueria') {
                            $totalPeluqueria += $montoServicio;
                            $totalPeluqueriaEfectivo += $montoServicioEfectivo;
                            $totalPeluqueriaTarjeta += $montoServicioTarjeta;
                        } elseif ($servicio->categoria === 'estetica') {
                            $totalEstetica += $montoServicio;
                            $totalEsteticaEfectivo += $montoServicioEfectivo;
                            $totalEsteticaTarjeta += $montoServicioTarjeta;
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
                            $precioServicio = $servicio->pivot->precio ?? $servicio->precio;
                            
                            // Calcular proporción de este servicio respecto al total
                            $proporcion = $cobro->coste > 0 ? ($precioServicio / $cobro->coste) : 0;
                            
                            // Solo sumar si NO es pago con bono
                            if ($metodoPago !== 'bono') {
                                $montoServicio = $montoPagado * $proporcion;
                                $montoServicioEfectivo = $montoEfectivo * $proporcion;
                                $montoServicioTarjeta = $montoTarjeta * $proporcion;
                                
                                if ($servicio->categoria === 'peluqueria') {
                                    $totalPeluqueria += $montoServicio;
                                    $totalPeluqueriaEfectivo += $montoServicioEfectivo;
                                    $totalPeluqueriaTarjeta += $montoServicioTarjeta;
                                } elseif ($servicio->categoria === 'estetica') {
                                    $totalEstetica += $montoServicio;
                                    $totalEsteticaEfectivo += $montoServicioEfectivo;
                                    $totalEsteticaTarjeta += $montoServicioTarjeta;
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
                    $precioServicio = $servicio->pivot->precio ?? $servicio->precio;
                    
                    // Calcular proporción de este servicio respecto al total
                    $proporcion = $cobro->coste > 0 ? ($precioServicio / $cobro->coste) : 0;
                    
                    // Solo sumar si NO es pago con bono
                    if ($metodoPago !== 'bono') {
                        $montoServicio = $montoPagado * $proporcion;
                        $montoServicioEfectivo = $montoEfectivo * $proporcion;
                        $montoServicioTarjeta = $montoTarjeta * $proporcion;
                        
                        if ($servicio->categoria === 'peluqueria') {
                            $totalPeluqueria += $montoServicio;
                            $totalPeluqueriaEfectivo += $montoServicioEfectivo;
                            $totalPeluqueriaTarjeta += $montoServicioTarjeta;
                        } elseif ($servicio->categoria === 'estetica') {
                            $totalEstetica += $montoServicio;
                            $totalEsteticaEfectivo += $montoServicioEfectivo;
                            $totalEsteticaTarjeta += $montoServicioTarjeta;
                        }
                    }
                }
            }
            
            // Si NO tiene servicios pero SÍ tiene productos, contabilizar productos
            if (!$yaContados && $cobro->productos && $cobro->productos->count() > 0) {
                foreach($cobro->productos as $producto) {
                    $subtotal = $producto->pivot->subtotal ?? 0;
                    
                    // Calcular proporción de este producto respecto al total
                    $proporcion = $cobro->coste > 0 ? ($subtotal / $cobro->coste) : 0;
                    
                    // Solo sumar si NO es pago con bono
                    if ($metodoPago !== 'bono') {
                        $montoProducto = $montoPagado * $proporcion;
                        $montoProductoEfectivo = $montoEfectivo * $proporcion;
                        $montoProductoTarjeta = $montoTarjeta * $proporcion;
                        
                        if ($producto->categoria === 'peluqueria') {
                            $totalPeluqueria += $montoProducto;
                            $totalPeluqueriaEfectivo += $montoProductoEfectivo;
                            $totalPeluqueriaTarjeta += $montoProductoTarjeta;
                        } elseif ($producto->categoria === 'estetica') {
                            $totalEstetica += $montoProducto;
                            $totalEsteticaEfectivo += $montoProductoEfectivo;
                            $totalEsteticaTarjeta += $montoProductoTarjeta;
                        }
                    }
                }
            }
        }

        return view('caja.index', compact(
            'fecha',
            'totalEfectivo',
            'totalTarjeta',
            'totalBono',
            'totalBonosEfectivo',
            'totalBonosTarjeta',
            'totalBonosVendidos',
            'totalPagado',
            'totalServicios',
            'totalDeuda',
            'totalPeluqueria',
            'totalEstetica',
            'totalPeluqueriaEfectivo',
            'totalPeluqueriaTarjeta',
            'totalPeluqueriaBono',
            'totalEsteticaEfectivo',
            'totalEsteticaTarjeta',
            'totalEsteticaBono',
            'deudas',
            'detalleServicios',
            'bonosVendidos'
        ));
    }
}
