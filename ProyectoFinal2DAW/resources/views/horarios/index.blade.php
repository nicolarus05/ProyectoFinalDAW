<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Horarios de Trabajo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este horario?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Horarios de Trabajo</h1>

        <div class="mb-4">
            <a href="{{ route('horarios.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Añadir nuevo horario</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-2 border">Empleado</th>
                        <th class="p-2 border">Fecha</th>
                        <th class="p-2 border">Hora Inicio</th>
                        <th class="p-2 border">Hora Fin</th>
                        <th class="p-2 border">Disponible</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($horarios as $horario)
                    <tr class="text-center border-t">
                        <td class="p-2 border">{{ $horario->empleado->user->nombre ?? '-' }} {{ $horario->empleado->user->apellidos ?? '' }}</td>
                        <td class="p-2 border">{{ \Carbon\Carbon::parse($horario->fecha)->format('d/m/Y') }}</td>
                        <td class="p-2 border">{{ $horario->hora_inicio }}</td>
                        <td class="p-2 border">{{ $horario->hora_fin }}</td>
                        <td class="p-2 border">{{ $horario->disponible ? 'Sí' : 'No' }}</td>
                        <td class="p-2 border space-y-1">
                            <a href="{{ route('horarios.show', $horario->id) }}" class="text-blue-600 hover:underline block">Ver</a>
                            <a href="{{ route('horarios.edit', $horario->id) }}" class="text-yellow-600 hover:underline block">Editar</a>
                            <form id="delete-form-{{ $horario->id }}" action="{{ route('horarios.destroy', $horario->id) }}" method="POST" style="display:inline;" onsubmit="return false;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="text-red-600 hover:underline" onclick="confirmarEliminacion({{ $horario->id }})">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <a href="{{ route('dashboard') }}" class="text-blue-600 hover:underline">Volver al Inicio</a>
        </div>
    </div>
</body>
</html>
