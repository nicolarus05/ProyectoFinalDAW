<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Deuda;
use App\Models\MovimientoDeuda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\RegistrarPagoDeudaRequest;
use App\Traits\HasFlashMessages;
use App\Traits\HasCrudMessages;
use App\Traits\HasJsonResponses;

class DeudaController extends Controller
{
    use HasFlashMessages, HasCrudMessages, HasJsonResponses;

    protected function getResourceName(): string
    {
        return 'pago de deuda';
    }
    public function index()
    {
        $clientes = Cliente::conDeuda()
            ->with(['deuda', 'user'])
            ->get();

        $totalDeuda = $clientes->sum('deuda.saldo_pendiente');

        return view('deudas.index', compact('clientes', 'totalDeuda'));
    }

    public function show(Cliente $cliente)
    {
        $deuda = $cliente->obtenerDeuda();
        $movimientos = $deuda->movimientos()
            ->with([
                'usuarioRegistro',
                'registroCobro.cita.servicios',
                'registroCobro.productos'
            ])
            ->paginate(15);

        return view('deudas.show', compact('cliente', 'deuda', 'movimientos'));
    }

    public function crearPago(Cliente $cliente)
    {
        $deuda = $cliente->obtenerDeuda();

        if (!$deuda->tieneDeuda()) {
            return redirect()->route('deudas.show', $cliente)
                ->with('info', 'Este cliente no tiene deudas pendientes.');
        }

        // Obtener los empleados que realizaron servicios en el cobro original
        $ultimoCargo = $deuda->movimientos()
            ->where('tipo', 'cargo')
            ->with(['registroCobro.servicios', 'registroCobro.productos', 'registroCobro.empleado.user'])
            ->latest()
            ->first();
        
        $empleados = collect();
        $empleadoPreseleccionado = null;
        
        if ($ultimoCargo && $ultimoCargo->registroCobro) {
            // Obtener empleados únicos de los servicios
            if ($ultimoCargo->registroCobro->servicios && $ultimoCargo->registroCobro->servicios->count() > 0) {
                $empleadosServicios = $ultimoCargo->registroCobro->servicios
                    ->map(function($servicio) {
                        $empleadoId = $servicio->pivot->empleado_id ?? null;
                        if ($empleadoId) {
                            return \App\Models\Empleado::with('user')->find($empleadoId);
                        }
                        return null;
                    })
                    ->filter()
                    ->unique('id')
                    ->keyBy('id');
                
                $empleados = $empleados->merge($empleadosServicios);
            }
            
            // Obtener empleados de productos (si tienen empleado_id en pivot)
            if ($ultimoCargo->registroCobro->productos && $ultimoCargo->registroCobro->productos->count() > 0) {
                $empleadosProductos = $ultimoCargo->registroCobro->productos
                    ->filter(function($producto) {
                        return isset($producto->pivot->empleado_id);
                    })
                    ->map(function($producto) {
                        return \App\Models\Empleado::with('user')->find($producto->pivot->empleado_id);
                    })
                    ->filter()
                    ->keyBy('id');
                
                $empleados = $empleados->merge($empleadosProductos);
            }
            
            // Si no hay empleados de servicios/productos, usar el empleado del cobro
            if ($empleados->isEmpty() && $ultimoCargo->registroCobro->empleado) {
                $empleados->put(
                    $ultimoCargo->registroCobro->empleado->id,
                    $ultimoCargo->registroCobro->empleado
                );
            }
            
            // Pre-seleccionar el primer empleado (o el que más servicios realizó)
            if ($empleados->isNotEmpty()) {
                $empleadoPreseleccionado = $empleados->first()->id;
            }
        }
        
        // Si no se encontraron empleados, obtener todos los empleados activos como fallback
        if ($empleados->isEmpty()) {
            $empleados = \App\Models\Empleado::with('user')->get()->keyBy('id');
            
            // Intentar pre-seleccionar el empleado logueado si existe
            if (auth()->check() && auth()->user()->empleado) {
                $empleadoPreseleccionado = auth()->user()->empleado->id;
            } elseif ($empleados->isNotEmpty()) {
                $empleadoPreseleccionado = $empleados->first()->id;
            }
        }

        return view('deudas.pago', compact('cliente', 'deuda', 'empleados', 'empleadoPreseleccionado'));
    }

