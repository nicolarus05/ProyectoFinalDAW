<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Servicio</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">{{ $servicio->nombre }}</h1>

        <p><strong>Tiempo Estimado:</strong> {{ $servicio->tiempo_estimado }} min</p>
        <p><strong>Precio:</strong> {{ $servicio->precio }} €</p>
        <p><strong>Tipo:</strong> {{ $servicio->tipo }}</p>
        <p><strong>Descripción:</strong> {{ $servicio->descripcion ?? 'Sin descripción' }}</p>
        <p><strong>Activo:</strong> {{ $servicio->activo ? 'Sí' : 'No' }}</p>

        <div class="flex justify-between items-center mt-6">
            <a href="{{ route('servicios.edit', $servicio) }}" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">Editar</a>
            <a href="{{ route('servicios.index') }}" class="text-blue-600 hover:underline">Volver</a>
        </div>
    </div>
</body>
</html>
