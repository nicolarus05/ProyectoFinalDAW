<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\user;
use Illuminate\Support\Facades\Hash;

class userController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $users = user::all();
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     */


    public function store(Request $request){
        // Validación de los datos del formulario
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'telefono' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'genero' => 'nullable|string|in:masculino,femenino,otro',
            'edad' => 'nullable|integer|min:0|max:120',
            'rol' => 'required|in:cliente,empleado, admin',

            // Campos específicos para cada rol
            'categoria' => 'required_if:rol,empleado|nullable|in:peluqueria,estetica',
            'fecha_registro' => 'required_if:rol,cliente|nullable|date',
            'direccion' => 'required_if:rol,cliente|nullable|string|max:255',
            'notas_adicionales' => 'nullable|string|max:5000',
        ]);

        // Encriptar la contraseña
        $data['password'] = Hash::make($data['password']);

        try {
            // Crear el user principal
            $user = user::create([
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
                $user->empleado()->create([
                    'categoria' => $data['categoria'] ?? 'peluqueria',
                ]);
            }

            if ($data['rol'] === 'cliente') {
                $user->cliente()->create([
                    'direccion' => $data['direccion'] ?? null,
                    'fecha_registro' => $data['fecha_registro'] ?? null,
                    'notas_adicionales' => $data['notas_adicionales'] ?? null,
                ]);
            }

            return redirect()->route('users.index')->with('success', 'user creado correctamente.');

        } catch (\Exception $e) {
            // Mostrar mensaje de error si algo falla
            return back()->withErrors(['error' => 'Error al crear el user: ' . $e->getMessage()])
                        ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id){
        $user = user::with('empleado')->findOrFail($id);
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(user $user){
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, user $user){
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'telefono' => 'required|string|max:15',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'genero' => 'required|string|max:10',
            'edad' => 'required|integer|min:0',
            'rol' => 'required|string|max:20',
        ]);

        $user->update([
            'nombre' => $request->nombre,
            'apellidos' => $request->apellidos,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'password' => $request->password ? Hash::make($request->password) : $user->password,
            'genero' => $request->genero,
            'edad' => $request->edad,
            'rol' => $request->rol,
        ]);

        return redirect()->route('users.index')->with('success', 'user actualizado con éxito.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(user $user){
        $user->delete();
        return redirect()->route('users.index')->with('success', 'user eliminado con éxito.');
    }
}
