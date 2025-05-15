<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empleado</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Editar empleado</h1>

    <form action="{{ route('Empleados.update', $empleado->id) }}" method="POST">
        @csrf
        @method('PUT')

        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" value="{{ $empleado->usuario->nombre }}" required>

        <label for="apellidos">Apellidos:</label>
        <input type="text" name="apellidos" value="{{ $empleado->usuario->apellidos }}">

        <label for="telefono">Teléfono:</label>
        <input type="text" name="telefono" value="{{ $empleado->usuario->telefono }}">

        <label for="email">Email:</label>
        <input type="email" name="email" value="{{ $empleado->usuario->email }}">

        <label for="genero">Género:</label>
        <input type="text" name="genero" value="{{ $empleado->usuario->genero }}">

        <label for="edad">Edad:</label>
        <input type="number" name="edad" value="{{ $empleado->usuario->edad }}">

        <label for="especializacion">Especializacion:</label>
        <select name="especializacion"required>
            <option value="">Seleccione</option>
            <option value="Esteticien">Esteticista</option>
            <option value="Peluquera">Peluquera</option>
        </select>

        <label for="password">Contraseña (dejar en blanco para no cambiar):</label>
        <input type="password" name="password">

        <button type="submit">Actualizar</button>
    </form>

    <a href="{{ route('Empleados.index') }}">Volver a la lista</a>
</body>
</html>