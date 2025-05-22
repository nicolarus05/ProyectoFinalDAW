<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Usuario</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <h1>Detalles del usuario</h1>

    <p><strong>Nombre:</strong> {{ $usuario->nombre }}</p>
    <p><strong>Apellidos:</strong> {{ $usuario->apellidos }}</p>
    <p><strong>Email:</strong> {{ $usuario->email }}</p>
    <p><strong>Teléfono:</strong> {{ $usuario->telefono ?? 'No especificado' }}</p>
    <p><strong>Edad:</strong> {{ $usuario->edad ?? 'No especificada' }}</p>
    <p><strong>Género:</strong> {{ $usuario->genero ?? 'No especificado' }}</p>
    <p><strong>Rol:</strong> {{ ucfirst($usuario->rol) }}</p>

    {{-- Mostrar campos específicos según el rol --}}
    @if ($usuario->rol === 'empleado' && $usuario->empleado)
        <h3>Datos del empleado</h3>
        <p><strong>Especialización:</strong> {{ $usuario->empleado->especializacion ?? 'No especificada' }}</p>
    @elseif ($usuario->rol === 'cliente' && $usuario->cliente)
        <h3>Datos del cliente</h3>
        <p><strong>Dirección:</strong> {{ $usuario->cliente->direccion ?? 'No especificada' }}</p>
        <p><strong>Fecha de Registro:</strong> {{ $usuario->cliente->fecha_registro ?? 'No especificada' }}</p>
        <p><strong>Notas Adicionales:</strong> {{ $usuario->cliente->notas_adicionales ?? 'Ninguna' }}</p>
    @endif

    <br>

    <a href="{{ route('Usuarios.index') }}">Volver a la lista</a>
    <a href="{{ route('Usuarios.edit', $usuario->id) }}">Editar</a>
</body>
</html>
