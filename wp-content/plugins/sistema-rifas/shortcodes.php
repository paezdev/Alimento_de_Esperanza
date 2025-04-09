<?php
// Evitar acceso directo
if (!defined('ABSPATH')) exit;



/**
 * Shortcode para mostrar el porcentaje de boletas vendidas
 * Uso: [porcentaje_rifa id="123"]
 */
function sr_shortcode_porcentaje($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
        'color' => '#4CAF50', // Color por defecto
        'mostrar_numeros' => 'no', // Opción para mostrar números vendidos/total
    ), $atts, 'porcentaje_rifa');
    
    $rifa_id = intval($atts['id']);
    $color_barra = sanitize_hex_color($atts['color']) ?: '#4CAF50';
    $mostrar_numeros = ($atts['mostrar_numeros'] === 'si');
    
    if ($rifa_id <= 0) {
        global $post;
        if ($post && $post->post_type === 'rifas') {
            global $wpdb;
            $tabla_rifas = $wpdb->prefix . 'rifas';
            $rifa_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla_rifas WHERE post_id = %d",
                $post->ID
            ));
        }
    }
    
    if ($rifa_id <= 0) {
        return '<p>Error: ID de rifa no válido</p>';
    }
    
    global $wpdb;
    $tabla_rifas = $wpdb->prefix . 'rifas';
    
    $rifa = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tabla_rifas WHERE id = %d",
        $rifa_id
    ));
    
    if (!$rifa) {
        return '<p>Error: Rifa no encontrada</p>';
    }
    
    $porcentaje = sr_calcular_porcentaje_vendido($rifa_id);
    
    // Si se solicita mostrar números, obtener los datos
    $texto_adicional = '';
    if ($mostrar_numeros) {
        $total_boletas = sr_calcular_total_boletas($rifa->cifras);
        $boletas_vendidas = sr_obtener_boletas_vendidas($rifa_id);
        $texto_adicional = " ({$boletas_vendidas} de {$total_boletas} boletas)";
    }
    
    ob_start();
    ?>
    <div class="rifa-progress-container">
        <p class="rifa-progress-text">
            <span class="rifa-progress-percentage"><?php echo number_format($porcentaje, 1); ?>%</span> 
            <span class="rifa-progress-label">Meta alcanzada<?php echo esc_html($texto_adicional); ?></span>
        </p>
        <div class="rifa-progress-bar">
            <div class="rifa-progress" style="width: <?php echo min(100, $porcentaje); ?>%; background-color: <?php echo esc_attr($color_barra); ?>">
                <?php if ($porcentaje >= 5): ?>
                <span class="rifa-progress-text-inside"><?php echo number_format($porcentaje, 1); ?>%</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
    .rifa-progress-container {
        margin: 15px 0;
        font-family: Arial, sans-serif;
    }
    
    .rifa-progress-text {
        margin-bottom: 5px;
        font-size: 16px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .rifa-progress-percentage {
        font-weight: bold;
        font-size: 18px;
        margin-right: 5px;
    }
    
    .rifa-progress-bar {
        height: 25px;
        background-color: #f0f0f0;
        border-radius: 12.5px;
        overflow: hidden;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
        position: relative;
    }
    
    .rifa-progress {
        height: 100%;
        transition: width 0.8s ease-in-out;
        border-radius: 12.5px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    
    .rifa-progress-text-inside {
        color: white;
        font-weight: bold;
        text-shadow: 1px 1px 1px rgba(0,0,0,0.3);
        font-size: 14px;
    }
    
    @media (max-width: 480px) {
        .rifa-progress-text {
            font-size: 14px;
        }
        
        .rifa-progress-percentage {
            font-size: 16px;
        }
        
        .rifa-progress-bar {
            height: 20px;
            border-radius: 10px;
        }
        
        .rifa-progress {
            border-radius: 10px;
        }
        
        .rifa-progress-text-inside {
            font-size: 12px;
        }
    }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('porcentaje_rifa', 'sr_shortcode_porcentaje');

/**
 * Función auxiliar para sanitizar colores hexadecimales
 */
if (!function_exists('sanitize_hex_color')) {
    function sanitize_hex_color($color) {
        if ('' === $color) {
            return '';
        }
        
        // 3 or 6 hex digits, or the empty string.
        if (preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color)) {
            return $color;
        }
        
        return '';
    }
}

/**
 * Calcula el total de boletas basado en el número de cifras
 */
if (!function_exists('sr_calcular_total_boletas')) {
    function sr_calcular_total_boletas($cifras) {
        if ($cifras <= 0) {
            return 0;
        }
        
        return pow(10, $cifras);
    }
}

/**
 * Obtiene el número de boletas vendidas para una rifa
 */
if (!function_exists('sr_obtener_boletas_vendidas')) {
    function sr_obtener_boletas_vendidas($rifa_id) {
        global $wpdb;
        $tabla_boletas = $wpdb->prefix . 'boletas_vendidas';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_boletas WHERE rifa_id = %d AND estado = 'vendido'",
            $rifa_id
        ));
        
        return intval($count);
    }
}

