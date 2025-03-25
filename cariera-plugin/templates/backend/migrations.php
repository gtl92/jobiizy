<?php
/**
 * Debug Log
 *
 * This template can be overridden by copying it to cariera-child/cariera_core/backend/migrations.php.
 *
 * @package     cariera
 * @category    Template
 * @since       1.8.3
 * @version     1.8.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$migration_items = Cariera_Core\Core\Migrations::get_migration_items();
?>

<div class="cariera-migrations">
	<div class="migrations-item-wrapper">
		<?php
		/**
		 * Action: cariera_export_before_content
		 */
		do_action( 'cariera_migrations_after_content' );
		?>

		<?php if ( ! empty( $migration_items ) ) { ?>
			<?php foreach ( $migration_items as $item ) { ?>
				<?php if ( isset( $item['name'], $item['action'], $item['icon'] ) ) { ?>
					<div class="migrations-item migrations-item-<?php echo esc_attr( sanitize_title( $item['name'] ) ); ?>">
						<form action="<?php echo esc_url( admin_url( '/admin-post.php' ) ); ?>" method="POST" class="migration-form">
							<div class="process"><span><?php esc_html_e( 'Processing...', 'cariera-core' ); ?></span></div>

							<p class="item-name"><i class="<?php echo esc_attr( $item['icon'] ); ?>"></i><?php echo esc_html( $item['name'] ); ?>
							</p>

							<p class="item-description"><?php echo esc_html( $item['description'] ); ?></p>

							<div class="item-footer">
								<?php if ( isset( $item['type'] ) && ! empty( $item['type'] ) ) { ?>
									<a href="<?php echo esc_url( $item['link'] ); ?>" class="button item-button" target="_blank"><?php echo esc_html( $item['btn_title'] ); ?></a>
								<?php } else { ?>
									<button name="migrations" data-action="<?php echo esc_attr( $item['action'] ); ?>" class="cariera-btn"><?php echo esc_html( $item['btn_title'] ); ?></button>
								<?php } ?>
								<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( $item['action'] ) ); ?>">
								<input type="hidden" name="action" value="<?php echo esc_attr( $item['action'] ); ?>">
							</div>
						</form>
					</div>
				<?php } ?>
			<?php } ?>
		<?php } ?>

		<?php
		/**
		 * Action: cariera_export_after_content
		 */
		do_action( 'cariera_migrations_after_content' );
		?>
	</div>
</div>
