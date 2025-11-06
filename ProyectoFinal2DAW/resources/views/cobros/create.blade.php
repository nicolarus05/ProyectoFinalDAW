<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Cobro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* simple modal backdrop */
        .modal-backdrop { background: rgba(0,0,0,0.5); }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
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

        <form action="{{ route('cobros.store') }}" method="POST" oninput="calcularTotales()" onchange="actualizarCosteYTotales()" class="space-y-4" id="cobro-form">
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
                <input type="number" name="coste" id="coste" required class="w-full border rounded px-3 py-2" step="0.01" value="0.00">
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

            <div class="flex space-x-4">
                <div class="flex-1">
                    <label for="descuento_porcentaje" class="block font-semibold mb-1">Descuento %:</label>
                    <input type="number" name="descuento_porcentaje" id="descuento_porcentaje" class="w-full border rounded px-3 py-2" step="0.01" value="0">
                </div>
                <div class="flex-1">
                    <label for="descuento_euro" class="block font-semibold mb-1">Descuento €:</label>
                    <input type="number" name="descuento_euro" id="descuento_euro" class="w-full border rounded px-3 py-2" step="0.01" value="0">
                </div>
            </div>

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

<!-- SCRIPT -->
<script>
// ---------- helpers ----------
function formatMoney(v){ return Number(v).toFixed(2); }
function escapeHtml(s){ return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#039;"}[m])); }

// ---------- variables ----------
const productosModal = document.getElementById('productos-modal');
const btnOpen = document.getElementById('btn-open-products');
const btnClose = document.getElementById('btn-close-products');
const productosTbody = document.querySelector('#productos-list-table tbody');
const loadingHint = document.getElementById('productos-loading');
const selectedTbody = document.querySelector('#selected-products-table tbody');
const totalCell = document.getElementById('selected-products-total');
let selectedIndex = 0;
let productosLoaded = false;
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// abrir modal
btnOpen.addEventListener('click', () => {
  productosModal.classList.remove('hidden');
  productosModal.classList.add('flex');
  if (!productosLoaded) loadProductos();
});

// cerrar modal
btnClose.addEventListener('click', () => {
  productosModal.classList.add('hidden');
  productosModal.classList.remove('flex');
});

// cargar productos desde Laravel
async function loadProductos(){
  loadingHint.classList.remove('hidden');
  productosTbody.innerHTML = '<tr><td colspan="5" class="py-4">Cargando...</td></tr>';
  try {
    const resp = await fetch('{{ route("productos.available") }}', {
      headers: { 'Accept':'application/json','X-CSRF-TOKEN': csrfToken },
      credentials: 'same-origin'
    });
    if (!resp.ok) throw new Error('Error cargando productos');
    const productos = await resp.json();
    productosLoaded = true;
    productosTbody.innerHTML = '';
    if (productos.length === 0) {
      productosTbody.innerHTML = '<tr><td colspan="5" class="py-4">No hay productos disponibles.</td></tr>';
      return;
    }

    productos.forEach(p => {
      const tr = document.createElement('tr');
      tr.className = 'border-b';
      tr.dataset.productId = p.id;
      tr.dataset.productName = p.nombre;
      tr.dataset.productPrice = p.precio_venta;
      tr.dataset.productStock = p.stock;

      tr.innerHTML = `
        <td class="py-2">${escapeHtml(p.nombre)}</td>
        <td class="py-2"><input type="number" min="1" max="${p.stock}" value="1" class="w-20 border rounded px-2 py-1 qty-input"></td>
        <td class="py-2">${formatMoney(p.precio_venta)}</td>
        <td class="py-2">${p.stock}</td>
        <td class="py-2">
          <button type="button" class="add-product-btn bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700">Agregar</button>
        </td>
      `;
      productosTbody.appendChild(tr);
    });
  } catch (err) {
    productosTbody.innerHTML = '<tr><td colspan="5" class="py-4 text-red-600">Error cargando productos.</td></tr>';
    console.error(err);
  } finally {
    loadingHint.classList.add('hidden');
  }
}

// manejar click en “Agregar”
productosTbody.addEventListener('click', e => {
  if (!e.target.matches('.add-product-btn')) return;
  const tr = e.target.closest('tr');
  const id = tr.dataset.productId;
  const nombre = tr.dataset.productName;
  const precio = parseFloat(tr.dataset.productPrice) || 0;
  const stock = parseInt(tr.dataset.productStock,10) || 0;
  const qty = parseInt(tr.querySelector('.qty-input').value,10) || 0;

  if (qty <= 0) { alert('Indica una cantidad válida.'); return; }
  if (qty > stock) { alert(`Stock insuficiente para ${nombre}. Disponible: ${stock}`); return; }

  addOrIncreaseSelected({ id, nombre, precio, cantidad: qty, stock });
});

// añadir producto a la tabla principal
function addOrIncreaseSelected({ id, nombre, precio, cantidad, stock }){
  let existing = selectedTbody.querySelector(`tr[data-product-id="${id}"]`);
  if (existing) {
    const qtyInput = existing.querySelector('input[name$="[cantidad]"]');
    const newQty = parseInt(qtyInput.value,10) + cantidad;
    if (newQty > stock) { alert(`No puedes superar el stock (${stock}).`); return; }
    qtyInput.value = newQty;
    updateRowSubtotal(existing);
  } else {
    const idx = selectedIndex++;
    const tr = document.createElement('tr');
    tr.dataset.productId = id;
    tr.className = 'border-b';
    tr.innerHTML = `
      <td class="py-2">
        ${escapeHtml(nombre)}
        <input type="hidden" name="products[${idx}][id]" value="${id}">
        <input type="hidden" name="products[${idx}][nombre]" value="${escapeHtml(nombre)}">
      </td>
      <td class="py-2">
        <input type="number" name="products[${idx}][cantidad]" value="${cantidad}" min="1" class="w-20 border rounded px-2 py-1 sel-qty">
      </td>
      <td class="py-2">
        <input type="number" step="0.01" name="products[${idx}][precio_venta]" value="${formatMoney(precio)}" min="0" class="w-28 border rounded px-2 py-1 sel-price">
      </td>
      <td class="py-2 text-right sel-subtotal">${formatMoney(cantidad * precio)}</td>
      <td class="py-2 text-right">
        <button type="button" class="remove-selected text-red-600 hover:underline">Eliminar</button>
      </td>
    `;
    selectedTbody.appendChild(tr);
    tr.querySelector('.sel-qty').addEventListener('input', () => updateRowSubtotal(tr));
    tr.querySelector('.sel-price').addEventListener('input', () => updateRowSubtotal(tr));
    tr.querySelector('.remove-selected').addEventListener('click', () => { tr.remove(); recalcTotal(); });
  }
  recalcTotal();
}

function updateRowSubtotal(tr){
  const qty = parseInt(tr.querySelector('.sel-qty').value,10) || 0;
  const price = parseFloat(tr.querySelector('.sel-price').value) || 0;
  tr.querySelector('.sel-subtotal').textContent = formatMoney(qty * price);
  recalcTotal();
}

function recalcTotal(){
  let total = 0;
  selectedTbody.querySelectorAll('tr').forEach(r => {
    total += parseFloat(r.querySelector('.sel-subtotal').textContent) || 0;
  });
  totalCell.textContent = formatMoney(total);
  calcularTotales();
}

// mantener cálculo global
function actualizarCosteYTotales() {
  const select = document.getElementById('id_cita');
  const selectedOption = select.options[select.selectedIndex];
  const costeServicio = parseFloat(selectedOption?.getAttribute('data-coste')) || 0;
  document.getElementById('coste').value = formatMoney(costeServicio);
  calcularTotales();
}

function calcularTotales() {
  const coste = parseFloat(document.getElementById('coste').value) || 0;
  const descPor = parseFloat(document.getElementById('descuento_porcentaje').value) || 0;
  const descEur = parseFloat(document.getElementById('descuento_euro').value) || 0;
  const dineroCliente = parseFloat(document.getElementById('dinero_cliente').value) || 0;
  const productosTotal = parseFloat(totalCell.textContent) || 0;
  const subtotal = coste + productosTotal;
  const descuentoTotal = (subtotal * (descPor / 100)) + descEur;
  const totalFinal = Math.max(subtotal - descuentoTotal, 0);
  const cambio = Math.max(dineroCliente - totalFinal, 0);
  document.getElementById('total_final').value = formatMoney(totalFinal);
  document.getElementById('cambio').value = formatMoney(cambio);
  
  // Actualizar alerta de deuda en tiempo real
  actualizarAlertaDeuda(totalFinal);
}

function actualizarAlertaDeuda(totalFinal) {
  const select = document.getElementById('id_cita');
  const selectedOption = select.options[select.selectedIndex];
  const deudaExistente = parseFloat(selectedOption?.getAttribute('data-deuda-existente')) || 0;
  const metodoPago = document.getElementById('metodo_pago').value;
  
  let dineroReal = 0;
  if (metodoPago === 'tarjeta') {
    dineroReal = totalFinal;
  } else if (metodoPago === 'mixto') {
    const pagoEfectivo = parseFloat(document.getElementById('pago_efectivo').value) || 0;
    const pagoTarjeta = parseFloat(document.getElementById('pago_tarjeta').value) || 0;
    dineroReal = pagoEfectivo + pagoTarjeta;
  } else {
    dineroReal = parseFloat(document.getElementById('dinero_cliente').value) || 0;
  }
  
  const nuevaDeuda = Math.max(0, totalFinal - dineroReal);
  const totalAcumulada = deudaExistente + nuevaDeuda;
  
  const alertaDeuda = document.getElementById('alerta-deuda');
  const alertaDeudaExistente = document.getElementById('alerta-deuda-existente');
  
  // Mostrar u ocultar alerta
  if (nuevaDeuda > 0 || deudaExistente > 0) {
    alertaDeuda.classList.remove('hidden');
    alertaDeuda.classList.remove('bg-gray-50', 'border-gray-200');
    
    if (nuevaDeuda > 0) {
      alertaDeuda.classList.add('bg-yellow-50', 'border-yellow-300');
    } else {
      alertaDeuda.classList.add('bg-blue-50', 'border-blue-300');
    }
  } else {
    alertaDeuda.classList.add('hidden');
  }
  
  // Actualizar valores
  if (deudaExistente > 0) {
    alertaDeudaExistente.classList.remove('hidden');
    document.getElementById('alerta-deuda-existente-monto').textContent = '€' + formatMoney(deudaExistente);
  } else {
    alertaDeudaExistente.classList.add('hidden');
  }
  
  document.getElementById('alerta-deuda-nueva-monto').textContent = '€' + formatMoney(nuevaDeuda);
  document.getElementById('alerta-deuda-total-monto').textContent = '€' + formatMoney(totalAcumulada);
}

function toggleMetodoPagoCampos() {
  const metodoPago = document.getElementById('metodo_pago').value;
  const efectivoCampos = document.getElementById('efectivo_campos');
  const mixtoCampos = document.getElementById('mixto_campos');
  
  if (metodoPago === 'tarjeta') {
    efectivoCampos.style.display = 'none';
    mixtoCampos.classList.add('hidden');
    document.getElementById('dinero_cliente').value = '';
    document.getElementById('cambio').value = '';
  } else if (metodoPago === 'mixto') {
    efectivoCampos.style.display = 'none';
    mixtoCampos.classList.remove('hidden');
    document.getElementById('dinero_cliente').value = '';
    document.getElementById('cambio').value = '';
    calcularPagoMixto();
  } else {
    efectivoCampos.style.display = 'block';
    mixtoCampos.classList.add('hidden');
  }
  calcularTotales();
}

function calcularPagoMixto() {
  const totalFinal = parseFloat(document.getElementById('total_final').value) || 0;
  const pagoEfectivo = parseFloat(document.getElementById('pago_efectivo').value) || 0;
  const pagoTarjeta = parseFloat(document.getElementById('pago_tarjeta').value) || 0;
  const totalPagado = pagoEfectivo + pagoTarjeta;
  const diferencia = totalPagado - totalFinal;
  
  // Actualizar visualización
  document.getElementById('mixto_total_pagar').textContent = '€' + formatMoney(totalFinal);
  document.getElementById('mixto_total_pagado').textContent = '€' + formatMoney(totalPagado);
  document.getElementById('mixto_diferencia').textContent = '€' + formatMoney(diferencia);
  
  const errorDiv = document.getElementById('mixto_error');
  const successDiv = document.getElementById('mixto_success');
  const errorTexto = document.getElementById('mixto_error_texto');
  
  // Validación
  if (Math.abs(diferencia) < 0.01) { // Pago exacto (tolerancia de 1 céntimo)
    errorDiv.classList.add('hidden');
    successDiv.classList.remove('hidden');
    document.getElementById('mixto_diferencia').className = 'font-bold text-lg text-green-600';
  } else if (diferencia > 0) {
    errorTexto.textContent = 'El total pagado excede el monto a pagar por €' + formatMoney(diferencia);
    errorDiv.classList.remove('hidden');
    successDiv.classList.add('hidden');
    document.getElementById('mixto_diferencia').className = 'font-bold text-lg text-red-600';
  } else {
    errorTexto.textContent = 'Falta pagar €' + formatMoney(Math.abs(diferencia));
    errorDiv.classList.remove('hidden');
    successDiv.classList.add('hidden');
    document.getElementById('mixto_diferencia').className = 'font-bold text-lg text-yellow-600';
  }
  
  // Actualizar deuda en tiempo real para pago mixto
  actualizarAlertaDeuda(totalFinal);
}

window.addEventListener('load', () => {
  actualizarCosteYTotales();
  toggleMetodoPagoCampos();
  actualizarClienteInfo();
});

// ========== FUNCIONES PARA MODAL DE DEUDA ==========
const btnVerDeuda = document.getElementById('btn-ver-deuda');
const deudaModal = document.getElementById('deuda-modal');

btnVerDeuda.addEventListener('click', abrirModalDeuda);

function abrirModalDeuda() {
  calcularYMostrarDeuda();
  deudaModal.classList.remove('hidden');
  deudaModal.classList.add('flex');
}

function cerrarModalDeuda() {
  deudaModal.classList.add('hidden');
  deudaModal.classList.remove('flex');
}

function actualizarClienteInfo() {
  const select = document.getElementById('id_cita');
  const selectedOption = select.options[select.selectedIndex];
  const costeServicio = parseFloat(selectedOption?.getAttribute('data-coste')) || 0;
  document.getElementById('coste').value = formatMoney(costeServicio);
  calcularTotales();
}

function calcularYMostrarDeuda() {
  const select = document.getElementById('id_cita');
  const selectedOption = select.options[select.selectedIndex];
  
  const clienteNombre = selectedOption?.getAttribute('data-cliente-nombre') || 'N/A';
  const deudaExistente = parseFloat(selectedOption?.getAttribute('data-deuda-existente')) || 0;
  const totalFinal = parseFloat(document.getElementById('total_final').value) || 0;
  const dineroCliente = parseFloat(document.getElementById('dinero_cliente').value) || 0;
  const metodoPago = document.getElementById('metodo_pago').value;
  
  // Si es tarjeta, el dinero del cliente es igual al total
  const dineroReal = metodoPago === 'tarjeta' ? totalFinal : dineroCliente;
  
  // Calcular nueva deuda
  const nuevaDeuda = Math.max(0, totalFinal - dineroReal);
  const totalAcumulada = deudaExistente + nuevaDeuda;
  
  // Actualizar modal
  document.getElementById('deuda-cliente-nombre').textContent = clienteNombre;
  document.getElementById('deuda-existente-monto').textContent = '€' + formatMoney(deudaExistente);
  document.getElementById('deuda-total-pagar').textContent = '€' + formatMoney(totalFinal);
  document.getElementById('deuda-dinero-cliente').textContent = '€' + formatMoney(dineroReal);
  document.getElementById('deuda-nueva-monto').textContent = '€' + formatMoney(nuevaDeuda);
  document.getElementById('deuda-total-acumulada').textContent = '€' + formatMoney(totalAcumulada);
  
  // Configurar campos del modal
  document.getElementById('max-pago-existente').textContent = '€' + formatMoney(deudaExistente);
  document.getElementById('pago-deuda-existente').max = deudaExistente;
  document.getElementById('pago-deuda-existente').value = '';
  document.getElementById('nuevo-dinero-cliente').value = formatMoney(dineroReal);
  document.getElementById('total-cobro-modal').textContent = '€' + formatMoney(totalFinal);
  
  // Mostrar/ocultar sección de deuda existente
  const deudaExistenteSection = document.getElementById('deuda-existente-section');
  if (deudaExistente > 0) {
    deudaExistenteSection.classList.remove('hidden');
  } else {
    deudaExistenteSection.classList.add('hidden');
  }
}

// Actualizar dinero del cliente desde el modal
function actualizarDineroClienteDesdeModal() {
  const nuevoDinero = parseFloat(document.getElementById('nuevo-dinero-cliente').value) || 0;
  const totalFinal = parseFloat(document.getElementById('total_final').value) || 0;
  const select = document.getElementById('id_cita');
  const selectedOption = select.options[select.selectedIndex];
  const deudaExistente = parseFloat(selectedOption?.getAttribute('data-deuda-existente')) || 0;
  
  // Calcular nueva deuda
  const nuevaDeuda = Math.max(0, totalFinal - nuevoDinero);
  const totalAcumulada = deudaExistente + nuevaDeuda;
  
  // Actualizar visualización en el modal
  document.getElementById('deuda-dinero-cliente').textContent = '€' + formatMoney(nuevoDinero);
  document.getElementById('deuda-nueva-monto').textContent = '€' + formatMoney(nuevaDeuda);
  document.getElementById('deuda-total-acumulada').textContent = '€' + formatMoney(totalAcumulada);
}

// Pagar todo desde el modal
function pagarTodoModal() {
  const totalFinal = parseFloat(document.getElementById('total_final').value) || 0;
  document.getElementById('nuevo-dinero-cliente').value = formatMoney(totalFinal);
  actualizarDineroClienteDesdeModal();
}

// Aplicar cambios y cerrar modal
function aplicarCambiosYCerrar() {
  const nuevoDinero = parseFloat(document.getElementById('nuevo-dinero-cliente').value) || 0;
  const metodoPago = document.getElementById('metodo_pago').value;
  
  // Solo actualizar si es efectivo
  if (metodoPago === 'efectivo') {
    document.getElementById('dinero_cliente').value = formatMoney(nuevoDinero);
    calcularTotales();
  }
  
  cerrarModalDeuda();
}

// Registrar pago de deuda existente
async function registrarPagoDeudaExistente() {
  const select = document.getElementById('id_cita');
  const selectedOption = select.options[select.selectedIndex];
  const clienteId = selectedOption?.getAttribute('data-cliente-id');
  const montoPago = parseFloat(document.getElementById('pago-deuda-existente').value) || 0;
  const metodoPago = document.getElementById('metodo-pago-existente').value;
  const deudaExistente = parseFloat(selectedOption?.getAttribute('data-deuda-existente')) || 0;
  
  // Validaciones
  if (!clienteId) {
    alert('Error: No se ha seleccionado un cliente válido.');
    return;
  }
  
  if (montoPago <= 0) {
    alert('Por favor ingresa un monto válido mayor a 0.');
    return;
  }
  
  if (montoPago > deudaExistente) {
    alert(`El monto no puede ser mayor a la deuda existente (€${formatMoney(deudaExistente)})`);
    return;
  }
  
  if (!confirm(`¿Confirmar pago de €${formatMoney(montoPago)} para la deuda existente?`)) {
    return;
  }
  
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const response = await fetch(`/deudas/cliente/${clienteId}/pago`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        monto: montoPago,
        metodo_pago: metodoPago,
        nota: 'Pago registrado desde formulario de cobro'
      })
    });
    
    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Error al registrar el pago');
    }
    
    const result = await response.json();
    
    // Actualizar la deuda existente en el select
    const nuevaDeudaExistente = deudaExistente - montoPago;
    selectedOption.setAttribute('data-deuda-existente', nuevaDeudaExistente);
    
    // Mostrar mensaje de éxito
    alert(`✓ Pago registrado exitosamente.\nDeuda restante: €${formatMoney(nuevaDeudaExistente)}`);
    
    // Actualizar el modal
    calcularYMostrarDeuda();
    calcularTotales();
    
  } catch (error) {
    console.error('Error:', error);
    alert('Error al registrar el pago: ' + error.message);
  }
}

// Si hay una cita preseleccionada, actualizar la información automáticamente
@if($citaSeleccionada)
document.addEventListener('DOMContentLoaded', function() {
    actualizarClienteInfo();
    actualizarCosteYTotales();
    calcularTotales();
});
@endif

</script>
</body>
</html>