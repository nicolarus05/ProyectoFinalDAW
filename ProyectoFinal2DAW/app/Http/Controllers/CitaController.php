<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empleado;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Cita;
use App\Models\HorarioTrabajo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CitaController extends Controller{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $user = Auth::user();

        if ($user->rol === 'cliente') {
            $cliente = $user->cliente;
            if (!$cliente) {
                abort(403, 'No tienes permiso para acceder a esta sección.');
            }
            // Solo las citas del cliente
            $citas = $cliente->citas()->latest()->get();

        } else if ($user->rol === 'empleado') {
            $empleado = $user->empleado;
            if (!$empleado) {
                abort(403, 'No tienes permiso para acceder a esta sección.');
            }
            // Solo las citas del empleado
            $citas = Cita::where('id_empleado', $empleado->id)->latest()->get();

        } else if ($user->rol === 'admin') {
            // El admin ve todas las citas
            $citas = Cita::latest()->get();

        } else {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return view('citas.index', compact('citas'));
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        $user = Auth::user();

        $empleados = Empleado::all();
        $servicios = Servicio::all();

        // Si es admin o empleado, necesita poder elegir un cliente
        if ($user->rol === 'admin' || $user->rol === 'empleado') {
            $clientes = Cliente::with('user')->get();
        } else {
            // Es cliente normal
            $clientes = $user->cliente;
        }

        return view('citas.create', compact('clientes', 'empleados', 'servicios'));
    }


    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request){
        $data = $request->validate([
            'fecha_hora' => 'required|date|after:now',
            'estado' => 'required|in:pendiente,confirmada,cancelada,completada',
            'notas_adicionales' => 'nullable|string|max:255',
            'id_cliente' => 'required|exists:clientes,id',
            'id_empleado' => 'required|exists:empleados,id',
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'distinct|exists:servicios,id',
        ]);

        // Extraer fecha y hora
        $fechaHora = Carbon::parse($data['fecha_hora']);
        $fecha = $fechaHora->toDateString(); // 'Y-m-d'
        $hora = $fechaHora->format('H:i:s');

        // Validar disponibilidad exacta en horario_trabajo
        $horario = HorarioTrabajo::where('id_empleado', $data['id_empleado'])
            ->where('fecha', $fecha)
            ->where('disponible', true)
            ->whereTime('hora_inicio', '<=', $hora)
            ->whereTime('hora_fin', '>=', $hora)
            ->first();

        if (!$horario) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['id_empleado' => 'El empleado no está disponible en la fecha y hora seleccionadas.']);
        }

        // Verificar si hay otra cita muy cercana (margen de 15 minutos)
        $fechaInicioMargen = $fechaHora->copy()->subMinutes(15);
        $fechaFinMargen = $fechaHora->copy()->addMinutes(15);

        $existeCitaCercana = Cita::where('id_empleado', $data['id_empleado'])
            ->whereBetween('fecha_hora', [$fechaInicioMargen, $fechaFinMargen])
            ->exists();

        if ($existeCitaCercana) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['fecha_hora' => 'Este empleado ya tiene una cita muy cerca de la hora seleccionada. Debe mantener un margen de 15 minutos.']);
        }

        // Guardar la cita
        $servicios = $data['servicios'];
        unset($data['servicios']);

        $cita = Cita::create($data);
        $cita->servicios()->attach($servicios);

        return redirect()->route('citas.index')->with('success', 'Cita creada correctamente con múltiples servicios.');
    }




    /**
     * Display the specified resource.
     */
    public function show(Cita $cita){
        return view('citas.show', compact('cita'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cita $cita){
        $clientes = Cliente::all();
        $empleados = Empleado::all();
        $servicios = Servicio::all();
        return view('citas.edit', compact('cita','clientes','empleados','servicios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cita $cita){
        $data = $request->validate([
            'estado' => 'required|in:pendiente,confirmada,cancelada,completada',
        ]);

        $cita->update($data);
        return redirect()->route('citas.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cita $cita){
        $cita->delete();
        return redirect()->route('citas.index')->with('success', 'La cita ha sido eliminada con exito.');
    }
}
