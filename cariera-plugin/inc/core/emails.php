<?php

namespace Cariera_Core\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Emails {

	use \Cariera_Core\Src\Traits\Singleton;

	/**
	 * Constructor function.
	 *
	 * @since   1.4.3
	 * @version 1.6.1
	 */
	public function __construct() {
		// Welcome Mail.
		add_action( 'cariera_new_user_notification', [ $this, 'user_welcome' ] );
		add_action( 'cariera_new_user_notification', [ $this, 'user_welcome_admin' ] );

		// User Account Approval.
		add_action( 'cariera_new_user_approval_notification', [ $this, 'new_user_approval' ] );
		add_action( 'cariera_new_user_approved_notification', [ $this, 'user_approved' ] );
		add_action( 'cariera_new_user_denied_notification', [ $this, 'user_denied' ] );

		// Account Delete.
		add_action( 'cariera_delete_account_email', [ $this, 'account_delete' ] );

		// Listing Promotion.
		add_action( 'cariera_listing_promotion_started', [ $this, 'listing_promoted' ] );
		add_action( 'cariera_listing_promotion_ended', [ $this, 'listing_promotion_expired' ] );

		// Private Message.
		add_action( 'cariera_private_messages_email_notification', [ $this, 'private_message_sent' ] );

		// WP Job Manager.
		add_action( 'pending_to_publish', [ $this, 'job_listing_published_send_email' ] );
		add_action( 'pending_payment_to_publish', [ $this, 'job_listing_published_send_email' ] );

		// WP Resume Manager.
		add_action( 'pending_to_publish', [ $this, 'resume_published_send_email' ] );
		add_action( 'pending_payment_to_publish', [ $this, 'resume_published_send_email' ] );
		add_action( 'transition_post_status', [ $this, 'resume_expired_send_email' ], 10, 3 );
	}

	/**
	 * Main function to send the emails with all the required data
	 *
	 * @since   1.4.8
	 * @version 1.5.3
	 *
	 * @param string $emailto
	 * @param string $subject
	 * @param mixed  $body
	 * @param array  $attachment
	 */
	public static function send( $emailto, $subject, $body, $attachment = [] ) {
		$headers = self::get_email_headers();

		if ( empty( $emailto ) || empty( $subject ) || empty( $body ) ) {
			return;
		}

		ob_start();

		get_template_part( '/templates/emails/header' ); ?>
			<tr>
				<td class="details">
					<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</td>
			</tr>
		<?php
		get_template_part( '/templates/emails/footer' );

		$body = ob_get_clean();

		// Use WP Mail function and handle possible errors.
		$sent = wp_mail( $emailto, $subject, $body, $headers, $attachment );

		if ( ! $sent ) {
			\Cariera\write_log( sprintf( 'Failed to send email to %s with subject: %s', $emailto, $subject ) );
		}
	}


	/**
	 * Get email headers.
	 *
	 * @since 1.8.4
	 *
	 * @return string
	 */
	private static function get_email_headers() {
		$from_name  = esc_html( get_option( 'cariera_emails_name', get_bloginfo( 'name' ) ) );
		$from_email = sanitize_email( get_option( 'cariera_emails_from_email', get_bloginfo( 'admin_email' ) ) );
		return sprintf( "From: %s <%s>\r\nContent-Type: text/html", $from_name, $from_email );
	}

	/**
	 * Replacing Email args with actual data
	 *
	 * @since   1.4.8
	 * @version 1.8.7
	 *
	 * @param array $args
	 * @param mixed $body
	 */
	public function replace_shortcode( $args, $body ) {
		$tags = [
			'user_name'    => '',
			'user_mail'    => '',
			'password'     => '',
			'first_name'   => '',
			'last_name'    => '',
			'site_name'    => '',
			'site_url'     => '',
			'approval_url' => '',
			'listing_name' => '',
			'listing_url'  => '',
			'sender_name'  => '',
			'sender_mail'  => '',
		];

		$tags = array_merge( $tags, $args );

		extract( $tags );

		$tags = [
			'{user_name}',
			'{user_mail}',
			'{password}',
			'{first_name}',
			'{last_name}',
			'{site_name}',
			'{site_url}',
			'{approval_url}',
			'{listing_name}',
			'{listing_url}',
			'{sender_name}',
			'{sender_mail}',
		];

		$values = [
			$user_name,
			$user_mail,
			$password,
			$first_name,
			$last_name,
			get_bloginfo( 'name' ),
			get_home_url(),
			$approval_url,
			$listing_name,
			$listing_url,
			$sender_name,
			$sender_mail,
		];

		$message = str_replace( $tags, $values, $body );
		$message = nl2br( $message );
		$message = htmlspecialchars_decode( $message, ENT_QUOTES );

		return $message;
	}

