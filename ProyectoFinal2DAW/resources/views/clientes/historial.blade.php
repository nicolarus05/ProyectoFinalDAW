<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Citas - {{ $cliente->user->nombre }}</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
        :root { --sidebar-w: 210px; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f3f4f8; }
        .sidebar { position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;background:#1e1a4b;display:flex;flex-direction:column;z-index:50;overflow-y:auto;transition:transform .3s ease; }
        body.sidebar-collapsed .sidebar { transform:translateX(calc(-1 * var(--sidebar-w))); }
        body.sidebar-collapsed .main-wrapper { margin-left:0; }
        .sidebar-logo { padding:14px 14px 10px;border-bottom:1px solid rgba(255,255,255,.1); }
        .logo-icon { width:36px;height:36px;background:linear-gradient(135deg,#f472b6,#a855f7);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0; }
        .logo-text { color:#fff;font-size:12.5px;font-weight:700;line-height:1.2; }
        .logo-sub  { color:rgba(255,255,255,.55);font-size:10px; }
        .sidebar-nav { flex:1;padding:8px; }
        .nav-item { display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:8px;margin-bottom:1px;color:rgba(255,255,255,.7);font-size:12.5px;font-weight:500;text-decoration:none;transition:all .2s; }
        .nav-item:hover { background:rgba(255,255,255,.1);color:#fff; }
        .nav-item.active { background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;font-weight:600; }
        .nav-item .nav-icon { width:16px;text-align:center;flex-shrink:0;font-size:13px; }
        .sidebar-help { margin:0 8px 8px;background:linear-gradient(135deg,#f97316,#ec4899);border-radius:10px;padding:10px;color:#fff;font-size:11px; }
        .sidebar-footer { padding:6px 14px 12px;color:rgba(255,255,255,.35);font-size:9.5px; }
        .main-wrapper { margin-left:var(--sidebar-w);min-height:100vh;display:flex;flex-direction:column;transition:margin-left .3s ease; }
        .topbar { background:#fff;padding:8px 20px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #e5e7eb;position:sticky;top:0;z-index:40; }
        .menu-btn { width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#f472b6,#a855f7);display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;cursor:pointer;flex-shrink:0; }
        .page-title { font-size:15px;font-weight:700;color:#1f2937; }
        .user-area { display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit; }
        .user-avatar { width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#f472b6,#a855f7);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0; }
        .content { flex:1;padding:18px 20px; }
        .panel { background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;margin-bottom:16px; }
        .panel-header { padding:14px 20px;background:#1e1a4b;display:flex;align-items:center;justify-content:space-between; }
        .panel-header h2 { color:#fff;font-size:15px;font-weight:700; }
        .panel-body { padding:20px; }
        .table-wrap { overflow-x:auto; }
        table { width:100%;border-collapse:collapse; }
        thead th { background:#f8f9fa;padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid #e5e7eb;white-space:nowrap; }
        tbody tr { border-bottom:1px solid #f3f4f6;transition:background .15s; }
        tbody tr:hover { background:#fafafa; }
        tbody td { padding:10px 14px;font-size:13px;color:#374151;vertical-align:middle; }
        .badge { display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700; }
        .badge-green  { background:#dcfce7;color:#166534; }
        .badge-yellow { background:#fef9c3;color:#713f12; }
        .badge-red    { background:#fee2e2;color:#991b1b; }
        .badge-blue   { background:#dbeafe;color:#1e40af; }
        .charged-prices { display:flex;flex-direction:column;gap:4px;min-width:155px; }
        .charged-price-row { display:flex;align-items:center;justify-content:space-between;gap:10px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:6px;padding:4px 7px;font-size:11px; }
        .charged-service { color:#4b5563;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
        .charged-amount { color:#047857;font-weight:800;white-space:nowrap; }
        .charged-pending { color:#9ca3af;font-weight:700;white-space:nowrap; }
        .btn-link { font-size:12px;font-weight:600;color:#a855f7;text-decoration:none; }
        .btn-link:hover { text-decoration:underline; }
        .btn-primary { padding:8px 18px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px; }
        .btn-secondary { padding:8px 16px;background:#f3f4f6;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;font-weight:600;color:#374151;text-decoration:none;display:inline-flex;align-items:center;gap:6px; }
        @media (max-width:768px) { .sidebar{transform:translateX(-100%)} .main-wrapper{margin-left:0} }
    </style>
</head>
<body>
@php $user = Auth::user(); $rol = $user->rol ?? null; @endphp

<!-- SIDEBAR -->
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
        <a href="{{ route('clientes.index') }}" class="nav-item active"><span class="nav-icon">👤</span> Clientes</a>
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

<!-- MAIN -->
<div class="main-wrapper">
    <header class="topbar">
        <div class="menu-btn" onclick="document.body.classList.toggle('sidebar-collapsed')">☰</div>
        <span class="page-title">📅 Historial de Citas</span>
        <div style="flex:1"></div>
        <a href="{{ route('clientes.show', $cliente->id) }}" style="font-size:12px;color:#a855f7;font-weight:600;text-decoration:none;margin-right:12px">← Ver perfil</a>
        <a href="{{ route('profile.edit') }}" class="user-area">
            @if($user && $user->foto_perfil)
                <img src="{{ route('tenant.file',$user->foto_perfil) }}" style="width:32px;height:32px;border-radius:50%;object-fit:cover">
            @else
                <div class="user-avatar">{{ strtoupper(substr($user->nombre??'U',0,1)) }}</div>
            @endif
            <div style="line-height:1.2">
                <div style="font-weight:600;font-size:13px;color:#1f2937">{{ $user->nombre??'' }} {{ $user->apellidos??'' }}</div>
                <div style="font-size:11px;color:#6b7280;text-transform:capitalize">{{ ucfirst($user->rol??'') }}</div>
            </div>
        </a>
    </header>

    <div class="content">

        {{-- Info del cliente --}}
        <div class="panel">
            <div class="panel-header">
                <h2>👤 {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}</h2>
                <span style="font-size:11px;color:rgba(255,255,255,.6)">{{ $citas->count() }} cita(s) registrada(s)</span>
            </div>
            <div class="panel-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
                <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:200px">
                    <div style="width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,#f472b6,#a855f7);display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;font-weight:700;flex-shrink:0">
                        {{ strtoupper(substr($cliente->user->nombre??'C',0,1)) }}
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:700;color:#1f2937">{{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}</div>
                        <div style="font-size:12px;color:#6b7280">{{ $cliente->user->email }}</div>
                    </div>
                </div>
                <div style="font-size:13px;color:#6b7280"><span style="font-weight:600;color:#374151">📞</span> {{ $cliente->user->telefono ?? '—' }}</div>
                @if($cliente->notas_adicionales)
                    <div style="background:#fffbeb;border-left:3px solid #f59e0b;padding:8px 12px;border-radius:6px;font-size:12px;color:#78350f;flex:1">
                        <strong>📝</strong> {{ $cliente->notas_adicionales }}
                    </div>
                @endif
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <a href="{{ route('clientes.show', $cliente->id) }}" class="btn-secondary">👤 Perfil</a>
                    <a href="{{ route('citas.create') }}?cliente_id={{ $cliente->id }}" class="btn-primary">➕ Nueva Cita</a>
                </div>
            </div>
        </div>

        {{-- Tabla de citas --}}
        <div class="panel">
            <div class="panel-header">
                <h2>📋 Citas Registradas</h2>
            </div>
            <div class="panel-body" style="padding:0">
                @if($citas->isEmpty())
                    <div style="padding:32px;text-align:center;color:#6b7280">
                        <div style="font-size:36px;margin-bottom:10px">📭</div>
                        <div style="font-size:14px;font-weight:600">Este cliente aún no tiene citas registradas.</div>
                        <a href="{{ route('citas.create') }}?cliente_id={{ $cliente->id }}" class="btn-primary" style="margin-top:14px">➕ Crear Primera Cita</a>
                    </div>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha y Hora</th>
                                    <th>Empleado</th>
                                    <th>Servicios</th>
                                    <th>Precio cobrado</th>
                                    <th style="text-align:center">Duración</th>
                                    <th style="text-align:center">Estado</th>
                                    <th style="text-align:center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($citas as $cita)
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;font-size:13px">{{ \Carbon\Carbon::parse($cita->fecha_hora)->format('d/m/Y') }}</div>
                                            <div style="font-size:11px;color:#9ca3af">{{ \Carbon\Carbon::parse($cita->fecha_hora)->format('H:i') }}</div>
                                        </td>
                                        <td>
                                            {{ $cita->empleado->user->nombre ?? 'N/A' }}
                                            {{ $cita->empleado->user->apellidos ?? '' }}
                                        </td>
                                        <td>
                                            @if($cita->servicios->isNotEmpty())
                                                <div style="font-size:12px">
                                                    @foreach($cita->servicios as $servicio)
                                                        <span style="display:inline-block;background:#f3f4f6;padding:2px 7px;border-radius:4px;margin:1px;font-size:11px">{{ $servicio->nombre }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span style="color:#9ca3af;font-size:12px">Sin servicios</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php $serviciosCobrados = $cita->serviciosConPrecioCobrado(); @endphp
                                            @if($serviciosCobrados->isNotEmpty())
                                                <div class="charged-prices">
                                                    @foreach($serviciosCobrados as $servicioCobrado)
                                                        <div class="charged-price-row">
                                                            <span class="charged-service">{{ $servicioCobrado->servicio->nombre }}</span>
                                                            @if($servicioCobrado->tiene_precio_cobrado)
                                                                <span class="charged-amount">{{ number_format($servicioCobrado->precio_cobrado, 2, ',', '.') }} €</span>
                                                            @else
                                                                <span class="charged-pending">Sin cobro</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span style="color:#9ca3af;font-size:12px">Sin servicios</span>
                                            @endif
                                        </td>
                                        <td style="text-align:center">
                                            <span class="badge badge-blue">{{ $cita->duracion_minutos ?? 0 }} min</span>
                                        </td>
                                        <td style="text-align:center">
                                            @if($cita->estado === 'completada')
                                                <span class="badge badge-green">✓ Completada</span>
                                            @elseif($cita->estado === 'pendiente')
                                                <span class="badge badge-yellow">⏱️ Pendiente</span>
                                            @else
                                                <span class="badge badge-red">✕ Cancelada</span>
                                            @endif
                                        </td>
                                        <td style="text-align:center">
                                            <a href="{{ route('citas.show', $cita->id) }}" class="btn-link">👁️ Ver</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>{{-- /content --}}
</div>{{-- /main-wrapper --}}

</body>
</html>
