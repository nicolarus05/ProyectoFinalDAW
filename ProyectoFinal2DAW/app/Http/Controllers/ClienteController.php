<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\user;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Traits\HasFlashMessages;
use App\Traits\HasCrudMessages;
use App\Traits\HasJsonResponses;

class ClienteController extends Controller{
    use HasFlashMessages, HasCrudMessages, HasJsonResponses;

    protected function getResourceName(): string
    {
        return 'Cliente';
    }

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
    public function store(StoreClienteRequest $request){
        // Los datos ya vienen validados y sanitizados del Form Request
        $validated = $request->validated();

        // Asignar fecha de registro automáticamente
        $fechaRegistro = $request->fecha_registro ?? date('Y-m-d');

        // Crear user con datos validados y sanitizados
        $user = user::create([
            'nombre' => $validated['nombre'],
            'apellidos' => $validated['apellidos'],
            'telefono' => $validated['telefono'] ?? null,
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'genero' => $validated['genero'],
            'edad' => $validated['edad'],
            'rol' => 'cliente',
        ]);
        
        // Crear cliente
        $cliente = Cliente::create([
            'id_user' => $user->id,
            'direccion' => $validated['direccion'],
            'notas_adicionales' => $validated['notas_adicionales'] ?? null,
            'fecha_registro' => $fechaRegistro,
        ]);

        // Si la petición espera JSON (desde el modal), devolver JSON
        if ($request->expectsJson() || $request->ajax()) {
            return $this->createdResponse(
                new ClienteResource($cliente->load('user')),
                $this->getCreatedMessage()
            );
        }

        return $this->redirectWithSuccess('clientes.index', $this->getCreatedMessage());
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
    public function update(UpdateClienteRequest $request, Cliente $cliente){
        // Los datos ya vienen validados y sanitizados del Form Request
        $validated = $request->validated();

        // Preparar datos para actualizar el user
        $userData = [
            'nombre' => $validated['nombre'],
            'apellidos' => $validated['apellidos'],
            'telefono' => $validated['telefono'] ?? null,
            'email' => $validated['email'],
            'genero' => $validated['genero'],
            'edad' => $validated['edad'],
        ];

        // Si se proporciona contraseña, agregarla
        if (!empty($validated['password'])) {
            $userData['password'] = bcrypt($validated['password']);
        }

        // Actualizar el user relacionado
        $cliente->user->update($userData);

        // Actualizar los datos del cliente
        $cliente->update([
            'direccion' => $validated['direccion'],
            'notas_adicionales' => $validated['notas_adicionales'] ?? null,
            'fecha_registro' => $validated['fecha_registro'],
        ]);

        return $this->redirectWithSuccess('clientes.index', $this->getUpdatedMessage());
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
            
            return $this->redirectWithSuccess('clientes.index', $this->getDeletedMessage());
        } catch (\Exception $e) {
            return $this->redirectWithError('clientes.index', $this->getDeleteErrorMessage() . ': ' . $e->getMessage());
        }
    }
}

