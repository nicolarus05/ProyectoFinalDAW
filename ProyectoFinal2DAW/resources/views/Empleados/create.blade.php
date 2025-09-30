<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Empleado</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-8 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Crear nuevo empleado</h1>

        <form action="{{ route('empleados.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="nombre" class="block font-semibold mb-1">Nombre:</label>
                <input type="text" name="nombre" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label for="apellidos" class="block font-semibold mb-1">Apellidos:</label>
                <input type="text" name="apellidos" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label for="telefono" class="block font-semibold mb-1">Teléfono:</label>
                <input type="text" name="telefono" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label for="email" class="block font-semibold mb-1">Correo electrónico:</label>
                <input type="email" name="email" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label for="password" class="block font-semibold mb-1">Contraseña:</label>
                <input type="password" name="password" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label for="genero" class="block font-semibold mb-1">Género:</label>
                <select name="genero" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Seleccione</option>
                    <option value="Masculino">Masculino</option>
                    <option value="Femenino">Femenino</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>

            <div>
                <label for="edad" class="block font-semibold mb-1">Edad:</label>
                <input type="number" name="edad" min="0" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label for="especializacion" class="block font-semibold mb-1">Especialización:</label>
                <select name="especializacion" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Seleccione</option>
                    <option value="Esteticien">Esteticista</option>
                    <option value="Peluquera">Peluquera</option>
                </select>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 font-semibold">Guardar</button>
            </div>
        </form>

        <div class="mt-6">
            <a href="{{ route('empleados.index') }}" class="text-blue-600 hover:underline">Volver a la lista</a>
        </div>
    </div>
</body>
</html>