<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empleado;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Cita;
use App\Models\HorarioTrabajo;
use App\Services\NotificacionEmailService;
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Http\Requests\StoreCitaRequest;
use App\Http\Requests\UpdateCitaRequest;
use App\Http\Resources\CitaResource;
use App\Traits\HasFlashMessages;
use App\Traits\HasCrudMessages;
use App\Traits\HasJsonResponses;

class CitaController extends Controller{
    use HasFlashMessages, HasCrudMessages, HasJsonResponses;

    protected function getResourceName(): string
    {
        return 'cita';
    }

    private function horaInicioAlineada(Carbon $fechaHora): bool
    {
        return $fechaHora->minute % HorarioTrabajo::DURACION_BLOQUE_MINUTOS === 0
            && $fechaHora->second === 0;
    }

    private function errorEmpleadoServicios(Empleado $empleado, Collection $servicios): ?string
    {
        $serviciosPermitidosIds = $empleado->servicios->pluck('id')->toArray();

        foreach ($servicios as $servicio) {
            if (in_array($servicio->id, $serviciosPermitidosIds)) {
                continue;
            }

            if ($servicio->categoria !== $empleado->categoria) {
                return "El empleado seleccionado ({$empleado->user->nombre}) no puede realizar el servicio '{$servicio->nombre}'.";
            }
        }

        return null;
    }

    private function citaSolapada(
        int $empleadoId,
        Carbon $inicio,
        Carbon $fin,
        array $idsExcluidos = []
    ): ?Cita {
        $citas = Cita::with('servicios')
            ->where('id_empleado', $empleadoId)
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->whereDate('fecha_hora', $inicio->format('Y-m-d'))
            ->when(!empty($idsExcluidos), fn ($query) => $query->whereNotIn('id', $idsExcluidos))
            ->get();

        foreach ($citas as $citaExistente) {
            $inicioExistente = Carbon::parse($citaExistente->fecha_hora);
            $finExistente = $inicioExistente->copy()->addMinutes($citaExistente->duracion_minutos);

            if ($inicio->lessThan($finExistente) && $fin->greaterThan($inicioExistente)) {
                return $citaExistente;
            }
        }

        return null;
    }

    private function horariosArrayParaAgenda(Carbon $fecha): array
    {
        $horariosDelDia = HorarioTrabajo::with('empleado')
            ->whereDate('fecha', $fecha->format('Y-m-d'))
            ->whereHas('empleado')
            ->get(['id_empleado', 'hora', 'hora_inicio', 'hora_fin']);

        $rangos = [];
        foreach ($horariosDelDia->groupBy('id_empleado') as $horariosEmpleado) {
            $horarioGeneral = $horariosEmpleado->first(
                fn ($horario) => $horario->hora_inicio && $horario->hora_fin
            );

            if ($horarioGeneral) {
                $inicio = Carbon::parse($horarioGeneral->hora_inicio);
                $fin = Carbon::parse($horarioGeneral->hora_fin);
            } else {
                // En horarios generados automáticamente, la configuración
                // del empleado es la fuente fiable aunque existan filas
                // antiguas con minutos corruptos.
                $empleado = $horariosEmpleado->first()->empleado;
                $horarioConfigurado = $empleado?->obtenerHorario($fecha);

                if ($horarioConfigurado) {
                    $inicio = Carbon::parse($horarioConfigurado['inicio']);
                    $fin = Carbon::parse($horarioConfigurado['fin']);
                } else {
                    $horas = $horariosEmpleado->pluck('hora')->filter();
                    if ($horas->isEmpty()) {
                        continue;
                    }

                    $minutos = $horas->map(function ($hora) {
                        $carbon = Carbon::parse($hora);

                        return ($carbon->hour * 60) + $carbon->minute;
                    });

                    $minutoInicio = intdiv(
                        (int) $minutos->min(),
                        HorarioTrabajo::DURACION_BLOQUE_MINUTOS
                    ) * HorarioTrabajo::DURACION_BLOQUE_MINUTOS;
                    $minutoFin = intdiv(
                        (int) $minutos->max(),
                        HorarioTrabajo::DURACION_BLOQUE_MINUTOS
                    ) * HorarioTrabajo::DURACION_BLOQUE_MINUTOS;

                    $inicio = Carbon::parse($this->horaDesdeMinutos($minutoInicio));
                    $fin = Carbon::parse($this->horaDesdeMinutos($minutoFin));
                }
            }

            $rangos[] = [
                ($inicio->hour * 60) + $inicio->minute,
                ($fin->hour * 60) + $fin->minute,
            ];
        }

        if (!empty($rangos)) {
            $minutoInicio = min(array_column($rangos, 0));
            $minutoFin = max(array_column($rangos, 1));

            return HorarioTrabajo::generarBloquesHorarios(
                $this->horaDesdeMinutos($minutoInicio),
                $this->horaDesdeMinutos($minutoFin)
            );
        }

        $horarioDia = HorarioTrabajo::obtenerHorarioPorFecha($fecha);

        if (!$horarioDia) {
            return [];
        }

        return HorarioTrabajo::generarBloquesHorarios(
            $horarioDia['inicio'],
            $horarioDia['fin']
        );
    }

