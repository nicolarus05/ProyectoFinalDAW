<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja del día</title>
    @vite(['resources/js/app.js'])
    <style>
        .seccion-caja {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .titulo-seccion {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        .tabla-caja { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .tabla-caja th { background-color: #f3f4f6; padding: 0.75rem; text-align: left; font-weight: 600; border-bottom: 2px solid #e5e7eb; font-size: 0.875rem; }
        .tabla-caja td { padding: 0.75rem; border-bottom: 1px solid #e5e7eb; font-size: 0.875rem; }
        .tabla-caja tr:hover { background-color: #f9fafb; }
        .total-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1.5rem; }
        .total-item { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .total-item:last-child { border-bottom: none; margin-top: 0.5rem; padding-top: 1rem; border-top: 2px solid rgba(255,255,255,0.3); font-size: 1.25rem; font-weight: bold; }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <div class="w-full max-w-none mx-auto">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-4xl font-bold text-gray-800">💰 Caja del día: {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</h1>
            <a href="{{ route('dashboard') }}" class="inline-block bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-semibold">← Volver</a>
        </div>

        <div class="total-box">
            <h2 class="text-2xl font-bold mb-4">📊 TOTALES GENERALES DEL DÍA</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="total-item"><span>💵 Efectivo (Servicios):</span><span class="font-bold">€{{ number_format($totalEfectivo, 2) }}</span></div>
                    <div class="total-item"><span>💳 Tarjeta (Servicios):</span><span class="font-bold">€{{ number_format($totalTarjeta, 2) }}</span></div>
                    <div class="total-item"><span>🎫 Bono (Servicios):</span><span class="font-bold">€{{ number_format($totalBono, 2) }}</span></div>
                </div>
                <div>
                    <div class="total-item"><span>💵 Efectivo (Bonos vendidos):</span><span class="font-bold">€{{ number_format($totalBonosEfectivo, 2) }}</span></div>
                    <div class="total-item"><span>💳 Tarjeta (Bonos vendidos):</span><span class="font-bold">€{{ number_format($totalBonosTarjeta, 2) }}</span></div>
                    <div class="total-item"><span>❌ Deudas generadas:</span><span class="font-bold text-red-300">€{{ number_format($totalDeuda, 2) }}</span></div>
                </div>
            </div>
            <div class="total-item"><span>💰 TOTAL INGRESADO:</span><span>€{{ number_format($totalPagado, 2) }}</span></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- PELUQUERÍA -->
            <div class="seccion-caja" style="border-left: 4px solid #3b82f6;">
                <h3 class="titulo-seccion text-blue-700">💇 PELUQUERÍA</h3>
                
                <!-- Totales por método de pago -->
                <div class="space-y-2 mb-4 pb-4 border-b-2 border-blue-100">
                    <div class="flex justify-between"><span class="text-gray-600">💵 Efectivo:</span><span class="font-bold">€{{ number_format($totalPeluqueriaEfectivo, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">💳 Tarjeta:</span><span class="font-bold">€{{ number_format($totalPeluqueriaTarjeta, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">🎫 Bono:</span><span class="font-bold">€{{ number_format($totalPeluqueriaBono, 2) }}</span></div>
                    <div class="flex justify-between pt-2 border-t-2 border-blue-200"><span class="font-bold text-blue-700">TOTAL:</span><span class="font-bold text-blue-700 text-xl">€{{ number_format($totalPeluqueria, 2) }}</span></div>
                </div>

                <!-- Servicios de Peluquería -->
                <div class="mb-3">
                    <h4 class="font-semibold text-blue-600 text-sm mb-2">Servicios:</h4>
                    @php
                        $serviciosPeluqueria = [];
                        foreach($detalleServicios as $cobro) {
                            if ($cobro->cita && $cobro->cita->servicios) {
                                foreach($cobro->cita->servicios as $servicio) {
                                    if ($servicio->categoria === 'peluqueria') {
                                        $precio = $servicio->pivot->precio ?? $servicio->precio;
                                        $serviciosPeluqueria[] = [
                                            'nombre' => $servicio->nombre,
                                            'precio' => $precio
                                        ];
                                    }
                                }
                            }
                        }
                    @endphp
                    @if(count($serviciosPeluqueria) > 0)
                        <div class="space-y-1 text-sm">
                            @foreach($serviciosPeluqueria as $s)
                                <div class="flex justify-between text-gray-700">
                                    <span>• {{ $s['nombre'] }}</span>
                                    <span>€{{ number_format($s['precio'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-xs italic">Sin servicios</p>
                    @endif
                </div>

                <!-- Productos de Peluquería -->
                <div>
                    <h4 class="font-semibold text-blue-600 text-sm mb-2">Productos:</h4>
                    @php
                        $productosPeluqueria = [];
                        foreach($detalleServicios as $cobro) {
                            if ($cobro->productos) {
                                foreach($cobro->productos as $producto) {
                                    if ($producto->categoria === 'peluqueria') {
                                        $cantidad = $producto->pivot->cantidad ?? 1;
                                        $subtotal = $producto->pivot->subtotal ?? 0;
                                        $productosPeluqueria[] = [
                                            'nombre' => $producto->nombre,
                                            'cantidad' => $cantidad,
                                            'subtotal' => $subtotal
                                        ];
                                    }
                                }
                            }
                        }
                    @endphp
                    @if(count($productosPeluqueria) > 0)
                        <div class="space-y-1 text-sm">
                            @foreach($productosPeluqueria as $p)
                                <div class="flex justify-between text-gray-700">
                                    <span>• {{ $p['nombre'] }} (x{{ $p['cantidad'] }})</span>
                                    <span>€{{ number_format($p['subtotal'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-xs italic">Sin productos</p>
                    @endif
                </div>
            </div>

            <!-- ESTÉTICA -->
            <div class="seccion-caja" style="border-left: 4px solid #ec4899;">
                <h3 class="titulo-seccion text-pink-700">💅 ESTÉTICA</h3>
                
                <!-- Totales por método de pago -->
                <div class="space-y-2 mb-4 pb-4 border-b-2 border-pink-100">
                    <div class="flex justify-between"><span class="text-gray-600">💵 Efectivo:</span><span class="font-bold">€{{ number_format($totalEsteticaEfectivo, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">💳 Tarjeta:</span><span class="font-bold">€{{ number_format($totalEsteticaTarjeta, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">🎫 Bono:</span><span class="font-bold">€{{ number_format($totalEsteticaBono, 2) }}</span></div>
                    <div class="flex justify-between pt-2 border-t-2 border-pink-200"><span class="font-bold text-pink-700">TOTAL:</span><span class="font-bold text-pink-700 text-xl">€{{ number_format($totalEstetica, 2) }}</span></div>
                </div>

                <!-- Servicios de Estética -->
                <div class="mb-3">
                    <h4 class="font-semibold text-pink-600 text-sm mb-2">Servicios:</h4>
                    @php
                        $serviciosEstetica = [];
                        foreach($detalleServicios as $cobro) {
                            if ($cobro->cita && $cobro->cita->servicios) {
                                foreach($cobro->cita->servicios as $servicio) {
                                    if ($servicio->categoria === 'estetica') {
                                        $precio = $servicio->pivot->precio ?? $servicio->precio;
                                        $serviciosEstetica[] = [
                                            'nombre' => $servicio->nombre,
                                            'precio' => $precio
                                        ];
                                    }
                                }
                            }
                        }
                    @endphp
                    @if(count($serviciosEstetica) > 0)
                        <div class="space-y-1 text-sm">
                            @foreach($serviciosEstetica as $s)
                                <div class="flex justify-between text-gray-700">
                                    <span>• {{ $s['nombre'] }}</span>
                                    <span>€{{ number_format($s['precio'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-xs italic">Sin servicios</p>
                    @endif
                </div>

                <!-- Productos de Estética -->
                <div>
                    <h4 class="font-semibold text-pink-600 text-sm mb-2">Productos:</h4>
                    @php
                        $productosEstetica = [];
                        foreach($detalleServicios as $cobro) {
                            if ($cobro->productos) {
                                foreach($cobro->productos as $producto) {
                                    if ($producto->categoria === 'estetica') {
                                        $cantidad = $producto->pivot->cantidad ?? 1;
                                        $subtotal = $producto->pivot->subtotal ?? 0;
                                        $productosEstetica[] = [
                                            'nombre' => $producto->nombre,
                                            'cantidad' => $cantidad,
                                            'subtotal' => $subtotal
                                        ];
                                    }
                                }
                            }
                        }
                    @endphp
                    @if(count($productosEstetica) > 0)
                        <div class="space-y-1 text-sm">
                            @foreach($productosEstetica as $p)
                                <div class="flex justify-between text-gray-700">
                                    <span>• {{ $p['nombre'] }} (x{{ $p['cantidad'] }})</span>
                                    <span>€{{ number_format($p['subtotal'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-xs italic">Sin productos</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="seccion-caja">
            <h3 class="titulo-seccion text-green-700">✅ SERVICIOS REALIZADOS</h3>
            @if($detalleServicios->count() > 0)
                <div style="overflow-x: auto;">
                    <table class="tabla-caja">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Cliente</th>
                                <th>Servicio(s)</th>
                                <th>Empleado</th>
                                <th>Método</th>
                                <th>Total</th>
                                <th>Deuda</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($detalleServicios as $item)
                                <tr>
                                    <td class="font-semibold">{{ optional($item->cita)->fecha_hora ? \Carbon\Carbon::parse($item->cita->fecha_hora)->format('H:i') : '-' }}</td>
                                    <td>
                                        @if($item->cliente && $item->cliente->user)
                                            {{ $item->cliente->user->nombre }} {{ $item->cliente->user->apellidos }}
                                        @elseif($item->cita && $item->cita->cliente && $item->cita->cliente->user)
                                            {{ $item->cita->cliente->user->nombre }} {{ $item->cita->cliente->user->apellidos }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->cita && $item->cita->servicios)
                                            @foreach($item->cita->servicios as $servicio)
                                                @if($servicio->categoria === 'peluqueria')
                                                    <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs mr-1 mb-1">💇 {{ $servicio->nombre }}</span>
                                                @elseif($servicio->categoria === 'estetica')
                                                    <span class="inline-block px-2 py-1 bg-pink-100 text-pink-700 rounded text-xs mr-1 mb-1">💅 {{ $servicio->nombre }}</span>
                                                @endif
                                            @endforeach
                                        @endif
                                        @if($item->productos && $item->productos->count() > 0)
                                            @foreach($item->productos as $producto)
                                                <span class="inline-block px-2 py-1 bg-green-100 text-green-700 rounded text-xs mr-1 mb-1">🛍️ {{ $producto->nombre }} (x{{ $producto->pivot->cantidad }})</span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->empleado && $item->empleado->user)
                                            {{ $item->empleado->user->nombre }}
                                        @elseif($item->cita && $item->cita->empleado && $item->cita->empleado->user)
                                            {{ $item->cita->empleado->user->nombre }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->metodo_pago === 'efectivo')
                                            <span class="text-green-600 font-semibold">💵 Efectivo</span>
                                        @elseif($item->metodo_pago === 'tarjeta')
                                            <span class="text-blue-600 font-semibold">💳 Tarjeta</span>
                                        @elseif($item->metodo_pago === 'bono')
                                            <span class="text-purple-600 font-semibold">🎫 Bono</span>
                                        @endif
                                    </td>
                                    <td class="font-bold text-green-600">€{{ number_format($item->total_final, 2) }}</td>
                                    <td class="font-bold {{ $item->deuda > 0 ? 'text-red-600' : 'text-gray-400' }}">€{{ number_format($item->deuda ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 text-center py-4">No se realizaron servicios este día.</p>
            @endif
        </div>

        <div class="seccion-caja">
            <h3 class="titulo-seccion text-purple-700">🎫 BONOS VENDIDOS</h3>
            @if($bonosVendidos->count() > 0)
                <div style="overflow-x: auto;">
                    <table class="tabla-caja">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Cliente</th>
                                <th>Bono</th>
                                <th>Empleado</th>
                                <th>Método</th>
                                <th>Precio</th>
                                <th>Dinero</th>
                                <th>Cambio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bonosVendidos as $bono)
                                <tr>
                                    <td class="font-semibold">{{ \Carbon\Carbon::parse($bono->fecha_compra)->format('H:i') }}</td>
                                    <td>
                                        @if($bono->cliente && $bono->cliente->user)
                                            {{ $bono->cliente->user->nombre }} {{ $bono->cliente->user->apellidos }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-semibold text-purple-700">🎫 {{ $bono->plantilla->nombre }}</span>
                                        @if($bono->plantilla->duracion_dias)
                                            <span class="text-xs text-gray-500">({{ $bono->plantilla->duracion_dias }} días)</span>
                                        @else
                                            <span class="text-xs text-purple-500">(Sin límite)</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($bono->empleado && $bono->empleado->user)
                                            {{ $bono->empleado->user->nombre }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($bono->metodo_pago === 'efectivo')
                                            <span class="text-green-600 font-semibold">💵 Efectivo</span>
                                        @elseif($bono->metodo_pago === 'tarjeta')
                                            <span class="text-blue-600 font-semibold">💳 Tarjeta</span>
                                        @endif
                                    </td>
                                    <td class="font-bold text-purple-600">€{{ number_format($bono->precio_pagado, 2) }}</td>
                                    <td class="font-semibold">€{{ number_format($bono->dinero_cliente, 2) }}</td>
                                    <td class="font-semibold {{ $bono->cambio > 0 ? 'text-orange-600' : 'text-gray-400' }}">€{{ number_format($bono->cambio, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 text-center py-4">No se vendieron bonos este día.</p>
            @endif
        </div>

        <div class="seccion-caja">
            <h3 class="titulo-seccion text-red-700">💰 DEUDAS GENERADAS</h3>
            @if($deudas->count() > 0)
                <div style="overflow-x: auto;">
                    <table class="tabla-caja">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Servicio</th>
                                <th>Total Servicio</th>
                                <th>Pagado</th>
                                <th>Deuda</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deudas as $deuda)
                                <tr>
                                    <td>
                                        @if($deuda->cliente && $deuda->cliente->user)
                                            {{ $deuda->cliente->user->nombre }} {{ $deuda->cliente->user->apellidos }}
                                        @elseif($deuda->cita && $deuda->cita->cliente && $deuda->cita->cliente->user)
                                            {{ $deuda->cita->cliente->user->nombre }} {{ $deuda->cita->cliente->user->apellidos }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($deuda->cita && $deuda->cita->servicios)
                                            @foreach($deuda->cita->servicios as $servicio)
                                                <span class="inline-block px-2 py-1 bg-gray-100 rounded text-xs mr-1">{{ $servicio->nombre }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="font-semibold">€{{ number_format($deuda->total_final + $deuda->deuda, 2) }}</td>
                                    <td class="font-semibold text-green-600">€{{ number_format($deuda->total_final, 2) }}</td>
                                    <td class="font-bold text-red-600">€{{ number_format($deuda->deuda, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-red-50">
                                <td colspan="4" class="text-right font-bold">TOTAL DEUDA DEL DÍA:</td>
                                <td class="font-bold text-red-700 text-lg">€{{ number_format($totalDeuda, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 text-center py-4">✅ No se generaron deudas este día.</p>
            @endif
        </div>
    </div>
</body>
</html>
