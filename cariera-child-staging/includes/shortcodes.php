<?php
/**
 * Shortcodes Jobiizy
 */

add_shortcode('jobiizy_cv_form', function($atts = []) {
    $atts = shortcode_atts([
        'job_id' => get_the_ID(),
        'title'  => 'Envoyer votre CV',
    ], $atts, 'jobiizy_cv_form');

    $job_id = absint($atts['job_id']);
    if (!$job_id) return '';

    ob_start();
    $template = get_stylesheet_directory() . '/assets/templates/cv-form.php';
    if (file_exists($template)) {
        include $template;
    } else {
        echo '<p style="color:red">⚠️ Fichier cv-form.php manquant dans /assets/templates/</p>';
    }
    return ob_get_clean();
});

add_shortcode('jobiizy_mobile_header', function() {
    ob_start();
    include get_stylesheet_directory() . '/templates/header-mobile-test.php';
    return ob_get_clean();
});

add_shortcode('jobiizy_packages', function() {
    ob_start();
    include get_stylesheet_directory() . '/templates/jobiizy-packages.html';
    return ob_get_clean();
});
