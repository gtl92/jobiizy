<?php
/**
 * ELEMENTOR WIDGET - JOB CATEGORIES SLIDER
 *
 * @since    1.4.0
 * @version  1.7.2
 **/

namespace Cariera_Core\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cariera_Job_Categories_Slider extends \Elementor\Widget_Base {

	/**
	 * Get widget's name.
	 */
	public function get_name() {
		return 'job_categories_slider';
	}

	/**
	 * Get widget's title.
	 */
	public function get_title() {
		return esc_html__( 'Job Categories Slider', 'cariera-core' );
	}

	/**
	 * Get widget's icon.
	 */
	public function get_icon() {
		return 'eicon-slider-3d';
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
			'style',
			[
				'label'       => esc_html__( 'Category Box Style', 'cariera-core' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => [
					'dark'  => esc_html__( 'Dark', 'cariera-core' ),
					'light' => esc_html__( 'Light', 'cariera-core' ),
				],
				'default'     => 'dark',
				'description' => '',
			]
		);
		$this->add_control(
			'icon',
			[
				'label'        => esc_html__( 'Category Icon', 'cariera-core' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'cariera-core' ),
				'label_off'    => esc_html__( 'Hide', 'cariera-core' ),
				'return_value' => 'show',
				'default'      => 'show',
			]
		);
		$this->add_control(
			'columns',
			[
				'label'       => esc_html__( 'Visible Items per Slide', 'cariera-core' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => '5',
				'min'         => '1',
				'max'         => '10',
				'description' => esc_html__( 'This will change how many categories will be visible per slide.', 'cariera-core' ),
			]
		);
		$this->add_control(
			'hide_empty',
			[
				'label'        => esc_html__( 'Hide Empty', 'cariera-core' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'cariera-core' ),
				'label_off'    => esc_html__( 'Hide', 'cariera-core' ),
				'return_value' => 'true',
				'default'      => '',
			]
		);
		$this->add_control(
			'orderby',
			[
				'label'       => esc_html__( 'Order by', 'cariera-core' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => [
					'name'  => esc_html__( 'Name', 'cariera-core' ),
					'ID'    => esc_html__( 'ID', 'cariera-core' ),
					'count' => esc_html__( 'Count', 'cariera-core' ),
					'slug'  => esc_html__( 'Slug', 'cariera-core' ),
					'none'  => esc_html__( 'None', 'cariera-core' ),
				],
				'default'     => 'count',
				'description' => '',
			]
		);
		$this->add_control(
			'order',
			[
				'label'       => esc_html__( 'Order', 'cariera-core' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => [
					'DESC' => esc_html__( 'Descending', 'cariera-core' ),
					'ASC'  => esc_html__( 'Ascending', 'cariera-core' ),
				],
				'default'     => 'DESC',
				'description' => '',
			]
		);
		$this->add_control(
			'items',
			[
				'label'       => esc_html__( 'Total Items', 'cariera-core' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => '10',
				'min'         => '1',
				'description' => esc_html__( 'Set max limit for items (limited to 1000).', 'cariera-core' ),
			]
		);
		$this->add_control(
			'custom_class',
			[
				'label'       => esc_html__( 'Custom Class', 'cariera-core' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => '',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Get Style Dependency
	 *
	 * @since 1.7.1
	 */
	public function get_style_depends() {
		return [ 'cariera-listing-categories' ];
	}

	/**
	 * Widget output
	 */
	protected function render() {
		wp_enqueue_style( 'cariera-listing-categories' );

		$settings   = $this->get_settings();
		$categories = get_terms(
			[
				'taxonomy'   => 'job_listing_category',
				'orderby'    => $settings['orderby'],
				'order'      => $settings['order'],
				'hide_empty' => $settings['hide_empty'],
				'number'     => $settings['items'],
			]
		);

		$chunks = cariera_partition( $categories, $settings['columns'] );

		if ( is_wp_error( $categories ) ) {
			return;
		}

		// Load template.
		cariera_get_template(
			'elements/listing-category/job-category-slider.php',
			[
				'settings' => $settings,
				'chunks'   => $chunks,
			]
		);
	}
}
