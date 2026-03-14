<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del cobro #{{ $cobro->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-soft: #f4f6f8;
            --ink: #1e293b;
            --teal: #0f766e;
            --sand: #f6ede4;
            --mint: #e6f6ef;
        }
        body {
            background: radial-gradient(circle at 8% 0%, #e6fffa 0%, #f8fafc 35%, #eef2ff 100%);
            color: var(--ink);
        }
        .hero {
            background: linear-gradient(135deg, #0f766e 0%, #155e75 65%, #1f2937 100%);
        }
        .glass {
            backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.88);
        }
        .section-title {
            letter-spacing: .02em;
        }
        .pill {
            border-radius: 9999px;
        }
    </style>
</head>
<body class="min-h-screen p-4 md:p-8">
    @php
        $clienteNombre = '-';
        if ($cobro->cliente && $cobro->cliente->user) {
            $clienteNombre = trim(($cobro->cliente->user->nombre ?? '') . ' ' . ($cobro->cliente->user->apellidos ?? ''));
        } elseif ($cobro->cita && $cobro->cita->cliente && $cobro->cita->cliente->user) {
            $clienteNombre = trim(($cobro->cita->cliente->user->nombre ?? '') . ' ' . ($cobro->cita->cliente->user->apellidos ?? ''));
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

        $citasRelacionadas = collect();
        if ($cobro->cita) {
            $citasRelacionadas->push($cobro->cita);
        }
        if ($cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
            foreach ($cobro->citasAgrupadas as $citaAgrupada) {
                $citasRelacionadas->push($citaAgrupada);
            }
        }
        $citasRelacionadas = $citasRelacionadas->unique('id')->values();

        $sumServicios = 0;
        $sumProductos = 0;
        $sumBonos = 0;
        $sumBonosPagado = 0;

        foreach ($cobro->servicios ?? [] as $servicio) {
            $sumServicios += (float) ($servicio->pivot->precio ?? 0);
        }

        foreach ($cobro->productos ?? [] as $producto) {
            $sumProductos += (float) ($producto->pivot->subtotal ?? 0);
        }

        foreach ($cobro->bonosVendidos ?? [] as $bono) {
            $sumBonos += (float) ($bono->pivot->precio ?? 0);
            $sumBonosPagado += (float) ($bono->precio_pagado ?? 0);
        }

        $descServPct = (float) ($cobro->descuento_servicios_porcentaje ?? 0);
        $descServEur = (float) ($cobro->descuento_servicios_euro ?? 0);
        $descProdPct = (float) ($cobro->descuento_productos_porcentaje ?? 0);
        $descProdEur = (float) ($cobro->descuento_productos_euro ?? 0);
        $descLegacyPct = (float) ($cobro->descuento_porcentaje ?? 0);
        $descLegacyEur = (float) ($cobro->descuento_euro ?? 0);

        $totalDescuentoServicios = ($sumServicios * ($descServPct / 100)) + $descServEur;
        $totalDescuentoProductos = ($sumProductos * ($descProdPct / 100)) + $descProdEur;
        $deudaBonos = max(0, $sumBonos - $sumBonosPagado);
        $deudaTotal = (float) ($cobro->deuda ?? 0) + $deudaBonos;

        $movs = $cobro->movimientosDeuda ?? collect();
        $esPagoDeuda = $movs->where('tipo', 'abono')->count() > 0;
    @endphp

    <div class="max-w-6xl mx-auto space-y-6">
        <section class="hero text-white rounded-3xl p-6 md:p-8 shadow-2xl">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-widest opacity-80">Resumen de cobro</p>
                    <h1 class="text-3xl md:text-4xl font-black mt-1">Cobro #{{ $cobro->id }}</h1>
                    <p class="text-teal-100 mt-3">Informacion completa y clara del cobro para uso de cliente y recepcion.</p>
                </div>
                <div class="bg-white/15 rounded-2xl p-4 text-sm leading-6">
                    <div><span class="font-semibold">Fecha:</span> {{ optional($cobro->created_at)->format('d/m/Y H:i') }}</div>
                    <div><span class="font-semibold">Metodo de pago:</span> {{ $cobro->metodo_pago ?? '-' }}</div>
                    <div><span class="font-semibold">Estado:</span>
                        @if($cobro->contabilizado)
                            Confirmado
                        @else
                            Pendiente de cierre
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <article class="glass rounded-2xl shadow-lg p-5">
                <p class="text-xs uppercase text-gray-500 tracking-wide">Cliente</p>
                <h2 class="text-2xl font-bold mt-1">{{ $clienteNombre ?: '-' }}</h2>
                <p class="mt-3 text-sm text-gray-600">Empleado principal: <span class="font-semibold text-gray-800">{{ $empleadoPrincipal ?: '-' }}</span></p>
            </article>

            <article class="glass rounded-2xl shadow-lg p-5" style="background: linear-gradient(145deg, #ffffff 0%, var(--sand) 100%);">
                <p class="text-xs uppercase text-gray-500 tracking-wide">Estado de deuda</p>
                <h2 class="text-2xl font-extrabold mt-1 {{ $deudaTotal > 0 ? 'text-red-700' : 'text-emerald-700' }}">
                    {{ number_format($deudaTotal, 2) }} EUR
                </h2>
                @if($esPagoDeuda)
                    <p class="mt-3 text-sm text-orange-700">Este cobro incluye un abono de deuda.</p>
                @else
                    <p class="mt-3 text-sm text-gray-600">No se han registrado abonos de deuda en este cobro.</p>
                @endif
            </article>
        </section>

        <section class="glass rounded-2xl shadow-lg p-6">
            <h3 class="section-title text-xl font-bold mb-4">Importes del cobro</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="rounded-xl p-4" style="background: var(--bg-soft);">
                    <p class="text-xs uppercase text-gray-500">Total final</p>
                    <p class="text-2xl font-extrabold text-emerald-700 mt-1">{{ number_format((float)($cobro->total_final ?? 0), 2) }} EUR</p>
                </div>
                <div class="rounded-xl p-4" style="background: var(--bg-soft);">
                    <p class="text-xs uppercase text-gray-500">Dinero recibido</p>
                    <p class="text-2xl font-extrabold mt-1">{{ number_format((float)($cobro->dinero_cliente ?? 0), 2) }} EUR</p>
                </div>
                <div class="rounded-xl p-4" style="background: var(--bg-soft);">
                    <p class="text-xs uppercase text-gray-500">Cambio entregado</p>
                    <p class="text-2xl font-extrabold mt-1">{{ number_format((float)($cobro->cambio ?? 0), 2) }} EUR</p>
                </div>
                <div class="rounded-xl p-4" style="background: var(--bg-soft);">
                    <p class="text-xs uppercase text-gray-500">Coste</p>
                    <p class="text-2xl font-extrabold mt-1">{{ number_format((float)($cobro->coste ?? 0), 2) }} EUR</p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl p-4" style="background: var(--mint);">
                    <p class="font-semibold text-gray-800">Resumen por concepto</p>
                    <p class="mt-2">Servicios: <span class="font-bold">{{ number_format($sumServicios, 2) }} EUR</span></p>
                    <p>Productos: <span class="font-bold">{{ number_format($sumProductos, 2) }} EUR</span></p>
                    <p>Bonos vendidos: <span class="font-bold">{{ number_format($sumBonos, 2) }} EUR</span></p>
                </div>
                <div class="rounded-xl p-4 bg-yellow-50 border border-yellow-100">
                    <p class="font-semibold text-gray-800">Descuentos aplicados</p>
                    <p class="mt-2">Servicios: {{ number_format($descServPct, 2) }}% + {{ number_format($descServEur, 2) }} EUR</p>
                    <p>Productos: {{ number_format($descProdPct, 2) }}% + {{ number_format($descProdEur, 2) }} EUR</p>
                    <p>General: {{ number_format($descLegacyPct, 2) }}% + {{ number_format($descLegacyEur, 2) }} EUR</p>
                    <p class="mt-2 text-gray-700">Total descontado servicios: <span class="font-semibold">{{ number_format($totalDescuentoServicios, 2) }} EUR</span></p>
                    <p class="text-gray-700">Total descontado productos: <span class="font-semibold">{{ number_format($totalDescuentoProductos, 2) }} EUR</span></p>
                </div>
            </div>
        </section>

        <section class="glass rounded-2xl shadow-lg p-6">
            <h3 class="section-title text-xl font-bold mb-4">Servicios incluidos</h3>
            @if($cobro->servicios && $cobro->servicios->count() > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach($cobro->servicios as $servicio)
                        <span class="pill px-4 py-2 text-sm bg-teal-50 text-teal-800 border border-teal-100">{{ $servicio->nombre }}</span>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">No hay servicios registrados en este cobro.</p>
            @endif
        </section>

        <section class="glass rounded-2xl shadow-lg p-6">
            <h3 class="section-title text-xl font-bold mb-4">Productos vendidos</h3>
            @if($cobro->productos && $cobro->productos->count() > 0)
                <div class="space-y-3">
                    @foreach($cobro->productos as $producto)
                        <article class="rounded-xl border border-gray-100 p-4 bg-white/70">
                            <p class="font-bold text-lg">{{ $producto->nombre }}</p>
                            <p class="text-sm text-gray-600 mt-1">Cantidad: {{ (int)($producto->pivot->cantidad ?? 1) }}</p>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">No hay productos asociados a este cobro.</p>
            @endif
        </section>

        <section class="glass rounded-2xl shadow-lg p-6">
            <h3 class="section-title text-xl font-bold mb-4">Bonos vendidos</h3>
            @if($cobro->bonosVendidos && $cobro->bonosVendidos->count() > 0)
                <div class="space-y-3">
                    @foreach($cobro->bonosVendidos as $bono)
                        <article class="rounded-xl border border-yellow-100 p-4 bg-yellow-50/80">
                            <p class="font-bold text-lg">{{ optional($bono->plantilla)->nombre ?? 'Bono sin plantilla' }}</p>
                            <p class="text-sm text-gray-700 mt-1">Estado: {{ $bono->estado ?? '-' }}</p>
                            <p class="text-sm text-gray-700">Metodo de pago: {{ $bono->metodo_pago ?? '-' }}</p>
                            <p class="text-sm text-gray-700">Fecha compra: {{ optional($bono->fecha_compra)->format('d/m/Y') ?: '-' }}</p>
                            <p class="text-sm text-gray-700">Fecha expiracion: {{ optional($bono->fecha_expiracion)->format('d/m/Y') ?: '-' }}</p>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">No se vendieron bonos en este cobro.</p>
            @endif
        </section>

        <section class="glass rounded-2xl shadow-lg p-6">
            <h3 class="section-title text-xl font-bold mb-4">Citas relacionadas</h3>
            @if($citasRelacionadas->count() > 0)
                <div class="space-y-3">
                    @foreach($citasRelacionadas as $cita)
                        <article class="rounded-xl border border-cyan-100 p-4 bg-cyan-50/70">
                            <p class="font-bold">Cita #{{ $cita->id }}</p>
                            <p class="text-sm text-gray-700 mt-1">Fecha: {{ optional($cita->fecha_hora)->format('d/m/Y H:i') ?: '-' }}</p>
                            <p class="text-sm text-gray-700">Estado: {{ $cita->estado ?? '-' }}</p>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">Este cobro no tiene citas vinculadas.</p>
            @endif
        </section>

        <section class="glass rounded-2xl shadow-lg p-6">
            <h3 class="section-title text-xl font-bold mb-4">Movimientos de deuda</h3>
            @if($movs->count() > 0)
                <div class="space-y-3">
                    @foreach($movs as $mv)
                        <article class="rounded-xl border border-orange-100 p-4 bg-orange-50/70">
                            <p class="font-bold">{{ strtoupper($mv->tipo ?? '-') }} - {{ number_format((float)($mv->monto ?? 0), 2) }} EUR</p>
                            <p class="text-sm text-gray-700 mt-1">Metodo de pago: {{ $mv->metodo_pago ?? '-' }}</p>
                            <p class="text-sm text-gray-700">Nota: {{ $mv->nota ?? '-' }}</p>
                            <p class="text-sm text-gray-700">Fecha: {{ optional($mv->created_at)->format('d/m/Y H:i') ?: '-' }}</p>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">No hay movimientos de deuda en este cobro.</p>
            @endif
        </section>

        <section class="flex flex-wrap gap-3 pb-8">
            <a href="{{ route('cobros.index') }}" class="px-5 py-3 rounded-xl text-white font-semibold shadow" style="background:#155e75;">Volver a cobros</a>
            <a href="{{ route('cobros.edit', $cobro->id) }}" class="px-5 py-3 rounded-xl text-white font-semibold shadow" style="background:#b45309;">Editar cobro</a>
            <a href="{{ route('dashboard') }}" class="px-5 py-3 rounded-xl text-white font-semibold shadow" style="background:#334155;">Dashboard</a>
        </section>
    </div>
</body>
</html>
