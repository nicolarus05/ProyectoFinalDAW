<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empleado</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-8 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Editar empleado</h1>

        <form action="{{ route('Empleados.update', $empleado->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="nombre" class="block font-semibold mb-1">Nombre:</label>
                <input type="text" name="nombre" value="{{ $empleado->user->nombre }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label for="apellidos" class="block font-semibold mb-1">Apellidos:</label>
                <input type="text" name="apellidos" value="{{ $empleado->user->apellidos }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label for="telefono" class="block font-semibold mb-1">Teléfono:</label>
                <input type="text" name="telefono" value="{{ $empleado->user->telefono }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label for="email" class="block font-semibold mb-1">Email:</label>
                <input type="email" name="email" value="{{ $empleado->user->email }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label for="genero" class="block font-semibold mb-1">Género:</label>
                <input type="text" name="genero" value="{{ $empleado->user->genero }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label for="edad" class="block font-semibold mb-1">Edad:</label>
                <input type="number" name="edad" value="{{ $empleado->user->edad }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label for="especializacion" class="block font-semibold mb-1">Especialización:</label>
                <select name="especializacion" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Seleccione</option>
                    <option value="Esteticien" {{ $empleado->especializacion == 'Esteticien' ? 'selected' : '' }}>Esteticista</option>
                    <option value="Peluquera" {{ $empleado->especializacion == 'Peluquera' ? 'selected' : '' }}>Peluquera</option>
                </select>
            </div>

            <div>
                <label for="password" class="block font-semibold mb-1">Contraseña (dejar en blanco para no cambiar):</label>
                <input type="password" name="password" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div class="flex justify-between items-center mt-6">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-semibold">Actualizar</button>
                <a href="{{ route('Empleados.index') }}" class="text-blue-600 hover:underline">Volver a la lista</a>
            </div>
        </form>
    </div>
</body>
</html>