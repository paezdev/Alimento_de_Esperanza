/**
 * Código específico para la página de detalle de la rifa
 */
const RifaSingle = {
    // Elementos del DOM
    elements: {
        container: null,
        participarBtn: null,
        btnsComprar: null,
        btnPagar: null,
        cantidadInput: null,
        numerosSection: null
    },
    
    // Inicialización
    init: function() {
        console.log('RifaSingle inicializado');
        
        // Obtener elementos del DOM
        this.elements.container = document.querySelector('.rifa-container');
        this.elements.participarBtn = document.querySelector('.rifa-button');
        this.elements.btnsComprar = document.querySelectorAll('.btn-comprar');
        this.elements.btnPagar = document.querySelector('.btn-pagar');
        this.elements.cantidadInput = document.getElementById('cantidad-numero');
        this.elements.numerosSection = document.getElementById('comprar-numeros');
        
        // Inicializar eventos
        this.initEvents();
    },
    
    // Inicializar eventos
    initEvents: function() {
        // Scroll suave al hacer clic en PARTICIPAR
        if (this.elements.participarBtn) {
            this.elements.participarBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.elements.numerosSection) {
                    RifaCore.utils.scrollTo(this.elements.numerosSection);
                }
            });
        }
        
        // Manejar la selección de opciones de compra
        if (this.elements.btnsComprar.length > 0) {
            this.elements.btnsComprar.forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleCompra(btn);
                });
            });
        }
        
        // Manejar el botón de pagar
        if (this.elements.btnPagar) {
            this.elements.btnPagar.addEventListener('click', (e) => {
                e.preventDefault();
                this.handlePago();
            });
        }
    },
    
    // Manejar clic en botón COMPRAR
    handleCompra: function(btn) {
        const cantidad = btn.getAttribute('data-cantidad');
        console.log('Cantidad seleccionada:', cantidad);
        
        if (!this.elements.container) {
            console.error('No se encontró el contenedor de la rifa');
            return;
        }
        
        const rifaId = this.elements.container.getAttribute('data-rifa-id');
        console.log('ID de la rifa:', rifaId);
        
        if (!rifaId) {
            console.error('No se encontró el ID de la rifa');
            return;
        }
        
        // Redirigir a checkout
        RifaCore.utils.redirectToCheckout(rifaId, cantidad);
    },
    
    // Manejar clic en botón PAGAR
    handlePago: function() {
        if (!this.elements.cantidadInput) {
            console.error('No se encontró el input de cantidad');
            return;
        }
        
        const cantidad = this.elements.cantidadInput.value;
        console.log('Cantidad personalizada:', cantidad);
        
        if (!this.elements.container) {
            console.error('No se encontró el contenedor de la rifa');
            return;
        }
        
        const rifaId = this.elements.container.getAttribute('data-rifa-id');
        console.log('ID de la rifa:', rifaId);
        
        if (!rifaId) {
            console.error('No se encontró el ID de la rifa');
            return;
        }
        
        // Redirigir a checkout
        RifaCore.utils.redirectToCheckout(rifaId, cantidad);
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    RifaSingle.init();
});