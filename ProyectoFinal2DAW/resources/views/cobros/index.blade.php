<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Cobros</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .cobro-card { transition: all 0.2s; }
        .cobro-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .desglose-item { border-left: 3px solid #e5e7eb; padding-left: 0.75rem; }
        .desglose-item.servicio { border-left-color: #3b82f6; }
        .desglose-item.producto { border-left-color: #10b981; }
        .desglose-item.bono { border-left-color: #f59e0b; }
        .empleado-tag { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .bono-badge { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; padding: 2px 8px; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded shadow">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">📋 Registros de Cobro</h1>
            <a href="{{ route('citas.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition">← Volver a las citas</a>
        </div>

        <!-- Navegación por fecha -->
        <div class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg border-2 border-blue-200">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('cobros.index', ['fecha' => \Carbon\Carbon::parse($fecha)->subDay()->format('Y-m-d')]) }}" 
                       class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                        ◀ Anterior
                    </a>
                    
                    <div class="flex items-center gap-2">
                        <label for="fecha-selector" class="font-semibold text-gray-700">📅</label>
                        <input type="date" 
                               id="fecha-selector" 
                               value="{{ $fecha }}" 
                               max="{{ now()->format('Y-m-d') }}"
                               onchange="window.location.href='{{ route('cobros.index') }}?fecha=' + this.value"
                               class="border-2 border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <a href="{{ route('cobros.index', ['fecha' => now()->format('Y-m-d')]) }}" 
                       class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition font-semibold">
                        📍 Hoy
                    </a>
                    
                    @if($fecha < now()->format('Y-m-d'))
                    <a href="{{ route('cobros.index', ['fecha' => \Carbon\Carbon::parse($fecha)->addDay()->format('Y-m-d')]) }}" 
                       class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                        Siguiente ▶
                    </a>
                    @endif
                </div>
                
                <div class="text-right">
                    <div class="text-xl font-bold text-gray-800">
                        {{ $fechaCarbon->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                    </div>
                    <div class="text-sm text-gray-600">
                        <span class="font-bold text-blue-600">{{ $cobros->count() }}</span> {{ $cobros->count() === 1 ? 'cobro registrado' : 'cobros registrados' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-6 flex gap-3">
            <a href="{{ route('cobros.create') }}" class="bg-blue-600 text-white px-5 py-3 rounded-lg hover:bg-blue-700 font-semibold shadow-md hover:shadow-lg transition">
                🎫 Cobrar Cita
            </a>
            <a href="{{ route('cobros.create.direct') }}" class="bg-green-600 text-white px-5 py-3 rounded-lg hover:bg-green-700 font-semibold shadow-md hover:shadow-lg transition">
                💰 Nuevo Cobro Directo
            </a>
        </div>

        <!-- Lista de cobros como cards -->
        <div class="space-y-4">
            @forelse ($cobros as $cobro)
                @php
                    // Obtener hora del cobro
                    $horaCita = null;
                    if ($cobro->cita && $cobro->cita->fecha_hora) {
                        $horaCita = \Carbon\Carbon::parse($cobro->cita->fecha_hora)->format('H:i');
                    } elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                        $primeraCita = $cobro->citasAgrupadas->first();
                        if ($primeraCita && $primeraCita->fecha_hora) {
                            $horaCita = \Carbon\Carbon::parse($primeraCita->fecha_hora)->format('H:i');
                        }
                    }
                    if (!$horaCita && $cobro->created_at) {
                        $horaCita = \Carbon\Carbon::parse($cobro->created_at)->format('H:i');
                    }
                    
                    // Cliente
                    $clienteNombre = '-';
                    if ($cobro->cita && $cobro->cita->cliente && $cobro->cita->cliente->user) {
                        $clienteNombre = $cobro->cita->cliente->user->nombre;
                    } elseif ($cobro->cliente && $cobro->cliente->user) {
                        $clienteNombre = $cobro->cliente->user->nombre;
                    }
                    
                    // Calcular deuda total
                    $deudaTotal = $cobro->deuda ?? 0;
                    if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                        foreach ($cobro->bonosVendidos as $bono) {
                            $precioTotalBono = $bono->pivot->precio ?? 0;
                            $precioPagadoBono = $bono->precio_pagado ?? 0;
                            $deudaBono = max(0, $precioTotalBono - $precioPagadoBono);
                            $deudaTotal += $deudaBono;
                        }
                    }
                    
                    // Verificar si hay bonos vendidos
                    $tieneBonos = $cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0;
                @endphp
                
                <div class="cobro-card bg-white border-2 border-gray-200 rounded-lg p-5 hover:border-blue-300">
                    <!-- Header del cobro -->
                    <div class="flex justify-between items-start mb-4 pb-3 border-b-2 border-gray-100">
                        <div class="flex items-center gap-4">
                            <div class="text-2xl font-bold text-blue-600">
                                🕐 {{ $horaCita ?? '-' }}
                            </div>
                            <div>
                                <div class="text-lg font-semibold text-gray-800">
                                    👤 {{ $clienteNombre }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    ID Cobro: #{{ $cobro->id }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <div class="text-2xl font-bold text-green-600">
                                {{ number_format($cobro->total_final, 2) }} €
                            </div>
                            <div class="text-xs text-gray-500">Total Facturado</div>
                            @if($deudaTotal > 0)
                                <div class="mt-1 px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-semibold">
                                    ⚠️ Deuda: {{ number_format($deudaTotal, 2) }} €
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Contenido: Servicios, Productos y Bonos -->
                    <div class="grid grid-cols-1 {{ $tieneBonos ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }} gap-4 mb-4">
                        <!-- SERVICIOS -->
                        <div class="space-y-2">
                            <div class="font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                <span class="text-blue-600">✂️</span> SERVICIOS
                            </div>
                            
                            @php
                                // Obtener servicios con su información detallada
                                $serviciosDetalle = [];
                                
                                // CASO 1: Cita individual
                                if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                                    $empleado = $cobro->cita->empleado && $cobro->cita->empleado->user 
                                        ? $cobro->cita->empleado->user->nombre 
                                        : 'Sin asignar';
                                    
                                    foreach ($cobro->cita->servicios as $servicio) {
                                        // Buscar precio en registro_cobro_servicio
                                        $precioServicio = \DB::table('registro_cobro_servicio')
                                            ->where('registro_cobro_id', $cobro->id)
                                            ->where('servicio_id', $servicio->id)
                                            ->value('precio') ?? $servicio->precio;
                                        
                                        // Verificar si fue pagado con bono
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
                                // CASO 2: Citas agrupadas
                                elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                                    foreach ($cobro->citasAgrupadas as $citaGrupo) {
                                        $empleado = $citaGrupo->empleado && $citaGrupo->empleado->user 
                                            ? $citaGrupo->empleado->user->nombre 
                                            : 'Sin asignar';
                                        
                                        if ($citaGrupo->servicios) {
                                            foreach ($citaGrupo->servicios as $servicio) {
                                                $precioServicio = \DB::table('registro_cobro_servicio')
                                                    ->where('registro_cobro_id', $cobro->id)
                                                    ->where('servicio_id', $servicio->id)
                                                    ->value('precio') ?? $servicio->precio;
                                                
                                                // Verificar si fue pagado con bono
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
                                // CASO 3: Cobro directo (servicios sin cita)
                                elseif ($cobro->servicios && $cobro->servicios->count() > 0) {
                                    $empleado = $cobro->empleado && $cobro->empleado->user 
                                        ? $cobro->empleado->user->nombre 
                                        : 'Sin asignar';
                                    
                                    foreach ($cobro->servicios as $servicio) {
                                        $precioServicio = $servicio->pivot->precio ?? $servicio->precio;
                                        
                                        // Para cobros directos, verificar si el método de pago es bono
                                        $pagadoConBono = $cobro->metodo_pago === 'bono';
                                        
                                        $serviciosDetalle[] = [
                                            'nombre' => $servicio->nombre,
                                            'precio' => $precioServicio,
                                            'empleado' => $empleado,
                                            'es_bono' => $pagadoConBono
                                        ];
                                    }
                                }
                            @endphp
                            
                            @if(count($serviciosDetalle) > 0)
                                @foreach($serviciosDetalle as $servicio)
                                    <div class="desglose-item servicio bg-blue-50 p-3 rounded-lg {{ $servicio['es_bono'] ? 'border-2 border-yellow-400' : '' }}">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <div class="font-medium text-gray-800">{{ $servicio['nombre'] }}</div>
                                                    @if($servicio['es_bono'])
                                                        <span class="bono-badge">🎫 BONO</span>
                                                    @endif
                                                </div>
                                                <div class="empleado-tag bg-blue-200 text-blue-800">
                                                    👨‍💼 {{ $servicio['empleado'] }}
                                                </div>
                                            </div>
                                            <div class="text-lg font-bold {{ $servicio['es_bono'] ? 'text-yellow-600' : 'text-blue-700' }} ml-3">
                                                {{ number_format($servicio['precio'], 2) }} €
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-gray-400 italic text-sm bg-gray-50 p-3 rounded">
                                    Sin servicios
                                </div>
                            @endif
                        </div>
                        
                        <!-- PRODUCTOS -->
                        <div class="space-y-2">
                            <div class="font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                <span class="text-green-600">🛍️</span> PRODUCTOS
                            </div>
                            
                            @if($cobro->productos && $cobro->productos->count() > 0)
                                @foreach($cobro->productos as $producto)
                                    @php
                                        $cantidad = $producto->pivot->cantidad ?? 1;
                                        $precioUnitario = $producto->pivot->precio ?? $producto->precio;
                                        $subtotal = $precioUnitario * $cantidad;
                                    @endphp
                                    <div class="desglose-item producto bg-green-50 p-3 rounded-lg">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-800">{{ $producto->nombre }}</div>
                                                <div class="text-sm text-gray-600 mt-1">
                                                    {{ $cantidad }}x unidad{{ $cantidad > 1 ? 'es' : '' }} × {{ number_format($precioUnitario, 2) }} €
                                                </div>
                                            </div>
                                            <div class="text-lg font-bold text-green-700 ml-3">
                                                {{ number_format($subtotal, 2) }} €
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-gray-400 italic text-sm bg-gray-50 p-3 rounded">
                                    Sin productos
                                </div>
                            @endif
                        </div>
                        
                        <!-- BONOS VENDIDOS -->
                        @if($tieneBonos)
                        <div class="space-y-2">
                            <div class="font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                <span class="text-yellow-600">🎫</span> BONOS VENDIDOS
                            </div>
                            
                            @foreach($cobro->bonosVendidos as $bono)
                                @php
                                    $precioTotal = $bono->pivot->precio ?? 0;
                                    $precioPagado = $bono->precio_pagado ?? 0;
                                    $deudaBono = max(0, $precioTotal - $precioPagado);
                                    
                                    // Obtener template del bono para mostrar servicios incluidos
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
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-800 mb-1">
                                                {{ $plantilla ? $plantilla->nombre : 'Bono #' . $bono->id }}
                                            </div>
                                            @if(count($serviciosBono) > 0)
                                                <div class="text-xs text-gray-600 mb-2">
                                                    Incluye: {{ implode(', ', $serviciosBono) }}
                                                </div>
                                            @endif
                                            <div class="flex flex-wrap gap-2 mt-2">
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
                                        </div>
                                        <div class="ml-3 text-right">
                                            <div class="text-lg font-bold text-yellow-700">
                                                {{ number_format($precioTotal, 2) }} €
                                            </div>
                                            @if($precioPagado > 0 && $precioPagado < $precioTotal)
                                                <div class="text-xs text-green-600">
                                                    Pagado: {{ number_format($precioPagado, 2) }} €
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    
                    <!-- Footer: Información de pago y acciones -->
                    <div class="flex justify-between items-center pt-3 border-t-2 border-gray-100">
                        <div class="flex gap-4 items-center">
                            <!-- Método de pago -->
                            <div class="px-4 py-2 rounded-lg font-semibold
                                {{ $cobro->metodo_pago === 'efectivo' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $cobro->metodo_pago === 'tarjeta' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $cobro->metodo_pago === 'mixto' ? 'bg-purple-100 text-purple-700' : '' }}
                                {{ $cobro->metodo_pago === 'bono' ? 'bg-yellow-100 text-yellow-700' : '' }}">
                                @if($cobro->metodo_pago === 'mixto')
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm">💵💳 Pago Mixto</span>
                                        <div class="text-xs space-y-0.5">
                                            <div>Efectivo: €{{ number_format($cobro->pago_efectivo ?? 0, 2) }}</div>
                                            <div>Tarjeta: €{{ number_format($cobro->pago_tarjeta ?? 0, 2) }}</div>
                                        </div>
                                    </div>
                                @elseif($cobro->metodo_pago === 'efectivo')
                                    💵 Efectivo
                                @elseif($cobro->metodo_pago === 'tarjeta')
                                    💳 Tarjeta
                                @elseif($cobro->metodo_pago === 'bono')
                                    🎫 Bono
                                @else
                                    {{ ucfirst($cobro->metodo_pago) }}
                                @endif
                            </div>
                            
                            <!-- Descuentos -->
                            @if(($cobro->descuento_porcentaje ?? 0) > 0 || ($cobro->descuento_euro ?? 0) > 0)
                                <div class="text-sm bg-orange-100 text-orange-700 px-3 py-2 rounded-lg">
                                    <span class="font-semibold">🏷️ Descuentos:</span>
                                    @if(($cobro->descuento_porcentaje ?? 0) > 0)
                                        {{ $cobro->descuento_porcentaje }}%
                                    @endif
                                    @if(($cobro->descuento_euro ?? 0) > 0)
                                        {{ number_format($cobro->descuento_euro, 2) }} €
                                    @endif
                                </div>
                            @endif
                            
                            <!-- Cambio -->
                            @if($cobro->cambio > 0)
                                <div class="text-sm bg-gray-100 text-gray-700 px-3 py-2 rounded-lg">
                                    💸 Cambio: {{ number_format($cobro->cambio, 2) }} €
                                </div>
                            @endif
                        </div>
                        
                        <!-- Acciones -->
                        <div class="flex gap-2">
                            <a href="{{ route('cobros.show', $cobro->id) }}" 
                               class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition text-sm font-medium">
                                👁️ Ver
                            </a>
                            <a href="{{ route('cobros.edit', $cobro->id) }}" 
                               class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600 transition text-sm font-medium">
                                ✏️ Editar
                            </a>
                            <form action="{{ route('cobros.destroy', $cobro->id) }}" method="POST" 
                                  onsubmit="return confirm('¿Seguro que deseas eliminar este cobro?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition text-sm font-medium">
                                    🗑️ Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                    <div class="text-6xl mb-4">📭</div>
                    <div class="text-xl text-gray-600 font-semibold">No hay cobros registrados para esta fecha</div>
                    <div class="text-gray-500 mt-2">Selecciona otra fecha o crea un nuevo cobro</div>
                </div>
            @endforelse
        </div>
        
        <!-- Resumen de totales del día -->
        @if($cobros->count() > 0)
            <div class="mt-8 bg-gradient-to-r from-gray-50 to-gray-100 border-2 border-gray-300 rounded-lg p-6 shadow-md">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span>📊</span> RESUMEN DEL DÍA
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Facturado -->
                    <div class="bg-white border-2 border-green-200 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1">Total Facturado</div>
                        @php
                            $totalFacturadoDia = 0;
                            foreach($cobros as $cobro) {
                                if ($cobro->metodo_pago !== 'bono') {
                                    $totalFacturadoDia += $cobro->total_final;
                                }
                                if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                                    foreach ($cobro->bonosVendidos as $bono) {
                                        if ($bono->metodo_pago !== 'deuda') {
                                            $totalFacturadoDia += $bono->precio_pagado ?? 0;
                                        }
                                    }
                                }
                            }
                        @endphp
                        <div class="text-3xl font-bold text-green-600">
                            {{ number_format($totalFacturadoDia, 2) }} €
                        </div>
                    </div>
                    
                    <!-- Total Deuda -->
                    <div class="bg-white border-2 border-red-200 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1">Deuda Pendiente</div>
                        @php
                            $totalDeudaDia = $cobros->sum('deuda');
                            foreach($cobros as $cobro) {
                                if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                                    foreach ($cobro->bonosVendidos as $bono) {
                                        $precioTotalBono = $bono->pivot->precio ?? 0;
                                        $precioPagadoBono = $bono->precio_pagado ?? 0;
                                        $deudaBono = max(0, $precioTotalBono - $precioPagadoBono);
                                        $totalDeudaDia += $deudaBono;
                                    }
                                }
                            }
                        @endphp
                        <div class="text-3xl font-bold text-red-600">
                            {{ number_format($totalDeudaDia, 2) }} €
                        </div>
                    </div>
                    
                    <!-- Efectivo -->
                    <div class="bg-white border-2 border-green-300 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1 flex items-center gap-1">
                            💵 Efectivo
                        </div>
                        @php
                            $totalEfectivo = 0;
                            foreach($cobros as $cobro) {
                                if ($cobro->metodo_pago === 'efectivo') {
                                    $totalEfectivo += $cobro->total_final;
                                    if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                                        foreach ($cobro->bonosVendidos as $bono) {
                                            if ($bono->metodo_pago === 'efectivo') {
                                                $totalEfectivo += $bono->precio_pagado ?? 0;
                                            }
                                        }
                                    }
                                } elseif ($cobro->metodo_pago === 'mixto') {
                                    $totalEfectivo += $cobro->pago_efectivo ?? 0;
                                }
                            }
                        @endphp
                        <div class="text-3xl font-bold text-green-700">
                            {{ number_format($totalEfectivo, 2) }} €
                        </div>
                    </div>
                    
                    <!-- Tarjeta -->
                    <div class="bg-white border-2 border-blue-300 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1 flex items-center gap-1">
                            💳 Tarjeta
                        </div>
                        @php
                            $totalTarjeta = 0;
                            foreach($cobros as $cobro) {
                                if ($cobro->metodo_pago === 'tarjeta') {
                                    $totalTarjeta += $cobro->total_final;
                                    if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                                        foreach ($cobro->bonosVendidos as $bono) {
                                            if ($bono->metodo_pago === 'tarjeta') {
                                                $totalTarjeta += $bono->precio_pagado ?? 0;
                                            }
                                        }
                                    }
                                } elseif ($cobro->metodo_pago === 'mixto') {
                                    $totalTarjeta += $cobro->pago_tarjeta ?? 0;
                                }
                            }
                        @endphp
                        <div class="text-3xl font-bold text-blue-600">
                            {{ number_format($totalTarjeta, 2) }} €
                        </div>
                    </div>
                </div>
                
                <!-- Bonos vendidos del día -->
                <div class="mt-4 bg-white border-2 border-yellow-300 rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="text-sm text-gray-600 font-semibold">💰 Total Bonos Vendidos</div>
                            <div class="text-xs text-gray-500 mt-1">
                                (Ingresos por venta de bonos + deuda de bonos)
                            </div>
                        </div>
                        @php
                            $totalBonosVendidos = 0;
                            $totalBonosVendidosPagados = 0;
                            $totalBonosVendidosDeuda = 0;
                            $cantidadBonosVendidos = 0;
                            
                            foreach($cobros as $cobro) {
                                if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                                    foreach ($cobro->bonosVendidos as $bono) {
                                        $cantidadBonosVendidos++;
                                        $precioTotal = $bono->pivot->precio ?? 0;
                                        $precioPagado = $bono->precio_pagado ?? 0;
                                        $deudaBono = max(0, $precioTotal - $precioPagado);
                                        
                                        $totalBonosVendidos += $precioTotal;
                                        $totalBonosVendidosPagados += $precioPagado;
                                        $totalBonosVendidosDeuda += $deudaBono;
                                    }
                                }
                            }
                        @endphp
                        <div class="text-right">
                            <div class="text-3xl font-bold text-yellow-600">
                                🎫 {{ number_format($totalBonosVendidos, 2) }} €
                            </div>
                            @if($cantidadBonosVendidos > 0)
                                <div class="text-xs text-gray-600 mt-1 space-y-0.5">
                                    <div class="text-green-600">✓ Cobrado: {{ number_format($totalBonosVendidosPagados, 2) }} €</div>
                                    @if($totalBonosVendidosDeuda > 0)
                                        <div class="text-red-600">⚠ A deber: {{ number_format($totalBonosVendidosDeuda, 2) }} €</div>
                                    @endif
                                    <div class="text-gray-500">{{ $cantidadBonosVendidos }} bono{{ $cantidadBonosVendidos > 1 ? 's' : '' }} vendido{{ $cantidadBonosVendidos > 1 ? 's' : '' }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Fila adicional: Bonos consumidos -->
                <div class="mt-4 bg-white border-2 border-purple-300 rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="text-sm text-gray-600">Servicios Pagados con Bono</div>
                            <div class="text-xs text-gray-500 mt-1">
                                (No genera ingreso directo, ya fue cobrado en la venta del bono)
                            </div>
                        </div>
                        @php
                            $totalBonosPago = 0;
                            foreach($cobros as $cobro) {
                                if ($cobro->metodo_pago === 'bono') {
                                    $totalBonosPago += $cobro->total_final;
                                }
                            }
                        @endphp
                        <div class="text-3xl font-bold text-purple-600">
                            🎫 {{ number_format($totalBonosPago, 2) }} €
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</body>
</html>