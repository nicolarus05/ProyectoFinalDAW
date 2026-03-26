<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BonoPlantilla;
use App\Models\BonoCliente;
use App\Models\RegistroCobro;
use App\Models\Servicio;
use App\Models\Cliente;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\BonoClienteResource;
use App\Traits\HasFlashMessages;
use App\Traits\HasCrudMessages;
use App\Traits\HasJsonResponses;

class BonoController extends Controller
{
    use HasFlashMessages, HasCrudMessages, HasJsonResponses;

    protected function getResourceName(): string
    {
        return 'bono';
    }
    /**
     * Listar plantillas de bonos
     */
    public function index()
    {
        $plantillas = BonoPlantilla::with('servicios')->where('activo', true)->get();
        return view('bonos.index', compact('plantillas'));
    }

    /**
     * Mostrar formulario para crear plantilla de bono
     */
    public function create()
    {
        $servicios = Servicio::where('activo', true)->get();
        return view('bonos.create', compact('servicios'));
    }

    /**
     * Guardar nueva plantilla de bono
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'duracion_tipo' => 'required|in:30,sin_limite',
            'servicios' => 'required|array|min:1',
            'servicios.*.id' => 'required|exists:servicios,id',
            'servicios.*.cantidad' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            // Determinar duración en días (NULL = sin límite)
            $duracionDias = $request->duracion_tipo === 'sin_limite' ? null : 30;

            $plantilla = BonoPlantilla::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'precio' => $request->precio,
                'duracion_dias' => $duracionDias,
                'activo' => true
            ]);

            // Adjuntar servicios con sus cantidades
            foreach ($request->servicios as $servicio) {
                $plantilla->servicios()->attach($servicio['id'], [
                    'cantidad' => $servicio['cantidad']
                ]);
            }

            DB::commit();
            return $this->redirectWithSuccess('bonos.index', $this->getCreatedMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando bono plantilla: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error al crear el bono.'])->withInput();
        }
    }

    /**
     * Mostrar formulario de compra de bono para un cliente
     */
    public function comprar($plantillaId)
    {
        $plantilla = BonoPlantilla::with('servicios')->findOrFail($plantillaId);
        $clientes = Cliente::with('user')->get();
        $empleados = Empleado::with('user')->get();
        return view('bonos.comprar', compact('plantilla', 'clientes', 'empleados'));
    }

    /**
     * Procesar compra de bono por un cliente
     */
    public function procesarCompra(Request $request, $plantillaId)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'id_empleado' => 'required|exists:empleados,id',
            'metodo_pago' => 'required|in:efectivo,tarjeta,mixto',
            'dinero_cliente' => 'nullable|numeric|min:0',
            'pago_efectivo' => 'nullable|numeric|min:0',
            'pago_tarjeta' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $plantilla = BonoPlantilla::with('servicios')->findOrFail($plantillaId);
            $clienteId = $request->cliente_id;
            $metodoPago = $request->metodo_pago;

            // Verificar que no tenga un bono activo con exactamente los mismos servicios Y que tenga usos disponibles
            // 1. Obtener los servicios del bono que se intenta vender
            $serviciosNuevoBono = $plantilla->servicios->map(function($servicio) {
                return [
                    'servicio_id' => $servicio->id,
                    'cantidad' => $servicio->pivot->cantidad
                ];
            })->sortBy('servicio_id')->values()->all();

            Log::info('Intentando vender bono', [
                'plantilla_id' => $plantilla->id,
                'plantilla_nombre' => $plantilla->nombre,
                'cliente_id' => $clienteId,
                'servicios_nuevo_bono' => $serviciosNuevoBono
            ]);

            // 2. Obtener todos los bonos activos del cliente
            $bonosActivos = BonoCliente::with(['servicios' => function($query) {
                    $query->withPivot('cantidad_total', 'cantidad_usada');
                }])
                ->where('cliente_id', $clienteId)
                ->where('estado', 'activo')
                ->get();

