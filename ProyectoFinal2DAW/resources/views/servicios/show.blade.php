<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Servicio</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">{{ $servicio->nombre }}</h1>

        <p><strong>Tiempo Estimado:</strong> {{ $servicio->tiempo_estimado }} min</p>
        <p><strong>Precio:</strong> {{ $servicio->precio }} €</p>
        <p><strong>Categoría:</strong> {{ ucfirst($servicio->categoria) }}</p>
        <p><strong>Descripción:</strong> {{ $servicio->descripcion ?? 'Sin descripción' }}</p>
        <p><strong>Activo:</strong> {{ $servicio->activo ? 'Sí' : 'No' }}</p>

        <!-- Empleados Asignados -->
        <div class="mt-6 p-4 bg-gray-50 rounded">
            <h2 class="font-bold text-lg mb-2">👥 Empleados Asignados ({{ $servicio->empleados->count() }})</h2>
            @if($servicio->empleados->count() > 0)
                <ul class="list-disc list-inside space-y-1">
                    @foreach($servicio->empleados as $empleado)
                        <li class="text-gray-700">
                            {{ $empleado->user->nombre ?? '' }} {{ $empleado->user->apellidos ?? '' }}
                            <span class="text-sm text-gray-500">({{ ucfirst($empleado->categoria) }})</span>
                            @if($empleado->categoria !== $servicio->categoria)
                                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">⚠️ Manual</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('servicios.empleados', $servicio) }}" class="text-blue-600 hover:underline text-sm mt-2 inline-block">
                    Gestionar empleados →
                </a>
            @else
                <p class="text-gray-500">No hay empleados asignados.</p>
                <a href="{{ route('servicios.empleados', $servicio) }}" class="text-blue-600 hover:underline text-sm mt-2 inline-block">
                    Asignar empleados →
                </a>
            @endif
        </div>

        <div class="flex justify-between items-center mt-6">
            <a href="{{ route('servicios.edit', $servicio) }}" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">Editar</a>
            <a href="{{ route('servicios.index') }}" class="text-blue-600 hover:underline">Volver</a>
        </div>
    </div>
</body>
</html>
