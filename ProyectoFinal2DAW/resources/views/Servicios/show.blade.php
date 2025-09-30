<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Servicio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Detalle del Servicio</h1>

        <div class="space-y-4 mb-6">
            <p><span class="font-semibold">Nombre:</span> {{ $servicio->nombre }}</p>
            <p><span class="font-semibold">Precio:</span> {{ $servicio->precio }} €</p>
            <p><span class="font-semibold">Tiempo estimado:</span> {{ $servicio->tiempo_estimado }} minutos</p>
            <p><span class="font-semibold">Tipo:</span> {{ $servicio->tipo }}</p>
        </div>

        <a href="{{ route('servicios.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Volver</a>
    </div>
</body>
</html>
