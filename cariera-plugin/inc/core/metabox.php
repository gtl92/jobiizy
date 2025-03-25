<?php

namespace Cariera_Core\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Metabox {

	use \Cariera_Core\Src\Traits\Singleton;

	/**
	 * Constructor
	 */
	public function __construct() {
		new \Cariera_Core\Core\Metabox\Page();
		new \Cariera_Core\Core\Metabox\Post();
		new \Cariera_Core\Core\Metabox\Testimonial();
	}

	/**
	 * Field Type: Heading
	 *
	 * @since 1.5.3
	 *
	 * @param string $key
	 * @param string $field
	 */
	public static function input_heading( $key, $field ) {
		if ( ! empty( $field['name'] ) ) {
			$name = $field['name'];
		} else {
			$name = $key;
		} ?>

		<div class="cariera-form-group field-heading">
			<div class="heading">
				<span class="meta-name"><?php echo esc_html( wp_strip_all_tags( $field['label'] ) ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Field Type: Text
	 *
	 * @since 1.5.3
	 *
	 * @param string $key
	 * @param string $field
	 */
	public static function input_text( $key, $field ) {
		if ( ! empty( $field['name'] ) ) {
			$name = $field['name'];
		} else {
			$name = $key;
		}
		if ( ! empty( $field['classes'] ) ) {
			$classes = implode( ' ', is_array( $field['classes'] ) ? $field['classes'] : [ $field['classes'] ] );
		} else {
			$classes = '';
		}
		?>

		<div class="cariera-form-group field-text">
			<div class="meta-heading">
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( wp_strip_all_tags( $field['label'] ) ); ?>:
				</label>
			</div>
			<div class="meta-field">
				<input type="text" autocomplete="off" name="<?php echo esc_attr( $name ); ?>" class="<?php echo esc_attr( $classes ); ?>" id="<?php echo esc_attr( $key ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" value="<?php echo esc_attr( $field['value'] ); ?>" />            
			</div>
			<?php if ( ! empty( $field['description'] ) ) { ?>
				<div class="meta-description">
					<p><?php echo esc_attr( $field['description'] ); ?></p>
				</div>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * File input field
	 *
	 * @since 1.5.3
	 *
	 * @param string $key
	 * @param string $name
	 * @param string $placeholder
	 * @param string $value
	 * @param string $multiple
	 */
	private static function file_url_field( $key, $name, $placeholder, $value, $multiple ) {
		$name = esc_attr( $name );
		if ( $multiple ) {
			$name = $name . '[]';
		}
		?>

		<span class="file_url">
			<input type="text" name="<?php echo esc_attr( $name ); ?>" 
			<?php
			if ( ! $multiple ) {
				echo 'id="' . esc_attr( $key ) . '"'; }
			?>
			placeholder="<?php echo esc_attr( $placeholder ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			<button class="button button-small cariera_upload_file_button" data-uploader_button_text="<?php esc_attr_e( 'Use file', 'cariera-core' ); ?>"><?php esc_html_e( 'Upload', 'cariera-core' ); ?></button>
			<button class="button button-small cariera_view_file_button"><?php esc_html_e( 'View', 'cariera-core' ); ?></button>
		</span>
		<?php
	}

	/**
	 * Field Type: File
	 *
	 * @since 1.5.3
	 *
	 * @param string $key
	 * @param string $field
	 */
	public static function input_file( $key, $field ) {
		global $post;

		if ( empty( $field['placeholder'] ) ) {
			$field['placeholder'] = 'https://';
		}
		if ( ! empty( $field['name'] ) ) {
			$name = $field['name'];
		} else {
			$name = $key;
		}
		?>

		<div class="cariera-form-group field-file">
			<div class="meta-heading">
				<label for="<?php echo esc_attr( $key ); ?>">
					<?php echo esc_html( wp_strip_all_tags( $field['label'] ) ); ?>:
				</label>
			</div>
			<div class="meta-field">
				<?php
				if ( ! empty( $field['multiple'] ) ) {
					foreach ( (array) $field['value'] as $k => $value ) {
						self::file_url_field( $key, $name, $field['placeholder'], $value, true );
					}
				} else {
					self::file_url_field( $key, $name, $field['placeholder'], $field['value'], false );
				}

				if ( ! empty( $field['multiple'] ) ) {
					?>
					<button class="button button-small cariera_add_another_file_button" data-field_name="<?php echo esc_attr( $key ); ?>" data-field_placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" data-uploader_button_text="<?php esc_attr_e( 'Use file', 'cariera-core' ); ?>" data-uploader_button="<?php esc_attr_e( 'Upload', 'cariera-core' ); ?>" data-view_button="<?php esc_attr_e( 'View', 'cariera-core' ); ?>"><?php esc_html_e( 'Add file', 'cariera-core' ); ?></button>
				<?php } ?>
			</div>
			<?php if ( ! empty( $field['description'] ) ) { ?>
				<div class="meta-description">
					<p><?php echo esc_attr( $field['description'] ); ?></p>
				</div>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Field Type: Select
	 *
	 * @since   1.5.3
	 * @version 1.8.7
	 *
	 * @param string $key
	 * @param string $field
	 */
	public static function input_select( $key, $field ) {
		if ( ! empty( $field['name'] ) ) {
			$name = $field['name'];
		} else {
			$name = $key;
		}

		$selected_value = null;
		if ( isset( $field['value'] ) ) {
			$selected_value = esc_attr( $field['value'] );
		}
		?>

		<div class="cariera-form-group field-select">
			<div class="meta-heading">
				<label for="<?php echo esc_attr( $key ); ?>">
					<?php echo esc_html( wp_strip_all_tags( $field['label'] ) ); ?>:
				</label>
			</div>
			<div class="meta-field">
				<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $key ); ?>" autocomplete="off">
					<?php foreach ( $field['options'] as $key => $value ) : ?>
						<option
							value="<?php echo esc_attr( $key ); ?>"
							<?php
							if ( null !== $selected_value ) {
								selected( $selected_value, trim( $key ) );
							}
							?>
						><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php if ( ! empty( $field['description'] ) ) { ?>
				<div class="meta-description">
					<p><?php echo esc_attr( $field['description'] ); ?></p>
				</div>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Displays label and multi-select input field.
	 *
	 * @since 1.8.8
	 *
	 * @param string $key
	 * @param array  $field
	 */
	public static function input_multiselect( $key, $field ) {
		if ( ! empty( $field['name'] ) ) {
			$name = $field['name'];
		} else {
			$name = $key;
		}
		?>
		<div class="cariera-form-group field-multiselect">
			<div class="meta-heading">
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( wp_strip_all_tags( $field['label'] ) ); ?>:
			</div>

			<div class="meta-field">
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="">
				<select multiple="multiple" name="<?php echo esc_attr( $name ); ?>[]" id="<?php echo esc_attr( $key ); ?>">
					<?php foreach ( $field['options'] as $key => $value ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"
						<?php
						if ( ! empty( $field['value'] ) && is_array( $field['value'] ) ) {
							// phpcs:ignore WordPress.PHP.StrictInArray
							selected( in_array( $key, $field['value'] ), true );
						}
						?>
					><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php if ( ! empty( $field['description'] ) ) : ?>
				<span class="tips" data-tip="<?php echo esc_attr( $field['description'] ); ?>">[?]</span>
			<?php endif; ?>
			</label>
		</div>
		<?php
	}

	/**
	 * Field Type: Switch
	 *
	 * @since 1.5.3
	 *
	 * @param string $key
	 * @param string $field
	 */
	public static function input_switch( $key, $field ) {
		if ( ! empty( $field['name'] ) ) {
			$name = $field['name'];
		} else {
			$name = $key;
		}
		?>

		<div class="cariera-form-group field-switch">
			<div class="meta-heading">
				<label for="<?php echo esc_attr( $key ); ?>">
					<?php echo esc_html( wp_strip_all_tags( $field['label'] ) ); ?>:
				</label>
			</div>
			<div class="meta-field">
				<div class="switch-container">
					<label class="switch">
						<input id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>" type="checkbox" value="1" <?php checked( $field['value'], 1 ); ?>/>
						<span class="switch-btn">
							<span data-on="<?php esc_html_e( 'on', 'cariera-core' ); ?>" data-off="<?php esc_html_e( 'off', 'cariera-core' ); ?>"></span>
						</span>   
					</label>   
					<?php if ( ! empty( $field['description'] ) ) { ?>
						<p class="description"><?php echo esc_attr( $field['description'] ); ?></p>
					<?php } ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Field Type: Textarea
	 *
	 * @since 1.5.3
	 *
	 * @param string $key
	 * @param string $field
	 */
	public static function input_textarea( $key, $field ) {
		if ( ! empty( $field['name'] ) ) {
			$name = $field['name'];
		} else {
			$name = $key;
		}
		?>

		<div class="cariera-form-group field-switch">
			<div class="meta-heading">
				<label for="<?php echo esc_attr( $key ); ?>">
					<?php echo esc_html( wp_strip_all_tags( $field['label'] ) ); ?>:
				</label>
			</div>
			<div class="meta-field">
				<textarea name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $key ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"><?php echo esc_html( $field['value'] ); ?></textarea>
			</div>
			<?php if ( ! empty( $field['description'] ) ) { ?>
				<div class="meta-description">
					<p><?php echo esc_attr( $field['description'] ); ?></p>
				</div>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Displays label and user select field.
	 *
	 * @since 1.8.8
	 *
	 * @param string $key
	 * @param array  $field
	 */
	public static function input_user( $key, $field ) {
		global $thepostid;

		// Get the saved user ID for this field.
		$saved_user_id = get_post_meta( $thepostid, $key, true );

		// Get user details.
		$selected_user = $saved_user_id ? get_user_by( 'id', $saved_user_id ) : null;

		// Field name fallback.
		$name = ! empty( $field['name'] ) ? $field['name'] : $key;

		?>
		<div class="cariera-form-group field-author">
			<div class="meta-heading">
				<label for="<?php echo esc_attr( $key ); ?>">
					<?php echo esc_html( wp_strip_all_tags( $field['label'] ) ); ?>:
				</label>
			</div>
			<div class="meta-field">
				<span class="current-author">
					<?php
					if ( $selected_user ) {
						$user_string = sprintf(
							// translators: Used in user select. %1$s is the user's display name; #%2$s is the user ID; %3$s is the user email.
							esc_html__( '%1$s (#%2$s – %3$s)', 'cariera-core' ),
							htmlentities( $selected_user->display_name ),
							absint( $selected_user->ID ),
							$selected_user->user_email
						);
						echo '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . absint( $saved_user_id ) ) ) . '">#' . absint( $saved_user_id ) . ' &ndash; ' . esc_html( $selected_user->user_login ) . '</a>';
					} else {
						echo esc_html__( 'Guest User', 'cariera-core' );
					}
					?>
					<a href="#" class="change-author button button-small"><?php esc_html_e( 'Change', 'cariera-core' ); ?></a>
				</span>
				<span class="hidden change-author">
					<select class="wpjm-user-search" id="job_manager_user_search" name="<?php echo esc_attr( $name ); ?>" data-placeholder="<?php esc_attr_e( 'Select a user', 'cariera-core' ); ?>" data-allow_clear="true">
						<?php if ( $selected_user ) : ?>
							<option value="<?php echo esc_attr( $saved_user_id ); ?>" selected="selected"><?php echo esc_html( htmlspecialchars( $user_string ) ); ?></option>
						<?php endif; ?>
					</select>
				</span>
			</div>
		</div>
		<?php
	}
}
