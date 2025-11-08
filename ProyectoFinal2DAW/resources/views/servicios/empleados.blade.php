<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Empleados - {{ $servicio->nombre }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Gestionar Empleados</h1>
                    <p class="text-gray-600 mt-2">
                        Servicio: <span class="font-semibold">{{ $servicio->nombre }}</span>
                        <span class="ml-4 px-3 py-1 rounded-full text-sm {{ $servicio->categoria === 'peluqueria' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' }}">
                            {{ ucfirst($servicio->categoria) }}
                        </span>
                    </p>
                </div>
                <a href="{{ route('servicios.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    ← Volver
                </a>
            </div>
        </div>

        <!-- Mensajes de éxito/error -->
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
                {{ session('warning') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Empleados Asignados -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4 text-gray-800">
                    👥 Empleados Asignados 
                    <span class="text-sm font-normal text-gray-600">({{ $servicio->empleados->count() }})</span>
                </h2>

                @if($servicio->empleados->count() > 0)
                    <div class="space-y-3">
                        @foreach($servicio->empleados as $empleado)
                            <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold
                                        {{ $empleado->categoria === 'peluqueria' ? 'bg-blue-500' : 'bg-pink-500' }}">
                                        {{ strtoupper(substr($empleado->user->nombre ?? '', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">
                                            {{ $empleado->user->nombre ?? '' }} {{ $empleado->user->apellidos ?? '' }}
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <span class="px-2 py-1 rounded text-xs {{ $empleado->categoria === 'peluqueria' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' }}">
                                                {{ ucfirst($empleado->categoria) }}
                                            </span>
                                            @if($empleado->categoria !== $servicio->categoria)
                                                <span class="ml-2 px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-800">
                                                    ⚠️ Asignación manual
                                                </span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <form action="{{ route('servicios.removeempleado', [$servicio, $empleado->id]) }}" 
                                      method="POST" 
                                      onsubmit="return confirm('¿Desasignar a {{ $empleado->user->nombre ?? '' }} de este servicio?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-semibold">
                                        ✕ Remover
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <p class="text-lg">No hay empleados asignados</p>
                        <p class="text-sm mt-2">Agrega empleados desde el panel de la derecha</p>
                    </div>
                @endif
            </div>

            <!-- Agregar Empleados -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4 text-gray-800">
                    ➕ Asignar Empleado
                </h2>

                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded">
                    <p class="text-sm text-blue-800">
                        <strong>ℹ️ Nota:</strong> Puedes asignar empleados de cualquier categoría a este servicio. 
                        Las asignaciones fuera de categoría se marcarán con ⚠️.
                    </p>
                </div>

                @if($empleadosDisponibles->count() > 0)
                    <form action="{{ route('servicios.addempleado', $servicio) }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="id_empleado" class="block text-sm font-semibold text-gray-700 mb-2">
                                Seleccionar Empleado:
                            </label>
                            <select name="id_empleado" id="id_empleado" 
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                                <option value="">-- Seleccione un empleado --</option>
                                @foreach($empleadosDisponibles as $empleado)
                                    <option value="{{ $empleado->id }}"
                                            data-categoria="{{ $empleado->categoria }}">
                                        {{ $empleado->user->nombre ?? '' }} {{ $empleado->user->apellidos ?? '' }}
                                        ({{ ucfirst($empleado->categoria) }})
                                        @if($empleado->categoria !== $servicio->categoria)
                                            ⚠️
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('id_empleado')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="warning-message" class="hidden mb-4 p-3 bg-yellow-50 border border-yellow-300 rounded">
                            <p class="text-sm text-yellow-800">
                                ⚠️ <strong>Atención:</strong> Este empleado es de categoría diferente al servicio. 
                                La asignación se realizará de forma manual.
                            </p>
                        </div>

                        <button type="submit" class="w-full bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 font-semibold transition">
                            ✓ Asignar Empleado
                        </button>
                    </form>

                    <script>
                        const selectEmpleado = document.getElementById('id_empleado');
                        const warningMessage = document.getElementById('warning-message');
                        const servicioCategoria = '{{ $servicio->categoria }}';

                        selectEmpleado.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            const empleadoCategoria = selectedOption.dataset.categoria;

                            if (empleadoCategoria && empleadoCategoria !== servicioCategoria) {
                                warningMessage.classList.remove('hidden');
                            } else {
                                warningMessage.classList.add('hidden');
                            }
                        });
                    </script>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <p class="text-lg">✓ Todos los empleados están asignados</p>
                        <p class="text-sm mt-2">No hay más empleados disponibles para asignar</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Resumen -->
        <div class="bg-white p-6 rounded-lg shadow mt-6">
            <h3 class="text-lg font-bold mb-3 text-gray-800">📊 Resumen</h3>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="p-4 bg-blue-50 rounded">
                    <p class="text-2xl font-bold text-blue-600">{{ $servicio->empleados->count() }}</p>
                    <p class="text-sm text-gray-600">Empleados Asignados</p>
                </div>
                <div class="p-4 bg-green-50 rounded">
                    <p class="text-2xl font-bold text-green-600">
                        {{ $servicio->empleados->where('categoria', $servicio->categoria)->count() }}
                    </p>
                    <p class="text-sm text-gray-600">Misma Categoría</p>
                </div>
                <div class="p-4 bg-yellow-50 rounded">
                    <p class="text-2xl font-bold text-yellow-600">
                        {{ $servicio->empleados->where('categoria', '!=', $servicio->categoria)->count() }}
                    </p>
                    <p class="text-sm text-gray-600">Asignación Manual</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
