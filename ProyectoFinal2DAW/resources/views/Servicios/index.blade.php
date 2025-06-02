<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Servicios</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este servicio?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Servicios disponibles</h1>

        <div class="mb-4">
            <a href="{{ route('Servicios.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Añadir nuevo servicio</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-2 border">Nombre</th>
                        <th class="p-2 border">Precio</th>
                        <th class="p-2 border">Tiempo estimado (min)</th>
                        <th class="p-2 border">Tipo</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($servicios as $servicio)
                        <tr class="text-center border-t">
                            <td class="p-2 border">{{ $servicio->nombre }}</td>
                            <td class="p-2 border">{{ $servicio->precio }} €</td>
                            <td class="p-2 border">{{ $servicio->tiempo_estimado }}</td>
                            <td class="p-2 border">{{ $servicio->tipo }}</td>
                            <td class="p-2 border space-y-1">
                                <a href="{{ route('Servicios.show', $servicio->id) }}" class="text-blue-600 hover:underline block">Ver</a>
                                <a href="{{ route('Servicios.edit', $servicio->id) }}" class="text-yellow-600 hover:underline block">Editar</a>
                                <form id="delete-form-{{ $servicio->id }}" action="{{ route('Servicios.destroy', $servicio->id) }}" method="POST" style="display:inline;" onsubmit="return false;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="text-red-600 hover:underline" onclick="confirmarEliminacion({{ $servicio->id }})">Eliminar</button>
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
