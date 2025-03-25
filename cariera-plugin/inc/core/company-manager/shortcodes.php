<?php

namespace Cariera_Core\Core\Company_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcodes {

	use \Cariera_Core\Src\Traits\Singleton;

	/**
	 * Dashboard message.
	 *
	 * @access private
	 * @var string
	 */
	private $company_dashboard_message = '';

	/**
	 * Cache of company post IDs currently displayed on company dashboard.
	 *
	 * @var int[]
	 */
	private $job_dashboard_job_ids;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp', [ $this, 'shortcode_action_handler' ] );
		add_action( 'wp_print_scripts', [ $this, 'maybe_deuqueue_scipts' ] );
		add_shortcode( 'company_dashboard', [ $this, 'company_dashboard' ] );
		add_shortcode( 'submit_company', [ $this, 'submit_company' ] );
		add_shortcode( 'companies', [ $this, 'output_companies' ] );
		add_shortcode( 'cariera_companies_list', [ $this, 'output_companies_list' ] );
	}

	/**
	 * Handle actions which need to be run before the shortcode e.g. post actions
	 *
	 * @since 1.4.4
	 */
	public function shortcode_action_handler() {
		global $post;

		if ( is_page() && strstr( $post->post_content, '[company_dashboard' ) ) {
			$this->company_dashboard_handler();
		}
	}

	/**
	 * Function to dequeue scripts if recaptcha is not enabled.
	 *
	 * @since 1.7.9
	 */
	public function maybe_deuqueue_scipts() {
		$companies_enabled_recaptcha = (bool) get_option( 'cariera_enable_recaptcha_company_submission', false );
		if ( has_shortcode( get_the_content(), 'submit_company' ) && ! $companies_enabled_recaptcha ) {
			wp_dequeue_script( 'recaptcha' );
		}
	}

	/**
	 * Handles actions on company dashboard
	 *
	 * @since   1.4.4
	 * @version 1.7.7
	 */
	public function company_dashboard_handler() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! empty( $_REQUEST['action'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'cariera_my_company_actions' ) ) {

			$action     = sanitize_title( wp_unslash( $_REQUEST['action'] ) );
			$company_id = isset( $_REQUEST['company_id'] ) ? absint( $_REQUEST['company_id'] ) : 0;

			$company         = get_post( $company_id );
			$company_actions = $this->get_company_actions( $company );

			if (
				! isset( $company_actions[ $action ] )
				|| empty( $company_actions[ $action ]['nonce'] )
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce should not be modified.
				|| ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), $company_actions[ $action ]['nonce'] )
			) {
				return;
			}

			try {
				// Check ownership.
				if ( empty( $company ) || 'company' !== $company->post_type || ! cariera_user_can_edit_company( $company_id ) ) {
					throw new \Exception( esc_html__( 'Invalid Company ID', 'cariera-core' ) );
				}

				switch ( $action ) {
					case 'delete':
						// Trash it.
						wp_trash_post( $company_id );

						// Message.
						// translators: %s is the company title.
						$this->company_dashboard_message = '<div class="job-manager-message">' . wp_kses_post( sprintf( esc_html__( '%s has been deleted', 'cariera-core' ), $company->post_title ) ) . '</div>';
						break;
					case 'hide':
						if ( 'publish' === $company->post_status ) {
							$update_company = [
								'ID'          => $company_id,
								'post_status' => 'private',
							];
							wp_update_post( $update_company );
							// translators: %s is the company title.
							$this->company_dashboard_message = '<div class="job-manager-message">' . wp_kses_post( sprintf( esc_html__( '%s has been hidden', 'cariera-core' ), $company->post_title ) ) . '</div>';
						}
						break;
					case 'publish':
						if ( in_array( $company->post_status, [ 'private', 'hidden' ], true ) ) {
							$update_company = [
								'ID'          => $company_id,
								'post_status' => 'publish',
							];
							wp_update_post( $update_company );
							// translators: %s is the company title.
							$this->company_dashboard_message = '<div class="job-manager-message">' . wp_kses_post( sprintf( esc_html__( '%s has been published', 'cariera-core' ), $company->post_title ) ) . '</div>';
						}
						break;
				}

				do_action( 'cariera_my_company_do_action', $action, $company_id );

			} catch ( \Exception $e ) {
				$this->company_dashboard_message = '<div class="job-manager-error">' . wp_kses_post( $e->getMessage() ) . '</div>';
			}
		}
	}

	/**
	 * Check if a company is listed on the current user's company dashboard page.
	 *
	 * @since 1.7.0
	 *
	 * @param WP_Post $company Company post object.
	 *
	 * @return bool
	 */
	private function is_company_available_on_dashboard( \WP_Post $company ) {
		// Check cache of currently displayed company dashboard IDs first to avoid lots of queries.
		if ( isset( $this->company_dashboard_company_ids ) && in_array( (int) $company->ID, $this->company_dashboard_company_ids, true ) ) {
			return true;
		}

		$args           = $this->get_company_dashboard_query_args();
		$args['p']      = $company->ID;
		$args['fields'] = 'ids';

		$query = new \WP_Query( $args );

		return (int) $query->post_count > 0;
	}

	/**
	 * Helper that generates the company dashboard query args.
	 *
	 * @since 1.7.0
	 *
	 * @param int $posts_per_page Number of posts per page.
	 *
	 * @return array
	 */
	private function get_company_dashboard_query_args( $posts_per_page = -1 ) {
		$company_dashboard_args = [
			'post_type'           => 'company',
			'post_status'         => [ 'publish', 'expired', 'pending', 'draft', 'preview', 'private', 'hidden' ],
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => $posts_per_page,
			'orderby'             => 'date',
			'order'               => 'desc',
			'author'              => get_current_user_id(),
		];

		if ( $posts_per_page > 0 ) {
			$company_dashboard_args['offset'] = ( max( 1, get_query_var( 'paged' ) ) - 1 ) * $posts_per_page;
		}

		/**
		 * Customize the query that is used to get jobs on the company dashboard.
		 *
		 * @since 1.0.0
		 *
		 * @param array $company_dashboard_args Arguments to pass to WP_Query.
		 */
		return apply_filters( 'cariera_company_manager_get_dashboard_companies_args', $company_dashboard_args );
	}

	/**
	 * Companies Dashboard shortcode
	 *
	 * @since  1.4.4
	 * @version 1.6.4
	 *
	 * @param array $atts
	 */
	public function company_dashboard( $atts ) {
		global $cariera_company_manager;

		if ( ! is_user_logged_in() ) {
			ob_start();
			get_job_manager_template( 'company-dashboard-login.php', [], 'wp-job-manager-companies' );
			return ob_get_clean();
		}

		$posts_per_page = isset( $atts['posts_per_page'] ) ? intval( $atts['posts_per_page'] ) : 25;

		// If doing an action, show conditional content if needed....
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		$action = isset( $_REQUEST['action'] ) ? sanitize_title( wp_unslash( $_REQUEST['action'] ) ) : false;
		if ( ! empty( $action ) ) {
			// phpcs:ignore
			$company_id = absint( $_REQUEST['company_id'] );

			switch ( $action ) {
				case 'edit':
					return $cariera_company_manager->forms->get_form( 'edit-company' );
			}
		}

		// ....If not show the company dashboard
		$args = apply_filters(
			'cariera_get_dashboard_companies_args',
			[
				'post_type'           => 'company',
				'post_status'         => [ 'publish', 'expired', 'pending', 'hidden', 'private' ],
				'ignore_sticky_posts' => 1,
				'posts_per_page'      => $posts_per_page,
				'offset'              => ( max( 1, get_query_var( 'paged' ) ) - 1 ) * $posts_per_page,
				'orderby'             => 'date',
				'order'               => 'desc',
				'author'              => get_current_user_id(),
			]
		);

		wp_enqueue_script( 'cariera-company-manager-dashboard' );

		$companies = new \WP_Query();

		ob_start();

		// If doing an action, show conditional content if needed....
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		$action = isset( $_REQUEST['action'] ) ? sanitize_title( wp_unslash( $_REQUEST['action'] ) ) : false;
		if ( ! empty( $action ) ) {
			// Show alternative content if a plugin wants to.
			if ( has_action( 'cariera_company_manager_company_dashboard_content_' . $action ) ) {
				do_action( 'cariera_company_manager_company_dashboard_content_' . $action, $atts );

				return ob_get_clean();
			}
		}

		// ....If not show the company dashboard.
		$companies = new \WP_Query( $this->get_company_dashboard_query_args( $posts_per_page ) );

		// Cache IDs for access check later on.
		$this->company_dashboard_company_ids = wp_list_pluck( $companies->posts, 'ID' );

		echo wp_kses_post( $this->company_dashboard_message );

		// Get the flash messages sent by external handlers.
		$messages = self::get_company_dashboard_messages( true );
		foreach ( $messages as $message ) {
			$div_class = 'job-manager-message';
			if ( ! empty( $message['is_error'] ) ) {
				$div_class = 'job-manager-error';
			}
			echo '<div class="' . esc_attr( $div_class ) . '">' . wp_kses_post( $message['message'] ) . '</div>';
		}

		$company_dashboard_columns = apply_filters(
			'cariera_company_dashboard_columns',
			[
				'company-name'     => esc_html__( 'Name', 'cariera-core' ),
				'company-location' => esc_html__( 'Location', 'cariera-core' ),
				'company-category' => esc_html__( 'Category', 'cariera-core' ),
				'date'             => esc_html__( 'Date Posted', 'cariera-core' ),
				// translators: %s is the "job post type label".
				'company-jobs'     => sprintf( esc_html__( 'Active %s', 'cariera-core' ), cariera_get_job_post_label( true ) ),
			]
		);

		if ( ! get_option( 'cariera_company_category' ) ) {
			unset( $company_dashboard_columns['company-category'] );
		}

		$company_actions = [];
		foreach ( $companies->posts as $company ) {
			$company_actions[ $company->ID ] = $this->get_company_actions( $company );
		}

		get_job_manager_template(
			'company-dashboard.php',
			[
				'companies'                 => $companies->query( $args ),
				'company_actions'           => $company_actions,
				'max_num_pages'             => $companies->max_num_pages,
				'company_dashboard_columns' => $company_dashboard_columns,
			],
			'wp-job-manager-companies'
		);

		return ob_get_clean();
	}

	/**
	 * Get the actions available to the user for a company listing on the company dashboard page.
	 *
	 * @since 1.7.0
	 *
	 * @param WP_POST $company
	 */
	public function get_company_actions( $company ) {
		if (
			! get_current_user_id()
			|| ! $company instanceof \WP_Post
			|| 'company' !== $company->post_type
			|| ! $this->is_company_available_on_dashboard( $company )
		) {
			return [];
		}

		$base_nonce_action_name = 'cariera_my_company_actions';

		$actions = [];
		switch ( $company->post_status ) {
			case 'publish':
				if ( \Cariera_Core\Core\Company_Manager\CPT::company_is_editable( $company->ID ) ) {
					$actions['edit'] = [
						'label' => esc_html__( 'Edit', 'cariera-core' ),
						'nonce' => false,
					];
				}
				$actions['hide'] = [
					'label' => esc_html__( 'Hide', 'cariera-core' ),
					'nonce' => true,
				];
				break;
			case 'private':
			case 'hidden':
				if ( \Cariera_Core\Core\Company_Manager\CPT::company_is_editable( $company->ID ) ) {
					$actions['edit'] = [
						'label' => esc_html__( 'Edit', 'cariera-core' ),
						'nonce' => false,
					];
				}
				$actions['publish'] = [
					'label' => esc_html__( 'Publish', 'cariera-core' ),
					'nonce' => true,
				];
				break;
			case 'pending_payment':
			case 'pending':
				if ( \Cariera_Core\Core\Company_Manager\CPT::company_is_editable( $company->ID ) ) {
					$actions['edit'] = [
						'label' => esc_html__( 'Edit', 'cariera-core' ),
						'nonce' => false,
					];
				}
				break;
		}

		$actions['delete'] = [
			'label' => esc_html__( 'Delete', 'cariera-core' ),
			'nonce' => $base_nonce_action_name,
		];

		/**
		 * Filter the actions available to the current user for a company on the company dashboard page.
		 *
		 * @since 1.7.0
		 *
		 * @param array   $actions Actions to filter.
		 * @param WP_Post $company     Company post object.
		 */
		$actions = apply_filters( 'cariera_my_company_actions', $actions, $company );

		// For backwards compatibility, convert `nonce => true` to the nonce action name.
		foreach ( $actions as $key => $action ) {
			if ( true === $action['nonce'] ) {
				$actions[ $key ]['nonce'] = $base_nonce_action_name;
			}
		}

		return $actions;
	}

	/**
	 * Show the company submission form
	 *
	 * @since   1.4.4
	 * @version 1.7.9
	 *
	 * @param array $atts
	 */
	public function submit_company( $atts = [] ) {
		$this->maybe_deuqueue_scipts();
		global $cariera_company_manager;

		return $cariera_company_manager->forms->get_form( 'submit-company', $atts );
	}

	/**
	 * Companies shortcode
	 *
	 * @since   1.3.0
	 * @version 1.8.2
	 *
	 * @param array $atts
	 */
	public function output_companies( $atts ) {
		global $cariera_company_manager;

		wp_enqueue_style( 'cariera-company-listings' );

		ob_start();

		if ( ! cariera_user_can_browse_companies() ) {
			get_job_manager_template_part( 'access-denied', 'browse-companies', 'wp-job-manager-companies' );
			return ob_get_clean();
		}

		$atts = shortcode_atts(
			apply_filters(
				'cariera_output_companies_defaults',
				[
					'companies_layout'          => 'list',
					'companies_list_version'    => '1',
					'companies_grid_version'    => '1',

					'per_page'                  => get_option( 'cariera_companies_per_page' ),
					'orderby'                   => 'featured',
					'order'                     => 'DESC',

					// Filters.
					'show_filters'              => true,
					'show_categories'           => true,
					'show_category_multiselect' => get_option( 'cariera_company_category_multiselect', false ),
					'show_pagination'           => false,
					'show_more'                 => true,

					// Limit what companies are shown based on category, post status, and type.
					'categories'                => '',
					'post_status'               => '',
					'featured'                  => null, // True to show only featured, false to hide featured, leave null to show both.
					'active_jobs'               => null, // True to show only companies with jobs, false to hide companies with jobs, leave null to show all.

					// Default values for filters.
					'location'                  => '',
					'keywords'                  => '',
					'selected_category'         => '',
				]
			),
			$atts
		);

		// Companies Layout.
		if ( 'list' === $atts['companies_layout'] ) {
			$companies_layout         = '_list';
			$companies_layout_wrapper = 'company_list';
			$companies_version        = $atts['companies_list_version'];
		} else {
			$companies_layout         = '_' . $atts['companies_layout'];
			$companies_layout_wrapper = 'company_grid';
			$companies_version        = $atts['companies_grid_version'];
		}

		// String and bool handling.
		$atts['show_filters']              = $this->string_to_bool( $atts['show_filters'] );
		$atts['show_categories']           = $this->string_to_bool( $atts['show_categories'] );
		$atts['show_category_multiselect'] = $this->string_to_bool( $atts['show_category_multiselect'] );
		$atts['show_more']                 = $this->string_to_bool( $atts['show_more'] );
		$atts['show_pagination']           = $this->string_to_bool( $atts['show_pagination'] );

		if ( ! is_null( $atts['featured'] ) ) {
			$atts['featured'] = ( is_bool( $atts['featured'] ) && $atts['featured'] ) || in_array( $atts['featured'], [ '1', 'true', 'yes' ], true ) ? true : false;
		}

		if ( ! is_null( $atts['active_jobs'] ) ) {
			$atts['active_jobs'] = ( is_bool( $atts['active_jobs'] ) && $atts['active_jobs'] ) || in_array( $atts['active_jobs'], [ '1', 'true', 'yes' ], true ) ? true : false;
		}

		// Get keywords and location from querystring if set.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		if ( ! empty( $_GET['search_keywords'] ) ) {
			$atts['keywords'] = sanitize_text_field( wp_unslash( $_GET['search_keywords'] ) );
		}
		if ( ! empty( $_GET['search_location'] ) ) {
			$atts['location'] = sanitize_text_field( wp_unslash( $_GET['search_location'] ) );
		}
		if ( ! empty( $_GET['search_category'] ) ) {
			$atts['selected_category'] = sanitize_text_field( wp_unslash( $_GET['search_category'] ) );
		}

		// Array handling.
		$atts['categories']        = is_array( $atts['categories'] ) ? $atts['categories'] : array_filter( array_map( 'trim', explode( ',', $atts['categories'] ) ) );
		$atts['selected_category'] = is_array( $atts['selected_category'] ) ? $atts['selected_category'] : array_filter( array_map( 'trim', explode( ',', $atts['selected_category'] ) ) );
		$atts['post_status']       = is_array( $atts['post_status'] ) ? $atts['post_status'] : array_filter( array_map( 'trim', explode( ',', $atts['post_status'] ) ) );

		// Normalize field for categories.
		if ( ! empty( $atts['selected_category'] ) ) {
			foreach ( $atts['selected_category'] as $cat_index => $category ) {
				if ( ! is_numeric( $category ) ) {
					$term = get_term_by( 'slug', $category, 'company_category' );

					if ( $term ) {
						$atts['selected_category'][ $cat_index ] = $term->term_id;
					}
				}
			}
		}

		if ( $atts['show_filters'] ) {
			get_company_template(
				'company-filters.php',
				[
					'per_page'                  => $atts['per_page'],
					'orderby'                   => $atts['orderby'],
					'order'                     => $atts['order'],
					'show_categories'           => $atts['show_categories'],
					'categories'                => $atts['categories'],
					'selected_category'         => $atts['selected_category'],
					'atts'                      => $atts,
					'location'                  => $atts['location'],
					'keywords'                  => $atts['keywords'],
					'show_category_multiselect' => $atts['show_category_multiselect'],
				]
			);

			echo '<ul class="company_listings company_listings_main ' . esc_attr( $companies_layout_wrapper ) . '"></ul>';
			echo '<div class="listing-loader"><div></div></div>';

			if ( ! $atts['show_pagination'] && $atts['show_more'] ) {
				echo '<div class="text-center"><a class="load_more_companies btn btn-main btn-effect mt40" href="#" style="display:none;">' . esc_html__( 'Load more companies', 'cariera-core' ) . '</a></div>';
			}
		} else {
			$companies = cariera_get_companies(
				apply_filters(
					'cariera_output_companies_args',
					[
						'search_location'   => $atts['location'],
						'search_keywords'   => $atts['keywords'],
						'post_status'       => $atts['post_status'],
						'search_categories' => $atts['categories'],
						'orderby'           => $atts['orderby'],
						'order'             => $atts['order'],
						'posts_per_page'    => $atts['per_page'],
						'featured'          => $atts['featured'],
						'active_jobs'       => $atts['active_jobs'],
					]
				)
			);

			if ( $companies->have_posts() ) {
				echo '<ul class="company_listings company_listings_main ' . esc_attr( $companies_layout_wrapper ) . '">';

				while ( $companies->have_posts() ) {
					$companies->the_post();
					get_job_manager_template_part( 'company-templates/content', 'company' . $companies_layout . $companies_version, 'wp-job-manager-companies' );
				}

				echo '</ul>';
				echo '<div class="listing-loader"><div></div></div>';

				if ( $companies->found_posts > $atts['per_page'] && $atts['show_more'] ) {
					wp_enqueue_script( 'company-ajax-filters' );

					if ( $atts['show_pagination'] ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output.
						echo cariera_get_company_pagination( $companies->max_num_pages );
					} else { ?>
						<div class="text-center">
							<a class="load_more_companies btn btn-main btn-effect" href="#"><?php esc_html_e( 'Load more companies', 'cariera-core' ); ?></a>
						</div>
						<?php
					}
				}
			} else {
				do_action( 'cariera_company_no_results' );
			}

			wp_reset_postdata();
		}

		$data_attributes_string = '';
		$data_attributes        = [
			'company_layout'  => $companies_layout,
			'company_version' => $companies_version,
			'location'        => $atts['location'],
			'keywords'        => $atts['keywords'],
			'show_filters'    => $atts['show_filters'] ? 'true' : 'false',
			'show_pagination' => $atts['show_pagination'] ? 'true' : 'false',
			'per_page'        => $atts['per_page'],
			'orderby'         => $atts['orderby'],
			'order'           => $atts['order'],
			'categories'      => implode( ',', $atts['categories'] ),
		];

		if ( ! is_null( $atts['featured'] ) ) {
			$data_attributes['featured'] = $atts['featured'] ? 'true' : 'false';
		}
		if ( ! is_null( $atts['active_jobs'] ) ) {
			$data_attributes['active_jobs'] = $atts['active_jobs'] ? 'true' : 'false';
		}
		if ( ! empty( $atts['post_status'] ) ) {
			$data_attributes['post_status'] = implode( ',', $atts['post_status'] );
		}

		$data_attributes['post_id'] = isset( $GLOBALS['post'] ) ? $GLOBALS['post']->ID : 0;

		/**
		 * Pass additional data to the job listings <div> wrapper.
		 *
		 * @since 1.7.0
		 *
		 * @param array $data_attributes {
		 *     Key => Value array of data attributes to pass.
		 *
		 *     @type string $$key Value to pass as a data attribute.
		 * }
		 * @param array $atts            Attributes for the shortcode.
		 */
		$data_attributes = apply_filters( 'cariera_companies_shortcode_data_attributes', $data_attributes, $atts );

		foreach ( $data_attributes as $key => $value ) {
			$data_attributes_string .= 'data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
		}

		$companies_output = apply_filters( 'cariera_companies_output', ob_get_clean() );

		return '<div class="company_listings" ' . $data_attributes_string . '>' . $companies_output . '</div>';
	}

	/**
	 * Output of the company list shortcode
	 *
	 * @since   1.3.0
	 * @version 1.6.0
	 *
	 * @param array $atts
	 */
	public function output_companies_list( $atts ) {
		wp_enqueue_style( 'cariera-companies-list' );

		$atts = shortcode_atts(
			[
				'show_letters' => true,
			],
			$atts
		);

		$companies = get_posts(
			[
				'numberposts' => -1,
				'post_type'   => 'company',
				'post_status' => 'publish',
			]
		);

		$_companies = [];
		foreach ( $companies as $company ) {
			if ( is_numeric( $company->post_title[0] ) ) {
				$_companies['numeric'][] = $company;
			} else {
				$_companies[ strtoupper( $company->post_title[0] ) ][] = $company;
			}
		}

		ob_start();

		get_company_template(
			'company-list.php',
			[
				'companies'    => $_companies,
				'show_letters' => $this->string_to_bool( $atts['show_letters'] ),
			]
		);

		return ob_get_clean();
	}

	/**
	 * Gets string as a bool.
	 *
	 * @since   1.3.0
	 * @version 1.5.1
	 *
	 * @param mixed $value
	 */
	public function string_to_bool( $value ) {
		return ( is_bool( $value ) && $value ) || in_array( $value, [ '1', 'true', 'yes' ], true ) ? true : false;
	}

	/**
	 * Add a flash message to display on a company dashboard.
	 *
	 * @since 1.4.7
	 *
	 * @param string $message
	 * @param bool   $is_error
	 */
	public static function add_company_dashboard_message( $message, $is_error = false ) {
		$company_dashboard_page_id = get_option( 'cariera_company_dashboard_page' );
		if ( ! wp_get_session_token() || ! $company_dashboard_page_id ) {
			// We only handle flash messages when the company dashboard page ID is set and user has valid session token.
			return false;
		}
		$messages_key = self::get_company_dashboard_message_key();
		$messages     = self::get_company_dashboard_messages( false );

		$messages[] = [
			'message'  => $message,
			'is_error' => $is_error,
		];

		set_transient( $messages_key, wp_json_encode( $messages ), HOUR_IN_SECONDS );

		return true;
	}

	/**
	 * Gets the current flash messages for the listing dashboard.
	 *
	 * @since 1.4.7
	 *
	 * @param mixed $clear
	 */
	private static function get_company_dashboard_messages( $clear ) {
		$messages_key = self::get_company_dashboard_message_key();
		$messages     = get_transient( $messages_key );

		if ( empty( $messages ) ) {
			$messages = [];
		} else {
			$messages = json_decode( $messages, true );
		}

		if ( $clear ) {
			delete_transient( $messages_key );
		}

		return $messages;
	}

	/**
	 * Get the transient key to use to store listing dashboard messages.
	 *
	 * @since 1.4.7
	 */
	private static function get_company_dashboard_message_key() {
		return 'company_dashboard_messages_' . md5( wp_get_session_token() );
	}
}
