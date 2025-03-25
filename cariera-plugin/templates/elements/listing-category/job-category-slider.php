<?php
/**
 * Elementor Element: Job Category Slider
 *
 * This template can be overridden by copying it to cariera-child/cariera_core/elements/listing/job-category-slider.php.
 *
 * @package     cariera
 * @category    Template
 * @since       1.7.3
 * @version     1.7.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="category-groups category-slider-layout <?php echo esc_attr( $settings['custom_class'] ); ?>">
	<div class="job-cat-slider1" data-columns="<?php echo esc_attr( $settings['columns'] ); ?>">

		<?php
		foreach ( $chunks as $chunk ) {
			foreach ( $chunk as $term ) {
				$img_icon  = get_term_meta( $term->term_id, 'cariera_image_icon', true );
				$font_icon = get_term_meta( $term->term_id, 'cariera_font_icon', true );
				?>

				<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="item">
					<div class="cat-item <?php echo esc_attr( $settings['style'] ); ?>-style">

						<?php if ( 'show' === $settings['icon'] ) { ?>
							<span class="cat-icon">
								<?php if ( ! empty( $img_icon ) ) { ?>
									<img src="<?php echo esc_attr( $img_icon ); ?>" class="category-icon" alt="<?php esc_attr_e( 'Image icon', 'cariera-core' ); ?>" />
								<?php } ?>

								<?php if ( ! empty( $font_icon ) ) { ?>
									<i class="<?php echo esc_attr( $font_icon ); ?>"></i>
								<?php } ?>
							</span>
						<?php } ?>

						<span class="cat-title"><?php echo esc_html( $term->name ); ?></span>
					</div>
				</a>
				<?php
			}
		}
		?>
	</div>
</div>
