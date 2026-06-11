<?php
/**
 * VERSION SIMPLIFIÉE - Synchronisation des champs
 * Ajoute un bouton directement dans le tableau de bord WordPress
 * 
 * INSTRUCTIONS :
 * 1. Copiez TOUT ce code
 * 2. Collez-le À LA FIN de votre functions.php
 * 3. Allez dans "Tableau de bord" dans WordPress admin
 * 4. Vous verrez un widget avec le bouton de synchronisation
 */

// Fonction de synchronisation
function jobiizy_sync_single_job($post_id) {
    // Éviter les auto-saves
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }
    
    // Localisation
    $location = get_post_meta($post_id, '_job_location', true);
    if ($location) {
        $location_clean = trim(strip_tags($location));
        update_post_meta($post_id, 'jobiizy_location', $location_clean);
    }
    
    // Titre
    $job_title = get_post_meta($post_id, '_job_title', true);
    if ($job_title) {
        update_post_meta($post_id, 'jobiizy_title', $job_title);
    }
    
    // Application
    $application = get_post_meta($post_id, '_application', true);
    if ($application) {
        update_post_meta($post_id, 'jobiizy_application', $application);
    }
    
    // Salaire
    $salary = get_post_meta($post_id, '_job_salary', true);
    if ($salary) {
        update_post_meta($post_id, 'jobiizy_salary', $salary);
    }
    
    // Type d'emploi (taxonomy)
    $job_types = get_the_terms($post_id, 'job_listing_type');
    if ($job_types && !is_wp_error($job_types)) {
        $type_names = array();
        foreach ($job_types as $term) {
            $type_names[] = $term->name;
        }
        update_post_meta($post_id, 'jobiizy_job_type', implode(', ', $type_names));
    }
    
    // Featured
    $featured = get_post_meta($post_id, '_featured', true);
    update_post_meta($post_id, 'jobiizy_featured', $featured ? '1' : '0');
}

// Auto-sync sur les nouvelles offres
add_action('save_post_job_listing', 'jobiizy_sync_single_job', 20, 1);

// Widget dans le tableau de bord
add_action('wp_dashboard_setup', 'jobiizy_add_dashboard_widget');
function jobiizy_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'jobiizy_sync_widget',
        '🔄 Synchronisation JobiiZy',
        'jobiizy_dashboard_widget_content'
    );
}

function jobiizy_dashboard_widget_content() {
    // Compter les offres
    $total_jobs = wp_count_posts('job_listing');
    $published = $total_jobs->publish;
    
    ?>
    <div style="padding: 10px;">
        <p><strong>Synchronisez les champs pour Unlimited Elements</strong></p>
        <p style="color: #666; font-size: 13px;">
            Cette opération va créer de nouveaux champs (<code>jobiizy_location</code>, <code>jobiizy_title</code>, etc.) 
            lisibles par Unlimited Elements.
        </p>
        
        <p style="background: #fff3cd; border-left: 3px solid #ffc107; padding: 10px; font-size: 13px;">
            📊 <strong><?php echo $published; ?> offres</strong> à synchroniser
        </p>
        
        <?php if (isset($_GET['jobiizy_synced'])) : ?>
            <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px;">
                ✅ <strong>Synchronisation terminée !</strong><br>
                <?php echo (int)$_GET['jobiizy_synced']; ?> offres ont été synchronisées.
            </div>
        <?php endif; ?>
        
        <form method="post" action="" style="margin-top: 15px;">
            <?php wp_nonce_field('jobiizy_sync_action', 'jobiizy_sync_nonce'); ?>
            <input type="hidden" name="jobiizy_sync_all" value="1">
            <button type="submit" class="button button-primary button-large" 
                    onclick="return confirm('Synchroniser <?php echo $published; ?> offres ?');">
                🚀 Lancer la synchronisation
            </button>
        </form>
        
        <hr style="margin: 20px 0;">
        
        <details style="cursor: pointer;">
            <summary style="font-weight: bold; color: #0073aa;">📋 Champs créés (cliquez pour voir)</summary>
            <table style="width: 100%; margin-top: 10px; font-size: 12px;">
                <tr>
                    <td><code>_job_location</code></td>
                    <td>→</td>
                    <td><code>jobiizy_location</code></td>
                </tr>
                <tr>
                    <td><code>_job_title</code></td>
                    <td>→</td>
                    <td><code>jobiizy_title</code></td>
                </tr>
                <tr>
                    <td><code>_application</code></td>
                    <td>→</td>
                    <td><code>jobiizy_application</code></td>
                </tr>
                <tr>
                    <td><code>_job_salary</code></td>
                    <td>→</td>
                    <td><code>jobiizy_salary</code></td>
                </tr>
                <tr>
                    <td><code>job_listing_type</code></td>
                    <td>→</td>
                    <td><code>jobiizy_job_type</code></td>
                </tr>
            </table>
        </details>
    </div>
    <?php
}

// Traiter la synchronisation
add_action('admin_init', 'jobiizy_process_sync');
function jobiizy_process_sync() {
    // Vérifier le nonce et les permissions
    if (!isset($_POST['jobiizy_sync_all'])) {
        return;
    }
    
    if (!isset($_POST['jobiizy_sync_nonce']) || !wp_verify_nonce($_POST['jobiizy_sync_nonce'], 'jobiizy_sync_action')) {
        return;
    }
    
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Récupérer toutes les offres
    $args = array(
        'post_type' => 'job_listing',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'draft', 'pending'),
    );
    
    $jobs = get_posts($args);
    $synced = 0;
    
    foreach ($jobs as $job) {
        jobiizy_sync_single_job($job->ID);
        $synced++;
    }
    
    // Rediriger avec message de succès
    wp_redirect(admin_url('index.php?jobiizy_synced=' . $synced));
    exit;
}

// Message de confirmation après installation
add_action('admin_notices', 'jobiizy_sync_installed_notice');
function jobiizy_sync_installed_notice() {
    // Afficher seulement une fois
    if (get_option('jobiizy_sync_notice_shown')) {
        return;
    }
    
    // Marquer comme affiché
    update_option('jobiizy_sync_notice_shown', true);
    
    ?>
    <div class="notice notice-success is-dismissible">
        <p>
            <strong>✅ Code de synchronisation JobiiZy installé !</strong><br>
            Allez dans <a href="<?php echo admin_url('index.php'); ?>"><strong>Tableau de bord</strong></a> 
            pour voir le widget de synchronisation.
        </p>
    </div>
    <?php
}