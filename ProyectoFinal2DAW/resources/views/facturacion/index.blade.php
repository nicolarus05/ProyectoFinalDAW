<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación Mensual</title>
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
        .user-badge { display:flex; align-items:center; gap:8px; padding:6px 12px; background:#f3f4f8; border-radius:20px; text-decoration:none; color:#1e1a4b; font-size:13px; }
        .user-avatar { width:30px; height:30px; background:linear-gradient(135deg,#f472b6,#a855f7); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:13px; }
        .main-content { padding:20px 24px; flex:1; }
    </style>
</head>
<body>
@php $user = Auth::user(); $rol = $user->rol ?? null; @endphp
<div class="main-wrapper">
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
            <a href="{{ route('subcategorias.index') }}" class="nav-item"><span class="nav-icon">🏷️</span> Subcategorías</a>
            <a href="{{ route('productos.index') }}" class="nav-item"><span class="nav-icon">🛍️</span> Productos</a>
            @endif
            <a href="{{ route('cobros.index') }}" class="nav-item"><span class="nav-icon">💳</span> Cobros</a>
            <a href="{{ route('deudas.index') }}" class="nav-item"><span class="nav-icon">💰</span> Deudas</a>
            <a href="{{ route('bonos.index') }}" class="nav-item"><span class="nav-icon">🎫</span> Bonos</a>
            <a href="{{ route('bonos.clientesConBonos') }}" class="nav-item"><span class="nav-icon">👥</span> Clientes con Bonos</a>
            <a href="{{ route('caja.index') }}" class="nav-item"><span class="nav-icon">💵</span> Caja del Día</a>
            @if(in_array($rol,['admin','gerente']))
            <a href="{{ route('facturacion.index') }}" class="nav-item active"><span class="nav-icon">📊</span> Facturación</a>
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
    <div class="content">
        <header class="topbar">
            <div class="topbar-title">📊 Facturación Mensual</div>
            <a href="{{ route('profile.edit') }}" class="user-badge">
                <div class="user-avatar">{{ strtoupper(substr($user->nombre ?? 'U', 0, 1)) }}</div>
                <div style="display:flex;flex-direction:column">
                    <span style="font-weight:600;font-size:13px">{{ $user->nombre ?? '' }} {{ $user->apellidos ?? '' }}</span>
                    <span style="font-size:11px;color:#888;text-transform:capitalize">{{ $rol }}</span>
                </div>
            </a>
        </header>
        <main class="main-content">
    <div>

            <!-- Selector de Mes y Año -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" action="{{ route('facturacion.index') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mes</label>
                        <select name="mes" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            @foreach($meses as $num => $nombre)
                                <option value="{{ $num }}" {{ $mes == $num ? 'selected' : '' }}>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Año</label>
                        <select name="anio" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            @for($y = now()->year; $y >= now()->year - 5; $y--)
                                <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Día</label>
                        <select name="dia" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="">Mes completo</option>
                            @for($d = 1; $d <= $fechaFin->day; $d++)
                                <option value="{{ $d }}" {{ (int) $diaSeleccionado === $d ? 'selected' : '' }}>
                                    {{ str_pad($d, 2, '0', STR_PAD_LEFT) }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-semibold">
                        🔍 Consultar
                    </button>
                    @if($fechaDiaSeleccionado)
                        <a href="{{ route('facturacion.index', ['mes' => $mes, 'anio' => $anio]) }}"
                           class="text-sm font-semibold text-gray-600 px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition">
                            Ver mes completo
                        </a>
                    @endif
                </form>
                <p class="text-sm text-gray-600 mt-3">
                    Mostrando facturación de <strong>{{ $meses[$mes] }} {{ $anio }}</strong>
                    ({{ $fechaInicio->format('d/m/Y') }} - {{ $fechaFin->format('d/m/Y') }})
                </p>
            </div>

            @if($fechaDiaSeleccionado && $datosDiaSeleccionado)
            <!-- Facturación diaria -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6 border-l-4 border-indigo-500">
                <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">📆 Facturación del día</h2>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $fechaDiaSeleccionado->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                        </p>
                    </div>
                    <a href="{{ route('caja.index', ['fecha' => $fechaDiaSeleccionado->toDateString()]) }}"
                       class="bg-purple-600 text-white px-5 py-2 rounded-lg hover:bg-purple-700 transition font-semibold">
                        Ver caja diaria →
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-5">
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                        <div class="text-xs text-indigo-700 font-semibold mb-1">Total facturado</div>
                        <div class="text-2xl font-bold text-indigo-900">€{{ number_format($facturacionDia['totalGeneral'], 2) }}</div>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="text-xs text-green-700 font-semibold mb-1">Total cobrado</div>
                        <div class="text-2xl font-bold text-green-900">€{{ number_format($datosDiaSeleccionado['total'], 2) }}</div>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                        <div class="text-xs text-emerald-700 font-semibold mb-1">Efectivo</div>
                        <div class="text-xl font-bold text-emerald-900">€{{ number_format($datosDiaSeleccionado['efectivo'], 2) }}</div>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="text-xs text-blue-700 font-semibold mb-1">Tarjeta</div>
                        <div class="text-xl font-bold text-blue-900">€{{ number_format($datosDiaSeleccionado['tarjeta'], 2) }}</div>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="text-xs text-red-700 font-semibold mb-1">Deuda</div>
                        <div class="text-xl font-bold text-red-900">€{{ number_format($deudaDiaSeleccionado, 2) }}</div>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <div class="text-xs text-gray-700 font-semibold mb-1">Cobros</div>
                        <div class="text-xl font-bold text-gray-900">{{ $cobrosDiaSeleccionado->count() }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold text-gray-800">Servicios</span>
                            <span class="font-bold text-blue-700">€{{ number_format($facturacionDia['totalServicios'], 2) }}</span>
                        </div>
                        <div class="text-xs text-gray-600 space-y-1">
                            <div class="flex justify-between"><span>Peluquería</span><span>€{{ number_format($facturacionDia['serviciosPeluqueria'], 2) }}</span></div>
                            <div class="flex justify-between"><span>Estética</span><span>€{{ number_format($facturacionDia['serviciosEstetica'], 2) }}</span></div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-purple-200 bg-purple-50 p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold text-gray-800">Productos</span>
                            <span class="font-bold text-purple-700">€{{ number_format($facturacionDia['totalProductos'], 2) }}</span>
                        </div>
                        <div class="text-xs text-gray-600 space-y-1">
                            <div class="flex justify-between"><span>Peluquería</span><span>€{{ number_format($facturacionDia['productosPeluqueria'], 2) }}</span></div>
                            <div class="flex justify-between"><span>Estética</span><span>€{{ number_format($facturacionDia['productosEstetica'], 2) }}</span></div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-pink-200 bg-pink-50 p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold text-gray-800">Bonos</span>
                            <span class="font-bold text-pink-700">€{{ number_format($facturacionDia['totalBonos'], 2) }}</span>
                        </div>
                        <div class="text-xs text-gray-600 space-y-1">
                            <div class="flex justify-between"><span>Peluquería</span><span>€{{ number_format($facturacionDia['bonosPeluqueria'], 2) }}</span></div>
                            <div class="flex justify-between"><span>Estética</span><span>€{{ number_format($facturacionDia['bonosEstetica'], 2) }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Resumen Total Destacado -->
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg shadow-lg p-8 mb-8 text-white">
                <div class="text-center">
                    <h2 class="text-2xl font-bold mb-2">Total Facturado</h2>
                    <p class="text-6xl font-bold">€{{ number_format($totalGeneral, 2) }}</p>
                    <p class="text-sm opacity-90 mt-2">{{ $meses[$mes] }} {{ $anio }}</p>
                </div>
            </div>

            <!-- Grid de Desglose -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                
                <!-- SERVICIOS -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        💇 Servicios
                        <span class="text-lg font-normal text-gray-600">
                            (€{{ number_format($totalServicios, 2) }})
                        </span>
                    </h2>

                    <!-- Peluquería -->
                    <div class="mb-4 p-4 bg-blue-50 rounded-lg border-2 border-blue-200">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-3xl">✂️</span>
                                <span class="font-semibold text-gray-800 text-lg">Peluquería</span>
                            </div>
                            <span class="text-2xl font-bold text-blue-700">
                                €{{ number_format($serviciosPeluqueria, 2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Estética -->
                    <div class="p-4 bg-pink-50 rounded-lg border-2 border-pink-200">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-3xl">💆</span>
                                <span class="font-semibold text-gray-800 text-lg">Estética</span>
                            </div>
                            <span class="text-2xl font-bold text-pink-700">
                                €{{ number_format($serviciosEstetica, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- PRODUCTOS -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        📦 Productos
                        <span class="text-lg font-normal text-gray-600">
                            (€{{ number_format($totalProductos, 2) }})
                        </span>
                    </h2>

                    <!-- Peluquería -->
                    <div class="mb-4 p-4 bg-purple-50 rounded-lg border-2 border-purple-200">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-3xl">✂️</span>
                                <span class="font-semibold text-gray-800 text-lg">Peluquería</span>
                            </div>
                            <span class="text-2xl font-bold text-purple-700">
                                €{{ number_format($productosPeluqueria, 2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Estética -->
                    <div class="p-4 bg-orange-50 rounded-lg border-2 border-orange-200">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-3xl">💆</span>
                                <span class="font-semibold text-gray-800 text-lg">Estética</span>
                            </div>
                            <span class="text-2xl font-bold text-orange-700">
                                €{{ number_format($productosEstetica, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- BONOS VENDIDOS -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    🎫 Bonos Vendidos
                </h2>
                
                <!-- Bonos Peluquería -->
                <div class="p-4 bg-blue-50 rounded-lg border-2 border-blue-200 mb-4">
                    <div class="flex justify-between items-center">
                        <span class="font-semibold text-gray-800">💇 Bonos Peluquería</span>
                        <span class="text-2xl font-bold text-blue-700">
                            €{{ number_format($bonosPeluqueria, 2) }}
                        </span>
                    </div>
                </div>
                
                <!-- Bonos Estética -->
                <div class="p-4 bg-pink-50 rounded-lg border-2 border-pink-200 mb-4">
                    <div class="flex justify-between items-center">
                        <span class="font-semibold text-gray-800">✨ Bonos Estética</span>
                        <span class="text-2xl font-bold text-pink-700">
                            €{{ number_format($bonosEstetica, 2) }}
                        </span>
                    </div>
                </div>
                
                <!-- Total Bonos -->
                <div class="p-4 bg-indigo-50 rounded-lg border-2 border-indigo-200">
                    <div class="flex justify-between items-center">
                        <span class="font-semibold text-gray-800 text-lg">Total Bonos</span>
                        <span class="text-3xl font-bold text-indigo-700">
                            €{{ number_format($bonosVendidos, 2) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Desglose por Categoría (Resumen Visual) -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">📊 Desglose por Categoría</h2>
                
                <div class="space-y-4">
                    <!-- Servicios -->
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold text-gray-800">Servicios</span>
                            <span class="font-bold text-gray-900">
                                €{{ number_format($totalServicios, 2) }}
                                <span class="text-sm text-gray-600">
                                    ({{ $totalGeneral > 0 ? number_format(($totalServicios / $totalGeneral) * 100, 1) : 0 }}%)
                                </span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-blue-600 h-3 rounded-full" 
                                 style="width: {{ $totalGeneral > 0 ? ($totalServicios / $totalGeneral) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>

                    <!-- Productos -->
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold text-gray-800">Productos</span>
                            <span class="font-bold text-gray-900">
                                €{{ number_format($totalProductos, 2) }}
                                <span class="text-sm text-gray-600">
                                    ({{ $totalGeneral > 0 ? number_format(($totalProductos / $totalGeneral) * 100, 1) : 0 }}%)
                                </span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-purple-600 h-3 rounded-full" 
                                 style="width: {{ $totalGeneral > 0 ? ($totalProductos / $totalGeneral) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>

                    <!-- Bonos -->
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold text-gray-800">Bonos</span>
                            <span class="font-bold text-gray-900">
                                €{{ number_format($bonosVendidos, 2) }}
                                <span class="text-sm text-gray-600">
                                    ({{ $totalGeneral > 0 ? number_format(($bonosVendidos / $totalGeneral) * 100, 1) : 0 }}%)
                                </span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-indigo-600 h-3 rounded-full" 
                                 style="width: {{ $totalGeneral > 0 ? ($bonosVendidos / $totalGeneral) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen y Verificación -->
            <div class="bg-blue-50 border-2 border-blue-200 rounded-lg shadow-md p-6 mt-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">📊 Resumen de Verificación</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white p-4 rounded-lg border border-gray-300">
                        <div class="text-sm text-gray-600 mb-1">Total Facturado</div>
                        <div class="text-2xl font-bold text-gray-900">€{{ number_format($totalGeneral, 2) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Servicios + Productos + Bonos</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-red-300">
                        <div class="text-sm text-gray-600 mb-1">Deuda Pendiente</div>
                        <div class="text-2xl font-bold text-red-600">€{{ number_format($deudaTotal, 2) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Pendiente de cobro</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-green-300">
                        <div class="text-sm text-gray-600 mb-1">Total Cobrado (Cajas)</div>
                        <div class="text-2xl font-bold text-green-600">€{{ number_format($sumaCajasDiarias, 2) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Efectivo + Tarjeta recibidos</div>
                    </div>
                </div>
                @php
                    $diferencia = abs($totalRealmenteCobrado - $sumaCajasDiarias);
                @endphp
                @if($diferencia < 0.01)
                    <div class="mt-4 p-3 bg-green-100 border border-green-300 rounded text-center">
                        <span class="text-green-800 font-semibold">✓ Los cálculos son correctos</span>
                        <span class="text-sm text-gray-600 ml-2">(Total Facturado - Deuda = Suma de Cajas)</span>
                    </div>
                @else
                    <div class="mt-4 p-3 bg-yellow-100 border border-yellow-300 rounded text-center">
                        <span class="text-yellow-800 font-semibold">⚠ Diferencia detectada: €{{ number_format($diferencia, 2) }}</span>
                    </div>
                @endif
            </div>

            <!-- Cajas Diarias -->
            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">📅 Cajas Diarias</h2>
                <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-7 xl:grid-cols-10 gap-2">
                    @foreach($cajasDiarias as $fecha => $datos)
                        @php
                            $esDiaConsultado = $fechaDiaSeleccionado && $fechaDiaSeleccionado->toDateString() === $fecha;
                        @endphp
                        <a href="{{ route('caja.index', ['fecha' => $fecha]) }}"
                           class="block no-underline p-2 rounded transition hover:shadow-md hover:-translate-y-0.5 {{ $datos['total'] > 0 ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200 opacity-60' }} {{ $esDiaConsultado ? 'ring-2 ring-indigo-500' : '' }}"
                           title="Ver caja diaria del {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}">
                            <div class="text-[10px] text-gray-500 mb-1 uppercase font-semibold text-center">
                                {{ \Carbon\Carbon::parse($fecha)->translatedFormat('D d') }}
                            </div>
                            <div class="text-center mb-1">
                                <div class="font-bold text-sm {{ $datos['total'] > 0 ? 'text-green-700' : 'text-gray-400' }}">
                                    €{{ number_format($datos['total'], 2) }}
                                </div>
                            </div>
                            @if($datos['total'] > 0)
                                <div class="border-t border-green-200 pt-1 space-y-0.5">
                                    <div class="flex justify-between items-center text-[9px]">
                                        <span class="text-green-600 flex items-center">
                                            <svg class="w-2.5 h-2.5 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                                <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                                            </svg>
                                            Efec.
                                        </span>
                                        <span class="font-semibold text-green-700">€{{ number_format($datos['efectivo'], 2) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[9px]">
                                        <span class="text-blue-600 flex items-center">
                                            <svg class="w-2.5 h-2.5 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v2H4V6zm0 4h12v4H4v-4z"/>
                                            </svg>
                                            Tarj.
                                        </span>
                                        <span class="font-semibold text-blue-700">€{{ number_format($datos['tarjeta'], 2) }}</span>
                                    </div>
                                </div>
                                @if(($datos['peluqueria'] ?? 0) > 0 || ($datos['estetica'] ?? 0) > 0)
                                    <div class="border-t border-green-200 pt-1 space-y-0.5">
                                        <div class="flex justify-between items-center text-[9px]">
                                            <span class="text-pink-600">✂️ Pelu.</span>
                                            <span class="font-semibold text-pink-700">€{{ number_format($datos['peluqueria'] ?? 0, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-[9px]">
                                            <span class="text-purple-600">💆 Esté.</span>
                                            <span class="font-semibold text-purple-700">€{{ number_format($datos['estetica'] ?? 0, 2) }}</span>
                                        </div>
                                    </div>
                                @endif
                            @endif
                            <div class="text-[9px] text-center mt-1 text-indigo-600 font-semibold">Ver caja</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
        </main>
    </div>
</div>
</body>
</html>
