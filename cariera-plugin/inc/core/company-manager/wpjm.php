<?php

namespace Cariera_Core\Core\Company_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPJM {

	private $values          = [];
	private $submit_instance = null;

	/**
	 * Constructor.
	 *
	 * @since   1.4.4
	 * @version 1.8.3
	 */
	public function __construct() {

		// Backend Fields & Integration.
		add_filter( 'job_manager_job_listing_data_fields', [ $this, 'job_admin_fields' ], 9999 );

		// Frontend Fields & Integration.
		add_action( 'submit_job_form_company_fields_start', [ $this, 'company_selection' ], 99 );
		add_filter( 'submit_job_form_fields', [ $this, 'company_fields' ] );
		add_action( 'submit_job_form_company_fields_end', [ $this, 'add_company_fields' ] );

		// Submission form fields validation.
		add_filter( 'submit_job_form_validate_fields', [ $this, 'validate_fields' ], 99, 3 );

		if ( $this->integration_enabled() ) {
			// Company Search.
			add_filter( 'job_manager_get_listings', [ $this, 'job_manager_get_listings' ], 9999, 2 );

			// Meta Mappings.
			add_filter( 'the_company_name', [ $this, 'the_company_name' ], 10, 2 );
			add_filter( 'the_company_website', [ $this, 'the_company_website' ], 10, 2 );
			add_filter( 'the_company_twitter', [ $this, 'the_company_twitter' ], 10, 2 );
			add_filter( 'the_company_tagline', [ $this, 'the_company_tagline' ], 10, 2 );
			add_filter( 'the_company_video', [ $this, 'the_company_video' ], 10, 2 );
		}

		// Job Dashboard: WPJM 2.3.0+.
		add_action( 'job_manager_job_dashboard_columns', [ $this, 'maybe_display_company_column' ], 8 );
		add_action( 'job_manager_job_dashboard_column_cariera_company', [ $this, 'company_column' ] );

		// Active Jobs Meta.
		add_action( 'job_manager_user_edit_job_listing', [ $this, 'active_jobs_count' ] );

		// Company select AJAX loading.
		add_action( 'wp_ajax_company_select_loading', [ $this, 'company_select_ajax_backend_loading' ] );
		add_action( 'wp_ajax_company_select_frontend_loading', [ $this, 'company_select_ajax_frontend_loading' ] );
		add_action( 'wp_ajax_nopriv_company_select_frontend_loading', [ $this, 'company_select_ajax_frontend_loading' ] );
	}

	/**
	 * Check if the Cariera Company Manager integrations is enabled
	 *
	 * @since 1.4.4
	 */
	protected function integration_enabled() {
		return get_option( 'cariera_company_manager_integration', false );
	}

	/**
	 * Check if Company is required on job submission
	 *
	 * @since 1.7.0
	 *
	 * @return boolean
	 */
	protected function is_required() {
		return get_option( 'cariera_job_submit_company_required', true );
	}

	/**
	 * Check if Company submission is enabled on job submission
	 *
	 * @since 1.7.7
	 *
	 * @return boolean
	 */
	protected function company_submission() {
		return get_option( 'cariera_job_submit_company_submission', true );
	}

	/**
	 * Removing default wpjm company meta fields
	 *
	 * @since 1.4.4
	 *
	 * @param array $fields
	 */
	public function job_admin_fields( $fields ) {

		if ( ! $this->integration_enabled() ) {
			return $fields;
		}

		if ( isset( $fields['_company_name'] ) ) {
			unset( $fields['_company_name'] );
		}

		if ( isset( $fields['_company_website'] ) ) {
			unset( $fields['_company_website'] );
		}

		if ( isset( $fields['_company_tagline'] ) ) {
			unset( $fields['_company_tagline'] );
		}

		if ( isset( $fields['_company_twitter'] ) ) {
			unset( $fields['_company_twitter'] );
		}

		if ( isset( $fields['_company_video'] ) ) {
			unset( $fields['_company_video'] );
		}

		$fields['_company_manager_id'] = [
			'label'        => esc_html__( 'Company', 'cariera-core' ),
			'type'         => 'company_select',
			'priority'     => 0.1,
			'options'      => [],
			'show_in_rest' => true,
		];

		return $fields;
	}

