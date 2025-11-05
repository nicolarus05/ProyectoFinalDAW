<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BonoPlantilla;
use App\Models\BonoCliente;
use App\Models\Servicio;
use App\Models\Cliente;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BonoController extends Controller
{
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
            return redirect()->route('bonos.index')->with('success', 'Bono creado correctamente.');
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
            'metodo_pago' => 'required|in:efectivo,tarjeta',
            'dinero_cliente' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $plantilla = BonoPlantilla::with('servicios')->findOrFail($plantillaId);
            $clienteId = $request->cliente_id;
            $metodoPago = $request->metodo_pago;

            // Verificar que no tenga un bono activo con los mismos servicios
            $serviciosIds = $plantilla->servicios->pluck('id')->toArray();
            
            $bonoExistente = BonoCliente::where('cliente_id', $clienteId)
                ->where('estado', 'activo')
                ->whereHas('servicios', function($query) use ($serviciosIds) {
                    $query->whereIn('servicio_id', $serviciosIds);
                })
                ->first();

            if ($bonoExistente) {
                return redirect()->back()->withErrors(['error' => 'El cliente ya tiene un bono activo con alguno de estos servicios.']);
            }

            // Calcular pago
            $precioTotal = $plantilla->precio;
            $dineroCliente = $request->dinero_cliente ?? 0;
            $cambio = 0;

            if ($metodoPago === 'efectivo') {
                if ($dineroCliente < $precioTotal) {
                    return redirect()->back()->withErrors(['dinero_cliente' => 'El dinero del cliente debe ser al menos €' . number_format($precioTotal, 2)])->withInput();
                }
                $cambio = $dineroCliente - $precioTotal;
            } else {
                // Tarjeta: pago exacto
                $dineroCliente = $precioTotal;
                $cambio = 0;
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

            DB::commit();
            
            $mensaje = "Bono adquirido correctamente. ";
            $mensaje .= "Precio: €" . number_format($precioTotal, 2);
            if ($metodoPago === 'efectivo') {
                $mensaje .= " | Dinero recibido: €" . number_format($dineroCliente, 2);
                $mensaje .= " | Cambio: €" . number_format($cambio, 2);
            } else {
                $mensaje .= " | Pagado con tarjeta";
            }
            
            return redirect()->route('bonos.misClientes', $clienteId)->with('success', $mensaje);
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

            return redirect()->route('bonos.index')->with('success', 'Bono actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error actualizando bono: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error al actualizar el bono.']);
        }
    }

    /**
     * Listar todos los clientes que tienen bonos activos
     */
    public function clientesConBonos()
    {
        $clientes = Cliente::with(['user', 'bonos' => function($query) {
            $query->where('estado', 'activo')
                  ->with(['plantilla', 'usoDetalles.cita', 'usoDetalles.servicio'])
                  ->orderBy('fecha_compra', 'desc');
        }])
        ->whereHas('bonos', function($query) {
            $query->where('estado', 'activo');
        })
        ->get();

        return view('bonos.clientes-con-bonos', compact('clientes'));
    }
}
