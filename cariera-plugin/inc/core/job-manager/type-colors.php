<?php

namespace Cariera_Core\Core\Job_Manager;

use Cariera_Core\Core\Job_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Type_Colors extends Job_Manager {

	use \Cariera_Core\Src\Traits\Singleton;

	/**
	 * Constructor
	 *
	 * @since 1.7.2
	 */
	public function __construct() {
		if ( 0 === absint( get_option( 'job_manager_enable_types' ) ) ) {
			return;
		}

		$this->setup_actions();
	}

	/**
	 * Setup all main actions for the file
	 *
	 * @since 1.7.2
	 */
	private function setup_actions() {
		add_filter( 'job_manager_settings', [ $this, 'job_manager_settings' ] );
		add_action( 'wp_head', [ $this, 'output_colors' ] );

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'colorpickers' ] );
			add_action( 'admin_footer', [ $this, 'colorpickersjs' ] );
		}
	}

	/**
	 * Job Manager Settings.
	 *
	 * @since 1.7.2
	 *
	 * @param array $settings
	 */
	public function job_manager_settings( $settings ) {
		$settings['job_colors'] = [
			esc_html__( 'Job Colors', 'cariera-core' ),
			$this->create_options(),
		];

		return $settings;
	}

	/**
	 * Create seperate options for each term.
	 *
	 * @since   1.7.2
	 * @version 1.8.1
	 */
	private function create_options() {
		$terms   = get_terms( 'job_listing_type' );
		$options = [];

		foreach ( $terms as $term ) {
			$options[] = [
				'name'        => 'job_manager_job_type_' . $term->slug . '_color',
				'std'         => '',
				'placeholder' => '#',
				'label'       => $term->name,
				'desc'        => esc_html__( 'Hex value for the color of this job type.', 'cariera-core' ),
				'attributes'  => [
					'data-default-color' => '',
					'data-type'          => 'colorpicker',
				],
			];
		}

		return $options;
	}

	/**
	 * Outputting the selected colors.
	 *
	 * @since   1.7.2
	 * @version 1.7.9
	 */
	public function output_colors() {
		$terms = get_terms( 'job_listing_type' );

		echo "<style id='job_manager_colors'>\n";

		foreach ( $terms as $term ) {
			if ( ! empty( get_option( 'job_manager_job_type_' . $term->slug . '_color' ) ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				printf( ".job-type.term-%s { background-color: %s; } \n", esc_attr( $term->term_id ), get_option( 'job_manager_job_type_' . $term->slug . '_color', '#fff' ) );
			}
		}

		echo "</style>\n";
	}

	/**
	 * Color Picker JS
	 *
	 * @since 1.7.2
	 *
	 * @param [type] $hook
	 */
	public function colorpickers( $hook ) {
		$screen = get_current_screen();

		if ( 'job_listing_page_job-manager-settings' !== $screen->id ) {
			return;
		}

		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}

	/**
	 * Add script on Job Manager settings screen only.
	 *
	 * @since 1.7.2
	 */
	public function colorpickersjs() {
		$screen = get_current_screen();

		if ( 'job_listing_page_job-manager-settings' !== $screen->id ) {
			return;
		} ?>

		<script>
			jQuery(document).ready(function($){
				$( 'input[data-type="colorpicker"]' ).wpColorPicker();
			});
		</script>
		<?php
	}
}
