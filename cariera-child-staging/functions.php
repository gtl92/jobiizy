<?php
/**
 * ============================================================
 *  Jobiizy — Thème enfant Cariera
 *  Version propre avec layout Jobiizy personnalisé
 *  VERSION CORRIGÉE - Sans erreurs AJAX/JSON
 * ============================================================
 */

/* ============================================================
   DEBUG JOBIIZY (DÉSACTIVÉ)
   ============================================================ 
*/
/**
 * Tout le système debug est désormais géré dans le plugin :
 * wp-content/themes/cariera-child/modules/debug/debug.php
 *
 * Ce bloc reste ici UNIQUEMENT pour empêcher les appels manquants.
 * Aucune sortie console, pas d'overlay, pas d'injection JS.
 */

// 1) Déclarer la constante debug
// define('JOBIIZY_DEBUG', false);
ini_set('error_log', __DIR__ . '/error_log');
// error_log('🔥 functions.php du thème enfant CARIERA-CHILD exécuté à ' . date('H:i:s'));


// 2) Charger le module debug
$jobiizy_debug_file = get_stylesheet_directory() . '/modules/debug/debug.php';
if (file_exists($jobiizy_debug_file)) {
     require_once $jobiizy_debug_file;
}
// Stub safe : évite erreurs sans exécuter le vrai debug
// function jobiizy_log($msg, $color = '#09f')    { /* désactivé */ }
// function jobiizy_success($msg)                { /* désactivé */ }
// function jobiizy_warn($msg)                   { /* désactivé */ }
// function jobiizy_error($msg)                  { /* désactivé */ }


/* ============================================================
   CHARGEMENT UNIFIÉ DES ASSETS
   ============================================================ */
// Charger le module anti-spam
require_once get_stylesheet_directory() . '/anti-spam.php';

function jobiizy_should_load_cvform_script() {
    return is_singular('job_listing');
}
function jobiizy_should_load_job_global() {
    return is_singular('job_listing');
}
function jobiizy_should_load_job_list_scripts() {
   $is_split_view = (
        (is_page() && isset($post->post_name) && strpos($post->post_name, 'jobs-s-') === 0) ||
        is_page(['jobs-split-view','jobs-split-view-2','jobs-s-jobiizy','jobiizy-offres']) ||
        is_post_type_archive('job_listing') ||
        is_tax(['job_listing_category','job_listing_tag'])
    );

    return $is_split_view;
}
// Dans le functions.php de votre thème enfant
add_action( 'wp_enqueue_scripts', 'jobiizy_force_child_style', 999 );

function jobiizy_force_child_style() {
       $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();
       /* ========== 1. STYLES PARENT ET ENFANT (OBLIGATOIRES) ========== */
    wp_enqueue_style(
        'cariera-parent-style',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme(get_template())->get('Version')
    );
       // On décharge puis recharge le style pour qu'il soit tout en bas du 
    wp_dequeue_style( 'cariera-child-style' );
     wp_enqueue_style( 'cariera-child-style', get_stylesheet_uri(), array(), time() );
}

function cariera_child_enqueue_assets() {
    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();
    
     
    /* ========== 2. CSS PERSONNALISÉS ========== */
    $css_files = [
        'cv-form-style'         => '/assets/css/cv_form_styles_final.css',
        'jobiizy-overview'      => '/assets/css/jobiizy-overview.css',
        // 'jobiizy-mobile-header' => '/assets/css/jobiizy-mobile-header-fix.css',
    ];
    
    foreach ($css_files as $handle => $file) {
        $file_path = $theme_dir . $file;
        if (file_exists($file_path)) {
            wp_enqueue_style($handle, $theme_uri . $file, [], filemtime($file_path));
        }
    }
    
    /* ========== 3. SCRIPTS JAVASCRIPT ========== */
    $js_files = [
        'jobiizy-global'            => '/assets/js/jobiizy-global.js',
        'resume-filters'            => '/assets/js/resume-filters.js',
        'job-filters'               => '/assets/js/job-filters.js',
        'jobiizy-custom'            => '/assets/js/custom_with_scroll.js',
        'jobiizy-listingSearchForm' => '/cariera_core/elements/listing-search/jobiizy-listingSearchForm.js',
        'jobiizy-title-capitalize'  => '/assets/js/title-capitalize.js',
        'jobiizy-random-company-bg' => '/assets/js/random-company-bg.js',
        // 'jobiizy-mobile-header-js'  => '/assets/js/jobiizy-mobile-header-fix.js',
        'jobiizy-company-link'       => '/assets/js/splitview-company-link.js',
    ];
    
    foreach ($js_files as $handle => $file) {
        $file_path = $theme_dir . $file;
        if (file_exists($file_path)) {
            wp_enqueue_script($handle, $theme_uri . $file, ['jquery'], filemtime($file_path), true);
        }
    }
    
    /* ========== 4. STYLE DE RECHERCHE CARIERA (CONDITIONNEL) ========== */
    if (!wp_style_is('cariera-search-forms', 'enqueued') && !wp_style_is('cariera-search-forms', 'registered')) {
        $search_css = get_template_directory() . '/assets/dist/css/wpjm-search-forms.css';
        if (file_exists($search_css)) {
            wp_enqueue_style('cariera-search-forms', get_template_directory_uri() . '/assets/dist/css/wpjm-search-forms.css', [], filemtime($search_css));
        }
    }
}
// Priorité 20 : après le parent (10) mais avant les plugins (100+)
add_action('wp_enqueue_scripts', 'cariera_child_enqueue_assets', 20);
add_action('wp_head', function () {
    // Admin ?
    $is_admin = current_user_can('administrator');

    // Debug actif ?
    $debug_on = defined('JOBIIZY_DEBUG') && JOBIIZY_DEBUG === true;

    ?>
    <script>
        window.jobiizyDebug = <?php echo ($is_admin && $debug_on) ? 'true' : 'false'; ?>;
        console.log('🔧 jobiizyDebug =', window.jobiizyDebug);
    </script>
    <?php
});
add_action('wp_enqueue_scripts', function() {
    // Supprime l'ancien enregistrement si déjà présent
    wp_dequeue_style('jobiizy-mobile-header');
    wp_deregister_style('jobiizy-mobile-header');

    // Recharge APRÈS le frontend du thème Cariera
    wp_enqueue_style(
        'jobiizy-mobile-header-fix',
        get_stylesheet_directory_uri() . '/assets/css/jobiizy-mobile-header-fix.css',
        ['cariera-frontend'], // dépend de frontend.css => se charge après
        filemtime(get_stylesheet_directory() . '/assets/css/jobiizy-mobile-header-fix.css')
    );
}, 99);


/* 
add_action('wp_enqueue_scripts', function() {

//    if (is_page('company-split-view')) {
        wp_enqueue_script(
            'jobiizy-company-link',
            get_stylesheet_directory_uri() . '/assets/js/splitview-company-link.js',
            [],
            filemtime(get_stylesheet_directory() . '/assets/js/splitview-company-link.js'),
            true
        );
//    }

});
 */

