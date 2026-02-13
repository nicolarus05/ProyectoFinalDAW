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
        
        // Buscar el cobro original
        $ultimoCargo = $deuda->movimientos()
            ->where('tipo', 'cargo')
            ->with(['registroCobro.servicios', 'registroCobro.productos'])
            ->latest()
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
                    if ($servicio->pivot->precio == 0) {
                        $esBono = DB::table('bono_uso_detalle')
                            ->where('servicio_id', $servicio->id)
                            ->where(function($q) use ($cobroOriginal) {
                                if ($cobroOriginal->id_cita) {
                                    $q->where('cita_id', $cobroOriginal->id_cita);
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
                            $totalDeuda += $servicio->precio;
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
                        'nombre' => $empleado ? $empleado->user->name : 'Desconocido',
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
                            'nombre' => $empleado ? $empleado->user->name : 'Desconocido',
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

        // Buscar el cargo original (cuando se generó la deuda) para obtener los servicios y productos originales
        $ultimoCargo = $deuda->movimientos()
            ->where('tipo', 'cargo')
            ->with(['registroCobro.servicios', 'registroCobro.productos', 'registroCobro.cita'])
            ->latest()
            ->first();
        
        $citaId = null;
        $serviciosOriginales = collect();
        $productosOriginales = collect();
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
                    if ($servicio->pivot->precio == 0) {
                        // Comprobar si es servicio de bono (NO deuda)
                        $esBono = DB::table('bono_uso_detalle')
                            ->where('servicio_id', $servicio->id)
                            ->where(function($q) use ($cobroOriginal) {
                                if ($cobroOriginal->id_cita) {
                                    $q->where('cita_id', $cobroOriginal->id_cita);
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
                            $totalDeuda += $servicio->precio;
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
        }
        
        // Calcular empleado principal (el que más dinero recibe o el del primer servicio)
        $empleadoPrincipalId = null;
        
        if ($serviciosOriginales->count() > 0 || $productosOriginales->count() > 0) {
            // Distribución automática basada en servicios/productos originales
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
        
        // Crear registro de cobro para la caja del día
        $registroCobro = \App\Models\RegistroCobro::create([
            'id_cita' => $citaId,
            'id_cliente' => $cliente->id,
            'id_empleado' => $empleadoPrincipalId,
            'coste' => $monto,
            'total_final' => $monto,
            'metodo_pago' => $validated['metodo_pago'],
            'deuda' => 0,
            'dinero_cliente' => $monto,
            'pago_efectivo' => $validated['metodo_pago'] === 'efectivo' ? $monto : 0,
            'pago_tarjeta' => $validated['metodo_pago'] === 'tarjeta' ? $monto : 0,
            'cambio' => 0,
            'contabilizado' => true,
        ]);
        
        // Vincular servicios originales con distribución proporcional al pago
        if ($serviciosOriginales->count() > 0 && $totalOriginal > 0) {
            foreach ($serviciosOriginales as $servicio) {
                $empleadoServicio = $servicio->pivot->empleado_id;
                
                // Para servicios en deuda: usar precio de catálogo como referencia
                // Para servicios pagados normales: usar precio del pivot
                $precioReferencia = $usarServiciosDeuda ? $servicio->precio : $servicio->pivot->precio;
                
                // Calcular precio proporcional al monto pagado
                $precioProporcion = ($precioReferencia / $totalOriginal) * $monto;
                
                $registroCobro->servicios()->attach($servicio->id, [
                    'empleado_id' => $empleadoServicio, // Empleado original del servicio
                    'precio' => round($precioProporcion, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Vincular productos originales con distribución proporcional al pago
        if ($productosOriginales->count() > 0 && $totalOriginal > 0) {
            foreach ($productosOriginales as $producto) {
                $subtotalOriginal = $producto->pivot->subtotal;
                $empleadoProducto = $producto->pivot->empleado_id;
                
                // Calcular subtotal proporcional al monto pagado
                $subtotalProporcion = ($subtotalOriginal / $totalOriginal) * $monto;
                
                $registroCobro->productos()->attach($producto->id, [
                    'cantidad' => $producto->pivot->cantidad,
                    'precio_unitario' => $producto->pivot->precio_unitario,
                    'subtotal' => $subtotalProporcion,
                    'empleado_id' => $empleadoProducto,
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
}
