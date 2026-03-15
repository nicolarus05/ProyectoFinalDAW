<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar cobro #{{ $cobro->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #1f2937;
            --teal: #0f766e;
            --sand: #f7efe7;
            --mint: #e9f8f2;
        }
        body {
            color: var(--ink);
            background: radial-gradient(circle at 0% 0%, #dcfce7 0%, #f8fafc 45%, #e0f2fe 100%);
        }
        .hero {
            background: linear-gradient(135deg, #0f766e 0%, #155e75 60%, #1e293b 100%);
        }
        .glass {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(8px);
        }
        .chip {
            border-radius: 9999px;
        }
    </style>
</head>
<body class="min-h-screen p-4 md:p-8">
    @php
        $clienteNombre = '-';
        if ($cobro->cita && $cobro->cita->cliente && $cobro->cita->cliente->user) {
            $clienteNombre = trim(($cobro->cita->cliente->user->nombre ?? '') . ' ' . ($cobro->cita->cliente->user->apellidos ?? ''));
        } elseif ($cobro->cliente && $cobro->cliente->user) {
            $clienteNombre = trim(($cobro->cliente->user->nombre ?? '') . ' ' . ($cobro->cliente->user->apellidos ?? ''));
        } elseif ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
            $citaRef = $cobro->citasAgrupadas->first();
            if ($citaRef && $citaRef->cliente && $citaRef->cliente->user) {
                $clienteNombre = trim(($citaRef->cliente->user->nombre ?? '') . ' ' . ($citaRef->cliente->user->apellidos ?? ''));
            }
        }

        $empleadoPrincipal = '-';
        if ($cobro->empleado && $cobro->empleado->user) {
            $empleadoPrincipal = trim(($cobro->empleado->user->nombre ?? '') . ' ' . ($cobro->empleado->user->apellidos ?? ''));
        } elseif ($cobro->cita && $cobro->cita->empleado && $cobro->cita->empleado->user) {
            $empleadoPrincipal = trim(($cobro->cita->empleado->user->nombre ?? '') . ' ' . ($cobro->cita->empleado->user->apellidos ?? ''));
        }

        $totalProductos = 0;
        if ($cobro->productos && $cobro->productos->count() > 0) {
            foreach ($cobro->productos as $producto) {
                $totalProductos += (float) ($producto->pivot->subtotal ?? 0);
            }
        }

        $servicios = $cobro->servicios && $cobro->servicios->count() > 0
            ? $cobro->servicios
            : collect();
    @endphp

    <div class="max-w-6xl mx-auto space-y-6">
        <section class="hero text-white rounded-3xl p-6 md:p-8 shadow-2xl">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-widest opacity-80">Edicion de cobro</p>
                    <h1 class="text-3xl md:text-4xl font-black mt-1">Cobro #{{ $cobro->id }}</h1>
                    <p class="text-teal-100 mt-3">Actualiza importes, descuentos y forma de pago sin mostrar detalle tecnico al usuario.</p>
                </div>
                <div class="bg-white/15 rounded-2xl p-4 text-sm leading-6">
                    <div><span class="font-semibold">Cliente:</span> {{ $clienteNombre ?: '-' }}</div>
                    <div><span class="font-semibold">Empleado:</span> {{ $empleadoPrincipal ?: '-' }}</div>
                    <div><span class="font-semibold">Fecha:</span> {{ optional($cobro->created_at)->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </section>

        @if ($errors->any())
            <section class="glass rounded-2xl border border-red-200 p-4 shadow">
                <h2 class="font-bold text-red-700 mb-2">Hay errores en el formulario</h2>
                <ul class="text-sm text-red-700 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>- {{ $error }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <form action="{{ route('cobros.update', $cobro->id) }}" method="POST" class="space-y-6" id="form-editar-cobro">
            @csrf
            @method('PUT')

            <div id="aviso-cambio-cliente" class="hidden rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800 shadow-sm">
                <p class="font-semibold">Atencion: cambio de cliente en cobro con deuda</p>
                <p class="text-sm mt-1">Este cobro tiene deuda y al cambiar el cliente se ajustaran saldos y movimientos de deuda entre clientes.</p>
            </div>

            <section class="glass rounded-2xl p-6 shadow-lg">
                <h2 class="text-xl font-bold mb-4">Datos generales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label for="id_cita" class="block text-sm font-semibold mb-1">Cita (opcional)</label>
                        <select name="id_cita" id="id_cita" class="w-full border rounded-xl px-3 py-2" onchange="actualizarCosteYTotales()">
                            <option value="">Venta directa (sin cita)</option>
                            @foreach ($citas as $cita)
                                <option value="{{ $cita->id }}" data-coste="{{ $cita->servicios->sum('precio') }}" data-cliente-id="{{ $cita->id_cliente }}" {{ $cita->id === $cobro->id_cita ? 'selected' : '' }}>
                                    {{ optional(optional($cita->cliente)->user)->nombre ?? 'Cliente' }} - {{ $cita->servicios->pluck('nombre')->implode(', ') ?: 'Sin servicios' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="id_cliente" class="block text-sm font-semibold mb-1">Cliente</label>
                        <select name="id_cliente" id="id_cliente" class="w-full border rounded-xl px-3 py-2">
                            <option value="">Sin cliente</option>
                            @foreach ($clientes as $cliente)
                                @php
                                    $nombreClienteOption = trim((optional($cliente->user)->nombre ?? '') . ' ' . (optional($cliente->user)->apellidos ?? ''));
                                @endphp
                                <option value="{{ $cliente->id }}" {{ (string) old('id_cliente', $cobro->id_cliente) === (string) $cliente->id ? 'selected' : '' }}>
                                    {{ $nombreClienteOption ?: ('Cliente #' . $cliente->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Metodo de pago</label>
                        <select name="metodo_pago" id="metodo_pago" required class="w-full border rounded-xl px-3 py-2" onchange="toggleMetodoPago()">
                            <option value="efectivo" {{ old('metodo_pago', $cobro->metodo_pago) === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                            <option value="tarjeta" {{ old('metodo_pago', $cobro->metodo_pago) === 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                            <option value="mixto" {{ old('metodo_pago', $cobro->metodo_pago) === 'mixto' ? 'selected' : '' }}>Mixto</option>
                            <option value="bono" {{ old('metodo_pago', $cobro->metodo_pago) === 'bono' ? 'selected' : '' }}>Bono</option>
                            <option value="deuda" {{ old('metodo_pago', $cobro->metodo_pago) === 'deuda' ? 'selected' : '' }}>Deuda</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="glass rounded-2xl p-6 shadow-lg">
                <h2 class="text-xl font-bold mb-4">Conceptos incluidos</h2>

                <div class="mb-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Servicios</p>
                    @if($servicios->count() > 0)
                        <div class="space-y-2">
                            @foreach($servicios as $servicio)
                                <div class="rounded-xl border border-teal-100 bg-teal-50/60 p-3">
                                    <p class="text-sm font-semibold text-teal-900">{{ $servicio->nombre }}</p>
                                    @php $pivotServicioId = $servicio->pivot->id ?? null; @endphp
                                    <label class="block text-xs font-semibold mt-2 mb-1 text-gray-700">Empleado para este servicio</label>
                                    <select name="servicios_empleado[{{ $pivotServicioId }}]" class="w-full border rounded-xl px-3 py-2 text-sm bg-white" {{ $pivotServicioId ? '' : 'disabled' }}>
                                        <option value="">Sin asignar</option>
                                        @foreach($empleados as $empleado)
                                            @php
                                                $nombreEmpleadoSrv = trim((optional($empleado->user)->nombre ?? '') . ' ' . (optional($empleado->user)->apellidos ?? ''));
                                                $empleadoPivotServicio = old('servicios_empleado.' . $pivotServicioId, $servicio->pivot->empleado_id ?? null);
                                            @endphp
                                            <option value="{{ $empleado->id }}" {{ (string)$empleadoPivotServicio === (string)$empleado->id ? 'selected' : '' }}>
                                                {{ $nombreEmpleadoSrv ?: ('Empleado #' . $empleado->id) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No hay servicios asociados.</p>
                    @endif
                </div>

                <div class="mb-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Productos</p>
                    @if($cobro->productos && $cobro->productos->count() > 0)
                        <div class="space-y-2">
                            @foreach($cobro->productos as $producto)
                                <div class="rounded-xl border border-gray-100 px-3 py-3 bg-white/80 text-sm">
                                    <p class="font-semibold text-gray-800">{{ $producto->nombre }} x{{ (int)($producto->pivot->cantidad ?? 1) }}</p>
                                    @php $pivotProductoId = $producto->pivot->id ?? null; @endphp
                                    <label class="block text-xs font-semibold mt-2 mb-1 text-gray-700">Empleado para este producto</label>
                                    <select name="productos_empleado[{{ $pivotProductoId }}]" class="w-full border rounded-xl px-3 py-2 text-sm bg-white" {{ $pivotProductoId ? '' : 'disabled' }}>
                                        <option value="">Sin asignar</option>
                                        @foreach($empleados as $empleado)
                                            @php
                                                $nombreEmpleadoProd = trim((optional($empleado->user)->nombre ?? '') . ' ' . (optional($empleado->user)->apellidos ?? ''));
                                                $empleadoPivotProducto = old('productos_empleado.' . $pivotProductoId, $producto->pivot->empleado_id ?? null);
                                            @endphp
                                            <option value="{{ $empleado->id }}" {{ (string)$empleadoPivotProducto === (string)$empleado->id ? 'selected' : '' }}>
                                                {{ $nombreEmpleadoProd ?: ('Empleado #' . $empleado->id) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No hay productos asociados.</p>
                    @endif
                </div>

                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-2">Bonos vendidos</p>
                    @if($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0)
                        <div class="space-y-2">
                            @foreach($cobro->bonosVendidos as $bono)
                                <div class="rounded-xl border border-yellow-100 px-3 py-2 bg-yellow-50/80 text-sm">
                                    {{ optional($bono->plantilla)->nombre ?? ('Bono #' . $bono->id) }}
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No hay bonos vendidos en este cobro.</p>
                    @endif
                </div>
            </section>

            <section class="glass rounded-2xl p-6 shadow-lg">
                <h2 class="text-xl font-bold mb-4">Importes y descuentos</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="coste" class="block text-sm font-semibold mb-1">Coste servicios (EUR)</label>
                        <input type="number" name="coste" id="coste" step="0.01" min="0" required value="{{ old('coste', $cobro->coste) }}" class="w-full border rounded-xl px-3 py-2">
                    </div>
                    <div>
                        <label for="total_productos" class="block text-sm font-semibold mb-1">Total productos (informativo)</label>
                        <input type="number" id="total_productos" step="0.01" value="{{ number_format($totalProductos, 2, '.', '') }}" readonly class="w-full border rounded-xl px-3 py-2 bg-gray-100 text-gray-500">
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="rounded-2xl p-4" style="background: var(--mint);">
                        <h3 class="font-semibold mb-3">Descuento servicios</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="descuento_servicios_porcentaje" class="block text-xs font-semibold mb-1">%</label>
                                <input type="number" name="descuento_servicios_porcentaje" id="descuento_servicios_porcentaje" step="0.01" min="0" max="100" value="{{ old('descuento_servicios_porcentaje', $cobro->descuento_servicios_porcentaje ?? 0) }}" class="w-full border rounded-xl px-3 py-2">
                            </div>
                            <div>
                                <label for="descuento_servicios_euro" class="block text-xs font-semibold mb-1">EUR</label>
                                <input type="number" name="descuento_servicios_euro" id="descuento_servicios_euro" step="0.01" min="0" value="{{ old('descuento_servicios_euro', $cobro->descuento_servicios_euro ?? 0) }}" class="w-full border rounded-xl px-3 py-2">
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl p-4" style="background: var(--sand);">
                        <h3 class="font-semibold mb-3">Descuento productos</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="descuento_productos_porcentaje" class="block text-xs font-semibold mb-1">%</label>
                                <input type="number" name="descuento_productos_porcentaje" id="descuento_productos_porcentaje" step="0.01" min="0" max="100" value="{{ old('descuento_productos_porcentaje', $cobro->descuento_productos_porcentaje ?? 0) }}" class="w-full border rounded-xl px-3 py-2">
                            </div>
                            <div>
                                <label for="descuento_productos_euro" class="block text-xs font-semibold mb-1">EUR</label>
                                <input type="number" name="descuento_productos_euro" id="descuento_productos_euro" step="0.01" min="0" value="{{ old('descuento_productos_euro', $cobro->descuento_productos_euro ?? 0) }}" class="w-full border rounded-xl px-3 py-2">
                            </div>
                        </div>
                    </div>
                </div>

                <details class="mt-4 rounded-xl border border-gray-200 p-4 bg-white/80">
                    <summary class="cursor-pointer font-semibold text-sm">Compatibilidad descuento general (legacy)</summary>
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label for="descuento_porcentaje" class="block text-xs font-semibold mb-1">% general</label>
                            <input type="number" name="descuento_porcentaje" id="descuento_porcentaje" step="0.01" min="0" max="100" value="{{ old('descuento_porcentaje', $cobro->descuento_porcentaje ?? 0) }}" class="w-full border rounded-xl px-3 py-2">
                        </div>
                        <div>
                            <label for="descuento_euro" class="block text-xs font-semibold mb-1">EUR general</label>
                            <input type="number" name="descuento_euro" id="descuento_euro" step="0.01" min="0" value="{{ old('descuento_euro', $cobro->descuento_euro ?? 0) }}" class="w-full border rounded-xl px-3 py-2">
                        </div>
                    </div>
                </details>
            </section>

            <section class="glass rounded-2xl p-6 shadow-lg">
                <h2 class="text-xl font-bold mb-4">Pago final</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="bloque-efectivo">
                    <div>
                        <label for="dinero_cliente" class="block text-sm font-semibold mb-1">Dinero recibido (EUR)</label>
                        <input type="number" name="dinero_cliente" id="dinero_cliente" step="0.01" min="0" value="{{ old('dinero_cliente', $cobro->dinero_cliente ?? 0) }}" class="w-full border rounded-xl px-3 py-2">
                    </div>
                    <div>
                        <label for="cambio" class="block text-sm font-semibold mb-1">Cambio (EUR)</label>
                        <input type="number" name="cambio" id="cambio" step="0.01" min="0" value="{{ old('cambio', $cobro->cambio ?? 0) }}" readonly class="w-full border rounded-xl px-3 py-2 bg-gray-100 text-gray-600">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 hidden" id="bloque-mixto">
                    <div>
                        <label for="pago_efectivo" class="block text-sm font-semibold mb-1">Pago en efectivo (EUR)</label>
                        <input type="number" name="pago_efectivo" id="pago_efectivo" step="0.01" min="0" value="{{ old('pago_efectivo', $cobro->pago_efectivo ?? 0) }}" class="w-full border rounded-xl px-3 py-2">
                    </div>
                    <div>
                        <label for="pago_tarjeta" class="block text-sm font-semibold mb-1">Pago con tarjeta (EUR)</label>
                        <input type="number" name="pago_tarjeta" id="pago_tarjeta" step="0.01" min="0" value="{{ old('pago_tarjeta', $cobro->pago_tarjeta ?? 0) }}" class="w-full border rounded-xl px-3 py-2">
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="total_final" class="block text-sm font-semibold mb-1">Total cobrado (EUR)</label>
                        <input type="number" name="total_final" id="total_final" step="0.01" min="0" value="{{ old('total_final', $cobro->total_final ?? 0) }}" readonly class="w-full border rounded-xl px-3 py-2 bg-emerald-50 text-emerald-700 font-bold">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Deuda estimada (EUR)</label>
                        <div id="deuda-estimada" class="w-full border rounded-xl px-3 py-2 bg-gray-100 text-gray-700 font-semibold">0.00</div>
                    </div>
                </div>
            </section>

            <section class="flex flex-wrap gap-3 pb-8">
                <button type="submit" class="px-6 py-3 rounded-xl text-white font-semibold shadow" style="background:#0f766e;">Actualizar cobro</button>
                <a href="{{ route('cobros.index') }}" class="px-6 py-3 rounded-xl text-white font-semibold shadow" style="background:#475569;">Cancelar</a>
                <a href="{{ route('cobros.show', $cobro->id) }}" class="px-6 py-3 rounded-xl text-white font-semibold shadow" style="background:#155e75;">Ver detalle</a>
            </section>
        </form>
    </div>

    <script>
        const clienteOriginalId = '{{ (string) ($cobro->id_cliente ?? '') }}';
        const deudaInicialCobro = {{ (float) ($cobro->deuda ?? 0) }};

        function getNum(id) {
            const value = parseFloat(document.getElementById(id).value);
            return isNaN(value) ? 0 : value;
        }

        function actualizarAvisoCambioCliente() {
            const clienteActualId = document.getElementById('id_cliente').value || '';
            const aviso = document.getElementById('aviso-cambio-cliente');

            const hayCambioCliente = clienteActualId !== clienteOriginalId;
            const tieneDeuda = deudaInicialCobro > 0.01;

            aviso.classList.toggle('hidden', !(hayCambioCliente && tieneDeuda));
        }

        function setNum(id, value) {
            document.getElementById(id).value = Number(value).toFixed(2);
        }

        function actualizarCosteYTotales() {
            const select = document.getElementById('id_cita');
            const selectedOption = select.options[select.selectedIndex];
            const costeServicio = parseFloat(selectedOption.getAttribute('data-coste')) || 0;
            const clienteId = selectedOption.getAttribute('data-cliente-id');

            if (select.value) {
                setNum('coste', costeServicio);
                if (clienteId) {
                    document.getElementById('id_cliente').value = clienteId;
                }
            }
            calcularTotales();
        }

        function toggleMetodoPago() {
            const metodo = document.getElementById('metodo_pago').value;
            const bloqueEfectivo = document.getElementById('bloque-efectivo');
            const bloqueMixto = document.getElementById('bloque-mixto');
            const dineroInput = document.getElementById('dinero_cliente');

            bloqueMixto.classList.toggle('hidden', metodo !== 'mixto');
            bloqueEfectivo.classList.toggle('hidden', metodo === 'mixto');

            // tarjeta y bono calculan el dinero recibido automaticamente
            dineroInput.readOnly = (metodo === 'tarjeta' || metodo === 'bono' || metodo === 'mixto');
            dineroInput.classList.toggle('bg-gray-100', dineroInput.readOnly);

            calcularTotales();
        }

        function calcularTotales() {
            const coste = getNum('coste');
            const totalProductos = getNum('total_productos');
            const metodo = document.getElementById('metodo_pago').value;

            const descServPct = getNum('descuento_servicios_porcentaje');
            const descServEur = getNum('descuento_servicios_euro');
            const descProdPct = getNum('descuento_productos_porcentaje');
            const descProdEur = getNum('descuento_productos_euro');
            const descLegacyPct = getNum('descuento_porcentaje');
            const descLegacyEur = getNum('descuento_euro');

            let subtotalServicios;
            let subtotalProductos;

            if (descServPct > 0 || descServEur > 0 || descProdPct > 0 || descProdEur > 0) {
                subtotalServicios = Math.max(0, coste - (coste * descServPct / 100) - descServEur);
                subtotalProductos = Math.max(0, totalProductos - (totalProductos * descProdPct / 100) - descProdEur);
            } else {
                const descuentoGeneral = (coste * descLegacyPct / 100) + descLegacyEur;
                subtotalServicios = Math.max(0, coste - descuentoGeneral);
                subtotalProductos = totalProductos;
            }

            const precioConDescuento = subtotalServicios + subtotalProductos;

            let dineroCliente = getNum('dinero_cliente');
            let cambio = 0;
            let totalFinal = 0;
            let deuda = 0;

            if (metodo === 'tarjeta') {
                dineroCliente = precioConDescuento;
                setNum('dinero_cliente', dineroCliente);
                totalFinal = precioConDescuento;
                cambio = 0;
                deuda = 0;
            } else if (metodo === 'mixto') {
                const pagoEfectivo = getNum('pago_efectivo');
                const pagoTarjeta = getNum('pago_tarjeta');
                dineroCliente = pagoEfectivo + pagoTarjeta;
                setNum('dinero_cliente', dineroCliente);
                totalFinal = Math.min(dineroCliente, precioConDescuento);
                cambio = 0;
                deuda = Math.max(0, precioConDescuento - dineroCliente);
            } else if (metodo === 'bono') {
                dineroCliente = 0;
                setNum('dinero_cliente', dineroCliente);
                totalFinal = 0;
                cambio = 0;
                deuda = 0;
            } else if (metodo === 'deuda') {
                totalFinal = Math.min(dineroCliente, precioConDescuento);
                cambio = 0;
                deuda = Math.max(0, precioConDescuento - dineroCliente);
            } else {
                totalFinal = Math.min(dineroCliente, precioConDescuento);
                cambio = Math.max(0, dineroCliente - precioConDescuento);
                deuda = Math.max(0, precioConDescuento - dineroCliente);
            }

            setNum('total_final', totalFinal);
            setNum('cambio', cambio);

            const deudaEl = document.getElementById('deuda-estimada');
            deudaEl.textContent = deuda.toFixed(2);
            if (deuda > 0.01) {
                deudaEl.className = 'w-full border rounded-xl px-3 py-2 bg-red-50 text-red-700 font-semibold';
            } else {
                deudaEl.className = 'w-full border rounded-xl px-3 py-2 bg-emerald-50 text-emerald-700 font-semibold';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const ids = [
                'coste',
                'dinero_cliente',
                'pago_efectivo',
                'pago_tarjeta',
                'descuento_servicios_porcentaje',
                'descuento_servicios_euro',
                'descuento_productos_porcentaje',
                'descuento_productos_euro',
                'descuento_porcentaje',
                'descuento_euro'
            ];

            ids.forEach(function(id) {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', calcularTotales);
                }
            });

            const selectCliente = document.getElementById('id_cliente');
            if (selectCliente) {
                selectCliente.addEventListener('change', actualizarAvisoCambioCliente);
            }

            const form = document.getElementById('form-editar-cobro');
            if (form) {
                form.addEventListener('submit', function(event) {
                    const clienteActualId = document.getElementById('id_cliente').value || '';
                    const hayCambioCliente = clienteActualId !== clienteOriginalId;
                    const tieneDeuda = deudaInicialCobro > 0.01;

                    if (hayCambioCliente && tieneDeuda) {
                        const confirmado = window.confirm('Este cobro tiene deuda y has cambiado el cliente. Se moveran ajustes de deuda al nuevo cliente. Quieres continuar?');
                        if (!confirmado) {
                            event.preventDefault();
                        }
                    }
                });
            }

            toggleMetodoPago();
            calcularTotales();
            actualizarAvisoCambioCliente();
        });
    </script>
</body>
</html>
