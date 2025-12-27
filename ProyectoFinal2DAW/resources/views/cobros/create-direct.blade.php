<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Cobro Directo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .modal-backdrop { background: rgba(0,0,0,0.5); }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .animate-fade-in {
            animation: fadeIn 0.2s ease-out;
        }
        
        /* Estilos para badges de bonos */
        .badge-bono-verde {
            background-color: #10b981;
            color: white;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .badge-bono-amarillo {
            background-color: #f59e0b;
            color: white;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .badge-bono-rojo {
            background-color: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .7; }
        }
        
        .bono-card {
            background: white;
            border: 2px solid #a78bfa;
            border-radius: 8px;
            padding: 12px;
            transition: all 0.2s;
        }
        
        .bono-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .bono-servicio-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">💰 Nuevo Cobro Directo</h1>
            <a href="{{ route('cobros.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">← Volver a cobros</a>
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
            <p class="text-sm text-blue-800">
                <strong>Cobro directo:</strong> Usa esta opción para registrar ventas de productos o servicios sin necesidad de tener una cita programada.
            </p>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                <strong>Errores encontrados:</strong>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('cobros.store') }}" method="POST" id="cobro-form" class="space-y-6">
            @csrf

            <!-- Detectar si viene UNA cita o MÚLTIPLES citas -->
            @if(isset($citas) && $citas->count() > 1)
                {{-- COBRO AGRUPADO: Múltiples citas del mismo cliente y día --}}
                @foreach($citas as $citaItem)
                    <input type="hidden" name="citas_ids[]" value="{{ $citaItem->id }}">
                @endforeach
                
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                    <p class="text-lg text-blue-900 font-semibold mb-2">
                        🎉 <strong>Cobro Agrupado:</strong> {{ $citas->count() }} citas del mismo día
                    </p>
                    <p class="text-sm text-blue-800">
                        Cliente: <strong>{{ $citas->first()->cliente->user->nombre ?? '' }} {{ $citas->first()->cliente->user->apellidos ?? '' }}</strong><br>
                        Fecha: <strong>{{ \Carbon\Carbon::parse($citas->first()->fecha_hora)->format('d/m/Y') }}</strong>
                    </p>
                    <div class="mt-3 text-xs text-blue-700">
                        <strong>Citas incluidas:</strong>
                        <ul class="list-disc list-inside mt-1">
                            @foreach($citas as $citaItem)
                                <li>{{ \Carbon\Carbon::parse($citaItem->fecha_hora)->format('H:i') }} - {{ $citaItem->servicios->pluck('nombre')->implode(', ') }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                
            @elseif(isset($cita))
                {{-- COBRO INDIVIDUAL: Una sola cita --}}
                <input type="hidden" name="id_cita" value="{{ $cita->id }}">
                
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                    <p class="text-sm text-green-800">
                        <strong>✓ Cita completada:</strong> Registrando cobro para la cita de <strong>{{ $cita->cliente->user->nombre ?? '' }} {{ $cita->cliente->user->apellidos ?? '' }}</strong>
                    </p>
                </div>
            @endif

            <!-- Cliente -->
            <div class="bg-gray-50 p-4 rounded">
                <h2 class="text-lg font-semibold mb-3">Cliente</h2>
                <div>
                    <label for="buscar-cliente" class="block font-semibold mb-1">🔍 Buscar cliente:</label>
                    <input type="text" id="buscar-cliente" class="w-full border rounded px-3 py-2 mb-2" placeholder="Escribe nombre o apellido..." oninput="filtrarClientes()">
                    
                    <label for="id_cliente" class="block font-semibold mb-1">Seleccionar cliente:</label>
                    <select name="id_cliente" id="id_cliente" class="w-full border rounded px-3 py-2" onchange="actualizarDeudaCliente(); mostrarPanelBonos()">
                        <option value="">-- Sin cliente --</option>
                        @foreach($clientes as $cliente)
                            @php
                                $deuda = $cliente->deuda ? $cliente->deuda->saldo_pendiente : 0;
                                // Pre-seleccionar cliente si viene de cita individual o de citas agrupadas
                                $selected = '';
                                if (isset($cita) && $cita->id_cliente == $cliente->id) {
                                    $selected = 'selected';
                                } elseif (isset($citas) && $citas->count() > 0 && $citas->first()->id_cliente == $cliente->id) {
                                    $selected = 'selected';
                                }
                            @endphp
                            <option value="{{ $cliente->id }}" data-deuda="{{ $deuda }}" data-nombre="{{ strtolower(($cliente->user->nombre ?? '') . ' ' . ($cliente->user->apellidos ?? '')) }}" {{ $selected }}>
                                {{ $cliente->user->nombre ?? '' }} {{ $cliente->user->apellidos ?? '' }}
                                @if($deuda > 0) (Deuda: €{{ number_format($deuda, 2) }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <p id="deuda-info" class="text-sm text-gray-600 mt-2"></p>
                
                <!-- Panel informativo de bonos del cliente -->
                <div id="panel-bonos-cliente" class="mt-4 bg-gradient-to-r from-purple-50 to-blue-50 border-2 border-purple-300 rounded-lg p-4 shadow-md" style="{{ (isset($cita) && $cita->cliente->bonos->count() > 0) || (isset($citas) && $citas->count() > 0 && $citas->first()->cliente->bonos->count() > 0) ? '' : 'display: none;' }}">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-bold text-purple-800 flex items-center">
                            <span class="text-2xl mr-2">🎫</span>
                            Bonos Activos del Cliente
                        </h3>
                        <button type="button" onclick="document.getElementById('panel-bonos-cliente').style.display='none'" class="text-gray-500 hover:text-gray-700">
                            ✕
                        </button>
                    </div>
                    <div id="lista-bonos-cliente" class="space-y-3">
                        @if(isset($cita) && $cita->cliente->bonos)
                            @foreach($cita->cliente->bonos as $bono)
                                @php
                                    // Verificar si el bono tiene al menos un servicio disponible
                                    $tieneServiciosDisponibles = false;
                                    if ($bono->servicios && $bono->servicios->count() > 0) {
                                        foreach ($bono->servicios as $servicio) {
                                            $disponible = $servicio->pivot->cantidad_total - $servicio->pivot->cantidad_usada;
                                            if ($disponible > 0) {
                                                $tieneServiciosDisponibles = true;
                                                break;
                                            }
                                        }
                                    }
                                @endphp
                                
                                @if($tieneServiciosDisponibles)
                                    <div class="bg-white border-l-4 border-purple-500 rounded-lg p-3 shadow-sm">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <p class="font-bold text-purple-700 mb-1">{{ $bono->plantilla->nombre ?? $bono->nombre }}</p>
                                                @if($bono->servicios && $bono->servicios->count() > 0)
                                                    <div class="space-y-1 mt-2 text-sm text-gray-600">
                                                        @foreach($bono->servicios as $servicio)
                                                            @php
                                                                $cantidadTotal = $servicio->pivot->cantidad_total;
                                                                $cantidadUsada = $servicio->pivot->cantidad_usada;
                                                                $cantidadDisponible = $cantidadTotal - $cantidadUsada;
                                                                $colorTexto = $cantidadDisponible > 2 ? 'text-green-600' : ($cantidadDisponible > 0 ? 'text-yellow-600' : 'text-gray-400');
                                                            @endphp
                                                            @if($cantidadDisponible > 0)
                                                                <div class="flex items-center justify-between">
                                                                    <span>• {{ $servicio->nombre }}</span>
                                                                    <span class="font-semibold {{ $colorTexto }}">
                                                                        {{ $cantidadDisponible }}/{{ $cantidadTotal }} disponibles
                                                                    </span>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @elseif(isset($citas) && $citas->count() > 0 && $citas->first()->cliente->bonos)
                            @foreach($citas->first()->cliente->bonos as $bono)
                                @php
                                    // Verificar si el bono tiene al menos un servicio disponible
                                    $tieneServiciosDisponibles = false;
                                    if ($bono->servicios && $bono->servicios->count() > 0) {
                                        foreach ($bono->servicios as $servicio) {
                                            $disponible = $servicio->pivot->cantidad_total - $servicio->pivot->cantidad_usada;
                                            if ($disponible > 0) {
                                                $tieneServiciosDisponibles = true;
                                                break;
                                            }
                                        }
                                    }
                                @endphp
                                
                                @if($tieneServiciosDisponibles)
                                    <div class="bg-white border-l-4 border-purple-500 rounded-lg p-3 shadow-sm">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <p class="font-bold text-purple-700 mb-1">{{ $bono->plantilla->nombre ?? $bono->nombre }}</p>
                                                @if($bono->servicios && $bono->servicios->count() > 0)
                                                    <div class="space-y-1 mt-2 text-sm text-gray-600">
                                                        @foreach($bono->servicios as $servicio)
                                                            @php
                                                                $cantidadTotal = $servicio->pivot->cantidad_total;
                                                                $cantidadUsada = $servicio->pivot->cantidad_usada;
                                                                $cantidadDisponible = $cantidadTotal - $cantidadUsada;
                                                                $colorTexto = $cantidadDisponible > 2 ? 'text-green-600' : ($cantidadDisponible > 0 ? 'text-yellow-600' : 'text-gray-400');
                                                            @endphp
                                                            @if($cantidadDisponible > 0)
                                                                <div class="flex items-center justify-between">
                                                                    <span>• {{ $servicio->nombre }}</span>
                                                                    <span class="font-semibold {{ $colorTexto }}">
                                                                        {{ $cantidadDisponible }}/{{ $cantidadTotal }} disponibles
                                                                    </span>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <!-- Empleado -->
            <div class="bg-gray-50 p-4 rounded">
                <h2 class="text-lg font-semibold mb-3">Empleado que atiende</h2>
                @if(isset($citas) && $citas->count() > 1)
                    {{-- Si hay múltiples citas, mostrar todos los empleados involucrados --}}
                    <div class="bg-blue-50 p-3 rounded border border-blue-200">
                        <p class="text-sm text-blue-800 mb-2"><strong>Empleados de las citas:</strong></p>
                        <ul class="list-disc list-inside text-sm text-blue-700">
                            @foreach($citas->unique('id_empleado') as $citaItem)
                                <li>{{ $citaItem->empleado->user->nombre ?? '' }} {{ $citaItem->empleado->user->apellidos ?? '' }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <select name="id_empleado" id="id_empleado" class="w-full border rounded px-3 py-2 mt-3" required>
                        <option value="">-- Seleccionar empleado principal --</option>
                        @foreach($empleados as $empleado)
                            @php
                                // Pre-seleccionar el primer empleado de las citas
                                $selected = isset($citas) && $citas->first()->id_empleado == $empleado->id ? 'selected' : '';
                            @endphp
                            <option value="{{ $empleado->id }}" {{ $selected }}>
                                {{ $empleado->user->nombre ?? '' }} {{ $empleado->user->apellidos ?? '' }}
                            </option>
                        @endforeach
                    </select>
                @else
                    {{-- Una sola cita o sin cita --}}
                    <div>
                        <label for="id_empleado" class="block font-semibold mb-1">Seleccionar empleado: <span class="text-red-500">*</span></label>
                        <select name="id_empleado" id="id_empleado" class="w-full border rounded px-3 py-2" required>
                            <option value="">-- Seleccionar empleado --</option>
                            @foreach($empleados as $empleado)
                                @php
                                    $selected = isset($cita) && $cita->id_empleado == $empleado->id ? 'selected' : '';
                                @endphp
                                <option value="{{ $empleado->id }}" {{ $selected }}>
                                    {{ $empleado->user->nombre ?? '' }} {{ $empleado->user->apellidos ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <!-- Servicios -->
            <div class="bg-gray-50 p-4 rounded">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-lg font-semibold">Servicios</h2>
                    <button type="button" id="btn-add-service" class="bg-purple-600 text-white px-3 py-1 rounded hover:bg-purple-700 text-sm">
                        + Añadir servicio
                    </button>
                </div>
                <table class="w-full text-sm" id="services-table">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="p-2 text-left">Servicio</th>
                            <th class="p-2 text-left">Empleado</th>
                            <th class="p-2 text-right">Precio</th>
                            <th class="p-2"></th>
                        </tr>
                    </thead>
                    <tbody id="services-tbody">
                        <!-- Servicios añadidos dinámicamente -->
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-300">
                            <td colspan="2" class="p-2 text-right font-semibold">Total servicios:</td>
                            <td id="services-total" class="p-2 text-right font-semibold">€0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Productos -->
            <div class="bg-gray-50 p-4 rounded">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-lg font-semibold">Productos</h2>
                    <button type="button" id="btn-add-product" class="bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700 text-sm">
                        + Añadir producto
                    </button>
                </div>
                <table class="w-full text-sm" id="products-table">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="p-2 text-left">Producto</th>
                            <th class="p-2 text-center">Cantidad</th>
                            <th class="p-2 text-right">Precio unit.</th>
                            <th class="p-2 text-right">Subtotal</th>
                            <th class="p-2"></th>
                        </tr>
                    </thead>
                    <tbody id="products-tbody">
                        <!-- Productos añadidos dinámicamente -->
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-300">
                            <td colspan="3" class="p-2 text-right font-semibold">Total productos:</td>
                            <td id="products-total" class="p-2 text-right font-semibold">€0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Descuentos para Servicios -->
            <div class="bg-blue-50 border border-blue-200 rounded p-4">
                <h3 class="font-semibold mb-3 text-blue-800">💇 Descuentos para Servicios</h3>
                <div class="flex space-x-4">
                    <div class="flex-1">
                        <label for="descuento_servicios_porcentaje" class="block font-semibold mb-1">Descuento %:</label>
                        <input type="number" name="descuento_servicios_porcentaje" id="descuento_servicios_porcentaje" class="w-full border rounded px-3 py-2" step="0.01" value="0" min="0" max="100" oninput="calcularTotales()">
                    </div>
                    <div class="flex-1">
                        <label for="descuento_servicios_euro" class="block font-semibold mb-1">Descuento €:</label>
                        <input type="number" name="descuento_servicios_euro" id="descuento_servicios_euro" class="w-full border rounded px-3 py-2" step="0.01" value="0" min="0" oninput="calcularTotales()">
                    </div>
                </div>
            </div>

            <!-- Descuentos para Productos -->
            <div class="bg-green-50 border border-green-200 rounded p-4 mt-3">
                <h3 class="font-semibold mb-3 text-green-800">🛍️ Descuentos para Productos</h3>
                <div class="flex space-x-4">
                    <div class="flex-1">
                        <label for="descuento_productos_porcentaje" class="block font-semibold mb-1">Descuento %:</label>
                        <input type="number" name="descuento_productos_porcentaje" id="descuento_productos_porcentaje" class="w-full border rounded px-3 py-2" step="0.01" value="0" min="0" max="100" oninput="calcularTotales()">
                    </div>
                    <div class="flex-1">
                        <label for="descuento_productos_euro" class="block font-semibold mb-1">Descuento €:</label>
                        <input type="number" name="descuento_productos_euro" id="descuento_productos_euro" class="w-full border rounded px-3 py-2" step="0.01" value="0" min="0" oninput="calcularTotales()">
                    </div>
                </div>
            </div>

            <!-- Campos ocultos para compatibilidad (descuento general antiguo) -->
            <input type="hidden" name="descuento_porcentaje" id="descuento_porcentaje" value="0">
            <input type="hidden" name="descuento_euro" id="descuento_euro" value="0">

            <!-- Bonos -->
            <div class="bg-yellow-50 p-4 rounded">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-lg font-semibold">💳 Bonos</h2>
                    <button type="button" id="btn-add-bono" class="bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 text-sm">
                        + Añadir bono
                    </button>
                </div>
                <table class="w-full text-sm" id="bonos-table">
                    <thead class="bg-yellow-100">
                        <tr>
                            <th class="p-2 text-left">Bono</th>
                            <th class="p-2 text-left">Servicios incluidos</th>
                            <th class="p-2 text-right">Precio</th>
                            <th class="p-2 text-center">Validez</th>
                            <th class="p-2"></th>
                        </tr>
                    </thead>
                    <tbody id="bonos-tbody">
                        <!-- Bono añadido dinámicamente -->
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-yellow-300">
                            <td colspan="2" class="p-2 text-right font-semibold">Total bono:</td>
                            <td id="bonos-total" class="p-2 text-right font-semibold">€0.00</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
                
                <!-- Mensaje de descuento automático -->
                <div id="descuento-aplicado" class="mt-3 p-2 bg-green-100 border border-green-300 rounded hidden">
                    <p class="text-green-700 font-semibold text-sm">
                        ✅ Se descontarán €<span id="descuento-amount">0.00</span> de esta cita porque incluye servicios del bono
                    </p>
                    <p class="text-xs text-green-600 mt-1">Los servicios se marcarán como usados automáticamente</p>
                </div>
            </div>

            <!-- Resumen y pago -->
            <div class="bg-green-50 border-2 border-green-600 p-4 rounded mt-4">
                <h2 class="text-lg font-semibold mb-3">Resumen y Pago</h2>
                
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between">
                        <span>Subtotal:</span>
                        <span id="subtotal" class="font-semibold">€0.00</span>
                    </div>
                    <div class="flex justify-between text-red-600">
                        <span>Descuentos:</span>
                        <span id="descuentos-total" class="font-semibold">€0.00</span>
                    </div>
                    <div class="flex justify-between text-2xl font-bold border-t-2 border-green-600 pt-2">
                        <span>TOTAL:</span>
                        <span id="total-final">€0.00</span>
                    </div>
                </div>

                <input type="hidden" name="coste" id="coste" value="0">
                <input type="hidden" name="total_final" id="total_final_input" value="0">

                <div class="mb-4">
                    <label class="block font-semibold mb-2">Método de pago:</label>
                    <div class="flex gap-4">
                        <label class="flex items-center">
                            <input type="radio" name="metodo_pago" value="efectivo" class="mr-2" onchange="cambiarMetodoPago()" required>
                            <span>💵 Efectivo</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="metodo_pago" value="tarjeta" class="mr-2" onchange="cambiarMetodoPago()">
                            <span>💳 Tarjeta</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="metodo_pago" value="mixto" class="mr-2" onchange="cambiarMetodoPago()">
                            <span>🔀 Mixto</span>
                        </label>
                    </div>
                </div>

                <!-- Campos dinámicos según método de pago -->
                <div id="pago-efectivo" class="hidden space-y-3">
                    <div>
                        <label for="dinero_cliente" class="block font-semibold mb-1">Dinero del cliente:</label>
                        <input type="number" name="dinero_cliente" id="dinero_cliente" class="w-full border rounded px-3 py-2" step="0.01" min="0" oninput="calcularCambio()">
                    </div>
                    <div class="bg-white p-3 rounded">
                        <div class="flex justify-between">
                            <span class="font-semibold">Cambio:</span>
                            <span id="cambio-display" class="text-lg font-bold text-green-600">€0.00</span>
                        </div>
                        <input type="hidden" name="cambio" id="cambio" value="0">
                    </div>
                </div>

                <div id="pago-mixto" class="hidden space-y-3">
                    <div>
                        <label for="pago_efectivo" class="block font-semibold mb-1">Pago en efectivo:</label>
                        <input type="number" name="pago_efectivo" id="pago_efectivo" class="w-full border rounded px-3 py-2" step="0.01" min="0" oninput="calcularPagoMixto()">
                    </div>
                    <div>
                        <label for="pago_tarjeta" class="block font-semibold mb-1">Pago con tarjeta:</label>
                        <input type="number" name="pago_tarjeta" id="pago_tarjeta" class="w-full border rounded px-3 py-2" step="0.01" min="0" oninput="calcularPagoMixto()">
                    </div>
                    <div class="bg-white p-3 rounded">
                        <div class="flex justify-between">
                            <span>Total pagado:</span>
                            <span id="total-pagado" class="font-semibold">€0.00</span>
                        </div>
                        <div class="flex justify-between mt-2">
                            <span>Restante:</span>
                            <span id="restante" class="font-semibold">€0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campos ocultos para productos, servicios y bono -->
            <input type="hidden" name="productos_data" id="productos_data" value="[]">
            <input type="hidden" name="servicios_data" id="servicios_data" value="[]">
            <input type="hidden" name="bono_plantilla_id" id="bono_plantilla_id" value="">

            <div class="flex justify-end gap-3">
                <a href="{{ route('cobros.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                    Cancelar
                </a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 font-semibold">
                    ✓ Registrar Cobro
                </button>
            </div>
        </form>
    </div>

    <!-- Modal para añadir servicios -->
    <div id="modal-services" class="hidden fixed inset-0 modal-backdrop flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Seleccionar Servicio</h3>
                <button type="button" onclick="closeModalServices()" class="text-gray-600 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            
            <!-- Buscador de servicios -->
            <div class="mb-4">
                <input type="text" id="buscar-servicio" class="w-full border rounded px-3 py-2" placeholder="🔍 Buscar servicio..." oninput="filtrarServicios()">
            </div>
            
            <div class="max-h-96 overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 sticky top-0">
                        <tr>
                            <th class="p-2 text-left">Servicio</th>
                            <th class="p-2 text-right">Precio</th>
                            <th class="p-2"></th>
                        </tr>
                    </thead>
                    <tbody id="servicios-tbody-modal">
                        @foreach($servicios as $servicio)
                        <tr class="border-b hover:bg-gray-50 servicio-row" data-nombre="{{ strtolower($servicio->nombre) }}" data-servicio-id="{{ $servicio->id }}">
                            <td class="p-2">
                                <div class="flex items-center justify-between">
                                    <span>{{ $servicio->nombre }}</span>
                                    <span class="badge-bono-disponible hidden ml-2" data-servicio-id="{{ $servicio->id }}">
                                        <!-- Badge dinámico -->
                                    </span>
                                </div>
                            </td>
                            <td class="p-2 text-right">€{{ number_format($servicio->precio, 2) }}</td>
                            <td class="p-2">
                                <button type="button" onclick="addService({{ $servicio->id }}, '{{ $servicio->nombre }}', {{ $servicio->precio }})" class="bg-purple-600 text-white px-3 py-1 rounded hover:bg-purple-700 text-sm">
                                    Añadir
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para añadir productos -->
    <div id="modal-products" class="hidden fixed inset-0 modal-backdrop flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Seleccionar Producto</h3>
                <button type="button" onclick="closeModalProducts()" class="text-gray-600 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            
            <!-- Buscador de productos -->
            <div class="mb-4">
                <input type="text" id="buscar-producto" class="w-full border rounded px-3 py-2" placeholder="🔍 Buscar producto..." oninput="filtrarProductos()">
            </div>
            
            <div id="products-loading" class="text-center py-4">Cargando productos...</div>
            <div id="products-content" class="hidden max-h-96 overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 sticky top-0">
                        <tr>
                            <th class="p-2 text-left">Producto</th>
                            <th class="p-2 text-center">Stock</th>
                            <th class="p-2 text-right">Precio</th>
                            <th class="p-2 text-center">Cantidad</th>
                            <th class="p-2"></th>
                        </tr>
                    </thead>
                    <tbody id="modal-products-tbody">
                        <!-- Cargado dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- Modal de Alertas de Bonos -->
<div id="bono-alerta-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 animate-fade-in">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <span id="alerta-icono" class="text-3xl mr-3">⚠️</span>
                    <h3 id="alerta-titulo" class="text-xl font-bold text-gray-800">
                        Alerta de Bono
                    </h3>
                </div>
                <button type="button" id="close-alerta-modal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Contenido -->
            <div id="alerta-contenido" class="mb-6">
                <!-- Se llenará dinámicamente -->
            </div>

            <!-- Botones -->
            <div class="flex justify-center">
                <button type="button" id="btn-entendido" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-semibold">
                    Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales (fuera del DOMContentLoaded para que las funciones window puedan acceder)
let serviciosSeleccionados = [];
let productosSeleccionados = [];
let csrfToken;
let bonoSeleccionado = null;
let descuentoPorBono = 0;
let bonosActivosCliente = @json($bonosCliente ?? collect()); // Bonos activos del cliente

document.addEventListener('DOMContentLoaded', function() {
csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Bonos disponibles
const bonosDisponibles = @json($bonosPlantilla);

// Modal para seleccionar bono
window.mostrarModalBonos = function() {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.onclick = function(e) {
        if (e.target === modal) modal.remove();
    };
    
    modal.innerHTML = `
        <div class="bg-white rounded-lg w-full mx-4 max-w-2xl flex flex-col" style="max-height: 90vh;" onclick="event.stopPropagation()">
            <!-- Header fijo -->
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold">Seleccionar Bono</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            
            <!-- Barra de búsqueda fija -->
            <div class="p-4 border-b">
                <input type="text" 
                       id="buscar-bono" 
                       placeholder="🔍 Buscar bono por nombre o servicio..." 
                       class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                       oninput="filtrarBonos()">
            </div>
            
            <!-- Contenido con scroll -->
            <div class="flex-1 overflow-y-auto p-6">
                <div id="bonos-lista" class="space-y-3">
                    ${bonosDisponibles.map(bono => `
                        <div class="bono-item border rounded p-3 hover:bg-yellow-50 cursor-pointer transition" 
                             data-nombre="${bono.nombre.toLowerCase()}"
                             data-descripcion="${(bono.descripcion || '').toLowerCase()}"
                             data-servicios="${bono.servicios.map(s => s.nombre.toLowerCase()).join(' ')}"
                             onclick="seleccionarBono(${bono.id})">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-lg">${bono.nombre}</h4>
                                    <p class="text-sm text-gray-600 mb-2">${bono.descripcion || ''}</p>
                                    <div class="text-sm">
                                        <strong>Servicios incluidos:</strong>
                                        <ul class="list-disc ml-5 mt-1">
                                            ${bono.servicios.map(s => `<li>${s.nombre} (${s.pivot.cantidad}x)</li>`).join('')}
                                        </ul>
                                    </div>
                                    <p class="text-sm mt-2"><strong>Validez:</strong> ${bono.duracion_dias} días</p>
                                </div>
                                <div class="ml-4 text-right">
                                    <p class="text-2xl font-bold text-yellow-600">€${parseFloat(bono.precio).toFixed(2)}</p>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

window.filtrarBonos = function() {
    const busqueda = document.getElementById('buscar-bono').value.toLowerCase().trim();
    const items = document.querySelectorAll('.bono-item');
    
    items.forEach(item => {
        const nombre = item.dataset.nombre;
        const descripcion = item.dataset.descripcion;
        const servicios = item.dataset.servicios;
        
        const coincide = nombre.includes(busqueda) || 
                        descripcion.includes(busqueda) || 
                        servicios.includes(busqueda);
        
        item.style.display = coincide ? 'block' : 'none';
    });
}

window.seleccionarBono = function(bonoId) {
    // Verificar si ya hay un bono seleccionado
    if (bonoSeleccionado) {
        alert('Ya hay un bono añadido. Elimínalo primero si deseas añadir otro.');
        document.querySelector('.fixed').remove();
        return;
    }
    
    const bono = bonosDisponibles.find(b => b.id === bonoId);
    if (!bono) return;
    
    bonoSeleccionado = {
        id: bono.id,
        nombre: bono.nombre,
        precio: parseFloat(bono.precio),
        duracion: bono.duracion_dias,
        servicios: bono.servicios.map(s => s.id)
    };
    
    // Actualizar campo oculto
    document.getElementById('bono_plantilla_id').value = bono.id;
    
    // Crear fila en la tabla
    const tbody = document.getElementById('bonos-tbody');
    const serviciosTexto = bono.servicios.map(s => `${s.nombre} (${s.pivot.cantidad}x)`).join(', ');
    
    const row = document.createElement('tr');
    row.innerHTML = `
        <td class="p-2 font-semibold">${bono.nombre}</td>
        <td class="p-2 text-sm">${serviciosTexto}</td>
        <td class="p-2 text-right">€${parseFloat(bono.precio).toFixed(2)}</td>
        <td class="p-2 text-center text-sm">${bono.duracion_dias} días</td>
        <td class="p-2 text-center">
            <button type="button" onclick="eliminarBono()" class="text-red-600 hover:text-red-800 font-bold">✕</button>
        </td>
    `;
    tbody.innerHTML = '';
    tbody.appendChild(row);
    
    // Actualizar total
    document.getElementById('bonos-total').textContent = `€${parseFloat(bono.precio).toFixed(2)}`;
    
    // Calcular descuento automático
    calcularDescuentoBono();
    
    // Cerrar modal (buscar el modal específico)
    const modal = document.querySelector('.fixed.inset-0.bg-black.bg-opacity-50');
    if (modal) {
        modal.remove();
    }
    
    calcularTotales();
}

window.eliminarBono = function() {
    bonoSeleccionado = null;
    descuentoPorBono = 0;
    document.getElementById('bono_plantilla_id').value = '';
    document.getElementById('bonos-tbody').innerHTML = '';
    document.getElementById('bonos-total').textContent = '€0.00';
    document.getElementById('descuento-aplicado').classList.add('hidden');
    calcularTotales();
}

window.calcularDescuentoBono = function() {
    if (!bonoSeleccionado) return;
    
    // Calcular si hay servicios de la cita en el bono
    let serviciosCoincidentes = serviciosSeleccionados.filter(s => 
        bonoSeleccionado.servicios.includes(s.id)
    );
    
    descuentoPorBono = serviciosCoincidentes.reduce((sum, s) => sum + s.precio, 0);
    
    // Mostrar o ocultar descuento aplicado
    const descuentoDiv = document.getElementById('descuento-aplicado');
    if (descuentoPorBono > 0) {
        descuentoDiv.classList.remove('hidden');
        document.getElementById('descuento-amount').textContent = descuentoPorBono.toFixed(2);
    } else {
        descuentoDiv.classList.add('hidden');
    }
}

// NUEVA FUNCIÓN: Detectar y aplicar automáticamente bonos activos del cliente
window.detectarBonosActivos = function() {
    if (!bonosActivosCliente || bonosActivosCliente.length === 0) {
        return 0;
    }
    
    let totalDescuentoBonosActivos = 0;
    
    // Por cada servicio seleccionado, buscar si hay un bono activo que lo cubra
    serviciosSeleccionados.forEach(servicio => {
        // Buscar si algún bono activo incluye este servicio
        for (let bono of bonosActivosCliente) {
            if (!bono.servicios || bono.servicios.length === 0) continue;
            
            // Verificar si el servicio está en el bono y tiene usos disponibles
            const servicioEnBono = bono.servicios.find(s => 
                s.id === servicio.id && 
                (s.pivot.cantidad_usada < s.pivot.cantidad_total)
            );
            
            if (servicioEnBono) {
                // Este servicio está cubierto por un bono activo
                totalDescuentoBonosActivos += servicio.precio;
                servicio.pagadoConBono = true; // Marcar el servicio
                break; // No buscar en más bonos para este servicio
            }
        }
    });
    
    return totalDescuentoBonosActivos;
}

// Botón añadir bono
document.getElementById('btn-add-bono').addEventListener('click', function() {
    mostrarModalBonos();
});

// Actualizar deuda del cliente
window.actualizarDeudaCliente = function() {
    const select = document.getElementById('id_cliente');
    const option = select.options[select.selectedIndex];
    const deuda = parseFloat(option.dataset.deuda || 0);
    const info = document.getElementById('deuda-info');
    
    if (deuda > 0) {
        info.textContent = `⚠️ Este cliente tiene una deuda pendiente de €${deuda.toFixed(2)}`;
        info.className = 'text-sm text-red-600 mt-2 font-semibold';
    } else {
        info.textContent = '✓ Sin deudas pendientes';
        info.className = 'text-sm text-green-600 mt-2';
    }
}

// Mostrar panel de bonos del cliente y actualizar badges
window.mostrarPanelBonos = function() {
    const clienteId = document.getElementById('id_cliente').value;
    const panelBonos = document.getElementById('panel-bonos-cliente');
    const listaBonos = document.getElementById('lista-bonos-cliente');
    
    // Ocultar panel si no hay cliente seleccionado
    if (!clienteId) {
        panelBonos.classList.add('hidden');
        ocultarTodosBadges();
        return;
    }
    
    // Buscar bonos del cliente
    const bonosData = @json($bonosCliente ?? collect());
    
    // Filtrar bonos del cliente actual
    const bonosCliente = bonosData.filter(bono => bono.cliente_id == clienteId);
    
    if (bonosCliente.length === 0) {
        panelBonos.classList.add('hidden');
        ocultarTodosBadges();
        return;
    }
    
    // Mostrar panel
    panelBonos.classList.remove('hidden');
    
    // Construir contenido del panel
    let html = '';
    bonosCliente.forEach(bono => {
        const plantilla = bono.plantilla;
        const servicios = bono.servicios || [];
        
        // Verificar si el bono tiene al menos un servicio disponible
        let tieneServiciosDisponibles = false;
        let serviciosHTML = '';
        
        servicios.forEach(servicio => {
            const usado = servicio.pivot.cantidad_usada;
            const total = servicio.pivot.cantidad_total;
            const restante = total - usado;
            
            // Solo incluir servicios con cantidad disponible > 0
            if (restante > 0) {
                tieneServiciosDisponibles = true;
                
                let colorTexto = 'text-green-600';
                if (restante <= 2 && restante > 0) {
                    colorTexto = 'text-yellow-600';
                }
                
                serviciosHTML += '<div class="bono-servicio-item">';
                serviciosHTML += '<span>• ' + servicio.nombre + '</span>';
                serviciosHTML += '<span class="font-semibold ' + colorTexto + '">' + restante + '/' + total + ' disponibles</span>';
                serviciosHTML += '</div>';
            }
        });
        
        // Solo mostrar el bono si tiene servicios disponibles
        if (tieneServiciosDisponibles) {
            // Calcular fecha de vencimiento
            const fechaExp = new Date(bono.fecha_expiracion);
            const hoy = new Date();
            const diasRestantes = Math.ceil((fechaExp - hoy) / (1000 * 60 * 60 * 24));
            
            // Determinar color de alerta
            let alertaVencimiento = '';
            if (diasRestantes <= 7 && diasRestantes > 0) {
                alertaVencimiento = '<span class="text-red-600 font-semibold">⚠️ Vence en ' + diasRestantes + ' días</span>';
            } else if (diasRestantes <= 0) {
                alertaVencimiento = '<span class="text-red-700 font-bold">❌ VENCIDO</span>';
            } else {
                alertaVencimiento = '<span class="text-gray-600">⏰ Vence: ' + fechaExp.toLocaleDateString('es-ES') + '</span>';
            }
            
            html += '<div class="bono-card">';
            html += '<div class="flex justify-between items-start mb-2">';
            html += '<h4 class="font-bold text-purple-700">' + plantilla.nombre + '</h4>';
            html += '<span class="text-xs">' + alertaVencimiento + '</span>';
            html += '</div>';
            html += '<div class="text-sm text-gray-600 space-y-1">';
            html += serviciosHTML;
            html += '</div>';
            html += '</div>';
        }
    });
    
    listaBonos.innerHTML = html;
    
    // Actualizar badges en servicios
    actualizarBadgesBonos(bonosCliente);
}

// Actualizar badges de bonos disponibles en servicios
function actualizarBadgesBonos(bonosCliente) {
    // Primero ocultar todos los badges
    ocultarTodosBadges();
    
    // Crear un mapa de servicios disponibles
    const serviciosDisponibles = {};
    
    bonosCliente.forEach(bono => {
        const servicios = bono.servicios || [];
        servicios.forEach(servicio => {
            const restante = servicio.pivot.cantidad_total - servicio.pivot.cantidad_usada;
            if (restante > 0) {
                if (!serviciosDisponibles[servicio.id]) {
                    serviciosDisponibles[servicio.id] = {
                        cantidad: 0,
                        diasVencimiento: null
                    };
                }
                serviciosDisponibles[servicio.id].cantidad += restante;
                
                // Calcular días hasta vencimiento
                const fechaExp = new Date(bono.fecha_expiracion);
                const hoy = new Date();
                const dias = Math.ceil((fechaExp - hoy) / (1000 * 60 * 60 * 24));
                
                if (serviciosDisponibles[servicio.id].diasVencimiento === null || dias < serviciosDisponibles[servicio.id].diasVencimiento) {
                    serviciosDisponibles[servicio.id].diasVencimiento = dias;
                }
            }
        });
    });
    
    // Mostrar badges en cada servicio
    Object.keys(serviciosDisponibles).forEach(servicioId => {
        const badge = document.querySelector(`.badge-bono-disponible[data-servicio-id="${servicioId}"]`);
        if (badge) {
            const info = serviciosDisponibles[servicioId];
            const cantidad = info.cantidad;
            const dias = info.diasVencimiento;
            
            // Determinar color del badge
            let claseColor = 'badge-bono-verde';
            let icono = '🎫';
            
            if (dias <= 7 && dias > 0) {
                claseColor = 'badge-bono-rojo';
                icono = '⚠️';
            } else if (cantidad <= 2) {
                claseColor = 'badge-bono-amarillo';
                icono = '⚠️';
            }
            
            badge.innerHTML = `<span class="${claseColor}">${icono} ${cantidad} ${cantidad === 1 ? 'uso' : 'usos'}</span>`;
            badge.classList.remove('hidden');
        }
    });
}

// Ocultar todos los badges
function ocultarTodosBadges() {
    document.querySelectorAll('.badge-bono-disponible').forEach(badge => {
        badge.classList.add('hidden');
        badge.innerHTML = '';
    });
}

// Filtrar clientes
window.filtrarClientes = function() {
    const busqueda = document.getElementById('buscar-cliente').value.toLowerCase().trim();
    const select = document.getElementById('id_cliente');
    const options = select.querySelectorAll('option');
    
    let encontrados = 0;
    options.forEach((option, index) => {
        if (index === 0) return; // Skip first "-- Sin cliente --" option
        
        const nombre = option.dataset.nombre || '';
        if (nombre.includes(busqueda)) {
            option.style.display = '';
            encontrados++;
        } else {
            option.style.display = 'none';
        }
    });
    
    // Si no hay búsqueda, mostrar todos
    if (busqueda === '') {
        options.forEach(option => option.style.display = '');
    }
}

// Filtrar servicios en el modal
window.filtrarServicios = function() {
    const busqueda = document.getElementById('buscar-servicio').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.servicio-row');
    
    let encontrados = 0;
    rows.forEach(row => {
        const nombre = row.dataset.nombre || '';
        if (nombre.includes(busqueda)) {
            row.style.display = '';
            encontrados++;
        } else {
            row.style.display = 'none';
        }
    });
}

// Filtrar productos en el modal - ahora con búsqueda en backend
let productSearchTimeout;
window.filtrarProductos = function() {
    const busqueda = document.getElementById('buscar-producto').value.toLowerCase().trim();
    
    // Limpiar timeout anterior
    clearTimeout(productSearchTimeout);
    
    // Esperar 500ms después de que el usuario deje de escribir
    productSearchTimeout = setTimeout(() => {
        loadProducts(busqueda);
    }, 500);
}

// Modal de servicios
document.getElementById('btn-add-service').addEventListener('click', function() {
    document.getElementById('modal-services').classList.remove('hidden');
});

window.closeModalServices = function() {
    document.getElementById('modal-services').classList.add('hidden');
}

window.addService = function(id, nombre, precio) {
    // Verificar si ya está añadido
    if (serviciosSeleccionados.find(s => s.id === id)) {
        alert('Este servicio ya está añadido');
        return;
    }
    
    // Obtener el empleado por defecto (el seleccionado en el formulario principal)
    const empleadoSelect = document.getElementById('id_empleado');
    const empleadoId = empleadoSelect.value ? parseInt(empleadoSelect.value) : null;
    
    // Validar que haya un empleado seleccionado
    if (!empleadoId) {
        alert('Debe seleccionar un empleado antes de añadir servicios');
        empleadoSelect.focus();
        return;
    }
    
    serviciosSeleccionados.push({ id, nombre, precio, empleado_id: empleadoId });
    renderServicios();
    closeModalServices();
    calcularDescuentoBono();
    calcularTotales();
}

window.removeService = function(id) {
    serviciosSeleccionados = serviciosSeleccionados.filter(s => s.id !== id);
    renderServicios();
    calcularDescuentoBono();
    calcularTotales();
}

window.updateServicioEmpleado = function(index, empleadoId) {
    if (serviciosSeleccionados[index]) {
        serviciosSeleccionados[index].empleado_id = parseInt(empleadoId);
        document.getElementById('servicios_data').value = JSON.stringify(serviciosSeleccionados);
    }
}

function renderServicios() {
    const tbody = document.getElementById('services-tbody');
    tbody.innerHTML = '';
    
    if (serviciosSeleccionados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="p-2 text-center text-gray-500">No hay servicios añadidos</td></tr>';
        return;
    }
    
    serviciosSeleccionados.forEach((servicio, index) => {
        const tr = document.createElement('tr');
        tr.className = 'border-b';
        
        // Crear select de empleados
        const empleadosOptions = `
            @foreach($empleados as $empleado)
                <option value="{{ $empleado->id }}" ${servicio.empleado_id == {{ $empleado->id }} ? 'selected' : ''}>
                    {{ $empleado->user->nombre ?? '' }} {{ $empleado->user->apellidos ?? '' }}
                </option>
            @endforeach
        `;
        
        tr.innerHTML = `
            <td class="p-2">${servicio.nombre}</td>
            <td class="p-2">
                <select onchange="updateServicioEmpleado(${index}, this.value)" class="w-full border rounded px-2 py-1 text-sm">
                    ${empleadosOptions}
                </select>
            </td>
            <td class="p-2 text-right">€${servicio.precio.toFixed(2)}</td>
            <td class="p-2 text-center">
                <button type="button" onclick="removeService(${servicio.id})" class="text-red-600 hover:text-red-800">✕</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    
    // Actualizar campo oculto
    document.getElementById('servicios_data').value = JSON.stringify(serviciosSeleccionados);
}

// Modal de productos
document.getElementById('btn-add-product').addEventListener('click', async function() {
    document.getElementById('modal-products').classList.remove('hidden');
    await loadProducts();
});

window.closeModalProducts = function() {
    document.getElementById('modal-products').classList.add('hidden');
}

async function loadProducts(searchQuery = '') {
    const loading = document.getElementById('products-loading');
    const content = document.getElementById('products-content');
    const tbody = document.getElementById('modal-products-tbody');
    
    loading.classList.remove('hidden');
    content.classList.add('hidden');
    
    try {
        // Construir URL con parámetro de búsqueda si existe
        let url = '{{ route("productos.available") }}';
        if (searchQuery) {
            url += '?q=' + encodeURIComponent(searchQuery);
        }
        
        const response = await fetch(url, {
            headers: { 'Accept': 'application/json' }
        });
        
        if (!response.ok) throw new Error('Error al cargar productos');
        
        const productos = await response.json();
        tbody.innerHTML = '';
        
        if (productos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">No se encontraron productos</td></tr>';
        } else {
            productos.forEach(producto => {
                const tr = document.createElement('tr');
                tr.className = 'border-b hover:bg-gray-50 producto-row';
                tr.innerHTML = `
                    <td class="p-2">${producto.nombre}</td>
                    <td class="p-2 text-center">${producto.stock}</td>
                    <td class="p-2 text-right">€${parseFloat(producto.precio_venta).toFixed(2)}</td>
                    <td class="p-2 text-center">
                        <input type="number" id="qty-${producto.id}" min="1" max="${producto.stock}" value="1" class="w-16 border rounded px-2 py-1 text-center">
                    </td>
                    <td class="p-2">
                        <button type="button" onclick="addProduct(${producto.id}, '${producto.nombre}', ${producto.precio_venta}, ${producto.stock})" class="bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700 text-sm">
                            Añadir
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
        
        loading.classList.add('hidden');
        content.classList.remove('hidden');
    } catch (error) {
        loading.textContent = 'Error al cargar productos';
        console.error(error);
    }
}

window.addProduct = function(id, nombre, precio, stock) {
    const qtyInput = document.getElementById(`qty-${id}`);
    const cantidad = parseInt(qtyInput.value);
    
    if (cantidad < 1 || cantidad > stock) {
        alert('Cantidad no válida');
        return;
    }
    
    // Verificar si ya está añadido
    const existente = productosSeleccionados.find(p => p.id === id);
    if (existente) {
        existente.cantidad += cantidad;
    } else {
        productosSeleccionados.push({ id, nombre, precio, cantidad });
    }
    
    renderProductos();
    closeModalProducts();
    calcularTotales();
}

window.removeProduct = function(id) {
    productosSeleccionados = productosSeleccionados.filter(p => p.id !== id);
    renderProductos();
    calcularTotales();
}

window.updateProductQty = function(id, cantidad) {
    const producto = productosSeleccionados.find(p => p.id === id);
    if (producto) {
        producto.cantidad = parseInt(cantidad);
        renderProductos();
        calcularTotales();
    }
}

function renderProductos() {
    const tbody = document.getElementById('products-tbody');
    tbody.innerHTML = '';
    
    if (productosSeleccionados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="p-2 text-center text-gray-500">No hay productos añadidos</td></tr>';
        return;
    }
    
    productosSeleccionados.forEach(producto => {
        const subtotal = producto.precio * producto.cantidad;
        const tr = document.createElement('tr');
        tr.className = 'border-b';
        tr.innerHTML = `
            <td class="p-2">${producto.nombre}</td>
            <td class="p-2 text-center">
                <input type="number" min="1" value="${producto.cantidad}" onchange="updateProductQty(${producto.id}, this.value)" class="w-16 border rounded px-2 py-1 text-center">
            </td>
            <td class="p-2 text-right">€${producto.precio.toFixed(2)}</td>
            <td class="p-2 text-right">€${subtotal.toFixed(2)}</td>
            <td class="p-2 text-center">
                <button type="button" onclick="removeProduct(${producto.id})" class="text-red-600 hover:text-red-800">✕</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    
    // Actualizar campo oculto
    document.getElementById('productos_data').value = JSON.stringify(productosSeleccionados);
}

// Calcular totales
window.calcularTotales = function() {
    // Calcular total de servicios
    const totalServicios = serviciosSeleccionados.reduce((sum, s) => sum + parseFloat(s.precio), 0);
    document.getElementById('services-total').textContent = `€${totalServicios.toFixed(2)}`;
    
    // IMPORTANTE: El campo 'coste' debe contener SOLO el total de servicios, no productos ni bonos
    document.getElementById('coste').value = totalServicios.toFixed(2);

    // Calcular total de productos
    const totalProductos = productosSeleccionados.reduce((sum, p) => sum + (parseFloat(p.precio) * parseInt(p.cantidad)), 0);
    document.getElementById('products-total').textContent = `€${totalProductos.toFixed(2)}`;

    // Detectar bonos activos del cliente automáticamente
    const descuentoBonosActivos = detectarBonosActivos();
    
    // Subtotal (para mostrar en pantalla, incluye servicios + productos + bono vendido)
    let subtotal = totalServicios + totalProductos;
    if (bonoSeleccionado) {
        subtotal += bonoSeleccionado.precio;
    }
    document.getElementById('subtotal').textContent = `€${subtotal.toFixed(2)}`;

    // Descuentos separados
    const descServPor = parseFloat(document.getElementById('descuento_servicios_porcentaje')?.value || 0);
    const descServEur = parseFloat(document.getElementById('descuento_servicios_euro')?.value || 0);
    const descProdPor = parseFloat(document.getElementById('descuento_productos_porcentaje')?.value || 0);
    const descProdEur = parseFloat(document.getElementById('descuento_productos_euro')?.value || 0);

    const descuentoServicios = (totalServicios * (descServPor / 100)) + descServEur;
    const descuentoProductos = (totalProductos * (descProdPor / 100)) + descProdEur;
    const totalDescuentos = descuentoServicios + descuentoProductos;

    document.getElementById('descuentos-total').textContent = `-€${totalDescuentos.toFixed(2)}`;

    // Total final
    // 1. Servicios sin bonos activos (restar primero los servicios cubiertos por bonos activos del cliente)
    let serviciosSinBonosActivos = totalServicios - descuentoBonosActivos;
    
    // 2. Si hay bono VENDIDO, restar también los servicios coincidentes con ese bono
    if (bonoSeleccionado) {
        serviciosSinBonosActivos -= descuentoPorBono;
    }
    
    let totalServiciosFinal = Math.max(0, serviciosSinBonosActivos - descuentoServicios);
    
    // 3. Productos con su descuento
    let totalProductosFinal = Math.max(0, totalProductos - descuentoProductos);
    
    // 4. Si hay bono vendido, sumar el precio del bono (es un ingreso adicional)
    let precioBonoVendido = bonoSeleccionado ? bonoSeleccionado.precio : 0;
    
    // Total final = servicios (sin los que están cubiertos por bonos) + productos + precio del bono vendido
    let totalFinal = totalServiciosFinal + totalProductosFinal + precioBonoVendido;
    
    document.getElementById('total-final').textContent = `€${totalFinal.toFixed(2)}`;
    document.getElementById('total_final_input').value = totalFinal.toFixed(2);

    // Mantener compatibilidad: actualizar campos ocultos de descuento total
    const descTotalPor = (descServPor || 0) + (descProdPor || 0);
    const descTotalEur = (descServEur || 0) + (descProdEur || 0);
    const hiddenPor = document.getElementById('descuento_porcentaje');
    const hiddenEur = document.getElementById('descuento_euro');
    if (hiddenPor) hiddenPor.value = descTotalPor.toFixed(2);
    if (hiddenEur) hiddenEur.value = descTotalEur.toFixed(2);

    // Recalcular según método de pago
    const metodo = document.querySelector('input[name="metodo_pago"]:checked');
    if (metodo) {
        if (metodo.value === 'efectivo') calcularCambio();
        if (metodo.value === 'mixto') calcularPagoMixto();
    }
}

// Cambiar método de pago
window.cambiarMetodoPago = function() {
    const metodo = document.querySelector('input[name="metodo_pago"]:checked').value;
    
    document.getElementById('pago-efectivo').classList.add('hidden');
    document.getElementById('pago-mixto').classList.add('hidden');
    
    if (metodo === 'efectivo') {
        document.getElementById('pago-efectivo').classList.remove('hidden');
    } else if (metodo === 'mixto') {
        document.getElementById('pago-mixto').classList.remove('hidden');
    }
}

window.calcularCambio = function() {
    const totalFinal = parseFloat(document.getElementById('total_final_input').value || 0);
    const dineroCliente = parseFloat(document.getElementById('dinero_cliente').value || 0);
    const cambio = Math.max(0, dineroCliente - totalFinal);
    
    document.getElementById('cambio-display').textContent = `€${cambio.toFixed(2)}`;
    document.getElementById('cambio').value = cambio.toFixed(2);
}

window.calcularPagoMixto = function() {
    const totalFinal = parseFloat(document.getElementById('total_final_input').value || 0);
    const pagoEfectivo = parseFloat(document.getElementById('pago_efectivo').value || 0);
    const pagoTarjeta = parseFloat(document.getElementById('pago_tarjeta').value || 0);
    const totalPagado = pagoEfectivo + pagoTarjeta;
    const restante = Math.max(0, totalFinal - totalPagado);
    
    document.getElementById('total-pagado').textContent = `€${totalPagado.toFixed(2)}`;
    document.getElementById('restante').textContent = `€${restante.toFixed(2)}`;
}

// Validar formulario antes de enviar
document.getElementById('cobro-form').addEventListener('submit', function(e) {
    // Verificar si se está vendiendo un bono
    const bonoSeleccionado = document.getElementById('bono_plantilla_id').value;
    
    // Si no hay servicios, productos NI bono, mostrar error
    if (serviciosSeleccionados.length === 0 && productosSeleccionados.length === 0 && !bonoSeleccionado) {
        e.preventDefault();
        alert('Debe añadir al menos un servicio, producto o bono');
        return false;
    }
    
    const metodo = document.querySelector('input[name="metodo_pago"]:checked');
    if (!metodo) {
        e.preventDefault();
        alert('Debe seleccionar un método de pago');
        return false;
    }
    
    return true;
});

// Inicializar
renderServicios();
renderProductos();
calcularTotales();

// Pre-cargar servicios de las citas (una o múltiples)
@if(isset($citas) && $citas->count() > 1)
    // COBRO AGRUPADO: Cargar servicios de TODAS las citas
    @foreach($citas as $citaItem)
        @foreach($citaItem->servicios as $servicio)
            serviciosSeleccionados.push({
                id: {{ $servicio->id }},
                nombre: "{{ $servicio->nombre }} ({{ \Carbon\Carbon::parse($citaItem->fecha_hora)->format('H:i') }})",
                precio: {{ $servicio->precio }},
                empleado_id: {{ $citaItem->id_empleado }}
            });
        @endforeach
    @endforeach
    renderServicios();
    calcularTotales();
    console.log('✅ Cobro agrupado: {{ $citas->count() }} citas cargadas automáticamente');
    
@elseif(isset($cita) && $cita->servicios->isNotEmpty())
    // COBRO INDIVIDUAL: Cargar servicios de una sola cita
    @foreach($cita->servicios as $servicio)
        serviciosSeleccionados.push({
            id: {{ $servicio->id }},
            nombre: "{{ $servicio->nombre }}",
            precio: {{ $servicio->precio }},
            empleado_id: {{ $cita->id_empleado }}
        });
    @endforeach
    renderServicios();
    calcularTotales();
    console.log('Servicios de la cita cargados automáticamente');
@endif

// Actualizar deuda del cliente si está pre-seleccionado
@if(isset($cita) || (isset($citas) && $citas->count() > 0))
    actualizarDeudaCliente();
    mostrarPanelBonos();  // Mostrar panel de bonos si hay cliente
@endif

// --- Funciones para el modal de alertas de bonos ---
window.mostrarAlertaBono = function(datos) {
    const modal = document.getElementById('bono-alerta-modal');
    const icono = document.getElementById('alerta-icono');
    const titulo = document.getElementById('alerta-titulo');
    const contenido = document.getElementById('alerta-contenido');

    if (!modal || !datos.alertas || datos.alertas.length === 0) {
        return;
    }

    // Determinar el nivel de alerta más crítico
    const tieneCritico = datos.alertas.some(a => a.tipo === 'critico');
    
    // Configurar icono y título según criticidad
    if (tieneCritico) {
        icono.textContent = '🔴';
        titulo.textContent = '¡Atención! Bono Crítico';
        titulo.className = 'text-xl font-bold text-red-600';
    } else {
        icono.textContent = '🟡';
        titulo.textContent = 'Advertencia de Bono';
        titulo.className = 'text-xl font-bold text-yellow-600';
    }

    // Construir contenido
    let html = `
        <div class="bg-gray-50 rounded p-4 mb-4">
            <p class="font-semibold text-gray-800 mb-2">Bono: ${datos.nombreBono}</p>
            <p class="text-sm text-gray-600">Cliente: ${datos.nombreCliente}</p>
        </div>
    `;

    // Listar alertas
    html += '<div class="space-y-2">';
    datos.alertas.forEach(alerta => {
        const bgColor = alerta.tipo === 'critico' ? 'bg-red-50 border-red-200' : 'bg-yellow-50 border-yellow-200';
        const textColor = alerta.tipo === 'critico' ? 'text-red-800' : 'text-yellow-800';
        
        html += `
            <div class="border ${bgColor} ${textColor} rounded p-3 flex items-center">
                <span class="text-2xl mr-3">${alerta.icono}</span>
                <span>${alerta.mensaje}</span>
            </div>
        `;
    });
    html += '</div>';

    contenido.innerHTML = html;
    
    // Mostrar modal
    modal.classList.remove('hidden');
};

window.cerrarAlertaBono = function() {
    const modal = document.getElementById('bono-alerta-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
};

// Event listeners para el modal
document.getElementById('close-alerta-modal')?.addEventListener('click', cerrarAlertaBono);
document.getElementById('btn-entendido')?.addEventListener('click', cerrarAlertaBono);

// --- Sistema de alertas de bonos ---
@if(isset($bonosCliente) && $bonosCliente->isNotEmpty())
    const bonosClienteData = @json($bonosCliente);
    console.log('🎫 Bonos del cliente cargados:', bonosClienteData);
    
    // Verificar si algún bono tiene alertas Y servicios disponibles
    bonosClienteData.forEach(bono => {
        console.log('Verificando bono:', bono.plantilla.nombre, 'Alertas:', bono.alertas);
        
        // Verificar si el bono tiene al menos un servicio disponible
        let tieneServiciosDisponibles = false;
        if (bono.servicios && bono.servicios.length > 0) {
            bono.servicios.forEach(servicio => {
                const restante = servicio.pivot.cantidad_total - servicio.pivot.cantidad_usada;
                if (restante > 0) {
                    tieneServiciosDisponibles = true;
                }
            });
        }
        
        // Solo mostrar alertas de bonos con servicios disponibles
        if (tieneServiciosDisponibles && bono.alertas && bono.alertas.length > 0) {
            const cliente = @json(isset($cita) ? $cita->cliente : (isset($citas) && $citas->isNotEmpty() ? $citas->first()->cliente : null));
            
            const datosAlerta = {
                nombreBono: bono.plantilla.nombre,
                nombreCliente: cliente ? `${cliente.user.nombre} ${cliente.user.apellidos}` : 'Cliente',
                alertas: bono.alertas
            };
            
            console.log('🔔 Mostrando alerta para bono:', datosAlerta);
            
            // Mostrar alerta automáticamente al cargar la página
            setTimeout(() => {
                mostrarAlertaBono(datosAlerta);
                console.log('✅ Modal de alerta mostrado');
            }, 500);
        } else if (!tieneServiciosDisponibles) {
            console.log('⏭️ Bono sin servicios disponibles, alerta omitida:', bono.plantilla.nombre);
        }
    });
@else
    console.log('ℹ️ No hay bonos del cliente o $bonosCliente no está definido');
@endif

}); // Fin DOMContentLoaded
</script>

</body>
</html>
