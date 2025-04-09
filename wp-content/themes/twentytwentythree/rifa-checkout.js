/**
 * Código específico para la página de checkout
 */
const RifaCheckout = {
    // Elementos del DOM
    elements: {
        container: null,
        form: null,
        btnSiguiente: null
    },
    
    // Inicialización
    init: function() {
        console.log('RifaCheckout inicializado');
        
        // Obtener elementos del DOM
        this.elements.container = document.querySelector('.checkout-container');
        this.elements.form = document.getElementById('checkout-form');
        this.elements.btnSiguiente = document.getElementById('btn-siguiente');
        
        // Inicializar eventos
        this.initEvents();
    },
    
    // Inicializar eventos
    initEvents: function() {
        // Manejar clic en botón SIGUIENTE
        if (this.elements.btnSiguiente) {
            this.elements.btnSiguiente.addEventListener('click', () => {
                this.handleSiguiente();
            });
        }
    },
    
    // Manejar clic en botón SIGUIENTE
    handleSiguiente: function() {
        if (!this.elements.form) {
            console.error('No se encontró el formulario de checkout');
            return;
        }
        
        // Validar formulario
        if (!RifaCore.utils.validateForm(this.elements.form)) {
            this.elements.form.reportValidity();
            return;
        }
        
        // Obtener datos del formulario
        const formData = RifaCore.utils.getFormData(this.elements.form);
        console.log('Datos del formulario:', formData);
        
        // Iniciar pago con ePayco
        if (typeof RifaPayment !== 'undefined') {
            RifaPayment.iniciarPago(
                formData.rifa_id,
                formData.cantidad,
                formData.precio_unitario * formData.cantidad,
                formData.nombre,
                formData.apellidos,
                formData.email,
                formData.telefono
            );
        } else {
            console.error('El módulo de pago no está disponible');
        }
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    RifaCheckout.init();
});