/* ============================================================
   LAYOUT JOBIIZY - GESTION DU TEMPLATE ET DE LA LOGIQUE
   ============================================================ */

/**
 * 🎯 Faire hériter Jobiizy du comportement v1
 */
add_filter('cariera_single_job_layout', function($layout) {
    if ($layout === 'jobiizy') {
        jobiizy_log('Layout jobiizy → hérite de la logique v1');
        return 'v1';
    }
    return $layout;
}, 0);

/**
 * 📄 Charger le template custom jobiizy
 */
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

/**
 * 🔧 Fonction helper pour détecter si le layout Jobiizy est actif
 */
if (!function_exists('jobiizy_is_layout_active')) {
    function jobiizy_is_layout_active(): bool {
        return is_singular('job_listing')
            && function_exists('cariera_single_job_layout')
            && cariera_single_job_layout() === 'jobiizy';
    }
}

/**
 * 🏢 Forcer l'affichage du bloc "Company Info"
 */
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

/**
 * 📝 Forcer l'affichage du bouton "Postuler" - CORRIGÉ
 */
add_action('single_job_listing_meta_end', function() {
    // Autoriser l'affichage dans les vues split-view
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

    // Charger le template du bouton postuler
    $tpl = get_template_directory() . '/job_manager/single-job/single-job-application.php';
    if (file_exists($tpl)) {
        include $tpl;
    } elseif (current_user_can('administrator') && !wp_doing_ajax()) {
        echo '<div style="color:red">⚠️ Fichier manquant : ' . esc_html($tpl) . '</div>';
    }

    wp_reset_postdata();
    $post = $prev_post;
}, 20);


/**
 * 🧩 Correctif AJAX Jobiizy - VERSION FINALE CORRIGÉE
 * Injection du bouton "Postuler" dans les vues SPLIT-VIEW
 * SANS CASSER DEBUG LOG MANAGER
 */

// ✅ MÉTHODE 1 : Utiliser un hook AJAX spécifique au lieu de shutdown
add_action('wp_ajax_cariera_load_single_job_ajax', 'jobiizy_inject_button_ajax', 999);
add_action('wp_ajax_nopriv_cariera_load_single_job_ajax', 'jobiizy_inject_button_ajax', 999);

function jobiizy_inject_button_ajax() {
    // Démarrer la capture de sortie
    ob_start();
    
    error_log('[Jobiizy] Hook AJAX cariera_load_single_job_ajax');
    
    // Laisser les autres hooks s'exécuter d'abord
    // (on ne fait rien ici, juste préparer la capture)
}

// ✅ MÉTHODE 2 : Modifier la sortie avec un filtre au lieu de shutdown
add_filter('cariera_single_job_ajax_output', 'jobiizy_inject_button_in_output', 999);
function jobiizy_inject_button_in_output($output) {
    error_log('[Jobiizy] Filtre cariera_single_job_ajax_output');
    
    // Si déjà un bouton → ne rien faire
    if (strpos($output, 'application_button') !== false) {
        error_log('[Jobiizy] Bouton déjà présent');
        return $output;
    }
    
    // Charger le template du bouton
    $tpl = get_template_directory() . '/job_manager/single-job/single-job-application.php';
    if (!file_exists($tpl)) {
        error_log('[Jobiizy] Template non trouvé');
        return $output;
    }
    
    ob_start();
    include $tpl;
    $button_html = ob_get_clean();
    
    // Injection
    if (strpos($output, '</aside>') !== false) {
        $output = str_replace('</aside>', $button_html . '</aside>', $output);
        error_log('[Jobiizy] Bouton injecté avant </aside>');
    } else {
        $output .= "\n<!-- Jobiizy Postuler -->\n" . $button_html;
        error_log('[Jobiizy] Bouton injecté à la fin');
    }
    
    return $output;
}

// ✅ MÉTHODE 3 (BACKUP) : Shutdown avec priority haute pour s'exécuter APRÈS Debug Log Manager
add_action('shutdown', function() {
    // ✅ Ne s'exécute que pour les requêtes AJAX Cariera
    if (empty($_POST['action']) || $_POST['action'] !== 'cariera_load_single_job_ajax') {
        return;
    }

    // Vérifier si on a déjà traité via les méthodes 1 ou 2
    static $already_processed = false;
    if ($already_processed) {
        return;
    }
    $already_processed = true;

    error_log('[Jobiizy] Shutdown hook - AJAX détecté');

    // Récupère la sortie
    $final_output = ob_get_contents();
    if (empty($final_output)) {
        error_log('[Jobiizy] Shutdown - Output vide');
        return;
    }

    // Si déjà un bouton → ne rien faire
    if (strpos($final_output, 'application_button') !== false) {
        error_log('[Jobiizy] Shutdown - Bouton déjà présent');
        return;
    }

    error_log('[Jobiizy] Shutdown - Injection du bouton');

    // Charge le template
    $tpl = get_template_directory() . '/job_manager/single-job/single-job-application.php';
    if (!file_exists($tpl)) {
        error_log('[Jobiizy] Shutdown - Template non trouvé');
        return;
    }

    ob_start();
    include $tpl;
    $button_html = ob_get_clean();

    // Injection
    if (strpos($final_output, '</aside>') !== false) {
        $final_output = str_replace('</aside>', $button_html . '</aside>', $final_output);
    } else {
        $final_output .= "\n<!-- Jobiizy Postuler via shutdown -->\n" . $button_html;
    }

    // ⚠️ NE PAS utiliser wp_die() - laisser WordPress terminer normalement
    // wp_die(); // ❌ SUPPRIMÉ
    
    // À la place, remplacer le contenu du buffer
    if (ob_get_level() > 0) {
        ob_clean(); // Vider le contenu actuel
        echo $final_output; // Mettre le nouveau contenu
    }
    
    error_log('[Jobiizy] Shutdown - Bouton injecté avec succès');
    
}, 999); // Priority 999 pour s'exécuter APRÈS Debug Log Manager


/**
 * 🎨 Ajouter l'option "Version 4 — Jobiizy" dans les réglages Cariera
 */
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

/* ============================================================
   STYLES ET SCRIPTS
   ============================================================ */
/**
 * 📄 Charger le script de remplacement #cv-form
 * 
 * IMPORTANT : Ce script doit se charger sur les pages de liste (split-view)
 * mais PAS sur les pages individuelles d'offres (single job)
 */
/**
 * 📄 Charger le script de remplacement #cv-form (avec traçage complet)
 */
add_action('wp_enqueue_scripts', 'jobiizy_enqueue_cvform_script',20);

