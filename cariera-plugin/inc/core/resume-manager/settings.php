<?php

namespace Cariera_Core\Core\Resume_Manager;

use Cariera_Core\Core\Resume_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings extends Resume_Manager {

	use \Cariera_Core\Src\Traits\Singleton;

	/**
	 * Constructor function.
	 */
	public function __construct() {
		add_filter( 'resume_manager_settings', [ $this, 'settings' ] );
	}

	/**
	 * Add extra settings to Resume Options
	 *
	 * @since   1.3.0
	 * @version 1.8.5
	 *
	 * @param array $settings
	 */
	public function settings( $settings = [] ) {
		$settings['resume_listings'][1][] = [
			'name'       => 'cariera_resume_manager_enable_rate',
			'std'        => '1',
			'label'      => esc_html__( 'Rate', 'cariera-core' ),
			'cb_label'   => esc_html__( 'Enable Rate', 'cariera-core' ),
			'desc'       => esc_html__( 'Allows users to specify their rate when submitting a resume.', 'cariera-core' ),
			'type'       => 'checkbox',
			'attributes' => [],
		];
		$settings['resume_listings'][1][] = [
			'name'       => 'cariera_resume_manager_enable_education',
			'std'        => '1',
			'label'      => esc_html__( 'Education', 'cariera-core' ),
			'cb_label'   => esc_html__( 'Enable listing education', 'cariera-core' ),
			'desc'       => esc_html__( 'Allows users select their education when submitting a resume. Note: an admin has to create experience before site users can select them.', 'cariera-core' ),
			'type'       => 'checkbox',
			'attributes' => [],
		];
		$settings['resume_listings'][1][] = [
			'name'       => 'cariera_resume_manager_enable_experience',
			'std'        => '1',
			'label'      => esc_html__( 'Experience', 'cariera-core' ),
			'cb_label'   => esc_html__( 'Enable listing experience', 'cariera-core' ),
			'desc'       => esc_html__( 'Allows users select their experience when submitting a resume. Note: an admin has to create experience before site users can select them.', 'cariera-core' ),
			'type'       => 'checkbox',
			'attributes' => [],
		];
		$settings['resume_listings'][1][] = [
			'name'       => 'cariera_resume_manager_enable_portfolio',
			'std'        => '1',
			'label'      => esc_html__( 'Portfolio', 'cariera-core' ),
			'cb_label'   => esc_html__( 'Enable Candidate Portfolio', 'cariera-core' ),
			'desc'       => esc_html__( 'When enabled, the submission form will include a "gallery" upload field, allowing the candidate\'s portfolio to be displayed on their profile page.', 'cariera-core' ),
			'type'       => 'checkbox',
			'attributes' => [],
		];
		$settings['resume_listings'][1][] = [
			'name'    => 'resume_manager_single_resume_contact_form',
			'std'     => '',
			'label'   => esc_html__( 'Single Resume Contact Form', 'cariera-core' ),
			'desc'    => esc_html__( 'Select the contact form that you want to show on a single resume page. The contact form will show only if the private messages are disabled for resumes.', 'cariera-core' ),
			'type'    => 'select',
			'options' => cariera_get_forms(),
		];
		$settings['resume_listings'][1][] = [
			'name'       => 'cariera_resume_manager_contact_owner',
			'std'        => '0',
			'label'      => esc_html__( 'Owner Contact', 'cariera-core' ),
			'cb_label'   => esc_html__( 'Hide Contact to Owner', 'cariera-core' ),
			'desc'       => esc_html__( 'When enabled the "contact button & form" of the Resume will be hidden from the owner of the Resume. This will avoid Candidates being able to send emails to themselves via their own Resume.', 'cariera-core' ),
			'type'       => 'checkbox',
			'attributes' => [],
		];
		$settings['resume_listings'][1][] = [
			'name'    => 'cariera_resume_manager_single_resume_layout',
			'std'     => 'v1',
			'label'   => esc_html__( 'Single Resume Layout', 'cariera-core' ),
			'desc'    => esc_html__( 'Select the default layout version for your single resume page.', 'cariera-core' ),
			'type'    => 'select',
			'options' => [
				'v1' => esc_html__( 'Version 1', 'cariera-core' ),
				'v2' => esc_html__( 'Version 2', 'cariera-core' ),
				'v3' => esc_html__( 'Version 3', 'cariera-core' ),
			],
		];
		$settings['resume_listings'][1][] = [
			'name'     => 'cariera_resume_manager_related_resumes',
			'std'      => '1',
			'label'    => esc_html__( 'Related Resumes', 'cariera-core' ),
			'cb_label' => esc_html__( 'Enable related listings', 'cariera-core' ),
			'desc'     => esc_html__( 'Show related listings in single listing page.', 'cariera-core' ),
			'type'     => 'checkbox',
		];
		$settings['resume_listings'][1][] = [
			'name'     => 'cariera_resume_manager_featured_resumes',
			'std'      => '1',
			'label'    => esc_html__( 'Featured Resumes', 'cariera-core' ),
			'cb_label' => esc_html__( 'Enable featured listings', 'cariera-core' ),
			'desc'     => esc_html__( 'Show featured listings in single listing page v1.', 'cariera-core' ),
			'type'     => 'checkbox',
		];

		// Email Setting.
		$settings['email_notifications'][1][] = [
			'name'       => 'cariera_resume_manager_approved_resume_notification',
			'std'        => '1',
			'label'      => esc_html__( 'Approved Resume', 'cariera-core' ),
			'cb_label'   => esc_html__( 'Approved Resume Notification', 'cariera-core' ),
			'desc'       => esc_html__( 'When enabled the Candidate will receive an email notification when their resume get\'s approved.', 'cariera-core' ),
			'type'       => 'checkbox',
			'attributes' => [],
		];
		$settings['email_notifications'][1][] = [
			'name'       => 'cariera_resume_manager_expired_resume_notification',
			'std'        => '1',
			'label'      => esc_html__( 'Expired Resume', 'cariera-core' ),
			'cb_label'   => esc_html__( 'Expired Resume Notification', 'cariera-core' ),
			'desc'       => esc_html__( 'When enabled the Candidate will receive an email notification when their resume get\'s expired.', 'cariera-core' ),
			'type'       => 'checkbox',
			'attributes' => [],
		];

		return $settings;
	}
}
