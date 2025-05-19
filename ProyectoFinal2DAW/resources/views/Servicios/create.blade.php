<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Servicio</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Añadir nuevo servicio</h1>

    <form action="{{ route('Servicios.store') }}" method="POST">
        @csrf
        <label>Nombre:</label>
        <input type="text" name="nombre" required>

        <label>Precio (€):</label>
        <input type="number" name="precio" step="0.01" required>

        <label>Tiempo estimado (minutos):</label>
        <input type="number" name="tiempo_estimado" required>

        <label>Tipo:</label>
        <select name="tipo" required>
            <option value="">Seleccione</option>
            <option value="Peluqueria">Peluqueria</option>
            <option value="Estetica">Estetica</option>
        </select>

        <button type="submit">Guardar</button>
    </form>

    <a href="{{ route('Servicios.index') }}">Volver</a>
</body>
</html>
