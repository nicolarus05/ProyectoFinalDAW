<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HorarioTrabajo;
use App\Models\Empleado;

class HorarioTrabajoController extends Controller{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $horarios = HorarioTrabajo::with('empleado.user')->get();
        return view('horarios.index', compact('horarios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        $empleados = Empleado::all();
        return view('horarios.create', compact('empleados'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        $data = $request->validate([
            'id_empleado' => 'required|exists:empleados,id',
            'fecha' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'disponible' => 'boolean',
            'notas' => 'nullable|string|max:1000',
        ]);


        HorarioTrabajo::create($data);
        return redirect()->route('horarios.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(HorarioTrabajo $horario){
        return view('horarios.show', compact('horario'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HorarioTrabajo $horario){
        $empleados = Empleado::all();
        return view('horarios.edit', compact('horario', 'empleados'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HorarioTrabajo $horario){

        $data = $request->validate([
            'id_empleado' => 'required|exists:empleados,id',
            'fecha' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'disponible' => 'boolean',
            'notas' => 'nullable|string|max:1000',
        ]);


        $horario->update($data);
        return redirect()->route('horarios.index')->with('success', 'El horario ha sido actualizado con éxito.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HorarioTrabajo $horario){
        $horario->delete();
        return redirect()->route('horarios.index')->with('success', 'El horario ha sido eliminado con éxito.');
    }
}
