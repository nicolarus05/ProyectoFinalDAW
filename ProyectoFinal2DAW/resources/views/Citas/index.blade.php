<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Citas</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('css/indexCita.css') }}">
    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que quieres eliminar esta cita?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
</head>
<body>
    <h1>Citas registradas</h1>

    <a href="{{ route('Citas.create') }}" class="btn btn-primary">Añadir nueva cita</a>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Empleado</th>
                <th>Servicio</th>
                <th>Notas Adicionales</th>
                <th>Fecha y Hora</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($citas as $cita)
                <tr>
                    <td>{{ $cita->cliente->user->nombre ?? '-' }} {{ $cita->cliente->user->apellidos ?? '' }}</td>
                    <td>{{ $cita->empleado->user->nombre ?? '-' }} {{ $cita->empleado->user->apellidos ?? '' }}</td>
                    <td>
                        @if ($cita->servicios && count($cita->servicios))
                            @foreach ($cita->servicios as $servicio)
                                {{ $servicio->nombre }}@if (!$loop->last), @endif
                            @endforeach
                        @else
                            No hay servicios asociados a esta cita.
                        @endif
                    </td>

                    <td>{{ $cita->notas_adicionales ?? '-' }}</td>
                    <td>{{ $cita->fecha_hora }}</td>
                    <td>{{ ucfirst($cita->estado) }}</td>
                    <td>
                        <a href="{{ route('Citas.show', $cita->id) }}">Ver</a>
                        <a href="{{ route('Citas.edit', $cita->id) }}">Editar</a>
                        <form id="delete-form-{{ $cita->id }}" action="{{ route('Citas.destroy', $cita->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="button" onclick="confirmarEliminacion({{ $cita->id }})">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <a href="{{ route('dashboard') }}">Volver al Inicio</a>
</body>
</html>
