<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de la Cita</title>
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
        .content { flex:1;padding:18px 20px; }
        .panel { background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;max-width:800px;margin:0 auto 16px; }
        .panel-header { padding:14px 20px;background:#1e1a4b;display:flex;align-items:center;justify-content:space-between; }
        .panel-header h2 { color:#fff;font-size:15px;font-weight:700; }
        .panel-body { padding:20px; }
        .info-grid { display:grid;grid-template-columns:1fr 1fr;gap:12px; }
        .info-card { padding:14px;background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:10px; }
        .info-label { font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px; }
        .info-value { font-size:13px;font-weight:600;color:#1e1a4b; }
        .badge { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700; }
        .badge-pending { background:#fef3c7;color:#92400e; }
        .badge-completed { background:#d1fae5;color:#065f46; }
        .badge-cancelled { background:#fee2e2;color:#991b1b; }
        .form-control { width:100%;padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;outline:none;transition:border .2s;resize:vertical; }
        .form-control:focus { border-color:#a855f7;box-shadow:0 0 0 3px rgba(168,85,247,.08); }
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
        <a href="{{ route('citas.index') }}" class="nav-item active"><span class="nav-icon">📅</span> Citas</a>
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
        <span class="page-title">📋 Detalle de la Cita #{{ $cita->id }}</span>
        <div style="flex:1"></div>
        <a href="{{ route('citas.edit', $cita->id) }}" style="font-size:12px;padding:6px 14px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border-radius:8px;font-weight:700;text-decoration:none;margin-right:8px">✏️ Editar</a>
        <a href="{{ route('citas.index') }}" style="font-size:12px;color:#a855f7;font-weight:600;text-decoration:none;margin-right:12px">← Volver</a>
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

        @if(session('success'))
            <div style="max-width:800px;margin:0 auto 14px;background:#d1fae5;border-left:4px solid #10b981;padding:10px 16px;border-radius:8px">
                <p style="font-size:13px;color:#065f46;font-weight:600">✅ {{ session('success') }}</p>
            </div>
        @endif

        <!-- Panel info principal -->
        <div class="panel">
            <div class="panel-header">
                <h2>📋 Información de la Cita</h2>
                @php
                    $badgeClass = match($cita->estado) { 'completada' => 'badge-completed', 'cancelada' => 'badge-cancelled', default => 'badge-pending' };
                    $badgeIcon  = match($cita->estado) { 'completada' => '✅', 'cancelada' => '❌', default => '⏳' };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $badgeIcon }} {{ ucfirst($cita->estado) }}</span>
            </div>
            <div class="panel-body">
                <!-- Notas del cliente (aviso especial) -->
                @if($cita->cliente && $cita->cliente->notas_adicionales)
                    <div style="background:#fffbeb;border-left:4px solid #f59e0b;padding:12px 16px;border-radius:8px;margin-bottom:16px">
                        <p style="font-size:12px;font-weight:700;color:#92400e;margin-bottom:4px">📝 Notas del Cliente</p>
                        <p style="font-size:13px;color:#78350f;white-space:pre-line">{{ $cita->cliente->notas_adicionales }}</p>
                    </div>
                @endif

                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-label">👤 Cliente</div>
                        <div class="info-value">{{ $cita->cliente->user->nombre }} {{ $cita->cliente->user->apellidos }}</div>
                        @if($cita->cliente->user->telefono)
                            <div style="font-size:12px;color:#6b7280;margin-top:3px">📞 {{ $cita->cliente->user->telefono }}</div>
                        @endif
                    </div>
                    <div class="info-card">
                        <div class="info-label">👔 Empleado</div>
                        <div class="info-value">{{ $cita->empleado->user->nombre }} {{ $cita->empleado->user->apellidos }}</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">📅 Fecha y Hora</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($cita->fecha_hora)->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">✂️ Servicios</div>
                        @if ($cita->servicios && count($cita->servicios))
                            @foreach ($cita->servicios as $servicio)
                                <div style="font-size:13px;color:#1e1a4b;font-weight:600;padding:2px 0">• {{ $servicio->nombre }}</div>
                            @endforeach
                        @else
                            <div class="info-value" style="color:#9ca3af">Sin servicios</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel notas de la cita -->
        <div class="panel">
            <div class="panel-header" style="background:linear-gradient(135deg,#1e1a4b,#312e81)">
                <h2>📋 Notas de la Cita</h2>
            </div>
            <div class="panel-body">
                @if($errors->any())
                    <div style="margin-bottom:12px;background:#fef2f2;border-left:4px solid #ef4444;padding:10px 14px;border-radius:8px">
                        @foreach($errors->all() as $error)
                            <p style="font-size:12px;color:#b91c1c">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('citas.actualizarNotas', $cita->id) }}" method="POST">
                    @csrf
                    @if($cita->cliente && $cita->cliente->notas_adicionales)
                        <div style="margin-bottom:12px;padding:10px 14px;background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:9px">
                            <p style="font-size:11px;font-weight:700;color:#6b7280;margin-bottom:6px">Notas existentes del cliente:</p>
                            <div style="font-size:12px;color:#374151;white-space:pre-line;max-height:120px;overflow-y:auto">{{ $cita->cliente->notas_adicionales }}</div>
                        </div>
                    @endif

                    <textarea name="notas_adicionales" rows="4" maxlength="5000" class="form-control"
                              placeholder="Escribe aquí una nueva nota sobre el cliente (se añadirá a las notas existentes)"
                    >{{ old('notas_adicionales') }}</textarea>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
                        <p style="font-size:11px;color:#9ca3af"><span id="contador-notas">0</span>/5000 caracteres</p>
                        <button type="submit" style="padding:8px 20px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer">
                            ➕ Añadir Nota
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Acciones -->
        <div style="max-width:800px;margin:0 auto;display:flex;gap:10px;flex-wrap:wrap">
            @php $tieneCobro = DB::table('registro_cobros')->where('id_cita', $cita->id)->exists(); @endphp
            @if(!$tieneCobro)
                <a href="{{ route('cobros.create', ['cita_id' => $cita->id]) }}"
                   style="padding:10px 24px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none">
                    💰 Pasar a Caja
                </a>
            @else
                <span style="padding:10px 24px;background:#d1fae5;color:#065f46;border-radius:9px;font-size:13px;font-weight:700;border:1.5px solid #a7f3d0">
                    ✓ Cita Cobrada
                </span>
            @endif
        </div>

    </div>
</div>

<script>
    document.querySelector('textarea[name="notas_adicionales"]').addEventListener('input', function() {
        document.getElementById('contador-notas').textContent = this.value.length;
    });
</script>

</body>
