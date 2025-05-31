<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de la Cita</title>
    @vite(['resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 flex flex-col items-center justify-center p-6">

    <div class="bg-white rounded-lg shadow-md w-full max-w-2xl p-8">
        <h1 class="text-3xl font-bold text-center text-black mb-6">Detalle de la Cita</h1>

        <div class="space-y-4 text-gray-800 text-base">
            <p><strong class="font-semibold">Cliente:</strong> {{ $cita->cliente->user->nombre }} {{ $cita->cliente->user->apellidos }}</p>
            <p><strong class="font-semibold">Empleado:</strong> {{ $cita->empleado->user->nombre }} {{ $cita->empleado->user->apellidos }}</p>

            <div>
                <strong class="font-semibold">Servicios:</strong>
                @if ($cita->servicios && count($cita->servicios))
                    <ul class="list-disc list-inside mt-1">
                        @foreach ($cita->servicios as $servicio)
                            <li>{{ $servicio->nombre }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-600 mt-1">No hay servicios asociados a esta cita.</p>
                @endif
            </div>

            <p><strong class="font-semibold">Fecha y Hora:</strong> {{ $cita->fecha_hora }}</p>
            <p><strong class="font-semibold">Estado:</strong> {{ ucfirst($cita->estado) }}</p>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('Citas.index') }}"
               class="inline-block bg-black text-white px-6 py-2 rounded hover:bg-gray-800 transition-colors duration-300 font-semibold">
                Volver
            </a>
        </div>
    </div>

</body>
</html>
