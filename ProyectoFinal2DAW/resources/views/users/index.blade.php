<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de usuarios</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este usuario?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Usuarios registrados</h1>

        <div class="mb-4">
            <a href="{{ route('users.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Añadir nuevo usuario</a>
        </div>

        @if(session('success'))
            <div class="text-green-600 mb-4">{{ str_replace('user', 'usuario', session('success')) }}</div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-2 border">Nombre</th>
                        <th class="p-2 border">Email</th>
                        <th class="p-2 border">Teléfono</th>
                        <th class="p-2 border">Edad</th>
                        <th class="p-2 border">Género</th>
                        <th class="p-2 border">Rol</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr class="text-center border-t">
                            <td class="p-2 border">{{ $user->nombre }} {{ $user->apellidos }}</td>
                            <td class="p-2 border">{{ $user->email }}</td>
                            <td class="p-2 border">{{ $user->telefono }}</td>
                            <td class="p-2 border">{{ $user->edad }}</td>
                            <td class="p-2 border">{{ $user->genero }}</td>
                            <td class="p-2 border">{{ ucfirst($user->rol) }}</td>
                            <td class="p-2 border space-y-1">
                                <a href="{{ route('users.show', $user->id) }}" class="text-blue-600 hover:underline block">Ver</a>
                                <a href="{{ route('users.edit', $user->id) }}" class="text-yellow-600 hover:underline block">Editar</a>
                                <form id="delete-form-{{ $user->id }}" action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;" onsubmit="return false;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="text-red-600 hover:underline" onclick="confirmarEliminacion({{ $user->id }})">Eliminar</button>
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
