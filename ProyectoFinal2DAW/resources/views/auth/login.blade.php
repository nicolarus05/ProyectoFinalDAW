<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    @vite(['resources/js/app.js', 'resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-100 flex flex-col items-center justify-center p-4">

    <header class="mb-8 text-center">
        <h1 class="text-4xl font-extrabold text-black mb-2">Iniciar Sesión</h1>
        <p class="text-gray-700 text-lg">Accede a tu cuenta para gestionar el sistema.</p>
    </header>

    @if (session('status'))
        <div class="mb-4 px-4 py-2 bg-green-100 text-green-800 rounded border border-green-300">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4 w-full max-w-md">
        @csrf

        <div class="mb-4">
            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Correo electrónico:</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            @error('email')
                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Contraseña:</label>
            <div class="flex items-center">
                <input id="password" type="password" name="password" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <button type="button" onclick="verContraseña('password', this)"
                        class="ml-2 px-3 py-1 bg-gray-200 rounded text-sm hover:bg-gray-300 transition-colors duration-200">
                    Ver
                </button>
            </div>
            @error('password')
                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div class="flex items-center justify-between mb-4">
            <button type="submit"
                    class="bg-black text-white px-6 py-2 rounded hover:bg-gray-800 transition-colors duration-300 font-semibold w-full">
                Entrar
            </button>
        </div>

        @if (Route::has('password.request'))
            <div class="mb-2 text-center">
                <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:underline">
                    ¿Has olvidado tu contraseña?
                </a>
            </div>
        @endif
    </form>

    <div class="text-center">
        <a href="{{ route('register') }}"
           class="text-black px-4 py-2 rounded border border-black hover:bg-gray-200 transition-colors duration-300 font-semibold">
            ¿No tienes cuenta? Regístrate
        </a>
    </div>

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
