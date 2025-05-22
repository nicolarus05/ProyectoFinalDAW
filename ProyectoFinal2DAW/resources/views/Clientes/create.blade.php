<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cliente</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Crear nuevo cliente</h1>

    <form action="{{ route('Clientes.store') }}" method="POST">
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

        <label for="direccion">Dirección:</label>
        <input type="text" name="direccion" required>

        <label for="notas_adicionales">Notas Adicionales:</label>
        <textarea name="notas_adicionales"></textarea>

        <label for="fecha_registro">Fecha de Registro:</label>
        <input type="date" name="fecha_registro" value="{{ date('Y-m-d') }}" required>

        <br>
        <button type="submit">Guardar</button>
    </form>

    <a href="{{ route('Clientes.index') }}">Volver a la lista</a>
</body>
</html>
