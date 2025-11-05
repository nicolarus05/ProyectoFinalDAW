<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Horario de Trabajo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Detalle Horario de Trabajo</h1>

        <div class="space-y-4">
            <div>
                <span class="font-semibold">Empleado:</span>
                <span>{{ $horario->empleado->user->nombre ?? '-' }} {{ $horario->empleado->user->apellidos ?? '' }}</span>
            </div>
            <div>
                <span class="font-semibold">Fecha:</span>
                <span>{{ \Carbon\Carbon::parse($horario->fecha)->format('d/m/Y') }}</span>
            </div>

            <div>
                <span class="font-semibold">Hora de inicio:</span>
                <span>{{ $horario->hora_inicio }}</span>
            </div>
            <div>
                <span class="font-semibold">Hora de fin:</span>
                <span>{{ $horario->hora_fin }}</span>
            </div>
            <div>
                <span class="font-semibold">Disponible:</span>
                <span>{{ $horario->disponible ? 'Sí' : 'No' }}</span>
            </div>
            <div>
                <span class="font-semibold">Notas:</span>
                <p class="mt-1 text-gray-700 bg-gray-50 p-3 rounded border border-gray-200">
                    {{ $horario->notas ?? 'Sin notas' }}
                </p>
            </div>
        </div>

        <div class="mt-6">
            <a href="{{ route('horarios.index') }}" class="text-blue-600 hover:underline">Volver a la lista</a>
        </div>
    </div>
</body>
</html>
