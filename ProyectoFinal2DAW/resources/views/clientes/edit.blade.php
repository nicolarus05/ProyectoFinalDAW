<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-semibold mb-6">Editar cliente</h1>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <strong>Error:</strong>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('clientes.update', $cliente->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="nombre" class="block font-medium">Nombre:</label>
                    <input type="text" name="nombre" value="{{ $cliente->user->nombre }}" required class="w-full border border-gray-300 rounded p-2">
                </div>

                <div>
                    <label for="apellidos" class="block font-medium">Apellidos:</label>
                    <input type="text" name="apellidos" value="{{ $cliente->user->apellidos }}" class="w-full border border-gray-300 rounded p-2">
                </div>

                <div>
                    <label for="telefono" class="block font-medium">Teléfono:</label>
                    <input type="text" name="telefono" value="{{ $cliente->user->telefono }}" class="w-full border border-gray-300 rounded p-2">
                </div>

                <div>
                    <label for="email" class="block font-medium">Email:</label>
                    <input type="email" name="email" value="{{ $cliente->user->email }}" class="w-full border border-gray-300 rounded p-2">
                </div>

                <div>
                    <label for="genero" class="block font-medium">Género:</label>
                    <input type="text" name="genero" value="{{ $cliente->user->genero }}" class="w-full border border-gray-300 rounded p-2">
                </div>

                <div>
                    <label for="edad" class="block font-medium">Edad:</label>
                    <input type="number" name="edad" value="{{ $cliente->user->edad }}" class="w-full border border-gray-300 rounded p-2">
                </div>

                <div>
                    <label for="direccion" class="block font-medium">Dirección:</label>
                    <input type="text" name="direccion" value="{{ $cliente->direccion }}" class="w-full border border-gray-300 rounded p-2">
                </div>

                <div>
                    <label for="fecha_registro" class="block font-medium">Fecha de Registro:</label>
                    <input type="text" name="fecha_registro" value="{{ $cliente->fecha_registro }}" readonly class="w-full border border-gray-300 rounded p-2 bg-gray-100">
                </div>
            </div>

            <div>
                <label for="notas_adicionales" class="block font-medium">Notas Adicionales:</label>
                <textarea name="notas_adicionales" class="w-full border border-gray-300 rounded p-2">{{ $cliente->notas_adicionales }}</textarea>
            </div>

            <div>
                <label for="password" class="block font-medium">Contraseña (dejar en blanco para no cambiar):</label>
                <input type="password" name="password" class="w-full border border-gray-300 rounded p-2">
            </div>

            <div class="flex justify-between mt-6">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Actualizar</button>
                <a href="{{ route('clientes.index') }}" class="text-blue-600 hover:underline">Volver a la lista</a>
            </div>
        </form>
    </div>
</body>
</html>
