<?php

namespace Cariera_Core\Core\Resume_Manager;

use Cariera_Core\Core\Resume_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Search extends Resume_Manager {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Search Radius field and search query.
		add_action( 'cariera_wprm_resume_filters_location_extra', [ $this, 'extra_location_fields' ], 2 );
		add_action( 'cariera_wprm_resume_filters_search_radius', [ $this, 'search_by_radius_fields' ], 2 );
		add_filter( 'resume_manager_get_resumes', [ $this, 'search_by_radius_query' ], 10, 2 );
		add_action( 'cariera_resume_title_after', [ $this, 'output_search_radius_distance' ], 99 );

		add_action( 'resume_manager_resume_filters_search_resumes_end', [ $this, 'search_skills_field' ] );
		add_action( 'cariera_wprm_sidebar_job_filters_search_jobs_end', [ $this, 'search_skills_field' ] );
		add_filter( 'resume_manager_get_resumes', [ $this, 'search_skills_query' ], 10, 2 );

		add_action( 'resume_manager_resume_filters_search_resumes_end', [ $this, 'search_rate_field' ] );
		add_action( 'cariera_wprm_sidebar_job_filters_search_jobs_end', [ $this, 'search_rate_field' ] );
		add_filter( 'resume_manager_get_resumes', [ $this, 'search_rate_query' ], 10, 2 );
	}

	/**
	 * Extra location fields.
	 *
	 * @since 1.7.5
	 */
	public function extra_location_fields() {
		get_job_manager_template_part( 'search-fields/location-extra' );
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
	 * Custom search by location radius for the Resume search
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

		// This will show the 'reset' link.
		add_filter( 'resume_manager_get_resumes_custom_filter', '__return_true' );

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

	/**
	 * Custom search by skills field for the Resume search
	 *
	 * @since   1.3.6
	 * @version 1.7.3
	 */
	public function search_skills_field() {

		if ( ! get_option( 'resume_manager_enable_skills' ) ) {
			return;
		}

		$selected_skills = '';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['search_skills'] ) ) {
			$selected_skills = sanitize_text_field( wp_unslash( $_GET['search_skills'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( ! is_tax( 'resume_skill' ) && get_terms( 'resume_skill' ) ) {
			?>
			<div class="search_skills resume-filter">
				<label for="search_skills"><?php esc_html_e( 'Filter by Skills', 'cariera-core' ); ?></label>
				<?php
				job_manager_dropdown_categories(
					[
						'taxonomy'     => 'resume_skill',
						'hierarchical' => 1,
						'name'         => 'search_skills',
						'orderby'      => 'name',
						'selected'     => $selected_skills,
						'hide_empty'   => false,
						'class'        => 'cariera-select2',
						'id'           => 'search_skills',
						'placeholder'  => esc_html__( 'Choose a skill', 'cariera-core' ),
					]
				);
				?>
			</div>
			<?php
		}
	}

	/**
	 * Modifying the resume search query.
	 *
	 * @since   1.3.6
	 * @version 1.7.3
	 *
	 * @param array $query_args
	 * @param array $args
	 */
	public function search_skills_query( $query_args, $args ) {
		if ( ! isset( $_POST['form_data'] ) ) {
			return $query_args;
		}

		// phpcs:ignore
		parse_str( $_POST['form_data'], $form_data );

		// Return if search_skills returns null.
		if ( ! isset( $form_data['search_skills'] ) ) {
			return $query_args;
		}

		$field    = is_numeric( $form_data['search_skills'][0] ) ? 'term_id' : 'slug';
		$operator = 'all' === count( $form_data['search_skills'] ) > 1 ? 'AND' : 'IN';

		$query_args['tax_query'][] = [
			'taxonomy'         => 'resume_skill',
			'field'            => $field,
			'terms'            => array_values( $form_data['search_skills'] ),
			'include_children' => $operator !== 'AND',
			'operator'         => $operator,
		];

		// This will show the 'reset' link.
		add_filter( 'resume_manager_get_resumes_custom_filter', '__return_true' );

		return $query_args;
	}

	/**
	 * Custom search by rate field for the Resume search
	 *
	 * @since   1.3.6
	 * @version 1.8.5
	 */
	public function search_rate_field() {
		if ( ! get_option( 'cariera_resume_manager_enable_rate' ) ) {
			return;
		}
		?>
		<div class="search_by_rate resume-filter">
			<label for="search_by_rate"><?php esc_html_e( 'Minimum Rate', 'cariera-core' ); ?></label>
			<input type="text" name="search_by_rate" id="search_by_rate"  placeholder="<?php esc_attr_e( 'Search by minimum rate', 'cariera-core' ); ?>">
		</div>
		<?php
	}

	/**
	 * Modifying the resume search query.
	 *
	 * @since   1.3.6
	 * @version 1.7.3
	 *
	 * @param array $query_args
	 * @param array $args
	 */
	public function search_rate_query( $query_args, $args ) {
		if ( ! isset( $_POST['form_data'] ) ) {
			return $query_args;
		}

		// phpcs:ignore
		parse_str( $_POST['form_data'], $form_data );

		// Return if search_by_rate returns null.
		if ( ! isset( $form_data['search_by_rate'] ) ) {
			return $query_args;
		}

		$rate = sanitize_text_field( $form_data['search_by_rate'] );

		$query_args['meta_query'][] = [
			'key'     => '_rate',
			'value'   => $rate,
			'compare' => '>=',
			'type'    => 'NUMERIC',
		];

		// This will show the 'reset' link.
		add_filter( 'resume_manager_get_resumes_custom_filter', '__return_true' );

		return $query_args;
	}
}
