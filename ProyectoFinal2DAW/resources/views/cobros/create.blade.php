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
                <select name="id_cita" id="id_cita" required class="w-full border rounded px-3 py-2">
                    @foreach($citas as $cita)
                        @php
                            $costeTotal = $cita->servicios->sum('precio');
                            $nombresServicios = $cita->servicios->pluck('nombre')->implode(', ');
                        @endphp
                        <option value="{{ $cita->id }}" data-coste="{{ $costeTotal }}">
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

            <div>
                <label for="metodo_pago" class="block font-semibold mb-1">Método de Pago:</label>
                <select name="metodo_pago" id="metodo_pago" required class="w-full border rounded px-3 py-2" onchange="toggleEfectivoCampos()">
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                </select>
            </div>

            <div id="efectivo_campos">
                <div>
                    <label for="dinero_cliente" class="block font-semibold mb-1">Dinero del Cliente:</label>
                    <input type="number" name="dinero_cliente" id="dinero_cliente" class="w-full border rounded px-3 py-2" step="0.01" value="">
                </div>

                <div>
                    <label for="cambio" class="block font-semibold mb-1">Cambio:</label>
                    <input type="number" name="cambio" id="cambio" class="w-full border rounded px-3 py-2" step="0.01" value="0.00" readonly>
                </div>
            </div>

            <div class="flex justify-between items-center mt-6">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Registrar</button>
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
}

function toggleEfectivoCampos() {
  const metodoPago = document.getElementById('metodo_pago').value;
  const efectivoCampos = document.getElementById('efectivo_campos');
  if (metodoPago === 'tarjeta') {
    efectivoCampos.style.display = 'none';
    document.getElementById('dinero_cliente').value = '';
    document.getElementById('cambio').value = '';
  } else {
    efectivoCampos.style.display = 'block';
  }
}

window.addEventListener('load', () => {
  actualizarCosteYTotales();
  toggleEfectivoCampos();
});
</script>
</body>
</html>