            Log::info('Bonos activos del cliente', [
                'cantidad' => $bonosActivos->count()
            ]);

            // 3. Verificar si algún bono activo tiene exactamente los mismos servicios con usos disponibles
            foreach ($bonosActivos as $bonoActivo) {
                $serviciosBonoActivo = $bonoActivo->servicios->map(function($servicio) {
                    return [
                        'servicio_id' => $servicio->id,
                        'cantidad' => $servicio->pivot->cantidad_total
                    ];
                })->sortBy('servicio_id')->values()->all();

                Log::info('Comparando bonos', [
                    'bono_activo_id' => $bonoActivo->id,
                    'servicios_bono_activo' => $serviciosBonoActivo,
                    'servicios_nuevo_bono' => $serviciosNuevoBono,
                    'son_iguales' => $serviciosNuevoBono == $serviciosBonoActivo
                ]);

                // Comparar si ambos bonos tienen exactamente los mismos servicios con las mismas cantidades
                if ($serviciosNuevoBono == $serviciosBonoActivo) {
                    // Verificar si el bono activo tiene usos disponibles en al menos un servicio
                    $tieneUsosDisponibles = false;
                    foreach ($bonoActivo->servicios as $servicio) {
                        $disponibles = $servicio->pivot->cantidad_total - $servicio->pivot->cantidad_usada;
                        Log::info('Verificando servicio', [
                            'servicio_id' => $servicio->id,
                            'servicio_nombre' => $servicio->nombre,
                            'cantidad_total' => $servicio->pivot->cantidad_total,
                            'cantidad_usada' => $servicio->pivot->cantidad_usada,
                            'disponibles' => $disponibles
                        ]);
                        if ($disponibles > 0) {
                            $tieneUsosDisponibles = true;
                            break;
                        }
                    }

                    Log::info('Resultado validación', [
                        'tiene_usos_disponibles' => $tieneUsosDisponibles
                    ]);

                    if ($tieneUsosDisponibles) {
                        $nombreBono = $plantilla->nombre;
                        DB::rollBack();
                        return redirect()->back()->withErrors([
                            'error' => "El cliente ya tiene un bono activo '{$nombreBono}' con estos servicios y todavía le quedan usos disponibles. No se puede vender un bono duplicado hasta que el anterior se haya usado completamente."
                        ])->withInput();
                    }
                }
            }

            // Calcular pago
            $precioTotal = $plantilla->precio;
            $dineroCliente = $request->dinero_cliente ?? 0;
            $cambio = 0;
            $pagoEfectivo = null;
            $pagoTarjeta = null;

            if ($metodoPago === 'efectivo') {
                if ($dineroCliente < $precioTotal) {
                    DB::rollBack();
                    return redirect()->back()->withErrors(['dinero_cliente' => 'El dinero del cliente debe ser al menos €' . number_format($precioTotal, 2)])->withInput();
                }
                $cambio = $dineroCliente - $precioTotal;
                $pagoEfectivo = $precioTotal;
                $pagoTarjeta = 0;
            } elseif ($metodoPago === 'mixto') {
                // Pago mixto: validar que la suma cubra el precio
                $pagoEfectivo = $request->pago_efectivo ?? 0;
                $pagoTarjeta = $request->pago_tarjeta ?? 0;
                $totalPagado = $pagoEfectivo + $pagoTarjeta;
                
                if ($totalPagado < $precioTotal) {
                    DB::rollBack();
                    return redirect()->back()->withErrors(['pago_efectivo' => 'La suma de efectivo y tarjeta debe ser al menos €' . number_format($precioTotal, 2) . '. Actualmente: €' . number_format($totalPagado, 2)])->withInput();
                }
                
                // Si pagaron de más, el exceso se devuelve como cambio del efectivo
                $cambio = $totalPagado - $precioTotal;
                $dineroCliente = $pagoEfectivo; // El dinero en mano es el efectivo
            } else {
                // Tarjeta: pago exacto
                $dineroCliente = $precioTotal;
                $cambio = 0;
                $pagoEfectivo = 0;
                $pagoTarjeta = $precioTotal;
            }

