<?php
/**
 * ============================================================
 * MODULE COMPANY — JOBIIZY
 * Fonctions unifiées pour gérer les entreprises, leur compteur
 * d'offres et leur intégration avec WP Job Manager & Cariera.
 * ============================================================
 */

if (!defined('ABSPATH')) exit;

/**
 * Calcul robuste du nombre d'emplois actifs pour une entreprise.
 * Compatible Cariera, WPJM, anciens formats et données Jobiizy.
 *
 * @param int $company_id
 * @return int
 */
function jobiizy_calculate_active_jobs($company_id) {

    if (empty($company_id) || !is_numeric($company_id)) {
        return 0;
    }

    // 1) Lire la méta Cariera "_active_jobs"
    $meta = get_post_meta($company_id, '_active_jobs', true);

    if (is_array($meta)) {

        // CAS SPÉCIAL Cariera : [ [ ] ]
        if (count($meta) === 1 && isset($meta[0]) && is_array($meta[0]) && empty($meta[0])) {
            $count = 0;
        }

        // CAS Cariera format → [X, N]
        elseif (isset($meta[1]) && is_numeric($meta[1])) {
            $count = intval($meta[1]);
        }

        // CAS format simple → [3]
        elseif (isset($meta[0]) && is_numeric($meta[0])) {
            $count = intval($meta[0]);
        }

        // CAS fallback
        else {
            $count = count($meta);
        }

    } elseif (is_numeric($meta)) {
        $count = intval($meta);

    } else {
        $count = 0;
    }

    // 2) Si compteur vide → recalcul dynamique
    if (empty($count)) {

        $jobs = new WP_Query([
            'post_type'      => 'job_listing',
            'post_status'    => ['publish'],
            'posts_per_page' => -1,
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'     => '_company_id',
                    'value'   => $company_id,
                    'compare' => '=',
                ],
                [
                    'key'     => '_company_manager_id',
                    'value'   => $company_id,
                    'compare' => '=',
                ]
            ]
        ]);

        $count = $jobs->found_posts;
        wp_reset_postdata();
    }

    return $count;
}