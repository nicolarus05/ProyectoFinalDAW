<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Empleados</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este empleado?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
</head>
<body>
    <h1>Empleados registrados</h1>
    <a href="{{ route('Empleados.create') }}" class="btn btn-primary">Añadir un nuevo empleado</a>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Genero</th>
                <th>Edad</th>
                <th>Especializacion</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($empleados as $empleado)
            <tr>
                <td>{{ $empleado->user->nombre ?? '-' }}</td>
                <td>{{ $empleado->user->apellidos ?? '-' }}</td>
                <td>{{ $empleado->user->telefono ?? '-' }}</td>
                <td>{{ $empleado->user->email ?? '-' }}</td>
                <td>{{ $empleado->user->genero ?? '-' }}</td>
                <td>{{ $empleado->user->edad ?? '-' }}</td>
                <td>{{ $empleado->especializacion ?? '-' }}</td>
                <td>{{ $empleado->user->rol ?? '-' }}</td>
                <td>
                    <a href="{{ route('Empleados.show', $empleado->id) }}">Ver</a>
                    <a href="{{ route('Empleados.edit', $empleado->id) }}">Editar</a>
                    <form id="delete-form-{{ $empleado->id }}" action="{{ route('Empleados.destroy', $empleado->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-danger" onclick="confirmarEliminacion({{ $empleado->id }})">Eliminar</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <a href="{{ route('dashboard') }}">Volver al Inicio</a>
</body>
</html>