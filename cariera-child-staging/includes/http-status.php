<?php
/**
 * HTTP status — évite les soft 404 sur les offres expirées.
 *
 * Le template single-job-listing-jobiizy.php affichait "This listing has
 * expired." avec un code HTTP 200, ce que Google Search Console remonte
 * comme "Soft 404" (page vide servie en 200).
 */

add_action( 'template_redirect', function () {
    if ( ! is_singular( 'job_listing' ) ) {
        return;
    }

    $post = get_queried_object();

    if ( ! $post || 'expired' !== $post->post_status ) {
        return;
    }

    if ( ! get_option( 'job_manager_hide_expired_content', 1 ) ) {
        return;
    }

    status_header( 410 );
    nocache_headers();
} );
