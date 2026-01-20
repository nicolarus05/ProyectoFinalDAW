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
        
        // Obtener IDs de cobros que son pagos de deudas (para excluirlos SOLO de facturación)
        $cobrosDeudas = DB::table('movimientos_deuda')
            ->where('tipo', 'abono')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->pluck('id_registro_cobro')
            ->toArray();
        
        // Obtener todos los cobros del mes (por fecha de cobro, no fecha de cita)
        // INCLUIR pagos de deudas para cajas diarias, pero excluirlos de facturación
        $cobros = RegistroCobro::with(['cita.servicios', 'citasAgrupadas.servicios', 'servicios', 'productos', 'bonosVendidos'])
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
                    // total_final contiene el monto real cobrado (sin deuda)
                    $montoPagadoServicios = $cobro->total_final;
                    
                    // Sumar total (servicios/productos sin bonos todavía)
                    $cajasDiarias[$fechaCobro]['total'] += $montoPagadoServicios;
                    
                    // Desglosar servicios/productos por método de pago del cobro
                    if ($cobro->metodo_pago === 'efectivo') {
                        $cajasDiarias[$fechaCobro]['efectivo'] += $montoPagadoServicios;
                    } elseif ($cobro->metodo_pago === 'tarjeta') {
                        $cajasDiarias[$fechaCobro]['tarjeta'] += $montoPagadoServicios;
                    } elseif ($cobro->metodo_pago === 'mixto') {
                        $efectivoServicios = $cobro->pago_efectivo ?? 0;
                        $tarjetaServicios = $cobro->pago_tarjeta ?? 0;
                        
                        $cajasDiarias[$fechaCobro]['efectivo'] += $efectivoServicios;
                        $cajasDiarias[$fechaCobro]['tarjeta'] += $tarjetaServicios;
                    } elseif ($cobro->metodo_pago === 'deuda') {
                        // Si tiene deuda pero se pagó algo, sumarlo a efectivo
                        if ($montoPagadoServicios > 0) {
                            $cajasDiarias[$fechaCobro]['efectivo'] += $montoPagadoServicios;
                        }
                    }
                    
                    // IMPORTANTE: Sumar bonos vendidos por SU PROPIO método de pago (no del cobro)
                    if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                        foreach ($cobro->bonosVendidos as $bono) {
                            $metodoPagoBono = $bono->metodo_pago; // Método de pago del BONO, no del cobro
                            
                            // SOLO sumar si NO quedó a deber (si método de pago es 'deuda', no contar)
                            if ($metodoPagoBono !== 'deuda') {
                                $precioBonoPagado = $bono->precio_pagado ?? 0;
                                
                                // Sumar al total general
                                $cajasDiarias[$fechaCobro]['total'] += $precioBonoPagado;
                                
                                // Sumar al método específico del bono
                                if ($metodoPagoBono === 'efectivo') {
                                    $cajasDiarias[$fechaCobro]['efectivo'] += $precioBonoPagado;
                                } elseif ($metodoPagoBono === 'tarjeta') {
                                    $cajasDiarias[$fechaCobro]['tarjeta'] += $precioBonoPagado;
                                } elseif ($metodoPagoBono === 'mixto') {
                                    // Para bonos con pago mixto, distribuir proporcionalmente
                                    // (esto es raro pero puede pasar)
                                    $cajasDiarias[$fechaCobro]['efectivo'] += $precioBonoPagado / 2;
                                    $cajasDiarias[$fechaCobro]['tarjeta'] += $precioBonoPagado / 2;
                                }
                            }
                            // Si el bono se pagó con deuda, NO se suma (se sumará cuando se pague la deuda)
                        }
                    }
                }
            }
            
            // PASO 2: DESGLOSE DE SERVICIOS POR CATEGORÍA
            // EXCLUIR cobros que son pagos de deudas (no contar servicios duplicados)
            if (in_array($cobro->id, $cobrosDeudas)) {
                continue; // Saltar a siguiente cobro sin procesar facturación
            }
            
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
            
            // PASO 4: DISTRIBUIR TOTAL FACTURADO (total_final + deuda) ENTRE SERVICIOS Y PRODUCTOS PROPORCIONALMENTE
            // Para facturación mensual queremos el total facturado (cobrado + deuda pendiente)
            // EXCLUIR cobros pagados con bono (porque eso es consumo, no ingreso)
            // Procesar si hay servicios O productos (coste es solo servicios, pero puede haber productos sin servicios)
            $costoTotalCobro = $costoServiciosCobro + $costoProductosCobro;
            if ($cobro->metodo_pago !== 'bono' && $costoTotalCobro > 0) {
                $proporcionServicios = $costoServiciosCobro / $costoTotalCobro;
                $proporcionProductos = $costoProductosCobro / $costoTotalCobro;
                
                // COMPATIBLE con registros antiguos y nuevos:
                // - Nuevos registros: dinero_cliente tiene lo cobrado, deuda tiene lo pendiente
                // - Registros antiguos: total_final tenía todo, usar ese valor
                if ($cobro->dinero_cliente !== null) {
                    // Registro nuevo: total facturado = dinero cobrado + deuda pendiente
                    $totalFacturadoCobro = $cobro->dinero_cliente + $cobro->deuda;
                } else {
                    // Registro antiguo: total_final ya incluía todo
                    $totalFacturadoCobro = $cobro->total_final;
                }
                $totalServiciosCobro = $totalFacturadoCobro * $proporcionServicios;
                $totalProductosCobro = $totalFacturadoCobro * $proporcionProductos;
                
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
        
        // BONOS - Calcular total de bonos vendidos (excluir cobros pagados con bono)
        // Solo necesitamos calcular el total para estadísticas generales
        $bonosVendidos = $cobros->where('metodo_pago', '!=', 'bono')->sum('total_bonos_vendidos');
        
        // YA NO procesamos bonos_clientes aquí porque duplicaría el conteo
        // Los bonos se registran en registro_cobros cuando se venden
        
        // PAGOS DE DEUDAS - Sumar los pagos de deudas al total facturado
        // Estos cobros no se desglosan por categoría (para evitar duplicar servicios)
        // pero SÍ deben contar en el total facturado del mes
        $totalPagosDeudas = 0;
        foreach ($cobrosDeudas as $idCobroDeuda) {
            $cobroDeuda = $cobros->firstWhere('id', $idCobroDeuda);
            if ($cobroDeuda && $cobroDeuda->metodo_pago !== 'bono') {
                $totalPagosDeudas += $cobroDeuda->total_final;
            }
        }
        
        // Calcular totales
        $totalServicios = $serviciosPeluqueria + $serviciosEstetica;
        $totalProductos = $productosPeluqueria + $productosEstetica;
        $totalGeneral = $totalServicios + $totalProductos + $bonosVendidos + $totalPagosDeudas;
        
        // Calcular deuda total del mes (solo deudas pendientes)
        // El campo 'deuda' ahora se actualiza automáticamente cuando se paga
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
