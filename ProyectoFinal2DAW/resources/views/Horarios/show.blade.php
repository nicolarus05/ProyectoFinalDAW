<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Horario de Trabajo</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Detalle Horario de Trabajo</h1>

    <p><strong>Empleado:</strong> {{ $horario->empleado->usuario->nombre ?? '-' }} {{ $horario->empleado->usuario->apellidos ?? '' }}</p>
    <p><strong>Día de la semana:</strong> {{ ucfirst($horario->dia_semana) }}</p>
    <p><strong>Hora de inicio:</strong> {{ $horario->hora_inicio }}</p>
    <p><strong>Hora de fin:</strong> {{ $horario->hora_fin }}</p>
    <p><strong>Disponible:</strong> {{ $horario->disponible ? 'Sí' : 'No' }}</p>

    <a href="{{ route('Horarios.index') }}">Volver a la lista</a>
</body>
</html>
