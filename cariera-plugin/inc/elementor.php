<?php

namespace Cariera_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Elementor {

	/**
	 * Constructor function.
	 */
	public function __construct() {
		add_action( 'elementor/init', [ $this, 'elementor_add_category' ] );
		add_action( 'elementor/widgets/register', [ $this, 'elementor_register_widgets' ] );

		// Add support for Elementor Pro custom headers & footers.
		add_action( 'elementor/theme/register_locations', [ $this, 'register_locations' ] );

		// Add custom icons to Elementor.
		add_action( 'elementor/icons_manager/additional_tabs', [ $this, 'register_custom_icons' ] );
	}

	/**
	 * Add a custom category for panel widgets
	 *
	 * @since 1.4.0
	 */
	public function elementor_add_category() {
		\Elementor\Plugin::$instance->elements_manager->add_category(
			'cariera-elements',
			[
				'title' => esc_html__( 'Cariera Elements', 'cariera-core' ),
				'icon'  => 'fa fa-gmap',
			],
			1 // Position.
		);
	}

	/**
	 * Register custom widgets for Elementor
	 *
	 * @since   1.4.0
	 * @version 1.8.3
	 *
	 * @param mixed $widgets_manager
	 */
	public function elementor_register_widgets( $widgets_manager ) {

		// Widget names.
		$elements = [
			// Generic Elements.
			'button',
			'blog_posts',
			'blog_slider',
			'contact_form7',
			'counter',
			'count_down',
			'testimonials',
			'logo_slider',
			'login_register',
			// 'text_rotator',
			'pricing_tables',
			'video_popup',

			// Listing Elements.
			'job_board',
			'job_slider',
			'job_categories_slider',
			'job_resume_search',
			'company_board',
			'company_list',
			'company_slider',
			'resumes',
			'resume_slider',
			'listing_map',
			'listing_categories_grid',
			'listing_categories_list',
			'listing_dashboard',
			'listing_split_view',
			'listing_search',
			'listing_search_box',
			'listing_search_sidebar',
			'listing_submission',
		];

		foreach ( $elements as $element_name ) {
			$template_file = CARIERA_CORE_PATH . '/inc/elementor/' . $element_name . '.php';

			if ( $template_file && is_readable( $template_file ) ) {
				require_once $template_file;
				$class_name = '\Cariera_Core\Elementor\Cariera_' . ucwords( $element_name, '_' );
				$widgets_manager->register( new $class_name() );
			}
		}
	}

	/**
	 * Register locations for Elementor Pro Theme Builder Support
	 *
	 * @since 1.4.6
	 * @see https://developers.elementor.com/docs/themes/registering-locations/
	 *
	 * @param mixed $location_manager
	 */
	public function register_locations( $location_manager ) {
		$location_manager->register_location( 'header' );
		$location_manager->register_location( 'footer' );
	}

	/**
	 * Register new icons library to Elementor.
	 *
	 * @see https://icons8.com/line-awesome
	 * @param array $packs
	 *
	 * @since 1.8.0
	 */
	public function register_custom_icons( $packs ) {
		$base_url = trailingslashit( get_template_directory_uri() . '/assets/vendors/font-icons/' );

		$packs['la-regular'] = [
			'name'          => 'la-regular',
			'label'         => esc_html__( 'Line Awesome - Regular', 'cariera-core' ),
			'url'           => $base_url . 'line-awesome.min.css',
			'enqueue'       => [],
			'prefix'        => 'la-',
			'displayPrefix' => 'lar',
			'labelIcon'     => 'fab fa-font-awesome-alt',
			'ver'           => '1.3.0',
			'fetchJson'     => $base_url . 'line-awesome-regular.js',
			'native'        => false,
		];

		$packs['la-solid'] = [
			'name'          => 'la-solid',
			'label'         => esc_html__( 'Line Awesome - Solid', 'cariera-core' ),
			'url'           => $base_url . 'line-awesome.min.css',
			'enqueue'       => [],
			'prefix'        => 'la-',
			'displayPrefix' => 'las',
			'labelIcon'     => 'fab fa-font-awesome-alt',
			'ver'           => '1.3.0',
			'fetchJson'     => $base_url . 'line-awesome-solid.js',
			'native'        => false,
		];

		$packs['la-brands'] = [
			'name'          => 'la-brands',
			'label'         => esc_html__( 'Line Awesome - Brands', 'cariera-core' ),
			'url'           => $base_url . 'line-awesome.min.css',
			'enqueue'       => [],
			'prefix'        => 'la-',
			'displayPrefix' => 'lab',
			'labelIcon'     => 'fab fa-font-awesome-alt',
			'ver'           => '1.3.0',
			'fetchJson'     => $base_url . 'line-awesome-brands.js',
			'native'        => false,
		];

		return $packs;
	}
}
