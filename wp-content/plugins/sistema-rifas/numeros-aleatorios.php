<?php
// Evitar acceso directo
if (!defined('ABSPATH')) exit;

/**
 * Genera números aleatorios para una rifa sin repetir los ya vendidos
 * 
 * @param int $rifa_id ID de la rifa
 * @param int $cantidad Cantidad de números a generar
 * @return array Array con los números generados
 */
function sr_generar_numeros_aleatorios($rifa_id, $cantidad) {
    global $wpdb;
    
    // Obtener información de la rifa desde el post
    $cifras = get_field('cantidad_de_cifras_3_o_4', $rifa_id);
    $cifras = intval($cifras) ?: 3; // Por defecto 3 cifras
    
    // Determinar el rango de números según las cifras
    $min = pow(10, $cifras - 1); // Ejemplo: para 3 cifras, min = 100
    $max = pow(10, $cifras) - 1; // Ejemplo: para 3 cifras, max = 999
    
    // Obtener números ya vendidos
    $tabla_tickets = $wpdb->prefix . 'sistema_rifas_tickets';
    $numeros_vendidos = $wpdb->get_col($wpdb->prepare(
        "SELECT numero FROM $tabla_tickets WHERE rifa_id = %d AND estado != 'cancelado'",
        $rifa_id
    ));
    
    // Convertir a enteros para comparación
    $numeros_vendidos = array_map('intval', $numeros_vendidos);
    
    // Generar números aleatorios sin repetir
    $numeros_generados = array();
    $intentos = 0;
    $max_intentos = ($max - $min + 1) * 2; // Límite de intentos para evitar bucles infinitos
    
    while (count($numeros_generados) < $cantidad && $intentos < $max_intentos) {
        $numero = mt_rand($min, $max);
        
        // Verificar que el número no esté ya vendido ni ya generado
        if (!in_array($numero, $numeros_vendidos) && !in_array($numero, $numeros_generados)) {
            $numeros_generados[] = $numero;
        }
        
        $intentos++;
    }
    
    // Si no se pudieron generar todos los números solicitados
    if (count($numeros_generados) < $cantidad) {
        // Registrar error en el log
        error_log("No se pudieron generar {$cantidad} números aleatorios para la rifa {$rifa_id}. Solo se generaron " . count($numeros_generados));
    }
    
    return $numeros_generados;
}

/**
 * Reserva números aleatorios para una compra
 * 
 * @param int $rifa_id ID de la rifa
 * @param int $cantidad Cantidad de números a reservar
 * @param array $datos_cliente Datos del cliente
 * @return array|WP_Error Array con los números reservados o error
 */
function sr_reservar_numeros_aleatorios($rifa_id, $cantidad, $datos_cliente) {
    // Validar datos del cliente
    if (empty($datos_cliente['nombre']) || empty($datos_cliente['email']) || empty($datos_cliente['telefono'])) {
        return new WP_Error('datos_incompletos', 'Los datos del cliente están incompletos');
    }
    
    // Generar números aleatorios
    $numeros = sr_generar_numeros_aleatorios($rifa_id, $cantidad);
    
    if (empty($numeros)) {
        return new WP_Error('sin_numeros', 'No se pudieron generar números aleatorios');
    }
    
    if (count($numeros) < $cantidad) {
        return new WP_Error('numeros_insuficientes', 'No hay suficientes números disponibles');
    }
    
    // Reservar los números en la base de datos
    global $wpdb;
    $tabla_tickets = $wpdb->prefix . 'sistema_rifas_tickets';
    $transaccion_id = 'res_' . uniqid();
    $numeros_reservados = array();
    
    foreach ($numeros as $numero) {
        $resultado = $wpdb->insert(
            $tabla_tickets,
            array(
                'rifa_id' => $rifa_id,
                'numero' => $numero,
                'nombre_cliente' => $datos_cliente['nombre'],
                'apellido_cliente' => isset($datos_cliente['apellido']) ? $datos_cliente['apellido'] : '',
                'telefono_cliente' => $datos_cliente['telefono'],
                'email_cliente' => $datos_cliente['email'],
                'fecha_compra' => current_time('mysql'),
                'estado' => 'reservado',
                'transaccion_id' => $transaccion_id
            )
        );
        
        if ($resultado) {
            $numeros_reservados[] = $numero;
        }
    }
    
    if (empty($numeros_reservados)) {
        return new WP_Error('error_reserva', 'No se pudieron reservar los números');
    }
    
    return array(
        'numeros' => $numeros_reservados,
        'transaccion_id' => $transaccion_id
    );
}