    private function horaDesdeMinutos(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }

    private function bloquesDisponiblesParaRango(
        int $empleadoId,
        Carbon $fechaHoraInicio,
        int $duracionMinutos,
        ?Cita $citaIgnorada = null
    ): bool
    {
        $bloques = $this->bloquesDelEmpleadoEnFecha($empleadoId, $fechaHoraInicio);

        if ($bloques->isEmpty()) {
            return false;
        }

        $fechaHoraFin = $fechaHoraInicio->copy()->addMinutes($duracionMinutos);
        $estadoBloques = [];

        foreach ($bloques as $bloque) {
            $horaNormalizada = HorarioTrabajo::normalizarHoraBloque($bloque->hora);

            if (!array_key_exists($horaNormalizada, $estadoBloques)) {
                $estadoBloques[$horaNormalizada] = (bool) $bloque->disponible;
            } else {
                // Si hay datos antiguos duplicados al normalizar, un bloque
                // ocupado debe seguir considerándose ocupado.
                $estadoBloques[$horaNormalizada] = $estadoBloques[$horaNormalizada]
                    && (bool) $bloque->disponible;
            }
        }

        if (empty($estadoBloques)) {
            return false;
        }

        $ultimaHoraBloque = max(array_keys($estadoBloques));
        $horaFinJornada = Carbon::parse(
            $fechaHoraInicio->format('Y-m-d') . ' ' . $ultimaHoraBloque
        );

        if ($fechaHoraFin->greaterThan($horaFinJornada)) {
            return false;
        }

        $bloquesDisponibles = collect($estadoBloques)
            ->filter(fn (bool $disponible) => $disponible);

        $horaActual = HorarioTrabajo::inicioBloque($fechaHoraInicio);
        $inicioCitaIgnorada = $citaIgnorada ? Carbon::parse($citaIgnorada->fecha_hora) : null;
        $finCitaIgnorada = $inicioCitaIgnorada
            ? $inicioCitaIgnorada->copy()->addMinutes($citaIgnorada->duracion_minutos)
            : null;

        while ($horaActual->lessThan($fechaHoraFin)) {
            $finBloque = $horaActual->copy()->addMinutes(HorarioTrabajo::DURACION_BLOQUE_MINUTOS);
            $horaNormalizada = $horaActual->format('H:i:s');
            $bloqueDisponible = $bloquesDisponibles->has($horaNormalizada);
            $bloquePerteneceACitaIgnorada = $citaIgnorada !== null
                && (int) $citaIgnorada->id_empleado === $empleadoId
                && $horaActual->toDateString() === $inicioCitaIgnorada->toDateString()
                && $horaActual->lessThan($finCitaIgnorada)
                && $finBloque->greaterThan($inicioCitaIgnorada);

            if (!$bloqueDisponible && !$bloquePerteneceACitaIgnorada) {
                return false;
            }

            $horaActual->addMinutes(HorarioTrabajo::DURACION_BLOQUE_MINUTOS);
        }

        return true;
    }

    private function actualizarDisponibilidadCita(
        int $empleadoId,
        Carbon $fechaHoraInicio,
        int $duracionMinutos,
        bool $disponible,
        ?int $citaIgnoradaId = null
    ): void {
        $inicioBloque = HorarioTrabajo::inicioBloque($fechaHoraInicio);
        $finCita = $fechaHoraInicio->copy()->addMinutes($duracionMinutos);
        $horasAfectadas = [];
        $horaActual = $inicioBloque->copy();

        while ($horaActual->lessThan($finCita)) {
            $horasAfectadas[$horaActual->format('H:i:s')] = true;
            $horaActual->addMinutes(HorarioTrabajo::DURACION_BLOQUE_MINUTOS);
        }

        if (empty($horasAfectadas)) {
            return;
        }

        $bloques = HorarioTrabajo::where('id_empleado', $empleadoId)
            ->whereDate('fecha', $fechaHoraInicio->format('Y-m-d'))
            ->whereNotNull('hora')
            ->get(['id', 'hora', 'disponible']);

        $citasActivas = collect();
        if ($disponible) {
            $citasActivas = Cita::with('servicios')
                ->where('id_empleado', $empleadoId)
                ->whereIn('estado', ['pendiente', 'confirmada', 'completada'])
                ->whereDate('fecha_hora', $fechaHoraInicio->format('Y-m-d'))
                ->when($citaIgnoradaId !== null, fn ($query) => $query->where('id', '!=', $citaIgnoradaId))
                ->get();
        }

        foreach ($bloques as $bloque) {
            $horaNormalizada = HorarioTrabajo::normalizarHoraBloque($bloque->hora);

            if (!isset($horasAfectadas[$horaNormalizada])) {
                continue;
            }

            $nuevoEstado = $disponible;
            if ($disponible) {
                $inicioBloqueGuardado = Carbon::parse(
                    $fechaHoraInicio->format('Y-m-d') . ' ' . $horaNormalizada
                );
                $finBloqueGuardado = $inicioBloqueGuardado->copy()
                    ->addMinutes(HorarioTrabajo::DURACION_BLOQUE_MINUTOS);

                $nuevoEstado = !$citasActivas->contains(function (Cita $cita) use (
                    $inicioBloqueGuardado,
                    $finBloqueGuardado
                ) {
                    $inicioCita = Carbon::parse($cita->fecha_hora);
                    $finCita = $inicioCita->copy()->addMinutes((int) $cita->duracion_minutos);

                    return $inicioCita->lessThan($finBloqueGuardado)
                        && $finCita->greaterThan($inicioBloqueGuardado);
                });
            }

            if ((bool) $bloque->disponible !== $nuevoEstado) {
                $bloque->update(['disponible' => $nuevoEstado]);
            }
        }
    }

