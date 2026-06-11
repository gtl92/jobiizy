<?php
/**
 * Application form shown on job listing page.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-applications/application-form.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-job-manager-applications
 * @category    Template
 * @version     3.0.1
 */
echo "<!-- JOBIIZY application-form_copie.php -->\n";

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;
echo '<pre>';
echo '<strong>DEBUG PHP :</strong><br>';
echo 'Valeur de $form_id : ' . esc_html( $form_id ) . '<br>';
echo 'Valeur de get_the_ID() : ' . esc_html( get_the_ID() ) . '<br>';
echo '</pre>';
$job_type = get_post_type( $job_id );
echo '<p>Type de contenu de $job_id : ' . esc_html( $job_type ) . '</p>';

$captcha_version = ( class_exists('WP_Job_Manager\WP_Job_Manager_Recaptcha') && get_option( 'job_application_enable_recaptcha_application_submission' ) )
    ? WP_Job_Manager\WP_Job_Manager_Recaptcha::instance()->get_recaptcha_version()
    : null;

?>
<!-- <form class="job-manager-application-form job-manager-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( get_permalink() ); ?>"> -->
<form class="job-manager-application-form job-manager-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( get_permalink() . '?form_id=' . get_the_ID() ); ?>">

<?php
// Récupérer l'objet de l'offre d'emploi
//  GTL MODIFICATION pour la recuperation du job_id ( ne fonctionne pas avec absint( $_POST['job_id'] ))
//  $job_id = absint( $_POST['job_id'] ); 
$job_id = get_the_ID();
$job = get_post( $job_id );
// echo '<p>Job ID 00: ' . esc_html( $job_id ) . '</p>';
// Si on veut récupérer d'autres champs debug de tous les champs meta 
// Afficher toutes les métadonnées du job
//  $all_meta = get_post_meta( $job_id );
//  echo '<pre>';
//  print_r( $all_meta );
//  echo '</pre>';

//  TEST GTL
// Vérifier si l’offre existe
if ( $job ) {
    $job_title = get_the_title( $job_id ); // Titre de l’offre
    $contact_email = get_post_meta( $job_id, '_application', true ); // Email de contact

    // Récupérer le custom field
    $custom_field_value_message = get_post_meta( $job_id, '_field_cfwjm12180', true ); // Remplace "job_custom_field" par ton propre nom de custom field
//    if ( ! empty( $custom_field_value_message ) ) {
//        echo '<p><strong>Custom message:</strong> ' . esc_html( $custom_field_value_message ) . '</p>';
//    }
    $custom_field_value_mail = get_post_meta( $job_id, '_field_cfwjm12185', true ); // Remplace "job_custom_field" par ton propre nom de custom field
//    if ( ! empty( $custom_field_value_mail ) ) {
//        echo '<p><strong>Custom mail:</strong> ' . esc_html( $custom_field_value_mail ) . '</p>';
//    }

    // Affichage du titre et de l'email de contact
}

	// $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0; 
	if ( isset( $_POST['form_id'] ) && ! empty( $_POST['form_id'] ) ) {
    	$form_id = absint( $_POST['form_id'] );
	}

	if ( $form_id ) {
    	$form_title = get_the_title( $form_id ); // Récupère le nom du formulaire
    	// echo '<p>Nom du formulaire : <strong>' . esc_html( $form_title ) . ' ('.  esc_html( $form_id ) . ') </strong></p>';
	}
    // echo '<p><strong>Contact Jobiizy 88:</strong> <a href="mailto:' . esc_attr( $contact_email ) . '">' . esc_html( $contact_email ) . '</a></p>';
?>

<?php
if ( $form_id == 12245 ) { // Formulaire Externe Contact avec inscription
    echo '<p>Compléter les informations ci-dessous pour bénéficier des avantages d\'être visible sur la platforme.</p>
          <p> <h7 style="color:blue;">Pour encore plus de services, <strong>inscrivez-vous.</strong></h7></p>';
}
?>

	<?php do_action( 'job_application_form_fields_start' ); ?>

	
	<?php foreach ( $application_fields as $key => $field ) : ?>
		<?php if ( 'output-content' === $field['type'] ) : ?>
			<div class="form-content">
				<h3><?php esc_html( wp_unslash( $field['label'] ) ); ?></h3>
				<?php
				if ( ! empty( $field['description'] ) ) :
					?>
					<?php echo wpautop( wp_kses_post( $field['description'] ) ); ?><?php endif; ?>
			</div>
		<?php else : ?>
			<fieldset class="fieldset-<?php echo esc_attr( $key ); ?>">
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_unslash( $field['label'] ) . apply_filters( 'submit_job_form_required_label', $field['required'] ? '' : ' <small>' . __( '(optional)', 'wp-job-manager-applications' ) . '</small>', $field ); ?></label>
				<div class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
					<?php $class->get_field_template( $key, $field ); ?>
				</div>
			</fieldset>
		<?php endif; ?>
	<?php endforeach; ?>

<?php
if ( $form_id == 12183 ) { // Formulaire offre externe
     echo '<p><strong>Pour postuler,</strong> </p>';

    if ( ! empty( $custom_field_value_mail ) ) {
//        echo '<p><strong>Custom mail:</strong> ' . esc_html( $custom_field_value_mail ) . '</p>';
    	echo '<p>veuillez envoyer votre candidature à :<br>
          <strong><a href="mailto:' . esc_html( $custom_field_value_mail ) . '">' . esc_html( $custom_field_value_mail ) . '</a></strong></p>';
    }
    if ( ! empty( $custom_field_value_message ) ) {
        // echo '<p><strong>Custom message:</strong> ' . esc_html( $custom_field_value_message ) . '</p>';
        echo '<p>' . esc_html( $custom_field_value_message ) . '</p>';
    }
    echo '<p>En indiquant le nom du poste :<br>
          <strong>' . esc_html( $job_title ) . '</strong></p>';
} elseif ( $form_id == 12245 ) { // Formulaire Externe Contact avec inscription
//    echo '<p>Compléter les informations ci-dessous pour bénéficier des avantages d être visible sur la platforme <br>
//             Pour encore plus de services, inscrivez-vous.</p>';
} else { // Valeur par défaut
    echo '<p>Merci de postuler en remplissant ce formulaire.</p>';
}
?>


	<?php do_action( 'job_application_form_fields_end' ); ?>

	<p>
		<input type="submit" class="button wp_job_manager_send_application_button" value="<?php esc_attr_e( 'Send application', 'wp-job-manager-applications' ); ?>" />
		<input type="hidden" name="wp_job_manager_send_application" value="1" />
		<input type="hidden" name="job_id" value="<?php echo absint( $post->ID ); ?>" />
		<input type="hidden" name="form_id" value="<?php echo absint( $form_id ); ?>" />
	</p>
</form>
