// JavaScript para la vista de clientes

// Variables globales
let currentSort = 'asc';

// Función de confirmación de eliminación
function confirmarEliminacion(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este cliente?')) {
        document.getElementById('delete-form-' + id).submit();
    }
}

// Función para ordenar clientes
function sortClients(direction) {
    currentSort = direction;
    
    const sortAscBtn = document.getElementById('sortAscBtn');
    const sortDescBtn = document.getElementById('sortDescBtn');
    const tableBody = document.getElementById('clientsTableBody');
    const clientRows = document.querySelectorAll('.cliente-row');
    
    // Actualizar botones activos
    if (direction === 'asc') {
        sortAscBtn.classList.add('active');
        sortDescBtn.classList.remove('active');
    } else {
        sortDescBtn.classList.add('active');
        sortAscBtn.classList.remove('active');
    }
    
    // Obtener todas las filas y convertir a array
    const rows = Array.from(clientRows);
    
    // Ordenar por apellidos + nombre
    rows.sort((a, b) => {
        const fullnameA = a.dataset.fullname;
        const fullnameB = b.dataset.fullname;
        
        if (direction === 'asc') {
            return fullnameA.localeCompare(fullnameB, 'es');
        } else {
            return fullnameB.localeCompare(fullnameA, 'es');
        }
    });
    
    // Reordenar en el DOM
    rows.forEach(row => tableBody.appendChild(row));
    
    // Actualizar información
    const searchInput = document.getElementById('searchInput');
    const searchTerm = searchInput.value.toLowerCase().trim();
    const visibleCount = rows.filter(row => !row.classList.contains('hidden')).length;
    updateResultsInfo(visibleCount, searchTerm);
}

// Función para limpiar búsqueda
function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    const clientRows = document.querySelectorAll('.cliente-row');
    
    searchInput.value = '';
    
    clientRows.forEach(row => {
        row.classList.remove('hidden');
    });
    
    updateResultsInfo(clientRows.length, '');
}

// Actualizar información de resultados
function updateResultsInfo(count, searchTerm) {
    const resultsInfo = document.getElementById('resultsInfo');
    const clientRows = document.querySelectorAll('.cliente-row');
    const total = clientRows.length;
    
    if (searchTerm) {
        resultsInfo.textContent = `Mostrando ${count} de ${total} clientes`;
    } else {
        resultsInfo.textContent = `Total: ${total} clientes`;
    }
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const clientRows = document.querySelectorAll('.cliente-row');
    const resultsInfo = document.getElementById('resultsInfo');
    
    // Búsqueda en tiempo real
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        let visibleCount = 0;
        
        clientRows.forEach(row => {
            const nombre = row.dataset.nombre;
            const apellidos = row.dataset.apellidos;
            const email = row.dataset.email;
            const telefono = row.dataset.telefono;
            const fullname = row.dataset.fullname;
            
            // Buscar en nombre, apellidos, email y teléfono
            const matches = 
                nombre.includes(searchTerm) ||
                apellidos.includes(searchTerm) ||
                email.includes(searchTerm) ||
                telefono.includes(searchTerm) ||
                fullname.includes(searchTerm);
            
            if (matches) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });
        
        // Actualizar información de resultados
        updateResultsInfo(visibleCount, searchTerm);
    });
    
    // Inicializar contador
    updateResultsInfo(clientRows.length, '');
    
    // Focus en el input al cargar la página
    searchInput.focus();
});
