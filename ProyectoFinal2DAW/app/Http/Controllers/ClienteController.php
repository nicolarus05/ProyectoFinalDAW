<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\user;

class ClienteController extends Controller{

    /**
     * Display a listing of the resource.
     */
    public function index(){
        $clientes = Cliente::with('user')->get();
        return view('Clientes.index', compact('clientes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        $users = user::where('rol', 'cliente')->get();
        return view('Clientes.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        // Validar datos del user y del cliente
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'required|email|unique:users,email',
            'genero' => 'required|string|max:20',
            'edad' => 'required|integer|min:0',
            'direccion' => 'required|string|max:255',
            'notas_adicionales' => 'nullable|string|max:255',
            'fecha_registro' => 'required|date',
        ]);

        // Crear user
        $user = user::create([
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
            'id_user' => $user->id,
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
        $users = user::where('rol', 'cliente')->get();
        return view('Clientes.edit', compact('cliente', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cliente $cliente){
        // Validar datos del user y del cliente
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20', 
            'email' => 'required|email|unique:users,email,' . $cliente->user->id,
            'genero' => 'required|string|max:20',
            'edad' => 'required|integer|min:0',
            'direccion' => 'required|string|max:255',
            'notas_adicionales' => 'nullable|string|max:255',
            'fecha_registro' => 'required|date',
        ]);

        // Actualizar el user relacionado
        $cliente->user->update([
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
