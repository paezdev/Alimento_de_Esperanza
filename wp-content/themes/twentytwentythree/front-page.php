<?php
// Redirige automáticamente a la rifa más reciente
$args = array(
    'post_type' => 'rifas',
    'posts_per_page' => 1,
    'orderby' => 'date',
    'order' => 'DESC',
);
$query = new WP_Query($args);

if ($query->have_posts()) :
    $query->the_post();
    wp_redirect(get_permalink()); // Redirige a la URL de la rifa más reciente
    exit;
else :
    get_header();
    echo '<p>No hay rifas disponibles en este momento.</p>';
    get_footer();
endif;
?>