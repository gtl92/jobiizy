<?php
/**
 * Plugin Name: JobiiZy Customization
 * Description: Personnalisations avancées pour JobiiZy (WP Job Manager + Cariera)
 * Version:  jobiizy-customizations 25.12.26
 * Author: GTL
 */
// =======================================================
// REDIRECTION GLOBALE DES LOGS DU PLUGIN JOBIIZY
// =======================================================
$child_error_log = get_stylesheet_directory() . '/error_log';
@ini_set('error_log', $child_error_log);
/**
 * Les error_log() peuvent causer des sorties HTML si mal configurés
 * Ajoutez cette vérification au début de votre fichier
 */
if ( ! defined('WP_DEBUG') || ! WP_DEBUG ) {
    // En production, désactiver les logs du plugin
    // Commentez cette ligne si vous avez besoin des logs
    // ini_set('error_reporting', 0);
}
// error_log('🟢 JOBIIZY: tous les logs du plugin → theme_child/error_log');
// Sécurité
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// APRÈS la ligne "if ( ! defined( 'ABSPATH' ) ) { exit; }"

/*
// 🔥 PATCH TEMPORAIRE - Tuer toute sortie HTML pendant AJAX
// ✅ NOUVEAU CODE (remplacez par) :
if ( defined('DOING_AJAX') && DOING_AJAX && !is_admin() ) {
    // Seulement pour AJAX frontend, pas admin
    ob_start();
    register_shutdown_function(function() {
        ob_end_clean();
    });
}
*/
/**
 * DEBUG AJAX - À ajouter TEMPORAIREMENT dans Jobiizy-function.php
 * Supprimez après avoir trouvé le problème !
 */

// ✅ NOUVEAU CODE (remplacez par) :
/*
add_action('init', function() {
    // ❌ Ne pas capturer les requêtes AJAX de l'admin WP
    if ( is_admin() ) {
        return;
    }
    
    if ( defined('DOING_AJAX') && DOING_AJAX ) {
        ob_start();
        add_action('shutdown', function() {
            $output = ob_get_clean();
            if ( ! empty( $output ) ) {
                error_log('[JOBIIZY AJAX LEAK] Sortie inattendue : ' . substr($output, 0, 500));
                error_log('[JOBIIZY AJAX LEAK] URL : ' . $_SERVER['REQUEST_URI']);
                error_log('[JOBIIZY AJAX LEAK] Action : ' . ($_REQUEST['action'] ?? 'N/A'));
            }
        }, 0);
    }
}, 1);
*/

// ============================================================
// ✅ REMPLACEZ PAR CE CODE SÉCURISÉ
// ============================================================

/**
 * Nettoyage AJAX UNIQUEMENT pour les requêtes Cariera/WPJM
 * Ne touche PAS aux requêtes admin WordPress (menus, widgets, etc.)
 */
add_action('init', function() {
    
    // ❌ JAMAIS dans l'admin WordPress
    if ( is_admin() ) {
        return;
    }
    
    // ❌ JAMAIS si pas en AJAX
    if ( ! defined('DOING_AJAX') || ! DOING_AJAX ) {
        return;
    }
    
    // ✅ Whitelist : actions AJAX autorisées (Cariera/WPJM uniquement)
    $allowed_actions = [
        'job_manager_get_listings',
        'cariera_ajax_register',
        'cariera_user_registration',
        'jobiizy_get_company_job_count',
        // Ajoutez ici vos actions AJAX custom
    ];
    
    $current_action = $_REQUEST['action'] ?? '';
    
    // ✅ Ne capturer QUE si c'est une action Jobiizy/Cariera
    if ( ! in_array( $current_action, $allowed_actions, true ) ) {
        return;
    }
    
    // Capturer les sorties parasites
    ob_start();
    
    add_action('shutdown', function() {
        $output = ob_get_clean();
        
        if ( ! empty( $output ) ) {
            error_log('[JOBIIZY AJAX LEAK] Action : ' . ($_REQUEST['action'] ?? 'N/A'));
            error_log('[JOBIIZY AJAX LEAK] Sortie : ' . substr($output, 0, 200));
        }
    }, 0);
    
}, 1);
/**
 * Tracer les appels AJAX Cariera/WP Job Manager
 */
add_action('wp_ajax_nopriv_job_manager_get_listings', function() {
    error_log('[JOBIIZY AJAX] job_manager_get_listings appelé');
}, 1);

add_action('wp_ajax_job_manager_get_listings', function() {
    error_log('[JOBIIZY AJAX] job_manager_get_listings appelé (logged in)');
}, 1);
/**
 * Enqueue Jobiizy apply JS
 * - Intercepte le clic "Postuler"
 * - Bloque Cariera pour les candidatures externes
 */
add_action( 'wp_enqueue_scripts', function () {

    // 🚫 Jamais dans l’admin WP
    if ( is_admin() ) {
         return;
    }

    // 🚫 Charger UNIQUEMENT sur une fiche offre
    // if ( ! is_singular( 'job_listing' ) ) {
    //    return;
    //}

    // Chemins plugin (CORRECTS)
    $plugin_dir = plugin_dir_path( __FILE__ );
    $plugin_url = plugin_dir_url( __FILE__ );

    $file_rel  = 'assets/js/jobiizy-apply.js';
    $file_path = $plugin_dir . $file_rel;

    // Sécurité : le fichier existe bien
    if ( ! file_exists( $file_path ) ) {
        error_log( '❌ Jobiizy: jobiizy-apply.js introuvable' );
        return;
    }

    wp_enqueue_script(
        'jobiizy-apply',
        $plugin_url . $file_rel,
        [],                     // ❗ pas besoin de jQuery
        filemtime( $file_path ),// version auto = cache busting
        true                    // footer
    );
});
/**
 * Détermine si une offre utilise un formulaire externe
 * (utilisable partout : templates, AJAX, hooks WPJM)
 */
if ( ! function_exists( 'jobiizy_is_external_application' ) ) {

    function jobiizy_is_external_application( $job_id ) {

        if ( ! $job_id ) {
            return false;
        }

        // ID du formulaire associé à l’offre
        $form_id = get_post_meta( $job_id, '_job_application_form', true );

        // Liste des formulaires externes
        $external_forms = [ 12183, 12245 ];

        return in_array( (int) $form_id, $external_forms, true );
    }
}
/**
 * Plugin Name: JobiiZy Customization
 * Description: Custom modifications for JobiiZy
 * Version: 3.0.0
 * Author: GTL
 */
/////////////////////////////
// AJOUT DES REGLAGES WPJM //
/////////////////////////////
// error_log('🚀 PLUGIN JOBIIZY CHARGÉ - ' . date('H:i:s'));
// if (isset($_GET['test_antispam'])) {
//    error_log('[JOBIIZY ANTI-SPAM] Test via URL OK');
//     echo "Log ajouté !";
//    exit;
// }

add_action('plugins_loaded', function () {
    if (function_exists('get_job_listing_categories')) {
        // Fonction déjà définie
    }

    function get_job_listing_categories() {
        if (!get_option('job_manager_enable_categories')) {
            return [];
        }

        $show_empty_categories = get_option('job_manager_show_empty_categories', '0');

        $args = [
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => ($show_empty_categories === '0'),
        ];

        $args = apply_filters('get_job_listing_category_args', $args);
        $args['taxonomy'] = \WP_Job_Manager_Post_Types::TAX_LISTING_CATEGORY;

        return get_terms($args);
    }
}, 5);

