<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
</head>
<body>
    <h1>¿Has olvidado tu contraseña?</h1>

    @if (session('status'))
        <div style="color: green;">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <label for="email">Correo electrónico:</label>
        <input type="email" name="email" required>
        <button type="submit">Enviar enlace de restablecimiento</button>
    </form>

    <br>
    <a href="{{ route('login') }}">Volver al inicio de sesión</a>
</body>
</html>
