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
        // Validación de los datos del formulario
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email',
            'telefono' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'genero' => 'nullable|string|in:Masculino,Femenino,Otro',
            'edad' => 'nullable|integer|min:0|max:120',
            'rol' => 'required|in:cliente,empleado',

            // Campos específicos para cada rol
            'especializacion' => 'nullable|string|max:255',
            'fecha_registro' => 'nullable|date',
            'direccion' => 'nullable|string|max:255',
            'notas_adicionales' => 'nullable|string|max:1000',
        ]);

        // Encriptar la contraseña
        $data['password'] = Hash::make($data['password']);

        try {
            // Crear el usuario principal
            $usuario = Usuario::create([
                'nombre' => $data['nombre'],
                'apellidos' => $data['apellidos'],
                'email' => $data['email'],
                'telefono' => $data['telefono'] ?? null,
                'edad' => $data['edad'] ?? null,
                'genero' => $data['genero'] ?? null,
                'password' => $data['password'],
                'rol' => $data['rol'],
            ]);

            // Crear datos adicionales según el rol
            if ($data['rol'] === 'empleado') {
                $usuario->empleado()->create([
                    'especializacion' => $data['especializacion'] ?? null,
                ]);
            }

            if ($data['rol'] === 'cliente') {
                $usuario->cliente()->create([
                    'direccion' => $data['direccion'] ?? null,
                    'fecha_registro' => $data['fecha_registro'] ?? null,
                    'notas_adicionales' => $data['notas_adicionales'] ?? null,
                ]);
            }

            return redirect()->route('Usuarios.index')->with('success', 'Usuario creado correctamente.');

        } catch (\Exception $e) {
            // Mostrar mensaje de error si algo falla
            return back()->withErrors(['error' => 'Error al crear el usuario: ' . $e->getMessage()])
                        ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id){
        $usuario = Usuario::with('empleado')->findOrFail($id);
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
