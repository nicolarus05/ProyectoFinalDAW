<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Cliente</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Registro de Cliente</h1>

    <form method="POST" action="{{ route('register.cliente.store') }}">
        @csrf

        <label>Nombre:</label>
        <input type="text" name="nombre" value="{{ old('nombre') }}" required><br>

        <label>Apellidos:</label>
        <input type="text" name="apellidos" value="{{ old('apellidos') }}" required><br>

        <label>Teléfono:</label>
        <input type="text" name="telefono" value="{{ old('telefono') }}" required><br>

        <label>Email:</label>
        <input type="email" name="email" value="{{ old('email') }}" required><br>

        <label>Contraseña:</label>
        <input type="password" name="password" required><br>

        <label>Confirmar contraseña:</label>
        <input type="password" name="password_confirmation" required><br>

        <label>Género:</label>
        <select name="genero" required>
            <option value="">Selecciona</option>
            <option value="masculino" {{ old('genero') == 'masculino' ? 'selected' : '' }}>Masculino</option>
            <option value="femenino" {{ old('genero') == 'femenino' ? 'selected' : '' }}>Femenino</option>
            <option value="otro" {{ old('genero') == 'otro' ? 'selected' : '' }}>Otro</option>
        </select><br>

        <label>Edad:</label>
        <input type="number" name="edad" min="0" max="120" value="{{ old('edad') }}" required><br>

        <label>Dirección:</label>
        <input type="text" name="direccion" value="{{ old('direccion') }}" required><br>

        <label>Notas adicionales:</label>
        <textarea name="notas_adicionales">{{ old('notas_adicionales') }}</textarea><br>

        <label>Fecha de registro:</label>
        <input type="date" name="fecha_registro" value="{{ old('fecha_registro', date('Y-m-d')) }}" required><br>

        <button type="submit">Registrarse</button>
    </form>

    <br>
    <a href="{{ route('login') }}">¿Ya tienes cuenta? Inicia sesión</a>
</body>
</html>