            // Crear bono para el cliente
            $fechaCompra = Carbon::now();
            // Si duracion_dias es NULL (sin límite), fecha_expiracion será muy lejana (100 años)
            $fechaExpiracion = $plantilla->duracion_dias 
                ? $fechaCompra->copy()->addDays($plantilla->duracion_dias)
                : $fechaCompra->copy()->addYears(100);

            $bonoCliente = BonoCliente::create([
                'cliente_id' => $clienteId,
                'bono_plantilla_id' => $plantilla->id,
                'fecha_compra' => $fechaCompra,
                'fecha_expiracion' => $fechaExpiracion,
                'estado' => 'activo',
                'metodo_pago' => $metodoPago,
                'precio_pagado' => $precioTotal,
                'pago_efectivo' => $pagoEfectivo,
                'pago_tarjeta' => $pagoTarjeta,
                'dinero_cliente' => $dineroCliente,
                'cambio' => $cambio,
                'id_empleado' => $request->id_empleado
            ]);

            // Copiar servicios de la plantilla al bono del cliente
            foreach ($plantilla->servicios as $servicio) {
                $bonoCliente->servicios()->attach($servicio->id, [
                    'cantidad_total' => $servicio->pivot->cantidad,
                    'cantidad_usada' => 0
                ]);
            }

            // Crear RegistroCobro auxiliar para que el bono aparezca en caja diaria y facturación
            // total_final = 0 (no hay servicios/productos) → no suma al monto de servicios
            // total_bonos_vendidos = precioTotal → el bono se cuenta via bonosVendidos()
            // dinero_cliente = total pagado → necesario para que FacturacionService verifique que el bono se cobró
            $dineroClienteCobro = ($metodoPago === 'mixto') 
                ? ($pagoEfectivo + $pagoTarjeta) 
                : $dineroCliente;

            $cobroAuxiliar = RegistroCobro::create([
                'id_cliente' => $clienteId,
                'id_empleado' => $request->id_empleado,
                'coste' => 0,
                'total_final' => 0,
                'total_bonos_vendidos' => $precioTotal,
                'metodo_pago' => $metodoPago,
                'dinero_cliente' => $dineroClienteCobro,
                'pago_efectivo' => 0, // Servicios = 0, el pago del bono está en BonoCliente
                'pago_tarjeta' => 0,  // Servicios = 0, el pago del bono está en BonoCliente
                'cambio' => 0,
                'deuda' => 0,
                'contabilizado' => true,
            ]);

            // Vincular el bono al cobro para que CajaDiariaController y FacturacionController lo detecten
            $cobroAuxiliar->bonosVendidos()->attach($bonoCliente->id, [
                'precio' => $precioTotal
            ]);

            DB::commit();
            
            $mensaje = "Bono adquirido correctamente. ";
            $mensaje .= "Precio: €" . number_format($precioTotal, 2);
            if ($metodoPago === 'efectivo') {
                $mensaje .= " | Dinero recibido: €" . number_format($dineroCliente, 2);
                $mensaje .= " | Cambio: €" . number_format($cambio, 2);
            } elseif ($metodoPago === 'mixto') {
                $mensaje .= " | Efectivo: €" . number_format($pagoEfectivo, 2);
                $mensaje .= " | Tarjeta: €" . number_format($pagoTarjeta, 2);
                if ($cambio > 0) {
                    $mensaje .= " | Cambio: €" . number_format($cambio, 2);
                }
            } else {
                $mensaje .= " | Pagado con tarjeta";
            }
            
