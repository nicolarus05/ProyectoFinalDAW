<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Calendario de Citas</title>
    @vite(['resources/js/app.js'])
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F3FAFA;
            padding: 20px;
        }

        .calendario-container {
            max-width: 100%;
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .calendario-header {
            background: linear-gradient(135deg, #C41C34 0%, #DC8A97 100%);
            color: white;
            padding: 24px;
        }

        .calendario-header h1 {
            font-size: 28px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-left {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .header-right {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .navegacion-fechas {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.2);
            padding: 8px 12px;
            border-radius: 8px;
        }

        .navegacion-fechas button {
            background: rgba(255,255,255,0.9);
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            color: #000000;
        }

        .navegacion-fechas button:hover {
            background: #A8E8E6;
            transform: translateY(-2px);
        }
        
        .navegacion-fechas a {
            text-decoration: none;
        }

        .fecha-actual {
            font-size: 18px;
            font-weight: 600;
            padding: 0 16px;
        }

        .btn-nueva-cita {
            background: #6EC7C5;
            color: #000000;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-nueva-cita:hover {
            background: #A8E8E6;
            transform: translateY(-2px);
        }

        .btn-dashboard {
            background: #4F7C7A;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-dashboard:hover {
            background: #6EC7C5;
            transform: translateY(-2px);
        }

        .leyenda {
            display: flex;
            gap: 16px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .leyenda-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }

        .leyenda-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }

        /* Mini Calendario */
        .mini-calendario-container {
            background: rgba(255,255,255,0.15);
            border-radius: 6px;
            padding: 10px;
            margin-top: 12px;
            max-width: 300px;
        }

        .mini-calendario-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .mini-calendario-header h3 {
            font-size: 13px;
            font-weight: 600;
        }

        .mini-calendario-nav {
            display: flex;
            gap: 4px;
        }

        .mini-calendario-nav button {
            background: rgba(255,255,255,0.9);
            border: none;
            width: 22px;
            height: 22px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            color: #000000;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 11px;
        }

        .mini-calendario-nav button:hover {
            background: #A8E8E6;
            transform: scale(1.05);
        }

        .mini-calendario-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }

        .mini-calendario-dia-nombre {
            text-align: center;
            font-size: 9px;
            font-weight: 600;
            padding: 2px;
            opacity: 0.9;
        }

        .mini-calendario-dia {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            font-size: 10px;
            cursor: pointer;
            background: rgba(255,255,255,0.2);
            transition: all 0.2s;
            text-decoration: none;
            color: white;
            padding: 2px;
        }

        .mini-calendario-dia:hover {
            background: rgba(255,255,255,0.4);
            transform: scale(1.08);
        }

        .mini-calendario-dia.otro-mes {
            opacity: 0.3;
        }

        .mini-calendario-dia.hoy {
            background: #6EC7C5;
            font-weight: bold;
        }

        .mini-calendario-dia.seleccionado {
            background: rgba(255,255,255,0.95);
            color: #C41C34;
            font-weight: bold;
        }

        .calendario-grid-container {
            overflow-x: auto;
            padding: 20px;
        }

        .calendario-grid {
            display: grid;
            grid-template-columns: 80px repeat(var(--num-empleados), minmax(200px, 1fr));
            gap: 0;
            background: #d1d5db;
            border: 2px solid #d1d5db;
            min-width: fit-content;
        }

        .columna-horas {
            position: sticky;
            left: 0;
            z-index: 10;
            background: #f9fafb;
        }

        .header-columna {
            background: #C41C34;
            color: white;
            padding: 20px;
            font-weight: 600;
            text-align: center;
            height: 140px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .header-columna.horas {
            background: #4F7C7A;
            height: 140px;
            position: relative;
            z-index: 11;
        }

        .empleado-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #C41C34;
            font-size: 20px;
        }
        
        .header-columna .empleado-nombre {
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            color: #ffffff !important;
            margin-top: 8px;
            display: block !important;
            line-height: 1.3;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
            width: 100%;
        }

        .celda-hora {
            background: #F3FAFA;
            padding: 10px;
            text-align: center;
            font-weight: 600;
            color: #4F7C7A;
            border-right: 2px solid #d1d5db;
            border-bottom: 2px solid #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 80px;
            font-size: 15px;
        }

        .columna-empleado {
            position: relative;
            background: white;
        }

        .celda-horario {
            height: 80px;
            background: white;
            border-bottom: 2px solid #d1d5db;
            border-right: 2px solid #d1d5db;
            transition: background-color 0.2s;
            cursor: pointer;
        }

        .celda-horario:hover {
            background: #D4B4BC;
        }

        .celda-horario.no-disponible {
            background: #f3f4f6;
            cursor: not-allowed;
        }

        .celda-horario.no-disponible:hover {
            background: #f3f4f6;
        }

        .cita-card {
            position: absolute;
            left: 6px;
            right: 6px;
            background: white;
            border-left: 4px solid;
            border-radius: 6px;
            padding: 8px;
            font-size: 14px;
            cursor: move;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s;
            z-index: 5;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .cita-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transform: translateY(-2px);
            z-index: 6;
        }

        .cita-card.pendiente {
            border-left-color: #DC8A97;
            background: #FFFFFF;
        }

        .cita-card.confirmada {
            border-left-color: #6EC7C5;
            background: #F3FAFA;
        }

        .cita-card.completada {
            border-left-color: #4F7C7A;
            background: #A8E8E6;
        }

        .cita-card.cancelada {
            border-left-color: #C41C34;
            background: #D4B4BC;
            opacity: 0.7;
        }

        .cita-cliente {
            font-weight: 600;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 13px;
        }

        .cita-servicio {
            color: #4F7C7A;
            font-size: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cita-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .cita-acciones {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex-shrink: 0;
        }

        .btn-accion {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-completar {
            background: #6EC7C5;
            color: #000000;
        }

        .btn-completar:hover {
            background: #A8E8E6;
        }

        .btn-ver {
            background: #C41C34;
            color: white;
        }

        .btn-ver:hover {
            background: #DC8A97;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 24px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
        }

        .modal-header h2 {
            font-size: 24px;
            color: #000000;
        }

        .btn-cerrar {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #4F7C7A;
            line-height: 1;
        }

        .btn-cerrar:hover {
            color: #C41C34;
        }

        .dragging {
            opacity: 0.5;
            cursor: grabbing !important;
        }

        .mensaje-vacio {
            text-align: center;
            padding: 40px;
            color: #4F7C7A;
        }

        @media (max-width: 768px) {
            .calendario-grid {
                font-size: 12px;
            }
            
            .header-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
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
                            @endphp
                            
                            <div class="cita-card {{ $cita->estado }}"
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

    <script>
        // Configuración CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Drag and Drop
        function allowDrop(ev) {
            ev.preventDefault();
        }

        function drag(ev) {
            ev.dataTransfer.setData("citaId", ev.target.dataset.citaId);
            ev.target.classList.add('dragging');
        }

        function drop(ev) {
            ev.preventDefault();
            
            // Remover clase dragging de todas las citas
            document.querySelectorAll('.cita-card').forEach(card => {
                card.classList.remove('dragging');
            });

            const citaId = ev.dataTransfer.getData("citaId");
            const celda = ev.target.classList.contains('celda-horario') 
                ? ev.target 
                : ev.target.closest('.celda-horario');
            
            if (!celda || celda.classList.contains('no-disponible')) {
                alert('No se puede mover la cita a este horario (empleado no disponible)');
                return;
            }

            const empleadoId = celda.dataset.empleadoId;
            const fechaHora = celda.dataset.fechaHora;

            moverCita(citaId, empleadoId, fechaHora);
        }

        // Mover Cita via AJAX
        function moverCita(citaId, empleadoId, fechaHora) {
            if (!confirm('¿Deseas mover esta cita?')) {
                return;
            }

            fetch('{{ route("citas.mover") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    cita_id: citaId,
                    nuevo_empleado_id: empleadoId,
                    nueva_fecha_hora: fechaHora
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al mover la cita');
            });
        }

        // Marcar como Completada
        function marcarCompletada(citaId) {
            if (!confirm('¿Marcar esta cita como completada?')) {
                return;
            }

            fetch('{{ route("citas.marcarCompletada") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    cita_id: citaId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al marcar la cita como completada');
            });
        }

        // Crear Cita Rápida
        function crearCitaRapida(empleadoId, fechaHora) {
            const celda = event.target;
            if (celda.classList.contains('no-disponible')) {
                return;
            }
            
            // Redirigir a crear cita con parámetros prellenados
            window.location.href = `{{ route('citas.create') }}?empleado_id=${empleadoId}&fecha_hora=${encodeURIComponent(fechaHora)}`;
        }

        // Modal
        function abrirModal(citaId) {
            const modal = document.getElementById('modalCita');
            modal.classList.add('active');
            
            // Aquí podrías cargar detalles adicionales via AJAX si es necesario
        }

        function cerrarModal(event) {
            if (!event || event.target.id === 'modalCita' || !event) {
                const modal = document.getElementById('modalCita');
                modal.classList.remove('active');
            }
        }

        // Cerrar modal con tecla ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModal();
            }
        });

        // Prevenir comportamiento por defecto del drag
        document.addEventListener('dragend', function(e) {
            document.querySelectorAll('.cita-card').forEach(card => {
                card.classList.remove('dragging');
            });
        });
    </script>
</body>
</html>
