<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de la Cita</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Detalle de la Cita</h1>

    <p><strong>Cliente:</strong> {{ $cita->cliente->user->nombre }} {{ $cita->cliente->user->apellidos }}</p>
    <p><strong>Empleado:</strong> {{ $cita->empleado->user->nombre }} {{ $cita->empleado->user->apellidos }}</p>
    @if ($cita->servicios)<strong>Servicios:</strong>
        @foreach ($cita->servicios as $servicio)
            {{ $servicio->nombre }}
        @endforeach
    @else
        <p>No hay servicios asociados a esta cita.</p>
    @endif

    <p><strong>Fecha y Hora:</strong> {{ $cita->fecha_hora }}</p>
    <p><strong>Estado:</strong> {{ $cita->estado }}</p>

    <a href="{{ route('Citas.index') }}">Volver</a>
</body>
</html>
