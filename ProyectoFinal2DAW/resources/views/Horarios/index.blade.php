<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Horarios de Trabajo</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este horario?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
</head>
<body>
    <h1>Horarios de Trabajo</h1>
    <a href="{{ route('Horarios.create') }}" class="btn btn-primary">Añadir nuevo horario</a>

    <table>
        <thead>
            <tr>
                <th>Empleado</th>
                <th>Día de la Semana</th>
                <th>Hora Inicio</th>
                <th>Hora Fin</th>
                <th>Disponible</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($horarios as $horario)
            <tr>
                <td>{{ $horario->empleado->user->nombre ?? '-' }} {{ $horario->empleado->user->apellidos ?? '' }}</td>
                <td>{{ ucfirst($horario->dia_semana) }}</td>
                <td>{{ $horario->hora_inicio }}</td>
                <td>{{ $horario->hora_fin }}</td>
                <td>{{ $horario->disponible ? 'Sí' : 'No' }}</td>
                <td>
                    <a href="{{ route('Horarios.show', $horario->id) }}">Ver</a>
                    <a href="{{ route('Horarios.edit', $horario->id) }}">Editar</a>
                    <form id="delete-form-{{ $horario->id }}" action="{{ route('Horarios.destroy', $horario->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-danger" onclick="confirmarEliminacion({{ $horario->id }})">Eliminar</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <a href="{{ route('dashboard') }}">Volver al Inicio</a>
</body>
</html>
