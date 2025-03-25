<?php

namespace Cariera_Core\Core\Job_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Maps {

	use \Cariera_Core\Src\Traits\Singleton;

	/**
	 * Constructor
	 *
	 * @since   1.2.0
	 * @version 1.7.2
	 */
	public function __construct() {
		add_shortcode( 'cariera-map', [ $this, 'show_map' ] );
	}

	/**
	 * Show map function
	 *
	 * @since   1.2.0
	 * @version 1.8.9
	 *
	 * @param array $atts
	 */
	public function show_map( $atts ) {

		$atts = shortcode_atts(
			[
				'class'  => '',
				'type'   => 'job_listing',
				'height' => '',
			],
			$atts
		);

		$query_args = [
			'post_type'      => $atts['type'],
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		];

		if ( empty( $atts['height'] ) ) {
			$map_height = '450px';
		} else {
			$map_height = $atts['height'];
		}

		$output = '<div id="map-container" class="' . esc_attr( $atts['class'] ) . '"><div id="cariera-map" style="height:' . esc_attr( $map_height ) . '" ></div></div>';

		return $output;
	}
}
