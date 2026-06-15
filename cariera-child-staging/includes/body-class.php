<?php
/**
 * Body class — classes CSS sur <body> + sticky header init
 */

add_filter('body_class', 'jobiizy_mobile_body_class');
function jobiizy_mobile_body_class($classes) {
    if (wp_is_mobile()) {
        $classes[] = 'jobiizy-mobile-device';
    }
    return $classes;
}

add_filter('body_class', 'jobiizy_emplois_body_class');
function jobiizy_emplois_body_class($classes) {
    if (is_page(JOBIIZY_JOBS_PAGE_ID)) {
        $classes[] = 'jobiizy-emplois-page';
    }
    return $classes;
}

add_action('init', 'jobiizy_customize_sticky_header');
function jobiizy_customize_sticky_header() {
    if (function_exists('cariera_get_option')) {
        add_filter('cariera_sticky_mobile_header', '__return_true');
    }
}

// ────────────────────────────────────────────────────────────────────────
// 2. À AJOUTER dans includes/body-class.php
//    Ajoute la body class pour le reset CSS Cariera
// ────────────────────────────────────────────────────────────────────────
add_filter('body_class', 'jobiizy_offre_globale_body_class');
function jobiizy_offre_globale_body_class($classes) {
    if (is_page_template('templates/page-offre-globale.php')) {
        $classes[] = 'jobiizy-offre-globale-page';
    }
    return $classes;
}
 