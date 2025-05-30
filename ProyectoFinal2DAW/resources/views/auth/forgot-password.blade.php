<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    @vite(['resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100">
    
    
    <div class="flex flex-col items-center w-full">
        <div class="max-w-md w-full bg-white p-8 rounded shadow-md">
            @if (session('status'))
                <div class="flex justify-center">
                    <div class="mt-4 bg-green-100 text-green-700 px-4 py-3 rounded w-full max-w-md text-center">
                        {{ session('status') }}
                    </div>
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
            
            <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                @csrf
                <h2 class="text-2xl font-bold text-gray-900 mb-4">¿Has olvidado tu contraseña?</h2>
                <p class="text-gray-600 mb-4">Introduce tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>
                <div class="mb-4 text-left">
                    <label for="email" class="block text-gray-700 font-semibold mb-2">Correo electrónico:</label>
                    <input type="email" name="email" id="email" required
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-black transition"/>
                    @error('email')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit"
                        class="w-full bg-black text-white py-2 rounded hover:bg-gray-800 transition-colors duration-300 font-semibold">
                    Enviar enlace de restablecimiento
                </button>
            </form>
        </div>

        <a href="{{ route('login') }}" class="mt-4 inline-block px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition-colors duration-300 font-semibold text-center">
            Volver al inicio de sesión
        </a>
    </div>

</body>
</html>
