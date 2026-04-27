<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gestión de Deudas</title>
    {!! vite_asset(['resources/css/app.css', 'resources/css/deudas.css', 'resources/js/deudas.js', 'resources/js/app.js']) !!}
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
            <div class="topbar-title">💰 Gestión de Deudas</div>
            <div style="display:flex;align-items:center;gap:10px">
                <button onclick="abrirModalPago()" style="background:linear-gradient(135deg,#f472b6,#a855f7);color:#fff;padding:7px 16px;border-radius:8px;border:none;cursor:pointer;font-size:13px;font-weight:600">💵 Pago Rápido</button>
                <a href="{{ route('profile.edit') }}" class="user-badge">
                    <div class="user-avatar">{{ strtoupper(substr($user->nombre ?? 'U', 0, 1)) }}</div>
                    <div style="display:flex;flex-direction:column">
                        <span style="font-weight:600;font-size:13px">{{ $user->nombre ?? '' }} {{ $user->apellidos ?? '' }}</span>
                        <span style="font-size:11px;color:#888;text-transform:capitalize">{{ $rol }}</span>
                    </div>
                </a>
            </div>
        </header>
        <main class="main-content">
    <div id="deudas-app">
        
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                ✓ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                ✗ {{ session('error') }}
            </div>
        @endif

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="stat-card bg-red-50 border-2 border-red-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Deuda Total</p>
                        <p class="text-3xl font-bold text-red-600">€{{ number_format($totalDeuda, 2) }}</p>
                    </div>
                    <div class="text-4xl">💸</div>
                </div>
                <p class="text-xs text-gray-500 mt-2">Suma de todas las deudas pendientes</p>
            </div>

            <div class="stat-card bg-blue-50 border-2 border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Clientes con Deuda</p>
                        <p class="text-3xl font-bold text-blue-600">{{ $clientes->count() }}</p>
                    </div>
                    <div class="text-4xl">👥</div>
                </div>
                <p class="text-xs text-gray-500 mt-2">Total de clientes que deben</p>
            </div>
        </div>

        <!-- Buscador y Filtros -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex gap-4 items-end">
                <div class="flex-1">
                    <label for="buscar" class="block text-sm font-medium text-gray-700 mb-1">Buscar Cliente</label>
                    <input type="text" 
                           id="buscar" 
                           placeholder="Nombre, teléfono o email..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           onkeyup="filtrarClientes()">
                </div>
                <div>
                    <label for="ordenar" class="block text-sm font-medium text-gray-700 mb-1">Ordenar por</label>
                    <select id="ordenar" 
                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onchange="ordenarClientes()">
                        <option value="deuda-desc">Mayor Deuda</option>
                        <option value="deuda-asc">Menor Deuda</option>
                        <option value="nombre-asc">Nombre A-Z</option>
                        <option value="nombre-desc">Nombre Z-A</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla de Clientes -->
        <div class="overflow-x-auto">
            <table class="w-full table-auto text-sm text-left" id="tabla-deudas">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Contacto</th>
                        <th class="px-4 py-3">Deuda Total Histórica</th>
                        <th class="px-4 py-3">Deuda Pendiente</th>
                        <th class="px-4 py-3">% Pagado</th>
                        <th class="px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clientes as $index => $cliente)
                    <tr class="border-t hover:bg-gray-50 fila-cliente" 
                        data-nombre="{{ strtolower($cliente->user->nombre ?? '') }} {{ strtolower($cliente->user->apellidos ?? '') }}"
                        data-telefono="{{ $cliente->user->telefono ?? '' }}"
                        data-email="{{ strtolower($cliente->user->email ?? '') }}"
                        data-deuda="{{ $cliente->deuda->saldo_pendiente }}">
                        <td class="px-4 py-3 text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if($cliente->user->foto_perfil)
                                    <img src="{{ route('tenant.file', $cliente->user->foto_perfil) }}" loading="lazy" 
                                         class="w-8 h-8 rounded-full object-cover">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-xs">
                                        {{ substr($cliente->user->nombre ?? 'U', 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="font-semibold">{{ $cliente->user->nombre ?? '-' }} {{ $cliente->user->apellidos ?? '-' }}</p>
                                    <p class="text-xs text-gray-500">Cliente #{{ $cliente->id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm">📞 {{ $cliente->user->telefono ?? '-' }}</p>
                            <p class="text-xs text-gray-500">✉️ {{ $cliente->user->email ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-gray-600">€{{ number_format($cliente->deuda->saldo_total, 2) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-bold text-red-600 text-lg">€{{ number_format($cliente->deuda->saldo_pendiente, 2) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $porcentajePagado = $cliente->deuda->saldo_total > 0 
                                    ? (($cliente->deuda->saldo_total - $cliente->deuda->saldo_pendiente) / $cliente->deuda->saldo_total) * 100 
                                    : 0;
                            @endphp
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $porcentajePagado }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ number_format($porcentajePagado, 1) }}%</p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-1">
                                <a href="{{ route('deudas.show', $cliente) }}" 
                                   class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 text-xs"
                                   title="Ver Detalle">
                                    👁️ Ver
                                </a>
                                <a href="{{ route('deudas.pago.create', $cliente) }}" 
                                   class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-xs"
                                   title="Registrar Pago">
                                    💵 Pagar
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr id="sin-resultados">
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <div class="text-6xl">🎉</div>
                                <p class="text-lg font-semibold">¡Excelente!</p>
                                <p>No hay clientes con deudas pendientes</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="sin-coincidencias" class="hidden text-center py-8 text-gray-500">
            <p class="text-lg">No se encontraron clientes que coincidan con la búsqueda</p>
        </div>
    </div>

    <!-- Modal Pago Rápido -->
    <div id="modal-pago-rapido" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">💵 Registrar Pago Rápido</h3>
                <button onclick="cerrarModalPago()" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="form-pago-rapido" onsubmit="registrarPagoRapido(event)">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Cliente *</label>
                    <select name="id_cliente" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar cliente...</option>
                        @foreach($clientes as $cliente)
                            @if($cliente->deuda && $cliente->deuda->tieneDeuda())
                            <option value="{{ $cliente->id_cliente }}">
                                {{ $cliente->nombre_completo }} - Deuda: €{{ number_format($cliente->deuda->saldo_pendiente, 2) }}
                            </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Monto a Pagar *</label>
                    <input type="number" name="monto" step="0.01" min="0.01" required 
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="0.00">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Método de Pago *</label>
                    <select name="metodo_pago" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar...</option>
                        <option value="efectivo">💵 Efectivo</option>
                        <option value="tarjeta">💳 Tarjeta</option>
                        <option value="transferencia">🏦 Transferencia</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nota (Opcional)</label>
                    <textarea name="nota" rows="2" 
                              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Información adicional..."></textarea>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="cerrarModalPago()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        💰 Registrar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
        </main>
    </div>
</div>
</body>
</html>