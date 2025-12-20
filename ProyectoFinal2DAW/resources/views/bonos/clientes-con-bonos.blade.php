<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes con Bonos</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
        .btn-volver {
            display: inline-block;
            background-color: #3b82f6;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .btn-volver:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-7xl mx-auto">
        <div class="bg-white shadow-md rounded p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold">🎫 Clientes con Bonos Activos</h1>
                <a href="{{ route('dashboard') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">← Volver al Inicio</a>
            </div>

            @if($clientes->count() > 0)
                <div class="grid gap-6">
                    @foreach($clientes as $cliente)
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">
                                        {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}
                                    </h3>
                                    <p class="text-gray-600">📧 {{ $cliente->user->email }}</p>
                                    @if($cliente->user->telefono)
                                        <p class="text-gray-600">📞 {{ $cliente->user->telefono }}</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <span class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded font-semibold">
                                        {{ $cliente->bonos->count() }} {{ $cliente->bonos->count() === 1 ? 'Bono' : 'Bonos' }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                @foreach($cliente->bonos as $bono)
                                    <div class="bg-white border border-purple-200 rounded p-3">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <h4 class="font-bold text-purple-700">
                                                    🎫 {{ $bono->plantilla->nombre }}
                                                </h4>
                                                @if($bono->plantilla->descripcion)
                                                    <p class="text-sm text-gray-600 mt-1">
                                                        {{ $bono->plantilla->descripcion }}
                                                    </p>
                                                @endif
                                                <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                                                    <div>
                                                        <span class="text-gray-600">Comprado:</span>
                                                        <span class="font-semibold">{{ \Carbon\Carbon::parse($bono->fecha_compra)->format('d/m/Y') }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-600">Expira:</span>
                                                        @if($bono->plantilla->duracion_dias && $bono->fecha_expiracion)
                                                            <span class="font-semibold">{{ \Carbon\Carbon::parse($bono->fecha_expiracion)->format('d/m/Y') }}</span>
                                                        @else
                                                            <span class="font-semibold text-purple-600">✨ Sin límite</span>
                                                        @endif
                                                    </div>
                                                    @if($bono->empleado && $bono->empleado->user)
                                                    <div class="col-span-2">
                                                        <span class="text-gray-600">Vendido por:</span>
                                                        <span class="font-semibold">{{ $bono->empleado->user->nombre }} {{ $bono->empleado->user->apellidos }}</span>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-right">
                                                    <p class="text-sm text-gray-600">Precio pagado</p>
                                                    <p class="text-xl font-bold text-green-600">€{{ number_format($bono->precio_pagado, 2) }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Servicios incluidos -->
                                        @if($bono->plantilla && $bono->plantilla->servicios && $bono->plantilla->servicios->count() > 0)
                                        <div class="mt-3 pt-3 border-t border-gray-200">
                                            <p class="text-sm font-semibold text-gray-700 mb-2">Servicios incluidos:</p>
                                            <div class="space-y-2">
                                                @foreach($bono->plantilla->servicios as $servicio)
                                                    @php
                                                        $cantidadDisponible = $bono->cantidadDisponible($servicio->id);
                                                        $cantidadTotal = $servicio->pivot->cantidad;
                                                        $cantidadUsada = $cantidadTotal - $cantidadDisponible;
                                                        // Obtener usos de este servicio específico
                                                        $usosServicio = $bono->usoDetalles->where('servicio_id', $servicio->id);
                                                    @endphp
                                                    <div class="bg-gray-50 rounded p-2">
                                                        <div class="flex justify-between items-center text-sm mb-1">
                                                            <span>
                                                                @if($servicio->categoria === 'peluqueria')
                                                                    💇
                                                                @else
                                                                    💅
                                                                @endif
                                                                {{ $servicio->nombre }}
                                                            </span>
                                                            <span class="font-semibold">
                                                                @if($cantidadDisponible > 0)
                                                                    <span class="text-green-600">{{ $cantidadDisponible }}/{{ $cantidadTotal }} disponibles</span>
                                                                @else
                                                                    <span class="text-red-600">❌ Agotado ({{ $cantidadUsada }}/{{ $cantidadTotal }})</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                        
                                                        @if($usosServicio->count() > 0)
                                                            <div class="ml-4 mt-2 space-y-1">
                                                                <p class="text-xs font-semibold text-gray-600">Fechas de uso:</p>
                                                                @foreach($usosServicio as $uso)
                                                                    @php
                                                                        // Intentar obtener fecha de la cita, si no usar created_at del uso
                                                                        $fechaUso = null;
                                                                        if ($uso->cita && $uso->cita->fecha_hora) {
                                                                            $fechaUso = \Carbon\Carbon::parse($uso->cita->fecha_hora)->format('d/m/Y H:i');
                                                                        } elseif ($uso->created_at) {
                                                                            $fechaUso = \Carbon\Carbon::parse($uso->created_at)->format('d/m/Y H:i');
                                                                        }
                                                                    @endphp
                                                                    @if($fechaUso)
                                                                        <div class="text-xs text-gray-700 flex items-center gap-1">
                                                                            <span class="text-green-600">✓</span>
                                                                            <span>{{ $fechaUso }}</span>
                                                                        </div>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-500 text-lg">No hay clientes con bonos activos en este momento.</p>
                    <a href="{{ route('bonos.index') }}" class="mt-4 inline-block text-blue-600 hover:underline">
                        Ver bonos disponibles para vender
                    </a>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
