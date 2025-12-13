<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Deuda;
use App\Models\MovimientoDeuda;
use Illuminate\Http\Request;
use App\Http\Requests\RegistrarPagoDeudaRequest;

class DeudaController extends Controller
{
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
                return response()->json([
                    'success' => false,
                    'message' => 'Este cliente no tiene deudas pendientes.'
                ], 400);
            }
            return redirect()->route('deudas.show', $cliente)
                ->with('error', 'Este cliente no tiene deudas pendientes.');
        }

        $monto = $validated['monto'];

        if ($monto > $deuda->saldo_pendiente) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El monto no puede ser mayor a la deuda pendiente (€' . number_format($deuda->saldo_pendiente, 2) . ')'
                ], 400);
            }
            return back()->withErrors([
                'monto' => 'El monto no puede ser mayor a la deuda pendiente (€' . number_format($deuda->saldo_pendiente, 2) . ')'
            ])->withInput();
        }

        $deuda->registrarAbono(
            $validated['monto'],
            $validated['metodo_pago'],
            $validated['nota'] ?? null
        );

        $mensaje = $deuda->saldo_pendiente > 0
            ? 'Pago registrado. Deuda restante: €' . number_format($deuda->saldo_pendiente, 2)
            : 'Pago registrado. Deuda saldada completamente.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'deuda_restante' => $deuda->saldo_pendiente
            ]);
        }

        return redirect()->route('deudas.show', $cliente)
            ->with('success', $mensaje);
    }

    public function historial(Cliente $cliente)
    {
        $deuda = $cliente->obtenerDeuda();
        $movimientos = $deuda->movimientos()->with('usuarioRegistro')->get();

        return view('deudas.historial', compact('cliente', 'deuda', 'movimientos'));
    }
}
