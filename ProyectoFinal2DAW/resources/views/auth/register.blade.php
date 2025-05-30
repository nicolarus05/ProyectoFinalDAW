<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Cliente</title>
    @vite(['resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 flex flex-col items-center justify-center p-4">

    <h1 class="text-4xl font-extrabold text-black mb-6">Registro de Cliente</h1>

    <form method="POST" action="{{ route('register.cliente.store') }}"
        class="bg-white shadow-lg rounded-lg p-8 w-full max-w-2xl space-y-5">
        @csrf

        <div class="flex flex-col md:flex-row gap-4">
            <div class="w-full md:w-1/2">
                <label class="block text-gray-700 font-semibold mb-1">Nombre:</label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" required
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-black">
            </div>
            <div class="w-full md:w-1/2">
                <label class="block text-gray-700 font-semibold mb-1">Apellidos:</label>
                <input type="text" name="apellidos" value="{{ old('apellidos') }}" required
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-black">
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-4">
            <div class="w-full md:w-1/2">
                <label class="block text-gray-700 font-semibold mb-1">Teléfono:</label>
                <input type="text" name="telefono" value="{{ old('telefono') }}" required
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-black">
            </div>
            <div class="w-full md:w-1/2">
                <label class="block text-gray-700 font-semibold mb-1">Email:</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-black">
            </div>
        </div>

        <div>
            <label class="block text-gray-700 font-semibold mb-1">Contraseña:</label>
            <div class="flex items-center">
                <input type="password" name="password" id="miPassword" required
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-black">
                <button type="button" class="ml-2 px-3 py-2 bg-gray-200 rounded ver-btn" data-target="miPassword">
                    Ver
                </button>
            </div>
        </div>

        <div>
            <label class="block text-gray-700 font-semibold mb-1">Confirmar contraseña:</label>
            <input type="password" name="password_confirmation" required
                class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-black">
        </div>

        <div class="flex flex-col md:flex-row gap-4">
            <div class="w-full md:w-1/2">
                <label class="block text-gray-700 font-semibold mb-1">Género:</label>
                <select name="genero" required
                        class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="">Selecciona</option>
                    <option value="masculino" {{ old('genero') == 'masculino' ? 'selected' : '' }}>Masculino</option>
                    <option value="femenino" {{ old('genero') == 'femenino' ? 'selected' : '' }}>Femenino</option>
                    <option value="otro" {{ old('genero') == 'otro' ? 'selected' : '' }}>Otro</option>
                </select>
            </div>
            <div class="w-full md:w-1/2">
                <label class="block text-gray-700 font-semibold mb-1">Edad:</label>
                <input type="number" name="edad" min="0" max="120" value="{{ old('edad') }}" required
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-black">
            </div>
        </div>

        <div>
            <label class="block text-gray-700 font-semibold mb-1">Dirección:</label>
            <input type="text" name="direccion" value="{{ old('direccion') }}" required
                class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-black">
        </div>

        <div>
            <label class="block text-gray-700 font-semibold mb-1">Notas adicionales:</label>
            <textarea name="notas_adicionales"
                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-black">{{ old('notas_adicionales') }}</textarea>
        </div>

        <div>
            <label class="block text-gray-700 font-semibold mb-1">Fecha de registro:</label>
            <input type="date" name="fecha_registro" value="{{ old('fecha_registro', date('Y-m-d')) }}" required
                class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-black">
        </div>

        <button type="submit"
                class="w-full bg-black text-white py-2 rounded font-semibold hover:bg-gray-800 transition-colors duration-300">
            Registrarse
        </button>
    </form>


    <a href="{{ route('login') }}"
       class="mt-6 text-black underline hover:text-gray-700 transition-colors duration-300">
        ¿Ya tienes cuenta? Inicia sesión
    </a>
    <script src="{{ asset('js/boton.js') }}" defer></script>
</body>
</html>
