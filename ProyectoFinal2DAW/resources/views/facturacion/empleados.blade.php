<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación por Empleado</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
        :root { --sidebar-w: 210px; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f3f4f8; color:#111827; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar { position:fixed; top:0; left:0; width:var(--sidebar-w); height:100vh; background:#1e1a4b; display:flex; flex-direction:column; z-index:100; overflow-y:auto; }
        .sidebar-logo { padding:18px 16px 14px; border-bottom:1px solid rgba(255,255,255,.1); }
        .logo-icon { width:36px; height:36px; background:linear-gradient(135deg,#f472b6,#a855f7); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:18px; }
        .logo-text { font-weight:700; font-size:14px; color:#fff; }
        .logo-sub { font-size:10px; color:rgba(255,255,255,.6); }
        .sidebar-nav { flex:1; padding:10px 0; }
        .nav-item { display:flex; align-items:center; gap:9px; padding:9px 16px; color:rgba(255,255,255,.75); text-decoration:none; font-size:13px; font-weight:500; transition:.15s; border-left:3px solid transparent; }
        .nav-item:hover { background:rgba(255,255,255,.08); color:#fff; }
        .nav-item.active { background:linear-gradient(135deg,#f472b6,#a855f7); color:#fff; border-left-color:transparent; }
        .nav-icon { font-size:15px; min-width:18px; }
        .sidebar-help { margin:10px 12px; background:rgba(255,255,255,.08); border-radius:8px; padding:12px; color:rgba(255,255,255,.8); }
        .sidebar-footer { padding:12px 16px; font-size:10px; color:rgba(255,255,255,.4); border-top:1px solid rgba(255,255,255,.08); }
        .content { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; min-width:0; }
        .topbar { background:#fff; padding:14px 24px; display:flex; align-items:center; justify-content:space-between; gap:16px; box-shadow:0 1px 4px rgba(0,0,0,.08); position:sticky; top:0; z-index:50; }
        .topbar-title { font-size:18px; font-weight:700; color:#1e1a4b; }
        .topbar-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .user-badge { display:flex; align-items:center; gap:8px; padding:6px 12px; background:#f3f4f8; border-radius:20px; text-decoration:none; color:#1e1a4b; font-size:13px; }
        .user-avatar { width:30px; height:30px; background:linear-gradient(135deg,#f472b6,#a855f7); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:13px; }
        .main-content { padding:20px 24px; flex:1; min-width:0; }
        .btn-navy { background:#1e1a4b; color:#fff !important; border:none; padding:8px 14px; border-radius:8px; font-weight:700; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; white-space:nowrap; }
        .btn-soft { background:#fff; color:#1e1a4b !important; border:1px solid #d8d5ee; padding:8px 14px; border-radius:8px; font-weight:700; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; white-space:nowrap; }
        .panel { background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 1px 3px rgba(15,23,42,.06); }
        .metric { border-radius:8px; border:1px solid #e5e7eb; background:#fff; padding:16px; min-width:0; }
        .metric-label { font-size:12px; color:#6b7280; font-weight:700; margin-bottom:6px; }
        .metric-value { font-size:28px; color:#111827; font-weight:800; line-height:1.1; overflow-wrap:anywhere; }
        .field-label { display:block; font-size:12px; color:#4b5563; font-weight:700; margin-bottom:6px; }
        .field { width:100%; border:1px solid #d1d5db; border-radius:8px; padding:9px 11px; font-size:14px; background:#fff; color:#111827; }
        .field:focus { outline:none; border-color:#8b5cf6; box-shadow:0 0 0 3px rgba(139,92,246,.14); }
        .table-wrap { overflow-x:auto; }
        .data-table { width:100%; border-collapse:collapse; min-width:980px; }
        .data-table th { background:#f9fafb; color:#4b5563; text-align:left; font-size:12px; font-weight:800; padding:12px 14px; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
        .data-table td { padding:13px 14px; border-bottom:1px solid #eef0f3; font-size:13px; vertical-align:top; }
        .money { text-align:right; font-variant-numeric:tabular-nums; font-weight:800; white-space:nowrap; }
        .muted { color:#6b7280; }
        .badge { display:inline-flex; align-items:center; justify-content:center; padding:4px 8px; border-radius:999px; font-size:11px; font-weight:800; white-space:nowrap; }
        .badge-cat { background:#f3f4f6; color:#374151; }
        .pay-efectivo { background:#ecfdf5; color:#047857; }
        .pay-tarjeta { background:#eff6ff; color:#1d4ed8; }
        .pay-mixto { background:#f5f3ff; color:#6d28d9; }
        .pay-bono { background:#fff7ed; color:#c2410c; }
        .pay-default { background:#f3f4f6; color:#374151; }
        .rank-line { display:grid; grid-template-columns:minmax(160px,1.2fr) 110px 1.4fr 96px; gap:14px; align-items:center; padding:13px 0; border-bottom:1px solid #eef0f3; }
        .rank-line:last-child { border-bottom:none; }
        .bar { height:8px; background:#eef2f7; border-radius:999px; overflow:hidden; }
        .bar-fill { height:100%; background:linear-gradient(90deg,#2563eb,#ec4899); border-radius:999px; min-width:2px; }
        @media (max-width: 900px) {
            :root { --sidebar-w: 0px; }
            .sidebar { display:none; }
            .content { margin-left:0; }
            .topbar { align-items:flex-start; flex-direction:column; }
            .rank-line { grid-template-columns:1fr; gap:8px; }
            .main-content { padding:16px; }
        }
    </style>
</head>
@php
    $user = Auth::user();
    $rol = $user->rol ?? null;
    $fmt = fn ($valor) => '€' . number_format((float) $valor, 2);
@endphp
<body>
<div class="main-wrapper">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div style="display:flex;align-items:center;gap:10px">
                <div class="logo-icon">💇‍♀️</div>
                <div><div class="logo-text">Salón de Belleza</div><div class="logo-sub">Sistema de Gestión</div></div>
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
            <a href="{{ route('cobros.index') }}" class="nav-item"><span class="nav-icon">💳</span> Cobros</a>
            <a href="{{ route('deudas.index') }}" class="nav-item"><span class="nav-icon">💰</span> Deudas</a>
            <a href="{{ route('bonos.index') }}" class="nav-item"><span class="nav-icon">🎫</span> Bonos</a>
            <a href="{{ route('bonos.clientesConBonos') }}" class="nav-item"><span class="nav-icon">👥</span> Clientes con Bonos</a>
            <a href="{{ route('caja.index') }}" class="nav-item"><span class="nav-icon">💵</span> Caja del Día</a>
            @if(in_array($rol,['admin','gerente']))
            <a href="{{ route('facturacion.index') }}" class="nav-item active"><span class="nav-icon">📊</span> Facturación</a>
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
            <div>
                <div class="topbar-title">Facturación por empleado</div>
                <div style="font-size:12px;color:#6b7280;margin-top:2px">
                    {{ $meses[$mes] }} {{ $anio }} · {{ $fechaInicio->format('d/m/Y') }} - {{ $fechaFin->format('d/m/Y') }}
                </div>
            </div>
            <div class="topbar-actions">
                <a href="{{ route('facturacion.index', ['mes' => $mes, 'anio' => $anio]) }}" class="btn-soft">Resumen mensual</a>
                <a href="{{ route('profile.edit') }}" class="user-badge">
                    <div class="user-avatar">{{ strtoupper(substr($user->nombre ?? 'U', 0, 1)) }}</div>
                    <div style="display:flex;flex-direction:column">
                        <span style="font-weight:700;font-size:13px">{{ $user->nombre ?? '' }} {{ $user->apellidos ?? '' }}</span>
                        <span style="font-size:11px;color:#888;text-transform:capitalize">{{ $rol }}</span>
                    </div>
                </a>
            </div>
        </header>

        <main class="main-content">
            <section class="panel p-5 mb-5">
                <form method="GET" action="{{ route('facturacion.empleados') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <div>
                        <label class="field-label" for="mes">Mes</label>
                        <select name="mes" id="mes" class="field">
                            @foreach($meses as $num => $nombre)
                                <option value="{{ $num }}" {{ $mes == $num ? 'selected' : '' }}>{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label" for="anio">Año</label>
                        <select name="anio" id="anio" class="field">
                            @for($y = now()->year; $y >= now()->year - 5; $y--)
                                <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="field-label" for="empleado_id">Empleado</label>
                        <select name="empleado_id" id="empleado_id" class="field">
                            <option value="">Todos</option>
                            @foreach($empleados as $empleado)
                                <option value="{{ $empleado->id }}" {{ (int) $empleadoId === $empleado->id ? 'selected' : '' }}>
                                    {{ $empleado->user->nombre ?? '' }} {{ $empleado->user->apellidos ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label" for="tipo">Concepto</label>
                        <select name="tipo" id="tipo" class="field">
                            <option value="todos" {{ $tipo === 'todos' ? 'selected' : '' }}>Todos</option>
                            <option value="servicios" {{ $tipo === 'servicios' ? 'selected' : '' }}>Servicios</option>
                            <option value="productos" {{ $tipo === 'productos' ? 'selected' : '' }}>Productos</option>
                            <option value="bonos" {{ $tipo === 'bonos' ? 'selected' : '' }}>Bonos</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-navy justify-center">Consultar</button>
                </form>
            </section>

            <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 mb-5">
                <div class="metric">
                    <div class="metric-label">Total atribuido</div>
                    <div class="metric-value">{{ $fmt($totalGeneral) }}</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Servicios</div>
                    <div class="metric-value">{{ $fmt($totalServicios) }}</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Productos</div>
                    <div class="metric-value">{{ $fmt($totalProductos) }}</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Bonos</div>
                    <div class="metric-value">{{ $fmt($totalBonos) }}</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Ticket medio</div>
                    <div class="metric-value">{{ $fmt($ticketMedio) }}</div>
                </div>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-5">
                <div class="panel p-5 xl:col-span-2">
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Ranking de empleados</h2>
                            <p class="text-sm text-gray-500">{{ $empleadosConFacturacion }} con facturación en el periodo</p>
                        </div>
                        @if($empleadoSeleccionado)
                            <a href="{{ route('facturacion.empleados', ['mes' => $mes, 'anio' => $anio, 'tipo' => $tipo]) }}" class="btn-soft">Ver todos</a>
                        @endif
                    </div>

                    @forelse($resumenEmpleados as $dato)
                        @php
                            $share = $totalGeneral > 0 ? min(100, round(($dato['total'] / $totalGeneral) * 100, 1)) : 0;
                        @endphp
                        <div class="rank-line">
                            <div>
                                <div class="font-bold text-gray-900">{{ $dato['nombre'] }}</div>
                                <div class="mt-1"><span class="badge badge-cat">{{ ucfirst(str_replace('_', ' ', $dato['categoria'])) }}</span></div>
                            </div>
                            <div class="money">{{ $fmt($dato['total']) }}</div>
                            <div>
                                <div class="bar"><div class="bar-fill" style="width: {{ $share }}%"></div></div>
                                <div class="text-xs text-gray-500 mt-1">{{ number_format($share, 1) }}% del periodo</div>
                            </div>
                            <div class="text-right text-sm">
                                <div class="font-bold">{{ $dato['cobros'] }} cobros</div>
                                <div class="muted">{{ $dato['clientes_count'] }} clientes</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-500 py-8">No hay facturación atribuida en este periodo.</div>
                    @endforelse
                </div>

                <div class="panel p-5">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Desglose</h2>
                    @php
                        $base = max($totalGeneral, 0.01);
                        $porServicios = min(100, round(($totalServicios / $base) * 100, 1));
                        $porProductos = min(100, round(($totalProductos / $base) * 100, 1));
                        $porBonos = min(100, round(($totalBonos / $base) * 100, 1));
                    @endphp
                    <div class="space-y-5">
                        <div>
                            <div class="flex justify-between text-sm mb-2"><span class="font-semibold">Servicios</span><span class="font-bold">{{ $fmt($totalServicios) }}</span></div>
                            <div class="bar"><div class="bar-fill" style="width: {{ $porServicios }}%"></div></div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-2"><span class="font-semibold">Productos</span><span class="font-bold">{{ $fmt($totalProductos) }}</span></div>
                            <div class="bar"><div class="bar-fill" style="width: {{ $porProductos }}%"></div></div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-2"><span class="font-semibold">Bonos</span><span class="font-bold">{{ $fmt($totalBonos) }}</span></div>
                            <div class="bar"><div class="bar-fill" style="width: {{ $porBonos }}%"></div></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="p-5 border-b border-gray-200 flex flex-wrap justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Movimientos atribuidos</h2>
                        <p class="text-sm text-gray-500">{{ $movimientosPaginados->total() }} registros</p>
                    </div>
                    <a href="{{ route('facturacion.index', ['mes' => $mes, 'anio' => $anio]) }}" class="btn-soft">Volver a facturación</a>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Cobro</th>
                                <th>Empleado</th>
                                <th>Cliente</th>
                                <th>Concepto</th>
                                <th>Método</th>
                                <th class="money">Servicios</th>
                                <th class="money">Productos</th>
                                <th class="money">Bonos</th>
                                <th class="money">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movimientosPaginados as $movimiento)
                                @php
                                    $payClass = match($movimiento['metodo_pago']) {
                                        'efectivo' => 'pay-efectivo',
                                        'tarjeta' => 'pay-tarjeta',
                                        'mixto' => 'pay-mixto',
                                        'bono' => 'pay-bono',
                                        default => 'pay-default',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <div class="font-bold">{{ $movimiento['fecha']->format('d/m/Y') }}</div>
                                        <div class="muted">{{ $movimiento['fecha']->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <a href="{{ route('cobros.show', $movimiento['cobro']->id) }}" class="font-bold text-blue-700 hover:underline">
                                            #{{ $movimiento['cobro']->id }}
                                        </a>
                                    </td>
                                    <td>
                                        @if(in_array($rol, ['admin','gerente']) && $movimiento['empleado'])
                                            <a href="{{ route('empleados.show', $movimiento['empleado']->id) }}" class="font-bold text-gray-900 hover:underline">
                                                {{ $movimiento['empleado_nombre'] }}
                                            </a>
                                        @else
                                            <span class="font-bold text-gray-900">{{ $movimiento['empleado_nombre'] }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $movimiento['cliente'] }}</td>
                                    <td>
                                        <div style="max-width:360px;line-height:1.35">{{ $movimiento['concepto'] }}</div>
                                    </td>
                                    <td><span class="badge {{ $payClass }}">{{ ucfirst($movimiento['metodo_pago'] ?? 'N/A') }}</span></td>
                                    <td class="money">{{ $fmt($movimiento['servicios']) }}</td>
                                    <td class="money">{{ $fmt($movimiento['productos']) }}</td>
                                    <td class="money">{{ $fmt($movimiento['bonos']) }}</td>
                                    <td class="money">{{ $fmt($movimiento['total']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-gray-500 py-10">No hay movimientos para los filtros seleccionados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($movimientosPaginados->hasPages())
                    <div class="p-5">
                        {{ $movimientosPaginados->links() }}
                    </div>
                @endif
            </section>
        </main>
    </div>
</div>
</body>
</html>
