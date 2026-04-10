// Calendario de Citas - JavaScript

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Configuración CSRF
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    
    // Obtener URLs desde data attributes
    const appElement = document.getElementById('calendar-app');
    const moverUrl = appElement?.dataset.moverUrl || '';
    const completarUrl = appElement?.dataset.completarUrl || '';
    const createUrl = appElement?.dataset.createUrl || '';

    // Drag and Drop
    window.allowDrop = function(ev) {
        ev.preventDefault();
    };

    window.drag = function(ev) {
        // Obtener el ID de la cita desde el elemento padre .cita-card
        const citaCard = ev.target.closest('.cita-card');
        if (citaCard) {
            const citaId = citaCard.dataset.citaId;
            ev.dataTransfer.setData("citaId", citaId);
            citaCard.classList.add('dragging');
        }
    };

    window.drop = function(ev) {
        ev.preventDefault();
        
        // Remover clase dragging de todas las citas
        document.querySelectorAll('.cita-card').forEach(card => {
            card.classList.remove('dragging');
        });

        const citaId = ev.dataTransfer.getData("citaId");
        const celda = ev.target.classList.contains('celda-horario') 
            ? ev.target 
            : ev.target.closest('.celda-horario');
        
        if (!celda || celda.classList.contains('no-disponible')) {
            alert('No se puede mover la cita a este horario (empleado no disponible)');
            return;
        }

        const empleadoId = celda.dataset.empleadoId;
        const fechaHora = celda.dataset.fechaHora;

        moverCita(citaId, empleadoId, fechaHora);
    };

    // Mover Cita via AJAX
    window.moverCita = function(citaId, empleadoId, fechaHora) {
        if (!confirm('¿Deseas mover esta cita?')) {
            return;
        }

        fetch(moverUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                cita_id: citaId,
                nuevo_empleado_id: empleadoId,
                nueva_fecha_hora: fechaHora
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al mover la cita');
        });
    };

    // Marcar como Completada
    window.marcarCompletada = function(citaId) {
        if (!confirm('¿Marcar esta cita como completada?')) {
            return;
        }

        fetch(completarUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                cita_id: citaId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al marcar la cita como completada');
        });
    };

    // Crear Cita Rápida
    window.crearCitaRapida = function(empleadoId, fechaHora, event) {
        // Si no se pasa el evento como parámetro, usar el global window.event
        const e = event || window.event;
        const celda = e ? e.target : null;
        
        // Verificar que no sea una celda no disponible o deshabilitada
        if (celda && (celda.classList.contains('no-disponible') || celda.classList.contains('hora-deshabilitada'))) {
            return;
        }
        
        // Redirigir a crear cita con parámetros prellenados
        window.location.href = `${createUrl}?empleado_id=${empleadoId}&fecha_hora=${encodeURIComponent(fechaHora)}`;
    };

    // Popover para cambiar fecha y hora de cita
    let _popoverState = { citaId: null, empleadoId: null };

    window.abrirPopoverCita = function(btn) {
        const popover = document.getElementById('popover-cita');
        _popoverState.citaId     = btn.dataset.citaId;
        _popoverState.empleadoId = btn.dataset.empleadoId;

        document.getElementById('popover-cliente-nombre').textContent = btn.dataset.clienteNombre;
        document.getElementById('popover-fecha').value = btn.dataset.fecha;
        document.getElementById('popover-hora').value  = btn.dataset.hora;

        // Posicionar relativo al botón
        const rect      = btn.getBoundingClientRect();
        const popoverW  = 280;
        let left = rect.left;
        let top  = rect.bottom + 6;

        if (left + popoverW > window.innerWidth - 10) left = window.innerWidth - popoverW - 10;
        if (top  + 230 > window.innerHeight)          top  = rect.top - 230 - 6;

        popover.style.left    = left + 'px';
        popover.style.top     = top  + 'px';
        popover.style.display = 'block';
    };

    window.cerrarPopoverCita = function() {
        const popover = document.getElementById('popover-cita');
        if (popover) popover.style.display = 'none';
        _popoverState.citaId     = null;
        _popoverState.empleadoId = null;
    };

    window.confirmarMoverCita = function() {
        const fecha = document.getElementById('popover-fecha').value;
        const hora  = document.getElementById('popover-hora').value;

        if (!fecha || !hora) {
            alert('Selecciona la fecha y la hora');
            return;
        }

        const horaHHMM        = hora.substring(0, 5);
        const nuevaFechaHora  = fecha + 'T' + horaHHMM + ':00';
        console.log('[popover] fecha:', fecha, 'hora raw:', hora, 'enviando:', nuevaFechaHora);
        const clienteNombre   = document.getElementById('popover-cliente-nombre').textContent;
        const citaId          = _popoverState.citaId;
        const empleadoId      = _popoverState.empleadoId;

        const fechaObj        = new Date(fecha + 'T00:00:00');
        const fechaFormateada = fechaObj.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

        if (!confirm(`¿Mover cita de ${clienteNombre} al ${fechaFormateada} a las ${hora}?`)) return;

        window.cerrarPopoverCita();

        fetch(moverUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                cita_id:           citaId,
                nuevo_empleado_id: empleadoId,
                nueva_fecha_hora:  nuevaFechaHora
            })
        })
        .then(response => {
            if (!response.ok && response.status !== 400 && response.status !== 422) {
                return response.text().then(text => { throw new Error('HTTP ' + response.status + ': ' + text.substring(0, 200)); });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.href = window.location.pathname + '?fecha=' + fecha;
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error al mover cita:', error);
            alert('Error al mover la cita: ' + error.message);
        });
    };

    // Cerrar popover al hacer click fuera
    document.addEventListener('click', function(e) {
        const popover = document.getElementById('popover-cita');
        if (popover && popover.style.display !== 'none' && !popover.contains(e.target)) {
            window.cerrarPopoverCita();
        }
    });

    // Modal
    window.abrirModal = function(citaId) {
        const modal = document.getElementById('modalCita');
        modal.classList.add('active');
        
        // Aquí podrías cargar detalles adicionales via AJAX si es necesario
    };

    window.cerrarModal = function(event) {
        if (!event || event.target.id === 'modalCita' || !event) {
            const modal = document.getElementById('modalCita');
            modal.classList.remove('active');
        }
    };

    // Cerrar modal y popover con tecla ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            window.cerrarPopoverCita();
            window.cerrarModal();
        }
    });

    // Prevenir comportamiento por defecto del drag
    document.addEventListener('dragend', function(e) {
        document.querySelectorAll('.cita-card').forEach(card => {
            card.classList.remove('dragging');
        });
    });

    // Ajustar duración de cita
    window.ajustarDuracion = function(citaId, cambioMinutos) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        // Obtener la cita del DOM
        const citaCard = document.querySelector(`[data-cita-id="${citaId}"]`);
        if (!citaCard) {
            alert('No se pudo encontrar la cita');
            return;
        }

        // Obtener la duración actual desde el atributo data
        const duracionActual = parseInt(citaCard.dataset.duracionActual);
        const nuevaDuracion = duracionActual + cambioMinutos;

        // Validar duración mínima
        if (nuevaDuracion < 15) {
            alert('La duración mínima de una cita es 15 minutos');
            return;
        }

        // Validar duración máxima
        if (nuevaDuracion > 480) {
            alert('La duración máxima de una cita es 8 horas (480 minutos)');
            return;
        }

        // Enviar solicitud AJAX
        fetch('/citas/actualizar-duracion', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                cita_id: citaId,
                duracion_minutos: nuevaDuracion
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar el atributo data
                citaCard.dataset.duracionActual = data.nueva_duracion;
                
                // Actualizar visualmente la altura de la cita
                const nuevosBloques = Math.max(1, Math.ceil(data.nueva_duracion / 15));
                const nuevaAltura = (nuevosBloques * 30) * 0.92;
                citaCard.style.height = nuevaAltura + 'px';
                
                // Actualizar el texto de duración en el DOM
                const duracionValor = citaCard.querySelector('.duracion-valor');
                if (duracionValor) {
                    duracionValor.textContent = data.nueva_duracion;
                }
                
                // Actualizar las clases de tamaño según la nueva duración
                citaCard.classList.remove('cita-corta', 'cita-mediana', 'cita-larga');
                if (data.nueva_duracion < 30) {
                    citaCard.classList.add('cita-corta');
                } else if (data.nueva_duracion >= 30 && data.nueva_duracion < 60) {
                    citaCard.classList.add('cita-mediana');
                } else {
                    citaCard.classList.add('cita-larga');
                }
                
                // Mostrar mensaje de éxito
                mostrarMensaje('Duración actualizada correctamente', 'success');
                
                // Recargar el calendario después de 500ms para actualizar la disponibilidad de bloques
                // Esto permite que el usuario vea el cambio visual primero, luego se actualiza la disponibilidad
                setTimeout(() => {
                    // Obtener la fecha actual de la URL o del calendario
                    const urlParams = new URLSearchParams(window.location.search);
                    const fechaActual = urlParams.get('fecha') || new Date().toISOString().split('T')[0];
                    
                    // Recargar la página manteniendo la misma fecha
                    window.location.href = window.location.pathname + '?fecha=' + fechaActual;
                }, 500);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar la duración de la cita');
        });
    };

    // Función auxiliar para mostrar mensajes
    function mostrarMensaje(texto, tipo = 'info') {
        // Crear elemento de mensaje
        const mensaje = document.createElement('div');
        mensaje.className = `mensaje-flotante mensaje-${tipo}`;
        mensaje.textContent = texto;
        mensaje.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${tipo === 'success' ? '#4CAF50' : '#2196F3'};
            color: white;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(mensaje);
        
        // Eliminar después de 3 segundos
        setTimeout(() => {
            mensaje.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => mensaje.remove(), 300);
        }, 3000);
    }
});
