<?php
/**
 * ELEMENTOR WIDGET - LISTING DASHBOARD
 *
 * @since   1.7.5
 * @version 1.7.5
 **/

namespace Cariera_Core\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cariera_Listing_Dashboard extends \Elementor\Widget_Base {

	/**
	 * Get widget's name.
	 */
	public function get_name() {
		return 'listing_dashboard';
	}

	/**
	 * Get widget's title.
	 */
	public function get_title() {
		return esc_html__( 'Listing Dashboard', 'cariera-core' );
	}

	/**
	 * Get widget's icon.
	 */
	public function get_icon() {
		return 'eicon-post-list';
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
			'listing_dashboard',
			[
				'label'       => esc_html__( 'Select Dashboard', 'cariera-core' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => [
					'job_listing' => esc_html__( 'Job Dashboard', 'cariera-core' ),
					'resume'      => esc_html__( 'Resume Dashboard', 'cariera-core' ),
					'company'     => esc_html__( 'Company Dashboard', 'cariera-core' ),
				],
				'default'     => 'job_listing',
				'description' => '',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Widget output
	 */
	protected function render() {
		$settings = $this->get_settings();

		// Job submission form.
		if ( 'job_listing' === $settings['listing_dashboard'] ) {
			echo do_shortcode( '[job_dashboard]' );
		}

		// Resume submission form.
		if ( 'resume' === $settings['listing_dashboard'] ) {
			echo do_shortcode( '[candidate_dashboard]' );
		}

		// Company submission form.
		if ( 'company' === $settings['listing_dashboard'] ) {
			echo do_shortcode( '[company_dashboard]' );
		}
	}
}
