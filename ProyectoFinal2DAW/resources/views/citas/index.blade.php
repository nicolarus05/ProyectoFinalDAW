<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Calendario de Citas</title>
    @vite(['resources/css/calendar.css', 'resources/js/calendar.js', 'resources/js/app.js'])
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
                    ← Volver al Dashboard
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
                    <div class="leyenda-color" style="background-color: #DC8A97;"></div>
                    <span>Pendiente</span>
                </div>
                <div class="leyenda-item">
                    <div class="leyenda-color" style="background-color: #6EC7C5;"></div>
                    <span>Confirmada</span>
                </div>
                <div class="leyenda-item">
                    <div class="leyenda-color" style="background-color: #4F7C7A;"></div>
                    <span>Completada</span>
                </div>
                <div class="leyenda-item">
                    <div class="leyenda-color" style="background-color: #C41C34;"></div>
                    <span>Cancelada</span>
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
                                foreach ($horariosEmpleado as $horarioTrabajo) {
                                    if ($horarioTrabajo->hora_inicio && $horarioTrabajo->hora_fin) {
                                        $inicioTrabajo = \Carbon\Carbon::parse($fecha->format('Y-m-d') . ' ' . $horarioTrabajo->hora_inicio);
                                        $finTrabajo = \Carbon\Carbon::parse($fecha->format('Y-m-d') . ' ' . $horarioTrabajo->hora_fin);
                                        
                                        if ($horaCarbon->between($inicioTrabajo, $finTrabajo->copy()->subMinutes(30))) {
                                            $disponible = true;
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            
                            <div class="celda-horario {{ !$disponible ? 'no-disponible' : '' }}"
                                 data-empleado-id="{{ $empleado->id }}"
                                 data-fecha-hora="{{ $horaCarbon->format('Y-m-d H:i:s') }}"
                                 ondrop="drop(event)"
                                 ondragover="allowDrop(event)"
                                 onclick="crearCitaRapida({{ $empleado->id }}, '{{ $horaCarbon->format('Y-m-d H:i:s') }}')">
                            </div>
                        @endforeach

                        <!-- Citas del Empleado -->
                        @foreach($citasEmpleado as $cita)
                            @php
                                $horaInicio = \Carbon\Carbon::parse($cita->fecha_hora);
                                $horaBase = \Carbon\Carbon::parse($fecha->format('Y-m-d') . ' 08:00:00');
                                // diffInMinutes calcula: $horaBase->diffInMinutes($horaInicio) para obtener positivo
                                $minutosDesdeInicio = $horaBase->diffInMinutes($horaInicio);
                                // Calcular número de bloques de 30 minutos desde las 8:00
                                $numeroBloque = $minutosDesdeInicio / 30;
                                // Cada celda ocupa exactamente 80px (sin contar el borde que se solapa)
                                // Sumar: 140px (header) + (80px altura celda) * número de bloques + 4px margen superior
                                $topPosition = 140 + ($numeroBloque * 80) + 4;
                                // Altura proporcional: cada 30 min = 80px, menos 8px de márgenes (4px arriba + 4px abajo)
                                $altura = (($cita->duracion_minutos / 30) * 80) - 8;
                                
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
                                 draggable="true"
                                 ondragstart="drag(event)"
                                 data-cita-id="{{ $cita->id }}"
                                 style="top: {{ $topPosition }}px; height: {{ $altura }}px;">
                                
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
                                        <button class="btn-accion btn-completar" 
                                                onclick="event.stopPropagation(); marcarCompletada({{ $cita->id }})">
                                            ✓
                                        </button>
                                        <button class="btn-accion btn-ver" 
                                                onclick="event.stopPropagation(); window.location.href='{{ route('citas.show', $cita->id) }}'">
                                            👁
                                        </button>
                                    </div>
                                @endif
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
