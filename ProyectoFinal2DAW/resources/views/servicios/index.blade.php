<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios</title>
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
        .panel { background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden; }
        .panel-header { padding:14px 20px;background:#1e1a4b;display:flex;align-items:center;justify-content:space-between; }
        .panel-header h2 { color:#fff;font-size:15px;font-weight:700; }
        .toolbar { padding:14px 20px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;border-bottom:1px solid #f3f4f6; }
        .btn-primary { padding:8px 16px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;border-radius:9px;font-size:12.5px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;white-space:nowrap; }
        .btn-secondary { padding:8px 14px;background:#f3f4f6;border:1.5px solid #e5e7eb;border-radius:9px;font-size:12.5px;font-weight:600;cursor:pointer;color:#374151;text-decoration:none;display:inline-flex;align-items:center;gap:6px;white-space:nowrap; }
        .search-input { flex:1;min-width:200px;max-width:340px;padding:8px 14px 8px 36px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;outline:none;background:#f9fafb url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 10px center; }
        .search-input:focus { border-color:#a855f7;background-color:#fff; }
        .table-wrap { overflow-x:auto; }
        table { width:100%;border-collapse:collapse; }
        thead th { background:#f8f9fa;padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid #e5e7eb;white-space:nowrap; }
        tbody tr { border-bottom:1px solid #f3f4f6;transition:background .15s; }
        tbody tr:hover { background:#fafafa; }
        tbody td { padding:10px 14px;font-size:13px;color:#374151;vertical-align:middle; }
        .badge { display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700; }
        .badge-blue   { background:#dbeafe;color:#1e40af; }
        .badge-pink   { background:#fce7f3;color:#9d174d; }
        .badge-green  { background:#dcfce7;color:#166534; }
        .badge-gray   { background:#f3f4f6;color:#6b7280; }
        .action-link  { font-size:12px;font-weight:600;text-decoration:none;padding:3px 8px;border-radius:6px; }
        .al-purple { color:#7c3aed;background:#ede9fe; }
        .al-yellow { color:#b45309;background:#fef9c3; }
        .al-violet { color:#6d28d9;background:#ede9fe; }
        .al-red    { color:#dc2626;background:#fee2e2; }
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
        <span class="page-title">✂️ Servicios</span>
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
        <div class="panel">
            <div class="panel-header">
                <h2>✂️ Listado de Servicios</h2>
                <span style="font-size:11px;color:rgba(255,255,255,.6)">{{ $servicios->count() }} servicio(s)</span>
            </div>
            <div class="toolbar">
                <a href="{{ route('servicios.create') }}" class="btn-primary">➕ Nuevo Servicio</a>
                <a href="{{ route('servicios.exportar') }}" class="btn-secondary">⬇️ Exportar CSV</a>
                <input type="text" id="buscar-servicio" class="search-input" placeholder="Buscar por nombre o categoría...">
            </div>
            <div class="table-wrap">
                <table>
                    
                    <tbody>
    <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Servicios</h1>
            <a href="{{ route('dashboard') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">← Volver al Inicio</a>
        </div>

        <div class="mb-4 flex gap-3">
            <a href="{{ route('servicios.create') }}" 
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Nuevo Servicio</a>
            <a href="{{ route('servicios.exportar') }}" 
               class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">&#8595; Exportar CSV</a>
        </div>

        <!-- Barra de búsqueda -->
        <div class="mb-4">
            <input type="text" 
                   id="buscar-servicio" 
                   placeholder="🔍 Buscar por nombre o categoría..."
                   class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <table class="w-full border border-gray-300 rounded">
            <thead>
                <tr class="bg-gray-200 text-left">
                    <th class="px-3 py-2">Nombre</th>
                    <th class="px-3 py-2">Tiempo</th>
                    <th class="px-3 py-2">Precio</th>
                    <th class="px-3 py-2">Categoría</th>
                    <th class="px-3 py-2">Subcategoría</th>
                    <th class="px-3 py-2">Activo</th>
                    <th class="px-3 py-2">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($servicios as $servicio)
                    <tr>
                        <td style="font-weight:600;color:#1f2937">{{ $servicio->nombre }}</td>
                        <td><span class="badge badge-blue">⏱️ {{ $servicio->tiempo_estimado }} min</span></td>
                        <td style="font-weight:700;color:#059669">{{ number_format($servicio->precio, 2) }} €</td>
                        <td>
                            @if($servicio->categoria === 'peluqueria')
                                <span class="badge badge-blue">✂️ Peluquería</span>
                            @else
                                <span class="badge badge-pink">💅 Estética</span>
                            @endif
                        </td>
                        <td>
                            @if($servicio->subcategoria)
                                <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;background-color:{{ $servicio->subcategoria->color }}20;border:1px solid {{ $servicio->subcategoria->color }};color:{{ $servicio->subcategoria->color }}">
                                    <span style="width:7px;height:7px;border-radius:50%;background:{{ $servicio->subcategoria->color }};flex-shrink:0"></span>
                                    {{ $servicio->subcategoria->nombre }}
                                </span>
                            @else
                                <span style="color:#9ca3af;font-size:12px">—</span>
                            @endif
                        </td>
                        <td style="text-align:center">
                            @if($servicio->activo)
                                <span class="badge badge-green">✓ Sí</span>
                            @else
                                <span class="badge badge-gray">✕ No</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <a href="{{ route('servicios.show', $servicio) }}" class="action-link al-purple">👁️ Ver</a>
                                <a href="{{ route('servicios.edit', $servicio) }}" class="action-link al-yellow">✏️ Editar</a>
                                <a href="{{ route('servicios.empleados', $servicio) }}" class="action-link al-violet">👥 Empleados</a>
                                <form action="{{ route('servicios.destroy', $servicio) }}" method="POST" onsubmit="return confirm('¿Eliminar este servicio?')" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-link al-red" style="border:none;cursor:pointer">🗑️ Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:32px;text-align:center;color:#9ca3af;font-size:13px">No hay servicios registrados</td>
                    </tr>
                @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const inputBuscar = document.getElementById('buscar-servicio');
    const tbody = document.querySelector('tbody');

    inputBuscar.addEventListener('input', function () {
        const busqueda = this.value.toLowerCase().trim();
        let visibles = 0;
        Array.from(tbody.querySelectorAll('tr:not(.no-resultados)')).forEach(fila => {
            if (fila.querySelector('td[colspan]')) return;
            const nombre   = fila.children[0]?.textContent.toLowerCase() ?? '';
            const categoria = fila.children[3]?.textContent.toLowerCase() ?? '';
            const coincide = nombre.includes(busqueda) || categoria.includes(busqueda);
            fila.style.display = coincide ? '' : 'none';
            if (coincide) visibles++;
        });
        let noRes = tbody.querySelector('.no-resultados');
        if (visibles === 0 && busqueda) {
            if (!noRes) {
                noRes = document.createElement('tr');
                noRes.className = 'no-resultados';
                noRes.innerHTML = '<td colspan="7" style="padding:24px;text-align:center;color:#9ca3af;font-size:13px">No se encontraron servicios que coincidan con la búsqueda.</td>';
                tbody.appendChild(noRes);
            }
        } else if (noRes) noRes.remove();
    });
</script>
</body>
</html>
