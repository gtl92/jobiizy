<?php
if (!defined('ABSPATH')) exit;

/**
 * ============================================================
 *   MODULE DEBUG — JOBIIZY
 *   Version complète (toutes fonctionnalités regroupées)
 * ============================================================
 *
 *  Ce module contient :
 *   ✔ logging console + colors
 *   ✔ collecte des logs (console collector)
 *   ✔ injection console footer
 *   ✔ Debug overlay (template, layout, roles…)
 *   ✔ Top companies
 *   ✔ Meta Query
 *   ✔ Debug des scripts et styles
 *   ✔ Debug Cariera company_manager query
 *   ✔ Trace AJAX & split view
 *   ✔ Indicateurs visuels
 *   ✔ Protection AJAX (aucune injection HTML dans JSON)
 */

// ============================================================
// 0. CONFIG — Activer/désactiver le module
// ============================================================

if (!defined('JOBIIZY_DEBUG')) {
    define('JOBIIZY_DEBUG', false);
}

// Ne jamais injecter HTML en AJAX
function jobiizy_debug_can_output() {
    return (
        JOBIIZY_DEBUG &&
        !wp_doing_ajax() &&
            !is_admin() &&   
        current_user_can('administrator')
    );
}

// ============================================================
// 1. LOGGING (console collector)
// ============================================================

global $jobiizy_debug_logs;
$jobiizy_debug_logs = [];

function jobiizy_log($msg, $color = '#09f') {
    if (!JOBIIZY_DEBUG) return;

    error_log('[Jobiizy] ' . $msg);

    if (!jobiizy_debug_can_output()) return;

    global $jobiizy_debug_logs;

    $jobiizy_debug_logs[] = [
        'message' => $msg,
        'color'   => $color
    ];
}

function jobiizy_success($m) { jobiizy_log("✅ $m", '#00c853'); }
function jobiizy_warn($m)    { jobiizy_log("⚠️ $m", '#ff9800'); }
function jobiizy_error($m)   { jobiizy_log("❌ $m", '#ff1744'); }


// ============================================================
// 2. Injection des logs dans la console (footer)
// ============================================================

add_action('wp_footer', function() {

    if (!jobiizy_debug_can_output()) return;

    global $jobiizy_debug_logs;

    if (empty($jobiizy_debug_logs)) return;

    echo "\n<!-- JOBIIZY DEBUG LOGS -->\n<script>\n";
    echo "console.group('%cJOBIIZY DEBUG','color:#09f;font-weight:bold;font-size:14px');\n";

    foreach ($jobiizy_debug_logs as $log) {
        printf(
            'console.log("%%c%s","color:%s;font-weight:bold");' . "\n",
            esc_js($log['message']),
            esc_js($log['color'])
        );
    }

    echo "console.groupEnd();\n</script>\n";
}, 9999);


// ============================================================
// 3. Overlays visuels (layout, roles, meta queries, companies…)
// ============================================================

