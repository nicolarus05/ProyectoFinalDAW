<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Servicio</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Crear Servicio</h1>

        <form action="{{ route('servicios.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block font-semibold mb-1">Nombre:</label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" required
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block font-semibold mb-1">Tiempo Estimado (min):</label>
                <input type="number" name="tiempo_estimado" value="{{ old('tiempo_estimado') }}" required
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block font-semibold mb-1">Precio (€):</label>
                <input type="number" step="0.01" name="precio" value="{{ old('precio') }}" required
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block font-semibold mb-1">Tipo:</label>
                <select name="tipo" required class="w-full border rounded px-3 py-2">
                    <option value="peluqueria" {{ old('tipo') == 'peluqueria' ? 'selected' : '' }}>Peluquería</option>
                    <option value="estetica" {{ old('tipo') == 'estetica' ? 'selected' : '' }}>Estética</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold mb-1">Descripción:</label>
                <textarea name="descripcion" class="w-full border rounded px-3 py-2">{{ old('descripcion') }}</textarea>
            </div>

            <div>
                <label class="block font-semibold mb-1">Activo:</label>
                <select name="activo" class="w-full border rounded px-3 py-2">
                    <option value="1" selected>Sí</option>
                    <option value="0">No</option>
                </select>
            </div>

            <div class="flex justify-between items-center mt-6">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Guardar</button>
                <a href="{{ route('servicios.index') }}" class="text-blue-600 hover:underline">Volver</a>
            </div>
        </form>
    </div>
</body>
</html>
