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

        // Totales por metodo de pago - SOLO lo que realmente se pagó (total_final - deuda)
        // Para efectivo: total_final - deuda para cobros en efectivo
        $cobrosEfectivo = RegistroCobro::whereDate('created_at', $fecha)
            ->where('metodo_pago', 'efectivo')
            ->get();
        $totalEfectivo = $cobrosEfectivo->sum(function($cobro) {
            return $cobro->total_final - $cobro->deuda;
        });
        
        // Para pagos mixtos, sumar el pago en efectivo (ya refleja lo pagado realmente)
        $totalEfectivoMixto = RegistroCobro::whereDate('created_at', $fecha)
            ->where('metodo_pago', 'mixto')
            ->sum('pago_efectivo');
        
        $totalEfectivo += $totalEfectivoMixto;

        // Para tarjeta: total_final - deuda para cobros en tarjeta
        $cobrosTarjeta = RegistroCobro::whereDate('created_at', $fecha)
            ->where('metodo_pago', 'tarjeta')
            ->get();
        $totalTarjeta = $cobrosTarjeta->sum(function($cobro) {
            return $cobro->total_final - $cobro->deuda;
        });
        
        // Para pagos mixtos, sumar el pago con tarjeta (ya refleja lo pagado realmente)
        $totalTarjetaMixto = RegistroCobro::whereDate('created_at', $fecha)
            ->where('metodo_pago', 'mixto')
            ->sum('pago_tarjeta');
        
        $totalTarjeta += $totalTarjetaMixto;

        // Para bonos: el coste es el valor real del servicio (sin deuda porque se paga con bono)
        $totalBono  = RegistroCobro::whereDate('created_at', $fecha)
            ->where('metodo_pago', 'bono')
            ->sum('coste');

        // Bonos vendidos ese día
        $totalBonosEfectivo = BonoCliente::whereDate('fecha_compra', $fecha)
            ->where('metodo_pago', 'efectivo')
            ->sum('precio_pagado');

        $totalBonosTarjeta = BonoCliente::whereDate('fecha_compra', $fecha)
            ->where('metodo_pago', 'tarjeta')
            ->sum('precio_pagado');

        $totalBonosVendidos = $totalBonosEfectivo + $totalBonosTarjeta;

        // Total pagado: lo que realmente ingresó en caja (total_final - deuda) + bonos vendidos
        $cobrosDelDia = RegistroCobro::whereDate('created_at', $fecha)->get();
        $totalPagado = $cobrosDelDia->sum(function($cobro) {
            return $cobro->total_final - $cobro->deuda;
        }) + $totalBonosVendidos;

        // Total de servicios realizados (incluye todo, pagado y no pagado)
        $totalServicios = RegistroCobro::whereDate('created_at', $fecha)->sum('total_final');

        // Total de deuda del día (dinero que quedó pendiente)
        $totalDeuda = RegistroCobro::whereDate('created_at', $fecha)->sum('deuda');

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

        foreach($detalleServicios as $cobro) {
            $metodoPago = $cobro->metodo_pago;
            $yaContados = false; // Flag para evitar duplicación
            
            // Calcular el monto realmente pagado (sin incluir la deuda)
            $montoPagado = $cobro->total_final - $cobro->deuda;
            
            // PRIORIDAD 1: Servicios de cita individual
            if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                foreach($cobro->cita->servicios as $servicio) {
                    $precioServicio = $servicio->pivot->precio ?? $servicio->precio;
                    
                    // Calcular precio real considerando descuentos y solo lo pagado
                    $proporcion = $cobro->coste > 0 ? ($precioServicio / $cobro->coste) : 0;
                    $precioRealServicio = $montoPagado * $proporcion;
                    
                    // Solo sumar si NO es pago con bono (los bonos no generan ingreso ese día)
                    if ($metodoPago !== 'bono') {
                        if ($servicio->categoria === 'peluqueria') {
                            $totalPeluqueria += $precioRealServicio;
                            if ($metodoPago === 'efectivo') $totalPeluqueriaEfectivo += $precioRealServicio;
                            elseif ($metodoPago === 'tarjeta') $totalPeluqueriaTarjeta += $precioRealServicio;
                            elseif ($metodoPago === 'mixto') {
                                $totalPeluqueriaEfectivo += $cobro->pago_efectivo * $proporcion;
                                $totalPeluqueriaTarjeta += $cobro->pago_tarjeta * $proporcion;
                            }
                        } elseif ($servicio->categoria === 'estetica') {
                            $totalEstetica += $precioRealServicio;
                            if ($metodoPago === 'efectivo') $totalEsteticaEfectivo += $precioRealServicio;
                            elseif ($metodoPago === 'tarjeta') $totalEsteticaTarjeta += $precioRealServicio;
                            elseif ($metodoPago === 'mixto') {
                                $totalEsteticaEfectivo += $cobro->pago_efectivo * $proporcion;
                                $totalEsteticaTarjeta += $cobro->pago_tarjeta * $proporcion;
                            }
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
                            
                            // Calcular precio real considerando descuentos y solo lo pagado
                            $proporcion = $cobro->coste > 0 ? ($precioServicio / $cobro->coste) : 0;
                            $precioRealServicio = $montoPagado * $proporcion;
                            
                            // Solo sumar si NO es pago con bono
                            if ($metodoPago !== 'bono') {
                                if ($servicio->categoria === 'peluqueria') {
                                    $totalPeluqueria += $precioRealServicio;
                                    if ($metodoPago === 'efectivo') $totalPeluqueriaEfectivo += $precioRealServicio;
                                    elseif ($metodoPago === 'tarjeta') $totalPeluqueriaTarjeta += $precioRealServicio;
                                    elseif ($metodoPago === 'mixto') {
                                        $totalPeluqueriaEfectivo += $cobro->pago_efectivo * $proporcion;
                                        $totalPeluqueriaTarjeta += $cobro->pago_tarjeta * $proporcion;
                                    }
                                } elseif ($servicio->categoria === 'estetica') {
                                    $totalEstetica += $precioRealServicio;
                                    if ($metodoPago === 'efectivo') $totalEsteticaEfectivo += $precioRealServicio;
                                    elseif ($metodoPago === 'tarjeta') $totalEsteticaTarjeta += $precioRealServicio;
                                    elseif ($metodoPago === 'mixto') {
                                        $totalEsteticaEfectivo += $cobro->pago_efectivo * $proporcion;
                                        $totalEsteticaTarjeta += $cobro->pago_tarjeta * $proporcion;
                                    }
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
                    
                    // Calcular precio real considerando descuentos y solo lo pagado
                    $proporcion = $cobro->coste > 0 ? ($precioServicio / $cobro->coste) : 0;
                    $precioRealServicio = $montoPagado * $proporcion;
                    
                    // Solo sumar si NO es pago con bono
                    if ($metodoPago !== 'bono') {
                        if ($servicio->categoria === 'peluqueria') {
                            $totalPeluqueria += $precioRealServicio;
                            if ($metodoPago === 'efectivo') $totalPeluqueriaEfectivo += $precioRealServicio;
                            elseif ($metodoPago === 'tarjeta') $totalPeluqueriaTarjeta += $precioRealServicio;
                            elseif ($metodoPago === 'mixto') {
                                $totalPeluqueriaEfectivo += $cobro->pago_efectivo * $proporcion;
                                $totalPeluqueriaTarjeta += $cobro->pago_tarjeta * $proporcion;
                            }
                        } elseif ($servicio->categoria === 'estetica') {
                            $totalEstetica += $precioRealServicio;
                            if ($metodoPago === 'efectivo') $totalEsteticaEfectivo += $precioRealServicio;
                            elseif ($metodoPago === 'tarjeta') $totalEsteticaTarjeta += $precioRealServicio;
                            elseif ($metodoPago === 'mixto') {
                                $totalEsteticaEfectivo += $cobro->pago_efectivo * $proporcion;
                                $totalEsteticaTarjeta += $cobro->pago_tarjeta * $proporcion;
                            }
                        }
                    }
                }
            }
            
            // Calcular total de productos por categoría y método de pago
            if ($cobro->productos) {
                foreach($cobro->productos as $producto) {
                    $subtotal = $producto->pivot->subtotal ?? 0;
                    
                    // Calcular precio real considerando descuentos y solo lo pagado
                    $proporcion = $cobro->coste > 0 ? ($subtotal / $cobro->coste) : 0;
                    $subtotalReal = $montoPagado * $proporcion;
                    
                    // Solo sumar si NO es pago con bono
                    if ($metodoPago !== 'bono') {
                        if ($producto->categoria === 'peluqueria') {
                            $totalPeluqueria += $subtotalReal;
                            if ($metodoPago === 'efectivo') $totalPeluqueriaEfectivo += $subtotalReal;
                            elseif ($metodoPago === 'tarjeta') $totalPeluqueriaTarjeta += $subtotalReal;
                            elseif ($metodoPago === 'mixto') {
                                $totalPeluqueriaEfectivo += $cobro->pago_efectivo * $proporcion;
                                $totalPeluqueriaTarjeta += $cobro->pago_tarjeta * $proporcion;
                            }
                        } elseif ($producto->categoria === 'estetica') {
                            $totalEstetica += $subtotalReal;
                            if ($metodoPago === 'efectivo') $totalEsteticaEfectivo += $subtotalReal;
                            elseif ($metodoPago === 'tarjeta') $totalEsteticaTarjeta += $subtotalReal;
                            elseif ($metodoPago === 'mixto') {
                                $totalEsteticaEfectivo += $cobro->pago_efectivo * $proporcion;
                                $totalEsteticaTarjeta += $cobro->pago_tarjeta * $proporcion;
                            }
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
