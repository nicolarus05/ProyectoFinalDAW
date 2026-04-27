<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Horarios - Generar {{ ucfirst($tipo) }}</title>
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
        .main-content { padding:20px 24px; flex:1; max-width: calc(100vw - var(--sidebar-w)); }
        .btn-back { color:#6b7280; font-size:.82rem; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
        .btn-back:hover { color:#1e1a4b; }
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
            <a href="{{ route('horarios.index') }}" class="nav-item active"><span class="nav-icon">⏰</span> Horarios</a>
            <a href="{{ route('asistencia.index') }}" class="nav-item"><span class="nav-icon">🕐</span> Asistencia</a>
            <a href="{{ route('users.index') }}" class="nav-item"><span class="nav-icon">⚙️</span> Usuarios</a>
        </nav>
        <div class="sidebar-footer"><p>© 2026 Salón de Belleza</p></div>
    </aside>
    <div class="content">
        <header class="topbar">
            <span class="topbar-title">⏰ Configurar y Generar Horarios</span>
            <div class="topbar-actions">
                <a href="{{ route('horarios.index') }}" class="btn-back">← Volver</a>
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
            <div class="max-w-6xl mx-auto">
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 mb-6 text-sm text-blue-800">
                    <strong>Empleado:</strong> {{ $empleado->user->nombre }} {{ $empleado->user->apellidos }} &nbsp;|&nbsp;
                    <strong>Tipo:</strong> {{ $tipo === 'semana' ? 'Semana' : ($tipo === 'mes' ? 'Mes' : 'Año completo') }}
                    @if($tipo === 'semana' && $fecha_inicio)
                        — Desde: {{ \Carbon\Carbon::parse($fecha_inicio)->format('d/m/Y') }}
                    @elseif($tipo === 'mes' && $mes && $anio)
                        — {{ \Carbon\Carbon::create($anio, $mes, 1)->locale('es')->translatedFormat('F Y') }}
                    @elseif($tipo === 'anual' && $anio)
                        — Año {{ $anio }}
                    @endif
                </div>

            <form action="{{ 
                $tipo === 'semana' ? route('horarios.generarSemana') : 
                ($tipo === 'mes' ? route('horarios.generarMes') : route('horarios.generarAnual')) 
            }}" method="POST" class="space-y-8">
                @csrf
                
                <!-- Campos ocultos -->
                <input type="hidden" name="id_empleado" value="{{ $empleado->id }}">
                @if($tipo === 'semana')
                    <input type="hidden" name="fecha_inicio" value="{{ $fecha_inicio }}">
                @elseif($tipo === 'mes')
                    <input type="hidden" name="mes" value="{{ $mes }}">
                    <input type="hidden" name="anio" value="{{ $anio }}">
                @else
                    <input type="hidden" name="anio" value="{{ $anio }}">
                @endif

                <!-- Horarios de Invierno (Septiembre - Junio) -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        ❄️ Horarios de Invierno
                        <span class="text-sm font-normal text-gray-500">(Septiembre - Junio)</span>
                    </h2>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Día</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Hora Inicio</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Hora Fin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @php
                                    $dias = [
                                        1 => 'Lunes',
                                        2 => 'Martes',
                                        3 => 'Miércoles',
                                        4 => 'Jueves',
                                        5 => 'Viernes',
                                        6 => 'Sábado',
                                        0 => 'Domingo'
                                    ];
                                    $horarioInvierno = $empleado->horario_invierno;
                                @endphp
                                @foreach($dias as $numDia => $nombreDia)
                                    @php
                                        $horario = null;
                                        if (is_array($horarioInvierno) && isset($horarioInvierno[$numDia])) {
                                            $horario = $horarioInvierno[$numDia];
                                        }
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-800">{{ $nombreDia }}</td>
                                        <td class="px-4 py-3">
                                            <input type="time" 
                                                   name="horario_invierno[{{ $numDia }}][inicio]" 
                                                   value="{{ $horario['inicio'] ?? '' }}"
                                                   class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="time" 
                                                   name="horario_invierno[{{ $numDia }}][fin]" 
                                                   value="{{ $horario['fin'] ?? '' }}"
                                                   class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Horarios de Verano (Julio - Agosto) -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        ☀️ Horarios de Verano
                        <span class="text-sm font-normal text-gray-500">(Julio - Agosto)</span>
                    </h2>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Día</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Hora Inicio</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Hora Fin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @php
                                    $horarioVerano = $empleado->horario_verano;
                                @endphp
                                @foreach($dias as $numDia => $nombreDia)
                                    @php
                                        $horario = null;
                                        if (is_array($horarioVerano) && isset($horarioVerano[$numDia])) {
                                            $horario = $horarioVerano[$numDia];
                                        }
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-800">{{ $nombreDia }}</td>
                                        <td class="px-4 py-3">
                                            <input type="time" 
                                                   name="horario_verano[{{ $numDia }}][inicio]" 
                                                   value="{{ $horario['inicio'] ?? '' }}"
                                                   class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="time" 
                                                   name="horario_verano[{{ $numDia }}][fin]" 
                                                   value="{{ $horario['fin'] ?? '' }}"
                                                   class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex gap-4 justify-end">
                    <a href="{{ route('horarios.index') }}" 
                       class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                        🎯 Guardar y Generar Horarios
                    </button>
                </div>

                <!-- Información adicional -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-900 mb-2">ℹ️ Información</h3>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>• Configura los horarios base que se usarán para generar automáticamente</li>
                        <li>• Deja los campos vacíos para los días no laborables</li>
                        <li>• Los horarios se guardarán en el perfil del empleado para futuras generaciones</li>
                        <li>• <strong>IMPORTANTE:</strong> La hora de fin debe ser mayor que la hora de inicio</li>
                    </ul>
                </div>
            </form>
            </div>
        </main>
    </div>
</div>
</body>
</html>
