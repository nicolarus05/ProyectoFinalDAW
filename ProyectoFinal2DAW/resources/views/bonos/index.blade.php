<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonos - Plantillas</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
        .btn-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-float:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }
        .btn-crear-top {
            display: inline-block;
            background-color: #16a34a;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .btn-crear-top:hover { background-color: #15803d; }
        :root { --sidebar-w: 210px; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f3f4f8; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .sidebar { position:fixed; top:0; left:0; width:var(--sidebar-w); height:100vh; background:#1e1a4b; display:flex; flex-direction:column; z-index:100; overflow-y:auto; }
        .sidebar-logo { padding:18px 16px 14px; border-bottom:1px solid rgba(255,255,255,.1); }
        .logo-icon { width:36px; height:36px; background:linear-gradient(135deg,#f472b6,#a855f7); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; }
        .logo-text { font-weight:700; font-size:14px; color:#fff; }
        .logo-sub { font-size:10px; color:rgba(255,255,255,.6); }
        .sidebar-nav { flex:1; padding:10px 0; }
        .nav-item { display:flex; align-items:center; gap:9px; padding:9px 16px; color:rgba(255,255,255,.75); text-decoration:none; font-size:13px; font-weight:500; transition:.15s; border-left:3px solid transparent; }
        .nav-item:hover { background:rgba(255,255,255,.08); color:#fff; }
        .nav-item.active { background:linear-gradient(135deg,#f472b6,#a855f7); color:#fff; border-left-color:transparent; }
        .nav-icon { font-size:15px; min-width:18px; }
        .sidebar-help { margin:10px 12px; background:rgba(255,255,255,.08); border-radius:10px; padding:12px; color:rgba(255,255,255,.8); }
        .sidebar-footer { padding:12px 16px; font-size:10px; color:rgba(255,255,255,.4); border-top:1px solid rgba(255,255,255,.08); }
        .content { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; }
        .topbar { background:#fff; padding:14px 24px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 1px 4px rgba(0,0,0,.08); position:sticky; top:0; z-index:50; }
        .topbar-title { font-size:18px; font-weight:700; color:#1e1a4b; }
        .user-badge { display:flex; align-items:center; gap:8px; padding:6px 12px; background:#f3f4f8; border-radius:20px; text-decoration:none; color:#1e1a4b; font-size:13px; }
        .user-avatar { width:30px; height:30px; background:linear-gradient(135deg,#f472b6,#a855f7); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:13px; }
        .main-content { padding:20px 24px; flex:1; }
    </style>
</head>
<body>
@php $user = Auth::user(); $rol = $user->rol ?? null; @endphp
<div class="main-wrapper">
    <aside class="sidebar">
        <div class="sidebar-logo"><div style="display:flex;align-items:center;gap:10px"><div class="logo-icon">💇‍♀️</div><div><div class="logo-text">Salón de Belleza</div><div class="logo-sub">Sistema de Gestión</div></div></div></div>
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-item"><span class="nav-icon">🏠</span> Inicio</a>
            <a href="{{ route('citas.index') }}" class="nav-item"><span class="nav-icon">📅</span> Citas</a>
            <a href="{{ route('clientes.index') }}" class="nav-item"><span class="nav-icon">👤</span> Clientes</a>
            @if(in_array($rol,['admin','gerente']))
            <a href="{{ route('empleados.index') }}" class="nav-item"><span class="nav-icon">👔</span> Empleados</a>
            <a href="{{ route('servicios.index') }}" class="nav-item"><span class="nav-icon">✂️</span> Servicios</a>
            <a href="{{ route('subcategorias.index') }}" class="nav-item"><span class="nav-icon">🏷️</span> Subcategorías</a>
            <a href="{{ route('productos.index') }}" class="nav-item"><span class="nav-icon">🛍️</span> Productos</a>
            @endif
            <a href="{{ route('cobros.index') }}" class="nav-item"><span class="nav-icon">💳</span> Cobros</a>
            <a href="{{ route('deudas.index') }}" class="nav-item"><span class="nav-icon">💰</span> Deudas</a>
            <a href="{{ route('bonos.index') }}" class="nav-item active"><span class="nav-icon">🎫</span> Bonos</a>
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
        <div class="sidebar-help"><div style="display:flex;align-items:center;gap:8px;margin-bottom:5px"><span style="font-size:20px">❓</span><span style="font-weight:700;font-size:12px">¿Necesitas ayuda?</span></div><p style="opacity:.85;font-size:11px;line-height:1.4">Consulta nuestra guía o contacta soporte</p></div>
        <div class="sidebar-footer">© {{ date('Y') }} Salón de Belleza</div>
    </aside>
    <div class="content">
        <header class="topbar">
            <div class="topbar-title">🎫 Bonos Disponibles</div>
            <div style="display:flex;align-items:center;gap:10px">
                <a href="{{ route('bonos.ventaMultiple') }}" style="background:#1e1a4b;color:#fff;padding:7px 14px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600">🛒 Venta Múltiple</a>
                <a href="{{ route('bonos.create') }}" style="background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;padding:7px 14px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600">➕ Crear Bono</a>
                <a href="{{ route('profile.edit') }}" class="user-badge">
                    <div class="user-avatar">{{ strtoupper(substr($user->nombre ?? 'U', 0, 1)) }}</div>
                    <div style="display:flex;flex-direction:column"><span style="font-weight:600;font-size:13px">{{ $user->nombre ?? '' }} {{ $user->apellidos ?? '' }}</span><span style="font-size:11px;color:#888;text-transform:capitalize">{{ $rol }}</span></div>
                </a>
            </div>
        </header>
        <main class="main-content">
    <div>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-4 mb-4 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if($plantillas->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($plantillas as $plantilla)
                    <div class="border rounded-lg p-6 shadow hover:shadow-lg transition">
                        <h3 class="text-xl font-bold mb-2">{{ $plantilla->nombre }}</h3>
                        
                        @if($plantilla->descripcion)
                            <p class="text-gray-600 mb-4">{{ $plantilla->descripcion }}</p>
                        @endif

                        <div class="mb-4">
                            <p class="font-semibold text-2xl text-green-600">€{{ number_format($plantilla->precio, 2) }}</p>
                            <p class="text-sm text-gray-500">
                                @if($plantilla->duracion_dias)
                                    Válido por {{ $plantilla->duracion_dias }} días
                                @else
                                    <span class="text-purple-600 font-semibold">✨ Sin límite de tiempo</span>
                                @endif
                            </p>
                        </div>

                        <div class="mb-4">
                            <p class="font-semibold mb-2">Servicios incluidos:</p>
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach($plantilla->servicios as $servicio)
                                    <li class="text-sm">
                                        {{ $servicio->nombre }} 
                                        @if($servicio->tipo === 'peluqueria')
                                            <span class="text-blue-600">💇</span>
                                        @else
                                            <span class="text-pink-600">💅</span>
                                        @endif
                                        <span class="font-semibold">(x{{ $servicio->pivot->cantidad }})</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="flex gap-2">
                            <a href="{{ route('bonos.comprar', $plantilla->id) }}" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded text-center hover:bg-blue-700">
                                Vender
                            </a>
                            <a href="{{ route('bonos.edit', $plantilla->id) }}" class="flex-1 bg-gray-600 text-white px-4 py-2 rounded text-center hover:bg-gray-700">
                                Editar
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-500 mb-4">No hay bonos creados aún.</p>
                <a href="{{ route('bonos.create') }}" class="text-blue-600 hover:underline">Crear el primer bono</a>
            </div>
        @endif
    </div>

    <!-- Botón flotante para crear bono -->
    <a href="{{ route('bonos.create') }}" class="btn-float" 
       style="background-color: #16a34a; color: white; text-decoration: none;" 
       title="Crear Nuevo Bono">
        <span style="font-weight: bold; font-size: 32px; line-height: 1;">+</span>
    </a>
    </div>
        </main>
    </div>
</div>
</body>
</html>
