<?php
get_header(); // Carga el encabezado del tema

// Verificar si el script de ePayco está encolado
global $wp_scripts;
$epayco_loaded = false;
foreach ($wp_scripts->registered as $script) {
    if (strpos($script->src, 'checkout.epayco.co') !== false) {
        $epayco_loaded = true;
        break;
    }
}
?>

<?php if (!$epayco_loaded): ?>
<div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border: 1px solid #f5c6cb; border-radius: 5px;">
    <strong>Error:</strong> El script de ePayco no está cargado correctamente. Verifica la configuración del plugin.
</div>
<?php endif; ?>

<!-- Incluir Font Awesome directamente -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<?php
if (have_posts()) :
    while (have_posts()) : the_post();
        // Obtener campos ACF
        $imagen_premio = get_field('imagen_del_premio');
        $nombre_premio = get_field('nombre_del_premio');
        $valor_numero = get_field('valor_de_cada_numero');
        $cantidad_cifras = get_field('cantidad_de_cifras_3_o_4');
        $nombre_loteria = get_field('nombre_de_la_loteria');
        $descripcion = get_field('descripcion_personalizada');
        $numeros_premiados = get_field('numeros_premiados');
        
        // Obtener el ID de la rifa desde la base de datos
        global $wpdb;
        $post_id = get_the_ID();
        $tabla_rifas = $wpdb->prefix . 'rifas';
        $rifa_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_rifas WHERE post_id = %d",
            $post_id
        ));

        // Si no existe, crear el registro
        if (!$rifa_id && function_exists('get_field')) {
            // Convertir cantidad_cifras a número
            $cifras = ($cantidad_cifras == '3') ? 3 : 4;
            
            $wpdb->insert(
                $tabla_rifas,
                array(
                    'post_id' => $post_id,
                    'cifras' => $cifras,
                    'precio_boleta' => $valor_numero,
                    'fecha_sorteo' => current_time('mysql'),
                    'loteria' => $nombre_loteria
                )
            );
            $rifa_id = $wpdb->insert_id;
        }

        // Calcular total de boletas basado en cifras
        $cifras = ($cantidad_cifras == '3') ? 3 : 4;
        $total_boletas = pow(10, $cifras); // 1000 para 3 cifras, 10000 para 4 cifras
        
        // Obtener boletas vendidas de la base de datos
        $tabla_boletas = $wpdb->prefix . 'boletas_vendidas';
        $boletas_vendidas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_boletas WHERE rifa_id = %d AND estado != 'cancelado'",
            $rifa_id
        )) ?: 0;
        
        // Calcular boletas disponibles
        $boletas_disponibles = $total_boletas - $boletas_vendidas;
        
        // Calcular porcentaje de boletas vendidas
        $porcentaje_vendido = ($boletas_vendidas > 0 && $total_boletas > 0) ? 
                             ($boletas_vendidas / $total_boletas) * 100 : 0;
        
        // Formatear porcentaje con dos decimales
        $porcentaje_formateado = number_format($porcentaje_vendido, 2);
        
        // Calcular precios para diferentes cantidades de números
        $precio_2_numeros = $valor_numero * 2;
        $precio_5_numeros = $valor_numero * 5;
        $precio_10_numeros = $valor_numero * 10;
        ?>
        
        <!-- Contenedor principal que envuelve ambas secciones -->
        <div class="rifa-wrapper">
            <div class="rifa-container" data-rifa-id="<?php echo esc_attr($rifa_id); ?>">
                <div class="rifa-main">
                    <!-- Columna izquierda - Imagen -->
                    <div class="rifa-image">
                        <?php if ($imagen_premio): ?>
                            <?php if (is_array($imagen_premio)): ?>
                                <img src="<?php echo esc_url($imagen_premio['url']); ?>" alt="<?php echo esc_attr($nombre_premio); ?>">
                            <?php else: ?>
                                <img src="<?php echo esc_url($imagen_premio); ?>" alt="<?php echo esc_attr($nombre_premio); ?>">
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Columna derecha - Información -->
                    <div class="rifa-details">
                        <h1 class="rifa-title"><?php echo esc_html($nombre_premio); ?></h1>
                        
                        <!-- Barra de progreso -->
                        <div class="rifa-progress">
                            <p><?php echo esc_html($porcentaje_formateado); ?>% Meta alcanzada</p>
                            <div class="progress-bar">
                                <div class="progress" style="width: <?php echo esc_attr($porcentaje_formateado); ?>%;"></div>
                            </div>
                        </div>
                        
                        <!-- Valor de cada número -->
                        <p class="rifa-price-title">VALOR DE CADA NÚMERO</p>
                        <p class="rifa-price" data-precio="<?php echo esc_attr($valor_numero); ?>">$<?php echo number_format($valor_numero, 0, '.', ','); ?> PESOS</p>
                        <p class="rifa-price-description">Boletas de <?php echo $cantidad_cifras == '3' ? 'tres' : 'cuatro'; ?> cifras.</p>
                        
                        <!-- Botón participar -->
                        <a href="#comprar-numeros" class="rifa-button">PARTICIPAR</a>
                    </div>
                </div>
                
                <!-- Información adicional -->
                <div class="rifa-extra">
                    <p>Este sorteo jugará con la lotería de <?php echo esc_html($nombre_loteria); ?> cuando se venda el 100% de la boletería</p>
                    
                    <?php if (!empty($descripcion)): ?>
                        <div>
                            <?php echo wp_kses_post($descripcion); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($numeros_premiados): ?>
                        <div class="numeros-premiados-container">
                            <div class="icono-premiados">
                                <i class="fas fa-hand-point-right" style="color: #333;"></i>
                            </div>
                            <div class="texto-premiados">
                                <?php echo esc_html($numeros_premiados); ?>
                            </div>
                        </div>
                    <?php endif; ?>                
                </div>
            </div>
            
            <!-- Nueva sección para comprar números aleatorios (dentro del mismo wrapper) -->
             
            <div id="comprar-numeros" class="numeros-section">
                <h2 class="numeros-title">Genera tus números aleatorios</h2>
                <div class="numeros-divider"></div>
                
                <div class="numeros-options">
                    <!-- Opción 1: 2 números -->
                    <div class="numero-option">
                        <div class="numero-multiplier">x2</div>
                        <div class="numero-price">$<?php echo number_format($precio_2_numeros, 0, '.', ','); ?></div>
                        <div class="numero-currency">Pesos colombianos</div>
                        <button class="btn-comprar" data-cantidad="2">COMPRAR</button>
                    </div>
                    
                    <!-- Opción 2: 5 números -->
                    <div class="numero-option">
                        <div class="numero-multiplier">x5</div>
                        <div class="numero-price">$<?php echo number_format($precio_5_numeros, 0, '.', ','); ?></div>
                        <div class="numero-currency">Pesos colombianos</div>
                        <button class="btn-comprar" data-cantidad="5">COMPRAR</button>
                    </div>
                    
                    <!-- Opción 3: 10 números -->
                    <div class="numero-option">
                        <div class="numero-multiplier">x10</div>
                        <div class="numero-price">$<?php echo number_format($precio_10_numeros, 0, '.', ','); ?></div>
                        <div class="numero-currency">Pesos colombianos</div>
                        <button class="btn-comprar" data-cantidad="10">COMPRAR</button>
                    </div>
                </div>
                
                <!-- Selector de cantidad personalizada -->
                <div class="selector-section">
                    <label class="selector-label">
                        <i class="fas fa-check-circle" style="margin-right: 5px;"></i> 
                        Seleccione la cantidad de números:
                    </label>
                    <input type="number" id="cantidad-numero" class="selector-input" min="1" max="<?php echo esc_attr($boletas_disponibles); ?>" value="1">
                    <button class="btn-pagar">PAGAR</button>
                </div>
                
                <!-- Campos ocultos para ePayco y datos para JavaScript -->
                <input type="hidden" id="rifa_id" value="<?php echo esc_attr($rifa_id); ?>">
                <input type="hidden" id="precio_unitario" value="<?php echo esc_attr($valor_numero); ?>">
                <input type="hidden" id="total_boletas" value="<?php echo esc_attr($total_boletas); ?>">
                <input type="hidden" id="boletas_vendidas" value="<?php echo esc_attr($boletas_vendidas); ?>">
                <input type="hidden" id="boletas_disponibles" value="<?php echo esc_attr($boletas_disponibles); ?>">
            </div>
        </div><!-- Fin del wrapper -->
        
        <?php
    endwhile;
endif;

get_footer(); // Carga el pie de página del tema
?>