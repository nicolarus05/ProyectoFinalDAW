<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-semibold mb-6">Crear nuevo cliente</h1>

        <form action="{{ route('Clientes.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="nombre" class="block font-medium">Nombre:</label>
                    <input type="text" name="nombre" required class="w-full border border-gray-300 rounded p-2">
                </div>

                <div>
                    <label for="apellidos" class="block font-medium">Apellidos:</label>
                    <input type="text" name="apellidos" required class="w-full border border-gray-300 rounded p-2">
                </div>

                <div>
                    <label for="telefono" class="block font-medium">Teléfono:</label>
                    <input type="text" name="telefono" required class="w-full border border-gray-300 rounded p-2">
                </div>

                <div>
                    <label for="email" class="block font-medium">Correo electrónico:</label>
                    <input type="email" name="email" required class="w-full border border-gray-300 rounded p-2">
                </div>

                <div>
                    <label for="password" class="block font-medium">Contraseña:</label>
                    <input type="password" name="password" required class="w-full border border-gray-300 rounded p-2">
                </div>

                <div>
                    <label for="genero" class="block font-medium">Género:</label>
                    <select name="genero" required class="w-full border border-gray-300 rounded p-2">
                        <option value="">Seleccione</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <div>
                    <label for="edad" class="block font-medium">Edad:</label>
                    <input type="number" name="edad" min="0" required class="w-full border border-gray-300 rounded p-2">
                </div>

                <div>
                    <label for="direccion" class="block font-medium">Dirección:</label>
                    <input type="text" name="direccion" required class="w-full border border-gray-300 rounded p-2">
                </div>
            </div>

            <div>
                <label for="notas_adicionales" class="block font-medium">Notas Adicionales:</label>
                <textarea name="notas_adicionales" class="w-full border border-gray-300 rounded p-2"></textarea>
            </div>

            <div>
                <label for="fecha_registro" class="block font-medium">Fecha de Registro:</label>
                <input type="date" name="fecha_registro" value="{{ date('Y-m-d') }}" required class="w-full border border-gray-300 rounded p-2">
            </div>

            <div class="flex justify-between mt-6">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Guardar</button>
                <a href="{{ route('Clientes.index') }}" class="text-blue-600 hover:underline">Volver a la lista</a>
            </div>
        </form>
    </div>
</body>
</html>
