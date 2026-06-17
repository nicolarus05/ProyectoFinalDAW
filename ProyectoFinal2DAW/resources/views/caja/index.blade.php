<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja del Día</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <style>
        :root { --sidebar-w: 210px; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f3f4f8; }
        .main-wrapper { display: flex; min-height: 100vh; }
        /* SIDEBAR */
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
        /* CONTENT */
        .content { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; min-height:100vh; }
        .topbar { background:#fff; padding:14px 24px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 1px 4px rgba(0,0,0,.08); position:sticky; top:0; z-index:50; }
        .topbar-title { font-size:18px; font-weight:700; color:#1e1a4b; }
        .topbar-sub { font-size:12px; color:#888; margin-top:2px; }
        .user-badge { display:flex; align-items:center; gap:8px; padding:6px 12px; background:#f3f4f8; border-radius:20px; text-decoration:none; color:#1e1a4b; font-size:13px; }
        .user-avatar { width:30px; height:30px; background:linear-gradient(135deg,#f472b6,#a855f7); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:13px; }
        .main-content { padding:20px 24px; flex:1; }
        /* PANEL OVERRIDES — elimina dark: variants visualmente */
        .panel-navy { background:#1e1a4b !important; border-color:#1e1a4b !important; }
        .panel-navy h3 { color:#fff !important; }
    </style>
</head>
<body>
@php $user = Auth::user(); $rol = $user->rol ?? null; @endphp
<div class="main-wrapper">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div style="display:flex;align-items:center;gap:10px">
                <div class="logo-icon">💇‍♀️</div>
                <div>
                    <div class="logo-text">Salón de Belleza</div>
                    <div class="logo-sub">Sistema de Gestión</div>
                </div>
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
            <a href="{{ route('caja.index') }}" class="nav-item active"><span class="nav-icon">💵</span> Caja del Día</a>
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

    <!-- CONTENT -->
    <div class="content">
        <header class="topbar">
            <div>
                <div class="topbar-title">💵 Caja del Día</div>
                <div class="topbar-sub">{{ \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</div>
            </div>
            <a href="{{ route('profile.edit') }}" class="user-badge">
                <div class="user-avatar">{{ strtoupper(substr($user->nombre ?? 'U', 0, 1)) }}</div>
                <div style="display:flex;flex-direction:column">
                    <span style="font-weight:600;font-size:13px">{{ $user->nombre ?? '' }} {{ $user->apellidos ?? '' }}</span>
                    <span style="font-size:11px;color:#888;text-transform:capitalize">{{ $rol }}</span>
                </div>
            </a>
        </header>
        <main class="main-content">

        <!-- Selector de fecha -->
        <div class="bg-white rounded-xl shadow-sm mb-5 p-5">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <form method="GET" action="{{ route('caja.index') }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="fecha" class="block text-sm font-semibold text-gray-700 mb-2">Consultar caja</label>
                        <input type="date" id="fecha" name="fecha" value="{{ $fecha }}"
                               class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <button type="submit" class="bg-purple-600 text-white px-5 py-2 rounded-lg hover:bg-purple-700 transition font-semibold">
                        🔍 Ver día
                    </button>
                </form>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('caja.index', ['fecha' => $fechaAnterior]) }}"
                       class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50 transition">
                        ← Día anterior
                    </a>
                    <a href="{{ route('caja.index', ['fecha' => $fechaHoy]) }}"
                       class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $esHoy ? 'bg-gray-100 text-gray-400 cursor-default pointer-events-none' : 'bg-emerald-600 text-white hover:bg-emerald-700' }}">
                        Hoy
                    </a>
                    <a href="{{ route('caja.index', ['fecha' => $fechaSiguiente]) }}"
                       class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50 transition">
                        Día siguiente →
                    </a>
                </div>
            </div>
        </div>

        <!-- Resumen General -->
        <div class="bg-white rounded-xl shadow-sm mb-5 overflow-hidden">
            <div style="background:#1e1a4b;color:#fff;padding:14px 20px;font-size:15px;font-weight:700">📊 Resumen General</div>
            <div class="p-6">
            
            <!-- Grid de estadísticas principales -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Ingresado -->
                <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-800/30 p-5 border border-emerald-200 dark:border-emerald-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300 mb-1">Total Ingresado</p>
                            <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">€{{ number_format($totalPagado, 2) }}</p>
                        </div>
                        <div class="text-3xl">💰</div>
                    </div>
                </div>
                
                <!-- Efectivo -->
                <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 p-5 border border-green-200 dark:border-green-700">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-medium text-green-700 dark:text-green-300 mb-1">Efectivo Total</p>
                            <p class="text-2xl font-bold text-green-900 dark:text-green-100">€{{ number_format($totalEfectivo + $totalBonosEfectivo, 2) }}</p>
                        </div>
                        <div class="text-3xl">💵</div>
                    </div>
                    <div class="text-xs text-green-700/70 dark:text-green-300/70 space-y-0.5">
                        <div>Servicios: €{{ number_format($totalEfectivo, 2) }}</div>
                        <div>Bonos: €{{ number_format($totalBonosEfectivo, 2) }}</div>
                    </div>
                </div>
                
                <!-- Tarjeta -->
                <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 p-5 border border-blue-200 dark:border-blue-700">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-1">Tarjeta Total</p>
                            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">€{{ number_format($totalTarjeta + $totalBonosTarjeta, 2) }}</p>
                        </div>
                        <div class="text-3xl">💳</div>
                    </div>
                    <div class="text-xs text-blue-700/70 dark:text-blue-300/70 space-y-0.5">
                        <div>Servicios: €{{ number_format($totalTarjeta, 2) }}</div>
                        <div>Bonos: €{{ number_format($totalBonosTarjeta, 2) }}</div>
                    </div>
                </div>
                
                <!-- Deudas -->
                <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 p-5 border border-red-200 dark:border-red-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-red-700 dark:text-red-300 mb-1">Deudas Generadas</p>
                            <p class="text-2xl font-bold text-red-900 dark:text-red-100">€{{ number_format($totalDeuda, 2) }}</p>
                        </div>
                        <div class="text-3xl">⚠️</div>
                    </div>
                </div>
            </div>
            
            <!-- Desglose adicional -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-gray-200">
                <div class="flex items-center gap-3 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-100 dark:border-purple-800">
                    <div class="text-2xl">🎫</div>
                    <div>
                        <p class="text-lg font-bold text-purple-900 dark:text-purple-100">€{{ number_format($totalBono, 2) }}</p>
                        <p class="text-xs text-purple-700 dark:text-purple-300">Servicios con Bono</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-100 dark:border-indigo-800">
                    <div class="text-2xl">🎁</div>
                    <div>
                        <p class="text-lg font-bold text-indigo-900 dark:text-indigo-100">€{{ number_format($totalBonosVendidos, 2) }}</p>
                        <p class="text-xs text-indigo-700 dark:text-indigo-300">Bonos Vendidos</p>
                        @if($totalDeudaBonos > 0)
                            <div class="text-xs space-y-0.5 mt-1">
                                <div class="text-green-600 dark:text-green-400">✓ Cobrado: €{{ number_format($totalBonosVendidosPagados, 2) }}</div>
                                <div class="text-red-600 dark:text-red-400">⚠ A deber: €{{ number_format($totalDeudaBonos, 2) }}</div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-100 dark:border-amber-800">
                    <div class="text-2xl">💼</div>
                    <div>
                        <p class="text-lg font-bold text-amber-900 dark:text-amber-100">€{{ number_format($totalServicios, 2) }}</p>
                        <p class="text-xs text-amber-700 dark:text-amber-300">Total Servicios</p>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <!-- Peluquería y Estética -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            
            <!-- PELUQUERÍA -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div style="background:#1e1a4b;padding:14px 20px;">
                    <h3 style="font-size:15px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px">
                        <span>💇</span> Peluquería
                    </h3>
                </div>
                
                <div class="p-6">
                    <!-- Totales -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-5 border border-blue-100 dark:border-blue-800">
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div class="text-center">
                                <div class="text-xl mb-1">💵</div>
                                <div class="text-lg font-bold text-green-600 dark:text-green-400">€{{ number_format($totalPeluqueriaEfectivo, 2) }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Efectivo</div>
                            </div>
                            <div class="text-center">
                                <div class="text-xl mb-1">💳</div>
                                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">€{{ number_format($totalPeluqueriaTarjeta, 2) }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Tarjeta</div>
                            </div>
                        </div>
                        <div class="text-center pt-3 border-t border-blue-200 dark:border-blue-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">🎫 Bono: €{{ number_format($totalPeluqueriaBono, 2) }}</div>
                            <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">€{{ number_format($totalPeluqueria, 2) }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Total</div>
                        </div>
                    </div>

                    <!-- Servicios -->
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Servicios</h4>
                        @php
                            $serviciosPeluqueria = [];
                            $serviciosPeluqueriaBono = [];
                            
                            foreach($detalleServicios as $cobro) {
                                $yaContados = false;
                                $esBono = $cobro->metodo_pago === 'bono';
                                
                                // Prioridad 1: Servicios adjuntos directamente al cobro (Fuente de verdad)
                                if ($cobro->servicios && $cobro->servicios->count() > 0) {
                                    foreach($cobro->servicios as $servicio) {
                                        if ($servicio->categoria === 'peluqueria') {
                                            $precio = $servicio->pivot->precio ?? $servicio->precio;
                                            $nombre = $servicio->nombre;
                                            
                                            if ($esBono) {
                                                $clave = $nombre . '_bono';
                                                if (!isset($serviciosPeluqueriaBono[$clave])) {
                                                    $serviciosPeluqueriaBono[$clave] = ['nombre' => $nombre, 'cantidad' => 0];
                                                }
                                                $serviciosPeluqueriaBono[$clave]['cantidad']++;
                                            } else {
                                                $clave = $nombre . '_' . $precio;
                                                if (!isset($serviciosPeluqueria[$clave])) {
                                                    $serviciosPeluqueria[$clave] = ['nombre' => $nombre, 'precio_unitario' => $precio, 'cantidad' => 0, 'precio_total' => 0];
                                                }
                                                $serviciosPeluqueria[$clave]['cantidad']++;
                                                $serviciosPeluqueria[$clave]['precio_total'] += $precio;
                                            }
                                        }
                                    }
                                    $yaContados = true;
                                }
                                
                                // Prioridad 2: Cita individual (Fallback para datos antiguos)
                                if (!$yaContados && $cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                                    foreach($cobro->cita->servicios as $servicio) {
                                        if ($servicio->categoria === 'peluqueria') {
                                            $precio = $servicio->pivot->precio ?? $servicio->precio;
                                            $nombre = $servicio->nombre;
                                            
                                            if ($esBono) {
                                                $clave = $nombre . '_bono';
                                                if (!isset($serviciosPeluqueriaBono[$clave])) {
                                                    $serviciosPeluqueriaBono[$clave] = ['nombre' => $nombre, 'cantidad' => 0];
                                                }
                                                $serviciosPeluqueriaBono[$clave]['cantidad']++;
                                            } else {
                                                $clave = $nombre . '_' . $precio;
                                                if (!isset($serviciosPeluqueria[$clave])) {
                                                    $serviciosPeluqueria[$clave] = ['nombre' => $nombre, 'precio_unitario' => $precio, 'cantidad' => 0, 'precio_total' => 0];
                                                }
                                                $serviciosPeluqueria[$clave]['cantidad']++;
                                                $serviciosPeluqueria[$clave]['precio_total'] += $precio;
                                            }
                                        }
                                    }
                                    $yaContados = true;
                                }
                                
                                // Prioridad 3: Citas agrupadas (Fallback para datos antiguos)
                                if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                                    foreach($cobro->citasAgrupadas as $citaGrupo) {
                                        if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                                            foreach($citaGrupo->servicios as $servicio) {
                                                if ($servicio->categoria === 'peluqueria') {
                                                    $precio = $servicio->pivot->precio ?? $servicio->precio;
                                                    $nombre = $servicio->nombre;
                                                    
                                                    if ($esBono) {
                                                        $clave = $nombre . '_bono';
                                                        if (!isset($serviciosPeluqueriaBono[$clave])) {
                                                            $serviciosPeluqueriaBono[$clave] = ['nombre' => $nombre, 'cantidad' => 0];
                                                        }
                                                        $serviciosPeluqueriaBono[$clave]['cantidad']++;
                                                    } else {
                                                        $clave = $nombre . '_' . $precio;
                                                        if (!isset($serviciosPeluqueria[$clave])) {
                                                            $serviciosPeluqueria[$clave] = ['nombre' => $nombre, 'precio_unitario' => $precio, 'cantidad' => 0, 'precio_total' => 0];
                                                        }
                                                        $serviciosPeluqueria[$clave]['cantidad']++;
                                                        $serviciosPeluqueria[$clave]['precio_total'] += $precio;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $yaContados = true;
                                }
                            }
                        @endphp
                        
                        @if(count($serviciosPeluqueria) > 0 || count($serviciosPeluqueriaBono) > 0)
                            <div class="space-y-2">
                                @foreach($serviciosPeluqueria as $datos)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $datos['nombre'] }}</span>
                                        <div class="flex items-center gap-2">
                                            @if($datos['cantidad'] > 1)
                                                <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-full text-xs font-semibold">x{{ $datos['cantidad'] }}</span>
                                            @endif
                                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">€{{ number_format($datos['precio_total'], 2) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                                @foreach($serviciosPeluqueriaBono as $datos)
                                    <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $datos['nombre'] }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-full text-xs font-semibold">🎫 Bono</span>
                                            @if($datos['cantidad'] > 1)
                                                <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-full text-xs font-semibold">x{{ $datos['cantidad'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">Sin servicios registrados</p>
                        @endif
                    </div>

                    <!-- Productos -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Productos</h4>
                        @php
                            $productosPeluqueria = [];
                            foreach($detalleServicios as $cobro) {
                                if ($cobro->productos) {
                                    foreach($cobro->productos as $producto) {
                                        if ($producto->categoria === 'peluqueria') {
                                            $cantidad = $producto->pivot->cantidad ?? 1;
                                            $subtotal = $producto->pivot->subtotal ?? 0;
                                            $nombre = $producto->nombre;
                                            
                                            if (!isset($productosPeluqueria[$nombre])) {
                                                $productosPeluqueria[$nombre] = ['cantidad' => 0, 'precio_total' => 0];
                                            }
                                            $productosPeluqueria[$nombre]['cantidad'] += $cantidad;
                                            $productosPeluqueria[$nombre]['precio_total'] += $subtotal;
                                        }
                                    }
                                }
                            }
                        @endphp
                        
                        @if(count($productosPeluqueria) > 0)
                            <div class="space-y-1.5">
                                @foreach($productosPeluqueria as $nombre => $datos)
                                    <div class="flex justify-between items-center text-sm text-gray-700 dark:text-gray-300">
                                        <span>• {{ $nombre }} <span class="text-blue-600 dark:text-blue-400 font-semibold">(x{{ $datos['cantidad'] }})</span></span>
                                        <span class="font-medium">€{{ number_format($datos['precio_total'], 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 dark:text-gray-500 italic">Sin productos</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- ESTÉTICA -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div style="background:linear-gradient(135deg,#f472b6,#a855f7);padding:14px 20px;">
                    <h3 style="font-size:15px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px">
                        <span>💅</span> Estética
                    </h3>
                </div>
                
                <div class="p-6">
                    <!-- Totales -->
                    <div class="bg-pink-50 dark:bg-pink-900/20 rounded-lg p-4 mb-5 border border-pink-100 dark:border-pink-800">
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div class="text-center">
                                <div class="text-xl mb-1">💵</div>
                                <div class="text-lg font-bold text-green-600 dark:text-green-400">€{{ number_format($totalEsteticaEfectivo, 2) }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Efectivo</div>
                            </div>
                            <div class="text-center">
                                <div class="text-xl mb-1">💳</div>
                                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">€{{ number_format($totalEsteticaTarjeta, 2) }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">Tarjeta</div>
                            </div>
                        </div>
                        <div class="text-center pt-3 border-t border-pink-200 dark:border-pink-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">🎫 Bono: €{{ number_format($totalEsteticaBono, 2) }}</div>
                            <div class="text-2xl font-bold text-pink-700 dark:text-pink-300">€{{ number_format($totalEstetica, 2) }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Total</div>
                        </div>
                    </div>

                    <!-- Servicios -->
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Servicios</h4>
                        @php
                            $serviciosEstetica = [];
                            $serviciosEsteticaBono = [];
                            
                            foreach($detalleServicios as $cobro) {
                                $yaContados = false;
                                $esBono = $cobro->metodo_pago === 'bono';
                                
                                // Prioridad 1: Servicios adjuntos directamente al cobro (Fuente de verdad)
                                if ($cobro->servicios && $cobro->servicios->count() > 0) {
                                    foreach($cobro->servicios as $servicio) {
                                        if ($servicio->categoria === 'estetica') {
                                            $precio = $servicio->pivot->precio ?? $servicio->precio;
                                            $nombre = $servicio->nombre;
                                            
                                            if ($esBono) {
                                                $clave = $nombre . '_bono';
                                                if (!isset($serviciosEsteticaBono[$clave])) {
                                                    $serviciosEsteticaBono[$clave] = ['nombre' => $nombre, 'cantidad' => 0];
                                                }
                                                $serviciosEsteticaBono[$clave]['cantidad']++;
                                            } else {
                                                $clave = $nombre . '_' . $precio;
                                                if (!isset($serviciosEstetica[$clave])) {
                                                    $serviciosEstetica[$clave] = ['nombre' => $nombre, 'precio_unitario' => $precio, 'cantidad' => 0, 'precio_total' => 0];
                                                }
                                                $serviciosEstetica[$clave]['cantidad']++;
                                                $serviciosEstetica[$clave]['precio_total'] += $precio;
                                            }
                                        }
                                    }
                                    $yaContados = true;
                                }
                                
                                // Prioridad 2: Cita individual (Fallback)
                                if (!$yaContados && $cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                                    foreach($cobro->cita->servicios as $servicio) {
                                        if ($servicio->categoria === 'estetica') {
                                            $precio = $servicio->pivot->precio ?? $servicio->precio;
                                            $nombre = $servicio->nombre;
                                            
                                            if ($esBono) {
                                                $clave = $nombre . '_bono';
                                                if (!isset($serviciosEsteticaBono[$clave])) {
                                                    $serviciosEsteticaBono[$clave] = ['nombre' => $nombre, 'cantidad' => 0];
                                                }
                                                $serviciosEsteticaBono[$clave]['cantidad']++;
                                            } else {
                                                $clave = $nombre . '_' . $precio;
                                                if (!isset($serviciosEstetica[$clave])) {
                                                    $serviciosEstetica[$clave] = ['nombre' => $nombre, 'precio_unitario' => $precio, 'cantidad' => 0, 'precio_total' => 0];
                                                }
                                                $serviciosEstetica[$clave]['cantidad']++;
                                                $serviciosEstetica[$clave]['precio_total'] += $precio;
                                            }
                                        }
                                    }
                                    $yaContados = true;
                                }
                                
                                // Prioridad 3: Citas agrupadas (Fallback)
                                if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                                    foreach($cobro->citasAgrupadas as $citaGrupo) {
                                        if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                                            foreach($citaGrupo->servicios as $servicio) {
                                                if ($servicio->categoria === 'estetica') {
                                                    $precio = $servicio->pivot->precio ?? $servicio->precio;
                                                    $nombre = $servicio->nombre;
                                                    
                                                    if ($esBono) {
                                                        $clave = $nombre . '_bono';
                                                        if (!isset($serviciosEsteticaBono[$clave])) {
                                                            $serviciosEsteticaBono[$clave] = ['nombre' => $nombre, 'cantidad' => 0];
                                                        }
                                                        $serviciosEsteticaBono[$clave]['cantidad']++;
                                                    } else {
                                                        $clave = $nombre . '_' . $precio;
                                                        if (!isset($serviciosEstetica[$clave])) {
                                                            $serviciosEstetica[$clave] = ['nombre' => $nombre, 'precio_unitario' => $precio, 'cantidad' => 0, 'precio_total' => 0];
                                                        }
                                                        $serviciosEstetica[$clave]['cantidad']++;
                                                        $serviciosEstetica[$clave]['precio_total'] += $precio;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $yaContados = true;
                                }
                            }
                        @endphp
                        
                        @if(count($serviciosEstetica) > 0 || count($serviciosEsteticaBono) > 0)
                            <div class="space-y-2">
                                @foreach($serviciosEstetica as $datos)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $datos['nombre'] }}</span>
                                        <div class="flex items-center gap-2">
                                            @if($datos['cantidad'] > 1)
                                                <span class="px-2 py-0.5 bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300 rounded-full text-xs font-semibold">x{{ $datos['cantidad'] }}</span>
                                            @endif
                                            <span class="text-sm font-bold text-pink-600 dark:text-pink-400">€{{ number_format($datos['precio_total'], 2) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                                @foreach($serviciosEsteticaBono as $datos)
                                    <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $datos['nombre'] }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-full text-xs font-semibold">🎫 Bono</span>
                                            @if($datos['cantidad'] > 1)
                                                <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-full text-xs font-semibold">x{{ $datos['cantidad'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">Sin servicios registrados</p>
                        @endif
                    </div>

                    <!-- Productos -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Productos</h4>
                        @php
                            $productosEstetica = [];
                            foreach($detalleServicios as $cobro) {
                                if ($cobro->productos) {
                                    foreach($cobro->productos as $producto) {
                                        if ($producto->categoria === 'estetica') {
                                            $cantidad = $producto->pivot->cantidad ?? 1;
                                            $subtotal = $producto->pivot->subtotal ?? 0;
                                            $nombre = $producto->nombre;
                                            
                                            if (!isset($productosEstetica[$nombre])) {
                                                $productosEstetica[$nombre] = ['cantidad' => 0, 'precio_total' => 0];
                                            }
                                            $productosEstetica[$nombre]['cantidad'] += $cantidad;
                                            $productosEstetica[$nombre]['precio_total'] += $subtotal;
                                        }
                                    }
                                }
                            }
                        @endphp
                        
                        @if(count($productosEstetica) > 0)
                            <div class="space-y-1.5">
                                @foreach($productosEstetica as $nombre => $datos)
                                    <div class="flex justify-between items-center text-sm text-gray-700 dark:text-gray-300">
                                        <span>• {{ $nombre }} <span class="text-pink-600 dark:text-pink-400 font-semibold">(x{{ $datos['cantidad'] }})</span></span>
                                        <span class="font-medium">€{{ number_format($datos['precio_total'], 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 dark:text-gray-500 italic">Sin productos</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Servicios Realizados -->
        <div class="bg-white rounded-xl shadow-sm mb-5 overflow-hidden">
            <div style="background:#1e1a4b;color:#fff;padding:14px 20px;font-size:15px;font-weight:700">📋 Servicios Realizados (Desglosado por Empleado)</div>
            <div class="p-6">
            
            @if($detalleServicios->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Hora</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Cliente</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Servicio</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Empleado</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Método</th>
                                <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Precio Servicio</th>
                                <th class="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Monto Pagado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($detalleServicios as $item)
                                @php
                                    // Obtener información común del cobro
                                    $horaCita = null;
                                    if ($item->cita && $item->cita->fecha_hora) {
                                        $horaCita = \Carbon\Carbon::parse($item->cita->fecha_hora)->format('H:i');
                                    } elseif ($item->citasAgrupadas && $item->citasAgrupadas->count() > 0) {
                                        $primeraCita = $item->citasAgrupadas->first();
                                        if ($primeraCita && $primeraCita->fecha_hora) {
                                            $horaCita = \Carbon\Carbon::parse($primeraCita->fecha_hora)->format('H:i');
                                        }
                                    }
                                    
                                    $nombreCliente = '-';
                                    if($item->cliente && $item->cliente->user) {
                                        $nombreCliente = $item->cliente->user->nombre . ' ' . $item->cliente->user->apellidos;
                                    } elseif($item->cita && $item->cita->cliente && $item->cita->cliente->user) {
                                        $nombreCliente = $item->cita->cliente->user->nombre . ' ' . $item->cita->cliente->user->apellidos;
                                    }
                                    
                                    // Usar total_final que ya es el monto cobrado (sin deuda)
                                    $montoPagado = $item->total_final;
                                    
                                    // Variable para controlar si se mostraron filas
                                    $filasMostradas = false;
                                @endphp
                                
                                {{-- PRIORIDAD 1: Servicios directos del cobro con empleado en pivot --}}
                                @if($item->servicios && $item->servicios->count() > 0)
                                    @foreach($item->servicios as $servicio)
                                        @php
                                            // Obtener empleado del pivot
                                            $empleadoId = $servicio->pivot->empleado_id ?? null;
                                            $empleadoNombre = '-';
                                            
                                            if($empleadoId) {
                                                $empleado = \App\Models\Empleado::with('user')->find($empleadoId);
                                                if($empleado && $empleado->user) {
                                                    $empleadoNombre = $empleado->user->nombre;
                                                }
                                            }
                                            
                                            // Obtener precio del servicio desde el pivot
                                            $precioServicio = $servicio->pivot->precio ?? $servicio->precio;
                                            
                                            // Calcular proporción del dinero pagado para este servicio
                                            // Usar suma real de pivots (no coste, que incluye precios pre-bono)
                                            $sumaPivots = $item->servicios->sum(fn($s) => $s->pivot->precio ?? 0)
                                                        + ($item->productos ? $item->productos->sum(fn($p) => $p->pivot->subtotal ?? 0) : 0);
                                            $proporcion = $sumaPivots > 0 ? ($precioServicio / $sumaPivots) : 0;
                                            $montoPagadoServicio = $montoPagado * $proporcion;
                                            
                                            $filasMostradas = true;
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="py-3 px-4 font-medium text-gray-900 dark:text-gray-100">{{ $horaCita ?? '-' }}</td>
                                            <td class="py-3 px-4 text-gray-700 dark:text-gray-300">{{ $nombreCliente }}</td>
                                            <td class="py-3 px-4">
                                                @if($servicio->categoria === 'peluqueria')
                                                    <span class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded text-xs">💇 {{ $servicio->nombre }}</span>
                                                @elseif($servicio->categoria === 'estetica')
                                                    <span class="inline-flex items-center px-2 py-1 bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300 rounded text-xs">💅 {{ $servicio->nombre }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-gray-700 dark:text-gray-300 font-semibold">{{ $empleadoNombre }}</td>
                                            <td class="py-3 px-4">
                                                @if($item->metodo_pago === 'efectivo')
                                                    <span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded-full text-xs font-semibold">💵 Efectivo</span>
                                                @elseif($item->metodo_pago === 'tarjeta')
                                                    <span class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-full text-xs font-semibold">💳 Tarjeta</span>
                                                @elseif($item->metodo_pago === 'bono')
                                                    <span class="inline-flex items-center px-2 py-1 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-full text-xs font-semibold">🎫 Bono</span>
                                                @elseif($item->metodo_pago === 'mixto')
                                                    <span class="inline-flex items-center px-2 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 rounded-full text-xs font-semibold">💳💵 Mixto</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-right text-gray-700 dark:text-gray-300">€{{ number_format($precioServicio, 2) }}</td>
                                            <td class="py-3 px-4 text-right font-bold text-green-600 dark:text-green-400">€{{ number_format($montoPagadoServicio, 2) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                                
                                {{-- Si hay productos, mostrarlos también --}}
                                @if($item->productos && $item->productos->count() > 0)
                                    @foreach($item->productos as $producto)
                                        @php
                                            $subtotalProducto = $producto->pivot->subtotal ?? 0;
                                            $proporcion = $item->coste > 0 ? ($subtotalProducto / $item->coste) : 0;
                                            $montoPagadoProducto = $montoPagado * $proporcion;
                                            $filasMostradas = true;
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="py-3 px-4 font-medium text-gray-900 dark:text-gray-100">{{ $horaCita ?? '-' }}</td>
                                            <td class="py-3 px-4 text-gray-700 dark:text-gray-300">{{ $nombreCliente }}</td>
                                            <td class="py-3 px-4">
                                                <span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded text-xs">🛍️ {{ $producto->nombre }} (x{{ $producto->pivot->cantidad }})</span>
                                            </td>
                                            <td class="py-3 px-4 text-gray-700 dark:text-gray-300">
                                                @if($item->empleado && $item->empleado->user)
                                                    {{ $item->empleado->user->nombre }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="py-3 px-4">
                                                @if($item->metodo_pago === 'efectivo')
                                                    <span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded-full text-xs font-semibold">💵 Efectivo</span>
                                                @elseif($item->metodo_pago === 'tarjeta')
                                                    <span class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-full text-xs font-semibold">💳 Tarjeta</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-right text-gray-700 dark:text-gray-300">€{{ number_format($subtotalProducto, 2) }}</td>
                                            <td class="py-3 px-4 text-right font-bold text-green-600 dark:text-green-400">€{{ number_format($montoPagadoProducto, 2) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                                
                                {{-- FALLBACK: Si no hay servicios directos, mostrar los de la cita (datos antiguos) --}}
                                @if(!$filasMostradas && $item->cita && $item->cita->servicios && $item->cita->servicios->count() > 0)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="py-3 px-4 font-medium text-gray-900 dark:text-gray-100">{{ $horaCita ?? '-' }}</td>
                                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300">{{ $nombreCliente }}</td>
                                        <td class="py-3 px-4">
                                            @foreach($item->cita->servicios as $servicio)
                                                @if($servicio->categoria === 'peluqueria')
                                                    <span class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded text-xs mr-1 mb-1">💇 {{ $servicio->nombre }}</span>
                                                @elseif($servicio->categoria === 'estetica')
                                                    <span class="inline-flex items-center px-2 py-1 bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300 rounded text-xs mr-1 mb-1">💅 {{ $servicio->nombre }}</span>
                                                @endif
                                            @endforeach
                                        </td>
                                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300">
                                            @if($item->cita && $item->cita->empleado && $item->cita->empleado->user)
                                                {{ $item->cita->empleado->user->nombre }}
                                            @elseif($item->empleado && $item->empleado->user)
                                                {{ $item->empleado->user->nombre }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            @if($item->metodo_pago === 'efectivo')
                                                <span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded-full text-xs font-semibold">💵 Efectivo</span>
                                            @elseif($item->metodo_pago === 'tarjeta')
                                                <span class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-full text-xs font-semibold">💳 Tarjeta</span>
                                            @elseif($item->metodo_pago === 'bono')
                                                <span class="inline-flex items-center px-2 py-1 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-full text-xs font-semibold">🎫 Bono</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-right text-gray-700 dark:text-gray-300">€{{ number_format($item->total_final, 2) }}</td>
                                        <td class="py-3 px-4 text-right font-bold text-green-600 dark:text-green-400">€{{ number_format($montoPagado, 2) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-400">No se realizaron servicios este día</p>
                </div>
            @endif
            </div>
        </div>

        <!-- Bonos Vendidos y Deudas -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- BONOS VENDIDOS -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div style="background:linear-gradient(135deg,#a855f7,#7c3aed);padding:14px 20px;">
                    <h3 style="font-size:15px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px">
                        <span>🎫</span> Bonos Vendidos
                    </h3>
                </div>
                
                <div class="p-6">
                    @if($bonosVendidos->count() > 0)
                        <div class="space-y-3">
                            @foreach($bonosVendidos as $bono)
                                @php
                                    $precioOriginalBono = $bono->_pivot_precio ?? $bono->precio_pagado ?? 0;
                                    $precioPagadoBono = $bono->precio_pagado ?? 0;
                                    $deudaBono = max(0, $precioOriginalBono - $precioPagadoBono);
                                @endphp
                                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors border border-purple-200 dark:border-purple-800">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <div class="font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $bono->plantilla->nombre ?? 'Bono' }}</div>
                                            <div class="text-xs text-gray-600 dark:text-gray-400 space-y-0.5">
                                                @if($bono->cliente && $bono->cliente->user)
                                                    <div>👤 {{ $bono->cliente->user->nombre }} {{ $bono->cliente->user->apellidos }}</div>
                                                @endif
                                                @if($bono->empleado && $bono->empleado->user)
                                                    <div>👨‍💼 {{ $bono->empleado->user->nombre }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-right ml-4">
                                            <div class="text-xl font-bold text-purple-700 dark:text-purple-300">€{{ number_format($precioOriginalBono, 2) }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($bono->created_at)->format('H:i') }}</div>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center pt-3 border-t border-purple-200 dark:border-purple-700">
                                        <div class="flex flex-wrap gap-1">
                                            @if($bono->metodo_pago === 'efectivo')
                                                <span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded-full text-xs font-semibold">💵 Efectivo</span>
                                            @elseif($bono->metodo_pago === 'tarjeta')
                                                <span class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-full text-xs font-semibold">💳 Tarjeta</span>
                                            @elseif($bono->metodo_pago === 'mixto')
                                                <span class="inline-flex items-center px-2 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 rounded-full text-xs font-semibold">💳💵 Mixto</span>
                                            @elseif($bono->metodo_pago === 'deuda')
                                                <span class="inline-flex items-center px-2 py-1 bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 rounded-full text-xs font-semibold">⚠️ A deber</span>
                                            @endif
                                            @if($deudaBono > 0 && $bono->metodo_pago !== 'deuda')
                                                <span class="inline-flex items-center px-2 py-1 bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 rounded-full text-xs font-semibold">⚠ Deuda: €{{ number_format($deudaBono, 2) }}</span>
                                            @endif
                                        </div>
                                        @if($bono->plantilla && $bono->plantilla->duracion_dias)
                                            <span class="text-xs text-gray-500 dark:text-gray-400">⏰ {{ $bono->plantilla->duracion_dias }} días</span>
                                        @else
                                            <span class="text-xs text-purple-600 dark:text-purple-400 font-semibold">♾️ Sin límite</span>
                                        @endif
                                    </div>
                                    @if($precioPagadoBono > 0 && $deudaBono > 0)
                                        <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 flex justify-between">
                                            <span class="text-green-600 dark:text-green-400">✓ Pagado: €{{ number_format($precioPagadoBono, 2) }}</span>
                                            <span class="text-red-600 dark:text-red-400">⚠ Deuda: €{{ number_format($deudaBono, 2) }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="text-5xl mb-3">🎫</div>
                            <p class="text-gray-400 dark:text-gray-500">No se vendieron bonos este día</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- DEUDAS GENERADAS -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div style="background:#dc2626;padding:14px 20px;">
                    <h3 style="font-size:15px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px">
                        <span>⚠️</span> Deudas Generadas
                    </h3>
                </div>
                
                <div class="p-6">
                    @if($deudas->count() > 0 || $bonoDeudas->count() > 0)
                        <div class="space-y-3">
                            {{-- Deudas de servicios/productos --}}
                            @foreach($deudas as $deuda)
                                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors border border-red-200 dark:border-red-800">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <div class="font-bold text-gray-900 dark:text-gray-100 mb-2">
                                                @if($deuda->cliente && $deuda->cliente->user)
                                                    {{ $deuda->cliente->user->nombre }} {{ $deuda->cliente->user->apellidos }}
                                                @elseif($deuda->cita && $deuda->cita->cliente && $deuda->cita->cliente->user)
                                                    {{ $deuda->cita->cliente->user->nombre }} {{ $deuda->cita->cliente->user->apellidos }}
                                                @else
                                                    Cliente desconocido
                                                @endif
                                            </div>
                                            <div class="flex flex-wrap gap-1">
                                                @php $serviciosMostrados = false; @endphp
                                                
                                                @if($deuda->servicios && $deuda->servicios->count() > 0)
                                                    @foreach($deuda->servicios as $servicio)
                                                        <span class="inline-flex items-center px-2 py-0.5 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs border border-red-200 dark:border-red-700">{{ $servicio->nombre }}</span>
                                                    @endforeach
                                                    @php $serviciosMostrados = true; @endphp
                                                @endif

                                                @if(!$serviciosMostrados && $deuda->cita && $deuda->cita->servicios && $deuda->cita->servicios->count() > 0)
                                                    @foreach($deuda->cita->servicios as $servicio)
                                                        <span class="inline-flex items-center px-2 py-0.5 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs border border-red-200 dark:border-red-700">{{ $servicio->nombre }}</span>
                                                    @endforeach
                                                    @php $serviciosMostrados = true; @endphp
                                                @endif
                                                
                                                @if(!$serviciosMostrados)
                                                    <span class="text-xs text-gray-400 dark:text-gray-500 italic">Sin servicios</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-right ml-4">
                                            <div class="text-xl font-bold text-red-600 dark:text-red-400">€{{ number_format($deuda->deuda, 2) }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Deuda</div>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center pt-3 border-t border-red-200 dark:border-red-700 text-sm">
                                        <div class="text-gray-600 dark:text-gray-400">
                                            Total: <span class="font-semibold text-gray-900 dark:text-gray-100">€{{ number_format($deuda->total_final + $deuda->deuda, 2) }}</span>
                                        </div>
                                        <div class="font-semibold text-green-600 dark:text-green-400">
                                            Pagado: €{{ number_format($deuda->total_final, 2) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Deudas de bonos vendidos --}}
                            @foreach($bonoDeudas as $bono)
                                @php
                                    $precioOriginal = $bono->_pivot_precio ?? 0;
                                    $pagado = $bono->precio_pagado ?? 0;
                                    $deudaBono = max(0, $precioOriginal - $pagado);
                                @endphp
                                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors border border-red-200 dark:border-red-800">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <div class="font-bold text-gray-900 dark:text-gray-100 mb-2">
                                                @if($bono->cliente && $bono->cliente->user)
                                                    {{ $bono->cliente->user->nombre }} {{ $bono->cliente->user->apellidos }}
                                                @else
                                                    Cliente desconocido
                                                @endif
                                            </div>
                                            <div class="flex flex-wrap gap-1">
                                                <span class="inline-flex items-center px-2 py-0.5 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded text-xs border border-purple-200 dark:border-purple-700">🎫 Bono: {{ $bono->plantilla->nombre ?? 'Bono' }}</span>
                                            </div>
                                        </div>
                                        <div class="text-right ml-4">
                                            <div class="text-xl font-bold text-red-600 dark:text-red-400">€{{ number_format($deudaBono, 2) }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Deuda (Bono)</div>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center pt-3 border-t border-red-200 dark:border-red-700 text-sm">
                                        <div class="text-gray-600 dark:text-gray-400">
                                            Total: <span class="font-semibold text-gray-900 dark:text-gray-100">€{{ number_format($precioOriginal, 2) }}</span>
                                        </div>
                                        <div class="font-semibold text-green-600 dark:text-green-400">
                                            Pagado: €{{ number_format($pagado, 2) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            
                            <!-- Total de deudas -->
                            <div class="bg-red-100 dark:bg-red-900/40 rounded-lg p-4 border-2 border-red-300 dark:border-red-700">
                                <div class="flex justify-between items-center">
                                    <span class="text-red-800 dark:text-red-300 font-bold">Total Deuda del Día</span>
                                    <span class="text-2xl font-bold text-red-700 dark:text-red-400">€{{ number_format($totalDeuda, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="text-5xl mb-3">✅</div>
                            <p class="text-emerald-600 dark:text-emerald-400 font-semibold">No se generaron deudas este día</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        </main>
    </div>
</div>
</body>
</html>
