<?php
/**
 * Jobiizy — Thème enfant Cariera
 * Orchestrateur : inclut les modules dans l'ordre de chargement.
 */

ini_set('error_log', __DIR__ . '/error_log');

$jobiizy_debug_file = get_stylesheet_directory() . '/modules/debug/debug.php';
if (file_exists($jobiizy_debug_file)) {
    require_once $jobiizy_debug_file;
}

require_once __DIR__ . '/includes/config.php';
require_once get_stylesheet_directory() . '/anti-spam.php';
require_once __DIR__ . '/includes/enqueue.php';
require_once __DIR__ . '/includes/templates.php';
require_once __DIR__ . '/includes/job-hooks.php';
require_once __DIR__ . '/includes/notifications.php';
require_once __DIR__ . '/includes/cvtheque.php';
require_once __DIR__ . '/includes/menus.php';
require_once __DIR__ . '/includes/shortcodes.php';
require_once __DIR__ . '/includes/popup.php';
require_once __DIR__ . '/includes/body-class.php';
require_once __DIR__ . '/includes/company-sync.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/http-status.php';
require_once __DIR__ . '/includes/job-location-fallback.php';

require_once get_stylesheet_directory() . '/jobiizy-custom-widgets.php';
require_once get_stylesheet_directory() . '/ajax-job-details.php';
require_once get_stylesheet_directory() . '/jobiizy-ajax-search.php';
require_once get_stylesheet_directory() . '/jobiizy-ajax-functions.php';
