<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Calendario de Horarios</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
        .calendario-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }
        .dia-calendario {
            min-height: 100px;
            max-height: 200px;
            font-size: 0.75rem;
        }
        .dia-numero {
            font-size: 1rem;
            font-weight: bold;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100">
    
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            
            <!-- Cabecera -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">📅 Calendario de Horarios</h1>
                <a href="{{ route('horarios.index') }}" class="text-blue-600 hover:underline">← Volver a Horarios</a>
            </div>

            <!-- Filtro de Empleado -->
            <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                <form method="GET" action="{{ route('horarios.calendario') }}" class="flex gap-4 items-end">
                    <div class="flex-1">
                        <label for="empleado_id" class="block text-sm font-semibold mb-2">Seleccionar Empleado:</label>
                        <select name="empleado_id" id="empleado_id" class="w-full border rounded px-3 py-2" onchange="this.form.submit()">
                            <option value="">-- Seleccione un empleado --</option>
                            @foreach($empleados as $emp)
                                <option value="{{ $emp->id }}" {{ $empleadoId == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->user->nombre ?? 'N/A' }} {{ $emp->user->apellidos ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="mes" value="{{ $mes }}">
                    <input type="hidden" name="anio" value="{{ $anio }}">
                </form>
            </div>

            @if($empleadoId)
                <!-- Navegación de Mes -->
                <div class="flex justify-between items-center mb-6">
                    <a href="{{ route('horarios.calendario', ['empleado_id' => $empleadoId, 'mes' => $mes == 1 ? 12 : $mes - 1, 'anio' => $mes == 1 ? $anio - 1 : $anio]) }}" 
                       class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        ← Anterior
                    </a>
                    
                    <h2 class="text-2xl font-bold text-gray-700">
                        {{ \Carbon\Carbon::create($anio, $mes, 1)->locale('es')->translatedFormat('F Y') }}
                    </h2>
                    
                    <a href="{{ route('horarios.calendario', ['empleado_id' => $empleadoId, 'mes' => $mes == 12 ? 1 : $mes + 1, 'anio' => $mes == 12 ? $anio + 1 : $anio]) }}" 
                       class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Siguiente →
                    </a>
                </div>

                <!-- Calendario -->
                <div class="calendario-grid mb-4">
                    <!-- Cabeceras de días -->
                    <div class="text-center font-bold bg-gray-700 text-black p-3 rounded">Lun</div>
                    <div class="text-center font-bold bg-gray-700 text-black p-3 rounded">Mar</div>
                    <div class="text-center font-bold bg-gray-700 text-black p-3 rounded">Mié</div>
                    <div class="text-center font-bold bg-gray-700 text-black p-3 rounded">Jue</div>
                    <div class="text-center font-bold bg-gray-700 text-black p-3 rounded">Vie</div>
                    <div class="text-center font-bold bg-gray-700 text-black p-3 rounded">Sáb</div>
                    <div class="text-center font-bold bg-gray-700 text-black p-3 rounded">Dom</div>

                    @php
                        // Ajustar primerDiaSemana: 0=Domingo, 1=Lunes, etc. -> convertir a 1=Lunes, 7=Domingo
                        $offset = $primerDiaSemana == 0 ? 6 : $primerDiaSemana - 1;
                    @endphp

                    <!-- Espacios vacíos antes del primer día -->
                    @for($i = 0; $i < $offset; $i++)
                        <div class="p-2"></div>
                    @endfor

                    <!-- Días del mes -->
                    @for($dia = 1; $dia <= $diasEnMes; $dia++)
                        @php
                            $fechaDia = \Carbon\Carbon::create($anio, $mes, $dia);
                            $fechaStr = $fechaDia->format('Y-m-d');
                            $horariosDelDia = $horarios->get($fechaStr, collect());
                            
                            $totalBloques = $horariosDelDia->count();
                            $bloquesDisponibles = $horariosDelDia->where('disponible', true)->count();
                            $bloquesDeshabilitados = $totalBloques - $bloquesDisponibles;
                            
                            $esDomingo = $fechaDia->dayOfWeek == 0;
                            $esHoy = $fechaDia->isToday();
                            
                            // Obtener jornada (hora inicio y fin) del registro general
                            $horarioGeneral = $horariosDelDia->whereNotNull('hora_inicio')->first();
                            $horaInicio = $horarioGeneral ? $horarioGeneral->hora_inicio : $horariosDelDia->min('hora');
                            $horaFin = $horarioGeneral ? $horarioGeneral->hora_fin : $horariosDelDia->max('hora');
                            
                            // Obtener horas deshabilitadas con sus notas
                            $horasDeshabilitadas = $horariosDelDia->where('disponible', false);
                            $notasDeshabilitadas = $horasDeshabilitadas->pluck('notas')->filter()->unique()->implode(', ');
                            
                            // Determinar color según especificación
                            if ($esDomingo) {
                                $colorClass = 'bg-gray-300 text-gray-600 cursor-not-allowed';
                                $borderColor = '';
                            } elseif ($totalBloques == 0) {
                                // Sin horario: #5670ff
                                $colorClass = 'bg-white hover:shadow-xl cursor-pointer';
                                $borderColor = 'border-2';
                                $customStyle = 'border-color: #5670ff; background-color: rgba(86, 112, 255, 0.1);';
                            } elseif ($bloquesDeshabilitados == 0) {
                                // Todos disponibles: #008f39
                                $colorClass = 'hover:shadow-xl cursor-pointer';
                                $borderColor = 'border-2';
                                $customStyle = 'border-color: #008f39; background-color: rgba(0, 143, 57, 0.15);';
                            } elseif ($bloquesDisponibles == 0) {
                                // Todos deshabilitados: #ff1f14
                                $colorClass = 'hover:shadow-xl cursor-pointer';
                                $borderColor = 'border-2';
                                $customStyle = 'border-color: #ff1f14; background-color: rgba(255, 31, 20, 0.15);';
                            } else {
                                // Parcialmente disponible: #fff721
                                $colorClass = 'hover:shadow-xl cursor-pointer';
                                $borderColor = 'border-2';
                                $customStyle = 'border-color: #fff721; background-color: rgba(255, 247, 33, 0.15);';
                            }
                            
                            if ($esHoy && !$esDomingo) {
                                $colorClass .= ' ring-2 ring-blue-500';
                            }
                        @endphp
                        
                        <div class="dia-calendario {{ $colorClass }} {{ $borderColor }} p-1 rounded transition-all overflow-hidden"
                             @if(!$esDomingo && $totalBloques > 0)
                                onclick="abrirModalDia('{{ $fechaStr }}', {{ $dia }})"
                                style="{{ $customStyle ?? '' }}"
                             @elseif(!$esDomingo)
                                style="{{ $customStyle ?? '' }}"
                             @endif>
                            <div class="text-center dia-numero mb-0.5">{{ $dia }}</div>
                            @if(!$esDomingo && $totalBloques > 0)
                                <div class="text-xs text-center px-0.5">
                                    <!-- Jornada completa -->
                                    <div class="font-semibold text-gray-800" style="font-size: 0.75rem; line-height: 1.1;">
                                        {{ substr($horaInicio, 0, 5) }}-{{ substr($horaFin, 0, 5) }}
                                    </div>
                                    
                                    <!-- Horas deshabilitadas en rojo si existen -->
                                    @if($bloquesDeshabilitados > 0)
                                        <div class="mt-0.5 space-y-0.5">
                                            @foreach($horasDeshabilitadas as $horaDesha)
                                                <div class="bg-red-600 text-white px-1 rounded text-center" style="font-size: 0.75rem; line-height: 1.2;">
                                                    <span>{{ substr($horaDesha->hora, 0, 5) }}</span>
                                                    @if($horaDesha->notas)
                                                        <span class="text-black-700 font-semibold truncate" style="font-size: 0.55rem;" title="{{ $horaDesha->notas }}">
                                                            {{ \Illuminate\Support\Str::limit($horaDesha->notas, 10) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @elseif(!$esDomingo)
                                <div class="text-xs text-center text-gray-500" style="font-size: 0.6rem;">Sin horario</div>
                            @endif
                        </div>
                    @endfor
                </div>
            @else
                <div class="text-center py-12 text-gray-500">
                    <p class="text-xl">Por favor, selecciona un empleado para ver su calendario</p>
                </div>
            @endif

        </div>
    </div>

    <!-- Modal para ver/editar bloques del día -->
    <div id="modalDia" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold" id="modalTitulo">Día</h3>
                <button onclick="cerrarModal()" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
            </div>
            
            <div id="modalContenido" class="space-y-2">
                <!-- Se llenará con JavaScript -->
            </div>
        </div>
    </div>

    <script>
        let empleadoIdActual = {{ $empleadoId ?? 'null' }};

        function abrirModalDia(fecha, dia) {
            if (!empleadoIdActual) return;
            
            document.getElementById('modalTitulo').textContent = `Horarios del ${dia} de {{ \Carbon\Carbon::create($anio, $mes, 1)->locale('es')->translatedFormat('F Y') }}`;
            document.getElementById('modalContenido').innerHTML = '<p class="text-center">Cargando...</p>';
            document.getElementById('modalDia').classList.remove('hidden');
            
            // Cargar bloques con AJAX
            fetch(`{{ route('horarios.bloquesDia') }}?empleado_id=${empleadoIdActual}&fecha=${fecha}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Bloques recibidos:', data.bloques); // Debug
                    if (data.success && data.bloques.length > 0) {
                        let html = '<div class="space-y-2">';
                        data.bloques.forEach(bloque => {
                            // Asegurar que disponible sea un booleano verdadero
                            const disponible = Boolean(bloque.disponible);
                            const colorClass = disponible ? 'bg-green-50 border-green-500' : 'bg-red-50 border-red-500';
                            const iconoClass = disponible ? 'bg-green-500' : 'bg-red-500';
                            const textoEstado = disponible ? 'Disponible' : 'Deshabilitado';
                            const textoBoton = disponible ? 'Deshabilitar' : 'Habilitar';
                            const botonEstilo = disponible 
                                ? 'background-color: #dc2626 !important; color: white !important;' 
                                : 'background-color: #16a34a !important; color: white !important;';
                            const notasHtml = (!disponible && bloque.notas) ? `<span class="text-sm text-red-700 italic ml-3">- ${bloque.notas}</span>` : '';
                            
                            console.log(`Hora ${bloque.hora}: disponible=${disponible}, botón=${textoBoton}`); // Debug
                            
                            html += `
                                <div class="${colorClass} border-2 rounded p-3">
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center gap-3 flex-1">
                                            <div class="${iconoClass} w-3 h-3 rounded-full flex-shrink-0"></div>
                                            <span class="font-bold text-lg flex-shrink-0">${bloque.hora.substring(0, 5)}</span>
                                            <span class="text-sm text-gray-600 flex-shrink-0">${textoEstado}</span>
                                            ${notasHtml}
                                        </div>
                                        <button onclick="toggleBloque(${bloque.id}, '${bloque.hora.substring(0, 5)}', ${disponible}, this)" 
                                                class="px-3 py-1 rounded text-sm flex-shrink-0"
                                                style="${botonEstilo}">
                                            ${textoBoton}
                                        </button>
                                    </div>
                                    ${disponible ? `
                                        <div id="notasContainer-${bloque.id}" class="hidden mt-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Motivo de la deshabilitación:
                                            </label>
                                            <input type="text" 
                                                   id="notas-${bloque.id}" 
                                                   placeholder="Ej: Comida, Vacaciones, Reunión, etc."
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                                   maxlength="255">
                                        </div>
                                    ` : ''}
                                </div>
                            `;
                        });
                        html += '</div>';
                        document.getElementById('modalContenido').innerHTML = html;
                    } else {
                        document.getElementById('modalContenido').innerHTML = '<p class="text-center text-gray-500">No hay bloques horarios para este día</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('modalContenido').innerHTML = '<p class="text-center text-red-500">Error al cargar los bloques</p>';
                });
        }

        function cerrarModal() {
            document.getElementById('modalDia').classList.add('hidden');
        }

        function toggleBloque(id, hora, disponible, button) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            
            // Si está disponible (se va a deshabilitar), primero mostrar el campo de notas
            if (disponible) {
                const notasContainer = document.getElementById(`notasContainer-${id}`);
                const notasInput = document.getElementById(`notas-${id}`);
                
                // Si el campo de notas está oculto, mostrarlo
                if (notasContainer.classList.contains('hidden')) {
                    notasContainer.classList.remove('hidden');
                    notasInput.focus();
                    button.textContent = 'Confirmar deshabilitación';
                    return;
                }
                
                // Si ya está visible, proceder con la deshabilitación
                const notas = notasInput.value.trim();
                
                if (!notas) {
                    alert('Por favor, añade un motivo para deshabilitar esta hora');
                    notasInput.focus();
                    return;
                }
                
                // Deshabilitar botón mientras se procesa
                button.disabled = true;
                button.textContent = 'Procesando...';
                
                fetch('{{ route('horarios.toggleDisponibilidad') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ 
                        id: id,
                        notas: notas
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recargar la página para actualizar el calendario
                        window.location.reload();
                    } else {
                        alert('Error al actualizar el bloque');
                        button.disabled = false;
                        button.textContent = 'Deshabilitar';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión');
                    button.disabled = false;
                    button.textContent = 'Deshabilitar';
                });
            } else {
                // Si está deshabilitado (se va a habilitar), proceder directamente
                button.disabled = true;
                button.textContent = 'Procesando...';
                
                fetch('{{ route('horarios.toggleDisponibilidad') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recargar la página para actualizar el calendario
                        window.location.reload();
                    } else {
                        alert('Error al actualizar el bloque');
                        button.disabled = false;
                        button.textContent = 'Habilitar';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión');
                    button.disabled = false;
                    button.textContent = 'Habilitar';
                });
            }
        }

        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
            }
        });
    </script>

</body>
</html>
