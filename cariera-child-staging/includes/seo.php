<?php
/**
 * SEO — Titres et meta descriptions Yoast pour les job listings
 */

add_filter('wpseo_title', function( $title ) {
    if ( ! is_singular('job_listing') ) {
        return $title;
    }
    global $post;
    $location = get_post_meta($post->ID, '_job_location', true) ?: 'Israël';
    $terms    = get_the_terms($post->ID, 'job_listing_type');
    $job_type = ( ! empty($terms) && ! is_wp_error($terms) ) ? $terms[0]->name : 'CDI';

    return get_the_title($post->ID) . ' à ' . $location . ' — ' . $job_type . ' Francophone';
});

add_filter('wpseo_metadesc', function( $desc ) {
    if ( ! is_singular('job_listing') ) {
        return $desc;
    }
    global $post;
    $location = get_post_meta($post->ID, '_job_location', true) ?: 'Israël';
    $terms    = get_the_terms($post->ID, 'job_listing_type');
    $job_type = ( ! empty($terms) && ! is_wp_error($terms) ) ? $terms[0]->name : 'CDI';
    $title    = get_the_title($post->ID);

    return 'Offre d\'emploi: ' . $title . ' (' . $job_type . ') à ' . $location . '. Emploi francophone en Israël. Postulez gratuitement.';
});