/////////////////////////////////////////////
// LOGIQUE DE PUBLICATION / MODIFICATION   //
/////////////////////////////////////////////

function jobiizy_skip_job_approval_for_selected_roles($needs_approval, $job_id) {
    $author_id = get_post_field('post_author', $job_id);
    $user = get_userdata($author_id);
    $allowed_roles = get_option('job_manager_skip_moderation_roles', []);

    if ($user && array_intersect($allowed_roles, (array) $user->roles)) {
        return false;
    }
    return $needs_approval;
}
add_filter('job_manager_job_submission_requires_approval', 'jobiizy_skip_job_approval_for_selected_roles', 10, 2);
add_filter('job_manager_job_edit_requires_approval', 'jobiizy_skip_job_approval_for_selected_roles', 10, 2);

/////////////////////////////////////////////////////////
// FORCE LE STATUT PUBLISH POUR CES RÔLES DANS ADMIN   //
/////////////////////////////////////////////////////////
function jobiizy_force_publish_for_selected_roles($post_data, $postarr) {

    // 🚫 Si on déplace à la corbeille → ne rien faire !
    if (isset($post_data['post_status']) && $post_data['post_status'] === 'trash') {
        return $post_data;
    }

    if (isset($post_data['post_type']) && $post_data['post_type'] === 'job_listing') {
        $author_id = $post_data['post_author'];
        $user = get_userdata($author_id);

        $allowed_roles = get_option('job_manager_skip_moderation_roles', []);
        if (!is_array($allowed_roles)) {
            $allowed_roles = [$allowed_roles];
        }

        if ($user && array_intersect($allowed_roles, (array) $user->roles)) {

            // ⚠️ Ne pas forcer publish si déjà trash ou draft
            if (!in_array($post_data['post_status'], ['trash', 'draft', 'pending'])) {
                $post_data['post_status'] = 'publish';
            }
        }
    }

    return $post_data;
}
add_filter('wp_insert_post_data', 'jobiizy_force_publish_for_selected_roles', 99, 2);

/////////////////////////////////
// BULK ACTIONS MIS EN AVANT   //
/////////////////////////////////

add_filter('bulk_actions-edit-job_listing', function ($bulk_actions) {
    $bulk_actions['mark_featured'] = __('Mettre en avant', 'my-job-manager-customizations');
    $bulk_actions['remove_featured'] = __('Retirer mis en avant', 'my-job-manager-customizations');
    return $bulk_actions;
});

add_filter('handle_bulk_actions-edit-job_listing', function ($redirect_to, $doaction, $post_ids) {
    if ($doaction === 'mark_featured') {
        foreach ($post_ids as $post_id) {
            update_post_meta($post_id, '_featured', '1');
        }
        $redirect_to = add_query_arg('bulk_marked_featured', count($post_ids), $redirect_to);
    }
    if ($doaction === 'remove_featured') {
        foreach ($post_ids as $post_id) {
            update_post_meta($post_id, '_featured', '0');
        }
        $redirect_to = add_query_arg('bulk_removed_featured', count($post_ids), $redirect_to);
    }
    return $redirect_to;
}, 10, 3);

add_action('admin_notices', function () {
    if (!empty($_REQUEST['bulk_marked_featured'])) {
        $count = intval($_REQUEST['bulk_marked_featured']);
        printf('<div id="message" class="updated fade"><p>%s offre(s) mises en avant.</p></div>', $count);
    }
    if (!empty($_REQUEST['bulk_removed_featured'])) {
        $count = intval($_REQUEST['bulk_removed_featured']);
        printf('<div id="message" class="updated fade"><p>%s offre(s) retirées du mode mis en avant.</p></div>', $count);
    }
});

/////////////////////////////////////////////////////////
// NETTOYAGE DES OPTIONS LORS DE LA SUPPRESSION PLUGIN //
/////////////////////////////////////////////////////////

add_filter('job_manager_data_cleaner_options', function ($options) {
    $options[] = 'job_manager_show_empty_categories';
    $options[] = 'job_manager_skip_moderation_roles';
    return $options;
});


// ✅ Inclure la page des réglages JobiiZy (admin)
require_once plugin_dir_path(__FILE__) . 'jobiizy-settings-page.php';
// ✅ Inclure le diagnostic des jobs
require_once plugin_dir_path(__FILE__) . 'diagnostic-jobs-entreprise.php';

// ✅ Inclure la correction en masse
require_once plugin_dir_path(__FILE__) . 'correction-masse-jobs.php';
// ✅ Inclure la correction en masse
require_once plugin_dir_path(__FILE__) . 'filtre-entreprise-jobs.php';
// Amélioration affichage entreprise
require_once plugin_dir_path(__FILE__) . 'amelioration-entreprise-simple.php';
// Correctif logos entreprises
require_once plugin_dir_path(__FILE__) . 'correctif-logos-entreprises.php';
// gestion des API externes JOBS
require_once __DIR__ . '/api/jobiizy-api-external-jobs.php';

// MODULE COMPANY
require_once __DIR__ . '/modules/company/functions-company.php';
// Page admin anti-spam
require_once __DIR__ . '/admin/anti-spam-admin.php';
// ✅ Inclure la page des menus JobiiZy customization (admin)
require_once plugin_dir_path(__FILE__) . 'admin-menus.php';

// Gestion avancée des comptes utilisateurs (Cariera + WP)
require_once __DIR__ . '/admin/users/user-management.php';

require_once plugin_dir_path(__FILE__) . 'admin/users/email-notifications.php';

// Gestion formulaire custom
require_once plugin_dir_path(__FILE__) . 'apply-mode.php';

require_once __DIR__ . '/admin/users/email-log.php';

// ✅ Valeur par défaut à l’activation du plugin
register_activation_hook(__FILE__, function () {
    if (get_option('jobiizy_enable_company_carousel_override') === false) {
        add_option('jobiizy_enable_company_carousel_override', '1');
    }
});

////////////////////////////////////
// SURCHARGE DU CARROUSEL COMPANY //
////////////////////////////////////
add_action('plugins_loaded', function () {
    $enabled = get_option('jobiizy_enable_company_carousel_override', '1');
    if ($enabled === '1') {
        add_filter('job_manager_locate_template', function ($located, $template_name, $template_path, $default_path = '') {
            if ($template_name === 'company-templates/company-carousel.php') {
                $custom = plugin_dir_path(__FILE__) . 'templates/company-carousel.php';
                if (file_exists($custom)) {
                    return $custom;
                }
            }
            return $located;
        }, 5, 4);
    }
});

/* 
/////////////////////////////
// PAGE SCRAPPING / API JOBS
/////////////////////////////

 add_action('admin_menu', function() {
    add_submenu_page(
        'jobiizy_settings',
        'Offres externes JobiiZy',
        'Offres externes',
        'manage_options',
        'jobiizy_external_jobs',
        'jobiizy_render_external_jobs_page'
    );
});
 */


