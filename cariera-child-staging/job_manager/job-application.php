<?php
/**
 * Show job application when viewing a single job listing.
 * Custom Cariera Child version.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * JOBIIZY
 * Bloque totalement la candidature Cariera
 * si l'offre est EXTERNE
 */
$job_id = get_the_ID();
$job_id = get_the_ID();

$is_external = false;
if ( $job_id && function_exists( 'jobiizy_is_external_application' ) ) {
    $is_external = jobiizy_is_external_application( $job_id );
}
$application_form_id = absint(
    get_post_meta( $job_id, '_application_form', true )
);

// ID du formulaire par défaut (interne)
$INTERNAL_FORM_ID = 12181;
$is_external_job = (
    $application_form_id
    && $application_form_id !== $INTERNAL_FORM_ID
);
?>
<!-- DEBUG job-application.php external: <?php echo $is_external_job ? 'YES' : 'NO'; ?> -->
<?php 

/* if ( ! $is_external ) {
    // On ne rend AUCUN formulaire Cariera
    return;
}
*/


if ( $apply = get_the_job_application_method() ) :
	wp_enqueue_script( 'wp-job-manager-job-application' );
    $apply_text = __( 'Postuler', 'cariera' );
	?>

	<div class="job_application application">
		<?php do_action( 'job_application_start', $apply ); ?>

        <?php if ( is_user_logged_in() ) : ?>
            <!-- ✅ Connecté : bouton + popup -->
            <a href="#job-popup" class="application_button btn btn-main btn-effect popup-with-zoom-anim">
                <?php echo esc_html( $apply_text ); ?>
            </a>

            <div id="job-popup" class="small-dialog zoom-anim-dialog mfp-hide">
                <div class="job-app-msg">
                    <div class="small-dialog-headline">
                        <h3 class="title"><?php esc_html_e( 'Envoyer votre CV', 'cariera' ); ?></h3>
                    </div>
                    <div class="small-dialog-content">
                        <?php
                        // Formulaire natif seulement si connecté
                        do_action( 'job_manager_application_details_' . $apply->type, $apply );
                        ?>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <!-- ❌ Non connecté : seulement bouton vers #cv-form -->
            <a href="#cv-form" class="application_button btn btn-main btn-effect js-scroll-to-form">
                <?php echo esc_html( $apply_text ); ?>
            </a>
        <?php endif; ?>

		<?php do_action( 'job_application_end', $apply ); ?>
	</div>

<?php endif; ?>