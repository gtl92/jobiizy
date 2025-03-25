<?php
/**
 * Cariera Dashboard template
 *
 * This template can be overridden by copying it to cariera-child/cariera_core/account/dashboard.php.
 *
 * @package     cariera
 * @category    Template
 * @since       1.5.2
 * @version     1.8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_roles;

if ( ! is_user_logged_in() ) { ?>
	<p><?php esc_html_e( 'You need to be signed in to access your dashboard.', 'cariera-core' ); ?></p>

	<?php
	$login_registration = get_option( 'cariera_login_register_layout' );

	if ( 'popup' === $login_registration ) {
		?>
		<a href="#login-register-popup" class="btn btn-main btn-effect popup-with-zoom-anim">
		<?php
	} else {
		$login_registration_page     = apply_filters( 'cariera_login_register_page', get_option( 'cariera_login_register_page' ) );
		$login_registration_page_url = get_permalink( $login_registration_page );
		?>

		<a href="<?php echo esc_url( $login_registration_page_url ); ?>" class="btn btn-main btn-effect">
		<?php
	}
		esc_html_e( 'Sign in', 'cariera-core' );
	?>
	</a>
	<?php
} else {
	// Dashboard Cards.
	cariera_get_template_part( 'account/dashboard/cards' );
	?>

	<!-- Start of Charts & Packages -->
	<div class="row mt20">
		<div class="col-lg-8 col-md-12">
			<?php
			cariera_get_template_part( 'account/dashboard/views-charts' );
			cariera_get_template_part( 'account/dashboard/active-packages' );
			?>
		</div>
		
		<div class="col-lg-4 col-md-12">
			<?php
			cariera_get_template_part( 'account/dashboard/expiring-listings' );
			cariera_get_template_part( 'account/dashboard/expiring-promotions' );
			cariera_get_template_part( 'account/dashboard/applications' );
			?>
		</div>
	</div>
	<?php
}