/**
 * ============================================================
 * 9. DEBUG : AFFICHER LES METAS D’UN POST VIA ?debug_meta=ID
 * ============================================================
 */

add_action('init', function () {
    if (!isset($_GET['debug_meta'])) {
        return;
    }

    $post_id = intval($_GET['debug_meta']);
    if (!$post_id) {
        return;
    }

    $meta         = get_post_meta($post_id);
    $post_title   = get_the_title($post_id);
    $company_name = get_post_meta($post_id, '_company_name', true);

    echo "<h1>Metas du post #$post_id</h1>";

    if ($post_title) {
        echo "<p style='font-size:16px;color:#0ff;'>Titre WP : <strong>{$post_title}</strong></p>";
    }

    if ($company_name) {
        echo "<p style='font-size:16px;color:#0ff;'>Nom d’entreprise : <strong>{$company_name}</strong></p>";
    }

    echo "<p><a href='/wp-admin/post.php?post={$post_id}&action=edit' style='color:#7bf;text-decoration:underline;' target='_blank'>
📝 Ouvrir dans l’éditeur WordPress
</a></p>";

    echo "<pre style='font-size:14px;background:#111;color:#0f0;padding:20px;'>";
$meta['_active_jobs'][0] = maybe_unserialize($meta['_active_jobs'][0]);
print_r($meta);
    echo "</pre>";
    exit;
});

/**
 * ============================================================
 * BONUS: Script de vérification du format des metas
 * Accéder à /wp-admin/?check_active_jobs_format
 * ============================================================
 */

add_action('admin_init', 'jobiizy_check_active_jobs_format');

function jobiizy_check_active_jobs_format() {
    
    if (!isset($_GET['check_active_jobs_format']) || !current_user_can('manage_options')) {
        return;
    }
    
    echo "<h1>🔍 Vérification du format _active_jobs</h1>";
    
    $companies = get_posts([
        'post_type'   => 'company',
        'numberposts' => 20, // Limiter à 20 pour la démo
        'post_status' => 'publish'
    ]);
    
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
    echo "<tr>
        <th>ID</th>
        <th>Nom</th>
        <th>Meta brut</th>
        <th>Format</th>
        <th>Total</th>
        <th>Actifs</th>
        <th>WP_Query</th>
    </tr>";
    
    foreach ($companies as $company) {
        $meta = get_post_meta($company->ID, '_active_jobs', true);
        
        // Déterminer le format
        $format = 'inconnu';
        $total = '-';
        $actifs = '-';
        
        if (is_array($meta) && isset($meta[0]) && is_array($meta[0])) {
            $format = 'Double tableau';
            $total = $meta[0][0] ?? '-';
            $actifs = $meta[0][1] ?? '-';
        } elseif (is_array($meta)) {
            $format = 'Tableau simple';
            $total = $meta[0] ?? '-';
            $actifs = $meta[1] ?? '-';
        } elseif (is_numeric($meta)) {
            $format = 'Nombre';
            $actifs = $meta;
        }
        
        // Compter via WP_Query
        $args = [
            'post_type'   => 'job_listing',
            'post_status' => 'publish',
            'meta_query'  => [
                ['key' => '_company_manager_id', 'value' => $company->ID]
            ]
        ];
        $query = new WP_Query($args);
        $real_count = $query->found_posts;
        wp_reset_postdata();
        
        // Couleur selon si ça correspond
        $color = ($actifs == $real_count) ? 'lightgreen' : 'lightcoral';
        
        echo "<tr style='background:{$color};'>
            <td>{$company->ID}</td>
            <td>" . esc_html($company->post_title) . "</td>
            <td><pre style='font-size:10px;'>" . print_r($meta, true) . "</pre></td>
            <td>{$format}</td>
            <td>{$total}</td>
            <td><strong>{$actifs}</strong></td>
            <td><strong>{$real_count}</strong></td>
        </tr>";
    }
    
    echo "</table>";
    
    echo "<hr><h2>📊 Légende</h2>";
    echo "<p><span style='background:lightgreen;padding:5px;'>Vert</span> = Le meta correspond au nombre réel de jobs</p>";
    echo "<p><span style='background:lightcoral;padding:5px;'>Rouge</span> = Différence entre meta et réalité</p>";
    
    echo "<hr>";
    echo "<p><a href='/wp-admin/'>← Retour à l'admin</a></p>";
    
    exit;
}

/////////////////////////////
// RESTAURATION ACTIVE JOBS //
/////////////////////////////

/* add_action('admin_menu', function() {
    add_submenu_page(
        'jobiizy_settings',
        'Restaurer Active Jobs',
        '🔄 Restaurer Active Jobs',
        'manage_options',
        'jobiizy_restore_active_jobs',
        'jobiizy_render_restore_active_jobs_page'
    );
}); */

function jobiizy_render_restore_active_jobs_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Accès refusé');
    }
    
    echo '<div class="wrap">';
    echo '<h1>🔄 Restauration des metas _active_jobs</h1>';
    
    // Traitement du formulaire
    if (isset($_POST['generate_sql'])) {
        jobiizy_generate_restore_sql();
    } else {
        jobiizy_show_restore_form();
    }
    
    echo '</div>';
}

function jobiizy_show_restore_form() {
    global $wpdb;
    
    // Compter les entreprises
    $total_companies = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$wpdb->posts} 
        WHERE post_type = 'company' 
        AND post_status IN ('publish', 'pending', 'draft')
    ");
    
    // Compter celles qui ont _active_jobs
    $companies_with_meta = $wpdb->get_var("
        SELECT COUNT(DISTINCT post_id) 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_active_jobs'
        AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'company')
    ");
    
    echo '<div class="notice notice-info">';
    echo '<p><strong>📊 Statistiques actuelles :</strong></p>';
    echo '<ul>';
    echo '<li>Total entreprises : ' . $total_companies . '</li>';
    echo '<li>Entreprises avec _active_jobs : ' . $companies_with_meta . '</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<div class="notice notice-warning">';
    echo '<p><strong>⚠️ ATTENTION - Utilisation :</strong></p>';
    echo '<ul>';
    echo '<li><strong>À exécuter UNIQUEMENT sur la PRODUCTION</strong> (jobiizy.com)</li>';
    echo '<li>Ce script génère du SQL pour copier les metas vers le clone</li>';
    echo '<li>Ne pas exécuter sur le clone !</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<form method="post">';
    echo '<input type="hidden" name="generate_sql" value="1">';
    echo '<p>';
    submit_button('🚀 Générer le SQL de restauration', 'primary', 'submit', false);
    echo '</p>';
    echo '</form>';
}

