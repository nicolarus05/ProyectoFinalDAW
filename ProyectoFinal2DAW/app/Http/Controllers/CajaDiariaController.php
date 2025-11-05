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

        // Totales por metodo de pago (usamos total_final -> el precio real del servicio realizado)
        $totalEfectivo = RegistroCobro::whereDate('created_at', $fecha)
            ->where('metodo_pago', 'efectivo')
            ->sum('total_final');

        $totalTarjeta  = RegistroCobro::whereDate('created_at', $fecha)
            ->where('metodo_pago', 'tarjeta')
            ->sum('dinero_cliente');

        $totalBono  = RegistroCobro::whereDate('created_at', $fecha)
            ->where('metodo_pago', 'bono')
            ->sum('dinero_cliente');

        // Bonos vendidos ese día
        $totalBonosEfectivo = BonoCliente::whereDate('fecha_compra', $fecha)
            ->where('metodo_pago', 'efectivo')
            ->sum('precio_pagado');

        $totalBonosTarjeta = BonoCliente::whereDate('fecha_compra', $fecha)
            ->where('metodo_pago', 'tarjeta')
            ->sum('precio_pagado');

        $totalBonosVendidos = $totalBonosEfectivo + $totalBonosTarjeta;

        // Total pagado incluye servicios + bonos vendidos
        $totalPagado = RegistroCobro::whereDate('created_at', $fecha)->sum('total_final') + $totalBonosVendidos;

        // Total de servicios realizados
        $totalServicios = RegistroCobro::whereDate('created_at', $fecha)->sum('total_final');

        // Total de deuda del día
        $totalDeuda = RegistroCobro::whereDate('created_at', $fecha)->sum('deuda');

        // Clientes que han dejado deuda ese día
        $deudas = RegistroCobro::with(['cliente.user', 'cita.cliente.user', 'cita.servicios'])
            ->whereDate('created_at', $fecha)
            ->where('deuda', '>', 0)
            ->get();

        // Detalle de servicios: cliente, servicios (de la cita), empleado, metodo_pago, dinero_pagado, deuda
        $detalleServicios = RegistroCobro::with(['cliente.user', 'empleado.user', 'cita.servicios', 'cita.empleado.user', 'cita.cliente.user', 'productos'])
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
            
            if ($cobro->cita && $cobro->cita->servicios) {
                foreach($cobro->cita->servicios as $servicio) {
                    $precioServicio = $servicio->pivot->precio ?? $servicio->precio;
                    
                    if ($servicio->categoria === 'peluqueria') {
                        $totalPeluqueria += $precioServicio;
                        if ($metodoPago === 'efectivo') $totalPeluqueriaEfectivo += $precioServicio;
                        elseif ($metodoPago === 'tarjeta') $totalPeluqueriaTarjeta += $precioServicio;
                        elseif ($metodoPago === 'bono') $totalPeluqueriaBono += $precioServicio;
                    } elseif ($servicio->categoria === 'estetica') {
                        $totalEstetica += $precioServicio;
                        if ($metodoPago === 'efectivo') $totalEsteticaEfectivo += $precioServicio;
                        elseif ($metodoPago === 'tarjeta') $totalEsteticaTarjeta += $precioServicio;
                        elseif ($metodoPago === 'bono') $totalEsteticaBono += $precioServicio;
                    }
                }
            }
            
            // Calcular total de productos por categoría y método de pago
            if ($cobro->productos) {
                foreach($cobro->productos as $producto) {
                    $subtotal = $producto->pivot->subtotal ?? 0;
                    
                    if ($producto->categoria === 'peluqueria') {
                        $totalPeluqueria += $subtotal;
                        if ($metodoPago === 'efectivo') $totalPeluqueriaEfectivo += $subtotal;
                        elseif ($metodoPago === 'tarjeta') $totalPeluqueriaTarjeta += $subtotal;
                        elseif ($metodoPago === 'bono') $totalPeluqueriaBono += $subtotal;
                    } elseif ($producto->categoria === 'estetica') {
                        $totalEstetica += $subtotal;
                        if ($metodoPago === 'efectivo') $totalEsteticaEfectivo += $subtotal;
                        elseif ($metodoPago === 'tarjeta') $totalEsteticaTarjeta += $subtotal;
                        elseif ($metodoPago === 'bono') $totalEsteticaBono += $subtotal;
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
