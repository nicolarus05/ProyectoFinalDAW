<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Cobros</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded shadow">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Registros de Cobro</h1>
            <a href="{{ route('citas.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">← Volver a las citas</a>
        </div>

        <!-- Navegación por fecha -->
        <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('cobros.index', ['fecha' => \Carbon\Carbon::parse($fecha)->subDay()->format('Y-m-d')]) }}" 
                       class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                        ◀ Día Anterior
                    </a>
                    
                    <div class="flex items-center gap-2">
                        <label for="fecha-selector" class="font-semibold text-gray-700">📅 Fecha:</label>
                        <input type="date" 
                               id="fecha-selector" 
                               value="{{ $fecha }}" 
                               max="{{ now()->format('Y-m-d') }}"
                               onchange="window.location.href='{{ route('cobros.index') }}?fecha=' + this.value"
                               class="border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <a href="{{ route('cobros.index', ['fecha' => now()->format('Y-m-d')]) }}" 
                       class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                        Hoy
                    </a>
                    
                    @if($fecha < now()->format('Y-m-d'))
                    <a href="{{ route('cobros.index', ['fecha' => \Carbon\Carbon::parse($fecha)->addDay()->format('Y-m-d')]) }}" 
                       class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                        Día Siguiente ▶
                    </a>
                    @endif
                </div>
                
                <div class="text-right">
                    <div class="text-lg font-bold text-gray-800">
                        {{ $fechaCarbon->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                    </div>
                    <div class="text-sm text-gray-600">
                        Total de cobros: <span class="font-bold text-green-600">{{ $cobros->count() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4 flex gap-3">
            <a href="{{ route('cobros.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Cobrar Cita
            </a>
            <a href="{{ route('cobros.create.direct') }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 font-semibold">
                💰 Nuevo Cobro Directo
            </a>
        </div>

        <div class="overflow-x-auto rounded-lg">
            <table class="min-w-full border border-gray-300 text-sm rounded-lg overflow-hidden">
                <thead class="bg-gray-200 text-gray-700">
                    <tr>
                        <th class="p-2 border">Hora</th>
                        <th class="p-2 border">Cliente</th>
                        <th class="p-2 border">Empleado</th>
                        <th class="p-2 border">Servicio</th>
                        <th class="p-2 border">Productos</th>
                        <th class="p-2 border">Coste</th>
                        <th class="p-2 border">Desc. %</th>
                        <th class="p-2 border">Desc. €</th>
                        <th class="p-2 border">Total Facturado</th>
                        <th class="p-2 border">Dinero Cliente</th>
                        <th class="p-2 border">Deuda</th>
                        <th class="p-2 border">Cambio</th>
                        <th class="p-2 border">Método Pago</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cobros as $cobro)
                        <tr class="text-center border-t hover:bg-gray-50">
                            <!-- Hora de la cita -->
                            <td class="p-2 border font-semibold text-gray-700">
                                @php
                                    $horaCita = null;
                                    
                                    // Intentar obtener la hora de la cita principal
                                    if ($cobro->cita && $cobro->cita->fecha_hora) {
                                        $horaCita = \Carbon\Carbon::parse($cobro->cita->fecha_hora)->format('H:i');
                                    }
                                    // Si no, intentar de citas agrupadas
                                    elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                                        $primeraCita = $cobro->citasAgrupadas->first();
                                        if ($primeraCita && $primeraCita->fecha_hora) {
                                            $horaCita = \Carbon\Carbon::parse($primeraCita->fecha_hora)->format('H:i');
                                        }
                                    }
                                    // Si no hay cita, usar la hora del cobro (para cobros directos)
                                    if (!$horaCita && $cobro->created_at) {
                                        $horaCita = \Carbon\Carbon::parse($cobro->created_at)->format('H:i');
                                    }
                                @endphp
                                {{ $horaCita ?? '-' }}
                            </td>
                            
                            <td class="p-2 border">
                                @if($cobro->cita && $cobro->cita->cliente && $cobro->cita->cliente->user)
                                    {{ $cobro->cita->cliente->user->nombre }}
                                @elseif($cobro->cliente && $cobro->cliente->user)
                                    {{ $cobro->cliente->user->nombre }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="p-2 border">
                                @if($cobro->cita && $cobro->cita->empleado && $cobro->cita->empleado->user)
                                    {{ $cobro->cita->empleado->user->nombre }}
                                @elseif($cobro->empleado && $cobro->empleado->user)
                                    {{ $cobro->empleado->user->nombre }}
                                @else
                                    -
                                @endif
                            </td>

                            <!-- Servicios -->
                            <td class="p-2 border">
                                @php
                                    $serviciosNombres = [];
                                    $yaContados = false;
                                    
                                    // PRIORIDAD 1: Servicios de cita individual
                                    if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                                        $serviciosNombres = $cobro->cita->servicios->pluck('nombre')->toArray();
                                        $yaContados = true;
                                    }
                                    
                                    // PRIORIDAD 2: Servicios de citas agrupadas (solo si no tiene cita individual)
                                    if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                                        foreach ($cobro->citasAgrupadas as $citaGrupo) {
                                            if ($citaGrupo->servicios) {
                                                $serviciosNombres = array_merge($serviciosNombres, $citaGrupo->servicios->pluck('nombre')->toArray());
                                            }
                                        }
                                        $yaContados = true;
                                    }
                                    
                                    // PRIORIDAD 3: Servicios directos (solo si no tiene citas)
                                    if (!$yaContados && $cobro->servicios && $cobro->servicios->count() > 0) {
                                        $serviciosNombres = $cobro->servicios->pluck('nombre')->toArray();
                                    }
                                    
                                    $servicios = !empty($serviciosNombres) ? implode(', ', $serviciosNombres) : '-';
                                @endphp
                                {{ $servicios }}
                            </td>

                            <!-- Productos -->
                            <td class="p-2 border text-left">
                                @if($cobro->productos && $cobro->productos->count() > 0)
                                    <ul class="list-disc list-inside">
                                        @foreach($cobro->productos as $prod)
                                            <li>
                                                {{ $prod->nombre }}
                                                <span class="text-gray-500">(x{{ $prod->pivot->cantidad }})</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-gray-400 italic">—</span>
                                @endif
                            </td>

                            <td class="p-2 border">{{ number_format($cobro->coste, 2) }} €</td>
                            <td class="p-2 border">{{ $cobro->descuento_porcentaje ?? 0 }}%</td>
                            <td class="p-2 border">{{ number_format($cobro->descuento_euro ?? 0, 2) }} €</td>
                            <td class="p-2 border font-semibold">
                                @php
                                    // Total facturado = total_final (lo que se cobró sin deuda)
                                    $totalCobrado = $cobro->total_final;
                                @endphp
                                {{ number_format($totalCobrado, 2) }} €
                            </td>
                            <td class="p-2 border">{{ number_format($cobro->total_final, 2) }} €</td>
                            <td class="p-2 border {{ ($cobro->deuda ?? 0) > 0 ? 'bg-red-100 text-red-700 font-semibold' : '' }}">
                                @php
                                    // Calcular deuda total: deuda de servicios + deuda de bonos
                                    $deudaTotal = $cobro->deuda ?? 0;
                                    // Sumar deuda de bonos vendidos en este cobro
                                    if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                                        foreach ($cobro->bonosVendidos as $bono) {
                                            $precioTotalBono = $bono->pivot->precio ?? 0;
                                            $precioPagadoBono = $bono->precio_pagado ?? 0;
                                            $deudaBono = max(0, $precioTotalBono - $precioPagadoBono);
                                            $deudaTotal += $deudaBono;
                                        }
                                    }
                                @endphp
                                {{ number_format($deudaTotal, 2) }} €
                            </td>
                            <td class="p-2 border">{{ number_format($cobro->cambio, 2) }} €</td>
                            <td class="p-2 border capitalize">
                                @if($cobro->metodo_pago === 'mixto')
                                    <span class="font-semibold text-purple-700">Mixto</span>
                                    <div class="text-xs mt-1 space-y-0.5">
                                        <div class="text-green-700">💵 Efectivo: €{{ number_format($cobro->pago_efectivo ?? 0, 2) }}</div>
                                        <div class="text-blue-700">💳 Tarjeta: €{{ number_format($cobro->pago_tarjeta ?? 0, 2) }}</div>
                                    </div>
                                @elseif($cobro->metodo_pago === 'efectivo')
                                    <span class="text-green-700">💵 Efectivo</span>
                                @elseif($cobro->metodo_pago === 'tarjeta')
                                    <span class="text-blue-700">💳 Tarjeta</span>
                                @elseif($cobro->metodo_pago === 'bono')
                                    <span class="text-purple-700">🎫 Bono</span>
                                @else
                                    {{ $cobro->metodo_pago }}
                                @endif
                            </td>

                            <td class="p-2 border space-y-1">
                                <a href="{{ route('cobros.show', $cobro->id) }}" class="text-blue-600 hover:underline block">Ver</a>
                                <a href="{{ route('cobros.edit', $cobro->id) }}" class="text-yellow-600 hover:underline block">Editar</a>
                                <form action="{{ route('cobros.destroy', $cobro->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este cobro?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-4 text-gray-500">No hay cobros registrados para esta fecha.</td>
                        </tr>
                    @endforelse
                </tbody>
                
                @if($cobros->count() > 0)
                <tfoot class="bg-gray-100 font-bold border-t-2 border-gray-400">
                    <tr>
                        <td colspan="8" class="p-3 text-right border">TOTALES DEL DÍA:</td>
                        <td class="p-3 border text-center text-green-700 text-lg">
                            @php
                                // Total facturado = solo lo que se cobró (total_final)
                                $totalFacturadoDia = $cobros->sum('total_final');
                            @endphp
                            {{ number_format($totalFacturadoDia, 2) }} €
                            <div class="text-xs text-gray-600 mt-1">
                                (Total Facturado)
                            </div>
                        </td>
                        <td class="p-3 border text-center">
                            @php
                                // Deuda total = deuda de servicios + deuda de bonos
                                $totalDeudaDia = $cobros->sum('deuda');
                                // Sumar deuda de bonos
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
                            <div class="text-red-700 text-base">
                                {{ number_format($totalDeudaDia, 2) }} €
                            </div>
                            <div class="text-xs text-gray-600">
                                (Deuda)
                            </div>
                        </td>
                        <td colspan="3" class="p-3 border text-sm">
                            @php
                                // Calcular efectivo: solo lo cobrado en efectivo (servicios + bonos pagados)
                                $totalEfectivo = 0;
                                $totalTarjeta = 0;
                                $totalBonosPago = 0;
                                
                                foreach($cobros as $cobro) {
                                    if ($cobro->metodo_pago === 'efectivo') {
                                        // Sumar lo cobrado de servicios
                                        $totalEfectivo += $cobro->total_final;
                                        // Sumar bonos pagados en efectivo
                                        if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                                            foreach ($cobro->bonosVendidos as $bono) {
                                                if ($bono->metodo_pago === 'efectivo') {
                                                    $totalEfectivo += $bono->precio_pagado ?? 0;
                                                }
                                            }
                                        }
                                    } elseif ($cobro->metodo_pago === 'tarjeta') {
                                        // Sumar lo cobrado de servicios
                                        $totalTarjeta += $cobro->total_final;
                                        // Sumar bonos pagados en tarjeta
                                        if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                                            foreach ($cobro->bonosVendidos as $bono) {
                                                if ($bono->metodo_pago === 'tarjeta') {
                                                    $totalTarjeta += $bono->precio_pagado ?? 0;
                                                }
                                            }
                                        }
                                    } elseif ($cobro->metodo_pago === 'mixto') {
                                        // Pago mixto: usar las cantidades específicas
                                        $totalEfectivo += $cobro->pago_efectivo ?? 0;
                                        $totalTarjeta += $cobro->pago_tarjeta ?? 0;
                                    } elseif ($cobro->metodo_pago === 'bono') {
                                        // Servicios pagados con bono
                                        $totalBonosPago += $cobro->total_final;
                                    }
                                }
                            @endphp
                            <div class="flex gap-4 justify-center">
                                <span class="text-green-700">💵 Efectivo: €{{ number_format($totalEfectivo, 2) }}</span>
                                <span class="text-blue-700">💳 Tarjeta: €{{ number_format($totalTarjeta, 2) }}</span>
                                <span class="text-purple-700">🎫 Bonos: €{{ number_format($totalBonosPago, 2) }}</span>
                            </div>
                        </td></td>
                            @php
                                // Calcular efectivo: solo lo cobrado en efectivo (servicios + bonos pagados)
                                $totalEfectivo = 0;
                                $totalTarjeta = 0;
                                $totalBonosPago = 0;
                                
                                foreach($cobros as $cobro) {
                                    if ($cobro->metodo_pago === 'efectivo') {
                                        // Sumar lo cobrado de servicios
                                        $totalEfectivo += $cobro->total_final;
                                        // Sumar bonos pagados en efectivo
                                        if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                                            foreach ($cobro->bonosVendidos as $bono) {
                                                if ($bono->metodo_pago === 'efectivo') {
                                                    $totalEfectivo += $bono->precio_pagado ?? 0;
                                                }
                                            }
                                        }
                                    } elseif ($cobro->metodo_pago === 'tarjeta') {
                                        // Sumar lo cobrado de servicios
                                        $totalTarjeta += $cobro->total_final;
                                        // Sumar bonos pagados en tarjeta
                                        if ($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0) {
                                            foreach ($cobro->bonosVendidos as $bono) {
                                                if ($bono->metodo_pago === 'tarjeta') {
                                                    $totalTarjeta += $bono->precio_pagado ?? 0;
                                                }
                                            }
                                        }
                                    } elseif ($cobro->metodo_pago === 'mixto') {
                                        // Pago mixto: usar las cantidades específicas
                                        $totalEfectivo += $cobro->pago_efectivo ?? 0;
                                        $totalTarjeta += $cobro->pago_tarjeta ?? 0;
                                    } elseif ($cobro->metodo_pago === 'bono') {
                                        // Servicios pagados con bono
                                        $totalBonosPago += $cobro->total_final;
                                    }
                                }
                            @endphp
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</body>
</html>
