<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Horarios - Generar {{ ucfirst($tipo) }}</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
</head>
<body class="bg-gray-50">
    <div class="min-h-screen p-6">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <a href="{{ route('horarios.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                    ← Volver a horarios
                </a>
                <h1 class="text-3xl font-bold text-gray-800">Configurar y Generar Horarios</h1>
                <p class="text-gray-600 mt-2">
                    Empleado: <span class="font-semibold">{{ $empleado->user->nombre }} {{ $empleado->user->apellidos }}</span>
                </p>
                <p class="text-sm text-blue-600 mt-1">
                    Tipo: <strong>{{ $tipo === 'semana' ? 'Semana' : ($tipo === 'mes' ? 'Mes' : 'Año completo') }}</strong>
                    @if($tipo === 'semana' && $fecha_inicio)
                        - Desde: {{ \Carbon\Carbon::parse($fecha_inicio)->format('d/m/Y') }}
                    @elseif($tipo === 'mes' && $mes && $anio)
                        - {{ \Carbon\Carbon::create($anio, $mes, 1)->locale('es')->translatedFormat('F Y') }}
                    @elseif($tipo === 'anual' && $anio)
                        - Año {{ $anio }}
                    @endif
                </p>
            </div>

            <form action="{{ 
                $tipo === 'semana' ? route('horarios.generarSemana') : 
                ($tipo === 'mes' ? route('horarios.generarMes') : route('horarios.generarAnual')) 
            }}" method="POST" class="space-y-8">
                @csrf
                
                <!-- Campos ocultos -->
                <input type="hidden" name="id_empleado" value="{{ $empleado->id }}">
                @if($tipo === 'semana')
                    <input type="hidden" name="fecha_inicio" value="{{ $fecha_inicio }}">
                @elseif($tipo === 'mes')
                    <input type="hidden" name="mes" value="{{ $mes }}">
                    <input type="hidden" name="anio" value="{{ $anio }}">
                @else
                    <input type="hidden" name="anio" value="{{ $anio }}">
                @endif

                <!-- Horarios de Invierno (Septiembre - Junio) -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        ❄️ Horarios de Invierno
                        <span class="text-sm font-normal text-gray-500">(Septiembre - Junio)</span>
                    </h2>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Día</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Hora Inicio</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Hora Fin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @php
                                    $dias = [
                                        1 => 'Lunes',
                                        2 => 'Martes',
                                        3 => 'Miércoles',
                                        4 => 'Jueves',
                                        5 => 'Viernes',
                                        6 => 'Sábado',
                                        0 => 'Domingo'
                                    ];
                                    $horarioInvierno = $empleado->horario_invierno;
                                @endphp
                                @foreach($dias as $numDia => $nombreDia)
                                    @php
                                        $horario = null;
                                        if (is_array($horarioInvierno) && isset($horarioInvierno[$numDia])) {
                                            $horario = $horarioInvierno[$numDia];
                                        }
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-800">{{ $nombreDia }}</td>
                                        <td class="px-4 py-3">
                                            <input type="time" 
                                                   name="horario_invierno[{{ $numDia }}][inicio]" 
                                                   value="{{ $horario['inicio'] ?? '' }}"
                                                   class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="time" 
                                                   name="horario_invierno[{{ $numDia }}][fin]" 
                                                   value="{{ $horario['fin'] ?? '' }}"
                                                   class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Horarios de Verano (Julio - Agosto) -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        ☀️ Horarios de Verano
                        <span class="text-sm font-normal text-gray-500">(Julio - Agosto)</span>
                    </h2>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Día</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Hora Inicio</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Hora Fin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @php
                                    $horarioVerano = $empleado->horario_verano;
                                @endphp
                                @foreach($dias as $numDia => $nombreDia)
                                    @php
                                        $horario = null;
                                        if (is_array($horarioVerano) && isset($horarioVerano[$numDia])) {
                                            $horario = $horarioVerano[$numDia];
                                        }
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-800">{{ $nombreDia }}</td>
                                        <td class="px-4 py-3">
                                            <input type="time" 
                                                   name="horario_verano[{{ $numDia }}][inicio]" 
                                                   value="{{ $horario['inicio'] ?? '' }}"
                                                   class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="time" 
                                                   name="horario_verano[{{ $numDia }}][fin]" 
                                                   value="{{ $horario['fin'] ?? '' }}"
                                                   class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex gap-4 justify-end">
                    <a href="{{ route('horarios.index') }}" 
                       class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                        🎯 Guardar y Generar Horarios
                    </button>
                </div>

                <!-- Información adicional -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-900 mb-2">ℹ️ Información</h3>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>• Configura los horarios base que se usarán para generar automáticamente</li>
                        <li>• Deja los campos vacíos para los días no laborables</li>
                        <li>• Los horarios se guardarán en el perfil del empleado para futuras generaciones</li>
                        <li>• <strong>IMPORTANTE:</strong> La hora de fin debe ser mayor que la hora de inicio</li>
                    </ul>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
