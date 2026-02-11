<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Cobro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .desglose-item { border-left: 3px solid #e5e7eb; padding-left: 0.75rem; }
        .desglose-item.servicio { border-left-color: #3b82f6; }
        .desglose-item.producto { border-left-color: #10b981; }
        .desglose-item.bono { border-left-color: #f59e0b; }
        .bono-badge { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; padding: 2px 8px; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; }
        .section-card { background: white; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-6 border-b-2 pb-4">
                <h1 class="text-3xl font-bold text-gray-800">📋 Detalle del Cobro #{{ $cobro->id }}</h1>
                <div class="text-right">
                    <div class="text-sm text-gray-500">
                        {{ \Carbon\Carbon::parse($cobro->created_at)->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>

            <!-- Información General -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <!-- Cliente -->
                <div class="section-card">
                    <div class="text-sm text-gray-500 mb-1">👤 Cliente</div>
                    <div class="text-lg font-semibold text-gray-800">
                        @php
                            $nombreCliente = '-';
                            if ($cobro->cita && $cobro->cita->cliente && $cobro->cita->cliente->user) {
                                $nombreCliente = $cobro->cita->cliente->user->nombre . ' ' . $cobro->cita->cliente->user->apellidos;
                            } elseif ($cobro->cliente && $cobro->cliente->user) {
                                $nombreCliente = $cobro->cliente->user->nombre . ' ' . $cobro->cliente->user->apellidos;
                            } elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                                $primeraCita = $cobro->citasAgrupadas->first();
                                if ($primeraCita && $primeraCita->cliente && $primeraCita->cliente->user) {
                                    $nombreCliente = $primeraCita->cliente->user->nombre . ' ' . $primeraCita->cliente->user->apellidos;
                                }
                            }
                        @endphp
                        {{ $nombreCliente }}
                    </div>
                </div>
                
                <!-- Empleado -->
                <div class="section-card">
                    <div class="text-sm text-gray-500 mb-1">👨‍💼 Empleado Principal</div>
                    <div class="text-lg font-semibold text-gray-800">
                        @php
                            $nombreEmpleado = '-';
                            if ($cobro->cita && $cobro->cita->empleado && $cobro->cita->empleado->user) {
                                $nombreEmpleado = $cobro->cita->empleado->user->nombre . ' ' . $cobro->cita->empleado->user->apellidos;
                            } elseif ($cobro->empleado && $cobro->empleado->user) {
                                $nombreEmpleado = $cobro->empleado->user->nombre . ' ' . $cobro->empleado->user->apellidos;
                            } elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                                $primeraCita = $cobro->citasAgrupadas->first();
                                if ($primeraCita && $primeraCita->empleado && $primeraCita->empleado->user) {
                                    $nombreEmpleado = $primeraCita->empleado->user->nombre . ' ' . $primeraCita->empleado->user->apellidos;
                                }
                            }
                        @endphp
                        {{ $nombreEmpleado }}
                    </div>
                </div>
            </div>
            
            <!-- Servicios, Productos y Bonos -->
            <div class="grid grid-cols-1 {{ ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }} gap-4 mb-6">
                <!-- SERVICIOS -->
                <div class="section-card">
                    <div class="font-semibold text-gray-700 mb-3 flex items-center gap-2 text-lg">
                        <span class="text-blue-600">✂️</span> SERVICIOS
                    </div>
                    
                    @php
                        // Obtener servicios con información detallada
                        $serviciosDetalle = [];
                        
                        // PRIORIDAD: Usar servicios vinculados al cobro (registro_cobro_servicio)
                        // Esto incluye cobros de citas con servicios editados Y cobros directos
                        if ($cobro->servicios && $cobro->servicios->count() > 0) {
                            // Precargar empleados para evitar N+1
                            $empleadoIds = $cobro->servicios->pluck('pivot.empleado_id')->filter()->unique();
                            $empleadosMap = \App\Models\Empleado::with('user')->whereIn('id', $empleadoIds)->get()->keyBy('id');
                            
                            foreach ($cobro->servicios as $servicio) {
                                $precioServicio = $servicio->pivot->precio ?? $servicio->precio;
                                $empleadoId = $servicio->pivot->empleado_id;
                                $empleado = 'Sin asignar';
                                if ($empleadoId && isset($empleadosMap[$empleadoId])) {
                                    $empleado = $empleadosMap[$empleadoId]->user->nombre ?? 'Sin asignar';
                                }
                                
                                // Detectar si fue pagado con bono
                                $pagadoConBono = false;
                                if ($precioServicio == 0) {
                                    // Precio 0 indica pago con bono
                                    $pagadoConBono = true;
                                } elseif ($cobro->id_cita) {
                                    $pagadoConBono = \DB::table('bono_uso_detalle')
                                        ->where('cita_id', $cobro->id_cita)
                                        ->where('servicio_id', $servicio->id)
                                        ->exists();
                                } elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                                    $pagadoConBono = \DB::table('bono_uso_detalle')
                                        ->whereIn('cita_id', $cobro->citasAgrupadas->pluck('id'))
                                        ->where('servicio_id', $servicio->id)
                                        ->exists();
                                }
                                
                                $serviciosDetalle[] = [
                                    'nombre' => $servicio->nombre,
                                    'precio' => $precioServicio,
                                    'empleado' => $empleado,
                                    'es_bono' => $pagadoConBono
                                ];
                            }
                        }
                        // FALLBACK: Para cobros antiguos sin servicios en pivot, usar cita
                        elseif ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                            $empleado = $cobro->cita->empleado && $cobro->cita->empleado->user 
                                ? $cobro->cita->empleado->user->nombre 
                                : 'Sin asignar';
                            
                            foreach ($cobro->cita->servicios as $servicio) {
                                $precioServicio = $servicio->pivot->precio ?? $servicio->precio;
                                
                                $pagadoConBono = \DB::table('bono_uso_detalle')
                                    ->where('cita_id', $cobro->cita->id)
                                    ->where('servicio_id', $servicio->id)
                                    ->exists();
                                
                                $serviciosDetalle[] = [
                                    'nombre' => $servicio->nombre,
                                    'precio' => $precioServicio,
                                    'empleado' => $empleado,
                                    'es_bono' => $pagadoConBono
                                ];
                            }
                        }
                        // FALLBACK 2: Citas agrupadas sin servicios en pivot
                        elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                            foreach ($cobro->citasAgrupadas as $citaGrupo) {
                                $empleado = $citaGrupo->empleado && $citaGrupo->empleado->user 
                                    ? $citaGrupo->empleado->user->nombre 
                                    : 'Sin asignar';
                                
                                if ($citaGrupo->servicios) {
                                    foreach ($citaGrupo->servicios as $servicio) {
                                        $precioServicio = $servicio->pivot->precio ?? $servicio->precio;
                                        
                                        $pagadoConBono = \DB::table('bono_uso_detalle')
                                            ->where('cita_id', $citaGrupo->id)
                                            ->where('servicio_id', $servicio->id)
                                            ->exists();
                                        
                                        $serviciosDetalle[] = [
                                            'nombre' => $servicio->nombre,
                                            'precio' => $precioServicio,
                                            'empleado' => $empleado,
                                            'es_bono' => $pagadoConBono
                                        ];
                                    }
                                }
                            }
                        }
                    @endphp
                    
                    @if(count($serviciosDetalle) > 0)
                        <div class="space-y-3">
                            @foreach($serviciosDetalle as $servicio)
                                <div class="desglose-item servicio bg-blue-50 p-3 rounded-lg {{ $servicio['es_bono'] ? 'border-2 border-yellow-400' : '' }}">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="font-medium text-gray-800 text-sm">{{ $servicio['nombre'] }}</div>
                                        @if($servicio['es_bono'])
                                            <span class="bono-badge">🎫 BONO</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-600 mb-2">👨‍💼 {{ $servicio['empleado'] }}</div>
                                    <div class="text-lg font-bold {{ $servicio['es_bono'] ? 'text-yellow-600' : 'text-blue-700' }}">
                                        {{ number_format($servicio['precio'], 2) }} €
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-gray-400 italic text-sm">Sin servicios</div>
                    @endif
                </div>
                
                <!-- PRODUCTOS -->
                <div class="section-card">
                    <div class="font-semibold text-gray-700 mb-3 flex items-center gap-2 text-lg">
                        <span class="text-green-600">🛍️</span> PRODUCTOS
                    </div>
                    
                    @if($cobro->productos && $cobro->productos->count() > 0)
                        <div class="space-y-3">
                            @foreach($cobro->productos as $producto)
                                @php
                                    $cantidad = $producto->pivot->cantidad ?? 1;
                                    $precioUnitario = $producto->pivot->precio ?? $producto->precio;
                                    $subtotal = $precioUnitario * $cantidad;
                                @endphp
                                <div class="desglose-item producto bg-green-50 p-3 rounded-lg">
                                    <div class="font-medium text-gray-800 text-sm mb-1">{{ $producto->nombre }}</div>
                                    <div class="text-xs text-gray-600 mb-2">
                                        {{ $cantidad }}x unidad{{ $cantidad > 1 ? 'es' : '' }} × {{ number_format($precioUnitario, 2) }} €
                                    </div>
                                    <div class="text-lg font-bold text-green-700">
                                        {{ number_format($subtotal, 2) }} €
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-gray-400 italic text-sm">Sin productos</div>
                    @endif
                </div>
                
                <!-- BONOS VENDIDOS -->
                @if($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0)
                <div class="section-card">
                    <div class="font-semibold text-gray-700 mb-3 flex items-center gap-2 text-lg">
                        <span class="text-yellow-600">🎫</span> BONOS VENDIDOS
                    </div>
                    
                    <div class="space-y-3">
                        @foreach($cobro->bonosVendidos as $bono)
                            @php
                                $precioTotal = $bono->pivot->precio ?? 0;
                                $precioPagado = $bono->precio_pagado ?? 0;
                                $deudaBono = max(0, $precioTotal - $precioPagado);
                                
                                $plantilla = $bono->plantilla ?? null;
                                $serviciosBono = [];
                                if ($plantilla && $plantilla->servicios) {
                                    foreach ($plantilla->servicios as $servicioBono) {
                                        $cantidad = $servicioBono->pivot->cantidad ?? 1;
                                        $serviciosBono[] = $servicioBono->nombre . ' (x' . $cantidad . ')';
                                    }
                                }
                            @endphp
                            <div class="desglose-item bono bg-yellow-50 p-3 rounded-lg border-2 border-yellow-300">
                                <div class="font-medium text-gray-800 text-sm mb-1">
                                    {{ $plantilla ? $plantilla->nombre : 'Bono #' . $bono->id }}
                                </div>
                                @if(count($serviciosBono) > 0)
                                    <div class="text-xs text-gray-600 mb-2">
                                        {{ implode(', ', $serviciosBono) }}
                                    </div>
                                @endif
                                <div class="flex flex-wrap gap-2 mb-2">
                                    <span class="text-xs px-2 py-1 rounded-full
                                        {{ $bono->metodo_pago === 'efectivo' ? 'bg-green-200 text-green-800' : '' }}
                                        {{ $bono->metodo_pago === 'tarjeta' ? 'bg-blue-200 text-blue-800' : '' }}
                                        {{ $bono->metodo_pago === 'deuda' ? 'bg-red-200 text-red-800' : '' }}">
                                        @if($bono->metodo_pago === 'efectivo')
                                            💵 Efectivo
                                        @elseif($bono->metodo_pago === 'tarjeta')
                                            💳 Tarjeta
                                        @elseif($bono->metodo_pago === 'deuda')
                                            ⚠️ A deber
                                        @else
                                            {{ ucfirst($bono->metodo_pago) }}
                                        @endif
                                    </span>
                                    @if($deudaBono > 0)
                                        <span class="text-xs px-2 py-1 rounded-full bg-red-200 text-red-800 font-semibold">
                                            Deuda: {{ number_format($deudaBono, 2) }} €
                                        </span>
                                    @endif
                                </div>
                                <div class="text-lg font-bold text-yellow-700">
                                    {{ number_format($precioTotal, 2) }} €
                                </div>
                                @if($precioPagado > 0 && $precioPagado < $precioTotal)
                                    <div class="text-xs text-green-600 mt-1">
                                        Pagado: {{ number_format($precioPagado, 2) }} €
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            
            <!-- Resumen Financiero -->
            <div class="section-card bg-gradient-to-r from-gray-50 to-gray-100 border-2 border-gray-300">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span>💰</span> RESUMEN FINANCIERO
                </h2>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Coste Base -->
                    <div class="bg-white p-3 rounded-lg">
                        <div class="text-xs text-gray-500 mb-1">Coste Base</div>
                        <div class="text-xl font-bold text-gray-700">
                            {{ number_format($cobro->coste, 2) }} €
                        </div>
                    </div>
                    
                    <!-- Descuentos -->
                    @if(($cobro->descuento_porcentaje ?? 0) > 0 || ($cobro->descuento_euro ?? 0) > 0)
                    <div class="bg-white p-3 rounded-lg">
                        <div class="text-xs text-gray-500 mb-1">Descuentos</div>
                        <div class="text-xl font-bold text-orange-600">
                            @if(($cobro->descuento_porcentaje ?? 0) > 0)
                                {{ $cobro->descuento_porcentaje }}%
                            @endif
                            @if(($cobro->descuento_euro ?? 0) > 0)
                                -{{ number_format($cobro->descuento_euro, 2) }} €
                            @endif
                        </div>
                    </div>
                    @endif
                    
                    <!-- Total Facturado -->
                    <div class="bg-white p-3 rounded-lg">
                        <div class="text-xs text-gray-500 mb-1">Total Facturado</div>
                        <div class="text-xl font-bold text-green-600">
                            @php
                                $totalBonosVendidos = 0;
                                if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                                    foreach ($cobro->bonosVendidos as $bono) {
                                        $totalBonosVendidos += $bono->pivot->precio ?? 0;
                                    }
                                }
                                $totalFacturado = $cobro->total_final + ($cobro->deuda ?? 0) + $totalBonosVendidos;
                            @endphp
                            {{ number_format($totalFacturado, 2) }} €
                        </div>
                    </div>
                    
                    <!-- Deuda -->
                    @if(($cobro->deuda ?? 0) > 0 || $totalBonosVendidos > 0)
                    <div class="bg-white p-3 rounded-lg">
                        <div class="text-xs text-gray-500 mb-1">Deuda</div>
                        <div class="text-xl font-bold text-red-600">
                            @php
                                $deudaTotal = $cobro->deuda ?? 0;
                                if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                                    foreach ($cobro->bonosVendidos as $bono) {
                                        $precioTotal = $bono->pivot->precio ?? 0;
                                        $precioPagado = $bono->precio_pagado ?? 0;
                                        $deudaTotal += max(0, $precioTotal - $precioPagado);
                                    }
                                }
                            @endphp
                            {{ number_format($deudaTotal, 2) }} €
                        </div>
                    </div>
                    @endif
                </div>
                
                <!-- Información de Pago -->
                <div class="mt-4 pt-4 border-t border-gray-300">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Método de Pago:</span>
                            <span class="font-semibold px-3 py-1 rounded-full
                                {{ $cobro->metodo_pago === 'efectivo' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $cobro->metodo_pago === 'tarjeta' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $cobro->metodo_pago === 'mixto' ? 'bg-purple-100 text-purple-700' : '' }}
                                {{ $cobro->metodo_pago === 'bono' ? 'bg-yellow-100 text-yellow-700' : '' }}">
                                @if($cobro->metodo_pago === 'efectivo')
                                    💵 Efectivo
                                @elseif($cobro->metodo_pago === 'tarjeta')
                                    💳 Tarjeta
                                @elseif($cobro->metodo_pago === 'mixto')
                                    💵💳 Mixto
                                @elseif($cobro->metodo_pago === 'bono')
                                    🎫 Bono
                                @else
                                    {{ ucfirst($cobro->metodo_pago) }}
                                @endif
                            </span>
                        </div>
                        
                        @if($cobro->metodo_pago === 'mixto')
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Efectivo:</span>
                                <span class="font-semibold text-green-700">{{ number_format($cobro->pago_efectivo ?? 0, 2) }} €</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Tarjeta:</span>
                                <span class="font-semibold text-blue-700">{{ number_format($cobro->pago_tarjeta ?? 0, 2) }} €</span>
                            </div>
                        @endif
                        
                        @if($cobro->cambio > 0)
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Cambio:</span>
                                <span class="font-semibold text-gray-700">{{ number_format($cobro->cambio, 2) }} €</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Botones de Navegación -->
            <div class="mt-6 flex justify-between items-center">
                <a href="{{ route('cobros.index') }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold shadow-md hover:shadow-lg transition">
                    ← Volver al Listado
                </a>
                <div class="flex gap-3">
                    <a href="{{ route('cobros.edit', $cobro->id) }}" class="bg-yellow-500 text-white px-6 py-3 rounded-lg hover:bg-yellow-600 font-semibold shadow-md hover:shadow-lg transition">
                        ✏️ Editar
                    </a>
                    <a href="{{ route('dashboard') }}" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 font-semibold shadow-md hover:shadow-lg transition">
                        🏠 Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>