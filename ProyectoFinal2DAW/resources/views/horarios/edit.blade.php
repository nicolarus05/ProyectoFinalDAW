<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Horario de Trabajo</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
        :root { --sidebar-w: 210px; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f3f4f8; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar { position:fixed; top:0; left:0; width:var(--sidebar-w); height:100vh; background:#1e1a4b; display:flex; flex-direction:column; z-index:100; overflow-y:auto; }
        .sidebar-logo { padding:20px 16px 12px; border-bottom:1px solid rgba(255,255,255,.08); }
        .sidebar-logo .logo-icon { font-size:1.6rem; }
        .sidebar-logo .logo-name { color:#fff; font-weight:700; font-size:.95rem; line-height:1.2; }
        .sidebar-logo .logo-sub { color:rgba(255,255,255,.5); font-size:.72rem; }
        nav.sidebar-nav { flex:1; padding:12px 0; }
        .nav-item { display:flex; align-items:center; gap:10px; padding:9px 16px; color:rgba(255,255,255,.75); font-size:.82rem; font-weight:500; cursor:pointer; border-left:3px solid transparent; transition:all .18s; text-decoration:none; }
        .nav-item:hover { background:rgba(255,255,255,.07); color:#fff; }
        .nav-item.active { background:linear-gradient(135deg,#f472b6,#a855f7); color:#fff; border-left-color:transparent; }
        .nav-icon { font-size:1rem; width:20px; text-align:center; }
        .sidebar-footer { padding:12px 16px; border-top:1px solid rgba(255,255,255,.08); }
        .sidebar-footer p { color:rgba(255,255,255,.4); font-size:.68rem; }
        .content { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; }
        .topbar { background:#fff; padding:14px 24px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 1px 4px rgba(0,0,0,.08); position:sticky; top:0; z-index:50; }
        .topbar-title { font-size:1.1rem; font-weight:700; color:#1e1a4b; }
        .topbar-actions { display:flex; align-items:center; gap:12px; }
        .topbar-user { display:flex; align-items:center; gap:8px; text-decoration:none; }
        .topbar-user .avatar { width:34px; height:34px; background:linear-gradient(135deg,#f472b6,#a855f7); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:.9rem; }
        .topbar-user .user-info .name { font-size:.82rem; font-weight:600; color:#1e1a4b; }
        .topbar-user .user-info .role { font-size:.7rem; color:#6b7280; }
        .main-content { padding:20px 24px; flex:1; }
        .btn-back { color:#6b7280; font-size:.82rem; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
        .btn-back:hover { color:#1e1a4b; }
        .panel { background:#fff; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,.08); padding:24px; max-width:560px; }
        .panel label { display:block; font-size:.82rem; font-weight:600; color:#374151; margin-bottom:4px; }
        .panel input, .panel select, .panel textarea { width:100%; border:1px solid #d1d5db; border-radius:8px; padding:8px 12px; font-size:.85rem; color:#111827; box-sizing:border-box; }
        .panel input:focus, .panel select:focus, .panel textarea:focus { outline:none; border-color:#a855f7; box-shadow:0 0 0 2px rgba(168,85,247,.15); }
        .btn-submit { background:linear-gradient(135deg,#f472b6,#a855f7); color:#fff; border:none; padding:10px 24px; border-radius:8px; font-weight:600; font-size:.85rem; cursor:pointer; }
        .btn-submit:hover { opacity:.9; }
        .space-y-4 > * + * { margin-top: 1rem; }
    </style>
</head>
@php $user = auth()->user(); $rol = $user->rol ?? ''; @endphp
<body>
<div class="main-wrapper">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">💇‍♀️</div>
            <div class="logo-name">Salón de Belleza</div>
            <div class="logo-sub">Sistema de Gestión</div>
        </div>
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-item"><span class="nav-icon">🏠</span> Inicio</a>
            <a href="{{ route('citas.index') }}" class="nav-item"><span class="nav-icon">📅</span> Citas</a>
            <a href="{{ route('clientes.index') }}" class="nav-item"><span class="nav-icon">👤</span> Clientes</a>
            <a href="{{ route('empleados.index') }}" class="nav-item"><span class="nav-icon">👔</span> Empleados</a>
            <a href="{{ route('servicios.index') }}" class="nav-item"><span class="nav-icon">✂️</span> Servicios</a>
            <a href="{{ route('subcategorias.index') }}" class="nav-item"><span class="nav-icon">🏷️</span> Subcategorías</a>
            <a href="{{ route('productos.index') }}" class="nav-item"><span class="nav-icon">🛍️</span> Productos</a>
            <a href="{{ route('cobros.index') }}" class="nav-item"><span class="nav-icon">💳</span> Cobros</a>
            <a href="{{ route('deudas.index') }}" class="nav-item"><span class="nav-icon">💰</span> Deudas</a>
            <a href="{{ route('bonos.index') }}" class="nav-item"><span class="nav-icon">🎫</span> Bonos</a>
            <a href="{{ route('bonos.clientesConBonos') }}" class="nav-item"><span class="nav-icon">👥</span> Clientes con Bonos</a>
            <a href="{{ route('caja.index') }}" class="nav-item"><span class="nav-icon">💵</span> Caja del Día</a>
            <a href="{{ route('facturacion.index') }}" class="nav-item"><span class="nav-icon">📊</span> Facturación</a>
            <a href="{{ route('horarios.index') }}" class="nav-item active"><span class="nav-icon">⏰</span> Horarios</a>
            <a href="{{ route('asistencia.index') }}" class="nav-item"><span class="nav-icon">🕐</span> Asistencia</a>
            <a href="{{ route('users.index') }}" class="nav-item"><span class="nav-icon">⚙️</span> Usuarios</a>
        </nav>
        <div class="sidebar-footer"><p>© 2026 Salón de Belleza</p></div>
    </aside>
    <div class="content">
        <header class="topbar">
            <span class="topbar-title">⏰ Editar Horario de Trabajo</span>
            <div class="topbar-actions">
                <a href="{{ route('horarios.index') }}" class="btn-back">← Volver</a>
                <a href="{{ route('profile.edit') }}" class="topbar-user">
                    <div class="avatar">{{ strtoupper(substr($user->nombre ?? $user->name ?? 'U', 0, 1)) }}</div>
                    <div class="user-info">
                        <div class="name">{{ $user->nombre ?? $user->name ?? '' }} {{ $user->apellidos ?? '' }}</div>
                        <div class="role">{{ $rol }}</div>
                    </div>
                </a>
            </div>
        </header>
        <main class="main-content">
            <div class="panel">
                @if($errors->any())
                    <div style="background:#fee2e2;border-left:4px solid #ef4444;color:#b91c1c;padding:12px 16px;border-radius:6px;margin-bottom:16px;font-size:.83rem;">
                        <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif
                <form action="{{ route('horarios.update', $horario->id) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="id_empleado">Empleado:</label>
                        <select name="id_empleado" required>
                            @foreach($empleados as $empleado)
                                <option value="{{ $empleado->id }}" {{ $horario->id_empleado == $empleado->id ? 'selected' : '' }}>{{ $empleado->user->nombre ?? '' }} {{ $empleado->user->apellidos ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="fecha">Fecha:</label>
                        <input type="date" name="fecha" id="fecha" value="{{ $horario->fecha }}" required>
                    </div>
                    <div>
                        <label for="hora_inicio">Hora inicio:</label>
                        <input type="time" name="hora_inicio" value="{{ $horario->hora_inicio }}" required>
                    </div>
                    <div>
                        <label for="hora_fin">Hora fin:</label>
                        <input type="time" name="hora_fin" value="{{ $horario->hora_fin }}" required>
                    </div>
                    <input type="hidden" name="disponible" value="0">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="disponible" value="1" id="disponible" {{ $horario->disponible ? 'checked' : '' }} style="width:auto;">
                        <label for="disponible" style="margin-bottom:0;">Disponible</label>
                    </div>
                    <div>
                        <label for="notas">Notas:</label>
                        <textarea name="notas" id="notas" rows="3" placeholder="Observaciones o comentarios (opcional)">{{ old('notas', $horario->notas) }}</textarea>
                    </div>
                    <div>
                        <button type="submit" class="btn-submit">Actualizar</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>
</body>
</html>
