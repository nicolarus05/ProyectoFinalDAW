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
            // CRÍTICO: Restar bonos vendidos para evitar duplicación (total_final puede incluirlos en algunos casos)
            // Usar max(0, ...) para evitar valores negativos si total_final ya está corregido
            $montoPagadoServicios = max(0, $cobro->total_final - ($cobro->total_bonos_vendidos ?? 0));
            
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
            // Restar bonos vendidos del total_final para obtener solo servicios/productos
            return max(0, $cobro->total_final - ($cobro->total_bonos_vendidos ?? 0));
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
            
            // PUNTO 2: Para cobros pagados con bono, sumar a totalPeluqueriaBono/totalEsteticaBono
            if ($metodoPago === 'bono') {
                // Distribuir el coste entre categorías según servicios
                if ($cobro->servicios && $cobro->servicios->count() > 0) {
                    foreach($cobro->servicios as $servicio) {
                        $precioServicio = $servicio->pivot->precio ?? $servicio->precio ?? 0;
                        if ($servicio->categoria === 'peluqueria') {
                            $totalPeluqueriaBono += $precioServicio;
                        } elseif ($servicio->categoria === 'estetica') {
                            $totalEsteticaBono += $precioServicio;
                        }
                    }
                }
                continue; // No suma a efectivo/tarjeta
            }
            
            // Cobros de deuda no generan ingreso
            if ($metodoPago === 'deuda') {
                continue;
            }
            
            // Usar total_final que ya contiene el monto real cobrado (sin deuda)
            // CRÍTICO: Restar bonos vendidos para evitar duplicación
            // Usar max(0, ...) para evitar valores negativos si total_final ya está corregido
            $montoPagado = max(0, $cobro->total_final - ($cobro->total_bonos_vendidos ?? 0));
            
            // Si no se cobró nada, saltar
            if ($montoPagado <= 0) {
                continue;
            }
            
            // Para pagos mixtos, necesitamos distribuir entre categorías
            $montoEfectivo = $metodoPago === 'mixto' ? ($cobro->pago_efectivo ?? 0) : ($metodoPago === 'efectivo' ? $montoPagado : 0);
            $montoTarjeta = $metodoPago === 'mixto' ? ($cobro->pago_tarjeta ?? 0) : ($metodoPago === 'tarjeta' ? $montoPagado : 0);
            
            // PASO 1: Calcular suma total de servicios y productos del pivot
            // El pivot registro_cobro_servicio ya tiene los precios con descuentos aplicados
            $sumaRealServicios = 0;
            $serviciosDelCobro = [];
            
            // PRIORIDAD 1: Servicios directos del cobro (FUENTE DE VERDAD)
            if ($cobro->servicios && $cobro->servicios->count() > 0) {
                foreach($cobro->servicios as $servicio) {
                    // El precio del pivot YA tiene descuentos aplicados - es el precio REAL cobrado
                    $precioServicio = $servicio->pivot->precio ?? 0;
                    
                    // Solo contar servicios con precio > 0 (excluir los pagados con bono)
                    if ($precioServicio > 0) {
                        $sumaRealServicios += $precioServicio;
                        $serviciosDelCobro[] = ['servicio' => $servicio, 'precio' => $precioServicio];
                    }
                }
            }
            // PRIORIDAD 2: Servicios de cita individual (FALLBACK - datos antiguos)
            elseif ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                foreach($cobro->cita->servicios as $servicio) {
                    // Para datos antiguos, usar precio base del servicio
                    $precioServicio = $servicio->precio ?? 0;
                    
                    // Solo contar servicios con precio > 0
                    if ($precioServicio > 0) {
                        $sumaRealServicios += $precioServicio;
                        $serviciosDelCobro[] = ['servicio' => $servicio, 'precio' => $precioServicio];
                    }
                }
            }
            // PRIORIDAD 3: Servicios de citas agrupadas (FALLBACK - datos antiguos)
            elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                foreach($cobro->citasAgrupadas as $citaGrupo) {
                    if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                        foreach($citaGrupo->servicios as $servicio) {
                            // Para datos antiguos, usar precio base del servicio
                            $precioServicio = $servicio->precio ?? 0;
                            
                            // Solo contar servicios con precio > 0
                            if ($precioServicio > 0) {
                                $sumaRealServicios += $precioServicio;
                                $serviciosDelCobro[] = ['servicio' => $servicio, 'precio' => $precioServicio];
                            }
                        }
                    }
                }
            }
            
            // Calcular suma de productos (el subtotal del pivot YA tiene descuentos aplicados)
            $sumaRealProductos = 0;
            $productosDelCobro = [];
            if ($cobro->productos && $cobro->productos->count() > 0) {
                foreach($cobro->productos as $producto) {
                    $subtotal = $producto->pivot->subtotal ?? 0;
                    $sumaRealProductos += $subtotal;
                    $productosDelCobro[] = ['producto' => $producto, 'subtotal' => $subtotal];
                }
            }
            
            // Suma total (servicios + productos) - Esta es la suma de precios REALES con descuentos
            $sumaTotal = $sumaRealServicios + $sumaRealProductos;
            
            if ($sumaTotal <= 0) {
                continue; // No hay nada que distribuir
            }
            
            // IMPORTANTE: Los precios del pivot YA tienen descuentos aplicados
            // Solo necesitamos distribuir entre efectivo/tarjeta proporcionalmente
            // NO aplicamos descuento adicional porque ya está en los precios
            
            // PUNTO 5: Usar misma lógica que totales generales para distribuir efectivo/tarjeta
            // Para mixto, distribuir proporcionalmente según pago_efectivo/pago_tarjeta
            if ($metodoPago === 'mixto') {
                $totalPagoMixto = ($cobro->pago_efectivo ?? 0) + ($cobro->pago_tarjeta ?? 0);
                if ($totalPagoMixto > 0) {
                    $montoEfectivo = $montoPagado * (($cobro->pago_efectivo ?? 0) / $totalPagoMixto);
                    $montoTarjeta = $montoPagado * (($cobro->pago_tarjeta ?? 0) / $totalPagoMixto);
                }
            }
            
            // PASO 2: Distribuir servicios (usar precio exacto del pivot)
            foreach($serviciosDelCobro as $item) {
                $servicio = $item['servicio'];
                $precioServicio = $item['precio']; // Precio REAL con descuento ya aplicado
                
                // VALIDAR: Solo procesar servicios con categoría válida
                if (!in_array($servicio->categoria, ['peluqueria', 'estetica'])) {
                    continue;
                }
                
                // Distribuir efectivo/tarjeta proporcionalmente
                $proporcionItem = $sumaTotal > 0 ? ($precioServicio / $sumaTotal) : 0;
                $montoServicioEfectivo = $montoEfectivo * $proporcionItem;
                $montoServicioTarjeta = $montoTarjeta * $proporcionItem;
                
                // El total por categoría debe ser la suma real cobrada (efectivo+tarjeta), no el precio teórico
                $montoServicio = $montoPagado * $proporcionItem;
                
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
            
            // PASO 3: Distribuir productos (usar subtotal exacto del pivot)
            foreach($productosDelCobro as $item) {
                $producto = $item['producto'];
                $subtotal = $item['subtotal']; // Subtotal REAL con descuento ya aplicado
                
                // VALIDAR: Solo procesar productos con categoría válida
                if (!in_array($producto->categoria, ['peluqueria', 'estetica'])) {
                    continue;
                }
                
                // Distribuir efectivo/tarjeta proporcionalmente
                $proporcionItem = $sumaTotal > 0 ? ($subtotal / $sumaTotal) : 0;
                $montoProductoEfectivo = $montoEfectivo * $proporcionItem;
                $montoProductoTarjeta = $montoTarjeta * $proporcionItem;
                
                // El total por categoría debe ser la suma real cobrada (efectivo+tarjeta), no el subtotal teórico
                $montoProducto = $montoPagado * $proporcionItem;
                
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
            
            // Contar servicios por categoría en el bono
            $serviciosPeluqueria = $plantilla->servicios->where('categoria', 'peluqueria')->count();
            $serviciosEstetica = $plantilla->servicios->where('categoria', 'estetica')->count();
            $totalServiciosBono = $serviciosPeluqueria + $serviciosEstetica;
            
            if ($totalServiciosBono <= 0) {
                continue;
            }
            
            // Calcular proporción por categoría
            $proporcionPeluqueria = $serviciosPeluqueria / $totalServiciosBono;
            $proporcionEstetica = $serviciosEstetica / $totalServiciosBono;
            
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
