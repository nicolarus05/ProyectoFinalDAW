<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Calendario de Citas</title>
    {!! vite_asset(['resources/css/app.css', 'resources/css/calendar.css', 'resources/js/calendar.js', 'resources/js/app.js']) !!}
</head>
<body>
    <div id="calendar-app" 
         data-mover-url="{{ route('citas.mover') }}"
         data-completar-url="{{ route('citas.marcarCompletada') }}"
         data-create-url="{{ route('citas.create') }}">
    <div class="calendario-container">
        <!-- Header -->
        <div class="calendario-header">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1 style="margin: 0;">
                    <span>📅</span>
                    Calendario de Citas
                </h1>
                <a href="{{ route('dashboard') }}" class="btn-dashboard">
                    ← Volver a Inicio
                </a>
            </div>
            
            <div class="header-actions">
                <div class="navegacion-fechas">
                    <a href="{{ route('citas.index', ['fecha' => \Carbon\Carbon::parse($fecha)->subDay()->format('Y-m-d')]) }}">
                        <button>◀ Anterior</button>
                    </a>
                    
                    <span class="fecha-actual">
                        {{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                    </span>
                    
                    <a href="{{ route('citas.index', ['fecha' => \Carbon\Carbon::now()->format('Y-m-d')]) }}">
                        <button>Hoy</button>
                    </a>
                    
                    <a href="{{ route('citas.index', ['fecha' => \Carbon\Carbon::parse($fecha)->addDay()->format('Y-m-d')]) }}">
                        <button>Siguiente ▶</button>
                    </a>
                </div>
                
                <a href="{{ route('citas.create') }}">
                    <button class="btn-nueva-cita">+ Nueva Cita</button>
                </a>
            </div>
            
            <div class="leyenda">
                <div class="leyenda-item">
                    <div class="leyenda-color" style="background-color: #93C572;"></div>
                    <span>Pendiente</span>
                </div>
                <div class="leyenda-item">
                    <div class="leyenda-color" style="background-color: #4B5563;"></div>
                    <span>Completada</span>
                </div>
                <div class="leyenda-item">
                    <div class="leyenda-color" style="background: repeating-linear-gradient(45deg, #FEE2E2, #FEE2E2 5px, #FCA5A5 5px, #FCA5A5 10px);"></div>
                    <span>⛔ Deshabilitada</span>
                </div>
                <div class="leyenda-item">
                    <div class="leyenda-color" style="background-color: #f3f4f6;"></div>
                    <span>Fuera de horario</span>
                </div>
            </div>

            <!-- Mini Calendario -->
            @php
                $fechaActual = \Carbon\Carbon::parse($fecha);
                $primerDiaMes = $fechaActual->copy()->startOfMonth();
                $ultimoDiaMes = $fechaActual->copy()->endOfMonth();
                $primerDiaGrid = $primerDiaMes->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
                $ultimoDiaGrid = $ultimoDiaMes->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
                $mesAnterior = $fechaActual->copy()->subMonth();
                $mesSiguiente = $fechaActual->copy()->addMonth();
            @endphp

            <div class="mini-calendario-container">
                <div class="mini-calendario-header">
                    <h3>{{ $fechaActual->locale('es')->isoFormat('MMMM YYYY') }}</h3>
                    <div class="mini-calendario-nav">
                        <a href="{{ route('citas.index', ['fecha' => $mesAnterior->startOfMonth()->format('Y-m-d')]) }}">
                            <button>◀</button>
                        </a>
                        <a href="{{ route('citas.index', ['fecha' => \Carbon\Carbon::now()->format('Y-m-d')]) }}">
                            <button>•</button>
                        </a>
                        <a href="{{ route('citas.index', ['fecha' => $mesSiguiente->startOfMonth()->format('Y-m-d')]) }}">
                            <button>▶</button>
                        </a>
                    </div>
                </div>

                <div class="mini-calendario-grid">
                    <div class="mini-calendario-dia-nombre">L</div>
                    <div class="mini-calendario-dia-nombre">M</div>
                    <div class="mini-calendario-dia-nombre">X</div>
                    <div class="mini-calendario-dia-nombre">J</div>
                    <div class="mini-calendario-dia-nombre">V</div>
                    <div class="mini-calendario-dia-nombre">S</div>
                    <div class="mini-calendario-dia-nombre">D</div>

                    @php
                        $diaIterador = $primerDiaGrid->copy();
                        $hoy = \Carbon\Carbon::now()->startOfDay();
                    @endphp

                    @while($diaIterador <= $ultimoDiaGrid)
                        @php
                            $esOtroMes = $diaIterador->month !== $fechaActual->month;
                            $esHoy = $diaIterador->isSameDay($hoy);
                            $esSeleccionado = $diaIterador->isSameDay($fechaActual);
                        @endphp
                        
                        <a href="{{ route('citas.index', ['fecha' => $diaIterador->format('Y-m-d')]) }}" 
                           class="mini-calendario-dia {{ $esOtroMes ? 'otro-mes' : '' }} {{ $esHoy ? 'hoy' : '' }} {{ $esSeleccionado ? 'seleccionado' : '' }}">
                            {{ $diaIterador->day }}
                        </a>
                        
                        @php
                            $diaIterador->addDay();
                        @endphp
                    @endwhile
                </div>
            </div>
        </div>

        <!-- Grid del Calendario -->
        <div class="calendario-grid-container">
            @php
                $numEmpleados = count($empleados);
            @endphp
            
            <div class="calendario-grid" style="--num-empleados: {{ $numEmpleados }};">
                <!-- Columna de Horas -->
                <div class="columna-horas">
                    <div class="header-columna horas">
                        Horario
                    </div>
                    
                    @foreach($horariosArray as $hora)
                        <div class="celda-hora">
                            {{ \Carbon\Carbon::parse($hora)->format('H:i') }}
                        </div>
                    @endforeach
                </div>

                <!-- Columnas de Empleados -->
                @foreach($empleados as $empleado)
                    <div class="columna-empleado">
                        <!-- Header del Empleado -->
                        <div class="header-columna">
                            <div class="empleado-avatar">
                                {{ strtoupper(substr($empleado->user->nombre, 0, 1)) }}{{ strtoupper(substr($empleado->user->apellidos, 0, 1)) }}
                            </div>
                            <div class="empleado-nombre">
                                {{ $empleado->user->nombre }} {{ $empleado->user->apellidos }}
                            </div>
                        </div>

                        <!-- Celdas de Horario -->
                        @php
                            $horariosEmpleado = isset($horariosEmpleados[$empleado->id]) 
                                ? $horariosEmpleados[$empleado->id] 
                                : collect();
                            
                            $citasEmpleado = isset($citas[$empleado->id]) 
                                ? $citas[$empleado->id] 
                                : collect();
                        @endphp

                        @foreach($horariosArray as $hora)
                            @php
                                $horaCarbon = \Carbon\Carbon::parse($fecha->format('Y-m-d') . ' ' . $hora);
                                
                                // Verificar si el empleado trabaja en este horario
                                $disponible = false;
                                $bloqueDeshabilitado = false;
                                
                                foreach ($horariosEmpleado as $horarioTrabajo) {
                                    // Primero verificar si hay un bloque específico para esta hora exacta
                                    if ($horarioTrabajo->hora && $horarioTrabajo->hora == $hora) {
                                        if (!$horarioTrabajo->disponible) {
                                            $bloqueDeshabilitado = true;
                                            $disponible = false;
                                        } else {
                                            // Bloque disponible con hora exacta
                                            $disponible = true;
                                        }
                                        break; // Ya encontramos el bloque exacto, no seguir buscando
                                    }
                                    
                                    // Luego verificar si está dentro del rango de trabajo general
                                    if ($horarioTrabajo->hora_inicio && $horarioTrabajo->hora_fin) {
                                        $inicioTrabajo = \Carbon\Carbon::parse($fecha->format('Y-m-d') . ' ' . $horarioTrabajo->hora_inicio);
                                        $finTrabajo = \Carbon\Carbon::parse($fecha->format('Y-m-d') . ' ' . $horarioTrabajo->hora_fin);
                                        
                                        if ($horaCarbon->between($inicioTrabajo, $finTrabajo->copy()->subMinutes(30))) {
                                            // Solo marcar como disponible si no está deshabilitado específicamente
                                            if (!$bloqueDeshabilitado) {
                                                $disponible = true;
                                            }
                                        }
                                    }
                                }
                                
                                $claseEstado = '';
                                if ($bloqueDeshabilitado) {
                                    $claseEstado = 'hora-deshabilitada';
                                } elseif (!$disponible) {
                                    $claseEstado = 'no-disponible';
                                }
                            @endphp
                            
                            <div class="celda-horario {{ $claseEstado }}"
                                 data-empleado-id="{{ $empleado->id }}"
                                 data-fecha-hora="{{ $horaCarbon->format('Y-m-d H:i:s') }}"
                                 @if($disponible && !$bloqueDeshabilitado)
                                 ondrop="drop(event)"
                                 ondragover="allowDrop(event)"
                                 onclick="crearCitaRapida({{ $empleado->id }}, '{{ $horaCarbon->format('Y-m-d H:i:s') }}', event)"
                                 @endif
                                 @if($bloqueDeshabilitado)
                                 title="⛔ Hora deshabilitada"
                                 @endif
                                 >
                                @if($bloqueDeshabilitado)
                                    <span class="icono-deshabilitado">⛔</span>
                                @endif
                            </div>
                        @endforeach

                        <!-- Citas del Empleado -->
                        @foreach($citasEmpleado as $cita)
                            @php
                                $horaInicio = \Carbon\Carbon::parse($cita->fecha_hora);
                                $horaBase = \Carbon\Carbon::parse($fecha->format('Y-m-d') . ' 08:00:00');
                                // Calcular minutos desde las 8:00
                                $minutosDesdeInicio = $horaBase->diffInMinutes($horaInicio, false);
                                // Calcular número de bloques de 30 minutos desde las 8:00
                                $numeroBloque = $minutosDesdeInicio / 30;
                                // Cada celda ocupa exactamente 80px de altura
                                // Posición: 140px (header) + (bloques * 80px por bloque) + 2px margen
                                $topPosition = 140 + ($numeroBloque * 80) + 2;
                                
                                // CÁLCULO BASADO EN TIEMPO REAL
                                // Cada bloque de 30 minutos = 80px
                                // La cita ocupa 92% del espacio proporcional a su duración
                                // Mínimo: 1 celda completa (92%) aunque la cita dure menos de 30 min
                                $bloquesOcupados = max(1, $cita->duracion_minutos / 30);
                                $altura = ($bloquesOcupados * 80) * 0.92;
                                
                                // Ejemplos de cálculo:
                                // 15 min: max(1, 15/30) = 1 → 1 * 80 * 0.92 = 73.6px (92% de 1 celda)
                                // 30 min: max(1, 30/30) = 1 → 1 * 80 * 0.92 = 73.6px (92% de 1 celda)
                                // 45 min: max(1, 45/30) = 1.5 → 1.5 * 80 * 0.92 = 110.4px (92% de 1.5 celdas)
                                // 60 min: max(1, 60/30) = 2 → 2 * 80 * 0.92 = 147.2px (92% de 2 celdas)
                                // 90 min: max(1, 90/30) = 3 → 3 * 80 * 0.92 = 220.8px (92% de 3 celdas)
                                // 45 min: max(78.4, 117.6) = 117.6px (98% de 1.5 celdas)
                                // 60 min: max(78.4, 156.8) = 156.8px (98% de 2 celdas)
                                // 90 min: max(78.4, 235.2) = 235.2px (98% de 3 celdas)
                                
                                // Determinar categoría predominante de los servicios
                                $categoriaServicio = 'peluqueria'; // por defecto
                                if ($cita->servicios->isNotEmpty()) {
                                    $categorias = $cita->servicios->pluck('categoria')->toArray();
                                    if (in_array('estetica', $categorias)) {
                                        $categoriaServicio = 'estetica';
                                    }
                                }
                            @endphp
                            
                            <div class="cita-card {{ $cita->estado }} cita-{{ $categoriaServicio }}"
                                 data-cita-id="{{ $cita->id }}"
                                 style="top: {{ $topPosition }}px; height: {{ $altura }}px;">
                                
                                @if($cita->estado !== 'completada' && $cita->estado !== 'cancelada')
                                    <div class="cita-drag-handle" 
                                         draggable="true"
                                         ondragstart="drag(event)">
                                        <span class="cita-drag-icon">⋮⋮</span>
                                        <span style="font-size: 11px; opacity: 0.7;">Arrastra para mover</span>
                                    </div>
                                @endif
                                
                                <div class="cita-content">
                                    <div class="cita-info">
                                        <div class="cita-cliente">
                                            {{ $cita->cliente && $cita->cliente->user ? $cita->cliente->user->nombre . ' ' . $cita->cliente->user->apellidos : 'Cliente no disponible' }}
                                        </div>
                                        
                                        <div class="cita-servicio">
                                            {{ $cita->servicios->isNotEmpty() ? $cita->servicios->pluck('nombre')->join(', ') : 'Servicio no especificado' }}
                                        </div>
                                    </div>

                                    @if($cita->estado !== 'completada' && $cita->estado !== 'cancelada')
                                        <div class="cita-acciones">
                                            <form action="{{ route('citas.completarYCobrar', $cita->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn-accion btn-completar" 
                                                        onclick="event.stopPropagation();"
                                                        title="Completar y cobrar">
                                                    ✓
                                                </button>
                                            </form>
                                            <form action="{{ route('citas.destroy', $cita->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-accion btn-cancelar" 
                                                        onclick="event.stopPropagation(); return confirm('¿Estás seguro de que deseas eliminar esta cita permanentemente?');"
                                                        title="Eliminar cita">
                                                    ✕
                                                </button>
                                            </form>
                                            <button class="btn-accion btn-ver" 
                                                    onclick="event.stopPropagation(); window.location.href='{{ route('citas.show', $cita->id) }}'">
                                                👁
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            @if($empleados->isEmpty())
                <div class="mensaje-vacio">
                    <p>No hay empleados disponibles para mostrar el calendario.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal -->
    <div id="modalCita" class="modal" onclick="cerrarModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2>Detalles de la Cita</h2>
                <button class="btn-cerrar" onclick="cerrarModal()">&times;</button>
            </div>
            <div id="modalBody">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>
    </div>
</body>
</html>
