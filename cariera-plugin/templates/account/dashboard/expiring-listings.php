<?php
/**
 * Cariera Dashboard - Expiring Listings template
 *
 * This template can be overridden by copying it to cariera-child/cariera_core/account/dashboard/expiring-listings.php.
 *
 * @package     cariera
 * @category    Template
 * @since       1.8.0
 * @version     1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Job_Manager' ) ) {
	return;
}

$current_user = wp_get_current_user();
$listing_ids  = cariera_check_soon_to_expire_listings();
$expire_meta  = '_job_expires';
$icon_class   = 'las la-briefcase';

if ( in_array( 'candidate', (array) $current_user->roles, true ) ) {
	$listing_ids = cariera_check_soon_to_expire_listings( 'resume' );
	$expire_meta = '_resume_expires';
	$icon_class  = 'las la-user-tie';
}
?>

<div class="dashboard-card-box dashboard-content-expiring-listings">
	<div class="dashboard-card-title">
		<h3 class="title"><?php esc_html_e( 'Expiring Soon Listings', 'cariera-core' ); ?></h3>
	</div>

	<div class="dashboard-card-box-inner">
		<ul class="cariera-dashboard-list expiring-listings">
			<?php
			if ( $listing_ids ) {
				foreach ( $listing_ids as $listing_id ) {
					$listing    = get_post( $listing_id );
					$expiration = $listing->{$expire_meta};
					?>

					<li>
						<i class="<?php echo esc_attr( $icon_class ); ?>"></i>
						<div class="content">
							<a href="<?php echo the_permalink( $listing_id ); ?>" target="_blank"><h6 class="listing-title"><?php echo esc_html( $listing->post_title ); ?></h6></a>
							<div class="listing-expires"><small><?php echo \WP_Job_Manager\UI\UI_Elements::rel_time( $expiration, esc_html__( 'Expires in %s', 'cariera-core' ) ); ?></small></div>
						</div>
					</li>
					<?php
				}
			} else {
				?>
				<li><?php esc_html_e( 'No listings are expiring soon.', 'cariera-core' ); ?></li>
			<?php } ?>
		</ul>
	</div>
</div>
