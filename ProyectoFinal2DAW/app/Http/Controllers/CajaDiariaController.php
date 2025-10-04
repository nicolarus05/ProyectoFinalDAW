<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroCobro;
use Carbon\Carbon;

class CajaDiariaController extends Controller{
    public function index(Request $request){
        
        // Fecha que queremos ver (formato YYYY-MM-DD). Por defecto hoy.
        $fecha = $request->input('fecha', Carbon::today()->toDateString());

        // Totales por metodo de pago (usamos dinero_cliente -> lo realmente ingresado)
        $totalEfectivo = RegistroCobro::whereDate('created_at', $fecha)
            ->where('metodo_pago', 'efectivo')
            ->sum('dinero_cliente');

        $totalTarjeta  = RegistroCobro::whereDate('created_at', $fecha)
            ->where('metodo_pago', 'tarjeta')
            ->sum('dinero_cliente');

        $totalBono  = RegistroCobro::whereDate('created_at', $fecha)
            ->where('metodo_pago', 'bono')
            ->sum('dinero_cliente');

        $totalPagado = RegistroCobro::whereDate('created_at', $fecha)->sum('dinero_cliente');

        // Total de servicios realizados (suma de total_final)
        $totalServicios = RegistroCobro::whereDate('created_at', $fecha)->sum('total_final');

        // Clientes que han dejado deuda ese día
        $deudas = RegistroCobro::with(['cliente.user'])
            ->whereDate('created_at', $fecha)
            ->where('deuda', '>', 0)
            ->get();

        // Detalle de servicios: cliente, servicios (de la cita), empleado, metodo_pago, dinero_pagado, deuda
        $detalleServicios = RegistroCobro::with(['cliente.user', 'empleado.user', 'cita.servicios'])
            ->whereDate('created_at', $fecha)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('caja.index', compact(
            'fecha',
            'totalEfectivo',
            'totalTarjeta',
            'totalBono',
            'totalPagado',
            'totalServicios',
            'deudas',
            'detalleServicios'
        ));
    }
}