    private function bloquesDelEmpleadoEnFecha(int $empleadoId, Carbon $fecha): Collection
    {
        return HorarioTrabajo::where('id_empleado', $empleadoId)
            ->whereDate('fecha', $fecha->format('Y-m-d'))
            ->whereNotNull('hora')
            ->orderBy('hora')
            ->get(['hora', 'disponible']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request){
        $user = Auth::user();

        // Fecha seleccionada (por defecto hoy)
        $fecha = $request->fecha ? Carbon::parse($request->fecha) : Carbon::today();

        // Obtener todos los empleados para las columnas del calendario
        // Ordenados por categoría (peluquería primero, luego estética) y luego por nombre
        $empleados = Empleado::with('user')
            ->orderByRaw("FIELD(categoria, 'peluqueria', 'estetica')")
            ->orderBy('id')
            ->get();

        $horariosArray = $this->horariosArrayParaAgenda($fecha);

        if ($user->rol === 'cliente') {
            $cliente = $user->cliente;
            if (!$cliente) {
                abort(403, 'No tienes permiso para acceder a esta sección.');
            }
            // Solo las citas del cliente para la fecha seleccionada (excluir canceladas)
            $citas = Cita::with(['cliente.user', 'empleado.user', 'servicios.subcategoria'])
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
            $citas = Cita::with(['cliente.user', 'cliente.bonos' => function($query) {
                    $query->where('estado', 'activo')
                          ->with(['servicios' => function($q) {
                              $q->withPivot('cantidad_total', 'cantidad_usada');
                          }]);
                }, 'empleado.user', 'servicios.subcategoria'])
                ->where('estado', '!=', 'cancelada')
                ->porFecha($fecha)
                ->orderBy('fecha_hora')
                ->get()
                ->groupBy('id_empleado');

        } else if ($user->rol === 'admin' || $user->rol === 'gerente') {
            // El admin/gerente ve todas las citas del día (excluir canceladas)
            $citas = Cita::with(['cliente.user', 'cliente.bonos' => function($query) {
                    $query->where('estado', 'activo')
                          ->with(['servicios' => function($q) {
                              $q->withPivot('cantidad_total', 'cantidad_usada');
                          }]);
                }, 'empleado.user', 'servicios.subcategoria'])
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
            ->whereHas('empleado')
            ->get()
            ->groupBy('id_empleado');

        // Subcategorías activas para la leyenda
        $subcategorias = \App\Models\Subcategoria::where('activo', true)->orderBy('categoria')->orderBy('nombre')->get();

        return view('citas.index', compact('citas', 'empleados', 'fecha', 'horariosArray', 'horariosEmpleados', 'subcategorias'));
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        $user = Auth::user();

        // Usar caché para empleados y servicios
        $empleados = CacheService::getEmpleados();
        $servicios = CacheService::getServiciosActivos();

        // Si es admin, gerente o empleado, necesita poder elegir un cliente
        if (in_array($user->rol, ['admin', 'gerente', 'empleado'])) {
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

    public function store(StoreCitaRequest $request){
        // Los datos ya vienen validados y sanitizados del Form Request
        $data = $request->validated();

        // Establecer estado automáticamente en "pendiente"
        $data['estado'] = 'pendiente';

        // Validar que el empleado pueda realizar los servicios seleccionados
        // Primero verificar si el servicio está en la relación empleado_servicio
        // Si está, el empleado puede realizarlo aunque sea de otra categoría
        // Si no está, verificar que sea de la misma categoría que el empleado
        $empleado = Empleado::with('servicios')->findOrFail($data['id_empleado']);
        $serviciosSeleccionados = Servicio::whereIn('id', $data['servicios'])->get();
        
        // Obtener IDs de servicios que el empleado puede realizar explícitamente
        $serviciosPermitidosIds = $empleado->servicios->pluck('id')->toArray();
        
        foreach ($serviciosSeleccionados as $servicio) {
            // Si el servicio está explícitamente permitido en empleado_servicio, OK
            if (in_array($servicio->id, $serviciosPermitidosIds)) {
                continue;
            }
            
            // Si no está explícitamente permitido, verificar que sea de la misma categoría
            if ($servicio->categoria !== $empleado->categoria) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['servicios' => "El empleado seleccionado ({$empleado->user->nombre}) es de categoría '{$empleado->categoria}' y no puede realizar el servicio '{$servicio->nombre}' que es de categoría '{$servicio->categoria}'."]);
            }
        }

        // Extraer fecha y hora
        $fechaHora = Carbon::parse($data['fecha_hora']);

        // Calcular la duración total de la nueva cita
        $servicios = $data['servicios'];
        $serviciosSeleccionados = Servicio::whereIn('id', $servicios)->get();
        $duracionTotalNuevaCita = $serviciosSeleccionados->sum('tiempo_estimado');
        $finNuevaCita = $fechaHora->copy()->addMinutes($duracionTotalNuevaCita);

        if (!$this->bloquesDisponiblesParaRango($data['id_empleado'], $fechaHora, $duracionTotalNuevaCita)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['id_empleado' => 'El empleado no está disponible durante todo el rango solicitado.']);
        }

        // Verificar solapamiento real con otras citas del empleado
        // Solo verificar citas pendientes, no las completadas ni canceladas
        $citasCercanas = Cita::where('id_empleado', $data['id_empleado'])
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->whereDate('fecha_hora', $fechaHora->format('Y-m-d'))
            ->get();

        foreach ($citasCercanas as $citaExistente) {
            $inicioExistente = Carbon::parse($citaExistente->fecha_hora);
            $finExistente = $inicioExistente->copy()->addMinutes($citaExistente->duracion_minutos);
            
            // Verificar solapamiento: hay conflicto si:
            // La nueva cita empieza ANTES de que termine la existente Y termina DESPUÉS de que empiece la existente
            // Permitir que una cita empiece exactamente cuando termina otra (10:00 después de 09:00-10:00 está OK)
            
            $haySolapamiento = $fechaHora->lessThan($finExistente) && $finNuevaCita->greaterThan($inicioExistente);
            
            if ($haySolapamiento) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['fecha_hora' => 'Este horario se solapa con otra cita que va de ' . $inicioExistente->format('H:i') . ' a ' . $finExistente->format('H:i') . '. Por favor, seleccione otro horario.']);
            }
        }

