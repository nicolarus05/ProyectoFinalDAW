<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del cobro #{{ $cobro->id }}</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
        :root { --sidebar-w: 210px; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f3f4f8; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar { position:fixed; top:0; left:0; width:var(--sidebar-w); height:100vh; background:#1e1a4b; display:flex; flex-direction:column; z-index:100; overflow-y:auto; }
        .sidebar-logo { padding:18px 16px 14px; border-bottom:1px solid rgba(255,255,255,.1); }
        .logo-icon { width:36px; height:36px; background:linear-gradient(135deg,#f472b6,#a855f7); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; }
        .logo-text { font-weight:700; font-size:14px; color:#fff; }
        .logo-sub { font-size:10px; color:rgba(255,255,255,.6); }
        .sidebar-nav { flex:1; padding:10px 0; }
        .nav-item { display:flex; align-items:center; gap:9px; padding:9px 16px; color:rgba(255,255,255,.75); text-decoration:none; font-size:13px; font-weight:500; transition:.15s; border-left:3px solid transparent; }
        .nav-item:hover { background:rgba(255,255,255,.08); color:#fff; }
        .nav-item.active { background:linear-gradient(135deg,#f472b6,#a855f7); color:#fff; border-left-color:transparent; }
        .nav-icon { font-size:15px; min-width:18px; }
        .sidebar-help { margin:10px 12px; background:rgba(255,255,255,.08); border-radius:10px; padding:12px; color:rgba(255,255,255,.8); }
        .sidebar-footer { padding:12px 16px; font-size:10px; color:rgba(255,255,255,.4); border-top:1px solid rgba(255,255,255,.08); }
        .content { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; }
        .topbar { background:#fff; padding:14px 24px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 1px 4px rgba(0,0,0,.08); position:sticky; top:0; z-index:50; }
        .topbar-left { display:flex; align-items:center; gap:14px; }
        .topbar-title { font-size:18px; font-weight:700; color:#1e1a4b; }
        .topbar-sub { font-size:12px; color:#888; }
        .user-badge { display:flex; align-items:center; gap:8px; padding:6px 12px; background:#f3f4f8; border-radius:20px; text-decoration:none; color:#1e1a4b; font-size:13px; }
        .user-avatar { width:30px; height:30px; background:linear-gradient(135deg,#f472b6,#a855f7); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:13px; }
        .main-content { padding:20px 24px; flex:1; }
        .section-title { letter-spacing: .02em; }
        .pill { border-radius: 9999px; }
        .glass { background: rgba(255,255,255,0.95); backdrop-filter: blur(8px); }
    </style>
</head>
<body>
@php $user = Auth::user(); $rol = $user->rol ?? null; @endphp
<div class="main-wrapper">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div style="display:flex;align-items:center;gap:10px">
                <div class="logo-icon">💇‍♀️</div>
                <div>
                    <div class="logo-text">Salón de Belleza</div>
                    <div class="logo-sub">Sistema de Gestión</div>
                </div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-item"><span class="nav-icon">🏠</span> Inicio</a>
            <a href="{{ route('citas.index') }}" class="nav-item"><span class="nav-icon">📅</span> Citas</a>
            <a href="{{ route('clientes.index') }}" class="nav-item"><span class="nav-icon">👤</span> Clientes</a>
            @if(in_array($rol,['admin','gerente']))
            <a href="{{ route('empleados.index') }}" class="nav-item"><span class="nav-icon">👔</span> Empleados</a>
            <a href="{{ route('servicios.index') }}" class="nav-item"><span class="nav-icon">✂️</span> Servicios</a>
            <a href="{{ route('subcategorias.index') }}" class="nav-item"><span class="nav-icon">🏷️</span> Subcategorías</a>
            <a href="{{ route('productos.index') }}" class="nav-item"><span class="nav-icon">🛍️</span> Productos</a>
            @endif
            <a href="{{ route('cobros.index') }}" class="nav-item active"><span class="nav-icon">💳</span> Cobros</a>
            <a href="{{ route('deudas.index') }}" class="nav-item"><span class="nav-icon">💰</span> Deudas</a>
            <a href="{{ route('bonos.index') }}" class="nav-item"><span class="nav-icon">🎫</span> Bonos</a>
            <a href="{{ route('bonos.clientesConBonos') }}" class="nav-item"><span class="nav-icon">👥</span> Clientes con Bonos</a>
            <a href="{{ route('caja.index') }}" class="nav-item"><span class="nav-icon">💵</span> Caja del Día</a>
            @if(in_array($rol,['admin','gerente']))
            <a href="{{ route('facturacion.index') }}" class="nav-item"><span class="nav-icon">📊</span> Facturación</a>
            <a href="{{ route('horarios.index') }}" class="nav-item"><span class="nav-icon">⏰</span> Horarios</a>
            <a href="{{ route('asistencia.index') }}" class="nav-item"><span class="nav-icon">🕐</span> Asistencia</a>
            @endif
            @if($rol==='admin')
            <a href="{{ route('users.index') }}" class="nav-item"><span class="nav-icon">⚙️</span> Usuarios</a>
            @endif
        </nav>
        <div class="sidebar-help">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px"><span style="font-size:20px">❓</span><span style="font-weight:700;font-size:12px">¿Necesitas ayuda?</span></div>
            <p style="opacity:.85;font-size:11px;line-height:1.4">Consulta nuestra guía o contacta soporte</p>
        </div>
        <div class="sidebar-footer">© {{ date('Y') }} Salón de Belleza</div>
    </aside>

    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <a href="{{ route('cobros.index') }}" style="color:#1e1a4b;font-size:20px;text-decoration:none">←</a>
                <div>
                    <div class="topbar-title">👁️ Cobro #{{ $cobro->id }}</div>
                    <div class="topbar-sub">🕐 Cobrado a las {{ optional($cobro->created_at)->format('H:i') }} — {{ optional($cobro->created_at)->format('d/m/Y') }}</div>
                </div>
            </div>
            <a href="{{ route('profile.edit') }}" class="user-badge">
                <div class="user-avatar">{{ strtoupper(substr($user->nombre ?? 'U', 0, 1)) }}</div>
                <div style="display:flex;flex-direction:column">
                    <span style="font-weight:600;font-size:13px">{{ $user->nombre ?? '' }} {{ $user->apellidos ?? '' }}</span>
                    <span style="font-size:11px;color:#888;text-transform:capitalize">{{ $rol }}</span>
                </div>
            </a>
        </header>
        <main class="main-content">
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

    <div class="space-y-5" style="max-width:900px">
        <!-- Panel de resumen superior -->
        <div style="background:#1e1a4b;color:#fff;padding:14px 20px;border-radius:12px;display:flex;justify-content:space-between;align-items:center">
            <div>
                <div style="font-size:20px;font-weight:800">Cobro #{{ $cobro->id }}</div>
                <div style="font-size:12px;opacity:.75">🕐 Cobrado el {{ optional($cobro->created_at)->format('d/m/Y') }} a las {{ optional($cobro->created_at)->format('H:i') }} &bull; Método: {{ $cobro->metodo_pago ?? '-' }}</div>
            </div>
            <div style="text-align:right">
                <span style="background:#22c55e;color:#fff;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700">✓ Completado</span>
            </div>
        </div>
        <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <article class="glass rounded-2xl shadow-lg p-5">
                <p class="text-xs uppercase text-gray-500 tracking-wide">Cliente</p>
                <h2 class="text-2xl font-bold mt-1">{{ $clienteNombre ?: '-' }}</h2>
                <p class="mt-3 text-sm text-gray-600">Empleado principal: <span class="font-semibold text-gray-800">{{ $empleadoPrincipal ?: '-' }}</span></p>
                <p class="mt-2 text-sm text-gray-600">🕐 Hora del cobro: <span class="font-semibold text-gray-800">{{ optional($cobro->created_at)->format('H:i') ?: '-' }}</span></p>
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
                            <p class="text-sm text-gray-700">Vendido por: {{ $bono->empleado && $bono->empleado->user ? $bono->empleado->user->nombre . ' ' . $bono->empleado->user->apellidos : '—' }}</p>
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
                            <p class="text-sm text-gray-700 mt-1">📅 Fecha de la cita: {{ optional($cita->fecha_hora)->format('d/m/Y') ?: '-' }}</p>
                            <p class="text-sm text-gray-700">🕐 Hora de la cita: {{ optional($cita->fecha_hora)->format('H:i') ?: '-' }}</p>
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
            <a href="{{ route('cobros.index') }}" class="px-5 py-3 rounded-xl text-white font-semibold shadow" style="background:#1e1a4b;">← Volver a cobros</a>
            <a href="{{ route('cobros.edit', $cobro->id) }}" class="px-5 py-3 rounded-xl text-white font-semibold shadow" style="background:linear-gradient(135deg,#f472b6,#a855f7);">✏️ Editar cobro</a>
        </section>
    </div>
        </main>
    </div>
</div>
</body>
</html>
