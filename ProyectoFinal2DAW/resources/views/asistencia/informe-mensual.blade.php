<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe Horas Mensuales - {{ $nombreMes }}</title>
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
        .topbar { background:#fff; padding:10px 24px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 1px 4px rgba(0,0,0,.08); position:sticky; top:0; z-index:50; gap:12px; flex-wrap:wrap; }
        .topbar-title { font-size:1.1rem; font-weight:700; color:#1e1a4b; }
        .topbar-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .topbar-user { display:flex; align-items:center; gap:8px; text-decoration:none; }
        .topbar-user .avatar { width:34px; height:34px; background:linear-gradient(135deg,#f472b6,#a855f7); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:.9rem; }
        .topbar-user .user-info .name { font-size:.82rem; font-weight:600; color:#1e1a4b; }
        .topbar-user .user-info .role { font-size:.7rem; color:#6b7280; }
        .main-content { padding:20px 24px; flex:1; }
        .btn-navy { background:#1e1a4b; color:#fff !important; border:none; padding:7px 14px; border-radius:8px; font-weight:600; font-size:.82rem; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
        .btn-navy:hover { opacity:.9; }
        .btn-primary { background:linear-gradient(135deg,#f472b6,#a855f7); color:#fff !important; border:none; padding:7px 14px; border-radius:8px; font-weight:600; font-size:.82rem; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
        .btn-primary:hover { opacity:.9; }
        .filter-form { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .filter-form label { font-size:.8rem; font-weight:600; color:#374151; }
        .filter-form select { border:1px solid #d1d5db; border-radius:6px; padding:4px 8px; font-size:.82rem; }
        .filter-form button { background:#1e1a4b; color:#fff; border:none; padding:5px 12px; border-radius:6px; font-size:.82rem; font-weight:600; cursor:pointer; }
        @media print {
            .sidebar, .topbar { display: none !important; }
            .content { margin-left: 0 !important; }
            body { background: white !important; font-size: 11px; }
            .page-break { page-break-before: always; }
            .main-content { padding: 0 !important; }
            .shadow-md { box-shadow: none !important; }
            .bg-white { background: white !important; }
            .bg-gray-100 { background: white !important; }
            table { font-size: 10px; }
            h1 { font-size: 20px !important; }
            h2 { font-size: 16px !important; }
            .stat-card { border: 1px solid #ccc !important; background: white !important; }
        }
        @media print {
            @page { margin: 1cm; size: A4 landscape; }
        }
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
            <span class="topbar-title">📊 Informe Horas Mensuales</span>
            <div class="topbar-actions">
                <form method="GET" class="filter-form">
                    <label>Mes:</label>
                    <select name="mes">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $mes == $m ? 'selected' : '' }}>
                                {{ ucfirst(\Carbon\Carbon::create(null, $m, 1)->locale('es')->isoFormat('MMMM')) }}
                            </option>
                        @endfor
                    </select>
                    <label>Año:</label>
                    <select name="anio">
                        @for ($a = now()->year; $a >= now()->year - 3; $a--)
                            <option value="{{ $a }}" {{ $anio == $a ? 'selected' : '' }}>{{ $a }}</option>
                        @endfor
                    </select>
                    <button type="submit">Consultar</button>
                </form>
                <button onclick="window.print()" class="btn-primary">🖨️ Imprimir</button>
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
        <!-- Cabecera del informe -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="text-center mb-4">
                <h1 class="text-3xl font-bold text-gray-800">📊 Informe de Horas Mensuales</h1>
                <p class="text-xl text-gray-600 mt-1">{{ $nombreMes }}</p>
                <p class="text-sm text-gray-400 mt-1">
                    Periodo: {{ $fechaInicio->format('d/m/Y') }} - {{ $fechaFin->format('d/m/Y') }}
                </p>
            </div>

            <!-- Resumen general -->
            @php
                $totalGeneralMinutos = collect($datosEmpleados)->sum('total_minutos');
                $totalGeneralDias = collect($datosEmpleados)->sum('dias_trabajados');
                $empleadosActivos = collect($datosEmpleados)->filter(fn($e) => $e['dias_trabajados'] > 0)->count();
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="stat-card bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200 text-center">
                    <p class="text-sm text-blue-800 mb-1">Empleados con Registros</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $empleadosActivos }}</p>
                </div>
                <div class="stat-card bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200 text-center">
                    <p class="text-sm text-green-800 mb-1">Total Horas del Mes</p>
                    <p class="text-3xl font-bold text-green-600">{{ sprintf('%dh %02dmin', floor($totalGeneralMinutos / 60), $totalGeneralMinutos % 60) }}</p>
                </div>
                <div class="stat-card bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200 text-center">
                    <p class="text-sm text-purple-800 mb-1">Total Jornadas</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $totalGeneralDias }}</p>
                </div>
            </div>
        </div>

        <!-- Tabla resumen por empleado -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Resumen por Empleado</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-left">
                            <th class="px-4 py-3 border-b font-semibold">Empleado</th>
                            <th class="px-4 py-3 border-b font-semibold text-center">Días Trabajados</th>
                            <th class="px-4 py-3 border-b font-semibold text-center">Horas Totales</th>
                            <th class="px-4 py-3 border-b font-semibold text-center">Horas Extra</th>
                            <th class="px-4 py-3 border-b font-semibold text-center">Promedio Diario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($datosEmpleados as $dato)
                            @if($dato['dias_trabajados'] > 0)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">
                                    {{ $dato['empleado']->user->nombre ?? '' }} {{ $dato['empleado']->user->apellidos ?? '' }}
                                    <span class="text-xs text-gray-500 ml-1">({{ ucfirst($dato['empleado']->categoria ?? 'N/A') }})</span>
                                </td>
                                <td class="px-4 py-3 text-center">{{ $dato['dias_trabajados'] }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-blue-700">{{ $dato['total_formatted'] }}</td>
                                @php
                                    $totalExtraMin = $dato['total_minutos_extra'] ?? 0;
                                    $extraColor = $totalExtraMin > 0 ? 'text-orange-600 font-semibold' : 'text-gray-400';
                                @endphp
                                <td class="px-4 py-3 text-center {{ $extraColor }}">{{ $dato['extra_formatted'] }}</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ $dato['promedio_diario'] }}</td>
                            </tr>
                            @endif
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay registros para este periodo</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 font-bold">
                            <td class="px-4 py-3">TOTAL</td>
                            <td class="px-4 py-3 text-center">{{ $totalGeneralDias }}</td>
                            <td class="px-4 py-3 text-center text-blue-700">{{ sprintf('%dh %02dmin', floor($totalGeneralMinutos / 60), $totalGeneralMinutos % 60) }}</td>
                            <td class="px-4 py-3 text-center" colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Detalle por empleado -->
        @foreach($datosEmpleados as $index => $dato)
            @if($dato['dias_trabajados'] > 0)
            <div class="bg-white rounded-lg shadow-md p-6 mb-6 {{ $index > 0 ? 'page-break' : '' }}">
                <h2 class="text-lg font-bold text-gray-800 mb-1">
                    {{ $dato['empleado']->user->nombre ?? '' }} {{ $dato['empleado']->user->apellidos ?? '' }}
                </h2>
                <p class="text-sm text-gray-500 mb-4">
                    {{ ucfirst($dato['empleado']->categoria ?? 'N/A') }} · 
                    {{ $dato['dias_trabajados'] }} días · 
                    <span class="font-semibold text-blue-700">{{ $dato['total_formatted'] }} totales</span>
                </p>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-left">
                                <th class="px-3 py-2 border-b">Fecha</th>
                                <th class="px-3 py-2 border-b">Día</th>
                                <th class="px-3 py-2 border-b text-center">Entrada</th>
                                <th class="px-3 py-2 border-b text-center">Salida</th>
                                <th class="px-3 py-2 border-b text-center">Horas</th>
                                <th class="px-3 py-2 border-b text-center">Horas Extra</th>
                                <th class="px-3 py-2 border-b text-center">Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dato['detalle_dias'] as $dia)
                                @php
                                    $fechaDia = \Carbon\Carbon::parse($dia['fecha'])->locale('es');
                                    $nombreDia = ucfirst($fechaDia->isoFormat('dddd'));
                                    $esSabado = $fechaDia->isSaturday();
                                @endphp
                                <tr class="border-b {{ $esSabado ? 'bg-yellow-50' : '' }} {{ $dia['fuera_horario'] ? 'bg-red-50' : '' }}">
                                    <td class="px-3 py-2">{{ $fechaDia->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2">{{ $nombreDia }}</td>
                                    <td class="px-3 py-2 text-center">{{ $dia['entrada'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $dia['salida'] }}</td>
                                    <td class="px-3 py-2 text-center font-medium">{{ $dia['horas'] }}</td>
                                    @php
                                        $me = $dia['minutos_extra'];
                                        $extraColorDia = $me > 0 ? 'text-orange-600 font-semibold' : 'text-gray-400';
                                        $extraTxt = $me > 0
                                            ? sprintf('+%dh %02dmin', intdiv($me, 60), $me % 60)
                                            : '—';
                                    @endphp
                                    <td class="px-3 py-2 text-center {{ $extraColorDia }}">
                                        {{ $extraTxt }}
                                    </td>
                                    <td class="px-3 py-2 text-center text-xs">
                                        @if($dia['fuera_horario'])
                                            <span class="text-red-600">⚠️ Fuera de horario</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-100 font-bold">
                                <td class="px-3 py-2" colspan="4">Total</td>
                                <td class="px-3 py-2 text-center text-blue-700">{{ $dato['total_formatted'] }}</td>
                                @php
                                    $totalExtraMinFoot = $dato['total_minutos_extra'] ?? 0;
                                    $extraColorFoot = $totalExtraMinFoot > 0 ? 'text-orange-600' : 'text-gray-400';
                                @endphp
                                <td class="px-3 py-2 text-center {{ $extraColorFoot }}">{{ $dato['extra_formatted'] }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif
        @endforeach

        <!-- Pie de informe -->
        <div class="text-center text-xs text-gray-400 mt-4 mb-8">
            Informe generado el {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY, HH:mm') }}
        </div>
        </main>
    </div>
</div>
</body>
</html>
