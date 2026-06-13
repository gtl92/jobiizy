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


require_once __DIR__ . '/includes/config.php';
require_once get_stylesheet_directory() . '/anti-spam.php';
require_once __DIR__ . '/includes/enqueue.php';

require_once __DIR__ . '/includes/templates.php';
require_once __DIR__ . '/includes/job-hooks.php';

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

require_once __DIR__ . '/includes/cvtheque.php';
require_once __DIR__ . '/includes/menus.php';

require_once __DIR__ . '/includes/popup.php';






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

function jobiizy_emplois_body_class($classes) {
    if (is_page(JOBIIZY_JOBS_PAGE_ID)) {
        $classes[] = 'jobiizy-emplois-page';
    }
    return $classes;
}
add_filter('body_class', 'jobiizy_emplois_body_class');

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
require_once __DIR__ . '/includes/company-sync.php';

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

require_once __DIR__ . '/includes/seo.php';

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