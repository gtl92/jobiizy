<?php
/* info: GTL child modified 2025 */

global $post, $wp;
$post_type = get_post_type_object( get_post_type( $post ) );
if ( ! isset( $post ) || ! is_object( $post ) || empty( $post->ID ) ) {
	$post = get_post();
}
$is_bookmarked = function_exists( 'WP_Job_Manager_Bookmarks' ) ? WP_Job_Manager_Bookmarks()->is_bookmarked( $post->ID ) : false;
$note = function_exists( 'WP_Job_Manager_Bookmarks' ) ? WP_Job_Manager_Bookmarks()->get_note( $post->ID ) : '';
?>

<form method="post" action="<?php echo esc_url( get_permalink() ); ?>" class="job-manager-form wp-job-manager-bookmarks-form <?php echo $is_bookmarked ? 'has-bookmark' : ''; ?>">
	<?php if ( current_user_can( 'administrator' ) ) : ?>
		<!-- 🛠 Template enfant utilisé -->
	<?php endif; ?>

	<?php if ( $is_bookmarked ) : ?>
		<div class="remove-bookmark-wrapper">
			<!-- Le lien de suppression est identique à celui utilisé dans "My Bookmarks" -->
			<a class="remove-bookmark" href="<?php echo wp_nonce_url( add_query_arg( 'remove_bookmark', absint( $post->ID ), get_permalink() ), 'remove_bookmark' ); ?>">
				<?php _e( 'Remove Bookmark', 'wp-job-manager-bookmarks' ); ?>
			</a>
		</div>
	<?php else : ?>
		<div class="add-bookmark-wrapper">
			<a class="bookmark-notice" href="#">
				<?php printf( __( 'Bookmark This %s', 'wp-job-manager-bookmarks' ), ucwords( $post_type->labels->singular_name ) ); ?>
			</a>
		</div>
	<?php endif; ?>

	<div class="bookmark-details">
		<p>
			<label for="bookmark_notes"><?php _e( 'Notes:', 'wp-job-manager-bookmarks' ); ?></label>
			<textarea name="bookmark_notes" id="bookmark_notes" cols="25" rows="3"><?php echo esc_textarea( $note ); ?></textarea>
		</p>
		<p>
			<?php wp_nonce_field( 'update_bookmark' ); ?>
			<input type="hidden" name="job_id" value="<?php echo absint( $post->ID ); ?>" />
			<input type="hidden" name="bookmark_post_id" value="<?php echo absint( $post->ID ); ?>" />
			<input type="submit" class="submit-bookmark-button" name="submit_bookmark" value="<?php echo $is_bookmarked ? __( 'Update Bookmark', 'wp-job-manager-bookmarks' ) : __( 'Add Bookmark', 'wp-job-manager-bookmarks' ); ?>" />
		</p>
	</div>
</form>

<?php
// Traitement du formulaire (ajout ou suppression) via template_redirect
add_action('template_redirect', function () {
	if (
		$_SERVER['REQUEST_METHOD'] === 'POST' &&
		isset($_POST['submit_bookmark']) &&
		! empty($_POST['bookmark_post_id']) &&
		wp_verify_nonce($_POST['_wpnonce'], 'update_bookmark')
	) {
		$job_id = absint($_POST['bookmark_post_id']);
		$user_id = get_current_user_id();

		// Si le formulaire a été soumis pour suppression, on utilise la méthode GET du plugin
		if (isset($_POST['submit_bookmark']) && strtolower(trim($_POST['submit_bookmark'])) === 'remove bookmark') {
			// Pour la suppression, on peut soit appeler la méthode du plugin (si disponible), soit supprimer directement
			if ( $user_id && $job_id ) {
				$bookmarked_jobs = get_user_meta( $user_id, '_bookmarks', true );
				if ( ! is_array( $bookmarked_jobs ) ) {
					$bookmarked_jobs = array();
				}
				$updated = array_values(array_diff($bookmarked_jobs, array($job_id)));
				if ( empty($updated) ) {
					delete_user_meta( $user_id, '_bookmarks' );
				} else {
					update_user_meta( $user_id, '_bookmarks', $updated );
				}
			}
			$redirect_url = add_query_arg('remove_bookmark', 1, get_permalink($job_id));
			wp_safe_redirect($redirect_url);
			exit;
		} else {
			// Sinon, on ajoute le signet via le plugin
			if ( function_exists( 'WP_Job_Manager_Bookmarks' ) ) {
				WP_Job_Manager_Bookmarks()->add_bookmark();
			}
			$redirect_url = add_query_arg('bookmark_added', 1, get_permalink($job_id));
			wp_safe_redirect($redirect_url);
			exit;
		}
	}
});
?>




