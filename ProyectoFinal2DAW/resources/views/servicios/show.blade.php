<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Servicio</title>
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
        .content { flex:1;padding:18px 20px;max-width:760px;margin:0 auto;width:100%; }
        .panel { background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;margin-bottom:16px; }
        .panel-header { padding:14px 20px;background:#1e1a4b;display:flex;align-items:center;justify-content:space-between; }
        .panel-header h2 { color:#fff;font-size:15px;font-weight:700; }
        .panel-body { padding:24px; }
        .info-grid { display:grid;grid-template-columns:1fr 1fr;gap:12px 24px; }
        .info-label { font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px; }
        .info-value { font-size:13px;color:#1f2937;font-weight:500; }
        .stat-card { background:#f8f9fa;border-radius:10px;padding:14px;text-align:center; }
        .stat-num { font-size:22px;font-weight:800;color:#1e1a4b; }
        .stat-label { font-size:11px;color:#6b7280;margin-top:2px; }
        .badge { display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700; }
        .badge-blue  { background:#dbeafe;color:#1e40af; }
        .badge-pink  { background:#fce7f3;color:#9d174d; }
        .badge-green { background:#dcfce7;color:#166534; }
        .badge-gray  { background:#f3f4f6;color:#6b7280; }
        .badge-yellow { background:#fef9c3;color:#713f12; }
        .btn-primary { padding:8px 18px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px; }
        .btn-secondary { padding:8px 16px;background:#f3f4f6;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;font-weight:600;color:#374151;text-decoration:none;display:inline-flex;align-items:center;gap:6px; }
        .emp-item { display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f3f4f6; }
        .emp-item:last-child { border-bottom:none; }
        .emp-avatar { width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;flex-shrink:0; }
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
        <a href="{{ route('empleados.index') }}" class="nav-item"><span class="nav-icon">👔</span> Empleados</a>
        <a href="{{ route('servicios.index') }}" class="nav-item active"><span class="nav-icon">✂️</span> Servicios</a>
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
        <span class="page-title">✂️ {{ $servicio->nombre }}</span>
        <div style="flex:1"></div>
        <a href="{{ route('servicios.edit', $servicio) }}" class="btn-primary" style="margin-right:8px">✏️ Editar</a>
        <a href="{{ route('servicios.index') }}" class="btn-secondary" style="margin-right:12px">← Volver</a>
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

        {{-- Panel: Información del servicio --}}
        <div class="panel">
            <div class="panel-header">
                <h2>✂️ {{ $servicio->nombre }}</h2>
                @if($servicio->activo)
                    <span class="badge badge-green">✓ Activo</span>
                @else
                    <span class="badge badge-gray">✕ Inactivo</span>
                @endif
            </div>
            <div class="panel-body">
                {{-- Stat cards --}}
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px">
                    <div class="stat-card">
                        <div class="stat-num" style="color:#059669">{{ number_format($servicio->precio, 2) }} €</div>
                        <div class="stat-label">Precio</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-num" style="color:#2563eb">{{ $servicio->tiempo_estimado }} min</div>
                        <div class="stat-label">Duración</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-num" style="color:#7c3aed">{{ $servicio->empleados->count() }}</div>
                        <div class="stat-label">Empleados</div>
                    </div>
                </div>

                <div class="info-grid">
                    <div>
                        <div class="info-label">🏷️ Categoría</div>
                        <div class="info-value">
                            @if($servicio->categoria === 'peluqueria')
                                <span class="badge badge-blue">✂️ Peluquería</span>
                            @else
                                <span class="badge badge-pink">💅 Estética</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="info-label">🏷️ Subcategoría</div>
                        <div class="info-value">
                            @if($servicio->subcategoria)
                                <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;padding:3px 9px;border-radius:20px;background:{{ $servicio->subcategoria->color }}20;border:1px solid {{ $servicio->subcategoria->color }};color:{{ $servicio->subcategoria->color }}">
                                    <span style="width:8px;height:8px;border-radius:50%;background:{{ $servicio->subcategoria->color }}"></span>
                                    {{ $servicio->subcategoria->nombre }}
                                </span>
                            @else
                                <span style="color:#9ca3af">—</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($servicio->descripcion)
                    <div style="margin-top:16px;background:#f8f9fa;padding:12px 16px;border-radius:10px">
                        <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">📝 DESCRIPCIÓN</div>
                        <div style="font-size:13px;color:#374151">{{ $servicio->descripcion }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Panel: Empleados asignados --}}
        <div class="panel">
            <div class="panel-header">
                <h2>👥 Empleados Asignados</h2>
                <span style="font-size:11px;color:rgba(255,255,255,.6)">{{ $servicio->empleados->count() }} empleado(s)</span>
            </div>
            <div class="panel-body">
                @if($servicio->empleados->count() > 0)
                    @foreach($servicio->empleados as $empleado)
                        <div class="emp-item">
                            <div class="emp-avatar" style="background:{{ $empleado->categoria === 'peluqueria' ? 'linear-gradient(135deg,#3b82f6,#2563eb)' : 'linear-gradient(135deg,#f472b6,#ec4899)' }}">
                                {{ strtoupper(substr($empleado->user->nombre ?? 'E', 0, 1)) }}
                            </div>
                            <div style="flex:1">
                                <div style="font-weight:600;font-size:13px;color:#1f2937">{{ $empleado->user->nombre ?? '' }} {{ $empleado->user->apellidos ?? '' }}</div>
                                <div style="display:flex;gap:6px;margin-top:3px">
                                    @if($empleado->categoria === 'peluqueria')
                                        <span class="badge badge-blue">✂️ Peluquería</span>
                                    @else
                                        <span class="badge badge-pink">💅 Estética</span>
                                    @endif
                                    @if($empleado->categoria !== $servicio->categoria)
                                        <span class="badge badge-yellow">⚠️ Manual</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <a href="{{ route('servicios.empleados', $servicio) }}" class="btn-secondary" style="margin-top:12px">👥 Gestionar empleados →</a>
                @else
                    <div style="text-align:center;padding:24px;color:#9ca3af">
                        <div style="font-size:28px;margin-bottom:8px">👥</div>
                        <div style="font-size:13px;font-weight:600">No hay empleados asignados</div>
                        <a href="{{ route('servicios.empleados', $servicio) }}" class="btn-primary" style="margin-top:12px">➕ Asignar empleados</a>
                    </div>
                @endif
            </div>
        </div>

    </div>{{-- /content --}}
</div>{{-- /main-wrapper --}}

</body>
</html>