/**
 * Calcula el porcentaje de boletas vendidas
 */
if (!function_exists('sr_calcular_porcentaje_vendido')) {
    function sr_calcular_porcentaje_vendido($rifa_id) {
        global $wpdb;
        $tabla_rifas = $wpdb->prefix . 'rifas';
        
        $rifa = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_rifas WHERE id = %d",
            $rifa_id
        ));
        
        if (!$rifa) {
            return 0;
        }
        
        $total_boletas = sr_calcular_total_boletas($rifa->cifras);
        $boletas_vendidas = sr_obtener_boletas_vendidas($rifa_id);
        
        if ($total_boletas <= 0) {
            return 0;
        }
        
        return ($boletas_vendidas / $total_boletas) * 100;
    }
}





/**
 * Shortcode para mostrar estadísticas de la rifa
 * Uso: [estadisticas_rifa id="123"]
 */
function sr_shortcode_estadisticas($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'estadisticas_rifa');
    
    $rifa_id = intval($atts['id']);
    
    if ($rifa_id <= 0) {
        global $post;
        if ($post && $post->post_type === 'rifas') {
            global $wpdb;
            $tabla_rifas = $wpdb->prefix . 'rifas';
            $rifa_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla_rifas WHERE post_id = %d",
                $post->ID
            ));
        }
    }
    
    if ($rifa_id <= 0) {
        return '<p>Error: ID de rifa no válido</p>';
    }
    
    global $wpdb;
    $tabla_rifas = $wpdb->prefix . 'rifas';
    
    $rifa = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tabla_rifas WHERE id = %d",
        $rifa_id
    ));
    
    if (!$rifa) {
        return '<p>Error: Rifa no encontrada</p>';
    }
    
    $total_boletas = sr_calcular_total_boletas($rifa->cifras);
    $boletas_vendidas = sr_obtener_boletas_vendidas($rifa_id);
    $boletas_disponibles = $total_boletas - $boletas_vendidas;
    $porcentaje = sr_calcular_porcentaje_vendido($rifa_id);
    
    ob_start();
    ?>
    <div class="rifa-estadisticas">
        <div class="rifa-progreso">
            <div class="rifa-progreso-barra">
                <div class="rifa-progreso-completado" style="width: <?php echo min(100, $porcentaje); ?>%;"></div>
            </div>
            <div class="rifa-progreso-porcentaje"><?php echo number_format($porcentaje, 2); ?>% completado</div>
        </div>
        
        <div class="rifa-estadisticas-grid">
            <div class="rifa-estadistica">
                <span class="rifa-estadistica-valor"><?php echo number_format($total_boletas); ?></span>
                <span class="rifa-estadistica-label">Total de boletas</span>
            </div>
            
            <div class="rifa-estadistica">
                <span class="rifa-estadistica-valor"><?php echo number_format($boletas_vendidas); ?></span>
                <span class="rifa-estadistica-label">Boletas vendidas</span>
            </div>
            
            <div class="rifa-estadistica">
                <span class="rifa-estadistica-valor"><?php echo number_format($boletas_disponibles); ?></span>
                <span class="rifa-estadistica-label">Boletas disponibles</span>
            </div>
            
            <div class="rifa-estadistica">
                <span class="rifa-estadistica-valor"><?php echo number_format($porcentaje, 2); ?>%</span>
                <span class="rifa-estadistica-label">Meta alcanzada</span>
            </div>
        </div>
    </div>
    
    <style>
    .rifa-estadisticas {
        margin: 20px 0;
        font-family: Arial, sans-serif;
    }
    
    .rifa-progreso {
        margin-bottom: 20px;
    }
    
    .rifa-progreso-barra {
        height: 20px;
        background-color: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 5px;
    }
    
    .rifa-progreso-completado {
        height: 100%;
        background-color: #4CAF50;
        transition: width 0.5s ease-in-out;
    }
    
    .rifa-progreso-porcentaje {
        text-align: center;
        font-size: 14px;
        color: #666;
    }
    
    .rifa-estadisticas-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    @media (min-width: 768px) {
        .rifa-estadisticas-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }
    
    .rifa-estadistica {
        background-color: #f9f9f9;
        border-radius: 5px;
        padding: 15px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .rifa-estadistica-valor {
        display: block;
        font-size: 24px;
        font-weight: bold;
        color: #333;
        margin-bottom: 5px;
    }
    
    .rifa-estadistica-label {
        display: block;
        font-size: 14px;
        color: #666;
    }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('estadisticas_rifa', 'sr_shortcode_estadisticas');

/**
 * Calcula el total de boletas basado en el número de cifras
 */
if (!function_exists('sr_calcular_total_boletas')) {
    function sr_calcular_total_boletas($cifras) {
        if ($cifras <= 0) {
            return 0;
        }
        
        return pow(10, $cifras);
    }
}

/**
 * Obtiene el número de boletas vendidas para una rifa
 */
if (!function_exists('sr_obtener_boletas_vendidas')) {
    function sr_obtener_boletas_vendidas($rifa_id) {
        global $wpdb;
        $tabla_boletas = $wpdb->prefix . 'boletas_vendidas';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_boletas WHERE rifa_id = %d AND estado = 'vendido'",
            $rifa_id
        ));
        
        return intval($count);
    }
}

