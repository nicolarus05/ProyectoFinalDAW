<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Citas - {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
</head>
<body class="bg-gray-100 p-6">
    <div class="w-full max-w-6xl mx-auto bg-white shadow-md rounded p-6">
        <!-- Header con botón volver -->
        <div class="flex justify-between items-start mb-6">
            <div class="flex-1">
                <h1 class="text-3xl font-bold mb-2">📅 Historial de Citas</h1>
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                    <p class="text-lg">
                        <strong>Cliente:</strong> {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}
                    </p>
                    <p class="text-sm text-gray-600 mt-1">
                        <strong>Email:</strong> {{ $cliente->user->email }} | 
                        <strong>Teléfono:</strong> {{ $cliente->user->telefono ?? 'No disponible' }}
                    </p>
                    @if($cliente->notas_adicionales)
                        <div class="mt-2 bg-yellow-50 border-l-4 border-yellow-400 p-2 rounded">
                            <p class="text-sm text-yellow-900">
                                <strong>📝 Notas:</strong> {{ $cliente->notas_adicionales }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
            <a href="{{ route('clientes.index') }}" 
               class="ml-4 bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition-colors whitespace-nowrap">
                ← Volver a Clientes
            </a>
        </div>
        </div>

        <!-- Tabla de Citas -->
        @if($citas->isEmpty())
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded mb-6">
                <p class="text-yellow-800">
                    <strong>ℹ️ Sin historial:</strong> Este cliente aún no tiene citas registradas.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left">Fecha y Hora</th>
                            <th class="px-4 py-3 text-left">Empleado</th>
                            <th class="px-4 py-3 text-left">Servicios</th>
                            <th class="px-4 py-3 text-center">Duración</th>
                            <th class="px-4 py-3 text-center">Estado</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($citas as $cita)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="font-semibold">
                                        {{ \Carbon\Carbon::parse($cita->fecha_hora)->format('d/m/Y') }}
                                    </div>
                                    <div class="text-gray-600 text-xs">
                                        {{ \Carbon\Carbon::parse($cita->fecha_hora)->format('H:i') }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    {{ $cita->empleado->user->nombre ?? 'N/A' }} 
                                    {{ $cita->empleado->user->apellidos ?? '' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($cita->servicios->isNotEmpty())
                                        <ul class="text-xs">
                                            @foreach($cita->servicios as $servicio)
                                                <li>• {{ $servicio->nombre }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-gray-400">Sin servicios</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-semibold">
                                        {{ $cita->duracion_minutos ?? 0 }} min
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($cita->estado === 'completada')
                                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-semibold">
                                            ✓ Completada
                                        </span>
                                    @elseif($cita->estado === 'pendiente')
                                        <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-semibold">
                                            ⏱️ Pendiente
                                        </span>
                                    @else
                                        <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-semibold">
                                            ✕ Cancelada
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('citas.show', $cita->id) }}" 
                                       class="text-blue-600 hover:underline text-sm">
                                        👁️ Ver
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Botones de acción -->
        <div class="mt-6 flex gap-3">
            <a href="{{ route('clientes.show', $cliente->id) }}" 
               class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition-colors">
                Ver Perfil del Cliente
            </a>
            <a href="{{ route('citas.create') }}?cliente_id={{ $cliente->id }}" 
               class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition-colors">
                + Nueva Cita
            </a>
        </div>
    </div>
</body>
</html>
