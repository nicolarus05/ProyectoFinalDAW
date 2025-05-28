<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Editar cliente</h1>

    <form action="{{ route('Clientes.update', $cliente->id) }}" method="POST">
        @csrf
        @method('PUT')

        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" value="{{ $cliente->user->nombre }}" required>

        <label for="apellidos">Apellidos:</label>
        <input type="text" name="apellidos" value="{{ $cliente->user->apellidos }}">

        <label for="telefono">Teléfono:</label>
        <input type="text" name="telefono" value="{{ $cliente->user->telefono }}">

        <label for="email">Email:</label>
        <input type="email" name="email" value="{{ $cliente->user->email }}">

        <label for="genero">Género:</label>
        <input type="text" name="genero" value="{{ $cliente->user->genero }}">

        <label for="edad">Edad:</label>
        <input type="number" name="edad" value="{{ $cliente->user->edad }}">

        <label for="direccion">Dirección:</label>
        <input type="text" name="direccion" value="{{ $cliente->direccion }}">

        <label for="notas_adicionales">Notas Adicionales:</label>
        <textarea name="notas_adicionales">{{ $cliente->notas_adicionales }}</textarea>

        <label for="fecha_registro">Fecha de Registro:</label>
        <input type="text" name="fecha_registro" value="{{ $cliente->fecha_registro }}" readonly>

        <label for="password">Contraseña (dejar en blanco para no cambiar):</label>
        <input type="password" name="password">

        <button type="submit">Actualizar</button>
    </form>

    <a href="{{ route('Clientes.index') }}">Volver a la lista</a>
</body>
</html>
