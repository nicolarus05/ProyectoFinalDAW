<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Deudas</title>
    @vite(['resources/js/app.js'])
    <style>
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <div class="w-full max-w-7xl mx-auto bg-white shadow-md rounded p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">💰 Gestión de Deudas</h1>
            <div class="flex gap-2">
                <button onclick="abrirModalPago()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    💵 Registrar Pago Rápido
                </button>
                <a href="{{ route('dashboard') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    ← Volver al Dashboard
                </a>
            </div>
        </div>
        
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
                                    <img src="{{ asset('storage/' . $cliente->user->foto_perfil) }}" 
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

    <script>
        function filtrarClientes() {
            const busqueda = document.getElementById('buscar').value.toLowerCase();
            const filas = document.querySelectorAll('.fila-cliente');
            let visibles = 0;

            filas.forEach(fila => {
                const nombre = fila.dataset.nombre;
                const telefono = fila.dataset.telefono;
                const email = fila.dataset.email;

                if (nombre.includes(busqueda) || telefono.includes(busqueda) || email.includes(busqueda)) {
                    fila.style.display = '';
                    visibles++;
                } else {
                    fila.style.display = 'none';
                }
            });

            document.getElementById('sin-coincidencias').classList.toggle('hidden', visibles > 0);
        }

        function ordenarClientes() {
            const orden = document.getElementById('ordenar').value;
            const tbody = document.querySelector('#tabla-deudas tbody');
            const filas = Array.from(document.querySelectorAll('.fila-cliente'));

            filas.sort((a, b) => {
                switch(orden) {
                    case 'deuda-desc':
                        return parseFloat(b.dataset.deuda) - parseFloat(a.dataset.deuda);
                    case 'deuda-asc':
                        return parseFloat(a.dataset.deuda) - parseFloat(b.dataset.deuda);
                    case 'nombre-asc':
                        return a.dataset.nombre.localeCompare(b.dataset.nombre);
                    case 'nombre-desc':
                        return b.dataset.nombre.localeCompare(a.dataset.nombre);
                }
            });

            filas.forEach(fila => tbody.appendChild(fila));
        }

        function abrirModalPago() {
            document.getElementById('modal-pago-rapido').classList.remove('hidden');
        }

        function cerrarModalPago() {
            document.getElementById('modal-pago-rapido').classList.add('hidden');
            document.getElementById('form-pago-rapido').reset();
        }

        async function registrarPagoRapido(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            const idCliente = formData.get('id_cliente');
            
            if (!idCliente) {
                alert('Por favor selecciona un cliente');
                return;
            }

            try {
                const response = await fetch(`/deudas/cliente/${idCliente}/pago`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        monto: formData.get('monto'),
                        metodo_pago: formData.get('metodo_pago'),
                        nota: formData.get('nota')
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Pago registrado exitosamente');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo registrar el pago'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al procesar el pago');
            }
        }
    </script>

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
</body>
</html>