<?php
/**
 * Template personnalisé Jobiizy - Overview façon HelloWork
 * Emplacement : cariera-child/job_manager/single-job/single-job-listing-overview.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $post;
$job_id = $post->ID;
$job    = get_post( $job_id );
$job_title = get_the_title( $job_id );
$company_name = get_post_meta( $job_id, '_company_name', true );
$location = get_post_meta( $job_id, '_job_location', true );
$job_type = get_the_terms( $job_id, 'job_listing_type' );
$job_type_name = $job_type && ! is_wp_error( $job_type ) ? $job_type[0]->name : '';
$reference = get_post_meta( $job_id, '_job_reference', true );
$published_date = get_the_date( 'd F Y', $job_id );
do_action( 'single_job_listing_meta_before' );

?>

<div class="jobiizy-overview">
    <h2 class="job-title"><?php echo esc_html( $job_title ); ?></h2>

    <?php if ( $company_name ) : ?>
        <div class="job-company">
            <a href="#" class="company-link"><?php echo esc_html( $company_name ); ?></a>
        </div>
    <?php endif; ?>

    <div class="job-tags">
        <?php if ( $location ) : ?>
            <span class="tw-tag-grey-s"><?php echo esc_html( $location ); ?></span>
        <?php endif; ?>
        <?php if ( $job_type_name ) : ?>
            <span class="tw-tag-grey-s"><?php echo esc_html( $job_type_name ); ?></span>
        <?php endif; ?>
    </div>

    <?php if ( is_user_logged_in() ) : ?>
        <div class="job-apply">
            <a href="<?php echo esc_url( get_permalink( $job_id ) . '#job-popup' ); ?>"
               class="tw-btn-primary-candidacy-l tw-w-full tw-text-center">
               Postuler
            </a>
        </div>
    <?php else : ?>
        <div class="job-apply">
            <a href="<?php echo esc_url( home_url( '/pages/connexion-inscription/' ) ); ?>"
               class="tw-btn-primary-candidacy-l tw-w-full tw-text-center">
               Se connecter pour postuler
            </a>
        </div>
    <?php endif; ?>

    <div class="job-meta">
        <small>
            Publiée le <?php echo esc_html( $published_date ); ?>
            <?php if ( $reference ) : ?>
                – Réf : <?php echo esc_html( $reference ); ?>
            <?php endif; ?>
        </small>
    </div>
</div>