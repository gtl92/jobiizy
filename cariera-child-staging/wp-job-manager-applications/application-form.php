<?php
/**
 * Job Application Form – Jobiizy Optimized
 *
 * Override: yourtheme/wp-job-manager-applications/application-form.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $post;

/* ======================================================
 * 1. CONTEXTE JOB
 * ====================================================== */
$job_id    = $post->ID;
$job_title = get_the_title( $job_id );

/* ======================================================
 * 2. FORMULAIRE CHOISI (pivot UNIQUE)
 * ====================================================== */
$form_id = 0;

// Priorité au POST (popup / submit)
if ( isset( $_POST['form_id'] ) ) {
    $form_id = (int) $_POST['form_id'];
} else {
    $form_id = (int) get_post_meta( $job_id, '_application_form', true );
}

/* ======================================================
 * 3. DÉTECTION MODE EXTERNE
 * ====================================================== */
$EXTERNAL_FORMS = [ 12183, 12245 ]; // à centraliser si besoin
$is_external_apply = in_array( $form_id, $EXTERNAL_FORMS, true );

/* ======================================================
 * 4. DONNÉES CANDIDATURE EXTERNE
 * ====================================================== */
$external_message = get_post_meta( $job_id, '_field_cfwjm12180', true );
$external_email   = get_post_meta( $job_id, '_field_cfwjm12185', true );
$external_notes   = get_post_meta( $job_id, '_field_cfwjm12770', true );
$external_link    = get_post_meta( $job_id, '_application', true );

// Sécurité / nettoyage
$external_message = trim( wp_strip_all_tags( (string) $external_message ) );
$external_email   = trim( sanitize_email( (string) $external_email ) );
$external_notes   = trim( wp_strip_all_tags( (string) $external_notes ) );
$external_link    = esc_url( $external_link );

/* ======================================================
 * 5. MODE CANDIDATURE EXTERNE
 * ====================================================== */
if ( $is_external_apply ) :
?>
    <div class="jobiizy-apply-external">

        <?php if ( $external_message ) : ?>
            <p class="jobiizy-apply-message">
                <?php echo esc_html( $external_message ); ?>
            </p>
        <?php endif; ?>

        <?php if ( $external_email ) : ?>
            <p class="jobiizy-apply-contact">
                📧 <strong>Email :</strong>
                <a href="mailto:<?php echo esc_attr( $external_email ); ?>">
                    <?php echo esc_html( $external_email ); ?>
                </a>
            </p>
        <?php endif; ?>

        <?php if ( $external_link ) : ?>
            <p class="jobiizy-apply-link">
                🔗 <a href="<?php echo esc_url( $external_link ); ?>" target="_blank" rel="noopener">
                    Accéder au lien de candidature
                </a>
            </p>
        <?php endif; ?>

        <?php if ( $external_notes ) : ?>
            <p class="jobiizy-apply-notes">
                <?php echo esc_html( $external_notes ); ?>
            </p>
        <?php endif; ?>

        <p class="jobiizy-apply-job">
            <strong>Poste :</strong> <?php echo esc_html( $job_title ); ?>
        </p>

    </div>

<?php
    // 🔴 IMPORTANT : on stoppe ici → aucun rendu Cariera
    return;
endif;

/* ======================================================
 * 6. MODE CANDIDATURE INTERNE (Cariera / WPJM)
 * ====================================================== */

// Sécurité : Cariera fournit ces variables
if ( empty( $application_fields ) || ! is_array( $application_fields ) ) {
    return;
}
?>

<form class="job-manager-application-form job-manager-form"
      method="post"
      enctype="multipart/form-data"
      action="<?php echo esc_url( get_permalink( $job_id ) ); ?>">

    <?php do_action( 'job_application_form_fields_start' ); ?>

    <?php foreach ( $application_fields as $key => $field ) : ?>

        <?php if ( 'output-content' === $field['type'] ) : ?>

            <div class="form-content">
                <h3><?php echo esc_html( wp_unslash( $field['label'] ) ); ?></h3>
                <?php if ( ! empty( $field['description'] ) ) : ?>
                    <?php echo wpautop( wp_kses_post( $field['description'] ) ); ?>
                <?php endif; ?>
            </div>

        <?php else : ?>

            <fieldset class="fieldset-<?php echo esc_attr( $key ); ?>">
                <label for="<?php echo esc_attr( $key ); ?>">
                    <?php
                        echo wp_unslash( $field['label'] );
                        echo $field['required']
                            ? ''
                            : ' <small>(' . esc_html__( 'optional', 'wp-job-manager-applications' ) . ')</small>';
                    ?>
                </label>

                <div class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
                    <?php $class->get_field_template( $key, $field ); ?>
                </div>
            </fieldset>

        <?php endif; ?>

    <?php endforeach; ?>

    <?php do_action( 'job_application_form_fields_end' ); ?>

    <p class="jobiizy-apply-submit">
        <input type="submit"
               class="button wp_job_manager_send_application_button"
               value="<?php esc_attr_e( 'Send application', 'wp-job-manager-applications' ); ?>" />

        <input type="hidden" name="wp_job_manager_send_application" value="1" />
        <input type="hidden" name="job_id" value="<?php echo absint( $job_id ); ?>" />
        <input type="hidden" name="form_id" value="<?php echo absint( $form_id ); ?>" />
    </p>

</form>