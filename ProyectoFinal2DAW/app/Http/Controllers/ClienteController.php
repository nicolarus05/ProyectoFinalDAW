<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Usuario;

class ClienteController extends Controller{

    /**
     * Display a listing of the resource.
     */
    public function index(){
        $clientes = Cliente::with('usuario')->get();
        return view('Clientes.index', compact('clientes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        $usuarios = Usuario::where('rol', 'cliente')->get();
        return view('Clientes.create', compact('usuarios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        // Validar datos del usuario y del cliente
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'required|email|unique:usuarios,email',
            'genero' => 'required|string|max:20',
            'edad' => 'required|integer|min:0',
            'direccion' => 'required|string|max:255',
            'notas_adicionales' => 'nullable|string|max:255',
            'fecha_registro' => 'required|date',
        ]);

        // Crear usuario
        $usuario = Usuario::create([
            'nombre' => $request->input('nombre'),
            'apellidos' => $request->input('apellidos'),
            'telefono' => $request->input('telefono'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'genero' => $request->input('genero'),
            'edad' => $request->input('edad'),
            'rol' => 'cliente',
        ]);
        
        // Crear cliente
        Cliente::create([
            'id_usuario' => $usuario->id,
            'direccion' => $request->input('direccion'),
            'notas_adicionales' => $request->input('notas_adicionales'),
            'fecha_registro' => $request->input('fecha_registro'),
        ]);

        return redirect()->route('Clientes.index')->with('success', 'El Cliente ha sido creado con exito.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente){
        return view('Clientes.show', compact('cliente'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cliente $cliente){
        $usuarios = Usuario::where('rol', 'cliente')->get();
        return view('Clientes.edit', compact('cliente', 'usuarios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cliente $cliente){
        // Validar datos del usuario y del cliente
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20', 
            'email' => 'required|email|unique:usuarios,email,' . $cliente->usuario->id,
            'genero' => 'required|string|max:20',
            'edad' => 'required|integer|min:0',
            'direccion' => 'required|string|max:255',
            'notas_adicionales' => 'nullable|string|max:255',
            'fecha_registro' => 'required|date',
        ]);

        // Actualizar el usuario relacionado
        $cliente->usuario->update([
            'nombre' => $request->input('nombre'),
            'apellidos' => $request->input('apellidos'),
            'telefono' => $request->input('telefono'),
            'email' => $request->input('email'),
            'genero' => $request->input('genero'),
            'edad' => $request->input('edad'),
        ]);

        // Actualizar los datos del cliente
        $cliente->update([
            'direccion' => $request->input('direccion'),
            'notas_adicionales' => $request->input('notas_adicionales'),
            'fecha_registro' => $request->input('fecha_registro'),
        ]);

        return redirect()->route('Clientes.index')->with('success', 'El Cliente ha sido actualizado con éxito.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cliente $cliente){
        $cliente->delete();
        return redirect()->route('Clientes.index')->with('success', 'El Cliente ha sido eliminado con exito.');
    }
}
