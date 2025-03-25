<?php

namespace Cariera_Core\Extensions\Social_Share;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sharer {

	use \Cariera_Core\Src\Traits\Singleton;

	/**
	 * Constructor function.
	 */
	public function __construct() {
		add_action( 'cariera_social_share', [ $this, 'social_share' ] );
	}

	/**
	 * Sharing output function
	 *
	 * @since   1.4.2
	 * @version 1.8.0
	 */
	public function social_share() {
		echo '<div class="social-sharer-wrapper mt20"><a href="#social-share-modal" class="btn btn-main popup-with-zoom-anim">' . esc_html__( 'share', 'cariera-core' ) . '</a></div>';

		add_action( 'wp_footer', [ $this, 'sharing_modal' ] );
	}

	/**
	 * Sharing modal
	 *
	 * @since  1.4.2
	 */
	public function sharing_modal( $post = null ) {
		?>
		<div id="social-share-modal" class="small-dialog zoom-anim-dialog mfp-hide">
			<div class="small-dialog-headline">
				<h3 class="title"><?php esc_html_e( 'Share', 'cariera-core' ); ?></h3>
			</div>

			<div class="small-dialog-content">
				<?php $this->sharing_options_output( $post ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * An array of all social media options.
	 *
	 * @since   1.7.9
	 * @version 1.8.0
	 */
	public function social_media( $post = null ) {
		$title = get_the_title( $post );
		$link  = get_permalink( $post );

		$social = apply_filters(
			'cariera_sharing_social_media_options',
			[
				'facebook'  => [
					'id'    => 'facebook',
					'link'  => 'https://www.facebook.com/sharer.php?u=' . urlencode( $link ) . '&title=' . urlencode( $title ),
					'title' => esc_html__( 'Facebook', 'cariera-core' ),
					'icon'  => '<i class="social-btn-icon lab la-facebook-f"></i>',
				],
				'twitter-x' => [
					'id'    => 'twitter-x',
					'link'  => 'http://twitter.com/share?text=' . urlencode( $title ) . '&url=' . urlencode( $link ),
					'title' => esc_html__( 'X', 'cariera-core' ),
					// 'icon'  => '<svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"></path></svg>',
				],
				'linkedin'  => [
					'id'    => 'linkedin',
					'link'  => 'http://www.linkedin.com/shareArticle?url=' . urlencode( $link ) . '&title=' . urlencode( $title ),
					'title' => esc_html__( 'LinkedIn', 'cariera-core' ),
					'icon'  => '<i class="social-btn-icon lab la-linkedin-in"></i>',
				],
				'telegram'  => [
					'id'    => 'telegram',
					'link'  => 'https://telegram.me/share/url?url=' . urlencode( $link ) . '&text=' . urlencode( $title ),
					'title' => esc_html__( 'Telegram', 'cariera-core' ),
					'icon'  => '<i class="social-btn-icon lab la-telegram"></i>',
				],
				'tumblr'    => [
					'id'    => 'tumblr',
					'link'  => 'http://www.tumblr.com/share?v=3&u=' . urlencode( $link ) . '&t=' . urlencode( $title ),
					'title' => esc_html__( 'Tumblr', 'cariera-core' ),
					'icon'  => '<i class="social-btn-icon lab la-tumblr"></i>',
				],
				'whatsapp'  => [
					'id'    => 'whatsapp',
					'link'  => 'https://api.whatsapp.com/send?text=' . urlencode( $link ),
					'title' => esc_html__( 'Whatsapp', 'cariera-core' ),
					'icon'  => '<i class="social-btn-icon lab la-whatsapp"></i>',
				],
				'vk'        => [
					'id'    => 'vk',
					'link'  => 'http://vk.com/share.php?url=' . urlencode( $link ) . '&title=' . urlencode( $title ),
					'title' => esc_html__( 'VK', 'cariera-core' ),
					'icon'  => '<i class="social-btn-icon lab la-vk"></i>',
				],
				'mail'      => [
					'id'    => 'mail',
					'link'  => 'mailto:?subject=' . urlencode( $link ) . '&body=' . urlencode( $title ) . ' - ' . urlencode( $link ),
					'title' => esc_html__( 'Mail', 'cariera-core' ),
					'icon'  => '<i class="social-btn-icon lar la-envelope"></i>',
				],
			]
		);

		return $social;
	}

	/**
	 * Outputting the markup with all the social media options.
	 *
	 * @since 1.7.9
	 */
	public function sharing_options_output( $post = null) {
		$socials = $this->social_media( $post );
		?>

		<ul class="social-btns">
			<?php foreach ( $socials as $social ) { ?>
				<li class="share-<?php echo esc_attr( $social['id'] ); ?>">
					<a href="<?php echo esc_url( $social['link'] ); ?>" target="_blank">
						<div class="social-btn <?php echo esc_attr( $social['id'] ); ?>">
							<?php if ( 'twitter-x' === $social['id'] ) { ?>
								<svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"></path></svg>
								<?php
							} else {
								echo wp_kses_post( $social['icon'] );
							}
							?>
						</div>
						<h4 class="title"><?php echo esc_html( $social['title'] ); ?></h4>
					</a>
				</li>
			<?php } ?>
		</ul>

		<?php
	}
}