function jobiizy_generate_restore_sql() {
    global $wpdb;
    
    echo '<h2>📝 SQL généré</h2>';
    
    // Récupérer TOUTES les entreprises avec leur _active_jobs
    // DISTINCT pour éviter les doublons si une entreprise a plusieurs lignes de meta
    $results = $wpdb->get_results("
        SELECT DISTINCT p.ID, p.post_title, 
               (SELECT meta_value FROM {$wpdb->postmeta} 
                WHERE post_id = p.ID AND meta_key = '_active_jobs' 
                LIMIT 1) as meta_value
        FROM {$wpdb->posts} p
        WHERE p.post_type = 'company'
        AND p.post_status IN ('publish', 'pending', 'draft')
        ORDER BY p.ID ASC
    ");
    
    if (empty($results)) {
        echo '<div class="notice notice-error">';
        echo '<p>❌ Aucune entreprise trouvée !</p>';
        echo '</div>';
        return;
    }
    
    echo '<div class="notice notice-success">';
    echo '<p>✅ ' . count($results) . ' entreprises trouvées</p>';
    echo '</div>';
    
    echo '<h3>📋 Instructions :</h3>';
    echo '<ol>';
    echo '<li><strong>Copier</strong> le SQL ci-dessous</li>';
    echo '<li>Aller sur <strong>phpMyAdmin du CLONE</strong></li>';
    echo '<li>Sélectionner la base de données du clone</li>';
    echo '<li>Onglet "SQL"</li>';
    echo '<li>Coller et exécuter</li>';
    echo '</ol>';
    
    // Générer le SQL
    echo '<div style="background: #f5f5f5; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">';
    echo '<textarea readonly style="width: 100%; height: 400px; font-family: monospace; font-size: 12px;">';
    
    echo "-- ============================================================\n";
    echo "-- Script de restauration _active_jobs\n";
    echo "-- Généré depuis la PRODUCTION : " . get_site_url() . "\n";
    echo "-- Date : " . date('Y-m-d H:i:s') . "\n";
    echo "-- Entreprises : " . count($results) . "\n";
    echo "-- ============================================================\n\n";
    
    echo "-- ÉTAPE 1 : Supprimer tous les _active_jobs existants\n";
    echo "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_active_jobs';\n\n";
    
    echo "-- ÉTAPE 2 : Réinsérer les bonnes valeurs\n";
    
    $inserted = 0;
    $processed_ids = array(); // Pour éviter les doublons
    
    foreach ($results as $row) {
        // Éviter les doublons (au cas où la requête retournerait 2 fois la même entreprise)
        if (in_array($row->ID, $processed_ids)) {
            continue;
        }
        $processed_ids[] = $row->ID;
        
        // ⚠️ IMPORTANT : Ne pas sauter les valeurs vides ou "0" !
        // Même une entreprise avec 0 jobs doit avoir son meta
        if ($row->meta_value === null || $row->meta_value === false) {
            // Vraiment aucun meta = on calcule avec WP_Query
            $count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) 
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_company_manager_id'
                WHERE m.meta_value = %d
                AND p.post_type = 'job_listing'
                AND p.post_status = 'publish'
            ", $row->ID));
            
            // Créer le format sérialisé [count]
            $row->meta_value = serialize(array(intval($count)));
        }
        
        // Échapper la valeur pour SQL
        $meta_value_escaped = str_replace("'", "''", $row->meta_value);
        
        echo "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES ";
        echo "(" . intval($row->ID) . ", '_active_jobs', '" . $meta_value_escaped . "');\n";
        
        $inserted++;
    }
    
    echo "\n-- ============================================================\n";
    echo "-- FIN : " . $inserted . " metas réinsérés\n";
    echo "-- ============================================================\n";
    
    echo '</textarea>';
    
    // Bouton copier
    echo '<p><button type="button" class="button button-secondary" onclick="copySQL()">📋 Copier le SQL</button></p>';
    echo '</div>';
    
    // Script JS pour copier
    echo '<script>
    function copySQL() {
        const textarea = document.querySelector("textarea");
        textarea.select();
        document.execCommand("copy");
        alert("✅ SQL copié dans le presse-papier !");
    }
    </script>';
    
    // Table récapitulative
    echo '<h3>📊 Aperçu des données (20 premières entreprises)</h3>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th style="width: 60px;">ID</th>';
    echo '<th>Nom entreprise</th>';
    echo '<th>Meta _active_jobs</th>';
    echo '<th style="width: 150px;">Type détecté</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $count = 0;
    foreach ($results as $row) {
        if ($count >= 20) {
            echo '<tr><td colspan="4"><em>... et ' . (count($results) - 20) . ' autres entreprises</em></td></tr>';
            break;
        }
        
        echo '<tr>';
        echo '<td>' . $row->ID . '</td>';
        echo '<td>' . esc_html($row->post_title) . '</td>';
        
        if (empty($row->meta_value)) {
            echo '<td><em style="color: #999;">Aucun meta</em></td>';
            echo '<td><span style="color: orange;">⚠️ Vide</span></td>';
        } else {
            // Détecter le type
            $unserialized = @maybe_unserialize($row->meta_value);
            
            echo '<td><code style="font-size: 11px;">' . esc_html(substr($row->meta_value, 0, 60)) . '...</code></td>';
            
            if (is_array($unserialized)) {
                if (count($unserialized) === 2) {
                    echo '<td><span style="color: green;">✅ [total, actifs]</span></td>';
                } elseif (count($unserialized) === 1) {
                    if (is_array($unserialized[0]) && empty($unserialized[0])) {
                        echo '<td><span style="color: red;">❌ Array vide</span></td>';
                    } else {
                        echo '<td><span style="color: blue;">📊 [count]</span></td>';
                    }
                } else {
                    echo '<td><span style="color: purple;">📋 [IDs: ' . count($unserialized) . ']</span></td>';
                }
            } elseif (is_numeric($row->meta_value)) {
                echo '<td><span style="color: green;">✅ Nombre</span></td>';
            } else {
                echo '<td><span style="color: gray;">❓ Autre</span></td>';
            }
        }
        echo '</tr>';
        
        $count++;
    }
    
    echo '</tbody>';
    echo '</table>';
}

/**
 * ============================================================
 * 10. COLONNES CUSTOM POUR LES ENTREPRISES
 * ============================================================
 */

add_filter('manage_edit-company_columns', function ($columns) {

    $new_columns = [];

    // Ajouter la colonne ID avant le titre
    //GTL $new_columns['company_id'] = 'ID';

    foreach ($columns as $key => $label) {
        $new_columns[$key] = $label;

        if ($key === 'title') {
            $new_columns['company_jobs_total']   = 'Jobs totaux';
            $new_columns['company_jobs_active']  = 'Jobs actifs';
            $new_columns['company_jobs_visible'] = 'Affichés';
        }
    }

    return $new_columns;
});


add_action('manage_company_posts_custom_column', function ($column, $post_id) {

/*
if ($column === 'company_id') {
    echo '<strong>' . $post_id . '</strong>';
    return;
}
*/

if (!in_array($column, ['company_jobs_total', 'company_jobs_active', 'company_jobs_visible'], true)) {
    return;
}
    $meta   = get_post_meta($post_id, '_active_jobs', true);
    $total  = 0;
    $active = 0;

    if (is_array($meta)) {
        $total  = intval($meta[0] ?? 0);
        $active = intval($meta[1] ?? 0);
    } elseif ($meta !== '') {
        $total  = intval($meta);
        $active = 0;
    }

    switch ($column) {
        case 'company_jobs_total':
            echo "<strong>{$total}</strong>";
            break;

        case 'company_jobs_active':
            $color = $active > 0 ? '#2ECC71' : '#E74C3C';
            echo "<span style='font-weight:bold;color:{$color};'>{$active}</span>";
            break;

        case 'company_jobs_visible':
            if ($active > 0) {
                echo "<span style='color:#2ECC71;font-weight:bold;'>🟢 Oui</span>";
            } else {
                echo "<span style='color:#E74C3C;font-weight:bold;'>🔴 Non</span>";
            }
            break;
    }
}, 10, 2);

