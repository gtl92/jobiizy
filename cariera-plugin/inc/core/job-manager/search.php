<?php

namespace Cariera_Core\Core\Job_Manager;

use Cariera_Core\Core\Job_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Search extends Job_Manager {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'job_manager_job_filters_search_jobs_end', [ $this, 'advanced_search_start' ], 1 );
		add_action( 'job_manager_job_filters_search_jobs_end', [ $this, 'advanced_search_end' ], 10 );

		// Custom, needs work.
		add_action( 'cariera_wpjm_job_filters_location_extra', [ $this, 'extra_location_fields' ], 2 );

		add_action( 'cariera_wpjm_job_filters_search_radius', [ $this, 'search_by_radius_fields' ], 2 );
		add_filter( 'job_manager_get_listings', [ $this, 'search_by_radius_query' ], 10, 2 );
		add_action( 'cariera_job_listing_title_after', [ $this, 'output_search_radius_distance' ], 99 );

		add_action( 'job_manager_job_filters_search_jobs_end', [ $this, 'search_by_salary_fields' ], 2 );
		add_action( 'cariera_wpjm_sidebar_job_filters_search_jobs_end', [ $this, 'search_by_salary_fields' ], 2 );
		add_filter( 'job_manager_get_listings', [ $this, 'search_by_salary_query' ], 10, 2 );

		add_action( 'job_manager_job_filters_search_jobs_end', [ $this, 'search_by_rate_fields' ], 2 );
		add_action( 'cariera_wpjm_sidebar_job_filters_search_jobs_end', [ $this, 'search_by_rate_fields' ], 2 );
		add_filter( 'job_manager_get_listings', [ $this, 'search_by_rate_query' ], 10, 2 );
	}

	/**
	 * Extra job search fields wrapper start
	 *
	 * @since 1.3.6
	 */
	public function advanced_search_start() {
		if ( ! get_option( 'cariera_enable_filter_salary' ) && ! get_option( 'cariera_enable_filter_rate' ) ) {
			return;
		}

		echo '<div class="advanced-search-btn"><a href="#" id="advance-search">' . esc_html__( 'Advanced Search', 'cariera-core' ) . '</a></div>';
		echo '<div class="advanced-search-filters">';
	}

	/**
	 * Extra job search fields wrapper end
	 *
	 * @since 1.3.6
	 */
	public function advanced_search_end() {
		if ( ! get_option( 'cariera_enable_filter_salary' ) && ! get_option( 'cariera_enable_filter_rate' ) ) {
			return;
		}

		echo '</div>';
	}

	/**
	 * Extra location fields.
	 *
	 * @since   1.7.5
	 */
	public function extra_location_fields() {
		get_job_manager_template_part( 'search-fields/location-extra' );
	}

	/**
	 * Custom search by salary field for the Job search
	 *
	 * @since   1.3.6
	 * @version 1.7.5
	 */
	public function search_by_salary_fields() {
		if ( ! get_option( 'cariera_enable_filter_salary' ) ) {
			return;
		}
		?>

		<div class="search_salary_min">
			<label for="search_salary_min"><?php esc_html_e( 'Minimum Salary', 'cariera-core' ); ?></label>
			<input type="text" id="search_salary_min" class="job-manager-filter" name="search_salary_min" placeholder="<?php esc_attr_e( 'Search Salary Min', 'cariera-core' ); ?>">
		</div>

		<div class="search_salary_max">
			<label for="search_salary_max"><?php esc_html_e( 'Maximum Salary', 'cariera-core' ); ?></label>
			<input type="text" id="search_salary_max" class="job-manager-filter" name="search_salary_max" placeholder="<?php esc_attr_e( 'Search Salary Max', 'cariera-core' ); ?>">
		</div>
		<?php
	}

	/**
	 * Modifying the job search query.
	 *
	 * @since   1.3.6
	 * @version 1.7.3
	 *
	 * @param array $query_args
	 * @param array $args
	 */
	public function search_by_salary_query( $query_args, $args ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['form_data'] ) ) {
			return $query_args;
		}

		// phpcs:ignore
		parse_str( $_POST['form_data'], $form_data );

		// If this is set, we are filtering by salary min.
		if ( ! empty( $form_data['search_salary_min'] ) ) {
			$salary_min = sanitize_text_field( $form_data['search_salary_min'] );

			$query_args['meta_query'][] = [
				'key'     => '_salary_min',
				'value'   => $salary_min,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			];

			// This will show the 'reset' link.
			add_filter( 'job_manager_get_listings_custom_filter', '__return_true' );
		}

		// If this is set, we are filtering by salary max.
		if ( ! empty( $form_data['search_salary_max'] ) ) {
			$salary_max = sanitize_text_field( $form_data['search_salary_max'] );

			$query_args['meta_query'][] = [
				'key'     => '_salary_max',
				'value'   => $salary_max,
				'compare' => '<=',
				'type'    => 'NUMERIC',
			];

			// This will show the 'reset' link.
			add_filter( 'job_manager_get_listings_custom_filter', '__return_true' );
		}

		return $query_args;
	}

	/**
	 * Custom search by rate field for the Job search
	 *
	 * @since   1.3.6
	 * @version 1.7.5
	 */
	public function search_by_rate_fields() {
		if ( ! get_option( 'cariera_enable_filter_rate' ) ) {
			return;
		}
		?>

		<div class="search_rate_min">
			<label for="search_rate_min"><?php esc_html_e( 'Minimum Rate', 'cariera-core' ); ?></label>
			<input type="text" id="search_rate_min" class="job-manager-filter" name="search_rate_min" placeholder="<?php esc_attr_e( 'Search Rate Min', 'cariera-core' ); ?>">
		</div>

		<div class="search_rate_max">
			<label for="search_rate_max"><?php esc_html_e( 'Maximum Rate', 'cariera-core' ); ?></label>
			<input type="text" id="search_rate_max" class="job-manager-filter" name="search_rate_max" placeholder="<?php esc_attr_e( 'Search Rate Max', 'cariera-core' ); ?>">
		</div>
		<?php
	}

	/**
	 * Modifying the job search query.
	 *
	 * @since   1.3.6
	 * @version 1.7.3
	 *
	 * @param array $query_args
	 * @param array $args
	 */
	public function search_by_rate_query( $query_args, $args ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['form_data'] ) ) {
			return $query_args;
		}

		// phpcs:ignore
		parse_str( $_POST['form_data'], $form_data );

		// If this is set, we are filtering by salary min.
		if ( ! empty( $form_data['search_rate_min'] ) ) {
			$rate_min = sanitize_text_field( $form_data['search_rate_min'] );

			$query_args['meta_query'][] = [
				'key'     => '_rate_min',
				'value'   => $rate_min,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			];

			// This will show the 'reset' link.
			add_filter( 'job_manager_get_listings_custom_filter', '__return_true' );
		}

		// If this is set, we are filtering by salary max.
		if ( ! empty( $form_data['search_rate_max'] ) ) {
			$rate_max = sanitize_text_field( $form_data['search_rate_max'] );

			$query_args['meta_query'][] = [
				'key'     => '_rate_max',
				'value'   => $rate_max,
				'compare' => '<=',
				'type'    => 'NUMERIC',
			];

			// This will show the 'reset' link.
			add_filter( 'job_manager_get_listings_custom_filter', '__return_true' );
		}

		return $query_args;
	}

	/**
	 * Custom search by location radius for the Job search
	 *
	 * @since   1.4.3
	 * @version 1.7.5
	 */
	public function search_by_radius_fields() {
		get_job_manager_template_part( 'search-fields/radius-field' );
	}

	/**
	 * Modifying the job search query.
	 *
	 * @since   1.4.3
	 * @version 1.8.9
	 *
	 * @param array $query_args
	 * @param array $args
	 */
	public function search_by_radius_query( $query_args, $args ) {
		global $wpdb, $cariera_distances;

		// Check if form data is present and parse it.
		if ( empty( $_POST['form_data'] ) ) {
			return $query_args;
		}

		// phpcs:ignore
		parse_str( $_POST['form_data'], $form_data ); 

		// Validate required fields.
		$search_location = isset( $form_data['search_location'] ) ? sanitize_text_field( $form_data['search_location'] ) : '';
		$search_radius   = isset( $form_data['search_radius'] ) ? $form_data['search_radius'] : 0;
		$radius_status   = isset( $form_data['search_radius_status'] ) ? sanitize_text_field( $form_data['search_radius_status'] ) : '';

		if ( empty( $search_location ) || empty( $radius_status ) || $search_radius <= 0 ) {
			add_filter( 'job_manager_get_listings_custom_filter', '__return_true' );
			return $query_args;
		}

		// Get map provider.
		$map_provider = get_option( 'cariera_map_provider' );

		// Geocode the address to get latitude and longitude.
		$latlng = cariera_geocode( $search_location, $map_provider );

		if ( empty( $latlng ) ) {
			\Cariera\write_log( sprintf( 'Geocoding failed for address: %s. Radius search aborted.', $search_location ) );
			return $query_args;
		}

		// Fetch nearby listings based on geolocation and radius.
		$radius_type = get_option( 'cariera_search_radius_unit' );
		$nearbyposts = cariera_get_nearby_listings( $latlng[0], $latlng[1], $search_radius, $radius_type );

		if ( ! empty( $nearbyposts ) ) {
			cariera_array_sort_by_column( $nearbyposts, 'distance' );
			$cariera_distances = [];

			foreach ( $nearbyposts as $post ) {
				$cariera_distances[ $post['post_id'] ] = round( $post['distance'], 2 );
			}

			$ids = array_keys( $cariera_distances );

			if ( ! empty( $ids ) ) {
				$query_args['post__in'] = $ids;
				$query_args['orderby']  = 'post__in';

				// Optionally remove meta_query filter if it exists.
				if ( isset( $query_args['meta_query'][0] ) ) {
					unset( $query_args['meta_query'][0] );
				}
			}
		}

		// Add filter to show 'reset' link for custom filters.
		add_filter( 'job_manager_get_listings_custom_filter', '__return_true' );

		return $query_args;
	}

	/**
	 * Output the distance based on the radius search.
	 *
	 * @since 1.8.9
	 */
	public function output_search_radius_distance() {
		global $post, $cariera_distances;

		if ( empty( $cariera_distances ) || ! isset( $cariera_distances[ $post->ID ] ) ) {
			return;
		}

		$radius_unit = get_option( 'cariera_search_radius_unit', 'km' );
		$distance    = esc_attr( $cariera_distances[ $post->ID ] );

		echo '<span class="cariera-listing-distance">' . esc_html( $distance ) . '' . esc_html( $radius_unit ) . '</span>';
	}
}
