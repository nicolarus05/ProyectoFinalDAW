<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Empleado</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Detalles del empleado</h1>

    <ul>
        <li><strong>Nombre:</strong> {{ $empleado->usuario->nombre }}</li>
        <li><strong>Apellidos:</strong> {{ $empleado->usuario->apellidos ?? '-' }}</li>
        <li><strong>Teléfono:</strong> {{ $empleado->usuario->telefono ?? '-' }}</li>
        <li><strong>Email:</strong> {{ $empleado->usuario->email }}</li>
        <li><strong>Género:</strong> {{ $empleado->usuario->genero ?? '-' }}</li>
        <li><strong>Edad:</strong> {{ $empleado->usuario->edad ?? '-' }}</li>
        <li><strong>Epecializacion:</strong> {{ $empleado->especializacion ?? '-' }}</li>
    </ul>

    <a href="{{ route('Empleados.edit', $empleado->id) }}">Editar</a>
    <a href="{{ route('Empleados.index') }}">Volver a la lista</a>
</body>
</html>