add_filter('manage_edit-company_sortable_columns', function ($sortable) {
// $sortable['company_id']          = 'ID';
$sortable['company_jobs_total']  = 'company_jobs_total';
$sortable['company_jobs_active'] = 'company_jobs_active';
return $sortable;
});


/**
 * ============================================================
 * COLONNE ENTREPRISE DANS LA LISTE DES OFFRES D'EMPLOI
 * ============================================================
 */
/*
// 1) Ajouter la colonne
add_filter('manage_edit-job_listing_columns', function($columns) {

    // On insère la colonne juste après le titre du job
    $new = [];

    foreach ($columns as $key => $label) {
        $new[$key] = $label;

        if ($key === 'title') {
            $new['job_company'] = 'Entreprise';
        }
    }

    return $new;
});
*/
/*
// 2) Affichage du contenu de la colonne
add_action('manage_job_listing_posts_custom_column', function($column, $post_id) {

    if ($column !== 'job_company') {
        return;
    }

    // 1) Récupérer l'entreprise liée
    $company_id = get_post_meta($post_id, '_company_id', true);

    // 2) Si vide → fallback sur _company_manager_id
    if (!$company_id) {
        $company_id = get_post_meta($post_id, '_company_manager_id', true);
    }

    // Toujours rien ? → afficher —
    if (!$company_id) {
        echo '<span style="color:#999;">—</span>';
        return;
    }

    // Nom de l’entreprise
    $name = get_post_meta($company_id, '_company_name', true);
    if (!$name) {
        $name = get_the_title($company_id);
    }
    if (!$name) {
        $name = "Entreprise #{$company_id}";
    }

    // Lien vers l'édition
    $edit_url = admin_url("post.php?post={$company_id}&action=edit");

    echo '<a href="' . esc_url($edit_url) . '" style="font-weight:bold;">' . esc_html($name) . '</a>';

}, 10, 2);
*/
/*
// 3) Colonne sortable
add_filter('manage_edit-job_listing_sortable_columns', function($sortable) {
    $sortable['job_company'] = '_company_id';
    return $sortable;
});
*/
/**
 * ============================================================
 * JOBIIZY — OUTIL ADMIN : Réparation massive des logos entreprises
 * Ajouté dans le plugin Jobiizy Customizations
 * Menu : Outils → Fix logos entreprises
 * ============================================================
 */

/* add_action('admin_menu', function() {
	add_submenu_page(
		'jobiizy_settings',   // 🔥 identifiant du menu parent (ton plugin)
		'Fix logos entreprises',
		'Fix logos entreprises',
		'manage_options',
		'jobiizy-fix-company-logos',
		'jobiizy_render_fix_company_logos_page'
	);
}); */

/**
 * Page admin : interface + exécution
 */
function jobiizy_render_fix_company_logos_page() {
    echo '<div class="wrap"><h1>🔧 Jobiizy – Réparation massive des logos entreprises</h1>';

    // Bouton cliqué → exécution
    if (isset($_POST['jobiizy_run_fix']) && check_admin_referer('jobiizy_fix_company_logos')) {

        echo '<h2>Résultats</h2>';
        echo '<table class="widefat fixed striped"><thead><tr>
                <th style="width:80px">Job</th>
                <th>Entreprise</th>
                <th style="width:80px">Logo</th>
                <th style="width:120px">Action</th>
              </tr></thead><tbody>';

        // Récupération de tous les jobs
        $jobs = get_posts([
            'post_type'      => 'job_listing',
            'posts_per_page' => -1,
            'post_status'    => 'any'
        ]);

        foreach ($jobs as $job) {

            $job_id = $job->ID;

            // Récupérer la company associée
            $company_id =
                get_post_meta($job_id, '_company_id', true)
                ?: get_post_meta($job_id, '_company_manager_id', true);

            // Cas 1 : aucune entreprise
            if (!$company_id) {
                echo "<tr>
                        <td>$job_id</td>
                        <td><em>Aucune entreprise</em></td>
                        <td>-</td>
                        <td style='color:#d00;'>❌ Ignoré</td>
                      </tr>";
                continue;
            }

            $company = get_post($company_id);

            // Cas 2 : entreprise inexistante
            if (!$company) {
                echo "<tr>
                        <td>$job_id</td>
                        <td>ID $company_id</td>
                        <td>-</td>
                        <td style='color:#d00;'>❌ Introuvable</td>
                      </tr>";
                continue;
            }

            // Lire le logo entreprise
            $logo_id = get_post_thumbnail_id($company_id);

            // Cas 3 : pas de logo pour l’entreprise
            if (!$logo_id) {
                echo "<tr>
                        <td>$job_id</td>
                        <td>{$company->post_title}</td>
                        <td>-</td>
                        <td style='color:#e67e00;'>⚠️ Pas de logo</td>
                      </tr>";
                continue;
            }

            // Copie du logo sur le job
            set_post_thumbnail($job_id, $logo_id);

            $logo_url = wp_get_attachment_image_url($logo_id, 'thumbnail');

            echo "<tr>
                    <td>$job_id</td>
                    <td>{$company->post_title}</td>
                    <td><img src='$logo_url' style='width:60px;height:auto;'></td>
                    <td style='color:#0a0;'>✅ Mis à jour</td>
                  </tr>";
        }

        echo '</tbody></table>';

        echo '<div class="updated notice"><p>🎉 Réparation terminée.</p></div>';

    } else {

        // Page d’accueil : bouton d’exécution
        echo '<p>Cette opération va :</p>
        <ul>
            <li>Analyser toutes les offres d’emploi</li>
            <li>Détecter l’entreprise associée (compatibilité complète Cariera)</li>
            <li>Corriger les metas `_company_id` manquantes</li>
            <li>Copier le logo de l’entreprise vers l’offre</li>
        </ul>';

        echo '<form method="post">';
        wp_nonce_field('jobiizy_fix_company_logos');

        echo '<p><button class="button button-primary button-hero" name="jobiizy_run_fix" value="1">
                🔧 Lancer la réparation maintenant
              </button></p>';

        echo '</form>';
    }

    echo '</div>';
}

/**
 * ============================================================
 * JOBIIZY — Correctif automatique : synchronisation entreprise + logo
 * Version améliorée avec gestion des permissions et optimisations
 * ============================================================
 */

