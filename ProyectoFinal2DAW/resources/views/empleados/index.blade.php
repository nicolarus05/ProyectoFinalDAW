<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Empleados</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este empleado?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Empleados registrados</h1>

        <div class="mb-4">
            <a href="{{ route('empleados.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Añadir un nuevo empleado</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-2 border">Nombre</th>
                        <th class="p-2 border">Apellidos</th>
                        <th class="p-2 border">Email</th>
                        <th class="p-2 border">Categoría</th>
                        <th class="p-2 border">Citas (mes)</th>
                        <th class="p-2 border">Facturación Mensual</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($empleados as $empleado)
                    <tr class="text-center border-t hover:bg-gray-50">
                        <td class="p-2 border">{{ $empleado->user->nombre ?? '-' }}</td>
                        <td class="p-2 border">{{ $empleado->user->apellidos ?? '-' }}</td>
                        <td class="p-2 border text-sm">{{ $empleado->user->email ?? '-' }}</td>
                        <td class="p-2 border">
                            @if($empleado->categoria === 'peluqueria')
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">💇 Peluquería</span>
                            @else
                                <span class="inline-block px-2 py-1 bg-pink-100 text-pink-800 rounded text-sm">💅 Estética</span>
                            @endif
                        </td>
                        <td class="p-2 border">
                            <span class="font-semibold text-gray-700">{{ $empleado->citasAtendidas ?? 0 }}</span>
                        </td>
                        <td class="p-2 border">
                            <div class="space-y-1">
                                <div class="font-bold text-lg text-green-700">
                                    €{{ number_format($empleado->facturacion['total'] ?? 0, 2) }}
                                </div>
                                <details class="text-xs">
                                    <summary class="cursor-pointer text-blue-600 hover:underline">Ver detalles</summary>
                                    <div class="mt-2 p-2 bg-gray-50 rounded text-left">
                                        <div class="flex justify-between mb-1">
                                            <span class="text-gray-600">💇 Servicios:</span>
                                            <span class="font-semibold">€{{ number_format($empleado->facturacion['servicios'] ?? 0, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between mb-1">
                                            <span class="text-gray-600">📦 Productos:</span>
                                            <span class="font-semibold">€{{ number_format($empleado->facturacion['productos'] ?? 0, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between mb-1">
                                            <span class="text-gray-600">🎫 Bonos:</span>
                                            <span class="font-semibold">€{{ number_format($empleado->facturacion['bonos'] ?? 0, 2) }}</span>
                                        </div>
                                        @if(isset($empleado->facturacionAnterior))
                                        <div class="border-t pt-1 mt-2">
                                            <div class="flex justify-between">
                                                <span class="text-gray-500 text-xs">Mes anterior:</span>
                                                <span class="text-xs font-semibold text-gray-600">€{{ number_format($empleado->facturacionAnterior['total'] ?? 0, 2) }}</span>
                                            </div>
                                            @php
                                                $actual = $empleado->facturacion['total'] ?? 0;
                                                $anterior = $empleado->facturacionAnterior['total'] ?? 0;
                                                $diferencia = $actual - $anterior;
                                                $porcentaje = $anterior > 0 ? (($diferencia / $anterior) * 100) : 0;
                                            @endphp
                                            @if($diferencia != 0)
                                            <div class="flex justify-between items-center text-xs">
                                                <span>Variación:</span>
                                                <span class="{{ $diferencia > 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                                    {{ $diferencia > 0 ? '▲' : '▼' }} 
                                                    €{{ number_format(abs($diferencia), 2) }}
                                                    ({{ number_format($porcentaje, 1) }}%)
                                                </span>
                                            </div>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                </details>
                            </div>
                        </td>
                        <td class="p-2 border space-y-1">
                            <a href="{{ route('empleados.show', $empleado->id) }}" class="text-blue-600 hover:underline block">Ver</a>
                            <a href="{{ route('empleados.edit', $empleado->id) }}" class="text-yellow-600 hover:underline block">Editar</a>
                            <form id="delete-form-{{ $empleado->id }}" action="{{ route('empleados.destroy', $empleado->id) }}" method="POST" style="display:inline;" onsubmit="return false;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="text-red-600 hover:underline" onclick="confirmarEliminacion({{ $empleado->id }})">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <a href="{{ route('dashboard') }}" class="text-blue-600 hover:underline">Volver al Inicio</a>
        </div>
    </div>
</body>
</html>