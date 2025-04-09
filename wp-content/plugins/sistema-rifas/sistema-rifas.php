<?php
/**
 * Plugin Name: Sistema de Rifas
 * Description: Sistema para gestionar rifas y boletas
 * Version: 1.0
 * Author: Tu Nombre
 */

// Evitar acceso directo
if (!defined('ABSPATH')) exit;

// Crear tablas al activar el plugin
register_activation_hook(__FILE__, 'sr_crear_tablas');

// Actualizar la estructura de la base de datos si es necesario
function sr_actualizar_db_check() {
    $db_version = get_option('sr_db_version', '1.0');
    
    if (version_compare($db_version, '1.1', '<')) {
        global $wpdb;
        $tabla_rifas = $wpdb->prefix . 'rifas';
        
        // Verificar si la columna ya existe
        $columna_existe = $wpdb->get_results("SHOW COLUMNS FROM $tabla_rifas LIKE 'numeros_vendidos'");
        
        if (empty($columna_existe)) {
            $wpdb->query("ALTER TABLE $tabla_rifas ADD COLUMN numeros_vendidos int(10) NOT NULL DEFAULT 0");
        }
        
        update_option('sr_db_version', '1.1');
    }
}
add_action('plugins_loaded', 'sr_actualizar_db_check');

function sr_crear_tablas() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Tabla para rifas
    $tabla_rifas = $wpdb->prefix . 'rifas';
    $sql_rifas = "CREATE TABLE $tabla_rifas (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    cifras int(2) NOT NULL DEFAULT 3,
    precio_boleta decimal(10,2) NOT NULL DEFAULT 0,
    fecha_sorteo datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    loteria varchar(100) DEFAULT '' NOT NULL,
    numeros_vendidos int(10) NOT NULL DEFAULT 0,
    PRIMARY KEY  (id),
    KEY post_id (post_id)
    ) $charset_collate;";
    
    // Tabla para boletas vendidas
    $tabla_boletas = $wpdb->prefix . 'boletas_vendidas';
    $sql_boletas = "CREATE TABLE $tabla_boletas (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    rifa_id mediumint(9) NOT NULL,
    numero_boleta varchar(10) NOT NULL,
    cliente_nombre varchar(100) NOT NULL,
    cliente_apellido varchar(100) NOT NULL,
    cliente_telefono varchar(20) NOT NULL,
    cliente_email varchar(100) NOT NULL,
    fecha_compra datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    estado varchar(20) DEFAULT 'pendiente' NOT NULL,
    transaccion_id varchar(100) DEFAULT '' NOT NULL,
    PRIMARY KEY  (id),
    KEY rifa_id (rifa_id),
    UNIQUE KEY rifa_numero (rifa_id, numero_boleta)
    ) $charset_collate;";
    
    // Tabla para compras de rifas
    $tabla_compras = $wpdb->prefix . 'sistema_rifas_compras';
    $sql_compras = "CREATE TABLE $tabla_compras (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    rifa_id mediumint(9) NOT NULL,
    email_cliente varchar(100) NOT NULL,
    cantidad_numeros int(5) NOT NULL DEFAULT 1,
    monto_pagado decimal(10,2) NOT NULL DEFAULT 0,
    referencia_pago varchar(100) NOT NULL,
    fecha_compra datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY  (id),
    KEY rifa_id (rifa_id)
    ) $charset_collate;";

    // Añadir la tabla sistema_rifas_tickets
    $tabla_tickets = $wpdb->prefix . 'sistema_rifas_tickets';
    $sql_tickets = "CREATE TABLE $tabla_tickets (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        rifa_id bigint(20) NOT NULL,
        numero varchar(10) NOT NULL,
        nombre_cliente varchar(100) NOT NULL,
        apellido_cliente varchar(100) NOT NULL,
        telefono_cliente varchar(20) NOT NULL,
        email_cliente varchar(100) NOT NULL,
        fecha_compra datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        estado varchar(20) DEFAULT 'pendiente' NOT NULL,
        transaccion_id varchar(100) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id),
        KEY rifa_id (rifa_id),
        UNIQUE KEY rifa_numero (rifa_id, numero)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_rifas);
    dbDelta($sql_boletas);
    dbDelta($sql_compras);
    dbDelta($sql_tickets); // Añadir esta línea
}

