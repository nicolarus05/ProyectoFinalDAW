<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empleado;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Cita;

class CitaController extends Controller{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $citas = Cita::with('cliente.usuario','empleado.usuario','servicio')->get();
        return view('citas.index', compact('citas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        $clientes = Cliente::all();
        $empleados = Empleado::all();
        $servicios = Servicio::all();
        return view('citas.create', compact('clientes','empleados','servicios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        $data = $request->validate([
            'fecha_hora' => 'required|date',
            'estado' => 'required|in:pendiente,confirmada,cancelada,completada',
            'cliente_id' => 'required|exists:clientes,id',
            'empleado_id' => 'required|exists:empleados,id',
            'servicio_id' => 'required|exists:servicios,id',
        ]);

        Cita::create($data);
        return redirect()->route('citas.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Cita $cita){
        return view('citas.show', compact('cita'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cita $cita){
        $clientes = Cliente::all();
        $empleados = Empleado::all();
        $servicios = Servicio::all();
        return view('citas.edit', compact('cita','clientes','empleados','servicios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cita $cita){
        $data = $request->validate([
            'estado' => 'required|in:pendiente,confirmada,cancelada,completada',
        ]);

        $cita->update($data);
        return redirect()->route('citas.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cita $cita){
        $cita->delete();
        return redirect()->route('citas.index')->with('success', 'La cita ha sido eliminada con exito.');
    }
}
