<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Editar Horario de Trabajo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Editar Horario de Trabajo</h1>

        <form action="{{ route('horarios.update', $horario->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="id_empleado" class="block font-semibold mb-1">Empleado:</label>
                <select name="id_empleado" required class="w-full border border-gray-300 rounded px-3 py-2">
                    @foreach($empleados as $empleado)
                        <option value="{{ $empleado->id }}" {{ $horario->id_empleado == $empleado->id ? 'selected' : '' }}>
                            {{ $empleado->user->nombre ?? '' }} {{ $empleado->user->apellidos ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="fecha" class="block font-semibold mb-1">Fecha:</label>
                <input type="date" name="fecha" id="fecha" value="{{ $horario->fecha }}" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>


            <div>
                <label for="hora_inicio" class="block font-semibold mb-1">Hora inicio:</label>
                <input type="time" name="hora_inicio" value="{{ $horario->hora_inicio }}" required class="w-full border border-gray-300 rounded px-3 py-2" />
            </div>

            <div>
                <label for="hora_fin" class="block font-semibold mb-1">Hora fin:</label>
                <input type="time" name="hora_fin" value="{{ $horario->hora_fin }}" required class="w-full border border-gray-300 rounded px-3 py-2" />
            </div>

            <input type="hidden" name="disponible" value="0" />
            <div class="flex items-center">
                <input type="checkbox" name="disponible" value="1" id="disponible" {{ $horario->disponible ? 'checked' : '' }} class="mr-2" />
                <label for="disponible" class="font-semibold">Disponible</label>
            </div>

            <div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Actualizar</button>
            </div>
        </form>

        <div class="mt-6">
            <a href="{{ route('horarios.index') }}" class="text-blue-600 hover:underline">Volver a la lista</a>
        </div>
    </div>
</body>
</html>