        // Guardar la cita o citas
        unset($data['servicios']);
        
        // Obtener servicios seleccionados con sus categorías y duraciones
        $serviciosSeleccionados = Servicio::whereIn('id', $servicios)->get();
        
        // Reordenar servicios según el orden de selección del usuario
        // whereIn() no respeta el orden del array, así que lo ordenamos manualmente
        $serviciosSeleccionados = $serviciosSeleccionados->sortBy(function($servicio) use ($servicios) {
            return array_search($servicio->id, $servicios);
        })->values();
        
        $hayMultiplesServicios = $serviciosSeleccionados->count() > 1;

        $cita = DB::transaction(function () use (
            $data,
            $servicios,
            $serviciosSeleccionados,
            $hayMultiplesServicios,
            $fechaHora,
            $duracionTotalNuevaCita
        ) {
            // Si hay múltiples servicios, se crean citas consecutivas dentro
            // de una única transacción para no dejar grupos incompletos.
            if ($hayMultiplesServicios) {
                $horaActual = Carbon::parse($data['fecha_hora']);
                $citaPrincipal = null;
                $grupoCitaId = null;
                $orden = 1;

                foreach ($serviciosSeleccionados as $servicio) {
                    $citaIndividual = Cita::create([
                        'fecha_hora' => $horaActual->format('Y-m-d H:i:s'),
                        'estado' => $data['estado'],
                        'notas_adicionales' => $data['notas_adicionales'],
                        'id_cliente' => $data['id_cliente'],
                        'id_empleado' => $data['id_empleado'],
                        'grupo_cita_id' => $grupoCitaId,
                        'orden_servicio' => $orden,
                    ]);

                    if ($citaPrincipal === null) {
                        // El ID de la primera cita es único y evita colisiones
                        // que podían producirse usando now()->timestamp.
                        $citaPrincipal = $citaIndividual;
                        $grupoCitaId = $citaIndividual->id;
                        $citaIndividual->update(['grupo_cita_id' => $grupoCitaId]);
                    }

                    $citaIndividual->servicios()->attach($servicio->id);
                    $horaActual->addMinutes($servicio->duracion_minutos);
                    $orden++;
                }

                $this->actualizarDisponibilidadCita(
                    (int) $data['id_empleado'],
                    $fechaHora,
                    (int) $duracionTotalNuevaCita,
                    false
                );

                return $citaPrincipal;
            }

            $cita = Cita::create($data);
            $cita->servicios()->attach($servicios);

            $this->actualizarDisponibilidadCita(
                (int) $data['id_empleado'],
                $fechaHora,
                (int) $duracionTotalNuevaCita,
                false
            );

            return $cita;
        });

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

