document.addEventListener('DOMContentLoaded', function() {
    // Obtener elementos del DOM
    const customQuantityInput = document.getElementById('cantidad-numero');
    const customQuantityButton = document.querySelector('.btn-pagar');
    const rifaContainer = document.querySelector('.rifa-container');
    
    if (!customQuantityInput || !customQuantityButton || !rifaContainer) {
        console.error('No se encontraron los elementos necesarios');
        return;
    }
    
    // Obtener datos de boletas desde los campos ocultos
    const totalBoletas = parseInt(document.getElementById('total_boletas').value) || 0;
    const boletasVendidas = parseInt(document.getElementById('boletas_vendidas').value) || 0;
    const boletasDisponibles = parseInt(document.getElementById('boletas_disponibles').value) || 0;
    const rifaId = rifaContainer.getAttribute('data-rifa-id');
    
    console.log('Boletas totales:', totalBoletas);
    console.log('Boletas vendidas:', boletasVendidas);
    console.log('Boletas disponibles:', boletasDisponibles);
    
    // Validar mientras el usuario escribe
    customQuantityInput.addEventListener('input', function() {
        const value = parseInt(this.value) || 0;
        
        if (value > boletasDisponibles) {
            this.value = boletasDisponibles;
            alert('Solo hay ' + boletasDisponibles + ' boletas disponibles.');
        }
    });
    
    // Validar al hacer clic en el botón de pagar
    customQuantityButton.addEventListener('click', function(e) {
        e.preventDefault();
        
        const value = parseInt(customQuantityInput.value) || 0;
        
        if (value <= 0) {
            alert('Por favor ingrese una cantidad válida de boletas.');
            return false;
        }
        
        if (value > boletasDisponibles) {
            alert('Solo hay ' + boletasDisponibles + ' boletas disponibles.');
            customQuantityInput.value = boletasDisponibles;
            return false;
        }
        
        // Redirigir directamente al checkout con parámetros
        window.location.href = sistema_rifas_vars.checkout_url + '?rifa_id=' + rifaId + '&cantidad=' + value;
    });
    
    // También validar los botones de compra predefinidos
    const btnsComprar = document.querySelectorAll('.btn-comprar');
    btnsComprar.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const cantidad = parseInt(this.getAttribute('data-cantidad')) || 0;
            
            if (cantidad > boletasDisponibles) {
                alert('No hay suficientes boletas disponibles. Solo quedan ' + boletasDisponibles + ' boletas.');
                return false;
            }
            
            // Actualizar el campo de cantidad
            customQuantityInput.value = cantidad;
            
            // Redirigir directamente al checkout con parámetros
            window.location.href = sistema_rifas_vars.checkout_url + '?rifa_id=' + rifaId + '&cantidad=' + cantidad;
        });
    });
});