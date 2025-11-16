<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de la Cita</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
</head>
<body class="min-h-screen bg-gray-100 flex flex-col items-center justify-center p-6">

    <div class="bg-white rounded-lg shadow-md w-full max-w-2xl p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-black">Detalle de la Cita</h1>
            <a href="{{ route('citas.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">← Volver</a>
        </div>

        <div class="space-y-4 text-gray-800 text-base">
            <p><strong class="font-semibold">Cliente:</strong> {{ $cita->cliente->user->nombre }} {{ $cita->cliente->user->apellidos }}</p>
            
            @if($cita->cliente && $cita->cliente->notas_adicionales)
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-md shadow-sm">
                    <p class="font-semibold text-yellow-800 mb-2 flex items-center">
                        <span class="text-xl mr-2">📝</span>
                        <span>Notas del Cliente:</span>
                    </p>
                    <p class="text-yellow-900 whitespace-pre-line">{{ $cita->cliente->notas_adicionales }}</p>
                </div>
            @endif
            
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

        <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
            @php
                $tieneCobro = DB::table('registro_cobros')->where('id_cita', $cita->id)->exists();
            @endphp
            
            @if(!$tieneCobro)
                <a href="{{ route('cobros.create', ['cita_id' => $cita->id]) }}"
                   class="inline-flex items-center justify-center gap-2 bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition-colors duration-300 font-semibold">
                    <span>💰</span>
                    <span>Pasar a Caja</span>
                </a>
            @else
                <span class="inline-flex items-center justify-center gap-2 bg-green-100 text-green-800 px-6 py-2 rounded font-semibold border border-green-300">
                    <span>✓</span>
                    <span>Cita Cobrada</span>
                </span>
            @endif
        </div>
    </div>

</body>
</html>
