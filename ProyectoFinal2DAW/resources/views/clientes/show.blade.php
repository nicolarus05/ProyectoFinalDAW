<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-semibold mb-6">Detalles del cliente</h1>
        <ul class="space-y-3">
            <li>
                <span class="font-medium">Nombre:</span>
                <span class="ml-2">{{ $cliente->user->nombre }}</span>
            </li>
            <li>
                <span class="font-medium">Apellidos:</span>
                <span class="ml-2">{{ $cliente->user->apellidos ?? '-' }}</span>
            </li>
            <li>
                <span class="font-medium">Teléfono:</span>
                <span class="ml-2">{{ $cliente->user->telefono ?? '-' }}</span>
            </li>
            <li>
                <span class="font-medium">Email:</span>
                <span class="ml-2">{{ $cliente->user->email }}</span>
            </li>
            <li>
                <span class="font-medium">Género:</span>
                <span class="ml-2">{{ $cliente->user->genero ?? '-' }}</span>
            </li>
            <li>
                <span class="font-medium">Edad:</span>
                <span class="ml-2">{{ $cliente->user->edad ?? '-' }}</span>
            </li>
            <li>
                <span class="font-medium">Dirección:</span>
                <span class="ml-2">{{ $cliente->direccion ?? '-' }}</span>
            </li>
            <li>
                <span class="font-medium">Notas Adicionales:</span>
                <span class="ml-2">{{ $cliente->notas_adicionales ?? '-' }}</span>
            </li>
            <li>
                <span class="font-medium">Fecha de Registro:</span>
                <span class="ml-2">{{ $cliente->created_at ? $cliente->created_at->format('d/m/Y') : '-' }}</span>
            </li>
        </ul>
        <div class="flex justify-between mt-8">
            <a href="{{ route('clientes.edit', $cliente->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Editar</a>
            <a href="{{ route('clientes.index') }}" class="text-blue-600 hover:underline">Volver a la lista</a>
        </div>
    </div>
</body>
</html>