	/**
	 * "Welcome" Email function when user registers
	 *
	 * @since   1.4.8
	 * @version 1.5.3
	 *
	 * @param array $args
	 */
	public function user_welcome( $args ) {

		// Return if email notification has been disabled.
		if ( ! get_option( 'cariera_user_welcome_email' ) ) {
			return;
		}

		$email = $args['email'];

		// The args that can be replaced via the replace_shortcode().
		$args = [
			'user_name' => $args['display_name'],
			'user_mail' => $args['email'],
			'password'  => $args['password'],
		];

		// Email Subject.
		$subject = get_option( 'cariera_user_welcome_email_subject' );
		$subject = $this->replace_shortcode( $args, $subject );

		// Email Content.
		$body = get_option( 'cariera_user_welcome_email_content' );
		$body = $this->replace_shortcode( $args, $body );

		self::send( $email, $subject, $body, apply_filters( 'cariera_registration_users_attachment', [] ) );
	}

	/**
	 * Email function to notify admin that a new user just registered
	 *
	 * @since   1.4.8
	 * @version 1.8.4
	 *
	 * @param array $args
	 */
	public function user_welcome_admin( $args ) {

		// Return if email notification has been disabled.
		if ( ! get_option( 'cariera_user_welcome_email_admin' ) ) {
			return;
		}

		$email = get_option( 'admin_email' );

		// The args that can be replaced via the replace_shortcode().
		$shortcode_args = [
			'user_name' => $args['display_name'],
			'user_mail' => $args['email'],
		];

		// Email Subject.
		$subject = esc_html__( 'New User Registration', 'cariera-core' );

		// Email Content.
		$body  = esc_html__( 'Hi Admin,', 'cariera-core' ) . '<br><br>';
		$body .= esc_html__( 'A new user just registered on your website.', 'cariera-core' ) . '<br><br>';
		$body .= esc_html__( 'Username: {user_name}', 'cariera-core' ) . '<br>';
		$body .= esc_html__( 'Email: {user_mail}', 'cariera-core' );

		$subject = $this->replace_shortcode( $shortcode_args, $subject );
		$body    = $this->replace_shortcode( $shortcode_args, $body );

		self::send( $email, $subject, $body );
	}

	/**
	 * Email function to notify admin/user to approve their account after they register
	 *
	 * @since   1.4.8
	 * @version 1.8.4
	 *
	 * @param array $args
	 */
	public function new_user_approval( $args ) {
		$email = $args['send_to'];

		// The args that can be replaced via the replace_shortcode().
		$shortcode_args = [
			'user_name'    => $args['display_name'],
			'user_mail'    => $args['email'],
			'password'     => $args['password'],
			'approval_url' => $args['approval_url'],
		];

		// Email subject & body.
		$subject = get_option( 'cariera_new_user_approve_email_subject' );
		$body    = get_option( 'cariera_new_user_approve_email_content' );

		// Email shortcode replacement.
		$subject = $this->replace_shortcode( $shortcode_args, $subject );
		$body    = $this->replace_shortcode( $shortcode_args, $body );

		self::send( $email, $subject, $body );
	}

	/**
	 * Email function when the new user has been approved
	 *
	 * @since 1.4.8
	 * @version 1.8.4
	 *
	 * @param array $args
	 */
	public function user_approved( $args ) {
		$email = $args['email'];

		// The args that can be replaced via the replace_shortcode().
		$shortcode_args = [
			'user_name' => $args['display_name'],
			'user_mail' => $args['email'],
			'site_url'  => $args['site_url'],
			'password'  => $args['password'],
		];

		// Email subject & body.
		$subject = get_option( 'cariera_new_user_approved_email_subject' );
		$body    = get_option( 'cariera_new_user_approved_email_content' );

		// Shortcode Replacement.
		$subject = $this->replace_shortcode( $shortcode_args, $subject );
		$body    = $this->replace_shortcode( $shortcode_args, $body );

		self::send( $email, $subject, $body );
	}

