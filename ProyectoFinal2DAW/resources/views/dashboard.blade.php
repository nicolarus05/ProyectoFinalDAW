<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Control</title>
    @vite(['resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 flex flex-col items-center justify-center text-center p-4">

    <header class="mb-8">
        <h1 class="text-5xl font-extrabold text-black mb-4 transition-all duration-300">
            Panel de Control
        </h1>
        <p class="text-gray-700 text-lg">Gestiona clientes, empleados, citas y más de forma sencilla.</p>
    </header>

    @php
        $user = Auth::user();
        $rol = $user->rol ?? null;
    @endphp

    <div class="flex flex-col items-center gap-4 mb-6">
        <a href="{{ route('profile.edit') }}" title="Editar Perfil" class="flex items-center gap-2">
            @if ($user && $user->foto_perfil)
                <img src="{{ asset('storage/' . $user->foto_perfil) }}"
                    class="w-32 h-32 object-cover rounded-full border-2 border-black shadow">
            @else
                <span class="w-16 h-16 flex items-center justify-center bg-gray-300 rounded-full text-gray-600">Sin foto</span>
            @endif
        </a>
        <p class="text-xl font-semibold">
            Bienvenido, {{ $user->nombre }} {{ $user->apellidos }} <span class="text-gray-500">({{ $user->rol }})</span>
        </p>
    </div>

    <ul class="w-full max-w-md flex flex-col gap-3 mb-8">
        @if ($rol === 'admin')
            <li>
                <a href="{{ route('users.index') }}" class="block bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors font-semibold">Gestionar Usuarios</a>
            </li>
            <li>
                <a href="{{ route('Clientes.index') }}" class="block bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors font-semibold">Gestionar Clientes</a>
            </li>
            <li>
                <a href="{{ route('Empleados.index') }}" class="block bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors font-semibold">Gestionar Empleados</a>
            </li>
            <li>
                <a href="{{ route('Servicios.index') }}" class="block bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors font-semibold">Gestionar Servicios</a>
            </li>
            <li>
                <a href="{{ route('Horarios.index') }}" class="block bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors font-semibold">Gestionar Horarios</a>
            </li>
            <li>
                <a href="{{ route('Cobros.index') }}" class="block bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors font-semibold">Registro de Cobros</a>
            </li>
            <li>
                <a href="{{ route('Citas.index') }}" class="block bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors font-semibold">Todas las Citas</a>
            </li>
        @endif

        @if ($rol === 'empleado')
            <li>
                <a href="{{ route('Citas.index') }}" class="block bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors font-semibold">Mis Citas</a>
            </li>
            <li>
                <a href="{{ route('Servicios.index') }}" class="block bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors font-semibold">Servicios</a>
            </li>
        @endif

        @if ($rol === 'cliente')
            <li>
                <a href="{{ route('Citas.create') }}" class="block bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors font-semibold">Reservar Cita</a>
            </li>
            <li>
                <a href="{{ route('Citas.index') }}" class="block bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors font-semibold">Mis Citas</a>
            </li>
        @endif
    </ul>

    <div class="flex flex-col sm:flex-row gap-4">
        <a href="{{ route('profile.edit') }}"
           class="bg-white text-black px-6 py-3 rounded border border-black hover:bg-gray-200 transition-colors font-semibold">
            Editar Perfil
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition-colors font-semibold">
                Cerrar sesión
            </button>
        </form>
    </div>

</body>
</html>
