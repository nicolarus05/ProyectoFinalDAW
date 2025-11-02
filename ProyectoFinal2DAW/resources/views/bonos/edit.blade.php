<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Bono</title>
    @vite(['resources/js/app.js'])
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto bg-white shadow-md rounded p-6">
        <h1 class="text-3xl font-bold mb-6">Editar Bono: {{ $plantilla->nombre }}</h1>

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

        <form action="{{ route('bonos.update', $plantilla->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="nombre" class="block font-semibold mb-1">Nombre del Bono</label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $plantilla->nombre) }}" required 
                    class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label for="descripcion" class="block font-semibold mb-1">Descripción</label>
                <textarea name="descripcion" id="descripcion" rows="3" 
                    class="w-full border rounded px-3 py-2">{{ old('descripcion', $plantilla->descripcion) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="precio" class="block font-semibold mb-1">Precio (€)</label>
                    <input type="number" name="precio" id="precio" value="{{ old('precio', $plantilla->precio) }}" step="0.01" min="0" required 
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label for="duracion_tipo" class="block font-semibold mb-1">Duración</label>
                    <select name="duracion_tipo" id="duracion_tipo" required class="w-full border rounded px-3 py-2">
                        <option value="30" {{ old('duracion_tipo', $plantilla->duracion_dias ? '30' : 'sin_limite') == '30' ? 'selected' : '' }}>30 días</option>
                        <option value="sin_limite" {{ old('duracion_tipo', $plantilla->duracion_dias ? '30' : 'sin_limite') == 'sin_limite' ? 'selected' : '' }}>Sin límite</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="activo" value="1" {{ old('activo', $plantilla->activo) ? 'checked' : '' }} class="form-checkbox mr-2">
                    <span>Activo</span>
                </label>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded p-4">
                <p class="text-sm text-gray-700">
                    <strong>Nota:</strong> Los servicios incluidos en este bono no se pueden editar aquí. 
                    Si necesitas cambiar los servicios, crea un nuevo bono.
                </p>
                <div class="mt-3">
                    <p class="font-semibold mb-2">Servicios actuales:</p>
                    <ul class="list-disc pl-5">
                        @foreach($plantilla->servicios as $servicio)
                            <li>{{ $servicio->nombre }} (x{{ $servicio->pivot->cantidad }})</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="flex justify-between items-center mt-6">
                <a href="{{ route('bonos.index') }}" class="text-blue-600 hover:underline">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</body>
</html>