    /**
     * Calcular cómo se distribuirá el pago entre empleados
     */
    public function calcularDistribucion(Cliente $cliente)
    {
        $deuda = $cliente->obtenerDeuda();
        
        if (!$deuda->tieneDeuda()) {
            return response()->json(['error' => 'No hay deuda pendiente'], 400);
        }
        
        // Buscar el cargo MÁS ANTIGUO cuyo cobro aún tiene deuda pendiente
        // ALINEADO con Deuda::registrarAbono() que distribuye pagos al cobro más antiguo primero
        // reorder() elimina el ORDER BY DESC por defecto de la relación movimientos()
        $ultimoCargo = $deuda->movimientos()
            ->where('tipo', 'cargo')
            ->whereHas('registroCobro', function($q) {
                $q->where('deuda', '>', 0);
            })
            ->with(['registroCobro.servicios', 'registroCobro.productos'])
            ->reorder()
            ->orderBy('created_at', 'asc')
            ->first();
        
        $distribucion = [];
        $tieneCobroOriginal = false;
        
        if ($ultimoCargo && $ultimoCargo->registroCobro) {
            $cobroOriginal = $ultimoCargo->registroCobro;
            $tieneCobroOriginal = true;
            
            // --- DISTRIBUCIÓN INTELIGENTE DE DEUDA ---
            // Si el cobro original tiene servicios en deuda (precio=0 por asignación inteligente),
            // mostrar la distribución basada en esos servicios, no en los ya pagados.
            $usarServiciosDeuda = false;
            $serviciosDeuda = collect();
            $totalDeuda = 0;
            
            if ($cobroOriginal->deuda > 0) {
                $servicios = $cobroOriginal->servicios ?? collect();
                
                foreach ($servicios as $servicio) {
                    $pago_actual = round((float)$servicio->pivot->precio, 2);
                    $precio_catalogo = round((float)$servicio->precio, 2);
                    if ($pago_actual < $precio_catalogo) {
                        $servicio->precio_deuda_restante = $precio_catalogo - $pago_actual;
                        $esBono = DB::table('bono_uso_detalle')
                            ->where('servicio_id', $servicio->id)
                            ->where(function($q) use ($cobroOriginal) {
                                if ($cobroOriginal->id_cita) {
                                    // Filtrar también por ventana temporal para evitar falsos positivos
                                    // si la misma cita se reprocesó en otro cobro anterior
                                    $q->where('cita_id', $cobroOriginal->id_cita)
                                      ->whereBetween('created_at', [
                                          $cobroOriginal->created_at->copy()->subMinutes(5),
                                          $cobroOriginal->created_at->copy()->addMinutes(5)
                                      ]);
                                } else {
                                    $q->whereNull('cita_id')
                                      ->whereBetween('created_at', [
                                          $cobroOriginal->created_at->copy()->subMinutes(5),
                                          $cobroOriginal->created_at->copy()->addMinutes(5)
                                      ]);
                                }
                            })
                            ->exists();
                        
                        if (!$esBono) {
                            $serviciosDeuda->push($servicio);
                            $totalDeuda += $servicio->precio_deuda_restante ?? $servicio->precio;
                        }
                    }
                }
                
                if ($serviciosDeuda->count() > 0 && $totalDeuda > 0) {
                    $usarServiciosDeuda = true;
                }
            }
            
            // Calcular total original
            $totalOriginal = 0;
            $servicios = $usarServiciosDeuda ? $serviciosDeuda : ($cobroOriginal->servicios ?? collect());
            $productos = $usarServiciosDeuda ? collect() : ($cobroOriginal->productos ?? collect());
            
            foreach ($servicios as $servicio) {
                $totalOriginal += $usarServiciosDeuda ? $servicio->precio : $servicio->pivot->precio;
            }
            
            foreach ($productos as $producto) {
                $totalOriginal += $producto->pivot->subtotal;
            }
            
            // Agrupar por empleado
            $montoPorEmpleado = [];
            
            foreach ($servicios as $servicio) {
                $empId = $servicio->pivot->empleado_id;
                $precio = $usarServiciosDeuda ? $servicio->precio : $servicio->pivot->precio;
                
                if (!isset($montoPorEmpleado[$empId])) {
                    $empleado = \App\Models\Empleado::with('user')->find($empId);
                    $montoPorEmpleado[$empId] = [
                        'empleado_id' => $empId,
                        'nombre' => $empleado ? $empleado->user->nombre : 'Desconocido',
                        'monto_original' => 0,
                        'servicios' => []
                    ];
                }
                
                $montoPorEmpleado[$empId]['monto_original'] += $precio;
                $montoPorEmpleado[$empId]['servicios'][] = $servicio->nombre;
            }
            
            foreach ($productos as $producto) {
                $empId = $producto->pivot->empleado_id;
                if ($empId) {
                    $subtotal = $producto->pivot->subtotal;
                    
                    if (!isset($montoPorEmpleado[$empId])) {
                        $empleado = \App\Models\Empleado::with('user')->find($empId);
                        $montoPorEmpleado[$empId] = [
                            'empleado_id' => $empId,
                            'nombre' => $empleado ? $empleado->user->nombre : 'Desconocido',
                            'monto_original' => 0,
                            'servicios' => []
                        ];
                    }
                    
                    $montoPorEmpleado[$empId]['monto_original'] += $subtotal;
                    $montoPorEmpleado[$empId]['servicios'][] = $producto->nombre;
                }
            }
            
            // Calcular proporciones para mostrar
            foreach ($montoPorEmpleado as &$datos) {
                $datos['proporcion'] = $totalOriginal > 0 ? ($datos['monto_original'] / $totalOriginal) * 100 : 0;
            }
            
            $distribucion = array_values($montoPorEmpleado);
        }
        
        return response()->json([
            'tiene_cobro_original' => $tieneCobroOriginal,
            'distribucion' => $distribucion,
            'total_original' => $totalOriginal ?? 0
        ]);
    }

