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

require_once __DIR__ . '/includes/menus.php';

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