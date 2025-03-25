<?php
/**
 * Onboarding Importer Popup: Success
 *
 * This template can be overridden by copying it to cariera-child/cariera_core/backend/importer/popup-download-images-form.php.
 *
 * @package     cariera
 * @category    Template
 * @since       1.7.3
 * @version     1.7.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="import-success">
	<svg version="1.1" viewBox="0 0 52 52" xml:space="preserve" xmlns="http://www.w3.org/2000/svg"><path d="M26,0C11.664,0,0,11.663,0,26s11.664,26,26,26s26-11.663,26-26S40.336,0,26,0z M26,50C12.767,50,2,39.233,2,26 S12.767,2,26,2s24,10.767,24,24S39.233,50,26,50z"/><path d="m38.252 15.336-15.369 17.29-9.259-7.407c-0.43-0.345-1.061-0.274-1.405 0.156-0.345 0.432-0.275 1.061 0.156 1.406l10 8c0.184 0.147 0.405 0.219 0.625 0.219 0.276 0 0.551-0.114 0.748-0.336l16-18c0.367-0.412 0.33-1.045-0.083-1.411-0.414-0.368-1.045-0.331-1.413 0.083z"/></svg>
	<h4 class="popup-title"><?php esc_html_e( 'Import completed successfully!', 'cariera-core' ); ?></h4>
	<p class="popup-subtitle"><?php esc_html_e( 'You can now start customizing the imported data.', 'cariera-core' ); ?></p>

	<div class="popup-footer">
		<div class="buttons">
			<a href="#" class="close-button"><?php esc_html_e( 'Close', 'cariera-core' ); ?></a>
			<a href="<?php echo esc_url( site_url( '/' ) ); ?>" target="_blank" class="next-button"><?php esc_html_e( 'View your website', 'cariera-core' ); ?></a>
		</div>
	</div>
</div>
