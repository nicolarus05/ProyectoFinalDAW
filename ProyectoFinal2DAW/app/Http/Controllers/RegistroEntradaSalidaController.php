<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroEntradaSalida;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegistroEntradaSalidaController extends Controller{
    /**
     * Vista principal de asistencia (Solo Admin)
     */
    public function index(Request $request){
        // Verificar que el usuario es admin
        if (Auth::user()->rol !== 'admin') {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        $fecha = $request->get('fecha', Carbon::today()->format('Y-m-d'));
        $empleadoId = $request->get('empleado_id');

        $query = RegistroEntradaSalida::with('empleado.user')
            ->orderBy('fecha', 'desc')
            ->orderBy('hora_entrada', 'desc');

        // Filtrar por fecha
        if ($fecha) {
            $query->whereDate('fecha', $fecha);
        }

        // Filtrar por empleado
        if ($empleadoId) {
            $query->where('id_empleado', $empleadoId);
        }

        $registros = $query->paginate(20);

        // Obtener empleados actualmente trabajando
        $empleadosActivos = RegistroEntradaSalida::with('empleado.user')
            ->whereDate('fecha', Carbon::today())
            ->whereNotNull('hora_entrada')
            ->whereNull('hora_salida')
            ->get();

        // Calcular estadísticas del día
        $registrosHoy = RegistroEntradaSalida::whereDate('fecha', Carbon::today())->get();
        $totalHorasHoy = 0;
        
        foreach ($registrosHoy as $registro) {
            $horas = $registro->calcularHorasTrabajadas();
            if ($horas) {
                $totalHorasHoy += $horas['total_minutos'];
            }
        }

        $estadisticas = [
            'presentes' => $empleadosActivos->count(),
            'total_horas' => sprintf('%dh %02dmin', floor($totalHorasHoy / 60), $totalHorasHoy % 60),
            'total_registros_hoy' => $registrosHoy->count()
        ];

        $empleados = Empleado::with('user')->get();

        return view('asistencia.index', compact('registros', 'empleadosActivos', 'estadisticas', 'empleados', 'fecha', 'empleadoId'));
    }

    /**
     * Registrar entrada del empleado
     */
    public function registrarEntrada(Request $request){
        $user = Auth::user();
        
        // Verificar que el usuario tiene un empleado asociado
        if (!$user->empleado) {
            return back()->with('error', 'No tienes un perfil de empleado asociado.');
        }

        $empleadoId = $user->empleado->id;
        $hoy = Carbon::today();
        $horaActual = Carbon::now();

        // Verificar si ya tiene un registro hoy
        $registroExistente = RegistroEntradaSalida::registroDelDia($empleadoId, $hoy);

        if ($registroExistente) {
            if ($registroExistente->estaEnJornada()) {
                return back()->with('error', 'Ya has registrado tu entrada hoy a las ' . $registroExistente->hora_entrada);
            } else {
                return back()->with('error', 'Ya has completado tu jornada de hoy.');
            }
        }

        // Buscar el horario de trabajo para hoy
        $horario = \App\Models\HorarioTrabajo::where('id_empleado', $empleadoId)
            ->whereDate('fecha', $hoy)
            ->first();

        // Validar hora de entrada según horario
        if ($horario) {
            // Si existe un horario específico, validar contra ese horario
            $horaInicioPermitida = Carbon::parse($hoy->format('Y-m-d') . ' ' . $horario->hora_inicio);
            $margenAntes = 15; // 15 minutos antes está permitido
            $margenDespues = 30; // 30 minutos después se considera tarde
            
            $horaInicioMin = $horaInicioPermitida->copy()->subMinutes($margenAntes);
            $horaInicioMax = $horaInicioPermitida->copy()->addMinutes($margenDespues);
            
            if ($horaActual->lt($horaInicioMin)) {
                return back()->with('error', 
                    'Es muy temprano para fichar. Tu horario de entrada es a las ' . 
                    Carbon::parse($horario->hora_inicio)->format('H:i') . 
                    ' (puedes fichar desde las ' . $horaInicioMin->format('H:i') . ').'
                );
            }
            
            $mensaje = '✓ Entrada registrada correctamente a las ' . $horaActual->format('H:i');
            
            // Advertencia si llega tarde
            if ($horaActual->gt($horaInicioPermitida)) {
                $minutosTarde = $horaActual->diffInMinutes($horaInicioPermitida);
                $mensaje .= ' ⚠️ Llegada ' . $minutosTarde . ' minuto(s) tarde.';
            } elseif ($horaActual->lt($horaInicioPermitida)) {
                $minutosAntes = $horaInicioPermitida->diffInMinutes($horaActual);
                $mensaje .= ' ✅ Llegada ' . $minutosAntes . ' minuto(s) antes.';
            } else {
                $mensaje .= ' ✅ Puntual.';
            }
        } else {
            // Si no hay horario específico, aplicar horarios generales
            $mes = $hoy->month;
            $diaSemana = $hoy->dayOfWeek; // 0=Domingo, 6=Sábado
            
            // Verificar que es día laborable (Lunes a Sábado)
            if ($diaSemana === 0) {
                return back()->with('error', 'No se puede fichar los domingos.');
            }
            
            // Determinar hora de inicio según mes (verano o normal)
            $horaInicio = in_array($mes, \App\Models\HorarioTrabajo::MESES_VERANO) 
                ? \App\Models\HorarioTrabajo::HORA_INICIO_VERANO 
                : \App\Models\HorarioTrabajo::HORA_INICIO_INVIERNO_LV;
            
            $horaInicioPermitida = Carbon::parse($hoy->format('Y-m-d') . ' ' . $horaInicio);
            $margenAntes = 15;
            $horaInicioMin = $horaInicioPermitida->copy()->subMinutes($margenAntes);
            
            if ($horaActual->lt($horaInicioMin)) {
                return back()->with('error', 
                    'Es muy temprano para fichar. El horario de entrada es a las ' . 
                    $horaInicioPermitida->format('H:i') . 
                    ' (puedes fichar desde las ' . $horaInicioMin->format('H:i') . ').'
                );
            }
            
            $mensaje = '✓ Entrada registrada correctamente a las ' . $horaActual->format('H:i');
            
            if ($horaActual->gt($horaInicioPermitida)) {
                $minutosTarde = $horaActual->diffInMinutes($horaInicioPermitida);
                $mensaje .= ' ⚠️ Llegada ' . $minutosTarde . ' minuto(s) tarde.';
            } elseif ($horaActual->lt($horaInicioPermitida)) {
                $minutosAntes = $horaInicioPermitida->diffInMinutes($horaActual);
                $mensaje .= ' ✅ Llegada ' . $minutosAntes . ' minuto(s) antes.';
            } else {
                $mensaje .= ' ✅ Puntual.';
            }
        }

        // Crear el registro
        RegistroEntradaSalida::create([
            'id_empleado' => $empleadoId,
            'fecha' => $hoy,
            'hora_entrada' => $horaActual->format('H:i:s'),
            'hora_salida' => null,
        ]);

        return back()->with('success', $mensaje);
    }

    /**
     * Registrar salida del empleado
     */
    public function registrarSalida(Request $request){
        $user = Auth::user();
        
        if (!$user->empleado) {
            return back()->with('error', 'No tienes un perfil de empleado asociado.');
        }

        $empleadoId = $user->empleado->id;
        $hoy = Carbon::today();

        // Buscar el registro de hoy
        $registro = RegistroEntradaSalida::registroDelDia($empleadoId, $hoy);

        if (!$registro) {
            return back()->with('error', 'No has registrado tu entrada hoy.');
        }

        if (!$registro->estaEnJornada()) {
            return back()->with('error', 'Ya has registrado tu salida hoy.');
        }

        // Actualizar con la hora de salida
        $horaSalida = Carbon::now()->format('H:i:s');
        $registro->update([
            'hora_salida' => $horaSalida,
        ]);

        $horasTrabajadas = $registro->calcularHorasTrabajadas();
        
        return back()->with('success', '✓ Salida registrada correctamente a las ' . Carbon::now()->format('H:i') . '. Has trabajado ' . $horasTrabajadas['formatted']);
    }

    /**
     * Desconectar empleado (Solo Admin)
     */
    public function desconectarEmpleado($registroId){
        // Verificar que el usuario es admin
        if (Auth::user()->rol !== 'admin') {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }

        $registro = RegistroEntradaSalida::findOrFail($registroId);

        if (!$registro->estaEnJornada()) {
            return back()->with('error', 'Este empleado ya ha registrado su salida.');
        }

        // Registrar salida con hora actual
        $horaSalida = Carbon::now()->format('H:i:s');
        $registro->update([
            'hora_salida' => $horaSalida,
        ]);

        $horasTrabajadas = $registro->calcularHorasTrabajadas();
        $nombreEmpleado = $registro->empleado->user->nombre . ' ' . $registro->empleado->user->apellidos;
        
        return back()->with('success', "✓ {$nombreEmpleado} desconectado a las " . Carbon::now()->format('H:i') . '. Trabajó ' . $horasTrabajadas['formatted']);
    }

    /**
     * Ver historial personal del empleado
     */
    public function miHistorial(Request $request){
        $user = Auth::user();
        
        if (!$user->empleado) {
            return redirect()->route('dashboard')->with('error', 'No tienes un perfil de empleado asociado.');
        }

        $empleadoId = $user->empleado->id;
        
        // Filtros
        $fechaInicio = $request->get('fecha_inicio', Carbon::now()->subDays(30)->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', Carbon::today()->format('Y-m-d'));

        $registros = RegistroEntradaSalida::where('id_empleado', $empleadoId)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->orderBy('fecha', 'desc')
            ->get();

        // Calcular totales
        $totalMinutos = 0;
        $diasTrabajados = 0;

        foreach ($registros as $registro) {
            $horas = $registro->calcularHorasTrabajadas();
            if ($horas) {
                $totalMinutos += $horas['total_minutos'];
                $diasTrabajados++;
            }
        }

        $estadisticas = [
            'total_horas' => sprintf('%dh %02dmin', floor($totalMinutos / 60), $totalMinutos % 60),
            'dias_trabajados' => $diasTrabajados,
            'promedio_diario' => $diasTrabajados > 0 ? sprintf('%dh %02dmin', floor(($totalMinutos / $diasTrabajados) / 60), ($totalMinutos / $diasTrabajados) % 60) : '0h 00min'
        ];

        return view('asistencia.mi-historial', compact('registros', 'estadisticas', 'fechaInicio', 'fechaFin'));
    }

    /**
     * Ver historial de un empleado específico (Admin)
     */
    public function porEmpleado($empleadoId, Request $request){
        // Verificar que el usuario es admin
        if (Auth::user()->rol !== 'admin') {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        $empleado = Empleado::with('user')->findOrFail($empleadoId);
        
        $fechaInicio = $request->get('fecha_inicio', Carbon::now()->subDays(30)->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', Carbon::today()->format('Y-m-d'));

        $registros = RegistroEntradaSalida::where('id_empleado', $empleadoId)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->orderBy('fecha', 'desc')
            ->paginate(15);

        // Calcular totales (obtener todos los registros para las estadísticas)
        $todosRegistros = RegistroEntradaSalida::where('id_empleado', $empleadoId)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->get();
            
        $totalMinutos = 0;
        $diasTrabajados = 0;

        foreach ($todosRegistros as $registro) {
            $horas = $registro->calcularHorasTrabajadas();
            if ($horas) {
                $totalMinutos += $horas['total_minutos'];
                $diasTrabajados++;
            }
        }

        $estadisticas = [
            'total_horas' => sprintf('%dh %02dmin', floor($totalMinutos / 60), $totalMinutos % 60),
            'dias_trabajados' => $diasTrabajados,
            'promedio_diario' => $diasTrabajados > 0 ? sprintf('%dh %02dmin', floor(($totalMinutos / $diasTrabajados) / 60), ($totalMinutos / $diasTrabajados) % 60) : '0h 00min'
        ];

        return view('asistencia.por-empleado', compact('empleado', 'registros', 'estadisticas', 'fechaInicio', 'fechaFin'));
    }

    /**
     * Obtener estado actual del empleado (para AJAX)
     */
    public function estadoActual(){
        $user = Auth::user();
        
        if (!$user->empleado) {
            return response()->json(['error' => 'No employee profile'], 404);
        }

        $registro = RegistroEntradaSalida::registroDelDia($user->empleado->id);

        if (!$registro) {
            return response()->json([
                'estado' => 'sin_fichar',
                'mensaje' => 'No has fichado hoy'
            ]);
        }

        if ($registro->estaEnJornada()) {
            $horasActuales = $registro->calcularHorasActuales();
            return response()->json([
                'estado' => 'trabajando',
                'hora_entrada' => $registro->hora_entrada,
                'horas_trabajadas' => $horasActuales['formatted'],
                'mensaje' => 'Trabajando desde las ' . Carbon::parse($registro->hora_entrada)->format('H:i')
            ]);
        }

        $horasTrabajadas = $registro->calcularHorasTrabajadas();
        return response()->json([
            'estado' => 'jornada_completa',
            'hora_entrada' => $registro->hora_entrada,
            'hora_salida' => $registro->hora_salida,
            'horas_trabajadas' => $horasTrabajadas['formatted'],
            'mensaje' => 'Jornada completada'
        ]);
    }
}

