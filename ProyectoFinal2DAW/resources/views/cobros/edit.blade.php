<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cobro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .bono-badge { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; padding: 2px 6px; border-radius: 9999px; font-size: 0.65rem; font-weight: 700; }
        .info-badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
    </style>
    <script>
        function actualizarCosteYTotales() {
            const select = document.getElementById('id_cita');
            const selectedOption = select.options[select.selectedIndex];
            const costeServicio = parseFloat(selectedOption.getAttribute('data-coste')) || 0;

            document.getElementById('coste').value = costeServicio.toFixed(2);
            calcularTotales();
        }

        function calcularTotales() {
            const coste = parseFloat(document.getElementById('coste').value) || 0;
            const descPor = parseFloat(document.getElementById('descuento_porcentaje').value) || 0;
            const descEur = parseFloat(document.getElementById('descuento_euro').value) || 0;
            const dineroCliente = parseFloat(document.getElementById('dinero_cliente').value) || 0;

            const descuentoTotal = (coste * (descPor / 100)) + descEur;
            const totalFinal = Math.max(coste - descuentoTotal, 0);
            const cambio = Math.max(dineroCliente - totalFinal, 0);

            document.getElementById('total_final').value = totalFinal.toFixed(2);
            document.getElementById('cambio').value = cambio.toFixed(2);
        }
    </script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-5xl mx-auto">
        <!-- Información Actual del Cobro -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg shadow-md mb-6 border-2 border-blue-200">
            <h2 class="text-xl font-bold text-gray-800 mb-4">ℹ️ Información Actual del Cobro #{{ $cobro->id }}</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Servicios -->
                <div class="bg-white p-4 rounded-lg">
                    <div class="text-sm font-semibold text-gray-600 mb-2">✂️ Servicios</div>
                    @php
                        $serviciosInfo = [];
                        if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                            foreach ($cobro->cita->servicios as $servicio) {
                                $pagadoConBono = \DB::table('bono_uso_detalle')
                                    ->where('cita_id', $cobro->cita->id)
                                    ->where('servicio_id', $servicio->id)
                                    ->exists();
                                $serviciosInfo[] = ['nombre' => $servicio->nombre, 'es_bono' => $pagadoConBono];
                            }
                        } elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                            foreach ($cobro->citasAgrupadas as $citaGrupo) {
                                if ($citaGrupo->servicios) {
                                    foreach ($citaGrupo->servicios as $servicio) {
                                        $pagadoConBono = \DB::table('bono_uso_detalle')
                                            ->where('cita_id', $citaGrupo->id)
                                            ->where('servicio_id', $servicio->id)
                                            ->exists();
                                        $serviciosInfo[] = ['nombre' => $servicio->nombre, 'es_bono' => $pagadoConBono];
                                    }
                                }
                            }
                        } elseif ($cobro->servicios && $cobro->servicios->count() > 0) {
                            foreach ($cobro->servicios as $servicio) {
                                $pagadoConBono = $cobro->metodo_pago === 'bono';
                                $serviciosInfo[] = ['nombre' => $servicio->nombre, 'es_bono' => $pagadoConBono];
                            }
                        }
                    @endphp
                    
                    @if(count($serviciosInfo) > 0)
                        <ul class="space-y-1 text-sm">
                            @foreach($serviciosInfo as $servInfo)
                                <li class="flex items-center gap-2">
                                    <span class="text-gray-700">{{ $servInfo['nombre'] }}</span>
                                    @if($servInfo['es_bono'])
                                        <span class="bono-badge">🎫 BONO</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <span class="text-gray-400 text-sm">Sin servicios</span>
                    @endif
                </div>
                
                <!-- Productos -->
                <div class="bg-white p-4 rounded-lg">
                    <div class="text-sm font-semibold text-gray-600 mb-2">🛍️ Productos</div>
                    @if($cobro->productos && $cobro->productos->count() > 0)
                        <ul class="space-y-1 text-sm">
                            @foreach($cobro->productos as $producto)
                                <li class="text-gray-700">
                                    {{ $producto->nombre }} (x{{ $producto->pivot->cantidad ?? 1 }})
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <span class="text-gray-400 text-sm">Sin productos</span>
                    @endif
                </div>
                
                <!-- Bonos Vendidos -->
                <div class="bg-white p-4 rounded-lg">
                    <div class="text-sm font-semibold text-gray-600 mb-2">🎫 Bonos Vendidos</div>
                    @if($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0)
                        <ul class="space-y-1 text-sm">
                            @foreach($cobro->bonosVendidos as $bono)
                                @php
                                    $plantilla = $bono->plantilla ?? null;
                                    $nombreBono = $plantilla ? $plantilla->nombre : 'Bono #' . $bono->id;
                                    $precioTotal = $bono->pivot->precio ?? 0;
                                @endphp
                                <li class="text-gray-700">
                                    {{ $nombreBono }}
                                    <span class="text-yellow-600 font-semibold">({{ number_format($precioTotal, 2) }}€)</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <span class="text-gray-400 text-sm">Sin bonos vendidos</span>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Formulario de Edición -->
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <h1 class="text-3xl font-bold mb-6">✏️ Editar Cobro</h1>

        <form action="{{ route('cobros.update', $cobro->id) }}" method="POST" class="space-y-5" oninput="calcularTotales()">
            @csrf
            @method('PUT')

            {{-- Cita --}}
            <div class="mb-4">
            <label for="id_cita" class="block font-semibold mb-1">Cita (opcional):</label>
            <select name="id_cita" id="id_cita" class="w-full border rounded px-3 py-2" onchange="actualizarCosteYTotales()">
                <option value="">-- Venta directa (sin cita) --</option>
                @foreach ($citas as $cita)
                <option value="{{ $cita->id }}" data-coste="{{ $cita->servicios->sum('precio') }}"
                    {{ $cita->id === $cobro->id_cita ? 'selected' : '' }}>
                    {{ $cita->cliente->user->nombre ?? 'Cliente' }} - {{ $cita->servicios->pluck('nombre')->implode(', ') ?? 'Sin servicios' }}
                </option>
                @endforeach
            </select>
            </div>

            {{-- Cliente y Servicio --}}
            <div class="mb-4 flex flex-col md:flex-row md:space-x-4">
            <div class="flex-1 mb-2 md:mb-0">
                <label for="cliente" class="block font-semibold mb-1">Cliente:</label>
                @php
                    $nombreCliente = '-';
                    if ($cobro->cita && $cobro->cita->cliente && $cobro->cita->cliente->user) {
                        $nombreCliente = $cobro->cita->cliente->user->nombre;
                    } elseif ($cobro->cliente && $cobro->cliente->user) {
                        $nombreCliente = $cobro->cliente->user->nombre;
                    } elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                        $primeraCita = $cobro->citasAgrupadas->first();
                        if ($primeraCita && $primeraCita->cliente && $primeraCita->cliente->user) {
                            $nombreCliente = $primeraCita->cliente->user->nombre;
                        }
                    }
                @endphp
                <input type="text" id="cliente" name="cliente" value="{{ $nombreCliente }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" readonly>
            </div>
            <div class="flex-1">
                <label for="servicio" class="block font-semibold mb-1">Servicio:</label>
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
                    
                    $serviciosEdit = !empty($serviciosNombres) ? implode(', ', $serviciosNombres) : '-';
                @endphp
                <input type="text" id="servicio" name="servicio" value="{{ $serviciosEdit }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" readonly>
            </div>
            </div>

            {{-- Coste, Descuento %, Descuento € --}}
            <div class="mb-4 flex flex-col md:flex-row md:space-x-4">
            <div class="flex-1 mb-2 md:mb-0">
                <label for="coste" class="block font-semibold mb-1">Coste (€):</label>
                <input type="number" id="coste" name="coste" value="{{ $cobro->coste }}" step="0.01" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="flex-1 mb-2 md:mb-0">
                <label for="descuento_porcentaje" class="block font-semibold mb-1">Descuento %:</label>
                <input type="number" name="descuento_porcentaje" id="descuento_porcentaje"
                value="{{ old('descuento_porcentaje', $cobro->descuento_porcentaje) }}" step="0.01"
                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="flex-1">
                <label for="descuento_euro" class="block font-semibold mb-1">Descuento €:</label>
                <input type="number" name="descuento_euro" id="descuento_euro"
                value="{{ old('descuento_euro', $cobro->descuento_euro) }}" step="0.01"
                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            </div>

            {{-- Total Final, Dinero Cliente, Cambio --}}
            <div class="mb-4 flex flex-col md:flex-row md:space-x-4">
            <div class="flex-1 mb-2 md:mb-0">
                <label for="total_final" class="block font-semibold mb-1">Total Final (€):</label>
                <input type="number" id="total_final" name="total_final" value="{{ $cobro->total_final }}" step="0.01" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="flex-1 mb-2 md:mb-0">
                <label for="dinero_cliente" class="block font-semibold mb-1">Dinero del Cliente:</label>
                <input type="number" id="dinero_cliente" name="dinero_cliente" value="{{ $cobro->dinero_cliente }}" step="0.01" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="flex-1">
                <label for="cambio" class="block font-semibold mb-1">Cambio (€):</label>
                <input type="number" id="cambio" name="cambio" value="{{ $cobro->cambio }}" step="0.01" readonly class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-500">
            </div>
            </div>

            {{-- Método Pago --}}
            <div class="mb-4">
            <label for="metodo_pago" class="block font-semibold mb-1">Método de Pago:</label>
            <select name="metodo_pago" id="metodo_pago" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="efectivo" {{ $cobro->metodo_pago === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                <option value="tarjeta" {{ $cobro->metodo_pago === 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
            </select>
            </div>

            <div class="flex items-center justify-between mt-6">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold shadow-md hover:shadow-lg transition">
                    💾 Actualizar Cobro
                </button>
                <a href="{{ route('cobros.index') }}" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 font-semibold shadow-md hover:shadow-lg transition">
                    ← Cancelar
                </a>
            </div>
        </form>
        </div>
    </div>
</body>
</html>
