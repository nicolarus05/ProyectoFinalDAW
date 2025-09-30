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
                        <th class="p-2 border">Teléfono</th>
                        <th class="p-2 border">Email</th>
                        <th class="p-2 border">Genero</th>
                        <th class="p-2 border">Edad</th>
                        <th class="p-2 border">Especialización</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($empleados as $empleado)
                    <tr class="text-center border-t">
                        <td class="p-2 border">{{ $empleado->user->nombre ?? '-' }}</td>
                        <td class="p-2 border">{{ $empleado->user->apellidos ?? '-' }}</td>
                        <td class="p-2 border">{{ $empleado->user->telefono ?? '-' }}</td>
                        <td class="p-2 border">{{ $empleado->user->email ?? '-' }}</td>
                        <td class="p-2 border">{{ $empleado->user->genero ?? '-' }}</td>
                        <td class="p-2 border">{{ $empleado->user->edad ?? '-' }}</td>
                        <td class="p-2 border">{{ $empleado->especializacion ?? '-' }}</td>
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