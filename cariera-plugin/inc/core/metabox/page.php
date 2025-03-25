<?php

namespace Cariera_Core\Core\Metabox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Page extends \Cariera_Core\Core\Metabox {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Register Metaboxes.
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );

		// Save Data.
		add_action( 'save_post', [ $this, 'save_page' ], 10, 2 );
	}

	/**
	 * Register Metaboxes
	 *
	 * @since   1.5.3
	 * @version 1.8.8
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'cariera_page_data',
			_x( 'Page Settings', 'Pages data options in wp-admin', 'cariera-core' ),
			[ $this, 'meta_boxes_page' ],
			'page',
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
	public function meta_boxes_page( $post ) {
		global $post, $thepostid, $wp_post_types;

		$thepostid = $post->ID;

		echo '<div class="cariera_meta_data">';

		wp_nonce_field( 'save_meta_data', 'cariera_meta_nonce' );

		foreach ( $this->page_fields() as $key => $field ) {
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
	 * Fields for page meta
	 *
	 * @since   1.5.3
	 * @version 1.8.8
	 */
	public function page_fields() {

		/* get the registered sidebars */
		global $wp_registered_sidebars;

		$sidebars = [];
		foreach ( $wp_registered_sidebars as $id => $sidebar ) {
			$sidebars[ $id ] = $sidebar['name'];
		}

		$fields = apply_filters(
			'cariera_page_meta_fields',
			[
				// MAIN OPTIONS.
				'cariera_main_heading'        => [
					'label'       => esc_html__( 'Main Options', 'cariera-core' ),
					'placeholder' => '',
					'description' => '',
					'type'        => 'heading',
				],
				'cariera_show_page_title'     => [
					'label'       => esc_html__( 'Page Header', 'cariera-core' ),
					'placeholder' => '',
					'description' => esc_html__( 'Set to "Disable" if you want to hide the Page Header on this Page.', 'cariera-core' ),
					'type'        => 'select',
					'options'     => [
						'show' => 'Enable',
						'hide' => 'Disable',
					],
					'default'     => 'show',
				],
				'cariera_page_header_bg'      => [
					'label'       => esc_html__( 'Page Header Cover Image', 'cariera-core' ),
					'placeholder' => '',
					'description' => esc_html__( 'The header image size should be at least 1600x200px', 'cariera-core' ),
					'type'        => 'file',
					'multiple'    => 0,
				],
				'cariera_page_layout'         => [
					'label'       => esc_html__( 'Page Layout', 'cariera-core' ),
					'placeholder' => '',
					'description' => esc_html__( 'Choose the layout of your page.', 'cariera-core' ),
					'type'        => 'select',
					'options'     => [
						'fullwidth' => esc_html__( 'Fullwidth', 'cariera-core' ),
						'sidebar'   => esc_html__( 'With Sidebar', 'cariera-core' ),
					],
					'default'     => 'fullwidth',
				],
				'cariera_select_page_sidebar' => [
					'label'       => esc_html__( 'Select Sidebar', 'cariera-core' ),
					'placeholder' => '',
					'description' => esc_html__( 'The sidebar will be shown only if you have chose a sidebar layout for the page in the "Page Layout" option.', 'cariera-core' ),
					'type'        => 'select',
					'options'     => $sidebars,
					'default'     => 'sidebar-1',
				],

				// HEADER OPTIONS.
				'cariera_header_heading'      => [
					'label'       => esc_html__( 'Header Options', 'cariera-core' ),
					'placeholder' => '',
					'description' => '',
					'type'        => 'heading',
				],
				'cariera_show_header'         => [
					'label'       => esc_html__( 'Header', 'cariera-core' ),
					'placeholder' => '',
					'description' => esc_html__( 'Set to "Disable" if you want to hide the Header on this Page.', 'cariera-core' ),
					'type'        => 'select',
					'options'     => [
						'show' => 'Enable',
						'hide' => 'Disable',
					],
					'default'     => 'show',
				],

				'cariera_header1_fixed_top'   => [
					'label'       => esc_html__( 'Header 1 - Fixed Top', 'cariera-core' ),
					'placeholder' => '',
					'description' => '',
					'type'        => 'switch',
					'default'     => 0,
				],
				'cariera_header1_transparent' => [
					'label'       => esc_html__( 'Header 1 - Transparent', 'cariera-core' ),
					'placeholder' => '',
					'description' => '',
					'type'        => 'switch',
					'default'     => 0,
				],
				'cariera_header1_white'       => [
					'label'       => esc_html__( 'Header 1 - White Text', 'cariera-core' ),
					'placeholder' => '',
					'description' => '',
					'type'        => 'switch',
					'default'     => 0,
				],

				// FOOTER OPTIONS.
				'cariera_footer_heading'      => [
					'label'       => esc_html__( 'Footer Options', 'cariera-core' ),
					'placeholder' => '',
					'description' => '',
					'type'        => 'heading',
				],
				'cariera_show_footer'         => [
					'label'       => esc_html__( 'Footer', 'cariera-core' ),
					'placeholder' => '',
					'description' => esc_html__( 'Set to "Disable" if you want to hide the Footer on this Page.', 'cariera-core' ),
					'type'        => 'select',
					'options'     => [
						'show' => 'Enable',
						'hide' => 'Disable',
					],
					'default'     => 'show',
				],
				'cariera_show_footer_widgets' => [
					'label'       => esc_html__( 'Footer Widget Area', 'cariera-core' ),
					'placeholder' => '',
					'description' => esc_html__( 'Set to "Disable" if you want to hide the Footer Widget Area on this Page.', 'cariera-core' ),
					'type'        => 'select',
					'options'     => [
						'show' => 'Enable',
						'hide' => 'Disable',
					],
					'default'     => 'show',
				],
			]
		);

		return $fields;
	}

	/**
	 * Save Page Meta Data
	 *
	 * @since   1.5.3
	 * @version 1.8.8
	 *
	 * @param int   $post_id
	 * @param mixed $post
	 */
	public function save_page( $post_id, $post ) {
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

		if ( 'page' !== $post->post_type ) {
			return;
		}

		// Save Page meta data.
		foreach ( $this->page_fields() as $key => $field ) {
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
