<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Servicio</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Editar servicio</h1>

    <form action="{{ route('Servicios.update', $servicio->id) }}" method="POST">
        @csrf
        @method('PUT')

        <label>Nombre:</label>
        <input type="text" name="nombre" value="{{ $servicio->nombre }}" required>

        <label>Precio (€):</label>
        <input type="number" name="precio" step="0.01" value="{{ $servicio->precio }}" required>

        <label>Tiempo estimado (minutos):</label>
        <input type="number" name="tiempo_estimado" value="{{ $servicio->tiempo_estimado }}" required>

        <button type="submit">Actualizar</button>
    </form>

    <a href="{{ route('Servicios.index') }}">Volver</a>
</body>
</html>
