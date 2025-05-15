<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Servicio</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Detalle del Servicio</h1>

    <p><strong>Nombre:</strong> {{ $servicio->nombre }}</p>
    <p><strong>Precio:</strong> {{ $servicio->precio }} €</p>
    <p><strong>Tiempo estimado:</strong> {{ $servicio->tiempo_estimado }} minutos</p>

    <a href="{{ route('Servicios.index') }}">Volver</a>
</body>
</html>