function jobiizy_enqueue_cvform_script() {

    // 🔍 Trace 1 — exécution de la fonction
    if ( current_user_can('administrator') ) {
        error_log('[Jobiizy] → jobiizy_enqueue_cvform_script() appelée sur ' . $_SERVER['REQUEST_URI']);
    }
    if (!jobiizy_should_load_cvform_script()) {
       if ( current_user_can('administrator') ) {
            error_log('[Jobiizy] → Pas une page split-view (' . $_SERVER['REQUEST_URI'] . ')');
        }
        return;
    }


    // ✅ Forcer temporairement le chargement pour debug
    // (remet le if is_user_logged_in() plus tard)
    // if (is_user_logged_in()) return;

    // ✅ Pages concernées (slug dynamique)
    global $post;
/*    $is_split_view = (
        (is_page() && isset($post->post_name) && strpos($post->post_name, 'jobs-s-') === 0) ||
        is_page(['jobs-split-view','jobs-split-view-2','jobs-s-jobiizy','jobiizy-offres']) ||
        is_post_type_archive('job_listing') ||
        is_tax(['job_listing_category','job_listing_tag'])
    );

    if ( ! $is_split_view ) {
        if ( current_user_can('administrator') ) {
            error_log('[Jobiizy] → Pas une page split-view (' . $_SERVER['REQUEST_URI'] . ')');
        }
        return;
    }
*/
    // ✅ Trace 2 — avant enqueue
    if ( current_user_can('administrator') ) {
        error_log('[Jobiizy] → wp_enqueue_script(jobiizy-replace-cvform)');
    }
     $rel = '/assets/js/replace-cvform.js';
    $js_path = get_stylesheet_directory() . $rel;
    $js_uri  = get_stylesheet_directory_uri() . $rel;

    if (!file_exists($js_path)) {
        return;
    }
  ?>
  <!-- DEBUG jobiizy_enqueue_cvform_script
      <?php echo esc_html('GTLGTL00' . $rel); ?>
  -->
<?php

	wp_enqueue_script(
		'jobiizy-replace-cvform',
		$js_uri,
		['jquery'],
		filemtime($js_path),
		true
	);

    // ✅ Trace 3 — confirmation visuelle + console
    if ( current_user_can('administrator') ) {
            add_action('wp_footer', function() {
        		echo '<div style="position:fixed;bottom:10px;right:10px;background:#222;color:#0f0;
        			padding:6px 10px;font-family:monospace;font-size:12px;z-index:9999;border-radius:4px;">
        			✅ jobiizy-replace-cvform.js en file d’attente</div>';
        		jobiizy_success('jobiizy-replace-cvform.js enqueued ✅');
    		}, 99);
	}
}


/**
 * JS Split-view (connecté) : injecter le bouton Postuler si absent
 */
/* function jobiizy_enqueue_splitview_apply_script() {
    // On ne charge le script que si l'utilisateur est connecté
    if ( ! is_user_logged_in() ) return;

    // Pages concernées : split-view et équivalentes
    $is_split_view = is_page('jobs-split-view') || is_page('jobs-split-view-2') || is_page('jobs-s-jobiizy') || is_page('emplois');    
   // $is_split_view = is_page('jobs-split-view') || is_page('jobs-split-view-2') || is_page('jobs-s-jobiizy') || is_page('jobiizy-offres'|| is_page('jobiizy-offres');    
    if ( ! $is_split_view ) return;

 	$rel = '/assets/js/splitview-apply.js';

	$js_path = get_stylesheet_directory() . $rel;
	$js_uri  = get_stylesheet_directory_uri() . $rel;
   // Enqueue du script JS
  ?>
  <!-- DEBUG jobiizy_enqueue_splitview_apply_script
      <?php echo esc_html('GTLGTLa00' . $rel); ?>
  -->
<?php
	wp_enqueue_script(
		'jobiizy-splitview-apply',
		$js_uri,
		['jquery'],
		filemtime($js_path),
		true
	);

	// Debug : confirmer le chargement dans la console
	if ( current_user_can('administrator') ) {
   	 add_action('wp_footer', function () {
        if (function_exists('jobiizy_success')) {
            jobiizy_success('splitview-apply.js chargé ✅');
        } else {
            // ✅ Version safe (pas de echo <script> pendant les requêtes AJAX)
            if (!wp_doing_ajax()) {
                ?>
                <script>
                    if (window.JOBIIZY_DEBUG)
                        console.log('%c[Jobiizy] splitview-apply.js chargé ✅','color:#4caf50;font-weight:bold');
                </script>
                <?php
            } else {
                error_log('[Jobiizy] splitview-apply.js chargé ✅ (AJAX)');
            }
        }
    	}, 1);
	}
} 
// add_action('wp_enqueue_scripts', 'jobiizy_enqueue_splitview_apply_script');
*/


add_action('wp_enqueue_scripts', function () {

    // Page split-view
    // if ( ! is_page('emplois') ) return;
 // uniquement sur la page split / emplois
    if (!is_page('emplois') && !is_post_type_archive('job_listing')) {
        return;
    }

    // 1) Charger redirect.js
    $rel = '/assets/js/splitview-redirect.js';
    $path = get_stylesheet_directory() . $rel;
    $uri  = get_stylesheet_directory_uri() . $rel;

    if ( file_exists($path) ) {
        wp_enqueue_script(
            'jobiizy-splitview-redirect',
            $uri,
            [],
            filemtime($path),
            true
        );
    }

    // 2) IMPORTANT : empêcher tes scripts "company-link" et "global popup" de polluer le split-view
    // --> à faire en désenregistrant si tu les enqueues avec des handles connus.
    // Exemples (adapte les handles réels) :
    wp_dequeue_script('jobiizy-splitview-company-link');
    wp_deregister_script('jobiizy-splitview-company-link');

    wp_dequeue_script('jobiizy-global');
    wp_deregister_script('jobiizy-global');
}, 100);

/* 
function jobiizy_enqueue_splitview_redirect_script() {

    // Pages concernées : split-view
    $is_split_view =
        is_page('emplois') ||
        is_page('jobs-split-view') ||
        is_page('jobs-split-view-2') ||
        is_page('jobs-s-jobiizy');

    if ( ! $is_split_view ) {
        return;
    }

    $rel = '/assets/js/splitview-redirect.js';
    $js_path = get_stylesheet_directory() . $rel;
    $js_uri  = get_stylesheet_directory_uri() . $rel;

    if ( ! file_exists($js_path) ) {
        error_log('[Jobiizy] splitview-redirect.js introuvable');
        return;
    }

    wp_enqueue_script(
        'jobiizy-splitview-redirect',
        $js_uri,
        ['jquery'],
        filemtime($js_path),
        true
    );

    // Debug console (admin only)
    if ( current_user_can('administrator') && ! wp_doing_ajax() ) {
        add_action('wp_footer', function () {
            ?>
            <script>
                console.log(
                  '%c[Jobiizy] splitview-redirect.js chargé ✅',
                  'color:#4caf50;font-weight:bold'
                );
            </script>
            <?php
        }, 1);
    }
}
add_action('wp_enqueue_scripts', 'jobiizy_enqueue_splitview_redirect_script', 20); */
/**
 * Charger la police Inter depuis Google Fonts
 */
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('inter-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', [], null);
});

