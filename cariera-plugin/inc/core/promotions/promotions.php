<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cariera_Core_Promotions {

	use \Cariera_Core\Src\Traits\Singleton;

	/**
	 * Instance of Cariera_Core_Promotion_Package
	 */
	public $package;

	/**
	 * Instance of Cariera_Core_Promotions
	 */
	public $woocommerce;

	/**
	 * Construct
	 *
	 * @since  1.5.0
	 */
	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'include_files' ], 10 );
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	/**
	 * Include Files
	 *
	 * @since 1.5.0
	 */
	public function include_files() {
		include_once CARIERA_CORE_PATH . '/inc/core/promotions/package.php';
		include_once CARIERA_CORE_PATH . '/inc/core/promotions/promotions-wc.php';
		include_once CARIERA_CORE_PATH . '/inc/core/promotions/wc-product-listing-promotion.php';
	}

	/**
	 * Initialize promotions.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Return if WC is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Init Classes.
		$this->package     = Cariera_Core_Promotion_Package::instance();
		$this->woocommerce = Cariera_Core_Promotions_WC::instance();

		// Handle 'Buy Promotion' action in Promotions modal.
		add_action( 'wp_ajax_cariera_promotions', [ $this, 'handle_promotion_request' ] );

		// Check for expired Promos.
		add_action( 'cariera_check_expired_promotions', [ $this, 'check_for_expired_promotions' ] );
	}

	/**
	 * Get the Cariera_Core_Promotion_Package instance.
	 *
	 * @since 1.5.0
	 */
	public function package() {
		return $this->package;
	}

	/**
	 * Get the Cariera_Core_Promotions_WC instance.
	 *
	 * @since 1.5.0
	 */
	public function wc() {
		return $this->woocommerce;
	}

	/**
	 * Add the chosen promotion package to cart and proceed to checkout.
	 *
	 * @since 1.5.0
	 */
	public function handle_promotion_request() {
		check_ajax_referer( '_cariera_core_nonce', 'security' );
		$process = ! empty( $_POST['process'] ) ? sanitize_text_field( wp_unslash( $_POST['process'] ) ) : 'buy-package';

		try {
			// Validate request.
			if ( ! is_user_logged_in() || empty( $_POST['listing_id'] ) ) {
				throw new \Exception( __( 'Could not process request.', 'cariera-core' ), 10 );
			}

			// Verify it's a published listing and editable by current user.
			$listing = get_post( absint( $_POST['listing_id'] ) );
			if ( ! ( $listing && 'publish' === $listing->post_status ) ) {
				throw new \Exception( __( 'Could not process request.', 'cariera-core' ), 11 );
			}

			// Process when a user buys a new promotion package.
			if ( 'buy-package' === $process ) {
				if ( empty( $_POST['package_id'] ) ) {
					throw new \Exception( __( 'Could not process request.', 'cariera-core' ), 20 );
				}

				// Init "buy_function".
				$this->package->buy_package( $_POST['package_id'], $listing->ID ); // phpcs:ignore

				return wp_send_json(
					[
						'status'   => 'success',
						'redirect' => add_query_arg(
							[ 't' => time() ],
							WC()->cart->get_cart_contents_count() > 1 ? wc_get_cart_url() : wc_get_checkout_url()
						),
					]
				);
			}

			// Process when a user cancels their promotion package.
			if ( 'cancel-package' === $process ) {
				// Code here.
			}
		} catch ( \Exception $e ) {
			return wp_send_json(
				[
					'status'  => 'error',
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
				]
			);
		}
	}

	/**
	 * Activate promotion package.
	 *
	 * @since 1.5.0
	 *
	 * @param int  $package_id
	 * @param bool $listing_id
	 */
	public function activate_package( $package_id, $listing_id = false ) {
		global $wpdb;

		if ( ! ( $package = get_post( $package_id ) ) || 'cariera_promotion' !== $package->post_type ) {
			return false;
		}

		// If no listing id has been provided, see if there's one present in the package meta.
		if ( ! $listing_id ) {
			$listing_id = get_post_meta( $package_id, '_listing_id', true );
		}

		if ( ! ( $listing = get_post( $listing_id ) ) || ! in_array( $listing->post_type, [ 'job_listing', 'resume', 'company' ], true ) ) {
			return false;
		}

		// Add package info to listing.
		update_post_meta( $listing->ID, '_promo_package_id', $package_id );

		// Add listing info to package.
		update_post_meta( $package_id, '_listing_id', $listing->ID );

		// Return if listing is already featured.
		if ( $current_priority = get_post_meta( $listing->ID, '_featured', 1 ) ) {
			return;
		}

		// Make listing featured.
		update_post_meta( $listing->ID, '_featured', 1 );
		$wpdb->update( $wpdb->posts, [ 'menu_order' => -1 ], [ 'ID' => $listing->ID ] );

		// Clear caches once listing gets featured.
		if ( 'job_listing' === $listing->post_type ) {
			WP_Job_Manager_Cache_Helper::get_transient_version( 'get_job_listings', true );
		} elseif ( 'company' === $listing->post_type ) {
			WP_Job_Manager_Cache_Helper::get_transient_version( 'cariera_get_company_listings', true );
		} else {
			WP_Job_Manager_Cache_Helper::get_transient_version( 'get_resume_listings', true );
		}

		// Calculate promotion expiry date.
		$expires  = '';
		$duration = absint( get_post_meta( $package_id, '_duration', true ) );

		if ( $duration ) {
			$expires = date( 'Y-m-d H:i:s', strtotime( sprintf( '+%s days', $duration ), current_time( 'timestamp' ) ) ); // phpcs:ignore
		}

		// Update package status to active (published) and set it's expiry date.
		wp_update_post(
			[
				'ID'          => $package->ID,
				'post_status' => 'publish',
				'meta_input'  => [
					'_expires' => $expires,
				],
			]
		);

		do_action( 'cariera_listing_promotion_started', $listing->ID, $package_id );

		return true;
	}

	/**
	 * Change status to expired afte promotion has ended and remove package related data
	 *
	 * @since 1.5.0
	 *
	 * @param int $package_id
	 */
	public function expire_package( $package_id ) {
		global $wpdb;

		$package    = get_post( $package_id );
		$listing_id = get_post_meta( $package_id, '_listing_id', true );

		if ( ! $package || 'cariera_promotion' !== $package->post_type ) {
			return false;
		}

		if ( $listing_id ) {
			$listing = get_post( $listing_id );

			// Make the listing normal again.
			update_post_meta( $listing->ID, '_featured', 0 );

			// phpcs:ignore
			$wpdb->update(
				$wpdb->posts,
				[ 'menu_order' => 0 ],
				[
					'ID'         => $listing_id,
					'menu_order' => -1,
				]
			);

			// Clear caches on expiry.
			if ( 'job_listing' === $listing->post_type ) {
				WP_Job_Manager_Cache_Helper::get_transient_version( 'get_job_listings', true );
			} elseif ( 'company' === $listing->post_type ) {
				WP_Job_Manager_Cache_Helper::get_transient_version( 'cariera_get_company_listings', true );
			} else {
				WP_Job_Manager_Cache_Helper::get_transient_version( 'get_resume_listings', true );
			}

			// Delete other promotion data from listing meta.
			delete_post_meta( $listing_id, '_promo_package_id' );
		}

		// Delete package.
		wp_trash_post( $package_id );
		do_action( 'cariera_listing_promotion_ended', $listing_id, $package_id );
	}

	/**
	 * Check and trash expired promotion packages.
	 *
	 * @since 1.5.0
	 */
	public function check_for_expired_promotions() {
		global $wpdb;

		// Get package ids.
		$package_ids = $wpdb->get_col(
			$wpdb->prepare(
				"
			SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
			LEFT JOIN {$wpdb->posts} as posts ON postmeta.post_id = posts.ID
			WHERE postmeta.meta_key = '_expires'
			AND postmeta.meta_value > 0
			AND postmeta.meta_value < %s
			AND posts.post_status = 'publish'
			AND posts.post_type = 'cariera_promotion'
		",
				date( 'Y-m-d H:i:s', current_time( 'timestamp' ) )
			)
		);

		// Expire found packages.
		foreach ( (array) $package_ids as $package_id ) {
			$this->expire_package( $package_id );
		}
	}

	/**
	 * Check for promotions that are expiring soon.
	 *
	 * @since 1.8.0
	 */
	public static function check_for_expiring_soon_promotions() {
		$current_user = wp_get_current_user();

		$days_notice            = 8;
		$notice_before_datetime = current_datetime()->add( new DateInterval( 'P' . $days_notice . 'D' ) );

		$promotions = get_posts(
			[
				'post_type'      => 'cariera_promotion',
				'post_status'    => 'publish',
				'author'         => $current_user->ID,
				'fields'         => 'ids',
				'orderby'        => '_expires',
				'order'          => 'ASC',
				'posts_per_page' => 5,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Used in production with no issues.
					[
						'key'     => '_expires',
						'value'   => 0,
						'compare' => '>',
					],
					[
						'key'     => '_expires',
						'value'   => $notice_before_datetime->format( 'Y-m-d' ),
						'compare' => '<',
					],
				],
			]
		);

		return $promotions;
	}
}

new Cariera_Core_Promotions();
