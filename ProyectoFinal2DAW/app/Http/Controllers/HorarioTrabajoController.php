<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HorarioTrabajo;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HorarioTrabajoController extends Controller{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        // Obtener horarios agrupados por empleado, fecha
        $horariosRaw = HorarioTrabajo::with('empleado.user')
            ->whereNotNull('hora')
            ->orderBy('fecha', 'desc')
            ->orderBy('id_empleado')
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
                'primer_id' => $primero->id, // Para acciones
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

        return redirect()->route('horarios.index')
            ->with('success', 'Horario creado correctamente. Se han sobrescrito los horarios anteriores de ese día.');
    }

    /**
     * Display the specified resource.
     */
    public function show(HorarioTrabajo $horario){
        return view('horarios.show', compact('horario'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HorarioTrabajo $horario){
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

        $horario->update($data);
        return redirect()->route('horarios.index')->with('success', 'El horario ha sido actualizado con éxito.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HorarioTrabajo $horario){
        $horario->delete();
        return redirect()->route('horarios.index')->with('success', 'El horario ha sido eliminado con éxito.');
    }

    /**
     * Generar horarios para una semana completa (lunes a sábado)
     */
    public function generarSemana(Request $request){
        $request->validate([
            'id_empleado' => 'required|exists:empleados,id',
            'fecha_inicio' => 'required|date',
        ]);

        $empleadoId = $request->id_empleado;
        $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfWeek(); // Lunes
        $registrosCreados = 0;

        // Generar para 6 días (lunes a sábado)
        for ($dia = 0; $dia < 6; $dia++) {
            $fecha = $fechaInicio->copy()->addDays($dia);
            
            // Obtener horario específico para este día
            $horarioDia = HorarioTrabajo::obtenerHorarioPorFecha($fecha);
            
            if (!$horarioDia) {
                // Día no laborable (domingo), saltar
                continue;
            }
            
            $bloques = HorarioTrabajo::generarBloquesHorarios(
                $horarioDia['inicio'],
                $horarioDia['fin']
            );

            foreach ($bloques as $hora) {
                // Verificar si ya existe
                $existe = HorarioTrabajo::where('id_empleado', $empleadoId)
                    ->where('fecha', $fecha->format('Y-m-d'))
                    ->where('hora', $hora)
                    ->exists();

                if (!$existe) {
                    HorarioTrabajo::create([
                        'id_empleado' => $empleadoId,
                        'fecha' => $fecha->format('Y-m-d'),
                        'hora' => $hora,
                        'disponible' => true,
                    ]);
                    $registrosCreados++;
                }
            }
        }

        return redirect()->route('horarios.index')
            ->with('success', "Se crearon {$registrosCreados} bloques horarios para la semana.");
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

        $empleadoId = $request->id_empleado;
        $mes = $request->mes;
        $anio = $request->anio;
        $registrosCreados = 0;

        $fechaInicio = Carbon::create($anio, $mes, 1);
        $fechaFin = $fechaInicio->copy()->endOfMonth();

        $fecha = $fechaInicio->copy();
        while ($fecha <= $fechaFin) {
            // Solo días laborables (lunes a sábado)
            if (in_array($fecha->dayOfWeek, HorarioTrabajo::DIAS_LABORABLES)) {
                
                // Obtener horario específico para este día
                $horarioDia = HorarioTrabajo::obtenerHorarioPorFecha($fecha);
                
                if (!$horarioDia) {
                    // Día no laborable, saltar
                    $fecha->addDay();
                    continue;
                }
                
                $bloques = HorarioTrabajo::generarBloquesHorarios(
                    $horarioDia['inicio'],
                    $horarioDia['fin']
                );

                foreach ($bloques as $hora) {
                    $existe = HorarioTrabajo::where('id_empleado', $empleadoId)
                        ->where('fecha', $fecha->format('Y-m-d'))
                        ->where('hora', $hora)
                        ->exists();

                    if (!$existe) {
                        HorarioTrabajo::create([
                            'id_empleado' => $empleadoId,
                            'fecha' => $fecha->format('Y-m-d'),
                            'hora' => $hora,
                            'disponible' => true,
                        ]);
                        $registrosCreados++;
                    }
                }
            }
            $fecha->addDay();
        }

        return redirect()->route('horarios.index')
            ->with('success', "Se crearon {$registrosCreados} bloques horarios para el mes.");
    }

    /**
     * Generar horarios para un año completo
     */
    public function generarAnual(Request $request){
        $request->validate([
            'id_empleado' => 'required|exists:empleados,id',
            'anio' => 'required|integer|min:2024',
        ]);

        $empleadoId = $request->id_empleado;
        $anio = $request->anio;
        $registrosCreados = 0;

        // Iterar por todos los meses del año
        for ($mes = 1; $mes <= 12; $mes++) {
            $fechaInicio = Carbon::create($anio, $mes, 1);
            $fechaFin = $fechaInicio->copy()->endOfMonth();

            $fecha = $fechaInicio->copy();
            while ($fecha <= $fechaFin) {
                // Solo días laborables (lunes a sábado)
                if (in_array($fecha->dayOfWeek, HorarioTrabajo::DIAS_LABORABLES)) {
                    
                    // Obtener horario específico para este día
                    $horarioDia = HorarioTrabajo::obtenerHorarioPorFecha($fecha);
                    
                    if (!$horarioDia) {
                        // Día no laborable, saltar
                        $fecha->addDay();
                        continue;
                    }
                    
                    $bloques = HorarioTrabajo::generarBloquesHorarios(
                        $horarioDia['inicio'],
                        $horarioDia['fin']
                    );

                    foreach ($bloques as $hora) {
                        $existe = HorarioTrabajo::where('id_empleado', $empleadoId)
                            ->where('fecha', $fecha->format('Y-m-d'))
                            ->where('hora', $hora)
                            ->exists();

                        if (!$existe) {
                            HorarioTrabajo::create([
                                'id_empleado' => $empleadoId,
                                'fecha' => $fecha->format('Y-m-d'),
                                'hora' => $hora,
                                'disponible' => true,
                            ]);
                            $registrosCreados++;
                        }
                    }
                }
                $fecha->addDay();
            }
        }

        return redirect()->route('horarios.index')
            ->with('success', "Se crearon {$registrosCreados} bloques horarios para el año {$anio}.");
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

        $bloques = HorarioTrabajo::generarBloquesHorarios(
            $request->hora_inicio,
            $request->hora_fin
        );

        $actualizados = 0;
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
        $horarios = collect();
        if ($empleadoId) {
            $horarios = HorarioTrabajo::where('id_empleado', $empleadoId)
                ->whereYear('fecha', $anio)
                ->whereMonth('fecha', $mes)
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
