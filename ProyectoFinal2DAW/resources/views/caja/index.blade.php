<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja del día</title>
    {!! vite_asset(['resources/css/app.css', 'resources/css/caja.css', 'resources/js/app.js']) !!}
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
                        $serviciosPeluqueriaBono = [];
                        
                        foreach($detalleServicios as $cobro) {
                            $yaContados = false;
                            $esBono = $cobro->metodo_pago === 'bono';
                            
                            // PRIORIDAD 1: Servicios de cita individual
                            if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                                foreach($cobro->cita->servicios as $servicio) {
                                    if ($servicio->categoria === 'peluqueria') {
                                        $precio = $servicio->pivot->precio ?? $servicio->precio;
                                        $nombre = $servicio->nombre;
                                        
                                        if ($esBono) {
                                            // Servicios con bono: usar clave con _bono
                                            $clave = $nombre . '_bono';
                                            if (!isset($serviciosPeluqueriaBono[$clave])) {
                                                $serviciosPeluqueriaBono[$clave] = [
                                                    'nombre' => $nombre,
                                                    'cantidad' => 0
                                                ];
                                            }
                                            $serviciosPeluqueriaBono[$clave]['cantidad']++;
                                        } else {
                                            // Servicios normales
                                            $clave = $nombre . '_' . $precio;
                                            if (!isset($serviciosPeluqueria[$clave])) {
                                                $serviciosPeluqueria[$clave] = [
                                                    'nombre' => $nombre,
                                                    'precio_unitario' => $precio,
                                                    'cantidad' => 0,
                                                    'precio_total' => 0
                                                ];
                                            }
                                            $serviciosPeluqueria[$clave]['cantidad']++;
                                            $serviciosPeluqueria[$clave]['precio_total'] += $precio;
                                        }
                                    }
                                }
                                $yaContados = true;
                            }
                            
                            // PRIORIDAD 2: Servicios de citas agrupadas (solo si no tiene cita individual)
                            if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                                foreach($cobro->citasAgrupadas as $citaGrupo) {
                                    if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                                        foreach($citaGrupo->servicios as $servicio) {
                                            if ($servicio->categoria === 'peluqueria') {
                                                $precio = $servicio->pivot->precio ?? $servicio->precio;
                                                $nombre = $servicio->nombre;
                                                
                                                if ($esBono) {
                                                    $clave = $nombre . '_bono';
                                                    if (!isset($serviciosPeluqueriaBono[$clave])) {
                                                        $serviciosPeluqueriaBono[$clave] = [
                                                            'nombre' => $nombre,
                                                            'cantidad' => 0
                                                        ];
                                                    }
                                                    $serviciosPeluqueriaBono[$clave]['cantidad']++;
                                                } else {
                                                    $clave = $nombre . '_' . $precio;
                                                    if (!isset($serviciosPeluqueria[$clave])) {
                                                        $serviciosPeluqueria[$clave] = [
                                                            'nombre' => $nombre,
                                                            'precio_unitario' => $precio,
                                                            'cantidad' => 0,
                                                            'precio_total' => 0
                                                        ];
                                                    }
                                                    $serviciosPeluqueria[$clave]['cantidad']++;
                                                    $serviciosPeluqueria[$clave]['precio_total'] += $precio;
                                                }
                                            }
                                        }
                                    }
                                }
                                $yaContados = true;
                            }
                            
                            // PRIORIDAD 3: Servicios directos (solo si no tiene citas)
                            if (!$yaContados && $cobro->servicios && $cobro->servicios->count() > 0) {
                                foreach($cobro->servicios as $servicio) {
                                    if ($servicio->categoria === 'peluqueria') {
                                        $precio = $servicio->pivot->precio ?? $servicio->precio;
                                        $nombre = $servicio->nombre;
                                        
                                        if ($esBono) {
                                            $clave = $nombre . '_bono';
                                            if (!isset($serviciosPeluqueriaBono[$clave])) {
                                                $serviciosPeluqueriaBono[$clave] = [
                                                    'nombre' => $nombre,
                                                    'cantidad' => 0
                                                ];
                                            }
                                            $serviciosPeluqueriaBono[$clave]['cantidad']++;
                                        } else {
                                            $clave = $nombre . '_' . $precio;
                                            if (!isset($serviciosPeluqueria[$clave])) {
                                                $serviciosPeluqueria[$clave] = [
                                                    'nombre' => $nombre,
                                                    'precio_unitario' => $precio,
                                                    'cantidad' => 0,
                                                    'precio_total' => 0
                                                ];
                                            }
                                            $serviciosPeluqueria[$clave]['cantidad']++;
                                            $serviciosPeluqueria[$clave]['precio_total'] += $precio;
                                        }
                                    }
                                }
                            }
                        }
                    @endphp
                    @if(count($serviciosPeluqueria) > 0 || count($serviciosPeluqueriaBono) > 0)
                        <div class="space-y-1 text-sm">
                            @foreach($serviciosPeluqueria as $datos)
                                <div class="flex justify-between text-gray-700">
                                    <span>• {{ $datos['nombre'] }} @if($datos['cantidad'] > 1)<span class="text-blue-600 font-semibold">(x{{ $datos['cantidad'] }})</span>@endif</span>
                                    <span>€{{ number_format($datos['precio_total'], 2) }}</span>
                                </div>
                            @endforeach
                            @foreach($serviciosPeluqueriaBono as $datos)
                                <div class="flex justify-between text-gray-700">
                                    <span>• {{ $datos['nombre'] }} @if($datos['cantidad'] > 1)<span class="text-purple-600 font-semibold">(x{{ $datos['cantidad'] }})</span>@endif <span class="text-purple-600 text-xs italic">(Bono)</span></span>
                                    <span class="text-purple-600">€0.00</span>
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
                                        $nombre = $producto->nombre;
                                        
                                        if (!isset($productosPeluqueria[$nombre])) {
                                            $productosPeluqueria[$nombre] = [
                                                'cantidad' => 0,
                                                'precio_total' => 0
                                            ];
                                        }
                                        $productosPeluqueria[$nombre]['cantidad'] += $cantidad;
                                        $productosPeluqueria[$nombre]['precio_total'] += $subtotal;
                                    }
                                }
                            }
                        }
                    @endphp
                    @if(count($productosPeluqueria) > 0)
                        <div class="space-y-1 text-sm">
                            @foreach($productosPeluqueria as $nombre => $datos)
                                <div class="flex justify-between text-gray-700">
                                    <span>• {{ $nombre }} <span class="text-blue-600 font-semibold">(x{{ $datos['cantidad'] }})</span></span>
                                    <span>€{{ number_format($datos['precio_total'], 2) }}</span>
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
                        $serviciosEsteticaBono = [];
                        
                        foreach($detalleServicios as $cobro) {
                            $yaContados = false;
                            $esBono = $cobro->metodo_pago === 'bono';
                            
                            // PRIORIDAD 1: Servicios de cita individual
                            if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                                foreach($cobro->cita->servicios as $servicio) {
                                    if ($servicio->categoria === 'estetica') {
                                        $precio = $servicio->pivot->precio ?? $servicio->precio;
                                        $nombre = $servicio->nombre;
                                        
                                        if ($esBono) {
                                            $clave = $nombre . '_bono';
                                            if (!isset($serviciosEsteticaBono[$clave])) {
                                                $serviciosEsteticaBono[$clave] = [
                                                    'nombre' => $nombre,
                                                    'cantidad' => 0
                                                ];
                                            }
                                            $serviciosEsteticaBono[$clave]['cantidad']++;
                                        } else {
                                            $clave = $nombre . '_' . $precio;
                                            if (!isset($serviciosEstetica[$clave])) {
                                                $serviciosEstetica[$clave] = [
                                                    'nombre' => $nombre,
                                                    'precio_unitario' => $precio,
                                                    'cantidad' => 0,
                                                    'precio_total' => 0
                                                ];
                                            }
                                            $serviciosEstetica[$clave]['cantidad']++;
                                            $serviciosEstetica[$clave]['precio_total'] += $precio;
                                        }
                                    }
                                }
                                $yaContados = true;
                            }
                            
                            // PRIORIDAD 2: Servicios de citas agrupadas (solo si no tiene cita individual)
                            if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                                foreach($cobro->citasAgrupadas as $citaGrupo) {
                                    if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                                        foreach($citaGrupo->servicios as $servicio) {
                                            if ($servicio->categoria === 'estetica') {
                                                $precio = $servicio->pivot->precio ?? $servicio->precio;
                                                $nombre = $servicio->nombre;
                                                
                                                if ($esBono) {
                                                    $clave = $nombre . '_bono';
                                                    if (!isset($serviciosEsteticaBono[$clave])) {
                                                        $serviciosEsteticaBono[$clave] = [
                                                            'nombre' => $nombre,
                                                            'cantidad' => 0
                                                        ];
                                                    }
                                                    $serviciosEsteticaBono[$clave]['cantidad']++;
                                                } else {
                                                    $clave = $nombre . '_' . $precio;
                                                    if (!isset($serviciosEstetica[$clave])) {
                                                        $serviciosEstetica[$clave] = [
                                                            'nombre' => $nombre,
                                                            'precio_unitario' => $precio,
                                                            'cantidad' => 0,
                                                            'precio_total' => 0
                                                        ];
                                                    }
                                                    $serviciosEstetica[$clave]['cantidad']++;
                                                    $serviciosEstetica[$clave]['precio_total'] += $precio;
                                                }
                                            }
                                        }
                                    }
                                }
                                $yaContados = true;
                            }
                            
                            // PRIORIDAD 3: Servicios directos (solo si no tiene citas)
                            if (!$yaContados && $cobro->servicios && $cobro->servicios->count() > 0) {
                                foreach($cobro->servicios as $servicio) {
                                    if ($servicio->categoria === 'estetica') {
                                        $precio = $servicio->pivot->precio ?? $servicio->precio;
                                        $nombre = $servicio->nombre;
                                        
                                        if ($esBono) {
                                            $clave = $nombre . '_bono';
                                            if (!isset($serviciosEsteticaBono[$clave])) {
                                                $serviciosEsteticaBono[$clave] = [
                                                    'nombre' => $nombre,
                                                    'cantidad' => 0
                                                ];
                                            }
                                            $serviciosEsteticaBono[$clave]['cantidad']++;
                                        } else {
                                            $clave = $nombre . '_' . $precio;
                                            if (!isset($serviciosEstetica[$clave])) {
                                                $serviciosEstetica[$clave] = [
                                                    'nombre' => $nombre,
                                                    'precio_unitario' => $precio,
                                                    'cantidad' => 0,
                                                    'precio_total' => 0
                                                ];
                                            }
                                            $serviciosEstetica[$clave]['cantidad']++;
                                            $serviciosEstetica[$clave]['precio_total'] += $precio;
                                        }
                                    }
                                }
                            }
                        }
                    @endphp
                    @if(count($serviciosEstetica) > 0 || count($serviciosEsteticaBono) > 0)
                        <div class="space-y-1 text-sm">
                            @foreach($serviciosEstetica as $datos)
                                <div class="flex justify-between text-gray-700">
                                    <span>• {{ $datos['nombre'] }} @if($datos['cantidad'] > 1)<span class="text-pink-600 font-semibold">(x{{ $datos['cantidad'] }})</span>@endif</span>
                                    <span>€{{ number_format($datos['precio_total'], 2) }}</span>
                                </div>
                            @endforeach
                            @foreach($serviciosEsteticaBono as $datos)
                                <div class="flex justify-between text-gray-700">
                                    <span>• {{ $datos['nombre'] }} @if($datos['cantidad'] > 1)<span class="text-purple-600 font-semibold">(x{{ $datos['cantidad'] }})</span>@endif <span class="text-purple-600 text-xs italic">(Bono)</span></span>
                                    <span class="text-purple-600">€0.00</span>
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
                                        $nombre = $producto->nombre;
                                        
                                        if (!isset($productosEstetica[$nombre])) {
                                            $productosEstetica[$nombre] = [
                                                'cantidad' => 0,
                                                'precio_total' => 0
                                            ];
                                        }
                                        $productosEstetica[$nombre]['cantidad'] += $cantidad;
                                        $productosEstetica[$nombre]['precio_total'] += $subtotal;
                                    }
                                }
                            }
                        }
                    @endphp
                    @if(count($productosEstetica) > 0)
                        <div class="space-y-1 text-sm">
                            @foreach($productosEstetica as $nombre => $datos)
                                <div class="flex justify-between text-gray-700">
                                    <span>• {{ $nombre }} <span class="text-pink-600 font-semibold">(x{{ $datos['cantidad'] }})</span></span>
                                    <span>€{{ number_format($datos['precio_total'], 2) }}</span>
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
                                    <td class="font-semibold">
                                        @php
                                            $horaCita = null;
                                            
                                            // Intentar obtener la hora de la cita principal
                                            if ($item->cita && $item->cita->fecha_hora) {
                                                $horaCita = \Carbon\Carbon::parse($item->cita->fecha_hora)->format('H:i');
                                            }
                                            // Si no, intentar de citas agrupadas
                                            elseif ($item->citasAgrupadas && $item->citasAgrupadas->count() > 0) {
                                                $primeraCita = $item->citasAgrupadas->first();
                                                if ($primeraCita && $primeraCita->fecha_hora) {
                                                    $horaCita = \Carbon\Carbon::parse($primeraCita->fecha_hora)->format('H:i');
                                                }
                                            }
                                        @endphp
                                        {{ $horaCita ?? '-' }}
                                    </td>
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
                                        @php
                                            $serviciosMostrados = false;
                                            $yaContados = false;
                                            
                                            // PRIORIDAD 1: Servicios de cita individual
                                            if ($item->cita && $item->cita->servicios && $item->cita->servicios->count() > 0) {
                                                foreach($item->cita->servicios as $servicio) {
                                                    if($servicio->categoria === 'peluqueria') {
                                                        echo '<span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs mr-1 mb-1">💇 ' . $servicio->nombre . '</span>';
                                                    } elseif($servicio->categoria === 'estetica') {
                                                        echo '<span class="inline-block px-2 py-1 bg-pink-100 text-pink-700 rounded text-xs mr-1 mb-1">💅 ' . $servicio->nombre . '</span>';
                                                    }
                                                    $serviciosMostrados = true;
                                                }
                                                $yaContados = true;
                                            }
                                            
                                            // PRIORIDAD 2: Servicios de citas agrupadas (solo si no tiene cita individual)
                                            if (!$yaContados && $item->citasAgrupadas && $item->citasAgrupadas->count() > 0) {
                                                foreach($item->citasAgrupadas as $citaGrupo) {
                                                    if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                                                        foreach($citaGrupo->servicios as $servicio) {
                                                            if($servicio->categoria === 'peluqueria') {
                                                                echo '<span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs mr-1 mb-1">💇 ' . $servicio->nombre . '</span>';
                                                            } elseif($servicio->categoria === 'estetica') {
                                                                echo '<span class="inline-block px-2 py-1 bg-pink-100 text-pink-700 rounded text-xs mr-1 mb-1">💅 ' . $servicio->nombre . '</span>';
                                                            }
                                                            $serviciosMostrados = true;
                                                        }
                                                    }
                                                }
                                                $yaContados = true;
                                            }
                                            
                                            // PRIORIDAD 3: Servicios directos (solo si no tiene citas)
                                            if (!$yaContados && $item->servicios && $item->servicios->count() > 0) {
                                                foreach($item->servicios as $servicio) {
                                                    if($servicio->categoria === 'peluqueria') {
                                                        echo '<span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs mr-1 mb-1">💇 ' . $servicio->nombre . '</span>';
                                                    } elseif($servicio->categoria === 'estetica') {
                                                        echo '<span class="inline-block px-2 py-1 bg-pink-100 text-pink-700 rounded text-xs mr-1 mb-1">💅 ' . $servicio->nombre . '</span>';
                                                    }
                                                    $serviciosMostrados = true;
                                                }
                                            }
                                            
                                            // Productos
                                            if ($item->productos && $item->productos->count() > 0) {
                                                foreach($item->productos as $producto) {
                                                    echo '<span class="inline-block px-2 py-1 bg-green-100 text-green-700 rounded text-xs mr-1 mb-1">🛍️ ' . $producto->nombre . ' (x' . $producto->pivot->cantidad . ')</span>';
                                                    $serviciosMostrados = true;
                                                }
                                            }
                                            
                                            // Si no hay nada, mostrar guion
                                            if (!$serviciosMostrados) {
                                                echo '<span class="text-gray-400">-</span>';
                                            }
                                        @endphp
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
