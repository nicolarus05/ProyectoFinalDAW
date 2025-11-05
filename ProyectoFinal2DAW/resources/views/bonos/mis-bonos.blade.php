<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonos del Cliente</title>
    @vite(['resources/js/app.js'])
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-6xl mx-auto bg-white shadow-md rounded p-6">
        <h1 class="text-3xl font-bold mb-2">Bonos de {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}</h1>
        <p class="text-gray-600 mb-6">{{ $cliente->user->email }}</p>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-4 mb-4 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if($bonos->count() > 0)
            <div class="space-y-6">
                @foreach($bonos as $bono)
                    <div class="border rounded-lg p-6 {{ $bono->estado === 'activo' ? 'border-green-500 bg-green-50' : ($bono->estado === 'expirado' ? 'border-red-500 bg-red-50' : 'border-gray-400 bg-gray-50') }}">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-bold">{{ $bono->plantilla->nombre }}</h3>
                                @if($bono->plantilla->descripcion)
                                    <p class="text-gray-600">{{ $bono->plantilla->descripcion }}</p>
                                @endif
                            </div>
                            <div>
                                @if($bono->estado === 'activo')
                                    <span class="inline-block px-3 py-1 bg-green-600 text-white rounded-full text-sm font-semibold">Activo</span>
                                @elseif($bono->estado === 'usado')
                                    <span class="inline-block px-3 py-1 bg-gray-600 text-white rounded-full text-sm font-semibold">Usado</span>
                                @else
                                    <span class="inline-block px-3 py-1 bg-red-600 text-white rounded-full text-sm font-semibold">Expirado</span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mb-4 text-sm">
                            <div>
                                <p class="text-gray-600">Precio pagado:</p>
                                <p class="font-semibold text-lg">€{{ number_format($bono->plantilla->precio, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Fecha de compra:</p>
                                <p class="font-semibold">{{ $bono->fecha_compra->format('d/m/Y') }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Fecha de expiración:</p>
                                @if($bono->plantilla->duracion_dias)
                                    <p class="font-semibold {{ $bono->estaExpirado() ? 'text-red-600' : '' }}">
                                        {{ $bono->fecha_expiracion->format('d/m/Y') }}
                                    </p>
                                @else
                                    <p class="font-semibold text-purple-600">✨ Sin límite</p>
                                @endif
                            </div>
                        </div>

                        <div>
                            <p class="font-semibold mb-2">Servicios:</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($bono->servicios as $servicio)
                                    @php
                                        $disponibles = $servicio->pivot->cantidad_total - $servicio->pivot->cantidad_usada;
                                        $porcentaje = ($servicio->pivot->cantidad_usada / $servicio->pivot->cantidad_total) * 100;
                                        // Obtener usos de este servicio específico
                                        $usosServicio = $bono->usoDetalles->where('servicio_id', $servicio->id);
                                    @endphp
                                    <div class="bg-white border rounded p-3">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="font-medium">
                                                {{ $servicio->nombre }}
                                                @if($servicio->categoria === 'peluqueria')
                                                    <span class="text-blue-600">💇</span>
                                                @else
                                                    <span class="text-pink-600">💅</span>
                                                @endif
                                            </span>
                                            <span class="text-sm font-semibold {{ $disponibles > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $disponibles }}/{{ $servicio->pivot->cantidad_total }} disponibles
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $porcentaje }}%"></div>
                                        </div>
                                        
                                        @if($usosServicio->count() > 0)
                                            <div class="mt-3 pt-2 border-t border-gray-200">
                                                <p class="text-xs font-semibold text-gray-600 mb-1">Fechas de uso:</p>
                                                <div class="space-y-1">
                                                    @foreach($usosServicio as $uso)
                                                        <div class="text-xs text-gray-700 flex items-center gap-1">
                                                            <span class="text-green-600">✓</span>
                                                            <span>{{ \Carbon\Carbon::parse($uso->cita->fecha_hora)->format('d/m/Y H:i') }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-500 mb-4">Este cliente no tiene bonos.</p>
                <a href="{{ route('bonos.index') }}" class="text-blue-600 hover:underline">Ver bonos disponibles</a>
            </div>
        @endif

        <div class="mt-6">
            <a href="{{ route('bonos.index') }}" class="text-blue-600 hover:underline">Volver a Bonos</a>
        </div>
    </div>
</body>
</html>
