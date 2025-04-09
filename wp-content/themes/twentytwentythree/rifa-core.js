/**
 * Funcionalidad principal y utilidades para el sistema de rifas
 */
const RifaCore = {
    // Inicialización
    init: function() {
        console.log('RifaCore inicializado');
    },
    
    // Utilidades
    utils: {
        // Scroll suave a un elemento
        scrollTo: function(element, options = {}) {
            if (element) {
                element.scrollIntoView({ 
                    behavior: options.behavior || 'smooth',
                    block: options.block || 'start'
                });
            }
        },
        
        // Redireccionar a checkout
        redirectToCheckout: function(rifaId, cantidad) {
            if (!rifaId) {
                console.error('No se proporcionó un ID de rifa válido');
                return;
            }
            
            // Obtener la URL del checkout
            let checkoutUrl = '';
            if (typeof sistema_rifas_vars !== 'undefined' && sistema_rifas_vars.checkout_url) {
                checkoutUrl = sistema_rifas_vars.checkout_url;
            } else {
                checkoutUrl = '/checkout/';
            }
            
            // Asegurarse de que la URL termina con /
            if (!checkoutUrl.endsWith('/')) {
                checkoutUrl += '/';
            }
            
            // Construir la URL completa
            const url = checkoutUrl + '?rifa_id=' + rifaId + '&cantidad=' + cantidad;
            console.log('Redirigiendo a:', url);
            
            // Redirigir a la página de checkout
            window.location.href = url;
        },
        
        // Validar un formulario
        validateForm: function(form) {
            if (!form) return false;
            return form.checkValidity();
        },
        
        // Obtener datos de un formulario como objeto
        getFormData: function(form) {
            if (!form) return {};
            
            const formData = new FormData(form);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            return data;
        }
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    RifaCore.init();
});

// Exponer RifaCore globalmente
window.RifaCore = RifaCore;