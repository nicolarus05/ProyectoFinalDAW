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
            const totalProductos = parseFloat(document.getElementById('total_productos').value) || 0;
            
            // Descuentos separados
            const descServPct = parseFloat(document.getElementById('descuento_servicios_porcentaje').value) || 0;
            const descServEur = parseFloat(document.getElementById('descuento_servicios_euro').value) || 0;
            const descProdPct = parseFloat(document.getElementById('descuento_productos_porcentaje').value) || 0;
            const descProdEur = parseFloat(document.getElementById('descuento_productos_euro').value) || 0;
            
            // Descuento legacy (si no hay separados)
            const descPor = parseFloat(document.getElementById('descuento_porcentaje').value) || 0;
            const descEur = parseFloat(document.getElementById('descuento_euro').value) || 0;
            
            let subtotalServicios, subtotalProductos;
            
            if (descServPct > 0 || descServEur > 0 || descProdPct > 0 || descProdEur > 0) {
                subtotalServicios = Math.max(0, coste - (coste * descServPct / 100) - descServEur);
                subtotalProductos = Math.max(0, totalProductos - (totalProductos * descProdPct / 100) - descProdEur);
            } else {
                const descuentoGeneral = (coste * descPor / 100) + descEur;
                subtotalServicios = Math.max(0, coste - descuentoGeneral);
                subtotalProductos = totalProductos;
            }
            
            const precioConDescuento = subtotalServicios + subtotalProductos;
            
            // Calcular total_final y deuda según método de pago
            const metodoPago = document.getElementById('metodo_pago').value;
            let dineroCliente = 0;
            let totalFinal = 0;
            let cambio = 0;
            let deuda = 0;
            
            if (metodoPago === 'tarjeta') {
                dineroCliente = precioConDescuento;
                totalFinal = precioConDescuento;
                cambio = 0;
                deuda = 0;
                document.getElementById('dinero_cliente').value = dineroCliente.toFixed(2);
            } else if (metodoPago === 'mixto') {
                const pagoEfectivo = parseFloat(document.getElementById('pago_efectivo').value) || 0;
                const pagoTarjeta = parseFloat(document.getElementById('pago_tarjeta').value) || 0;
                dineroCliente = pagoEfectivo + pagoTarjeta;
                totalFinal = Math.min(dineroCliente, precioConDescuento);
                cambio = 0;
                deuda = Math.max(0, precioConDescuento - dineroCliente);
                document.getElementById('dinero_cliente').value = dineroCliente.toFixed(2);
            } else if (metodoPago === 'bono') {
                dineroCliente = 0;
                totalFinal = 0;
                cambio = 0;
                deuda = 0;
            } else {
                // efectivo
                dineroCliente = parseFloat(document.getElementById('dinero_cliente').value) || 0;
                totalFinal = Math.min(dineroCliente, precioConDescuento);
                cambio = Math.max(0, dineroCliente - precioConDescuento);
                deuda = Math.max(0, precioConDescuento - dineroCliente);
            }

            document.getElementById('total_final').value = totalFinal.toFixed(2);
            document.getElementById('cambio').value = cambio.toFixed(2);
            
            // Mostrar información de deuda
            const deudaInfo = document.getElementById('deuda-info');
            if (deudaInfo) {
                if (deuda > 0.01) {
                    deudaInfo.textContent = '⚠️ Deuda: €' + deuda.toFixed(2);
                    deudaInfo.className = 'text-sm text-red-600 mt-1 font-semibold';
                } else {
                    deudaInfo.textContent = '';
                }
            }
        }
        
        function toggleMetodoPago() {
            const metodo = document.getElementById('metodo_pago').value;
            const camposEfectivo = document.getElementById('campos-efectivo');
            const camposMixto = document.getElementById('campos-mixto');
            
            camposEfectivo.classList.toggle('hidden', metodo !== 'efectivo');
            camposMixto.classList.toggle('hidden', metodo !== 'mixto');
            
            // Para tarjeta/bono, dinero_cliente se calcula automáticamente
            const dineroInput = document.getElementById('dinero_cliente');
            dineroInput.readOnly = (metodo === 'tarjeta' || metodo === 'mixto' || metodo === 'bono');
            
            calcularTotales();
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

        @if ($errors->any())
            <div class="bg-red-50 border-2 border-red-300 p-4 rounded-lg mb-4">
                @foreach ($errors->all() as $error)
                    <p class="text-red-700 text-sm">{{ $error }}</p>
                @endforeach
            </div>
        @endif

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
                    
                    if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                        $serviciosNombres = $cobro->cita->servicios->pluck('nombre')->toArray();
                        $yaContados = true;
                    }
                    if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                        foreach ($cobro->citasAgrupadas as $citaGrupo) {
                            if ($citaGrupo->servicios) {
                                $serviciosNombres = array_merge($serviciosNombres, $citaGrupo->servicios->pluck('nombre')->toArray());
                            }
                        }
                        $yaContados = true;
                    }
                    if (!$yaContados && $cobro->servicios && $cobro->servicios->count() > 0) {
                        $serviciosNombres = $cobro->servicios->pluck('nombre')->toArray();
                    }
                    
                    $serviciosEdit = !empty($serviciosNombres) ? implode(', ', $serviciosNombres) : '-';
                @endphp
                <input type="text" id="servicio" name="servicio" value="{{ $serviciosEdit }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" readonly>
            </div>
            </div>

            {{-- Coste servicios y productos --}}
            @php
                $totalProductos = 0;
                if ($cobro->productos && $cobro->productos->count() > 0) {
                    foreach ($cobro->productos as $p) {
                        $totalProductos += $p->pivot->subtotal ?? 0;
                    }
                }
            @endphp
            <div class="mb-4 flex flex-col md:flex-row md:space-x-4">
            <div class="flex-1 mb-2 md:mb-0">
                <label for="coste" class="block font-semibold mb-1">Coste Servicios (€):</label>
                <input type="number" id="coste" name="coste" value="{{ old('coste', $cobro->coste) }}" step="0.01" min="0" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="flex-1">
                <label for="total_productos" class="block font-semibold mb-1">Total Productos (€):</label>
                <input type="number" id="total_productos" name="total_productos" value="{{ number_format($totalProductos, 2, '.', '') }}" step="0.01" min="0" readonly class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-500">
                <p class="text-xs text-gray-400 mt-1">Los productos no se pueden modificar desde aquí</p>
            </div>
            </div>

            {{-- Descuentos separados --}}
            <div class="mb-4">
                <p class="font-semibold mb-2">Descuentos Servicios:</p>
                <div class="flex flex-col md:flex-row md:space-x-4">
                    <div class="flex-1 mb-2 md:mb-0">
                        <label for="descuento_servicios_porcentaje" class="block text-sm text-gray-600 mb-1">% Servicios:</label>
                        <input type="number" name="descuento_servicios_porcentaje" id="descuento_servicios_porcentaje"
                        value="{{ old('descuento_servicios_porcentaje', $cobro->descuento_servicios_porcentaje ?? 0) }}" step="0.01" min="0" max="100"
                        class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div class="flex-1">
                        <label for="descuento_servicios_euro" class="block text-sm text-gray-600 mb-1">€ Servicios:</label>
                        <input type="number" name="descuento_servicios_euro" id="descuento_servicios_euro"
                        value="{{ old('descuento_servicios_euro', $cobro->descuento_servicios_euro ?? 0) }}" step="0.01" min="0"
                        class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <p class="font-semibold mb-2">Descuentos Productos:</p>
                <div class="flex flex-col md:flex-row md:space-x-4">
                    <div class="flex-1 mb-2 md:mb-0">
                        <label for="descuento_productos_porcentaje" class="block text-sm text-gray-600 mb-1">% Productos:</label>
                        <input type="number" name="descuento_productos_porcentaje" id="descuento_productos_porcentaje"
                        value="{{ old('descuento_productos_porcentaje', $cobro->descuento_productos_porcentaje ?? 0) }}" step="0.01" min="0" max="100"
                        class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div class="flex-1">
                        <label for="descuento_productos_euro" class="block text-sm text-gray-600 mb-1">€ Productos:</label>
                        <input type="number" name="descuento_productos_euro" id="descuento_productos_euro"
                        value="{{ old('descuento_productos_euro', $cobro->descuento_productos_euro ?? 0) }}" step="0.01" min="0"
                        class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>
            </div>
            
            {{-- Descuentos legacy (ocultos, para compatibilidad) --}}
            <input type="hidden" name="descuento_porcentaje" id="descuento_porcentaje" value="{{ old('descuento_porcentaje', $cobro->descuento_porcentaje ?? 0) }}">
            <input type="hidden" name="descuento_euro" id="descuento_euro" value="{{ old('descuento_euro', $cobro->descuento_euro ?? 0) }}">

            {{-- Método Pago --}}
            <div class="mb-4">
            <label for="metodo_pago" class="block font-semibold mb-1">Método de Pago:</label>
            <select name="metodo_pago" id="metodo_pago" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" onchange="toggleMetodoPago()">
                <option value="efectivo" {{ $cobro->metodo_pago === 'efectivo' ? 'selected' : '' }}>💵 Efectivo</option>
                <option value="tarjeta" {{ $cobro->metodo_pago === 'tarjeta' ? 'selected' : '' }}>💳 Tarjeta</option>
                <option value="mixto" {{ $cobro->metodo_pago === 'mixto' ? 'selected' : '' }}>💵💳 Mixto</option>
                @if($cobro->metodo_pago === 'bono')
                <option value="bono" selected>🎫 Bono</option>
                @endif
            </select>
            </div>

            {{-- Campos Efectivo --}}
            <div id="campos-efectivo" class="{{ $cobro->metodo_pago === 'efectivo' ? '' : 'hidden' }}">
                <div class="mb-4 flex flex-col md:flex-row md:space-x-4">
                    <div class="flex-1 mb-2 md:mb-0">
                        <label for="dinero_cliente" class="block font-semibold mb-1">Dinero del Cliente (€):</label>
                        <input type="number" id="dinero_cliente" name="dinero_cliente" value="{{ old('dinero_cliente', $cobro->dinero_cliente) }}" step="0.01" min="0" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <div id="deuda-info" class="text-sm mt-1"></div>
                    </div>
                    <div class="flex-1">
                        <label for="cambio" class="block font-semibold mb-1">Cambio (€):</label>
                        <input type="number" id="cambio" name="cambio" value="{{ old('cambio', $cobro->cambio) }}" step="0.01" readonly class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-500">
                    </div>
                </div>
            </div>

            {{-- Campos Mixto --}}
            <div id="campos-mixto" class="{{ $cobro->metodo_pago === 'mixto' ? '' : 'hidden' }}">
                <div class="mb-4 flex flex-col md:flex-row md:space-x-4">
                    <div class="flex-1 mb-2 md:mb-0">
                        <label for="pago_efectivo" class="block font-semibold mb-1">💵 Efectivo (€):</label>
                        <input type="number" id="pago_efectivo" name="pago_efectivo" value="{{ old('pago_efectivo', $cobro->pago_efectivo ?? 0) }}" step="0.01" min="0" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div class="flex-1">
                        <label for="pago_tarjeta" class="block font-semibold mb-1">💳 Tarjeta (€):</label>
                        <input type="number" id="pago_tarjeta" name="pago_tarjeta" value="{{ old('pago_tarjeta', $cobro->pago_tarjeta ?? 0) }}" step="0.01" min="0" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>
            </div>

            {{-- Campos ocultos para tarjeta/bono --}}
            @if($cobro->metodo_pago !== 'efectivo' && $cobro->metodo_pago !== 'mixto')
                <input type="hidden" id="dinero_cliente" name="dinero_cliente" value="{{ $cobro->dinero_cliente }}">
                <input type="hidden" id="cambio" name="cambio" value="{{ $cobro->cambio }}">
            @endif

            {{-- Total Final y Deuda --}}
            <div class="mb-4 flex flex-col md:flex-row md:space-x-4">
            <div class="flex-1 mb-2 md:mb-0">
                <label for="total_final" class="block font-semibold mb-1">Total Cobrado (€):</label>
                <input type="number" id="total_final" name="total_final" value="{{ old('total_final', $cobro->total_final) }}" step="0.01" readonly class="w-full border rounded px-3 py-2 bg-green-50 text-green-700 font-bold">
                <p class="text-xs text-gray-400 mt-1">Se calcula automáticamente (lo cobrado, sin deuda)</p>
            </div>
            <div class="flex-1">
                <label class="block font-semibold mb-1">Deuda actual:</label>
                <div class="w-full border rounded px-3 py-2 {{ ($cobro->deuda ?? 0) > 0 ? 'bg-red-50 text-red-700 font-bold' : 'bg-gray-50 text-gray-500' }}">
                    {{ number_format($cobro->deuda ?? 0, 2) }} €
                </div>
            </div>
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
    <script>
        // Inicializar visibilidad de campos al cargar
        document.addEventListener('DOMContentLoaded', function() {
            toggleMetodoPago();
        });
    </script>
</body>
</html>
