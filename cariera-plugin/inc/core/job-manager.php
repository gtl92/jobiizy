<?php

namespace Cariera_Core\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Job_Manager {

	use \Cariera_Core\Src\Traits\Singleton;

	/**
	 * Constructor function.
	 *
	 * @since 1.7.2
	 */
	public function __construct() {
		// Init Classes.
		new \Cariera_Core\Core\Job_Manager\Jobs_Extender();
		new \Cariera_Core\Core\Job_Manager\Fields();
		new \Cariera_Core\Core\Job_Manager\Search();
		new \Cariera_Core\Core\Job_Manager\Geocode();
		new \Cariera_Core\Core\Job_Manager\Settings();
		new \Cariera_Core\Core\Job_Manager\Taxonomy();
		\Cariera_Core\Core\Job_Manager\Type_Colors::instance();
		\Cariera_Core\Core\Job_Manager\Maps::instance();
		\Cariera_Core\Core\Job_Manager\Writepanels::instance();

		// Cariera Company Manager.
		$GLOBALS['cariera_company_manager'] = new \Cariera_Core\Core\Company_Manager\Company_Manager();

		// Listing half details AJAX.
		add_action( 'wp_ajax_cariera_listing_half_loading', [ $this, 'loading_listing_details' ] );
		add_action( 'wp_ajax_nopriv_cariera_listing_half_loading', [ $this, 'loading_listing_details' ] );

		// Listing Split View Search.
		add_action( 'cariera_listing_split_view_search', [ $this, 'split_view_search' ] );
	}

	/**
	 * Load listing ajax details
	 *
	 * @since 1.8.3
	 */
	public function loading_listing_details() {
		global $listing_half_detail;

		// phpcs:ignore
		if ( ! isset( $_POST['listing_id'] ) ) {
			return;
		}

		$listing_half_detail = true;
		$listing_id          = str_replace( 'listing-id-', '', sanitize_text_field( wp_unslash( $_POST['listing_id'] ) ) ); // phpcs:ignore
		$listing             = get_post( $listing_id );
		$post_type           = $listing->post_type;

		ob_start();

		setup_postdata( $GLOBALS['post'] =& $listing ); // phpcs:ignore

		if ( 'job_listing' === $post_type ) {
			get_job_manager_template( 'content-single-job_listing.php' );
		} elseif ( 'resume' === $post_type ) {
			get_job_manager_template( 'content-single-resume.php', [], 'wp-job-manager-resumes' );
		} else {
			get_job_manager_template( 'content-single-company.php', [], 'wp-job-manager-companies' );
		}

		\Cariera_Core\Extensions\Social_Share\Sharer::instance()->sharing_modal( $listing_id );
		wp_reset_postdata();

		$response = ob_get_clean();

		wp_send_json_success(
			[
				'response' => $response,
				'link'     => get_permalink( $listing_id ),
			]
		);
	}

	/**
	 * Add the search form for dynamic listing split-view.
	 *
	 * @since   1.8.3
	 * @version 1.8.4
	 *
	 * @param string $listing_type
	 */
	public function split_view_search( $listing_type ) {
		if ( empty( $listing_type ) ) {
			return;
		}

		$listing_type = apply_filters( 'cariera_listing_split_view_search_listing_type', $listing_type );

		switch ( $listing_type ) {
			case 'job_listing':
				do_shortcode( '[cariera_job_sidebar_search]' );
				break;

			case 'resume':
				do_shortcode( '[cariera_resume_sidebar_search]' );
				break;

			case 'company':
				do_shortcode( '[cariera_company_sidebar_search]' );
				break;
		}
	}
}
