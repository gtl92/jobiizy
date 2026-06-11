<?php
/**
 * Content for job submission (`[submit_job_form]`) shortcode.
 *
 * @package     wp-job-manager
 * @version     1.34.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "<!-- JOBIIZY job_submit -->\n";

wp_enqueue_style( 'cariera-wpjm-submissions' );

$captcha_version = WP_Job_Manager\WP_Job_Manager_Recaptcha::instance()->get_recaptcha_version();

// -----------------------------------------------------------------------------
// JobiiZy – CONTEXTE UTILISATEUR
// -----------------------------------------------------------------------------
$is_admin_can_manage_apply = current_user_can( 'manage_options' );

// -----------------------------------------------------------------------------
// JobiiZy – Détection offre EXTERNE / INTERNE
// -----------------------------------------------------------------------------
$is_external_job = (
    isset( $job_fields['_jobiizy_is_external']['value'] )
    && $job_fields['_jobiizy_is_external']['value'] === '1'
);
$form_class = $is_external_job ? 'jobiizy-form-external' : 'jobiizy-form-internal';
// Règle métier :
// → Admin : toujours voir les champs externes
// → Non-admin : seulement si l’offre est déjà externe
$show_external_fields = $is_admin_can_manage_apply || $is_external_job;

// -----------------------------------------------------------------------------
// Champs liés à la candidature externe
// -----------------------------------------------------------------------------
$external_fields = [
    'application_form',
    'field_cfwjm12180', // message
    'field_cfwjm12185', // email
    'field_cfwjm12770', // notes
];

// Séparation des champs
$enabled_group  = [];
$disabled_group = [];

foreach ( $job_fields as $key => $field ) {
    if ( in_array( $key, $external_fields, true ) ) {
        $disabled_group[ $key ] = $field;
    } else {
        $enabled_group[ $key ] = $field;
    }
}
?>

<form action="<?php echo esc_url( $action ); ?>" method="post" id="submit-job-form" class="job-manager-form" enctype="multipart/form-data">

<?php do_action( 'submit_job_form_start' ); ?>
<?php
// ================================
// JOBIIZY – Bouton Preview annonce
// ================================

$job_id = $job_id ?? 0;

$can_preview = false;
$preview_url = '';

if ( $job_id ) {
    $can_preview = true;

    if ( get_post_status( $job_id ) === 'publish' ) {
        $preview_url = get_permalink( $job_id );
    } else {
        $preview_url = get_preview_post_link( $job_id );
    }
}

$is_external = $job_id ? jobiizy_is_external_application( $job_id ) : false;
?>

<div class="jobiizy-preview-toolbar">
    <div class="jobiizy-preview-left">
        <?php if ( $is_external ) : ?>
            <span class="jobiizy-preview-badge external">
                🟠 Offre externe
            </span>
        <?php else : ?>
            <span class="jobiizy-preview-badge internal">
                🔵 Offre interne
            </span>
        <?php endif; ?>
    </div>

    <div class="jobiizy-preview-right">
        <?php if ( $can_preview ) : ?>
            <button
                type="button"
                class="button button-secondary jobiizy-preview-open"
                data-preview-url="<?php echo esc_url( $preview_url ); ?>">
                👁️ Aperçu
            </button>
        <?php else : ?>
            <button class="button button-secondary" disabled>
                👁️ Aperçu
            </button>
        <?php endif; ?>
    </div>
</div>

<div id="jobiizy-preview-modal" class="jobiizy-modal" hidden>
    <div class="jobiizy-modal-overlay"></div>

    <div class="jobiizy-modal-box">
        <div class="jobiizy-modal-header">
            <strong>Aperçu de l’annonce</strong>

            <!-- ✅ Bouton close clair -->
            <button
                type="button"
                class="jobiizy-modal-close"
                aria-label="Fermer l’aperçu">
                ✕
            </button>
        </div>

        <iframe
            class="jobiizy-preview-frame"
            src=""
            loading="lazy">
        </iframe>

        <!-- ✅ Footer avec bouton explicite -->
        <div class="jobiizy-modal-footer">
            <button
                type="button"
                class="button button-secondary jobiizy-modal-close">
                Fermer
            </button>
        </div>
    </div>
</div>


<?php if ( apply_filters( 'submit_job_form_show_signin', true ) ) : ?>
    <?php get_job_manager_template( 'account-signin.php' ); ?>
<?php endif; ?>

<?php if ( job_manager_user_can_post_job() || job_manager_user_can_edit_job( $job_id ) ) : ?>

<?php do_action( 'submit_job_form_job_fields_start' ); ?>

<!-- ===================================================== -->
<!-- Champs STANDARD -->
<!-- ===================================================== -->

<?php foreach ( $enabled_group as $key => $field ) : ?>

    <?php
    $custom_class = '';

    if ( in_array( $key, [ 'job_location', 'job_description' ], true ) ) {
        $custom_class = ' full-width';
    }
    ?>

    <fieldset class="fieldset-<?php echo esc_attr( $key ); ?><?php echo esc_attr( $custom_class ); ?>">
        <label style="font-weight: bold;"  for="<?php echo esc_attr( $key ); ?>">
            <?php echo wp_kses_post( $field['label'] ); ?>
        </label>
        <div class="field">
            <?php
            get_job_manager_template(
                'form-fields/' . $field['type'] . '-field.php',
                [
                    'key'   => $key,
                    'field' => $field,
                ]
            );
            ?>
        </div>
    </fieldset>

<?php endforeach; ?>

<!-- ===================================================== -->
<!-- Champs CANDIDATURE EXTERNE (Jobiizy) -->
<!-- ===================================================== -->

<?php if ( $show_external_fields && ! empty( $disabled_group ) ) : ?>

    <h3 class="title titreperso">
        <?php esc_html_e( 'Type de candidature', 'jobiizy' ); ?>
    </h3>
    <p class="description job-manager-message" style="color:#00000080;width:100%">
       <strong>
            <?php esc_html_e(
                'Ces options permettent de transformer l’offre en candidature externe.',
                'jobiizy'
            ); ?>
       </strong>
   </p>
<div id="jobiizy-application-mode"
     class="jobiizy-application-mode"
     data-internal-form-id="<?php echo esc_attr( jobiizy_get_internal_application_form_id() ); ?>">

   	<div class="disabled-fields-group hidden-field  jobiizy-external-group "  data-jobiizy-external-group   >
    	<div class="jobiizy-external-fields">

        <?php foreach ( $disabled_group as $key => $field ) : ?>

            <?php
            $readonly = ( ! $is_admin_can_manage_apply ) ? 'readonly="readonly"' : '';
            ?>

            <fieldset class="fieldset-<?php echo esc_attr( $key ); ?>">
                <label style="font-weight: bold;" for="<?php echo esc_attr( $key ); ?>">
                    <?php echo wp_kses_post( $field['label'] ); ?>
                </label>

                <div class="field">
                    <?php
                    get_job_manager_template(
                        'form-fields/' . $field['type'] . '-field.php',
                        [
                            'key'      => $key,
                            'field'    => $field,
                            'readonly' => $readonly,
                        ]
                    );
                    ?>
                </div>
            </fieldset>

        <?php endforeach; ?>

    </div>
	</div>
	</div>
<?php endif; ?>

<?php do_action( 'submit_job_form_job_fields_end' ); ?>

<!-- ===================================================== -->
<!-- Champs ENTREPRISE -->
<!-- ===================================================== -->

<?php if ( $company_fields ) : ?>

    <h2><?php esc_html_e( 'Company Details', 'cariera' ); ?></h2>

    <?php do_action( 'submit_job_form_company_fields_start' ); ?>

    <?php foreach ( $company_fields as $key => $field ) : ?>
        <fieldset class="fieldset-<?php echo esc_attr( $key ); ?>">
            <label for="<?php echo esc_attr( $key ); ?>">
                <?php echo wp_kses_post( $field['label'] ); ?>
            </label>
            <div class="field">
                <?php
                get_job_manager_template(
                    'form-fields/' . $field['type'] . '-field.php',
                    [
                        'key'   => $key,
                        'field' => $field,
                    ]
                );
                ?>
            </div>
        </fieldset>
    <?php endforeach; ?>

    <?php do_action( 'submit_job_form_company_fields_end' ); ?>

<?php endif; ?>

<?php do_action( 'submit_job_form_end' ); ?>

<!-- ===================================================== -->
<!-- SUBMIT -->
<!-- ===================================================== -->

<div class="cariera-listing-submission">

    <input type="hidden" name="job_manager_form" value="<?php echo esc_attr( $form ); ?>" />
    <input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ); ?>" />
    <input type="hidden" name="step" value="<?php echo esc_attr( $step ); ?>" />

    <input type="submit"
        name="submit_job"
        class="button"
        value="<?php echo esc_attr( $submit_button_text ); ?>"
        <?php if ( 'v3' === $captcha_version ) echo 'onclick="jm_job_submit_click(event)"'; ?>
    />

</div>

<?php else : ?>

<?php do_action( 'submit_job_form_disabled' ); ?>

<?php endif; ?>

</form>