// JavaScript para la funcionalidad de la rifa
document.addEventListener('DOMContentLoaded', function() {
    console.log('Documento cargado - Sistema de Rifas');
    
    // Scroll suave al hacer clic en PARTICIPAR
    const participarBtn = document.querySelector('.rifa-button');
    if (participarBtn) {
        participarBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const numerosSection = document.querySelector('#comprar-numeros');
            if (numerosSection) {
                numerosSection.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
    
    // Detectar si estamos en la página de rifa o en la página de checkout
    const isCheckoutPage = document.querySelector('.checkout-container') !== null;
    const isRifaPage = document.querySelector('.rifa-container') !== null;
    
    if (isRifaPage) {
        // Obtener el ID de la rifa directamente del elemento oculto
        const rifaIdElement = document.getElementById('rifa_id');
        let rifaId = '';
        
        if (rifaIdElement) {
            rifaId = rifaIdElement.value;
            console.log('ID de la rifa obtenido del campo oculto:', rifaId);
        } else {
            // Intentar obtener el ID de la rifa del contenedor
            const rifaContainer = document.querySelector('.rifa-container');
            if (rifaContainer) {
                rifaId = rifaContainer.getAttribute('data-rifa-id');
                console.log('ID de la rifa obtenido del contenedor:', rifaId);
            }
        }
        
        if (!rifaId) {
            console.error('No se pudo encontrar el ID de la rifa');
            return;
        }
        
        // Código para la página de rifa
        // Manejar la selección de opciones de compra
        const btnsComprar = document.querySelectorAll('.btn-comprar');
        btnsComprar.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const cantidad = this.getAttribute('data-cantidad');
                
                console.log('Datos para redirección:', {
                    rifaId: rifaId,
                    cantidad: cantidad
                });
                
                // Usar la misma URL que funcionó con el botón de prueba
                const url = 'http://alimento-de-esperanza.local/checkout/?rifa_id=' + rifaId + '&cantidad=' + cantidad;
                
                console.log('Redirigiendo a:', url);
                
                // Redirigir a la página de checkout
                window.location.href = url;
            });
        });
        
        // Manejar el botón de pagar
        const btnPagar = document.querySelector('.btn-pagar');
        if (btnPagar) {
            btnPagar.addEventListener('click', function(e) {
                e.preventDefault();
                
                const cantidadInput = document.getElementById('cantidad-numero');
                if (!cantidadInput) {
                    console.error('No se encontró el input de cantidad');
                    return;
                }
                
                const cantidad = cantidadInput.value;
                
                console.log('Datos para redirección personalizada:', {
                    rifaId: rifaId,
                    cantidad: cantidad
                });
                
                // Usar la misma URL que funcionó con el botón de prueba
                const url = 'http://alimento-de-esperanza.local/checkout/?rifa_id=' + rifaId + '&cantidad=' + cantidad;
                
                console.log('Redirigiendo a:', url);
                
                // Redirigir a la página de checkout
                window.location.href = url;
            });
        }
    } else if (isCheckoutPage) {
        // El resto del código para la página de checkout permanece igual
        const btnSiguiente = document.getElementById('btn-siguiente');
        if (btnSiguiente) {
            btnSiguiente.addEventListener('click', function() {
                const form = document.getElementById('checkout-form');
                
                // Validar formulario
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                
                // Obtener datos del formulario
                const nombre = document.getElementById('nombre').value;
                const apellidos = document.getElementById('apellidos').value;
                const telefono = document.getElementById('telefono').value;
                const email = document.getElementById('email').value;
                const rifaId = document.getElementById('rifa_id').value;
                const cantidad = document.getElementById('cantidad').value;
                const precioUnitario = document.getElementById('precio_unitario').value;
                const precioTotal = precioUnitario * cantidad;
                
                // Iniciar pago con ePayco
                iniciarPagoEpayco(rifaId, cantidad, precioTotal, nombre, apellidos, email, telefono);
            });
        }
    }
});

// La función iniciarPagoEpayco permanece igual
function iniciarPagoEpayco(rifaId, cantidadNumeros, precioTotal, nombre = '', apellidos = '', email = '', telefono = '') {
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