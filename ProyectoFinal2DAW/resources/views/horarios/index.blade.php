<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Horarios</title>
    @vite(['resources/js/app.js'])
    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este horario?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }

        function confirmarGeneracion(tipo) {
            return confirm(`¿Estás seguro de generar horarios para ${tipo}? Esto puede crear cientos de registros.`);
        }
    </script>
    <style>
        /* Asegurar que los enlaces sean visibles */
        a {
            color: inherit;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .link-blue { color: #2563eb !important; }
        .link-blue:hover { color: #1d4ed8 !important; }
        .link-yellow { color: #d97706 !important; }
        .link-yellow:hover { color: #b45309 !important; }
        .link-red { color: #dc2626 !important; }
        .link-red:hover { color: #b91c1c !important; }
        .link-white { color: white !important; }
        .link-white:hover { opacity: 0.9; }
        
        /* Botones con texto visible */
        button.btn-primary, .btn-primary {
            background-color: #2563eb !important;
            color: white !important;
            font-weight: 600;
        }
        button.btn-green, .btn-green {
            background-color: #16a34a !important;
            color: white !important;
            font-weight: 600;
        }
        button.btn-purple, .btn-purple {
            background-color: #9333ea !important;
            color: white !important;
            font-weight: 600;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100">
    
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">⏰ Gestión de Horarios</h1>
                <a href="{{ route('dashboard') }}" class="link-blue font-semibold hover:underline">← Volver al Dashboard</a>
            </div>

            <!-- Mensajes de éxito -->
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Sección de Herramientas de Generación Automática -->
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 border-2 border-blue-300 rounded-lg p-6 mb-6">
                <h2 class="text-2xl font-bold text-blue-800 mb-4">🔧 Herramientas de Generación Automática</h2>
                
                <form id="formGeneracion" method="POST" class="space-y-4">
                    @csrf
                    
                    <!-- Selector de Empleado -->
                    <div>
                        <label for="empleado_gen" class="block text-sm font-semibold mb-2">Empleado:</label>
                        <select name="id_empleado" id="empleado_gen" required class="w-full border rounded px-3 py-2">
                            <option value="">-- Seleccione un empleado --</option>
                            @foreach($empleados as $emp)
                                <option value="{{ $emp->id }}">
                                    {{ $emp->user->nombre ?? 'N/A' }} {{ $emp->user->apellidos ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Botones de Generación -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        
                        <!-- Generar Semana -->
                        <div class="bg-white border-2 border-blue-500 rounded-lg p-4">
                            <h3 class="font-bold text-blue-700 mb-2">📅 Generar Semana</h3>
                            <p class="text-sm text-gray-600 mb-3">Lunes a Sábado de la semana actual</p>
                            <input type="date" name="fecha_inicio" id="fecha_semana" value="{{ now()->startOfWeek()->format('Y-m-d') }}" class="w-full border rounded px-2 py-1 mb-2 text-sm">
                            <button type="button" onclick="generarHorarios('semana')" class="btn-primary w-full text-white px-4 py-2 rounded hover:bg-blue-700 font-semibold">
                                Generar Semana
                            </button>
                        </div>

                        <!-- Generar Mes -->
                        <div class="bg-white border-2 border-green-500 rounded-lg p-4">
                            <h3 class="font-bold text-green-700 mb-2">📆 Generar Mes</h3>
                            <p class="text-sm text-gray-600 mb-3">Todos los días laborables del mes</p>
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <select name="mes" id="mes_gen" class="border rounded px-2 py-1 text-sm">
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(2025, $m, 1)->locale('es')->translatedFormat('F') }}
                                        </option>
                                    @endfor
                                </select>
                                <select name="anio" id="anio_gen" class="border rounded px-2 py-1 text-sm">
                                    <option value="2024">2024</option>
                                    <option value="2025" selected>2025</option>
                                    <option value="2026">2026</option>
                                </select>
                            </div>
                            <button type="button" onclick="generarHorarios('mes')" class="btn-green w-full text-white px-4 py-2 rounded hover:bg-green-700 font-semibold">
                                Generar Mes
                            </button>
                        </div>

                        <!-- Generar Año -->
                        <div class="bg-white border-2 border-purple-500 rounded-lg p-4">
                            <h3 class="font-bold text-purple-700 mb-2">🗓️ Generar Año Completo</h3>
                            <p class="text-sm text-gray-600 mb-3">Horario normal + verano (Jul-Ago)</p>
                            <select name="anio_completo" id="anio_completo" class="w-full border rounded px-2 py-1 mb-2 text-sm">
                                <option value="2024">2024</option>
                                <option value="2025" selected>2025</option>
                                <option value="2026">2026</option>
                            </select>
                            <button type="button" onclick="generarHorarios('anual')" class="btn-purple w-full text-white px-4 py-2 rounded hover:bg-purple-700 font-semibold">
                                Generar Año Completo
                            </button>
                        </div>

                    </div>

                    <!-- Información de horarios -->
                    <div class="bg-blue-50 border border-blue-200 rounded p-3">
                        <p class="text-sm text-blue-800">
                            <strong>ℹ️ Información:</strong> 
                            Los horarios se generarán de 08:00 a 20:00 (días normales) y de 08:00 a 15:00 (julio-agosto). 
                            Solo se crean bloques que no existan previamente.
                        </p>
                    </div>
                </form>
            </div>

            <!-- Botones de Navegación -->
            <div class="flex gap-4 mb-6">
                <a href="{{ route('horarios.calendario') }}" class="btn-primary text-white px-6 py-3 rounded-lg font-semibold shadow-md flex items-center gap-2" style="background: linear-gradient(to right, #06b6d4, #3b82f6) !important;">
                    <span class="text-xl">📅</span>
                    Ver Calendario Visual
                </a>
                <a href="{{ route('horarios.create') }}" class="text-white px-6 py-3 rounded-lg font-semibold shadow-md" style="background-color: #374151 !important;">
                    ➕ Añadir Horario Manual
                </a>
            </div>

            <!-- Tabla de Horarios Existentes -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h2 class="text-xl font-bold text-gray-800 mb-4">📋 Jornadas Registradas (últimas 50)</h2>

                
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-300 rounded-lg overflow-hidden bg-white">
                        <thead class="bg-gray-700 text-white">
                            <tr>
                                <th class="p-3 text-left">Empleado</th>
                                <th class="p-3 text-left">Fecha</th>
                                <th class="p-3 text-center">Jornada</th>
                                <th class="p-3 text-center">Bloques</th>
                                <th class="p-3 text-center">Tipo</th>
                                <th class="p-3 text-center">Estado</th>
                                <th class="p-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($horariosAgrupados as $jornada)
                            <tr class="hover:bg-gray-50">
                                <td class="p-3">
                                    <span class="font-semibold text-gray-800">{{ $jornada->empleado->user->nombre ?? '-' }} {{ $jornada->empleado->user->apellidos ?? '' }}</span>
                                </td>
                                <td class="p-3">
                                    <span class="font-medium">{{ \Carbon\Carbon::parse($jornada->fecha)->format('d/m/Y') }}</span>
                                    <br>
                                    <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($jornada->fecha)->locale('es')->dayName }}</span>
                                </td>
                                <td class="p-3 text-center">
                                    <div class="bg-blue-100 text-blue-900 px-3 py-2 rounded-lg inline-block font-bold">
                                        {{ substr($jornada->hora_inicio, 0, 5) }} - {{ substr($jornada->hora_fin, 0, 5) }}
                                    </div>
                                </td>
                                <td class="p-3 text-center">
                                    <div class="text-sm">
                                        <div class="font-bold text-gray-700">{{ $jornada->total_bloques }} horas</div>
                                        <div class="text-xs text-gray-500">{{ $jornada->bloques_disponibles }} disponibles</div>
                                    </div>
                                </td>
                                <td class="p-3 text-center">
                                    @if($jornada->tipo_horario == 'verano')
                                        <span class="bg-orange-100 text-orange-800 px-2 py-1 rounded text-xs font-semibold">☀️ Verano</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-semibold">📅 Normal</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    @php
                                        $porcentajeDisponible = ($jornada->bloques_disponibles / $jornada->total_bloques) * 100;
                                    @endphp
                                    @if($porcentajeDisponible == 100)
                                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-semibold">✓ Completa</span>
                                    @elseif($porcentajeDisponible == 0)
                                        <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-semibold">✗ Bloqueada</span>
                                    @else
                                        <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-xs font-semibold">⚠ Parcial ({{ round($porcentajeDisponible) }}%)</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('horarios.calendario', ['empleado_id' => $jornada->id_empleado, 'mes' => \Carbon\Carbon::parse($jornada->fecha)->month, 'anio' => \Carbon\Carbon::parse($jornada->fecha)->year]) }}" 
                                           class="link-blue text-sm font-semibold hover:underline">
                                            Ver en calendario
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-500">
                                    No hay horarios registrados. Usa las herramientas de generación automática para crear horarios.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($horariosAgrupados->count() >= 50)
                    <div class="mt-4 text-center text-sm text-gray-600">
                        <p>Mostrando las últimas 50 jornadas. Usa el calendario para ver todas.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>

    <script>
        function generarHorarios(tipo) {
            const empleado = document.getElementById('empleado_gen').value;
            if (!empleado) {
                alert('Por favor, selecciona un empleado primero');
                return;
            }

            if (!confirmarGeneracion(tipo)) {
                return;
            }

            const form = document.getElementById('formGeneracion');
            
            if (tipo === 'semana') {
                form.action = '{{ route('horarios.generarSemana') }}';
            } else if (tipo === 'mes') {
                form.action = '{{ route('horarios.generarMes') }}';
            } else if (tipo === 'anual') {
                form.action = '{{ route('horarios.generarAnual') }}';
                // Cambiar el nombre del campo para anual
                const anioInput = document.createElement('input');
                anioInput.type = 'hidden';
                anioInput.name = 'anio';
                anioInput.value = document.getElementById('anio_completo').value;
                form.appendChild(anioInput);
            }

            form.submit();
        }
    </script>

</body>
</html>
