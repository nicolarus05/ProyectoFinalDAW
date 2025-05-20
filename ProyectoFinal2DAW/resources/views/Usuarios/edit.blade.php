<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="{{ asset('css/') }}">
</head>
<body>
    <h1>Editar usuario</h1>

    <form action="{{ route('Usuarios.update', $usuario->id) }}" method="POST">
        @csrf
        @method('PUT')

        <label>Nombre:</label>
        <input type="text" name="nombre" value="{{ $usuario->nombre }}" required><br>

        <label>Apellidos:</label>
        <input type="text" name="apellidos" value="{{ $usuario->apellidos }}" required><br>

        <label>Email:</label>
        <input type="email" name="email" value="{{ $usuario->email }}" required><br>

        <label>Teléfono:</label>
        <input type="text" name="telefono" value="{{ $usuario->telefono }}"><br>

        <label>Edad:</label>
        <input type="number" name="edad" value="{{ $usuario->edad }}"><br>

        <label>Género:</label>
        <select name="genero">
            <option value="Masculino" @if($usuario->genero == 'Masculino') selected @endif>Masculino</option>
            <option value="Femenino" @if($usuario->genero == 'Femenino') selected @endif>Femenino</option>
            <option value="Otro" @if($usuario->genero == 'Otro') selected @endif>Otro</option>
        </select><br>

        <label>Rol:</label>
        <select name="rol">
            <option value="cliente" @if($usuario->rol == 'cliente') selected @endif>Cliente</option>
            <option value="empleado" @if($usuario->rol == 'empleado') selected @endif>Empleado</option>
            <option value="admin" @if($usuario->rol == 'admin') selected @endif>Administrador</option>
        </select><br>

        <button type="submit">Actualizar</button>
    </form>

    <a href="{{ route('Usuarios.index') }}">Volver a la lista</a>
</body>
</html>
