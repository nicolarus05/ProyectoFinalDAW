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
        
        // SERVICIOS - Obtener cobros de citas completadas
        // Peluquería
        $serviciosPeluqueria = RegistroCobro::whereHas('cita', function($query) use ($fechaInicio, $fechaFin) {
            $query->whereBetween('fecha_hora', [$fechaInicio, $fechaFin])
                  ->where('estado', 'completada')
                  ->whereHas('servicios', function($q) {
                      $q->where('categoria', 'peluqueria');
                  });
        })
        ->whereNotNull('id_cita')
        ->get()
        ->sum(function($cobro) {
            // Obtener solo el total de servicios de peluquería de esta cita
            $totalServicios = $cobro->cita->servicios()
                ->where('categoria', 'peluqueria')
                ->sum('precio');
            
            // Calcular la proporción de este cobro que corresponde a servicios de peluquería
            $totalServiciosCita = $cobro->cita->servicios->sum('precio');
            if ($totalServiciosCita > 0) {
                return ($totalServicios / $totalServiciosCita) * $cobro->total_final;
            }
            return 0;
        });
        
        // Estética
        $serviciosEstetica = RegistroCobro::whereHas('cita', function($query) use ($fechaInicio, $fechaFin) {
            $query->whereBetween('fecha_hora', [$fechaInicio, $fechaFin])
                  ->where('estado', 'completada')
                  ->whereHas('servicios', function($q) {
                      $q->where('categoria', 'estetica');
                  });
        })
        ->whereNotNull('id_cita')
        ->get()
        ->sum(function($cobro) {
            $totalServicios = $cobro->cita->servicios()
                ->where('categoria', 'estetica')
                ->sum('precio');
            
            $totalServiciosCita = $cobro->cita->servicios->sum('precio');
            if ($totalServiciosCita > 0) {
                return ($totalServicios / $totalServiciosCita) * $cobro->total_final;
            }
            return 0;
        });
        
        // PRODUCTOS - Desde la tabla pivot registro_cobro_productos
        $productosPeluqueria = DB::table('registro_cobro_productos')
            ->join('productos', 'registro_cobro_productos.id_producto', '=', 'productos.id')
            ->join('registro_cobros', 'registro_cobro_productos.id_registro_cobro', '=', 'registro_cobros.id')
            ->whereBetween('registro_cobros.created_at', [$fechaInicio, $fechaFin])
            ->where('productos.categoria', 'peluqueria')
            ->sum('registro_cobro_productos.subtotal');
        
        $productosEstetica = DB::table('registro_cobro_productos')
            ->join('productos', 'registro_cobro_productos.id_producto', '=', 'productos.id')
            ->join('registro_cobros', 'registro_cobro_productos.id_registro_cobro', '=', 'registro_cobros.id')
            ->whereBetween('registro_cobros.created_at', [$fechaInicio, $fechaFin])
            ->where('productos.categoria', 'estetica')
            ->sum('registro_cobro_productos.subtotal');
        
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
