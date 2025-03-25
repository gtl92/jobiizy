<?php

namespace Cariera_Core\Importer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WXR Importer class
 * Needed to extend the WXR_Importer class to get/set the importer protected variables,
 * for use in the multiple AJAX calls.
 */
class WXRImporter extends \Cariera_Core\Importer\WP_Importer\WXRImporter {

	/**
	 * Constructor.
	 *
	 * @param array $options Import options.
	 */
	public function __construct( $options = [] ) {
		parent::__construct( $options );

		// Set current user to $mapping variable.
		// Fixes the [WARNING] Could not find the author for ... log warning messages.
		$current_user_obj = wp_get_current_user();
		$this->mapping['user_slug'][ $current_user_obj->user_login ] = $current_user_obj->ID;

		/**
		 * Custom fix for WXR Importer on adding Elementor pages.
		 *
		 * For some kind of reason the importer still needs to unslash Elementor data
		 * the best way is to do this with a custom filter and add our own usage of 'wp_unslash'
		 *
		 * https://github.com/awesomemotive/one-click-demo-import/issues/218
		 * https://github.com/elementor/elementor/issues/10774
		 */
		add_filter( 'wxr_importer.pre_process.post_meta', [ $this, 'on_wxr_importer_pre_process_post_meta' ] );
	}

	/**
	 * Get all protected variables from the WXR_Importer needed for continuing the import.
	 *
	 * @since 1.7.3
	 */
	public function get_importer_data() {
		return [
			'mapping'            => $this->mapping,
			'requires_remapping' => $this->requires_remapping,
			'exists'             => $this->exists,
			'user_slug_override' => $this->user_slug_override,
			'url_remap'          => $this->url_remap,
			'featured_images'    => $this->featured_images,
		];
	}

	/**
	 * Sets all protected variables from the WXR_Importer needed for continuing the import.
	 *
	 * @since 1.7.3
	 *
	 * @param array $data with set variables.
	 */
	public function set_importer_data( $data ) {
		$this->mapping            = empty( $data['mapping'] ) ? [] : $data['mapping'];
		$this->requires_remapping = empty( $data['requires_remapping'] ) ? [] : $data['requires_remapping'];
		$this->exists             = empty( $data['exists'] ) ? [] : $data['exists'];
		$this->user_slug_override = empty( $data['user_slug_override'] ) ? [] : $data['user_slug_override'];
		$this->url_remap          = empty( $data['url_remap'] ) ? [] : $data['url_remap'];
		$this->featured_images    = empty( $data['featured_images'] ) ? [] : $data['featured_images'];
	}

	/**
	 * Process post meta before WXR importer.
	 *
	 * Normalize Elementor post meta on import with the new WP_importer, We need
	 * the `wp_slash` in order to avoid the unslashing during the `add_post_meta`.
	 *
	 * Fired by `wxr_importer.pre_process.post_meta` filter.
	 *
	 * @since 1.7.3
	 * @access public
	 * @static
	 *
	 * @param array $post_meta Post meta.
	 *
	 * @return array Updated post meta.
	 */
	public function on_wxr_importer_pre_process_post_meta( $post_meta ) {
		if ( '_elementor_data' === $post_meta['key'] ) {
			$post_meta['value'] = wp_slash( $post_meta['value'] );
		}

		return $post_meta;
	}
}
