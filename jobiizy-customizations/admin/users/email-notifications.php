<?php
/**
 * ============================================================
 * JobiiZy – Notifications email inscriptions / validations
 * ============================================================
 */

if (!defined('ABSPATH')) exit;

/* ------------------------------------------------------------
 * HEADER EMAIL
 * ------------------------------------------------------------ */
function jobiizy_email_header() {
    $logo_url = 'https://jobiizy.com/wp-content/uploads/2025/02/LogoaJobIIZYLatest-1.png';

    return '
        <div style="text-align:center;padding:25px 0;background:#ffffff;">
            <img src="'.$logo_url.'" alt="JobiiZy" 
                 style="width:160px;height:auto;display:inline-block;">
        </div>
    ';
}

/* ------------------------------------------------------------
 * FOOTER EMAIL (logo, copyright, lien vers site)
 * ------------------------------------------------------------ */
function jobiizy_email_footer() {
    $site_url = home_url('/');
    $year     = date_i18n('Y');
    $logo_url = 'https://jobiizy.com/wp-content/uploads/2025/02/LogoaJobIIZYLatest-1.png';

    ob_start(); ?>
    
    <div style="border-top:1px solid #eee;padding:15px 20px;background:#ffffff;">

        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="width:100%;margin-bottom:8px;">
            <tr>
                <td style="width:80px;vertical-align:middle;">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="JobiiZy"
                         style="width:70px;height:auto;display:block;">
                </td>
                <td style="vertical-align:middle;font-size:13px;color:#555;">
                    L'équipe de 
                    <a href="<?php echo esc_url($site_url); ?>" target="_blank"
                       style="color:#0058ff;text-decoration:none;">
                        JobiiZy.com
                    </a>
                </td>
            </tr>
        </table>

        <div style="font-size:12px;color:#999;text-align:left;">
            <?php echo esc_html($year); ?> © 
            <a href="<?php echo esc_url($site_url); ?>" target="_blank"
               style="color:#0058ff;text-decoration:none;">
               Jobiizy Emploi francophone
            </a>
            Tous droits réservés.
        </div>

    </div>

    <?php
    return ob_get_clean();
}

/* ------------------------------------------------------------
 * HELPERS
 * ------------------------------------------------------------ */
function jobiizy_get_admin_notification_email() {
    $email = get_option('admin_email');
    return apply_filters('jobiizy_admin_notification_email', $email);
}

function jobiizy_send_html_mail($to, $subject, $body, $type = 'general') {

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $success = wp_mail($to, $subject, $body, $headers);

    // Log dans la DB
    jobiizy_log_email(
        $to,
        $subject,
        $type,
        $success ? 'sent' : 'error',
        $body
    );

    return $success;
}

/* ------------------------------------------------------------
 * 1) NOUVELLE INSCRIPTION : Mail candidat + Mail admin
 * ------------------------------------------------------------ */
add_action('user_register', function($user_id) {

    $user = get_userdata($user_id);
    if (!$user) return;

    if (in_array('administrator', (array) $user->roles, true)) {
        return;
    }

    $role_label = 'Utilisateur';
    if (in_array('candidate', (array) $user->roles, true)) {
        $role_label = 'Candidat';
    } elseif (in_array('employer', (array) $user->roles, true)) {
        $role_label = 'Employeur';
    }


/* --------- A) MAIL AU CANDIDAT --------- */

$subject_user = 'JobiiZy – Votre inscription est en attente de validation';

// Page contact dynamique
$contact_url = home_url('/contact/');

ob_start(); ?>
<div style="font-family:Arial, sans-serif;padding:20px;background:#f5f5f5;">
    <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:6px;overflow:hidden;">

        <?php echo jobiizy_email_header(); ?>

        <div style="background:#0058ff;color:white;padding:20px;font-size:20px;font-weight:bold;">
            Inscription en attente de validation
        </div>

        <div style="padding:20px;font-size:15px;color:#333;">
            <p>Bonjour <strong><?php echo esc_html($user->display_name); ?></strong>,</p>

            <p>
                Merci pour votre inscription en tant que 
                <strong><?php echo esc_html($role_label); ?></strong> sur <strong>JobiiZy</strong>.
            </p>

            <p>
                Votre compte est maintenant <strong>en attente de validation</strong> par notre équipe.
                Vous recevrez un e-mail dès que votre inscription sera approuvée ou refusée.
            </p>

            <p>
                Si vous souhaitez nous transmettre une précision ou poser une question,
                vous pouvez nous contacter ici :
            </p>

            <p style="text-align:center;margin:25px 0;">
                <a href="<?php echo esc_url($contact_url); ?>" 
                   style="background:#0058ff;color:white;padding:12px 28px;
                          text-decoration:none;border-radius:5px;font-weight:bold;
                          font-size:15px;display:inline-block;">
                    📮 Contacter JobiiZy
                </a>
            </p>

            <p>
                Merci d’avoir choisi JobiiZy pour votre recherche d’emploi.
            </p>
        </div>

        <?php echo jobiizy_email_footer(); ?>

    </div>
</div>
<?php
$body_user = ob_get_clean();
jobiizy_send_html_mail($user->user_email, $subject_user, $body_user, 'inscription_candidat');

    /* --------- B) MAIL À L’ADMIN --------- */

    $admin_email = jobiizy_get_admin_notification_email();
    if (!$admin_email) return;

    $subject_admin = 'JobiiZy – Nouvelle inscription à valider';

    $admin_url_users = admin_url('admin.php?page=jobiizy-pending-users');

    ob_start(); ?>
    <div style="font-family:Arial, sans-serif;padding:20px;background:#f5f5f5;">
        <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:6px;overflow:hidden;">

            <?php echo jobiizy_email_header(); ?>

            <div style="background:#222;color:white;padding:20px;font-size:18px;font-weight:bold;">
                Nouvelle inscription à valider
            </div>

            <div style="padding:20px;font-size:14px;color:#333;">
                <p>Un nouvel utilisateur vient de s’inscrire sur <strong>JobiiZy</strong>.</p>
                <p>
                    <strong>Nom :</strong> <?php echo esc_html($user->display_name); ?><br>
                    <strong>Email :</strong> <?php echo esc_html($user->user_email); ?><br>
                    <strong>Rôle :</strong> <?php echo esc_html($role_label); ?><br>
                </p>

                <p style="margin-top:15px;">
                    Vous pouvez valider ou refuser ce compte depuis votre espace d’administration :
                </p>

                <p style="margin-top:10px;text-align:center;">
                    <a href="<?php echo esc_url($admin_url_users); ?>"
                       style="background:#0058ff;color:white;padding:12px 28px;
                              text-decoration:none;border-radius:5px;font-weight:bold;
                              font-size:16px;display:inline-block;">
                        📬 Voir les comptes à valider
                    </a>
                </p>
            </div>

            <?php echo jobiizy_email_footer(); ?>

        </div>
    </div>
    <?php
    $body_admin = ob_get_clean();
    jobiizy_send_html_mail($admin_email, $subject_admin, $body_admin,'inscription_admin');
});