/* ============================================================
   FONCTIONNALITÉS PERSONNALISÉES JOBIIZY
   ============================================================ 
*/

/**
 * 🔘 Bouton "Voir l'offre" dans les listes d'emplois
 * 
 * Ajoute un bouton sous chaque offre dans les pages d'archives/recherche
 */
add_action('job_listing_info_end', 'jobiizy_add_view_offer_button');
function jobiizy_add_view_offer_button() {
    global $post;
    echo '<a href="' . esc_url(get_the_job_permalink($post)) . '" class="btn-view-offer">' 
        . esc_html__("Voir l'offre", 'cariera') . '</a>';
}

/**
 * 💼 Type de contrat personnalisé : Télétravail
 * 
 * Ajoute "Remote Work / Télétravail" aux types de contrats disponibles
 */
add_filter('wpjm_job_listing_employment_type_options', function($types) {
    $types['REMOTE_WORK'] = esc_html__('Remote Work', 'wp-job-manager');
    // Traduction française
    if (get_locale() === 'fr_FR') {
        $types['REMOTE_WORK'] = 'Télétravail';
    }
    return $types;
});

/**
 * 📧 Email de notification de candidature personnalisé
 * 
 * Surcharge l'email par défaut envoyé à l'employeur lors d'une candidature
 * pour le rendre plus attractif et informatif
 */
add_action('plugins_loaded', function() {
    add_filter('job_application_notification_message', 'custom_job_application_notification_message', 99, 5);
});

function custom_job_application_notification_message($message, $application, $job_id, $candidate_name, $candidate_email) {
    $job_title = get_the_title($job_id);
    $candidate_message = get_post_meta($application->ID, '_candidate_message', true);
    $cv_url = get_post_meta($application->ID, '_candidate_cv', true);
    
    ob_start(); ?>
    <html>
    <body style="font-family: Arial, sans-serif; color: #333;">
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:auto;">
            <tr>
                <td style="padding:20px;background-color:#f8f8f8;">
                    <h2 style="color:#2c3e50;">🎉 Nouvelle candidature reçue</h2>
                    <p>Vous avez reçu une nouvelle candidature pour :</p>
                    <h3 style="color:#3498db;"><?php echo esc_html($job_title); ?></h3>
                    <p><strong>Nom :</strong> <?php echo esc_html($candidate_name); ?></p>
                    <p><strong>Email :</strong> <?php echo esc_html($candidate_email); ?></p>
                    <?php if ($candidate_message): ?>
                        <p><strong>Message :</strong><br><?php echo nl2br(esc_html($candidate_message)); ?></p>
                    <?php endif; ?>
                    <?php if ($cv_url): ?>
                        <p><strong>CV :</strong> <a href="<?php echo esc_url($cv_url); ?>" target="_blank">Télécharger</a></p>
                    <?php endif; ?>
                    <p>
                        <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" 
                           style="background-color:#3498db;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;">
                            Voir sur le tableau de bord
                        </a>
                    </p>
                </td>
            </tr>
        </table>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * 🏷️ Champ de recherche par tags
 * 
 * Ajoute un sélecteur de tags dans le formulaire de recherche d'emplois
 */
add_action('job_manager_job_filters_search_jobs_end', 'cariera_add_search_tags_field');
function cariera_add_search_tags_field($atts) {
    $tags = get_terms(['taxonomy' => 'job_listing_tag', 'hide_empty' => false]);
    if (empty($tags) || is_wp_error($tags)) return;

    echo '<div class="search_tag_list search_categories">';
    echo '<label>Tags</label>';
    echo '<select name="search_keywords[]" multiple class="cariera-select2-search" data-placeholder="Choisissez un ou plusieurs tags">';
    foreach ($tags as $tag) {
        echo '<option value="' . esc_attr($tag->name) . '">' . esc_html($tag->name) . '</option>';
    }
    echo '</select></div>';
}

/* ============================================================
   PROTECTION DE LA CVTHÈQUE
   ============================================================ */

/**
 * 🔒 Modifier le lien du menu CVthèque selon les droits
 * 
 * Seuls les administrateurs, employer-plus et employer avec package actif
 * peuvent accéder à la CVthèque complète
 */
add_filter('nav_menu_link_attributes', 'jobiizy_filter_cvtheque_menu_link', 10, 3);
function jobiizy_filter_cvtheque_menu_link($atts, $item, $args) {
    // ID du menu item CVthèque (à adapter selon votre configuration)
    if ($args->theme_location === 'primary' && $item->ID === 13414) {
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        
        // Vérifier les accès
        $has_access = in_array('administrator', $roles) || in_array('employer-plus', $roles);

        // Les employeurs avec package actif ont aussi accès
        if (!$has_access && in_array('employer', $roles)) {
            if (function_exists('job_package_is_active') && job_package_is_active($user->ID)) {
                $has_access = true;
            }
        }

        if ($has_access) {
            $atts['href'] = home_url('/jobiizy-profils/');
        }
    }
    return $atts;
}

/**
 * 🚪 Redirection pour protéger la page CVthèque
 * 
 * Redirige les utilisateurs non autorisés vers une page teaser
 */
add_action('template_redirect', 'jobiizy_protected_cvtheque_page');
function jobiizy_protected_cvtheque_page() {
    if (is_page('jobiizy-profils')) {
        // Les non-connectés vont sur la page teaser
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/jobiizy-profils-future/'));
            exit;
        }

        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;

        // Récupérer les rôles autorisés depuis les options
        $allowed_roles = get_option('jobiizy_private_access_roles', []);
        if (!is_array($allowed_roles)) {
            $allowed_roles = [$allowed_roles];
        }

        $has_access = (bool) array_intersect($user_roles, $allowed_roles);

        // Vérifier si l'employeur a un package actif
        if (!$has_access && in_array('employer', $user_roles)) {
            if (function_exists('job_package_is_active') && job_package_is_active($user->ID)) {
                $has_access = true;
            }
        }

        // Rediriger si pas d'accès
        if (!$has_access) {
            wp_redirect(home_url('/jobiizy-profils-future/'));
            exit;
        }
    }
}

/**
 * 📦 Vérifier si un utilisateur a un package job actif
 * 
 * Parcourt les commandes WooCommerce pour trouver un job package valide
 * 
 * @param int $user_id ID de l'utilisateur
 * @return bool True si un package actif existe
 */