// Funciones para calcular totales y porcentajes
function sr_calcular_total_boletas($cifras) {
    return pow(10, $cifras);
}

function sr_obtener_boletas_vendidas($rifa_id) {
    global $wpdb;
    $tabla_boletas = $wpdb->prefix . 'boletas_vendidas';
    
    return $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_boletas WHERE rifa_id = %d AND estado != 'cancelado'",
    $rifa_id
    ));
}

function sr_calcular_porcentaje_vendido($rifa_id) {
    global $wpdb;
    $tabla_rifas = $wpdb->prefix . 'rifas';
    
    // Obtener información de la rifa
    $rifa = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $tabla_rifas WHERE id = %d",
    $rifa_id
    ));
    
    if (!$rifa) return 0;
    
    $total_boletas = sr_calcular_total_boletas($rifa->cifras);
    $boletas_vendidas = sr_obtener_boletas_vendidas($rifa_id);
    
    if ($total_boletas == 0) return 0;
    
    return round(($boletas_vendidas / $total_boletas) * 100, 2);
}

// Guardar datos de la rifa cuando se actualiza un post
function sr_guardar_datos_rifa($post_id) {
    // Verificar si es el tipo de post correcto
    if (get_post_type($post_id) !== 'rifas') return;
    
    // Verificar si existen los campos ACF necesarios
    if (!function_exists('get_field')) return;
    
    $cifras = get_field('cifras_boleta', $post_id);
    $precio = get_field('precio_boleta', $post_id);
    $fecha_sorteo = get_field('fecha_sorteo', $post_id);
    $loteria = get_field('loteria', $post_id);
    
    global $wpdb;
    $tabla_rifas = $wpdb->prefix . 'rifas';
    
    // Verificar si ya existe un registro para este post
    $rifa_id = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM $tabla_rifas WHERE post_id = %d",
    $post_id
    ));
    
    if ($rifa_id) {
    // Actualizar registro existente
    $wpdb->update(
    $tabla_rifas,
    array(
    'cifras' => $cifras,
    'precio_boleta' => $precio,
    'fecha_sorteo' => $fecha_sorteo,
    'loteria' => $loteria
    ),
    array('post_id' => $post_id)
    );
    } else {
    // Crear nuevo registro
    $wpdb->insert(
    $tabla_rifas,
    array(
    'post_id' => $post_id,
    'cifras' => $cifras,
    'precio_boleta' => $precio,
    'fecha_sorteo' => $fecha_sorteo,
    'loteria' => $loteria
    )
    );
    }
}
add_action('acf/save_post', 'sr_guardar_datos_rifa', 20);

// Registrar tipo de post personalizado para rifas
function sr_registrar_post_type_rifas() {
    register_post_type('rifas', array(
    'labels' => array(
    'name' => 'Rifas',
    'singular_name' => 'Rifa',
    'add_new' => 'Añadir Nueva',
    'add_new_item' => 'Añadir Nueva Rifa',
    'edit_item' => 'Editar Rifa',
    'new_item' => 'Nueva Rifa',
    'view_item' => 'Ver Rifa',
    'search_items' => 'Buscar Rifas',
    'not_found' => 'No se encontraron rifas',
    'not_found_in_trash' => 'No se encontraron rifas en la papelera'
    ),
    'public' => true,
    'has_archive' => true,
    'menu_icon' => 'dashicons-tickets-alt',
    'supports' => array('title', 'editor', 'thumbnail'),
    'rewrite' => array('slug' => 'rifas')
    ));
}
add_action('init', 'sr_registrar_post_type_rifas');

