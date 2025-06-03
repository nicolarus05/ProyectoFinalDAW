<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido al Sistema de Gestión</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 flex flex-col items-center justify-center text-center p-4">

    <header class="mb-8">
        <h1 class="text-5xl font-extrabold text-black mb-4 transition-all duration-300">
            Bienvenido a Nuestro Sistema
        </h1>
        <p class="text-gray-700 text-lg">Gestiona clientes, empleados, citas y más de forma sencilla.</p>
    </header>

    <div class="flex flex-col sm:flex-row gap-4">
        @auth
            <a href="{{ route('dashboard') }}"
               class="bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors duration-300 font-semibold">
                Ir al Panel
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="bg-white text-black px-6 py-3 rounded border border-black hover:bg-gray-200 transition-colors duration-300 font-semibold">
                    Cerrar sesión
                </button>
            </form>
        @else
            <a href="{{ route('login') }}"
               class="bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors duration-300 font-semibold">
                Iniciar Sesión
            </a>

            <a href="{{ route('register.cliente') }}"
               class="bg-white text-black px-6 py-3 rounded border border-black hover:bg-gray-200 transition-colors duration-300 font-semibold">
                Registrarse
            </a>
        @endauth
    </div>
</body>
</html>
