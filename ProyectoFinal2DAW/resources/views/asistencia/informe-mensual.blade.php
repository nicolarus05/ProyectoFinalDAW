<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Horas Mensuales - {{ $nombreMes }}</title>
    {!! vite_asset(['resources/css/app.css']) !!}
    <style>
        @media print {
            body { background: white !important; font-size: 11px; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            .container { max-width: 100% !important; padding: 0 !important; }
            .shadow-md { box-shadow: none !important; }
            .bg-white { background: white !important; }
            .bg-gray-100 { background: white !important; }
            table { font-size: 10px; }
            h1 { font-size: 20px !important; }
            h2 { font-size: 16px !important; }
            .stat-card { border: 1px solid #ccc !important; background: white !important; }
        }
        @media print {
            @page { margin: 1cm; size: A4 landscape; }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100">

    <!-- Barra de acciones (no se imprime) -->
    <div class="no-print bg-white shadow-sm border-b sticky top-0 z-10">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="{{ route('asistencia.index') }}" class="text-blue-600 hover:underline">← Volver a Asistencia</a>
            
            <form method="GET" class="flex items-center gap-3">
                <label class="text-sm font-semibold text-gray-600">Mes:</label>
                <select name="mes" class="border rounded px-2 py-1 text-sm">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $mes == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
                <label class="text-sm font-semibold text-gray-600">Año:</label>
                <select name="anio" class="border rounded px-2 py-1 text-sm">
                    @for ($a = now()->year; $a >= now()->year - 3; $a--)
                        <option value="{{ $a }}" {{ $anio == $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endfor
                </select>
                <button type="submit" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 text-sm">Consultar</button>
            </form>

            <button onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-2">
                🖨️ Imprimir
            </button>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6">
        <!-- Cabecera del informe -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="text-center mb-4">
                <h1 class="text-3xl font-bold text-gray-800">📊 Informe de Horas Mensuales</h1>
                <p class="text-xl text-gray-600 mt-1">{{ $nombreMes }}</p>
                <p class="text-sm text-gray-400 mt-1">
                    Periodo: {{ $fechaInicio->format('d/m/Y') }} - {{ $fechaFin->format('d/m/Y') }}
                </p>
            </div>

            <!-- Resumen general -->
            @php
                $totalGeneralMinutos = collect($datosEmpleados)->sum('total_minutos');
                $totalGeneralDias = collect($datosEmpleados)->sum('dias_trabajados');
                $empleadosActivos = collect($datosEmpleados)->filter(fn($e) => $e['dias_trabajados'] > 0)->count();
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="stat-card bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200 text-center">
                    <p class="text-sm text-blue-800 mb-1">Empleados con Registros</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $empleadosActivos }}</p>
                </div>
                <div class="stat-card bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200 text-center">
                    <p class="text-sm text-green-800 mb-1">Total Horas del Mes</p>
                    <p class="text-3xl font-bold text-green-600">{{ sprintf('%dh %02dmin', floor($totalGeneralMinutos / 60), $totalGeneralMinutos % 60) }}</p>
                </div>
                <div class="stat-card bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200 text-center">
                    <p class="text-sm text-purple-800 mb-1">Total Jornadas</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $totalGeneralDias }}</p>
                </div>
            </div>
        </div>

        <!-- Tabla resumen por empleado -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Resumen por Empleado</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-left">
                            <th class="px-4 py-3 border-b font-semibold">Empleado</th>
                            <th class="px-4 py-3 border-b font-semibold text-center">Días Trabajados</th>
                            <th class="px-4 py-3 border-b font-semibold text-center">Horas Totales</th>
                            <th class="px-4 py-3 border-b font-semibold text-center">Horas Extra</th>
                            <th class="px-4 py-3 border-b font-semibold text-center">Promedio Diario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($datosEmpleados as $dato)
                            @if($dato['dias_trabajados'] > 0)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">
                                    {{ $dato['empleado']->user->nombre ?? '' }} {{ $dato['empleado']->user->apellidos ?? '' }}
                                    <span class="text-xs text-gray-500 ml-1">({{ ucfirst($dato['empleado']->categoria ?? 'N/A') }})</span>
                                </td>
                                <td class="px-4 py-3 text-center">{{ $dato['dias_trabajados'] }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-blue-700">{{ $dato['total_formatted'] }}</td>
                                <td class="px-4 py-3 text-center {{ $dato['total_minutos'] > 0 ? 'text-orange-600' : 'text-gray-400' }}">{{ $dato['extra_formatted'] }}</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ $dato['promedio_diario'] }}</td>
                            </tr>
                            @endif
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay registros para este periodo</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 font-bold">
                            <td class="px-4 py-3">TOTAL</td>
                            <td class="px-4 py-3 text-center">{{ $totalGeneralDias }}</td>
                            <td class="px-4 py-3 text-center text-blue-700">{{ sprintf('%dh %02dmin', floor($totalGeneralMinutos / 60), $totalGeneralMinutos % 60) }}</td>
                            <td class="px-4 py-3 text-center" colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Detalle por empleado -->
        @foreach($datosEmpleados as $index => $dato)
            @if($dato['dias_trabajados'] > 0)
            <div class="bg-white rounded-lg shadow-md p-6 mb-6 {{ $index > 0 ? 'page-break' : '' }}">
                <h2 class="text-lg font-bold text-gray-800 mb-1">
                    {{ $dato['empleado']->user->nombre ?? '' }} {{ $dato['empleado']->user->apellidos ?? '' }}
                </h2>
                <p class="text-sm text-gray-500 mb-4">
                    {{ ucfirst($dato['empleado']->categoria ?? 'N/A') }} · 
                    {{ $dato['dias_trabajados'] }} días · 
                    <span class="font-semibold text-blue-700">{{ $dato['total_formatted'] }} totales</span>
                </p>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-left">
                                <th class="px-3 py-2 border-b">Fecha</th>
                                <th class="px-3 py-2 border-b">Día</th>
                                <th class="px-3 py-2 border-b text-center">Entrada</th>
                                <th class="px-3 py-2 border-b text-center">Salida</th>
                                <th class="px-3 py-2 border-b text-center">Horas</th>
                                <th class="px-3 py-2 border-b text-center">Extra (min)</th>
                                <th class="px-3 py-2 border-b text-center">Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dato['detalle_dias'] as $dia)
                                @php
                                    $fechaDia = \Carbon\Carbon::parse($dia['fecha']);
                                    $esSabado = $fechaDia->isSaturday();
                                @endphp
                                <tr class="border-b {{ $esSabado ? 'bg-yellow-50' : '' }} {{ $dia['fuera_horario'] ? 'bg-red-50' : '' }}">
                                    <td class="px-3 py-2">{{ $fechaDia->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2">{{ ucfirst($fechaDia->translatedFormat('l')) }}</td>
                                    <td class="px-3 py-2 text-center">{{ $dia['entrada'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $dia['salida'] }}</td>
                                    <td class="px-3 py-2 text-center font-medium">{{ $dia['horas'] }}</td>
                                    <td class="px-3 py-2 text-center {{ $dia['minutos_extra'] > 0 ? 'text-orange-600 font-semibold' : 'text-gray-400' }}">
                                        {{ $dia['minutos_extra'] > 0 ? '+' . $dia['minutos_extra'] . ' min' : '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-center text-xs">
                                        @if($dia['fuera_horario'])
                                            <span class="text-red-600">⚠️ Fuera de horario</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-100 font-bold">
                                <td class="px-3 py-2" colspan="4">Total</td>
                                <td class="px-3 py-2 text-center text-blue-700">{{ $dato['total_formatted'] }}</td>
                                <td class="px-3 py-2 text-center text-orange-600">{{ $dato['extra_formatted'] }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif
        @endforeach

        <!-- Pie de informe -->
        <div class="text-center text-xs text-gray-400 mt-4 mb-8">
            Informe generado el {{ now()->translatedFormat('d F Y, H:i') }}
        </div>
    </div>
</body>
</html>