add_action('save_post_job_listing', 'jobiizy_sync_company_and_logo', 20, 3);
function jobiizy_sync_company_and_logo($post_id, $post, $update) {
    
    // Sécurité de base
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ($post->post_type !== 'job_listing') return;
    
    // ✅ AJOUT : Vérifier les permissions
    if (!current_user_can('edit_post', $post_id)) return;
    
    // ✅ AJOUT : Éviter les révisions
    if (wp_is_post_revision($post_id)) return;
    
    // ---- Trouver le bon company_id avec gestion Cariera 1.9.6+ ----
    $company_id = null;
    
    // Priorité 1 : Données POST (formulaire admin)
    if (!empty($_POST['company_id'])) {
        $company_id = $_POST['company_id'];
    } elseif (!empty($_POST['_company_id'])) {
        $company_id = $_POST['_company_id'];
    } elseif (!empty($_POST['_company_manager_id'])) {
        $company_id = $_POST['_company_manager_id'];
    }
    
    // Priorité 2 : Métadonnées existantes
    if (!$company_id) {
        $company_id = get_post_meta($post_id, '_company_manager_id', true); // Nouveau (Cariera 1.9.6+)
    }
    if (!$company_id) {
        $company_id = get_post_meta($post_id, '_company_id', true); // Ancien (standard)
    }
    
    // Aucune entreprise trouvée
    if (!$company_id) {
        error_log("❌ [JOBIIZY AUTO] Aucun company_id pour job $post_id");
        return;
    }
    
    $company_id = absint($company_id);
    
    // ✅ AJOUT : Vérifier que l'entreprise existe
    $company = get_post($company_id);
    if (!$company || $company->post_type !== 'company') {
        error_log("❌ [JOBIIZY AUTO] Entreprise #$company_id introuvable ou invalide pour job $post_id");
        return;
    }
    
    // ---- Toujours forcer _company_id (le champ standard pour compatibilité) ----
    $current_company_id = get_post_meta($post_id, '_company_id', true);
    if ($current_company_id != $company_id) {
        update_post_meta($post_id, '_company_id', $company_id);
        error_log("🔄 [JOBIIZY AUTO] Meta _company_id mise à jour : $company_id pour job $post_id");
    }
    
    // ---- Synchroniser le logo ----
    $logo_id = get_post_thumbnail_id($company_id);
    
    if ($logo_id) {
        // Vérifier si le logo actuel est différent
        $current_logo_id = get_post_thumbnail_id($post_id);
        
        if ($current_logo_id != $logo_id) {
            set_post_thumbnail($post_id, $logo_id);
            error_log("✅ [JOBIIZY AUTO] Logo $logo_id synchronisé pour job $post_id (company $company_id)");
        } else {
            error_log("ℹ️ [JOBIIZY AUTO] Logo déjà à jour pour job $post_id");
        }
    } else {
        error_log("⚠️ [JOBIIZY AUTO] Entreprise $company_id sans logo");
        
        // ✅ OPTIONNEL : Supprimer le logo du job si l'entreprise n'en a plus
        // Décommenter si vous voulez ce comportement :
        // if (has_post_thumbnail($post_id)) {
        //     delete_post_thumbnail($post_id);
        //     error_log("🗑️ [JOBIIZY AUTO] Logo supprimé du job $post_id (entreprise sans logo)");
        // }
    }
}

/**
 * ============================================================
 * JOBIIZY — Synchronisation aussi au changement de company
 * ============================================================
 */
add_action('updated_post_meta', 'jobiizy_sync_on_company_meta_change', 10, 4);
function jobiizy_sync_on_company_meta_change($meta_id, $post_id, $meta_key, $meta_value) {
    
    // On s'intéresse seulement aux changements de company
    if (!in_array($meta_key, ['_company_id', '_company_manager_id'])) {
        return;
    }
    
    // Vérifier que c'est un job_listing
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'job_listing') {
        return;
    }
    
    $new_company_id = absint($meta_value);
    
    if (!$new_company_id) {
        return;
    }
    
    // Synchroniser _company_id si c'est _company_manager_id qui a changé
    if ($meta_key === '_company_manager_id') {
        update_post_meta($post_id, '_company_id', $new_company_id);
    }
    
    // Synchroniser le logo
    $logo_id = get_post_thumbnail_id($new_company_id);
    
    if ($logo_id) {
        set_post_thumbnail($post_id, $logo_id);
        error_log("✅ [JOBIIZY META] Logo $logo_id synchronisé pour job $post_id (nouveau company $new_company_id)");
    } else {
        // Optionnel : supprimer le logo si la nouvelle company n'en a pas
        // delete_post_thumbnail($post_id);
        error_log("⚠️ [JOBIIZY META] Nouveau company $new_company_id sans logo pour job $post_id");
    }
}

/**
 * ============================================================
 * JOBIIZY — Colonne admin avec le logo
 * ============================================================
 */
add_action('job_manager_admin_after_job_title', 'jobiizy_show_logo_in_admin', 5);
function jobiizy_show_logo_in_admin($post) {
    $logo = get_the_company_logo($post, 'thumbnail');
    
    if ($logo) {
        echo '<div class="jobiizy-admin-logo" style="margin-top: 8px;">';
        // echo '<img src="' . esc_url($logo) . '" alt="' . esc_attr(get_the_company_name($post)) . '" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">';
        echo '<img src="' . esc_url($logo) . '" alt="' . esc_attr(get_the_company_name($post)) . '" style="">';
        echo '</div>';
    } else {
        // Logo par défaut (optionnel)
        $default = apply_filters('job_manager_default_company_logo', JOB_MANAGER_PLUGIN_URL . '/assets/images/company.png');
        echo '<div class="jobiizy-admin-logo" style="margin-top: 8px;">';
        // echo '<img src="' . esc_url($default) . '" alt="Logo par défaut" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; opacity: 0.3;">';
        echo '<img src="' . esc_url($default) . '" alt="Logo par défaut" style="opacity: 0.3;">';
        echo '</div>';
    }
}

// ============================================================
// FIX COMPTEUR ENTREPRISE - Backend AJAX
// L'affichage visuel est géré par splitview-company-link.js
// ============================================================

// Action AJAX pour récupérer le compteur d'emplois
add_action('wp_ajax_jobiizy_get_company_job_count', 'jobiizy_ajax_get_company_job_count');
add_action('wp_ajax_nopriv_jobiizy_get_company_job_count', 'jobiizy_ajax_get_company_job_count');

function jobiizy_ajax_get_company_job_count() {
    $company_id = intval($_POST['company_id']);
    
    if (!$company_id) {
        wp_send_json_error('Invalid company ID');
        return;
    }
    
    global $wpdb;
    $count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT p.ID) 
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'job_listing'
        AND p.post_status = 'publish'
        AND (
            (pm.meta_key = '_company_id' AND pm.meta_value = %d)
            OR (pm.meta_key = '_company_manager_id' AND pm.meta_value = %d)
        )
    ", $company_id, $company_id));
    
    $count = intval($count);
    
    wp_send_json_success(array('count' => $count));
}



// Dans Jobiizy-function.php
add_filter('cariera_company_open_positions_info', 'jobiizy_fix_company_job_count_text', 999, 1);
function jobiizy_fix_company_job_count_text($text) {
    // error_log('🔵 FILTRE APPELÉ ! Texte original: ' . $text);
    
    global $post;
    
    if (!$post || $post->post_type !== 'company') {
        error_log('⚠️ Pas un post de type company');
        return $text;
    }
    
    $company_id = $post->ID;
    // error_log('🔵 Company ID: ' . $company_id);
    
    // Comptage correct
    global $wpdb;
    $count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT p.ID) 
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'job_listing'
        AND p.post_status = 'publish'
        AND (
            (pm.meta_key = '_company_id' AND pm.meta_value = %d)
            OR (pm.meta_key = '_company_manager_id' AND pm.meta_value = %d)
        )
    ", $company_id, $company_id));
    
    $count = intval($count);
    // error_log('✅ Compteur corrigé: ' . $count);
    
    // Format correct en français
    if ($count > 1) {
        return $count . ' emplois';
    } elseif ($count === 1) {
        return '1 emploi';
    } else {
        return '0 emploi';
    }
}

