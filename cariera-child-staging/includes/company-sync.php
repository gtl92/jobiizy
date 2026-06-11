<?php
/**
 * Synchronisation company → job listing : _company_id et logo
 */

add_action('admin_init', 'jobiizy_quick_test');
function jobiizy_quick_test() {
    if (isset($_GET['test_logo'])) {
        $job_id = isset($_GET['job_id']) ? absint($_GET['job_id']) : 123;
        $post   = get_post($job_id);
        echo '<pre>';
        echo 'Job ID: ' . $job_id . "\n";
        echo 'Logo avec $post: ' . get_the_company_logo($post, 'thumbnail') . "\n";
        echo 'Logo sans $post: ' . get_the_company_logo(null, 'thumbnail') . "\n";
        echo '</pre>';
        exit;
    }
}

/**
 * Fix Cariera : forcer l'enregistrement de _company_id à la sauvegarde admin
 */
add_action('save_post_job_listing', 'jobiizy_fix_company_id_meta', 1, 3);
function jobiizy_fix_company_id_meta($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ($post->post_type !== 'job_listing') return;

    $company_id =
        $_POST['company_id']
        ?? $_POST['_company_id']
        ?? $_POST['_company_manager_id']
        ?? get_post_meta($post_id, '_company_id', true)
        ?? get_post_meta($post_id, '_company_manager_id', true);

    if ($company_id) {
        update_post_meta($post_id, '_company_id', absint($company_id));
        error_log("🔧 [JOBIIZY FIX] _company_id forcé → $company_id pour job $post_id");
    } else {
        error_log("⚠️ [JOBIIZY FIX] Aucun company_id fourni pour job $post_id");
    }
}

/**
 * Cœur de la synchro logo : copie le logo de la company vers le job listing.
 * Appelé par les deux hooks ci-dessous, jamais directement.
 */
function jobiizy_sync_company_logo_core($job_id) {
    if (empty($job_id) || get_post_type($job_id) !== 'job_listing') return;

    $company_id = (int) get_post_meta($job_id, '_company_id', true);
    if (!$company_id) {
        error_log("❌ [JOBIIZY LOGO] Aucun company_id pour le job $job_id");
        return;
    }
    if (!get_post($company_id)) {
        error_log("❌ [JOBIIZY LOGO] Company $company_id inexistante (job $job_id)");
        return;
    }
    if (!has_post_thumbnail($company_id)) {
        error_log("⚠️ [JOBIIZY LOGO] Company $company_id sans logo (job $job_id)");
        return;
    }
    $thumbnail_id = get_post_thumbnail_id($company_id);
    if (!$thumbnail_id) {
        error_log("⚠️ [JOBIIZY LOGO] Company $company_id : thumbnail_id introuvable (job $job_id)");
        return;
    }
    set_post_thumbnail($job_id, $thumbnail_id);
    error_log("✅ [JOBIIZY LOGO] Logo $thumbnail_id copié → job $job_id (company $company_id)");
}

add_action('job_manager_save_job_listing', 'jobiizy_sync_company_logo_from_front', 20, 2);
function jobiizy_sync_company_logo_from_front($job_id, $values) {
    jobiizy_sync_company_logo_core($job_id);
}

add_action('save_post_job_listing', 'jobiizy_sync_company_logo_from_admin', 200, 3);
function jobiizy_sync_company_logo_from_admin($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if ($post->post_type !== 'job_listing') return;
    if (!$update) return;

    remove_action('save_post_job_listing', 'jobiizy_sync_company_logo_from_admin', 20);
    jobiizy_sync_company_logo_core($post_id);
    add_action('save_post_job_listing', 'jobiizy_sync_company_logo_from_admin', 20, 3);
}

// Renvoie le logo en taille originale (désactive le resize Cariera)
add_filter('cariera_company_logo_size', function() {
    return 'full';
});
