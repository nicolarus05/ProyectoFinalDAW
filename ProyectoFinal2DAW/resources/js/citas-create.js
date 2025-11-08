// ===== Script para Crear Cita - Flujo Paso a Paso =====

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== VARIABLES GLOBALES =====
    let currentStep = 1;
    const totalSteps = 3;
    const serviciosSeleccionados = [];
    
    // Elementos del DOM
    const step1 = document.getElementById('step-1');
    const step2 = document.getElementById('step-2');
    const step3 = document.getElementById('step-3');
    
    const btnNextStep2 = document.getElementById('btn-next-step-2');
    const btnNextStep3 = document.getElementById('btn-next-step-3');
    const btnBackStep1 = document.getElementById('btn-back-step-1');
    const btnBackStep2 = document.getElementById('btn-back-step-2');
    const btnSubmit = document.getElementById('btn-submit');
    
    const searchInput = document.getElementById('search-servicios');
    const filterCategoria = document.getElementById('filter-categoria');
    const clearFilters = document.getElementById('clear-filters');
    const serviciosGrid = document.getElementById('servicios-grid');
    const servicesCount = document.getElementById('services-count');
    const selectedCount = document.getElementById('selected-count');
    const selectedContainer = document.getElementById('selected-services-container');
    const selectedServicesList = document.getElementById('selected-services-list');
    const totalTiempo = document.getElementById('total-tiempo');
    const totalPrecio = document.getElementById('total-precio');
    
    // ===== PASO 1: SELECCIÓN DE SERVICIOS =====
    
    // Búsqueda y filtrado de servicios
    searchInput.addEventListener('input', filterServicios);
    filterCategoria.addEventListener('change', filterServicios);
    clearFilters.addEventListener('click', function() {
        searchInput.value = '';
        filterCategoria.value = '';
        filterServicios();
    });
    
    function filterServicios() {
        const searchTerm = searchInput.value.toLowerCase();
        const categoria = filterCategoria.value;
        const cards = serviciosGrid.querySelectorAll('.servicio-card');
        let visibleCount = 0;
        
        cards.forEach(card => {
            const nombre = card.dataset.nombre;
            const cardCategoria = card.dataset.categoria;
            
            const matchesSearch = nombre.includes(searchTerm);
            const matchesCategoria = !categoria || cardCategoria === categoria;
            
            if (matchesSearch && matchesCategoria) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        servicesCount.textContent = visibleCount;
    }
    
    // Selección de servicios
    serviciosGrid.addEventListener('click', function(e) {
        const card = e.target.closest('.servicio-card');
        if (!card) return;
        
        const servicioId = card.dataset.id;
        const servicioNombre = card.querySelector('h3').textContent.trim();
        const servicioPrecio = parseFloat(card.dataset.precio);
        const servicioTiempo = parseInt(card.dataset.tiempo);
        
        const index = serviciosSeleccionados.findIndex(s => s.id === servicioId);
        
        if (index > -1) {
            // Deseleccionar
            serviciosSeleccionados.splice(index, 1);
            card.classList.remove('selected');
        } else {
            // Seleccionar
            serviciosSeleccionados.push({
                id: servicioId,
                nombre: servicioNombre,
                precio: servicioPrecio,
                tiempo: servicioTiempo
            });
            card.classList.add('selected');
        }
        
        updateSelectedServices();
    });
    
    function updateSelectedServices() {
        if (serviciosSeleccionados.length === 0) {
            selectedContainer.classList.add('hidden');
            selectedCount.textContent = '';
            btnNextStep2.disabled = true;
            return;
        }
        
        // Mostrar contenedor
        selectedContainer.classList.remove('hidden');
        selectedCount.textContent = `(${serviciosSeleccionados.length} seleccionado${serviciosSeleccionados.length > 1 ? 's' : ''})`;
        btnNextStep2.disabled = false;
        
        // Actualizar lista
        selectedServicesList.innerHTML = '';
        let totalT = 0;
        let totalP = 0;
        
        serviciosSeleccionados.forEach(servicio => {
            totalT += servicio.tiempo;
            totalP += servicio.precio;
            
            const div = document.createElement('div');
            div.className = 'flex justify-between items-center p-3 bg-white rounded border';
            div.innerHTML = `
                <span class="font-semibold">${servicio.nombre}</span>
                <div class="text-sm text-gray-600">
                    <span>⏱️ ${servicio.tiempo} min</span>
                    <span class="ml-3">💰 ${servicio.precio.toFixed(2)} €</span>
                </div>
            `;
            selectedServicesList.appendChild(div);
        });
        
        totalTiempo.textContent = `${totalT} min`;
        totalPrecio.textContent = `${totalP.toFixed(2)} €`;
    }
    
    // ===== NAVEGACIÓN ENTRE PASOS =====
    
    btnNextStep2.addEventListener('click', function() {
        if (serviciosSeleccionados.length > 0) {
            goToStep(2);
        }
    });
    
    btnBackStep1.addEventListener('click', function() {
        goToStep(1);
    });
    
    btnNextStep3.addEventListener('click', function() {
        if (validateStep2()) {
            goToStep(3);
            updateConfirmationSummary();
        }
    });
    
    btnBackStep2.addEventListener('click', function() {
        goToStep(2);
    });
    
    function goToStep(step) {
        // Ocultar todos los pasos
        document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.step-indicator').forEach(el => {
            el.classList.remove('active');
            el.classList.remove('completed');
        });
        document.querySelectorAll('[id^="step-"][id$="-label"]').forEach(el => {
            el.classList.remove('font-semibold', 'text-blue-600');
        });
        
        // Actualizar progreso
        for (let i = 1; i < step; i++) {
            document.getElementById(`step-${i}-indicator`).classList.add('completed');
            document.getElementById(`progress-${i}`).style.width = '100%';
        }
        
        // Mostrar paso actual
        currentStep = step;
        document.getElementById(`step-${step}`).classList.add('active');
        document.getElementById(`step-${step}-indicator`).classList.add('active');
        document.getElementById(`step-${step}-label`).classList.add('font-semibold', 'text-blue-600');
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    function validateStep2() {
        const idCliente = document.querySelector('input[name="id_cliente"]')?.value ||
                          document.querySelector('select[name="id_cliente"]')?.value;
        const idEmpleado = document.querySelector('input[name="id_empleado"]:checked')?.value;
        const fechaCita = document.getElementById('fecha_cita').value;
        const horaCita = document.getElementById('hora_cita').value;
        
        if (!idCliente) {
            alert('Por favor seleccione un cliente');
            return false;
        }
        if (!idEmpleado) {
            alert('Por favor seleccione un empleado');
            return false;
        }
        if (!fechaCita) {
            alert('Por favor seleccione una fecha');
            return false;
        }
        if (!horaCita) {
            alert('Por favor seleccione una hora');
            return false;
        }
        
        return true;
    }
    
    // ===== PASO 3: CONFIRMACIÓN =====
    
    function updateConfirmationSummary() {
        // Servicios
        const confirmServicesList = document.getElementById('confirm-services-list');
        confirmServicesList.innerHTML = '';
        let totalT = 0;
        let totalP = 0;
        
        serviciosSeleccionados.forEach(servicio => {
            totalT += servicio.tiempo;
            totalP += servicio.precio;
            
            const div = document.createElement('div');
            div.className = 'service-item';
            div.innerHTML = `
                <span class="font-semibold text-gray-900">${servicio.nombre}</span>
                <div class="text-sm text-gray-600">
                    <span>⏱️ ${servicio.tiempo} min</span>
                    <span class="ml-3">💰 ${servicio.precio.toFixed(2)} €</span>
                </div>
            `;
            confirmServicesList.appendChild(div);
        });
        
        document.getElementById('confirm-tiempo').textContent = `${totalT} min`;
        document.getElementById('confirm-precio').textContent = `${totalP.toFixed(2)} €`;
        
        // Cliente
        const clienteSelect = document.querySelector('select[name="id_cliente"]');
        const clienteHidden = document.querySelector('input[name="id_cliente"]');
        let clienteNombre = '';
        
        if (clienteSelect) {
            const selectedOption = clienteSelect.options[clienteSelect.selectedIndex];
            clienteNombre = selectedOption.text;
        } else if (clienteHidden) {
            // Para clientes autenticados, obtener del DOM
            const clienteText = document.querySelector('.bg-gray-50 p');
            if (clienteText) {
                clienteNombre = clienteText.textContent.trim();
            }
        }
        document.getElementById('confirm-cliente').textContent = clienteNombre || 'Cliente';
        
        // Empleado
        const empleadoRadio = document.querySelector('input[name="id_empleado"]:checked');
        const empleadoLabel = empleadoRadio?.closest('.empleado-option');
        const empleadoNombre = empleadoLabel?.querySelector('p.font-semibold')?.textContent.trim() || 'Empleado';
        document.getElementById('confirm-empleado').textContent = empleadoNombre;
        
        // Fecha y Hora
        const fechaCita = document.getElementById('fecha_cita').value;
        const horaCita = document.getElementById('hora_cita').value;
        
        // Formatear fecha
        const fechaObj = new Date(fechaCita + 'T00:00:00');
        const fechaFormateada = fechaObj.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        document.getElementById('confirm-fecha').textContent = fechaFormateada;
        document.getElementById('confirm-hora').textContent = horaCita;
        
        // Notas
        const notas = document.getElementById('notas_adicionales').value;
        if (notas) {
            document.getElementById('confirm-notas-container').classList.remove('hidden');
            document.getElementById('confirm-notas').textContent = notas;
        } else {
            document.getElementById('confirm-notas-container').classList.add('hidden');
        }
        
        // Combinar fecha y hora para el campo fecha_hora
        const fechaHoraCombined = `${fechaCita} ${horaCita}:00`;
        document.getElementById('fecha_hora_combined').value = fechaHoraCombined;
        
        // Crear inputs ocultos para servicios
        const hiddenServicesInputs = document.getElementById('hidden-services-inputs');
        hiddenServicesInputs.innerHTML = '';
        serviciosSeleccionados.forEach(servicio => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'servicios[]';
            input.value = servicio.id;
            hiddenServicesInputs.appendChild(input);
        });
    }
    
    // ===== INICIALIZACIÓN =====
    
    // Contar servicios iniciales
    const totalServicios = serviciosGrid.querySelectorAll('.servicio-card').length;
    servicesCount.textContent = totalServicios;
    
    console.log('✨ Flujo de creación de citas inicializado');
});
