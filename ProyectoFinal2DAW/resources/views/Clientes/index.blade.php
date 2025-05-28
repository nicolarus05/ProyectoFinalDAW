<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Clientes</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este cliente?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
</head>
<body>
    <h1>Clientes registrados</h1>
    <a href="{{ route('Clientes.create') }}" class="btn btn-primary">Añadir un nuevo cliente</a>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Genero</th>
                <th>Edad</th>
                <th>Direccion</th>
                <th>Notas Adicionales</th>
                <th>Fecha de Registro</th>
                <th>Rol</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clientes as $cliente)
            <tr>
                <td>{{ $cliente->user->nombre ?? '-' }}</td>
                <td>{{ $cliente->user->apellidos ?? '-' }}</td>
                <td>{{ $cliente->user->telefono ?? '-' }}</td>
                <td>{{ $cliente->user->email ?? '-' }}</td>
                <td>{{ $cliente->user->genero ?? '-' }}</td>
                <td>{{ $cliente->user->edad ?? '-' }}</td>
                <td>{{ $cliente->direccion ?? '-' }}</td>
                <td>{{ $cliente->notas_adicionales ?? '-' }}</td>
                <td>{{ $cliente->fecha_registro ?? '-' }}</td>
                <td>{{ $cliente->user->rol ?? '-' }}</td>
                
                <td>
                    <a href="{{ route('Clientes.show', $cliente->id) }}">Ver</a>
                    <a href="{{ route('Clientes.edit', $cliente->id) }}">Editar</a>
                    <form id="delete-form-{{ $cliente->id }}" action="{{ route('Clientes.destroy', $cliente->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-danger" onclick="confirmarEliminacion({{ $cliente->id }})">Eliminar</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <a href="{{ route('dashboard') }}">Volver al Inicio</a>
</body>
</html>