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
      const citaId = select.value;
      // Cargar servicios y recalcular desde la tabla editable
      cargarServiciosDeCita(citaId);
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
        // Usar serviciosSeleccionados (editados por el usuario) en vez de los de la cita original
        const serviciosActuales = serviciosSeleccionados || [];
        const bonosCliente = citaData.bonos || [];
        
        // Calcular qué servicios están cubiertos por bonos
        // Clonar disponibilidad para decrementar conforme se asignan servicios
        const disponibilidadBonos = {};
        bonosCliente.forEach(bono => {
          disponibilidadBonos[bono.id] = {};
          bono.servicios.forEach(s => {
            disponibilidadBonos[bono.id][s.id] = s.disponibles || 0;
          });
        });

        serviciosActuales.forEach(servicio => {
          for (let bono of bonosCliente) {
            const servicioEnBono = bono.servicios.find(s => s.id === servicio.id);
            if (servicioEnBono && disponibilidadBonos[bono.id][servicio.id] > 0) {
              descuentoBonosActivos += servicio.precio;
              disponibilidadBonos[bono.id][servicio.id]--; // Decrementar para no reutilizar
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
      const dineroInput = document.getElementById('dinero_cliente');
      
      // dinero_cliente es obligatorio solo para efectivo
      dineroInput.required = (metodoPago === 'efectivo');
      
      if (metodoPago === 'tarjeta') {
        efectivoCampos.style.display = 'none';
        mixtoCampos.classList.add('hidden');
        dineroInput.value = '';
        document.getElementById('cambio').value = '';
      } else if (metodoPago === 'mixto') {
        efectivoCampos.style.display = 'none';
        mixtoCampos.classList.remove('hidden');
        dineroInput.value = '';
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

    // ========== GESTIÓN DE SERVICIOS EDITABLES ==========
    let serviciosSeleccionados = [];
    const serviciosTbody = document.getElementById('servicios-tbody');
    const serviciosTotalCell = document.getElementById('servicios-total');
    const addServicioRow = document.getElementById('add-servicio-row');
    const btnAddServicio = document.getElementById('btn-add-servicio');
    const btnConfirmAddServicio = document.getElementById('btn-confirm-add-servicio');
    const btnCancelAddServicio = document.getElementById('btn-cancel-add-servicio');

    if (btnAddServicio) {
        btnAddServicio.addEventListener('click', () => {
            addServicioRow.classList.remove('hidden');
        });
    }
    if (btnCancelAddServicio) {
        btnCancelAddServicio.addEventListener('click', () => {
            addServicioRow.classList.add('hidden');
            document.getElementById('nuevo-servicio-select').value = '';
        });
    }
    if (btnConfirmAddServicio) {
        btnConfirmAddServicio.addEventListener('click', () => {
            const sel = document.getElementById('nuevo-servicio-select');
            const opt = sel.options[sel.selectedIndex];
            if (!sel.value) { alert('Selecciona un servicio.'); return; }
            
            const id = parseInt(sel.value);
            const nombre = opt.dataset.nombre;
            const precio = parseFloat(opt.dataset.precio) || 0;
            
            serviciosSeleccionados.push({ id, nombre, precio, empleado_id: null });
            renderServiciosTable();
            addServicioRow.classList.add('hidden');
            sel.value = '';
        });
    }

    function cargarServiciosDeCita(citaId) {
        serviciosSeleccionados = [];
        if (window.citasData && citaId && window.citasData[citaId]) {
            const serviciosCita = window.citasData[citaId].servicios || [];
            serviciosCita.forEach(s => {
                serviciosSeleccionados.push({
                    id: s.id,
                    nombre: s.nombre,
                    precio: parseFloat(s.precio) || 0,
                    empleado_id: s.empleado_id || null
                });
            });
        }
        renderServiciosTable();
    }

    function renderServiciosTable() {
        if (!serviciosTbody) return;
        serviciosTbody.innerHTML = '';
        
        if (serviciosSeleccionados.length === 0) {
            serviciosTbody.innerHTML = '<tr><td colspan="4" class="text-center text-gray-400 py-3">No hay servicios seleccionados</td></tr>';
        } else {
            const empleados = window.empleadosData || [];
            serviciosSeleccionados.forEach((s, idx) => {
                let empleadoOptions = '<option value="">-- Empleado --</option>';
                empleados.forEach(emp => {
                    const selected = s.empleado_id == emp.id ? 'selected' : '';
                    empleadoOptions += `<option value="${emp.id}" ${selected}>${escapeHtml(emp.nombre)}</option>`;
                });

                const tr = document.createElement('tr');
                tr.className = 'border-b';
                tr.innerHTML = `
                    <td class="py-2">${escapeHtml(s.nombre)}</td>
                    <td class="py-2">
                        <select class="w-full border rounded px-2 py-1 text-sm servicio-empleado-select" data-idx="${idx}">
                            ${empleadoOptions}
                        </select>
                    </td>
                    <td class="py-2 text-right">
                        <input type="number" step="0.01" min="0" value="${formatMoney(s.precio)}" class="w-24 border rounded px-2 py-1 text-right servicio-precio-input" data-idx="${idx}">
                    </td>
                    <td class="py-2 text-right">
                        <button type="button" class="remove-servicio text-red-600 hover:underline text-sm" data-idx="${idx}">Eliminar</button>
                    </td>
                `;
                serviciosTbody.appendChild(tr);
            });
        }
        
        // Event listeners
        serviciosTbody.querySelectorAll('.remove-servicio').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const idx = parseInt(e.target.dataset.idx);
                serviciosSeleccionados.splice(idx, 1);
                renderServiciosTable();
            });
        });
        serviciosTbody.querySelectorAll('.servicio-empleado-select').forEach(sel => {
            sel.addEventListener('change', (e) => {
                const idx = parseInt(e.target.dataset.idx);
                serviciosSeleccionados[idx].empleado_id = e.target.value ? parseInt(e.target.value) : null;
                actualizarServiciosData();
            });
        });
        serviciosTbody.querySelectorAll('.servicio-precio-input').forEach(inp => {
            inp.addEventListener('input', (e) => {
                const idx = parseInt(e.target.dataset.idx);
                serviciosSeleccionados[idx].precio = parseFloat(e.target.value) || 0;
                actualizarServiciosData();
                recalcServiciosTotal();
            });
        });
        
        actualizarServiciosData();
        recalcServiciosTotal();
    }

    function recalcServiciosTotal() {
        const total = serviciosSeleccionados.reduce((sum, s) => sum + (parseFloat(s.precio) || 0), 0);
        if (serviciosTotalCell) {
            serviciosTotalCell.textContent = '€' + formatMoney(total);
        }
        // Actualizar el campo coste
        const costeInput = document.getElementById('coste');
        if (costeInput) {
            costeInput.value = formatMoney(total);
        }
        calcularTotales();
    }

    function actualizarServiciosData() {
        const input = document.getElementById('servicios_data');
        if (input) {
            input.value = JSON.stringify(serviciosSeleccionados);
        }
    }

    window.actualizarClienteInfo = function() {
      const select = document.getElementById('id_cita');
      const selectedOption = select.options[select.selectedIndex];
      const citaId = select.value;
      
      // Cargar servicios editables de la cita seleccionada
      cargarServiciosDeCita(citaId);
      
      // El coste ahora se calcula desde la tabla de servicios, no desde data-coste
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

    // --- Serializar productos como JSON al enviar el formulario (unifica con create-direct) ---
    const cobroForm = document.getElementById('cobro-form');
    if (cobroForm) {
      cobroForm.addEventListener('submit', function() {
        // --- APLICAR DESCUENTO DE SERVICIOS A PRECIOS INDIVIDUALES ---
        // Antes de enviar, distribuir el descuento proporcionalmente a cada servicio
        // para que servicios_data contenga precios finales (no catálogo + descuento aparte).
        // Esto es CRÍTICO para la correcta facturación por empleado.
        const descServPct = parseFloat(document.getElementById('descuento_servicios_porcentaje').value) || 0;
        const descServEur = parseFloat(document.getElementById('descuento_servicios_euro').value) || 0;
        const descGenPct = parseFloat(document.getElementById('descuento_porcentaje').value) || 0;
        const descGenEur = parseFloat(document.getElementById('descuento_euro').value) || 0;

        if ((descServPct > 0 || descServEur > 0 || descGenPct > 0 || descGenEur > 0) && serviciosSeleccionados.length > 0) {
          const sumaPrecios = serviciosSeleccionados.reduce((sum, s) => sum + (parseFloat(s.precio) || 0), 0);
          if (sumaPrecios > 0.01) {
            const descuentoTotal = (sumaPrecios * (descServPct / 100)) + descServEur;
            const sumaObjetivo = Math.max(sumaPrecios - descuentoTotal, 0);
            const factor = sumaObjetivo / sumaPrecios;

            serviciosSeleccionados.forEach(s => {
              s.precio = Math.round(parseFloat(s.precio) * factor * 100) / 100;
            });

            // Ajustar el último servicio para que la suma sea exactamente sumaObjetivo
            // Esto evita que el error de redondeo acumulado supere 0.01€
            const sumaRedondeada = serviciosSeleccionados.reduce((sum, s) => sum + s.precio, 0);
            const diff = Math.round((sumaObjetivo - sumaRedondeada) * 100) / 100;
            if (Math.abs(diff) > 0 && serviciosSeleccionados.length > 0) {
              const ultimo = serviciosSeleccionados[serviciosSeleccionados.length - 1];
              ultimo.precio = Math.round((ultimo.precio + diff) * 100) / 100;
            }

            // Actualizar servicios_data con precios ajustados
            const inputServicios = document.getElementById('servicios_data');
            if (inputServicios) {
              inputServicios.value = JSON.stringify(serviciosSeleccionados);
            }

            // Actualizar coste para que coincida con los precios ya ajustados
            const nuevoCoste = serviciosSeleccionados.reduce((sum, s) => sum + (parseFloat(s.precio) || 0), 0);
            document.getElementById('coste').value = nuevoCoste.toFixed(2);

            // Resetear campos de descuento de servicios ya que el descuento
            // se ha aplicado directamente a los precios individuales
            document.getElementById('descuento_servicios_porcentaje').value = '0';
            document.getElementById('descuento_servicios_euro').value = '0';

            // Actualizar descuentos generales (ocultos) para que solo reflejen productos
            const descProdPorRestante = parseFloat(document.getElementById('descuento_productos_porcentaje')?.value || 0);
            const descProdEurRestante = parseFloat(document.getElementById('descuento_productos_euro')?.value || 0);
            const hiddenPor = document.getElementById('descuento_porcentaje');
            const hiddenEur = document.getElementById('descuento_euro');
            if (hiddenPor) hiddenPor.value = descProdPorRestante.toFixed(2);
            if (hiddenEur) hiddenEur.value = descProdEurRestante.toFixed(2);
          }
        }

        // --- Serializar productos como JSON ---
        const productosData = [];
        selectedTbody.querySelectorAll('tr').forEach(row => {
          const id = row.querySelector('input[name$="[id]"]')?.value;
          const cantidad = parseInt(row.querySelector('.sel-qty')?.value) || 0;
          const precio = parseFloat(row.querySelector('.sel-price')?.value) || 0;
          const empleadoId = row.querySelector('.sel-empleado')?.value || null;
          if (id && cantidad > 0) {
            productosData.push({ id: parseInt(id), cantidad, precio, empleado_id: empleadoId ? parseInt(empleadoId) : null });
          }
          // Eliminar los inputs products[] para evitar duplicación con productos_data
          row.querySelectorAll('input[name^="products["]').forEach(inp => inp.remove());
          row.querySelectorAll('select[name^="products["]').forEach(sel => sel.name = '');
        });
        const hiddenProductos = document.getElementById('productos_data');
        if (hiddenProductos) {
          hiddenProductos.value = JSON.stringify(productosData);
        }
      });
    }
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
