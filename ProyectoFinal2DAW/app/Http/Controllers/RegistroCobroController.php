<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroCobro;
use App\Models\Cita;
use App\Models\user;
use App\Models\Productos;
use App\Models\Cliente;


class RegistroCobroController extends Controller{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $cobros = RegistroCobro::with('cita.cliente.user','cita.empleado.user','cita.servicios')->get();
        return view('cobros.index', compact('cobros'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        $citas = Cita::whereDoesntHave('cobro')->get();
        return view('cobros.create', compact('citas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        // --- Validación base ---
        $data = $request->validate([
            'id_cita' => 'required|exists:citas,id',
            'coste' => 'required|numeric|min:0',
            'descuento_porcentaje' => 'nullable|numeric|min:0',
            'descuento_euro' => 'nullable|numeric|min:0',
            'total_final' => 'required|numeric|min:0',
            'metodo_pago' => 'required|in:efectivo,tarjeta',
            'dinero_cliente' => 'nullable|numeric|min:0',
            'cambio' => 'nullable|numeric|min:0',
        ]);

        // --- Lógica según método de pago ---
        if ($data['metodo_pago'] === 'efectivo') {
            // Si es efectivo → dinero_cliente es obligatorio y debe cubrir el total
            if (empty($data['dinero_cliente'])) {
                return back()
                    ->withErrors(['dinero_cliente' => 'El campo Dinero del Cliente es obligatorio para pagos en efectivo.'])
                    ->withInput();
            }

            if ($data['dinero_cliente'] < $data['total_final']) {
                return back()
                    ->withErrors(['dinero_cliente' => 'El dinero del cliente debe ser igual o superior al total final.'])
                    ->withInput();
            }

            // Calcular el cambio
            $data['cambio'] = $data['dinero_cliente'] - $data['total_final'];
        } 
        elseif ($data['metodo_pago'] === 'tarjeta') {
            // Si es tarjeta → se llena automáticamente
            $data['dinero_cliente'] = $data['total_final'];
            $data['cambio'] = 0;
        }

        // --- Calcular deuda si el dinero del cliente es menor que el total ---
        $deuda = max(0, $data['total_final'] - ($data['dinero_cliente'] ?? 0));

        // --- Crear el registro principal ---
        $cobro = RegistroCobro::create([
            'id_cita' => $data['id_cita'],
            'coste' => $data['coste'],
            'descuento_porcentaje' => $data['descuento_porcentaje'] ?? 0,
            'descuento_euro' => $data['descuento_euro'] ?? 0,
            'total_final' => $data['total_final'],
            'dinero_cliente' => $data['dinero_cliente'] ?? 0,
            'cambio' => $data['cambio'] ?? 0,
            'metodo_pago' => $data['metodo_pago'],
            'id_cliente' => $data['id_cliente'] ?? null,
            'id_empleado' => $data['id_empleado'] ?? (auth()->user()->empleado->id ?? null),
            'deuda' => $deuda,
        ]);

        // --- Si hay deuda, registrarla en el sistema de deudas ---
        if ($deuda > 0) {
            $cita = Cita::find($data['id_cita']);
            $cliente = Cliente::find($cita->id_cliente);
            
            if ($cliente) {
                $deudaCliente = $cliente->obtenerDeuda();
                $nota = "Cobro #" . $cobro->id . " - Cita #" . $cobro->id_cita;
                $deudaCliente->registrarCargo($deuda, $nota, null, $cobro->id);
            }
        }

        // --- Guardar productos asociados (si existen) ---
        if ($request->has('products')) {
            foreach ($request->products as $p) {
                $cantidad = (int) $p['cantidad'];
                $precio = (float) $p['precio_venta'];
                $subtotal = $cantidad * $precio;

                $cobro->productos()->attach($p['id'], [
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect()
            ->route('cobros.index')
            ->with('success', 'Cobro registrado correctamente.');
    }


    /**
     * Display the specified resource.
     */
    public function show(RegistroCobro $cobro){
        return view('cobros.show', compact('cobro'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RegistroCobro $cobro){
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
            'id_cita' => 'required|exists:citas,id',
            'coste' => 'required|numeric|min:0',
            'total_final' => 'required|numeric|min:0',
            'dinero_cliente' => 'required|numeric|min:0',
            'descuento_porcentaje' => 'nullable|numeric|min:0|max:100',
            'descuento_euro' => 'nullable|numeric|min:0',
            'metodo_pago' => 'required|in:efectivo,tarjeta',
            'cambio' => 'nullable|numeric|min:0'
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
            'id_cita' => $data['id_cita'],
            'coste' => $data['coste'],
            'descuento_porcentaje' => $descuentoPorcentaje,
            'descuento_euro' => $descuentoEuro,
            'total_final' => $data['total_final'],
            'dinero_cliente' => $dineroCliente,
            'cambio' => $data['cambio'],
            'metodo_pago' => $data['metodo_pago'],
        ]);

        return redirect()->route('cobros.index')->with('success', 'Cobro actualizado correctamente.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RegistroCobro $cobro){
        $cobro->delete();
        return redirect()->route('cobros.index');
    }
}
