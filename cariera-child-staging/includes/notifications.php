<?php
/**
 * Notifications — email de candidature personnalisé
 */

add_action('plugins_loaded', function() {
    add_filter('job_application_notification_message', 'custom_job_application_notification_message', 99, 5);
});

function custom_job_application_notification_message($message, $application, $job_id, $candidate_name, $candidate_email) {
    $job_title         = get_the_title($job_id);
    $candidate_message = get_post_meta($application->ID, '_candidate_message', true);
    $cv_url            = get_post_meta($application->ID, '_candidate_cv', true);

    ob_start(); ?>
    <html>
    <body style="font-family: Arial, sans-serif; color: #333;">
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:auto;">
            <tr>
                <td style="padding:20px;background-color:#f8f8f8;">
                    <h2 style="color:#2c3e50;">🎉 Nouvelle candidature reçue</h2>
                    <p>Vous avez reçu une nouvelle candidature pour :</p>
                    <h3 style="color:#3498db;"><?php echo esc_html($job_title); ?></h3>
                    <p><strong>Nom :</strong> <?php echo esc_html($candidate_name); ?></p>
                    <p><strong>Email :</strong> <?php echo esc_html($candidate_email); ?></p>
                    <?php if ($candidate_message): ?>
                        <p><strong>Message :</strong><br><?php echo nl2br(esc_html($candidate_message)); ?></p>
                    <?php endif; ?>
                    <?php if ($cv_url): ?>
                        <p><strong>CV :</strong> <a href="<?php echo esc_url($cv_url); ?>" target="_blank">Télécharger</a></p>
                    <?php endif; ?>
                    <p>
                        <a href="<?php echo esc_url(home_url('/dashboard/')); ?>"
                           style="background-color:#3498db;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;">
                            Voir sur le tableau de bord
                        </a>
                    </p>
                </td>
            </tr>
        </table>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
