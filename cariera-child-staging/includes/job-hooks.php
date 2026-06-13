<?php
/**
 * Job hooks — WPJM/Cariera hooks sur les offres d'emploi
 */

add_action('job_listing_info_end', 'jobiizy_add_view_offer_button');
function jobiizy_add_view_offer_button() {
    global $post;
    echo '<a href="' . esc_url(get_the_job_permalink($post)) . '" class="btn-view-offer">'
        . esc_html__("Voir l'offre", 'cariera') . '</a>';
}

add_filter('wpjm_job_listing_employment_type_options', function($types) {
    $types['REMOTE_WORK'] = esc_html__('Remote Work', 'wp-job-manager');
    if (get_locale() === 'fr_FR') {
        $types['REMOTE_WORK'] = 'Télétravail';
    }
    return $types;
});

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

add_action('single_job_listing_meta_start', 'jobiizy_add_title_in_job_overview');
function jobiizy_add_title_in_job_overview() {
    if (is_singular('job_listing')) {
        echo '<div class="single-job-overview-detail job-overview-title">';
        echo '<div class="content">';
        echo '<h2 class="job-overview-title-text">' . esc_html(get_the_title()) . '</h2>';
        echo '</div></div>';
    }
}

add_action('single_job_listing_start', 'jobiizy_add_job_title_before_company', 25);
function jobiizy_add_job_title_before_company() {
    if (is_singular('job_listing')) {
        echo '<h2 class="job-main-title">' . esc_html(get_the_title()) . '</h2>';
    }
}

add_action('cariera_job_listing_meta_start', 'jobiizy_custom_new_badge');
function jobiizy_custom_new_badge() {
    $post_date = get_the_date('U');
    $delta     = (time() - $post_date) / (60 * 60 * 24);
    if ($delta <= 3) {
        echo 'NOUVEAU';
    }
}
