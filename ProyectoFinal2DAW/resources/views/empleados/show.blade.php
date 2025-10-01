<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Empleado</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Detalles del empleado</h1>

        <ul class="divide-y divide-gray-200 mb-6">
            <li class="py-2 flex justify-between">
                <span class="font-semibold">Nombre:</span>
                <span>{{ $empleado->user->nombre }}</span>
            </li>
            <li class="py-2 flex justify-between">
                <span class="font-semibold">Apellidos:</span>
                <span>{{ $empleado->user->apellidos ?? '-' }}</span>
            </li>
            <li class="py-2 flex justify-between">
                <span class="font-semibold">Teléfono:</span>
                <span>{{ $empleado->user->telefono ?? '-' }}</span>
            </li>
            <li class="py-2 flex justify-between">
                <span class="font-semibold">Email:</span>
                <span>{{ $empleado->user->email }}</span>
            </li>
            <li class="py-2 flex justify-between">
                <span class="font-semibold">Género:</span>
                <span>{{ $empleado->user->genero ?? '-' }}</span>
            </li>
            <li class="py-2 flex justify-between">
                <span class="font-semibold">Edad:</span>
                <span>{{ $empleado->user->edad ?? '-' }}</span>
            </li>
            <li class="py-2 flex justify-between">
                <span class="font-semibold">Especialización:</span>
                <span>{{ $empleado->especializacion ?? '-' }}</span>
            </li>
        </ul>

        <div class="flex space-x-4">
            <a href="{{ route('empleados.edit', $empleado->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Editar</a>
            <a href="{{ route('empleados.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Volver a la lista</a>
        </div>
    </div>
</body>
</html>