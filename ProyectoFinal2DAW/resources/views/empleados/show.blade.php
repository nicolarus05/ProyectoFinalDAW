<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Empleado</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <!-- Información básica del empleado -->
        <div class="bg-white p-6 rounded shadow mb-6">
            <h1 class="text-3xl font-bold mb-6">Detalles del empleado</h1>

            <ul class="divide-y divide-gray-200 mb-6">
                <li class="py-2 flex justify-between">
                    <span class="font-semibold">Nombre:</span>
                    <span>{{ $empleado->user->nombre }}</span>
                </li>
                <li class="py-2 flex justify-between">
                    <span class="font-semibold">Apellidos:</span>
                    <span>{{ $empleado->user->apellidos ?? '-' }}</span>
                </li>
                <li class="py-2 flex justify-between">
                    <span class="font-semibold">Teléfono:</span>
                    <span>{{ $empleado->user->telefono ?? '-' }}</span>
                </li>
                <li class="py-2 flex justify-between">
                    <span class="font-semibold">Email:</span>
                    <span>{{ $empleado->user->email }}</span>
                </li>
                <li class="py-2 flex justify-between">
                    <span class="font-semibold">Género:</span>
                    <span>{{ $empleado->user->genero ?? '-' }}</span>
                </li>
                <li class="py-2 flex justify-between">
                    <span class="font-semibold">Edad:</span>
                    <span>{{ $empleado->user->edad ?? '-' }}</span>
                </li>
                <li class="py-2 flex justify-between">
                    <span class="font-semibold">Categoría:</span>
                    <span>
                        @if($empleado->categoria === 'peluqueria')
                            <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded">💇 Peluquería</span>
                        @else
                            <span class="inline-block px-3 py-1 bg-pink-100 text-pink-800 rounded">💅 Estética</span>
                        @endif
                    </span>
                </li>
            </ul>
        </div>

        <!-- Facturación Mensual -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-lg shadow-lg mb-6 border border-green-200">
            <h2 class="text-2xl font-bold mb-4 text-green-800 flex items-center gap-2">
                💰 Facturación del Mes Actual
                <span class="text-sm font-normal text-gray-600">({{ now()->format('F Y') }})</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <!-- Total Facturado -->
                <div class="bg-white rounded-lg p-4 shadow">
                    <div class="text-sm text-gray-600 mb-1">Total Facturado</div>
                    <div class="text-3xl font-bold text-green-700">
                        €{{ number_format($empleado->facturacion['total'] ?? 0, 2) }}
                    </div>
                </div>

                <!-- Citas Atendidas -->
                <div class="bg-white rounded-lg p-4 shadow">
                    <div class="text-sm text-gray-600 mb-1">Citas Atendidas</div>
                    <div class="text-3xl font-bold text-blue-700">
                        {{ $empleado->citasAtendidas ?? 0 }}
                    </div>
                </div>
            </div>

            <!-- Desglose por tipo -->
            <div class="bg-white rounded-lg p-4 shadow">
                <h3 class="font-semibold text-lg mb-3 text-gray-800">Desglose Detallado</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center p-3 bg-blue-50 rounded">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">💇</span>
                            <span class="font-semibold text-gray-700">Servicios</span>
                        </div>
                        <span class="text-xl font-bold text-blue-700">
                            €{{ number_format($empleado->facturacion['servicios'] ?? 0, 2) }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-purple-50 rounded">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">📦</span>
                            <span class="font-semibold text-gray-700">Productos Vendidos</span>
                        </div>
                        <span class="text-xl font-bold text-purple-700">
                            €{{ number_format($empleado->facturacion['productos'] ?? 0, 2) }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-pink-50 rounded">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">🎫</span>
                            <span class="font-semibold text-gray-700">Bonos Vendidos</span>
                        </div>
                        <span class="text-xl font-bold text-pink-700">
                            €{{ number_format($empleado->facturacion['bonos'] ?? 0, 2) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Comparativa con mes anterior -->
            @if(isset($empleado->facturacionAnterior))
            <div class="mt-4 bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h3 class="font-semibold text-md mb-3 text-gray-700">📊 Comparativa con Mes Anterior</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-gray-600 mb-1">Mes Anterior</div>
                        <div class="text-lg font-bold text-gray-700">
                            €{{ number_format($empleado->facturacionAnterior['total'] ?? 0, 2) }}
                        </div>
                    </div>
                    <div>
                        @php
                            $actual = $empleado->facturacion['total'] ?? 0;
                            $anterior = $empleado->facturacionAnterior['total'] ?? 0;
                            $diferencia = $actual - $anterior;
                            $porcentaje = $anterior > 0 ? (($diferencia / $anterior) * 100) : 0;
                        @endphp
                        <div class="text-xs text-gray-600 mb-1">Variación</div>
                        <div class="text-lg font-bold {{ $diferencia > 0 ? 'text-green-600' : ($diferencia < 0 ? 'text-red-600' : 'text-gray-600') }}">
                            @if($diferencia > 0)
                                ▲ +€{{ number_format($diferencia, 2) }} (+{{ number_format($porcentaje, 1) }}%)
                            @elseif($diferencia < 0)
                                ▼ -€{{ number_format(abs($diferencia), 2) }} ({{ number_format($porcentaje, 1) }}%)
                            @else
                                = Sin cambios
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="flex space-x-4">
            <a href="{{ route('empleados.edit', $empleado->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Editar</a>
            <a href="{{ route('empleados.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Volver a la lista</a>
        </div>
    </div>
</body>
</html>