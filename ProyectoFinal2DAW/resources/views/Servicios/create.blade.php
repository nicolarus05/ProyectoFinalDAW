<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Servicio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-lg mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Añadir nuevo servicio</h1>

        <form action="{{ route('Servicios.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block font-semibold mb-1">Nombre:</label>
                <input type="text" name="nombre" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label class="block font-semibold mb-1">Precio (€):</label>
                <input type="number" name="precio" step="0.01" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label class="block font-semibold mb-1">Tiempo estimado (minutos):</label>
                <input type="number" name="tiempo_estimado" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label class="block font-semibold mb-1">Tipo:</label>
                <select name="tipo" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Seleccione</option>
                    <option value="Peluqueria">Peluqueria</option>
                    <option value="Estetica">Estetica</option>
                </select>
            </div>

            <div class="flex justify-between items-center">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Guardar</button>
                <a href="{{ route('Servicios.index') }}" class="text-blue-600 hover:underline">Volver</a>
            </div>
        </form>
    </div>
</body>
</html>
