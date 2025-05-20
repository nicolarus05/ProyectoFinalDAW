<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Usuario</title>
    <link rel="stylesheet" href="{{ asset('css/') }}">
</head>
<body>
    <h1>Detalle del usuario</h1>

    <p><strong>Nombre:</strong> {{ $usuario->nombre }} {{ $usuario->apellidos }}</p>
    <p><strong>Email:</strong> {{ $usuario->email }}</p>
    <p><strong>Teléfono:</strong> {{ $usuario->telefono }}</p>
    <p><strong>Edad:</strong> {{ $usuario->edad }}</p>
    <p><strong>Género:</strong> {{ $usuario->genero }}</p>
    <p><strong>Rol:</strong> {{ ucfirst($usuario->rol) }}</p>

    <a href="{{ route('Usuarios.edit', $usuario->id) }}">Editar</a>
    <a href="{{ route('Usuarios.index') }}">Volver a la lista</a>
</body>
</html>
