<?php
/**
 * Company Listing - Carousel Content
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-companies/company-templates/company-carousel.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @package     Cariera
 * @category    Template
 * @since       1.7.3
 * @version     1.8.0
 */
/**
 * Template surchargé depuis Cariera 1.9.6 pour corriger le bug du compteur de jobs.
 * Lit directement la méta "_active_jobs".
 * À supprimer quand le thème Cariera corrigera ce problème.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// ✅ Protection : si la fonction de comptage n’existe plus
if (!function_exists('cariera_get_the_company_job_listing_active_count')) {
    // On continue quand même, on lit directement la méta "_active_jobs"
    // (aucune erreur PHP ne sera générée)
}

global $post;
?>

<div class="single-company">
    <a href="<?php cariera_the_company_permalink(); ?>" class="company-link" aria-label="<?php the_title(); ?>">
        <!-- Company Logo -->
        <?php if ('1' === $settings['version']) { ?>
            <div class="company-logo-wrapper">
                <?php
        } else {
            $image = get_post_meta($post->ID, '_company_header_image', true);
            ?>
                <div class="company-logo-wrapper" style="background-image: url(<?php echo esc_attr($image); ?>);">
                <?php } ?>

  <div class="company-logo">
    <?php
    if (function_exists('cariera_the_company_logo')) {
        cariera_the_company_logo();
    } else {
        // Fallback WordPress si jamais le thème change
        echo get_the_post_thumbnail(get_the_ID(), 'medium');
    }
    ?>
</div>
            </div>

            <!-- Company Details -->
            <div class="company-details">
                <div class="company-title">
                    <h3 class="title"><?php the_title(); ?></h3>
                </div>

                <?php if (!empty(cariera_get_the_company_location())) { ?>
                    <div class="company-location">
                        <span><i class="las la-map-marker"></i><?php echo cariera_get_the_company_location(); ?></span>
                    </div>
                <?php } ?>

                <div class="company-jobs">
                    <span>
                        <?php
                        // ✅ CORRECTION : Utiliser la fonction intelligente qui gère TOUS les formats
                        if (function_exists('jobiizy_calculate_active_jobs')) {
                            $active_jobs = jobiizy_calculate_active_jobs(get_the_ID());
                        } else {
                            // Fallback si la fonction n'existe pas
                            $active_jobs = get_post_meta(get_the_ID(), '_active_jobs', true);
                            
                            // Si c'est un tableau, prendre le 2e élément ou compter les éléments
                            if (is_array($active_jobs)) {
                                // CAS SPÉCIAL : Tableau vide imbriqué Array([0] => Array())
                                if (count($active_jobs) === 1 && isset($active_jobs[0]) && is_array($active_jobs[0]) && empty($active_jobs[0])) {
                                    $active_jobs = 0; // Format bizarre de Cariera pour "0 jobs"
                                }
                                elseif (count($active_jobs) === 2 && isset($active_jobs[1])) {
                                    $active_jobs = intval($active_jobs[1]);
                                } elseif (count($active_jobs) === 1 && is_numeric($active_jobs[0])) {
                                    $active_jobs = intval($active_jobs[0]);
                                } else {
                                    $active_jobs = count($active_jobs);
                                }
                            } elseif (is_numeric($active_jobs)) {
                                $active_jobs = intval($active_jobs);
                            } else {
                                $active_jobs = 0;
                            }
                            
                            // Si toujours rien, on fait un comptage dynamique
                            if ($active_jobs === 0 || $active_jobs === '') {
                                $jobs = new WP_Query([
                                    'post_type' => 'job_listing',
                                    'post_status' => ['publish'],
                                    'posts_per_page' => -1,
                                    'meta_query' => [
                                        [
                                            'key' => '_company_manager_id',
                                            'value' => get_the_ID(),
                                            'compare' => '=',
                                        ],
                                    ],
                                ]);
                                $active_jobs = $jobs->found_posts;
                                wp_reset_postdata();
                            }
                        }

                        printf(
                            esc_html(_n('%s emploi', '%s emplois', $active_jobs, 'cariera')),
                            esc_html($active_jobs)
                        );
                        ?>
                    </span>
                </div>
            </div>
    </a>
</div>