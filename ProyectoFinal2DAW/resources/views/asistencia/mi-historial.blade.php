<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Historial de Asistencia</title>
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
            <a href="{{ route('asistencia.index') }}" class="nav-item active"><span class="nav-icon">🕐</span> Asistencia</a>
            <a href="{{ route('users.index') }}" class="nav-item"><span class="nav-icon">⚙️</span> Usuarios</a>
        </nav>
        <div class="sidebar-footer"><p>© 2026 Salón de Belleza</p></div>
    </aside>
    <div class="content">
        <header class="topbar">
            <span class="topbar-title">📊 Mi Historial de Asistencia</span>
            <div class="topbar-actions">
                <a href="{{ route('asistencia.index') }}" class="btn-navy">← Volver</a>
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
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">

            <!-- Filtros -->
            <form method="GET" action="{{ route('asistencia.mi-historial') }}" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="fecha_inicio" class="block text-sm font-semibold mb-1">Fecha Inicio:</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" value="{{ $fechaInicio }}" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label for="fecha_fin" class="block text-sm font-semibold mb-1">Fecha Fin:</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" value="{{ $fechaFin }}" class="w-full border rounded px-3 py-2">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-semibold">
                            Filtrar
                        </button>
                    </div>
                </div>
            </form>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                    <p class="text-sm text-blue-800 mb-1">Total de Horas</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $estadisticas['total_horas'] }}</p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                    <p class="text-sm text-green-800 mb-1">Días Trabajados</p>
                    <p class="text-3xl font-bold text-green-600">{{ $estadisticas['dias_trabajados'] }}</p>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
                    <p class="text-sm text-purple-800 mb-1">Promedio Diario</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $estadisticas['promedio_diario'] }}</p>
                </div>
            </div>

            <!-- Tabla de registros -->
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-300 rounded-lg overflow-hidden">
                    <thead class="bg-gray-200 text-gray-700">
                        <tr>
                            <th class="p-3 text-left">Fecha</th>
                            <th class="p-3 text-left">Día</th>
                            <th class="p-3 text-center">Entrada</th>
                            <th class="p-3 text-center">Salida</th>
                            <th class="p-3 text-center">Horas Trabajadas</th>
                            <th class="p-3 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($registros as $registro)
                            @php
                                $horas = $registro->calcularHorasTrabajadas();
                                $estaEnJornada = $registro->estaEnJornada();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="p-3">{{ $registro->fecha->format('d/m/Y') }}</td>
                                <td class="p-3">{{ $registro->fecha->locale('es')->isoFormat('dddd') }}</td>
                                <td class="p-3 text-center">
                                    @if($registro->hora_entrada)
                                        <span class="font-semibold text-green-600">{{ \Carbon\Carbon::parse($registro->hora_entrada)->format('H:i') }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    @if($registro->hora_salida)
                                        <span class="font-semibold text-red-600">{{ \Carbon\Carbon::parse($registro->hora_salida)->format('H:i') }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    @if($horas)
                                        <span class="font-bold text-blue-600">{{ $horas['formatted'] }}</span>
                                    @elseif($estaEnJornada)
                                        <span class="text-orange-600 font-semibold">En curso...</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    @if($estaEnJornada)
                                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">🟢 Trabajando</span>
                                    @elseif($registro->hora_salida)
                                        <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-semibold">✅ Completo</span>
                                    @else
                                        <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold">⚠️ Incompleto</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-gray-500">
                                    No hay registros en el rango de fechas seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        </main>
    </div>
</div>
</body>
</html>
