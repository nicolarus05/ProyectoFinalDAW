<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Empleado</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
        :root { --sidebar-w: 210px; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f3f4f8; }
        .sidebar {
            position: fixed; top: 0; left: 0; width: var(--sidebar-w);
            height: 100vh; background: #1e1a4b;
            display: flex; flex-direction: column; z-index: 50;
            overflow-y: auto; transition: transform .3s ease;
        }
        body.sidebar-collapsed .sidebar { transform: translateX(calc(-1 * var(--sidebar-w))); }
        body.sidebar-collapsed .main-wrapper { margin-left: 0; }
        .sidebar-logo { padding: 14px 14px 10px; border-bottom: 1px solid rgba(255,255,255,.1); }
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
        .content { flex:1;padding:18px 20px;max-width:820px;margin:0 auto;width:100%; }
        .panel { background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;margin-bottom:16px; }
        .panel-header { padding:14px 20px;background:#1e1a4b;display:flex;align-items:center;justify-content:space-between; }
        .panel-header h2 { color:#fff;font-size:15px;font-weight:700; }
        .panel-body { padding:24px; }
        .info-grid { display:grid;grid-template-columns:1fr 1fr;gap:12px 24px; }
        .info-item { }
        .info-label { font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px; }
        .info-value { font-size:13px;color:#1f2937;font-weight:500; }
        .badge { display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700; }
        .badge-blue { background:#eff6ff;color:#1d4ed8; }
        .badge-pink { background:#fdf2f8;color:#be185d; }
        .stat-card { background:#f9fafb;border-radius:12px;padding:16px 20px;text-align:center;border:1px solid #f3f4f6; }
        .stat-value { font-size:26px;font-weight:800;line-height:1; }
        .stat-label { font-size:11px;color:#6b7280;margin-top:4px; }
        .desglose-row { display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-radius:10px;margin-bottom:8px; }
        .btn-primary { padding:8px 18px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px; }
        .btn-secondary { padding:8px 16px;background:#f3f4f6;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;color:#374151;text-decoration:none;display:inline-flex;align-items:center;gap:6px; }
        @media (max-width:768px) { .sidebar{transform:translateX(-100%)} .main-wrapper{margin-left:0} .info-grid{grid-template-columns:1fr} }
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
        <a href="{{ route('clientes.index') }}" class="nav-item"><span class="nav-icon">👤</span> Clientes</a>
        @if(in_array($rol,['admin','gerente']))
        <a href="{{ route('empleados.index') }}" class="nav-item active"><span class="nav-icon">👔</span> Empleados</a>
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
        <span class="page-title">👔 {{ $empleado->user->nombre }} {{ $empleado->user->apellidos }}</span>
        <div style="flex:1"></div>
        <a href="{{ route('empleados.edit', $empleado->id) }}" class="btn-primary" style="margin-right:8px">✏️ Editar</a>
        <a href="{{ route('empleados.index') }}" class="btn-secondary">← Volver</a>
        <a href="{{ route('profile.edit') }}" class="user-area" style="margin-left:12px">
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

        {{-- Panel: Información del empleado --}}
        <div class="panel">
            <div class="panel-header">
                <h2>👤 Información del Empleado</h2>
                @if($empleado->categoria === 'peluqueria')
                    <span class="badge badge-blue">💇 Peluquería</span>
                @else
                    <span class="badge badge-pink">💅 Estética</span>
                @endif
            </div>
            <div class="panel-body">
                {{-- Avatar + nombre --}}
                <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #f3f4f6">
                    <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#f472b6,#a855f7);display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:700;flex-shrink:0">
                        {{ strtoupper(substr($empleado->user->nombre??'E',0,1)) }}
                    </div>
                    <div>
                        <div style="font-size:17px;font-weight:800;color:#1f2937">{{ $empleado->user->nombre }} {{ $empleado->user->apellidos ?? '' }}</div>
                        <div style="font-size:12px;color:#6b7280;margin-top:2px">{{ $empleado->user->email }}</div>
                    </div>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">📞 Teléfono</div>
                        <div class="info-value">{{ $empleado->user->telefono ?? '—' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">⚧ Género</div>
                        <div class="info-value">{{ $empleado->user->genero ?? '—' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">🎂 Edad</div>
                        <div class="info-value">{{ $empleado->user->edad ? $empleado->user->edad.' años' : '—' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">✂️ Categoría</div>
                        <div class="info-value">
                            @if($empleado->categoria === 'peluqueria')
                                <span class="badge badge-blue">💇 Peluquería</span>
                            @else
                                <span class="badge badge-pink">💅 Estética</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel: Facturación --}}
        <div class="panel">
            <div class="panel-header">
                <h2>💰 Facturación del Mes Actual</h2>
                <span style="font-size:11px;color:rgba(255,255,255,.65)">{{ now()->translatedFormat('F Y') }}</span>
            </div>
            <div class="panel-body">
                {{-- Stats top --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
                    <div class="stat-card">
                        <div class="stat-value" style="color:#059669">€{{ number_format($empleado->facturacion['total'] ?? 0, 2) }}</div>
                        <div class="stat-label">Total Facturado</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color:#2563eb">{{ $empleado->citasAtendidas ?? 0 }}</div>
                        <div class="stat-label">Citas Atendidas</div>
                    </div>
                </div>

                {{-- Desglose --}}
                <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:10px">Desglose detallado</div>
                <div class="desglose-row" style="background:#eff6ff">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="font-size:20px">💇</span>
                        <span style="font-size:13px;font-weight:600;color:#374151">Servicios</span>
                    </div>
                    <span style="font-size:15px;font-weight:800;color:#1d4ed8">€{{ number_format($empleado->facturacion['servicios'] ?? 0, 2) }}</span>
                </div>
                <div class="desglose-row" style="background:#f5f3ff">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="font-size:20px">📦</span>
                        <span style="font-size:13px;font-weight:600;color:#374151">Productos Vendidos</span>
                    </div>
                    <span style="font-size:15px;font-weight:800;color:#7c3aed">€{{ number_format($empleado->facturacion['productos'] ?? 0, 2) }}</span>
                </div>
                <div class="desglose-row" style="background:#fdf2f8">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="font-size:20px">🎫</span>
                        <span style="font-size:13px;font-weight:600;color:#374151">Bonos Vendidos</span>
                    </div>
                    <span style="font-size:15px;font-weight:800;color:#be185d">€{{ number_format($empleado->facturacion['bonos'] ?? 0, 2) }}</span>
                </div>

                {{-- Comparativa mes anterior --}}
                @if(isset($empleado->facturacionAnterior))
                @php
                    $actual   = $empleado->facturacion['total'] ?? 0;
                    $anterior = $empleado->facturacionAnterior['total'] ?? 0;
                    $diff     = $actual - $anterior;
                    $pct      = $anterior > 0 ? (($diff / $anterior) * 100) : 0;
                @endphp
                <div style="margin-top:16px;background:#f9fafb;border-radius:10px;padding:14px 16px;border:1px solid #f3f4f6">
                    <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:10px">📊 Comparativa con Mes Anterior</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div>
                            <div style="font-size:11px;color:#6b7280;margin-bottom:3px">Mes anterior</div>
                            <div style="font-size:16px;font-weight:700;color:#374151">€{{ number_format($anterior, 2) }}</div>
                        </div>
                        <div>
                            <div style="font-size:11px;color:#6b7280;margin-bottom:3px">Variación</div>
                            <div style="font-size:15px;font-weight:700;color:{{ $diff > 0 ? '#059669' : ($diff < 0 ? '#dc2626' : '#6b7280') }}">
                                @if($diff > 0)▲ +€{{ number_format($diff,2) }} (+{{ number_format($pct,1) }}%)
                                @elseif($diff < 0)▼ −€{{ number_format(abs($diff),2) }} ({{ number_format($pct,1) }}%)
                                @else= Sin cambios
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

    </div>{{-- /content --}}
</div>{{-- /main-wrapper --}}

</body>
</html>
