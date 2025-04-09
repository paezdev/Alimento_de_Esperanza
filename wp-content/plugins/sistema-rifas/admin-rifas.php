<?php
// Evitar acceso directo
if (!defined('ABSPATH')) exit;

/**
 * Agrega página de administración para rifas
 */
function sr_agregar_menu_admin() {
    add_menu_page(
        'Gestión de Rifas',
        'Rifas',
        'manage_options',
        'gestion-rifas',
        'sr_pagina_admin_rifas',
        'dashicons-tickets-alt',
        30
    );
    
    add_submenu_page(
        'gestion-rifas',
        'Boletas Vendidas',
        'Boletas Vendidas',
        'manage_options',
        'boletas-vendidas',
        'sr_pagina_admin_boletas'
    );
}
add_action('admin_menu', 'sr_agregar_menu_admin');

/**
 * Contenido de la página de administración de rifas
 */
function sr_pagina_admin_rifas() {
    global $wpdb;
    $tabla_rifas = $wpdb->prefix . 'rifas';
    
    $rifas = $wpdb->get_results("SELECT r.*, p.post_title 
                                FROM $tabla_rifas r 
                                JOIN {$wpdb->posts} p ON r.post_id = p.ID 
                                WHERE p.post_status = 'publish'
                                ORDER BY r.id DESC");
    ?>
    <div class="wrap">
        <h1>Gestión de Rifas</h1>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Premio</th>
                    <th>Cifras</th>
                    <th>Precio</th>
                    <th>Fecha Sorteo</th>
                    <th>Boletas Vendidas</th>
                    <th>Porcentaje</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rifas): ?>
                    <?php foreach ($rifas as $rifa): ?>
                        <?php 
                        $total_boletas = sr_calcular_total_boletas($rifa->cifras);
                        $boletas_vendidas = sr_obtener_boletas_vendidas($rifa->id);
                        $porcentaje = sr_calcular_porcentaje_vendido($rifa->id);
                        ?>
                        <tr>
                            <td><?php echo $rifa->id; ?></td>
                            <td><a href="<?php echo get_permalink($rifa->post_id); ?>" target="_blank"><?php echo $rifa->post_title; ?></a></td>
                            <td><?php echo $rifa->cifras; ?></td>
                            <td>$<?php echo number_format($rifa->precio_boleta, 0, '.', ','); ?></td>
                            <td><?php echo date_i18n('j F, Y', strtotime($rifa->fecha_sorteo)); ?></td>
                            <td><?php echo $boletas_vendidas; ?> / <?php echo $total_boletas; ?></td>
                            <td>
                                <div class="progress-bar-admin">
                                    <div class="progress-admin" style="width: <?php echo $porcentaje; ?>%"></div>
                                </div>
                                <?php echo $porcentaje; ?>%
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=boletas-vendidas&rifa_id=' . $rifa->id); ?>" class="button">Ver Boletas</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No hay rifas disponibles.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <style>
    .progress-bar-admin {
        background-color: #ddd;
        height: 10px;
        width: 100%;
        border-radius: 5px;
        margin-bottom: 5px;
        overflow: hidden;
    }
    .progress-admin {
        background-color: #4caf50;
        height: 100%;
    }
    </style>
    <?php
}

/**
 * Contenido de la página de administración de boletas
 */
function sr_pagina_admin_boletas() {
    global $wpdb;
    $tabla_boletas = $wpdb->prefix . 'boletas_vendidas';
    $tabla_rifas = $wpdb->prefix . 'rifas';
    
    $rifa_id = isset($_GET['rifa_id']) ? intval($_GET['rifa_id']) : 0;
    
    // Si hay un ID de rifa, mostrar solo esas boletas
    if ($rifa_id > 0) {
        $rifa = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, p.post_title 
            FROM $tabla_rifas r 
            JOIN {$wpdb->posts} p ON r.post_id = p.ID 
            WHERE r.id = %d",
            $rifa_id
        ));
        
        $boletas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_boletas WHERE rifa_id = %d ORDER BY id DESC",
            $rifa_id
        ));
        
        $titulo = 'Boletas Vendidas: ' . $rifa->post_title;
    } else {
        $boletas = $wpdb->get_results(
            "SELECT b.*, r.post_id, p.post_title 
            FROM $tabla_boletas b
            JOIN $tabla_rifas r ON b.rifa_id = r.id
            JOIN {$wpdb->posts} p ON r.post_id = p.ID
            ORDER BY b.id DESC
            LIMIT 100"
        );
        
        $titulo = 'Todas las Boletas Vendidas (últimas 100)';
    }
    ?>
    <div class="wrap">
        <h1><?php echo $titulo; ?></h1>
        
        <?php if ($rifa_id > 0): ?>
            <a href="<?php echo admin_url('admin.php?page=gestion-rifas'); ?>" class="button">← Volver a Rifas</a>
            <p>
                <strong>Cifras:</strong> <?php echo $rifa->cifras; ?> |
                <strong>Precio:</strong> $<?php echo number_format($rifa->precio_boleta, 0, '.', ','); ?> |
                <strong>Fecha Sorteo:</strong> <?php echo date_i18n('j F, Y', strtotime($rifa->fecha_sorteo)); ?>
            </p>
        <?php endif; ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <?php if ($rifa_id == 0): ?>
                        <th>Rifa</th>
                    <?php endif; ?>
                    <th>Número</th>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Fecha Compra</th>
                    <th>Estado</th>
                    <th>Transacción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($boletas): ?>
                    <?php foreach ($boletas as $boleta): ?>
                        <tr>
                            <td><?php echo $boleta->id; ?></td>
                            <?php if ($rifa_id == 0): ?>
                                <td><a href="<?php echo admin_url('admin.php?page=boletas-vendidas&rifa_id=' . $boleta->rifa_id); ?>"><?php echo $boleta->post_title; ?></a></td>
                            <?php endif; ?>
                            <td><strong><?php echo $boleta->numero_boleta; ?></strong></td>
                            <td><?php echo $boleta->cliente_nombre . ' ' . $boleta->cliente_apellido; ?></td>
                            <td><?php echo $boleta->cliente_telefono; ?></td>
                            <td><?php echo $boleta->cliente_email; ?></td>
                            <td><?php echo date_i18n('j M, Y H:i', strtotime($boleta->fecha_compra)); ?></td>
                            <td>
                                <span class="estado-<?php echo $boleta->estado; ?>">
                                    <?php echo ucfirst($boleta->estado); ?>
                                </span>
                            </td>
                            <td><?php echo $boleta->transaccion_id; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo ($rifa_id == 0) ? 9 : 8; ?>">No hay boletas vendidas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <style>
    .estado-pendiente {
        background-color: #ffeb3b;
        color: #333;
        padding: 3px 8px;
        border-radius: 3px;
    }
    .estado-pagado {
        background-color: #4caf50;
        color: white;
        padding: 3px 8px;
        border-radius: 3px;
    }
    .estado-cancelado {
        background-color: #f44336;
        color: white;
        padding: 3px 8px;
        border-radius: 3px;
    }
    </style>
    <?php
}