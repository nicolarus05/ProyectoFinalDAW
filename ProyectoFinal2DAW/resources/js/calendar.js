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

    // Cerrar modal con tecla ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
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