    public function registrarPago(RegistrarPagoDeudaRequest $request, Cliente $cliente)
    {
        // Los datos ya vienen validados y sanitizados del Form Request
        $validated = $request->validated();

        $deuda = $cliente->obtenerDeuda();

        if (!$deuda->tieneDeuda()) {
            if ($request->expectsJson()) {
                return $this->errorResponse('Este cliente no tiene deudas pendientes.', 400);
            }
            return $this->redirectWithError('deudas.show', 'Este cliente no tiene deudas pendientes.', ['cliente' => $cliente->id]);
        }

        $monto = $validated['monto'];

        if ($monto > $deuda->saldo_pendiente) {
            if ($request->expectsJson()) {
                return $this->errorResponse(
                    'El monto no puede ser mayor a la deuda pendiente (€' . number_format($deuda->saldo_pendiente, 2) . ')',
                    400
                );
            }
            return back()->withErrors([
                'monto' => 'El monto no puede ser mayor a la deuda pendiente (€' . number_format($deuda->saldo_pendiente, 2) . ')'
            ])->withInput();
        }

        // Buscar el cargo MÁS ANTIGUO cuyo cobro aún tiene deuda pendiente
        // ALINEADO con Deuda::registrarAbono() que distribuye pagos al cobro más antiguo primero
        // reorder() elimina el ORDER BY DESC por defecto de la relación movimientos()
        $ultimoCargo = $deuda->movimientos()
            ->where('tipo', 'cargo')
            ->whereHas('registroCobro', function($q) {
                $q->where('deuda', '>', 0);
            })
            ->with(['registroCobro.servicios', 'registroCobro.productos', 'registroCobro.bonosVendidos', 'registroCobro.cita'])
            ->reorder()
            ->orderBy('created_at', 'asc')
            ->first();
        
        $citaId = null;
        $serviciosOriginales = collect();
        $productosOriginales = collect();
        $bonosDeuda = collect();
        $totalOriginal = 0;
        $usarServiciosDeuda = false; // Flag para distribución inteligente de deuda
        
        // Si hay cobro original con servicios/productos, los usaremos para distribuir automáticamente
        if ($ultimoCargo && $ultimoCargo->registroCobro) {
            $cobroOriginal = $ultimoCargo->registroCobro;
            $citaId = $cobroOriginal->id_cita;
            
            // --- DISTRIBUCIÓN INTELIGENTE DE DEUDA ---
            // Si el cobro original tiene deuda > 0 y servicios con precio = 0 (asignación inteligente),
            // el pago debe ir a los empleados de los servicios EN DEUDA, no a los ya pagados.
            // Servicios con precio=0 pueden ser: (1) pagados con bono, (2) en deuda por asignación inteligente.
            // Distinguimos comprobando bono_uso_detalle.
            if ($cobroOriginal->deuda > 0 && $cobroOriginal->servicios && $cobroOriginal->servicios->count() > 0) {
                $serviciosDeuda = collect();
                $totalDeuda = 0;
                
                foreach ($cobroOriginal->servicios as $servicio) {
                    if (round((float)$servicio->pivot->precio, 2) < round((float)$servicio->precio, 2)) {
                        $servicio->precio_deuda_restante = round((float)$servicio->precio, 2) - round((float)$servicio->pivot->precio, 2);
                        // Comprobar si es servicio de bono (NO deuda)
                        // Filtrar también por ventana temporal para evitar falsos positivos
                        // si la misma cita se reprocesó en otro cobro anterior
                        $esBono = DB::table('bono_uso_detalle')
                            ->where('servicio_id', $servicio->id)
                            ->where(function($q) use ($cobroOriginal) {
                                if ($cobroOriginal->id_cita) {
                                    $q->where('cita_id', $cobroOriginal->id_cita)
                                      ->whereBetween('created_at', [
                                          $cobroOriginal->created_at->copy()->subMinutes(5),
                                          $cobroOriginal->created_at->copy()->addMinutes(5)
                                      ]);
                                } else {
                                    // Ventas directas: buscar por proximidad temporal
                                    $q->whereNull('cita_id')
                                      ->whereBetween('created_at', [
                                          $cobroOriginal->created_at->copy()->subMinutes(5),
                                          $cobroOriginal->created_at->copy()->addMinutes(5)
                                      ]);
                                }
                            })
                            ->exists();
                        
                        if (!$esBono) {
                            // Es un servicio en DEUDA → usar su precio de catálogo
                            $serviciosDeuda->push($servicio);
                            $totalDeuda += $servicio->precio_deuda_restante ?? $servicio->precio;
                        }
                    }
                }
                
                // Si encontramos servicios en deuda, usarlos para la distribución
                if ($serviciosDeuda->count() > 0 && $totalDeuda > 0) {
                    $serviciosOriginales = $serviciosDeuda;
                    $totalOriginal = $totalDeuda;
                    $usarServiciosDeuda = true;
                    Log::info("Pago deuda cliente #{$cobroOriginal->id_cliente}: Distribución inteligente entre " . $serviciosDeuda->count() . " servicios en deuda (total catálogo: {$totalDeuda}€)");
                }
            }
            
            // Si NO hay distribución inteligente, usar el método original
            if (!$usarServiciosDeuda) {
                if ($cobroOriginal->servicios && $cobroOriginal->servicios->count() > 0) {
                    $serviciosOriginales = $cobroOriginal->servicios;
                    $totalOriginal += $serviciosOriginales->sum('pivot.precio');
                }
                
                if ($cobroOriginal->productos && $cobroOriginal->productos->count() > 0) {
                    $productosOriginales = $cobroOriginal->productos;
                    $totalOriginal += $productosOriginales->sum('pivot.subtotal');
                }
            }
            
            // --- BONOS VENDIDOS COMO DEUDA ---
            // Si el cobro original tiene bonos que quedaron a deber, incluirlos
            // para que el pago de deuda se registre como bono (no como servicio)
            if ($cobroOriginal->bonosVendidos && $cobroOriginal->bonosVendidos->count() > 0) {
                foreach ($cobroOriginal->bonosVendidos as $bono) {
                    if ($bono->metodo_pago === 'deuda') {
                        $deudaBonoPendiente = max(0, ($bono->pivot->precio ?? 0) - ($bono->precio_pagado ?? 0));
                        if ($deudaBonoPendiente > 0.01) {
                            $bono->deuda_pendiente_bono = $deudaBonoPendiente;
                            $bonosDeuda->push($bono);
                            $totalOriginal += $deudaBonoPendiente;
                        }
                    }
                }
            }
        }
        
        // Calcular empleado principal (el que más dinero recibe o el del primer servicio)
        $empleadoPrincipalId = null;
        
        if ($serviciosOriginales->count() > 0 || $productosOriginales->count() > 0 || $bonosDeuda->count() > 0) {
            // Distribución automática basada en servicios/productos/bonos originales
            // Encontrar el empleado que más cobra
            $montoPorEmpleado = [];
            
            foreach ($serviciosOriginales as $servicio) {
                $empId = $servicio->pivot->empleado_id;
                // Para servicios en deuda: usar precio de catálogo. Para pagados: usar precio del pivot.
                $precio = $usarServiciosDeuda ? $servicio->precio : $servicio->pivot->precio;
                $montoPorEmpleado[$empId] = ($montoPorEmpleado[$empId] ?? 0) + $precio;
            }
            
            foreach ($productosOriginales as $producto) {
                $empId = $producto->pivot->empleado_id ?? null;
                if ($empId) {
                    $subtotal = $producto->pivot->subtotal;
                    $montoPorEmpleado[$empId] = ($montoPorEmpleado[$empId] ?? 0) + $subtotal;
                }
            }
            
            // Considerar bonos en deuda para el empleado principal
            foreach ($bonosDeuda as $bono) {
                $empId = $bono->id_empleado ?? null;
                if ($empId) {
                    $montoPorEmpleado[$empId] = ($montoPorEmpleado[$empId] ?? 0) + ($bono->deuda_pendiente_bono ?? 0);
                }
            }
            
            // Empleado principal = el que más dinero cobra
            if (!empty($montoPorEmpleado)) {
                $empleadoPrincipalId = array_key_first(array_filter($montoPorEmpleado, fn($m) => $m == max($montoPorEmpleado)));
            }
        } else {
            // Sin cobro original: usar empleado seleccionado manualmente
            $empleadoPrincipalId = $validated['empleado_id'] ?? null;
            
            if (!$empleadoPrincipalId) {
                return back()->withErrors([
                    'empleado_id' => 'Debe seleccionar un empleado para este pago.'
                ])->withInput();
            }
        }
        
        // Calcular cuánto del monto corresponde a bonos vs servicios/productos
        $montoBonosDeuda = 0;
        if ($bonosDeuda->count() > 0 && $totalOriginal > 0) {
            $totalReferenciasBonos = $bonosDeuda->sum('deuda_pendiente_bono');
            // Proporción de bonos respecto al total original
            $proporcionBonos = $totalReferenciasBonos / $totalOriginal;
            $montoBonosDeuda = round($monto * $proporcionBonos, 2);
        }
        $montoServiciosDeuda = round($monto - $montoBonosDeuda, 2);
        
        // Crear registro de cobro para la caja del día
        $registroCobro = \App\Models\RegistroCobro::create([
            'id_cita' => $citaId,
            'id_cliente' => $cliente->id,
            'id_empleado' => $empleadoPrincipalId,
            'coste' => $monto,
            'total_final' => $montoServiciosDeuda,
            'total_bonos_vendidos' => $montoBonosDeuda,
            'metodo_pago' => $validated['metodo_pago'],
            'deuda' => 0,
            'dinero_cliente' => $monto,
            'pago_efectivo' => $validated['metodo_pago'] === 'efectivo' ? $monto : 0,
            'pago_tarjeta' => $validated['metodo_pago'] === 'tarjeta' ? $monto : 0,
            'cambio' => 0,
            'contabilizado' => true,
        ]);
        
        // Vincular servicios/productos/bonos con distribución proporcional exacta (sin drift de centimos)
        if (($serviciosOriginales->count() > 0 || $productosOriginales->count() > 0 || $bonosDeuda->count() > 0) && $totalOriginal > 0) {
            $lineasDistribucion = [];

            foreach ($serviciosOriginales as $index => $servicio) {
                $precioReferencia = $usarServiciosDeuda ? ((float) ($servicio->precio_deuda_restante ?? $servicio->precio)) : (float) $servicio->pivot->precio;
                $lineaId = 'servicio_' . $index;
                $lineasDistribucion[] = [
                    'id' => $lineaId,
                    'tipo' => 'servicio',
                    'referencia' => max(0, $precioReferencia),
                    'modelo' => $servicio,
                ];
            }

            foreach ($productosOriginales as $index => $producto) {
                $subtotalReferencia = (float) ($producto->pivot->subtotal ?? 0);
                $lineaId = 'producto_' . $index;
                $lineasDistribucion[] = [
                    'id' => $lineaId,
                    'tipo' => 'producto',
                    'referencia' => max(0, $subtotalReferencia),
                    'modelo' => $producto,
                ];
            }

            foreach ($bonosDeuda as $index => $bono) {
                $lineaId = 'bono_' . $index;
                $lineasDistribucion[] = [
                    'id' => $lineaId,
                    'tipo' => 'bono',
                    'referencia' => max(0, (float) $bono->deuda_pendiente_bono),
                    'modelo' => $bono,
                ];
            }

            $importesDistribuidos = $this->distribuirImportesProporcionalmente($lineasDistribucion, (float) $monto);

            foreach ($lineasDistribucion as $linea) {
                $importeLinea = (float) ($importesDistribuidos[$linea['id']] ?? 0);

                if ($linea['tipo'] === 'bono') {
                    $bono = $linea['modelo'];
                    $registroCobro->bonosVendidos()->attach($bono->id, [
                        'precio' => $importeLinea,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    // Actualizar precio_pagado del bono original
                    $bono->update([
                        'precio_pagado' => ($bono->precio_pagado ?? 0) + $importeLinea,
                    ]);
                    continue;
                }

                if ($linea['tipo'] === 'servicio') {
                    $servicio = $linea['modelo'];
                    $registroCobro->servicios()->attach($servicio->id, [
                        'empleado_id' => $servicio->pivot->empleado_id,
                        'precio' => $importeLinea,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    continue;
                }

                $producto = $linea['modelo'];
                $registroCobro->productos()->attach($producto->id, [
                    'cantidad' => $producto->pivot->cantidad,
                    'precio_unitario' => $producto->pivot->precio_unitario,
                    'subtotal' => $importeLinea,
                    'empleado_id' => $producto->pivot->empleado_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Si NO hay servicios ni productos originales, el cobro quedará sin vincular
        // y el CASO ESPECIAL en facturacionPorFechas() usará el coste completo para el empleado principal

        // Registrar el abono en la deuda vinculado al registro de cobro
        $deuda->registrarAbono(
            $validated['monto'],
            $validated['metodo_pago'],
            $validated['nota'] ?? 'Pago de deuda',
            $registroCobro->id
        );

        $mensaje = $deuda->saldo_pendiente > 0
            ? 'Pago registrado. Deuda restante: €' . number_format($deuda->saldo_pendiente, 2)
            : 'Pago registrado. Deuda saldada completamente.';

        if ($request->expectsJson()) {
            return $this->successResponse([
                'deuda_restante' => $deuda->saldo_pendiente
            ], $mensaje);
        }

        return $this->redirectWithSuccess('deudas.show', $mensaje, ['cliente' => $cliente->id]);
    }

    public function historial(Cliente $cliente)
    {
        $deuda = $cliente->obtenerDeuda();
        $movimientos = $deuda->movimientos()->with('usuarioRegistro')->get();

        return view('deudas.historial', compact('cliente', 'deuda', 'movimientos'));
    }

    /**
     * Distribuye un monto total entre lineas de forma proporcional sin perder centimos.
     * Usa asignacion por restos mayores en centimos para asegurar suma exacta.
     *
     * @param array<int, array{id:string, referencia:float}> $lineas
     * @return array<string, float>
     */
    protected function distribuirImportesProporcionalmente(array $lineas, float $monto): array
    {
        $resultado = [];
        foreach ($lineas as $linea) {
            $resultado[$linea['id']] = 0.0;
        }

        if (empty($lineas)) {
            return $resultado;
        }

        $montoCentimos = (int) round(max(0, $monto) * 100);
        $totalReferencia = array_sum(array_map(fn($linea) => max(0, (float) ($linea['referencia'] ?? 0)), $lineas));

        if ($montoCentimos <= 0 || $totalReferencia <= 0) {
            return $resultado;
        }

        $asignacionBase = [];
        $restos = [];
        $sumaBase = 0;

        foreach ($lineas as $linea) {
            $lineaId = $linea['id'];
            $referencia = max(0, (float) ($linea['referencia'] ?? 0));
            $cuotaExacta = ($referencia / $totalReferencia) * $montoCentimos;
            $baseCentimos = (int) floor($cuotaExacta);

            $asignacionBase[$lineaId] = $baseCentimos;
            $restos[] = [
                'id' => $lineaId,
                'resto' => $cuotaExacta - $baseCentimos,
            ];
            $sumaBase += $baseCentimos;
        }

        $centimosPendientes = $montoCentimos - $sumaBase;
        usort($restos, fn($a, $b) => $b['resto'] <=> $a['resto']);

        $index = 0;
        $totalRestos = count($restos);
        while ($centimosPendientes > 0 && $totalRestos > 0) {
            $id = $restos[$index % $totalRestos]['id'];
            $asignacionBase[$id]++;
            $centimosPendientes--;
            $index++;
        }

        foreach ($asignacionBase as $id => $centimos) {
            $resultado[$id] = round($centimos / 100, 2);
        }

        return $resultado;
    }
}
