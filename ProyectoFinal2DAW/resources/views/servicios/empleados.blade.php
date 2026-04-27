<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Empleados - {{ $servicio->nombre }}</title>
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
        .grid-2 { display:grid;grid-template-columns:1fr 1fr;gap:16px; }
        .panel { background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden; }
        .panel-header { padding:14px 20px;background:#1e1a4b;display:flex;align-items:center;justify-content:space-between; }
        .panel-header h2 { color:#fff;font-size:14px;font-weight:700; }
        .panel-body { padding:20px; }
        .emp-item { display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f3f4f6; }
        .emp-item:last-child { border-bottom:none; }
        .emp-avatar { width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;flex-shrink:0; }
        .badge { display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700; }
        .badge-blue   { background:#dbeafe;color:#1e40af; }
        .badge-pink   { background:#fce7f3;color:#9d174d; }
        .badge-yellow { background:#fef9c3;color:#713f12; }
        .badge-green  { background:#dcfce7;color:#166534; }
        .btn-primary { padding:10px 20px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;width:100%; }
        .btn-danger { font-size:12px;font-weight:600;color:#dc2626;background:#fee2e2;padding:4px 10px;border:none;border-radius:6px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:3px; }
        .btn-secondary { padding:8px 16px;background:#f3f4f6;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;font-weight:600;color:#374151;text-decoration:none;display:inline-flex;align-items:center;gap:6px; }
        .form-control { width:100%;padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;outline:none;transition:border .2s;background:#fff;color:#1f2937; }
        .form-control:focus { border-color:#a855f7;box-shadow:0 0 0 3px rgba(168,85,247,.08); }
        .form-label { display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px; }
        .stat-cards { display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:16px; }
        .stat-card { background:#f8f9fa;border-radius:10px;padding:12px;text-align:center; }
        .stat-num { font-size:20px;font-weight:800;color:#1e1a4b; }
        .stat-lbl { font-size:11px;color:#6b7280;margin-top:2px; }
        .alert-info { background:#eff6ff;border-left:3px solid #3b82f6;padding:10px 14px;border-radius:8px;font-size:12px;color:#1e40af;margin-bottom:14px; }
        .alert-warning { background:#fffbeb;border-left:3px solid #f59e0b;padding:10px 14px;border-radius:8px;font-size:12px;color:#92400e;margin-bottom:14px;display:none; }
        .flash-success { background:#dcfce7;border-left:3px solid #22c55e;padding:10px 14px;border-radius:8px;font-size:12px;color:#166534;margin-bottom:14px; }
        .flash-warning { background:#fffbeb;border-left:3px solid #f59e0b;padding:10px 14px;border-radius:8px;font-size:12px;color:#92400e;margin-bottom:14px; }
        @media (max-width:900px) { .grid-2{grid-template-columns:1fr} .sidebar{transform:translateX(-100%)} .main-wrapper{margin-left:0} }
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
        <span class="page-title">👥 Gestionar Empleados — {{ $servicio->nombre }}</span>
        <div style="flex:1"></div>
        <a href="{{ route('servicios.show', $servicio) }}" style="font-size:12px;color:#a855f7;font-weight:600;text-decoration:none;margin-right:12px">← Ver servicio</a>
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

        {{-- Mensajes flash --}}
        @if(session('success'))
            <div class="flash-success" style="margin-bottom:14px">✅ {{ session('success') }}</div>
        @endif
        @if(session('warning'))
            <div class="flash-warning" style="margin-bottom:14px">⚠️ {{ session('warning') }}</div>
        @endif

        <div class="grid-2">

            {{-- Panel izquierdo: Empleados asignados --}}
            <div class="panel">
                <div class="panel-header">
                    <h2>👥 Empleados Asignados</h2>
                    <span style="font-size:11px;color:rgba(255,255,255,.6)">{{ $servicio->empleados->count() }}</span>
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
                                <form action="{{ route('servicios.removeempleado', [$servicio, $empleado->id]) }}"
                                      method="POST"
                                      onsubmit="return confirm('¿Desasignar a {{ $empleado->user->nombre ?? '' }} de este servicio?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger">✕ Remover</button>
                                </form>
                            </div>
                        @endforeach
                    @else
                        <div style="text-align:center;padding:32px;color:#9ca3af">
                            <div style="font-size:28px;margin-bottom:8px">👥</div>
                            <div style="font-size:13px;font-weight:600">No hay empleados asignados</div>
                            <div style="font-size:12px;margin-top:4px">Agrega empleados desde el panel de la derecha</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Panel derecho: Asignar empleado --}}
            <div class="panel">
                <div class="panel-header">
                    <h2>➕ Asignar Empleado</h2>
                </div>
                <div class="panel-body">
                    <div class="alert-info">
                        <strong>ℹ️ Nota:</strong> Puedes asignar empleados de cualquier categoría. Las asignaciones fuera de categoría se marcarán con ⚠️.
                    </div>

                    @if($empleadosDisponibles->count() > 0)
                        <form action="{{ route('servicios.addempleado', $servicio) }}" method="POST">
                            @csrf
                            <div style="margin-bottom:14px">
                                <label class="form-label" for="id_empleado">Seleccionar Empleado:</label>
                                <select name="id_empleado" id="id_empleado" class="form-control" required>
                                    <option value="">— Selecciona un empleado —</option>
                                    @foreach($empleadosDisponibles as $empleado)
                                        <option value="{{ $empleado->id }}" data-categoria="{{ $empleado->categoria }}">
                                            {{ $empleado->user->nombre ?? '' }} {{ $empleado->user->apellidos ?? '' }}
                                            ({{ ucfirst($empleado->categoria) }})
                                            @if($empleado->categoria !== $servicio->categoria) ⚠️ @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_empleado')
                                    <p style="color:#dc2626;font-size:12px;margin-top:4px">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="warning-message" class="alert-warning">
                                ⚠️ <strong>Atención:</strong> Este empleado es de categoría diferente al servicio. La asignación se realizará de forma manual.
                            </div>

                            <button type="submit" class="btn-primary">✓ Asignar Empleado</button>
                        </form>
                    @else
                        <div style="text-align:center;padding:32px;color:#9ca3af">
                            <div style="font-size:28px;margin-bottom:8px">✅</div>
                            <div style="font-size:13px;font-weight:600">Todos los empleados están asignados</div>
                            <div style="font-size:12px;margin-top:4px">No hay más empleados disponibles</div>
                        </div>
                    @endif
                </div>
            </div>

        </div>{{-- /grid-2 --}}

        {{-- Resumen estadístico --}}
        <div class="panel" style="margin-top:16px">
            <div class="panel-header">
                <h2>📊 Resumen</h2>
            </div>
            <div class="panel-body">
                <div class="stat-cards">
                    <div class="stat-card">
                        <div class="stat-num">{{ $servicio->empleados->count() }}</div>
                        <div class="stat-lbl">Empleados Asignados</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-num" style="color:#059669">{{ $servicio->empleados->where('categoria', $servicio->categoria)->count() }}</div>
                        <div class="stat-lbl">Misma Categoría</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-num" style="color:#d97706">{{ $servicio->empleados->where('categoria', '!=', $servicio->categoria)->count() }}</div>
                        <div class="stat-lbl">Asignación Manual</div>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /content --}}
</div>{{-- /main-wrapper --}}

<script>
    const selectEmpleado  = document.getElementById('id_empleado');
    const warningMessage  = document.getElementById('warning-message');
    const servicioCategoria = '{{ $servicio->categoria }}';

    if (selectEmpleado) {
        selectEmpleado.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt.dataset.categoria && opt.dataset.categoria !== servicioCategoria) {
                warningMessage.style.display = 'block';
            } else {
                warningMessage.style.display = 'none';
            }
        });
    }
</script>
</body>
</html>
