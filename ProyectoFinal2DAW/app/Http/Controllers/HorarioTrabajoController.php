<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HorarioTrabajo;
use App\Models\Empleado;
use App\Models\Cita;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\CacheService;
use App\Traits\HasFlashMessages;
use App\Traits\HasCrudMessages;
use App\Traits\HasJsonResponses;

class HorarioTrabajoController extends Controller{
    use HasFlashMessages, HasCrudMessages, HasJsonResponses;

    private function validarRangoHorario(string $horaInicio, string $horaFin): void
    {
        $error = HorarioTrabajo::validarRangoHorario($horaInicio, $horaFin);

        if ($error !== null) {
            throw ValidationException::withMessages([
                'hora_inicio' => $error,
            ]);
        }
    }

    private function validarConfiguracionesHorarios(Request $request, ?Empleado $empleado = null): void
    {
        foreach (['horario_invierno', 'horario_verano'] as $temporada) {
            if ($request->has($temporada)) {
                $configuracion = $request->input($temporada);
            } elseif ($empleado) {
                $configuracion = $empleado->{$temporada};
            } else {
                $configuracion = null;
            }

            if ($configuracion === null) {
                continue;
            }

            if (!is_array($configuracion)) {
                throw ValidationException::withMessages([
                    $temporada => 'La configuración del horario no es válida.',
                ]);
            }

            foreach ($configuracion as $dia => $horario) {
                if ($horario === null || $horario === '') {
                    continue;
                }

                if (!is_array($horario)) {
                    throw ValidationException::withMessages([
                        "{$temporada}.{$dia}" => 'La configuración del día no es válida.',
                    ]);
                }

                $horaInicio = $horario['inicio'] ?? '';
                $horaFin = $horario['fin'] ?? '';
                $error = HorarioTrabajo::validarRangoHorario($horaInicio, $horaFin);

                if ($error !== null) {
                    throw ValidationException::withMessages([
                        "{$temporada}.{$dia}" => $error,
                    ]);
                }
            }
        }
    }

