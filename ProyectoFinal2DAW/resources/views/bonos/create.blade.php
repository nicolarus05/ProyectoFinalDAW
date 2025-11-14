<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Bono</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
        .btn-add-servicio {
            background-color: #2563eb;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .btn-add-servicio:hover {
            background-color: #1d4ed8;
        }
        .btn-submit {
            background-color: #16a34a;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .btn-submit:hover {
            background-color: #15803d;
        }
        .btn-remove {
            background-color: #dc2626;
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-remove:hover {
            background-color: #b91c1c;
        }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto bg-white shadow-md rounded p-6">
        <h1 class="text-3xl font-bold mb-6">Crear Plantilla de Bono</h1>

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

        <form action="{{ route('bonos.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label for="nombre" class="block font-semibold mb-1">Nombre del Bono</label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" required 
                    class="w-full border rounded px-3 py-2" placeholder="Ej: Bono Peluquería Básico">
            </div>

            <div>
                <label for="descripcion" class="block font-semibold mb-1">Descripción</label>
                <textarea name="descripcion" id="descripcion" rows="3" 
                    class="w-full border rounded px-3 py-2" placeholder="Descripción opcional del bono">{{ old('descripcion') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="precio" class="block font-semibold mb-1">Precio (€)</label>
                    <input type="number" name="precio" id="precio" value="{{ old('precio') }}" step="0.01" min="0" required 
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label for="duracion_tipo" class="block font-semibold mb-1">Duración</label>
                    <select name="duracion_tipo" id="duracion_tipo" required class="w-full border rounded px-3 py-2">
                        <option value="30" {{ old('duracion_tipo') == '30' ? 'selected' : '' }}>30 días</option>
                        <option value="sin_limite" {{ old('duracion_tipo') == 'sin_limite' ? 'selected' : '' }}>Sin límite</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-semibold mb-3">Servicios Incluidos</label>
                <div id="servicios-container" class="space-y-3">
                    <!-- Los servicios se añadirán dinámicamente aquí -->
                </div>
                <button type="button" id="add-servicio" class="btn-add-servicio mt-3">
                    ➕ Añadir Servicio
                </button>
            </div>

            <div class="flex justify-between items-center mt-6">
                <a href="{{ route('bonos.index') }}" class="text-blue-600 hover:underline" style="color: #2563eb; text-decoration: underline;">
                    ← Cancelar
                </a>
                <button type="submit" class="btn-submit">
                    ✅ Crear Bono
                </button>
            </div>
        </form>
    </div>

    <script>
        const servicios = @json($servicios);
        let servicioIndex = 0;

        document.getElementById('add-servicio').addEventListener('click', function() {
            const container = document.getElementById('servicios-container');
            const div = document.createElement('div');
            div.className = 'flex gap-3 items-end';
            div.innerHTML = `
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1">Servicio</label>
                    <select name="servicios[${servicioIndex}][id]" required class="w-full border rounded px-3 py-2">
                        <option value="">Seleccionar servicio</option>
                        ${servicios.map(s => `<option value="${s.id}">${s.categoria === 'peluqueria' ? '✂️' : '💅'} ${s.nombre}</option>`).join('')}
                    </select>
                </div>
                <div class="w-24">
                    <label class="block text-sm font-medium mb-1">Cantidad</label>
                    <input type="number" name="servicios[${servicioIndex}][cantidad]" min="1" value="1" required 
                        class="w-full border rounded px-3 py-2">
                </div>
                <button type="button" class="btn-remove remove-servicio">
                    🗑️ Eliminar
                </button>
            `;
            container.appendChild(div);
            servicioIndex++;

            // Añadir evento para eliminar
            div.querySelector('.remove-servicio').addEventListener('click', function() {
                div.remove();
            });
        });

        // Añadir al menos un servicio por defecto
        document.getElementById('add-servicio').click();
    </script>
</body>
</html>