/**
 * Confirma la compra de números reservados
 * 
 * @param string $transaccion_id ID de la transacción
 * @param string $referencia_pago Referencia del pago
 * @return bool|WP_Error True si se confirmó correctamente o error
 */
function sr_confirmar_compra_numeros($transaccion_id, $referencia_pago) {
    global $wpdb;
    $tabla_tickets = $wpdb->prefix . 'sistema_rifas_tickets';
    
    // Actualizar estado de los números reservados
    $resultado = $wpdb->update(
        $tabla_tickets,
        array(
            'estado' => 'vendido',
            'transaccion_id' => $referencia_pago
        ),
        array(
            'transaccion_id' => $transaccion_id,
            'estado' => 'reservado'
        )
    );
    
    if ($resultado === false) {
        return new WP_Error('error_confirmacion', 'Error al confirmar la compra');
    }
    
    return true;
}

/**
 * Cancela la reserva de números
 * 
 * @param string $transaccion_id ID de la transacción
 * @return bool|WP_Error True si se canceló correctamente o error
 */
function sr_cancelar_reserva_numeros($transaccion_id) {
    global $wpdb;
    $tabla_tickets = $wpdb->prefix . 'sistema_rifas_tickets';
    
    // Actualizar estado de los números reservados
    $resultado = $wpdb->update(
        $tabla_tickets,
        array(
            'estado' => 'cancelado'
        ),
        array(
            'transaccion_id' => $transaccion_id,
            'estado' => 'reservado'
        )
    );
    
    if ($resultado === false) {
        return new WP_Error('error_cancelacion', 'Error al cancelar la reserva');
    }
    
    return true;
}

/**
 * Obtiene los números comprados por un cliente
 * 
 * @param int $rifa_id ID de la rifa
 * @param string $email Email del cliente
 * @return array Array con los números comprados
 */
function sr_obtener_numeros_cliente($rifa_id, $email) {
    global $wpdb;
    $tabla_tickets = $wpdb->prefix . 'sistema_rifas_tickets';
    
    $numeros = $wpdb->get_col($wpdb->prepare(
        "SELECT numero FROM $tabla_tickets WHERE rifa_id = %d AND email_cliente = %s AND estado = 'vendido'",
        $rifa_id,
        $email
    ));
    
    return $numeros;
}

/**
 * Envía email al cliente con sus números comprados
 * 
 * @param int $rifa_id ID de la rifa
 * @param string $transaccion_id ID de la transacción
 * @return bool True si se envió correctamente
 */
function sr_enviar_email_numeros($rifa_id, $transaccion_id) {
    global $wpdb;
    $tabla_tickets = $wpdb->prefix . 'sistema_rifas_tickets';
    
    // Obtener información de la compra
    $compra = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tabla_tickets WHERE transaccion_id = %s AND estado = 'vendido' LIMIT 1",
        $transaccion_id
    ));
    
    if (!$compra) {
        return false;
    }
    
    // Obtener todos los números de esta transacción
    $numeros = $wpdb->get_col($wpdb->prepare(
        "SELECT numero FROM $tabla_tickets WHERE transaccion_id = %s AND estado = 'vendido'",
        $transaccion_id
    ));
    
    if (empty($numeros)) {
        return false;
    }
    
    // Obtener información de la rifa
    $rifa_title = get_the_title($rifa_id);
    
    if (!$rifa_title) {
        return false;
    }
    
    // Preparar el contenido del email
    $to = $compra->email_cliente;
    $subject = 'Tus números para la rifa: ' . $rifa_title;
    
    $message = '<html><body>';
    $message .= '<h2>¡Gracias por tu compra!</h2>';
    $message .= '<p>Has adquirido los siguientes números para la rifa <strong>' . esc_html($rifa_title) . '</strong>:</p>';
    $message .= '<div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">';
    $message .= '<h3>Tus números:</h3>';
    $message .= '<p style="font-size: 18px; font-weight: bold;">' . implode(', ', $numeros) . '</p>';
    $message .= '</div>';
    $message .= '<p>Referencia de pago: <strong>' . esc_html($transaccion_id) . '</strong></p>';
    $message .= '<p>Fecha de compra: <strong>' . date_i18n('d/m/Y H:i', strtotime($compra->fecha_compra)) . '</strong></p>';
    $message .= '<p>¡Buena suerte!</p>';
    $message .= '</body></html>';
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    return wp_mail($to, $subject, $message, $headers);
}