<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Cobro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {!! vite_asset(['resources/css/app.css', 'resources/css/cobros.css', 'resources/js/cobros.js']) !!}
</head>
<body class="bg-gray-100 p-8">
    <div id="cobros-app" 
         data-productos-url="/productos/available"
         data-pago-deuda-url="/deudas/cliente/:id/pago"
         data-cita-preseleccionada="{{ isset($citaSeleccionada) && $citaSeleccionada ? 'true' : 'false' }}"
         class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Registrar Cobro</h1>

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

        <form action="{{ route('cobros.store') }}" method="POST" oninput="calcularTotales()" class="space-y-4" id="cobro-form">
            @csrf

            <div>
                <label for="id_cita" class="block font-semibold mb-1">Cita:</label>
                <select name="id_cita" id="id_cita" required class="w-full border rounded px-3 py-2" onchange="actualizarClienteInfo()">
                    @foreach($citas as $cita)
                        @php
                            $costeTotal = $cita->servicios->sum('precio');
                            $nombresServicios = $cita->servicios->pluck('nombre')->implode(', ');
                            $deudaExistente = $cita->cliente->deuda ? $cita->cliente->deuda->saldo_pendiente : 0;
                            $isSelected = $citaSeleccionada && $citaSeleccionada->id == $cita->id;
                        @endphp
                        <option value="{{ $cita->id }}" 
                                data-coste="{{ $costeTotal }}"
                                data-cliente-id="{{ $cita->cliente->id }}"
                                data-cliente-nombre="{{ $cita->cliente->user->nombre ?? '' }} {{ $cita->cliente->user->apellidos ?? '' }}"
                                data-deuda-existente="{{ $deudaExistente }}"
                                {{ $isSelected ? 'selected' : '' }}>
                            {{ $cita->cliente->user->nombre ?? '' }} - {{ $nombresServicios }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="coste" class="block font-semibold mb-1">Coste (servicios):</label>
                <input type="number" name="coste" id="coste" required class="w-full border rounded px-3 py-2 bg-gray-50" step="0.01" 
                       value="{{ isset($citaSeleccionada) && $citaSeleccionada ? number_format($citaSeleccionada->servicios->sum('precio'), 2, '.', '') : '0.00' }}" 
                       readonly>
                <p class="text-xs text-gray-500 mt-1">Este valor se calcula automáticamente según los servicios de la cita</p>
            </div>

            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Productos</h2>
                <!-- botón que abre modal -->
                <button type="button" id="btn-open-products" class="bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700">Añadir productos</button>
            </div>

            <!-- tabla productos seleccionados -->
            <div class="bg-gray-50 border rounded p-3">
                <table class="w-full text-sm" id="selected-products-table">
                    <thead>
                        <tr class="text-left">
                            <th class="pb-2">Nombre</th>
                            <th class="pb-2">Cantidad</th>
                            <th class="pb-2">Precio unitario</th>
                            <th class="pb-2 text-right">Subtotal</th>
                            <th class="pb-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- filas añadidas por JS -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right font-semibold">Total productos:</td>
                            <td id="selected-products-total" class="text-right font-semibold">0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                <p class="text-xs text-gray-500 mt-2">Los productos se añadirán al registrar el cobro.</p>
            </div>

            <!-- Descuentos para Servicios -->
            <div class="bg-blue-50 border border-blue-200 rounded p-4">
                <h3 class="font-semibold mb-3 text-blue-800">💇 Descuentos para Servicios</h3>
                <div class="flex space-x-4">
                    <div class="flex-1">
                        <label for="descuento_servicios_porcentaje" class="block font-semibold mb-1">Descuento %:</label>
                        <input type="number" name="descuento_servicios_porcentaje" id="descuento_servicios_porcentaje" class="w-full border rounded px-3 py-2" step="0.01" value="0">
                    </div>
                    <div class="flex-1">
                        <label for="descuento_servicios_euro" class="block font-semibold mb-1">Descuento €:</label>
                        <input type="number" name="descuento_servicios_euro" id="descuento_servicios_euro" class="w-full border rounded px-3 py-2" step="0.01" value="0">
                    </div>
                </div>
            </div>

            <!-- Descuentos para Productos -->
            <div class="bg-green-50 border border-green-200 rounded p-4">
                <h3 class="font-semibold mb-3 text-green-800">🛍️ Descuentos para Productos</h3>
                <div class="flex space-x-4">
                    <div class="flex-1">
                        <label for="descuento_productos_porcentaje" class="block font-semibold mb-1">Descuento %:</label>
                        <input type="number" name="descuento_productos_porcentaje" id="descuento_productos_porcentaje" class="w-full border rounded px-3 py-2" step="0.01" value="0">
                    </div>
                    <div class="flex-1">
                        <label for="descuento_productos_euro" class="block font-semibold mb-1">Descuento €:</label>
                        <input type="number" name="descuento_productos_euro" id="descuento_productos_euro" class="w-full border rounded px-3 py-2" step="0.01" value="0">
                    </div>
                </div>
            </div>

            <!-- Campos ocultos para compatibilidad con el sistema antiguo -->
            <input type="hidden" name="descuento_porcentaje" id="descuento_porcentaje" value="0">
            <input type="hidden" name="descuento_euro" id="descuento_euro" value="0">

            <div>
                <label for="total_final" class="block font-semibold mb-1">Total Final:</label>
                <input type="number" name="total_final" id="total_final" required class="w-full border rounded px-3 py-2" step="0.01" value="0.00" readonly>
            </div>

            <!-- Alerta de deuda en tiempo real -->
            <div id="alerta-deuda" class="hidden p-4 rounded border">
                <div class="flex items-start gap-3">
                    <div class="text-2xl">⚠️</div>
                    <div class="flex-1">
                        <h3 class="font-bold text-lg mb-1">Información de Deuda</h3>
                        <div class="text-sm space-y-1">
                            <p id="alerta-deuda-existente" class="hidden">
                                <span class="font-semibold">Deuda existente:</span> 
                                <span class="text-red-600 font-bold" id="alerta-deuda-existente-monto"></span>
                            </p>
                            <p id="alerta-deuda-nueva">
                                <span class="font-semibold">Deuda nueva (este cobro):</span> 
                                <span class="text-yellow-600 font-bold" id="alerta-deuda-nueva-monto"></span>
                            </p>
                            <p class="border-t pt-1 mt-2">
                                <span class="font-semibold">Total deuda acumulada:</span> 
                                <span class="text-gray-800 font-bold text-lg" id="alerta-deuda-total-monto"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label for="metodo_pago" class="block font-semibold mb-1">Método de Pago:</label>
                <select name="metodo_pago" id="metodo_pago" required class="w-full border rounded px-3 py-2" onchange="toggleMetodoPagoCampos()">
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="mixto">Mixto (Efectivo + Tarjeta)</option>
                </select>
            </div>

            <!-- Campos para efectivo -->
            <div id="efectivo_campos">
                <div>
                    <label for="dinero_cliente" class="block font-semibold mb-1">Dinero del Cliente (Efectivo):</label>
                    <input type="number" name="dinero_cliente" id="dinero_cliente" class="w-full border rounded px-3 py-2" step="0.01" value="" oninput="calcularTotales()">
                </div>

                <div>
                    <label for="cambio" class="block font-semibold mb-1">Cambio:</label>
                    <input type="number" name="cambio" id="cambio" class="w-full border rounded px-3 py-2" step="0.01" value="0.00" readonly>
                </div>
            </div>

            <!-- Campos para pago mixto -->
            <div id="mixto_campos" class="hidden space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded p-4">
                    <h3 class="font-semibold mb-3 text-blue-800">💳 Pago Dividido</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="pago_efectivo" class="block font-semibold mb-1">Pago en Efectivo:</label>
                            <input type="number" name="pago_efectivo" id="pago_efectivo" class="w-full border rounded px-3 py-2" step="0.01" value="0" oninput="calcularPagoMixto()">
                        </div>
                        <div>
                            <label for="pago_tarjeta" class="block font-semibold mb-1">Pago con Tarjeta:</label>
                            <input type="number" name="pago_tarjeta" id="pago_tarjeta" class="w-full border rounded px-3 py-2" step="0.01" value="0" oninput="calcularPagoMixto()">
                        </div>
                    </div>

                    <div class="mt-3 p-3 bg-white rounded border">
                        <div class="flex justify-between items-center text-sm mb-2">
                            <span class="text-gray-600">Total a Pagar:</span>
                            <span class="font-bold" id="mixto_total_pagar">€0.00</span>
                        </div>
                        <div class="flex justify-between items-center text-sm mb-2">
                            <span class="text-gray-600">Total Pagado:</span>
                            <span class="font-bold" id="mixto_total_pagado">€0.00</span>
                        </div>
                        <div class="flex justify-between items-center text-sm border-t pt-2">
                            <span class="font-semibold">Diferencia:</span>
                            <span class="font-bold text-lg" id="mixto_diferencia">€0.00</span>
                        </div>
                    </div>

                    <div id="mixto_error" class="hidden mt-3 p-3 bg-red-50 border border-red-300 rounded text-sm text-red-700">
                        ⚠️ <span id="mixto_error_texto"></span>
                    </div>
                    
                    <div id="mixto_success" class="hidden mt-3 p-3 bg-green-50 border border-green-300 rounded text-sm text-green-700">
                        ✓ El pago está completo
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mt-6">
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Registrar</button>
                    <button type="button" id="btn-ver-deuda" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">
                        Ver Deuda del Cliente
                    </button>
                </div>
                <a href="{{ route('cobros.index') }}" class="text-blue-600 hover:underline">Volver</a>
            </div>
        </form>
    </div>

 <!-- ========== MODAL (Tailwind) ========== -->
<div id="productos-modal" class="fixed inset-0 hidden items-center justify-center z-50">
  <div class="absolute inset-0 modal-backdrop"></div>
  <div class="bg-white rounded shadow-lg z-10 w-11/12 max-w-4xl p-4">
    <div class="flex justify-between items-center mb-3">
      <h3 class="text-xl font-semibold">Seleccionar productos</h3>
      <button id="btn-close-products" class="text-gray-600 hover:text-gray-900">Cerrar ✕</button>
    </div>

    <div id="productos-loading" class="text-sm text-gray-600 mb-2 hidden">Cargando productos...</div>

    <div class="overflow-auto max-h-72">
      <table class="w-full text-sm border-t" id="productos-list-table">
        <thead>
          <tr class="text-left">
            <th class="py-2">Nombre</th>
            <th class="py-2">Cantidad</th>
            <th class="py-2">Precio (€)</th>
            <th class="py-2">Stock</th>
            <th class="py-2"></th>
          </tr>
        </thead>
        <tbody>
          <!-- Se rellena dinámicamente con JS -->
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ========== MODAL DE DEUDA ========== -->
<div id="deuda-modal" class="fixed inset-0 hidden items-center justify-center z-50">
  <div class="absolute inset-0 modal-backdrop" onclick="cerrarModalDeuda()"></div>
  <div class="bg-white rounded shadow-lg z-10 w-11/12 max-w-3xl p-6 max-h-[90vh] overflow-y-auto">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-2xl font-bold">Gestión de Deuda del Cliente</h3>
      <button onclick="cerrarModalDeuda()" class="text-gray-600 hover:text-gray-900 text-2xl">&times;</button>
    </div>

    <div class="mb-4 p-4 bg-blue-50 rounded">
      <h4 class="font-semibold text-lg mb-2">Cliente</h4>
      <p id="deuda-cliente-nombre" class="text-lg"></p>
    </div>

    <div id="deuda-existente-section" class="mb-4 p-4 bg-red-50 rounded border border-red-200">
      <h4 class="font-semibold text-lg mb-2 text-red-700">⚠️ Deuda Existente</h4>
      <p class="text-2xl font-bold text-red-600" id="deuda-existente-monto">€0.00</p>
      <p class="text-sm text-gray-600 mt-1">El cliente tiene deuda pendiente de pagos anteriores</p>
      
      <!-- Formulario de pago de deuda existente -->
      <div class="mt-4 p-3 bg-white rounded border border-red-300">
        <h5 class="font-semibold text-sm mb-2">Registrar Pago de Deuda Existente</h5>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs text-gray-600">Deuda a Pagar</label>
            <input type="number" 
                   id="pago-deuda-existente" 
                   step="0.01" 
                   min="0" 
                   class="w-full border rounded px-2 py-1 text-sm"
                   placeholder="0.00">
            <p class="text-xs text-gray-500 mt-1">Máximo: <span id="max-pago-existente">€0.00</span></p>
          </div>
          <div>
            <label class="text-xs text-gray-600">Método de Pago</label>
            <select id="metodo-pago-existente" class="w-full border rounded px-2 py-1 text-sm">
              <option value="efectivo">Efectivo</option>
              <option value="tarjeta">Tarjeta</option>
              <option value="transferencia">Transferencia</option>
            </select>
          </div>
        </div>
        <button type="button" 
                onclick="registrarPagoDeudaExistente()"
                class="mt-2 w-full bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700 text-sm font-semibold">
          💰 Registrar Pago de Deuda Existente
        </button>
      </div>
    </div>

    <div class="mb-4 p-4 bg-yellow-50 rounded border border-yellow-200">
      <h4 class="font-semibold text-lg mb-2 text-yellow-700">Nueva Deuda (Este Cobro)</h4>
      <div class="grid grid-cols-2 gap-4 text-sm mb-2">
        <div>
          <p class="text-gray-600">Total a Pagar:</p>
          <p class="font-semibold" id="deuda-total-pagar">€0.00</p>
        </div>
        <div>
          <p class="text-gray-600">Dinero del Cliente:</p>
          <p class="font-semibold" id="deuda-dinero-cliente">€0.00</p>
        </div>
      </div>
      
      <!-- Modificar dinero del cliente -->
      <div class="mt-3 p-3 bg-white rounded border border-yellow-300">
        <h5 class="font-semibold text-sm mb-2">Ajustar Pago del Cliente</h5>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs text-gray-600">Nuevo Deuda que Paga</label>
            <input type="number" 
                   id="nuevo-dinero-cliente" 
                   step="0.01" 
                   min="0" 
                   class="w-full border rounded px-2 py-1 text-sm"
                   placeholder="0.00"
                   oninput="actualizarDineroClienteDesdeModal()">
            <p class="text-xs text-gray-500 mt-1">Total cobro: <span id="total-cobro-modal">€0.00</span></p>
          </div>
          <div class="flex items-end">
            <button type="button"
                    onclick="pagarTodoModal()"
                    class="w-full bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 text-sm">
              Pagar Todo
            </button>
          </div>
        </div>
      </div>
      
      <div class="border-t pt-2 mt-2">
        <p class="text-gray-600">Deuda que quedará:</p>
        <p class="text-2xl font-bold text-yellow-600" id="deuda-nueva-monto">€0.00</p>
      </div>
    </div>

    <div class="p-4 bg-gray-50 rounded border border-gray-200">
      <h4 class="font-semibold text-lg mb-2">Deuda Total Acumulada (Después del Cobro)</h4>
      <p class="text-3xl font-bold text-gray-800" id="deuda-total-acumulada">€0.00</p>
      <p class="text-sm text-gray-600 mt-1">Suma de deuda existente + deuda nueva</p>
    </div>

    <div class="mt-6 flex justify-end gap-2">
      <button onclick="cerrarModalDeuda()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
        Cerrar
      </button>
      <button onclick="aplicarCambiosYCerrar()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        Aplicar y Continuar
      </button>
    </div>
  </div>
</div>

</body>
</html>
