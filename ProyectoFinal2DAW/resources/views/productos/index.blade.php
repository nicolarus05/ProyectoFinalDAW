<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold">Productos</h1>
            <a href="{{ route('productos.create') }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Nuevo producto</a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-4 mb-4 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                <strong>Errores encontrados:</strong>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full table-auto text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Nombre</th>
                        <th class="px-4 py-2 text-left">Categoría</th>
                        <th class="px-4 py-2 text-left">Descripción</th>
                        <th class="px-4 py-2 text-left">Precio venta</th>
                        <th class="px-4 py-2 text-left">Precio coste</th>
                        <th class="px-4 py-2 text-left">Stock</th>
                        <th class="px-4 py-2 text-left">Activo</th>
                        <th class="px-4 py-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productos as $producto)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $producto->nombre }}</td>
                            <td class="px-4 py-2">
                                @if($producto->categoria === 'peluqueria')
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded">💇 Peluquería</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-pink-700 bg-pink-100 rounded">💅 Estética</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ Str::limit($producto->descripcion, 80) }}</td>
                            <td class="px-4 py-2">{{ number_format($producto->precio_venta, 2) }} €</td>
                            <td class="px-4 py-2">{{ number_format($producto->precio_coste, 2) }} €</td>
                            <td class="px-4 py-2">{{ $producto->stock }}</td>
                            <td class="px-4 py-2">{{ $producto->activo ? 'Sí' : 'No' }}</td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('productos.edit', $producto) }}" class="text-yellow-600 mr-3">Editar</a>
                                <form action="{{ route('productos.destroy', $producto) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar producto?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-gray-600">No hay productos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{-- Paginación --}}
            @if(method_exists($productos,'links'))
                {{ $productos->links() }}
            @endif
        </div>

        <div class="mt-6">
            <a href="{{ route('dashboard') }}" class="text-blue-600 hover:underline">Volver al inicio</a>
        </div>
    </div>
</body>
</html>
