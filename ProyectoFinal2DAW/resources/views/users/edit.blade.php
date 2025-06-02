<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-8 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Editar Usuario</h1>

        {{-- Mostrar errores de validación --}}
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('users.update', $user->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- Campos generales -->
            <div>
                <label class="block font-semibold mb-1">Nombre:</label>
                <input type="text" name="nombre" value="{{ old('nombre', $user->nombre) }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label class="block font-semibold mb-1">Apellidos:</label>
                <input type="text" name="apellidos" value="{{ old('apellidos', $user->apellidos) }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label class="block font-semibold mb-1">Email:</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label class="block font-semibold mb-1">Teléfono:</label>
                <input type="text" name="telefono" value="{{ old('telefono', $user->telefono) }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label class="block font-semibold mb-1">Edad:</label>
                <input type="number" name="edad" value="{{ old('edad', $user->edad) }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label class="block font-semibold mb-1">Género:</label>
                <select name="genero" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                    <option value="">-- Selecciona --</option>
                    <option value="Masculino" {{ old('genero', $user->genero) == 'Masculino' ? 'selected' : '' }}>Masculino</option>
                    <option value="Femenino" {{ old('genero', $user->genero) == 'Femenino' ? 'selected' : '' }}>Femenino</option>
                    <option value="Otro" {{ old('genero', $user->genero) == 'Otro' ? 'selected' : '' }}>Otro</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold mb-1">Contraseña (solo si deseas cambiarla):</label>
                <input type="password" name="password" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div>
                <label class="block font-semibold mb-1">Rol:</label>
                <select name="rol" id="rol" onchange="mostrarCamposEspecificos()" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                    <option value="">-- Selecciona un rol --</option>
                    <option value="cliente" {{ old('rol', $user->rol) == 'cliente' ? 'selected' : '' }}>Cliente</option>
                    <option value="empleado" {{ old('rol', $user->rol) == 'empleado' ? 'selected' : '' }}>Empleado</option>
                </select>
            </div>

            <!-- Campos específicos para empleados -->
            <div id="campos-empleado" style="display: none;">
                <label for="especializacion" class="block font-semibold mb-1">Especialización:</label>
                <select name="especializacion" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                    <option value="">-- Seleccione --</option>
                    <option value="Esteticien" {{ old('especializacion', optional($user->empleado)->especializacion) == 'Esteticien' ? 'selected' : '' }}>Esteticista</option>
                    <option value="Peluquera" {{ old('especializacion', optional($user->empleado)->especializacion) == 'Peluquera' ? 'selected' : '' }}>Peluquera</option>
                </select>
            </div>

            <!-- Campos específicos para clientes -->
            <div id="campos-cliente" style="display: none;">
                <div>
                    <label class="block font-semibold mb-1">Dirección:</label>
                    <input type="text" name="direccion" value="{{ old('direccion', optional($user->cliente)->direccion) }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                </div>
                <div>
                    <label class="block font-semibold mb-1">Fecha de Registro:</label>
                    <input type="date" name="fecha_registro" value="{{ old('fecha_registro', optional($user->cliente)->fecha_registro) }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                </div>
                <div>
                    <label class="block font-semibold mb-1">Notas Adicionales:</label>
                    <textarea name="notas_adicionales" rows="4" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">{{ old('notas_adicionales', optional($user->cliente)->notas_adicionales) }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-semibold">Actualizar</button>
                <a href="{{ route('users.index') }}" class="text-blue-600 hover:underline">Volver a la lista</a>
            </div>
        </form>
    </div>

    <script>
        function mostrarCamposEspecificos() {
            const rol = document.getElementById('rol').value;

            const clienteFields = document.getElementById('campos-cliente');
            const empleadoFields = document.getElementById('campos-empleado');

            clienteFields.style.display = 'none';
            empleadoFields.style.display = 'none';

            if (rol === 'cliente') {
                clienteFields.style.display = 'block';
            } else if (rol === 'empleado') {
                empleadoFields.style.display = 'block';
            }
        }

        // Mostrar los campos correspondientes al cargar la página
        document.addEventListener('DOMContentLoaded', function () {
            mostrarCamposEspecificos();
        });
    </script>
</body>
</html>
