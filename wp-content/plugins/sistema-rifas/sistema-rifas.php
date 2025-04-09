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
    dbDelta($sql_tickets);
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
}
add_action('wp_enqueue_scripts', 'sistema_rifas_enqueue_scripts');

// Incluir el archivo de funciones de administración
include_once(plugin_dir_path(__FILE__) . 'admin-rifas.php');

// Incluir el archivo de shortcodes
include_once(plugin_dir_path(__FILE__) . 'shortcodes.php');

// Incluir el archivo de funciones para números aleatorios
include_once(plugin_dir_path(__FILE__) . 'numeros-aleatorios.php');