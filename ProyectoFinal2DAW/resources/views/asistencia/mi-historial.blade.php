<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Historial de Asistencia</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
</head>
<body class="min-h-screen bg-gray-100">
    
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">📊 Mi Historial de Asistencia</h1>
                <a href="{{ route('dashboard') }}" class="text-blue-600 hover:underline">← Volver al Dashboard</a>
            </div>

            <!-- Filtros -->
            <form method="GET" action="{{ route('asistencia.mi-historial') }}" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="fecha_inicio" class="block text-sm font-semibold mb-1">Fecha Inicio:</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" value="{{ $fechaInicio }}" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label for="fecha_fin" class="block text-sm font-semibold mb-1">Fecha Fin:</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" value="{{ $fechaFin }}" class="w-full border rounded px-3 py-2">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-semibold">
                            Filtrar
                        </button>
                    </div>
                </div>
            </form>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                    <p class="text-sm text-blue-800 mb-1">Total de Horas</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $estadisticas['total_horas'] }}</p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                    <p class="text-sm text-green-800 mb-1">Días Trabajados</p>
                    <p class="text-3xl font-bold text-green-600">{{ $estadisticas['dias_trabajados'] }}</p>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
                    <p class="text-sm text-purple-800 mb-1">Promedio Diario</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $estadisticas['promedio_diario'] }}</p>
                </div>
            </div>

            <!-- Tabla de registros -->
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-300 rounded-lg overflow-hidden">
                    <thead class="bg-gray-200 text-gray-700">
                        <tr>
                            <th class="p-3 text-left">Fecha</th>
                            <th class="p-3 text-left">Día</th>
                            <th class="p-3 text-center">Entrada</th>
                            <th class="p-3 text-center">Salida</th>
                            <th class="p-3 text-center">Horas Trabajadas</th>
                            <th class="p-3 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($registros as $registro)
                            @php
                                $horas = $registro->calcularHorasTrabajadas();
                                $estaEnJornada = $registro->estaEnJornada();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="p-3">{{ $registro->fecha->format('d/m/Y') }}</td>
                                <td class="p-3">{{ $registro->fecha->locale('es')->isoFormat('dddd') }}</td>
                                <td class="p-3 text-center">
                                    @if($registro->hora_entrada)
                                        <span class="font-semibold text-green-600">{{ \Carbon\Carbon::parse($registro->hora_entrada)->format('H:i') }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    @if($registro->hora_salida)
                                        <span class="font-semibold text-red-600">{{ \Carbon\Carbon::parse($registro->hora_salida)->format('H:i') }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    @if($horas)
                                        <span class="font-bold text-blue-600">{{ $horas['formatted'] }}</span>
                                    @elseif($estaEnJornada)
                                        <span class="text-orange-600 font-semibold">En curso...</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    @if($estaEnJornada)
                                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">🟢 Trabajando</span>
                                    @elseif($registro->hora_salida)
                                        <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-semibold">✅ Completo</span>
                                    @else
                                        <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold">⚠️ Incompleto</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-gray-500">
                                    No hay registros en el rango de fechas seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>
