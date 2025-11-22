<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Horarios</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
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
                <a href="{{ route('dashboard') }}" class="link-blue font-semibold hover:underline">← Volver al Inicio</a>
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
                            <button type="button" onclick="irAConfiguracion('semana')" class="btn-primary w-full text-white px-4 py-2 rounded hover:bg-blue-700 font-semibold">
                                Configurar y Generar
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
                            <button type="button" onclick="irAConfiguracion('mes')" class="btn-green w-full text-white px-4 py-2 rounded hover:bg-green-700 font-semibold">
                                Configurar y Generar
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
                            <button type="button" onclick="irAConfiguracion('anual')" class="btn-purple w-full text-white px-4 py-2 rounded hover:bg-purple-700 font-semibold">
                                Configurar y Generar
                            </button>
                        </div>

                    </div>

                    <!-- Información de horarios -->
                    <div class="bg-blue-50 border border-blue-200 rounded p-3">
                        <p class="text-sm text-blue-800">
                            <strong>ℹ️ Información:</strong> 
                            Los horarios se generan según la temporada:<br>
                            <strong>Invierno (Sep-Jun):</strong> Lunes a Viernes 9:00-20:00, Sábado 8:30-14:00<br>
                            <strong>Verano (Jul-Ago):</strong> Lunes a Sábado 8:30-14:00 (Miércoles hasta 19:00) 
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
        </div>
    </div>

    <script>
        function irAConfiguracion(tipo) {
            const empleado = document.getElementById('empleado_gen').value;
            if (!empleado) {
                alert('Por favor, selecciona un empleado primero');
                return;
            }

            let url = '/horarios/configurar?empleado=' + empleado + '&tipo=' + tipo;
            
            if (tipo === 'semana') {
                const fecha = document.getElementById('fecha_semana').value;
                url += '&fecha_inicio=' + fecha;
            } else if (tipo === 'mes') {
                const mes = document.getElementById('mes_gen').value;
                const anio = document.getElementById('anio_gen').value;
                url += '&mes=' + mes + '&anio=' + anio;
            } else if (tipo === 'anual') {
                const anio = document.getElementById('anio_completo').value;
                url += '&anio=' + anio;
            }

            window.location.href = url;
        }
    </script>

</body>
</html>
