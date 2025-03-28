<?php

namespace Cariera_Core\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ajax {

	use \Cariera_Core\Src\Traits\Singleton;

	/**
	 * Constructor function.
	 */
	public function __construct() {
		// AJAX Dropzone.
		add_action( 'wp_ajax_handle_uploaded_media', [ $this, 'uploaded_dropzone_media' ] );
		add_action( 'wp_ajax_handle_deleted_media', [ $this, 'deleted_dropzone_media' ] );
	}

	/**
	 * Upload Media function for dropzone
	 *
	 * @since 1.4.7
	 */
	public function uploaded_dropzone_media() {
		status_header( 200 );

		$upload_dir  = wp_upload_dir();
		$upload_path = $upload_dir['path'] . DIRECTORY_SEPARATOR;
		// $num_files        = count($_FILES['file']['tmp_name']);

		$newupload = 0;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_FILES ) ) {
			$files = $_FILES;
			foreach ( $files as $file ) {
				$newfile = [
					'name'     => $file['name'],
					'type'     => $file['type'],
					'tmp_name' => $file['tmp_name'],
					'error'    => $file['error'],
					'size'     => $file['size'],
				];

				$_FILES = [ 'upload' => $newfile ];
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				foreach ( $_FILES as $file => $array ) {
					$newupload = media_handle_upload( $file, 0 );
				}
			}
		}

		echo $newupload; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
	}

	/**
	 * Delete Media function for dropzone
	 *
	 * @since 1.4.7
	 */
	public function deleted_dropzone_media() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['media_id'] ) ) {
			$post_id = absint( $_REQUEST['media_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$status  = wp_delete_attachment( $post_id, true );
			if ( $status ) {
				echo wp_json_encode( [ 'status' => 'OK' ] );
			} else {
				echo wp_json_encode( [ 'status' => 'FAILED' ] );
			}
		}

		wp_die();
	}
}
