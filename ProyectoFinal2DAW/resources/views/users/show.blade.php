<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del usuario</title>
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
        .btn-navy { background:#1e1a4b; color:#fff !important; border:none; padding:8px 18px; border-radius:8px; font-weight:600; font-size:.82rem; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
        .btn-navy:hover { opacity:.9; }
        .btn-yellow { background:#f59e0b; color:#fff !important; border:none; padding:8px 18px; border-radius:8px; font-weight:600; font-size:.82rem; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
        .btn-yellow:hover { opacity:.9; }
    </style>
</head>
@php $authUser = auth()->user(); $rol = $authUser->rol ?? ''; @endphp
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
            <span class="topbar-title">⚙️ Detalle del Usuario</span>
            <div class="topbar-actions">
                <a href="{{ route('users.edit', $user->id) }}" class="btn-yellow">✏️ Editar</a>
                <a href="{{ route('users.index') }}" class="btn-navy">← Volver</a>
                <a href="{{ route('profile.edit') }}" class="topbar-user">
                    <div class="avatar">{{ strtoupper(substr($authUser->nombre ?? $authUser->name ?? 'U', 0, 1)) }}</div>
                    <div class="user-info">
                        <div class="name">{{ $authUser->nombre ?? $authUser->name ?? '' }} {{ $authUser->apellidos ?? '' }}</div>
                        <div class="role">{{ $rol }}</div>
                    </div>
                </a>
            </div>
        </header>
        <main class="main-content">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">

        <div class="space-y-3">
            <div>
                <span class="font-semibold">Nombre:</span>
                <span>{{ $user->nombre }}</span>
            </div>
            <div>
                <span class="font-semibold">Apellidos:</span>
                <span>{{ $user->apellidos }}</span>
            </div>
            <div>
                <span class="font-semibold">Email:</span>
                <span>{{ $user->email }}</span>
            </div>
            <div>
                <span class="font-semibold">Teléfono:</span>
                <span>{{ $user->telefono ?? 'No especificado' }}</span>
            </div>
            <div>
                <span class="font-semibold">Edad:</span>
                <span>{{ $user->edad ?? 'No especificada' }}</span>
            </div>
            <div>
                <span class="font-semibold">Género:</span>
                <span>{{ $user->genero ?? 'No especificado' }}</span>
            </div>
            <div>
                <span class="font-semibold">Rol:</span>
                <span>{{ ucfirst($user->rol) }}</span>
            </div>
        </div>

        {{-- Mostrar campos específicos según el rol --}}
        @if ($user->rol === 'empleado' && $user->empleado)
            <div class="mt-6">
                <h3 class="text-xl font-semibold mb-2">Datos del empleado</h3>
                <div>
                    <span class="font-semibold">Especialización:</span>
                    <span>{{ ucfirst($user->empleado->categoria) ?? 'No especificada' }}</span>
                </div>
            </div>
        @elseif ($user->rol === 'cliente' && $user->cliente)
            <div class="mt-6">
                <h3 class="text-xl font-semibold mb-2">Datos del cliente</h3>
                <div>
                    <span class="font-semibold">Dirección:</span>
                    <span>{{ $user->cliente->direccion ?? 'No especificada' }}</span>
                </div>
                <div>
                    <span class="font-semibold">Fecha de Registro:</span>
                    <span>{{ $user->cliente->fecha_registro ?? 'No especificada' }}</span>
                </div>
                <div>
                    <span class="font-semibold">Notas Adicionales:</span>
                    <span>{{ $user->cliente->notas_adicionales ?? 'Ninguna' }}</span>
                </div>
            </div>
        @endif

        <div class="flex space-x-4 mt-8">
            <a href="{{ route('users.index') }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">Volver a la lista</a>
            <a href="{{ route('users.edit', $user->id) }}" class="bg-yellow-400 text-white px-4 py-2 rounded hover:bg-yellow-500">Editar</a>
        </div>
    </div>
        </main>
    </div>
</div>
</body>
</html>
