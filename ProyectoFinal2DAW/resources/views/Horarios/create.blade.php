<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Horario de Trabajo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Crear Horario de Trabajo</h1>

        <form action="{{ route('Horarios.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="id_empleado" class="block font-semibold mb-1">Empleado:</label>
                <select name="id_empleado" id="id_empleado" required class="w-full border border-gray-300 rounded px-3 py-2">
                    <option value="">Seleccione un empleado</option>
                    @foreach($empleados as $empleado)
                        <option value="{{ $empleado->id }}">
                            {{ $empleado->user->nombre ?? '-' }} {{ $empleado->user->apellidos ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="dia_semana" class="block font-semibold mb-1">Día de la semana:</label>
                <select name="dia_semana" id="dia_semana" required class="w-full border border-gray-300 rounded px-3 py-2">
                    <option value="">Seleccione un día</option>
                    <option value="lunes">Lunes</option>
                    <option value="martes">Martes</option>
                    <option value="miércoles">Miércoles</option>
                    <option value="jueves">Jueves</option>
                    <option value="viernes">Viernes</option>
                    <option value="sábado">Sábado</option>
                </select>
            </div>

            <div>
                <label for="hora_inicio" class="block font-semibold mb-1">Hora de inicio:</label>
                <input type="time" name="hora_inicio" id="hora_inicio" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div>
                <label for="hora_fin" class="block font-semibold mb-1">Hora de fin:</label>
                <input type="time" name="hora_fin" id="hora_fin" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="disponible" id="disponible" value="1" class="mr-2">
                <label for="disponible" class="font-semibold">Disponible</label>
            </div>

            <div class="pt-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Guardar</button>
            </div>
        </form>

        <div class="mt-6">
            <a href="{{ route('Horarios.index') }}" class="text-blue-600 hover:underline">Volver a la lista</a>
        </div>
    </div>
</body>
</html>
