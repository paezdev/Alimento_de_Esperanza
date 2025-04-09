/**
 * Código para la integración con ePayco
 */
const RifaPayment = {
    // Inicialización
    init: function() {
        console.log('RifaPayment inicializado');
        
        // Verificar si ePayco está disponible
        if (typeof ePayco === 'undefined') {
            console.error('ePayco no está disponible');
            return;
        }
    },
    
    // Iniciar pago con ePayco
    iniciarPago: function(rifaId, cantidadNumeros, precioTotal, nombre = '', apellidos = '', email = '', telefono = '') {
        console.log('Iniciando pago con ePayco');
        
        // Verificar si ePayco está disponible
        if (typeof ePayco === 'undefined') {
            console.error('ePayco no está disponible');
            alert('El sistema de pagos no está disponible en este momento. Por favor, inténtalo más tarde.');
            return;
        }
        
        // Configuración de ePayco
        var handler = ePayco.checkout.configure({
            key: 'tu_llave_publica', // Reemplazar con tu llave pública real
            test: true // Cambiar a false en producción
        });
        
        // Datos para el checkout
        var data = {
            name: 'Compra de números para rifa #' + rifaId,
            description: 'Compra de ' + cantidadNumeros + ' número(s) para la rifa',
            currency: 'cop',
            amount: precioTotal,
            tax_base: '0',
            tax: '0',
            country: 'co',
            lang: 'es',
            external: false,
            
            // Información del cliente
            name_billing: nombre + ' ' + apellidos,
            email_billing: email,
            phone_billing: telefono,
            
            // Información adicional para el callback
            extra1: rifaId,
            extra2: cantidadNumeros,
            
            // URLs de respuesta
            response: window.location.origin + '/respuesta-pago/',
            confirmation: window.location.origin + '/wp-json/sistema-rifas/v1/epayco-callback'
        };
        
        console.log('Datos para ePayco:', data);
        handler.open(data);
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    RifaPayment.init();
});

// Exponer RifaPayment globalmente
window.RifaPayment = RifaPayment;