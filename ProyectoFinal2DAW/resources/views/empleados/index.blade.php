<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleados</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
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
        .logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #f472b6, #a855f7);
            border-radius: 10px; display: flex; align-items: center;
            justify-content: center; font-size: 18px; flex-shrink: 0;
        }
        .logo-text { color: #fff; font-size: 12.5px; font-weight: 700; line-height: 1.2; }
        .logo-sub  { color: rgba(255,255,255,.55); font-size: 10px; }
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
            background: #fff; padding: 8px 20px;
            display: flex; align-items: center; gap: 12px;
            border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 40;
        }
        .menu-btn {
            width: 32px; height: 32px; border-radius: 8px;
            background: linear-gradient(135deg, #f472b6, #a855f7);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 14px; cursor: pointer; flex-shrink: 0;
        }
        .page-title { font-size: 15px; font-weight: 700; color: #1f2937; }
        .user-area { display: flex; align-items: center; gap: 8px; text-decoration: none; color: inherit; }
        .user-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: linear-gradient(135deg, #f472b6, #a855f7);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 13px; flex-shrink: 0;
        }

        /* ── CONTENT ── */
        .content { flex: 1; padding: 18px 20px; display: flex; flex-direction: column; gap: 16px; }

        /* ── STAT CARDS ── */
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; }
        .stat-card {
            background: #fff; border-radius: 14px; padding: 16px 18px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06); display: flex; align-items: center; gap: 12px;
        }
        .stat-icon {
            width: 42px; height: 42px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;
        }
        .stat-value { font-size: 22px; font-weight: 800; color: #1e1a4b; line-height: 1; }
        .stat-label { font-size: 11px; color: #6b7280; margin-top: 2px; }

        /* ── PANEL / TABLE ── */
        .panel {
            background: #fff; border-radius: 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06); overflow: hidden;
        }
        .panel-header {
            padding: 14px 20px; background: #1e1a4b;
            display: flex; align-items: center; justify-content: space-between;
        }
        .panel-header h2 { color: #fff; font-size: 14px; font-weight: 700; }
        .btn-primary {
            background: linear-gradient(135deg, #f472b6, #a855f7);
            color: #fff; border: none; padding: 7px 16px; border-radius: 8px;
            font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: none;
            display: inline-flex; align-items: center; gap: 5px; transition: opacity .2s;
        }
        .btn-primary:hover { opacity: .88; }

        /* ── TABLE ── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #f9fafb; padding: 10px 14px; text-align: left;
            font-size: 11px; font-weight: 700; color: #6b7280;
            text-transform: uppercase; letter-spacing: .4px;
            border-bottom: 1px solid #f3f4f6;
        }
        tbody tr { border-bottom: 1px solid #f9fafb; transition: background .15s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #fafafa; }
        tbody td { padding: 10px 14px; font-size: 13px; color: #374151; vertical-align: middle; }

        /* ── AVATAR EMPLEADO ── */
        .emp-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, #f472b6, #a855f7);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 13px; flex-shrink: 0;
        }
        .emp-name { font-weight: 600; color: #1e1a4b; font-size: 13px; }

        /* ── BADGES ── */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
        }
        .badge-peluqueria { background: #eff6ff; color: #1d4ed8; }
        .badge-estetica   { background: #fdf2f8; color: #be185d; }

        /* ── FACTURACIÓN ── */
        .fact-total { font-size: 15px; font-weight: 800; color: #166534; }
        .fact-details { display: none; }
        .fact-toggle {
            font-size: 10px; color: #a855f7; cursor: pointer; font-weight: 600;
            background: none; border: none; padding: 0; text-decoration: underline;
        }
        .fact-row { display: flex; justify-content: space-between; padding: 2px 0; font-size: 11px; color: #6b7280; }
        .fact-row span:last-child { font-weight: 600; color: #374151; }
        .variacion-pos { color: #16a34a; font-size: 10px; font-weight: 700; }
        .variacion-neg { color: #dc2626; font-size: 10px; font-weight: 700; }

        /* ── CITAS BADGE ── */
        .citas-badge {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 28px; height: 28px; padding: 0 8px;
            background: #f5f3ff; color: #7c3aed; border-radius: 8px;
            font-weight: 700; font-size: 14px;
        }

        /* ── ACTION BUTTONS ── */
        .btn-ver    { background: #ede9fe; color: #7c3aed; border: none; padding: 5px 11px; border-radius: 7px; font-size: 11px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all .2s; }
        .btn-editar { background: #fff7ed; color: #ea580c; border: none; padding: 5px 11px; border-radius: 7px; font-size: 11px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all .2s; }
        .btn-borrar { background: #fef2f2; color: #dc2626; border: none; padding: 5px 11px; border-radius: 7px; font-size: 11px; font-weight: 600; cursor: pointer; transition: all .2s; }
        .btn-ver:hover    { background: #7c3aed; color: #fff; }
        .btn-editar:hover { background: #ea580c; color: #fff; }
        .btn-borrar:hover { background: #dc2626; color: #fff; }

        /* ── FLASH MESSAGES ── */
        .alert {
            padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 600;
            display: flex; align-items: center; gap: 8px;
        }
        .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #22c55e; }
        .alert-error   { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }

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
        <a href="{{ route('citas.index') }}" class="nav-item"><span class="nav-icon">📅</span> Citas</a>
        <a href="{{ route('clientes.index') }}" class="nav-item"><span class="nav-icon">👤</span> Clientes</a>
        @if(in_array($rol, ['admin','gerente']))
        <a href="{{ route('empleados.index') }}" class="nav-item active"><span class="nav-icon">👔</span> Empleados</a>
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

<!-- ═══════════ MAIN ═══════════ -->
<div class="main-wrapper">

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="menu-btn" onclick="document.body.classList.toggle('sidebar-collapsed')">☰</div>
        <span class="page-title">👔 Empleados</span>
        <div style="flex:1"></div>
        <a href="{{ route('profile.edit') }}" class="user-area">
            @if ($user && $user->foto_perfil)
                <img src="{{ route('tenant.file', $user->foto_perfil) }}" style="width:32px;height:32px;border-radius:50%;object-fit:cover">
            @else
                <div class="user-avatar">{{ strtoupper(substr($user->nombre ?? 'U', 0, 1)) }}</div>
            @endif
            <div style="line-height:1.2">
                <div style="font-weight:600;font-size:13px;color:#1f2937">{{ $user->nombre ?? '' }} {{ $user->apellidos ?? '' }}</div>
                <div style="font-size:11px;color:#6b7280;text-transform:capitalize">{{ ucfirst($user->rol ?? '') }}</div>
            </div>
        </a>
    </header>

    <!-- CONTENT -->
    <div class="content">

        @if(session('success'))
            <div class="alert alert-success">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">❌ {{ session('error') }}</div>
        @endif

        <!-- ── Stats ── -->
        @php
            $totalEmpleados   = $empleados->count();
            $totalPeluqueria  = $empleados->where('categoria','peluqueria')->count();
            $totalEstetica    = $empleados->where('categoria','estetica')->count();
            $totalCitas       = $empleados->sum('citasAtendidas');
            $totalFacturacion = $empleados->sum(fn($e) => $e->facturacion['total'] ?? 0);
        @endphp
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon" style="background:#ede9fe">👔</div>
                <div>
                    <div class="stat-value">{{ $totalEmpleados }}</div>
                    <div class="stat-label">Total empleados</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#eff6ff">💇</div>
                <div>
                    <div class="stat-value" style="color:#1d4ed8">{{ $totalPeluqueria }}</div>
                    <div class="stat-label">Peluquería</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fdf2f8">💅</div>
                <div>
                    <div class="stat-value" style="color:#be185d">{{ $totalEstetica }}</div>
                    <div class="stat-label">Estética</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#f5f3ff">📅</div>
                <div>
                    <div class="stat-value" style="color:#7c3aed">{{ $totalCitas }}</div>
                    <div class="stat-label">Citas este mes</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#dcfce7">💶</div>
                <div>
                    <div class="stat-value" style="color:#166534;font-size:18px">€{{ number_format($totalFacturacion, 0, ',', '.') }}</div>
                    <div class="stat-label">Facturación mes</div>
                </div>
            </div>
        </div>

        <!-- ── Table ── -->
        <div class="panel">
            <div class="panel-header">
                <h2>👔 Listado de empleados</h2>
                <a href="{{ route('empleados.create') }}" class="btn-primary">＋ Nuevo empleado</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Email</th>
                            <th>Categoría</th>
                            <th>Citas (mes)</th>
                            <th>Facturación mensual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($empleados as $empleado)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div class="emp-avatar">
                                        {{ strtoupper(substr($empleado->user->nombre ?? 'E', 0, 1)) }}{{ strtoupper(substr($empleado->user->apellidos ?? '', 0, 1)) }}
                                    </div>
                                    <div class="emp-name">{{ $empleado->user->nombre ?? '-' }} {{ $empleado->user->apellidos ?? '' }}</div>
                                </div>
                            </td>
                            <td style="font-size:12px;color:#6b7280">{{ $empleado->user->email ?? '-' }}</td>
                            <td>
                                @if($empleado->categoria === 'peluqueria')
                                    <span class="badge badge-peluqueria">💇 Peluquería</span>
                                @else
                                    <span class="badge badge-estetica">💅 Estética</span>
                                @endif
                            </td>
                            <td><span class="citas-badge">{{ $empleado->citasAtendidas ?? 0 }}</span></td>
                            <td>
                                @php
                                    $actual   = $empleado->facturacion['total'] ?? 0;
                                    $anterior = $empleado->facturacionAnterior['total'] ?? 0;
                                    $diferencia = $actual - $anterior;
                                    $porcentaje = $anterior > 0 ? (($diferencia / $anterior) * 100) : 0;
                                @endphp
                                <div class="fact-total">€{{ number_format($actual, 2, ',', '.') }}</div>
                                @if($anterior > 0 && $diferencia != 0)
                                    @if($diferencia > 0)
                                        <div class="variacion-pos">▲ +€{{ number_format(abs($diferencia), 2) }} ({{ number_format($porcentaje, 1) }}%)</div>
                                    @else
                                        <div class="variacion-neg">▼ -€{{ number_format(abs($diferencia), 2) }} ({{ number_format(abs($porcentaje), 1) }}%)</div>
                                    @endif
                                @endif
                                <button class="fact-toggle" onclick="toggleDetalle(this)">Ver desglose ▾</button>
                                <div class="fact-details" style="margin-top:6px;background:#f9fafb;border-radius:8px;padding:8px">
                                    <div class="fact-row"><span>💇 Servicios</span><span>€{{ number_format($empleado->facturacion['servicios'] ?? 0, 2, ',', '.') }}</span></div>
                                    <div class="fact-row"><span>📦 Productos</span><span>€{{ number_format($empleado->facturacion['productos'] ?? 0, 2, ',', '.') }}</span></div>
                                    <div class="fact-row"><span>🎫 Bonos</span><span>€{{ number_format($empleado->facturacion['bonos'] ?? 0, 2, ',', '.') }}</span></div>
                                    @if($anterior > 0)
                                    <div class="fact-row" style="border-top:1px solid #e5e7eb;margin-top:4px;padding-top:4px">
                                        <span style="color:#9ca3af">Mes anterior</span>
                                        <span>€{{ number_format($anterior, 2, ',', '.') }}</span>
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap">
                                    <a href="{{ route('empleados.show', $empleado->id) }}" class="btn-ver">👁 Ver</a>
                                    <a href="{{ route('empleados.edit', $empleado->id) }}" class="btn-editar">✏️ Editar</a>
                                    <form id="delete-form-{{ $empleado->id }}"
                                          action="{{ route('empleados.destroy', $empleado->id) }}"
                                          method="POST" style="display:inline" onsubmit="return false;">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn-borrar"
                                                onclick="confirmarEliminacion({{ $empleado->id }})">🗑 Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:40px;color:#9ca3af;font-size:14px">
                                <div style="font-size:36px;margin-bottom:10px">👔</div>
                                No hay empleados registrados aún.
                                <br><br>
                                <a href="{{ route('empleados.create') }}" class="btn-primary" style="display:inline-flex">＋ Añadir primer empleado</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>{{-- /content --}}
</div>{{-- /main-wrapper --}}

<script>
    function confirmarEliminacion(id) {
        if (confirm('¿Estás seguro de que quieres eliminar este empleado?')) {
            document.getElementById('delete-form-' + id).submit();
        }
    }
    function toggleDetalle(btn) {
        const details = btn.nextElementSibling;
        const visible = details.style.display === 'block';
        details.style.display = visible ? 'none' : 'block';
        btn.textContent = visible ? 'Ver desglose ▾' : 'Ocultar ▴';
    }
</script>
</body>
</html>