/* ------------------------------------------------------------
 * 2) COMPTE APPROUVÉ – mail au candidat
 * ------------------------------------------------------------ */
add_action('cariera_new_user_approve_approve_user', function($user_id) {

    $user = get_userdata($user_id);
    if (!$user) return;

    if (in_array('administrator', (array) $user->roles, true)) return;

    $role_label = in_array('candidate', $user->roles) ? 'Candidat' :
                 (in_array('employer', $user->roles) ? 'Employeur' : 'Utilisateur');

    // $login_url = wp_login_url();
    $login_url = home_url('/connexion-inscription/'); // https://clone.jobiizy.com/pages/connexion-inscription/

    $subject = 'JobiiZy – Votre compte a été approuvé';

    ob_start(); ?>
    <div style="font-family:Arial, sans-serif;padding:20px;background:#f5f5f5;">
        <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:6px;overflow:hidden;">

            <?php echo jobiizy_email_header(); ?>

            <div style="background:#0a8a0a;color:white;padding:20px;font-size:20px;font-weight:bold;">
                Compte approuvé ✔
            </div>

            <div style="padding:20px;font-size:15px;color:#333;">
                <p>Bonjour <strong><?php echo esc_html($user->display_name); ?></strong>,</p>
                <p>Votre compte a été <strong>approuvé</strong> et vous pouvez maintenant vous connecter :</p>

                <p style="text-align:center;margin-top:20px;">
                    <a href="<?php echo esc_url($login_url); ?>"
                       style="background:#0058ff;color:white;padding:12px 28px;
                              text-decoration:none;border-radius:5px;font-weight:bold;">
                        Se connecter
                    </a>
                </p>

                <p style="margin-top:20px;">Bienvenue sur JobiiZy !</p>
            </div>

            <?php echo jobiizy_email_footer(); ?>

        </div>
    </div>
    <?php
    $body = ob_get_clean();
    jobiizy_send_html_mail($user->user_email, $subject, $body,'validation_approved');
});


/* ------------------------------------------------------------
 * 3) COMPTE REFUSÉ – mail au candidat
 * ------------------------------------------------------------ */
add_action('cariera_new_user_approve_deny_user', function($user_id) {

    $user = get_userdata($user_id);
    if (!$user) return;

    if (in_array('administrator', (array) $user->roles, true)) return;

    $role_label = in_array('candidate', $user->roles) ? 'Candidat' :
                 (in_array('employer', $user->roles) ? 'Employeur' : 'Utilisateur');

    $subject = 'JobiiZy – Votre inscription n’a pas été approuvée';

    // Page contact dynamique (clone ou prod)
    $contact_url = home_url('/contact/');

    ob_start(); ?>
    <div style="font-family:Arial, sans-serif;padding:20px;background:#f5f5f5;">
        <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:6px;overflow:hidden;">

            <?php echo jobiizy_email_header(); ?>

            <div style="background:#c30000;color:white;padding:20px;font-size:20px;font-weight:bold;">
                Inscription non approuvée ❌
            </div>

            <div style="padding:20px;font-size:15px;color:#333;">
                <p>Bonjour <strong><?php echo esc_html($user->display_name); ?></strong>,</p>

                <p>
                    Votre demande d’inscription n’a malheureusement pas été approuvée pour le moment.
                </p>

                <p>
                    Si vous pensez qu’il s’agit d’une erreur ou si vous souhaitez obtenir des précisions,
                    vous pouvez contacter notre équipe en cliquant sur le bouton ci-dessous :
                </p>

                <p style="text-align:center;margin:25px 0;">
                    <a href="<?php echo esc_url($contact_url); ?>" 
                       style="background:#ff4d4d;color:white;padding:12px 28px;
                              text-decoration:none;border-radius:5px;font-weight:bold;
                              font-size:15px;display:inline-block;">
                        📮 Contacter JobiiZy
                    </a>
                </p>

                <p>
                    Nous restons à votre disposition pour toute question.
                </p>
            </div>

            <?php echo jobiizy_email_footer(); ?>

        </div>
    </div>
    <?php
    $body = ob_get_clean();
    jobiizy_send_html_mail($user->user_email, $subject, $body, 'validation_refused');
});