<?php
/**
 * Elementor Element: Video Popup
 *
 * This template can be overridden by copying it to cariera-child/cariera_core/elements/video-popup.php.
 *
 * @package     cariera
 * @category    Template
 * @since       1.7.2
 * @version     1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( 'window' === $settings['open'] ) {
	$open = 'target="_blank"';
} else {
	$open = 'class="popup-video"';
}
?>

<div class="video-container">
	<?php if ( ! empty( $settings['overlay'] ) ) { ?>
		<div class="overlay" style="background: <?php echo esc_attr( $settings['overlay'] ); ?>"></div>
	<?php } ?>

	<img src="<?php echo esc_url( $settings['image']['url'] ); ?>" alt="" />
	<a href="<?php echo esc_url( $settings['link']['url'] ); ?>" <?php echo esc_attr( $open ); ?>><span class="play-video"><span class="las la-play"></span></span></a>
</div>
