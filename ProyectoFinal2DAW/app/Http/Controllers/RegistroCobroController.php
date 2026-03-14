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
use App\Models\Empleado;
use App\Models\Servicio;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
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
            'bonosVendidos', // Cargar bonos vendidos para calcular deuda correctamente
            'movimientosDeuda.deuda.cliente.user', // Para identificar pagos de deuda
            // Para mostrar servicios/productos/bonos del cobro ORIGINAL en pagos de deuda
            'movimientosDeuda.deuda.movimientos.registroCobro.servicios',
            'movimientosDeuda.deuda.movimientos.registroCobro.productos',
            'movimientosDeuda.deuda.movimientos.registroCobro.bonosVendidos.plantilla.servicios',
            'movimientosDeuda.deuda.movimientos.registroCobro.empleado.user',
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
            ->with(['cliente.user', 'cliente.deuda', 'servicios', 'empleado', 'cliente.bonosActivos.plantilla.servicios', 'cliente.bonosActivos.servicios' => function($query) {
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
        
        $empleados = Empleado::with('user')->get();
        $servicios = Servicio::where('activo', true)->orderBy('nombre')->get();

        return view('cobros.create', compact('citas', 'citaSeleccionada', 'empleados', 'servicios'));
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
            // dinero_cliente es obligatorio para efectivo (validado en StoreRegistroCobroRequest)
            // Si es menor que total_final, la diferencia se registra como deuda
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
        $servicioIdsCubiertosporBonoActivo = []; // IDs de servicios ya cubiertos por bonos activos (evita doble consumo)
        $bonoUsoDetalleIds = []; // IDs de bono_uso_detalle creados antes del cobro, para vincularlos después
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
        
        // Procesar bonos activos del cliente (independiente de si se vende un bono nuevo,
        // ya que el nuevo bono aún no se ha creado y no hay riesgo de consumirlo)
        Log::info('🎫 PROCESANDO BONOS', [
            'se_vende_bono' => $seVendeBono,
            'cliente_id' => $clienteId,
            'tiene_citas' => $citasAProcesar->isNotEmpty()
        ]);
        
        if ($clienteId) {
            // CASO A: Cobro con citas
            if ($citasAProcesar->isNotEmpty()) {
                // Determinar qué servicios procesar: si hay servicios_data, usar esos (editados por el usuario)
                $serviciosParaBonos = collect();
                $usarServiciosData = $request->has('servicios_data') && !empty($data['servicios_data']);
                
                if ($usarServiciosData) {
                    $serviciosDataBonos = json_decode($data['servicios_data'], true);
                    if (is_array($serviciosDataBonos) && count($serviciosDataBonos) > 0) {
                        foreach ($serviciosDataBonos as $sd) {
                            $serv = \App\Models\Servicio::find((int) $sd['id']);
                            if ($serv) {
                                $serviciosParaBonos->push($serv);
                            }
                        }
                    }
                }
                
                foreach ($citasAProcesar as $cita) {
                    if ($cita && $cita->cliente) {
                        // Obtener bonos activos del cliente (con lock para evitar race conditions)
                        $bonosActivos = BonoCliente::with('servicios')
                            ->where('cliente_id', $cita->cliente->id)
                            ->where('estado', 'activo')
                            ->where('fecha_expiracion', '>=', Carbon::now())
                            ->lockForUpdate()
                            ->get();

                        Log::info('🔍 Bonos activos encontrados', [
                            'cita_id' => $cita->id,
                            'cliente_id' => $cita->cliente->id,
                            'cantidad_bonos' => $bonosActivos->count(),
                            'usando_servicios_data' => $usarServiciosData,
                            'bonos' => $bonosActivos->map(fn($b) => [
                                'id' => $b->id,
                                'plantilla' => $b->plantilla->nombre ?? 'N/A',
                                'fecha_expiracion' => $b->fecha_expiracion?->format('Y-m-d')
                            ])
                        ]);

                        // Usar servicios editados (servicios_data) o los originales de la cita
                        $serviciosAIterar = $usarServiciosData && $serviciosParaBonos->isNotEmpty()
                            ? $serviciosParaBonos
                            : $cita->servicios;

                        // Iterar sobre los servicios
                        foreach ($serviciosAIterar as $servicioCita) {
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
                                    $servicioIdsCubiertosporBonoActivo[] = $servicioCita->id;
                                    
                                    // Acumular el descuento del servicio cubierto por el bono
                                    $descuentoBonos += $servicioCita->precio;

                                    // Registrar el uso detallado del bono
                                    $budCasoA = BonoUsoDetalle::create([
                                        'bono_cliente_id' => $bono->id,
                                        'cita_id' => $cita->id,
                                        'servicio_id' => $servicioCita->id,
                                        'cantidad_usada' => 1
                                    ]);
                                    $bonoUsoDetalleIds[] = $budCasoA->id;

                                    Log::info('📝 Uso de bono registrado', [
                                        'bono_uso_detalle_id' => $budCasoA->id,
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
                    // Obtener bonos activos del cliente (con lock para evitar race conditions)
                    $bonosActivos = BonoCliente::with('servicios')
                        ->where('cliente_id', $clienteId)
                        ->where('estado', 'activo')
                        ->where('fecha_expiracion', '>=', Carbon::now())
                        ->lockForUpdate()
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
                                    $servicioIdsCubiertosporBonoActivo[] = $servicioId;
                                    
                                    // Acumular el descuento del servicio cubierto por el bono
                                    $descuentoBonos += $servicio->precio;

                                    // Registrar el uso detallado del bono (sin cita_id porque no hay cita)
                                    $budCasoB = BonoUsoDetalle::create([
                                        'bono_cliente_id' => $bono->id,
                                        'cita_id' => null,
                                        'servicio_id' => $servicioId,
                                        'cantidad_usada' => 1
                                    ]);
                                    $bonoUsoDetalleIds[] = $budCasoB->id;

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
            $user = Auth::user();
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

        // --- Vincular bono_uso_detalle creados antes del cobro con su registro_cobro_id ---
        if (!empty($bonoUsoDetalleIds)) {
            DB::table('bono_uso_detalle')
                ->whereIn('id', $bonoUsoDetalleIds)
                ->update(['registro_cobro_id' => $cobro->id]);
        }

        // --- Vincular citas agrupadas si existen ---
        if (!empty($data['citas_ids']) && is_array($data['citas_ids'])) {
            $cobro->citasAgrupadas()->attach($data['citas_ids']);
        }

        // --- VINCULAR SERVICIOS DE CITAS A registro_cobro_servicio ---
        // CRÍTICO: Esto permite calcular correctamente la facturación por empleado
        // y contabilizar servicios realizados por diferentes empleados en una misma cita
        // Determinar si hay servicios_data con contenido real (no "[]" vacío)
        $serviciosDataArray = [];
        $tieneServiciosEditados = false;
        if ($request->has('servicios_data') && !empty($data['servicios_data'])) {
            $serviciosDataArray = json_decode($data['servicios_data'], true);
            $tieneServiciosEditados = is_array($serviciosDataArray) && count($serviciosDataArray) > 0;
        }

        Log::info("Cobro #{$cobro->id}: Vinculando servicios", [
            'tieneServiciosEditados' => $tieneServiciosEditados,
            'metodoPago' => $metodoPagoFinal,
            'serviciosCubiertosConBono' => $servicioIdsCubiertosporBonoActivo,
        ]);
        
        // Si hay servicios editados por el usuario, usar esos
        if ($tieneServiciosEditados) {
            foreach ($serviciosDataArray as $s) {
                $servicioId = (int) $s['id'];
                $precio = (float) $s['precio'];
                $empleadoServicio = isset($s['empleado_id']) ? (int) $s['empleado_id'] : null;

                // Si no hay empleado_id en el servicio, usar el empleado principal del cobro
                if (!$empleadoServicio) {
                    $empleadoServicio = $empleadoId;
                }

                // Verificar si este servicio fue pagado con bono activo
                // Usamos el array construido durante el procesamiento de bonos activos (CASO A/B)
                $usoBono = in_array($servicioId, $servicioIdsCubiertosporBonoActivo);

                if ($usoBono) {
                    $precio = 0;
                    Log::info("Cobro #{$cobro->id}: Servicio #{$servicioId} pagado con bono, precio = 0");
                }

                $cobro->servicios()->attach($servicioId, [
                    'precio' => $precio,
                    'empleado_id' => $empleadoServicio,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // --- AJUSTAR PRECIOS PIVOT SI HAY DESCUENTO EN SERVICIOS ---
            // Cuando hay descuento_servicios_euro o descuento_servicios_porcentaje,
            // el frontend envía precios de catálogo pero total_final ya incluye el descuento.
            // Debemos ajustar los precios del pivot proporcionalmente para que:
            //   sumaPivotServicios + sumaPivotProductos = total_final (total cobrado)
            // Esto es CRÍTICO para que FacturacionService distribuya correctamente por empleado.
            $descServEuro = (float) ($data['descuento_servicios_euro'] ?? 0);
            $descServPct = (float) ($data['descuento_servicios_porcentaje'] ?? 0);
            $descGenEuro = (float) ($data['descuento_euro'] ?? 0);
            $descGenPct = (float) ($data['descuento_porcentaje'] ?? 0);
            $descProdEuro = (float) ($data['descuento_productos_euro'] ?? 0);
            $descProdPct = (float) ($data['descuento_productos_porcentaje'] ?? 0);

            // Ajustar servicios solo cuando realmente hay descuento de servicios.
            // Si solo hay descuento en productos, NO tocar precios de servicios.
            $hayDescuentoProductos = ($descProdEuro > 0.01 || $descProdPct > 0.01);
            $hayDescuentoServicios = ($descServEuro > 0.01 || $descServPct > 0.01);
            $hayDescuentoGeneralLegacy = ($descGenEuro > 0.01 || $descGenPct > 0.01) && !$hayDescuentoProductos;

            if ($hayDescuentoServicios || $hayDescuentoGeneralLegacy) {
                $pivotEntries = DB::table('registro_cobro_servicio')
                    ->where('registro_cobro_id', $cobro->id)
                    ->where('precio', '>', 0)
                    ->get();

                $sumaPivotServicios = $pivotEntries->sum('precio');

                // Usar productos YA descontados para no trasladar descuentos de productos a servicios.
                $sumaPivotProductosConDescuento = $totalProductosConDescuento ?? 0;

                // El objetivo es que sumaPivotServicios = totalCobradoServicios - productos_con_descuento
                $objetivoServicios = $totalCobradoServicios - $sumaPivotProductosConDescuento;
                $objetivoServicios = max(0, $objetivoServicios);

                if ($sumaPivotServicios > 0.01 && abs($sumaPivotServicios - $objetivoServicios) > 0.01) {
                    $factorDescuento = $objetivoServicios / $sumaPivotServicios;

                    foreach ($pivotEntries as $entry) {
                        $nuevoPrecio = round($entry->precio * $factorDescuento, 2);
                        DB::table('registro_cobro_servicio')
                            ->where('id', $entry->id)
                            ->update(['precio' => $nuevoPrecio]);
                    }

                    Log::info("Cobro #{$cobro->id}: Precios pivot ajustados por descuento (factor={$factorDescuento}). Pivot {$sumaPivotServicios}€ → {$objetivoServicios}€");
                }
            }
        }
        // Si NO hay servicios editados, usar los originales de la cita
        elseif (!$tieneServiciosEditados) {
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
                        // Usar total de productos DESCONTADOS para aislar el descuento de servicios.
                        $totalProductos = $totalProductosConDescuento ?? 0;
                        
                        // Calcular proporción de servicios del coste total
                        $proporcionServicios = $data['coste'] > 0 ? $costoTotalServicios / $data['coste'] : 1;
                        
                        // Aplicar proporción al total facturado MENOS productos (que ya tiene descuentos aplicados)
                        // Usar total facturado (incluyendo deuda) para cálculo proporcional
                        $totalServiciosConDescuento = max(0, ($totalFacturadoServicios - $totalProductos) * $proporcionServicios);
                        
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
                    // Usar total de productos DESCONTADOS para aislar el descuento de servicios.
                    $totalProductos = $totalProductosConDescuento ?? 0;
                    
                    // Calcular proporción de servicios del coste total
                    $proporcionServicios = $data['coste'] > 0 ? $costoTotalTodosServicios / $data['coste'] : 1;
                    
                    // Aplicar proporción al total facturado MENOS productos (que ya tiene descuentos aplicados)
                    // Usar total facturado (incluyendo deuda) para cálculo proporcional
                    $totalServiciosConDescuento = max(0, ($totalFacturadoServicios - $totalProductos) * $proporcionServicios);
                    
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

        // --- ASIGNACIÓN INTELIGENTE DE DEUDA A SERVICIOS ESPECÍFICOS ---
        // En vez de dejar todos los servicios a precio completo (lo que causa factor proporcional),
        // identificamos qué servicios concretos están en deuda y los ponemos a 0€ en el pivot.
        // Así: sumaPivot = total_final → factorAjuste = 1.0 → facturación correcta por empleado
        if ($deudaServicios > 0.01) {
            $pivotEntries = DB::table('registro_cobro_servicio')
                ->where('registro_cobro_id', $cobro->id)
                ->where('precio', '>', 0)
                ->orderBy('precio', 'desc')
                ->get();
            
            $deudaRestante = round($deudaServicios, 2);
            $serviciosAjustados = [];
            
            // 1. Buscar coincidencia EXACTA con un solo servicio
            $matchExacto = null;
            foreach ($pivotEntries as $entry) {
                if (abs(round($entry->precio, 2) - $deudaRestante) < 0.02) {
                    $matchExacto = $entry;
                    break;
                }
            }
            
            if ($matchExacto) {
                DB::table('registro_cobro_servicio')
                    ->where('id', $matchExacto->id)
                    ->update(['precio' => 0]);
                $serviciosAjustados[] = $matchExacto->id;
                $deudaRestante = 0;
                Log::info("Cobro #{$cobro->id}: Deuda asignada a servicio pivot #{$matchExacto->id} (precio={$matchExacto->precio}€ → 0€, match exacto)");
            } else {
                // 2. Buscar combinación exacta de 2 servicios
                $matchPar = null;
                $entries = $pivotEntries->values();
                for ($i = 0; $i < $entries->count() && !$matchPar; $i++) {
                    for ($j = $i + 1; $j < $entries->count(); $j++) {
                        if (abs(round($entries[$i]->precio + $entries[$j]->precio, 2) - $deudaRestante) < 0.02) {
                            $matchPar = [$entries[$i], $entries[$j]];
                            break;
                        }
                    }
                }
                
                if ($matchPar) {
                    foreach ($matchPar as $entry) {
                        DB::table('registro_cobro_servicio')
                            ->where('id', $entry->id)
                            ->update(['precio' => 0]);
                        $serviciosAjustados[] = $entry->id;
                    }
                    $deudaRestante = 0;
                    Log::info("Cobro #{$cobro->id}: Deuda asignada a par de servicios pivot #{$matchPar[0]->id} + #{$matchPar[1]->id} (match exacto de par)");
                } else {
                    // 3. Greedy: servicios más caros primero hasta cubrir la deuda
                    // El último servicio se reduce parcialmente si es necesario
                    foreach ($pivotEntries as $entry) {
                        if ($deudaRestante <= 0.01) break;
                        if (in_array($entry->id, $serviciosAjustados)) continue;
                        
                        $precioActual = round($entry->precio, 2);
                        
                        if ($precioActual <= $deudaRestante) {
                            // Servicio completo va a deuda
                            DB::table('registro_cobro_servicio')
                                ->where('id', $entry->id)
                                ->update(['precio' => 0]);
                            $deudaRestante = round($deudaRestante - $precioActual, 2);
                            $serviciosAjustados[] = $entry->id;
                            Log::info("Cobro #{$cobro->id}: Deuda greedy - servicio pivot #{$entry->id} completo a 0€ (era {$precioActual}€, resta {$deudaRestante}€)");
                        } else {
                            // Reducción parcial del servicio
                            $nuevoPrecio = round($precioActual - $deudaRestante, 2);
                            DB::table('registro_cobro_servicio')
                                ->where('id', $entry->id)
                                ->update(['precio' => $nuevoPrecio]);
                            Log::info("Cobro #{$cobro->id}: Deuda greedy - servicio pivot #{$entry->id} reducido {$precioActual}€ → {$nuevoPrecio}€ (deuda parcial {$deudaRestante}€)");
                            $deudaRestante = 0;
                        }
                    }
                }
            }
            
            // Verificación: la suma de precios en pivot debe ser ≈ total_final
            if ($deudaRestante > 0.01) {
                Log::warning("Cobro #{$cobro->id}: No se pudo asignar toda la deuda a servicios. Resta: {$deudaRestante}€");
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
                // IMPORTANTE: Si duracion_dias es NULL (sin límite), usar 100 años en lugar de addDays(null)=hoy
                $fechaExpiracionBono = $bonoPlantilla->duracion_dias
                    ? Carbon::now()->addDays($bonoPlantilla->duracion_dias)
                    : Carbon::now()->addYears(100);

                $bonoCliente = \App\Models\BonoCliente::create([
                    'cliente_id' => $clienteId,
                    'bono_plantilla_id' => $bonoPlantilla->id,
                    'fecha_compra' => Carbon::now(),
                    'fecha_expiracion' => $fechaExpiracionBono,
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
                    
                    // SKIP si este servicio ya fue cubierto por un bono activo del cliente
                    if (in_array($servicioId, $servicioIdsCubiertosporBonoActivo)) {
                        continue;
                    }
                    
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
                            'registro_cobro_id' => $cobro->id,
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

        // --- Para cobros directos: procesar productos_data ---
        if ($request->has('productos_data') && !empty($data['productos_data'])) {
            $productosData = json_decode($data['productos_data'], true);
            if (is_array($productosData)) {
                foreach ($productosData as $p) {
                    $cantidad = (int) $p['cantidad'];
                    $precio = (float) $p['precio'];
                    $subtotal = $cantidad * $precio;
                    $empleadoIdProducto = isset($p['empleado_id']) && $p['empleado_id'] ? (int) $p['empleado_id'] : ($cobro->id_empleado ?? null);

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

        // --- Guardar productos asociados (si existen - formato antiguo, solo si no hay productos_data) ---
        if ($request->has('products') && !($request->has('productos_data') && !empty($data['productos_data']))) {
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
        $cobro->load([
            'cita.cliente.user',
            'cita.empleado.user',
            'cita.servicios',
            'citasAgrupadas.cliente.user',
            'citasAgrupadas.empleado.user',
            'citasAgrupadas.servicios',
            'servicios',
            'cliente.user',
            'empleado.user',
            'productos',
            'bonosVendidos.plantilla.servicios',
            'movimientosDeuda.deuda.cliente.user',
            'movimientosDeuda.deuda.movimientos.usuarioRegistro',
            'movimientosDeuda.deuda.movimientos.registroCobro',
            'movimientosDeuda.usuarioRegistro',
        ]);
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

        $clientes = Cliente::with('user')->get();
        $empleados = Empleado::with('user')->get();

        return view('cobros.edit', compact('cobro', 'citas', 'clientes', 'empleados'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RegistroCobro $cobro){
        $data = $request->validate([
            'id_cita' => 'nullable|exists:citas,id',
            'id_cliente' => 'nullable|exists:clientes,id',
            'id_empleado' => 'nullable|exists:empleados,id',
            'servicios_empleado' => 'nullable|array',
            'servicios_empleado.*' => 'nullable|exists:empleados,id',
            'productos_empleado' => 'nullable|array',
            'productos_empleado.*' => 'nullable|exists:empleados,id',
            'coste' => 'required|numeric|min:0',
            'total_final' => 'required|numeric|min:0',
            'dinero_cliente' => 'nullable|numeric|min:0',
            'descuento_porcentaje' => 'nullable|numeric|min:0|max:100',
            'descuento_euro' => 'nullable|numeric|min:0',
            'descuento_servicios_porcentaje' => 'nullable|numeric|min:0|max:100',
            'descuento_servicios_euro' => 'nullable|numeric|min:0',
            'descuento_productos_porcentaje' => 'nullable|numeric|min:0|max:100',
            'descuento_productos_euro' => 'nullable|numeric|min:0',
            'metodo_pago' => 'required|in:efectivo,tarjeta,mixto,bono,deuda',
            'cambio' => 'nullable|numeric|min:0',
            'pago_efectivo' => 'nullable|numeric|min:0',
            'pago_tarjeta' => 'nullable|numeric|min:0'
        ]);

        DB::beginTransaction();
        try {

        // Calcular totales (CONSISTENTE con store(): total_final = lo cobrado, deuda = lo que falta)
        $coste = $data['coste'];
        // Soportar tanto descuentos legacy (general) como separados (servicios/productos)
        $descuentoPorcentaje = $data['descuento_porcentaje'] ?? 0;
        $descuentoEuro = $data['descuento_euro'] ?? 0;
        $descServPct = $data['descuento_servicios_porcentaje'] ?? 0;
        $descServEur = $data['descuento_servicios_euro'] ?? 0;
        $descProdPct = $data['descuento_productos_porcentaje'] ?? 0;
        $descProdEur = $data['descuento_productos_euro'] ?? 0;
        $dineroCliente = $data['dinero_cliente'] ?? 0;

        // Cargar total de productos desde el cobro (no editable desde edit)
        $cobro->load('productos');
        $totalProductos = 0;
        if ($cobro->productos && $cobro->productos->count() > 0) {
            foreach ($cobro->productos as $p) {
                $totalProductos += $p->pivot->subtotal ?? 0;
            }
        }

        // Si hay descuentos separados, aplicar cada descuento a su categoría correspondiente
        if ($descServPct > 0 || $descServEur > 0 || $descProdPct > 0 || $descProdEur > 0) {
            $subtotalServicios = max(0, $coste - ($coste * ($descServPct / 100)) - $descServEur);
            $subtotalProductos = max(0, $totalProductos - ($totalProductos * ($descProdPct / 100)) - $descProdEur);
            $precioConDescuento = round($subtotalServicios + $subtotalProductos, 2);
        } else {
            // Legacy: descuento general solo sobre servicios
            $descuentoTotal = ($coste * ($descuentoPorcentaje / 100)) + $descuentoEuro;
            $precioConDescuento = round(max(0, $coste - $descuentoTotal) + $totalProductos, 2);
        }

        // Lógica según método de pago (misma que store())
        if ($data['metodo_pago'] === 'tarjeta') {
            $dineroCliente = $precioConDescuento;
            $data['dinero_cliente'] = $dineroCliente;
            $data['cambio'] = 0;
        } elseif ($data['metodo_pago'] === 'mixto') {
            $pagoEfectivo = $data['pago_efectivo'] ?? 0;
            $pagoTarjeta = $data['pago_tarjeta'] ?? 0;
            $dineroCliente = $pagoEfectivo + $pagoTarjeta;
            $data['dinero_cliente'] = $dineroCliente;
            $data['cambio'] = 0;
        } elseif ($data['metodo_pago'] === 'efectivo') {
            $data['cambio'] = max(0, round($dineroCliente - $precioConDescuento, 2));
        } else {
            $data['cambio'] = 0;
        }

        // CRÍTICO: total_final = lo que se cobró realmente (excluye deuda), igual que en store()
        $data['total_final'] = min($dineroCliente, $precioConDescuento);
        $nuevaDeuda = max(0, round($precioConDescuento - $dineroCliente, 2));

        // Guardar total_final y deuda anteriores para recalcular
        $totalFinalAnterior = $cobro->total_final;
        $deudaAnterior = $cobro->deuda ?? 0;
        $clienteAnteriorId = $cobro->id_cliente;

        $clienteNuevoId = $data['id_cliente'] ?? $cobro->id_cliente;
        $empleadoNuevoId = $data['id_empleado'] ?? $cobro->id_empleado;

        // Actualizar la cita asociada (en caso de que se haya cambiado)
        $cobro->update([
            'id_cita' => $data['id_cita'] ?? null,
            'id_cliente' => $clienteNuevoId,
            'id_empleado' => $empleadoNuevoId,
            'coste' => $data['coste'],
            'descuento_porcentaje' => $descuentoPorcentaje,
            'descuento_euro' => $descuentoEuro,
            'descuento_servicios_porcentaje' => $descServPct,
            'descuento_servicios_euro' => $descServEur,
            'descuento_productos_porcentaje' => $descProdPct,
            'descuento_productos_euro' => $descProdEur,
            'total_final' => $data['total_final'],
            'dinero_cliente' => $dineroCliente,
            'cambio' => $data['cambio'],
            'metodo_pago' => $data['metodo_pago'],
            'pago_efectivo' => $data['metodo_pago'] === 'mixto' ? ($data['pago_efectivo'] ?? 0) : null,
            'pago_tarjeta' => $data['metodo_pago'] === 'mixto' ? ($data['pago_tarjeta'] ?? 0) : null,
            'deuda' => $nuevaDeuda,
        ]);

        // --- Actualizar empleado por servicio (pivot) ---
        if (isset($data['servicios_empleado']) && is_array($data['servicios_empleado'])) {
            foreach ($data['servicios_empleado'] as $servicioId => $empleadoIdServicio) {
                DB::table('registro_cobro_servicio')
                    ->where('registro_cobro_id', $cobro->id)
                    ->where('servicio_id', (int) $servicioId)
                    ->update([
                        'empleado_id' => $empleadoIdServicio ? (int) $empleadoIdServicio : null,
                        'updated_at' => now(),
                    ]);
            }
        }

        // --- Actualizar empleado por producto (pivot) ---
        if (isset($data['productos_empleado']) && is_array($data['productos_empleado'])) {
            foreach ($data['productos_empleado'] as $productoId => $empleadoIdProducto) {
                DB::table('registro_cobro_productos')
                    ->where('id_registro_cobro', $cobro->id)
                    ->where('id_producto', (int) $productoId)
                    ->update([
                        'empleado_id' => $empleadoIdProducto ? (int) $empleadoIdProducto : null,
                        'updated_at' => now(),
                    ]);
            }
        }

        // --- Ajustar deuda del cliente (incluye cambio de cliente del cobro) ---
        if ($clienteAnteriorId != $clienteNuevoId) {
            if ($clienteAnteriorId) {
                $clienteAnterior = Cliente::find($clienteAnteriorId);
                if ($clienteAnterior) {
                    $deudaAnteriorCliente = $clienteAnterior->obtenerDeuda();
                    $movimientoAnterior = $deudaAnteriorCliente->movimientos()
                        ->where('id_registro_cobro', $cobro->id)
                        ->where('tipo', 'cargo')
                        ->first();

                    $montoARevertir = $movimientoAnterior ? (float) $movimientoAnterior->monto : (float) $deudaAnterior;
                    if ($montoARevertir > 0.01) {
                        $deudaAnteriorCliente->saldo_total = max(0, round($deudaAnteriorCliente->saldo_total - $montoARevertir, 2));
                        $deudaAnteriorCliente->saldo_pendiente = max(0, round($deudaAnteriorCliente->saldo_pendiente - $montoARevertir, 2));
                        $deudaAnteriorCliente->save();
                    }

                    if ($movimientoAnterior) {
                        $movimientoAnterior->delete();
                    }
                }
            }

            if ($clienteNuevoId) {
                $clienteNuevo = Cliente::find($clienteNuevoId);
                if ($clienteNuevo) {
                    $deudaClienteNuevo = $clienteNuevo->obtenerDeuda();
                    $movimientoNuevo = $deudaClienteNuevo->movimientos()
                        ->where('id_registro_cobro', $cobro->id)
                        ->where('tipo', 'cargo')
                        ->first();

                    if ($movimientoNuevo) {
                        $diferenciaMovimiento = round($nuevaDeuda - (float) $movimientoNuevo->monto, 2);
                        if (abs($diferenciaMovimiento) > 0.01) {
                            $deudaClienteNuevo->saldo_total = max(0, round($deudaClienteNuevo->saldo_total + $diferenciaMovimiento, 2));
                            $deudaClienteNuevo->saldo_pendiente = max(0, round($deudaClienteNuevo->saldo_pendiente + $diferenciaMovimiento, 2));
                            $deudaClienteNuevo->save();
                        }
                        $movimientoNuevo->monto = $nuevaDeuda;
                        $movimientoNuevo->save();
                    } elseif ($nuevaDeuda > 0) {
                        $deudaClienteNuevo->saldo_total = max(0, round($deudaClienteNuevo->saldo_total + $nuevaDeuda, 2));
                        $deudaClienteNuevo->saldo_pendiente = max(0, round($deudaClienteNuevo->saldo_pendiente + $nuevaDeuda, 2));
                        $deudaClienteNuevo->save();

                        $deudaClienteNuevo->movimientos()->create([
                            'id_registro_cobro' => $cobro->id,
                            'tipo' => 'cargo',
                            'monto' => $nuevaDeuda,
                            'nota' => 'Cargo por edición de cobro #' . $cobro->id,
                            'usuario_registro_id' => Auth::id() ?? 1,
                        ]);
                    }
                }
            }
        } else {
            $diferenciaDeuda = round($nuevaDeuda - $deudaAnterior, 2);
            if (abs($diferenciaDeuda) > 0.01 && $cobro->id_cliente) {
                $cliente = Cliente::find($cobro->id_cliente);
                if ($cliente) {
                    $deudaCliente = $cliente->obtenerDeuda();
                    $deudaCliente->saldo_total = max(0, round($deudaCliente->saldo_total + $diferenciaDeuda, 2));
                    $deudaCliente->saldo_pendiente = max(0, round($deudaCliente->saldo_pendiente + $diferenciaDeuda, 2));
                    $deudaCliente->save();

                    $movimientoCargo = $deudaCliente->movimientos()
                        ->where('id_registro_cobro', $cobro->id)
                        ->where('tipo', 'cargo')
                        ->first();

                    if ($movimientoCargo) {
                        $movimientoCargo->monto = $nuevaDeuda;
                        $movimientoCargo->save();
                    } elseif ($nuevaDeuda > 0) {
                        $deudaCliente->movimientos()->create([
                            'id_registro_cobro' => $cobro->id,
                            'tipo' => 'cargo',
                            'monto' => $nuevaDeuda,
                            'nota' => 'Cargo por edición de cobro #' . $cobro->id,
                            'usuario_registro_id' => Auth::id() ?? 1,
                        ]);
                    }
                }
            }
        }

        // --- Recalcular precios del pivot registro_cobro_servicio ---
        // Si el total_final cambió, los precios de SERVICIOS del pivot deben ajustarse proporcionalmente
        // para que FacturacionService calcule correctamente la facturación por empleado.
        // Los productos NO se escalan — sus subtotales reflejan la venta original (no son editables).
        if (abs($totalFinalAnterior - $data['total_final']) > 0.01 && $totalFinalAnterior > 0.01) {
            $sumaServiciosPivot = DB::table('registro_cobro_servicio')
                ->where('registro_cobro_id', $cobro->id)
                ->where('precio', '>', 0)
                ->sum('precio');

            if ($sumaServiciosPivot > 0.01) {
                // Restar productos YA descontados, no los brutos del pivot.
                $totalProductosConDescuentoEdit = max(0, $totalProductos - ($totalProductos * ($descProdPct / 100)) - $descProdEur);
                $nuevoTotalServicios = max(0, $data['total_final'] - $totalProductosConDescuentoEdit);
                $factorAjuste = $nuevoTotalServicios / $sumaServiciosPivot;

                $pivotServicios = DB::table('registro_cobro_servicio')
                    ->where('registro_cobro_id', $cobro->id)
                    ->where('precio', '>', 0)
                    ->get();

                foreach ($pivotServicios as $pivot) {
                    DB::table('registro_cobro_servicio')
                        ->where('id', $pivot->id)
                        ->update(['precio' => round($pivot->precio * $factorAjuste, 2)]);
                }
            }
        }

        DB::commit();
        return $this->redirectWithSuccess('cobros.index', $this->getUpdatedMessage());

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Error al actualizar el cobro: ' . $e->getMessage()])
                ->withInput();
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RegistroCobro $cobro){
        $cobro->load(['productos', 'servicios', 'bonosVendidos.servicios', 'citasAgrupadas', 'movimientosDeuda.deuda']);

        DB::beginTransaction();
        try {
            // 1. Restaurar el stock de los productos antes de eliminar el cobro
            foreach ($cobro->productos as $producto) {
                $cantidad = $producto->pivot->cantidad;
                $producto->stock += $cantidad;
                $producto->save();
            }

            // 2. Revertir movimientos de deuda asociados a este cobro
            foreach ($cobro->movimientosDeuda as $movimiento) {
                $deuda = $movimiento->deuda;
                if ($deuda) {
                    if ($movimiento->tipo === 'cargo') {
                        // Revertir cargo: decrementar saldo_total (siempre) y saldo_pendiente (solo lo que aún está pendiente de ESTE cobro)
                        $deuda->saldo_total = max(0, $deuda->saldo_total - $movimiento->monto);
                        $deuda->saldo_pendiente = max(0, $deuda->saldo_pendiente - $cobro->deuda);
                        $deuda->save();
                    } elseif ($movimiento->tipo === 'abono') {
                        // Revertir abono: volver a incrementar saldo_pendiente (el dinero "deja de estar pagado")
                        $deuda->saldo_pendiente += $movimiento->monto;
                        $deuda->save();
                    }
                }
                $movimiento->delete();
            }

            // 3. Revertir usos de bono (servicios que fueron cubiertos por bonos activos del cliente)
            $citaIds = collect();
            if ($cobro->id_cita) {
                $citaIds->push($cobro->id_cita);
            }
            if ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                $citaIds = $citaIds->merge($cobro->citasAgrupadas->pluck('id'));
            }

            $servicioIds = $cobro->servicios->pluck('id')->toArray();

            if (!empty($servicioIds)) {
                // Intentar buscar por registro_cobro_id (migración 2026_03_03)
                // Si no hay resultados (datos antiguos sin registro_cobro_id), usar fallback por cita o temporal
                $query = BonoUsoDetalle::where('registro_cobro_id', $cobro->id);
                $usosDetalle = $query->get();

                // Fallback para datos anteriores a la migración (sin registro_cobro_id)
                if ($usosDetalle->isEmpty()) {
                    $queryFallback = BonoUsoDetalle::whereIn('servicio_id', $servicioIds);

                    if ($citaIds->isNotEmpty()) {
                        $queryFallback->whereIn('cita_id', $citaIds);
                    } else {
                        // Cobro directo sin cita: fallback por proximidad temporal
                        $queryFallback->whereNull('cita_id')
                              ->whereBetween('created_at', [
                                  $cobro->created_at->copy()->subSeconds(30),
                                  $cobro->created_at->copy()->addSeconds(30)
                              ]);
                    }

                    $usosDetalle = $queryFallback->get();
                }

                foreach ($usosDetalle as $uso) {
                    $bonoCliente = BonoCliente::find($uso->bono_cliente_id);
                    if ($bonoCliente) {
                        // Decrementar cantidad_usada en bono_cliente_servicios
                        $servicioBono = $bonoCliente->servicios()
                            ->where('servicio_id', $uso->servicio_id)
                            ->first();

                        if ($servicioBono) {
                            $nuevaCantidad = max(0, $servicioBono->pivot->cantidad_usada - $uso->cantidad_usada);
                            $bonoCliente->servicios()->updateExistingPivot($uso->servicio_id, [
                                'cantidad_usada' => $nuevaCantidad
                            ]);
                        }

                        // Si el bono estaba marcado como 'usado', restaurar a 'activo'
                        if ($bonoCliente->estado === 'usado') {
                            $bonoCliente->refresh();
                            if (!$bonoCliente->estaCompletamenteUsado()) {
                                $bonoCliente->update(['estado' => 'activo']);
                            }
                        }
                    }
                    $uso->delete();
                }
            }

            // 4. Manejar bonos vendidos en este cobro
            foreach ($cobro->bonosVendidos as $bonoVendido) {
                // Verificar si algún servicio del bono ya fue utilizado
                $tieneUsos = $bonoVendido->servicios->contains(function ($servicio) {
                    return $servicio->pivot->cantidad_usada > 0;
                });

                if (!$tieneUsos) {
                    // Bono no usado: eliminar completamente
                    $bonoVendido->servicios()->detach();
                    $bonoVendido->delete();
                    Log::info("Cobro #{$cobro->id}: Bono vendido #{$bonoVendido->id} eliminado (sin usos).");
                } else {
                    // Bono parcialmente usado: no eliminar, solo desvincular del cobro
                    Log::warning("Cobro #{$cobro->id}: Bono vendido #{$bonoVendido->id} tiene usos y no se puede eliminar. Se desvincula del cobro.");
                }
            }
            $cobro->bonosVendidos()->detach();

            // 5. Restaurar estado de citas a 'confirmada'
            if ($cobro->id_cita) {
                $cita = Cita::find($cobro->id_cita);
                if ($cita && $cita->estado === 'completada') {
                    $cita->update(['estado' => 'confirmada']);
                }
            }
            if ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                foreach ($cobro->citasAgrupadas as $citaAgrupada) {
                    if ($citaAgrupada->estado === 'completada') {
                        $citaAgrupada->update(['estado' => 'confirmada']);
                    }
                }
            }

            $cobro->delete();
            DB::commit();

            return $this->redirectWithSuccess('cobros.index', 'Cobro eliminado correctamente. Stock, deuda y bonos restaurados.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar cobro #' . $cobro->id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Error al eliminar el cobro: ' . $e->getMessage()]);
        }
    }
}
