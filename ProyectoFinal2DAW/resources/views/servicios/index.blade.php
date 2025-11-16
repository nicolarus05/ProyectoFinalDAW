<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Servicios</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Servicios</h1>
            <a href="{{ route('dashboard') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">← Volver al Inicio</a>
        </div>

        <div class="mb-4">
            <a href="{{ route('servicios.create') }}" 
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Nuevo Servicio</a>
        </div>

        <table class="w-full border border-gray-300 rounded">
            <thead>
                <tr class="bg-gray-200 text-left">
                    <th class="px-3 py-2">Nombre</th>
                    <th class="px-3 py-2">Tiempo</th>
                    <th class="px-3 py-2">Precio</th>
                    <th class="px-3 py-2">Categoría</th>
                    <th class="px-3 py-2">Activo</th>
                    <th class="px-3 py-2">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($servicios as $servicio)
                    <tr class="border-t">
                        <td class="px-3 py-2">{{ $servicio->nombre }}</td>
                        <td class="px-3 py-2">{{ $servicio->tiempo_estimado }} min</td>
                        <td class="px-3 py-2">{{ $servicio->precio }} €</td>
                        <td class="px-3 py-2">{{ ucfirst($servicio->categoria) }}</td>
                        <td class="px-3 py-2">
                            {{ $servicio->activo ? 'Sí' : 'No' }}
                        </td>
                        <td class="px-3 py-2 flex space-x-2">
                            <a href="{{ route('servicios.show', $servicio) }}" class="text-blue-600 hover:underline">Ver</a>
                            <a href="{{ route('servicios.edit', $servicio) }}" class="text-yellow-600 hover:underline">Editar</a>
                            <a href="{{ route('servicios.empleados', $servicio) }}" class="text-purple-600 hover:underline">👥 Empleados</a>
                            <form action="{{ route('servicios.destroy', $servicio) }}" method="POST" onsubmit="return confirm('¿Eliminar este servicio?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-2 text-center text-gray-500">No hay servicios registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
