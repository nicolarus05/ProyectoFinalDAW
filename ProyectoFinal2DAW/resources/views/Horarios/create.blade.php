<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Horario de Trabajo</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Crear Horario de Trabajo</h1>

    <form action="{{ route('Horarios.store') }}" method="POST">
        @csrf

        <label for="id_empleado">Empleado:</label>
        <select name="id_empleado" id="id_empleado" required>
            <option value="">Seleccione un empleado</option>
            @foreach($empleados as $empleado)
                <option value="{{ $empleado->id }}">
                    {{ $empleado->user->nombre ?? '-' }} {{ $empleado->user->apellidos ?? '' }}
                </option>
            @endforeach
        </select>

        <label for="dia_semana">Día de la semana:</label>
        <select name="dia_semana" id="dia_semana" required>
            <option value="">Seleccione un día</option>
            <option value="lunes">Lunes</option>
            <option value="martes">Martes</option>
            <option value="miércoles">Miércoles</option>
            <option value="jueves">Jueves</option>
            <option value="viernes">Viernes</option>
            <option value="sábado">Sábado</option>
        </select>

        <label for="hora_inicio">Hora de inicio:</label>
        <input type="time" name="hora_inicio" id="hora_inicio" required>

        <label for="hora_fin">Hora de fin:</label>
        <input type="time" name="hora_fin" id="hora_fin" required>

        <label for="disponible">Disponible:</label>
        <input type="checkbox" name="disponible" id="disponible" value="1">

        <br><br>
        <button type="submit">Guardar</button>
    </form>

    <a href="{{ route('Horarios.index') }}">Volver a la lista</a>
</body>
</html>
