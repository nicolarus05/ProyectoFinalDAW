<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Deuda - {{ $cliente->user->nombre }}</title>
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
                    <div class="topbar-title">💰 Detalle de Deuda</div>
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
        
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('info'))
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                {{ session('info') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Información del Cliente -->
        <div class="mb-6 p-4 bg-gray-50 rounded">
            <h2 class="text-xl font-semibold mb-2">Información del Cliente</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p><strong>Nombre:</strong> {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}</p>
                    <p><strong>Teléfono:</strong> {{ $cliente->user->telefono }}</p>
                </div>
                <div>
                    <p><strong>Email:</strong> {{ $cliente->user->email }}</p>
                    
                </div>
            </div>
        </div>

        <!-- Resumen de Deuda -->
        <div class="mb-6 p-4 bg-red-50 rounded border border-red-200">
            <h2 class="text-xl font-semibold mb-2">Resumen de Deuda</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Deuda Histórica Acumulada</p>
                    <p class="text-2xl font-bold text-gray-800">€{{ number_format($deuda->saldo_total, 2) }}</p>
                    <p class="text-xs text-gray-400">Total de cargos registrados</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Deuda Pendiente</p>
                    <p class="text-3xl font-bold text-red-600">€{{ number_format($deuda->saldo_pendiente, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        @if($deuda->tieneDeuda())
        <div class="mb-6">
            <a href="{{ route('deudas.pago.create', $cliente) }}" 
               class="bg-green-600 text-white px-6 py-3 rounded hover:bg-green-700 inline-block">
                Registrar Pago
            </a>
        </div>
        @else
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded">
            <p class="text-green-700 font-semibold">✓ Este cliente no tiene deudas pendientes</p>
        </div>
        @endif

        <!-- Historial de Movimientos -->
        <div class="mt-6">
            <h2 class="text-xl font-semibold mb-4">Historial de Movimientos</h2>
            <table class="w-full table-auto text-sm text-left">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-2">Fecha</th>
                        <th class="px-4 py-2">Tipo</th>
                        <th class="px-4 py-2">Deuda</th>
                        <th class="px-4 py-2">Método Pago</th>
                        <th class="px-4 py-2">Detalle</th>
                        <th class="px-4 py-2">Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movimientos as $movimiento)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $movimiento->created_at->format('d/m/Y H:i') }}</td>
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
                        <td class="px-4 py-2">
                            @if($movimiento->tipo === 'cargo' && $movimiento->registroCobro)
                                {{-- Mostrar Servicios con sistema de prioridades --}}
                                @php
                                    $cobro = $movimiento->registroCobro;
                                    $servicios = collect();
                                    $yaContados = false;
                                    
                                    // PRIORIDAD 1: Servicios de cita individual
                                    if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
                                        $servicios = $cobro->cita->servicios;
                                        $yaContados = true;
                                    }
                                    
                                    // PRIORIDAD 2: Servicios de citas agrupadas
                                    if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
                                        $serviciosTemp = collect();
                                        foreach ($cobro->citasAgrupadas as $citaGrupo) {
                                            if ($citaGrupo->servicios) {
                                                $serviciosTemp = $serviciosTemp->merge($citaGrupo->servicios);
                                            }
                                        }
                                        $servicios = $serviciosTemp;
                                        $yaContados = true;
                                    }
                                    
                                    // PRIORIDAD 3: Servicios directos
                                    if (!$yaContados && $cobro->servicios && $cobro->servicios->count() > 0) {
                                        $servicios = $cobro->servicios;
                                    }
                                    
                                    $productos = $cobro->productos ?? collect();
                                @endphp
                                
                                @if($servicios->isNotEmpty())
                                    <div class="mb-1">
                                        <span class="font-semibold text-blue-700">Servicios:</span>
                                        <ul class="list-disc list-inside text-sm">
                                            @foreach($servicios as $servicio)
                                                <li>{{ $servicio->nombre }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if($productos->isNotEmpty())
                                    <div>
                                        <span class="font-semibold text-green-700">Productos:</span>
                                        <ul class="list-disc list-inside text-sm">
                                            @foreach($productos as $producto)
                                                <li>{{ $producto->nombre }} (x{{ $producto->pivot->cantidad }})</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if($servicios->isEmpty() && $productos->isEmpty())
                                    <span class="text-gray-500 italic">{{ $movimiento->nota ?? 'Cargo a deuda' }}</span>
                                @endif
                            @else
                                <span class="text-gray-600">{{ $movimiento->nota ?? 'Pago de deuda' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            {{ $movimiento->usuarioRegistro->nombre ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            No hay movimientos registrados
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Paginación -->
            <div class="mt-4">
                {{ $movimientos->links() }}
            </div>
        </div>

        <div class="mt-6">
            <a href="{{ route('deudas.index') }}" class="px-4 py-2 rounded-lg text-white font-semibold" style="background:#1e1a4b;">← Volver a Deudas</a>
        </div>
    </div>
        </main>
    </div>
</div>
</body>
</html>
