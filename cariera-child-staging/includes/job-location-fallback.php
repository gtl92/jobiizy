<?php
/**
 * Corrige le "jobLocation manquant" remonté par Search Console (données
 * structurées JobPosting) :
 * - rend le champ "Localisation" obligatoire à la soumission d'une offre
 * - garantit une valeur par défaut sur les offres déjà publiées sans lieu
 */

add_filter( 'submit_job_form_fields', function ( $fields ) {
    if ( isset( $fields['job_location'] ) ) {
        $fields['job_location']['required'] = true;
    }
    return $fields;
} );

add_action( 'save_post_job_listing', function ( $post_id ) {
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    $location = get_post_meta( $post_id, '_job_location', true );

    if ( '' === trim( (string) $location ) ) {
        update_post_meta( $post_id, '_job_location', 'Israël' );
    }
}, 20 );

/**
 * Backfill ponctuel : corrige les offres déjà publiées sans lieu renseigné.
 * Déclenché depuis le widget du tableau de bord (bouton "Corriger les lieux manquants").
 */
add_action( 'wp_dashboard_setup', function () {
    wp_add_dashboard_widget(
        'jobiizy_location_backfill_widget',
        '📍 Correction jobLocation (Search Console)',
        function () {
            $missing = get_posts( [
                'post_type'      => 'job_listing',
                'post_status'    => [ 'publish', 'expired' ],
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => [
                    'relation' => 'OR',
                    [ 'key' => '_job_location', 'compare' => 'NOT EXISTS' ],
                    [ 'key' => '_job_location', 'value' => '', 'compare' => '=' ],
                ],
            ] );

            if ( isset( $_GET['jobiizy_location_fixed'] ) ) {
                echo '<div style="background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:10px;margin-bottom:10px;border-radius:4px;">✅ ' . (int) $_GET['jobiizy_location_fixed'] . ' offres corrigées.</div>';
            }

            echo '<p>📊 <strong>' . count( $missing ) . ' offres</strong> sans lieu renseigné.</p>';

            if ( $missing ) {
                echo '<form method="post">';
                wp_nonce_field( 'jobiizy_fix_locations', 'jobiizy_fix_locations_nonce' );
                echo '<input type="hidden" name="jobiizy_fix_locations" value="1">';
                echo '<button type="submit" class="button button-primary">Corriger les lieux manquants</button>';
                echo '</form>';
            }
        }
    );
} );

add_action( 'admin_init', function () {
    if ( empty( $_POST['jobiizy_fix_locations'] ) ) {
        return;
    }

    check_admin_referer( 'jobiizy_fix_locations', 'jobiizy_fix_locations_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $missing = get_posts( [
        'post_type'      => 'job_listing',
        'post_status'    => [ 'publish', 'expired' ],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            'relation' => 'OR',
            [ 'key' => '_job_location', 'compare' => 'NOT EXISTS' ],
            [ 'key' => '_job_location', 'value' => '', 'compare' => '=' ],
        ],
    ] );

    foreach ( $missing as $post_id ) {
        update_post_meta( $post_id, '_job_location', 'Israël' );
    }

    wp_safe_redirect( add_query_arg( 'jobiizy_location_fixed', count( $missing ), wp_get_referer() ?: admin_url() ) );
    exit;
} );
