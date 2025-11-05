<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del usuario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Detalles del usuario</h1>

        <div class="space-y-3">
            <div>
                <span class="font-semibold">Nombre:</span>
                <span>{{ $user->nombre }}</span>
            </div>
            <div>
                <span class="font-semibold">Apellidos:</span>
                <span>{{ $user->apellidos }}</span>
            </div>
            <div>
                <span class="font-semibold">Email:</span>
                <span>{{ $user->email }}</span>
            </div>
            <div>
                <span class="font-semibold">Teléfono:</span>
                <span>{{ $user->telefono ?? 'No especificado' }}</span>
            </div>
            <div>
                <span class="font-semibold">Edad:</span>
                <span>{{ $user->edad ?? 'No especificada' }}</span>
            </div>
            <div>
                <span class="font-semibold">Género:</span>
                <span>{{ $user->genero ?? 'No especificado' }}</span>
            </div>
            <div>
                <span class="font-semibold">Rol:</span>
                <span>{{ ucfirst($user->rol) }}</span>
            </div>
        </div>

        {{-- Mostrar campos específicos según el rol --}}
        @if ($user->rol === 'empleado' && $user->empleado)
            <div class="mt-6">
                <h3 class="text-xl font-semibold mb-2">Datos del empleado</h3>
                <div>
                    <span class="font-semibold">Especialización:</span>
                    <span>{{ ucfirst($user->empleado->categoria) ?? 'No especificada' }}</span>
                </div>
            </div>
        @elseif ($user->rol === 'cliente' && $user->cliente)
            <div class="mt-6">
                <h3 class="text-xl font-semibold mb-2">Datos del cliente</h3>
                <div>
                    <span class="font-semibold">Dirección:</span>
                    <span>{{ $user->cliente->direccion ?? 'No especificada' }}</span>
                </div>
                <div>
                    <span class="font-semibold">Fecha de Registro:</span>
                    <span>{{ $user->cliente->fecha_registro ?? 'No especificada' }}</span>
                </div>
                <div>
                    <span class="font-semibold">Notas Adicionales:</span>
                    <span>{{ $user->cliente->notas_adicionales ?? 'Ninguna' }}</span>
                </div>
            </div>
        @endif

        <div class="flex space-x-4 mt-8">
            <a href="{{ route('users.index') }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">Volver a la lista</a>
            <a href="{{ route('users.edit', $user->id) }}" class="bg-yellow-400 text-white px-4 py-2 rounded hover:bg-yellow-500">Editar</a>
        </div>
    </div>
</body>
</html>
