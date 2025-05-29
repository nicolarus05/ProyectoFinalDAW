<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
</head>
<body>
    <h1>Restablecer Contraseña</h1>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ request()->route('token') }}">
        <input type="hidden" name="token" value="{{ $token ?? '' }}">

        <label for="email">Correo electrónico:</label>
        <input type="email" name="email" value="{{ old('email', $request->email) }}" required>

        <label for="password">Nueva contraseña:</label>
        <input type="password" name="password" id="password" required>
        <button type="button" onclick="verContraseña('password', this)" style="margin-left: 5px;">Ver</button>


        <label for="password_confirmation">Confirmar nueva contraseña:</label>
        <input type="password" name="password_confirmation" id="password_confirmation" required>
        <button type="button" onclick="verContraseña('password_confirmation', this)" style="margin-left: 5px;">Ver</button>

        <br>
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

    <script>
        function verContraseña(fieldId, btn) {
            const input = document.getElementById(fieldId);
            if (input.type === "password") {
                input.type = "text";
                btn.textContent = "Ocultar";
            } else {
                input.type = "password";
                btn.textContent = "Ver";
            }
        }
    </script>
</body>
</html>