/**
 * Calcula el porcentaje de boletas vendidas
 */
if (!function_exists('sr_calcular_porcentaje_vendido')) {
    function sr_calcular_porcentaje_vendido($rifa_id) {
        global $wpdb;
        $tabla_rifas = $wpdb->prefix . 'rifas';
        
        $rifa = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_rifas WHERE id = %d",
            $rifa_id
        ));
        
        if (!$rifa) {
            return 0;
        }
        
        $total_boletas = sr_calcular_total_boletas($rifa->cifras);
        $boletas_vendidas = sr_obtener_boletas_vendidas($rifa_id);
        
        if ($total_boletas <= 0) {
            return 0;
        }
        
        return ($boletas_vendidas / $total_boletas) * 100;
    }
}









/**
 * Shortcode para mostrar la página de respuesta de pago
 * Uso: [respuesta_pago]
 */
function shortcode_respuesta_pago() {
    ob_start();
    
    // Verificar si hay parámetros de respuesta de ePayco
    if (isset($_GET['ref_payco'])) {
        $ref_payco = sanitize_text_field($_GET['ref_payco']);
        
        // URL para consultar el estado de la transacción
        $url = "https://secure.epayco.co/validation/v1/reference/" . $ref_payco;
        
        // Realizar la consulta
        $response = wp_remote_get($url);
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);
            
            if ($data && isset($data->success) && $data->success) {
                // Obtener datos de la transacción
                $estado = isset($data->data->x_transaction_state) ? $data->data->x_transaction_state : '';
                $codigo_estado = isset($data->data->x_cod_response) ? $data->data->x_cod_response : '';
                $motivo = isset($data->data->x_response_reason_text) ? $data->data->x_response_reason_text : '';
                $fecha = isset($data->data->x_transaction_date) ? $data->data->x_transaction_date : '';
                $valor = isset($data->data->x_amount) ? $data->data->x_amount : '';
                
                // Obtener datos adicionales
                $rifa_id = isset($data->data->x_extra1) ? intval($data->data->x_extra1) : 0;
                $cantidad = isset($data->data->x_extra2) ? intval($data->data->x_extra2) : 0;
                $nombre_cliente = isset($data->data->x_extra3) ? sanitize_text_field($data->data->x_extra3) : '';
                
                // Obtener información de la rifa
                $rifa_title = '';
                if ($rifa_id > 0) {
                    global $wpdb;
                    $tabla_rifas = $wpdb->prefix . 'rifas';
                    $rifa = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $tabla_rifas WHERE id = %d",
                        $rifa_id
                    ));
                    
                    if ($rifa && isset($rifa->post_id)) {
                        $post = get_post($rifa->post_id);
                        if ($post) {
                            $rifa_title = $post->post_title;
                        }
                    }
                }
                
                // Mostrar mensaje según el estado
                if ($estado == 'Aceptada' || $codigo_estado == '1') {
                    // Buscar los números asignados
                    $numeros = array();
                    global $wpdb;
                    $tabla_boletas = $wpdb->prefix . 'boletas_vendidas';
                    $resultados = $wpdb->get_results($wpdb->prepare(
                        "SELECT numero_boleta FROM $tabla_boletas WHERE transaccion_id = %s AND estado = 'vendido'",
                        $ref_payco
                    ));
                    
                    if ($resultados) {
                        foreach ($resultados as $resultado) {
                            $numeros[] = $resultado->numero_boleta;
                        }
                    }
                    
                    echo '<div class="mensaje-exito">';
                    echo '<h2>¡Pago realizado con éxito!</h2>';
                    echo '<p>Tu compra ha sido procesada correctamente.</p>';
                    
                    if (!empty($rifa_title)) {
                        echo '<p><strong>Rifa:</strong> ' . esc_html($rifa_title) . '</p>';
                    }
                    
                    echo '<p><strong>Referencia de pago:</strong> ' . esc_html($ref_payco) . '</p>';
                    echo '<p><strong>Fecha:</strong> ' . esc_html($fecha) . '</p>';
                    echo '<p><strong>Valor:</strong> $' . number_format(floatval($valor), 0, ',', '.') . '</p>';
                    
                    if (!empty($numeros)) {
                        echo '<p><strong>Tus números:</strong> ' . esc_html(implode(', ', $numeros)) . '</p>';
                        echo '<p>¡Buena suerte en el sorteo!</p>';
                    } else {
                        echo '<p>Tus números están siendo procesados y te serán enviados por correo electrónico.</p>';
                    }
                    
                    echo '<p><a href="' . home_url() . '" class="boton-volver">Volver al inicio</a></p>';
                    echo '</div>';
                } else if ($estado == 'Pendiente' || $codigo_estado == '3') {
                    echo '<div class="mensaje-pendiente">';
                    echo '<h2>Pago en proceso</h2>';
                    echo '<p>Tu pago está siendo procesado. Te notificaremos cuando se complete.</p>';
                    
                    if (!empty($rifa_title)) {
                        echo '<p><strong>Rifa:</strong> ' . esc_html($rifa_title) . '</p>';
                    }
                    
                    echo '<p><strong>Referencia de pago:</strong> ' . esc_html($ref_payco) . '</p>';
                    echo '<p><strong>Fecha:</strong> ' . esc_html($fecha) . '</p>';
                    echo '<p><strong>Valor:</strong> $' . number_format(floatval($valor), 0, ',', '.') . '</p>';
                    
                    echo '<p><a href="' . home_url() . '" class="boton-volver">Volver al inicio</a></p>';
                    echo '</div>';
                } else {
                    echo '<div class="mensaje-error">';
                    echo '<h2>Pago rechazado</h2>';
                    echo '<p>Lo sentimos, tu pago no pudo ser procesado.</p>';
                    
                    if (!empty($rifa_title)) {
                        echo '<p><strong>Rifa:</strong> ' . esc_html($rifa_title) . '</p>';
                    }
                    
                    echo '<p><strong>Referencia de pago:</strong> ' . esc_html($ref_payco) . '</p>';
                    echo '<p><strong>Motivo:</strong> ' . esc_html($motivo) . '</p>';
                    
                    if ($rifa_id > 0) {
                        $rifa_url = get_permalink(get_post($rifa->post_id));
                        echo '<p><a href="' . esc_url($rifa_url) . '" class="boton-volver">Volver a intentar</a></p>';
                    } else {
                        echo '<p><a href="' . home_url() . '" class="boton-volver">Volver al inicio</a></p>';
                    }
                    
                    echo '</div>';
                }
            } else {
                echo '<div class="mensaje-error">';
                echo '<h2>Error al verificar el pago</h2>';
                echo '<p>No pudimos verificar el estado de tu pago. Por favor, contacta al administrador.</p>';
                echo '<p><strong>Referencia:</strong> ' . esc_html($ref_payco) . '</p>';
                echo '<p><a href="' . home_url() . '" class="boton-volver">Volver al inicio</a></p>';
                echo '</div>';
            }
        } else {
            echo '<div class="mensaje-error">';
            echo '<h2>Error de conexión</h2>';
            echo '<p>No pudimos conectar con el servicio de pagos. Por favor, intenta más tarde.</p>';
            echo '<p><a href="' . home_url() . '" class="boton-volver">Volver al inicio</a></p>';
            echo '</div>';
        }
    } else {
        echo '<div class="mensaje-error">';
        echo '<h2>Información de pago no disponible</h2>';
        echo '<p>No se encontró información sobre tu pago.</p>';
        echo '<p><a href="' . home_url() . '" class="boton-volver">Volver al inicio</a></p>';
        echo '</div>';
    }
    
    // Añadir estilos CSS
    echo '<style>
    .mensaje-exito, .mensaje-pendiente, .mensaje-error {
        max-width: 800px;
        margin: 30px auto;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .mensaje-exito {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }
    
    .mensaje-pendiente {
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        color: #856404;
    }
    
    .mensaje-error {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }
    
    .mensaje-exito h2, .mensaje-pendiente h2, .mensaje-error h2 {
        margin-top: 0;
        margin-bottom: 20px;
    }
    
    .boton-volver {
        display: inline-block;
        background-color: #007bff;
        color: white;
        padding: 10px 15px;
        border-radius: 4px;
        text-decoration: none;
        margin-top: 10px;
    }
    
    .boton-volver:hover {
        background-color: #0069d9;
        text-decoration: none;
        color: white;
    }
    </style>';
    
    return ob_get_clean();
}
add_shortcode('respuesta_pago', 'shortcode_respuesta_pago');









