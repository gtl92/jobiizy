<?php

namespace Cariera_Core\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Migrations {

	use \Cariera_Core\Src\Traits\Singleton;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_cariera_company_jobs_count_fix', [ $this, 'company_job_count' ] );
		add_action( 'wp_ajax_cariera_geolocate_listings', [ $this, 'geolocate_listings' ] );
		add_action( 'wp_ajax_cariera_db_cleanup', [ $this, 'db_cleanup' ] );
	}

	/**
	 * Check if the migrations are enabled.
	 *
	 * @since 1.8.3
	 */
	protected function is_enabled() {
		return get_option( 'cariera_migrations' );
	}

	/**
	 * Get migration items.
	 *
	 * @since   1.8.3
	 * @version 1.8.5
	 */
	public static function get_migration_items() {
		$migration_items = [
			[
				'name'        => esc_html__( 'Company Job Count', 'cariera-core' ),
				'action'      => 'cariera_company_jobs_count_fix',
				'type'        => '', // link - to make it a link.
				'link'        => '',
				'btn_title'   => esc_html__( 'Fix Job Count', 'cariera-core' ),
				'icon'        => 'lab la-wordpress-simple',
				'description' => esc_html__( 'Update the "_active_jobs" meta for company listings. Clicking the button will calculate the active jobs for each company and save the result as updated meta data.', 'cariera-core' ),
			],
			[
				'name'        => esc_html__( 'Geolocate Listings', 'cariera-core' ),
				'action'      => 'cariera_geolocate_listings',
				'type'        => '', // link - to make it a link.
				'link'        => '',
				'btn_title'   => esc_html__( 'Generate Geolocation Data', 'cariera-core' ),
				'icon'        => 'lab la-wordpress-simple',
				'description' => esc_html__( 'Start generating geolocation data for all your listings. Ensure that you\'ve added a valid Google API Key in "WP Dashboard → Job Manager → Settings".', 'cariera-core' ),
			],
			[
				'name'        => esc_html__( 'Database Cleanup', 'cariera-core' ),
				'action'      => 'cariera_db_cleanup',
				'type'        => '', // link - to make it a link.
				'link'        => '',
				'btn_title'   => esc_html__( 'Clean Database', 'cariera-core' ),
				'icon'        => 'lab la-wordpress-simple',
				'description' => esc_html__( 'By clicking "Clean Database" all options that have been deleted from the theme and still exist in your database will be deleted.', 'cariera-core' ),
			],
		];

		return apply_filters( 'cariera_migration_items', $migration_items );
	}

	/**
	 * Calculate the number of job listings a company has and save them as a meta.
	 *
	 * @since   1.8.2
	 * @version 1.8.4
	 */
	public function company_job_count() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Verify the nonce for security.
		check_ajax_referer( '_cariera_core_admin_nonce', 'nonce' );

		$next_data = 50;
		$offset    = 0;

		do {
			$listings = (array) get_posts(
				[
					'post_type'      => 'company',
					'offset'         => $offset,
					'posts_per_page' => $next_data,
					'post_status'    => [ 'publish', 'pending', 'private', 'expired' ],
					'meta_query'     => [],
				]
			);

			foreach ( $listings as $listing ) {
				$jobs_count = cariera_get_the_company_job_listing_active_count( $listing->ID );

				update_post_meta( $listing->ID, '_active_jobs', $jobs_count );
			}
			$offset = ( ! $offset ) ? $next_data : $offset + $next_data;
		} while ( ! empty( $listings ) );

		wp_send_json_success( 'Active job listings are calculated for each company!' );
	}

	/**
	 * Generate geolocation data for all listings.
	 *
	 * @since   1.8.3
	 * @version 1.8.4
	 */
	public function geolocate_listings() {
		// Verify the nonce for security.
		check_ajax_referer( '_cariera_core_admin_nonce', 'nonce' );

		$google_maps_api_key = get_option( 'job_manager_google_maps_api_key' );
		if ( empty( $google_maps_api_key ) ) {
			wp_send_json_error( 'You need a valid Google Map API key for geolocation.' );
		}

		// Define the post types to be processed.
		$post_types = [ 'job_listing', 'resume', 'company' ];

		// Setting limits and handling large queries.
		$next_data       = 50; // Number of listings processed per batch.
		$offset          = 0;  // Start offset for pagination.
		$total_processed = 0;
		$max_retries     = 3;  // Max number of retries for geolocation failures.

		// Ensure script doesn't timeout for large datasets.
		set_time_limit( 0 );

		// Temporarily suspend cache invalidation to improve performance.
		wp_suspend_cache_invalidation( true );

		// Process each post type in batches.
		do {
			$query = new \WP_Query(
				[
					'post_type'      => $post_types, // Query all post types in one go.
					'offset'         => $offset,
					'posts_per_page' => $next_data,
					'post_status'    => [ 'publish', 'private', 'expired' ],
					'meta_query'     => [
						'relation' => 'OR',
						[
							'key'     => 'geolocation_lat',
							'compare' => 'NOT EXISTS',
						],
						[
							'key'     => 'geolocation_long',
							'compare' => 'NOT EXISTS',
						],
						[
							'key'   => 'geolocation_lat',
							'value' => '',
						],
						[
							'key'   => 'geolocation_long',
							'value' => '',
						],
					],
				]
			);

			if ( is_wp_error( $query ) ) {
				wp_send_json_error( 'Failed to query posts for geolocation. ' . $query->get_error_message() );
			}

			// Loop through the listings and process them.
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$post_type  = get_post_type();
					$listing_id = get_the_ID();

					// Choose the location meta key based on the post type.
					switch ( $post_type ) {
						case 'job_listing':
							$location = get_post_meta( $listing_id, '_job_location', true );
							break;
						case 'resume':
							$location = get_post_meta( $listing_id, '_candidate_location', true );
							break;
						case 'company':
							$location = get_post_meta( $listing_id, '_company_location', true );
							break;
						default:
							$location = false;
					}

					if ( ! $location ) {
						\Cariera\write_log( sprintf( 'Missing address for %s #%d', $post_type, $listing_id ) );
						continue;
					}

					// Retry mechanism in case of geolocation failure.
					$geocoded = false;
					$attempt  = 0;
					while ( $attempt < $max_retries && $geocoded === false ) {
						$geocoded = \WP_Job_Manager_Geocode::generate_location_data( $listing_id, $location );
						if ( $geocoded === false ) {
							++$attempt;
							if ( $attempt < $max_retries ) {
								\Cariera\write_log( sprintf( 'Retrying geolocation for %s #%d (%s)', $post_type, $listing_id, $location ) );
							}
						}
					}

					if ( $geocoded !== false ) {
						\Cariera\write_log( sprintf( 'Geolocation successful for %s #%d (%s)', $post_type, $listing_id, $location ) );
					} else {
						\Cariera\write_log( sprintf( 'Failed to geolocate %s #%d (%s) after %d attempts', $post_type, $listing_id, $location, $max_retries ) );
					}
				}
			}

			// Clear the object cache after each batch to reduce memory usage.
			wp_cache_flush();

			// Move to the next batch of listings.
			$offset          += $next_data;
			$total_processed += $query->post_count;

		} while ( $query->have_posts() );

		// Restore cache invalidation and script limits.
		wp_suspend_cache_invalidation( false );

		wp_send_json_success( sprintf( 'Geolocation completed. Total listings processed: %d.', $total_processed ) );
	}

	/**
	 * Delete options from the database that do not exist anymore.
	 *
	 * @since   1.8.5
	 * @version 1.8.9
	 */
	public function db_cleanup() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Verify the nonce for security.
		check_ajax_referer( '_cariera_core_admin_nonce', 'nonce' );

		// Get all Kirki options.
		$theme_options = [
			// Kirki Options.
			'cariera_dashboard_page_enable',
			'cariera_dashboard_job_alerts_page_enable',
			'cariera_dashboard_bookmark_page_enable',
			'cariera_dashboard_applied_jobs_page_enable',
			'cariera_dashboard_user_packages_page_enable',
			'cariera_dashboard_orders_page_enable',
			'cariera_dashboard_job_submission_page_enable',
			'cariera_dashboard_company_submission_page_enable',
			'cariera_dashboard_resume_submission_page_enable',
			'cariera_dashboard_profile_page_enable',
			'cariera_max_radius_search_value',
			'cariera_job_location_autocomplete',
			'cariera_map_restriction',
			'cariera_job_auto_location',
			'cariera_radius_unit',
			'cariera_max_radius_search_value',
			'cariera_map_height',

			// Native Options.
		];

		foreach ( $theme_options as $option ) {
			delete_option( $option );
		}

		wp_send_json_success( 'All options have been deleted from your Database!' );
	}
}
