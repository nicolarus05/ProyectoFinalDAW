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
        $usuario = Auth::user();

        if ($usuario->rol === 'cliente') {
            $cliente = $usuario->cliente;
            if (!$cliente) {
                abort(403, 'No tienes permiso para acceder a esta sección.');
            }
            // Solo las citas del cliente
            $citas = $cliente->citas()->latest()->get();

        } else if ($usuario->rol === 'empleado') {
            $empleado = $usuario->empleado;
            if (!$empleado) {
                abort(403, 'No tienes permiso para acceder a esta sección.');
            }
            // Solo las citas del empleado
            $citas = Cita::where('id_empleado', $empleado->id)->latest()->get();

        } else if ($usuario->rol === 'admin') {
            // El admin ve todas las citas
            $citas = Cita::latest()->get();

        } else {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return view('Citas.index', compact('citas'));
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        $usuario = Auth::user();

        $clientes = $usuario->cliente;

        
        $empleados = Empleado::all();
        $servicios = Servicio::all();
        return view('Citas.create', compact('clientes','empleados','servicios'));
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request){
        $data = $request->validate([
            'fecha_hora' => 'required|date',
            'estado' => 'required|in:pendiente,confirmada,cancelada,completada',
            'notas_adicionales' => 'nullable|string|max:255',
            'id_cliente' => 'required|exists:clientes,id',
            'id_empleado' => 'required|exists:empleados,id',
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'distinct|exists:servicios,id',
        ]);

        // Validación de disponibilidad del empleado
        $fechaHora = Carbon::parse($data['fecha_hora']);
        $diaSemana = $fechaHora->format('l'); // Por ejemplo: "Lunes"

        // traducir el día a español si tus horarios están en español
        $dias = [
            'Monday' => 'lunes',
            'Tuesday' => 'martes',
            'Wednesday' => 'miércoles',
            'Thursday' => 'jueves',
            'Friday' => 'viernes',
            'Saturday' => 'sábado',
            'Sunday' => 'domingo',
        ];

        $diaSemanaTraducido = $dias[$diaSemana] ?? $diaSemana;
        $hora = $fechaHora->format('H:i:s');

        $horario = HorarioTrabajo::where('id_empleado', $data['id_empleado'])
            ->where('dia_semana', $diaSemanaTraducido)
            ->where('disponible', true)
            ->whereTime('hora_inicio', '<=', $hora)
            ->whereTime('hora_fin', '>=', $hora)
            ->first();

        if (!$horario) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['id_empleado' => 'El empleado no está disponible en la fecha y hora seleccionadas.']);
        }

        // Verifica que no haya otra cita en los 15 minutos antes o después
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

        // Extraemos los IDs de servicios
        $servicios = $data['servicios'];
        unset($data['servicios']);

        // Creamos la cita
        $cita = Cita::create($data);

        // Asociamos los servicios seleccionados
        $cita->servicios()->attach($servicios);

        return redirect()->route('Citas.index')->with('success', 'Cita creada correctamente con múltiples servicios.');
    }



    /**
     * Display the specified resource.
     */
    public function show(Cita $cita){
        return view('Citas.show', compact('cita'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cita $cita){
        $clientes = Cliente::all();
        $empleados = Empleado::all();
        $servicios = Servicio::all();
        return view('Citas.edit', compact('cita','clientes','empleados','servicios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cita $cita){
        $data = $request->validate([
            'estado' => 'required|in:pendiente,confirmada,cancelada,completada',
        ]);

        $cita->update($data);
        return redirect()->route('Citas.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cita $cita){
        $cita->delete();
        return redirect()->route('Citas.index')->with('success', 'La cita ha sido eliminada con exito.');
    }
}