function has_active_job_package($user_id) {
    if (!class_exists('WooCommerce')) return false;

    $orders = wc_get_orders([
        'customer_id' => $user_id,
        'status' => ['completed', 'processing'],
        'limit' => -1,
    ]);

    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product || $product->get_type() !== 'job_package') continue;

            // Chercher les packages liés à cette commande
            $packages = get_posts([
                'post_type' => 'job_package',
                'post_status' => 'publish',
                'author' => $user_id,
                'posts_per_page' => -1,
                'meta_query' => [
                    ['key' => '_order_id', 'value' => $order->get_id(), 'compare' => '='],
                    ['key' => '_product_id', 'value' => $product->get_id(), 'compare' => '='],
                ],
            ]);

            // Vérifier la date d'expiration
            foreach ($packages as $package) {
                $expiry = get_post_meta($package->ID, '_package_expiry', true);
                if (!$expiry || strtotime($expiry) >= current_time('timestamp')) {
                    return true;
                }
            }

            // Si pas de package trouvé, considérer comme actif (nouveau package)
            if (empty($packages)) return true;
        }
    }
    return false;
}

/* ============================================================
   MENUS DYNAMIQUES SELON LE RÔLE
   ============================================================ */

/**
 * 📋 Enregistrer les emplacements de menus
 */
function jobiizy_register_menus() {
    register_nav_menus([
        'menu_admin' => __('Menu Admin'),
        'menu_employeur' => __('Menu Employeur'),
        'menu_candidat' => __('Menu Candidat'),
        'menu_default' => __('Menu Public'),
    ]);
}
add_action('init', 'jobiizy_register_menus');

/**
 * 🔄 Changer le menu principal selon le rôle de l'utilisateur
 */
add_filter('wp_nav_menu_args', function($args) {
    if (isset($args['theme_location']) && $args['theme_location'] === 'primary') {
        $user = wp_get_current_user();
        $menu_location = 'menu_default';

        if (in_array('administrator', (array) $user->roles)) {
            $menu_location = 'menu_admin';
        } elseif (in_array('employer', (array) $user->roles)) {
            $menu_location = 'menu_employeur';
        } elseif (in_array('candidate', (array) $user->roles)) {
            $menu_location = 'menu_candidat';
        }
        
        $args['theme_location'] = $menu_location;
    }
    return $args;
}, 10);

/* ============================================================
   SHORTCODES ET HOOKS ADDITIONNELS
   ============================================================ */

/**
 * 📄 Shortcode [jobiizy_cv_form] - Formulaire CV pour non-connectés
 * 
 * Usage : [jobiizy_cv_form job_id="123"]
 */
