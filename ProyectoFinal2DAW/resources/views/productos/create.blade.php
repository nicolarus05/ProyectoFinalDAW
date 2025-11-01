<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Crear Producto</h1>

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

        <form action="{{ route('productos.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="nombre" class="block font-semibold mb-1">Nombre</label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" required class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label for="categoria" class="block font-semibold mb-1">Categoría</label>
                <select name="categoria" id="categoria" required class="w-full border rounded px-3 py-2">
                    <option value="">Seleccionar categoría</option>
                    <option value="peluqueria" {{ old('categoria') == 'peluqueria' ? 'selected' : '' }}>💇 Peluquería</option>
                    <option value="estetica" {{ old('categoria') == 'estetica' ? 'selected' : '' }}>💅 Estética</option>
                </select>
            </div>

            <div>
                <label for="descripcion" class="block font-semibold mb-1">Descripción</label>
                <textarea name="descripcion" id="descripcion" rows="4" class="w-full border rounded px-3 py-2">{{ old('descripcion') }}</textarea>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label for="precio_venta" class="block font-semibold mb-1">Precio venta (€)</label>
                    <input type="number" name="precio_venta" id="precio_venta" value="{{ old('precio_venta', '0.00') }}" step="0.01" min="0" required class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label for="precio_coste" class="block font-semibold mb-1">Precio coste (€)</label>
                    <input type="number" name="precio_coste" id="precio_coste" value="{{ old('precio_coste', '0.00') }}" step="0.01" min="0" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label for="stock" class="block font-semibold mb-1">Stock</label>
                    <input type="number" name="stock" id="stock" value="{{ old('stock', 0) }}" min="0" required class="w-full border rounded px-3 py-2">
                </div>
            </div>

            <div>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="activo" value="1" {{ old('activo', true) ? 'checked' : '' }} class="form-checkbox mr-2">
                    <span>Activo</span>
                </label>
            </div>

            <div class="flex justify-between items-center mt-6">
                <a href="{{ route('productos.index') }}" class="text-blue-600 hover:underline">Volver</a>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Crear producto</button>
            </div>
        </form>
    </div>
</body>
</html>
