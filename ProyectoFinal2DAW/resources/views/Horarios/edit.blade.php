<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Editar Horario de Trabajo</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
</head>
<body>
    <h1>Editar Horario de Trabajo</h1>

    <form action="{{ route('Horarios.update', $horario->id) }}" method="POST">
        @csrf
        @method('PUT')

        <label for="id_empleado">Empleado:</label>
        <select name="id_empleado" required>
            @foreach($empleados as $empleado)
                <option value="{{ $empleado->id }}" {{ $horario->id_empleado == $empleado->id ? 'selected' : '' }}>
                    {{ $empleado->usuario->nombre ?? '' }} {{ $empleado->usuario->apellidos ?? '' }}
                </option>
            @endforeach
        </select>

        <label for="dia_semana">Día de la semana:</label>
        <select name="dia_semana" required>
            @foreach(['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'] as $dia)
                <option value="{{ $dia }}" {{ $horario->dia_semana == $dia ? 'selected' : '' }}>
                    {{ ucfirst($dia) }}
                </option>
            @endforeach
        </select>

        <label for="hora_inicio">Hora inicio:</label>
        <input type="time" name="hora_inicio" value="{{ $horario->hora_inicio }}" required />

        <label for="hora_fin">Hora fin:</label>
        <input type="time" name="hora_fin" value="{{ $horario->hora_fin }}" required />

        <!-- Campo oculto para asegurar que siempre se envíe el valor 'disponible' -->
        <input type="hidden" name="disponible" value="0" />
        
        <label for="disponible">Disponible:</label>
        <input type="checkbox" name="disponible" value="1" {{ $horario->disponible ? 'checked' : '' }} />

        <button type="submit">Actualizar</button>
    </form>

    <a href="{{ route('Horarios.index') }}">Volver a la lista</a>
</body>
</html>
