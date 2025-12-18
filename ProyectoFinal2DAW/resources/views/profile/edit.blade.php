<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil</title>
    {!! vite_asset(['resources/css/app.css', 'resources/css/profile.css', 'resources/js/app.js']) !!}
</head>
<body class="min-h-screen bg-gray-100 flex flex-col items-center justify-center p-6">

    <header class="mb-8 text-center">
        <h1 class="text-4xl font-extrabold text-black mb-2">Editar Perfil</h1>
        <p class="text-gray-700 text-lg">Actualiza tu información personal.</p>
    </header>

    {{-- Mensajes de éxito --}}
    @if (session('status') === 'profile-updated')
        <div class="mb-4 px-4 py-2 bg-green-100 text-green-800 rounded border border-green-300 w-full max-w-2xl">
            Perfil actualizado correctamente.
        </div>
    @elseif (session('status') === 'password-updated')
        <div class="mb-4 px-4 py-2 bg-green-100 text-green-800 rounded border border-green-300 w-full max-w-2xl">
            Contraseña actualizada correctamente.
        </div>
    @endif

    {{-- Errores --}}
    @if ($errors->any())
        <div class="mb-4 px-4 py-2 bg-red-100 text-red-800 rounded border border-red-300 w-full max-w-2xl">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="bg-white shadow-md rounded px-8 pt-6 pb-8 w-full max-w-2xl">
        @csrf
        @method('PATCH')

        {{-- Imagen de perfil --}}
        @if ($user->foto_perfil)
            <div class="mb-4">
                <img src="{{ tenant_asset($user->foto_perfil) }}" alt="Foto de perfil" class="foto-perfil-redonda rounded-full mx-auto border-2 border-gray-300" loading="lazy">
            </div>
        @endif

        <div class="mb-4">
            <label for="foto_perfil" class="block text-sm font-semibold mb-1 text-gray-700">Foto de perfil:</label>
            <input type="file" name="foto_perfil" accept="image/*" class="block w-full text-sm text-gray-700">
        </div>

        {{-- Nombre y Apellidos --}}
        <div class="flex flex-col md:flex-row gap-4 mb-4">
            <div class="w-full">
                <label for="nombre" class="block text-sm font-semibold mb-1 text-gray-700">Nombre:</label>
                <input type="text" name="nombre" value="{{ old('nombre', $user->nombre) }}" required
                       class="w-full border border-gray-300 rounded px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <div class="w-full">
                <label for="apellidos" class="block text-sm font-semibold mb-1 text-gray-700">Apellidos:</label>
                <input type="text" name="apellidos" value="{{ old('apellidos', $user->apellidos) }}" required
                       class="w-full border border-gray-300 rounded px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-black">
            </div>
        </div>

        {{-- Teléfono y Edad --}}
        <div class="flex flex-col md:flex-row gap-4 mb-4">
            <div class="w-full">
                <label for="telefono" class="block text-sm font-semibold mb-1 text-gray-700">Teléfono:</label>
                <input type="text" name="telefono" value="{{ old('telefono', $user->telefono) }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <div class="w-full">
                <label for="edad" class="block text-sm font-semibold mb-1 text-gray-700">Edad:</label>
                <input type="number" name="edad" value="{{ old('edad', $user->edad) }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-black">
            </div>
        </div>

        {{-- Email --}}
        <div class="mb-4">
            <label for="email" class="block text-sm font-semibold mb-1 text-gray-700">Correo electrónico:</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                   class="w-full border border-gray-300 rounded px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-black">
        </div>

        {{-- Género --}}
        <div class="mb-4">
            <label for="genero" class="block text-sm font-semibold mb-1 text-gray-700">Género:</label>
            <select name="genero" required
                    class="w-full border border-gray-300 rounded px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-black">
                <option value="">Seleccione</option>
                <option value="masculino" {{ $user->genero == 'masculino' ? 'selected' : '' }}>Masculino</option>
                <option value="femenino" {{ $user->genero == 'femenino' ? 'selected' : '' }}>Femenino</option>
                <option value="otro" {{ $user->genero == 'otro' ? 'selected' : '' }}>Otro</option>
            </select>
        </div>

        {{-- Contraseñas --}}
        <div class="mb-4">
            <label for="current_password" class="block text-sm font-semibold mb-1 text-gray-700">Contraseña Actual:</label>
            <input type="password" name="current_password"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-black">
        </div>

        <div class="flex flex-col md:flex-row gap-4 mb-4">
            <div class="w-full">
                <label for="password" class="block text-sm font-semibold mb-1 text-gray-700">Nueva Contraseña:</label>
                <input type="password" name="password"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            <div class="w-full">
                <label for="password_confirmation" class="block text-sm font-semibold mb-1 text-gray-700">Confirmar Nueva:</label>
                <input type="password" name="password_confirmation"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-black">
            </div>
        </div>

        {{-- Botón --}}
        <div class="mb-4">
            <button type="submit"
                    class="bg-black text-white px-6 py-2 rounded hover:bg-gray-800 transition-colors duration-300 font-semibold w-full">
                Actualizar Perfil
            </button>
        </div>
    </form>

    <div class="text-center mt-4">
        <a href="{{ route('dashboard') }}"
           class="text-black px-4 py-2 rounded border border-black hover:bg-gray-200 transition-colors duration-300 font-semibold">
            Volver al Panel
        </a>
    </div>

</body>
</html>
