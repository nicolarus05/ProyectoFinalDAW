<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Empleado</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Crear nuevo empleado</h1>

    <form action="{{ route('Empleados.store') }}" method="POST">
        @csrf
        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" required>

        <label for="apellidos">Apellidos:</label>
        <input type="text" name="apellidos" required>

        <label for="telefono">Teléfono:</label>
        <input type="text" name="telefono" required>

        <label for="email">Correo electrónico:</label>
        <input type="email" name="email" required>

        <label for="password">Contraseña:</label>
        <input type="password" name="password" required>

        <label for="genero">Género:</label>
        <select name="genero" required>
            <option value="">Seleccione</option>
            <option value="Masculino">Masculino</option>
            <option value="Femenino">Femenino</option>
            <option value="Otro">Otro</option>
        </select>

        <label for="edad">Edad:</label>
        <input type="number" name="edad" min="0" required>

        <label for="especializacion">Especializacion:</label>
        <select name="especializacion"required>
            <option value="">Seleccione</option>
            <option value="Esteticien">Esteticista</option>
            <option value="Peluquera">Peluquera</option>
        </select>

        <br>
        <button type="submit">Guardar</button>
    </form>

    <a href="{{ route('Empleados.index') }}">Volver a la lista</a>
</body>
</html>