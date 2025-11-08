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
        ev.dataTransfer.setData("citaId", ev.target.dataset.citaId);
        ev.target.classList.add('dragging');
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
    window.crearCitaRapida = function(empleadoId, fechaHora) {
        const celda = event.target;
        if (celda.classList.contains('no-disponible')) {
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
});
