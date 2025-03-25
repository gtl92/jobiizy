<?php

namespace Cariera_Core\Core\Metabox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Post extends \Cariera_Core\Core\Metabox {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Register Metaboxes.
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );

		// Save Data.
		add_action( 'save_post', [ $this, 'save_post' ], 10, 2 );
	}

	/**
	 * Register Metaboxes
	 *
	 * @since   1.5.3
	 * @version 1.8.8
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'cariera_post_data',
			_x( 'Post Settings', 'Posts data options in wp-admin', 'cariera-core' ),
			[ $this, 'meta_boxes_post' ],
			'post',
			'normal',
			'high'
		);
	}

	/**
	 * Displays metadata fields for Posts.
	 *
	 * @since   1.5.3
	 * @version 1.8.8
	 *
	 * @param mixed $post
	 */
	public function meta_boxes_post( $post ) {
		global $post, $thepostid, $wp_post_types;

		$thepostid = $post->ID;

		echo '<div class="cariera_meta_data">';

		wp_nonce_field( 'save_meta_data', 'cariera_meta_nonce' );

		foreach ( $this->post_fields() as $key => $field ) {
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
	 * Fields for posts meta
	 *
	 * @since   1.5.3
	 * @version 1.8.8
	 */
	public function post_fields() {

		$fields = apply_filters(
			'cariera_post_meta_fields',
			[
				// Audio Post.
				'cariera_post_audio_heading'   => [
					'label'       => esc_html__( 'Audio Post Options', 'cariera-core' ),
					'placeholder' => '',
					'description' => '',
					'type'        => 'heading',
				],
				'cariera_blog_audio'           => [
					'label'       => esc_html__( 'Audio Embed Code', 'cariera-core' ),
					'placeholder' => '',
					'description' => esc_html__( 'Please enter the Audio Embed Code here.', 'cariera-core' ),
					'type'        => 'textarea',
				],

				// Gallery Test.
				'cariera_post_gallery_heading' => [
					'label'       => esc_html__( 'Gallery Post Options', 'cariera-core' ),
					'placeholder' => '',
					'description' => '',
					'type'        => 'heading',
				],
				'cariera_blog_gallery'         => [
					'label'       => esc_html__( 'Gallery Images', 'cariera-core' ),
					'placeholder' => '',
					'description' => esc_html__( 'You can upload multiple gallery images for a slideshow', 'cariera-core' ),
					'type'        => 'file',
					'multiple'    => 1,
				],

				// Quote Post.
				'cariera_post_quote_heading'   => [
					'label'       => esc_html__( 'Quote Post Options', 'cariera-core' ),
					'placeholder' => '',
					'description' => '',
					'type'        => 'heading',
				],
				'cariera_blog_quote_author'    => [
					'label'       => esc_html__( 'Quote Author', 'cariera-core' ),
					'placeholder' => '',
					'description' => '',
				],
				'cariera_blog_quote_source'    => [
					'label'       => esc_html__( 'Quote Source', 'cariera-core' ),
					'placeholder' => '',
					'description' => esc_html__( 'Please enter the source (URL) of the quote here.', 'cariera-core' ),
				],
				'cariera_blog_quote_content'   => [
					'label'       => esc_html__( 'Quote Content', 'cariera-core' ),
					'placeholder' => '',
					'description' => '',
					'type'        => 'textarea',
				],

				// Video Post.
				'cariera_post_video_heading'   => [
					'label'       => esc_html__( 'Video Post Options', 'cariera-core' ),
					'placeholder' => '',
					'description' => '',
					'type'        => 'heading',
				],
				'cariera_blog_video_embed'     => [
					'label'       => esc_html__( 'Video Embed Code', 'cariera-core' ),
					'placeholder' => '',
					'description' => esc_html__( 'Add the full embed code here or the URL of a WordPress supported video site.', 'cariera-core' ),
					'type'        => 'textarea',
				],
			]
		);

		return $fields;
	}

	/**
	 * Save Post Meta Data
	 *
	 * @since   1.5.3
	 *
	 * @param int   $post_id
	 * @param mixed $post
	 */
	public function save_post( $post_id, $post ) {
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

		if ( 'post' !== $post->post_type ) {
			return;
		}

		// Save Page meta data.
		foreach ( $this->post_fields() as $key => $field ) {
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
