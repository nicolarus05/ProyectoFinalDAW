<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja del día</title>
    @vite(['resources/js/app.js'])
</head>
<body class="bg-gray-100 p-6">
    <div class="w-full max-w-none mx-auto bg-white shadow-md rounded p-6">
        <h1 class="text-3xl font-bold mb-4">Caja del día: {{ $fecha }}</h1>

        <div class="mb-6">
            <h3 class="text-xl font-semibold mb-2">Totales</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li>Efectivo: €{{ number_format($totalEfectivo, 2) }}</li>
                <li>Tarjeta: €{{ number_format($totalTarjeta, 2) }}</li>
                <li><strong>Total pagado: €{{ number_format($totalPagado, 2) }}</strong></li>
                <li class="text-red-600"><strong>Total deuda: €{{ number_format($totalDeuda, 2) }}</strong></li>
            </ul>
        </div>

        <div class="mb-6">
            <h3 class="text-xl font-semibold mb-2">Totales por Categoría</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li class="text-blue-700">💇 Peluquería (servicios + productos): €{{ number_format($totalPeluqueria, 2) }}</li>
                <li class="text-pink-700">💅 Estética (servicios + productos): €{{ number_format($totalEstetica, 2) }}</li>
            </ul>
        </div>

        <div>
            <h3 class="text-xl font-semibold mb-2">Detalle de servicios realizados</h3>
            <table class="w-full table-auto text-sm text-left break-words">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-2">Hora</th>
                        <th class="px-4 py-2">Cliente</th>
                        <th class="px-4 py-2">Servicio(s)</th>
                        <th class="px-4 py-2">Empleado</th>
                        <th class="px-4 py-2">Método de pago</th>
                        <th class="px-4 py-2">Pagado</th>
                        <th class="px-4 py-2">Deuda</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detalleServicios as $item)
                        <tr class="border-t">
                            <td class="px-4 py-2">
                                {{ optional($item->cita)->fecha_hora ? \Carbon\Carbon::parse($item->cita->fecha_hora)->format('H:i') : '-' }}
                            </td>
                            <td class="px-4 py-2">
                                @if($item->cliente && $item->cliente->user)
                                    {{ $item->cliente->user->nombre }} {{ $item->cliente->user->apellidos }}
                                @elseif($item->cita && $item->cita->cliente && $item->cita->cliente->user)
                                    {{ $item->cita->cliente->user->nombre }} {{ $item->cita->cliente->user->apellidos }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if($item->cita && $item->cita->servicios)
                                    @foreach($item->cita->servicios as $servicio)
                                        @if($servicio->tipo === 'peluqueria')
                                            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs mr-1 mb-1">
                                                💇 {{ $servicio->nombre }}
                                            </span>
                                        @elseif($servicio->tipo === 'estetica')
                                            <span class="inline-block px-2 py-1 bg-pink-100 text-pink-700 rounded text-xs mr-1 mb-1">
                                                💅 {{ $servicio->nombre }}
                                            </span>
                                        @else
                                            <span class="inline-block px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs mr-1 mb-1">
                                                {{ $servicio->nombre }}
                                            </span>
                                        @endif
                                    @endforeach
                                @endif
                                
                                @if($item->productos && $item->productos->count() > 0)
                                    @foreach($item->productos as $producto)
                                        @if($producto->categoria === 'peluqueria')
                                            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs mr-1 mb-1">
                                                🛍️ {{ $producto->nombre }} (x{{ $producto->pivot->cantidad }})
                                            </span>
                                        @elseif($producto->categoria === 'estetica')
                                            <span class="inline-block px-2 py-1 bg-pink-100 text-pink-700 rounded text-xs mr-1 mb-1">
                                                🛍️ {{ $producto->nombre }} (x{{ $producto->pivot->cantidad }})
                                            </span>
                                        @else
                                            <span class="inline-block px-2 py-1 bg-green-100 text-green-700 rounded text-xs mr-1 mb-1">
                                                🛍️ {{ $producto->nombre }} (x{{ $producto->pivot->cantidad }})
                                            </span>
                                        @endif
                                    @endforeach
                                @endif
                                
                                @if((!$item->cita || !$item->cita->servicios) && (!$item->productos || $item->productos->count() == 0))
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if($item->empleado && $item->empleado->user)
                                    {{ $item->empleado->user->nombre }}
                                @elseif($item->cita && $item->cita->empleado && $item->cita->empleado->user)
                                    {{ $item->cita->empleado->user->nombre }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ ucfirst($item->metodo_pago) }}</td>
                            <td class="px-4 py-2">€{{ number_format($item->dinero_cliente, 2) }}</td>
                            <td class="px-4 py-2">€{{ number_format($item->deuda ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <a href="{{ route('dashboard') }}" class="inline-block mt-6 text-gray-700 hover:underline">Volver al Inicio</a>
    </div>
</body>
</html>
