<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Agenda de Citas</title>
    {!! vite_asset(['resources/css/app.css', 'resources/css/calendar.css', 'resources/js/calendar.js', 'resources/js/app.js']) !!}
    <style>
        :root { --sidebar-w: 210px; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f3f4f8; }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed; top: 0; left: 0; width: var(--sidebar-w);
            height: 100vh; background: #1e1a4b;
            display: flex; flex-direction: column; z-index: 50;
            overflow-y: auto; transition: transform .3s ease;
        }
        body.sidebar-collapsed .sidebar { transform: translateX(calc(-1 * var(--sidebar-w))); }
        body.sidebar-collapsed .main-wrapper { margin-left: 0; }
        .sidebar-logo { padding: 14px 14px 10px; border-bottom: 1px solid rgba(255,255,255,.1); }
        .sidebar-logo .logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #f472b6, #a855f7);
            border-radius: 10px; display: flex; align-items: center;
            justify-content: center; font-size: 18px; flex-shrink: 0;
        }
        .sidebar-logo .logo-text { color: #fff; font-size: 12.5px; font-weight: 700; line-height: 1.2; }
        .sidebar-logo .logo-sub  { color: rgba(255,255,255,.55); font-size: 10px; }
        .sidebar-nav { flex: 1; padding: 8px; }
        .nav-item {
            display: flex; align-items: center; gap: 8px;
            padding: 7px 10px; border-radius: 8px; margin-bottom: 1px;
            color: rgba(255,255,255,.7); font-size: 12.5px; font-weight: 500;
            text-decoration: none; transition: all .2s;
        }
        .nav-item:hover { background: rgba(255,255,255,.1); color: #fff; }
        .nav-item.active { background: linear-gradient(135deg, #f472b6, #a855f7); color: #fff; font-weight: 600; }
        .nav-item .nav-icon { width: 16px; text-align: center; flex-shrink: 0; font-size: 13px; }
        .sidebar-help {
            margin: 0 8px 8px;
            background: linear-gradient(135deg, #f97316, #ec4899);
            border-radius: 10px; padding: 10px; color: #fff; font-size: 11px;
        }
        .sidebar-footer { padding: 6px 14px 12px; color: rgba(255,255,255,.35); font-size: 9.5px; }

        /* ── MAIN WRAPPER ── */
        .main-wrapper { margin-left: var(--sidebar-w); min-height: 100vh; display: flex; flex-direction: column; transition: margin-left .3s ease; }

        /* ── TOPBAR ── */
        .topbar {
            background: #fff; padding: 8px 16px;
            display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 40;
            flex-wrap: wrap;
        }
        .topbar .menu-btn {
            width: 32px; height: 32px; border-radius: 8px;
            background: linear-gradient(135deg, #f472b6, #a855f7);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 14px; cursor: pointer; flex-shrink: 0;
        }
        .topbar .page-title { font-size: 15px; font-weight: 700; color: #1f2937; white-space: nowrap; }
        .topbar .user-area { display: flex; align-items: center; gap: 8px; cursor: pointer; flex-shrink: 0; text-decoration: none; color: inherit; }
        .topbar .user-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: linear-gradient(135deg, #f472b6, #a855f7);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 13px;
        }

        /* ── Date nav in topbar ── */
        .cal-nav { display: flex; align-items: center; gap: 4px; }
        .cal-nav a { text-decoration: none; }
        .cal-nav-btn {
            background: #f3f4f6; border: 1px solid #e5e7eb; color: #374151;
            padding: 4px 10px; border-radius: 7px; font-size: 11px; font-weight: 600;
            cursor: pointer; transition: all .2s; white-space: nowrap;
        }
        .cal-nav-btn:hover { background: #e9d5ff; border-color: #a855f7; color: #7c3aed; }
        .cal-nav-fecha {
            font-size: 12.5px; font-weight: 700; color: #1e1a4b;
            padding: 4px 12px; background: #f5f3ff; border-radius: 7px; white-space: nowrap;
        }
        .cal-nav-btn-hoy {
            background: linear-gradient(135deg, #f472b6, #a855f7);
            color: #fff; border: none; padding: 4px 12px;
            border-radius: 7px; font-size: 11px; font-weight: 600; cursor: pointer;
        }
        .btn-nueva-cita-top {
            background: linear-gradient(135deg, #f472b6, #a855f7);
            color: #fff; border: none; padding: 6px 14px;
            border-radius: 8px; font-size: 12px; font-weight: 700;
            cursor: pointer; white-space: nowrap; text-decoration: none;
            display: inline-flex; align-items: center; gap: 4px;
            transition: opacity .2s;
        }
        .btn-nueva-cita-top:hover { opacity: .88; }

        /* ── CONTENT ── */
        .content { flex: 1; padding: 12px 14px; display: flex; flex-direction: column; gap: 10px; }

        /* ── Info row cards ── */
        .info-card {
            background: #fff; border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06); overflow: hidden;
        }
        .info-card-header {
            padding: 8px 14px; background: #1e1a4b;
            color: #fff; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px;
        }
        .info-card-body { padding: 12px 14px; }

        /* ── Leyenda subcategorías ── */
        .leyenda-grid {
            display: flex; flex-wrap: wrap; gap: 6px;
        }
        .leyenda-chip {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
            border: 1.5px solid transparent;
        }
        .leyenda-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
        .leyenda-estado {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-wrapper { margin-left: 0; }
        }
    </style>
</head>
<body>
@php $user = Auth::user(); $rol = $user->rol ?? null; @endphp

<!-- ═══════════ SIDEBAR ═══════════ -->
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
        <a href="{{ route('citas.index') }}" class="nav-item active"><span class="nav-icon">📅</span> Citas</a>
        <a href="{{ route('clientes.index') }}" class="nav-item"><span class="nav-icon">👤</span> Clientes</a>
        @if(in_array($rol, ['admin','gerente']))
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
        @if(in_array($rol, ['admin','gerente']))
        <a href="{{ route('facturacion.index') }}" class="nav-item"><span class="nav-icon">📊</span> Facturación</a>
        <a href="{{ route('horarios.index') }}" class="nav-item"><span class="nav-icon">⏰</span> Horarios</a>
        <a href="{{ route('asistencia.index') }}" class="nav-item"><span class="nav-icon">🕐</span> Asistencia</a>
        @endif
        @if($rol === 'admin')
        <a href="{{ route('users.index') }}" class="nav-item"><span class="nav-icon">⚙️</span> Usuarios</a>
        @endif
    </nav>
    <div class="sidebar-help">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px">
            <span style="font-size:20px">❓</span>
            <span style="font-weight:700;font-size:12px">¿Necesitas ayuda?</span>
        </div>
        <p style="opacity:.85;font-size:11px;line-height:1.4">Consulta nuestra guía o contacta soporte</p>
    </div>
    <div class="sidebar-footer">© {{ date('Y') }} Salón de Belleza</div>
</aside>

<!-- ═══════════ MAIN WRAPPER ═══════════ -->
<div class="main-wrapper"
     id="calendar-app"
     data-mover-url="{{ route('citas.mover') }}"
     data-completar-url="{{ route('citas.marcarCompletada') }}"
     data-create-url="{{ route('citas.create') }}">

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="menu-btn" id="sidebarToggle" onclick="document.body.classList.toggle('sidebar-collapsed')">☰</div>
        <span class="page-title">📅 Agenda de Citas</span>

        @php
            $fechaActual = \Carbon\Carbon::parse($fecha);
            $primerDiaMes = $fechaActual->copy()->startOfMonth();
            $ultimoDiaMes = $fechaActual->copy()->endOfMonth();
            $primerDiaGrid = $primerDiaMes->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
            $ultimoDiaGrid = $ultimoDiaMes->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
            $mesAnterior = $fechaActual->copy()->subMonth();
            $mesSiguiente = $fechaActual->copy()->addMonth();
        @endphp

        <!-- Navegación de fecha -->
        <div class="cal-nav">
            <a href="{{ route('citas.index', ['fecha' => $fechaActual->copy()->subDay()->format('Y-m-d')]) }}">
                <button class="cal-nav-btn">◀</button>
            </a>
            <span class="cal-nav-fecha">
                {{ $fechaActual->locale('es')->isoFormat('dddd, D MMM YYYY') }}
            </span>
            <a href="{{ route('citas.index', ['fecha' => \Carbon\Carbon::now()->format('Y-m-d')]) }}">
                <button class="cal-nav-btn-hoy">Hoy</button>
            </a>
            <a href="{{ route('citas.index', ['fecha' => $fechaActual->copy()->addDay()->format('Y-m-d')]) }}">
                <button class="cal-nav-btn">▶</button>
            </a>
        </div>

        <div style="margin-left:auto;display:flex;align-items:center;gap:10px">
            <a href="{{ route('citas.create') }}" class="btn-nueva-cita-top">
                ＋ Nueva Cita
            </a>
            <a href="{{ route('profile.edit') }}" class="user-area">
                @if ($user && $user->foto_perfil)
                    <img src="{{ route('tenant.file', $user->foto_perfil) }}" style="width:32px;height:32px;border-radius:50%;object-fit:cover">
                @else
                    <div class="user-avatar">{{ strtoupper(substr($user->nombre ?? 'U', 0, 1)) }}</div>
                @endif
                <span style="font-size:12px;font-weight:600;color:#1f2937">{{ $user->nombre ?? '' }}</span>
            </a>
        </div>
    </header>

    <!-- CONTENT -->
    <div class="content">

        <!-- ── Info row: mini-calendario + leyenda ── -->
        <div style="display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap">

            <!-- Mini calendario -->
            <div class="info-card" style="flex-shrink:0;background:#1e1a4b">
                <div class="info-card-header" style="background:rgba(0,0,0,.25)">📆 Calendario</div>
                <div class="info-card-body" style="padding:10px;background:#1e1a4b">
                    <div class="mini-calendario-container">
                        <div class="mini-calendario-header">
                            <h3>{{ $fechaActual->locale('es')->isoFormat('MMMM YYYY') }}</h3>
                            <div class="mini-calendario-nav">
                                <a href="{{ route('citas.index', ['fecha' => $mesAnterior->startOfMonth()->format('Y-m-d')]) }}">
                                    <button>◀</button>
                                </a>
                                <a href="{{ route('citas.index', ['fecha' => \Carbon\Carbon::now()->format('Y-m-d')]) }}">
                                    <button>•</button>
                                </a>
                                <a href="{{ route('citas.index', ['fecha' => $mesSiguiente->startOfMonth()->format('Y-m-d')]) }}">
                                    <button>▶</button>
                                </a>
                            </div>
                        </div>
                        <div class="mini-calendario-grid">
                            <div class="mini-calendario-dia-nombre">L</div>
                            <div class="mini-calendario-dia-nombre">M</div>
                            <div class="mini-calendario-dia-nombre">X</div>
                            <div class="mini-calendario-dia-nombre">J</div>
                            <div class="mini-calendario-dia-nombre">V</div>
                            <div class="mini-calendario-dia-nombre">S</div>
                            <div class="mini-calendario-dia-nombre">D</div>
                            @php
                                $diaIterador = $primerDiaGrid->copy();
                                $hoy = \Carbon\Carbon::now()->startOfDay();
                            @endphp
                            @while($diaIterador <= $ultimoDiaGrid)
                                @php
                                    $esOtroMes  = $diaIterador->month !== $fechaActual->month;
                                    $esHoy      = $diaIterador->isSameDay($hoy);
                                    $esSeleccionado = $diaIterador->isSameDay($fechaActual);
                                @endphp
                                <a href="{{ route('citas.index', ['fecha' => $diaIterador->format('Y-m-d')]) }}"
                                   class="mini-calendario-dia {{ $esOtroMes ? 'otro-mes' : '' }} {{ $esHoy ? 'hoy' : '' }} {{ $esSeleccionado ? 'seleccionado' : '' }}">
                                    {{ $diaIterador->day }}
                                </a>
                                @php $diaIterador->addDay(); @endphp
                            @endwhile
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leyenda subcategorías + estados -->
            <div class="info-card" style="flex:1;min-width:280px">
                <div class="info-card-header">🏷️ Leyenda de colores</div>
                <div class="info-card-body">
                    @if($subcategorias->isNotEmpty())
                        @php $subsPeluqueria = $subcategorias->where('categoria','peluqueria'); $subsEstetica = $subcategorias->where('categoria','estetica'); @endphp

                        @if($subsPeluqueria->isNotEmpty())
                        <div style="margin-bottom:10px">
                            <div style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">✂️ Peluquería</div>
                            <div class="leyenda-grid">
                                @foreach($subsPeluqueria as $sub)
                                @php
                                    $r = hexdec(substr($sub->color,1,2));
                                    $g = hexdec(substr($sub->color,3,2));
                                    $b = hexdec(substr($sub->color,5,2));
                                    $lum = (0.299*$r + 0.587*$g + 0.114*$b)/255;
                                    $textColor = $lum > 0.6 ? '#1f2937' : '#ffffff';
                                @endphp
                                <span class="leyenda-chip" style="background:{{ $sub->color }}20;border-color:{{ $sub->color }};color:{{ $sub->color }}">
                                    <span class="leyenda-dot" style="background:{{ $sub->color }}"></span>
                                    {{ $sub->nombre }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($subsEstetica->isNotEmpty())
                        <div style="margin-bottom:10px">
                            <div style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">💅 Estética</div>
                            <div class="leyenda-grid">
                                @foreach($subsEstetica as $sub)
                                <span class="leyenda-chip" style="background:{{ $sub->color }}20;border-color:{{ $sub->color }};color:{{ $sub->color }}">
                                    <span class="leyenda-dot" style="background:{{ $sub->color }}"></span>
                                    {{ $sub->nombre }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        <hr style="border:none;border-top:1px solid #f3f4f6;margin:8px 0">
                    @endif

                    <!-- Estados -->
                    <div style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Estados de cita</div>
                    <div class="leyenda-grid">
                        <span class="leyenda-estado" style="background:#93c572;color:#1f2937">
                            <span class="leyenda-dot" style="background:#1f2937;opacity:.5"></span> Pendiente (sin subcategoría)
                        </span>
                        <span class="leyenda-estado" style="background:#374151;color:#d1d5db">
                            <span class="leyenda-dot" style="background:#d1d5db"></span> Completada
                        </span>
                        <span class="leyenda-estado" style="background:#e5e7eb;color:#9ca3af;text-decoration:line-through">
                            <span class="leyenda-dot" style="background:#9ca3af"></span> Cancelada
                        </span>
                        <span class="leyenda-estado" style="background:repeating-linear-gradient(45deg,#fee2e2,#fee2e2 6px,#fca5a5 6px,#fca5a5 12px);color:#991b1b">
                            <span class="leyenda-dot" style="background:#ef4444"></span> ⛔ Hora deshabilitada
                        </span>
                        <span class="leyenda-estado" style="background:#d1d5db;color:#4b5563">
                            <span class="leyenda-dot" style="background:#9ca3af"></span> Fuera de horario
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Grid del Calendario ── -->
        <div class="calendario-grid-container">
            @php $numEmpleados = count($empleados); @endphp
            <div class="calendario-grid" style="--num-empleados: {{ $numEmpleados }};">

                <!-- Columna de Horas -->
                <div class="columna-horas">
                    <div class="header-columna horas">Hora</div>
                    @foreach($horariosArray as $hora)
                        <div class="celda-hora">{{ \Carbon\Carbon::parse($hora)->format('H:i') }}</div>
                    @endforeach
                </div>

                <!-- Columnas de Empleados -->
                @foreach($empleados as $empleado)
                    <div class="columna-empleado">
                        <div class="header-columna">
                            <div class="empleado-avatar">
                                @if($empleado->user->foto_perfil)
                                    <img src="{{ route('tenant.file', $empleado->user->foto_perfil) }}"
                                         alt="{{ $empleado->user->nombre }}"
                                         style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                                @else
                                    {{ strtoupper(substr($empleado->user->nombre, 0, 1)) }}{{ strtoupper(substr($empleado->user->apellidos, 0, 1)) }}
                                @endif
                            </div>
                            <div class="empleado-nombre">
                                {{ $empleado->user->nombre }} {{ $empleado->user->apellidos }}
                            </div>
                        </div>

                        @php
                            $horariosEmpleado = isset($horariosEmpleados[$empleado->id]) ? $horariosEmpleados[$empleado->id] : collect();
                            $citasEmpleado = isset($citas[$empleado->id]) ? $citas[$empleado->id] : collect();
                        @endphp

                        @foreach($horariosArray as $hora)
                            @php
                                $horaCarbon = \Carbon\Carbon::parse($fecha->format('Y-m-d') . ' ' . $hora);
                                $disponible = false;
                                $bloqueDeshabilitado = false;

                                foreach ($horariosEmpleado as $horarioTrabajo) {
                                    if ($horarioTrabajo->hora && $horarioTrabajo->hora == $hora) {
                                        if (!$horarioTrabajo->disponible) {
                                            $bloqueDeshabilitado = true;
                                            $disponible = false;
                                        } else {
                                            $disponible = true;
                                        }
                                        break;
                                    }
                                    if ($horarioTrabajo->hora_inicio && $horarioTrabajo->hora_fin) {
                                        $inicioTrabajo = \Carbon\Carbon::parse($fecha->format('Y-m-d') . ' ' . $horarioTrabajo->hora_inicio);
                                        $finTrabajo    = \Carbon\Carbon::parse($fecha->format('Y-m-d') . ' ' . $horarioTrabajo->hora_fin);
                                        if ($horaCarbon->between($inicioTrabajo, $finTrabajo->copy()->subMinutes(30))) {
                                            if (!$bloqueDeshabilitado) $disponible = true;
                                        }
                                    }
                                }

                                $claseEstado = '';
                                if ($bloqueDeshabilitado)    $claseEstado = 'hora-deshabilitada';
                                elseif (!$disponible)        $claseEstado = 'no-disponible';
                            @endphp

                            <div class="celda-horario {{ $claseEstado }}"
                                 data-empleado-id="{{ $empleado->id }}"
                                 data-fecha-hora="{{ $horaCarbon->format('Y-m-d H:i:s') }}"
                                 @if($disponible && !$bloqueDeshabilitado)
                                 ondrop="drop(event)"
                                 ondragover="allowDrop(event)"
                                 onclick="crearCitaRapida({{ $empleado->id }}, '{{ $horaCarbon->format('Y-m-d H:i:s') }}', event)"
                                 @endif
                                 @if($bloqueDeshabilitado) title="⛔ Hora deshabilitada" @endif>
                                @if($bloqueDeshabilitado)<span class="icono-deshabilitado">⛔</span>@endif
                            </div>
                        @endforeach

                        <!-- Citas del Empleado -->
                        @foreach($citasEmpleado as $cita)
                            @php
                                $horaInicio = \Carbon\Carbon::parse($cita->fecha_hora);
                                $horaBaseStr = $horariosArray[0] ?? '09:00:00';
                                $horaBase    = \Carbon\Carbon::parse($fecha->format('Y-m-d') . ' ' . $horaBaseStr);
                                $minutosDesdeInicio = $horaBase->diffInMinutes($horaInicio, false);
                                $numeroBloque  = $minutosDesdeInicio / 15;
                                $topPosition   = 88 + ($numeroBloque * 30) + 2;
                                $bloquesOcupados = max(1, $cita->duracion_minutos / 15);
                                $altura = $cita->duracion_minutos <= 15 ? 30 : ($bloquesOcupados * 30) * 0.92;

                                $categoriaServicio = 'peluqueria';
                                if ($cita->servicios->isNotEmpty()) {
                                    $categorias = $cita->servicios->pluck('categoria')->toArray();
                                    if (in_array('estetica', $categorias)) $categoriaServicio = 'estetica';
                                }

                                $colorSubcategoria = null;
                                if ($cita->estado === 'pendiente' && $cita->servicios->isNotEmpty()) {
                                    $primerServicio = $cita->servicios->first();
                                    if ($primerServicio->subcategoria && $primerServicio->subcategoria->color) {
                                        $colorSubcategoria = $primerServicio->subcategoria->color;
                                    }
                                }

                                $esParteDeGrupo   = $cita->grupo_cita_id !== null;
                                $esGrupoPeluqueria = $esParteDeGrupo && $categoriaServicio === 'peluqueria';
                                $esUltimoDelGrupo  = false;
                                if ($esParteDeGrupo) {
                                    $maxOrden = \App\Models\Cita::where('grupo_cita_id', $cita->grupo_cita_id)->max('orden_servicio');
                                    $esUltimoDelGrupo = $cita->orden_servicio == $maxOrden;
                                }
                            @endphp

                            <div class="cita-card {{ $cita->estado }} cita-{{ $categoriaServicio }}
                                 @if($esGrupoPeluqueria) cita-grupo-peluqueria @endif
                                 @if($cita->duracion_minutos < 30) cita-corta
                                 @elseif($cita->duracion_minutos < 60) cita-mediana
                                 @else cita-larga @endif"
                                 data-cita-id="{{ $cita->id }}"
                                 data-grupo-id="{{ $cita->grupo_cita_id ?? '' }}"
                                 data-orden="{{ $cita->orden_servicio ?? 1 }}"
                                 data-duracion-actual="{{ $cita->duracion_minutos }}"
                                 @if($cita->estado !== 'completada' && $cita->estado !== 'cancelada')
                                 draggable="true" ondragstart="drag(event)"
                                 @endif
                                 style="top: {{ $topPosition }}px; height: {{ $altura }}px;
                                 @if($colorSubcategoria) background-color: {{ $colorSubcategoria }}; @endif
                                 @if($esGrupoPeluqueria) border-left: 3px solid #6366f1; @endif
                                 @if($cita->estado !== 'completada' && $cita->estado !== 'cancelada') cursor: move; @endif">

                                <div class="cita-content">
                                    <div class="cita-info">
                                        <div class="cita-cliente">
                                            {{ $cita->cliente && $cita->cliente->user ? $cita->cliente->user->nombre . ' ' . $cita->cliente->user->apellidos : 'Cliente no disponible' }}
                                            @php
                                                $tieneBono = false;
                                                if ($cita->cliente && $cita->cliente->bonos) {
                                                    foreach ($cita->cliente->bonos as $bono) {
                                                        if ($bono->estado === 'activo') {
                                                            foreach ($cita->servicios as $servicio) {
                                                                $servicioEnBono = $bono->servicios->firstWhere('id', $servicio->id);
                                                                if ($servicioEnBono) {
                                                                    $disponible = $servicioEnBono->pivot->cantidad_total - $servicioEnBono->pivot->cantidad_usada;
                                                                    if ($disponible > 0) { $tieneBono = true; break 2; }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            @endphp
                                            @if($tieneBono)<span title="Cliente con bono disponible" style="margin-left:3px;font-size:12px">🎫</span>@endif
                                        </div>
                                        <div class="cita-servicio">
                                            {{ $cita->servicios->isNotEmpty() ? $cita->servicios->pluck('nombre')->join(', ') : 'Servicio no especificado' }}
                                        </div>
                                        <div class="cita-duracion">
                                            <span>⏱</span>
                                            <span class="duracion-valor">{{ $cita->duracion_minutos }}</span> min
                                            @if($cita->duracion_real)<span style="font-size:9px;opacity:.7">(ajustada)</span>@endif
                                        </div>
                                    </div>

                                    @if($cita->estado !== 'completada' && $cita->estado !== 'cancelada')
                                    <div class="cita-acciones">
                                        <form action="{{ route('citas.completarYCobrar', $cita->id) }}" method="POST" style="display:inline">
                                            @csrf
                                            <button type="submit" class="btn-accion btn-completar"
                                                    onclick="event.stopPropagation()" title="Completar y cobrar">✓</button>
                                        </form>
                                        <form action="{{ route('citas.destroy', $cita->id) }}" method="POST" style="display:inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-accion btn-cancelar"
                                                    onclick="event.stopPropagation(); return confirm('¿Eliminar esta cita permanentemente?')"
                                                    title="Eliminar cita">✕</button>
                                        </form>
                                        @php
                                            $citasClienteDia = $citasEmpleado->where('id_cliente', $cita->id_cliente)->count()
                                                + $citas->filter(fn($v,$k) => $k != $empleado->id)->flatten()->where('id_cliente', $cita->id_cliente)->count();
                                        @endphp
                                        @if($citasClienteDia > 1)
                                        <form action="{{ route('citas.destroyClienteDia', ['cliente' => $cita->id_cliente, 'fecha' => $fecha->format('Y-m-d')]) }}" method="POST" style="display:inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-accion btn-cancelar"
                                                    onclick="event.stopPropagation(); return confirm('¿Eliminar TODAS las citas ({{ $citasClienteDia }}) de {{ $cita->cliente && $cita->cliente->user ? $cita->cliente->user->nombre : 'este cliente' }} del día?')"
                                                    title="Eliminar todas las citas del cliente hoy"
                                                    style="font-size:9px;background:#b91c1c">✕All</button>
                                        </form>
                                        @endif
                                        <button class="btn-accion btn-ver"
                                                onclick="event.stopPropagation(); window.location.href='{{ route('citas.show', $cita->id) }}'">👁</button>
                                        <button class="btn-accion"
                                                onclick="event.stopPropagation(); abrirPopoverCita(this);"
                                                title="Mover cita"
                                                style="background:#3b82f6;color:#fff;font-size:10px"
                                                data-cita-id="{{ $cita->id }}"
                                                data-empleado-id="{{ $cita->id_empleado }}"
                                                data-hora="{{ \Carbon\Carbon::parse($cita->fecha_hora)->format('H:i:s') }}"
                                                data-fecha="{{ \Carbon\Carbon::parse($cita->fecha_hora)->format('Y-m-d') }}"
                                                data-cliente-nombre="{{ $cita->cliente && $cita->cliente->user ? $cita->cliente->user->nombre : 'Cliente' }}">
                                            📅
                                        </button>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            @if($empleados->isEmpty())
                <div class="mensaje-vacio">
                    <p>No hay empleados disponibles para mostrar el calendario.</p>
                </div>
            @endif
        </div>

    </div>{{-- /content --}}
</div>{{-- /main-wrapper --}}

<!-- Popover cambiar fecha/hora cita -->
<div id="popover-cita" style="display:none;position:fixed;z-index:9999;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.18);padding:16px;min-width:268px;">
    <p style="font-weight:700;margin:0 0 12px;color:#1e1a4b;font-size:14px">📅 Mover cita — <span id="popover-cliente-nombre" style="color:#374151"></span></p>
    <div style="margin-bottom:10px">
        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px">Fecha</label>
        <input type="date" id="popover-fecha" style="width:100%;border:1px solid #d1d5db;border-radius:7px;padding:6px 10px;font-size:13px;box-sizing:border-box">
    </div>
    <div style="margin-bottom:14px">
        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px">Hora</label>
        <input type="time" id="popover-hora" step="900" style="width:100%;border:1px solid #d1d5db;border-radius:7px;padding:6px 10px;font-size:13px;box-sizing:border-box">
    </div>
    <div style="display:flex;gap:8px;justify-content:flex-end">
        <button onclick="cerrarPopoverCita()" style="padding:6px 14px;border:1px solid #d1d5db;border-radius:7px;background:#fff;cursor:pointer;font-size:12px;color:#374151">Cancelar</button>
        <button onclick="confirmarMoverCita()" style="padding:6px 14px;border:none;border-radius:7px;background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;cursor:pointer;font-size:12px;font-weight:700">Mover →</button>
    </div>
</div>

<!-- Modal -->
<div id="modalCita" class="modal" onclick="cerrarModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2>Detalles de la Cita</h2>
            <button class="btn-cerrar" onclick="cerrarModal()">&times;</button>
        </div>
        <div id="modalBody"></div>
    </div>
</div>

</body>
</html>