//-- HONEYPOT (anti-bot) -->
// Charger le JS Anti-Spam (honeypot) Jobiizy
add_action('wp_enqueue_scripts', function () {

    // 🚫 Jamais dans l’admin WP
    if ( is_admin() ) {
        return;
    }

    // 🔒 Charger UNIQUEMENT si le fichier existe
    $plugin_dir = plugin_dir_path(__FILE__);
    $plugin_url = plugin_dir_url(__FILE__);

    // ⚠️ UTILISE LE NOM RÉEL DU FICHIER (MINUSCULE RECOMMANDÉ)
    $file_rel  = 'assets/js/jobiizy-function.js';
    $file_path = $plugin_dir . $file_rel;

    if ( ! file_exists( $file_path ) ) {
        error_log('❌ JOBIIZY: JS antispam introuvable → ' . $file_path);
        return;
    }

    wp_enqueue_script(
        'jobiizy-antispam-js',
        $plugin_url . $file_rel,
        ['jquery'],
        filemtime($file_path),
        true
    );
});
// Anti-spam Cariera : hook AJAX réel
add_action('wp_ajax_nopriv_cariera_ajax_register', 'jobiizy_antispam_before_register');
add_action('wp_ajax_cariera_ajax_register', 'jobiizy_antispam_before_register');
add_action('wp_ajax_nopriv_cariera_user_registration', 'jobiizy_antispam_before_register');
add_action('wp_ajax_cariera_user_registration', 'jobiizy_antispam_before_register');

function jobiizy_antispam_before_register() {
	// error_log('🔥 jobiizy_antispam_before_register() APPELÉ');
	// error_log('[DEBUG POST] ' . print_r($_POST, true));
    // 1️⃣ Honeypot
    if (!empty($_POST['jobiizy_hp'])) {
        error_log('[JOBIIZY ANTI-SPAM] Honeypot détecté – blocage');
      wp_send_json([
    		'success' => false,
    		'message' => 'Inscription bloquée (bot détecté).'
		]);
		exit;
	}

    // 2️⃣ Email jetable
    $email = sanitize_email($_POST['register_email'] ?? '');
    $domain = strtolower(substr(strrchr($email, "@"), 1));

    $blocked_domains = get_option('jobiizy_antispam_domains', []);

    if (in_array($domain, $blocked_domains)) {
        error_log("[JOBIIZY ANTI-SPAM] Email jetable détecté : $email");
        wp_send_json([
    		'success' => false,
    		'message' => 'Adresse email non autorisée.'
		]);
		exit;

    }

    // 3️⃣ User-Agent suspect
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $blocked_ua = get_option('jobiizy_antispam_useragents', []);

    foreach ($blocked_ua as $bad) {
        if (stripos($ua, $bad) !== false) {
            error_log("[JOBIIZY ANTI-SPAM] UA bloqué ($bad) : $ua");
        	wp_send_json([
    			'success' => false,
    			'message' => 'User-Agent non autorisé.'
			]);
			exit;
		}
    }
}


// DEBUG COMPLET - Tracer toutes les infos de la page
// ✅ NOUVEAU CODE (remplacez par) :
add_action('wp', function() {
    // ❌ Ne JAMAIS exécuter dans l'admin
    if ( is_admin() ) {
        return;
    }
    
    if ( wp_doing_ajax() ) {
        return;
    }
    
    if ( is_page('job-dashboard') ) {
        return;
    }
    
    global $post;
    error_log('=====================================');
    error_log('📍 URL: ' . $_SERVER['REQUEST_URI']);
    error_log('📄 Post Type: ' . get_post_type());
    error_log('🆔 Post ID: ' . get_the_ID());
    error_log('📝 Post Title: ' . get_the_title());
    error_log('=====================================');
}, 999);

// ✅ NOUVEAU CODE (remplacez par) :
// Ce hook doit être 'wp_enqueue_scripts' pour le frontend
add_action('wp_enqueue_scripts', function() {
    // ✅ Charger uniquement sur le frontend
    if ( is_admin() ) {
        return;
    }

    $plugin_dir = plugin_dir_path(__FILE__);
    $plugin_url = plugin_dir_url(__FILE__);

    $file_rel  = 'assets/js/jobiizy-debug.js';
    $file_path = $plugin_dir . $file_rel;

    if ( ! file_exists( $file_path ) ) {
        error_log('❌ JOBIIZY DEBUG JS introuvable → ' . $file_path);
        return;
    }

    wp_enqueue_script(
        'jobiizy-debug-js',
        $plugin_url . $file_rel,
        [],
        filemtime($file_path),
        true
    );
});
add_action('wp_enqueue_scripts', function() {
        if ( is_admin() ) return;
//    if (is_page('trouver-mon-entreprise')) {
/* 
        wp_enqueue_script(
            'jobiizy-company-link',
            get_stylesheet_directory_uri() . '/assets/js/splitview-company-link.js',
            [],
            filemtime(get_stylesheet_directory() . '/assets/js/splitview-company-link.js'),
            true
        );
 */
    $plugin_dir = plugin_dir_path(__FILE__);
    $plugin_url = plugin_dir_url(__FILE__);
   	$file_rel  = 'assets/css/jobiizy-modal.css';
    $file_path = $plugin_dir . $file_rel;
        
        wp_enqueue_style(
  			'jobiizy-modal',
        	$plugin_url . $file_rel,
  			[],
        	filemtime($file_path)
		);
//    }

});

/**
 * JobiiZy – Formulaire externe BASIC par défaut
 * Appliqué uniquement à la création d’une offre
 */
add_filter(
    'job_manager_job_listing_data_fields',
    function ( $fields, $job_id = null ) {

        // ID du formulaire externe BASIC
        $EXTERNAL_BASIC_FORM_ID = 12183;

        // ⚠️ Uniquement lors de la création (pas édition)
        if ( $job_id ) {
            return $fields;
        }

        // Si aucun formulaire n'est encore défini
        if ( empty( $fields['application_form']['value'] ) ) {
            $fields['application_form']['value'] = $EXTERNAL_BASIC_FORM_ID;
        }

        return $fields;
    },
    5,
    2
);
/* ------------------------------------------------------------
 * Désactiver les e-mails natifs Cariera + WordPress
 * ------------------------------------------------------------ */

add_action('init', function() {

    // Désactive les e-mails envoyés par Cariera
    remove_all_actions('cariera_new_user_notification');
    remove_all_actions('cariera_new_user_approved_notification');
    remove_all_actions('cariera_new_user_denied_notification');

    // Désactive emails Cariera liés à l’approbation
    remove_all_actions('cariera_new_user_approve_user_approved');
    remove_all_actions('cariera_new_user_approve_user_denied');

    // Option interne Cariera : désactiver complètement les notifications
    add_filter('cariera_enable_email_notifications', '__return_false');

    // Désactiver les emails WordPress natifs si doublons
    remove_action('register_new_user', 'wp_send_new_user_notifications');
    remove_all_actions('cariera_new_user_approval_notification');
error_log("🧹 Désactivation du hook Cariera : cariera_new_user_approval_notification");

});

