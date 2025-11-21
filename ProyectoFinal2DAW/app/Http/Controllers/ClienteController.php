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
        $clientes = Cliente::with('user')
            ->join('users', 'clientes.id_user', '=', 'users.id')
            ->orderBy('users.apellidos', 'asc')
            ->orderBy('users.nombre', 'asc')
            ->select('clientes.*')
            ->get();
        return view('clientes.index', compact('clientes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        $users = user::where('rol', 'cliente')->get();
        return view('clientes.create', compact('users'));
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
            'password' => 'required|string|min:6',
            'genero' => 'required|string|max:20',
            'edad' => 'required|integer|min:0',
            'direccion' => 'required|string|max:255',
            'notas_adicionales' => 'nullable|string|max:255',
        ]);

        // Asignar fecha de registro automáticamente
        $fechaRegistro = $request->fecha_registro ?? date('Y-m-d');

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
            'fecha_registro' => $fechaRegistro,
        ]);

        return redirect()->route('clientes.index')->with('success', 'El Cliente ha sido creado con éxito.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente){
        return view('clientes.show', compact('cliente'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cliente $cliente){
        $users = user::where('rol', 'cliente')->get();
        return view('clientes.edit', compact('cliente', 'users'));
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

        return redirect()->route('clientes.index')->with('success', 'El Cliente ha sido actualizado con éxito.');
    }

    /**
     * Mostrar historial de citas del cliente.
     */
    public function historial(Cliente $cliente)
    {
        // Cargar las citas con sus relaciones
        $citas = $cliente->citas()
            ->with(['empleado.user', 'servicios'])
            ->orderBy('fecha_hora', 'desc')
            ->get();
        
        return view('clientes.historial', compact('cliente', 'citas'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cliente $cliente){
        try {
            // Guardar el ID del usuario antes de eliminar el cliente
            $userId = $cliente->id_user;
            
            // Eliminar el cliente (las relaciones con cascade se eliminarán automáticamente)
            $cliente->delete();
            
            // Eliminar el usuario asociado si existe
            if ($userId) {
                $user = user::find($userId);
                if ($user) {
                    $user->delete();
                }
            }
            
            return redirect()->route('clientes.index')->with('success', 'El Cliente ha sido eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('clientes.index')->with('error', 'Error al eliminar el cliente: ' . $e->getMessage());
        }
    }
}

