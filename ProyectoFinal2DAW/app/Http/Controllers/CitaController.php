<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empleado;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Cita;
use App\Models\HorarioTrabajo;
use App\Services\NotificacionEmailService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CitaController extends Controller{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request){
        $user = Auth::user();

        // Fecha seleccionada (por defecto hoy)
        $fecha = $request->fecha ? Carbon::parse($request->fecha) : Carbon::today();

        // Obtener todos los empleados para las columnas del calendario
        $empleados = Empleado::with('user')->get();

        // Generar franjas horarias cada 30 minutos
        $horariosArray = HorarioTrabajo::generarBloquesHorarios('08:00', '20:00');

        if ($user->rol === 'cliente') {
            $cliente = $user->cliente;
            if (!$cliente) {
                abort(403, 'No tienes permiso para acceder a esta sección.');
            }
            // Solo las citas del cliente para la fecha seleccionada (excluir canceladas)
            $citas = Cita::with(['cliente.user', 'empleado.user', 'servicios'])
                ->where('id_cliente', $cliente->id)
                ->where('estado', '!=', 'cancelada')
                ->porFecha($fecha)
                ->orderBy('fecha_hora')
                ->get()
                ->groupBy('id_empleado');

        } else if ($user->rol === 'empleado') {
            $empleado = $user->empleado;
            if (!$empleado) {
                abort(403, 'No tienes permiso para acceder a esta sección.');
            }
            // El empleado ve todas las citas del día (no solo las suyas, excluir canceladas)
            $citas = Cita::with(['cliente.user', 'empleado.user', 'servicios'])
                ->where('estado', '!=', 'cancelada')
                ->porFecha($fecha)
                ->orderBy('fecha_hora')
                ->get()
                ->groupBy('id_empleado');

        } else if ($user->rol === 'admin') {
            // El admin ve todas las citas del día (excluir canceladas)
            $citas = Cita::with(['cliente.user', 'empleado.user', 'servicios'])
                ->where('estado', '!=', 'cancelada')
                ->porFecha($fecha)
                ->orderBy('fecha_hora')
                ->get()
                ->groupBy('id_empleado');

        } else {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        // Obtener horarios disponibles de los empleados para la fecha
        $horariosEmpleados = HorarioTrabajo::with('empleado')
            ->where('fecha', $fecha->format('Y-m-d'))
            ->get()
            ->groupBy('id_empleado');

        return view('citas.index', compact('citas', 'empleados', 'fecha', 'horariosArray', 'horariosEmpleados'));
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
            'notas_adicionales' => 'nullable|string|max:255',
            'id_cliente' => 'required|exists:clientes,id',
            'id_empleado' => 'required|exists:empleados,id',
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'distinct|exists:servicios,id',
        ]);

        // Establecer estado automáticamente en "pendiente"
        $data['estado'] = 'pendiente';

        // Validar que los servicios sean de la misma categoría que el empleado
        $empleado = Empleado::findOrFail($data['id_empleado']);
        $serviciosSeleccionados = Servicio::whereIn('id', $data['servicios'])->get();
        
        foreach ($serviciosSeleccionados as $servicio) {
            if ($servicio->categoria !== $empleado->categoria) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['servicios' => "El empleado seleccionado ({$empleado->user->nombre}) es de categoría '{$empleado->categoria}', pero se ha seleccionado un servicio de categoría '{$servicio->categoria}' ({$servicio->nombre}). Por favor, seleccione servicios de la misma categoría."]);
            }
        }

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

        // Enviar email de confirmación
        try {
            $notificacionService = new NotificacionEmailService();
            $notificacionService->enviarConfirmacionCita($cita->load(['cliente.user', 'servicios', 'empleado.user']));
        } catch (\Exception $e) {
            // Log del error pero no bloquear la creación de la cita
            Log::error("Error al enviar email de confirmación: " . $e->getMessage());
        }

        // Extraer la fecha de la cita para redirigir al día correcto
        $fechaCita = Carbon::parse($cita->fecha_hora)->format('Y-m-d');

        return redirect()->route('citas.index', ['fecha' => $fechaCita])
            ->with('success', 'Cita creada correctamente con múltiples servicios.');
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
            'estado' => 'required|in:pendiente,completada,cancelada',
        ]);

        $estadoAnterior = $cita->estado;
        $cita->update($data);
        
        // Ya no se envían notificaciones por cambio de estado (solo pendiente y completada)
        
        return redirect()->route('citas.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cita $cita){
        $cita->delete();
        return redirect()->route('citas.index')->with('success', 'La cita ha sido eliminada con exito.');
    }

    /**
     * Cancelar una cita
     */
    public function cancelar(Cita $cita){
        // Cambiar el estado a cancelada
        $cita->update(['estado' => 'cancelada']);

        // Liberar las horas ocupadas por esta cita
        // Obtener la fecha y hora de la cita
        $fechaHora = Carbon::parse($cita->fecha_hora);
        $empleadoId = $cita->id_empleado;
        
        // Calcular duración total de los servicios
        $duracionTotal = $cita->servicios->sum('tiempo_estimado');
        $bloques = ceil($duracionTotal / 30); // Bloques de 30 minutos
        
        // Liberar cada bloque de tiempo
        $horaActual = $fechaHora->copy();
        for ($i = 0; $i < $bloques; $i++) {
            HorarioTrabajo::where('id_empleado', $empleadoId)
                ->whereDate('fecha', $horaActual->format('Y-m-d'))
                ->where('hora', $horaActual->format('H:i:s'))
                ->update(['disponible' => true]);
            
            $horaActual->addMinutes(30);
        }

        return redirect()->route('citas.index')->with('success', 'La cita ha sido cancelada y las horas han sido liberadas.');
    }

    /**
     * Mover cita (drag & drop) - AJAX
     */
    public function moverCita(Request $request){
        $request->validate([
            'cita_id' => 'required|exists:citas,id',
            'nueva_fecha_hora' => 'required|date',
            'nuevo_empleado_id' => 'required|exists:empleados,id',
        ]);

        $cita = Cita::findOrFail($request->cita_id);
        $nuevaFechaHora = Carbon::parse($request->nueva_fecha_hora);
        $nuevoEmpleadoId = $request->nuevo_empleado_id;

        // Validar que no esté fuera del horario laboral
        $horarioDisponible = HorarioTrabajo::where('id_empleado', $nuevoEmpleadoId)
            ->porFecha($nuevaFechaHora->format('Y-m-d'))
            ->where('hora', $nuevaFechaHora->format('H:i:s'))
            ->where('disponible', true)
            ->exists();

        if (!$horarioDisponible) {
            return response()->json([
                'success' => false,
                'message' => 'El empleado no está disponible en este horario.'
            ], 400);
        }

        // Validar superposición con otras citas del mismo empleado
        $horaFin = $nuevaFechaHora->copy()->addMinutes($cita->duracion_minutos);
        
        $citaSuperpuesta = Cita::where('id_empleado', $nuevoEmpleadoId)
            ->where('id', '!=', $cita->id)
            ->where(function($query) use ($nuevaFechaHora, $horaFin) {
                $query->whereBetween('fecha_hora', [$nuevaFechaHora, $horaFin])
                    ->orWhere(function($q) use ($nuevaFechaHora, $horaFin) {
                        $q->where('fecha_hora', '<', $nuevaFechaHora)
                          ->whereRaw('DATE_ADD(fecha_hora, INTERVAL duracion_minutos MINUTE) > ?', [$nuevaFechaHora]);
                    });
            })
            ->exists();

        if ($citaSuperpuesta) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una cita que se superpone en este horario.'
            ], 400);
        }

        // Actualizar cita
        $cita->fecha_hora = $nuevaFechaHora;
        $cita->id_empleado = $nuevoEmpleadoId;
        $cita->save();

        return response()->json([
            'success' => true,
            'message' => 'Cita movida correctamente.',
            'cita' => $cita->load(['cliente.user', 'empleado.user', 'servicios'])
        ]);
    }

    /**
     * Marcar cita como completada - AJAX
     */
    public function marcarCompletada(Request $request){
        $request->validate([
            'cita_id' => 'required|exists:citas,id',
        ]);

        $cita = Cita::findOrFail($request->cita_id);
        $cita->estado = 'completada';
        $cita->save();

        return response()->json([
            'success' => true,
            'message' => 'Cita marcada como completada.',
            'cita' => $cita
        ]);
    }

    /**
     * Completar cita y redirigir a cobro
     */
    public function completarYCobrar($id){
        $cita = Cita::findOrFail($id);
        
        // Marcar como completada
        $cita->estado = 'completada';
        $cita->save();

        // Redirigir al formulario de cobro con el id_cita
        return redirect()->route('cobros.create.direct', ['id_cita' => $cita->id]);
    }

    /**
     * Actualizar duración de cita - AJAX
     */
    public function actualizarDuracion(Request $request){
        $request->validate([
            'cita_id' => 'required|exists:citas,id',
            'duracion_minutos' => 'required|integer|min:15|max:480', // Entre 15 min y 8 horas
        ]);

        $cita = Cita::findOrFail($request->cita_id);
        
        // Validar que la nueva duración no cause superposición
        $horaInicio = Carbon::parse($cita->fecha_hora);
        $nuevaHoraFin = $horaInicio->copy()->addMinutes($request->duracion_minutos);
        
        $citaSuperpuesta = Cita::where('id_empleado', $cita->id_empleado)
            ->where('id', '!=', $cita->id)
            ->where('fecha_hora', '>=', $horaInicio)
            ->where('fecha_hora', '<', $nuevaHoraFin)
            ->exists();

        if ($citaSuperpuesta) {
            return response()->json([
                'success' => false,
                'message' => 'La nueva duración causaría superposición con otra cita.'
            ], 400);
        }

        $cita->duracion_minutos = $request->duracion_minutos;
        $cita->save();

        return response()->json([
            'success' => true,
            'message' => 'Duración actualizada correctamente.',
            'cita' => $cita->load(['cliente.user', 'empleado.user', 'servicios'])
        ]);
    }
}