	/**
	 * Company Selection
	 *
	 * @since    1.4.0
	 * @version  1.8.0
	 */
	public function company_selection() {
		wp_enqueue_script( 'cariera-company-manager-submission' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $this->integration_enabled() || ! $this->company_submission() || isset( $_GET['action'] ) ) {
			return;
		}

		if ( get_option( 'cariera_user_specific_company' ) ) {
			$user_companies = cariera_get_user_companies( [ 'post_status' => 'any' ], true );
			$status_class   = $user_companies ? [ 'has-companies' ] : [ 'no-companies' ];
		} else {
			$status_class = [ 'has-companies' ];
		}

		$add_new_company  = get_option( 'cariera_add_new_company' );
		$submission_limit = get_option( 'cariera_company_submission_limit' );
		$total_companies  = cariera_count_user_companies();
		$checked          = ( $add_new_company && ( $total_companies < $submission_limit || ! $submission_limit ) ) ? '' : 'checked';

		if ( $add_new_company && ( $total_companies < $submission_limit || ! $submission_limit ) ) {
			$status_class[] = '';
		} else {
			$status_class[] = 'disable-add-company';
		}
		?>

		<div id="company-selection" class="<?php echo esc_attr( join( ' ', $status_class ) ); ?>">
			<?php if ( $add_new_company && ( $total_companies < $submission_limit || ! $submission_limit ) ) { ?>
				<div class="fieldset new-company">
					<input type="radio" name="company_submission" id="new-company" value="new_company" class="company-selection-radio" checked>
					<label for="new-company">
						<span class="icon"><i class="las la-plus-circle"></i></span>
						<span class="text"><?php esc_html_e( 'New Company', 'cariera-core' ); ?></span>
					</label>
				</div>
			<?php } ?>

			<div class="fieldset existing-company">
				<input type="radio" name="company_submission" id="existing-company" value="existing_company" class="company-selection-radio" <?php echo esc_attr( $checked ); ?>>
				<label for="existing-company">
					<span class="icon"><i class="lar la-building"></i></span>
					<span class="text"><?php esc_html_e( 'Existing Company', 'cariera-core' ); ?></span>
				</label>
			</div>
		</div>

		<fieldset class="no-companies-message hidden">
			<p class="job-manager-error">
				<?php esc_html_e( 'You either have not logged in or you don\'t have any companies with this account.', 'cariera-core' ); ?>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Removing default company fields
	 *
	 * @since   1.4.4
	 * @version 1.7.7
	 *
	 * @param array $fields
	 */
	public function company_fields( $fields ) {

		if ( ! $this->integration_enabled() ) {
			return $fields;
		}

		// If company submission has been disabled.
		if ( ! $this->company_submission() ) {
			if ( isset( $fields['company'] ) ) {
				unset( $fields['company'] );
			}

			return $fields;
		}

		if ( isset( $fields['company'] ) ) {
			unset( $fields['company'] );
		}

		$fields['company']['company_manager_id'] = [
			'label'       => esc_html__( 'Select Company', 'cariera-core' ),
			'type'        => 'company-select',
			'required'    => false,
			'description' => '',
			'priority'    => '0.1',
			// 'default'     => -1,
			'options'     => [],
		];

		return $fields;
	}

	/**
	 * Getting all the company fields
	 *
	 * @since 1.3.0
	 */
	public function submit_company_form_fields() {
		$fields = \Cariera_Core\Core\Company_Manager\Forms\Submit_Company::get_company_fields();

		return apply_filters( 'cariera_submit_job_form_company_fields', $fields );
	}

	/**
	 * Adding company fields
	 *
	 * @since   1.3.0
	 * @version 1.7.7
	 */
	public function add_company_fields() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $this->integration_enabled() || ! $this->company_submission() || isset( $_GET['action'] ) ) {
			return;
		}

		$company_fields = $this->submit_company_form_fields();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$job_id     = ! empty( $_REQUEST['job_id'] ) ? absint( $_REQUEST['job_id'] ) : 0;
		$company_id = 0;

		if ( ! job_manager_user_can_edit_job( $job_id ) ) {
			$job_id = 0;
		}

		if ( $job_id ) {
			$company_id = get_post_meta( $job_id, '_company_manager_id', true );
			if ( ! empty( $company_id ) ) {
				$company = get_post( $company_id );
			}
		}

