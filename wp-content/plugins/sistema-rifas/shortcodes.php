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
    <?php
    return ob_get_clean();
}
add_shortcode('porcentaje_rifa', 'sr_shortcode_porcentaje');

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
    <?php
    return ob_get_clean();
}
add_shortcode('estadisticas_rifa', 'sr_shortcode_estadisticas');

/**
 * Función auxiliar para calcular el total de boletas
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
 * Función auxiliar para obtener el número de boletas vendidas
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