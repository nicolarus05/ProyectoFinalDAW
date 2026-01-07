<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class RegisterClienteController extends Controller
{
    public function create(){
        return view('auth.register');
    }

    public function store(Request $request){

        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'genero' => 'required|in:Hombre,Mujer,Otro',
            'edad' => 'required|integer|min:0|max:120',
            'direccion' => 'required|string|max:255',
            'notas_adicionales' => 'nullable|string|max:5000',
        ]);

        // Asignar fecha de registro automáticamente
        $fechaRegistro = $request->fecha_registro ?? date('Y-m-d');

        $user = user::create([
            'nombre' => $request->nombre,
            'apellidos' => $request->apellidos,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'genero' => $request->genero,
            'edad' => $request->edad,
            'rol' => 'cliente',
            'direccion' => $request->direccion,
            'notas_adicionales' => $request->notas_adicionales,
            'fecha_registro' => $fechaRegistro,
        ]);

        // Crear registro en tabla clientes vinculado al user
        Cliente::create([
            'id_user' => $user->id,
            'direccion' => $request->direccion,
            'notas_adicionales' => $request->notas_adicionales,
            'fecha_registro' => $fechaRegistro,
        ]);


        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
