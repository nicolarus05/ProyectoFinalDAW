<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
</head>
<body>
    <h1>Restablecer Contraseña</h1>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        @method('PUT')

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <label for="email">Correo electrónico:</label>
        <input type="email" name="email" value="{{ old('email') }}" required>

        <label for="password">Nueva contraseña:</label>
        <input type="password" name="password" required>

        <label for="password_confirmation">Confirmar nueva contraseña:</label>
        <input type="password" name="password_confirmation" required>

        <button type="submit">Restablecer</button>
    </form>

    @if ($errors->any())
        <div style="color: red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</body>
</html>