// Shortcode para la página de checkout
function sistema_rifas_checkout_shortcode($atts) {
    // Verificar si tenemos los parámetros necesarios
    if (!isset($_GET['rifa_id']) || !isset($_GET['cantidad'])) {
        return '<p>Parámetros incorrectos. Por favor, vuelve a la página de la rifa.</p>';
    }
    
    $rifa_id = intval($_GET['rifa_id']);
    $cantidad = intval($_GET['cantidad']);
    
    // Obtener información de la rifa
    global $wpdb;
    $tabla_rifas = $wpdb->prefix . 'rifas';
    $rifa = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tabla_rifas WHERE id = %d",
        $rifa_id
    ));
    
    if (!$rifa) {
        return '<p>Rifa no encontrada. Por favor, vuelve a intentarlo.</p>';
    }
    
    // Obtener información del post asociado
    $post = get_post($rifa->post_id);
    if (!$post) {
        return '<p>Información de la rifa no disponible. Por favor, vuelve a intentarlo.</p>';
    }
    
    // Calcular precio total
    $precio_unitario = $rifa->precio_boleta;
    $precio_total = $precio_unitario * $cantidad;
    
    // Generar HTML para el checkout
    ob_start();
    ?>
    <div class="checkout-container">
        <div class="checkout-steps">
            <div class="step active">
                <span class="step-number">1</span>
                <span class="step-text">Facturación</span>
            </div>
            <div class="step">
                <span class="step-number">2</span>
                <span class="step-text">Pago</span>
            </div>
        </div>
        
        <div class="checkout-content">
            <div class="checkout-left">
                <h2>Detalles De Facturación</h2>
                
                <form id="checkout-form" method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre <span class="required">*</span></label>
                            <input type="text" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="apellidos">Apellidos <span class="required">*</span></label>
                            <input type="text" id="apellidos" name="apellidos" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">Teléfono <span class="required">*</span></label>
                        <input type="tel" id="telefono" name="telefono" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Dirección de correo electrónico <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <input type="hidden" id="rifa_id" name="rifa_id" value="<?php echo esc_attr($rifa_id); ?>">
                    <input type="hidden" id="cantidad" name="cantidad" value="<?php echo esc_attr($cantidad); ?>">
                    <input type="hidden" id="precio_unitario" name="precio_unitario" value="<?php echo esc_attr($precio_unitario); ?>">
                    
                    <button type="button" id="btn-siguiente" class="checkout-button">Siguiente</button>
                </form>
            </div>
            
            <div class="checkout-right">
                <div class="order-summary">
                    <h3>Resumen del pedido</h3>
                    
                    <div class="order-item">
                        <div class="item-image">
                            <?php echo get_the_post_thumbnail($post->ID, 'thumbnail'); ?>
                        </div>
                        <div class="item-details">
                            <h4><?php echo esc_html($post->post_title); ?></h4>
                            <p>Cantidad: <?php echo esc_html($cantidad); ?> número(s)</p>
                        </div>
                        <div class="item-price">
                            $<?php echo number_format($precio_total, 0, '.', ','); ?>
                        </div>
                    </div>
                    
                    <div class="order-totals">
                        <div class="total-row">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($precio_total, 0, '.', ','); ?></span>
                        </div>
                        <div class="total-row grand-total">
                            <span>Total</span>
                            <span>$<?php echo number_format($precio_total, 0, '.', ','); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cargar el script de ePayco -->
    <script src="https://checkout.epayco.co/checkout.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
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
                
                // Generar referencia única
                const referencia = "rifa_" + rifaId + "_" + Date.now();
                
                // Iniciar pago con ePayco
                if (typeof ePayco !== 'undefined') {
                    // Configuración de ePayco
                    var handler = ePayco.checkout.configure({
                        key: '<?php echo esc_js(get_option('sr_epayco_p_cust_id', '69101')); ?>',
                        test: <?php echo get_option('sr_epayco_test', '1') ? 'true' : 'false'; ?>
                    });
                    
                    // Datos para el checkout
                    var data = {
                        name: '<?php echo esc_js($post->post_title); ?>',
                        description: 'Compra de ' + cantidad + ' número(s) para la rifa',
                        currency: 'cop',
                        amount: precioTotal,
                        tax_base: '0',
                        tax: '0',
                        country: 'co',
                        lang: 'es',
                        external: false,
                        
                        // Información del cliente
                        name_billing: nombre,
                        lastname_billing: apellidos,
                        email_billing: email,
                        phone_billing: telefono,
                        
                        // Información adicional para el callback
                        extra1: rifaId,
                        extra2: cantidad,
                        extra3: nombre + ' ' + apellidos,
                        
                        // Referencia única para esta transacción
                        invoice: referencia,
                        
                        // URLs de respuesta
                        response: '<?php echo esc_js(site_url('/respuesta-pago/')); ?>',
                        confirmation: '<?php echo esc_js(site_url('/wp-admin/admin-ajax.php?action=sr_epayco_confirmation')); ?>'
                    };
                    
                    console.log('Datos para ePayco:', data);
                    handler.open(data);
                } else {
                    alert('El sistema de pagos no está disponible en este momento. Por favor, inténtalo más tarde.');
                }
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('sistema_rifas_checkout', 'sistema_rifas_checkout_shortcode');