    private function validarNoHayCitasEnRango(int $empleadoId, string $fechaInicio, string $fechaFin): void
    {
        if (
            Cita::where('id_empleado', $empleadoId)
                ->whereDate('fecha_hora', '>=', $fechaInicio)
                ->whereDate('fecha_hora', '<=', $fechaFin)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'fecha' => 'No se pueden regenerar horarios porque ya existen citas en el periodo seleccionado.',
            ]);
        }
    }

    private function citasQueBloqueanHorarios(
        int $empleadoId,
        string $fechaInicio,
        string $fechaFin
    ): \Illuminate\Support\Collection {
        return Cita::with('servicios')
            ->where('id_empleado', $empleadoId)
            ->whereIn('estado', ['pendiente', 'confirmada', 'completada'])
            ->whereDate('fecha_hora', '>=', $fechaInicio)
            ->whereDate('fecha_hora', '<=', $fechaFin)
            ->get()
            ->groupBy(fn (Cita $cita) => Carbon::parse($cita->fecha_hora)->toDateString());
    }

    private function cargarRangoHorario(HorarioTrabajo $horario): HorarioTrabajo
    {
        $horarioGeneral = HorarioTrabajo::where('id_empleado', $horario->id_empleado)
            ->whereDate('fecha', Carbon::parse($horario->fecha)->format('Y-m-d'))
            ->whereNotNull('hora_inicio')
            ->whereNotNull('hora_fin')
            ->first();

        if ($horarioGeneral) {
            return $horarioGeneral;
        }

        $horas = HorarioTrabajo::where('id_empleado', $horario->id_empleado)
            ->whereDate('fecha', Carbon::parse($horario->fecha)->format('Y-m-d'))
            ->whereNotNull('hora')
            ->orderBy('hora')
            ->pluck('hora');

        if ($horas->isNotEmpty()) {
            $horario->hora_inicio = Carbon::parse($horas->first())->format('H:i');
            $horario->hora_fin = Carbon::parse($horas->last())->format('H:i');
        }

        return $horario;
    }

    private function hayCitaEnBloque(int $empleadoId, string $fecha, string $hora): bool
    {
        $inicioBloque = Carbon::parse($fecha . ' ' . $hora);
        $finBloque = $inicioBloque->copy()->addMinutes(HorarioTrabajo::DURACION_BLOQUE_MINUTOS);

        $citas = Cita::with('servicios')
            ->where('id_empleado', $empleadoId)
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->whereDate('fecha_hora', $fecha)
            ->get();

        foreach ($citas as $cita) {
            $inicioCita = Carbon::parse($cita->fecha_hora);
            if ($inicioCita->lessThan($finBloque) && $cita->hora_fin->greaterThan($inicioBloque)) {
                return true;
            }
        }

        return false;
    }

    protected function getResourceName(): string
    {
        return 'horario';
    }
    /**
     * Display a listing of the resource.
     */
   public function index(){
	   // LIMITAR consulta a los últimos 3 meses y próximos 1 mes
	   $fechaInicio = now()->subMonths(3)->startOfDay();
	   $fechaFin = now()->addMonth()->endOfDay();

	   // Obtener solo horarios dentro del rango de fechas
	   $horariosRaw = HorarioTrabajo::with('empleado.user')
		   ->whereNotNull('hora')
		   ->whereBetween('fecha', [$fechaInicio, $fechaFin])
		   ->orderBy('fecha', 'desc')
		   ->orderBy('id_empleado')
		   ->limit(500) // Límite adicional de seguridad
		   ->get();

	   // Agrupar por empleado y fecha para mostrar jornadas completas
	   $horariosAgrupados = $horariosRaw->groupBy(function($item) {
		   return $item->id_empleado . '_' . $item->fecha->format('Y-m-d');
	   })->map(function($grupo) {
		   $primero = $grupo->first();
		   $horas = $grupo->pluck('hora')->sort();
		   $disponibles = $grupo->where('disponible', true)->count();
		   $totales = $grupo->count();

		   return (object)[
			   'id_empleado' => $primero->id_empleado,
			   'empleado' => $primero->empleado,
			   'fecha' => $primero->fecha,
			   'hora_inicio' => $horas->first(),
			   'hora_fin' => $horas->last(),
			   'total_bloques' => $totales,
			   'bloques_disponibles' => $disponibles,
			   'tipo_horario' => $primero->tipo_horario,
			   'notas' => $primero->notas,
			   'primer_id' => $primero->id,
		   ];
	   })->values()->take(50);

	   $empleados = Empleado::with('user')->get();

	   return view('horarios.index', compact('horariosAgrupados', 'empleados'));
	}


    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        $empleados = Empleado::all();
        return view('horarios.create', compact('empleados'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        $data = $request->validate([
            'id_empleado' => 'required|exists:empleados,id',
            'fecha' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'disponible' => 'boolean',
            'notas' => 'nullable|string|max:1000',
        ]);
        $this->validarRangoHorario($data['hora_inicio'], $data['hora_fin']);
        $this->validarNoHayCitasEnRango($data['id_empleado'], $data['fecha'], $data['fecha']);

        DB::transaction(function () use ($data) {
            // ELIMINAR todos los horarios existentes para este empleado en esta fecha
            HorarioTrabajo::where('id_empleado', $data['id_empleado'])
                ->where('fecha', $data['fecha'])
                ->delete();

            // CREAR bloques de 15 minutos
            $bloques = HorarioTrabajo::generarBloquesHorarios(
                $data['hora_inicio'],
                $data['hora_fin']
            );

            foreach ($bloques as $hora) {
                HorarioTrabajo::create([
                    'id_empleado' => $data['id_empleado'],
                    'fecha' => $data['fecha'],
                    'hora' => $hora,
                    'disponible' => $data['disponible'] ?? true,
                    'notas' => $data['notas'] ?? null,
                ]);
            }

            // CREAR registro general con hora_inicio y hora_fin para el calendario
            HorarioTrabajo::create([
                'id_empleado' => $data['id_empleado'],
                'fecha' => $data['fecha'],
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'],
                'disponible' => $data['disponible'] ?? true,
                'notas' => $data['notas'] ?? null,
            ]);
        });

        return $this->redirectWithSuccess(
            'horarios.index',
            'Horario creado correctamente. Se han sobrescrito los horarios anteriores de ese día.'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(HorarioTrabajo $horario){
        $horario = $this->cargarRangoHorario($horario);
        return view('horarios.show', compact('horario'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HorarioTrabajo $horario){
        $horario = $this->cargarRangoHorario($horario);
        $empleados = Empleado::all();
        return view('horarios.edit', compact('horario', 'empleados'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HorarioTrabajo $horario){
        $data = $request->validate([
            'id_empleado' => 'required|exists:empleados,id',
            'fecha' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'disponible' => 'boolean',
            'notas' => 'nullable|string|max:1000',
        ]);
        $this->validarRangoHorario($data['hora_inicio'], $data['hora_fin']);

        $empleadoOriginalId = (int) $horario->id_empleado;
        $fechaOriginal = Carbon::parse($horario->fecha)->format('Y-m-d');
        $empleadoNuevoId = (int) $data['id_empleado'];
        $fechaNueva = Carbon::parse($data['fecha'])->format('Y-m-d');

        $this->validarNoHayCitasEnRango($empleadoOriginalId, $fechaOriginal, $fechaOriginal);

        if ($empleadoOriginalId !== $empleadoNuevoId || $fechaOriginal !== $fechaNueva) {
            if (
                HorarioTrabajo::where('id_empleado', $empleadoNuevoId)
                    ->whereDate('fecha', $fechaNueva)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'fecha' => 'Ya existe un horario para ese empleado y fecha. Edítalo desde ese día o elige otra fecha.',
                ]);
            }

            $this->validarNoHayCitasEnRango($empleadoNuevoId, $fechaNueva, $fechaNueva);
        }

        DB::transaction(function () use (
            $empleadoOriginalId,
            $fechaOriginal,
            $data,
            $empleadoNuevoId,
            $fechaNueva
        ) {
            HorarioTrabajo::where('id_empleado', $empleadoOriginalId)
                ->whereDate('fecha', $fechaOriginal)
                ->delete();

            foreach (HorarioTrabajo::generarBloquesHorarios($data['hora_inicio'], $data['hora_fin']) as $hora) {
                HorarioTrabajo::create([
                    'id_empleado' => $empleadoNuevoId,
                    'fecha' => $fechaNueva,
                    'hora' => $hora,
                    'disponible' => $data['disponible'] ?? true,
                    'notas' => $data['notas'] ?? null,
                ]);
            }

            HorarioTrabajo::create([
                'id_empleado' => $empleadoNuevoId,
                'fecha' => $fechaNueva,
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'],
                'disponible' => $data['disponible'] ?? true,
                'notas' => $data['notas'] ?? null,
            ]);
        });

        return $this->redirectWithSuccess('horarios.index', $this->getUpdatedMessage());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HorarioTrabajo $horario){
        $fecha = Carbon::parse($horario->fecha)->format('Y-m-d');
        $this->validarNoHayCitasEnRango((int) $horario->id_empleado, $fecha, $fecha);

        DB::transaction(function () use ($horario, $fecha) {
            HorarioTrabajo::where('id_empleado', $horario->id_empleado)
                ->whereDate('fecha', $fecha)
                ->delete();
        });

        return $this->redirectWithSuccess('horarios.index', $this->getDeletedMessage());
    }

    /**
     * Mostrar formulario para configurar horarios antes de generar
     */
    public function mostrarFormularioGeneracion(Request $request){
        $empleadoId = $request->get('empleado');
        $tipo = $request->get('tipo');
        $empleado = Empleado::findOrFail($empleadoId);
        
        // Datos adicionales según el tipo
        $datos = [
            'empleado' => $empleado,
            'tipo' => $tipo,
            'fecha_inicio' => $request->get('fecha_inicio'),
            'mes' => $request->get('mes'),
            'anio' => $request->get('anio'),
        ];
        
        return view('horarios.configurar-generacion', $datos);
    }

    /**
     * Generar horarios para una semana completa (lunes a sábado)
     */
    public function generarSemana(Request $request){
        $request->validate([
            'id_empleado' => 'required|exists:empleados,id',
            'fecha_inicio' => 'required|date',
        ]);
        $empleado = Empleado::findOrFail($request->id_empleado);
        $this->validarConfiguracionesHorarios($request, $empleado);

        $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfWeek(); // Lunes
        $fechaFin = $fechaInicio->copy()->endOfWeek()->subDay(); // Sábado
        $citasPorFecha = $this->citasQueBloqueanHorarios(
            $empleado->id,
            $fechaInicio->format('Y-m-d'),
            $fechaFin->format('Y-m-d')
        );
        $registrosCreados = 0;

        DB::transaction(function () use ($request, $empleado, $fechaInicio, $fechaFin, $citasPorFecha, &$registrosCreados) {
            // PRIMERO: Guardar la configuración en el empleado
            if ($request->has('horario_invierno')) {
                $empleado->horario_invierno = $request->horario_invierno;
            }
            if ($request->has('horario_verano')) {
                $empleado->horario_verano = $request->horario_verano;
            }
            $empleado->save();

            // ELIMINAR horarios existentes de esta semana para este empleado
            HorarioTrabajo::where('id_empleado', $empleado->id)
                ->whereBetween('fecha', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')])
                ->delete();

            // Generar para 6 días (lunes a sábado)
            for ($dia = 0; $dia < 6; $dia++) {
                $fecha = $fechaInicio->copy()->addDays($dia);

                // Obtener horario personalizado del empleado o usar global
                $horarioDia = $empleado->obtenerHorario($fecha);

                if (!$horarioDia) {
                    // Día no laborable (domingo), saltar
                    continue;
                }

                $bloques = HorarioTrabajo::generarBloquesHorarios(
                    $horarioDia['inicio'],
                    $horarioDia['fin']
                );
                $citasDelDia = $citasPorFecha->get($fecha->toDateString(), collect());

                foreach ($bloques as $hora) {
                    HorarioTrabajo::create([
                        'id_empleado' => $empleado->id,
                        'fecha' => $fecha->format('Y-m-d'),
                        'hora' => $hora,
                        'disponible' => !HorarioTrabajo::bloqueOcupadoPorCitas(
                            $citasDelDia,
                            $fecha->toDateString(),
                            $hora
                        ),
                    ]);
                    $registrosCreados++;
                }
            }
        });

        return $this->redirectWithSuccess(
            'horarios.index',
            "Se crearon {$registrosCreados} bloques horarios para la semana."
        );
    }

    /**
     * Generar horarios para un mes completo
     */
    public function generarMes(Request $request){
        $request->validate([
            'id_empleado' => 'required|exists:empleados,id',
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2024',
        ]);
        $empleado = Empleado::findOrFail($request->id_empleado);
        $this->validarConfiguracionesHorarios($request, $empleado);
        $mes = $request->mes;
        $anio = $request->anio;
        $fechaInicio = Carbon::create($anio, $mes, 1);
        $fechaFin = $fechaInicio->copy()->endOfMonth();
        $citasPorFecha = $this->citasQueBloqueanHorarios(
            $empleado->id,
            $fechaInicio->format('Y-m-d'),
            $fechaFin->format('Y-m-d')
        );
        $registrosCreados = 0;

        DB::transaction(function () use ($request, $empleado, $fechaInicio, $fechaFin, $citasPorFecha, &$registrosCreados) {
        // PRIMERO: Guardar la configuración en el empleado
        if ($request->has('horario_invierno')) {
            $empleado->horario_invierno = $request->horario_invierno;
        }
        if ($request->has('horario_verano')) {
            $empleado->horario_verano = $request->horario_verano;
        }
        $empleado->save();

        // ELIMINAR horarios existentes de este mes para este empleado
        HorarioTrabajo::where('id_empleado', $empleado->id)
            ->whereBetween('fecha', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')])
            ->delete();
        
        $fecha = $fechaInicio->copy();
        while ($fecha <= $fechaFin) {
            // Solo días laborables (lunes a sábado)
            if (in_array($fecha->dayOfWeek, HorarioTrabajo::DIAS_LABORABLES)) {
                
                // Obtener horario personalizado del empleado o usar global
                $horarioDia = $empleado->obtenerHorario($fecha);
                
                if (!$horarioDia) {
                    // Día no laborable, saltar
                    $fecha->addDay();
                    continue;
                }
                
                $bloques = HorarioTrabajo::generarBloquesHorarios(
                    $horarioDia['inicio'],
                    $horarioDia['fin']
                );
                $citasDelDia = $citasPorFecha->get($fecha->toDateString(), collect());

                foreach ($bloques as $hora) {
                    HorarioTrabajo::create([
                        'id_empleado' => $empleado->id,
                        'fecha' => $fecha->format('Y-m-d'),
                        'hora' => $hora,
                        'disponible' => !HorarioTrabajo::bloqueOcupadoPorCitas(
                            $citasDelDia,
                            $fecha->toDateString(),
                            $hora
                        ),
                    ]);
                    $registrosCreados++;
                }
            }
            $fecha->addDay();
        }
        });

        return $this->redirectWithSuccess(
            'horarios.index',
            "Se crearon {$registrosCreados} bloques horarios para el mes."
        );
    }

    /**
     * Generar horarios para un año completo
     */
    public function generarAnual(Request $request){
        $request->validate([
            'id_empleado' => 'required|exists:empleados,id',
            'anio' => 'required|integer|min:2024',
        ]);
        $empleado = Empleado::findOrFail($request->id_empleado);
        $this->validarConfiguracionesHorarios($request, $empleado);
        $anio = $request->anio;
        $fechaInicioAnio = Carbon::create($anio, 1, 1);
        $fechaFinAnio = Carbon::create($anio, 12, 31);
        $citasPorFecha = $this->citasQueBloqueanHorarios(
            $empleado->id,
            $fechaInicioAnio->format('Y-m-d'),
            $fechaFinAnio->format('Y-m-d')
        );
        $registrosCreados = 0;

        DB::transaction(function () use ($request, $empleado, $anio, $fechaInicioAnio, $fechaFinAnio, $citasPorFecha, &$registrosCreados) {
            // PRIMERO: Guardar la configuración en el empleado
            if ($request->has('horario_invierno')) {
                $empleado->horario_invierno = $request->horario_invierno;
            }
            if ($request->has('horario_verano')) {
                $empleado->horario_verano = $request->horario_verano;
            }
            $empleado->save();

            // ELIMINAR todos los horarios existentes de este año para este empleado
            HorarioTrabajo::where('id_empleado', $empleado->id)
                ->whereBetween('fecha', [$fechaInicioAnio->format('Y-m-d'), $fechaFinAnio->format('Y-m-d')])
                ->delete();

            // Iterar por todos los meses del año
            for ($mes = 1; $mes <= 12; $mes++) {
                $fechaInicio = Carbon::create($anio, $mes, 1);
                $fechaFin = $fechaInicio->copy()->endOfMonth();

                $fecha = $fechaInicio->copy();
                while ($fecha <= $fechaFin) {
                    // Solo días laborables (lunes a sábado)
                    if (in_array($fecha->dayOfWeek, HorarioTrabajo::DIAS_LABORABLES)) {

                        // Obtener horario personalizado del empleado o usar global
                        $horarioDia = $empleado->obtenerHorario($fecha);

                        if (!$horarioDia) {
                            // Día no laborable, saltar
                            $fecha->addDay();
                            continue;
                        }

                        $bloques = HorarioTrabajo::generarBloquesHorarios(
                            $horarioDia['inicio'],
                            $horarioDia['fin']
                        );
                        $citasDelDia = $citasPorFecha->get($fecha->toDateString(), collect());

                        foreach ($bloques as $hora) {
                            HorarioTrabajo::create([
                                'id_empleado' => $empleado->id,
                                'fecha' => $fecha->format('Y-m-d'),
                                'hora' => $hora,
                                'disponible' => !HorarioTrabajo::bloqueOcupadoPorCitas(
                                    $citasDelDia,
                                    $fecha->toDateString(),
                                    $hora
                                ),
                            ]);
                            $registrosCreados++;
                        }
                    }
                    $fecha->addDay();
                }
            }
        });

        return $this->redirectWithSuccess(
            'horarios.index',
            "Se crearon {$registrosCreados} bloques horarios para el año {$anio}."
        );
    }

    /**
     * Toggle disponibilidad de un bloque horario (AJAX)
     */
    public function toggleDisponibilidad(Request $request){
        $request->validate([
            'id' => 'required|exists:horario_trabajo,id',
            'notas' => 'nullable|string|max:255',
        ]);

        $horario = HorarioTrabajo::findOrFail($request->id);

        if (
            $horario->disponible
            && $horario->hora
            && $this->hayCitaEnBloque(
                (int) $horario->id_empleado,
                Carbon::parse($horario->fecha)->format('Y-m-d'),
                $horario->hora
            )
        ) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se puede deshabilitar un bloque que tiene una cita activa.'
            ], 422);
        }

        $horario->disponible = !$horario->disponible;
        
        // Si se está deshabilitando, guardar la nota
        if (!$horario->disponible && $request->notas) {
            $horario->notas = $request->notas;
        }
        
        // Si se está habilitando, limpiar la nota
        if ($horario->disponible) {
            $horario->notas = null;
        }
        
        $horario->save();

        return response()->json([
            'success' => true,
            'disponible' => $horario->disponible,
            'mensaje' => $horario->disponible ? 'Bloque habilitado' : 'Bloque deshabilitado'
        ]);
    }

    /**
     * Toggle disponibilidad de múltiples bloques horarios (rango) (AJAX)
     */
    public function toggleDisponibilidadRango(Request $request){
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|exists:horario_trabajo,id',
            'notas' => 'required|string|max:255',
        ]);

        $count = 0;
        $horarios = HorarioTrabajo::whereIn('id', $request->ids)->get();

        foreach ($horarios as $horario) {
            if (
                $horario->disponible
                && $horario->hora
                && $this->hayCitaEnBloque(
                    (int) $horario->id_empleado,
                    Carbon::parse($horario->fecha)->format('Y-m-d'),
                    $horario->hora
                )
            ) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'No se puede deshabilitar un rango que contiene una cita activa.'
                ], 422);
            }
        }

        foreach ($request->ids as $id) {
            $horario = HorarioTrabajo::find($id);
            if ($horario && $horario->disponible) {
                $horario->disponible = false;
                $horario->notas = $request->notas;
                $horario->save();
                $count++;
            }
        }

        return response()->json([
            'success' => true,
            'count' => $count,
            'mensaje' => "{$count} bloques deshabilitados correctamente"
        ]);
    }

    /**
     * Deshabilitar un rango de horas
     */
    public function deshabilitarBloque(Request $request){
        $request->validate([
            'id_empleado' => 'required|exists:empleados,id',
            'fecha' => 'required|date',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i',
        ]);
        $errorRango = HorarioTrabajo::validarRangoHorario(
            $request->hora_inicio,
            $request->hora_fin
        );
        if ($errorRango !== null) {
            throw ValidationException::withMessages(['hora_inicio' => $errorRango]);
        }

        $bloques = HorarioTrabajo::generarBloquesHorarios(
            $request->hora_inicio,
            $request->hora_fin
        );

        $actualizados = 0;
        foreach ($bloques as $hora) {
            if ($this->hayCitaEnBloque((int) $request->id_empleado, $request->fecha, $hora)) {
                throw ValidationException::withMessages([
                    'hora_inicio' => 'No se puede deshabilitar un rango que contiene una cita activa.',
                ]);
            }
        }

        foreach ($bloques as $hora) {
            $updated = HorarioTrabajo::where('id_empleado', $request->id_empleado)
                ->where('fecha', $request->fecha)
                ->where('hora', $hora)
                ->update(['disponible' => false]);
            
            $actualizados += $updated;
        }

        return redirect()->back()
            ->with('success', "Se deshabilitaron {$actualizados} bloques horarios.");
    }

    /**
     * Vista de calendario
     */
    public function calendario(Request $request){
        $empleados = Empleado::with('user')->get();
        $empleadoId = $request->empleado_id ?? null;
        $mes = $request->mes ?? now()->month;
        $anio = $request->anio ?? now()->year;

        // Datos del calendario
        $fecha = Carbon::create($anio, $mes, 1);
        $diasEnMes = $fecha->daysInMonth;
        $primerDiaSemana = $fecha->dayOfWeek; // 0 = domingo

        // Obtener horarios del mes si hay empleado seleccionado
        // Obtener horarios del mes si hay empleado seleccionado
		$horarios = collect();
		if ($empleadoId) {
			$horarios = HorarioTrabajo::where('id_empleado', $empleadoId)
				->whereYear('fecha', $anio)
				->whereMonth('fecha', $mes)
				->select('id', 'fecha', 'hora', 'disponible', 'notas', 'tipo_horario') // Solo campos necesarios
				->get()
				->groupBy(function($item) {
					return $item->fecha->format('Y-m-d');
				});
		}

        return view('horarios.calendario', compact(
            'empleados',
            'empleadoId',
            'mes',
            'anio',
            'fecha',
            'diasEnMes',
            'primerDiaSemana',
            'horarios'
        ));
    }

    /**
     * Obtener bloques de un día específico (AJAX)
     */
    public function bloquesDia(Request $request){
        $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'fecha' => 'required|date',
        ]);

        $bloques = HorarioTrabajo::where('id_empleado', $request->empleado_id)
            ->where('fecha', $request->fecha)
            ->whereNotNull('hora') // Solo bloques individuales, no el registro de rango
            ->orderBy('hora')
            ->get()
            ->map(function($bloque) {
                return [
                    'id' => $bloque->id,
                    'hora' => $bloque->hora,
                    'disponible' => (bool) $bloque->disponible,
                    'notas' => $bloque->notas,
                    'tipo_horario' => $bloque->tipo_horario,
                ];
            });

        return response()->json([
            'success' => true,
            'bloques' => $bloques,
            'fecha' => Carbon::parse($request->fecha)->format('d/m/Y')
        ]);
    }
}