add_shortcode('jobiizy_cv_form', function($atts = []) {
    $atts = shortcode_atts([
        'job_id' => get_the_ID(),
        'title' => 'Envoyer votre CV'
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

/**
 * 📌 Ajouter le titre du job dans le widget overview (sidebar)
 */
add_action('single_job_listing_meta_start', 'jobiizy_add_title_in_job_overview');
function jobiizy_add_title_in_job_overview() {
    if (is_singular('job_listing')) {
        echo '<div class="single-job-overview-detail job-overview-title">';
        echo '<div class="content">';
        echo '<h2 class="job-overview-title-text">' . esc_html(get_the_title()) . '</h2>';
        echo '</div></div>';
    }
}

/**
 * 🏷️ Ajouter le titre du job avant le bloc entreprise
 */
add_action('single_job_listing_start', 'jobiizy_add_job_title_before_company', 25);
function jobiizy_add_job_title_before_company() {
    if (is_singular('job_listing')) {
        echo '<h2 class="job-main-title">' . esc_html(get_the_title()) . '</h2>';
    }
}

/* ============================================================
   POPUP LOGIN POUR UTILISATEURS NON CONNECTÉS
   ============================================================ */
/**
 * 🔒 Injecter le HTML de la popup dans le footer
 */
add_action('wp_footer', 'jobiizy_render_login_popup');
function jobiizy_render_login_popup() {
    // Ne pas afficher pour les utilisateurs connectés
    if (is_user_logged_in()) return;
    ?>
    <div id="jobiizy-login-popup-overlay">
        <div class="jobiizy-popup-box">
            
            <!-- Bouton fermer -->
            <button class="jobiizy-popup-close" aria-label="Fermer">×</button>

            <!-- Contenu -->
            <div style="text-align:center;">
                <span class="jobiizy-popup-emoji">🔓</span>
                
                <h2>Connectez-vous pour postuler</h2>
                
                <p>
                    Pour postuler à cette offre, vous devez être inscrit et connecté à votre compte Jobiizy.
                </p>

                <!-- Boutons -->
                <div class="jobiizy-popup-actions">
                    <!-- Bouton principal -->
                    <a href="<?php echo esc_url(home_url('/pages/connexion-inscription/')); ?>" 
                       id="jobiizy-login-confirm"
                       class="jobiizy-popup-btn-primary">
                        Se connecter / S'inscrire
                    </a>

                    <!-- Boutons secondaires sur une ligne -->
                    <div class="jobiizy-popup-secondary-row">
                        <a href="<?php echo esc_url(home_url('/pages/devenir-candidat/')); ?>" 
                           id="jobiizy-become-candidate"
                           class="jobiizy-popup-btn-secondary">
                            👤 Candidat
                        </a>

                        <a href="<?php echo esc_url(home_url('/pages/devenir-employeur/')); ?>" 
                           id="jobiizy-become-employer"
                           class="jobiizy-popup-btn-secondary">
                            💼 Employeur
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * 🔒 Script popup login
 */
function jobiizy_enqueue_popup_script() {
    if (is_user_logged_in()) return;

    wp_enqueue_style(
        'jobiizy-popup-login',
        get_stylesheet_directory_uri() . '/assets/css/jobiizy-popup-login.css',
        [],
        filemtime(get_stylesheet_directory() . '/assets/css/jobiizy-popup-login.css')
    );

    wp_enqueue_script(
        'jobiizy-popup-login',
        get_stylesheet_directory_uri() . '/assets/js/popup-login.js',
        [],
        '1.0.3',
        true
    );

    // Passer les URLs PHP → JS (pour compatibilité)
    wp_localize_script('jobiizy-popup-login', 'jobiizyPopupRoutes', [
        'login'     => esc_url(home_url('/pages/connexion-inscription/')),
        'candidate' => esc_url(home_url('/pages/devenir-candidat/')),
        'employer'  => esc_url(home_url('/pages/devenir-employeur/')),
    ]);
}
add_action('wp_enqueue_scripts', 'jobiizy_enqueue_popup_script');




function jobiizy_enqueue_halfajax_button_script() {
    global $post;

    $is_split_view = (
        (is_page() && isset($post->post_name) && strpos($post->post_name, 'jobs-s-') === 0)
        || is_page(['jobs-split-view', 'jobs-split-view-2', 'jobiizy-offres'])
    );

    if (!$is_split_view) return;

    $js_uri  = get_stylesheet_directory_uri() . '/assets/js/halfajax-button.js';
    $js_path = get_stylesheet_directory()     . '/assets/js/halfajax-button.js';

    if (file_exists($js_path)) {
        wp_enqueue_script(
            'jobiizy-halfajax-button',
            $js_uri,
            ['jquery'],
            filemtime($js_path),
            true
        );
    }

    // Debug
    if (current_user_can('administrator')) {
        add_action('wp_footer', function () {
            echo '<div style="position:fixed;bottom:20px;right:20px;background:#111;color:#0f0;
            padding:6px 10px;border-radius:4px;font-size:13px;z-index:9999;">
            ✅ halfajax-button.js chargé</div>';
        });
    }
}
add_action('wp_enqueue_scripts', 'jobiizy_enqueue_halfajax_button_script');

/**
 * 🔄 Popups combinées : Jobiizy + Cariera
 * ---------------------------------------------------------
 * Objectif : garder la popup Jobiizy (visuelle)
 * et restaurer le bloc officiel Cariera (#login-register-popup)
 * pour les formulaires complets Login / Register / Forgot Password
 */

add_action('wp_footer', function() {
    // Pages sur lesquelles on veut la popup
    if ( ! is_page( ['devenir-employeur','devenir-candidat','connexion-inscription'] ) ) return;

    // Si l’utilisateur est connecté → redirige vers la page définie dans le thème (ou fallback)
    if ( is_user_logged_in() ) {
        $login_page_id = get_option('cariera_login_register_page'); // option Cariera
        $redirect_url  = $login_page_id ? get_permalink($login_page_id) : home_url('/dashboard/');
        if ( $redirect_url ) {
            wp_safe_redirect( $redirect_url );
            exit;
        }
        return;
    }

    // 1) Méthode standard : utiliser la fonction Cariera si elle existe
    if ( function_exists('cariera_login_register_popup') ) {
        cariera_login_register_popup();
        if ( current_user_can('administrator') ) {
            jobiizy_success('Popup Cariera injectée via fonction ✅');
        }
        return;
    }

    // 2) Fallback : inclure le template directement
    //    - priorité au CHILD THEME : /cariera-child/templates/popups/login-register.php
    //    - sinon PARENT THEME     : /cariera/templates/popups/login-register.php
    $child_tpl  = trailingslashit( get_stylesheet_directory() ) . 'templates/popups/login-register.php';
    $parent_tpl = trailingslashit( get_template_directory() )   . 'templates/popups/login-register.php';

    if ( file_exists( $child_tpl ) ) {
        include $child_tpl;
        if ( current_user_can('administrator') ) {
           jobiizy_success('Popup injectée via template enfant ✅');
        }
        return;
    }

    if ( file_exists( $parent_tpl ) ) {
        include $parent_tpl;
        if ( current_user_can('administrator') ) {
            jobiizy_success('Popup injectée via template parent ✅');
        }
        return;
    }

    // 3) Rien trouvé
    if ( current_user_can('administrator') ) {
        jobiizy_error('Impossible d’injecter #login-register-popup (template introuvable)');
    }
}, 100);

/**
 * ============================================================
 * JOBIIZY - OPTIMISATION HEADER MOBILE
 * À ajouter dans functions.php de votre thème enfant
 * ============================================================
 */
/**
 * Ajouter une classe body pour identifier le mode mobile
 */
function jobiizy_mobile_body_class($classes) {
    // Détection simple basée sur le user agent (optionnel)
    // Vous pouvez aussi le gérer uniquement en CSS avec les media queries
    if (wp_is_mobile()) {
        $classes[] = 'jobiizy-mobile-device';
    }
    return $classes;
}
add_filter('body_class', 'jobiizy_mobile_body_class');

/**
 * Ajuster la configuration du header sticky pour mobile
 * (si Cariera a des options spécifiques)
 */
function jobiizy_customize_sticky_header() {
    // S'assurer que le header sticky est activé pour mobile
    if (function_exists('cariera_get_option')) {
        // Forcer l'activation du sticky header mobile si ce n'est pas déjà fait
        add_filter('cariera_sticky_mobile_header', '__return_true');
    }
}
add_action('init', 'jobiizy_customize_sticky_header');

/**
 * Message de confirmation pour les administrateurs (debug)
 */
if (defined('JOBIIZY_DEBUG') && JOBIIZY_DEBUG) {
    add_action('wp_footer', function() {
        if (current_user_can('administrator')) {
            ?>
            <script>
                if (window.innerWidth <= 991 && window.JOBIIZY_DEBUG) {
                    console.log('%c✅ Jobiizy Mobile Header actif', 'color: #00c853; font-weight: bold; font-size: 14px;');
                    console.log('📱 Largeur écran:', window.innerWidth + 'px');
                    console.log('🎨 CSS chargé:', document.querySelector('link[href*="jobiizy-mobile-header"]') ? 'OUI' : 'NON');
                    console.log('⚡ JS chargé:', typeof window.jobiizyMobileHeader !== 'undefined' ? 'OUI' : 'NON');
                }
            </script>
            <?php
        }
    }, 9999);
}

/*
 *  ============================================================
 *  DIAGNOSTIC GÉNÉRAL
 * ============================================================ 
*/
add_shortcode('jobiizy_mobile_header', function() {
    ob_start();
    include get_stylesheet_directory() . '/templates/header-mobile-test.php';
    return ob_get_clean();
});

/* 
* 🎯 Version courte (test rapide)
* Si vous voulez juste tester rapidement :
* TEST RAPIDE
*/
add_action('admin_init', 'jobiizy_quick_test');
function jobiizy_quick_test() {
    if (isset($_GET['test_logo'])) {
        $job_id = isset($_GET['job_id']) ? absint($_GET['job_id']) : 123;
        $post = get_post($job_id);
        
        echo '<pre>';
        echo 'Job ID: ' . $job_id . "\n";
        echo 'Logo avec $post: ' . get_the_company_logo($post, 'thumbnail') . "\n";
        echo 'Logo sans $post: ' . get_the_company_logo(null, 'thumbnail') . "\n";
        echo '</pre>';
        exit;
    }
}
/**
 * Fix Cariera : forcer l’enregistrement de _company_id lors de la sauvegarde admin
 */
add_action('save_post_job_listing', 'jobiizy_fix_company_id_meta', 1, 3);
function jobiizy_fix_company_id_meta($post_id, $post, $update) {

    // Pas autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if ($post->post_type !== 'job_listing') return;

    // Le field Cariera envoie ceci :
    $field_key = '_company_id';
    //  error_log("📩 POST : " . print_r($_POST, true));
    // Il se trouve dans $_POST['company_id'] ou dans $_POST['_company_id']
	$company_id =
    $_POST['company_id']
    ?? $_POST['_company_id']
    ?? $_POST['_company_manager_id']                // 🔥 Nouveau Cariera
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
 * Fix : Synchronisation automatique du logo entreprise → job listing
 * Version: 1.0.0
 * 
 * PROBLÈME :
 * Dans les anciennes versions de Cariera, quand on créait/éditait un job listing
 * et qu'on associait une entreprise (company), le logo de l'entreprise était
 * automatiquement copié comme "featured image" du job.
 * 
 * Depuis une mise à jour, cette synchronisation ne se fait plus automatiquement.
 * Résultat : les jobs n'ont plus de logo.
 * 
 * SOLUTION :
 * Hook sur la sauvegarde d'un job_listing pour copier automatiquement
 * le logo de la company associée.
 */
/**
 * Cœur de la synchro : NE PAS hooker directement ici
 */
function jobiizy_sync_company_logo_core($job_id) {

    // Sécurité de base
    if (empty($job_id) || get_post_type($job_id) !== 'job_listing') {
        return;
    }

    // Récupérer la company associée
    $company_id = (int) get_post_meta($job_id, '_company_id', true);
    if (!$company_id) {
        error_log("❌ [JOBIIZY LOGO] Aucun company_id pour le job $job_id");
        return;
    }

    if (!get_post($company_id)) {
        error_log("❌ [JOBIIZY LOGO] Company $company_id inexistante (job $job_id)");
        return;
    }

    // Vérifier que la company a un logo
    if (!has_post_thumbnail($company_id)) {
        error_log("⚠️ [JOBIIZY LOGO] Company $company_id sans logo (job $job_id)");
        return;
    }

    $thumbnail_id = get_post_thumbnail_id($company_id);
    if (!$thumbnail_id) {
        error_log("⚠️ [JOBIIZY LOGO] Company $company_id : thumbnail_id introuvable (job $job_id)");
        return;
    }

    // Appliquer le logo au job
    set_post_thumbnail($job_id, $thumbnail_id);
    error_log("✅ [JOBIIZY LOGO] Logo $thumbnail_id copié → job $job_id (company $company_id)");
}

/**
 * 1) Synchro après soumission front-end (formulaire WP Job Manager / Cariera)
 */
add_action('job_manager_save_job_listing', 'jobiizy_sync_company_logo_from_front', 20, 2);
function jobiizy_sync_company_logo_from_front($job_id, $values) {
    // Ici $values peut contenir meta, mais on n’en dépend plus
    jobiizy_sync_company_logo_core($job_id);
}

/**
 * 2) Synchro depuis l’admin quand on clique sur "Mettre à jour"
 */
add_action('save_post_job_listing', 'jobiizy_sync_company_logo_from_admin', 200, 3);
function jobiizy_sync_company_logo_from_admin($post_id, $post, $update) {

    // Éviter autosave / révisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    if ($post->post_type !== 'job_listing') return;

    // On ne s'intéresse qu'aux vraies mises à jour
    if (!$update) return;

    // Empêcher une boucle infinie
    remove_action('save_post_job_listing', 'jobiizy_sync_company_logo_from_admin', 20);

    jobiizy_sync_company_logo_core($post_id);

    // Réactiver le hook
    add_action('save_post_job_listing', 'jobiizy_sync_company_logo_from_admin', 20, 3);
}


// ============================================================
// FILTRE PERSONNALISÉ POUR MASQUER LES WARNINGS SPÉCIFIQUES
// ============================================================

/**
 * Filtre qui supprime les warnings deprecated de plugins tiers
 * À ajouter dans functions.php du thème
 */
function jobiizy_suppress_deprecated_warnings($errno, $errstr, $errfile, $errline) {
    // Liste des warnings à ignorer
    $ignored_warnings = [
        'Creation of dynamic property',
        'Deprecated:',
    ];
    
    foreach ($ignored_warnings as $ignored) {
        if (strpos($errstr, $ignored) !== false) {
            // Loguer seulement si debug actif
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log("[Warning supprimé] $errstr dans $errfile ligne $errline");
            }
            return true; // Supprime l'affichage
        }
    }
    
    return false; // Laisse passer les autres erreurs
}

// Activer le filtre (à mettre dans functions.php)
set_error_handler('jobiizy_suppress_deprecated_warnings');

// Désactive le resize du logo d'entreprise (Cariera)
add_filter('cariera_company_logo_size', function() {
    return 'full'; // renvoie la version originale
});



// Charger JS & CSS Jobiizy
add_action('wp_enqueue_scripts', function () {

    // CSS cœur
    wp_enqueue_style(
        'jobiizy-bookmark-css',
        get_stylesheet_directory_uri() . '/assets/css/jobiizy-bookmark.css',
        [],
        filemtime(get_stylesheet_directory() . '/assets/css/jobiizy-bookmark.css')
    );

    // JS cœur
    wp_enqueue_script(
        'jobiizy-bookmark-js',
        get_stylesheet_directory_uri() . '/assets/js/jobiizy-bookmark.js',
        ['jquery'],
        filemtime(get_stylesheet_directory() . '/assets/js/jobiizy-bookmark.js'),
        true
    );
}, 20);

// GEMINI LIVE 
/**
 * Ajouter un badge "Nouveau" sur les offres de moins de 3 jours
 */
add_action( 'cariera_job_listing_meta_start', 'jobiizy_custom_new_badge' );

function jobiizy_custom_new_badge() {
    $post_date = get_the_date('U');
    $delta = ( time() - $post_date ) / ( 60 * 60 * 24 );
    
    if ( $delta <= 3 ) {
        echo 'NOUVEAU';
    }
}




// CLAUDE  LIVE 
/**
 * Charger le script d'injection du nom d'entreprise (secours)
 */
add_action('wp_enqueue_scripts', 'jobiizy_enqueue_inject_company_script', 35);
function jobiizy_enqueue_inject_company_script() {
    $js_path = get_stylesheet_directory() . '/assets/js/inject-company-name.js';
    $js_uri  = get_stylesheet_directory_uri() . '/assets/js/inject-company-name.js';
    
    if (file_exists($js_path)) {
        wp_enqueue_script(
            'jobiizy-inject-company-name',
            $js_uri,
            array('jquery'),
            filemtime($js_path),
            true
        );
    }
}

/**
 * Debug : vérifier si get_the_company_name() fonctionne
 */
add_action('wp_footer', 'jobiizy_debug_company_name');
function jobiizy_debug_company_name() {
    if (current_user_can('administrator') && !wp_doing_ajax()) {
        ?>
        <script>
            jQuery(document).ready(function($) {
                console.log("=== DEBUG COMPANY NAME ===");
                
                // Vérifier chaque carte
                $(".job_listing.job-grid").each(function(index) {
                    const $card = $(this);
                    const logoAlt = $card.find(".company_logo").attr("alt");
                    const hasCompanyDiv = $card.find(".jobiizy-company-name-inline").length > 0;
                    
                    console.log("Carte " + index + ":");
                    console.log("  Logo alt:", logoAlt);
                    console.log("  Nom injecté:", hasCompanyDiv ? "OUI ✅" : "NON ❌");
                });
            });
        </script>
        <?php
    }
}
// CLAUDE LIVE 
// require_once get_stylesheet_directory() . '/jobiizy-sync-fields.php';
/**
 * JobiiZy Custom Widgets
 */
require_once get_stylesheet_directory() . '/jobiizy-custom-widgets.php';
require_once get_stylesheet_directory() . '/ajax-job-details.php';
/**
 * JobiiZy AJAX Search handlers
 */
require_once get_stylesheet_directory() . '/jobiizy-ajax-search.php';
// Dans cariera-child/functions.php, ajouter à la fin :
require_once get_stylesheet_directory() . '/jobiizy-ajax-functions.php';

/**
 * Enqueue JobiiZy Split View Assets
 */

function jobiizy_enqueue_split_view_assets() {
    // Charger uniquement sur les pages d'offres
    if ( ! is_singular( 'job_listing' ) && ! is_post_type_archive( 'job_listing' ) && ! is_tax( 'job_listing_category' ) && ! is_tax( 'job_listing_type' ) ) {
        return;
    }
    
    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();
    
    // CSS
    $css_file = $theme_dir . '/assets/css/jobiizy-split-view.css';
    if ( file_exists( $css_file ) ) {
        wp_enqueue_style(
            'jobiizy-split-view',
            $theme_uri . '/assets/css/jobiizy-split-view.css',
            [],
            filemtime( $css_file ) // Cache busting automatique
        );
    }
    
    // JavaScript
    $js_file = $theme_dir . '/assets/js/jobiizy-split-view.js';
    if ( file_exists( $js_file ) ) {
        wp_enqueue_script(
            'jobiizy-split-view',
            $theme_uri . '/assets/js/jobiizy-split-view.js',
            ['jquery'],
            filemtime( $js_file ),
            true // Dans le footer
        );
        
        // Passer les données PHP au JavaScript
        // Note: Ces valeurs par défaut sont écrasées dans le template
 /*        wp_localize_script( 'jobiizy-split-view', 'jobiizyData', [
            'ajaxurl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'jobiizy_nonce' ),
            'currentPage' => 1,
            'totalJobs'   => 0,
            'maxPages'    => 0,
            'perPage'     => 10,
        ]);
 */    }
}
add_action( 'wp_enqueue_scripts', 'jobiizy_enqueue_split_view_assets' );


/* CLAUDE 23/02 */
function jobiizy_enqueue_hc_offcanvas() {
    $dir = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();

    // HC-Offcanvas — global (le JS gère disableAt:1025)
    wp_enqueue_style(
        'hc-offcanvas-nav',
        $uri . '/assets/libs/hc-offcanvas-nav/hc-offcanvas-nav.carbon.css',
        [],
        '3.3.2'
    );
    wp_enqueue_script(
        'hc-offcanvas-nav',
        $uri . '/assets/libs/hc-offcanvas-nav/hc-offcanvas-nav.js',
        [],
        '3.3.2',
        true
    );

    // Mobile upgrade — global sur tout le site
    $css_m = $dir . '/assets/css/jobiizy-mobile-upgrade.css';
    if ( file_exists( $css_m ) ) {
        wp_enqueue_style(
            'jobiizy-mobile-upgrade',
            $uri . '/assets/css/jobiizy-mobile-upgrade.css',
            ['cariera-frontend', 'hc-offcanvas-nav'],
            filemtime( $css_m )
        );
    }

    $js_m = $dir . '/assets/js/jobiizy-mobile-upgrade.js';
    if ( file_exists( $js_m ) ) {
        wp_enqueue_script(
            'jobiizy-mobile-upgrade',
            $uri . '/assets/js/jobiizy-mobile-upgrade.js',
            ['jquery', 'hc-offcanvas-nav'],
            filemtime( $js_m ),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'jobiizy_enqueue_hc_offcanvas', 30);
/* CLAUDE 23/02 */

/* CLAUDE 05/12   Variable cf_job_location  */
/* 
add_action('wp_head', function() {
    if (is_singular('job_listing')) {
        global $post;
        error_log('🔍 Job Meta Keys:');
        error_log(print_r(get_post_meta($post->ID), true));
    }
}, 1);
*/

// ============= FIX YOAST CUSTOM FIELDS =============
// Post-traite les titres/descriptions pour remplacer les variables custom
// ============= FIX SEO META JOBIIZY =============
// Remplace Yoast title/desc pour les annonces

/*  DEBUG ALL META KEYS FOR POST
add_action('wp_footer', function() {
    if (is_singular('job_listing')) {
        global $post;
        $all_meta = get_post_meta($post->ID);
        echo '<pre style="background:#222;color:#0f0;padding:20px;font-family:monospace;font-size:12px;max-height:600px;overflow:auto;">';
        echo '🔍 ALL META KEYS FOR POST #' . $post->ID . "\n";
        foreach ($all_meta as $key => $value) {
            echo $key . ' => ' . json_encode($value) . "\n";
        }
        echo '</pre>';
    }
}, 999);
*/

// Fix le TITRE 
add_filter('wpseo_title', function($title) {
    if (is_singular('job_listing')) {
        global $post;
        
        $location = get_post_meta($post->ID, '_job_location', true) ?: 'Israël';
        
        // Récupère le job type depuis les termes (pas meta)
        $terms = get_the_terms($post->ID, 'job_listing_type');
        $job_type = (!empty($terms) && !is_wp_error($terms)) 
            ? $terms[0]->name 
            : 'CDI';
        
        return get_the_title($post->ID) . ' à ' . $location . ' — ' . $job_type . ' Francophone';
    }
    return $title;
});

// Fix la META DESCRIPTION
add_filter('wpseo_metadesc', function($desc) {
    if (is_singular('job_listing')) {
        global $post;
        
        $location = get_post_meta($post->ID, '_job_location', true) ?: 'Israël';
        
        // Récupère le job type depuis les termes
        $terms = get_the_terms($post->ID, 'job_listing_type');
        $job_type = (!empty($terms) && !is_wp_error($terms)) 
            ? $terms[0]->name 
            : 'CDI';
        
        $title = get_the_title($post->ID);
        
        return 'Offre d\'emploi: ' . $title . ' (' . $job_type . ') à ' . $location . '. Emploi francophone en Israël. Postulez gratuitement.';
    }
    return $desc;
});

// Claude Add package html
// functions.php
add_shortcode('jobiizy_packages', function() {
    ob_start();
    include get_stylesheet_directory() . '/templates/jobiizy-packages.html';
    return ob_get_clean();
});
// Puis dans la page WP : [jobiizy_packages]
add_action('template_redirect', function() {
    if (is_page('devenir-employeur')) {
        error_log('REDIRECT DEBUG - User: ' . get_current_user_id() 
            . ' - Roles: ' . implode(',', wp_get_current_user()->roles)
            . ' - Referer: ' . $_SERVER['HTTP_REFERER']);
    }
}, 1); // priorité 1 = avant tout le reste

// Ajoute temporairement dans functions.php
/* 
add_action('init', function() {
    if (!current_user_can('administrator')) return;
    if (!isset($_GET['check_product_types'])) return;
    $types = wc_get_product_types();
    wp_die('<pre>' . print_r($types, true) . '</pre>');
});
 */