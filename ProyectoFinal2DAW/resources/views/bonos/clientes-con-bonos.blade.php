<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes con Bonos</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
        .btn-volver {
            display: inline-block;
            background-color: #3b82f6;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .btn-volver:hover { background-color: #2563eb; }
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
            <div class="topbar-title">👥 Clientes con Bonos Activos</div>
            <a href="{{ route('profile.edit') }}" class="user-badge">
                <div class="user-avatar">{{ strtoupper(substr($user->nombre ?? 'U', 0, 1)) }}</div>
                <div style="display:flex;flex-direction:column"><span style="font-weight:600;font-size:13px">{{ $user->nombre ?? '' }} {{ $user->apellidos ?? '' }}</span><span style="font-size:11px;color:#888;text-transform:capitalize">{{ $rol }}</span></div>
            </a>
        </header>
        <main class="main-content">
    <div>

            @if($clientes->count() > 0)
                <div class="grid gap-6">
                    @foreach($clientes as $cliente)
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">
                                        {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}
                                    </h3>
                                    <p class="text-gray-600">📧 {{ $cliente->user->email }}</p>
                                    @if($cliente->user->telefono)
                                        <p class="text-gray-600">📞 {{ $cliente->user->telefono }}</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <span class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded font-semibold">
                                        {{ $cliente->bonos->count() }} {{ $cliente->bonos->count() === 1 ? 'Bono' : 'Bonos' }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                @foreach($cliente->bonos as $bono)
                                    <div class="bg-white border border-purple-200 rounded p-3">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <h4 class="font-bold text-purple-700">
                                                    🎫 {{ $bono->plantilla->nombre }}
                                                </h4>
                                                @if($bono->plantilla->descripcion)
                                                    <p class="text-sm text-gray-600 mt-1">
                                                        {{ $bono->plantilla->descripcion }}
                                                    </p>
                                                @endif
                                                <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                                                    <div>
                                                        <span class="text-gray-600">Comprado:</span>
                                                        <span class="font-semibold">{{ \Carbon\Carbon::parse($bono->fecha_compra)->format('d/m/Y') }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-600">Expira:</span>
                                                        @if($bono->plantilla->duracion_dias && $bono->fecha_expiracion)
                                                            <span class="font-semibold">{{ \Carbon\Carbon::parse($bono->fecha_expiracion)->format('d/m/Y') }}</span>
                                                        @else
                                                            <span class="font-semibold text-purple-600">✨ Sin límite</span>
                                                        @endif
                                                    </div>
                                                    @if($bono->empleado && $bono->empleado->user)
                                                    <div class="col-span-2">
                                                        <span class="text-gray-600">Vendido por:</span>
                                                        <span class="font-semibold">{{ $bono->empleado->user->nombre }} {{ $bono->empleado->user->apellidos }}</span>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-right">
                                                    <p class="text-sm text-gray-600">Precio pagado</p>
                                                    <p class="text-xl font-bold text-green-600">€{{ number_format($bono->precio_pagado, 2) }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Servicios incluidos -->
                                        @if($bono->plantilla && $bono->plantilla->servicios && $bono->plantilla->servicios->count() > 0)
                                        <div class="mt-3 pt-3 border-t border-gray-200">
                                            <p class="text-sm font-semibold text-gray-700 mb-2">Servicios incluidos:</p>
                                            <div class="space-y-2">
                                                @foreach($bono->plantilla->servicios as $servicio)
                                                    @php
                                                        $cantidadDisponible = $bono->cantidadDisponible($servicio->id);
                                                        $cantidadTotal = $servicio->pivot->cantidad;
                                                        $cantidadUsada = $cantidadTotal - $cantidadDisponible;
                                                        // Obtener usos de este servicio específico
                                                        $usosServicio = $bono->usoDetalles->where('servicio_id', $servicio->id);
                                                    @endphp
                                                    <div class="bg-gray-50 rounded p-2">
                                                        <div class="flex justify-between items-center text-sm mb-1">
                                                            <span>
                                                                @if($servicio->categoria === 'peluqueria')
                                                                    💇
                                                                @else
                                                                    💅
                                                                @endif
                                                                {{ $servicio->nombre }}
                                                            </span>
                                                            <span class="font-semibold">
                                                                @if($cantidadDisponible > 0)
                                                                    <span class="text-green-600">{{ $cantidadDisponible }}/{{ $cantidadTotal }} disponibles</span>
                                                                @else
                                                                    <span class="text-red-600">❌ Agotado ({{ $cantidadUsada }}/{{ $cantidadTotal }})</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                        
                                                        @if($usosServicio->count() > 0)
                                                            <div class="ml-4 mt-2 space-y-1">
                                                                <p class="text-xs font-semibold text-gray-600">Fechas de uso:</p>
                                                                @foreach($usosServicio as $uso)
                                                                    @php
                                                                        // Intentar obtener fecha de la cita, si no usar created_at del uso
                                                                        $fechaUso = null;
                                                                        if ($uso->cita && $uso->cita->fecha_hora) {
                                                                            $fechaUso = \Carbon\Carbon::parse($uso->cita->fecha_hora)->format('d/m/Y H:i');
                                                                        } elseif ($uso->created_at) {
                                                                            $fechaUso = \Carbon\Carbon::parse($uso->created_at)->format('d/m/Y H:i');
                                                                        }
                                                                    @endphp
                                                                    @if($fechaUso)
                                                                        <div class="text-xs text-gray-700 flex items-center gap-1">
                                                                            <span class="text-green-600">✓</span>
                                                                            <span>{{ $fechaUso }}</span>
                                                                        </div>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-500 text-lg">No hay clientes con bonos activos en este momento.</p>
                    <a href="{{ route('bonos.index') }}" class="mt-4 inline-block text-blue-600 hover:underline">
                        Ver bonos disponibles para vender
                    </a>
                </div>
            @endif
        </div>
    </div>
        </main>
    </div>
</div>
</body>
</html>
