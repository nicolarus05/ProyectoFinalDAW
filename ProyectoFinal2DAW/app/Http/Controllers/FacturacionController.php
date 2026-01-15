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
        Carbon::setLocale('es');
        
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

        // Procesar cada cobro
        foreach($cobros as $cobro) {
            // PASO 1: CAJAS DIARIAS - Solo sumar lo que realmente se cobró (sin deuda)
            if ($cobro->metodo_pago !== 'bono') {
                $fechaCobro = $cobro->created_at->format('Y-m-d');
                if (isset($cajasDiarias[$fechaCobro])) {
                    // Calcular lo que realmente se pagó (servicios/productos - deuda + bonos vendidos)
                    $montoPagadoServicios = $cobro->total_final - $cobro->deuda;
                    $totalBonos = $cobro->total_bonos_vendidos ?? 0;
                    $totalRealmentePagado = $montoPagadoServicios + $totalBonos;
                    
                    // Sumar total
                    $cajasDiarias[$fechaCobro]['total'] += $totalRealmentePagado;
                    
                    // Desglosar por método de pago
                    if ($cobro->metodo_pago === 'efectivo') {
                        $cajasDiarias[$fechaCobro]['efectivo'] += $totalRealmentePagado;
                    } elseif ($cobro->metodo_pago === 'tarjeta') {
                        $cajasDiarias[$fechaCobro]['tarjeta'] += $totalRealmentePagado;
                    } elseif ($cobro->metodo_pago === 'mixto') {
                        // Para pagos mixtos: distribuir proporcionalmente servicios + bonos
                        $efectivoServicios = $cobro->pago_efectivo ?? 0;
                        $tarjetaServicios = $cobro->pago_tarjeta ?? 0;
                        $totalPagadoServicios = $efectivoServicios + $tarjetaServicios;
                        
                        if ($totalBonos > 0 && $totalPagadoServicios > 0) {
                            $proporcionEfectivo = $efectivoServicios / $totalPagadoServicios;
                            $proporcionTarjeta = $tarjetaServicios / $totalPagadoServicios;
                            
                            $cajasDiarias[$fechaCobro]['efectivo'] += $efectivoServicios + ($totalBonos * $proporcionEfectivo);
                            $cajasDiarias[$fechaCobro]['tarjeta'] += $tarjetaServicios + ($totalBonos * $proporcionTarjeta);
                        } else {
                            $cajasDiarias[$fechaCobro]['efectivo'] += $efectivoServicios;
                            $cajasDiarias[$fechaCobro]['tarjeta'] += $tarjetaServicios;
                        }
                    } elseif ($cobro->metodo_pago === 'deuda') {
                        // Si tiene deuda pero se pagó algo, sumarlo a efectivo
                        if ($totalRealmentePagado > 0) {
                            $cajasDiarias[$fechaCobro]['efectivo'] += $totalRealmentePagado;
                        }
                    }
                }
            }
            
            // PASO 2: DESGLOSE DE SERVICIOS POR CATEGORÍA
            // Calcular cuánto del cobro corresponde a servicios (antes de descuentos)
            $yaContados = false;
            $costoServiciosCobro = 0;
            
            // PRIORIDAD 1: Servicios de cita individual
            if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                foreach($cobro->cita->servicios as $servicio) {
                    $precio = $servicio->pivot->precio ?? $servicio->precio;
                    $costoServiciosCobro += $precio;
                }
                $yaContados = true;
            }
            
            // PRIORIDAD 2: Servicios de citas agrupadas
            if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                foreach($cobro->citasAgrupadas as $citaGrupo) {
                    if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                        foreach($citaGrupo->servicios as $servicio) {
                            $precio = $servicio->pivot->precio ?? $servicio->precio;
                            $costoServiciosCobro += $precio;
                        }
                    }
                }
                $yaContados = true;
            }
            
            // PRIORIDAD 3: Servicios directos
            if (!$yaContados && $cobro->servicios && $cobro->servicios->count() > 0) {
                foreach($cobro->servicios as $servicio) {
                    $precio = $servicio->pivot->precio ?? $servicio->precio;
                    $costoServiciosCobro += $precio;
                }
            }
            
            // PASO 3: CALCULAR PRODUCTOS
            $costoProductosCobro = 0;
            if ($cobro->productos && $cobro->productos->count() > 0) {
                foreach($cobro->productos as $producto) {
                    $subtotal = $producto->pivot->subtotal ?? 0;
                    $costoProductosCobro += $subtotal;
                }
            }
            
            // PASO 4: DISTRIBUIR total_final ENTRE SERVICIOS Y PRODUCTOS PROPORCIONALMENTE
            // Ahora aplicamos el total_final (que ya tiene descuentos) proporcionalmente
            if ($cobro->metodo_pago !== 'bono' && $cobro->coste > 0) {
                $proporcionServicios = $costoServiciosCobro / $cobro->coste;
                $proporcionProductos = $costoProductosCobro / $cobro->coste;
                
                $totalServiciosCobro = $cobro->total_final * $proporcionServicios;
                $totalProductosCobro = $cobro->total_final * $proporcionProductos;
                
                // Ahora desglosar servicios por categoría
                $yaContados = false;
                if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                    foreach($cobro->cita->servicios as $servicio) {
                        $precio = $servicio->pivot->precio ?? $servicio->precio;
                        if ($costoServiciosCobro > 0) {
                            $proporcionServicio = $precio / $costoServiciosCobro;
                            $montoServicio = $totalServiciosCobro * $proporcionServicio;
                            
                            if ($servicio->categoria === 'peluqueria') {
                                $serviciosPeluqueria += $montoServicio;
                            } elseif ($servicio->categoria === 'estetica') {
                                $serviciosEstetica += $montoServicio;
                            }
                        }
                    }
                    $yaContados = true;
                }
                
                if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                    foreach($cobro->citasAgrupadas as $citaGrupo) {
                        if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                            foreach($citaGrupo->servicios as $servicio) {
                                $precio = $servicio->pivot->precio ?? $servicio->precio;
                                if ($costoServiciosCobro > 0) {
                                    $proporcionServicio = $precio / $costoServiciosCobro;
                                    $montoServicio = $totalServiciosCobro * $proporcionServicio;
                                    
                                    if ($servicio->categoria === 'peluqueria') {
                                        $serviciosPeluqueria += $montoServicio;
                                    } elseif ($servicio->categoria === 'estetica') {
                                        $serviciosEstetica += $montoServicio;
                                    }
                                }
                            }
                        }
                    }
                    $yaContados = true;
                }
                
                if (!$yaContados && $cobro->servicios && $cobro->servicios->count() > 0) {
                    foreach($cobro->servicios as $servicio) {
                        $precio = $servicio->pivot->precio ?? $servicio->precio;
                        if ($costoServiciosCobro > 0) {
                            $proporcionServicio = $precio / $costoServiciosCobro;
                            $montoServicio = $totalServiciosCobro * $proporcionServicio;
                            
                            if ($servicio->categoria === 'peluqueria') {
                                $serviciosPeluqueria += $montoServicio;
                            } elseif ($servicio->categoria === 'estetica') {
                                $serviciosEstetica += $montoServicio;
                            }
                        }
                    }
                }
                
                // Desglosar productos por categoría
                if ($cobro->productos && $cobro->productos->count() > 0) {
                    foreach($cobro->productos as $producto) {
                        $subtotal = $producto->pivot->subtotal ?? 0;
                        if ($costoProductosCobro > 0) {
                            $proporcionProducto = $subtotal / $costoProductosCobro;
                            $montoProducto = $totalProductosCobro * $proporcionProducto;
                            
                            if ($producto->categoria === 'peluqueria') {
                                $productosPeluqueria += $montoProducto;
                            } elseif ($producto->categoria === 'estetica') {
                                $productosEstetica += $montoProducto;
                            }
                        }
                    }
                }
            }
        }
        
        // BONOS - Los bonos ya están incluidos en registro_cobros.total_bonos_vendidos
        // Solo necesitamos calcular el total para estadísticas generales
        $bonosVendidos = $cobros->sum('total_bonos_vendidos');
        
        // YA NO procesamos bonos_clientes aquí porque duplicaría el conteo
        // Los bonos se registran en registro_cobros cuando se venden
        
        // Calcular totales
        $totalServicios = $serviciosPeluqueria + $serviciosEstetica;
        $totalProductos = $productosPeluqueria + $productosEstetica;
        $totalGeneral = $totalServicios + $totalProductos + $bonosVendidos;
        
        // Calcular deuda total del mes (para verificación)
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
