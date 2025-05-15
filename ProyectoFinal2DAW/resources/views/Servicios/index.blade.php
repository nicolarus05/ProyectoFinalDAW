<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Servicios</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este servicio?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
</head>
<body>
    <h1>Servicios disponibles</h1>

    <a href="{{ route('Servicios.create') }}" class="btn btn-primary">Añadir nuevo servicio</a>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Tiempo estimado (min)</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($servicios as $servicio)
                <tr>
                    <td>{{ $servicio->id }}</td>
                    <td>{{ $servicio->nombre }}</td>
                    <td>{{ $servicio->precio }} €</td>
                    <td>{{ $servicio->tiempo_estimado }}</td>
                    <td>
                        <a href="{{ route('Servicios.show', $servicio->id) }}">Ver</a>
                        <a href="{{ route('Servicios.edit', $servicio->id) }}">Editar</a>
                        <form id="delete-form-{{ $servicio->id }}" action="{{ route('Servicios.destroy', $servicio->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="button" onclick="confirmarEliminacion({{ $servicio->id }})">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <a href="{{ route('dashboard') }}">Volver al Inicio</a>
</body>
</html>
