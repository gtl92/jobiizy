<?php

namespace Cariera_Core\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets {

	use \Cariera_Core\Src\Traits\Singleton;

	/**
	 * Constructor function.
	 *
	 * @since   1.4.3
	 * @version 1.6.2
	 */
	public function __construct() {
		// Register Assets.
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ] );

		// Enqueue Assets.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ], 20 );
	}

	/**
	 * Register Core Plugin assets.
	 *
	 * @since   1.6.3
	 * @version 1.8.9
	 */
	public function register_assets() {
		$version = function_exists( '\Cariera\is_dev_mode' ) && \Cariera\is_dev_mode() ? wp_rand( 1, 1e4 ) : CARIERA_CORE_VERSION;
		$suffix  = is_rtl() ? '.rtl' : '';

		// Main Core Frontend.
		wp_register_script( 'cariera-core-main', CARIERA_URL . '/assets/dist/js/frontend.js', [ 'jquery' ], $version, true );

		$args = [
			'ajax_url'      => esc_url( admin_url( 'admin-ajax.php', 'relative' ) ),
			'nonce'         => wp_create_nonce( '_cariera_core_nonce' ),
			'is_rtl'        => is_rtl() ? 1 : 0,
			'home_url'      => esc_url( home_url( '/' ) ),
			'upload_ajax'   => admin_url( 'admin-ajax.php?action=handle_uploaded_media' ),
			'delete_ajax'   => admin_url( 'admin-ajax.php?action=handle_deleted_media' ),
			'max_file_size' => apply_filters( 'cariera_file_max_size', size_format( wp_max_upload_size() ) ),
			'map_provider'  => get_option( 'cariera_map_provider' ),
			'strings'       => [
				'delete_account_text' => esc_html__( 'Are you sure you want to delete your account?', 'cariera-core' ),
				'notification_loader' => esc_html__( 'No more notifications.', 'cariera-core' ),
			],
		];

		wp_localize_script( 'cariera-core-main', 'cariera_core_settings', $args );

		// WPJM Ajax Filters.
		if ( class_exists( 'WP_Job_Manager' ) && defined( 'JOB_MANAGER_VERSION' ) ) {
			wp_dequeue_script( 'wp-job-manager-ajax-filters' );
			wp_deregister_script( 'wp-job-manager-ajax-filters' );
			wp_register_script( 'wp-job-manager-ajax-filters', CARIERA_URL . '/assets/dist/js/jobs-ajax-filters.js', [ 'jquery', 'jquery-deserialize' ], $version, true );
			wp_localize_script(
				'wp-job-manager-ajax-filters',
				'job_manager_ajax_filters',
				[
					'ajax_url'                => \WP_Job_Manager_Ajax::get_endpoint(),
					'is_rtl'                  => is_rtl() ? 1 : 0,
					'i18n_load_prev_listings' => esc_html__( 'Load previous listings', 'cariera-core' ),
					'currency'                => \cariera_currency_symbol(),
				]
			);
		}

		// Resume AJAX Filters.
		if ( class_exists( 'WP_Job_Manager' ) && class_exists( 'WP_Resume_Manager' ) ) {
			wp_dequeue_script( 'wp-resume-manager-ajax-filters' );
			wp_deregister_script( 'wp-resume-manager-ajax-filters' );
			wp_register_script( 'wp-resume-manager-ajax-filters', CARIERA_URL . '/assets/dist/js/resumes-ajax-filters.js', [ 'jquery', 'jquery-deserialize' ], $version, true );
			wp_localize_script(
				'wp-resume-manager-ajax-filters',
				'resume_manager_ajax_filters',
				[
					'ajax_url'    => esc_url( admin_url( 'admin-ajax.php', 'relative' ) ),
					'currency'    => \cariera_currency_symbol(),
					'showing_all' => esc_html__( 'Showing all resumes', 'cariera-core' ),
				]
			);
		}

		// Maps.
		wp_register_script( 'cariera-maps', CARIERA_URL . '/assets/dist/js/maps.js', [ 'jquery' ], $version, true );
		wp_localize_script(
			'cariera-maps',
			'cariera_maps',
			[
				'map_provider'        => get_option( 'cariera_map_provider' ),
				'autolocation'        => get_option( 'cariera_location_autocomplete' ) ? true : false,
				'country'             => get_option( 'cariera_location_autocomplete_restriction' ),
				'map_autofit'         => get_option( 'cariera_map_autofit' ),
				'centerPoint'         => get_option( 'cariera_map_center' ),
				'mapbox_access_token' => get_option( 'cariera_mapbox_access_token' ),
				'map_type'            => get_option( 'cariera_maps_type' ),
				'strings'             => [
					'gelocation_error_denied'     => esc_html__( 'Location permission was denied. Please allow location access to use this feature.', 'cariera-core' ),
					'gelocation_error_unvailable' => esc_html__( 'Location information is unavailable.', 'cariera-core' ),
					'gelocation_error_timeout'    => esc_html__( 'The request to get user location timed out.', 'cariera-core' ),
				],
			]
		);

		// Backend.
		wp_register_style( 'cariera-core-admin', CARIERA_URL . '/assets/dist/css/backend/admin' . $suffix . '.css', [], $version );
		wp_register_script( 'cariera-core-admin', CARIERA_URL . '/assets/dist/js/backend/admin.js', [], $version, true );
		wp_localize_script(
			'cariera-core-admin',
			'cariera_core_admin',
			[
				'ajax_url'     => esc_url( admin_url( 'admin-ajax.php', 'relative' ) ),
				'nonce'        => wp_create_nonce( '_cariera_core_admin_nonce' ),
				'map_provider' => get_option( 'cariera_map_provider' ),
				'strings'      => [
					'select_company' => esc_html__( 'No Company Selected', 'cariera-core' ),
					'loading_icons'  => esc_html__( 'Loading Icons...', 'cariera-core' ),
				],
			]
		);

		// Cariera Core Settings.
		wp_register_style( 'cariera-core-settings', CARIERA_URL . '/assets/dist/css/backend/settings' . $suffix . '.css', [], $version );
		wp_register_script( 'cariera-core-settings', CARIERA_URL . '/assets/dist/js/backend/settings.js', [], $version, true );

		// reCaptcha.
		wp_register_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js', [], $version, true );

		// Blog Elementor Element.
		wp_register_style( 'cariera-blog-element', CARIERA_URL . '/assets/dist/css/blog-element' . $suffix . '.css', [], $version );

		// Pricing Tables.
		wp_register_style( 'cariera-pricing-tables', CARIERA_URL . '/assets/dist/css/pricing-tables' . $suffix . '.css', [], $version );

		// Testimonials.
		wp_register_style( 'cariera-testimonials', CARIERA_URL . '/assets/dist/css/testimonials' . $suffix . '.css', [], $version );

		// Listing Categories.
		wp_register_style( 'cariera-companies-list', CARIERA_URL . '/assets/dist/css/companies-list' . $suffix . '.css', [], $version );

		// Listing Categories.
		wp_register_style( 'cariera-listing-categories', CARIERA_URL . '/assets/dist/css/listing-categories' . $suffix . '.css', [], $version );

		// Listing Half Detail.
		wp_register_script( 'cariera-listing-split-view', CARIERA_URL . '/assets/dist/js/listing-split-view.js', [], $version, true );
		wp_register_style( 'cariera-listing-split-view', CARIERA_URL . '/assets/dist/css/listing-split-view' . $suffix . '.css', [], $version );
	}

	/**
	 * Enqueue Core Plugin assets.
	 *
	 * @since   1.6.3
	 * @version 1.8.9
	 */
	public function enqueue_assets() {
		$version = function_exists( '\Cariera\is_dev_mode' ) && \Cariera\is_dev_mode() ? wp_rand( 1, 1e4 ) : CARIERA_CORE_VERSION;

		// Main JS File of the core plugin.
		wp_enqueue_script( 'cariera-core-main' );

		// Map Providers.
		$map_provider  = get_option( 'cariera_map_provider' );
		$gmap_api_key  = get_option( 'cariera_gmap_api_key' );
		$gmap_language = get_option( 'cariera_gmap_language' );

		if ( 'google' === $map_provider && $gmap_api_key ) {
			wp_enqueue_script( 'google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $gmap_api_key . '&amp;libraries=places&language=' . $gmap_language . '&loading=async&callback=Function.prototype', [ 'jquery' ], $version, true );
		}

		// Maps.
		if ( 'none' !== $map_provider ) {
			wp_enqueue_script( 'cariera-maps' );
		}
	}

	/**
	 * Backend - Enqueue Core Plugin assets.
	 *
	 * @since   1.6.3
	 * @version 1.8.9
	 */
	public function enqueue_admin_assets() {
		$version = function_exists( '\Cariera\is_dev_mode' ) && \Cariera\is_dev_mode() ? wp_rand( 1, 1e4 ) : CARIERA_CORE_VERSION;

		// Main JS File of the core plugin.
		wp_enqueue_style( 'cariera-core-admin' );
		wp_enqueue_script( 'cariera-core-admin' );

		// Map Providers.
		$map_provider  = get_option( 'cariera_map_provider' );
		$gmap_api_key  = get_option( 'cariera_gmap_api_key' );
		$gmap_language = get_option( 'cariera_gmap_language' );

		if ( 'google' === $map_provider && $gmap_api_key ) {
			wp_enqueue_script( 'google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $gmap_api_key . '&amp;libraries=places&language=' . $gmap_language . '&callback=Function.prototype', [ 'jquery' ], $version, true );
		}
	}
}
