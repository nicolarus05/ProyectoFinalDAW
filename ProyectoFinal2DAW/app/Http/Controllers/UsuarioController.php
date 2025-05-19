<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $usuarios = Usuario::all();
        return view('Usuarios.index', compact('usuarios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        return view('Usuarios.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'telefono' => 'required|string|max:15',
            'email' => 'required|string|email|max:255|unique:usuarios',
            'password' => 'required|string|min:8|confirmed',
            'genero' => 'required|string|max:10',
            'edad' => 'required|integer|min:0',
            'rol' => 'required|string|max:20',
        ]);

        Usuario::create([
            'nombre' => $request->nombre,
            'apellidos' => $request->apellidos,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'genero' => $request->genero,
            'edad' => $request->edad,
            'rol' => $request->rol,
        ]);

        return redirect()->route('Usuarios.index')->with('success', 'Usuario creado con éxito.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Usuario $usuario){
        return view('Usuarios.show', compact('usuario'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Usuario $usuario){
        return view('Usuarios.edit', compact('usuario'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Usuario $usuario){
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'telefono' => 'required|string|max:15',
            'email' => 'required|string|email|max:255|unique:usuarios,email,' . $usuario->id,
            'password' => 'nullable|string|min:8|confirmed',
            'genero' => 'required|string|max:10',
            'edad' => 'required|integer|min:0',
            'rol' => 'required|string|max:20',
        ]);

        $usuario->update([
            'nombre' => $request->nombre,
            'apellidos' => $request->apellidos,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'password' => $request->password ? Hash::make($request->password) : $usuario->password,
            'genero' => $request->genero,
            'edad' => $request->edad,
            'rol' => $request->rol,
        ]);

        return redirect()->route('Usuarios.index')->with('success', 'Usuario actualizado con éxito.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Usuario $usuario){
        $usuario->delete();
        return redirect()->route('Usuarios.index')->with('success', 'Usuario eliminado con éxito.');
    }
}
