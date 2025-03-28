<?php
/**
 * ELEMENTOR WIDGET - LISTING SEARCH SIDEBAR
 *
 * @since    1.7.0
 * @version  1.8.9
 **/

namespace Cariera_Core\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cariera_Listing_Search_Sidebar extends \Elementor\Widget_Base {

	/**
	 * Get widget's name.
	 */
	public function get_name() {
		return 'listing_search_sidebar';
	}

	/**
	 * Get widget's title.
	 */
	public function get_title() {
		return esc_html__( 'Listing Search Sidebar', 'cariera-core' );
	}

	/**
	 * Get widget's icon.
	 */
	public function get_icon() {
		return 'eicon-search';
	}

	/**
	 * Get widget's categories.
	 */
	public function get_categories() {
		return [ 'cariera-elements' ];
	}

	/**
	 * Register the controls for the widget
	 */
	protected function register_controls() {

		// SECTION.
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Content', 'cariera-core' ),
			]
		);

		// CONTROLS.
		$this->add_control(
			'post_type_search',
			[
				'label'       => esc_html__( 'Post Type Search', 'cariera-core' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => [
					'job_listing' => esc_html__( 'Job Listing Search', 'cariera-core' ),
					'company'     => esc_html__( 'Company Search', 'cariera-core' ),
					'resume'      => esc_html__( 'Resume Search', 'cariera-core' ),
				],
				'default'     => 'job_listing',
				'description' => esc_html__( 'Choose the post type for your sidebar search.', 'cariera-core' ),
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Widget output
	 */
	protected function render() {
		$settings = $this->get_settings();
		$attrs    = '';

		if ( 'job_listing' === $settings['post_type_search'] ) {
			echo '<aside class="listing-sidebar-search job-search-widget">';
			do_shortcode( '[cariera_job_sidebar_search]' );
			echo '</aside>';
		}

		if ( 'company' === $settings['post_type_search'] ) {
			echo '<aside class="listing-sidebar-search company-search-widget">';
			do_shortcode( '[cariera_company_sidebar_search]' );
			echo '</aside>';
		}

		if ( 'resume' === $settings['post_type_search'] ) {
			echo '<aside class="listing-sidebar-search resume-search-widget">';
			do_shortcode( '[cariera_resume_sidebar_search]' );
			echo '</aside>';
		}
	}
}
