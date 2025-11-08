document.addEventListener('DOMContentLoaded', function() {
    const appElement = document.getElementById('deudas-app');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    
    window.filtrarClientes = function() {
        const busqueda = document.getElementById('buscar').value.toLowerCase();
        const filas = document.querySelectorAll('.fila-cliente');
        let visibles = 0;

        filas.forEach(fila => {
            const nombre = fila.dataset.nombre;
            const telefono = fila.dataset.telefono;
            const email = fila.dataset.email;

            if (nombre.includes(busqueda) || telefono.includes(busqueda) || email.includes(busqueda)) {
                fila.style.display = '';
                visibles++;
            } else {
                fila.style.display = 'none';
            }
        });

        document.getElementById('sin-coincidencias').classList.toggle('hidden', visibles > 0);
    };

    window.ordenarClientes = function() {
        const orden = document.getElementById('ordenar').value;
        const tbody = document.querySelector('#tabla-deudas tbody');
        const filas = Array.from(document.querySelectorAll('.fila-cliente'));

        filas.sort((a, b) => {
            switch(orden) {
                case 'deuda-desc':
                    return parseFloat(b.dataset.deuda) - parseFloat(a.dataset.deuda);
                case 'deuda-asc':
                    return parseFloat(a.dataset.deuda) - parseFloat(b.dataset.deuda);
                case 'nombre-asc':
                    return a.dataset.nombre.localeCompare(b.dataset.nombre);
                case 'nombre-desc':
                    return b.dataset.nombre.localeCompare(a.dataset.nombre);
            }
        });

        filas.forEach(fila => tbody.appendChild(fila));
    };

    window.abrirModalPago = function() {
        document.getElementById('modal-pago-rapido').classList.remove('hidden');
    };

    window.cerrarModalPago = function() {
        document.getElementById('modal-pago-rapido').classList.add('hidden');
        document.getElementById('form-pago-rapido').reset();
    };

    window.registrarPagoRapido = async function(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const idCliente = formData.get('id_cliente');
        
        if (!idCliente) {
            alert('Por favor selecciona un cliente');
            return;
        }

        try {
            const response = await fetch(`/deudas/cliente/${idCliente}/pago`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    monto: formData.get('monto'),
                    metodo_pago: formData.get('metodo_pago'),
                    nota: formData.get('nota')
                })
            });

            const data = await response.json();

            if (data.success) {
                alert('Pago registrado exitosamente');
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'No se pudo registrar el pago'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al procesar el pago');
        }
    };
});
