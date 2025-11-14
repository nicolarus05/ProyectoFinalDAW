<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!} <!-- Asegúrate de tener Tailwind incluido -->
</head>
<body class="min-h-screen bg-gray-100 flex flex-col items-center justify-center p-4">

    <h1 class="text-3xl font-bold text-black mb-6">Restablecer Contraseña</h1>

    <form method="POST" action="{{ route('password.store') }}"
          class="bg-white shadow-lg rounded-lg p-8 w-full max-w-md space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ request()->route('token') }}">
        <input type="hidden" name="token" value="{{ $token ?? '' }}">

        <div>
            <label for="email" class="block text-gray-700 font-semibold mb-1">Correo electrónico:</label>
            <input type="email" name="email" value="{{ old('email', $request->email) }}" required
                   class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-black">
        </div>

        <div>
            <label for="password" class="block text-gray-700 font-semibold mb-1">Nueva contraseña:</label>
            <div class="flex items-center">
                <input type="password" name="password" id="password" required
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-black">
                <button type="button"
                        onclick="verContraseña('password', this)"
                        class="ml-2 px-3 py-2 bg-gray-200 rounded text-sm">
                    Ver
                </button>
            </div>
        </div>

        <div>
            <label for="password_confirmation" class="block text-gray-700 font-semibold mb-1">Confirmar nueva contraseña:</label>
            <div class="flex items-center">
                <input type="password" name="password_confirmation" id="password_confirmation" required
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-black">
                <button type="button"
                        onclick="verContraseña('password_confirmation', this)"
                        class="ml-2 px-3 py-2 bg-gray-200 rounded text-sm">
                    Ver
                </button>
            </div>
        </div>

        <button type="submit"
                class="w-full bg-black text-white py-2 rounded font-semibold hover:bg-gray-800 transition-colors duration-300">
            Restablecer
        </button>
    </form>

    @if (session('success'))
        <div class="mt-4 bg-green-100 text-green-700 px-4 py-3 rounded w-full max-w-md">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-4 bg-red-100 text-red-700 px-4 py-3 rounded w-full max-w-md">
            <ul class="list-disc list-inside">
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