// Añadir scripts y estilos
function sistema_rifas_enqueue_scripts() {
    // Ruta base del tema
    $theme_url = get_template_directory_uri();
    
    // Estilos del tema
    wp_enqueue_style('sistema-rifas-style', $theme_url . '/style.css', array(), '1.0.0');
    
    // Scripts principales (en la raíz del tema)
    wp_enqueue_script('sistema-rifas-core', $theme_url . '/rifa-core.js', array('jquery'), '1.0.0', true);
    
    // Obtener configuración de ePayco
    $epayco_key = get_option('sr_epayco_key', '');
    $epayco_test = get_option('sr_epayco_test', '1');
    
    // Pasar variables globales al script principal
    wp_localize_script('sistema-rifas-core', 'sistema_rifas_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sistema_rifas_nonce'),
        'checkout_url' => site_url('/checkout/'),
        'respuesta_url' => site_url('/respuesta-pago/'),
        'epayco_key' => $epayco_key,
        'epayco_test' => $epayco_test
    ));
    
    // Scripts específicos para la página de rifas
    if (is_singular('rifas')) {
        $post_id = get_the_ID();
        
        // Obtener la cantidad de cifras de la rifa
        $cifras = get_field('cantidad_de_cifras_3_o_4', $post_id);
        $cifras = intval($cifras) ?: 3; // Por defecto 3 cifras
        
        // Calcular el total de boletas basado en las cifras
        $total_tickets = pow(10, $cifras); // 10^cifras (ej: 10^3 = 1000 boletas para 3 cifras)
        
        // Obtener los números vendidos de la base de datos
        global $wpdb;
        $table_name = $wpdb->prefix . 'sistema_rifas_tickets';
        $sold_tickets = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE rifa_id = %d AND estado = 'vendido'",
            $post_id
        )) ?: 0;
        
        // Enqueue el script de validación
        wp_enqueue_script('rifa-validation', plugin_dir_url(__FILE__) . 'rifa-validation.js', array('jquery'), '1.0.0', true);
        
        // Pasar datos de boletas al script de validación
        wp_localize_script('rifa-validation', 'rifaData', array(
            'totalTickets' => $total_tickets,
            'soldTickets' => intval($sold_tickets),
            'availableTickets' => $total_tickets - intval($sold_tickets),
            'rifaId' => $post_id
        ));
        
        // Otros scripts específicos para rifas
        wp_enqueue_script('sistema-rifas-single', $theme_url . '/rifa-single.js', array('sistema-rifas-core'), '1.0.0', true);
    }
    
    // Script para la página de checkout
    if (is_page('checkout')) {
        wp_enqueue_script('sistema-rifas-checkout', $theme_url . '/rifa-checkout.js', array('sistema-rifas-core'), '1.0.0', true);
    }
    
    // Script de ePayco (solo en checkout o respuesta de pago)
    if (is_page('checkout') || is_page('respuesta-pago')) {
        wp_enqueue_script('epayco-checkout', 'https://checkout.epayco.co/checkout.js', array(), null, true);
        wp_enqueue_script('sistema-rifas-payment', $theme_url . '/rifa-payment.js', array('sistema-rifas-core', 'epayco-checkout'), '1.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'sistema_rifas_enqueue_scripts');

// Funciones AJAX para manejar la compra de números
function sr_ajax_reservar_numeros() {
    // Verificar nonce para seguridad
    check_ajax_referer('sistema_rifas_nonce', 'nonce');
    
    $rifa_id = isset($_POST['rifa_id']) ? intval($_POST['rifa_id']) : 0;
    $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;
    
    // Validar datos
    if ($rifa_id <= 0 || $cantidad <= 0) {
        wp_send_json_error(array('mensaje' => 'Datos inválidos'));
        return;
    }
    
    // Verificar disponibilidad
    $total_boletas = sr_calcular_total_boletas(get_post_meta($rifa_id, 'cifras', true));
    $boletas_vendidas = sr_obtener_boletas_vendidas($rifa_id);
    
    if ($boletas_vendidas + $cantidad > $total_boletas) {
        wp_send_json_error(array('mensaje' => 'No hay suficientes boletas disponibles'));
        return;
    }
    
    // Datos del cliente (en este punto solo reservamos, los datos completos se enviarán en el checkout)
    $datos_cliente = array(
        'nombre' => 'Reserva Temporal',
        'email' => 'reserva@temporal.com',
        'telefono' => '0000000000'
    );
    
    // Reservar números
    $resultado = sr_reservar_numeros_aleatorios($rifa_id, $cantidad, $datos_cliente);
    
    if (is_wp_error($resultado)) {
        wp_send_json_error(array('mensaje' => $resultado->get_error_message()));
        return;
    }
    
    // Guardar la transacción en la sesión para usarla en el checkout
    WC()->session->set('sr_transaccion_id', $resultado['transaccion_id']);
    WC()->session->set('sr_numeros_reservados', $resultado['numeros']);
    WC()->session->set('sr_rifa_id', $rifa_id);
    WC()->session->set('sr_cantidad', $cantidad);
    
    wp_send_json_success(array(
        'transaccion_id' => $resultado['transaccion_id'],
        'numeros' => $resultado['numeros'],
        'redirect' => wc_get_checkout_url()
    ));
}
add_action('wp_ajax_sr_reservar_numeros', 'sr_ajax_reservar_numeros');
add_action('wp_ajax_nopriv_sr_reservar_numeros', 'sr_ajax_reservar_numeros');

// Función AJAX para actualizar datos del cliente
function sr_ajax_actualizar_datos_cliente() {
    // Verificar nonce para seguridad
    check_ajax_referer('sistema_rifas_nonce', 'nonce');
    
    $transaccion_id = isset($_POST['transaccion_id']) ? sanitize_text_field($_POST['transaccion_id']) : '';
    $nombre = isset($_POST['nombre']) ? sanitize_text_field($_POST['nombre']) : '';
    $apellido = isset($_POST['apellido']) ? sanitize_text_field($_POST['apellido']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $telefono = isset($_POST['telefono']) ? sanitize_text_field($_POST['telefono']) : '';
    
    // Validar datos
    if (empty($transaccion_id) || empty($nombre) || empty($email) || empty($telefono)) {
        wp_send_json_error(array('mensaje' => 'Datos incompletos'));
        return;
    }
    
    // Actualizar datos del cliente en la base de datos
    global $wpdb;
    $tabla_boletas = $wpdb->prefix . 'boletas_vendidas';
    
    $resultado = $wpdb->update(
        $tabla_boletas,
        array(
            'cliente_nombre' => $nombre,
            'cliente_apellido' => $apellido,
            'cliente_email' => $email,
            'cliente_telefono' => $telefono
        ),
        array(
            'transaccion_id' => $transaccion_id,
            'estado' => 'reservado'
        )
    );
    
    if ($resultado === false) {
        wp_send_json_error(array('mensaje' => 'Error al actualizar los datos del cliente'));
        return;
    }
    
    wp_send_json_success();
}
add_action('wp_ajax_sr_actualizar_datos_cliente', 'sr_ajax_actualizar_datos_cliente');
add_action('wp_ajax_nopriv_sr_actualizar_datos_cliente', 'sr_ajax_actualizar_datos_cliente');

// Registrar endpoints API para ePayco
function registrar_endpoints_api() {
    register_rest_route('sistema-rifas/v1', '/epayco-callback', array(
        'methods' => 'POST',
        'callback' => 'procesar_callback_epayco',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'registrar_endpoints_api');

function procesar_callback_epayco($request) {
    $params = $request->get_params();
    
    // Verificar la firma para asegurar que la solicitud viene de ePayco
    $p_cust_id_cliente = 'tu_customer_id'; // Reemplazar con tu ID de cliente
    $p_key = 'tu_p_key'; // Reemplazar con tu P_KEY
    
    $x_ref_payco = $params['x_ref_payco'];
    $x_transaction_id = $params['x_transaction_id'];
    $x_amount = $params['x_amount'];
    $x_currency_code = $params['x_currency_code'];
    $x_signature = $params['x_signature'];
    
    $signature = hash('sha256', $p_cust_id_cliente . '^' . $p_key . '^' . $x_ref_payco . '^' . $x_transaction_id . '^' . $x_amount . '^' . $x_currency_code);
    
    // Verificar que la firma coincida
    if ($signature == $x_signature) {
        // Obtener datos adicionales
        $rifa_id = $params['x_extra1'];
        $cantidad_numeros = $params['x_extra2'];
        $estado_transaccion = $params['x_transaction_state'];
        $referencia = $params['x_id_invoice'];
        $email_cliente = $params['x_customer_email'];
        
        // Procesar según el estado de la transacción
        if ($estado_transaccion == 'Aceptada') {
            // Registrar la compra en la base de datos
            registrar_compra_rifa($rifa_id, $email_cliente, $cantidad_numeros, $x_amount, $referencia);
            
            // Responder con éxito
            return new WP_REST_Response(array('status' => 'success'), 200);
        }
    }
    
    // Si algo falla, responder con error
    return new WP_REST_Response(array('status' => 'error'), 400);
}

function registrar_compra_rifa($rifa_id, $email_cliente, $cantidad_numeros, $monto, $referencia) {
    global $wpdb;
    $tabla_compras = $wpdb->prefix . 'sistema_rifas_compras';
    
    // Insertar registro de compra
    $wpdb->insert(
        $tabla_compras,
        array(
            'rifa_id' => $rifa_id,
            'email_cliente' => $email_cliente,
            'cantidad_numeros' => $cantidad_numeros,
            'monto_pagado' => $monto,
            'referencia_pago' => $referencia,
            'fecha_compra' => current_time('mysql')
        )
    );
    
    // Actualizar la tabla de boletas vendidas
    // Nota: Esto es una simplificación. En un sistema real, deberías generar números aleatorios
    // y registrarlos en la tabla de boletas_vendidas
    $tabla_boletas = $wpdb->prefix . 'boletas_vendidas';
    for ($i = 0; $i < $cantidad_numeros; $i++) {
        // Generar un número aleatorio que no esté ya vendido
        $numero_aleatorio = '';
        $existe = true;
        
        // Obtener información de la rifa para saber cuántas cifras usar
        $tabla_rifas = $wpdb->prefix . 'rifas';
        $cifras = $wpdb->get_var($wpdb->prepare(
            "SELECT cifras FROM $tabla_rifas WHERE id = %d",
            $rifa_id
        ));
        
        if (!$cifras) $cifras = 3; // Valor por defecto
        
        // Intentar hasta 10 veces encontrar un número no vendido
        for ($intento = 0; $intento < 10 && $existe; $intento++) {
            $numero_aleatorio = str_pad(rand(0, pow(10, $cifras) - 1), $cifras, '0', STR_PAD_LEFT);
            
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_boletas WHERE rifa_id = %d AND numero_boleta = %s",
                $rifa_id,
                $numero_aleatorio
            ));
        }
        
        // Si después de 10 intentos no encontramos un número disponible, saltamos esta iteración
        if ($existe) continue;
        
        // Registrar el número vendido
        $wpdb->insert(
            $tabla_boletas,
            array(
                'rifa_id' => $rifa_id,
                'numero_boleta' => $numero_aleatorio,
                'cliente_nombre' => 'Compra Online',
                'cliente_apellido' => '',
                'cliente_telefono' => '',
                'cliente_email' => $email_cliente,
                'fecha_compra' => current_time('mysql'),
                'estado' => 'pagado',
                'transaccion_id' => $referencia
            )
        );
    }
}


// Añadir página de configuración para ePayco
function sr_add_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=rifas',
        'Configuración de ePayco',
        'Configuración ePayco',
        'manage_options',
        'sistema_rifas_epayco',
        'sr_epayco_settings_page'
    );
}
add_action('admin_menu', 'sr_add_admin_menu');

// Registrar opciones
function sr_register_settings() {
    // Primer conjunto de llaves
    register_setting('sr_epayco_settings', 'sr_epayco_p_cust_id');
    register_setting('sr_epayco_settings', 'sr_epayco_p_key');
    
    // Segundo conjunto de llaves (API Rest)
    register_setting('sr_epayco_settings', 'sr_epayco_public_key');
    register_setting('sr_epayco_settings', 'sr_epayco_private_key');
    
    // Modo de prueba
    register_setting('sr_epayco_settings', 'sr_epayco_test', array(
        'type' => 'boolean',
        'default' => true
    ));
}
add_action('admin_init', 'sr_register_settings');

// Página de configuración
function sr_epayco_settings_page() {
    ?>
    <div class="wrap">
        <h1>Configuración de ePayco</h1>
        <form method="post" action="options.php">
            <?php settings_fields('sr_epayco_settings'); ?>
            <?php do_settings_sections('sr_epayco_settings'); ?>
            
            <h2>Llaves para Checkout Estándar</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">P_CUST_ID_CLIENTE</th>
                    <td>
                        <input type="text" name="sr_epayco_p_cust_id" value="<?php echo esc_attr(get_option('sr_epayco_p_cust_id', '69101')); ?>" class="regular-text" />
                        <p class="description">ID de cliente proporcionado por ePayco (P_CUST_ID_CLIENTE)</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">P_KEY</th>
                    <td>
                        <input type="text" name="sr_epayco_p_key" value="<?php echo esc_attr(get_option('sr_epayco_p_key', 'db5574778fed7e86b00279deb036d43a91459bab')); ?>" class="regular-text" />
                        <p class="description">Llave privada proporcionada por ePayco (P_KEY)</p>
                    </td>
                </tr>
            </table>
            
            <h2>Llaves para API Rest, Onpage Checkout</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">PUBLIC_KEY</th>
                    <td>
                        <input type="text" name="sr_epayco_public_key" value="<?php echo esc_attr(get_option('sr_epayco_public_key', '10451f8a790dc7e7a48c0822be14f832')); ?>" class="regular-text" />
                        <p class="description">Llave pública para API Rest (PUBLIC_KEY)</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">PRIVATE_KEY</th>
                    <td>
                        <input type="text" name="sr_epayco_private_key" value="<?php echo esc_attr(get_option('sr_epayco_private_key', 'c717bb838afe910beb13bf0dcbdfa920')); ?>" class="regular-text" />
                        <p class="description">Llave privada para API Rest (PRIVATE_KEY)</p>
                    </td>
                </tr>
            </table>
            
            <h2>Configuración General</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Modo de Prueba</th>
                    <td>
                        <label>
                            <input type="checkbox" name="sr_epayco_test" value="1" <?php checked(get_option('sr_epayco_test', true)); ?> />
                            Activar modo de prueba
                        </label>
                        <p class="description">Marca esta casilla para usar el entorno de pruebas de ePayco. Desmárcala para transacciones reales.</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Incluir el archivo de funciones de administración
include_once(plugin_dir_path(__FILE__) . 'admin-rifas.php');

// Incluir el archivo de shortcodes
include_once(plugin_dir_path(__FILE__) . 'shortcodes.php');

// Incluir el archivo de funciones para números aleatorios
include_once(plugin_dir_path(__FILE__) . 'numeros-aleatorios.php');