        return $this->redirectWithSuccess(
            'citas.index',
            'Cita creada correctamente con múltiples servicios.',
            ['fecha' => $fechaCita]
        );
    }




    /**
     * Display the specified resource.
     */
    public function show(Cita $cita){
        // Refrescar el modelo desde la base de datos
        $cita->refresh();
        
        // Cargar las relaciones necesarias
        $cita->load(['cliente.user', 'cliente.bonos' => function($query) {
            $query->where('estado', 'activo')
                  ->with(['servicios' => function($q) {
                      $q->withPivot('cantidad_total', 'cantidad_usada');
                  }]);
        }, 'empleado.user', 'servicios']);
        
        \Log::info('Mostrando cita', [
            'cita_id' => $cita->id,
            'notas_en_show' => $cita->notas_adicionales,
        ]);
        
        return view('citas.show', compact('cita'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cita $cita){
        $clientes = Cliente::with('user')->get();
        // Usar caché para empleados y servicios
        $empleados = CacheService::getEmpleados();
        $servicios = CacheService::getServiciosActivos();
        return view('citas.edit', compact('cita','clientes','empleados','servicios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCitaRequest $request, Cita $cita){
        $data = $request->validated();

        $cita->load('servicios');
        $serviciosIds = collect($data['servicios'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        $serviciosSeleccionados = Servicio::whereIn('id', $serviciosIds)->get();

        if ($serviciosSeleccionados->count() !== count($serviciosIds)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['servicios' => 'Uno o más servicios seleccionados no están disponibles.']);
        }

        $empleado = Empleado::with(['servicios', 'user'])->findOrFail($data['id_empleado']);
        $errorServicios = $this->errorEmpleadoServicios($empleado, $serviciosSeleccionados);

        if ($errorServicios !== null) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['servicios' => $errorServicios]);
        }

        $fechaHoraNueva = Carbon::parse($data['fecha_hora']);
        $fechaHoraAnterior = Carbon::parse($cita->fecha_hora);
        $idsServiciosAnteriores = $cita->servicios
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
        $idsServiciosNuevos = collect($serviciosIds)->sort()->values()->all();
        $serviciosCambiados = $idsServiciosAnteriores !== $idsServiciosNuevos;
        $horarioCambiado = $fechaHoraNueva->format('Y-m-d H:i:s')
            !== $fechaHoraAnterior->format('Y-m-d H:i:s')
            || (int) $data['id_empleado'] !== (int) $cita->id_empleado
            || $serviciosCambiados;
        $duracionAnterior = (int) $cita->duracion_minutos;
        $duracionNueva = $serviciosCambiados
            ? (int) $serviciosSeleccionados->sum('tiempo_estimado')
            : $duracionAnterior;

        if (
            ($cita->estado === 'cancelada' && $data['estado'] !== 'cancelada')
            || ($cita->estado !== 'cancelada' && $data['estado'] === 'cancelada')
        ) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'estado' => 'Los cambios a cancelada o desde cancelada deben realizarse mediante la acción específica de cancelar.',
                ]);
        }

        if ($horarioCambiado && $data['estado'] === 'completada') {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'estado' => 'Primero cambia el horario de la cita y después márcala como completada.',
                ]);
        }

        if ($horarioCambiado && in_array($cita->estado, ['cancelada', 'completada'], true)) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'fecha_hora' => 'No se puede cambiar el horario de una cita cancelada o completada.',
                ]);
        }

        if ($horarioCambiado) {
            $mismaHoraDeLaCita = $fechaHoraNueva->format('Y-m-d H:i:s')
                === $fechaHoraAnterior->format('Y-m-d H:i:s');

            if (
                !$this->horaInicioAlineada($fechaHoraNueva)
                && (!$mismaHoraDeLaCita || (int) $data['id_empleado'] !== (int) $cita->id_empleado)
            ) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        'fecha_hora' => 'La hora de inicio debe coincidir con un bloque de 15 minutos.',
                    ]);
            }

            if ($fechaHoraNueva->lt(Carbon::now()->startOfMinute())) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        'fecha_hora' => 'No se puede mover una cita activa al pasado.',
                    ]);
            }

            $finNueva = $fechaHoraNueva->copy()->addMinutes($duracionNueva);

            if (!$this->bloquesDisponiblesParaRango(
                (int) $data['id_empleado'],
                $fechaHoraNueva,
                $duracionNueva,
                $cita
            )) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        'fecha_hora' => 'El empleado no está disponible durante todo el rango solicitado.',
                    ]);
            }

            $citaSolapada = $this->citaSolapada(
                (int) $data['id_empleado'],
                $fechaHoraNueva,
                $finNueva,
                [$cita->id]
            );

            if ($citaSolapada !== null) {
                $inicioSolapada = Carbon::parse($citaSolapada->fecha_hora);
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        'fecha_hora' => 'El nuevo horario se solapa con otra cita de '
                            . $inicioSolapada->format('H:i') . ' a '
                            . $citaSolapada->hora_fin->format('H:i') . '.',
                    ]);
            }
        }

        if ($serviciosCambiados && $cita->duracion_real !== null) {
            // La duración real solo representa una ejecución ya registrada.
            // Al cambiar los servicios de una cita activa debe recalcularse.
            $data['duracion_real'] = null;
        }

        unset($data['servicios']);

        DB::transaction(function () use (
            $cita,
            $data,
            $serviciosIds,
            $horarioCambiado,
            $fechaHoraAnterior,
            $duracionAnterior,
            $fechaHoraNueva,
            $duracionNueva
        ) {
            if ($horarioCambiado) {
                $this->actualizarDisponibilidadCita(
                    (int) $cita->id_empleado,
                    $fechaHoraAnterior,
                    $duracionAnterior,
                    true,
                    (int) $cita->id
                );
            }

            $cita->update($data);

            // Sincronizar servicios (many-to-many)
            $cita->servicios()->sync($serviciosIds);

            if ($horarioCambiado) {
                $this->actualizarDisponibilidadCita(
                    (int) $data['id_empleado'],
                    $fechaHoraNueva,
                    $duracionNueva,
                    false
                );
            }
        });
        
        return redirect()->route('citas.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cita $cita){
        // Liberar las horas ocupadas por esta cita antes de eliminarla
        $fechaHora = Carbon::parse($cita->fecha_hora);
        $empleadoId = $cita->id_empleado;
        
        // Usar duracion_minutos (respeta duracion_real si existe) para liberar TODOS los bloques
        $duracionTotal = (int) $cita->duracion_minutos;

        DB::transaction(function () use ($cita, $fechaHora, $empleadoId, $duracionTotal) {
            $this->actualizarDisponibilidadCita(
                $empleadoId,
                $fechaHora,
                $duracionTotal,
                true,
                (int) $cita->id
            );
            $cita->delete();
        });
        
        return $this->redirectWithSuccess('citas.index', 'La cita ha sido eliminada permanentemente.');
    }

    /**
     * Eliminar todas las citas de un cliente en un día específico
     */
    public function destroyClienteDia(Cliente $cliente, string $fecha)
    {
        $fechaCarbon = Carbon::parse($fecha);

        $citas = Cita::where('id_cliente', $cliente->id)
            ->where('estado', '!=', 'cancelada')
            ->whereDate('fecha_hora', $fechaCarbon)
            ->get();

        if ($citas->isEmpty()) {
            return $this->redirectWithSuccess('citas.index', 'No se encontraron citas para eliminar.');
        }

        $totalEliminadas = 0;

        DB::transaction(function () use ($citas, &$totalEliminadas) {
            foreach ($citas as $cita) {
                $this->actualizarDisponibilidadCita(
                    (int) $cita->id_empleado,
                    Carbon::parse($cita->fecha_hora),
                    (int) $cita->duracion_minutos,
                    true,
                    (int) $cita->id
                );

                $cita->delete();
                $totalEliminadas++;
            }
        });

        $nombreCliente = $cliente->user ? $cliente->user->nombre : 'Cliente';

        return redirect()
            ->route('citas.index', ['fecha' => $fechaCarbon->format('Y-m-d')])
            ->with('success', "Se han eliminado {$totalEliminadas} citas de {$nombreCliente} del día {$fechaCarbon->format('d/m/Y')}.");
    }

    /**
     * Cancelar una cita
     */
    public function cancelar(Cita $cita){
        if ($cita->estado === 'cancelada') {
            return $this->backWithError('La cita ya está cancelada.');
        }

        if ($cita->estado === 'completada') {
            return $this->backWithError('No se puede cancelar una cita completada.');
        }

        // Guardar datos de la cita ANTES de cambiar estado (para liberar slots correctamente)
        $fechaHora = Carbon::parse($cita->fecha_hora);
        $empleadoId = $cita->id_empleado;
        
        // Usar duracion_minutos (respeta duracion_real si existe) para liberar TODOS los bloques
        $duracionTotal = (int) $cita->duracion_minutos;
        $teniaCobro = $cita->cobro !== null;

        DB::transaction(function () use ($cita, $fechaHora, $empleadoId, $duracionTotal) {
            if ($cita->cobro) {
                $cita->cobro->delete();
            }

            $cita->update(['estado' => 'cancelada']);
            $this->actualizarDisponibilidadCita(
                $empleadoId,
                $fechaHora,
                $duracionTotal,
                true,
                (int) $cita->id
            );
        });

        $mensaje = 'La cita ha sido cancelada y las horas han sido liberadas.';
        if ($teniaCobro) {
            $mensaje .= ' El cobro asociado ha sido eliminado.';
        }

        return $this->redirectWithSuccess('citas.index', $mensaje);
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
        $nuevoEmpleadoId = (int) $request->nuevo_empleado_id;

        if (in_array($cita->estado, ['cancelada', 'completada'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede mover una cita cancelada o completada.'
            ], 422);
        }

        $fechaHoraActual = Carbon::parse($cita->fecha_hora);
        if (
            !$this->horaInicioAlineada($nuevaFechaHora)
            && (
                $nuevaFechaHora->format('Y-m-d H:i:s') !== $fechaHoraActual->format('Y-m-d H:i:s')
                || $nuevoEmpleadoId !== (int) $cita->id_empleado
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'La hora de destino debe coincidir con un bloque de 15 minutos.'
            ], 422);
        }

        if ($nuevaFechaHora->lt(Carbon::now()->startOfMinute())) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede mover una cita activa al pasado.'
            ], 422);
        }

        $nuevoEmpleado = Empleado::with(['servicios', 'user'])->findOrFail($nuevoEmpleadoId);
        $errorServicios = $this->errorEmpleadoServicios(
            $nuevoEmpleado,
            $cita->load('servicios')->servicios
        );

        if ($errorServicios !== null) {
            return response()->json([
                'success' => false,
                'message' => $errorServicios
            ], 422);
        }

        $duracionMinutos = $cita->duracion_minutos;

        if (!$this->bloquesDisponiblesParaRango(
            $nuevoEmpleadoId,
            $nuevaFechaHora,
            $duracionMinutos,
            $cita
        )) {
            return response()->json([
                'success' => false,
                'message' => 'No hay suficiente espacio disponible en este horario. La cita necesita ' . $duracionMinutos . ' minutos.'
            ], 400);
        }

        // Validar superposición con otras citas del mismo empleado
        $horaFin = $nuevaFechaHora->copy()->addMinutes($duracionMinutos);
        
        // Obtener todas las citas del empleado en el día para validar manualmente
        $citasDelDia = Cita::with('servicios')
            ->where('id_empleado', $nuevoEmpleadoId)
            ->where('id', '!=', $cita->id)
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->whereDate('fecha_hora', $nuevaFechaHora->format('Y-m-d'))
            ->get();
        
        $citaSuperpuesta = false;
        foreach ($citasDelDia as $otraCita) {
            $otraInicio = Carbon::parse($otraCita->fecha_hora);
            $otraDuracion = $otraCita->duracion_minutos;
            $otraFin = $otraInicio->copy()->addMinutes($otraDuracion);
            
            // Verificar si hay superposición
            if (($nuevaFechaHora >= $otraInicio && $nuevaFechaHora < $otraFin) ||
                ($horaFin > $otraInicio && $horaFin <= $otraFin) ||
                ($nuevaFechaHora <= $otraInicio && $horaFin >= $otraFin)) {
                $citaSuperpuesta = true;
                break;
            }
        }

        if ($citaSuperpuesta) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una cita que se superpone en este horario.'
            ], 400);
        }
        
        $fechaHoraAntigua = Carbon::parse($cita->fecha_hora);
        $empleadoIdAntiguo = $cita->id_empleado;
        DB::transaction(function () use (
            $cita,
            $fechaHoraAntigua,
            $empleadoIdAntiguo,
            $duracionMinutos,
            $nuevaFechaHora,
            $nuevoEmpleadoId
        ) {
            $this->actualizarDisponibilidadCita(
                (int) $empleadoIdAntiguo,
                $fechaHoraAntigua,
                $duracionMinutos,
                true,
                (int) $cita->id
            );
            $this->actualizarDisponibilidadCita(
                $nuevoEmpleadoId,
                $nuevaFechaHora,
                $duracionMinutos,
                false
            );

            $cita->fecha_hora = $nuevaFechaHora;
            $cita->id_empleado = $nuevoEmpleadoId;
            $cita->save();
        });

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

        if (!in_array($cita->estado, ['pendiente', 'confirmada'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede completar una cita pendiente o confirmada.'
            ], 422);
        }

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
     * NOTA: La cita NO se marca como completada aquí, se marcará cuando se registre el cobro
     * Busca TODAS las citas pendientes del mismo cliente del mismo día para cobro agrupado
     */
    public function completarYCobrar($id){
        $cita = Cita::findOrFail($id);

        if (!in_array($cita->estado, ['pendiente', 'confirmada'], true)) {
            return $this->backWithError('Solo se puede cobrar una cita pendiente o confirmada.');
        }
        
        // Buscar TODAS las citas pendientes del mismo cliente del mismo día
        $fechaCita = Carbon::parse($cita->fecha_hora)->startOfDay();
        $citasDelDia = Cita::where('id_cliente', $cita->id_cliente)
            ->whereDate('fecha_hora', $fechaCita)
            ->where('estado', 'pendiente')
            ->with(['servicios', 'cliente.user', 'empleado.user'])
            ->get();

        // Si hay múltiples citas, cobrarlas todas juntas
        if ($citasDelDia->count() > 1) {
            return redirect()->route('cobros.create.direct', [
                'citas_ids' => $citasDelDia->pluck('id')->toArray()
            ]);
        }
        
        // Si es solo una, flujo normal
        return redirect()->route('cobros.create.direct', ['id_cita' => $cita->id]);
    }

    /**
     * Actualizar duración de cita - AJAX
     */
    public function actualizarDuracion(Request $request){
        $request->validate([
            'cita_id' => 'required|exists:citas,id',
            // La duración de los servicios puede ser de 5, 10, 20, 35 min,
            // etc. Los bloques de disponibilidad son de 15 min, pero la
            // duración real de una cita no tiene que ser múltiplo de 15.
            'duracion_minutos' => 'required|integer|min:15|max:480', // Entre 15 min y 8 horas
        ]);

        $cita = Cita::with(['servicios'])->findOrFail($request->cita_id);

        if (!in_array($cita->estado, ['pendiente', 'confirmada'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede ajustar la duración de una cita pendiente o confirmada.'
            ], 422);
        }
        
        // Validar que la nueva duración no cause superposición
        $horaInicio = Carbon::parse($cita->fecha_hora);
        $nuevaHoraFin = $horaInicio->copy()->addMinutes($request->duracion_minutos);
        
        // Obtener todas las citas del mismo empleado en el mismo día
        $citasDelDia = Cita::with(['servicios'])
            ->where('id_empleado', $cita->id_empleado)
            ->where('id', '!=', $cita->id)
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->whereDate('fecha_hora', $horaInicio->toDateString())
            ->get();
        
        // Verificar superposición con cada cita
        foreach ($citasDelDia as $otraCita) {
            $otraInicio = Carbon::parse($otraCita->fecha_hora);
            $otraFin = $otraInicio->copy()->addMinutes($otraCita->duracion_minutos);
            
            // Hay superposición si:
            // - La nueva cita empieza antes de que termine la otra Y
            // - La nueva cita termina después de que empiece la otra
            if ($horaInicio < $otraFin && $nuevaHoraFin > $otraInicio) {
                return response()->json([
                    'success' => false,
                    'message' => 'La nueva duración causaría superposición con otra cita de ' . 
                                $otraInicio->format('H:i') . ' a ' . $otraFin->format('H:i')
                ], 400);
            }
        }

        // Calcular duración anterior y nueva para gestionar bloques horarios.
        $duracionAnterior = (int) $cita->duracion_minutos;
        $duracionNueva = (int) $request->duracion_minutos;

        // Validar el rango completo ignorando la propia cita. Esto también
        // funciona cuando la cita empieza fuera del eje de 15 minutos (10:20).
        if (!$this->bloquesDisponiblesParaRango(
            (int) $cita->id_empleado,
            $horaInicio,
            $duracionNueva,
            $cita
        )) {
            return response()->json([
                'success' => false,
                'message' => 'No hay suficiente espacio disponible para ajustar la cita hasta ' . $nuevaHoraFin->format('H:i')
            ], 400);
        }

        DB::transaction(function () use ($cita, $horaInicio, $duracionAnterior, $duracionNueva) {
            // Recalcular el rango completo evita errores en los extremos de
            // citas no alineadas: un bloque puede seguir solapándose después
            // de reducir la duración.
            $this->actualizarDisponibilidadCita(
                (int) $cita->id_empleado,
                $horaInicio,
                $duracionAnterior,
                true,
                (int) $cita->id
            );
            $cita->duracion_real = $duracionNueva;
            $cita->save();
            $this->actualizarDisponibilidadCita(
                (int) $cita->id_empleado,
                $horaInicio,
                $duracionNueva,
                false
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Duración actualizada correctamente.',
            'cita' => $cita->load(['cliente.user', 'empleado.user', 'servicios']),
            'nueva_duracion' => $cita->duracion_minutos
        ]);
    }

    /**
     * Actualizar notas del cliente desde la vista de la cita
     */
    public function actualizarNotas(Request $request, Cita $cita){
        $request->validate([
            // Sin límite práctico en validación; el límite real lo marca la BD (LONGTEXT)
            'notas_adicionales' => 'nullable|string',
        ]);

        if (!$cita->cliente) {
            return $this->backWithError('Esta cita no tiene un cliente asociado.');
        }

        // Solo añadir si hay contenido nuevo
        if (!empty($request->notas_adicionales)) {
            $notasAnteriores = $cita->cliente->notas_adicionales ?? '';
            
            // Añadir separador si ya hay notas previas
            if (!empty($notasAnteriores)) {
                $nuevasNotas = $notasAnteriores . "\n---\n" . $request->notas_adicionales;
            } else {
                $nuevasNotas = $request->notas_adicionales;
            }
            
            \Log::info('Añadiendo notas al cliente desde cita', [
                'cita_id' => $cita->id,
                'cliente_id' => $cita->cliente->id,
                'notas_anteriores_length' => strlen($notasAnteriores),
                'notas_nuevas' => $request->notas_adicionales,
            ]);

            $cita->cliente->notas_adicionales = $nuevasNotas;
            $saved = $cita->cliente->save();

            \Log::info('Notas del cliente guardadas', [
                'cliente_id' => $cita->cliente->id,
                'saved' => $saved,
                'notas_finales_length' => strlen($nuevasNotas),
            ]);

            return redirect()->route('citas.show', $cita->id)
                ->with('success', 'Notas añadidas al cliente correctamente.');
        }

        return redirect()->route('citas.show', $cita->id)
            ->with('info', 'No se añadieron notas porque el campo estaba vacío.');
    }
}
