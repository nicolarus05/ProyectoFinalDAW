<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Cobro Directo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .modal-backdrop { background: rgba(0,0,0,0.5); }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">💰 Nuevo Cobro Directo</h1>
            <a href="{{ route('cobros.index') }}" class="text-blue-600 hover:underline">← Volver a cobros</a>
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

            <!-- Cliente -->
            <div class="bg-gray-50 p-4 rounded">
                <h2 class="text-lg font-semibold mb-3">Cliente</h2>
                <div>
                    <label for="id_cliente" class="block font-semibold mb-1">Seleccionar cliente:</label>
                    <select name="id_cliente" id="id_cliente" class="w-full border rounded px-3 py-2" onchange="actualizarDeudaCliente()">
                        <option value="">-- Sin cliente --</option>
                        @foreach($clientes as $cliente)
                            @php
                                $deuda = $cliente->deuda ? $cliente->deuda->saldo_pendiente : 0;
                            @endphp
                            <option value="{{ $cliente->id }}" data-deuda="{{ $deuda }}">
                                {{ $cliente->user->nombre ?? '' }} {{ $cliente->user->apellidos ?? '' }}
                                @if($deuda > 0) (Deuda: €{{ number_format($deuda, 2) }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <p id="deuda-info" class="text-sm text-gray-600 mt-2"></p>
            </div>

            <!-- Empleado -->
            <div class="bg-gray-50 p-4 rounded">
                <h2 class="text-lg font-semibold mb-3">Empleado que atiende</h2>
                <div>
                    <label for="id_empleado" class="block font-semibold mb-1">Seleccionar empleado:</label>
                    <select name="id_empleado" id="id_empleado" class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccionar empleado --</option>
                        @foreach($empleados as $empleado)
                            <option value="{{ $empleado->id }}">
                                {{ $empleado->user->nombre ?? '' }} {{ $empleado->user->apellidos ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
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
                            <th class="p-2 text-right">Precio</th>
                            <th class="p-2"></th>
                        </tr>
                    </thead>
                    <tbody id="services-tbody">
                        <!-- Servicios añadidos dinámicamente -->
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-300">
                            <td class="p-2 text-right font-semibold">Total servicios:</td>
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

            <!-- Descuentos -->
            <div class="bg-gray-50 p-4 rounded">
                <h2 class="text-lg font-semibold mb-3">Descuentos</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="descuento_porcentaje" class="block font-semibold mb-1">Descuento %:</label>
                        <input type="number" name="descuento_porcentaje" id="descuento_porcentaje" class="w-full border rounded px-3 py-2" step="0.01" value="0" min="0" max="100" oninput="calcularTotales()">
                    </div>
                    <div>
                        <label for="descuento_euro" class="block font-semibold mb-1">Descuento €:</label>
                        <input type="number" name="descuento_euro" id="descuento_euro" class="w-full border rounded px-3 py-2" step="0.01" value="0" min="0" oninput="calcularTotales()">
                    </div>
                </div>
            </div>

            <!-- Resumen y pago -->
            <div class="bg-green-50 border-2 border-green-600 p-4 rounded">
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

            <!-- Campos ocultos para productos y servicios -->
            <input type="hidden" name="productos_data" id="productos_data" value="[]">
            <input type="hidden" name="servicios_data" id="servicios_data" value="[]">

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
            <div class="max-h-96 overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 sticky top-0">
                        <tr>
                            <th class="p-2 text-left">Servicio</th>
                            <th class="p-2 text-right">Precio</th>
                            <th class="p-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($servicios as $servicio)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-2">{{ $servicio->nombre }}</td>
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

<script>
// Variables globales
let serviciosSeleccionados = [];
let productosSeleccionados = [];
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Actualizar deuda del cliente
function actualizarDeudaCliente() {
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

// Modal de servicios
document.getElementById('btn-add-service').addEventListener('click', function() {
    document.getElementById('modal-services').classList.remove('hidden');
});

function closeModalServices() {
    document.getElementById('modal-services').classList.add('hidden');
}

function addService(id, nombre, precio) {
    // Verificar si ya está añadido
    if (serviciosSeleccionados.find(s => s.id === id)) {
        alert('Este servicio ya está añadido');
        return;
    }
    
    serviciosSeleccionados.push({ id, nombre, precio });
    renderServicios();
    closeModalServices();
    calcularTotales();
}

function removeService(id) {
    serviciosSeleccionados = serviciosSeleccionados.filter(s => s.id !== id);
    renderServicios();
    calcularTotales();
}

function renderServicios() {
    const tbody = document.getElementById('services-tbody');
    tbody.innerHTML = '';
    
    if (serviciosSeleccionados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="p-2 text-center text-gray-500">No hay servicios añadidos</td></tr>';
        return;
    }
    
    serviciosSeleccionados.forEach(servicio => {
        const tr = document.createElement('tr');
        tr.className = 'border-b';
        tr.innerHTML = `
            <td class="p-2">${servicio.nombre}</td>
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

function closeModalProducts() {
    document.getElementById('modal-products').classList.add('hidden');
}

async function loadProducts() {
    const loading = document.getElementById('products-loading');
    const content = document.getElementById('products-content');
    const tbody = document.getElementById('modal-products-tbody');
    
    loading.classList.remove('hidden');
    content.classList.add('hidden');
    
    try {
        const response = await fetch('{{ route("productos.available") }}', {
            headers: { 'Accept': 'application/json' }
        });
        
        if (!response.ok) throw new Error('Error al cargar productos');
        
        const productos = await response.json();
        tbody.innerHTML = '';
        
        productos.forEach(producto => {
            const tr = document.createElement('tr');
            tr.className = 'border-b hover:bg-gray-50';
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
        
        loading.classList.add('hidden');
        content.classList.remove('hidden');
    } catch (error) {
        loading.textContent = 'Error al cargar productos';
        console.error(error);
    }
}

function addProduct(id, nombre, precio, stock) {
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

function removeProduct(id) {
    productosSeleccionados = productosSeleccionados.filter(p => p.id !== id);
    renderProductos();
    calcularTotales();
}

function updateProductQty(id, cantidad) {
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
function calcularTotales() {
    // Calcular total de servicios
    const totalServicios = serviciosSeleccionados.reduce((sum, s) => sum + parseFloat(s.precio), 0);
    document.getElementById('services-total').textContent = `€${totalServicios.toFixed(2)}`;
    
    // Calcular total de productos
    const totalProductos = productosSeleccionados.reduce((sum, p) => sum + (parseFloat(p.precio) * parseInt(p.cantidad)), 0);
    document.getElementById('products-total').textContent = `€${totalProductos.toFixed(2)}`;
    
    // Subtotal
    const subtotal = totalServicios + totalProductos;
    document.getElementById('subtotal').textContent = `€${subtotal.toFixed(2)}`;
    document.getElementById('coste').value = subtotal.toFixed(2);
    
    // Descuentos
    const descPorcentaje = parseFloat(document.getElementById('descuento_porcentaje').value || 0);
    const descEuro = parseFloat(document.getElementById('descuento_euro').value || 0);
    const descuentoPorPorcentaje = (subtotal * descPorcentaje) / 100;
    const totalDescuentos = descuentoPorPorcentaje + descEuro;
    document.getElementById('descuentos-total').textContent = `-€${totalDescuentos.toFixed(2)}`;
    
    // Total final
    const totalFinal = Math.max(0, subtotal - totalDescuentos);
    document.getElementById('total-final').textContent = `€${totalFinal.toFixed(2)}`;
    document.getElementById('total_final_input').value = totalFinal.toFixed(2);
    
    // Recalcular según método de pago
    const metodo = document.querySelector('input[name="metodo_pago"]:checked');
    if (metodo) {
        if (metodo.value === 'efectivo') calcularCambio();
        if (metodo.value === 'mixto') calcularPagoMixto();
    }
}

// Cambiar método de pago
function cambiarMetodoPago() {
    const metodo = document.querySelector('input[name="metodo_pago"]:checked').value;
    
    document.getElementById('pago-efectivo').classList.add('hidden');
    document.getElementById('pago-mixto').classList.add('hidden');
    
    if (metodo === 'efectivo') {
        document.getElementById('pago-efectivo').classList.remove('hidden');
    } else if (metodo === 'mixto') {
        document.getElementById('pago-mixto').classList.remove('hidden');
    }
}

function calcularCambio() {
    const totalFinal = parseFloat(document.getElementById('total_final_input').value || 0);
    const dineroCliente = parseFloat(document.getElementById('dinero_cliente').value || 0);
    const cambio = Math.max(0, dineroCliente - totalFinal);
    
    document.getElementById('cambio-display').textContent = `€${cambio.toFixed(2)}`;
    document.getElementById('cambio').value = cambio.toFixed(2);
}

function calcularPagoMixto() {
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
    if (serviciosSeleccionados.length === 0 && productosSeleccionados.length === 0) {
        e.preventDefault();
        alert('Debe añadir al menos un servicio o producto');
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
</script>

</body>
</html>