add_action('wp_footer', function() {

    if (!jobiizy_debug_can_output()) return;

    global $template, $wp_query, $wpdb;

    // ------------
    // Template, layout, post type, is_singular
    // ------------
    $tpl       = is_string($template) ? basename($template) : '(n/a)';
    $opt       = get_option('cariera_job_manager_single_job_layout', '(n/a)');
    $lay       = function_exists('cariera_single_job_layout') ? cariera_single_job_layout() : '(absent)';
    $ptype     = get_post_type() ?: '(n/a)';
    $is_sing   = is_singular('job_listing') ? 'TRUE' : 'FALSE';

?>
    <script>
    console.group("%c🎨 JOBIIZY TEMPLATE DEBUG", "color:#0af;font-weight:bold;font-size:14px");
    console.log("%cTemplate:", "color:#0f0", "<?php echo esc_js($tpl); ?>");
    console.log("%cOption BDD:", "color:#ff0", "<?php echo esc_js($opt); ?>");
    console.log("%cLayout actif:", "color:#0ff", "<?php echo esc_js($lay); ?>");
    console.log("%cPost Type:", "color:#f0f", "<?php echo esc_js($ptype); ?>");
    console.log("%cIs singular job:", "color:#fff", "<?php echo esc_js($is_sing); ?>");
    console.groupEnd();
    </script>
<?php

    // Overlay Layout + template
    if (is_singular('job_listing')) {
        $ok = ($lay === 'jobiizy');
        ?>
        <div style="background:#300;color:#fff;padding:10px;position:fixed;top:60px;right:10px;z-index:9999;border-radius:6px;">
            <strong>Layout :</strong>
            <span style="color:<?php echo $ok ? '#0f0' : '#f90'; ?>">
            <?php echo $ok ? 'Jobiizy actif' : $lay; ?>
            </span><br>
            <small>Template : <?php echo esc_html($tpl); ?></small>
        </div>
        <?php
    }

    // Top Companies
    $companies = $wpdb->get_results("
        SELECT post_id, meta_value 
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_active_jobs'
        ORDER BY meta_value+0 DESC
        LIMIT 10
    ");

    if (!empty($companies)) {
        ?>
        <div style="background:#111;color:#0f0;padding:8px;font-size:11px;position:fixed;bottom:10px;left:10px;z-index:9999;border-radius:5px;max-height:200px;overflow:auto;">
            <strong>Top Companies</strong><br>
            <?php foreach ($companies as $c): ?>
                Company #<?php echo $c->post_id; ?> → <?php echo $c->meta_value; ?> jobs<br>
            <?php endforeach; ?>
        </div>
        <?php
    }

    // Meta-query active
    if (!empty($wp_query->query_vars['meta_query'])) {
        ?>
        <div style="background:#000;color:#0ff;padding:8px;font-size:10px;position:fixed;bottom:10px;left:330px;z-index:9999;border-radius:5px;max-height:200px;overflow:auto;">
            <strong>WP_Query Meta Query</strong><br>
            <pre><?php echo esc_html(print_r($wp_query->query_vars['meta_query'], true)); ?></pre>
        </div>
        <?php
    }

    // Roles utilisateur
    $user  = wp_get_current_user();
    $roles = implode(', ', (array) $user->roles);

?>
    <div style="background:#003;color:#fff;padding:8px;position:fixed;bottom:60px;right:10px;z-index:9999;border-radius:5px;max-width:300px;">
        <strong>Rôles :</strong> <?php echo esc_html($roles); ?><br>
    </div>
<?php
}, 9998);


// ============================================================
// 4. Debug Cariera — company_manager job listing
// ============================================================

add_filter('company_manager_get_company_job_listings_query_args', function($args) {
    jobiizy_warn("Hook company_manager_get_company_job_listings_query_args");
    return $args;
}, 10);


// ============================================================
// 5. Debug enqueuing scripts & styles
// ============================================================

add_action('wp_print_scripts', function() {
    if (!jobiizy_debug_can_output()) return;

    global $wp_scripts;
    foreach ($wp_scripts->registered as $handle => $data) {
        if (str_contains($handle, 'jobiizy')) {
            error_log("[JS] $handle → {$data->src}");
        }
    }
}, 999);

add_action('wp_print_styles', function() {
    if (!jobiizy_debug_can_output()) return;

    global $wp_styles;
    foreach ($wp_styles->registered as $handle => $data) {
        if (str_contains($handle, 'jobiizy')) {
            error_log("[CSS] $handle → {$data->src}");
        }
    }
}, 999);


// ============================================================
// 6. Split View Debug — bouton postuler / AJAX traces
// ============================================================

add_action('wp_ajax_cariera_load_single_job_ajax', function(){
    jobiizy_log("AJAX cariera_load_single_job_ajax reçu");
}, 1);

add_filter('cariera_single_job_ajax_output', function($html) {
    jobiizy_log("Filtre cariera_single_job_ajax_output");
    return $html;
}, 1);