<?php

namespace Cariera_Core\Core\Company_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Search {

	use \Cariera_Core\Src\Traits\Singleton;

	/**
	 * Constructor
	 */
	public function __construct() {
		// AJAX Actions.
		add_action( 'wp_ajax_nopriv_cariera_get_companies', [ $this, 'get_ajax_companies' ] );
		add_action( 'wp_ajax_cariera_get_companies', [ $this, 'get_ajax_companies' ] );

		// Location extra fields.
		add_action( 'cariera_company_filters_location_extra', [ $this, 'extra_location_fields' ], 2 );
		add_action( 'cariera_company_filters_search_radius', [ $this, 'search_by_radius_fields' ], 2 );

        // Search by radius query.
		add_filter( 'cariera_get_companies', [ $this, 'search_by_radius_query' ], 10, 2 );
		add_action( 'cariera_company_title_after', [ $this, 'output_search_radius_distance' ], 99 );
	}

	/**
	 * Returns Company Listings for Ajax endpoint.
	 *
	 * @since   1.3.0
	 * @version 1.8.9
	 */
	public function get_ajax_companies() {
		global $wpdb, $cariera_distances;

		$search_keywords   = isset( $_REQUEST['search_keywords'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['search_keywords'] ) ) : '';
		$search_location   = isset( $_REQUEST['search_location'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['search_location'] ) ) : '';
		$search_categories = isset( $_REQUEST['search_categories'] ) ? wp_unslash( $_REQUEST['search_categories'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$order             = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'DESC';
		$orderby           = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'featured';
		$page              = isset( $_REQUEST['page'] ) ? absint( $_REQUEST['page'] ) : 1;
		$per_page          = isset( $_REQUEST['per_page'] ) ? absint( $_REQUEST['per_page'] ) : absint( get_option( 'cariera_companies_per_page' ) );
		$featured          = isset( $_REQUEST['featured'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['featured'] ) ) : null;
		$active_jobs       = isset( $_REQUEST['active_jobs'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['active_jobs'] ) ) : null;
		$show_pagination   = isset( $_REQUEST['show_pagination'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['show_pagination'] ) ) : null;
		$companies_layout  = isset( $_REQUEST['company_layout'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['company_layout'] ) ) : '';
		$companies_version = isset( $_REQUEST['company_version'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['company_version'] ) ) : '';

		if ( is_array( $search_categories ) ) {
			$search_categories = array_filter( array_map( 'sanitize_text_field', array_map( 'stripslashes', $search_categories ) ) );
		} else {
			$search_categories = array_filter( [ sanitize_text_field( wp_unslash( $search_categories ) ), 0 ] );
		}

		$args = [
			'search_keywords'   => $search_keywords,
			'search_location'   => $search_location,
			'search_categories' => $search_categories,
			'orderby'           => $orderby,
			'order'             => $order,
			'offset'            => ( $page - 1 ) * $per_page,
			'posts_per_page'    => max( 1, $per_page ), // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Known slow query.
		];

		if ( 'true' === $featured || 'false' === $featured ) {
			$args['featured'] = 'true' === $featured;
		}

		if ( 'true' === $active_jobs || 'false' === $active_jobs ) {
			$args['active_jobs'] = 'true' === $active_jobs;
		}

		// Get the arguments to use when building the Companies WP Query.
		$companies = cariera_get_companies( apply_filters( 'cariera_get_companies_args', $args ) );

		$result = [
			'found_companies' => $companies->have_posts(),
			'showing'         => '',
			'max_num_pages'   => $companies->max_num_pages,
		];

		if ( ( $search_location || $search_keywords || $search_categories ) ) {
			// translators: Placeholder %d is the number of found search results.
			$message               = sprintf( _n( 'Search completed. Found %d matching record.', 'Search completed. Found %d matching records.', $companies->found_posts, 'cariera-core' ), $companies->found_posts );
			$result['showing_all'] = true;
		} else {
			$message = '';
		}

		$search_values = [
			'location'   => $search_location,
			'keywords'   => $search_keywords,
			'categories' => $search_categories,
		];

		/**
		 * Filter the message that describes the results of the search query.
		 *
		 * @since 1.7.0
		 *
		 * @param string $message Default message that is generated when posts are found.
		 * @param array $search_values {
		 *  Helpful values often used in the generation of this message.
		 *
		 *  @type string $location   Query used to filter by company listing location.
		 *  @type string $keywords   Query used to filter by general keywords.
		 *  @type array  $categories List of the categories to filter by.
		 * }
		 */
		$result['showing'] = apply_filters( 'cariera_get_companies_custom_filter_text', $message, $search_values );

		// Generate RSS link.
		$result['showing_links'] = cariera_get_companies_filtered_links(
			[
				'search_location'   => $search_location,
				'search_categories' => $search_categories,
				'search_keywords'   => $search_keywords,
			]
		);

		ob_start();

		if ( $result['found_companies'] ) {
			while ( $companies->have_posts() ) {
				$companies->the_post();
				get_job_manager_template_part( 'company-templates/content', 'company' . $companies_layout . $companies_version, 'wp-job-manager-companies' );
			}
		} else {
			get_job_manager_template_part( 'content', 'no-companies-found', 'wp-job-manager-companies' );
		}

		$result['html'] = ob_get_clean();

		// Generate pagination.
		if ( 'true' === $show_pagination ) {
			$result['pagination'] = cariera_get_company_pagination( $companies->max_num_pages, absint( $_REQUEST['page'] ) );
		}

		/** This filter is documented in includes/class-wp-job-manager-ajax.php (above) */
		wp_send_json( apply_filters( 'cariera_get_companies_result', $result, $companies ) );
	}

	/**
	 * Extra location fields.
	 *
	 * @since 1.8.9
	 */
	public function extra_location_fields() {
		get_job_manager_template_part( 'search-fields/location-extra' );
	}

	/**
	 * Custom search by location radius for the Company search
	 *
	 * @since 1.8.9
	 */
	public function search_by_radius_fields() {
		get_job_manager_template_part( 'search-fields/radius-field' );
	}

    /**
	 * Modifying the company search query.
	 *
	 * @since   1.8.9
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
