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
            'productos'
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
            ->with(['cliente.user', 'cliente.deuda', 'servicios'])
            ->get();
        
        // Si viene un parámetro cita_id, precargar esa cita
        $citaSeleccionada = null;
        if ($request->has('cita_id')) {
            $citaSeleccionada = Cita::with(['cliente.user', 'cliente.deuda', 'servicios', 'empleado'])
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

        // --- VALIDACIÓN DE INTEGRIDAD: coste debe coincidir con servicios + productos ---
        $totalServiciosCalculado = 0;
        $totalProductosCalculado = 0;
        $detalleValidacion = [];

        // CASO 1: Cobro de cita individual
        if (!empty($data['id_cita'])) {
            $cita = Cita::with('servicios')->find($data['id_cita']);
            if ($cita && $cita->servicios) {
                foreach ($cita->servicios as $servicio) {
                    $precio = $servicio->pivot->precio ?? $servicio->precio;
                    $totalServiciosCalculado += $precio;
                    $detalleValidacion[] = "{$servicio->nombre}: €" . number_format($precio, 2);
                }
            }
        }
        
        // CASO 2: Cobro de múltiples citas agrupadas
        elseif (!empty($data['citas_ids']) && is_array($data['citas_ids'])) {
            $citas = Cita::with('servicios')->whereIn('id', $data['citas_ids'])->get();
            foreach ($citas as $cita) {
                if ($cita->servicios) {
                    foreach ($cita->servicios as $servicio) {
                        $precio = $servicio->pivot->precio ?? $servicio->precio;
                        $totalServiciosCalculado += $precio;
                        $detalleValidacion[] = "{$servicio->nombre}: €" . number_format($precio, 2);
                    }
                }
            }
        }
        
        // CASO 3: Cobro directo con servicios (sin cita)
        if ($request->has('servicios_data') && !empty($data['servicios_data'])) {
            $serviciosData = json_decode($data['servicios_data'], true);
            if (is_array($serviciosData)) {
                foreach ($serviciosData as $s) {
                    $precio = (float) ($s['precio'] ?? 0);
                    $totalServiciosCalculado += $precio;
                    $detalleValidacion[] = "Servicio ID {$s['id']}: €" . number_format($precio, 2);
                }
            }
        }

        // CASO 4: Productos (aplica a todos los tipos de cobro)
        if ($request->has('productos_data') && !empty($data['productos_data'])) {
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

        // Calcular el coste total esperado
        $costeCalculado = $totalServiciosCalculado + $totalProductosCalculado;
        $costeRecibido = (float) $data['coste'];

        // Validar con margen de error de ±€0.01 para redondeos
        $diferencia = abs($costeCalculado - $costeRecibido);
        if ($diferencia > 0.01) {
            $mensajeError = "El coste total no coincide con los servicios/productos.\n\n";
            $mensajeError .= "💰 Coste recibido: €" . number_format($costeRecibido, 2) . "\n";
            $mensajeError .= "🧮 Coste calculado: €" . number_format($costeCalculado, 2) . "\n";
            $mensajeError .= "   - Servicios: €" . number_format($totalServiciosCalculado, 2) . "\n";
            $mensajeError .= "   - Productos: €" . number_format($totalProductosCalculado, 2) . "\n";
            $mensajeError .= "❌ Diferencia: €" . number_format($diferencia, 2) . "\n\n";
            
            if (!empty($detalleValidacion)) {
                $mensajeError .= "📋 Detalle:\n" . implode("\n", $detalleValidacion);
            }

            return back()
                ->withErrors(['coste' => $mensajeError])
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

                        // Iterar sobre los servicios de la cita
                        foreach ($cita->servicios as $servicioCita) {
                            // Buscar si hay un bono que incluya este servicio
                            foreach ($bonosActivos as $bono) {
                                $servicioBono = $bono->servicios()
                                    ->where('servicio_id', $servicioCita->id)
                                    ->wherePivot('cantidad_usada', '<', DB::raw('cantidad_total'))
                                    ->first();

                                if ($servicioBono) {
                                    // Hay disponibilidad en el bono, deducir 1
                                    $cantidadUsada = $servicioBono->pivot->cantidad_usada + 1;
                                    
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
            // CASO B: Cobro directo SIN cita (con servicios_data)
            elseif ($request->has('servicios_data') && !empty($data['servicios_data'])) {
                $serviciosData = json_decode($data['servicios_data'], true);
                
                if (is_array($serviciosData) && count($serviciosData) > 0) {
                    // Obtener bonos activos del cliente
                    $bonosActivos = BonoCliente::with('servicios')
                        ->where('cliente_id', $clienteId)
                        ->where('estado', 'activo')
                        ->where('fecha_expiracion', '>=', Carbon::now())
                        ->get();

                    // Procesar cada servicio del cobro directo
                    foreach ($serviciosData as $servicioData) {
                        $servicioId = (int) $servicioData['id'];
                        $servicio = \App\Models\Servicio::find($servicioId);
                        
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

        // Ajustar el total final restando los servicios cubiertos por bonos
        $totalAjustado = max(0, $data['total_final'] - $descuentoBonos);

        // Recalcular el cambio con el total ajustado
        if ($data['metodo_pago'] === 'efectivo' && $descuentoBonos > 0) {
            $data['cambio'] = max(0, ($data['dinero_cliente'] ?? 0) - $totalAjustado);
        }

        // --- Calcular deuda si el dinero del cliente es menor que el total ajustado ---
        $deuda = max(0, $totalAjustado - ($data['dinero_cliente'] ?? 0));

        // --- Crear el registro principal ---
        $cobro = RegistroCobro::create([
            'id_cita' => $data['id_cita'] ?? null,
            'coste' => $data['coste'],
            'descuento_porcentaje' => $data['descuento_porcentaje'] ?? 0,
            'descuento_euro' => ($data['descuento_euro'] ?? 0) + $descuentoBonos, // Sumar descuento por bonos
            'descuento_servicios_porcentaje' => $data['descuento_servicios_porcentaje'] ?? 0,
            'descuento_servicios_euro' => $data['descuento_servicios_euro'] ?? 0,
            'descuento_productos_porcentaje' => $data['descuento_productos_porcentaje'] ?? 0,
            'descuento_productos_euro' => $data['descuento_productos_euro'] ?? 0,
            'total_final' => $totalAjustado, // Guardar el total ajustado
            'dinero_cliente' => $data['dinero_cliente'] ?? 0,
            'pago_efectivo' => $data['metodo_pago'] === 'mixto' ? ($data['pago_efectivo'] ?? 0) : null,
            'pago_tarjeta' => $data['metodo_pago'] === 'mixto' ? ($data['pago_tarjeta'] ?? 0) : null,
            'cambio' => $data['cambio'] ?? 0,
            'metodo_pago' => $descuentoBonos > 0 && $totalAjustado == 0 ? 'bono' : $data['metodo_pago'], // Si se pagó todo con bono, método = bono
            'id_cliente' => $clienteId,
            'id_empleado' => $data['id_empleado'] ?? null,
            'deuda' => $deuda,
        ]);

        // --- Vincular citas agrupadas si existen ---
        if (!empty($data['citas_ids']) && is_array($data['citas_ids'])) {
            $cobro->citasAgrupadas()->attach($data['citas_ids']);
        }

        // --- Si hay deuda, registrarla en el sistema de deudas ---
        if ($deuda > 0 && $clienteId) {
            $cliente = Cliente::find($clienteId);
            
            if ($cliente) {
                $deudaCliente = $cliente->obtenerDeuda();
                $nota = "Cobro #" . $cobro->id . (isset($data['id_cita']) && $data['id_cita'] ? " - Cita #" . $data['id_cita'] : " - Venta directa");
                $deudaCliente->registrarCargo($deuda, $nota, null, $cobro->id);
            }
        }

        // --- PROCESAR VENTA DE BONO ---
        if (!empty($data['bono_plantilla_id']) && $clienteId) {
            $bonoPlantilla = \App\Models\BonoPlantilla::with('servicios')->find($data['bono_plantilla_id']);
            
            if ($bonoPlantilla) {
                // Crear el bono del cliente
                $bonoCliente = \App\Models\BonoCliente::create([
                    'cliente_id' => $clienteId,
                    'bono_plantilla_id' => $bonoPlantilla->id,
                    'fecha_compra' => Carbon::now(),
                    'fecha_expiracion' => Carbon::now()->addDays($bonoPlantilla->duracion_dias),
                    'estado' => 'activo',
                    'metodo_pago' => $data['metodo_pago'],
                    'precio_pagado' => $bonoPlantilla->precio,
                    'dinero_cliente' => 0,
                    'cambio' => 0,
                    'id_empleado' => $data['id_empleado'] ?? null,
                ]);

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
                    $empleadoId = isset($s['empleado_id']) ? (int) $s['empleado_id'] : null;

                    // Si no hay empleado_id, usar el empleado principal del cobro
                    if (!$empleadoId && !empty($data['id_empleado'])) {
                        $empleadoId = $data['id_empleado'];
                    }

                    $cobro->servicios()->attach($servicioId, [
                        'precio' => $precio,
                        'empleado_id' => $empleadoId,
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

                    $producto = Productos::find($p['id']);
                    
                    if (!$producto) {
                        return back()
                            ->withErrors(['products' => 'Producto no encontrado: ID ' . $p['id']])
                            ->withInput();
                    }
                    
                    // Verificar stock
                    if ($producto->stock < $cantidad) {
                        return back()
                            ->withErrors(['products' => 'Stock insuficiente para: ' . $producto->nombre])
                            ->withInput();
                    }

                    // Descontar del stock
                    $producto->stock -= $cantidad;
                    $producto->save();

                    // Asociar el producto al cobro
                    $cobro->productos()->attach($p['id'], [
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'subtotal' => $subtotal,
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

                // Obtener el producto para actualizar el stock
                $producto = Productos::find($p['id']);
                
                if (!$producto) {
                    return back()
                        ->withErrors(['products' => 'Producto no encontrado: ID ' . $p['id']])
                        ->withInput();
                }

                // Verificar que hay suficiente stock
                if ($producto->stock < $cantidad) {
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
            $mensaje .= ' Servicios aplicados desde bono: ' . implode(', ', $serviciosAplicados) . '.';
            $mensaje .= ' Descuento por bonos: €' . number_format($descuentoBonos, 2) . '.';
        }
        if ($descuentoBonos > 0 && $totalAjustado == 0) {
            $mensaje .= ' ¡Pago completo con bono!';
        }

        return $this->redirectWithSuccess('cobros.index', $mensaje);
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
