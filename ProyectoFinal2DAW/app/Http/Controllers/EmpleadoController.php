<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empleado;
use App\Models\Usuario;

class EmpleadoController extends Controller{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $empleados = Empleado::with('usuario')->get();
        return view('empleados.index', compact('empleados'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        $usuarios = Usuario::where('rol', 'empleado')->get();
        return view('empleados.create', compact('usuarios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        $data = $request->validate([
            'usuario_id' => 'required|exists:usuarios,id',
            'especializacion' => 'required|string',
        ]);

        Empleado::create($data);
        return redirect()->route('empleados.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Empleado $empleado){
        return view('empleados.show', compact('empleado'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Empleado $empleado){
        $usuarios = Usuario::where('rol', 'empleado')->get();
        return view('empleados.edit', compact('empleado', 'usuarios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Empleado $empleado){
        $data = $request->validate([
            'especializacion' => 'required|string',
        ]);

        $empleado->update($data);
        return redirect()->route('empleados.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Empleado $empleado){
        $empleado->delete();
        return redirect()->route('empleados.index')->with('success', 'El empleado ha sido eliminado con éxito.');
    }
}
