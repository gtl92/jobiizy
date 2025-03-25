<?php

namespace Cariera_Core\Core\Metabox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Testimonial extends \Cariera_Core\Core\Metabox {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Register Metaboxes.
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );

		// Save Data.
		add_action( 'save_post', [ $this, 'save_testimonial' ], 10, 2 );
	}

	/**
	 * Register Metaboxes
	 *
	 * @since   1.5.3
	 * @version 1.8.8
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'cariera_testimonial_data',
			_x( 'Testimonial Settings', 'Testimonials data options in wp-admin', 'cariera-core' ),
			[ $this, 'meta_boxes_testimonial' ],
			'testimonial',
			'normal',
			'high'
		);
	}

	/**
	 * Displays metadata fields for Pages.
	 *
	 * @since   1.5.3
	 * @version 1.8.8
	 *
	 * @param mixed $post
	 */
	public function meta_boxes_testimonial( $post ) {
		global $post, $thepostid, $wp_post_types;

		$thepostid = $post->ID;

		echo '<div class="cariera_meta_data">';

		wp_nonce_field( 'save_meta_data', 'cariera_meta_nonce' );

		foreach ( $this->testimonial_fields() as $key => $field ) {
			$type = ! empty( $field['type'] ) ? $field['type'] : 'text';

			// Fix not saving fields.
			if ( ! isset( $field['value'] ) && metadata_exists( 'post', $thepostid, $key ) ) {
				$field['value'] = get_post_meta( $thepostid, $key, true );
			}

			if ( ! isset( $field['value'] ) && isset( $field['default'] ) ) {
				$field['value'] = $field['default'];
			} elseif ( ! isset( $field['value'] ) ) {
				$field['value'] = '';
			}

			if ( has_action( 'cariera_input_' . $type ) ) {
				do_action( 'cariera_input_' . $type, $key, $field );
			} elseif ( method_exists( $this, 'input_' . $type ) ) {
				call_user_func( [ $this, 'input_' . $type ], $key, $field );
			}
		}

		echo '</div>';
	}

	/**
	 * Fields for testimonial meta
	 *
	 * @since   1.5.3
	 * @version 1.8.8
	 */
	public function testimonial_fields() {
		$fields = apply_filters(
			'cariera_testimonial_meta_fields',
			[
				'cariera_testimonial_gravatar' => [
					'label'       => esc_html__( 'Gravatar E-mail Address', 'cariera-core' ),
					'placeholder' => '',
					'description' => esc_html__( 'Enter in an e-mail address, to use a Gravatar, instead of using the "Featured Image".', 'cariera-core' ),
				],
				'cariera_testimonial_byline'   => [
					'label'       => esc_html__( 'Byline', 'cariera-core' ),
					'placeholder' => '',
					'description' => esc_html__( 'Enter a byline for the customer giving this testimonial (for example: "CEO of Cariera").', 'cariera-core' ),
				],
				'cariera_testimonial_url'      => [
					'label'       => esc_html__( 'URL', 'cariera-core' ),
					'placeholder' => '',
					'description' => esc_html__( 'Enter a URL that applies to this customer (for example: http://cariera.co/).', 'cariera-core' ),
				],
			]
		);

		return $fields;
	}

	/**
	 * Save Testimonial Meta Data
	 *
	 * @since   1.5.3
	 *
	 * @param int   $post_id
	 * @param mixed $post
	 */
	public function save_testimonial( $post_id, $post ) {
		if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// phpcs:ignore
		if ( empty( $_POST['cariera_meta_nonce'] ) || ! wp_verify_nonce( $_POST['cariera_meta_nonce'], 'save_meta_data' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( 'testimonial' !== $post->post_type ) {
			return;
		}

		// Save Page meta data.
		foreach ( $this->testimonial_fields() as $key => $field ) {
			$type = ! empty( $field['type'] ) ? $field['type'] : '';

			switch ( $type ) {
				case 'textarea':
				case 'wp_editor':
				case 'wp-editor':
					update_post_meta( $post_id, $key, wp_kses_post( stripslashes( $_POST[ $key ] ) ) ); // phpcs:ignore
					break;
				case 'checkbox':
				case 'switch':
					if ( isset( $_POST[ $key ] ) ) {
						update_post_meta( $post_id, $key, 1 );
					} else {
						update_post_meta( $post_id, $key, 0 );
					}
					break;
				case 'heading':
					// nothing.
					break;
				default:
					if ( is_array( $_POST[ $key ] ) ) {
						update_post_meta( $post_id, $key, array_filter( array_map( 'sanitize_text_field', $_POST[ $key ] ) ) ); // phpcs:ignore
					} else {
						update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) ); // phpcs:ignore
					}
					break;
			}
		}
	}
}
