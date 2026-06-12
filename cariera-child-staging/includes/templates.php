<?php
/**
 * Layout Jobiizy — filtres Cariera, template overrides, AJAX
 */

add_filter('cariera_single_job_layout', function($layout) {
    if ($layout === 'jobiizy') {
        jobiizy_log('Layout jobiizy → hérite de la logique v1');
        return 'v1';
    }
    return $layout;
}, 0);

add_filter('cariera_get_single_job_listing_template', function($template, $layout) {
    if ($layout === 'jobiizy') {
        $custom = get_stylesheet_directory() . '/job_manager/single-job/single-job-listing-jobiizy.php';
        if (file_exists($custom)) {
            jobiizy_log('Template chargé : ' . basename($custom));
            return $custom;
        }
    }
    return $template;
}, 999, 2);

if (!function_exists('jobiizy_is_layout_active')) {
    function jobiizy_is_layout_active(): bool {
        return is_singular('job_listing')
            && function_exists('cariera_single_job_layout')
            && cariera_single_job_layout() === 'jobiizy';
    }
}

add_action('single_job_listing_start', function() {
    if (!jobiizy_is_layout_active()) return;

    static $done = false;
    if ($done) return;
    $done = true;

    $job_id = get_the_ID();
    if (!$job_id) return;

    global $post;
    $prev_post = $post;
    $post = get_post($job_id);
    setup_postdata($post);

    get_job_manager_template('content-single-job_listing-company.php');

    wp_reset_postdata();
    $post = $prev_post;
}, 30);

add_action('single_job_listing_meta_end', function() {
    if (!jobiizy_is_layout_active() &&
        !is_page(['jobs-split-view', 'jobs-split-view-2', 'jobiizy-offres', 'jobs-s-jobiizy']) &&
        !(wp_doing_ajax() && isset($_POST['action']) && $_POST['action'] === 'cariera_load_single_job_ajax')) {
        return;
    }

    static $done = false;
    if ($done) return;
    $done = true;

    $job_id = get_the_ID();
    if (!$job_id) return;

    global $post;
    $prev_post = $post;
    $post = get_post($job_id);
    setup_postdata($post);

    $tpl = get_template_directory() . '/job_manager/single-job/single-job-application.php';
    if (file_exists($tpl)) {
        include $tpl;
    } elseif (current_user_can('administrator') && !wp_doing_ajax()) {
        echo '<div style="color:red">⚠️ Fichier manquant : ' . esc_html($tpl) . '</div>';
    }

    wp_reset_postdata();
    $post = $prev_post;
}, 20);

// AJAX — méthode 1 : hook spécifique
add_action('wp_ajax_cariera_load_single_job_ajax', 'jobiizy_inject_button_ajax', 999);
add_action('wp_ajax_nopriv_cariera_load_single_job_ajax', 'jobiizy_inject_button_ajax', 999);
function jobiizy_inject_button_ajax() {
    ob_start();
    error_log('[Jobiizy] Hook AJAX cariera_load_single_job_ajax');
}

// AJAX — méthode 2 : filtre sur la sortie
add_filter('cariera_single_job_ajax_output', 'jobiizy_inject_button_in_output', 999);
function jobiizy_inject_button_in_output($output) {
    error_log('[Jobiizy] Filtre cariera_single_job_ajax_output');

    if (strpos($output, 'application_button') !== false) {
        error_log('[Jobiizy] Bouton déjà présent');
        return $output;
    }

    $tpl = get_template_directory() . '/job_manager/single-job/single-job-application.php';
    if (!file_exists($tpl)) {
        error_log('[Jobiizy] Template non trouvé');
        return $output;
    }

    ob_start();
    include $tpl;
    $button_html = ob_get_clean();

    if (strpos($output, '</aside>') !== false) {
        $output = str_replace('</aside>', $button_html . '</aside>', $output);
        error_log('[Jobiizy] Bouton injecté avant </aside>');
    } else {
        $output .= "\n<!-- Jobiizy Postuler -->\n" . $button_html;
        error_log('[Jobiizy] Bouton injecté à la fin');
    }

    return $output;
}

// AJAX — méthode 3 (backup) : shutdown priority 999, après Debug Log Manager
add_action('shutdown', function() {
    if (empty($_POST['action']) || $_POST['action'] !== 'cariera_load_single_job_ajax') {
        return;
    }

    static $already_processed = false;
    if ($already_processed) return;
    $already_processed = true;

    error_log('[Jobiizy] Shutdown hook - AJAX détecté');

    $final_output = ob_get_contents();
    if (empty($final_output)) {
        error_log('[Jobiizy] Shutdown - Output vide');
        return;
    }

    if (strpos($final_output, 'application_button') !== false) {
        error_log('[Jobiizy] Shutdown - Bouton déjà présent');
        return;
    }

    error_log('[Jobiizy] Shutdown - Injection du bouton');

    $tpl = get_template_directory() . '/job_manager/single-job/single-job-application.php';
    if (!file_exists($tpl)) {
        error_log('[Jobiizy] Shutdown - Template non trouvé');
        return;
    }

    ob_start();
    include $tpl;
    $button_html = ob_get_clean();

    if (strpos($final_output, '</aside>') !== false) {
        $final_output = str_replace('</aside>', $button_html . '</aside>', $final_output);
    } else {
        $final_output .= "\n<!-- Jobiizy Postuler via shutdown -->\n" . $button_html;
    }

    if (ob_get_level() > 0) {
        ob_clean();
        echo $final_output;
    }

    error_log('[Jobiizy] Shutdown - Bouton injecté avec succès');
}, 999);

// Option admin : ajouter "Version 4 — Jobiizy" dans les réglages Cariera
add_filter('job_manager_settings', function($settings) {
    if (isset($settings['job_listings'][1])) {
        foreach ($settings['job_listings'][1] as &$field) {
            if ($field['name'] === 'cariera_job_manager_single_job_layout' && isset($field['options'])) {
                $field['options']['jobiizy'] = esc_html__('Version 4 — Jobiizy', 'cariera_core');
            }
        }
    }
    return $settings;
});
