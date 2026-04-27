<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js', 'resources/css/clientes.css', 'resources/js/clientes.js']) !!}
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
        .panel { background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden; }
        .panel-header { padding:14px 20px;background:#1e1a4b;display:flex;align-items:center;justify-content:space-between; }
        .panel-header h2 { color:#fff;font-size:15px;font-weight:700; }
        .panel-body { padding:20px; }
        .btn-primary { padding:8px 16px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;border-radius:9px;font-size:12.5px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:5px; }
        .btn-secondary { padding:8px 14px;background:#f3f4f6;border:1.5px solid #e5e7eb;border-radius:9px;font-size:12.5px;font-weight:600;color:#374151;text-decoration:none;display:inline-flex;align-items:center;gap:5px; }
        .table-wrapper { overflow-x:auto;margin-top:4px; }
        table { width:100%;border-collapse:collapse;font-size:12.5px; }
        thead th { background:#1e1a4b;color:rgba(255,255,255,.85);padding:9px 12px;font-weight:600;font-size:11.5px;white-space:nowrap;text-align:left; }
        thead th:first-child { border-radius:8px 0 0 0; }
        thead th:last-child { border-radius:0 8px 0 0; }
        tbody tr { border-bottom:1px solid #f3f4f6;transition:background .15s; }
        tbody tr:hover { background:#fafafa; }
        tbody td { padding:9px 12px;color:#374151;vertical-align:middle; }
        .action-link { font-size:11.5px;font-weight:600;text-decoration:none;padding:3px 8px;border-radius:6px; }
        .action-link.ver    { color:#a855f7;background:#f5f3ff; }
        .action-link.editar { color:#d97706;background:#fffbeb; }
        .action-link.hist   { color:#059669;background:#ecfdf5; }
        .action-link.bonos  { color:#7c3aed;background:#ede9fe; }
        .action-link.del    { color:#dc2626;background:#fef2f2;border:none;cursor:pointer; }
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
        <span class="page-title">👤 Clientes</span>
        <div style="flex:1"></div>
        <a href="{{ route('clientes.create') }}" class="btn-primary" style="margin-right:8px">➕ Nuevo Cliente</a>
        <a href="{{ route('clientes.exportar') }}" class="btn-secondary" style="margin-right:12px">⬇️ Exportar CSV</a>
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
                <h2>👤 Clientes Registrados <span style="font-weight:400;opacity:.75;font-size:13px">({{ $clientes->count() }})</span></h2>
            </div>
            <div class="panel-body">

                <!-- Barra búsqueda y controles (clientes.js) -->
                <div class="search-container" style="margin-top:0">
                    <input type="text" id="searchInput" class="search-input"
                           placeholder="🔍 Buscar por nombre, apellidos, email o teléfono..."
                           autocomplete="off">
                    <button id="sortAscBtn"  class="sort-btn active" onclick="sortClients('asc')"  title="Ordenar A-Z">↑ A-Z</button>
                    <button id="sortDescBtn" class="sort-btn"        onclick="sortClients('desc')" title="Ordenar Z-A">↓ Z-A</button>
                    <button id="clearBtn"    class="clear-btn"       onclick="clearSearch()"       title="Limpiar">✕ Limpiar</button>
                    <span id="resultsInfo" class="results-info"></span>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Apellidos</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Género</th>
                                <th>Edad</th>
                                <th>Dirección</th>
                                <th>Notas</th>
                                <th>Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="clientsTableBody">
                            @foreach ($clientes as $cliente)
                            <tr class="cliente-row"
                                data-nombre="{{ strtolower($cliente->user->nombre ?? '') }}"
                                data-apellidos="{{ strtolower($cliente->user->apellidos ?? '') }}"
                                data-email="{{ strtolower($cliente->user->email ?? '') }}"
                                data-telefono="{{ $cliente->user->telefono ?? '' }}"
                                data-fullname="{{ strtolower(($cliente->user->apellidos ?? '') . ' ' . ($cliente->user->nombre ?? '')) }}">
                                <td>{{ $cliente->user->nombre ?? '-' }}</td>
                                <td>{{ $cliente->user->apellidos ?? '-' }}</td>
                                <td>{{ $cliente->user->telefono ?? '-' }}</td>
                                <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $cliente->user->email ?? '-' }}</td>
                                <td>{{ $cliente->user->genero ?? '-' }}</td>
                                <td>{{ $cliente->user->edad ?? '-' }}</td>
                                <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $cliente->direccion ?? '-' }}</td>
                                <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $cliente->notas_adicionales ?? '-' }}</td>
                                <td style="white-space:nowrap">{{ $cliente->fecha_registro ?? '-' }}</td>
                                <td style="white-space:nowrap">
                                    <div style="display:flex;gap:4px;flex-wrap:wrap">
                                        <a href="{{ route('clientes.show', $cliente->id) }}"     class="action-link ver">Ver</a>
                                        <a href="{{ route('clientes.edit', $cliente->id) }}"     class="action-link editar">Editar</a>
                                        <a href="{{ route('clientes.historial', $cliente->id) }}" class="action-link hist">📅 Historial</a>
                                        <a href="{{ route('bonos.misClientes', $cliente->id) }}" class="action-link bonos">🎫 Bonos</a>
                                        <form id="delete-form-{{ $cliente->id }}" action="{{ route('clientes.destroy', $cliente->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" onclick="confirmarEliminacion({{ $cliente->id }})" class="action-link del">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

</body>
</html>
