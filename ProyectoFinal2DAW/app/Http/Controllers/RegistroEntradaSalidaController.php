<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroEntradaSalida;
use App\Models\Empleado;

class RegistroEntradaSalidaController extends Controller{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $registros = RegistroEntradaSalida::with('empleado.usuario')->get();
        return view('registros.index', compact('registros'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        $empleados = Empleado::all();
        return view('registros.create', compact('empleados'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        $data = $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'fecha' => 'required|date',
            'hora_entrada' => 'nullable|date_format:H:i',
            'hora_salida' => 'nullable|date_format:H:i',
        ]);

        RegistroEntradaSalida::create($data);
        return redirect()->route('registros.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(RegistroEntradaSalida $registro){
        return view('registros.show', compact('registro'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RegistroEntradaSalida $registro){
        $empleados = Empleado::all();
        return view('registros.edit', compact('registro', 'empleados'));    
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RegistroEntradaSalida $registro){
        $data = $request->validate([
            'hora_salida' => 'nullable|date_format:H:i',
        ]);

        $registro->update($data);
        return redirect()->route('registros.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RegistroEntradaSalida $registro){
        $registro->delete();
        return redirect()->route('registros.index')->with('success', 'Registro eliminado correctamente.');
    }
}
