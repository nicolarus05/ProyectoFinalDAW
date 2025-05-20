<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario</title>
    <link rel="stylesheet" href="{{ asset('css/') }}">
</head>
<body>
    <h1>Crear nuevo usuario</h1>

    <form action="{{ route('Usuarios.store') }}" method="POST">
        @csrf

        <label>Nombre:</label>
        <input type="text" name="nombre" required><br>

        <label>Apellidos:</label>
        <input type="text" name="apellidos" required><br>

        <label>Email:</label>
        <input type="email" name="email" required><br>

        <label>Teléfono:</label>
        <input type="text" name="telefono"><br>

        <label>Edad:</label>
        <input type="number" name="edad"><br>

        <label>Género:</label>
        <select name="genero">
            <option value="Masculino">Masculino</option>
            <option value="Femenino">Femenino</option>
            <option value="Otro">Otro</option>
        </select><br>

        <label>Rol:</label>
        <select name="rol">
            <option value="cliente">Cliente</option>
            <option value="empleado">Empleado</option>
            <option value="admin">Administrador</option>
        </select><br>

        <label>Contraseña:</label>
        <input type="password" name="password" required><br>

        <button type="submit">Guardar</button>
    </form>

    <a href="{{ route('Usuarios.index') }}">Volver a la lista</a>
</body>
</html>
