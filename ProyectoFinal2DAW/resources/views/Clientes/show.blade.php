<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Cliente</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Detalles del cliente</h1>

    <ul>
        <li><strong>Nombre:</strong> {{ $cliente->usuario->nombre }}</li>
        <li><strong>Apellidos:</strong> {{ $cliente->usuario->apellidos ?? '-' }}</li>
        <li><strong>Teléfono:</strong> {{ $cliente->usuario->telefono ?? '-' }}</li>
        <li><strong>Email:</strong> {{ $cliente->usuario->email }}</li>
        <li><strong>Género:</strong> {{ $cliente->usuario->genero ?? '-' }}</li>
        <li><strong>Edad:</strong> {{ $cliente->usuario->edad ?? '-' }}</li>
        <li><strong>Dirección:</strong> {{ $cliente->direccion ?? '-' }}</li>
        <li><strong>Notas Adicionales:</strong> {{ $cliente->notas_adicionales ?? '-' }}</li>
        <li><strong>Fecha de Registro:</strong> {{ $cliente->created_at ? $cliente->created_at->format('d/m/Y') : '-' }}</li>
    </ul>

    <a href="{{ route('Clientes.edit', $cliente->id) }}">Editar</a>
    <a href="{{ route('Clientes.index') }}">Volver a la lista</a>
</body>
</html>
