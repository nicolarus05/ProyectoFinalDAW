<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Iniciar Sesión</h1>
    
    @if (session('status'))
    <div class="alert-success">
        {{ session('status') }}
    </div>
    @endif
    
    <form method="POST" action="{{ route('login') }}">
        @csrf
        
        <label for="email">Correo electrónico:</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email')
        <div class="error">{{ $message }}</div>
        @enderror
        
        <label for="password">Contraseña:</label>
        <input id="password" type="password" name="password" required>
        <button type="button" onclick="verContraseña('password', this)" style="margin-left: 5px;">Ver</button>
        @error('password')
        <div class="error">{{ $message }}</div>
        @enderror
        
        <div class="acciones">
            <button type="submit">Entrar</button>
            
            @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}">¿Has olvidado tu contraseña?</a>
            @endif
        </div>
    </form>
    
    <a href="{{ route('register') }}">¿No tienes cuenta? Regístrate</a>
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
