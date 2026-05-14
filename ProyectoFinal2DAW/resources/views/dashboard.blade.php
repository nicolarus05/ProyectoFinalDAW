<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Salón de Belleza</title>
    {!! vite_asset(['resources/css/dashboard.css', 'resources/css/app.css', 'resources/js/app.js']) !!}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root { --sidebar-w: 210px; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f3f4f8; }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed; top: 0; left: 0; width: var(--sidebar-w);
            height: 100vh; background: #1e1a4b;
            display: flex; flex-direction: column; z-index: 50;
            overflow-y: auto;
            transition: transform .3s ease;
        }
        body.sidebar-collapsed .sidebar { transform: translateX(calc(-1 * var(--sidebar-w))); }
        body.sidebar-collapsed .main-wrapper { margin-left: 0; }
        .sidebar-logo { padding: 14px 14px 10px; border-bottom: 1px solid rgba(255,255,255,.1); }
        .sidebar-logo .logo-icon {
            width: 36px; height: 36px; background: linear-gradient(135deg,#f472b6,#a855f7);
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .sidebar-logo .logo-text { color: #fff; font-size: 12.5px; font-weight: 700; line-height: 1.2; }
        .sidebar-logo .logo-sub  { color: rgba(255,255,255,.55); font-size: 10px; }

        .sidebar-nav { flex: 1; padding: 8px 8px; }
        .nav-item {
            display: flex; align-items: center; gap: 8px;
            padding: 7px 10px; border-radius: 8px; margin-bottom: 1px;
            color: rgba(255,255,255,.7); font-size: 12.5px; font-weight: 500;
            text-decoration: none; transition: all .2s;
        }
        .nav-item:hover { background: rgba(255,255,255,.1); color: #fff; }
        .nav-item.active {
            background: linear-gradient(135deg,#f472b6,#a855f7);
            color: #fff; font-weight: 600;
        }
        .nav-item .nav-icon { width: 16px; text-align: center; flex-shrink: 0; font-size: 13px; }

        .sidebar-help {
            margin: 0 8px 8px;
            background: linear-gradient(135deg,#f97316,#ec4899);
            border-radius: 10px; padding: 10px 10px;
            color: #fff; font-size: 11px;
        }
        .sidebar-footer { padding: 6px 14px 12px; color: rgba(255,255,255,.35); font-size: 9.5px; }

        /* ── MAIN WRAPPER ── */
        .main-wrapper { margin-left: var(--sidebar-w); min-height: 100vh; display: flex; flex-direction: column; transition: margin-left .3s ease; }

        /* ── TOPBAR ── */
        .topbar {
            background: #fff; padding: 8px 20px;
            display: flex; align-items: center; gap: 12px;
            border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 40;
        }
        .topbar .menu-btn {
            width: 32px; height: 32px; border-radius: 8px;
            background: linear-gradient(135deg,#f472b6,#a855f7);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 14px; cursor: pointer; flex-shrink: 0;
        }
        .topbar .page-title { font-size: 16px; font-weight: 700; color: #1f2937; white-space: nowrap; }
        .topbar .search-bar {
            background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 20px;
            padding: 6px 14px; font-size: 12px; color: #6b7280;
            display: flex; align-items: center; gap: 6px; min-width: 180px; flex: 1; max-width: 280px;
        }

        .topbar .user-area { display: flex; align-items: center; gap: 8px; cursor: pointer; flex-shrink: 0; }
        .topbar .user-area img, .topbar .user-area .user-avatar {
            width: 32px; height: 32px; border-radius: 50%; object-fit: cover;
        }
        .topbar .user-avatar {
            background: linear-gradient(135deg,#f472b6,#a855f7);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 13px;
        }

        /* ── CONTENT ── */
        .content { flex: 1; padding: 14px 18px; }

        /* ── STAT CARDS ── */
        .stat-card {
            background: #fff; border-radius: 12px; padding: 12px 14px 10px;
            display: flex; flex-direction: column; gap: 4px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .stat-icon {
            width: 34px; height: 34px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
        }

        /* ── QUICK ACCESS CARDS ── */
        .qa-card {
            background: #fff; border-radius: 10px; padding: 10px 12px;
            display: flex; align-items: center; gap: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
            text-decoration: none; transition: box-shadow .2s, transform .15s;
        }
        .qa-card:hover { box-shadow: 0 3px 10px rgba(0,0,0,.1); transform: translateY(-1px); }
        .qa-icon {
            width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 17px;
        }
        .qa-arrow {
            margin-left: auto; width: 22px; height: 22px; border-radius: 50%;
            background: #f3f4f6; display: flex; align-items: center; justify-content: center;
            font-size: 12px; color: #6b7280; flex-shrink: 0;
        }

        /* ── WELCOME BANNER ── */
        .welcome-banner {
            background: linear-gradient(135deg,#fce4ec,#fce4f0,#f9d5f7);
            border-radius: 14px; padding: 14px 20px; overflow: hidden; position: relative;
        }

        /* ── CHART CONTAINERS ── */
        .chart-card { background: #fff; border-radius: 12px; padding: 14px; box-shadow: 0 1px 4px rgba(0,0,0,.06); }

        /* ── CITA ITEM ── */
        .cita-item {
            display: flex; align-items: center; gap: 8px; padding: 7px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .cita-item:last-child { border-bottom: none; }
        .cita-time {
            font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 20px;
            min-width: 64px; text-align: center; flex-shrink: 0;
        }
        .badge-confirmada { background: #dcfce7; color: #15803d; }
        .badge-pendiente  { background: #fef9c3; color: #a16207; }
        .badge-cancelada  { background: #fee2e2; color: #dc2626; }

        @media(max-width:768px){
            .sidebar { transform: translateX(-100%); }
            .main-wrapper { margin-left: 0; }
        }
    </style>
</head>
<body class="min-h-screen">
@php
    $user = Auth::user();
    $rol  = $user->rol ?? null;
    $agent = new \Jenssegers\Agent\Agent();

    // ── Stats ──
    $hoy = \Carbon\Carbon::today();
    $ayer = \Carbon\Carbon::yesterday();

    $citasHoy  = \App\Models\Cita::whereDate('fecha_hora', $hoy)->count();
    $citasAyer = \App\Models\Cita::whereDate('fecha_hora', $ayer)->count();
    $citasTrend = $citasAyer > 0 ? round((($citasHoy - $citasAyer) / $citasAyer) * 100) : 0;

    $ingresosHoy  = \App\Models\RegistroCobro::whereDate('created_at', $hoy)->sum('total_final');
    $ingresosAyer = \App\Models\RegistroCobro::whereDate('created_at', $ayer)->sum('total_final');
    $ingresosTrend = $ingresosAyer > 0 ? round((($ingresosHoy - $ingresosAyer) / $ingresosAyer) * 100) : 0;

    $clientesNuevosHoy  = \App\Models\Cliente::whereDate('created_at', $hoy)->count();
    $clientesNuevosAyer = \App\Models\Cliente::whereDate('created_at', $ayer)->count();
    $clientesTrend = $clientesNuevosAyer > 0 ? round((($clientesNuevosHoy - $clientesNuevosAyer) / $clientesNuevosAyer) * 100) : 0;

    $deudasPendientes = \App\Models\Deuda::where('saldo_pendiente', '>', 0)->sum('saldo_pendiente');
    $serviciosHoy = \App\Models\Cita::whereDate('fecha_hora', $hoy)->withCount('servicios')->get()->sum('servicios_count');

    // ── Próximas citas (hoy, a partir de ahora) ──
    $proximasCitas = \App\Models\Cita::with(['cliente.user', 'servicios'])
        ->whereDate('fecha_hora', $hoy)
        ->where('fecha_hora', '>=', now())
        ->whereNotIn('estado', ['cancelada'])
        ->orderBy('fecha_hora')
        ->limit(5)
        ->get();

    // ── Ingresos últimos 7 días para gráfica ──
    $ingresosChart = [];
    $labelsChart = [];
    for ($i = 6; $i >= 0; $i--) {
        $dia = \Carbon\Carbon::today()->subDays($i);
        $labelsChart[] = $dia->format('d M');
        $ingresosChart[] = round(\App\Models\RegistroCobro::whereDate('created_at', $dia)->sum('total_final'), 2);
    }

    // ── Distribución servicios ──
    $distrib = [
        'Peluquería' => \App\Models\Cita::whereHas('servicios', fn($q) => $q->where('categoria','peluqueria'))->whereDate('fecha_hora', $hoy)->count(),
        'Estética'   => \App\Models\Cita::whereHas('servicios', fn($q) => $q->where('categoria','estetica'))->whereDate('fecha_hora', $hoy)->count(),
    ];
    $totalDistrib = max(1, array_sum($distrib));

    // ── Objetivo mensual ──
    $mesActual    = \Carbon\Carbon::now()->month;
    $añoActual    = \Carbon\Carbon::now()->year;
    $ingresosMes  = \App\Models\RegistroCobro::whereMonth('created_at', $mesActual)->whereYear('created_at', $añoActual)->sum('total_final');
    $objetivoMes  = 15000;
    $progreso     = min(100, round(($ingresosMes / $objetivoMes) * 100));

    $nombreBienvenida = $user->nombre ?? 'Usuario';
    $genero = $user->genero ?? 'hombre';
    $saludoGenero = $genero === 'mujer' ? '¡Bienvenida' : '¡Bienvenido';
@endphp
<!-- ═══════════════════════ SIDEBAR ═══════════════════════ -->
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
        <a href="{{ route('dashboard') }}" class="nav-item active">
            <span class="nav-icon">🏠</span> Inicio
        </a>
        <a href="{{ route('citas.index') }}" class="nav-item">
            <span class="nav-icon">📅</span> Citas
        </a>
        <a href="{{ route('clientes.index') }}" class="nav-item">
            <span class="nav-icon">👤</span> Clientes
        </a>
        @if(in_array($rol, ['admin','gerente']))
        <a href="{{ route('empleados.index') }}" class="nav-item">
            <span class="nav-icon">👔</span> Empleados
        </a>
        <a href="{{ route('servicios.index') }}" class="nav-item">
            <span class="nav-icon">✂️</span> Servicios
        </a>
        <a href="{{ route('subcategorias.index') }}" class="nav-item {{ request()->routeIs('subcategorias.*') ? 'active' : '' }}">
            <span class="nav-icon">🏷️</span> Subcategorías
        </a>
        <a href="{{ route('productos.index') }}" class="nav-item">
            <span class="nav-icon">🛍️</span> Productos
        </a>
        @endif
        <a href="{{ route('cobros.index') }}" class="nav-item">
            <span class="nav-icon">💳</span> Cobros
        </a>
        <a href="{{ route('deudas.index') }}" class="nav-item">
            <span class="nav-icon">💰</span> Deudas
        </a>
        <a href="{{ route('bonos.index') }}" class="nav-item">
            <span class="nav-icon">🎫</span> Bonos
        </a>
        <a href="{{ route('bonos.clientesConBonos') }}" class="nav-item">
            <span class="nav-icon">👥</span> Clientes con Bonos
        </a>
        <a href="{{ route('caja.index') }}" class="nav-item">
            <span class="nav-icon">💵</span> Caja del Día
        </a>
        @if(in_array($rol, ['admin','gerente']))
        <a href="{{ route('facturacion.index') }}" class="nav-item">
            <span class="nav-icon">📊</span> Facturación
        </a>
        <a href="{{ route('horarios.index') }}" class="nav-item">
            <span class="nav-icon">⏰</span> Horarios
        </a>
        <a href="{{ route('asistencia.index') }}" class="nav-item">
            <span class="nav-icon">🕐</span> Asistencia
        </a>
        @endif
        @if($rol === 'admin')
        <a href="{{ route('users.index') }}" class="nav-item">
            <span class="nav-icon">⚙️</span> Usuarios del Sistema
        </a>
        @endif
    </nav>

    <div class="sidebar-help">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
            <span style="font-size:22px">❓</span>
            <span style="font-weight:700;font-size:13px">¿Necesitas ayuda?</span>
        </div>
        <p style="opacity:.85;font-size:11.5px;line-height:1.4">Consulta nuestra guía o contacta soporte</p>
    </div>

    <div class="sidebar-footer">
        © {{ date('Y') }} Salón de Belleza<br>Todos los derechos reservados.
    </div>
</aside>

<!-- ═══════════════════════ MAIN ═══════════════════════ -->
<div class="main-wrapper">

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="menu-btn" id="sidebarToggle" onclick="document.body.classList.toggle('sidebar-collapsed')">☰</div>
        <span class="page-title">Panel de Control ✨</span>
        <div class="search-bar">
            <span style="color:#9ca3af">🔍</span>
            <span>Buscar en el sistema...</span>
        </div>
        <div style="flex:1"></div>
        <a href="{{ route('profile.edit') }}" class="user-area" style="text-decoration:none;color:inherit">
            @if ($user && $user->foto_perfil)
                <img src="{{ route('tenant.file', $user->foto_perfil) }}" loading="lazy" style="width:38px;height:38px;border-radius:50%;object-fit:cover">
            @else
                <div class="user-avatar">{{ strtoupper(substr($user->nombre ?? 'U', 0, 1)) }}</div>
            @endif
            <div class="hidden md:block" style="line-height:1.2">
                <div style="font-weight:600;font-size:13px;color:#1f2937">{{ $user->nombre ?? '' }} {{ $user->apellidos ?? '' }}</div>
                <div style="font-size:11px;color:#6b7280;text-transform:capitalize">{{ ucfirst($user->rol ?? '') }}</div>
            </div>
            <span style="color:#9ca3af;font-size:12px">▼</span>
        </a>
    </header>

    <!-- CONTENT -->
    <div class="content">

        <!-- ── Banner bienvenida + fecha/hora ── -->
        <div style="display:grid;grid-template-columns:1fr 220px;gap:10px;margin-bottom:12px">
            <div class="welcome-banner">
                <h2 style="font-size:17px;font-weight:800;color:#9d174d;margin-bottom:3px">
                    {{ $saludoGenero }}, {{ $nombreBienvenida }}! 👋
                </h2>
                <p style="font-size:12px;color:#be185d">Aquí tienes un resumen general de tu negocio.</p>
            </div>
            <div style="background:linear-gradient(135deg,#7c3aed,#4f46e5);border-radius:14px;padding:12px 14px;color:#fff;display:flex;flex-direction:column;justify-content:center;gap:4px">
                <div style="display:flex;align-items:center;gap:6px;opacity:.8;font-size:11px">
                    <span>📅</span>
                    {{ \Carbon\Carbon::now()->locale('es')->isoFormat('ddd, D [de] MMM [de] YYYY') }}
                </div>
                <div style="font-size:22px;font-weight:800;display:flex;align-items:center;gap:6px">
                    <span>⏰</span>
                    <span id="reloj">{{ \Carbon\Carbon::now()->format('h:i A') }}</span>
                </div>
            </div>
        </div>

        @if(in_array($rol, ['admin','gerente']))
        <!-- ── STATS CARDS ── -->
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:12px">

            {{-- Citas Hoy --}}
            <div class="stat-card">
                <div style="display:flex;align-items:center;gap:8px">
                    <div class="stat-icon" style="background:#ede9fe;color:#7c3aed">📅</div>
                    <span style="font-size:11px;color:#6b7280;font-weight:500">Citas Hoy</span>
                </div>
                <div style="font-size:20px;font-weight:800;color:#111827">{{ $citasHoy }}</div>
                <div style="font-size:10px;color:{{ $citasTrend >= 0 ? '#15803d' : '#dc2626' }}">
                    {{ $citasTrend >= 0 ? '↑' : '↓' }}{{ abs($citasTrend) }}% vs ayer
                </div>
            </div>

            {{-- Ingresos del Día --}}
            <div class="stat-card">
                <div style="display:flex;align-items:center;gap:8px">
                    <div class="stat-icon" style="background:#dcfce7;color:#15803d">💶</div>
                    <span style="font-size:11px;color:#6b7280;font-weight:500">Ingresos del Día</span>
                </div>
                <div style="font-size:16px;font-weight:800;color:#111827">€{{ number_format($ingresosHoy, 2) }}</div>
                <div style="font-size:10px;color:{{ $ingresosTrend >= 0 ? '#15803d' : '#dc2626' }}">
                    {{ $ingresosTrend >= 0 ? '↑' : '↓' }}{{ abs($ingresosTrend) }}% vs ayer
                </div>
            </div>

            {{-- Clientes Nuevos --}}
            <div class="stat-card">
                <div style="display:flex;align-items:center;gap:8px">
                    <div class="stat-icon" style="background:#ffedd5;color:#ea580c">👤</div>
                    <span style="font-size:11px;color:#6b7280;font-weight:500">Clientes Nuevos</span>
                </div>
                <div style="font-size:20px;font-weight:800;color:#111827">{{ $clientesNuevosHoy }}</div>
                <div style="font-size:10px;color:{{ $clientesTrend >= 0 ? '#15803d' : '#dc2626' }}">
                    {{ $clientesTrend >= 0 ? '↑' : '↓' }}{{ abs($clientesTrend) }}% vs ayer
                </div>
            </div>

            {{-- Deudas Pendientes --}}
            <div class="stat-card">
                <div style="display:flex;align-items:center;gap:8px">
                    <div class="stat-icon" style="background:#fce7f3;color:#db2777">⚠️</div>
                    <span style="font-size:11px;color:#6b7280;font-weight:500">Deudas Pendientes</span>
                </div>
                <div style="font-size:16px;font-weight:800;color:#111827">€{{ number_format($deudasPendientes, 2) }}</div>
                <div style="font-size:10px;color:#dc2626">↓ pendientes de cobro</div>
            </div>

            {{-- Servicios Hoy --}}
            <div class="stat-card">
                <div style="display:flex;align-items:center;gap:8px">
                    <div class="stat-icon" style="background:#dbeafe;color:#1d4ed8">✂️</div>
                    <span style="font-size:11px;color:#6b7280;font-weight:500">Servicios Hoy</span>
                </div>
                <div style="font-size:20px;font-weight:800;color:#111827">{{ $serviciosHoy }}</div>
                <div style="font-size:10px;color:#15803d">servicios programados</div>
            </div>

        </div>

        <!-- ── CHARTS + PRÓXIMAS CITAS ── -->
        <div style="display:grid;grid-template-columns:1fr 1fr 240px;gap:10px;margin-bottom:12px">

            {{-- Resumen de Ingresos --}}
            <div class="chart-card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                    <div>
                        <div style="font-size:12px;font-weight:700;color:#111827">Resumen de Ingresos</div>
                        <div style="font-size:18px;font-weight:800;color:#7c3aed">€{{ number_format(array_sum($ingresosChart), 2) }}</div>
                        <div style="font-size:10px;color:#9ca3af">Total 7 días</div>
                    </div>
                    <span style="font-size:10px;background:#f3f4f6;padding:3px 8px;border-radius:20px;color:#374151">Últimos 7 días</span>
                </div>
                <canvas id="ingresosChart" height="100"></canvas>
            </div>

            {{-- Próximas Citas --}}
            <div class="chart-card">
                <div style="font-size:12px;font-weight:700;color:#111827;margin-bottom:8px">Próximas Citas</div>
                @forelse($proximasCitas as $cita)
                @php
                    $colors = ['#ede9fe','#dbeafe','#fef9c3','#dcfce7','#fce7f3'];
                    $textColors = ['#7c3aed','#1d4ed8','#a16207','#15803d','#db2777'];
                    $ci = $loop->index % 5;
                    $estadoBadge = match($cita->estado) {
                        'confirmada' => 'badge-confirmada',
                        'cancelada'  => 'badge-cancelada',
                        default      => 'badge-pendiente',
                    };
                    $primerServicio = $cita->servicios->first()?->nombre ?? 'Servicio';
                @endphp
                <div class="cita-item">
                    <div class="cita-time" style="background:{{ $colors[$ci] }};color:{{ $textColors[$ci] }}">
                        {{ \Carbon\Carbon::parse($cita->fecha_hora)->format('H:i') }}
                    </div>
                    <div class="user-avatar" style="width:28px;height:28px;font-size:11px;flex-shrink:0;background:linear-gradient(135deg,#f472b6,#a855f7);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700">
                        {{ strtoupper(substr($cita->cliente?->user?->nombre ?? '?', 0, 1)) }}
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $primerServicio }}
                        </div>
                        <div style="font-size:10px;color:#6b7280">{{ $cita->cliente?->user?->nombre }} {{ $cita->cliente?->user?->apellidos }}</div>
                    </div>
                    <span class="cita-time {{ $estadoBadge }}" style="min-width:auto;padding:2px 6px">
                        {{ ucfirst($cita->estado) }}
                    </span>
                </div>
                @empty
                <p style="font-size:13px;color:#9ca3af;text-align:center;padding:20px 0">No hay más citas hoy</p>
                @endforelse
            </div>

            {{-- Distribución de Servicios --}}
            <div class="chart-card">
                <div style="font-size:12px;font-weight:700;color:#111827;margin-bottom:6px">Distribución de Servicios</div>
                <canvas id="distribChart" height="130"></canvas>
                <div style="margin-top:6px">
                    <div style="display:flex;align-items:center;gap:5px;font-size:10px;margin-bottom:2px">
                        <span style="width:8px;height:8px;background:#7c3aed;border-radius:50%;flex-shrink:0"></span>
                        Peluquería {{ $totalDistrib > 0 ? round(($distrib['Peluquería']/$totalDistrib)*100) : 0 }}%
                    </div>
                    <div style="display:flex;align-items:center;gap:5px;font-size:10px">
                        <span style="width:8px;height:8px;background:#f59e0b;border-radius:50%;flex-shrink:0"></span>
                        Estética {{ $totalDistrib > 0 ? round(($distrib['Estética']/$totalDistrib)*100) : 0 }}%
                    </div>
                </div>
                <div style="margin-top:6px;text-align:center">
                    <a href="{{ route('caja.index') }}" style="font-size:11px;color:#7c3aed;font-weight:600;text-decoration:none">📊 Ver reporte completo</a>
                </div>
            </div>

        </div>
        @endif

        <!-- ── ACCESOS RÁPIDOS ── -->
        <div style="margin-bottom:14px">
            <h3 style="font-size:13px;font-weight:700;color:#111827;margin-bottom:8px">Accesos Rápidos</h3>

            @if($rol === 'admin' || $rol === 'gerente')
            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:7px;margin-bottom:7px">
                <a href="{{ route('citas.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#ede9fe"><span>📅</span></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#111827">Gestionar Citas</div>
                        <div style="font-size:10px;color:#6b7280">Ver y administrar citas</div>
                    </div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('clientes.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#dcfce7"><span>👤</span></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#111827">Clientes</div>
                        <div style="font-size:10px;color:#6b7280">Información de clientes</div>
                    </div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('empleados.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#dbeafe"><span>👔</span></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#111827">Empleados</div>
                        <div style="font-size:10px;color:#6b7280">Personal del salón</div>
                    </div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('servicios.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#fce7f3"><span>✂️</span></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#111827">Servicios</div>
                        <div style="font-size:10px;color:#6b7280">Servicios de belleza</div>
                    </div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('productos.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#ffedd5"><span>🛍️</span></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#111827">Productos</div>
                        <div style="font-size:10px;color:#6b7280">Inventario y productos</div>
                    </div>
                    <div class="qa-arrow">›</div>
                </a>
            </div>
            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:7px;margin-bottom:7px">
                <a href="{{ route('cobros.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#ccfbf1"><span>💳</span></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#111827">Registro de Cobros</div>
                        <div style="font-size:10px;color:#6b7280">Pagos y transacciones</div>
                    </div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('deudas.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#fee2e2"><span>💰</span></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#111827">Gestionar Deudas</div>
                        <div style="font-size:10px;color:#6b7280">Cuentas pendientes</div>
                    </div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('bonos.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#f3e8ff"><span>🎫</span></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#111827">Gestionar Bonos</div>
                        <div style="font-size:10px;color:#6b7280">Crear y vender bonos</div>
                    </div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('bonos.clientesConBonos') }}" class="qa-card">
                    <div class="qa-icon" style="background:#dcfce7"><span>👥</span></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#111827">Clientes con Bonos</div>
                        <div style="font-size:10px;color:#6b7280">Bonos activos</div>
                    </div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('caja.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#fefce8"><span>💵</span></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#111827">Caja del Día</div>
                        <div style="font-size:10px;color:#6b7280">Ingresos diarios</div>
                    </div>
                    <div class="qa-arrow">›</div>
                </a>
            </div>
            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:7px">
                <a href="{{ route('facturacion.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#dbeafe"><span>📊</span></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#111827">Facturación</div>
                        <div style="font-size:10px;color:#6b7280">Informe mensual</div>
                    </div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('horarios.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#ffedd5"><span>⏰</span></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#111827">Horarios</div>
                        <div style="font-size:10px;color:#6b7280">Turnos de trabajo</div>
                    </div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('asistencia.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#ede9fe"><span>🕐</span></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#111827">Control Asistencia</div>
                        <div style="font-size:10px;color:#6b7280">Entrada/salida empleados</div>
                    </div>
                    <div class="qa-arrow">›</div>
                </a>
                @if($rol === 'admin')
                <a href="{{ route('users.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#dbeafe"><span>⚙️</span></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#111827">Usuarios del Sistema</div>
                        <div style="font-size:10px;color:#6b7280">Administración accesos</div>
                    </div>
                    <div class="qa-arrow">›</div>
                </a>
                @endif
            </div>
            @endif

            @if($rol === 'empleado')
            <!-- Widget de Asistencia -->
            <div class="mb-4">
                @if($agent->isDesktop())
                    @include('asistencia.widget-empleado')
                @else
                    <div style="background:#eff6ff;border-left:4px solid #3b82f6;padding:14px;border-radius:8px">
                        <p style="font-size:13px;color:#1d4ed8;font-weight:600">📱 Modo móvil detectado</p>
                        <p style="font-size:12px;color:#2563eb">El registro de entrada y salida solo está disponible desde el ordenador del salón.</p>
                    </div>
                @endif
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;max-width:700px">
                <a href="{{ route('citas.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#ede9fe"><span>📅</span></div>
                    <div style="flex:1"><div style="font-size:13px;font-weight:700;color:#111827">Citas</div></div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('clientes.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#dcfce7"><span>👤</span></div>
                    <div style="flex:1"><div style="font-size:13px;font-weight:700;color:#111827">Clientes</div></div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('cobros.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#ccfbf1"><span>💳</span></div>
                    <div style="flex:1"><div style="font-size:13px;font-weight:700;color:#111827">Cobros</div></div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('deudas.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#fee2e2"><span>💰</span></div>
                    <div style="flex:1"><div style="font-size:13px;font-weight:700;color:#111827">Deudas</div></div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('caja.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#fefce8"><span>💵</span></div>
                    <div style="flex:1"><div style="font-size:13px;font-weight:700;color:#111827">Caja del Día</div></div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('bonos.index') }}" class="qa-card">
                    <div class="qa-icon" style="background:#f3e8ff"><span>🎫</span></div>
                    <div style="flex:1"><div style="font-size:13px;font-weight:700;color:#111827">Bonos</div></div>
                    <div class="qa-arrow">›</div>
                </a>
                <a href="{{ route('bonos.clientesConBonos') }}" class="qa-card">
                    <div class="qa-icon" style="background:#fce7f3"><span>👥</span></div>
                    <div style="flex:1"><div style="font-size:13px;font-weight:700;color:#111827">Clientes con Bonos</div></div>
                    <div class="qa-arrow">›</div>
                </a>
            </div>
            @endif

            @if($rol === 'cliente')
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;max-width:500px">
                <a href="{{ route('citas.create') }}" class="qa-card" style="padding:20px;flex-direction:column;text-align:center;background:linear-gradient(135deg,#f3e8ff,#fce7f3)">
                    <div style="font-size:40px;margin-bottom:8px">➕</div>
                    <div style="font-size:15px;font-weight:700;color:#7c3aed">Reservar Cita</div>
                    <div style="font-size:12px;color:#a855f7">Agenda tu próxima visita</div>
                </a>
                <a href="{{ route('citas.index') }}" class="qa-card" style="padding:20px;flex-direction:column;text-align:center">
                    <div style="font-size:40px;margin-bottom:8px">📋</div>
                    <div style="font-size:15px;font-weight:700;color:#111827">Mis Citas</div>
                    <div style="font-size:12px;color:#6b7280">Ver citas programadas</div>
                </a>
            </div>
            @endif
        </div>

    </div><!-- /content -->

    <!-- ── FOOTER BAR ── -->
    <div style="background:#fff;border-top:1px solid #e5e7eb;padding:10px 18px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <div style="flex:1;min-width:160px">
            <div style="font-size:12px;font-weight:700;color:#111827;margin-bottom:1px">Consejo del día ✨</div>
            <div style="font-size:11px;color:#6b7280">Mantén tu agenda al día para ofrecer el mejor servicio a tus clientes.</div>
        </div>
        @if(in_array($rol, ['admin','gerente']))
        <div style="flex:1;min-width:200px">
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px;color:#374151;margin-bottom:3px">
                <span>Objetivo mensual</span>
                <span style="display:flex;align-items:center;gap:4px">
                    €{{ number_format($ingresosMes, 2) }} /
                    <span id="obj-display" style="font-weight:600;cursor:pointer;border-bottom:1px dashed #9ca3af" title="Clic para editar">
                        €<span id="obj-valor">{{ number_format($objetivoMes, 2, ',', '.') }}</span>
                    </span>
                    <span id="obj-input-wrap" style="display:none">
                        <input id="obj-input" type="number" min="1" step="100" value="{{ $objetivoMes }}"
                            style="width:80px;font-size:11px;border:1.5px solid #7c3aed;border-radius:4px;padding:1px 5px;color:#374151;outline:none"
                            onkeydown="if(event.key==='Enter')guardarObjetivo()" onblur="guardarObjetivo()">
                    </span>
                    <button onclick="editarObjetivo()" id="obj-btn-edit" title="Editar objetivo"
                        style="background:none;border:none;cursor:pointer;font-size:11px;padding:0 1px;color:#7c3aed;line-height:1">✏️</button>
                </span>
            </div>
            <div style="background:#e5e7eb;border-radius:20px;height:6px;overflow:hidden">
                <div id="obj-barra" style="width:{{ $progreso }}%;background:linear-gradient(90deg,#22c55e,#16a34a);height:100%;border-radius:20px;transition:width .5s"></div>
            </div>
            <div style="text-align:right;font-size:10px;color:#15803d;margin-top:1px;font-weight:600"><span id="obj-pct">{{ $progreso }}</span>%</div>
        </div>
        @endif
        <div style="display:flex;gap:8px;flex-shrink:0">
            <a href="{{ route('profile.edit') }}" style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12px;font-weight:600;color:#374151;text-decoration:none;transition:background .2s" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                ✏️ Editar Mi Perfil
            </a>
            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button type="submit" style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:opacity .2s" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    🚪 Cerrar Sesión
                </button>
            </form>
        </div>
    </div>

</div><!-- /main-wrapper -->

<script>
// ── Toggle sidebar con teclado (Escape) ──
document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') document.body.classList.remove('sidebar-collapsed');
});

// ── Objetivo mensual editable (localStorage) ──
(function(){
    var ingresosMes = {{ $ingresosMes ?? 0 }};
    var key = 'objetivo_mes_salonlh';
    var stored = localStorage.getItem(key);
    if(stored){
        var val = parseFloat(stored);
        if(val > 0){
            var fmt = new Intl.NumberFormat('es-ES', {minimumFractionDigits:2, maximumFractionDigits:2});
            var el = document.getElementById('obj-valor');
            var inp = document.getElementById('obj-input');
            var barra = document.getElementById('obj-barra');
            var pctEl = document.getElementById('obj-pct');
            if(el) el.textContent = fmt.format(val);
            if(inp) inp.value = val;
            var pct = Math.min(100, Math.round((ingresosMes / val) * 100));
            if(barra) barra.style.width = pct + '%';
            if(pctEl) pctEl.textContent = pct;
        }
    }
})();

function editarObjetivo(){
    document.getElementById('obj-display').style.display = 'none';
    document.getElementById('obj-btn-edit').style.display = 'none';
    document.getElementById('obj-input-wrap').style.display = 'inline';
    var inp = document.getElementById('obj-input');
    inp.focus(); inp.select();
}
function guardarObjetivo(){
    var inp = document.getElementById('obj-input');
    var val = parseFloat(inp.value);
    if(!val || val <= 0) val = 15000;
    localStorage.setItem('objetivo_mes_salonlh', val);
    var fmt = new Intl.NumberFormat('es-ES', {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('obj-valor').textContent = fmt.format(val);
    document.getElementById('obj-display').style.display = '';
    document.getElementById('obj-btn-edit').style.display = '';
    document.getElementById('obj-input-wrap').style.display = 'none';
    var ingresosMes = {{ $ingresosMes ?? 0 }};
    var pct = Math.min(100, Math.round((ingresosMes / val) * 100));
    document.getElementById('obj-barra').style.width = pct + '%';
    document.getElementById('obj-pct').textContent = pct;
}

// Reloj en tiempo real
(function(){
    function pad(n){ return n < 10 ? '0'+n : n; }
    function tick(){
        var d = new Date();
        var h = d.getHours(), m = d.getMinutes(), ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        var el = document.getElementById('reloj');
        if(el) el.textContent = h + ':' + pad(m) + ' ' + ampm;
    }
    tick();
    setInterval(tick, 30000);
})();

// Chart.js — Ingresos
(function(){
    var el = document.getElementById('ingresosChart');
    if(!el) return;
    new Chart(el, {
        type: 'line',
        data: {
            labels: @json($labelsChart),
            datasets:[{
                data: @json($ingresosChart),
                borderColor:'#7c3aed',
                backgroundColor:'rgba(124,58,237,.08)',
                borderWidth:2.5,
                pointBackgroundColor:'#7c3aed',
                pointRadius:4,
                tension:.4,
                fill:true
            }]
        },
        options:{
            plugins:{legend:{display:false}},
            scales:{
                x:{grid:{display:false},ticks:{font:{size:10},color:'#9ca3af'}},
                y:{grid:{color:'rgba(0,0,0,.05)'},ticks:{font:{size:10},color:'#9ca3af',callback:v=>'€'+v}}
            },
            responsive:true,
            maintainAspectRatio:true
        }
    });
})();

// Chart.js — Distribución
(function(){
    var el = document.getElementById('distribChart');
    if(!el) return;
    new Chart(el, {
        type:'doughnut',
        data:{
            labels:['Peluquería','Estética'],
            datasets:[{
                data:[ {{ $distrib['Peluquería'] }}, {{ $distrib['Estética'] }} ],
                backgroundColor:['#7c3aed','#f59e0b'],
                borderWidth:0
            }]
        },
        options:{
            plugins:{legend:{display:false}},
            cutout:'65%',
            responsive:true,
            maintainAspectRatio:true
        }
    });
})();
</script>

</body>
</html>
