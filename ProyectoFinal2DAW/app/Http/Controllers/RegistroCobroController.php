<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroCobro;
use App\Models\Cita;
use App\Models\user;
use App\Models\Productos;
use App\Models\Cliente;
use App\Models\BonoCliente;
use App\Models\BonoUsoDetalle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreRegistroCobroRequest;
use App\Services\CacheService;
use App\Http\Resources\RegistroCobroResource;
use App\Traits\HasFlashMessages;
use App\Traits\HasCrudMessages;
use App\Traits\HasJsonResponses;

class RegistroCobroController extends Controller{
    use HasFlashMessages, HasCrudMessages, HasJsonResponses;

    protected function getResourceName(): string
    {
        return 'cobro';
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request){
        // Obtener fecha del request o usar la fecha actual
        $fecha = $request->input('fecha', now()->format('Y-m-d'));
        $fechaCarbon = \Carbon\Carbon::parse($fecha);
        
        // Optimizar eager loading - cargar todas las relaciones necesarias en una sola consulta
        $cobros = RegistroCobro::with([
            'cita' => function($query) {
                $query->with(['cliente.user', 'empleado.user', 'servicios']);
            },
            'citasAgrupadas' => function($query) {
                $query->with('servicios');
            },
            'servicios',
            'cliente.user',
            'empleado.user',
            'productos',
            'bonosVendidos' // Cargar bonos vendidos para calcular deuda correctamente
        ])
        ->whereDate('created_at', $fecha)
        ->orderBy('created_at', 'desc')
        ->get();
        
        return view('cobros.index', compact('cobros', 'fecha', 'fechaCarbon'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request){
        $citas = Cita::whereDoesntHave('cobro')
            ->with(['cliente.user', 'cliente.deuda', 'servicios', 'cliente.bonosActivos.plantilla.servicios', 'cliente.bonosActivos.servicios' => function($query) {
                $query->withPivot('cantidad_total', 'cantidad_usada');
            }])
            ->get();
        
        // Si viene un parámetro cita_id, precargar esa cita
        $citaSeleccionada = null;
        if ($request->has('cita_id')) {
            $citaSeleccionada = Cita::with(['cliente.user', 'cliente.deuda', 'servicios', 'empleado', 'cliente.bonosActivos.plantilla.servicios', 'cliente.bonosActivos.servicios' => function($query) {
                $query->withPivot('cantidad_total', 'cantidad_usada');
            }])
                ->find($request->cita_id);
        }
        
        return view('cobros.create', compact('citas', 'citaSeleccionada'));
    }

    /**
     * Mostrar formulario para cobro directo (sin cita o con múltiples citas agrupadas)
     */
    public function createDirect(Request $request){
        $clientes = Cliente::with(['user', 'deuda'])->get();
        
        // Usar caché para datos maestros
        $empleados = CacheService::getEmpleados();
        $servicios = CacheService::getServiciosActivos();
        $bonosPlantilla = CacheService::getBonosPlantilla();
        
        $cita = null;
        $citas = collect(); // Colección vacía por defecto
        $bonosCliente = collect(); // Bonos activos del cliente
        
        // Detectar si viene UNA cita o MÚLTIPLES citas
        if ($request->has('id_cita')) {
            // Flujo normal: una sola cita
            $cita = \App\Models\Cita::with(['cliente.user', 'empleado.user', 'servicios'])->find($request->id_cita);
            
            // Cargar bonos activos del cliente con información de alertas
            if ($cita && $cita->cliente) {
                $bonosCliente = \App\Models\BonoCliente::with(['plantilla.servicios', 'servicios' => function($query) {
                    $query->withPivot('cantidad_total', 'cantidad_usada');
                }])
                    ->where('cliente_id', $cita->cliente->id)
                    ->where('estado', 'activo')
                    ->get()
                    ->map(function($bono) {
                        $bono->alertas = $bono->obtenerEstadoAlerta();
                        return $bono;
                    });
            }
        } elseif ($request->has('citas_ids')) {
            // Flujo agrupado: múltiples citas del mismo cliente y día
            $citas = \App\Models\Cita::with(['cliente.user', 'empleado.user', 'servicios'])
                ->whereIn('id', $request->citas_ids)
                ->get();
            
            // Cargar bonos del cliente (asumiendo todas las citas son del mismo cliente)
            if ($citas->isNotEmpty() && $citas->first()->cliente) {
                $bonosCliente = \App\Models\BonoCliente::with(['plantilla.servicios', 'servicios' => function($query) {
                    $query->withPivot('cantidad_total', 'cantidad_usada');
                }])
                    ->where('cliente_id', $citas->first()->cliente->id)
                    ->where('estado', 'activo')
                    ->get()
                    ->map(function($bono) {
                        $bono->alertas = $bono->obtenerEstadoAlerta();
                        return $bono;
                    });
            }
        }
        
        return view('cobros.create-direct', compact('clientes', 'empleados', 'servicios', 'cita', 'citas', 'bonosPlantilla', 'bonosCliente'));
    }

    /**
     * Obtener bonos activos de un cliente (para AJAX)
     */
    public function getBonosCliente($clienteId)
    {
        $bonos = \App\Models\BonoCliente::with(['plantilla.servicios', 'servicios' => function($query) {
            $query->withPivot('cantidad_total', 'cantidad_usada');
        }])
            ->where('cliente_id', $clienteId)
            ->where('estado', 'activo')
            ->whereHas('servicios', function($query) {
                $query->whereRaw('cantidad_usada < cantidad_total');
            })
            ->get()
            ->map(function($bono) {
                $bono->alertas = $bono->obtenerEstadoAlerta();
                return $bono;
            });

        return response()->json($bonos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRegistroCobroRequest $request){
        // Los datos ya vienen validados y sanitizados del Form Request
        $data = $request->validated();

        // Validar que al menos tenga una cita, múltiples citas O un cliente
        if (empty($data['id_cita']) && empty($data['citas_ids']) && empty($data['id_cliente'])) {
            return back()
                ->withErrors(['id_cliente' => 'Debe seleccionar una cita o un cliente.'])
                ->withInput();
        }

        try {
            DB::beginTransaction();

        // --- VALIDACIÓN DE INTEGRIDAD: coste debe coincidir con servicios + productos ---
        $totalServiciosCalculado = 0;
        $totalProductosCalculado = 0;
        $detalleValidacion = [];

        // IMPORTANTE: Detectar si es venta SOLO de bono comparando total_final con precio del bono
        $soloVentaDeBono = false;
        $precioBonoVendido = 0;
        
        if (!empty($data['bono_plantilla_id'])) {
            $bonoPlantilla = \App\Models\BonoPlantilla::find($data['bono_plantilla_id']);
            if ($bonoPlantilla) {
                $precioBonoVendido = $bonoPlantilla->precio;
                $totalFinalRecibido = (float) $data['total_final'];
                
                // Si el total_final coincide EXACTAMENTE con el precio del bono, es venta solo de bono
                if (abs($totalFinalRecibido - $precioBonoVendido) < 0.01) {
                    $soloVentaDeBono = true;
                }
            }
        }

        // CASO 1: Cobro de cita individual
        if (!empty($data['id_cita']) && !$soloVentaDeBono) {
            // Si hay servicios_data, usar esos (modificados por el usuario)
            if ($request->has('servicios_data') && !empty($data['servicios_data'])) {
                $serviciosData = json_decode($data['servicios_data'], true);
                if (is_array($serviciosData) && count($serviciosData) > 0) {
                    foreach ($serviciosData as $s) {
                        $precio = (float) ($s['precio'] ?? 0);
                        $totalServiciosCalculado += $precio;
                        $detalleValidacion[] = "{$s['nombre']}: €" . number_format($precio, 2);
                    }
                }
            } elseif (!$soloVentaDeBono) {
                // Si no hay servicios_data Y NO es solo venta de bono, usar los servicios originales de la cita
                $cita = Cita::with('servicios')->find($data['id_cita']);
                if ($cita && $cita->servicios) {
                    foreach ($cita->servicios as $servicio) {
                        $precio = $servicio->precio;
                        $totalServiciosCalculado += $precio;
                        $detalleValidacion[] = "{$servicio->nombre}: €" . number_format($precio, 2);
                    }
                }
            }
        }
        
        // CASO 2: Cobro de múltiples citas agrupadas
        elseif (!empty($data['citas_ids']) && is_array($data['citas_ids']) && !$soloVentaDeBono) {
            // Si hay servicios_data, usar esos (modificados por el usuario en create-direct)
            if ($request->has('servicios_data') && !empty($data['servicios_data'])) {
                $serviciosData = json_decode($data['servicios_data'], true);
                if (is_array($serviciosData) && count($serviciosData) > 0) {
                    foreach ($serviciosData as $s) {
                        $precio = (float) ($s['precio'] ?? 0);
                        $totalServiciosCalculado += $precio;
                        $detalleValidacion[] = "{$s['nombre']}: €" . number_format($precio, 2);
                    }
                }
            } elseif (!$soloVentaDeBono) {
                // Si no hay servicios_data Y NO es solo venta de bono, usar los servicios originales de las citas
                $citas = Cita::with('servicios')->whereIn('id', $data['citas_ids'])->get();
                foreach ($citas as $cita) {
                    if ($cita->servicios) {
                        foreach ($cita->servicios as $servicio) {
                            $precio = $servicio->precio;
                            $totalServiciosCalculado += $precio;
                            $detalleValidacion[] = "{$servicio->nombre}: €" . number_format($precio, 2);
                        }
                    }
                }
            }
        }
        
        // CASO 3: Cobro directo con servicios (sin cita - solo cuando no hay id_cita ni citas_ids)
        elseif ($request->has('servicios_data') && !empty($data['servicios_data']) && !$soloVentaDeBono) {
            $serviciosData = json_decode($data['servicios_data'], true);
            if (is_array($serviciosData)) {
                foreach ($serviciosData as $s) {
                    $precio = (float) ($s['precio'] ?? 0);
                    $totalServiciosCalculado += $precio;
                    $detalleValidacion[] = "{$s['nombre']}: €" . number_format($precio, 2);
                }
            }
        }

        // CASO 4: Productos (aplica a todos los tipos de cobro, excepto venta solo de bono)
        if (!$soloVentaDeBono && $request->has('productos_data') && !empty($data['productos_data'])) {
            $productosData = json_decode($data['productos_data'], true);
            if (is_array($productosData)) {
                foreach ($productosData as $p) {
                    $cantidad = (int) ($p['cantidad'] ?? 0);
                    $precio = (float) ($p['precio'] ?? 0);
                    $subtotal = $cantidad * $precio;
                    $totalProductosCalculado += $subtotal;
                    $detalleValidacion[] = "Producto ID {$p['id']} (x{$cantidad}): €" . number_format($subtotal, 2);
                }
            }
        }

        // VALIDACIÓN 1: El campo 'coste' debe coincidir con el total de SERVICIOS solamente
        // EXCEPCIÓN: Si solo se vende un bono, el coste puede ser 0 (no hay servicios)
        $costeRecibido = (float) $data['coste'];
        $diferenciaServicios = abs($totalServiciosCalculado - $costeRecibido);
        
        // Solo validar si NO es venta exclusiva de bono
        if (!$soloVentaDeBono && $diferenciaServicios > 0.01) {
            $mensajeError = "El coste de servicios no coincide.\n\n";
            $mensajeError .= "💰 Coste recibido: €" . number_format($costeRecibido, 2) . "\n";
            $mensajeError .= "🧮 Coste calculado (servicios): €" . number_format($totalServiciosCalculado, 2) . "\n";
            $mensajeError .= "❌ Diferencia: €" . number_format($diferenciaServicios, 2) . "\n\n";
            
            if (!empty($detalleValidacion)) {
                $mensajeError .= "📋 Detalle servicios:\n";
                foreach ($detalleValidacion as $detalle) {
                    if (strpos($detalle, 'Producto') === false) {
                        $mensajeError .= $detalle . "\n";
                    }
                }
            }

            DB::rollBack();
            return back()
                ->withErrors(['coste' => $mensajeError])
                ->withInput();
        }

        // VALIDACIÓN 2: El total_final debe ser igual a (servicios - desc_servicios) + (productos - desc_productos) + bonos
        $descServiciosPor = (float) ($data['descuento_servicios_porcentaje'] ?? 0);
        $descServiciosEur = (float) ($data['descuento_servicios_euro'] ?? 0);
        $descProductosPor = (float) ($data['descuento_productos_porcentaje'] ?? 0);
        $descProductosEur = (float) ($data['descuento_productos_euro'] ?? 0);
        
        // Calcular servicios cubiertos por bonos (vendidos + activos)
        $totalServiciosCubiertosporBono = 0;
        $serviciosYaContados = []; // Para evitar contar servicios dos veces
        
        // 1. Servicios cubiertos por bono VENDIDO en esta transacción
        if (!empty($data['bono_plantilla_id']) && !$soloVentaDeBono) {
            $bonoPlantilla = \App\Models\BonoPlantilla::with('servicios')->find($data['bono_plantilla_id']);
            if ($bonoPlantilla) {
                // Obtener IDs de servicios incluidos en el bono
                $serviciosEnBono = $bonoPlantilla->servicios->pluck('id')->toArray();
                
                // Calcular el precio de los servicios que están en el bono
                if ($request->has('servicios_data') && !empty($data['servicios_data'])) {
                    $serviciosData = json_decode($data['servicios_data'], true);
                    if (is_array($serviciosData)) {
                        foreach ($serviciosData as $s) {
                            $servicioId = (int) $s['id'];
                            $precio = (float) ($s['precio'] ?? 0);
                            
                            // Si el servicio está incluido en el bono, sumarlo al total cubierto
                            if (in_array($servicioId, $serviciosEnBono)) {
                                $totalServiciosCubiertosporBono += $precio;
                                $serviciosYaContados[] = $servicioId; // Marcar como contado
                            }
                        }
                    }
                }
            }
        }
        
        // 2. Servicios cubiertos por bonos ACTIVOS del cliente (solo los que no están ya cubiertos por bono vendido)
        // Obtener el ID del cliente
        $clienteId = null;
        if (!empty($data['id_cita'])) {
            $cita = Cita::find($data['id_cita']);
            $clienteId = $cita ? $cita->id_cliente : null;
        } elseif (!empty($data['citas_ids']) && is_array($data['citas_ids'])) {
            $cita = Cita::whereIn('id', $data['citas_ids'])->first();
            $clienteId = $cita ? $cita->id_cliente : null;
        } elseif (!empty($data['id_cliente'])) {
            $clienteId = $data['id_cliente'];
        }
        
        // Si hay cliente, buscar sus bonos activos
        if ($clienteId) {
            $bonosActivos = \App\Models\BonoCliente::with(['servicios' => function($query) {
                $query->withPivot('cantidad_total', 'cantidad_usada');
            }])
            ->where('cliente_id', $clienteId)
            ->where('estado', 'activo')
            ->get();
            
            // Por cada servicio de la cita, verificar si está cubierto por un bono activo
            if ($request->has('servicios_data') && !empty($data['servicios_data'])) {
                $serviciosData = json_decode($data['servicios_data'], true);
                if (is_array($serviciosData)) {
                    foreach ($serviciosData as $s) {
                        $servicioId = (int) $s['id'];
                        $precio = (float) ($s['precio'] ?? 0);
                        
                        // Skip si ya fue contado por bono vendido
                        if (in_array($servicioId, $serviciosYaContados)) {
                            continue;
                        }
                        
                        // Buscar si algún bono activo cubre este servicio
                        foreach ($bonosActivos as $bonoActivo) {
                            $servicioEnBono = $bonoActivo->servicios->firstWhere('id', $servicioId);
                            
                            if ($servicioEnBono) {
                                $cantidadDisponible = $servicioEnBono->pivot->cantidad_total - $servicioEnBono->pivot->cantidad_usada;
                                
                                // Si tiene usos disponibles, este servicio está cubierto
                                if ($cantidadDisponible > 0) {
                                    $totalServiciosCubiertosporBono += $precio;
                                    $serviciosYaContados[] = $servicioId; // Marcar como contado
                                    break; // No buscar en más bonos para este servicio
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Calcular descuentos aplicados
        $descuentoServiciosTotal = ($totalServiciosCalculado * ($descServiciosPor / 100)) + $descServiciosEur;
        $descuentoProductosTotal = ($totalProductosCalculado * ($descProductosPor / 100)) + $descProductosEur;
        
        // VALIDACIÓN 2: El total_final debe ser igual a (servicios - desc_servicios - bonos_activos) + (productos - desc_productos) + bonos_vendidos
        // IMPORTANTE: El frontend YA resta los bonos activos del total_final, así que NO debemos restarlos de nuevo aquí
        // Solo verificamos que el cálculo sea correcto CON los bonos ya restados
        
        $totalServiciosConDescuento = max(0, $totalServiciosCalculado - $descuentoServiciosTotal);
        $totalProductosConDescuento = max(0, $totalProductosCalculado - $descuentoProductosTotal);
        
        // Usar el precio del bono ya calculado arriba
        $totalBonosVendidos = $precioBonoVendido;
        
        // IMPORTANTE: Para la validación, debemos restar los bonos activos porque el frontend los envió ya restados
        $totalFinalCalculado = max(0, $totalServiciosConDescuento - $totalServiciosCubiertosporBono) + $totalProductosConDescuento + $totalBonosVendidos;
        
        $totalFinalRecibido = (float) $data['total_final'];
        $diferenciaTotalFinal = abs($totalFinalCalculado - $totalFinalRecibido);
        
        if ($diferenciaTotalFinal > 0.01) {
            $mensajeError = "El total final no coincide con el cálculo esperado.\n\n";
            $mensajeError .= "💰 Total final recibido: €" . number_format($totalFinalRecibido, 2) . "\n";
            $mensajeError .= "🧮 Total final calculado: €" . number_format($totalFinalCalculado, 2) . "\n\n";
            $mensajeError .= "📊 Desglose del cálculo:\n";
            $mensajeError .= "   Servicios totales: €" . number_format($totalServiciosCalculado, 2) . "\n";
            $mensajeError .= "   - Descuento servicios (" . number_format($descServiciosPor, 2) . "% + €" . number_format($descServiciosEur, 2) . "): -€" . number_format($descuentoServiciosTotal, 2) . "\n";
            
            if ($totalServiciosCubiertosporBono > 0) {
                $mensajeError .= "   - Servicios cubiertos por bono: -€" . number_format($totalServiciosCubiertosporBono, 2) . "\n";
            }
            
            $mensajeError .= "   = Subtotal servicios: €" . number_format(max(0, $totalServiciosConDescuento - $totalServiciosCubiertosporBono), 2) . "\n\n";
            $mensajeError .= "   Productos: €" . number_format($totalProductosCalculado, 2) . "\n";
            $mensajeError .= "   - Descuento productos (" . number_format($descProductosPor, 2) . "% + €" . number_format($descProductosEur, 2) . "): -€" . number_format($descuentoProductosTotal, 2) . "\n";
            $mensajeError .= "   = Subtotal productos: €" . number_format($totalProductosConDescuento, 2) . "\n\n";
            
            if ($totalBonosVendidos > 0) {
                $mensajeError .= "   Bonos vendidos: €" . number_format($totalBonosVendidos, 2) . "\n\n";
            }
            
            $mensajeError .= "❌ Diferencia: €" . number_format($diferenciaTotalFinal, 2) . "\n";

            DB::rollBack();
            return back()
                ->withErrors(['total_final' => $mensajeError])
                ->withInput();
        }

        // --- Lógica según método de pago ---
        if ($data['metodo_pago'] === 'efectivo') {
            // Si es efectivo, el dinero_cliente puede ser menor que el total (genera deuda)
            // Solo es obligatorio si no está vacío, validar que sea >= 0
            if (!isset($data['dinero_cliente'])) {
                $data['dinero_cliente'] = 0; // Si está vacío, se asume que no paga nada (deuda completa)
            }

            if ($data['dinero_cliente'] < 0) {
                DB::rollBack();
                return back()
                    ->withErrors(['dinero_cliente' => 'El dinero del cliente no puede ser negativo.'])
                    ->withInput();
            }

            // Calcular el cambio (solo si paga más del total)
            $data['cambio'] = max(0, $data['dinero_cliente'] - $data['total_final']);
        } 
        elseif ($data['metodo_pago'] === 'tarjeta') {
            // Si es tarjeta → se llena automáticamente (no genera deuda)
            $data['dinero_cliente'] = $data['total_final'];
            $data['cambio'] = 0;
        }
        elseif ($data['metodo_pago'] === 'mixto') {
            // Pago mixto: efectivo + tarjeta
            $pagoEfectivo = $data['pago_efectivo'] ?? 0;
            $pagoTarjeta = $data['pago_tarjeta'] ?? 0;
            $totalPagado = $pagoEfectivo + $pagoTarjeta;
            
            // Validar que la suma sea exactamente igual al total
            if (abs($totalPagado - $data['total_final']) > 0.01) {
                DB::rollBack();
                return back()
                    ->withErrors(['metodo_pago' => 'El total de efectivo + tarjeta debe ser igual al total a pagar. Total pagado: €' . number_format($totalPagado, 2) . ', Total requerido: €' . number_format($data['total_final'], 2)])
                    ->withInput();
            }
            
            $data['dinero_cliente'] = $totalPagado;
            $data['cambio'] = 0; // No hay cambio en pago mixto
        }

        // --- VERIFICAR Y APLICAR BONOS ---
        $serviciosAplicados = [];
        $descuentoBonos = 0; // Total descontado por bonos
        
        // Determinar las citas a procesar (puede ser una sola o múltiples agrupadas)
        $citasAProcesar = collect();
        
        if (!empty($data['id_cita'])) {
            // Caso 1: Una sola cita
            $cita = Cita::with(['servicios', 'cliente'])->find($data['id_cita']);
            if ($cita) {
                $citasAProcesar->push($cita);
            }
        } elseif (!empty($data['citas_ids']) && is_array($data['citas_ids'])) {
            // Caso 2: Múltiples citas agrupadas
            $citasAProcesar = Cita::with(['servicios', 'cliente'])
                ->whereIn('id', $data['citas_ids'])
                ->get();
        }
        
        // IMPORTANTE: Si se está vendiendo un bono nuevo, NO aplicar bonos automáticamente
        // El bono nuevo se aplicará manualmente más adelante para evitar duplicación
        $seVendeBono = !empty($data['bono_plantilla_id']);
        
        // Obtener el cliente para cobros directos sin cita
        $clienteId = $data['id_cliente'] ?? null;
        if (!$clienteId && !empty($data['id_cita'])) {
            $cita = Cita::find($data['id_cita']);
            $clienteId = $cita ? $cita->id_cliente : null;
        } elseif (!$clienteId && !empty($data['citas_ids']) && is_array($data['citas_ids'])) {
            $primeraCita = Cita::find($data['citas_ids'][0]);
            $clienteId = $primeraCita ? $primeraCita->id_cliente : null;
        }
        
        // Procesar bonos (solo si NO se está vendiendo un bono nuevo)
        Log::info('🎫 PROCESANDO BONOS', [
            'se_vende_bono' => $seVendeBono,
            'cliente_id' => $clienteId,
            'tiene_citas' => $citasAProcesar->isNotEmpty()
        ]);
        
        if (!$seVendeBono && $clienteId) {
            // CASO A: Cobro con citas
            if ($citasAProcesar->isNotEmpty()) {
                foreach ($citasAProcesar as $cita) {
                    if ($cita && $cita->cliente) {
                        // Obtener bonos activos del cliente
                        $bonosActivos = BonoCliente::with('servicios')
                            ->where('cliente_id', $cita->cliente->id)
                            ->where('estado', 'activo')
                            ->where('fecha_expiracion', '>=', Carbon::now())
                            ->get();

                        Log::info('🔍 Bonos activos encontrados', [
                            'cita_id' => $cita->id,
                            'cliente_id' => $cita->cliente->id,
                            'cantidad_bonos' => $bonosActivos->count(),
                            'bonos' => $bonosActivos->map(fn($b) => [
                                'id' => $b->id,
                                'plantilla' => $b->plantilla->nombre ?? 'N/A',
                                'fecha_expiracion' => $b->fecha_expiracion?->format('Y-m-d')
                            ])
                        ]);

                        // Iterar sobre los servicios de la cita
                        foreach ($cita->servicios as $servicioCita) {
                            Log::info('🔄 Procesando servicio de cita', [
                                'servicio_id' => $servicioCita->id,
                                'servicio_nombre' => $servicioCita->nombre
                            ]);
                            
                            // Buscar si hay un bono que incluya este servicio
                            foreach ($bonosActivos as $bono) {
                                $servicioBono = $bono->servicios()
                                    ->where('servicio_id', $servicioCita->id)
                                    ->wherePivot('cantidad_usada', '<', DB::raw('cantidad_total'))
                                    ->first();

                                if ($servicioBono) {
                                    // Hay disponibilidad en el bono, deducir 1
                                    $cantidadUsada = $servicioBono->pivot->cantidad_usada + 1;
                                    
                                    Log::info('✅ APLICANDO BONO', [
                                        'bono_id' => $bono->id,
                                        'servicio_id' => $servicioCita->id,
                                        'servicio_nombre' => $servicioCita->nombre,
                                        'cantidad_usada_antes' => $servicioBono->pivot->cantidad_usada,
                                        'cantidad_usada_despues' => $cantidadUsada,
                                        'cantidad_total' => $servicioBono->pivot->cantidad_total
                                    ]);
                                    
                                    $bono->servicios()->updateExistingPivot($servicioCita->id, [
                                        'cantidad_usada' => $cantidadUsada
                                    ]);

                                    $serviciosAplicados[] = $servicioCita->nombre;
                                    
                                    // Acumular el descuento del servicio cubierto por el bono
                                    $descuentoBonos += $servicioCita->precio;

                                    // Registrar el uso detallado del bono
                                    BonoUsoDetalle::create([
                                        'bono_cliente_id' => $bono->id,
                                        'cita_id' => $cita->id,
                                        'servicio_id' => $servicioCita->id,
                                        'cantidad_usada' => 1
                                    ]);

                                    Log::info('📝 Uso de bono registrado', [
                                        'bono_uso_detalle_id' => 'creado',
                                        'bono_id' => $bono->id
                                    ]);

                                    // NOTA: El precio a 0 se aplicará al guardar los servicios en el pivot

                                    // Verificar si el bono está completamente usado
                                    if ($bono->estaCompletamenteUsado()) {
                                        $bono->update(['estado' => 'usado']);
                                        Log::info('🏁 Bono marcado como usado completamente', [
                                            'bono_id' => $bono->id
                                        ]);
                                    }

                                    break; // Ya se aplicó un bono para este servicio, pasar al siguiente
                                } else {
                                    Log::info('⏭️  Servicio no encontrado en este bono', [
                                        'bono_id' => $bono->id,
                                        'servicio_id' => $servicioCita->id
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
            // CASO B: Cobro directo SIN cita (con servicios_data)
            elseif ($request->has('servicios_data') && !empty($data['servicios_data'])) {
                $serviciosData = json_decode($data['servicios_data'], true);
                
                Log::info('💼 CASO B: Cobro directo sin cita', [
                    'cliente_id' => $clienteId,
                    'servicios_count' => count($serviciosData)
                ]);
                
                if (is_array($serviciosData) && count($serviciosData) > 0) {
                    // Obtener bonos activos del cliente
                    $bonosActivos = BonoCliente::with('servicios')
                        ->where('cliente_id', $clienteId)
                        ->where('estado', 'activo')
                        ->where('fecha_expiracion', '>=', Carbon::now())
                        ->get();

                    Log::info('🔍 Bonos activos encontrados (cobro directo)', [
                        'cliente_id' => $clienteId,
                        'cantidad_bonos' => $bonosActivos->count(),
                        'bonos' => $bonosActivos->map(fn($b) => [
                            'id' => $b->id,
                            'plantilla' => $b->plantilla->nombre ?? 'N/A'
                        ])
                    ]);

                    // Procesar cada servicio del cobro directo
                    foreach ($serviciosData as $servicioData) {
                        $servicioId = (int) $servicioData['id'];
                        $servicio = \App\Models\Servicio::find($servicioId);
                        
                        Log::info('🔄 Procesando servicio de cobro directo', [
                            'servicio_id' => $servicioId,
                            'servicio_nombre' => $servicio->nombre ?? 'N/A'
                        ]);
                        
                        if ($servicio) {
                            // Buscar si hay un bono que incluya este servicio
                            foreach ($bonosActivos as $bono) {
                                $servicioBono = $bono->servicios()
                                    ->where('servicio_id', $servicioId)
                                    ->wherePivot('cantidad_usada', '<', DB::raw('cantidad_total'))
                                    ->first();

                                if ($servicioBono) {
                                    // Hay disponibilidad en el bono, deducir 1
                                    $cantidadUsada = $servicioBono->pivot->cantidad_usada + 1;
                                    
                                    Log::info('✅ APLICANDO BONO (cobro directo)', [
                                        'bono_id' => $bono->id,
                                        'servicio_id' => $servicioId,
                                        'servicio_nombre' => $servicio->nombre,
                                        'cantidad_usada_antes' => $servicioBono->pivot->cantidad_usada,
                                        'cantidad_usada_despues' => $cantidadUsada,
                                        'cantidad_total' => $servicioBono->pivot->cantidad_total
                                    ]);
                                    
                                    $bono->servicios()->updateExistingPivot($servicioId, [
                                        'cantidad_usada' => $cantidadUsada
                                    ]);

                                    $serviciosAplicados[] = $servicio->nombre;
                                    
                                    // Acumular el descuento del servicio cubierto por el bono
                                    $descuentoBonos += $servicio->precio;

                                    // Registrar el uso detallado del bono (sin cita_id porque no hay cita)
                                    BonoUsoDetalle::create([
                                        'bono_cliente_id' => $bono->id,
                                        'cita_id' => null,
                                        'servicio_id' => $servicioId,
                                        'cantidad_usada' => 1
                                    ]);

                                    // NOTA: El precio a 0 se aplicará al guardar los servicios en el pivot

                                    // Verificar si el bono está completamente usado
                                    if ($bono->estaCompletamenteUsado()) {
                                        $bono->update(['estado' => 'usado']);
                                    }

                                    break; // Ya se aplicó un bono para este servicio, pasar al siguiente
                                }
                            }
                        }
                    }
                }
            }
        }

        // NOTA: El total_final que viene del frontend YA tiene descontados los servicios cubiertos por bonos activos
        // No debemos ajustarlo de nuevo. Solo marcamos los servicios como usados en el bono (ya hecho arriba)

        // --- SEPARAR BONOS VENDIDOS DEL TOTAL ---
        $totalBonosVendidos = 0;
        $totalFacturadoServicios = $data['total_final']; // Total facturado de servicios/productos
        
        // Si se vendió un bono, su precio está incluido en total_final del frontend
        // Debemos separarlo para tener el total real de servicios/productos
        if (!empty($data['bono_plantilla_id'])) {
            $bonoPlantilla = \App\Models\BonoPlantilla::find($data['bono_plantilla_id']);
            if ($bonoPlantilla) {
                $totalBonosVendidos = $bonoPlantilla->precio;
                // Restar el precio del bono del total_final para obtener solo servicios/productos
                $totalFacturadoServicios = $data['total_final'] - $totalBonosVendidos;
            }
        }

        // --- Calcular cuánto dinero se pagó y cómo se distribuye ---
        $dineroPagado = $data['dinero_cliente'] ?? 0;
        
        // CASO 1: Si NO hay bono vendido, es simple
        if ($totalBonosVendidos == 0) {
            $deudaServicios = max(0, $totalFacturadoServicios - $dineroPagado);
            // El total cobrado es simplemente el dinero que pagó el cliente (no puede ser más que lo facturado)
            $totalCobradoServicios = min($dineroPagado, $totalFacturadoServicios);
            $deudaBonos = 0;
            $deuda = $deudaServicios;
        }
        // CASO 2: Si hay bono vendido, distribuir el dinero proporcionalmente
        else {
            $totalFacturadoCompleto = $totalFacturadoServicios + $totalBonosVendidos;
            
            if ($dineroPagado >= $totalFacturadoCompleto) {
                // Caso 2A: Se pagó todo
                $totalCobradoServicios = $totalFacturadoServicios;
                $deudaServicios = 0;
                $deudaBonos = 0;
                $deuda = 0;
            } else if ($dineroPagado == 0) {
                // Caso 2B: No se pagó nada, todo queda a deber
                $totalCobradoServicios = 0;
                $deudaServicios = max(0, $totalFacturadoServicios); // Asegurar positivo
                $deudaBonos = max(0, $totalBonosVendidos); // Asegurar positivo
                $deuda = $deudaServicios + $deudaBonos;
            } else {
                // Caso 2C: Pago parcial, distribuir proporcionalmente
                $proporcionServicios = $totalFacturadoCompleto > 0 ? $totalFacturadoServicios / $totalFacturadoCompleto : 0;
                $totalCobradoServicios = max(0, $dineroPagado * $proporcionServicios);
                $deudaServicios = max(0, $totalFacturadoServicios - $totalCobradoServicios);
                $deudaBonos = max(0, $totalBonosVendidos - ($dineroPagado - $totalCobradoServicios));
                $deuda = $deudaServicios + $deudaBonos;
            }
        }

        // --- Determinar id_empleado (nunca debe ser null) ---
        $empleadoId = $data['id_empleado'] ?? null;
        if (!$empleadoId) {
            $user = auth()->user();
            if ($user && $user->empleado) {
                $empleadoId = $user->empleado->id;
            }
        }

        // Validar que se haya determinado un empleado
        if (!$empleadoId) {
            DB::rollBack();
            return back()
                ->withErrors(['id_empleado' => 'No se pudo determinar el empleado para este cobro. Por favor, seleccione un empleado o asegúrese de que su usuario tiene un empleado asociado.'])
                ->withInput();
        }

        // --- DETERMINAR SI EL PAGO FUE COMPLETAMENTE CON BONO ---
        // Si todos los servicios fueron cubiertos por bonos, NO hay productos con costo, Y NO se está vendiendo un bono
        // entonces cambiar método de pago a 'bono'
        // IMPORTANTE: Si se vende un bono, mantener el método de pago original (efectivo/tarjeta/mixto)
        $metodoPagoFinal = $data['metodo_pago'];
        if ($descuentoBonos > 0 && !$seVendeBono && $totalBonosVendidos == 0) {
            // Calcular el costo real de servicios (sin descuentos porcentuales ni productos)
            $costoServicios = 0;
            
            if ($citasAProcesar->isNotEmpty()) {
                foreach ($citasAProcesar as $cita) {
                    if ($cita && $cita->servicios) {
                        $costoServicios += $cita->servicios->sum('precio');
                    }
                }
            } elseif ($request->has('servicios_data') && !empty($data['servicios_data'])) {
                $serviciosData = json_decode($data['servicios_data'], true);
                if (is_array($serviciosData)) {
                    foreach ($serviciosData as $servicio) {
                        $costoServicios += (float) ($servicio['precio'] ?? 0);
                    }
                }
            }
            
            // Si el descuento por bonos cubre todos los servicios (con margen de 0.01 por redondeos)
            // Y no hay productos que requieran pago
            if ($costoServicios > 0 && abs($descuentoBonos - $costoServicios) < 0.01) {
                $metodoPagoFinal = 'bono';
                $data['dinero_cliente'] = 0;
                $data['cambio'] = 0;
            }
        }

        // --- VALIDAR VENTA DE BONO ANTES DE CREAR EL COBRO ---
        if (!empty($data['bono_plantilla_id']) && $clienteId) {
            $bonoPlantilla = \App\Models\BonoPlantilla::with('servicios')->find($data['bono_plantilla_id']);
            
            if ($bonoPlantilla) {
                // VALIDACIÓN: Verificar que no tenga un bono activo con exactamente los mismos servicios Y que tenga usos disponibles
                // 1. Obtener los servicios del bono que se intenta vender
                $serviciosNuevoBono = $bonoPlantilla->servicios->map(function($servicio) {
                    return [
                        'servicio_id' => $servicio->id,
                        'cantidad' => $servicio->pivot->cantidad
                    ];
                })->sortBy('servicio_id')->values()->all();

                // 2. Obtener todos los bonos activos del cliente
                $bonosActivos = \App\Models\BonoCliente::with(['servicios' => function($query) {
                        $query->withPivot('cantidad_total', 'cantidad_usada');
                    }])
                    ->where('cliente_id', $clienteId)
                    ->where('estado', 'activo')
                    ->get();

                // 3. Verificar si algún bono activo tiene exactamente los mismos servicios con usos disponibles
                foreach ($bonosActivos as $bonoActivo) {
                    $serviciosBonoActivo = $bonoActivo->servicios->map(function($servicio) {
                        return [
                            'servicio_id' => $servicio->id,
                            'cantidad' => $servicio->pivot->cantidad_total
                        ];
                    })->sortBy('servicio_id')->values()->all();

                    // Comparar si ambos bonos tienen exactamente los mismos servicios con las mismas cantidades
                    if ($serviciosNuevoBono == $serviciosBonoActivo) {
                        // Verificar si el bono activo tiene usos disponibles en al menos un servicio
                        $tieneUsosDisponibles = false;
                        foreach ($bonoActivo->servicios as $servicio) {
                            $disponibles = $servicio->pivot->cantidad_total - $servicio->pivot->cantidad_usada;
                            if ($disponibles > 0) {
                                $tieneUsosDisponibles = true;
                                break;
                            }
                        }

                        if ($tieneUsosDisponibles) {
                            $nombreBono = $bonoPlantilla->nombre;
                            DB::rollBack();
                            return redirect()->back()->withErrors([
                                'error' => "El cliente ya tiene un bono activo '{$nombreBono}' con estos servicios y todavía le quedan usos disponibles. No se puede vender un bono duplicado hasta que el anterior se haya usado completamente."
                            ])->withInput();
                        }
                    }
                }
            }
        }

        // --- Crear el registro principal ---
        $cobro = RegistroCobro::create([
            'id_cita' => $data['id_cita'] ?? null,
            'coste' => $data['coste'],
            'descuento_porcentaje' => $data['descuento_porcentaje'] ?? 0,
            'descuento_euro' => $data['descuento_euro'] ?? 0, // NO sumar descuento por bonos aquí
            'descuento_servicios_porcentaje' => $data['descuento_servicios_porcentaje'] ?? 0,
            'descuento_servicios_euro' => $data['descuento_servicios_euro'] ?? 0,
            'descuento_productos_porcentaje' => $data['descuento_productos_porcentaje'] ?? 0,
            'descuento_productos_euro' => $data['descuento_productos_euro'] ?? 0,
            'total_final' => $totalCobradoServicios, // SOLO lo que se cobró de servicios/productos (sin bonos, sin deuda)
            'total_bonos_vendidos' => $totalBonosVendidos, // Bonos vendidos separado
            'dinero_cliente' => $data['dinero_cliente'] ?? 0,
            'pago_efectivo' => $metodoPagoFinal === 'mixto' ? ($data['pago_efectivo'] ?? 0) : null,
            'pago_tarjeta' => $metodoPagoFinal === 'mixto' ? ($data['pago_tarjeta'] ?? 0) : null,
            'cambio' => $data['cambio'] ?? 0,
            'metodo_pago' => $metodoPagoFinal, // Usar el método de pago determinado (puede ser 'bono' si todo fue cubierto)
            'id_cliente' => $clienteId,
            'id_empleado' => $empleadoId,
            'deuda' => $deudaServicios, // SOLO la deuda de servicios/productos (la deuda de bonos se maneja en bonos_clientes)
        ]);

        // --- Vincular citas agrupadas si existen ---
        if (!empty($data['citas_ids']) && is_array($data['citas_ids'])) {
            $cobro->citasAgrupadas()->attach($data['citas_ids']);
        }

        // --- VINCULAR SERVICIOS DE CITAS A registro_cobro_servicio ---
        // CRÍTICO: Esto permite calcular correctamente la facturación por empleado
        // y contabilizar servicios realizados por diferentes empleados en una misma cita
        // SOLO si NO hay servicios_data (para evitar duplicación en cobros directos)
        // Y SOLO si el método de pago NO es 'bono' (los servicios pagados con bono no generan facturación)
        if ((!$request->has('servicios_data') || empty($data['servicios_data'])) && $metodoPagoFinal !== 'bono') {
            if (!empty($data['id_cita'])) {
                // Caso 1: Cobro de una sola cita
                $cita = Cita::with('servicios', 'empleado')->find($data['id_cita']);
                if ($cita && $cita->servicios && $cita->servicios->count() > 0) {
                    // Calcular costo total de servicios ANTES de descuentos
                    $costoTotalServicios = $cita->servicios->sum(function($s) {
                        return $s->pivot->precio ?? $s->precio;
                    });
                    
                    if ($costoTotalServicios > 0) {
                        // Calcular el total de productos para restar del total_final
                        $totalProductos = 0;
                        if (isset($data['productos']) && is_array($data['productos'])) {
                            foreach ($data['productos'] as $producto) {
                                if (isset($producto['subtotal'])) {
                                    $totalProductos += $producto['subtotal'];
                                }
                            }
                        }
                        
                        // Calcular proporción de servicios del coste total
                        $proporcionServicios = $data['coste'] > 0 ? $costoTotalServicios / $data['coste'] : 1;
                        
                        // Aplicar proporción al total facturado MENOS productos (que ya tiene descuentos aplicados)
                        // Usar total facturado (incluyendo deuda) para cálculo proporcional
                        $totalServiciosConDescuento = ($totalFacturadoServicios - $totalProductos) * $proporcionServicios;
                        
                        foreach ($cita->servicios as $servicio) {
                            // Calcular precio proporcional del servicio considerando descuentos
                            $precioOriginal = $servicio->pivot->precio ?? $servicio->precio;
                            $proporcion = $precioOriginal / $costoTotalServicios;
                            $precioConDescuento = $totalServiciosConDescuento * $proporcion;
                            
                            // Verificar si este servicio fue pagado con bono
                            // CORRECCIÓN: Buscar SOLO por cita_id y servicio_id (sin ventana de tiempo amplia)
                            $usoBono = DB::table('bono_uso_detalle')
                                ->where('servicio_id', $servicio->id)
                                ->where('cita_id', $cita->id)
                                ->exists();
                            
                            if ($usoBono) {
                                $precioConDescuento = 0; // Servicio pagado con bono
                                Log::info("Cobro #{$cobro->id}: Servicio #{$servicio->id} en cita #{$cita->id} pagado con bono, precio = 0");
                            }
                            
                            $cobro->servicios()->attach($servicio->id, [
                                'precio' => $precioConDescuento,
                                'empleado_id' => $cita->id_empleado, // Por defecto, el empleado de la cita
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            } elseif (!empty($data['citas_ids']) && is_array($data['citas_ids'])) {
                // Caso 2: Cobro de múltiples citas agrupadas
                $citasAgrupadas = Cita::with('servicios', 'empleado')->whereIn('id', $data['citas_ids'])->get();
                
                // Calcular costo total de todos los servicios de todas las citas ANTES de descuentos
                $costoTotalTodosServicios = 0;
                foreach ($citasAgrupadas as $citaGrupo) {
                    if ($citaGrupo->servicios) {
                        $costoTotalTodosServicios += $citaGrupo->servicios->sum(function($s) {
                            return $s->pivot->precio ?? $s->precio;
                        });
                    }
                }
                
                if ($costoTotalTodosServicios > 0) {
                    // Calcular el total de productos para restar del total_final
                    $totalProductos = 0;
                    if (isset($data['productos']) && is_array($data['productos'])) {
                        foreach ($data['productos'] as $producto) {
                            if (isset($producto['subtotal'])) {
                                $totalProductos += $producto['subtotal'];
                            }
                        }
                    }
                    
                    // Calcular proporción de servicios del coste total
                    $proporcionServicios = $data['coste'] > 0 ? $costoTotalTodosServicios / $data['coste'] : 1;
                    
                    // Aplicar proporción al total facturado MENOS productos (que ya tiene descuentos aplicados)
                    // Usar total facturado (incluyendo deuda) para cálculo proporcional
                    $totalServiciosConDescuento = ($totalFacturadoServicios - $totalProductos) * $proporcionServicios;
                    
                    foreach ($citasAgrupadas as $citaGrupo) {
                        if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                            foreach ($citaGrupo->servicios as $servicio) {
                                $precioOriginal = $servicio->pivot->precio ?? $servicio->precio;
                                $proporcion = $precioOriginal / $costoTotalTodosServicios;
                                $precioConDescuento = $totalServiciosConDescuento * $proporcion;
                                
                                // Verificar si este servicio fue pagado con bono
                                // Buscar en una ventana de 24 horas para mayor seguridad
                                $usoBono = DB::table('bono_uso_detalle')
                                    ->where('servicio_id', $servicio->id)
                                    ->where('cita_id', $citaGrupo->id)
                                    ->where('created_at', '>=', now()->subHours(24))
                                    ->exists();
                                
                                if ($usoBono) {
                                    $precioConDescuento = 0; // Servicio pagado con bono
                                    Log::info("Cobro #{$cobro->id}: Servicio #{$servicio->id} en cita agrupada #{$citaGrupo->id} pagado con bono, precio = 0");
                                }
                                
                                $cobro->servicios()->attach($servicio->id, [
                                    'precio' => $precioConDescuento,
                                    'empleado_id' => $citaGrupo->id_empleado, // Empleado de cada cita individual
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }
                }
            }
        }

        // --- Si hay deuda de SERVICIOS, registrarla en el sistema de deudas ---
        // NOTA: La deuda de bonos se registra por separado cuando se crea el BonoCliente
        if ($deudaServicios > 0 && $clienteId) {
            $cliente = Cliente::find($clienteId);
            
            if ($cliente) {
                $deudaCliente = $cliente->obtenerDeuda();
                $nota = "Cobro #" . $cobro->id . (isset($data['id_cita']) && $data['id_cita'] ? " - Cita #" . $data['id_cita'] : " - Venta directa") . " - Servicios/Productos";
                $deudaCliente->registrarCargo($deudaServicios, $nota, null, $cobro->id);
            }
        }

        // --- PROCESAR VENTA DE BONO ---
        // Nota: La validación de bono duplicado ya se hizo ANTES de crear el cobro
        if (!empty($data['bono_plantilla_id']) && $clienteId) {
            $bonoPlantilla = \App\Models\BonoPlantilla::with('servicios')->find($data['bono_plantilla_id']);
            
            if ($bonoPlantilla) {
                // Calcular cuánto se pagó del bono
                $dineroPagadoBono = max(0, $totalBonosVendidos - $deudaBonos);
                
                // Determinar el método de pago del bono y calcular desglose
                $metodoPagoBono = $data['metodo_pago'];
                $pagoEfectivoBono = null;
                $pagoTarjetaBono = null;
                
                if ($deudaBonos >= $totalBonosVendidos) {
                    // El bono queda completamente a deber
                    $metodoPagoBono = 'deuda';
                    $pagoEfectivoBono = 0;
                    $pagoTarjetaBono = 0;
                } elseif ($deudaBonos > 0) {
                    // Pago parcial del bono (raro, pero posible)
                    $metodoPagoBono = 'mixto';
                } else {
                    // Pago completo - calcular desglose según método de pago del cobro
                    if ($metodoPagoBono === 'efectivo') {
                        $pagoEfectivoBono = $dineroPagadoBono;
                        $pagoTarjetaBono = 0;
                    } elseif ($metodoPagoBono === 'tarjeta') {
                        $pagoEfectivoBono = 0;
                        $pagoTarjetaBono = $dineroPagadoBono;
                    } elseif ($metodoPagoBono === 'mixto') {
                        // Para mixto, calcular proporción basándose en el cobro
                        $totalPagosCobro = ($data['pago_efectivo'] ?? 0) + ($data['pago_tarjeta'] ?? 0);
                        if ($totalPagosCobro > 0) {
                            $proporcionEfectivo = ($data['pago_efectivo'] ?? 0) / $totalPagosCobro;
                            $pagoEfectivoBono = $dineroPagadoBono * $proporcionEfectivo;
                            $pagoTarjetaBono = $dineroPagadoBono - $pagoEfectivoBono;
                        } else {
                            // Fallback 50/50
                            $pagoEfectivoBono = $dineroPagadoBono / 2;
                            $pagoTarjetaBono = $dineroPagadoBono / 2;
                        }
                    }
                }
                
                // Crear el bono del cliente
                $bonoCliente = \App\Models\BonoCliente::create([
                    'cliente_id' => $clienteId,
                    'bono_plantilla_id' => $bonoPlantilla->id,
                    'fecha_compra' => Carbon::now(),
                    'fecha_expiracion' => Carbon::now()->addDays($bonoPlantilla->duracion_dias),
                    'estado' => 'activo',
                    'metodo_pago' => $metodoPagoBono,
                    'precio_pagado' => $dineroPagadoBono,
                    'pago_efectivo' => $pagoEfectivoBono,
                    'pago_tarjeta' => $pagoTarjetaBono,
                    'dinero_cliente' => 0,
                    'cambio' => 0,
                    'id_empleado' => $empleadoId,
                ]);
                
                // Si el bono tiene deuda, registrarla en el sistema de deudas
                if ($deudaBonos > 0 && $clienteId) {
                    $cliente = Cliente::find($clienteId);
                    if ($cliente) {
                        $deudaCliente = $cliente->obtenerDeuda();
                        $nota = "Cobro #" . $cobro->id . " - Bono: " . $bonoPlantilla->nombre;
                        $deudaCliente->registrarCargo($deudaBonos, $nota, null, $cobro->id);
                    }
                }

                // Copiar servicios de la plantilla al bono del cliente
                foreach ($bonoPlantilla->servicios as $servicio) {
                    $bonoCliente->servicios()->attach($servicio->id, [
                        'cantidad_total' => $servicio->pivot->cantidad,
                        'cantidad_usada' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Vincular el bono al cobro mediante la tabla pivot
                $cobro->bonosVendidos()->attach($bonoCliente->id, [
                    'precio' => $bonoPlantilla->precio,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Descontar servicios coincidentes del bono
                $serviciosParaDescontar = [];
                
                // Caso 1: Cobro de cita existente
                if (!empty($data['id_cita'])) {
                    $cita = Cita::with('servicios')->find($data['id_cita']);
                    if ($cita && $cita->servicios) {
                        foreach ($cita->servicios as $servicio) {
                            $serviciosParaDescontar[] = [
                                'id' => $servicio->id,
                                'cita_id' => $cita->id
                            ];
                        }
                    }
                }
                
                // Caso 1b: Cobro de múltiples citas agrupadas
                elseif (!empty($data['citas_ids']) && is_array($data['citas_ids'])) {
                    $citasAgrupadas = Cita::with('servicios')->whereIn('id', $data['citas_ids'])->get();
                    foreach ($citasAgrupadas as $citaGrupo) {
                        if ($citaGrupo->servicios) {
                            foreach ($citaGrupo->servicios as $servicio) {
                                $serviciosParaDescontar[] = [
                                    'id' => $servicio->id,
                                    'cita_id' => $citaGrupo->id
                                ];
                            }
                        }
                    }
                }
                
                // Caso 2: Cobro directo SIN CITA con servicios_data
                // SOLO si NO hay cita ni citas agrupadas (evita duplicación)
                elseif ($request->has('servicios_data') && !empty($data['servicios_data'])) {
                    $serviciosData = json_decode($data['servicios_data'], true);
                    if (is_array($serviciosData)) {
                        foreach ($serviciosData as $s) {
                            $serviciosParaDescontar[] = [
                                'id' => (int) $s['id'],
                                'cita_id' => null // No hay cita asociada
                            ];
                        }
                    }
                }
                
                // Procesar descuento de servicios
                foreach ($serviciosParaDescontar as $servicioData) {
                    $servicioId = $servicioData['id'];
                    $citaId = $servicioData['cita_id'];
                    
                    // Verificar si el bono incluye este servicio
                    $servicioBono = $bonoCliente->servicios()
                        ->where('servicio_id', $servicioId)
                        ->first();

                    if ($servicioBono && $servicioBono->pivot->cantidad_usada < $servicioBono->pivot->cantidad_total) {
                        // Descontar 1 del servicio
                        $cantidadUsada = $servicioBono->pivot->cantidad_usada + 1;
                        
                        $bonoCliente->servicios()->updateExistingPivot($servicioId, [
                            'cantidad_usada' => $cantidadUsada
                        ]);

                        // Registrar el uso detallado del bono
                        \App\Models\BonoUsoDetalle::create([
                            'bono_cliente_id' => $bonoCliente->id,
                            'cita_id' => $citaId,
                            'servicio_id' => $servicioId,
                            'cantidad_usada' => 1
                        ]);

                        // Actualizar el precio a 0 en registro_cobro_servicio para que no se facture
                        // Solo actualizar UNO (el primero con precio > 0)
                        $pivotId = DB::table('registro_cobro_servicio')
                            ->where('registro_cobro_id', $cobro->id)
                            ->where('servicio_id', $servicioId)
                            ->where('precio', '>', 0)
                            ->orderBy('id')
                            ->limit(1)
                            ->value('id');
                        
                        if ($pivotId) {
                            DB::table('registro_cobro_servicio')
                                ->where('id', $pivotId)
                                ->update(['precio' => 0]);
                        }
                    }
                }

                // Verificar si el bono está completamente usado
                if ($bonoCliente->estaCompletamenteUsado()) {
                    $bonoCliente->update(['estado' => 'usado']);
                }
            }
        }

        // --- Para cobros directos: procesar servicios_data ---
        if ($request->has('servicios_data') && !empty($data['servicios_data'])) {
            $serviciosData = json_decode($data['servicios_data'], true);
            if (is_array($serviciosData)) {
                foreach ($serviciosData as $s) {
                    $servicioId = (int) $s['id'];
                    $precio = (float) $s['precio'];
                    $empleadoServicio = isset($s['empleado_id']) ? (int) $s['empleado_id'] : null;

                    // Si no hay empleado_id en el servicio, usar el empleado principal del cobro
                    if (!$empleadoServicio) {
                        $empleadoServicio = $empleadoId; // Usar la variable $empleadoId del cobro
                    }

                    // Verificar si este servicio fue pagado con bono ANTES de guardarlo
                    // CORRECCIÓN: Solo buscar si hay citas asociadas específicas
                    $usoBono = false;
                    
                    // Solo verificar si el cobro tiene citas agrupadas
                    if (!empty($data['citas_ids']) && is_array($data['citas_ids'])) {
                        $usoBono = DB::table('bono_uso_detalle')
                            ->where('servicio_id', $servicioId)
                            ->whereIn('cita_id', $data['citas_ids'])
                            ->exists();
                    }
                    // Para cobros directos sin cita: NO buscar en bono_uso_detalle
                    // Los bonos ya se aplicaron en las líneas 614-720
                    
                    if ($usoBono) {
                        $precio = 0; // Servicio pagado con bono, precio 0
                        Log::info("Cobro #{$cobro->id}: Servicio #{$servicioId} en cobro directo pagado con bono, precio = 0");
                    }

                    $cobro->servicios()->attach($servicioId, [
                        'precio' => $precio,
                        'empleado_id' => $empleadoServicio,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // --- Para cobros directos: procesar productos_data ---
        if ($request->has('productos_data') && !empty($data['productos_data'])) {
            $productosData = json_decode($data['productos_data'], true);
            if (is_array($productosData)) {
                foreach ($productosData as $p) {
                    $cantidad = (int) $p['cantidad'];
                    $precio = (float) $p['precio'];
                    $subtotal = $cantidad * $precio;
                    $empleadoIdProducto = isset($p['empleado_id']) && $p['empleado_id'] ? (int) $p['empleado_id'] : null;

                    $producto = Productos::find($p['id']);
                    
                    if (!$producto) {
                        DB::rollBack();
                        return back()
                            ->withErrors(['products' => 'Producto no encontrado: ID ' . $p['id']])
                            ->withInput();
                    }
                    
                    // Verificar stock
                    if ($producto->stock < $cantidad) {
                        DB::rollBack();
                        return back()
                            ->withErrors(['products' => 'Stock insuficiente para: ' . $producto->nombre])
                            ->withInput();
                    }

                    // Descontar del stock
                    $producto->stock -= $cantidad;
                    $producto->save();

                    // Asociar el producto al cobro con el empleado
                    $cobro->productos()->attach($p['id'], [
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'subtotal' => $subtotal,
                        'empleado_id' => $empleadoIdProducto,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // --- IMPORTANTE: Marcar citas como completadas SOLO si el cobro se registró exitosamente ---
        $numCitas = 0;
        if (isset($data['citas_ids']) && is_array($data['citas_ids']) && count($data['citas_ids']) > 0) {
            // COBRO AGRUPADO: Marcar TODAS las citas como completadas
            Cita::whereIn('id', $data['citas_ids'])
                ->where('estado', '!=', 'completada')
                ->update(['estado' => 'completada']);
            $numCitas = count($data['citas_ids']);
        } elseif (!empty($data['id_cita'])) {
            // COBRO INDIVIDUAL: Marcar una sola cita como completada
            $citaParaCompletar = Cita::find($data['id_cita']);
            if ($citaParaCompletar && $citaParaCompletar->estado !== 'completada') {
                $citaParaCompletar->update(['estado' => 'completada']);
            }
            $numCitas = 1;
        }

        // --- Guardar productos asociados (si existen - formato antiguo) ---
        if ($request->has('products')) {
            foreach ($request->products as $p) {
                $cantidad = (int) $p['cantidad'];
                $precio = (float) $p['precio_venta'];
                $subtotal = $cantidad * $precio;
                $empleadoIdProducto = isset($p['empleado_id']) && $p['empleado_id'] ? (int) $p['empleado_id'] : $empleadoId;

                // Obtener el producto para actualizar el stock
                $producto = Productos::find($p['id']);
                
                if (!$producto) {
                    DB::rollBack();
                    return back()
                        ->withErrors(['products' => 'Producto no encontrado: ID ' . $p['id']])
                        ->withInput();
                }

                // Verificar que hay suficiente stock
                if ($producto->stock < $cantidad) {
                    DB::rollBack();
                    return back()
                        ->withErrors(['products' => 'Stock insuficiente para el producto: ' . $producto->nombre . '. Stock disponible: ' . $producto->stock])
                        ->withInput();
                }

                // Descontar del stock
                $producto->stock -= $cantidad;
                $producto->save();

                // Asociar el producto al cobro en la tabla pivot
                $cobro->productos()->attach($p['id'], [
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotal,
                    'empleado_id' => $empleadoIdProducto,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Mensaje de éxito con información de citas agrupadas, deuda y bonos si aplica
        $mensaje = 'Cobro registrado correctamente.';
        if (isset($numCitas) && $numCitas > 1) {
            $mensaje = "🎉 Cobro agrupado de {$numCitas} citas registrado correctamente.";
        }
        if ($deuda > 0) {
            $mensaje .= ' Deuda registrada: €' . number_format($deuda, 2);
        }
        if (!empty($serviciosAplicados)) {
            $mensaje .= ' Servicios aplicados desde bono activo: ' . implode(', ', $serviciosAplicados) . '.';
        }

        DB::commit();
        return $this->redirectWithSuccess('cobros.index', $mensaje);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar cobro: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()
                ->withErrors(['error' => 'Error al registrar el cobro: ' . $e->getMessage()])
                ->withInput();
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(RegistroCobro $cobro){
        $cobro->load(['cita.servicios', 'citasAgrupadas.servicios', 'servicios', 'cliente.user', 'empleado.user', 'productos']);
        return view('cobros.show', compact('cobro'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RegistroCobro $cobro){
        $cobro->load(['cita.servicios', 'citasAgrupadas.servicios', 'servicios', 'cliente.user', 'empleado.user', 'productos']);
        
        $citas = Cita::whereDoesntHave('cobro')
            ->orWhere('id', $cobro->id_cita)
            ->with('cliente.user', 'servicios')
            ->get();

        return view('cobros.edit', compact('cobro', 'citas'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RegistroCobro $cobro){
        $data = $request->validate([
            'id_cita' => 'nullable|exists:citas,id',
            'coste' => 'required|numeric|min:0',
            'total_final' => 'required|numeric|min:0',
            'dinero_cliente' => 'required|numeric|min:0',
            'descuento_porcentaje' => 'nullable|numeric|min:0|max:100',
            'descuento_euro' => 'nullable|numeric|min:0',
            'metodo_pago' => 'required|in:efectivo,tarjeta,mixto,bono,deuda',
            'cambio' => 'nullable|numeric|min:0',
            'pago_efectivo' => 'nullable|numeric|min:0',
            'pago_tarjeta' => 'nullable|numeric|min:0'
        ]);

        // Calcular totales
        $coste = $data['coste'];
        $descuentoPorcentaje = $data['descuento_porcentaje'] ?? 0;
        $descuentoEuro = $data['descuento_euro'] ?? 0;
        $dineroCliente = $data['dinero_cliente'] ?? 0;

        $descuentoTotal = ($coste * ($descuentoPorcentaje / 100)) + $descuentoEuro;
        $totalFinal = $coste - $descuentoTotal;
        $data['total_final'] = round($totalFinal, 2);

        $data['cambio'] = $dineroCliente > 0 ? round($dineroCliente - $data['total_final'], 2) : null;

        // Actualizar la cita asociada (en caso de que se haya cambiado)
        $cobro->update([
            'id_cita' => $data['id_cita'] ?? null,
            'coste' => $data['coste'],
            'descuento_porcentaje' => $descuentoPorcentaje,
            'descuento_euro' => $descuentoEuro,
            'total_final' => $data['total_final'],
            'dinero_cliente' => $dineroCliente,
            'cambio' => $data['cambio'],
            'metodo_pago' => $data['metodo_pago'],
            'pago_efectivo' => $data['metodo_pago'] === 'mixto' ? ($data['pago_efectivo'] ?? 0) : null,
            'pago_tarjeta' => $data['metodo_pago'] === 'mixto' ? ($data['pago_tarjeta'] ?? 0) : null,
        ]);

        return $this->redirectWithSuccess('cobros.index', $this->getUpdatedMessage());
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RegistroCobro $cobro){
        // Restaurar el stock de los productos antes de eliminar el cobro
        foreach ($cobro->productos as $producto) {
            $cantidad = $producto->pivot->cantidad;
            $producto->stock += $cantidad;
            $producto->save();
        }

        $cobro->delete();
        return $this->redirectWithSuccess('cobros.index', 'Cobro eliminado y stock restaurado.');
    }
}