	/**
	 * Email function when the new user has been denied
	 *
	 * @since 1.4.8
	 *
	 * @param array $args
	 */
	public function user_denied( $args ) {
		$email = $args['email'];

		// The args that can be replaced via the replace_shortcode().
		$shortcode_args = [
			'user_name' => $args['display_name'],
			'user_mail' => $args['email'],
			'site_url'  => $args['site_url'],
		];

		// Email subject & body.
		$subject = get_option( 'cariera_new_user_denied_email_subject' );
		$body    = get_option( 'cariera_new_user_denied_email_content' );

		// Email shortcode replacement.
		$subject = $this->replace_shortcode( $shortcode_args, $subject );
		$body    = $this->replace_shortcode( $shortcode_args, $body );

		self::send( $email, $subject, $body );
	}

	/**
	 * Email function when user deletes their account
	 *
	 * @since 1.4.8
	 * @version 1.8.4
	 *
	 * @param array $args
	 */
	public function account_delete( $args ) {
		// Return if email notification has been disabled.
		if ( ! get_option( 'cariera_delete_account_email' ) ) {
			return;
		}

		$email = $args['email'];

		// The args that can be replaced via the replace_shortcode().
		$shortcode_args = [
			'user_name'  => $args['display_name'],
			'user_mail'  => $args['email'],
			'first_name' => $args['first_name'],
			'last_name'  => $args['last_name'],
		];

		// Email Subject & Body.
		$subject = get_option( 'cariera_delete_account_email_subject' );
		$body    = get_option( 'cariera_delete_account_email_content' );

		// Email shortcode replacement.
		$subject = $this->replace_shortcode( $shortcode_args, $subject );
		$body    = $this->replace_shortcode( $shortcode_args, $body );

		self::send( $email, $subject, $body );
	}

	/**
	 * Email function when a listing gets promoted
	 *
	 * @since 1.5.0
	 * @version 1.8.4
	 *
	 * @param int $post_id
	 */
	public function listing_promoted( $post_id ) {
		$post = get_post( $post_id );

		// Return if post is not a listing or "listing notification" has been disabled.
		if ( ! in_array( $post->post_type, [ 'job_listing', 'resume', 'company' ], true ) || ! get_option( 'cariera_listing_promoted_email' ) ) {
			return;
		}

		$author = get_userdata( $post->post_author );
		$email  = $author->data->user_email;

		// The args that can be replaced via the replace_shortcode().
		$shortcode_args = [
			'user_name'    => $author->display_name,
			'user_mail'    => $email,
			'listing_name' => $post->post_title,
			'listing_url'  => get_permalink( $post->ID ),
		];

		// Email Subject & Body.
		$subject = get_option( 'cariera_listing_promoted_email_subject' );
		$body    = get_option( 'cariera_listing_promoted_email_content' );

		// Email shortcode replacement.
		$subject = $this->replace_shortcode( $shortcode_args, $subject );
		$body    = $this->replace_shortcode( $shortcode_args, $body );

		self::send( $email, $subject, $body );
	}

	/**
	 * Email function when a listing promotion expires
	 *
	 * @since 1.5.0
	 *
	 * @param int $post_id
	 */
	public function listing_promotion_expired( $post_id ) {
		$post = get_post( $post_id );

		// Return if post is not a listing or "listing notification" has been disabled.
		if ( ! in_array( $post->post_type, [ 'job_listing', 'resume', 'company' ], true ) || ! get_option( 'cariera_promotion_expired_email' ) ) {
			return;
		}

		$author = get_userdata( $post->post_author );
		$email  = $author->data->user_email;

		// The args that can be replaced via the replace_shortcode().
		$shortcode_args = [
			'user_name'    => $author->display_name,
			'user_mail'    => $email,
			'listing_name' => $post->post_title,
			'listing_url'  => get_permalink( $post->ID ),
		];

		// Email Subject & Body.
		$subject = get_option( 'cariera_promotion_expired_email_subject' );
		$body    = get_option( 'cariera_promotion_expired_email_content' );

		// Email Content shortcode replacement.
		$subject = $this->replace_shortcode( $shortcode_args, $subject );
		$body    = $this->replace_shortcode( $shortcode_args, $body );

		self::send( $email, $subject, $body );
	}

