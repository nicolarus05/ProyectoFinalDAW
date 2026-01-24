<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Deuda;
use App\Models\MovimientoDeuda;
use Illuminate\Http\Request;
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

        // Obtener el empleado seleccionado por el usuario
        $empleadoId = $validated['empleado_id'];
        
        // Buscar el cargo original (cuando se generó la deuda) para obtener los servicios y productos originales
        $ultimoCargo = $deuda->movimientos()
            ->where('tipo', 'cargo')
            ->with(['registroCobro.servicios', 'registroCobro.productos', 'registroCobro.cita'])
            ->latest()
            ->first();
        
        $citaId = null;
        $serviciosOriginales = collect();
        $productosOriginales = collect();
        
        // Si hay cobro original con servicios/productos, los usaremos para distribuir proporcionalmente
        if ($ultimoCargo && $ultimoCargo->registroCobro) {
            $cobroOriginal = $ultimoCargo->registroCobro;
            $citaId = $cobroOriginal->id_cita;
            
            // Obtener servicios del cobro original
            if ($cobroOriginal->servicios && $cobroOriginal->servicios->count() > 0) {
                $serviciosOriginales = $cobroOriginal->servicios;
            }
            
            // Obtener productos del cobro original
            if ($cobroOriginal->productos && $cobroOriginal->productos->count() > 0) {
                $productosOriginales = $cobroOriginal->productos;
            }
        }
        
        // Calcular el porcentaje de pago basado en el saldo pendiente + monto pagado
        // Esto permite manejar pagos parciales correctamente
        $totalDeudaAntesPago = $deuda->saldo_pendiente + $monto;
        $porcentajePago = $monto / $totalDeudaAntesPago;
        
        // Crear registro de cobro para la caja del día (con referencia a la cita original si existe)
        $registroCobro = \App\Models\RegistroCobro::create([
            'id_cita' => $citaId, // Vinculamos con la cita original
            'id_cliente' => $cliente->id,
            'id_empleado' => $empleadoId, // El empleado seleccionado que cobra
            'coste' => $monto,
            'total_final' => $monto,
            'metodo_pago' => $validated['metodo_pago'],
            'deuda' => 0, // No genera nueva deuda, la está pagando
            'dinero_cliente' => $monto,
            'pago_efectivo' => $validated['metodo_pago'] === 'efectivo' ? $monto : 0,
            'pago_tarjeta' => $validated['metodo_pago'] === 'tarjeta' ? $monto : 0,
            'cambio' => 0,
            'contabilizado' => true, // IMPORTANTE: marcar como contabilizado para facturación
        ]);
        
        // Vincular servicios originales al nuevo cobro CON PRECIO proporcional
        // TODO el dinero va al empleado seleccionado
        $serviciosVinculados = false;
        if ($serviciosOriginales->count() > 0) {
            foreach ($serviciosOriginales as $servicio) {
                // Calcular precio proporcional del servicio
                // Si el servicio tiene precio en pivot, usarlo; si no, usar el precio base del servicio
                $precioServicio = $servicio->pivot->precio > 0 ? $servicio->pivot->precio : $servicio->precio;
                $precioProporcion = $precioServicio * $porcentajePago;
                
                $registroCobro->servicios()->attach($servicio->id, [
                    'empleado_id' => $empleadoId, // TODO el dinero va al empleado seleccionado
                    'precio' => $precioProporcion, // Precio proporcional al pago
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $serviciosVinculados = true;
            }
        }
        
        // Vincular productos originales al nuevo cobro CON PRECIO proporcional
        if ($productosOriginales->count() > 0) {
            foreach ($productosOriginales as $producto) {
                // Calcular subtotal proporcional según el porcentaje pagado
                $subtotalProporcion = $producto->pivot->subtotal * $porcentajePago;
                
                $registroCobro->productos()->attach($producto->id, [
                    'cantidad' => $producto->pivot->cantidad,
                    'precio_unitario' => $producto->pivot->precio_unitario,
                    'subtotal' => $subtotalProporcion, // Subtotal proporcional al pago
                    'empleado_id' => $empleadoId, // TODO el dinero va al empleado seleccionado
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Si NO hay servicios ni productos originales (deuda creada manualmente)
        // El cobro ya está creado con coste y total_final, por lo que aparecerá en caja diaria
        // Para facturación del empleado, FacturacionService usará el coste del cobro
        // NO hacemos nada adicional aquí

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
