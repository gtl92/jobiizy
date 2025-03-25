<?php
/**
 * Cariera Dashboard - Expiring Promotions template
 *
 * This template can be overridden by copying it to cariera-child/cariera_core/account/dashboard/expiring-promotions.php.
 *
 * @package     cariera
 * @category    Template
 * @since       1.8.0
 * @version     1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WP_Job_Manager' ) ) {
	return;
}

if ( ! get_option( 'cariera_job_promotions' ) && ! get_option( 'cariera_company_promotions' ) && ! get_option( 'cariera_resume_promotions' ) ) {
	return;
}

$promotions = \Cariera_Core_Promotions::check_for_expiring_soon_promotions();
?>

<div class="dashboard-card-box dashboard-content-packages">
	<div class="dashboard-card-title">
		<h3 class="title"><?php esc_html_e( 'Expiring Promotions', 'cariera-core' ); ?></h3>
	</div>

	<div class="dashboard-card-box-inner">
		<ul class="cariera-dashboard-list expiring-promotions">
			<?php
			if ( $promotions ) {
				foreach ( $promotions as $promotion ) {
					$promotion        = get_post( $promotion );
					$promotion_expire = $promotion->_expires;
					$listing          = get_post( $promotion->_listing_id );
					?>

					<li>
						<i class="las la-bolt"></i>
						<div class="content">
							<a href="<?php echo the_permalink( $listing->ID ); ?>" target="_blank"><h6 class="listing-title"><?php echo esc_html( $listing->post_title ); ?></h6></a>
							<div class="listing-expires"><small><?php echo \WP_Job_Manager\UI\UI_Elements::rel_time( $promotion_expire, esc_html__( 'Expires in %s', 'cariera-core' ) ); ?></small></div>
						</div>
					</li>
					<?php
				}
			} else {
				?>
				<li><?php esc_html_e( 'There are no expiring promotions.', 'cariera-core' ); ?></li>
			<?php } ?>
		</ul>
	</div>
</div>
