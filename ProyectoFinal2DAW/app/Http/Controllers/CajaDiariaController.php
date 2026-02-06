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
            'servicios.empleados', // Cargar empleados de cada servicio
            'servicios' => function($query) {
                $query->withPivot('empleado_id', 'precio'); // Incluir empleado_id y precio del pivot
            },
            'productos',
            'cita.servicios',
            'citasAgrupadas.servicios',
            'bonosVendidos' // IMPORTANTE: cargar bonos vendidos
        ])
            ->whereDate('created_at', $fecha)
            ->get();
        
        // Calculamos totales por método de pago considerando productos Y bonos vendidos
        foreach($cobrosDelDia as $cobro) {
            // IMPORTANTE: total_final es el monto real cobrado (sin deuda)
            // Para registros antiguos que no tienen dinero_cliente, usar total_final - deuda
            $montoPagadoServicios = $cobro->total_final;
            
            // Sumar servicios/productos según método de pago del COBRO
            if ($cobro->metodo_pago === 'efectivo') {
                $totalEfectivo += $montoPagadoServicios;
            } elseif ($cobro->metodo_pago === 'tarjeta') {
                $totalTarjeta += $montoPagadoServicios;
            } elseif ($cobro->metodo_pago === 'mixto') {
                $efectivoServicios = $cobro->pago_efectivo ?? 0;
                $tarjetaServicios = $cobro->pago_tarjeta ?? 0;
                
                $totalEfectivo += $efectivoServicios;
                $totalTarjeta += $tarjetaServicios;
            } elseif ($cobro->metodo_pago === 'bono') {
                $totalBono += $cobro->coste;
            } elseif ($cobro->metodo_pago === 'deuda') {
                // Si es deuda, no sumamos nada porque no se recibió dinero
                continue;
            }
        }
        
        // PASO SEPARADO: Calcular totales de bonos vendidos del día
        // IMPORTANTE: Solo contamos bonos que estén asociados a cobros del día
        // Esto evita contar bonos huérfanos si se eliminan los cobros
        $idsBonosDelDia = $cobrosDelDia->flatMap(function($cobro) {
            return $cobro->bonosVendidos->pluck('id');
        })->unique()->toArray();
        
        // Si no hay IDs, crear colección vacía en lugar de hacer query
        $bonosVendidosDelDia = !empty($idsBonosDelDia) 
            ? BonoCliente::with(['plantilla.servicios'])->whereIn('id', $idsBonosDelDia)->get()
            : collect();

        // Calcular totales de bonos para mostrar en la vista
        $totalBonosVendidos = $bonosVendidosDelDia->sum('precio_pagado');
        $totalBonosEfectivo = $bonosVendidosDelDia->where('metodo_pago', 'efectivo')->sum('precio_pagado');
        $totalBonosTarjeta = $bonosVendidosDelDia->where('metodo_pago', 'tarjeta')->sum('precio_pagado');

        // Total pagado: lo que realmente ingresó en caja (servicios + bonos)
        $totalPagado = $totalEfectivo + $totalTarjeta + $totalBonosVendidos;

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
            'servicios' => function($query) {
                $query->withPivot('empleado_id', 'precio'); // IMPORTANTE: cargar empleado_id y precio del pivot
            },
            'productos'
        ])
            ->whereDate('created_at', $fecha)
            ->orderBy('created_at', 'desc')
            ->get();

        // Detalle de bonos vendidos - Solo los que están asociados a cobros del día
        $bonosVendidos = !empty($idsBonosDelDia)
            ? BonoCliente::with(['cliente.user', 'empleado.user', 'plantilla'])
                ->whereIn('id', $idsBonosDelDia)
                ->orderBy('fecha_compra', 'desc')
                ->get()
            : collect();

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
            
            // Usar total_final que ya contiene el monto real cobrado (sin deuda)
            $montoPagado = $cobro->total_final;
            
            // Para pagos mixtos, necesitamos distribuir entre categorías
            $montoEfectivo = $metodoPago === 'mixto' ? $cobro->pago_efectivo : ($metodoPago === 'efectivo' ? $montoPagado : 0);
            $montoTarjeta = $metodoPago === 'mixto' ? $cobro->pago_tarjeta : ($metodoPago === 'tarjeta' ? $montoPagado : 0);
            
            // PRIORIDAD 1: Servicios directos del cobro (Fuente de verdad)
            if ($cobro->servicios && $cobro->servicios->count() > 0) {
                // Calcular suma REAL de servicios (no usar $cobro->coste que puede estar desactualizado)
                $sumaRealServicios = 0;
                foreach($cobro->servicios as $servicio) {
                    $sumaRealServicios += $servicio->pivot->precio ?? $servicio->precio;
                }
                
                foreach($cobro->servicios as $servicio) {
                    $precioServicio = $servicio->pivot->precio ?? $servicio->precio;
                    
                    // Calcular proporción usando la suma REAL de servicios
                    $proporcion = $sumaRealServicios > 0 ? ($precioServicio / $sumaRealServicios) : 0;
                    
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
            
            // PRIORIDAD 2: Servicios de cita individual (Fallback para datos antiguos)
            if (!$yaContados && $cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                // Calcular suma REAL de servicios
                $sumaRealServicios = 0;
                foreach($cobro->cita->servicios as $servicio) {
                    $sumaRealServicios += $servicio->pivot->precio ?? $servicio->precio;
                }
                
                foreach($cobro->cita->servicios as $servicio) {
                    $precioServicio = $servicio->pivot->precio ?? $servicio->precio;
                    
                    // Calcular proporción usando la suma REAL
                    $proporcion = $sumaRealServicios > 0 ? ($precioServicio / $sumaRealServicios) : 0;
                    
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
                $yaContados = true;
            }
            
            // PRIORIDAD 3: Servicios de citas agrupadas (Fallback para datos antiguos)
            if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                // Calcular suma REAL de todos los servicios de citas agrupadas
                $sumaRealServicios = 0;
                foreach($cobro->citasAgrupadas as $citaGrupo) {
                    if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                        foreach($citaGrupo->servicios as $servicio) {
                            $sumaRealServicios += $servicio->pivot->precio ?? $servicio->precio;
                        }
                    }
                }
                
                foreach($cobro->citasAgrupadas as $citaGrupo) {
                    if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                        foreach($citaGrupo->servicios as $servicio) {
                            $precioServicio = $servicio->pivot->precio ?? $servicio->precio;
                            
                            // Calcular proporción usando la suma REAL
                            $proporcion = $sumaRealServicios > 0 ? ($precioServicio / $sumaRealServicios) : 0;
                            
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
            
            // Si NO tiene servicios pero SÍ tiene productos, contabilizar productos
            if (!$yaContados && $cobro->productos && $cobro->productos->count() > 0) {
                // Calcular suma REAL de productos
                $sumaRealProductos = 0;
                foreach($cobro->productos as $producto) {
                    $sumaRealProductos += $producto->pivot->subtotal ?? 0;
                }
                
                foreach($cobro->productos as $producto) {
                    $subtotal = $producto->pivot->subtotal ?? 0;
                    
                    // Calcular proporción usando la suma REAL
                    $proporcion = $sumaRealProductos > 0 ? ($subtotal / $sumaRealProductos) : 0;
                    
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

        // PASO ADICIONAL: Agregar bonos vendidos a las categorías correspondientes
        // Los bonos vendidos deben sumarse a peluquería/estética según los servicios que incluyan
        foreach($bonosVendidosDelDia as $bono) {
            $precioPagado = $bono->precio_pagado ?? 0;
            $metodoPago = $bono->metodo_pago;
            
            \Log::info("Procesando bono #{$bono->id}: Precio pagado: {$precioPagado}, Método: {$metodoPago}");
            
            // Obtener servicios del bono desde la plantilla
            $plantilla = $bono->plantilla;
            
            if (!$plantilla) {
                \Log::warning("Bono #{$bono->id} no tiene plantilla asociada");
                continue;
            }
            
            \Log::info("Bono #{$bono->id} tiene plantilla: {$plantilla->nombre}");
            
            if ($plantilla && $plantilla->servicios && $plantilla->servicios->count() > 0) {
                // Contar servicios por categoría en el bono
                $serviciosPeluqueria = $plantilla->servicios->where('categoria', 'peluqueria')->count();
                $serviciosEstetica = $plantilla->servicios->where('categoria', 'estetica')->count();
                $totalServiciosBono = $serviciosPeluqueria + $serviciosEstetica;
                
                \Log::info("Bono #{$bono->id}: {$serviciosPeluqueria} servicios peluquería, {$serviciosEstetica} servicios estética");
                
                if ($totalServiciosBono > 0) {
                    // Calcular proporción por categoría
                    $proporcionPeluqueria = $serviciosPeluqueria / $totalServiciosBono;
                    $proporcionEstetica = $serviciosEstetica / $totalServiciosBono;
                    
                    // Distribuir el precio pagado según proporción
                    $montoPeluqueria = $precioPagado * $proporcionPeluqueria;
                    $montoEstetica = $precioPagado * $proporcionEstetica;
                    
                    \Log::info("Distribución bono #{$bono->id}: Peluquería: €{$montoPeluqueria}, Estética: €{$montoEstetica}");
                    
                    // Sumar a totales según método de pago
                    if ($metodoPago === 'efectivo') {
                        $totalPeluqueriaEfectivo += $montoPeluqueria;
                        $totalEsteticaEfectivo += $montoEstetica;
                    } elseif ($metodoPago === 'tarjeta') {
                        $totalPeluqueriaTarjeta += $montoPeluqueria;
                        $totalEsteticaTarjeta += $montoEstetica;
                        \Log::info("Sumado a tarjeta peluquería: €{$montoPeluqueria}");
                    }
                    // Si es método 'deuda', no sumamos porque no ingresó dinero
                    
                    // Sumar al total de la categoría
                    $totalPeluqueria += $montoPeluqueria;
                    $totalEstetica += $montoEstetica;
                } else {
                    \Log::warning("Bono #{$bono->id} no tiene servicios contables");
                }
            } else {
                \Log::warning("Bono #{$bono->id} no tiene servicios en la plantilla");
            }
        }
        
        \Log::info("Totales finales - Peluquería Tarjeta: €{$totalPeluqueriaTarjeta}, Peluquería Total: €{$totalPeluqueria}");

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