		foreach ( $company_fields as $key => $field ) {

			// If company subission is not require make all company fields not required.
			if ( ! $this->is_required() ) {
				$field['required'] = false;
			}

			if ( $company_id ) {
				if ( ! isset( $field['value'] ) ) {
					if ( 'company_name' === $key ) {
						$field['value'] = $company->post_title;
					} elseif ( 'company_content' === $key ) {
						$field['value'] = $company->post_content;
					} elseif ( ! empty( $field['taxonomy'] ) ) {
						$field['value'] = wp_get_object_terms( $company->ID, $field['taxonomy'], [ 'fields' => 'ids' ] );
					} else {
						$field['value'] = get_post_meta( $company->ID, '_' . $key, true );
					}
				}
			}
			?>
			<fieldset class="fieldset-<?php echo esc_attr( $key ); ?> fieldset-type-<?php echo esc_attr( $field['type'] ); ?> cariera-company-manager-fieldset">
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_kses_post( $field['label'] ) . wp_kses_post( apply_filters( 'submit_job_form_required_label', $field['required'] ? '' : ' <small>' . esc_html__( '(optional)', 'cariera-core' ) . '</small>', $field ) ); ?></label>
				<div class="field <?php echo esc_attr( $field['required'] ? 'required-field' : '' ); ?>">
					<?php
					get_job_manager_template(
						'form-fields/' . $field['type'] . '-field.php',
						[
							'key'   => $key,
							'field' => $field,
						]
					);
					?>
				</div>
			</fieldset>
			<?php
		}
	}

	/**
	 * Validate Fields
	 *
	 * @since   1.4.7
	 * @version 1.7.7
	 *
	 * @param bool  $valid
	 * @param array $fields
	 * @param array $values
	 */
	public function validate_fields( $valid, $fields, $values ) {

		if ( ! $this->integration_enabled() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['company_submission'] ) || is_wp_error( $valid ) || ! $valid ) {
			return $valid;
		}

		$values = $this->get_posted_values();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( 'new_company' === $_POST['company_submission'] ) {
			add_action( 'job_manager_update_job_data', [ $this, 'update_job_form_fields' ], 99, 2 );

			if ( $this->is_required() ) {
				try {
					return $this->get_submit_form()->validate_fields( $values );
				} catch ( \Exception $e ) {
					return new \WP_Error( 'thrown-error', $e->getMessage() );
				}
			}
		} else {
			add_action( 'job_manager_update_job_data', [ $this, 'update_job_form_fields' ], 99, 2 );

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( empty( $_POST['company_manager_id'] ) && apply_filters( 'cariera_company_manager_id_required', true ) && $this->is_required() ) {
				return new \WP_Error( 'validation-error', esc_html__( 'Selecting a company is required.', 'cariera-core' ) );
			}
		}

		return $valid;
	}

	/**
	 * Updating custom fields
	 *
	 * @since   1.3.0
	 * @version 1.7.7
	 *
	 * @param int   $job_id
	 * @param array $values
	 */
	public function update_job_form_fields( $job_id, $values ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['company_submission'] ) ) {
			return;
		}

		// Post the values.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( 'new_company' === $_POST['company_submission'] ) {

			$values = $this->get_posted_values();

			if ( empty( $values['company_fields']['company_name'] ) && ! $this->is_required() ) {
				return;
			}

			if ( ! empty( $values ) ) {
				$post_id    = get_post_meta( $job_id, '_company_manager_id', true );
				$company_id = ! empty( $post_id ) ? $post_id : 0;

				if ( $company_id == 0 ) {
					$company_id = $this->get_submit_form()->save_company( $values['company_fields']['company_name'], $values['company_fields']['company_content'], get_option( 'cariera_company_submission_requires_approval' ) ? 'pending' : 'publish', $values );
					$this->get_submit_form()->update_company_data( $values );
				} else {
					$company_id = $this->get_submit_form()->save_company( $values['company_fields']['company_name'], $values['company_fields']['company_content'], get_option( 'cariera_company_submission_requires_approval' ) ? 'pending' : 'publish', $values, $company_id );
					$this->get_submit_form()->update_company_data( $values );
				}
			}

			update_post_meta( $job_id, '_company_manager_id', $company_id );
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $_POST['company_manager_id'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$company_id = absint( $_POST['company_manager_id'] );

				update_post_meta( $job_id, '_company_manager_id', $company_id );
			}
		}
	}

	/**
	 * Init the "\Cariera_Core\Core\Company_Manager\Forms\Submit_Company" class
	 *
	 * @since 1.3.0
	 */
	public function get_submit_form() {
		if ( ! $this->submit_instance ) {
			$this->submit_instance = \Cariera_Core\Core\Company_Manager\Forms\Submit_Company::instance();
		}

		return $this->submit_instance;
	}

	/**
	 * Get posted company values
	 *
	 * @since 1.4.7
	 */
	public function get_posted_values() {

		if ( empty( $this->values ) ) {
			// Init fields.
			$this->get_submit_form()->init_fields();

			// Get posted values.
			$this->values = $this->get_submit_form()->job_submit_get_posted_fields();
		}

		return $this->values;
	}

	/**
	 * Get the Company Name from the job's company_manager_id and filter it.
	 *
	 * @since   1.4.7
	 * @version 1.5.4
	 *
	 * @param string $company_name
	 * @param mixed  $post
	 */
	public function the_company_name( $company_name, $post ) {
		$company_id = cariera_get_the_company( $post );

		if ( ! empty( $company_id ) ) {
			$company_name = get_the_title( $company_id );
		} else {
			$company_name = '';
		}

		return $company_name;
	}

	/**
	 * Gets the company website from the job's company_manager_id and filter it.
	 *
	 * @since   1.4.7
	 * @version 1.5.4
	 *
	 * @param string $website
	 * @param mixed  $post
	 */
	public function the_company_website( $website, $post ) {
		$company_id = cariera_get_the_company( $post );

		return get_post_meta( $company_id, '_company_website', true );
	}

	/**
	 * Gets the company twitter from the job's company_manager_id and filter it.
	 *
	 * @since   1.4.7
	 * @version 1.5.4
	 *
	 * @param string $twitter
	 * @param mixed  $post
	 */
	public function the_company_twitter( $twitter, $post ) {
		$company_id = cariera_get_the_company( $post );

		return get_post_meta( $company_id, '_company_twitter', true );
	}

	/**
	 * Gets the company tagline from the job's company_manager_id and filter it.
	 *
	 * @since   1.4.7
	 * @version 1.5.4
	 *
	 * @param string $tagline
	 * @param mixed  $post
	 */
	public function the_company_tagline( $tagline, $post ) {
		$company_id = cariera_get_the_company( $post );

		return get_post_meta( $company_id, '_company_tagline', true );
	}

	/**
	 * Gets the company video from the job's company_manager_id and filter it.
	 *
	 * @since   1.4.7
	 * @version 1.5.4
	 *
	 * @param string $video
	 * @param mixed  $post
	 */
	public function the_company_video( $video, $post ) {
		$company_id = cariera_get_the_company( $post );

		return get_post_meta( $company_id, '_company_video', true );
	}

	/**
	 * Get Job Listings Query Args
	 *
	 * This is a temporary workaround for passing company_id_XXX in search_keywords query argument,
	 * to allow showing specific company listings. Copied from wp-company-manager
	 *
	 * @since 1.7.0
	 *
	 * @param array $query_args
	 * @param array $args
	 */
	public function job_manager_get_listings( $query_args, $args ) {
		if ( ! array_key_exists( 's', $query_args ) || strpos( $query_args['s'], 'company_id_' ) === false ) {
			return $query_args;
		}

		$search_company_id = str_replace( 'company_id_', '', $query_args['s'] );
		if ( empty( $search_company_id ) ) {
			return $query_args;
		}

		$query_args['s'] = '';
		remove_filter( 'posts_search', 'get_job_listings_keyword_search' );
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$query_args['meta_query'] = [
			[
				'key'   => '_company_manager_id',
				'value' => absint( $search_company_id ),
			],
		];

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		$query_args['tax_query'] = [];

		return $query_args;
	}

	/**
	 * Add 'company' column if Cariera Company Manager is enabled.
	 *
	 * @since   1.7.9
	 * @version 1.8.5
	 *
	 * @param array $columns
	 */
	public function maybe_display_company_column( $columns ) {
		if ( ! $this->integration_enabled() ) {
			return $columns;
		}

		$columns = array_merge( [ 'cariera_company' => esc_html__( 'Company', 'cariera-core' ) ], $columns );

		return $columns;
	}

	/**
	 * Add company image column
	 *
	 * @since   1.7.9
	 * @version 1.8.7
	 *
	 * @param int $job
	 */
	public function company_column( $job ) {
		// Exit early if the integration is not enabled.
		if ( ! $this->integration_enabled() ) {
			return;
		}

		// Retrieve the associated company ID.
		$company_id = get_post_meta( $job->ID, '_company_manager_id', true );

		// Fetch the company post object, if available.
		$company = ! empty( $company_id ) ? get_post( $company_id ) : null;

		// Determine the logo: use the company's logo if available, otherwise fallback to a default logo.
		$logo_img = ( ! empty( $company ) && has_post_thumbnail( $company ) )
			? get_the_company_logo( $company, apply_filters( 'cariera_company_logo_size', 'thumbnail' ) )
			: apply_filters( 'job_manager_default_company_logo', get_template_directory_uri() . '/assets/images/company.png' );

		// Output the company logo image.
		printf(
			'<img class="company_logo" src="%s" alt="%s" />',
			esc_url( $logo_img ),
			esc_attr( $company ? get_the_title( $company ) : esc_attr__( 'Default Company Logo', 'cariera-core' ) )
		);
	}

	/**
	 * Fire the action to calculate company's active jobs.
	 *
	 * @since 1.8.2
	 *
	 * @param int $job_id
	 */
	public function active_jobs_count( $job_id ) {

		// Change the active jobs of the new company.
		$company_id = get_post_meta( $job_id, '_company_manager_id', true );
		$jobs_count = cariera_get_the_company_job_listing_active_count( $company_id );

		if ( empty( $company_id ) ) {
			return;
		}

		if ( metadata_exists( 'post', $company_id, '_active_jobs' ) ) {
			update_post_meta( $company_id, '_active_jobs', $jobs_count );
		} else {
			add_post_meta( $company_id, '_active_jobs', $jobs_count, true );
		}

		// Change the active jobs of the old company.
		$old_company_id = get_post_meta( $job_id, '_old_company_manager_id', true );

		if ( empty( $old_company_id ) ) {
			return;
		}

		$old_jobs_count = cariera_get_the_company_job_listing_active_count( $old_company_id );

		if ( metadata_exists( 'post', $old_company_id, '_active_jobs' ) ) {
			update_post_meta( $old_company_id, '_active_jobs', $old_jobs_count );
		} else {
			add_post_meta( $old_company_id, '_active_jobs', $old_jobs_count, true );
		}
	}

	/**
	 * Load companies select via AJAX for backend
	 *
	 * @since 1.8.3
	 */
	public function company_select_ajax_backend_loading() {
		// Receiving a search request from a user.
		$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : ''; // phpcs:ignore

		// Request parameters.
		$args = [
			'post_type'      => 'company',
			'posts_per_page' => 20, // Limit the number of results.
			'post_status'    => [ 'publish', 'pending' ],
			's'              => $search, // Search by company name.
		];

		// Request execution.
		$companies = get_posts( $args );

		// Generating response data.
		$results = [];
		foreach ( $companies as $company ) {
			$results[] = [
				'id'   => $company->ID,
				'text' => $company->post_title,
			];
		}

		// Return the result in JSON format.
		wp_send_json( $results );
	}

	/**
	 * Load companies select via AJAX for frontend
	 *
	 * @since 1.8.3
	 */
	public function company_select_ajax_frontend_loading() {
		// Receiving a search request from a user.
		$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : ''; // phpcs:ignore

		$user_companies = get_option( 'cariera_user_specific_company' );

		// Request parameters.
		$args = [
			'post_type'           => 'company',
			'post_status'         => [ 'publish', 'expired', 'pending', 'hidden' ],
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => 20,
			'orderby'             => 'date',
			'order'               => 'desc',
			's'                   => $search, // Search by company name.
		];

		if ( $user_companies && ! current_user_can( 'manage_options' ) ) {
			$args['author'] = get_current_user_id();

			if ( ! isset( $args['author'] ) || empty( $args['author'] ) ) {
				return [];
			}
		}

		// Request execution.
		$companies = get_posts( $args );

		// Generating response data.
		$results = [];
		foreach ( $companies as $company ) {
			// Company title.
			$company_title = $company->post_title;
			if ( 'pending' === $company->post_status ) {
				$company_title .= ' (' . $company->post_status . ')';
			}

			$results[] = [
				'id'   => $company->ID,
				'text' => $company_title,
			];
		}

		// Return the result in JSON format.
		wp_send_json( $results );
	}
}
