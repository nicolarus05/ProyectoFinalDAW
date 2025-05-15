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
        return view('Empleados.index', compact('empleados'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        $usuarios = Usuario::where('rol', 'empleado')->get();
        return view('Empleados.create', compact('usuarios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        // Validar datos del usuario y del empleado
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'required|email|unique:usuarios,email',
            'genero' => 'required|string|max:20',
            'edad' => 'required|integer|min:0',
            'especializacion' => 'required|string|max:255',
        ]);

        //dd($request->all());

        // Crear usuario
        $usuario = Usuario::create([
            'nombre' => $request->input('nombre'),
            'apellidos' => $request->input('apellidos'),
            'telefono' => $request->input('telefono'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'genero' => $request->input('genero'),
            'edad' => $request->input('edad'),
            'rol' => 'empleado',
        ]);

        // Crear empleado
        Empleado::create([
            'id_usuario' => $usuario->id,
            'especializacion' => $request->input('especializacion'),
        ]);
        
        // Redirigir a la lista de empleados
        return redirect()->route('Empleados.index')->with('success', 'El empleado ha sido creado con éxito.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Empleado $empleado){
        return view('Empleados.show', compact('empleado'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Empleado $empleado){
        $usuarios = Usuario::where('rol', 'empleado')->get();
        return view('Empleados.edit', compact('empleado', 'usuarios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Empleado $empleado){
        // Validar datos del usuario y del empleado
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'required|email|unique:usuarios,email,' . $empleado->usuario->id,
            'genero' => 'required|string|max:20',
            'edad' => 'required|integer|min:0',
            'especializacion' => 'required|string|max:255',
        ]);

        // Actualizar usuario
        $empleado->usuario->update([
            'nombre' => $request->input('nombre'),
            'apellidos' => $request->input('apellidos'),
            'telefono' => $request->input('telefono'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'genero' => $request->input('genero'),
            'edad' => $request->input('edad'),
        ]);

        // Actualizar empleado
        $empleado->update([
            'especializacion' => $request->input('especializacion'),
        ]);

        return redirect()->route('Empleados.index')->with('success', 'El empleado ha sido actualizado con éxito.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Empleado $empleado){
        $empleado->delete();
        return redirect()->route('Empleados.index')->with('success', 'El empleado ha sido eliminado con éxito.');
    }
}
