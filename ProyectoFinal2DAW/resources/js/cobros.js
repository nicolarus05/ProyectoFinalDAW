// ---------- helpers ----------
function formatMoney(v){ return Number(v).toFixed(2); }
function escapeHtml(s){ return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#039;"}[m])); }

// Variable global para timeout de búsqueda
let productSearchTimeout;

document.addEventListener('DOMContentLoaded', function() {
    const appElement = document.getElementById('cobros-app');
    const productosAvailableUrl = appElement?.dataset.productosUrl || '';
    const pagoDeudaUrl = appElement?.dataset.pagoDeudaUrl || '';
    const citaPreseleccionada = appElement?.dataset.citaPreseleccionada === 'true';
    
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
    async function loadProductos(searchQuery = ''){
      loadingHint.classList.remove('hidden');
      productosTbody.innerHTML = '<tr><td colspan="5" class="py-4">Cargando...</td></tr>';
      
      // Construir URL con parámetro de búsqueda si existe
      let url = productosAvailableUrl;
      if (searchQuery) {
        url += (url.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(searchQuery);
      }
      
      console.log('URL productos:', url);
      
      try {
        const resp = await fetch(url, {
          headers: { 'Accept':'application/json','X-CSRF-TOKEN': csrfToken },
          credentials: 'same-origin'
        });
        
        console.log('Respuesta status:', resp.status);
        
        if (!resp.ok) {
          const errorText = await resp.text();
          console.error('Error respuesta:', errorText);
          throw new Error(`Error ${resp.status}: ${errorText.substring(0, 100)}`);
        }
        
        const productos = await resp.json();
        console.log('Productos cargados:', productos);
        
        productosLoaded = true;
        productosTbody.innerHTML = '';
        if (productos.length === 0) {
          productosTbody.innerHTML = '<tr><td colspan="5" class="py-4">No se encontraron productos.</td></tr>';
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
        const errorMsg = err.message || 'Error desconocido';
        productosTbody.innerHTML = `<tr><td colspan="5" class="py-4 text-red-600">Error: ${escapeHtml(errorMsg)}</td></tr>`;
        console.error('Error completo:', err);
      } finally {
        loadingHint.classList.add('hidden');
      }
    }

    // manejar click en "Agregar"
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

        // Generar opciones de empleados
        const empleados = window.empleadosData || [];
        let empleadoOptions = '<option value="">-- Seleccionar --</option>';
        empleados.forEach(emp => {
          empleadoOptions += `<option value="${emp.id}">${escapeHtml(emp.nombre)}</option>`;
        });

        tr.innerHTML = `
          <td class="py-2">
            ${escapeHtml(nombre)}
            <input type="hidden" name="products[${idx}][id]" value="${id}">
            <input type="hidden" name="products[${idx}][nombre]" value="${escapeHtml(nombre)}">
          </td>
          <td class="py-2">
            <select name="products[${idx}][empleado_id]" class="w-full border rounded px-2 py-1 text-sm sel-empleado">
              ${empleadoOptions}
            </select>
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
    window.actualizarCosteYTotales = function() {
      const select = document.getElementById('id_cita');
      const selectedOption = select.options[select.selectedIndex];
      const costeServicio = parseFloat(selectedOption?.getAttribute('data-coste')) || 0;
      document.getElementById('coste').value = formatMoney(costeServicio);
      calcularTotales();
    };

    window.calcularTotales = function() {
      const coste = parseFloat(document.getElementById('coste').value) || 0;
      
      // Descuentos para servicios
      const descServiciosPor = parseFloat(document.getElementById('descuento_servicios_porcentaje').value) || 0;
      const descServiciosEur = parseFloat(document.getElementById('descuento_servicios_euro').value) || 0;
      
      // Descuentos para productos
      const descProductosPor = parseFloat(document.getElementById('descuento_productos_porcentaje').value) || 0;
      const descProductosEur = parseFloat(document.getElementById('descuento_productos_euro').value) || 0;
      
      const dineroCliente = parseFloat(document.getElementById('dinero_cliente').value) || 0;
      const productosTotal = parseFloat(totalCell.textContent) || 0;
      
      // Calcular descuentos por bonos activos
      let descuentoBonosActivos = 0;
      const select = document.getElementById('id_cita');
      const citaId = select.value;
      
      if (window.citasData && citaId && window.citasData[citaId]) {
        const citaData = window.citasData[citaId];
        const serviciosCita = citaData.servicios || [];
        const bonosCliente = citaData.bonos || [];
        
        // Calcular qué servicios están cubiertos por bonos
        serviciosCita.forEach(servicio => {
          for (let bono of bonosCliente) {
            const servicioEnBono = bono.servicios.find(s => s.id === servicio.id);
            if (servicioEnBono && servicioEnBono.disponibles > 0) {
              descuentoBonosActivos += servicio.precio;
              break; // Este servicio está cubierto, pasar al siguiente
            }
          }
        });
      }
      
      // Calcular descuentos separados
      const descuentoServicios = (coste * (descServiciosPor / 100)) + descServiciosEur;
      const descuentoProductos = (productosTotal * (descProductosPor / 100)) + descProductosEur;
      
      // Total con descuentos aplicados (incluyendo bonos)
      const totalServicios = Math.max(coste - descuentoServicios - descuentoBonosActivos, 0);
      const totalProductos = Math.max(productosTotal - descuentoProductos, 0);
      const totalFinal = totalServicios + totalProductos;
      
      const cambio = Math.max(dineroCliente - totalFinal, 0);
      document.getElementById('total_final').value = formatMoney(totalFinal);
      document.getElementById('cambio').value = formatMoney(cambio);
      
      // Actualizar campos antiguos para compatibilidad (suma de ambos descuentos)
      const descuentoTotalPorcentaje = descServiciosPor + descProductosPor;
      const descuentoTotalEuro = descServiciosEur + descProductosEur;
      document.getElementById('descuento_porcentaje').value = formatMoney(descuentoTotalPorcentaje);
      document.getElementById('descuento_euro').value = formatMoney(descuentoTotalEuro);
      
      // Actualizar alerta de deuda en tiempo real
      actualizarAlertaDeuda(totalFinal);
    };

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

    window.toggleMetodoPagoCampos = function() {
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
    };

    window.calcularPagoMixto = function() {
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
    };

    // ========== FUNCIONES PARA MODAL DE DEUDA ==========
    const btnVerDeuda = document.getElementById('btn-ver-deuda');
    const deudaModal = document.getElementById('deuda-modal');

    btnVerDeuda.addEventListener('click', abrirModalDeuda);

    function abrirModalDeuda() {
      calcularYMostrarDeuda();
      deudaModal.classList.remove('hidden');
      deudaModal.classList.add('flex');
    }

    window.cerrarModalDeuda = function() {
      deudaModal.classList.add('hidden');
      deudaModal.classList.remove('flex');
    };

    window.actualizarClienteInfo = function() {
      const select = document.getElementById('id_cita');
      const selectedOption = select.options[select.selectedIndex];
      const costeServicio = parseFloat(selectedOption?.getAttribute('data-coste')) || 0;
      document.getElementById('coste').value = formatMoney(costeServicio);
      calcularTotales();
    };

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
    window.actualizarDineroClienteDesdeModal = function() {
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
    };

    // Pagar todo desde el modal
    window.pagarTodoModal = function() {
      const totalFinal = parseFloat(document.getElementById('total_final').value) || 0;
      document.getElementById('nuevo-dinero-cliente').value = formatMoney(totalFinal);
      actualizarDineroClienteDesdeModal();
    };

    // Aplicar cambios y cerrar modal
    window.aplicarCambiosYCerrar = function() {
      const nuevoDinero = parseFloat(document.getElementById('nuevo-dinero-cliente').value) || 0;
      const metodoPago = document.getElementById('metodo_pago').value;
      
      // Solo actualizar si es efectivo
      if (metodoPago === 'efectivo') {
        document.getElementById('dinero_cliente').value = formatMoney(nuevoDinero);
        calcularTotales();
      }
      
      cerrarModalDeuda();
    };

    // Registrar pago de deuda existente
    window.registrarPagoDeudaExistente = async function() {
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
        const response = await fetch(`${pagoDeudaUrl.replace(':id', clienteId)}`, {
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
    };

    // Inicializar al cargar la página
    if (citaPreseleccionada) {
        // Si hay cita preseleccionada, actualizar todos los valores
        actualizarClienteInfo();
    } else {
        // Si no hay cita preseleccionada, solo inicializar el método de pago
        toggleMetodoPagoCampos();
    }
    
    // Exponer loadProductos globalmente para búsqueda
    window.loadProductos = loadProductos;
});

// Función de búsqueda de productos con debounce
window.searchProducts = function() {
    const busqueda = document.getElementById('buscar-producto-modal').value.trim();
    
    // Limpiar timeout anterior
    clearTimeout(productSearchTimeout);
    
    // Esperar 500ms después de que el usuario deje de escribir
    productSearchTimeout = setTimeout(() => {
        window.loadProductos(busqueda);
    }, 500);
};
