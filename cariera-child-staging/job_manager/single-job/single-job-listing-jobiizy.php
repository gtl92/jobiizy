<?php
/**
 * Custom: Single Job Page - Custom Jobiizy Layout – Version 4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$upload_dir = wp_upload_dir();
$image_url = $upload_dir['baseurl'] . '/new/suitecase01.webp';

$featured    = get_post_meta( $post->ID, '_featured', true );
$job_classes = [ 'single-job-listing-page', 'single-job-v1' ];

if ( 1 === absint( $featured ) ) {
    $job_classes[] = 'featured-listing';
}

$job_id = get_the_ID();
echo '<!-- DEBUG METAS JOB single-job-listing-jobiizy.php -->';
echo '<pre style="display:none">';
// print_r( get_post_meta( $job_id ) );
echo '</pre>';

$job_id = get_the_ID();
$is_external_job = jobiizy_is_external_application( get_the_ID() );
error_log(
    '[JOBIIZY FRONT single-job-listing-jobiizy.php ] default_form_id=' . get_option('job_manager_default_application_form')
);
// ID du formulaire interne (par défaut)
$INTERNAL_FORM_ID = 12181;
// $INTERNAL_FORM_ID = jobiizy_get_internal_application_form_id();
error_log(
    '[JOBIIZY FRONT single-job-listing-jobiizy.php ] INTERNAL_FORM_ID=$INTERNAL_FORM_ID: ' . $INTERNAL_FORM_ID);


// Formulaire sélectionné pour cette offre
$application_form_id = absint(
    get_post_meta( $job_id, '_application_form', true )
);

// Détection externe / interne
$is_external_apply = (
    $application_form_id &&
    $application_form_id !== $INTERNAL_FORM_ID
);

// Données destinées à la popup
$job_popup_data = [
    'title'   => get_the_title( $job_id ),
    'email'   => get_post_meta( $job_id, '_field_cfwjm12185', true ),
    'message' => get_post_meta( $job_id, '_field_cfwjm12180', true ),
    'notes'   => get_post_meta( $job_id, '_field_cfwjm12770', true ),
];

// Nettoyage sécurité / UX
foreach ( $job_popup_data as $k => $v ) {
    $job_popup_data[$k] = trim( wp_strip_all_tags( (string) $v ) );
}

?>
<!-- DEBUG JOBIIZY external_apply = <?php echo $is_external_apply ? 'YES' : 'NO'; ?> -->
<main
    id="post-<?php the_ID(); ?>"
    class="<?php echo esc_attr( join( ' ', $job_classes ) ); ?>"
    data-jobiizy="job"
	data-apply-type="<?php echo jobiizy_is_external_application(get_the_ID()) ? 'external' : 'internal'; ?>"
    data-title="<?php echo esc_attr( $job_popup_data['title'] ); ?>"
    data-email="<?php echo esc_attr( $job_popup_data['email'] ); ?>"
    data-message="<?php echo esc_attr( $job_popup_data['message'] ); ?>"
    data-notes="<?php echo esc_attr( $job_popup_data['notes'] ); ?>"
>	
    <?php do_action( 'cariera_single_job_listing_before' ); ?>
    <?php get_job_manager_template_part( 'single-job/page-header' ); ?>

    <section class="single-job-content">
        <div class="container">
            <div class="row">
                <?php if ( get_option( 'job_manager_hide_expired_content', 1 ) && 'expired' === $post->post_status ) { ?>
                    <div class="col-md-12">
                        <div class="job-manager-message error"><?php esc_html_e( 'This listing has expired.', 'cariera' ); ?></div>
                    </div>
                <?php } else { ?>
                    <div class="col-md-8 col-xs-12">
                        <div class="single-job-listing">
                            <?php
                                /**
                                 * Single_job_listing_start hook
                                 *
                                 * @hooked job_listing_meta_display - 20
                                 * @hooked job_listing_company_display - 30
                                 */
                                do_action( 'single_job_listing_start' );
                            ?>

                            <div class="job-description GTLGTLJOBIIZY">
                                <?php
                                   $image_url = $upload_dir['baseurl'] . '/_new/suitcase01.webp';
                                 ?>
                                <p class="job-description-title"> <img width="25" src="<?php echo esc_url( $image_url ); ?>" alt="Description Job">  Mission du job </p>
                                <?php wpjm_the_job_description(); ?>
                            </div>

                            <?php
                                /**
                                 * Single_job_listing_end hook
                                 */
                               /* do_action( 'single_job_listing_end' );*/
                            ?>
                        </div>
                    </div>

                    <div class="col-md-4 col-xs-12 job-sidebar">
                        <div class="sidebar-info">
                            <?php do_action( 'cariera_single_job_listing_sidebar' ); ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <?php do_action( 'cariera_single_job_listing_after' ); ?>

<?php if ( ! is_user_logged_in() && is_singular('job_listing') ) : ?>
    <section id="cv-form" class="cv-form-wrapper">
        <div class="container">
            <h2><?php esc_html_e('Envoyer votre CV', 'cariera'); ?></h2>
            <?php echo do_shortcode('[jobiizy_cv_form job_id="'. get_the_ID() .'"]'); ?>
        </div>
    </section>
<?php endif; ?>
    <?php
	// ✅ Toujours inclure la popup native, nécessaire pour #job-popup
	// get_job_manager_template( 'job-application.php' );
	?>
</main>