<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este usuario?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
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
        .btn-primary { background:linear-gradient(135deg,#f472b6,#a855f7); color:#fff !important; border:none; padding:8px 18px; border-radius:8px; font-weight:600; font-size:.82rem; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
        .btn-primary:hover { opacity:.9; }
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
            <a href="{{ route('horarios.index') }}" class="nav-item"><span class="nav-icon">⏰</span> Horarios</a>
            <a href="{{ route('asistencia.index') }}" class="nav-item"><span class="nav-icon">🕐</span> Asistencia</a>
            <a href="{{ route('users.index') }}" class="nav-item active"><span class="nav-icon">⚙️</span> Usuarios</a>
        </nav>
        <div class="sidebar-footer"><p>© 2026 Salón de Belleza</p></div>
    </aside>
    <div class="content">
        <header class="topbar">
            <span class="topbar-title">⚙️ Usuarios</span>
            <div class="topbar-actions">
                <a href="{{ route('users.create') }}" class="btn-primary">➕ Añadir Usuario</a>
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
    <div class="bg-white rounded-lg shadow-md p-6">

        @if(session('success'))
            <div class="text-green-600 mb-4">{{ str_replace('user', 'usuario', session('success')) }}</div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-2 border">Nombre</th>
                        <th class="p-2 border">Email</th>
                        <th class="p-2 border">Teléfono</th>
                        <th class="p-2 border">Edad</th>
                        <th class="p-2 border">Género</th>
                        <th class="p-2 border">Rol</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr class="text-center border-t">
                            <td class="p-2 border">{{ $user->nombre }} {{ $user->apellidos }}</td>
                            <td class="p-2 border">{{ $user->email }}</td>
                            <td class="p-2 border">{{ $user->telefono }}</td>
                            <td class="p-2 border">{{ $user->edad }}</td>
                            <td class="p-2 border">{{ $user->genero }}</td>
                            <td class="p-2 border">{{ ucfirst($user->rol) }}</td>
                            <td class="p-2 border space-y-1">
                                <a href="{{ route('users.show', $user->id) }}" class="text-blue-600 hover:underline block">Ver</a>
                                <a href="{{ route('users.edit', $user->id) }}" class="text-yellow-600 hover:underline block">Editar</a>
                                <form id="delete-form-{{ $user->id }}" action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;" onsubmit="return false;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="text-red-600 hover:underline" onclick="confirmarEliminacion({{ $user->id }})">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
        </main>
    </div>
</div>
</body>
</html>
