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
        return view('clientes.index', compact('clientes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        $usuarios = Usuario::where('tipo', 'cliente')->get();
        return view('clientes.create', compact('usuarios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        //validacion de los datos
        $data = $request->validate([
            'usuario_id' => 'required|exists:usuarios,id',
            'direccion' => 'required|string|max:255',
            'notas_adicionales' => 'nullable|string|max:255',
            'fecha_registro' => 'required|date',
        ]); 

        //creacion del cliente
        Cliente::create($data);
        return redirect()->route('clientes.index')->with('success', 'El Cliente ha sido creado con exito.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id){
        return view('clientes.show', compact('cliente'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id){
        $usuarios = Usuario::where('tipo', 'cliente')->get();
        return view('clientes.edit', compact('cliente', 'usuarios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id){
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id){
        
    }
}