            return $this->redirectWithSuccess('bonos.misClientes', $mensaje, ['cliente' => $clienteId]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error procesando compra de bono: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error al procesar la compra.']);
        }
    }

    /**
     * Mostrar bonos de un cliente
     */
    public function misClientes($clienteId)
    {
        $cliente = Cliente::with('user')->findOrFail($clienteId);
        $bonos = BonoCliente::with(['plantilla', 'servicios', 'usoDetalles.cita', 'usoDetalles.servicio'])
            ->where('cliente_id', $clienteId)
            ->orderByRaw("FIELD(estado, 'activo', 'expirado', 'usado')") // Primero activos, luego expirados, luego usados
            ->orderBy('created_at', 'desc')
            ->get();

        return view('bonos.mis-bonos', compact('cliente', 'bonos'));
    }

    /**
     * Editar plantilla de bono
     */
    public function edit($id)
    {
        $plantilla = BonoPlantilla::with('servicios')->findOrFail($id);
        $servicios = Servicio::where('activo', true)->get();
        return view('bonos.edit', compact('plantilla', 'servicios'));
    }

    /**
     * Actualizar plantilla de bono
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'duracion_tipo' => 'required|in:30,sin_limite',
            'activo' => 'nullable|boolean'
        ]);

        try {
            $plantilla = BonoPlantilla::findOrFail($id);
            
            // Determinar duración en días (NULL = sin límite)
            $duracionDias = $request->duracion_tipo === 'sin_limite' ? null : 30;
            
            $plantilla->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'precio' => $request->precio,
                'duracion_dias' => $duracionDias,
                'activo' => $request->has('activo')
            ]);

            return $this->redirectWithSuccess('bonos.index', $this->getUpdatedMessage());
        } catch (\Exception $e) {
            Log::error('Error actualizando bono: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error al actualizar el bono.']);
        }
    }

    /**
     * Mostrar formulario de venta múltiple de bonos
     */
    public function ventaMultiple()
    {
        $plantillas = BonoPlantilla::with('servicios')->where('activo', true)->get();
        $clientes = Cliente::with('user')->get();
        $empleados = Empleado::with('user')->get();

        // Preparar mapa de servicios por plantilla para validación JS
        $plantillasServicios = $plantillas->mapWithKeys(function ($plantilla) {
            return [$plantilla->id => $plantilla->servicios->pluck('id')->toArray()];
        });

        return view('bonos.venta-multiple', compact('plantillas', 'clientes', 'empleados', 'plantillasServicios'));
    }

    /**
     * Procesar venta múltiple de bonos
     */
    public function procesarVentaMultiple(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'id_empleado' => 'required|exists:empleados,id',
            'metodo_pago' => 'required|in:efectivo,tarjeta,mixto',
            'plantillas' => 'required|array|min:1',
            'plantillas.*' => 'exists:bonos_plantilla,id',
            'dinero_cliente' => 'nullable|numeric|min:0',
            'pago_efectivo' => 'nullable|numeric|min:0',
            'pago_tarjeta' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $clienteId = $request->cliente_id;
            $metodoPago = $request->metodo_pago;
            $plantillasIds = $request->plantillas;

            // Cargar plantillas seleccionadas con sus servicios
            $plantillas = BonoPlantilla::with('servicios')->whereIn('id', $plantillasIds)->where('activo', true)->get();

            if ($plantillas->count() !== count($plantillasIds)) {
                DB::rollBack();
                return redirect()->back()->withErrors(['error' => 'Alguna de las plantillas seleccionadas no está disponible.'])->withInput();
            }

            // Validar que no comparten servicios entre sí
            $serviciosUsados = [];
            foreach ($plantillas as $plantilla) {
                foreach ($plantilla->servicios as $servicio) {
                    if (in_array($servicio->id, $serviciosUsados)) {
                        DB::rollBack();
                        return redirect()->back()->withErrors([
                            'error' => "Los bonos seleccionados comparten el servicio '{$servicio->nombre}'. No se pueden vender bonos con servicios repetidos."
                        ])->withInput();
                    }
                    $serviciosUsados[] = $servicio->id;
                }
            }

            // Validar que el cliente no tiene bonos activos con esos servicios
            $bonosActivos = BonoCliente::with(['servicios' => function($query) {
                    $query->withPivot('cantidad_total', 'cantidad_usada');
                }])
                ->where('cliente_id', $clienteId)
                ->where('estado', 'activo')
                ->get();

            foreach ($plantillas as $plantilla) {
                $serviciosNuevoBono = $plantilla->servicios->map(function($servicio) {
                    return [
                        'servicio_id' => $servicio->id,
                        'cantidad' => $servicio->pivot->cantidad
                    ];
                })->sortBy('servicio_id')->values()->all();

                foreach ($bonosActivos as $bonoActivo) {
                    $serviciosBonoActivo = $bonoActivo->servicios->map(function($servicio) {
                        return [
                            'servicio_id' => $servicio->id,
                            'cantidad' => $servicio->pivot->cantidad_total
                        ];
                    })->sortBy('servicio_id')->values()->all();

                    if ($serviciosNuevoBono == $serviciosBonoActivo) {
                        $tieneUsosDisponibles = false;
                        foreach ($bonoActivo->servicios as $servicio) {
                            if (($servicio->pivot->cantidad_total - $servicio->pivot->cantidad_usada) > 0) {
                                $tieneUsosDisponibles = true;
                                break;
                            }
                        }

                        if ($tieneUsosDisponibles) {
                            DB::rollBack();
                            return redirect()->back()->withErrors([
                                'error' => "El cliente ya tiene un bono activo '{$plantilla->nombre}' con estos servicios y todavía le quedan usos disponibles."
                            ])->withInput();
                        }
                    }
                }
            }

            // Calcular precio total sumado
            $precioTotal = $plantillas->sum('precio');

            // Calcular pago
            $dineroCliente = $request->dinero_cliente ?? 0;
            $cambio = 0;
            $pagoEfectivo = null;
            $pagoTarjeta = null;

            if ($metodoPago === 'efectivo') {
                if ($dineroCliente < $precioTotal) {
                    DB::rollBack();
                    return redirect()->back()->withErrors(['dinero_cliente' => 'El dinero del cliente debe ser al menos €' . number_format($precioTotal, 2)])->withInput();
                }
                $cambio = $dineroCliente - $precioTotal;
                $pagoEfectivo = $precioTotal;
                $pagoTarjeta = 0;
            } elseif ($metodoPago === 'mixto') {
                $pagoEfectivo = $request->pago_efectivo ?? 0;
                $pagoTarjeta = $request->pago_tarjeta ?? 0;
                $totalPagado = $pagoEfectivo + $pagoTarjeta;

                if ($totalPagado < $precioTotal) {
                    DB::rollBack();
                    return redirect()->back()->withErrors(['pago_efectivo' => 'La suma de efectivo y tarjeta debe ser al menos €' . number_format($precioTotal, 2) . '. Actualmente: €' . number_format($totalPagado, 2)])->withInput();
                }

                $cambio = $totalPagado - $precioTotal;
                $dineroCliente = $pagoEfectivo;
            } else {
                $dineroCliente = $precioTotal;
                $cambio = 0;
                $pagoEfectivo = 0;
                $pagoTarjeta = $precioTotal;
            }

            // Crear cada bono y su cobro auxiliar
            $bonosCreados = [];
            foreach ($plantillas as $plantilla) {
                $fechaCompra = Carbon::now();
                $fechaExpiracion = $plantilla->duracion_dias
                    ? $fechaCompra->copy()->addDays($plantilla->duracion_dias)
                    : $fechaCompra->copy()->addYears(100);

                $precioBono = $plantilla->precio;
                // Proporción de este bono sobre el total para desglosar el pago
                $proporcion = $precioTotal > 0 ? $precioBono / $precioTotal : 0;

                $bonoCliente = BonoCliente::create([
                    'cliente_id' => $clienteId,
                    'bono_plantilla_id' => $plantilla->id,
                    'fecha_compra' => $fechaCompra,
                    'fecha_expiracion' => $fechaExpiracion,
                    'estado' => 'activo',
                    'metodo_pago' => $metodoPago,
                    'precio_pagado' => $precioBono,
                    'pago_efectivo' => $metodoPago === 'mixto' ? round($pagoEfectivo * $proporcion, 2) : ($metodoPago === 'efectivo' ? $precioBono : 0),
                    'pago_tarjeta' => $metodoPago === 'mixto' ? round($pagoTarjeta * $proporcion, 2) : ($metodoPago === 'tarjeta' ? $precioBono : 0),
                    'dinero_cliente' => round($dineroCliente * $proporcion, 2),
                    'cambio' => round($cambio * $proporcion, 2),
                    'id_empleado' => $request->id_empleado
                ]);

                foreach ($plantilla->servicios as $servicio) {
                    $bonoCliente->servicios()->attach($servicio->id, [
                        'cantidad_total' => $servicio->pivot->cantidad,
                        'cantidad_usada' => 0
                    ]);
                }

                $dineroClienteCobro = ($metodoPago === 'mixto')
                    ? round(($pagoEfectivo + $pagoTarjeta) * $proporcion, 2)
                    : round($dineroCliente * $proporcion, 2);

                $cobroAuxiliar = RegistroCobro::create([
                    'id_cliente' => $clienteId,
                    'id_empleado' => $request->id_empleado,
                    'coste' => 0,
                    'total_final' => 0,
                    'total_bonos_vendidos' => $precioBono,
                    'metodo_pago' => $metodoPago,
                    'dinero_cliente' => $dineroClienteCobro,
                    'pago_efectivo' => 0,
                    'pago_tarjeta' => 0,
                    'cambio' => 0,
                    'deuda' => 0,
                    'contabilizado' => true,
                ]);

                $cobroAuxiliar->bonosVendidos()->attach($bonoCliente->id, [
                    'precio' => $precioBono
                ]);

                $bonosCreados[] = $plantilla->nombre;
            }

            DB::commit();

            $mensaje = count($bonosCreados) . " bonos vendidos correctamente: " . implode(', ', $bonosCreados) . ".";
            $mensaje .= " Total: €" . number_format($precioTotal, 2);
            if ($metodoPago === 'efectivo' && $cambio > 0) {
                $mensaje .= " | Cambio: €" . number_format($cambio, 2);
            } elseif ($metodoPago === 'mixto') {
                $mensaje .= " | Efectivo: €" . number_format($pagoEfectivo, 2) . " | Tarjeta: €" . number_format($pagoTarjeta, 2);
                if ($cambio > 0) {
                    $mensaje .= " | Cambio: €" . number_format($cambio, 2);
                }
            }

            return $this->redirectWithSuccess('bonos.misClientes', $mensaje, ['cliente' => $clienteId]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error procesando venta múltiple de bonos: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error al procesar la venta múltiple.']);
        }
    }

    /**
     * Listar todos los clientes que tienen bonos activos con servicios disponibles
     */
    public function clientesConBonos()
    {
        $clientes = Cliente::with(['user', 'bonos' => function($query) {
            $query->where('estado', 'activo')
                  ->where(function($subQuery) {
                      // Filtrar bonos que tengan al menos un servicio disponible
                      $subQuery->whereHas('servicios', function($servicioQuery) {
                          $servicioQuery->whereRaw('cantidad_usada < cantidad_total');
                      });
                  })
                  ->with([
                      'plantilla.servicios', 
                      'servicios' => function($q) {
                          $q->withPivot('cantidad_total', 'cantidad_usada');
                      },
                      'usoDetalles.cita', 
                      'usoDetalles.servicio',
                      'empleado.user'
                  ])
                  ->orderBy('fecha_compra', 'desc');
        }])
        ->whereHas('bonos', function($query) {
            $query->where('estado', 'activo')
                  ->whereHas('servicios', function($servicioQuery) {
                      $servicioQuery->whereRaw('cantidad_usada < cantidad_total');
                  });
        })
        ->get();

        return view('bonos.clientes-con-bonos', compact('clientes'));
    }
}
