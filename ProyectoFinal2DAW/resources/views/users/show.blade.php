<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del usuario</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <h1>Detalles del user</h1>

    <p><strong>Nombre:</strong> {{ $user->nombre }}</p>
    <p><strong>Apellidos:</strong> {{ $user->apellidos }}</p>
    <p><strong>Email:</strong> {{ $user->email }}</p>
    <p><strong>Teléfono:</strong> {{ $user->telefono ?? 'No especificado' }}</p>
    <p><strong>Edad:</strong> {{ $user->edad ?? 'No especificada' }}</p>
    <p><strong>Género:</strong> {{ $user->genero ?? 'No especificado' }}</p>
    <p><strong>Rol:</strong> {{ ucfirst($user->rol) }}</p>

    {{-- Mostrar campos específicos según el rol --}}
    @if ($user->rol === 'empleado' && $user->empleado)
        <h3>Datos del empleado</h3>
        <p><strong>Especialización:</strong> {{ $user->empleado->especializacion ?? 'No especificada' }}</p>
    @elseif ($user->rol === 'cliente' && $user->cliente)
        <h3>Datos del cliente</h3>
        <p><strong>Dirección:</strong> {{ $user->cliente->direccion ?? 'No especificada' }}</p>
        <p><strong>Fecha de Registro:</strong> {{ $user->cliente->fecha_registro ?? 'No especificada' }}</p>
        <p><strong>Notas Adicionales:</strong> {{ $user->cliente->notas_adicionales ?? 'Ninguna' }}</p>
    @endif

    <br>

    <a href="{{ route('users.index') }}">Volver a la lista</a>
    <a href="{{ route('users.edit', $user->id) }}">Editar</a>
</body>
</html>
