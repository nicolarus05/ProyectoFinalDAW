<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de usuarios</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('css/') }}">
    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este usuario?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
</head>
<body>
    <h1>users registrados</h1>

    <a href="{{ route('users.create') }}" class="btn btn-primary">Añadir nuevo user</a>

    @if(session('success'))
        <div style="color: green; margin: 10px 0;">{{ str_replace('user', 'usuario', session('success')) }}</div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Edad</th>
                <th>Género</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->nombre }} {{ $user->apellidos }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->telefono }}</td>
                    <td>{{ $user->edad }}</td>
                    <td>{{ $user->genero }}</td>
                    <td>{{ ucfirst($user->rol) }}</td>
                    <td>
                        <a href="{{ route('users.show', $user->id) }}">Ver</a>
                        <a href="{{ route('users.edit', $user->id) }}">Editar</a>
                        <form id="delete-form-{{ $user->id }}" action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="button" onclick="confirmarEliminacion({{ $user->id }})">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <a href="{{ route('dashboard') }}">Volver al Inicio</a>
</body>
</html>
