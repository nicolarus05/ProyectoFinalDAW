/**
 * Sistema de alertas para bonos por agotar o expirar
 * Muestra modales informativos durante el proceso de cobro
 */

class BonoAlertaManager {
    constructor() {
        this.modalHTML = `
            <div id="bono-alerta-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 animate-fade-in">
                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <span id="alerta-icono" class="text-3xl mr-3">⚠️</span>
                                <h3 id="alerta-titulo" class="text-xl font-bold text-gray-800">
                                    Alerta de Bono
                                </h3>
                            </div>
                            <button type="button" id="close-alerta-modal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Contenido -->
                        <div id="alerta-contenido" class="mb-6">
                            <!-- Se llenará dinámicamente -->
                        </div>

                        <!-- Botones -->
                        <div class="flex gap-3">
                            <button type="button" id="btn-entendido" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 font-semibold">
                                Entendido
                            </button>
                            <button type="button" id="btn-ofrecer-renovacion" class="flex-1 bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 font-semibold">
                                Ofrecer Renovación
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.init();
    }

    init() {
        // Insertar el modal en el DOM si no existe
        if (!document.getElementById('bono-alerta-modal')) {
            document.body.insertAdjacentHTML('beforeend', this.modalHTML);
        }

        // Event listeners
        document.getElementById('close-alerta-modal')?.addEventListener('click', () => this.cerrarModal());
        document.getElementById('btn-entendido')?.addEventListener('click', () => this.cerrarModal());
        document.getElementById('btn-ofrecer-renovacion')?.addEventListener('click', () => this.redirigirARenovacion());
    }

    /**
     * Mostrar alerta de bono
     * @param {Object} datos - Información del bono y alertas
     */
    mostrarAlerta(datos) {
        const modal = document.getElementById('bono-alerta-modal');
        const icono = document.getElementById('alerta-icono');
        const titulo = document.getElementById('alerta-titulo');
        const contenido = document.getElementById('alerta-contenido');

        if (!modal || !datos.alertas || datos.alertas.length === 0) {
            return;
        }

        // Determinar el nivel de alerta más crítico
        const tieneCritico = datos.alertas.some(a => a.tipo === 'critico');
        
        // Configurar icono y título según criticidad
        if (tieneCritico) {
            icono.textContent = '🔴';
            titulo.textContent = '¡Atención! Bono Crítico';
            titulo.classList.add('text-red-600');
        } else {
            icono.textContent = '🟡';
            titulo.textContent = 'Advertencia de Bono';
            titulo.classList.add('text-yellow-600');
        }

        // Construir contenido
        let html = `
            <div class="bg-gray-50 rounded p-4 mb-4">
                <p class="font-semibold text-gray-800 mb-2">Bono: ${datos.nombreBono}</p>
                <p class="text-sm text-gray-600">Cliente: ${datos.nombreCliente}</p>
            </div>
        `;

        // Listar alertas
        html += '<div class="space-y-2">';
        datos.alertas.forEach(alerta => {
            const bgColor = alerta.tipo === 'critico' ? 'bg-red-50 border-red-200' : 'bg-yellow-50 border-yellow-200';
            const textColor = alerta.tipo === 'critico' ? 'text-red-800' : 'text-yellow-800';
            
            html += `
                <div class="border ${bgColor} ${textColor} rounded p-3 flex items-center">
                    <span class="text-2xl mr-3">${alerta.icono}</span>
                    <span>${alerta.mensaje}</span>
                </div>
            `;
        });
        html += '</div>';

        // Mensaje de sugerencia
        if (tieneCritico) {
            html += `
                <div class="mt-4 p-3 bg-purple-50 border border-purple-200 rounded">
                    <p class="text-sm text-purple-800">
                        💡 <strong>Sugerencia:</strong> Considera ofrecer la renovación del bono a la clienta.
                    </p>
                </div>
            `;
        }

        contenido.innerHTML = html;
        
        // Mostrar modal con animación
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.querySelector('.animate-fade-in').classList.add('scale-100');
        }, 10);
    }

    /**
     * Cerrar modal
     */
    cerrarModal() {
        const modal = document.getElementById('bono-alerta-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    /**
     * Redirigir a la página de bonos para renovación
     */
    redirigirARenovacion() {
        this.cerrarModal();
        // Aquí puedes agregar la lógica para redirigir o mostrar el catálogo de bonos
        window.location.href = '/bonos';
    }

    /**
     * Verificar y mostrar alerta si hay servicios del bono en la cita
     * @param {Array} serviciosEnCita - IDs de servicios en la cita actual
     * @param {Object} bonoData - Datos del bono del cliente
     */
    verificarYMostrarAlerta(serviciosEnCita, bonoData) {
        // Esta función se llamará cuando se detecte que se va a usar un bono
        if (bonoData && bonoData.alertas && bonoData.alertas.length > 0) {
            this.mostrarAlerta(bonoData);
        }
    }
}

// Estilos adicionales para animación
const styles = `
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    .animate-fade-in {
        animation: fadeIn 0.2s ease-out;
    }
`;

// Insertar estilos
if (!document.getElementById('bono-alerta-styles')) {
    const styleSheet = document.createElement('style');
    styleSheet.id = 'bono-alerta-styles';
    styleSheet.textContent = styles;
    document.head.appendChild(styleSheet);
}

// Exportar para uso global
window.BonoAlertaManager = BonoAlertaManager;

// Auto-inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.bonoAlertaManager = new BonoAlertaManager();
    });
} else {
    window.bonoAlertaManager = new BonoAlertaManager();
}
