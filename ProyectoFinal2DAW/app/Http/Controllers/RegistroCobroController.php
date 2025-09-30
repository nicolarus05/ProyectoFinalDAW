<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroCobro;
use App\Models\Cita;

class RegistroCobroController extends Controller{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $cobros = RegistroCobro::with('cita.cliente.user','cita.empleado.user','cita.servicios')->get();
        return view('Cobros.index', compact('cobros'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        $citas = Cita::whereDoesntHave('cobro')->get();
        return view('Cobros.create', compact('citas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        $data = $request->validate([
            'id_cita' => 'required|exists:citas,id',
            'descuento_porcentaje' => 'nullable|numeric|min:0|max:100',
            'descuento_euro' => 'nullable|numeric|min:0',
            'dinero_cliente' => 'nullable|numeric|min:0',
            'metodo_pago' => 'required|in:efectivo,tarjeta',
        ]);

        // Agregar validaciones adicionales
        $cita = Cita::with('servicios')->findOrFail($data['id_cita']);
        
        if ($cita->estado !== 'completada') {
            return back()
                ->withInput()
                ->withErrors(['id_cita' => 'Solo se pueden registrar cobros de citas completadas.']);
        }

        // Verificar que no tenga ya un cobro registrado
        if ($cita->cobro) {
            return back()
                ->withInput()
                ->withErrors(['id_cita' => 'Esta cita ya tiene un cobro registrado.']);
        }
        
        $coste = $cita->servicios->sum('precio');
        $data['coste'] = $coste;

        $cita = Cita::with('servicios')->findOrFail($data['id_cita']);
        $coste = $cita->servicios->sum('precio');
        $data['coste'] = $coste;

        $descuentoPorcentaje = $data['descuento_porcentaje'] ?? 0;
        $descuentoEuro = $data['descuento_euro'] ?? 0;
        $dineroCliente = $data['dinero_cliente'] ?? 0;

        $descuentoTotal = ($coste * ($descuentoPorcentaje / 100)) + $descuentoEuro;
        $totalFinal = $coste - $descuentoTotal;
        $data['total_final'] = round($totalFinal, 2);

        $data['cambio'] = $dineroCliente > 0 ? round($dineroCliente - $data['total_final'], 2) : null;

        RegistroCobro::create($data);

        return redirect()->route('Cobros.index')->with('success', 'Cobro registrado correctamente.');
    }



    /**
     * Display the specified resource.
     */
    public function show(RegistroCobro $cobro){
        return view('Cobros.show', compact('cobro'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RegistroCobro $cobro){
        $citas = Cita::whereDoesntHave('cobro')
            ->orWhere('id', $cobro->id_cita)
            ->with('cliente.user', 'servicios')
            ->get();

        return view('Cobros.edit', compact('cobro', 'citas'));
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

        return redirect()->route('Cobros.index')->with('success', 'Cobro actualizado correctamente.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RegistroCobro $cobro){
        $cobro->delete();
        return redirect()->route('Cobros.index');
    }
}