/* ------------------------------------------------------------
 * DEBUG – Voir quels hooks Cariera sont encore actifs
 * ------------------------------------------------------------ */

// ✅ NOUVEAU CODE (ne charger que si nécessaire) :
// Commentez ou supprimez complètement ce bloc de debug
// Si vous en avez besoin, ajoutez cette condition :
/*
add_action('init', function() {
    // ✅ Debug uniquement si paramètre GET activé
    if ( !isset($_GET['debug_cariera_hooks']) || !current_user_can('manage_options') ) {
        return;
    }
    
    $hooks = [
        'cariera_new_user_notification',
        'cariera_new_user_approved_notification',
        'cariera_new_user_denied_notification',
        'cariera_new_user_approve_user_approved',
        'cariera_new_user_approve_user_denied'
    ];

    foreach ($hooks as $hook) {
        $has_hooks = has_action($hook);
        error_log("🔍 DEBUG Cariera Hook: $hook → " . ($has_hooks ? "ACTIF ❌" : "DÉSACTIVÉ ✓"));
    }
});

/* ------------------------------------------------------------
 * Création de la table jobiizy_email_log à l’activation
 * ------------------------------------------------------------ */
register_activation_hook(__FILE__, function () {
    global $wpdb;

    $table = $wpdb->prefix . "jobiizy_email_log";
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        sent_at DATETIME NOT NULL,
        email_to VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        status VARCHAR(20) NOT NULL,
        message LONGTEXT NULL
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

function jobiizy_log_email($email_to, $subject, $type, $status, $message = null)
{
    global $wpdb;
    $table = $wpdb->prefix . "jobiizy_email_log";

    $wpdb->insert($table, [
        'sent_at' => current_time('mysql'),
        'email_to' => $email_to,
        'subject' => $subject,
        'type' => $type,
        'status' => $status,
        'message' => $message
    ]);
}

/* ------------------------------------------------------------
 * Création auto de la table jobiizy_email_log si manquante
 * ------------------------------------------------------------ */
function jobiizy_maybe_create_email_log_table() {
    global $wpdb;

    $table = $wpdb->prefix . "jobiizy_email_log";

    // Si la table existe déjà, on ne fait rien
    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table
        )
    );

    if ($exists === $table) {
        return;
    }

    // Sinon, on la crée
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        sent_at DATETIME NOT NULL,
        email_to VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        status VARCHAR(20) NOT NULL,
        message LONGTEXT NULL
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    error_log('✅ JOBIIZY: Table jobiizy_email_log créée ou mise à jour.');
}

add_action('plugins_loaded', 'jobiizy_maybe_create_email_log_table');


//  recupere tous les hook  de Cariera ( si debug a activer)
/* ------------------------------------------------------------
 * DEBUG – Voir quels hooks Cariera sont encore actifs
 * ------------------------------------------------------------ */

/* add_action('init', function() {
    $hooks = [
        'cariera_new_user_notification',
        'cariera_new_user_approved_notification',
        'cariera_new_user_denied_notification',
        'cariera_new_user_approve_user_approved',
        'cariera_new_user_approve_user_denied'
    ];

    foreach ($hooks as $hook) {
        $has_hooks = has_action($hook);

        error_log("🔍 DEBUG Cariera Hook: $hook → " . ($has_hooks ? "ACTIF ❌" : "DÉSACTIVÉ ✔"));
    }
});
add_action('init', function () {

    $patterns = [
        'cariera',
        'cariera_',
        'cariera-',
        'cariera_registration',
        'registration',
        'approval',
        'approve'
    ];

    foreach ($GLOBALS['wp_filter'] as $hook_name => $hook_obj) {

        foreach ($patterns as $p) {
            if (strpos($hook_name, $p) !== false) {

                error_log("🔎 HOOK ACTIF LIÉ A CARIERA : $hook_name");

            }
        }
    }
});
*/
/**
 * JobiiZy – Détection offre interne / externe
 */
 function jobiizy_get_internal_application_form_id() {
    return apply_filters(
        'jobiizy_internal_application_form_id',
        12181
    );
}

function jobiizy_is_external_application( $job_id ) {

    $application_form_id = absint(
        get_post_meta( $job_id, '_application_form', true )
    );

    if ( ! $application_form_id ) {
        return false; // pas de formulaire → interne
    }

    return (
        $application_form_id !== jobiizy_get_internal_application_form_id()
    );
}




add_action( 'job_listing_meta_end', function () {
    global $post;

    if ( ! $post || $post->post_type !== 'job_listing' ) {
        return;
    }

    if ( jobiizy_is_external_application( $post->ID ) ) {
        echo '<span class="jobiizy-badge jobiizy-badge-external">
                Candidature externe
              </span>';
    }
});

add_action( 'wp', function () {

    if ( ! is_singular( 'job_listing' ) ) {
        return;
    }

    $job_id = get_the_ID();

    $application_form_id = absint(
        get_post_meta( $job_id, '_application_form', true )
    );

    // ID du formulaire interne (par défaut)
    $INTERNAL_FORM_ID = 12181;

    $is_external_job = (
        $application_form_id
        && $application_form_id !== $INTERNAL_FORM_ID
    );

    if ( ! $is_external_job ) {
        return;
    }

    /**
     * 🚫 OFFRE EXTERNE
     * On supprime TOUT ce que Cariera injecte
     */
    remove_all_actions( 'job_application_start' );
    remove_all_actions( 'job_application_end' );

    remove_action(
        'job_application_start',
        'cariera_apply_with_resume'
    );

    remove_action(
        'job_application_start',
        'cariera_job_application_form'
    );

    error_log('[JOBIIZY] Offre EXTERNE → hooks Cariera désactivés');

}, 20 );






// ============================================================
// ✅ AJOUTEZ CE CODE POUR DÉBUGGER LA SAUVEGARDE DES MENUS
// (À supprimer une fois le problème résolu)
// ============================================================

/**
 * Debug temporaire : tracer les problèmes de sauvegarde menu
 * Accès : /wp-admin/nav-menus.php?debug_menu_save=1
 */
add_action('admin_init', function() {
    
    if ( ! isset($_GET['debug_menu_save']) || ! current_user_can('manage_options') ) {
        return;
    }
    
    // Capturer TOUTE sortie dans l'admin
    ob_start();
    
    add_action('shutdown', function() {
        $output = ob_get_contents();
        ob_end_clean();
        
        if ( ! empty( $output ) ) {
            error_log('========================================');
            error_log('🔴 SORTIE HTML DÉTECTÉE DANS ADMIN');
            error_log('URL : ' . $_SERVER['REQUEST_URI']);
            error_log('Contenu : ' . substr($output, 0, 500));
            error_log('========================================');
        }
        
        // Renvoyer le contenu pour ne pas casser la page
        echo $output;
    }, 999);
    
}, 1);

?>