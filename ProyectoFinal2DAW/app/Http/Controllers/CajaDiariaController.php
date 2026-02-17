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
            // IMPORTANTE: total_final ya contiene SOLO servicios/productos (sin bonos vendidos, sin deuda)
            // Los bonos vendidos se almacenan por separado en total_bonos_vendidos
            // NO restar total_bonos_vendidos aquí porque ya están excluidos de total_final
            $montoPagadoServicios = $cobro->total_final;
            
            // Sumar servicios/productos según método de pago del COBRO
            if ($cobro->metodo_pago === 'efectivo') {
                $totalEfectivo += $montoPagadoServicios;
            } elseif ($cobro->metodo_pago === 'tarjeta') {
                $totalTarjeta += $montoPagadoServicios;
            } elseif ($cobro->metodo_pago === 'mixto') {
                // PUNTO 1: Para mixto, distribuir proporcionalmente RESTANDO bonos vendidos
                $totalPagoMixto = ($cobro->pago_efectivo ?? 0) + ($cobro->pago_tarjeta ?? 0);
                
                if ($totalPagoMixto > 0 && $montoPagadoServicios > 0) {
                    $proporcionEfectivo = ($cobro->pago_efectivo ?? 0) / $totalPagoMixto;
                    $proporcionTarjeta = ($cobro->pago_tarjeta ?? 0) / $totalPagoMixto;
                    
                    $totalEfectivo += $montoPagadoServicios * $proporcionEfectivo;
                    $totalTarjeta += $montoPagadoServicios * $proporcionTarjeta;
                }
            } elseif ($cobro->metodo_pago === 'bono') {
                $totalBono += $cobro->total_final;
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
        
        // Calcular totales de bonos por método de pago (incluyendo mixtos con desglose)
        $totalBonosEfectivo = 0;
        $totalBonosTarjeta = 0;
        
        foreach($bonosVendidosDelDia as $bono) {
            if ($bono->metodo_pago === 'efectivo') {
                $totalBonosEfectivo += $bono->precio_pagado;
            } elseif ($bono->metodo_pago === 'tarjeta') {
                $totalBonosTarjeta += $bono->precio_pagado;
            } elseif ($bono->metodo_pago === 'mixto') {
                // Usar desglose real si existe, sino distribuir proporcionalmente
                if ($bono->pago_efectivo !== null && $bono->pago_tarjeta !== null) {
                    $totalBonosEfectivo += $bono->pago_efectivo;
                    $totalBonosTarjeta += $bono->pago_tarjeta;
                } else {
                    // Fallback 50/50 para datos antiguos sin desglose
                    $totalBonosEfectivo += $bono->precio_pagado / 2;
                    $totalBonosTarjeta += $bono->precio_pagado / 2;
                }
            }
            // 'deuda' no suma nada
        }

        // Total pagado: lo que realmente ingresó en caja (servicios + bonos)
        $totalPagado = $totalEfectivo + $totalTarjeta + $totalBonosVendidos;

        // PUNTO 4: Total de servicios realizados = suma de coste de servicios (sin bonos vendidos)
        // coste = precio total de servicios/productos antes de pagos
        // total_final puede incluir bonos vendidos, por eso usamos la suma del coste
        $totalServicios = $cobrosDelDia->sum(function($cobro) {
            // total_final ya contiene SOLO servicios/productos (bonos vendidos están aparte)
            return $cobro->total_final;
        });

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
            
            // Para cobros pagados con bono, sumar a totalPeluqueriaBono/totalEsteticaBono
            if ($metodoPago === 'bono') {
                $serviciosParaBono = collect();
                
                // Prioridad 1: Servicios directos del cobro (pivot)
                if ($cobro->servicios && $cobro->servicios->count() > 0) {
                    $serviciosParaBono = $cobro->servicios;
                }
                // Prioridad 2: Servicios de cita individual (fallback - bono no vincula pivot)
                elseif ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                    $serviciosParaBono = $cobro->cita->servicios;
                }
                // Prioridad 3: Servicios de citas agrupadas
                elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                    $serviciosParaBono = $cobro->citasAgrupadas->flatMap(function($c) {
                        return $c->servicios ?? collect();
                    });
                }
                
                foreach($serviciosParaBono as $servicio) {
                    $precioServicio = $servicio->pivot->precio ?? $servicio->precio ?? 0;
                    if ($servicio->categoria === 'peluqueria') {
                        $totalPeluqueriaBono += $precioServicio;
                    } elseif ($servicio->categoria === 'estetica') {
                        $totalEsteticaBono += $precioServicio;
                    }
                }
                continue;
            }
            
            // Cobros de deuda no generan ingreso
            if ($metodoPago === 'deuda') {
                continue;
            }
            
            // --- Determinar proporción efectivo/tarjeta del cobro ---
            $propEfectivo = 0;
            $propTarjeta = 0;
            
            if ($metodoPago === 'efectivo') {
                $propEfectivo = 1;
            } elseif ($metodoPago === 'tarjeta') {
                $propTarjeta = 1;
            } elseif ($metodoPago === 'mixto') {
                $totalPagoMixto = ($cobro->pago_efectivo ?? 0) + ($cobro->pago_tarjeta ?? 0);
                if ($totalPagoMixto > 0) {
                    $propEfectivo = ($cobro->pago_efectivo ?? 0) / $totalPagoMixto;
                    $propTarjeta = ($cobro->pago_tarjeta ?? 0) / $totalPagoMixto;
                }
            }
            
            // --- Recopilar servicios (con fallback para datos antiguos) ---
            $serviciosDelCobro = collect();
            
            if ($cobro->servicios && $cobro->servicios->count() > 0) {
                $serviciosDelCobro = $cobro->servicios;
            } elseif ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                $serviciosDelCobro = $cobro->cita->servicios;
            } elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                $serviciosDelCobro = $cobro->citasAgrupadas->flatMap(function($c) {
                    return $c->servicios ?? collect();
                });
            }
            
            // --- Distribuir servicios por categoría usando precio DIRECTO del pivot ---
            // El precio del pivot es la fuente de verdad (ya incluye descuentos y ajustes de deuda)
            foreach($serviciosDelCobro as $servicio) {
                $precioServicio = $servicio->pivot->precio ?? $servicio->precio ?? 0;
                if ($precioServicio <= 0) continue; // Excluir pagados con bono o asignados a deuda
                
                $categoria = in_array($servicio->categoria, ['peluqueria', 'estetica']) 
                    ? $servicio->categoria 
                    : 'peluqueria';
                
                if ($categoria === 'peluqueria') {
                    $totalPeluqueria += $precioServicio;
                    $totalPeluqueriaEfectivo += $precioServicio * $propEfectivo;
                    $totalPeluqueriaTarjeta += $precioServicio * $propTarjeta;
                } else {
                    $totalEstetica += $precioServicio;
                    $totalEsteticaEfectivo += $precioServicio * $propEfectivo;
                    $totalEsteticaTarjeta += $precioServicio * $propTarjeta;
                }
            }
            
            // --- Distribuir productos por categoría usando subtotal DIRECTO del pivot ---
            if ($cobro->productos && $cobro->productos->count() > 0) {
                foreach($cobro->productos as $producto) {
                    $subtotal = $producto->pivot->subtotal ?? 0;
                    if ($subtotal <= 0) continue;
                    
                    $categoria = in_array($producto->categoria, ['peluqueria', 'estetica']) 
                        ? $producto->categoria 
                        : 'peluqueria';
                    
                    if ($categoria === 'peluqueria') {
                        $totalPeluqueria += $subtotal;
                        $totalPeluqueriaEfectivo += $subtotal * $propEfectivo;
                        $totalPeluqueriaTarjeta += $subtotal * $propTarjeta;
                    } else {
                        $totalEstetica += $subtotal;
                        $totalEsteticaEfectivo += $subtotal * $propEfectivo;
                        $totalEsteticaTarjeta += $subtotal * $propTarjeta;
                    }
                }
            }
        }

        // PASO ADICIONAL: Agregar bonos vendidos a las categorías correspondientes
        // Los bonos vendidos deben sumarse a peluquería/estética según los servicios que incluyan
        foreach($bonosVendidosDelDia as $bono) {
            $precioPagado = $bono->precio_pagado ?? 0;
            $metodoPago = $bono->metodo_pago;
            
            // NO sumar bonos que quedaron a deber (no ingresó dinero)
            if ($metodoPago === 'deuda' || $precioPagado <= 0) {
                continue;
            }
            
            // Obtener servicios del bono desde la plantilla
            $plantilla = $bono->plantilla;
            
            if (!$plantilla || !$plantilla->servicios || $plantilla->servicios->count() === 0) {
                continue;
            }
            
            // Distribuir por PRECIO de servicios (no por cantidad) para mayor precisión
            $sumaPrecioPeluqueria = $plantilla->servicios->where('categoria', 'peluqueria')->sum('precio');
            $sumaPrecioEstetica = $plantilla->servicios->where('categoria', 'estetica')->sum('precio');
            $sumaPrecioTotal = $sumaPrecioPeluqueria + $sumaPrecioEstetica;
            
            if ($sumaPrecioTotal <= 0) {
                continue;
            }
            
            // Calcular proporción por categoría basada en precios reales
            $proporcionPeluqueria = $sumaPrecioPeluqueria / $sumaPrecioTotal;
            $proporcionEstetica = $sumaPrecioEstetica / $sumaPrecioTotal;
            
            // Distribuir el precio pagado según proporción
            $montoPeluqueria = $precioPagado * $proporcionPeluqueria;
            $montoEstetica = $precioPagado * $proporcionEstetica;
            
            // PUNTO 3: Sumar a totales según método de pago (con desglose real para mixtos)
            if ($metodoPago === 'efectivo') {
                $totalPeluqueriaEfectivo += $montoPeluqueria;
                $totalEsteticaEfectivo += $montoEstetica;
            } elseif ($metodoPago === 'tarjeta') {
                $totalPeluqueriaTarjeta += $montoPeluqueria;
                $totalEsteticaTarjeta += $montoEstetica;
            } elseif ($metodoPago === 'mixto') {
                // Usar campos pago_efectivo y pago_tarjeta si existen, sino distribuir proporcionalmente
                $pagoEfectivoBono = $bono->pago_efectivo ?? null;
                $pagoTarjetaBono = $bono->pago_tarjeta ?? null;
                
                if ($pagoEfectivoBono !== null && $pagoTarjetaBono !== null && $precioPagado > 0) {
                    // Usar desglose real del bono
                    $proporcionEfectivo = $pagoEfectivoBono / $precioPagado;
                    $proporcionTarjeta = $pagoTarjetaBono / $precioPagado;
                } else {
                    // Fallback: distribuir 50/50 si no hay desglose
                    $proporcionEfectivo = 0.5;
                    $proporcionTarjeta = 0.5;
                }
                
                $totalPeluqueriaEfectivo += $montoPeluqueria * $proporcionEfectivo;
                $totalPeluqueriaTarjeta += $montoPeluqueria * $proporcionTarjeta;
                $totalEsteticaEfectivo += $montoEstetica * $proporcionEfectivo;
                $totalEsteticaTarjeta += $montoEstetica * $proporcionTarjeta;
            }
            
            // Sumar al total de la categoría
            $totalPeluqueria += $montoPeluqueria;
            $totalEstetica += $montoEstetica;
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
