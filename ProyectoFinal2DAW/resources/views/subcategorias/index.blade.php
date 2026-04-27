<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subcategorías de Servicios</title>
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
        .toolbar { display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap; }
        .btn-primary { padding:8px 16px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px; }
        .search-input { flex:1;max-width:280px;padding:8px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;outline:none;transition:border .2s;background:#fff; }
        .search-input:focus { border-color:#a855f7;box-shadow:0 0 0 3px rgba(168,85,247,.08); }
        .panel { background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;margin-bottom:16px; }
        .panel-header { padding:12px 20px;display:flex;align-items:center;gap:10px; }
        .panel-header.pelq { background:linear-gradient(135deg,#ede9fe,#ddd6fe); }
        .panel-header.este { background:linear-gradient(135deg,#fce7f3,#fbcfe8); }
        .panel-header h2 { font-size:14px;font-weight:700; }
        .panel-header.pelq h2 { color:#5b21b6; }
        .panel-header.este h2 { color:#9d174d; }
        table { width:100%;border-collapse:collapse; }
        th { padding:10px 16px;background:#f9fafb;font-size:11.5px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;text-align:left;border-bottom:1px solid #f3f4f6; }
        td { padding:10px 16px;font-size:13px;color:#374151;border-bottom:1px solid #f9fafb;vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#fafbfc; }
        .badge { display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700; }
        .badge-green  { background:#dcfce7;color:#166534; }
        .badge-gray   { background:#f3f4f6;color:#6b7280; }
        .badge-purple { background:#ede9fe;color:#5b21b6; }
        .badge-pink   { background:#fce7f3;color:#9d174d; }
        .action-link { font-size:12px;font-weight:600;padding:4px 10px;border-radius:6px;text-decoration:none;display:inline-flex;align-items:center;gap:3px; }
        .link-yellow { background:#fef9c3;color:#854d0e; }
        .link-red    { background:#fee2e2;color:#dc2626;border:none;cursor:pointer; }
        .color-swatch { width:26px;height:26px;border-radius:6px;border:1px solid rgba(0,0,0,.12);display:inline-block;flex-shrink:0; }
        .flash-success { background:#dcfce7;border-left:3px solid #22c55e;padding:10px 14px;border-radius:8px;font-size:12px;color:#166534;margin-bottom:14px; }
        @media (max-width:900px) { .sidebar{transform:translateX(-100%)} .main-wrapper{margin-left:0} }
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
        <a href="{{ route('servicios.index') }}" class="nav-item"><span class="nav-icon">✂️</span> Servicios</a>
        <a href="{{ route('subcategorias.index') }}" class="nav-item active"><span class="nav-icon">🏷️</span> Subcategorías</a>
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
        <span class="page-title">🏷️ Subcategorías de Servicios</span>
        <div style="flex:1"></div>
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
            <div class="flash-success">✅ {{ session('success') }}</div>
        @endif

        <div class="toolbar">
            <a href="{{ route('subcategorias.create') }}" class="btn-primary">➕ Nueva Subcategoría</a>
            <input type="text" id="buscador" class="search-input" placeholder="🔍 Buscar subcategoría...">
            <span style="font-size:12px;color:#6b7280;margin-left:auto">Total: {{ $subcategorias->count() }}</span>
        </div>

        {{-- Agrupar por categoría --}}
        @php
            $porCategoria = $subcategorias->groupBy('categoria');
            $orden = ['peluqueria', 'estetica'];
        @endphp

        @foreach($orden as $cat)
            @if(isset($porCategoria[$cat]))
            <div class="panel grupo-panel" data-categoria="{{ $cat }}">
                <div class="panel-header {{ $cat === 'peluqueria' ? 'pelq' : 'este' }}">
                    <h2>{{ $cat === 'peluqueria' ? '✂️ Peluquería' : '💅 Estética' }}</h2>
                    <span style="font-size:11px;font-weight:600;opacity:.7">{{ $porCategoria[$cat]->count() }} subcategoría(s)</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Color</th>
                            <th>Nombre</th>
                            <th>Servicios</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($porCategoria[$cat] as $sub)
                        <tr class="fila-sub">
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <span class="color-swatch" style="background:{{ $sub->color }}"></span>
                                    <span style="font-size:11px;color:#9ca3af;font-family:monospace">{{ $sub->color }}</span>
                                </div>
                            </td>
                            <td style="font-weight:600">{{ $sub->nombre }}</td>
                            <td>
                                <span class="{{ $cat === 'peluqueria' ? 'badge badge-purple' : 'badge badge-pink' }}">
                                    {{ $sub->servicios()->count() }} servicio(s)
                                </span>
                            </td>
                            <td>
                                @if($sub->activo)
                                    <span class="badge badge-green">✓ Activa</span>
                                @else
                                    <span class="badge badge-gray">✗ Inactiva</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <a href="{{ route('subcategorias.edit', $sub) }}" class="action-link link-yellow">✏️ Editar</a>
                                    <form action="{{ route('subcategorias.destroy', $sub) }}" method="POST"
                                          onsubmit="return confirm('¿Eliminar «{{ $sub->nombre }}»? Los servicios asociados perderán su subcategoría.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-link link-red">🗑️ Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        @endforeach

        @if($subcategorias->isEmpty())
            <div style="text-align:center;padding:48px;background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06)">
                <div style="font-size:36px;margin-bottom:10px">🏷️</div>
                <div style="font-size:15px;font-weight:700;color:#374151;margin-bottom:6px">No hay subcategorías registradas</div>
                <a href="{{ route('subcategorias.create') }}" class="btn-primary" style="display:inline-flex;margin-top:10px">➕ Crear la primera</a>
            </div>
        @endif

    </div>
</div>

<script>
    const buscador = document.getElementById('buscador');
    if (buscador) {
        buscador.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.fila-sub').forEach(function (tr) {
                const nombre = tr.querySelector('td:nth-child(2)')?.textContent.toLowerCase() ?? '';
                tr.style.display = (!q || nombre.includes(q)) ? '' : 'none';
            });
        });
    }
</script>
</body>
</html>
