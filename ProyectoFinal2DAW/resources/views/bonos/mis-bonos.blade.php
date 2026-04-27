<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonos del Cliente</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
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
        .topbar-sub { font-size:12px; color:#888; }
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
            <a href="{{ route('bonos.index') }}" class="nav-item"><span class="nav-icon">🎫</span> Bonos</a>
            <a href="{{ route('bonos.clientesConBonos') }}" class="nav-item active"><span class="nav-icon">👥</span> Clientes con Bonos</a>
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
            <div style="display:flex;align-items:center;gap:14px">
                <a href="{{ route('bonos.clientesConBonos') }}" style="color:#1e1a4b;font-size:20px;text-decoration:none">←</a>
                <div>
                    <div class="topbar-title">🎫 Bonos del Cliente</div>
                    <div class="topbar-sub">{{ $cliente->user->nombre ?? '' }} {{ $cliente->user->apellidos ?? '' }} · {{ $cliente->user->email ?? '' }}</div>
                </div>
            </div>
            <a href="{{ route('profile.edit') }}" class="user-badge">
                <div class="user-avatar">{{ strtoupper(substr($user->nombre ?? 'U', 0, 1)) }}</div>
                <div style="display:flex;flex-direction:column"><span style="font-weight:600;font-size:13px">{{ $user->nombre ?? '' }} {{ $user->apellidos ?? '' }}</span><span style="font-size:11px;color:#888;text-transform:capitalize">{{ $rol }}</span></div>
            </a>
        </header>
        <main class="main-content">
    <div class="bg-white rounded-xl shadow-sm p-6">

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-4 mb-4 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if($bonos->count() > 0)
            <div class="space-y-6">
                @foreach($bonos as $bono)
                    <div class="border rounded-lg p-6 {{ $bono->estado === 'activo' ? 'border-green-500 bg-green-50' : ($bono->estado === 'expirado' ? 'border-red-500 bg-red-50' : 'border-gray-400 bg-gray-50') }}">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-bold">{{ $bono->plantilla->nombre }}</h3>
                                @if($bono->plantilla->descripcion)
                                    <p class="text-gray-600">{{ $bono->plantilla->descripcion }}</p>
                                @endif
                            </div>
                            <div>
                                @if($bono->estado === 'activo')
                                    <span class="inline-block px-3 py-1 bg-green-600 text-white rounded-full text-sm font-semibold">Activo</span>
                                @elseif($bono->estado === 'usado')
                                    <span class="inline-block px-3 py-1 bg-gray-600 text-white rounded-full text-sm font-semibold">Usado</span>
                                @else
                                    <span class="inline-block px-3 py-1 bg-red-600 text-white rounded-full text-sm font-semibold">Expirado</span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mb-4 text-sm">
                            <div>
                                <p class="text-gray-600">Precio pagado:</p>
                                <p class="font-semibold text-lg">€{{ number_format($bono->plantilla->precio, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Fecha de compra:</p>
                                <p class="font-semibold">{{ $bono->fecha_compra->format('d/m/Y') }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Vendido por:</p>
                                <p class="font-semibold">
                                    @if($bono->empleado && $bono->empleado->user)
                                        {{ $bono->empleado->user->nombre }} {{ $bono->empleado->user->apellidos }}
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600">Fecha de expiración:</p>
                                @if($bono->plantilla->duracion_dias)
                                    <p class="font-semibold {{ $bono->estaExpirado() ? 'text-red-600' : '' }}">
                                        {{ $bono->fecha_expiracion->format('d/m/Y') }}
                                    </p>
                                @else
                                    <p class="font-semibold text-purple-600">✨ Sin límite</p>
                                @endif
                            </div>
                        </div>

                        <div>
                            <p class="font-semibold mb-2">Servicios:</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($bono->servicios as $servicio)
                                    @php
                                        $disponibles = $servicio->pivot->cantidad_total - $servicio->pivot->cantidad_usada;
                                        $porcentaje = ($servicio->pivot->cantidad_usada / $servicio->pivot->cantidad_total) * 100;
                                        // Obtener usos de este servicio específico
                                        $usosServicio = $bono->usoDetalles->where('servicio_id', $servicio->id);
                                    @endphp
                                    <div class="bg-white border rounded p-3">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="font-medium">
                                                {{ $servicio->nombre }}
                                                @if($servicio->categoria === 'peluqueria')
                                                    <span class="text-blue-600">💇</span>
                                                @else
                                                    <span class="text-pink-600">💅</span>
                                                @endif
                                            </span>
                                            <span class="text-sm font-semibold {{ $disponibles > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $disponibles }}/{{ $servicio->pivot->cantidad_total }} disponibles
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $porcentaje }}%"></div>
                                        </div>
                                        
                                        @if($usosServicio->count() > 0)
                                            <div class="mt-3 pt-2 border-t border-gray-200">
                                                <p class="text-xs font-semibold text-gray-600 mb-1">Fechas de uso:</p>
                                                <div class="space-y-1">
                                                    @foreach($usosServicio as $uso)
                                                        <div class="text-xs text-gray-700 flex items-center gap-1">
                                                            <span class="text-green-600">✓</span>
                                                            <span>{{ \Carbon\Carbon::parse($uso->cita->fecha_hora)->format('d/m/Y H:i') }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-500 mb-4">Este cliente no tiene bonos.</p>
                <a href="{{ route('bonos.index') }}" class="text-blue-600 hover:underline">Ver bonos disponibles</a>
            </div>
        @endif

        <div class="mt-6">
            <a href="{{ route('bonos.clientesConBonos') }}" class="px-4 py-2 rounded-lg text-white font-semibold" style="background:#1e1a4b;">← Volver</a>
        </div>
    </div>
        </main>
    </div>
</div>
</body>
</html>
