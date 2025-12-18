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

        return view('deudas.pago', compact('cliente', 'deuda'));
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

        // Obtener el empleado y servicios del servicio original de la deuda
        // Buscamos el último cargo (cuando se generó la deuda) para obtener el empleado y servicios originales
        $ultimoCargo = $deuda->movimientos()
            ->where('tipo', 'cargo')
            ->with(['registroCobro.servicios', 'registroCobro.cita'])
            ->latest()
            ->first();
        
        // Usar el empleado del servicio original, o el empleado actual como fallback
        $empleadoId = null;
        $citaId = null;
        $serviciosOriginales = [];
        
        if ($ultimoCargo && $ultimoCargo->registroCobro) {
            $empleadoId = $ultimoCargo->registroCobro->id_empleado;
            $citaId = $ultimoCargo->registroCobro->id_cita;
            
            // Obtener los servicios del cobro original
            if ($ultimoCargo->registroCobro->servicios && $ultimoCargo->registroCobro->servicios->count() > 0) {
                $serviciosOriginales = $ultimoCargo->registroCobro->servicios;
            }
        } else {
            // Fallback: empleado actual que registra el pago
            $empleadoId = auth()->user()->empleado->id ?? null;
        }
        
        // Crear registro de cobro para la caja del día (con referencia a la cita original si existe)
        $registroCobro = \App\Models\RegistroCobro::create([
            'id_cita' => $citaId, // Vinculamos con la cita original
            'id_cliente' => $cliente->id,
            'id_empleado' => $empleadoId,
            'coste' => $monto,
            'total_final' => $monto,
            'metodo_pago' => $validated['metodo_pago'],
            'deuda' => 0, // No genera nueva deuda, la está pagando
            'dinero_cliente' => $monto,
            'pago_efectivo' => $validated['metodo_pago'] === 'efectivo' ? $monto : 0,
            'pago_tarjeta' => $validated['metodo_pago'] === 'tarjeta' ? $monto : 0,
            'cambio' => 0,
        ]);
        
        // Vincular los servicios originales al nuevo registro de cobro
        if (count($serviciosOriginales) > 0) {
            foreach ($serviciosOriginales as $servicio) {
                $registroCobro->servicios()->attach($servicio->id, [
                    'empleado_id' => $servicio->pivot->empleado_id ?? $empleadoId,
                    'precio' => 0 // El precio ya está en el monto del pago
                ]);
            }
        }

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
