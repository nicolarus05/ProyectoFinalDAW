<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pago - {{ $cliente->user->nombre }}</title>
    {!! vite_asset(['resources/css/app.css', 'resources/js/app.js']) !!}
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                    <div class="topbar-title">💵 Registrar Pago de Deuda</div>
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
    <div class="bg-white rounded-xl shadow-sm p-6" style="max-width:700px">

        <!-- Información del Cliente -->
        <div class="mb-6 p-4 bg-gray-50 rounded">
            <h2 class="text-xl font-semibold mb-2">Cliente</h2>
            <p><strong>Nombre:</strong> {{ $cliente->user->nombre }} {{ $cliente->user->apellidos }}</p>
            <p><strong>Deuda Pendiente:</strong> 
                <span class="text-2xl font-bold text-red-600">€{{ number_format($deuda->saldo_pendiente, 2) }}</span>
            </p>
        </div>

        <!-- Desglose de Distribución Automática -->
        <div id="distribucion-container" class="mb-6 p-4 bg-blue-50 rounded border-2 border-blue-200 hidden">
            <h3 class="text-lg font-semibold mb-3 text-blue-900 flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                </svg>
                Distribución Automática del Pago
            </h3>
            <p class="text-sm text-blue-800 mb-3">El pago se distribuirá automáticamente entre los empleados que realizaron los servicios:</p>
            <div id="distribucion-detalle" class="space-y-2">
                <!-- Se llenará dinámicamente con JavaScript -->
            </div>
        </div>

        <!-- Formulario de Pago -->
        <form action="{{ route('deudas.pago.store', $cliente) }}" method="POST" class="space-y-4">
            @csrf

            <!-- Monto -->
            <div>
                <label for="monto" class="block text-sm font-medium text-gray-700 mb-1">
                    Monto del Pago <span class="text-red-500">*</span>
                </label>
                <input type="number" 
                       name="monto" 
                       id="monto" 
                       step="0.01" 
                       min="0.01" 
                       max="{{ $deuda->saldo_pendiente }}"
                       value="{{ old('monto') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required>
                @error('monto')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-sm text-gray-500 mt-1">
                    Máximo: €{{ number_format($deuda->saldo_pendiente, 2) }}
                </p>
            </div>

            <!-- Método de Pago -->
            <div>
                <label for="metodo_pago" class="block text-sm font-medium text-gray-700 mb-1">
                    Método de Pago <span class="text-red-500">*</span>
                </label>
                <select name="metodo_pago" 
                        id="metodo_pago" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                    <option value="">Seleccione un método</option>
                    <option value="efectivo" {{ old('metodo_pago') === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                    <option value="tarjeta" {{ old('metodo_pago') === 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                    <option value="transferencia" {{ old('metodo_pago') === 'transferencia' ? 'selected' : '' }}>Transferencia</option>
                </select>
                @error('metodo_pago')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Empleado (solo si no hay cobro original) -->
            <div id="empleado-selector-container">
                <label for="empleado_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Empleado <span class="text-red-500" id="empleado-required">*</span>
                </label>
                <select name="empleado_id" 
                        id="empleado_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($empleados as $empleado)
                        <option value="{{ $empleado->id }}" 
                                {{ (old('empleado_id', $empleadoPreseleccionado) == $empleado->id) ? 'selected' : '' }}>
                            {{ $empleado->user->nombre }} {{ $empleado->user->apellidos ?? '' }}
                        </option>
                    @endforeach
                </select>
                @error('empleado_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-sm text-gray-500 mt-1" id="empleado-help">
                    <i class="fas fa-info-circle"></i> Seleccione el empleado que recibirá el pago
                </p>
            </div>

            <!-- Nota (Opcional) -->
            <div>
                <label for="nota" class="block text-sm font-medium text-gray-700 mb-1">
                    Nota (Opcional)
                </label>
                <textarea name="nota" 
                          id="nota" 
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Ej: Pago parcial, acordado plazo, etc.">{{ old('nota') }}</textarea>
                @error('nota')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Botones de Acción -->
            <div class="flex gap-4 mt-6">
                <button type="submit" 
                        class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                    Registrar Pago
                </button>
                <a href="{{ route('deudas.show', $cliente) }}" 
                   class="bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-700 inline-block">
                    Cancelar
                </a>
            </div>
        </form>

        <!-- Botones Rápidos -->
        <div class="mt-6 p-4 bg-blue-50 rounded">
            <p class="text-sm font-medium text-gray-700 mb-2">Acciones Rápidas:</p>
            <div class="flex gap-2 flex-wrap">
                <button type="button" 
                        onclick="setMontoYActualizar('{{ $deuda->saldo_pendiente }}')"
                        class="bg-blue-600 text-white px-4 py-1 rounded text-sm hover:bg-blue-700">
                    Pagar Todo (€{{ number_format($deuda->saldo_pendiente, 2) }})
                </button>
                <button type="button" 
                        onclick="setMontoYActualizar('{{ number_format($deuda->saldo_pendiente / 2, 2) }}')"
                        class="bg-blue-600 text-white px-4 py-1 rounded text-sm hover:bg-blue-700">
                    Pagar Mitad (€{{ number_format($deuda->saldo_pendiente / 2, 2) }})
                </button>
            </div>
        </div>
    </div>

    <script>
        let distribucionData = null;
        
        // Cargar distribución al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            cargarDistribucion();
            
            // Escuchar cambios en el monto para actualizar porcentajes
            document.getElementById('monto').addEventListener('input', function() {
                actualizarPorcentajesDistribucion();
            });
        });

        // Función para cargar la distribución del servidor
        function cargarDistribucion() {
            const url = '{{ route("deudas.distribucion", $cliente) }}';
            
            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                distribucionData = data;
                mostrarDistribucion(data);
            })
            .catch(error => {
                console.error('Error al cargar distribución:', error);
            });
        }

        // Función para mostrar la distribución en la interfaz
        function mostrarDistribucion(data) {
            const container = document.getElementById('distribucion-container');
            const detalleDiv = document.getElementById('distribucion-detalle');
            const empleadoContainer = document.getElementById('empleado-selector-container');
            const empleadoSelect = document.getElementById('empleado_id');
            const empleadoRequired = document.getElementById('empleado-required');
            const empleadoHelp = document.getElementById('empleado-help');

            if (!data.tiene_cobro_original) {
                // No hay cobro original - mostrar selector de empleado
                empleadoContainer.style.display = 'block';
                empleadoSelect.required = true;
                empleadoHelp.innerHTML = '<i class="fas fa-exclamation-circle"></i> <strong>Atención:</strong> No hay servicios asociados a esta deuda. Seleccione manualmente el empleado.';
                empleadoHelp.classList.add('text-yellow-600');
                empleadoHelp.classList.remove('text-gray-500');
                container.classList.add('hidden');
                return;
            }

            // Hay cobro original - ocultar selector y mostrar distribución
            empleadoContainer.style.display = 'none';
            empleadoSelect.required = false;
            container.classList.remove('hidden');

            // Construir el desglose visual
            let html = '';
            
            if (data.empleados && data.empleados.length > 0) {
                data.empleados.forEach((emp, index) => {
                    const porcentaje = ((emp.monto_original / data.total_original) * 100).toFixed(1);
                    const esPrincipal = data.empleado_principal_id === emp.empleado_id;
                    
                    html += `
                        <div class="flex items-center justify-between p-3 bg-white rounded border ${esPrincipal ? 'border-blue-500 border-2' : 'border-gray-200'}">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-800">${emp.nombre}</span>
                                    ${esPrincipal ? '<span class="text-xs bg-blue-500 text-white px-2 py-0.5 rounded">PRINCIPAL</span>' : ''}
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    ${emp.servicios.length} servicio${emp.servicios.length !== 1 ? 's' : ''}
                                </div>
                                <div class="text-xs text-gray-600 mt-1">
                                    ${emp.servicios.map(s => s.nombre).join(', ')}
                                </div>
                            </div>
                            <div class="text-right ml-4">
                                <div class="text-lg font-bold text-green-600">
                                    €<span class="monto-empleado" data-monto-original="${emp.monto_original}">
                                        ${emp.monto_original.toFixed(2)}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    ${porcentaje}% del total
                                </div>
                            </div>
                        </div>
                    `;
                });

                // Agregar total
                html += `
                    <div class="flex items-center justify-between p-3 bg-gray-100 rounded border-2 border-gray-300 font-bold mt-2">
                        <span class="text-gray-800">TOTAL:</span>
                        <span class="text-xl text-blue-800">
                            €<span id="monto-total-distribucion">${data.total_original.toFixed(2)}</span>
                        </span>
                    </div>
                `;

                if (data.empleados.length > 1) {
                    html += `
                        <p class="text-xs text-blue-700 mt-2 italic">
                            <i class="fas fa-info-circle"></i> 
                            El cobro se registrará a nombre de <strong>${data.empleados.find(e => e.empleado_id === data.empleado_principal_id)?.nombre}</strong> 
                            pero cada empleado recibirá su proporción en la facturación.
                        </p>
                    `;
                }
            } else {
                html = '<p class="text-gray-600 text-sm">No hay distribución disponible</p>';
            }

            detalleDiv.innerHTML = html;
        }

        // Función para actualizar los porcentajes cuando cambia el monto del pago
        function actualizarPorcentajesDistribucion() {
            if (!distribucionData || !distribucionData.tiene_cobro_original) return;

            const montoPago = parseFloat(document.getElementById('monto').value) || 0;
            const totalOriginal = distribucionData.total_original;
            const proporcion = montoPago / totalOriginal;

            // Actualizar cada monto de empleado
            document.querySelectorAll('.monto-empleado').forEach(span => {
                const montoOriginal = parseFloat(span.dataset.montoOriginal);
                const montoNuevo = montoOriginal * proporcion;
                span.textContent = montoNuevo.toFixed(2);
            });

            // Actualizar total
            const totalSpan = document.getElementById('monto-total-distribucion');
            if (totalSpan) {
                totalSpan.textContent = montoPago.toFixed(2);
            }
        }

        // Función auxiliar para botones rápidos
        function setMontoYActualizar(valor) {
            document.getElementById('monto').value = valor;
            actualizarPorcentajesDistribucion();
        }
    </script>
    </div>
        </main>
    </div>
</div>
</body>
</html>