	/**
	 * Sends email notification when a private message gets sent
	 *
	 * @since 1.6.1
	 *
	 * @param array $args
	 */
	public function private_message_sent( $args ) {
		// Receiver data.
		$receiver = get_userdata( $args['to_id'] );
		$email    = $receiver->user_email;

		// Sender data.
		$sender = get_userdata( $args['from_id'] );

		// The args that can be replaced via the replace_shortcode().
		$shortcode_args = [
			'user_name'   => $receiver->display_name,
			'user_mail'   => $email,
			'first_name'  => $receiver->first_name,
			'last_name'   => $receiver->last_name,
			'sender_name' => ! empty( $sender->first_name ) ? $sender->first_name : $sender->display_name,
			'sender_mail' => $sender->user_email,
		];

		// Email Subject & Body.
		$subject = get_option( 'cariera_private_messages_email_subject' );
		$body    = get_option( 'cariera_private_messages_email_content' );

		// Email Content shortocde replacement.
		$subject = $this->replace_shortcode( $shortcode_args, $subject );
		$body    = $this->replace_shortcode( $shortcode_args, $body );

		self::send( $email, $subject, $body );
	}

	/*
	=====================================================
		WP JOB MANAGER
	=====================================================
	*/

	/**
	 * Sends email to the Employer when their Job Listings get approved
	 *
	 * @since 	1.4.0
	 * @version 1.8.5
	 *
	 * @param int $post_id
	 */
	public function job_listing_published_send_email( $post_id ) {
		if ( 'job_listing' !== get_post_type( $post_id ) || ! get_option( 'cariera_job_manager_approved_job_notification' ) ) {
			return;
		}

		$post    = get_post( $post_id );
		$author  = get_userdata( $post->post_author );
		$subject = esc_html__( 'Your Job Listing has been approved!', 'cariera-core' );

		// translators: %s is the author name.
		$message = sprintf( esc_html__( 'Hello %s,', 'cariera-core' ), $author->display_name ) . "\n \n";
		// translators: %s is the listing title.
		$message .= sprintf( esc_html__( 'Your Job Listing *%s* has been approved.', 'cariera-core' ), $post->post_title ) . "\n \n";
		// translators: %s is the listing url.
		$message .= sprintf( esc_html__( 'You can visit your Job Listing by clicking on the following link: %s', 'cariera-core' ), get_permalink( $post_id ) ) . "\n \n";

		self::send( $author->user_email, $subject, $message );
	}

	/*
	=====================================================
		WP RESUME MANAGER
	=====================================================
	*/

	/**
	 * Sends email to the Candidate when their resume gets approved
	 *
	 * @since 	1.4.0
	 * @version 1.8.5
	 *
	 * @param int $post_id
	 */
	public function resume_published_send_email( $post_id ) {
		if ( 'resume' !== get_post_type( $post_id ) || ! get_option( 'cariera_resume_manager_approved_resume_notification' ) ) {
			return;
		}

		$post    = get_post( $post_id );
		$author  = get_userdata( $post->post_author );
		$subject = esc_html__( 'Your Resume has been approved!', 'cariera-core' );

		// translators: %s is the user name.
		$message = sprintf( esc_html__( 'Hello %s,', 'cariera-core' ), $author->display_name ) . "\n \n";
		// translators: %s is the listing title.
		$message .= sprintf( esc_html__( 'Your resume *%s* has been approved.', 'cariera-core' ), $post->post_title ) . "\n \n";
		// translators: %s is the listing url.
		$message .= sprintf( esc_html__( 'You can visit your resume by clicking on the following link: %s', 'cariera-core' ), get_permalink( $post_id ) ) . "\n \n";

		self::send( $author->user_email, $subject, $message );
	}

	/**
	 * Sends email to the Candidate when their resume expires
	 *
	 * @since 	1.4.0
	 * @version 1.8.5
	 *
	 * @param string  $new_status
	 * @param string  $old_status
	 * @param WP_POST $post
	 */
	public function resume_expired_send_email( $new_status, $old_status, $post ) {
		if ( 'resume' !== $post->post_type || 'expired' !== $new_status || $old_status === $new_status || ! get_option( 'cariera_resume_manager_expired_resume_notification' ) ) {
			return;
		}

		$author  = get_userdata( $post->post_author );
		$subject = esc_html__( 'Your Resume has expired!', 'cariera-core' );

		// translators: %s is the user's name.
		$message = sprintf( esc_html__( 'Hello %s,', 'cariera-core' ), $author->display_name ) . "\n \n";
		// translators: %1$s is the listing title, %2$s is the url.
		$message .= sprintf( esc_html__( 'Your resume *%1$s* has now expired: %2$s', 'cariera-core' ), $post->post_title, get_permalink( $post->ID ) );

		self::send( $author->user_email, $subject, $message );
	}
}