/**
 * Shortcode para mostrar el formulario de checkout
 * Uso: [checkout_rifa]
 */
function sr_shortcode_checkout($atts) {
    $atts = shortcode_atts(array(), $atts, 'checkout_rifa');
    
    // Verificar si hay una transacción en curso
    $transaccion_id = WC()->session->get('sr_transaccion_id');
    $numeros = WC()->session->get('sr_numeros_reservados');
    $rifa_id = WC()->session->get('sr_rifa_id');
    $cantidad = WC()->session->get('sr_cantidad');
    
    if (!$transaccion_id || !$numeros || !$rifa_id) {
        return '<p>No hay una compra en proceso. <a href="' . home_url('/rifas') . '">Ver rifas disponibles</a></p>';
    }
    
    // Obtener información de la rifa
    global $wpdb;
    $tabla_rifas = $wpdb->prefix . 'rifas';
    
    $rifa = $wpdb->get_row($wpdb->prepare(
        "SELECT r.*, p.post_title FROM $tabla_rifas r 
        JOIN {$wpdb->posts} p ON r.post_id = p.ID
        WHERE r.id = %d",
        $rifa_id
    ));
    
    if (!$rifa) {
        return '<p>Error: Rifa no encontrada</p>';
    }
    
    // Calcular precio total
    $precio_unitario = floatval($rifa->precio_boleta);
    $precio_total = $precio_unitario * $cantidad;
    
    ob_start();
    ?>
    <div class="checkout-rifa-container">
        <h2>Completar compra</h2>
        
        <div class="checkout-rifa-resumen">
            <h3>Resumen de tu compra</h3>
            <p><strong>Rifa:</strong> <?php echo esc_html($rifa->post_title); ?></p>
            <p><strong>Cantidad de números:</strong> <?php echo esc_html($cantidad); ?></p>
            <p><strong>Precio por número:</strong> $<?php echo number_format($precio_unitario, 0, '.', ','); ?></p>
            <p><strong>Total a pagar:</strong> $<?php echo number_format($precio_total, 0, '.', ','); ?></p>
            
            <div class="checkout-rifa-numeros">
                <h4>Tus números:</h4>
                <div class="numeros-list">
                    <?php foreach ($numeros as $numero): ?>
                    <span class="numero-item"><?php echo esc_html($numero); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <form id="checkout-form" class="checkout-rifa-form">
            <h3>Datos del comprador</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                
                <div class="form-group">
                    <label for="apellido">Apellido *</label>
                    <input type="text" id="apellido" name="apellido" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="telefono">Teléfono *</label>
                    <input type="tel" id="telefono" name="telefono" required>
                </div>
            </div>
            
            <input type="hidden" id="transaccion_id" name="transaccion_id" value="<?php echo esc_attr($transaccion_id); ?>">
            <input type="hidden" id="rifa_id" name="rifa_id" value="<?php echo esc_attr($rifa_id); ?>">
            <input type="hidden" id="cantidad" name="cantidad" value="<?php echo esc_attr($cantidad); ?>">
            <input type="hidden" id="precio_total" name="precio_total" value="<?php echo esc_attr($precio_total); ?>">
            
            <div class="form-actions">
                <button type="submit" id="btn-pagar-checkout" class="btn-pagar-checkout">
                    Proceder al pago
                </button>
            </div>
        </form>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkoutForm = document.getElementById('checkout-form');
        const btnPagar = document.getElementById('btn-pagar-checkout');
        
        if (checkoutForm && btnPagar) {
            checkoutForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validar formulario
                const nombre = document.getElementById('nombre').value.trim();
                const apellido = document.getElementById('apellido').value.trim();
                const email = document.getElementById('email').value.trim();
                const telefono = document.getElementById('telefono').value.trim();
                
                if (!nombre || !apellido || !email || !telefono) {
                    alert('Por favor complete todos los campos obligatorios');
                    return false;
                }
                
                // Deshabilitar botón para evitar doble envío
                btnPagar.disabled = true;
                btnPagar.textContent = 'Procesando...';
                
                // Actualizar datos del cliente en la base de datos
                const data = new FormData();
                data.append('action', 'sr_actualizar_datos_cliente');
                data.append('nonce', sistema_rifas_vars.nonce);
                data.append('transaccion_id', document.getElementById('transaccion_id').value);
                data.append('nombre', nombre);
                data.append('apellido', apellido);
                data.append('email', email);
                data.append('telefono', telefono);
                
                fetch(sistema_rifas_vars.ajax_url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: data
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Iniciar proceso de pago con ePayco
                        const handler = ePayco.checkout.configure({
                            key: sistema_rifas_vars.epayco_key,
                            test: sistema_rifas_vars.epayco_test === '1'
                        });
                        
                        const precio_total = parseFloat(document.getElementById('precio_total').value);
                        const rifa_id = document.getElementById('rifa_id').value;
                        const transaccion_id = document.getElementById('transaccion_id').value;
                        
                        handler.open({
                            name: 'Compra de boletas',
                            description: 'Rifa: ' + document.querySelector('h3 + p strong + span').textContent,
                            currency: 'cop',
                            amount: precio_total,
                            tax_base: '0',
                            tax: '0',
                            country: 'co',
                            lang: 'es',
                            external: false,
                            response: sistema_rifas_vars.respuesta_url,
                            
                            // Información del comprador
                            name_billing: nombre + ' ' + apellido,
                            email_billing: email,
                            mobilephone_billing: telefono,
                            
                            // Información adicional
                            extra1: rifa_id,
                            extra2: transaccion_id,
                            extra3: document.getElementById('cantidad').value
                        });
                    } else {
                        alert(data.data.mensaje || 'Error al procesar la solicitud');
                        btnPagar.disabled = false;
                        btnPagar.textContent = 'Proceder al pago';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud');
                    btnPagar.disabled = false;
                    btnPagar.textContent = 'Proceder al pago';
                });
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('checkout_rifa', 'sr_shortcode_checkout');





// Shortcode para la página de respuesta de pago
function sr_respuesta_pago_shortcode($atts) {
    // Verificar si hay datos de la transacción
    if (!isset($_GET['ref_payco']) || empty($_GET['ref_payco'])) {
        return '<div class="error-message">No se ha recibido información de la transacción.</div>';
    }
    
    $ref_payco = sanitize_text_field($_GET['ref_payco']);
    
    // Obtener información de la transacción desde ePayco
    $epayco_test = get_option('sr_epayco_test', '1');
    $url_consulta = 'https://secure.epayco.co/validation/v1/reference/' . $ref_payco;
    
    $response = wp_remote_get($url_consulta);
    
    if (is_wp_error($response)) {
        return '<div class="error-message">Error al consultar la transacción: ' . $response->get_error_message() . '</div>';
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);
    
    if (!$data || !isset($data->success) || !$data->success) {
        return '<div class="error-message">Error al procesar la información de la transacción.</div>';
    }
    
    // Verificar estado de la transacción
    $estado = isset($data->data->x_response) ? strtolower($data->data->x_response) : '';
    
    // Obtener datos adicionales
    $rifa_id = isset($data->data->x_extra1) ? intval($data->data->x_extra1) : 0;
    $cantidad = isset($data->data->x_extra2) ? intval($data->data->x_extra2) : 0;
    $nombre_cliente = isset($data->data->x_extra3) ? sanitize_text_field($data->data->x_extra3) : '';
    
    // Datos del cliente
    $nombre = isset($data->data->x_customer_name) ? sanitize_text_field($data->data->x_customer_name) : '';
    $email = isset($data->data->x_customer_email) ? sanitize_email($data->data->x_customer_email) : '';
    $telefono = isset($data->data->x_customer_phone) ? sanitize_text_field($data->data->x_customer_phone) : '';
    
    // Separar nombre y apellido
    $partes_nombre = explode(' ', $nombre, 2);
    $nombre_cliente = $partes_nombre[0];
    $apellido_cliente = isset($partes_nombre[1]) ? $partes_nombre[1] : '';
    
    // Procesar según el estado
    $output = '<div class="respuesta-pago">';
    
    if ($estado == 'aceptada' || $estado == 'aprobada') {
        // Transacción exitosa
        
        // Datos del cliente para la reserva
        $datos_cliente = array(
            'nombre' => $nombre_cliente,
            'apellido' => $apellido_cliente,
            'email' => $email,
            'telefono' => $telefono
        );
        
        // Reservar números
        $resultado = sr_reservar_numeros_aleatorios($rifa_id, $cantidad, $datos_cliente);
        
        if (is_wp_error($resultado)) {
            $output .= '<div class="error-message">';
            $output .= '<h2>Error al reservar números</h2>';
            $output .= '<p>' . $resultado->get_error_message() . '</p>';
            $output .= '</div>';
        } else {
            // Confirmar la compra
            $confirmacion = sr_confirmar_compra_numeros($resultado['transaccion_id'], $ref_payco);
            
            if (is_wp_error($confirmacion)) {
                $output .= '<div class="error-message">';
                $output .= '<h2>Error al confirmar la compra</h2>';
                $output .= '<p>' . $confirmacion->get_error_message() . '</p>';
                $output .= '</div>';
            } else {
                // Enviar email con los números
                sr_enviar_email_numeros($rifa_id, $ref_payco);
                
                $output .= '<div class="success-message">';
                $output .= '<h2>¡Compra exitosa!</h2>';
                $output .= '<p>Tu pago ha sido procesado correctamente.</p>';
                $output .= '<p>Referencia de pago: <strong>' . esc_html($ref_payco) . '</strong></p>';
                $output .= '<p>Tus números: <strong>' . implode(', ', $resultado['numeros']) . '</strong></p>';
                $output .= '<p>Te hemos enviado un correo electrónico con los detalles de tu compra.</p>';
                $output .= '</div>';
            }
        }
    } elseif ($estado == 'pendiente' || $estado == 'en validacion') {
        // Transacción pendiente
        $output .= '<div class="pending-message">';
        $output .= '<h2>Pago en proceso</h2>';
        $output .= '<p>Tu pago está siendo procesado. Te notificaremos cuando se complete.</p>';
        $output .= '<p>Referencia de pago: <strong>' . esc_html($ref_payco) . '</strong></p>';
        $output .= '</div>';
    } else {
        // Transacción fallida
        $output .= '<div class="error-message">';
        $output .= '<h2>Pago rechazado</h2>';
        $output .= '<p>Lo sentimos, tu pago ha sido rechazado.</p>';
        $output .= '<p>Referencia de pago: <strong>' . esc_html($ref_payco) . '</strong></p>';
        $output .= '<p>Motivo: <strong>' . esc_html($data->data->x_response_reason_text) . '</strong></p>';
        $output .= '<p><a href="' . esc_url(get_permalink($rifa_id)) . '">Volver a intentar</a></p>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}
add_shortcode('respuesta_pago', 'sr_respuesta_pago_shortcode');







// Shortcode para la página de checkout
function sr_checkout_shortcode($atts) {
    // Iniciar sesión si no está iniciada
    if (!session_id()) {
        session_start();
    }
    
    // Obtener parámetros de la URL
    $rifa_id = isset($_GET['rifa_id']) ? intval($_GET['rifa_id']) : 0;
    $cantidad = isset($_GET['cantidad']) ? intval($_GET['cantidad']) : 0;
    
    // Si no hay parámetros en la URL, intentar obtenerlos de la sesión
    if ($rifa_id <= 0 || $cantidad <= 0) {
        $rifa_id = isset($_SESSION['sr_rifa_id']) ? $_SESSION['sr_rifa_id'] : 0;
        $cantidad = isset($_SESSION['sr_cantidad']) ? $_SESSION['sr_cantidad'] : 0;
    }
    
    // Si no hay datos válidos, mostrar mensaje de error
    if ($rifa_id <= 0 || $cantidad <= 0) {
        return '<div class="error-message">No se han especificado los datos de la compra.</div>';
    }
    
    // Guardar en sesión
    $_SESSION['sr_rifa_id'] = $rifa_id;
    $_SESSION['sr_cantidad'] = $cantidad;
    
    // Obtener información de la rifa
    $rifa_title = get_the_title($rifa_id);
    $precio_boleta = get_field('precio_de_la_boleta', $rifa_id);
    $precio_total = $precio_boleta * $cantidad;
    
    // Construir el formulario de checkout
    $output = '<div class="checkout-container">';
    
    // Resumen de la compra
    $output .= '<div class="checkout-summary">';
    $output .= '<h2>Resumen de tu compra</h2>';
    $output .= '<p><strong>Rifa:</strong> ' . esc_html($rifa_title) . '</p>';
    $output .= '<p><strong>Cantidad de boletas:</strong> ' . esc_html($cantidad) . '</p>';
    $output .= '<p><strong>Precio por boleta:</strong> $' . number_format($precio_boleta, 0, ',', '.') . '</p>';
    $output .= '<p><strong>Total a pagar:</strong> $' . number_format($precio_total, 0, ',', '.') . '</p>';
    $output .= '</div>';
    
    // Formulario de datos del cliente
    $output .= '<div class="checkout-form">';
    $output .= '<h2>Datos del comprador</h2>';
    $output .= '<form id="checkout-form">';
    
    $output .= '<div class="form-group">';
    $output .= '<label for="nombre">Nombre *</label>';
    $output .= '<input type="text" id="nombre" name="nombre" required>';
    $output .= '</div>';
    
    $output .= '<div class="form-group">';
    $output .= '<label for="apellido">Apellido *</label>';
    $output .= '<input type="text" id="apellido" name="apellido" required>';
    $output .= '</div>';
    
    $output .= '<div class="form-group">';
    $output .= '<label for="telefono">Teléfono *</label>';
    $output .= '<input type="tel" id="telefono" name="telefono" required>';
    $output .= '</div>';
    
    $output .= '<div class="form-group">';
    $output .= '<label for="email">Email *</label>';
    $output .= '<input type="email" id="email" name="email" required>';
    $output .= '</div>';
    
    // Botón de pago con ePayco
    $output .= '<div class="form-group">';
    $output .= '<button type="button" id="btn-pagar-epayco" class="btn-pagar">Pagar con ePayco</button>';
    $output .= '</div>';
    
    $output .= '</form>';
    $output .= '</div>';
    
    // Datos para el script de ePayco
    $epayco_key = get_option('sr_epayco_key', '');
    $epayco_test = get_option('sr_epayco_test', '1');
    
    // Script para manejar el pago
    $output .= '<script>
    document.addEventListener("DOMContentLoaded", function() {
        const checkoutForm = document.getElementById("checkout-form");
        const btnPagarEpayco = document.getElementById("btn-pagar-epayco");
        
        if (checkoutForm && btnPagarEpayco) {
            btnPagarEpayco.addEventListener("click", function() {
                // Validar formulario
                const nombre = document.getElementById("nombre").value;
                const apellido = document.getElementById("apellido").value;
                const telefono = document.getElementById("telefono").value;
                const email = document.getElementById("email").value;
                
                if (!nombre || !apellido || !telefono || !email) {
                    alert("Por favor completa todos los campos obligatorios.");
                    return;
                }
                
                // Configurar ePayco
                var handler = ePayco.checkout.configure({
                    key: "' . esc_js($epayco_key) . '",
                    test: ' . esc_js($epayco_test) . '
                });
                
                // Abrir ventana de pago
                handler.open({
                    name: "' . esc_js($rifa_title) . '",
                    description: "Compra de ' . esc_js($cantidad) . ' boletas",
                    amount: "' . esc_js($precio_total) . '",
                    tax: "0",
                    tax_base: "' . esc_js($precio_total) . '",
                    currency: "cop",
                    country: "co",
                    lang: "es",
                    external: "false",
                    response: "' . esc_js(site_url('/respuesta-pago/')) . '",
                    
                    name_billing: nombre,
                    lastname_billing: apellido,
                    email_billing: email,
                    phone_billing: telefono,
                    
                    extra1: "' . esc_js($rifa_id) . '",
                    extra2: "' . esc_js($cantidad) . '",
                    extra3: nombre + " " + apellido
                });
            });
        }
    });
    </script>';
    
    $output .= '</div>';
    
    return $output;
}
add_shortcode('checkout_rifas', 'sr_checkout_shortcode');