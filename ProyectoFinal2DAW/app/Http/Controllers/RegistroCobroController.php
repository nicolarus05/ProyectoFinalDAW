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
        $cobros = RegistroCobro::with('cita.cliente.usuario','cita.empleado.usuario','cita.servicio')->get();
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
            'coste' => 'required|numeric',
            'descuento_porcentaje' => 'nullable|numeric|min:0|max:100',
            'descuento_euro' => 'nullable|numeric|min:0',
            'dinero_cliente' => 'nullable|numeric|min:0',
            'metodo_pago' => 'required|in:efectivo,tarjeta',
        ]);

        // Valores por defecto si vienen vacíos
        $descuentoPorcentaje = $data['descuento_porcentaje'] ?? 0;
        $descuentoEuro = $data['descuento_euro'] ?? 0;
        $dineroCliente = $data['dinero_cliente'] ?? 0;

        // Calcular total_final
        $descuentoTotal = ($data['coste'] * ($descuentoPorcentaje / 100)) + $descuentoEuro;
        $totalFinal = $data['coste'] - $descuentoTotal;
        $data['total_final'] = round($totalFinal, 2);

        // Calcular cambio
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
        // Obtener todas las citas que no tienen un cobro asociado
        // o la cita asociada al cobro actual
        $citas = Cita::whereDoesntHave('cobro')->orWhere('id', $cobro->cita_id)->get();
        return view('Cobros.edit', compact('cobro', 'citas'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RegistroCobro $cobro){
        $data = $request->validate([
            'metodo_pago' => 'required|in:efectivo,tarjeta',
            'dinero_cliente' => 'required|numeric|min:0',
        ]);

        // Calcular el descuento final
        $totalFinal = $cobro->total_final;
        $data['cambio'] = max(0, $data['dinero_cliente'] - $totalFinal);

        $cobro->update($data);
        return redirect()->route('Cobros.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RegistroCobro $cobro){
        $cobro->delete();
        return redirect()->route('Cobros.index');
    }
}
