<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Completo - {{ $cliente->user->nombre }}</title>
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
        .topbar-left { display:flex; align-items:center; gap:14px; }
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
            <a href="{{ route('deudas.index') }}" class="nav-item active"><span class="nav-icon">💰</span> Deudas</a>
            <a href="{{ route('bonos.index') }}" class="nav-item"><span class="nav-icon">🎫</span> Bonos</a>
            <a href="{{ route('bonos.clientesConBonos') }}" class="nav-item"><span class="nav-icon">👥</span> Clientes con Bonos</a>
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
        <div class="sidebar-help">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px"><span style="font-size:20px">❓</span><span style="font-weight:700;font-size:12px">¿Necesitas ayuda?</span></div>
            <p style="opacity:.85;font-size:11px;line-height:1.4">Consulta nuestra guía o contacta soporte</p>
        </div>
        <div class="sidebar-footer">© {{ date('Y') }} Salón de Belleza</div>
    </aside>

    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <a href="{{ route('deudas.index') }}" style="color:#1e1a4b;font-size:20px;text-decoration:none">←</a>
                <div>
                    <div class="topbar-title">📊 Historial de Deuda</div>
                    <div class="topbar-sub">{{ $cliente->user->nombre ?? '' }} {{ $cliente->user->apellidos ?? '' }}</div>
                </div>
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
    <div class="bg-white rounded-xl shadow-sm p-6" style="max-width:900px">

        <!-- Información del Cliente -->
        <div class="mb-6 p-4 bg-gray-50 rounded">
            <h2 class="text-xl font-semibold mb-2">Cliente</h2>
            <p><strong>Nombre:</strong> {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}</p>
            <p><strong>Deuda Actual:</strong> 
                <span class="text-xl font-bold {{ $deuda->saldo_pendiente > 0 ? 'text-red-600' : 'text-green-600' }}">
                    €{{ number_format($deuda->saldo_pendiente, 2) }}
                </span>
            </p>
        </div>

        <!-- Tabla de Movimientos -->
        <div class="mt-6">
            <table class="w-full table-auto text-sm text-left">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-2">#</th>
                        <th class="px-4 py-2">Fecha</th>
                        <th class="px-4 py-2">Tipo</th>
                        <th class="px-4 py-2">Monto</th>
                        <th class="px-4 py-2">Método</th>
                        <th class="px-4 py-2">Nota</th>
                        <th class="px-4 py-2">Usuario</th>
                        <th class="px-4 py-2">Saldo Resultante</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        // Usar saldo_pendiente como punto de partida para el saldo resultante
                        // saldo_pendiente refleja los abonos aplicados, a diferencia de saldo_total
                        // que solo se incrementa con cargos (historial contable acumulado)
                        $saldoActual = $deuda->saldo_pendiente;
                    @endphp
                    @forelse ($movimientos as $index => $movimiento)
                    @php
                        if ($movimiento->tipo === 'cargo') {
                            $saldoAnterior = $saldoActual - $movimiento->monto;
                        } else {
                            $saldoAnterior = $saldoActual + $movimiento->monto;
                        }
                    @endphp
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $movimientos->count() - $index }}</td>
                        <td class="px-4 py-2">
                            {{ $movimiento->created_at->format('d/m/Y') }}<br>
                            <span class="text-xs text-gray-500">{{ $movimiento->created_at->format('H:i') }}</span>
                        </td>
                        <td class="px-4 py-2">
                            @if($movimiento->tipo === 'cargo')
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-semibold">CARGO</span>
                            @else
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-semibold">PAGO</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @if($movimiento->tipo === 'cargo')
                                <span class="text-red-600 font-semibold">+€{{ number_format($movimiento->monto, 2) }}</span>
                            @else
                                <span class="text-green-600 font-semibold">-€{{ number_format($movimiento->monto, 2) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            {{ $movimiento->metodo_pago ? ucfirst($movimiento->metodo_pago) : '-' }}
                        </td>
                        <td class="px-4 py-2 max-w-xs truncate" title="{{ $movimiento->nota }}">
                            {{ $movimiento->nota ?? '-' }}
                        </td>
                        <td class="px-4 py-2">
                            {{ $movimiento->usuarioRegistro->nombre ?? '-' }}
                        </td>
                        <td class="px-4 py-2 font-semibold">
                            €{{ number_format($saldoActual, 2) }}
                        </td>
                    </tr>
                    @php
                        $saldoActual = $saldoAnterior;
                    @endphp
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            No hay movimientos registrados
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex gap-4">
            <a href="{{ route('deudas.show', $cliente) }}" 
               class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Volver al Detalle
            </a>
            <a href="{{ route('deudas.index') }}" class="px-4 py-2 rounded-lg text-white font-semibold" style="background:#1e1a4b;">← Volver a Deudas</a>
        </div>
    </div>
        </main>
    </div>
</div>
</body>
</html>
