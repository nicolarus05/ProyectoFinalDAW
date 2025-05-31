<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Clientes</title>
    @vite(['resources/js/app.js'])
    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este cliente?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-7xl mx-auto bg-white shadow-md rounded p-6">
        <h1 class="text-3xl font-bold mb-4">Clientes registrados</h1>
        <a href="{{ route('Clientes.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Añadir un nuevo cliente</a>

        <div class="overflow-x-auto mt-4">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-2">Nombre</th>
                        <th class="px-4 py-2">Apellidos</th>
                        <th class="px-4 py-2">Teléfono</th>
                        <th class="px-4 py-2">Email</th>
                        <th class="px-4 py-2">Genero</th>
                        <th class="px-4 py-2">Edad</th>
                        <th class="px-4 py-2">Direccion</th>
                        <th class="px-4 py-2">Notas</th>
                        <th class="px-4 py-2">Registro</th>
                        <th class="px-4 py-2">Rol</th>
                        <th class="px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clientes as $cliente)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $cliente->user->nombre ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->user->apellidos ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->user->telefono ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->user->email ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->user->genero ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->user->edad ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->direccion ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->notas_adicionales ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->fecha_registro ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $cliente->user->rol ?? '-' }}</td>
                        <td class="px-4 py-2 space-x-2">
                            <a href="{{ route('Clientes.show', $cliente->id) }}" class="text-blue-600 hover:underline">Ver</a>
                            <a href="{{ route('Clientes.edit', $cliente->id) }}" class="text-yellow-600 hover:underline">Editar</a>
                            <form id="delete-form-{{ $cliente->id }}" action="{{ route('Clientes.destroy', $cliente->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" onclick="confirmarEliminacion({{ $cliente->id }})" class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <a href="{{ route('dashboard') }}" class="inline-block mt-6 text-gray-700 hover:underline">Volver al Inicio</a>
    </div>
</body>
</html>